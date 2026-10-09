<?php

namespace App\Services;

use App\Models\BudgetItem;
use App\Models\ExpenseReceiptReview;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ActivityBudgetService
{
    public const POSTED = ['pending_seal', 'verified', 'approved'];

    public function period(?Carbon $date = null): array
    {
        $date ??= now();
        $start = $date->month < 8 ? $date->year - 1 : $date->year;
        return ['academic_year' => $start.'-'.($start + 1), 'semester' => $date->month >= 8 ? '1st Semester' : ($date->month <= 5 ? '2nd Semester' : 'Midyear')];
    }

    public function dates(string $year, string $semester = 'Annual'): array
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $year, $match) || (int) $match[2] !== (int) $match[1] + 1) {
            throw ValidationException::withMessages(['academic_year' => 'Select a valid consecutive academic year.']);
        }
        return match ($semester) {
            '1st Semester' => [$match[1].'-08-01', $match[1].'-12-31'],
            '2nd Semester' => [$match[2].'-01-01', $match[2].'-05-31'],
            'Midyear' => [$match[2].'-06-01', $match[2].'-07-31'],
            'Annual' => [$match[1].'-08-01', $match[2].'-07-31'],
            default => throw ValidationException::withMessages(['semester' => 'Select a valid semester.']),
        };
    }

    public function balance(OrgFundAccount $account): array
    {
        return $this->ledger()->balance($account);
    }

    /**
     * Called inside the activity transaction after the activity row lock and
     * before any non-locking read, so the account lock that serializes
     * approvals and expense posts also gives this check fresh totals.
     */
    public function reserve(OrgActivity $activity): void
    {
        $ledger = $this->ledger();
        $year = $this->period($activity->starts_at)['academic_year'];
        $account = $ledger->lockAccountFor($activity);
        if (! $account) {
            throw ValidationException::withMessages(['budget' => 'Save the organization’s AY '.$year.' opening cash balance in Financial Report before final approval.']);
        }
        $approved = OrganizationCashLedger::cents($activity->approved_budget);
        if ($approved <= 0) {
            throw ValidationException::withMessages(['budget' => 'Enter the activity’s approved budget before final approval.']);
        }
        $snapshot = $ledger->snapshot($account, (int) $activity->id);
        $unspent = max(0, $approved - $ledger->activitySpentCents($activity));
        if ($snapshot['reserved'] + $unspent > $snapshot['cash']) {
            throw ValidationException::withMessages(['budget' => 'The activity allocation of ₱'.number_format($unspent / 100, 2)
                .' exceeds the organization’s available cash of ₱'.number_format(max(0, $snapshot['available']) / 100, 2).' for AY '.$account->fiscal_year.'.']);
        }
        $activity->org_fund_account_id = $account->id;
        $activity->approved_at = now();
        $this->syncBudgetItem($activity);
    }

    private function ledger(): OrganizationCashLedger
    {
        return app(OrganizationCashLedger::class);
    }

    public function syncBudgetItem(OrgActivity $activity): void
    {
        BudgetItem::query()->updateOrCreate(['org_activity_id' => $activity->id], [
            'title' => $activity->title, 'organization_name' => $activity->organization_name,
            'college' => $activity->college, 'category' => 'Activity allocation',
            'scope' => $activity->activity_scope, 'fiscal_year' => $this->period($activity->starts_at)['academic_year'],
            'allocated' => $activity->approved_budget, 'utilized' => $activity->implemented_budget,
            'is_approved' => true,
        ]);
    }

    public function record(OrgActivity $activity, OfficeUser $actor, array $data, UploadedFile $file): ExpenseReceiptReview
    {
        return $this->recordMany($activity, $actor, [['data' => $data, 'file' => $file]])->firstOrFail();
    }

    /**
     * Record a batch atomically. Every line has one item and one or more
     * receipt photos; a failed line rolls back the whole batch so the activity
     * total cannot be partially updated.
     */
    public function recordMany(OrgActivity $activity, OfficeUser $actor, array $entries)
    {
        $paths = [];
        try {
            return DB::connection('mysql')->transaction(function () use ($activity, $actor, $entries, &$paths) {
                $activity = OrgActivity::query()->lockForUpdate()->findOrFail($activity->id);
                if ($activity->workflow_status !== 'oc_approved') {
                    throw ValidationException::withMessages(['activity' => 'Select a final-approved activity.']);
                }
                $ledger = $this->ledger();
                $year = $this->period($activity->starts_at)['academic_year'];
                $account = $ledger->lockAccountFor($activity);
                if (! $account) {
                    throw ValidationException::withMessages(['budget' => 'Save the organization’s AY '.$year.' opening cash balance in Financial Report before recording expenses.']);
                }
                [$fiscalStart, $fiscalEnd] = $this->dates($account->fiscal_year);
                $receipts = collect();
                $storedAttachmentsByHash = [];
                foreach ($entries as $entry) {
                    $data = $entry['data'];
                    $files = $entry['files'] ?? [];
                    if ($files instanceof UploadedFile) $files = [$files];
                    if (! is_array($files) || count($files) === 0) {
                        $files = isset($entry['file']) && $entry['file'] instanceof UploadedFile
                            ? [$entry['file']]
                            : [];
                    }
                    if (count($files) === 0) {
                        throw ValidationException::withMessages(['receipt' => 'Attach at least one receipt photo for every expense item.']);
                    }
                    $file = $files[0];
                    $fileHash = hash_file('sha256', $file->getRealPath());
                    $prior = ExpenseReceiptReview::query()->where('org_activity_id', $activity->id)
                        ->where(function ($q) use ($data, $fileHash) {
                        $q->where('request_key', $data['request_key'])
                            ->orWhere(function ($r) use ($data, $fileHash) {
                                // The same paper/e-wallet receipt may list
                                // several purchased items. Only treat a hash
                                // as a retry when the encoded line is also the
                                // same; different item rows may share photos.
                                $r->where('file_hash', $fileHash)
                                    ->where('item_name', trim($data['item_name']))
                                    ->where('receipt_reference', trim($data['receipt_reference']))
                                    ->where('supplier', trim($data['supplier'] ?? ''))
                                    ->where('quantity', (int) $data['quantity'])
                                    ->where('unit_cost', number_format((float) $data['unit_cost'], 2, '.', ''));
                            });
                        })->first();
                    if ($prior) {
                        $receipts->push($prior); // Browser retries never charge the budget again.
                        continue;
                    }
                    if (ExpenseReceiptReview::query()->where('request_key', $data['request_key'])->exists()) {
                        throw ValidationException::withMessages(['receipt' => 'This expense form was already submitted for another activity. Reload the page before recording it again.']);
                    }
                    $expenseDate = Carbon::parse($data['expense_date'])->toDateString();
                    if ($expenseDate < $fiscalStart || $expenseDate > $fiscalEnd || $expenseDate > now()->toDateString()) {
                        throw ValidationException::withMessages(['expense_date' => 'Expense dates must fall within AY '.$account->fiscal_year.' ('.$fiscalStart.' to '.$fiscalEnd.') and cannot be in the future.']);
                    }
                    $unitCents = OrganizationCashLedger::cents($data['unit_cost']);
                    $cents = $unitCents * (int) $data['quantity'];
                    if ($unitCents <= 0 || (int) $data['quantity'] < 1) {
                        throw ValidationException::withMessages(['unit_cost' => 'Enter a positive quantity and unit cost.']);
                    }
                    $activitySpent = $ledger->activitySpentCents($activity);
                    $activityRemaining = OrganizationCashLedger::cents($activity->approved_budget) - $activitySpent;
                    if ($cents > $activityRemaining || $cents > $ledger->snapshot($account)['cash']) {
                        throw ValidationException::withMessages(['unit_cost' => 'The batch exceeds the remaining activity budget or organization cash balance.']);
                    }
                    $extension = strtolower($file->getClientOriginalExtension());
                    if ($extension === 'docx' || $file->getMimeType() === 'application/pdf') {
                        $document = app(ReceiptDocumentValidator::class)->validate($file);
                        if (! $document['valid']) {
                            throw ValidationException::withMessages(['receipt' => $document['message']]);
                        }
                    }
                    $scanMetadata = [
                        'receipt_scan_id' => null,
                        'ocr_quality' => null,
                        'ocr_confidence' => null,
                        'ocr_corrections' => null,
                    ];
                    $attachments = [];
                    foreach ($files as $attachmentFile) {
                        $attachmentHash = hash_file('sha256', $attachmentFile->getRealPath());
                        if (isset($storedAttachmentsByHash[$attachmentHash])) {
                            $attachments[] = $storedAttachmentsByHash[$attachmentHash];
                            continue;
                        }
                        $attachmentPath = $attachmentFile->store('expense-receipts/'.$activity->id, 'local');
                        if (! $attachmentPath) throw new \RuntimeException('A receipt photo could not be stored. No expense was recorded.');
                        $paths[] = $attachmentPath;
                        $storedAttachmentsByHash[$attachmentHash] = [
                            'path' => $attachmentPath,
                            'disk' => 'local',
                            'name' => $attachmentFile->getClientOriginalName(),
                            'hash' => $attachmentHash,
                            'size' => $attachmentFile->getSize(),
                            'mime' => $attachmentFile->getMimeType(),
                        ];
                        $attachments[] = $storedAttachmentsByHash[$attachmentHash];
                    }
                    $primary = $attachments[0];
                    $receipt = ExpenseReceiptReview::query()->create([
                        'org_activity_id' => $activity->id, 'activity_title' => $activity->title,
                        'organization_name' => $activity->organization_name, 'uploaded_by' => $actor->id,
                        'request_key' => $data['request_key'], 'file_hash' => $fileHash,
                        'item_name' => $data['item_name'], 'category' => $data['category'] ?? null,
                        'supplier' => trim($data['supplier'] ?? ''), 'quantity' => $data['quantity'],
                        'unit_cost' => OrganizationCashLedger::decimal($unitCents), 'expense_date' => $expenseDate,
                        'receipt_reference' => trim($data['receipt_reference']),
                        'receipt_path' => $primary['path'], 'receipt_disk' => 'local', 'receipt_name' => $primary['name'],
                        'receipt_attachments' => $attachments,
                        ...$scanMetadata,
                        'receipt_type' => $data['receipt_type'] ?? 'unknown', 'payment_method' => $data['payment_method'] ?? 'unknown',
                        'student_confirmed' => true, 'verification_status' => 'pending_seal',
                    ]);
                    $activity->implemented_budget = OrganizationCashLedger::decimal($activitySpent + $cents);
                    $activity->org_fund_account_id = $account->id;
                    $activity->save();
                    $this->syncBudgetItem($activity);
                    $receipts->push($receipt);
                }
                return $receipts;
            });
        } catch (\Throwable $exception) {
            foreach ($paths as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function seal(ExpenseReceiptReview $receipt): bool
    {
        // Keep a failed/slow blockchain request recoverable. The saved receipt
        // and its one-time debit survive; only anchor metadata changes on retry.
        return DB::connection('mysql')->transaction(function () use ($receipt) {
            $receipt = ExpenseReceiptReview::query()->lockForUpdate()->findOrFail($receipt->id);
            if ($receipt->verification_status !== 'pending_seal') return true;
            try {
                $seal = app(BudgetChainService::class)->sealExpense($receipt->toArray() + [
                    'total' => round((float) $receipt->unit_cost * $receipt->quantity, 2),
                ]);
                $expected = ($seal['chain_driver'] ?? 'file') === 'besu' ? (int) config('besu.validator_count', 4) : BudgetChainService::NODE_COUNT;
                if (($seal['nodes_confirmed'] ?? 0) < $expected) throw new \RuntimeException('Incomplete node confirmation.');
                $receipt->fill([
                    'chain_hash' => $seal['block_hash'], 'previous_hash' => $seal['previous_hash'],
                    'nodes_confirmed' => $seal['nodes_confirmed'], 'chain_driver' => $seal['chain_driver'] ?? 'file',
                    'chain_tx_hash' => $seal['chain_tx_hash'] ?? null, 'chain_block_number' => $seal['chain_block_number'] ?? null,
                    'chain_contract_address' => $seal['chain_contract_address'] ?? null,
                    'verification_status' => 'verified',
                ])->save();
                return true;
            } catch (\Throwable $exception) {
                Log::warning('Receipt anchor pending', ['receipt_id' => $receipt->id, 'reason' => $exception->getMessage()]);
                return false;
            }
        });
    }

    public function receipts(?string $organization = null)
    {
        return ExpenseReceiptReview::query()->whereIn('verification_status', self::POSTED)
            ->whereIn('org_activity_id', OrgActivity::query()->visibleToStudents()->select('id'))
            ->when(filled($organization), fn ($q) => $q->where('organization_name', $organization));
    }

    public function financial(string $organization, string $year, string $semester): array
    {
        [$from, $to] = $this->dates($year, $semester);
        $receipts = $this->receipts($organization)->whereBetween('expense_date', [$from, $to])->orderBy('expense_date')->orderBy('id')->get();
        $accounts = OrgFundAccount::query()->where('fiscal_year', $year)
            ->when($organization !== '', fn ($q) => $q->where('organization_name', $organization))->get();
        return [
            'receiptRows' => $receipts, 'accountBalances' => $accounts->map(fn ($a) => $this->balance($a)),
            'periodExpenseTotal' => round($receipts->sum(fn ($r) => (float) $r->unit_cost * $r->quantity), 2),
            'fromDate' => $from, 'toDate' => $to,
        ];
    }
}
