<?php

namespace App\Services;

use App\Models\ExpenseReceiptReview;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgCashIncome;
use App\Models\OrgFundAccount;
use App\Models\StudentOrganization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Authoritative organization cash for one annual fund account:
 *
 *   cash = saved opening + recorded income − posted receipts − legacy spending gap
 *
 * Posted ExpenseReceiptReview rows are the canonical expense postings; an
 * activity's legacy gap is max(0, implemented_budget − its posted receipts),
 * so pre-receipt spending survives without counting receipts twice. Approved
 * activities reserve their unspent allocation; reservation is not an outflow.
 * Uploaded Financial Report workbooks are never read here.
 *
 * All money is handled in integer cents. Every write holds the fund-account
 * row lock; read methods never write.
 */
class OrganizationCashLedger
{
    public const SEMESTERS = ['1st Semester', '2nd Semester', 'Midyear'];

    public const GENERAL_FUND = 'General organization fund';

    public const LEGACY_SPENDING = 'Legacy spending';

    private const AMOUNT = '/^\d{1,14}(?:\.\d{1,2})?$/';

    public function __construct(private readonly ActivityBudgetService $budgets) {}

    /**
     * @return array{account_id: int, organization: string, year: string, total: float, allocated: float, spent: float, reserved: float, cash: float, available: float, opening_balance: float, income: float}
     */
    public function balance(OrgFundAccount $account): array
    {
        $snapshot = $this->snapshot($account);

        return [
            'account_id' => $account->id,
            'organization' => $account->organization_name,
            'year' => $account->fiscal_year,
            'total' => self::money($snapshot['opening'] + $snapshot['income']),
            'allocated' => self::money($snapshot['allocated']),
            'spent' => self::money($snapshot['spent']),
            'reserved' => self::money($snapshot['reserved']),
            'cash' => self::money($snapshot['cash']),
            'available' => self::money($snapshot['available']),
            'opening_balance' => self::money($snapshot['opening']),
            'income' => self::money($snapshot['income']),
        ];
    }

    /**
     * @return array{academic_year: string, has_account: bool, opening_balance: float, income: float, spent: float, allocated: float, reserved: float, cash: float, available: float}
     */
    public function funding(string $organization, string $year): array
    {
        $this->budgets->dates($year);
        $account = $this->accounts($organization)->where('fiscal_year', $year)->first();
        $balance = $account ? $this->balance($account) : null;

        return [
            'academic_year' => $year,
            'has_account' => $account !== null,
            'opening_balance' => $balance['opening_balance'] ?? 0.0,
            'income' => $balance['income'] ?? 0.0,
            'spent' => $balance['spent'] ?? 0.0,
            'allocated' => $balance['allocated'] ?? 0.0,
            'reserved' => $balance['reserved'] ?? 0.0,
            'cash' => $balance['cash'] ?? 0.0,
            'available' => $balance['available'] ?? 0.0,
        ];
    }

    /**
     * Cash statement for the organization's saved annual accounts. Each
     * account is independent: no balance is carried between academic years.
     * A semester opens at the annual opening plus that account's earlier
     * postings. Postings dated outside the account's academic year stay on
     * the account: earlier dates book into the 1st Semester, later into Midyear.
     *
     * @param  array{academic_year?: string, semester?: string}  $filters  'all' or a value
     * @return array{beginning_balance: float, cash_inflow: float, cash_outflow: float, ending_balance: float, activities: list<array{name: string, inflow: float, outflow: float}>, years: list<string>, selected: Collection<int, array{academic_year: string, semester: string, summary: array<string, mixed>}>}
     */
    public function cashFlow(string $organization, array $filters): array
    {
        return $this->cashFlows([$organization], $filters)[$organization];
    }

    /**
     * The same statements as cashFlow(), with account postings loaded in
     * batches so an institutional overview does not query once per office.
     *
     * @param  list<string>  $organizations
     * @param  array{academic_year?: string, semester?: string}  $filters
     * @return array<string, array>
     */
    public function cashFlows(array $organizations, array $filters): array
    {
        $year = (string) ($filters['academic_year'] ?? 'all');
        $semester = (string) ($filters['semester'] ?? 'all');
        if ($year !== 'all') {
            $this->budgets->dates($year);
        }
        if ($semester !== 'all' && ! in_array($semester, self::SEMESTERS, true)) {
            throw ValidationException::withMessages(['semester' => 'Select a valid semester.']);
        }

        $organizations = array_values(array_unique($organizations));
        $accounts = $organizations === [] ? collect() : OrgFundAccount::query()
            ->whereIn('organization_name', $organizations)->orderBy('id')->get()
            ->filter(fn (OrgFundAccount $account): bool => $this->isAcademicYear((string) $account->fiscal_year))
            ->unique(fn (OrgFundAccount $account): string => $account->organization_name."\0".$account->fiscal_year)
            ->sortBy('fiscal_year')->values();
        $selectedAccounts = $year === 'all' ? $accounts : $accounts->where('fiscal_year', $year);
        $books = $this->books($selectedAccounts);
        $accountsByOrganization = $accounts->groupBy('organization_name');
        $statements = [];
        foreach ($organizations as $organization) {
            $statements[$organization] = $this->statement(
                $accountsByOrganization->get($organization, collect()), $books, $year, $semester
            );
        }

        return $statements;
    }

    private function statement(Collection $accounts, array $books, string $year, string $semester): array
    {
        $periods = $semester === 'all' ? self::SEMESTERS : [$semester];
        $selected = collect();
        $beginning = $inflow = $outflow = 0;
        $activities = [];

        foreach ($accounts as $account) {
            if ($year !== 'all' && $account->fiscal_year !== $year) {
                continue;
            }
            $book = $books[$account->id];
            $running = $book['opening'];
            $opened = false;
            foreach (self::SEMESTERS as $period) {
                $rows = $book['periods'][$period];
                $in = array_sum(array_column($rows['inflow'], 'cents'));
                $out = array_sum(array_column($rows['outflow'], 'cents'));
                if (in_array($period, $periods, true)) {
                    if (! $opened) {
                        $beginning += $running;
                        $opened = true;
                    }
                    $inflow += $in;
                    $outflow += $out;
                    $this->mergeCents($activities, $rows['inflow'], 'inflow');
                    $this->mergeCents($activities, $rows['outflow'], 'outflow');
                    $selected->push([
                        'academic_year' => (string) $account->fiscal_year,
                        'semester' => $period,
                        'summary' => $this->summary($running, $rows),
                    ]);
                }
                $running += $in - $out;
            }
        }

        return [
            'beginning_balance' => self::money($beginning),
            'cash_inflow' => self::money($inflow),
            'cash_outflow' => self::money($outflow),
            'ending_balance' => self::money($beginning + $inflow - $outflow),
            'activities' => collect($this->activityTotals($activities))
                ->sortByDesc(fn (array $activity): float => $activity['inflow'] + $activity['outflow'])
                ->values()
                ->all(),
            'years' => $accounts->pluck('fiscal_year')->map(fn ($fy): string => (string) $fy)->sortDesc()->values()->all(),
            'selected' => $selected,
        ];
    }

    /**
     * Final-approved activities an income record may name for this AY. Empty
     * until the year's opening balance is saved, matching recordIncome().
     *
     * @return Collection<int, OrgActivity>
     */
    public function incomeActivities(string $organization, string $year): Collection
    {
        $this->budgets->dates($year);
        $account = $this->accounts($organization)->where('fiscal_year', $year)->first();
        if (! $account) {
            return collect();
        }

        return $this->activities($account)->where('workflow_status', 'oc_approved')
            ->orderBy('starts_at')->orderBy('id')->get(['id', 'title', 'starts_at']);
    }

    public function saveOpening(OfficeUser $actor, string $year, string $amount): OrgFundAccount
    {
        $organization = $this->actorOrganization($actor);
        $this->budgets->dates($year);
        $cents = $this->inputCents($amount, 'opening_balance', false);

        return DB::connection('mysql')->transaction(function () use ($organization, $year, $cents): OrgFundAccount {
            // The organization row serializes account creation; org/year has
            // no unique index because legacy duplicates are preserved.
            StudentOrganization::on('mysql')->whereKey($organization->id)->lockForUpdate()->firstOrFail();
            $account = $this->lockAccount($organization->name, $year);
            if (! $account) {
                return OrgFundAccount::query()->create([
                    'organization_name' => $organization->name,
                    'college' => $organization->college,
                    'fiscal_year' => $year,
                    'total_funds' => 0,
                    'beginning_balance' => 0,
                    'total_funds_received' => 0,
                    'cash_opening_balance' => self::decimal($cents),
                ]);
            }

            $snapshot = $this->snapshot($account);
            $floor = max(0, $snapshot['spent'] + $snapshot['reserved'] - $snapshot['income']);
            if ($cents < $floor) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'The AY '.$year.' opening balance cannot be lower than ₱'.number_format($floor / 100, 2)
                        .'. Recorded spending and approved activity reservations already use that amount.',
                ]);
            }
            $account->forceFill(['cash_opening_balance' => self::decimal($cents)])->save();

            return $account;
        });
    }

    /**
     * @param  array{academic_year?: mixed, transaction_date?: mixed, amount?: mixed, purpose?: mixed, received_from?: mixed, reference?: mixed, request_key?: mixed, org_activity_id?: mixed}  $data
     */
    public function recordIncome(OfficeUser $actor, array $data): OrgCashIncome
    {
        $organization = $this->actorOrganization($actor);
        $validated = Validator::make($data, [
            'academic_year' => ['required', 'string'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required'],
            'purpose' => ['required', 'string', 'max:255'],
            'received_from' => ['required', 'string', 'max:255'],
            'reference' => ['required', 'string', 'max:120'],
            'request_key' => ['required', 'uuid'],
            'org_activity_id' => ['nullable', 'integer'],
        ], [], [
            'transaction_date' => 'date',
            'received_from' => 'received from',
            'org_activity_id' => 'activity',
        ])->validate();

        $year = (string) $validated['academic_year'];
        [$from, $to] = $this->budgets->dates($year);
        $date = (string) $validated['transaction_date'];
        if ($date < $from || $date > $to) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Use a date within AY '.$year.' ('.$from.' to '.$to.').',
            ]);
        }
        if ($date > now()->toDateString()) {
            throw ValidationException::withMessages(['transaction_date' => 'The transaction date cannot be in the future.']);
        }
        $payload = [
            'transaction_date' => $date,
            'cents' => $this->inputCents($validated['amount'], 'amount', true),
            'purpose' => $this->text($validated['purpose']),
            'received_from' => $this->text($validated['received_from']),
            'reference' => $this->text($validated['reference']),
            'org_activity_id' => isset($validated['org_activity_id']) ? (int) $validated['org_activity_id'] : null,
        ];
        $requestKey = strtolower((string) $validated['request_key']);

        try {
            return DB::connection('mysql')->transaction(function () use ($organization, $actor, $year, $payload, $requestKey): OrgCashIncome {
                if ($payload['org_activity_id'] !== null) {
                    // Income's activity FK must follow the same lock order as
                    // approvals and receipt posts: activity before account.
                    $activity = OrgActivity::query()->whereKey($payload['org_activity_id'])
                        ->where('organization_name', $organization->name)->lockForUpdate()->first(['id']);
                    if (! $activity) {
                        throw ValidationException::withMessages([
                            'org_activity_id' => 'Select one of your organization’s final-approved activities for AY '.$year.'.',
                        ]);
                    }
                }

                $account = $this->lockAccount($organization->name, $year);
                if (! $account) {
                    throw ValidationException::withMessages([
                        'academic_year' => 'Save the AY '.$year.' opening cash balance in Financial Report before recording cash inflows.',
                    ]);
                }

                $existing = OrgCashIncome::query()->where('request_key', $requestKey)->first();
                if ($existing) {
                    if ($this->sameIncome($existing, $account, $payload)) {
                        return $existing;
                    }
                    throw ValidationException::withMessages([
                        'request_key' => 'This cash inflow form was already submitted with different details. Reload the form before recording another inflow.',
                    ]);
                }
                if (OrgCashIncome::query()->where('org_fund_account_id', $account->id)->where('reference', $payload['reference'])->exists()) {
                    throw ValidationException::withMessages([
                        'reference' => 'Reference '.$payload['reference'].' is already recorded for AY '.$year.'. Each cash inflow is credited once.',
                    ]);
                }
                if ($payload['org_activity_id'] !== null && ! $this->activities($account)
                    ->where('workflow_status', 'oc_approved')->whereKey($payload['org_activity_id'])->exists()) {
                    throw ValidationException::withMessages([
                        'org_activity_id' => 'Select one of your organization’s final-approved activities for AY '.$year.'.',
                    ]);
                }

                return OrgCashIncome::query()->create([
                    'org_fund_account_id' => $account->id,
                    'organization_name' => $account->organization_name,
                    'org_activity_id' => $payload['org_activity_id'],
                    'transaction_date' => $payload['transaction_date'],
                    'amount' => self::decimal($payload['cents']),
                    'purpose' => $payload['purpose'],
                    'received_from' => $payload['received_from'],
                    'reference' => $payload['reference'],
                    'request_key' => $requestKey,
                    'recorded_by' => $actor->id,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'reference' => 'This cash inflow is already recorded. Reload the Financial Report to see it.',
            ]);
        }
    }

    /**
     * Canonical (oldest) annual account, row-locked. Call before any
     * non-locking read so later reads see every committed posting.
     */
    public function lockAccount(string $organization, string $year): ?OrgFundAccount
    {
        return $this->accounts($organization)->where('fiscal_year', $year)->lockForUpdate()->first();
    }

    /**
     * The account an activity posts to: its linked account, otherwise the
     * canonical account for the academic year of its start date.
     */
    public function lockAccountFor(OrgActivity $activity): ?OrgFundAccount
    {
        if ($activity->org_fund_account_id) {
            return OrgFundAccount::query()->whereKey($activity->org_fund_account_id)->lockForUpdate()->first();
        }

        return $this->lockAccount(
            (string) $activity->organization_name,
            $this->budgets->period($activity->starts_at)['academic_year']
        );
    }

    /**
     * Activities whose spending belongs to the account: linked activities,
     * plus unlinked final-approved activities of the organization that start
     * within the account's academic year.
     */
    public function activities(OrgFundAccount $account): Builder
    {
        [$from, $to] = $this->budgets->dates((string) $account->fiscal_year);

        return OrgActivity::query()->where(function (Builder $query) use ($account, $from, $to): void {
            $query->where('org_fund_account_id', $account->id)->orWhere(function (Builder $legacy) use ($account, $from, $to): void {
                $legacy->whereNull('org_fund_account_id')
                    ->where('workflow_status', 'oc_approved')
                    ->where('organization_name', $account->organization_name)
                    ->whereBetween('starts_at', [$from.' 00:00:00', $to.' 23:59:59']);
            });
        });
    }

    /**
     * Account totals in cents. $excludeReservationOf leaves that activity's
     * allocation and reservation out (its spending still counts).
     *
     * @return array{opening: int, income: int, spent: int, allocated: int, reserved: int, cash: int, available: int}
     */
    public function snapshot(OrgFundAccount $account, ?int $excludeReservationOf = null): array
    {
        $activities = $this->activities($account)->get(['id', 'workflow_status', 'approved_budget', 'implemented_budget']);
        $receipts = $this->receiptCents($activities->pluck('id'));
        $spent = $allocated = $reserved = 0;
        foreach ($activities as $activity) {
            $activitySpent = max(self::cents($activity->implemented_budget), $receipts[$activity->id] ?? 0);
            $spent += $activitySpent;
            if ((int) $activity->id === $excludeReservationOf || $activity->workflow_status !== 'oc_approved') {
                continue;
            }
            $approved = self::cents($activity->approved_budget);
            $allocated += $approved;
            $reserved += max(0, $approved - $activitySpent);
        }

        $opening = self::cents(OrgFundAccount::query()->whereKey($account->id)->value('cash_opening_balance') ?? $account->cash_opening_balance);
        $income = self::cents(OrgCashIncome::query()->where('org_fund_account_id', $account->id)->sum('amount'));
        $cash = $opening + $income - $spent;

        return [
            'opening' => $opening,
            'income' => $income,
            'spent' => $spent,
            'allocated' => $allocated,
            'reserved' => $reserved,
            'cash' => $cash,
            'available' => $cash - $reserved,
        ];
    }

    /** Posted receipts plus any legacy gap for one activity, in cents. */
    public function activitySpentCents(OrgActivity $activity): int
    {
        return max(self::cents($activity->implemented_budget), $this->receiptCents(collect([$activity->id]))[$activity->id] ?? 0);
    }

    /** Exact cents from a decimal string, integer pesos, or a float. */
    public static function cents(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_int($value)) {
            return $value * 100;
        }
        $text = is_float($value) ? number_format($value, 2, '.', '') : trim((string) $value);
        if (! preg_match('/^([+-]?)(\d*)(?:\.(\d*))?$/', $text, $match) || ($match[2] === '' && ($match[3] ?? '') === '')) {
            throw new InvalidArgumentException('Invalid money amount: '.$text);
        }
        $thousandths = (int) str_pad(substr($match[3] ?? '', 0, 3), 3, '0');
        $cents = (int) ($match[2] === '' ? '0' : $match[2]) * 100 + intdiv($thousandths + 5, 10);

        return $match[1] === '-' ? -$cents : $cents;
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function money(int $cents): float
    {
        return round($cents / 100, 2);
    }

    private function accounts(string $organization): Builder
    {
        return OrgFundAccount::query()->where('organization_name', $organization)->orderBy('id');
    }

    private function actorOrganization(OfficeUser $actor): StudentOrganization
    {
        $organization = $actor->office_role === 'so' && $actor->is_active && $actor->student_organization_id
            ? StudentOrganization::on('mysql')->find($actor->student_organization_id)
            : null;
        if (! $organization || trim((string) $organization->name) === '') {
            throw new AuthorizationException('Only a Student Organization account assigned to an organization can manage its cash.');
        }

        return $organization;
    }

    /**
     * @param  Collection<int, mixed>  $activityIds
     * @return array<int, int>
     */
    private function receiptCents(Collection $activityIds): array
    {
        if ($activityIds->isEmpty()) {
            return [];
        }

        return ExpenseReceiptReview::query()
            ->whereIn('org_activity_id', $activityIds->all())
            ->whereIn('verification_status', ActivityBudgetService::POSTED)
            ->groupBy('org_activity_id')
            ->selectRaw('org_activity_id, SUM(quantity * unit_cost) AS total')
            ->pluck('total', 'org_activity_id')
            ->map(fn ($total): int => self::cents($total))
            ->all();
    }

    /**
     * Load each selected account's postings once, retaining the exact linked
     * and unlinked legacy activity ownership rules used by activities().
     *
     * @param  Collection<int, OrgFundAccount>  $accounts
     * @return array<int, array>
     */
    private function books(Collection $accounts): array
    {
        if ($accounts->isEmpty()) {
            return [];
        }

        $accountIds = $accounts->pluck('id')->all();
        $accountsById = $accounts->keyBy('id');
        $canonical = [];
        foreach ($accounts as $account) {
            $canonical[$account->organization_name][$account->fiscal_year] = $account->id;
        }
        $from = $this->budgets->dates((string) $accounts->min('fiscal_year'))[0];
        $to = $this->budgets->dates((string) $accounts->max('fiscal_year'))[1];
        $activities = OrgActivity::query()->where(function (Builder $query) use ($accountIds, $accounts, $from, $to): void {
            $query->whereIn('org_fund_account_id', $accountIds)->orWhere(function (Builder $legacy) use ($accounts, $from, $to): void {
                $legacy->whereNull('org_fund_account_id')->where('workflow_status', 'oc_approved')
                    ->whereIn('organization_name', $accounts->pluck('organization_name')->unique()->all())
                    ->whereBetween('starts_at', [$from.' 00:00:00', $to.' 23:59:59']);
            });
        })->orderBy('id')->get(['id', 'title', 'starts_at', 'implemented_budget', 'org_fund_account_id', 'organization_name']);
        $activitiesByAccount = [];
        foreach ($activities as $activity) {
            $accountId = $activity->org_fund_account_id
                ?? ($canonical[$activity->organization_name][$this->budgets->period($activity->starts_at)['academic_year']] ?? null);
            if ($accountId !== null && $accountsById->has($accountId)) {
                $activitiesByAccount[$accountId][] = $activity;
            }
        }
        $incomes = OrgCashIncome::query()->whereIn('org_fund_account_id', $accountIds)
            ->orderBy('transaction_date')->orderBy('id')->get();
        $incomesByAccount = $incomes->groupBy('org_fund_account_id');
        $incomeTitles = OrgActivity::query()
            ->whereIn('id', $incomes->pluck('org_activity_id')->filter()->unique()->all())->pluck('title', 'id');
        $receipts = $activities->isEmpty() ? collect() : ExpenseReceiptReview::query()
            ->whereIn('org_activity_id', $activities->pluck('id')->all())
            ->whereIn('verification_status', ActivityBudgetService::POSTED)
            ->orderBy('expense_date')->orderBy('id')->get()->groupBy('org_activity_id');

        $books = [];
        foreach ($accounts as $account) {
            $ownedActivities = collect($activitiesByAccount[$account->id] ?? []);
            $ownedReceipts = collect();
            foreach ($ownedActivities as $activity) {
                foreach ($receipts->get($activity->id, collect()) as $receipt) {
                    $ownedReceipts->push($receipt);
                }
            }
            $books[$account->id] = $this->book(
                $account, $ownedActivities, $incomesByAccount->get($account->id, collect()), $incomeTitles, $ownedReceipts
            );
        }

        return $books;
    }

    /**
     * Every posting of one account as workbook cash rows, bucketed by semester.
     *
     * @return array{opening: int, periods: array<string, array{inflow: list<array<string, mixed>>, outflow: list<array<string, mixed>>}>}
     */
    private function book(
        OrgFundAccount $account,
        Collection $activities,
        Collection $incomes,
        Collection $incomeTitles,
        Collection $receipts,
    ): array {
        $secondStart = $this->budgets->dates((string) $account->fiscal_year, '2nd Semester')[0];
        $midyearStart = $this->budgets->dates((string) $account->fiscal_year, 'Midyear')[0];
        $periodOf = fn (?string $date): string => match (true) {
            $date === null || $date < $secondStart => '1st Semester',
            $date < $midyearStart => '2nd Semester',
            default => 'Midyear',
        };

        $titles = $activities->pluck('title', 'id');
        $postings = [];

        foreach ($incomes as $income) {
            $cents = self::cents($income->amount);
            $postings[] = ['type' => 'inflow', 'sort' => [$income->transaction_date?->toDateString() ?? '', 0, $income->id], 'cents' => $cents, 'row' => [
                'row' => null,
                'date' => $income->transaction_date?->toDateString(),
                'event' => (string) ($income->org_activity_id ? ($incomeTitles[$income->org_activity_id] ?? self::GENERAL_FUND) : self::GENERAL_FUND),
                'reference' => (string) $income->reference,
                'payee' => (string) $income->received_from,
                'account' => 'Organization income',
                'description' => (string) $income->purpose,
                'quantity' => 1.0,
                'unit_cost' => self::money($cents),
                'amount' => self::money($cents),
                'ref' => 'INC-'.$income->id,
            ]];
        }

        $receiptTotals = [];
        foreach ($receipts as $receipt) {
            $cents = self::cents($receipt->unit_cost) * (int) $receipt->quantity;
            $receiptTotals[$receipt->org_activity_id] = ($receiptTotals[$receipt->org_activity_id] ?? 0) + $cents;
            $postings[] = ['type' => 'outflow', 'sort' => [$receipt->expense_date?->toDateString() ?? '', 1, $receipt->id], 'cents' => $cents, 'row' => [
                'row' => null,
                'date' => $receipt->expense_date?->toDateString(),
                'event' => (string) ($titles[$receipt->org_activity_id] ?? $receipt->activity_title),
                'reference' => (string) $receipt->receipt_reference,
                'payee' => (string) $receipt->supplier,
                'account' => (string) ($receipt->category ?: 'General'),
                'description' => (string) $receipt->item_name,
                'quantity' => (float) $receipt->quantity,
                'unit_cost' => self::money(self::cents($receipt->unit_cost)),
                'amount' => self::money($cents),
                'ref' => 'EXP-'.$receipt->id,
            ]];
        }

        foreach ($activities as $activity) {
            $gap = self::cents($activity->implemented_budget) - ($receiptTotals[$activity->id] ?? 0);
            if ($gap <= 0) {
                continue;
            }
            $date = $activity->starts_at?->toDateString();
            $postings[] = ['type' => 'outflow', 'sort' => [$date ?? '', 2, $activity->id], 'cents' => $gap, 'row' => [
                'row' => null,
                'date' => $date,
                'event' => (string) $activity->title,
                'reference' => '',
                'payee' => '',
                'account' => self::LEGACY_SPENDING,
                'description' => 'Legacy spending recorded before itemized receipts',
                'quantity' => 1.0,
                'unit_cost' => self::money($gap),
                'amount' => self::money($gap),
                'ref' => 'LEGACY-'.$activity->id,
            ]];
        }

        usort($postings, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);
        $periods = array_fill_keys(self::SEMESTERS, ['inflow' => [], 'outflow' => []]);
        foreach ($postings as $posting) {
            $periods[$periodOf($posting['row']['date'])][$posting['type']][] = ['cents' => $posting['cents'], 'row' => $posting['row']];
        }

        return [
            'opening' => self::cents($account->cash_opening_balance),
            'periods' => $periods,
        ];
    }

    /**
     * Summary in the uploaded-workbook schema read by FinancialWorkbookService::export().
     *
     * @param  array{inflow: list<array{cents: int, row: array<string, mixed>}>, outflow: list<array{cents: int, row: array<string, mixed>}>}  $rows
     * @return array<string, mixed>
     */
    private function summary(int $beginning, array $rows): array
    {
        $in = array_sum(array_column($rows['inflow'], 'cents'));
        $out = array_sum(array_column($rows['outflow'], 'cents'));
        $activities = [];
        $this->mergeCents($activities, $rows['inflow'], 'inflow');
        $this->mergeCents($activities, $rows['outflow'], 'outflow');

        return [
            'beginning_balance' => self::money($beginning),
            'cash_inflow' => self::money($in),
            'cash_outflow' => self::money($out),
            'ending_balance' => self::money($beginning + $in - $out),
            'inflow_source' => 'cash_receipts',
            'outflow_source' => 'cash_disbursements',
            'activities' => $this->activityTotals($activities),
            'receipts' => array_column($rows['inflow'], 'row'),
            'disbursements' => array_column($rows['outflow'], 'row'),
        ];
    }

    /**
     * @param  array<string, array{name: string, inflow: int, outflow: int}>  $activities
     * @param  list<array{cents: int, row: array<string, mixed>}>  $rows
     */
    private function mergeCents(array &$activities, array $rows, string $direction): void
    {
        foreach ($rows as $row) {
            $name = trim((string) $row['row']['event']) ?: self::GENERAL_FUND;
            $key = mb_strtolower((string) preg_replace('/\s+/u', ' ', $name));
            $activities[$key] ??= ['name' => $name, 'inflow' => 0, 'outflow' => 0];
            $activities[$key][$direction] += $row['cents'];
        }
    }

    /**
     * @param  array<string, array{name: string, inflow: int, outflow: int}>  $activities
     * @return list<array{name: string, inflow: float, outflow: float}>
     */
    private function activityTotals(array $activities): array
    {
        return array_values(array_map(fn (array $activity): array => [
            'name' => $activity['name'],
            'inflow' => self::money($activity['inflow']),
            'outflow' => self::money($activity['outflow']),
        ], $activities));
    }

    /**
     * @param  array{transaction_date: string, cents: int, purpose: string, received_from: string, reference: string, org_activity_id: ?int}  $payload
     */
    private function sameIncome(OrgCashIncome $income, OrgFundAccount $account, array $payload): bool
    {
        return (int) $income->org_fund_account_id === (int) $account->id
            && $income->transaction_date?->toDateString() === $payload['transaction_date']
            && self::cents($income->amount) === $payload['cents']
            && $income->purpose === $payload['purpose']
            && $income->received_from === $payload['received_from']
            && $income->reference === $payload['reference']
            && ($income->org_activity_id === null ? null : (int) $income->org_activity_id) === $payload['org_activity_id'];
    }

    private function inputCents(mixed $value, string $field, bool $positive): int
    {
        $text = is_int($value) ? (string) $value : (is_string($value) ? trim($value) : '');
        if (! preg_match(self::AMOUNT, $text)) {
            throw ValidationException::withMessages([
                $field => $positive
                    ? 'Enter an amount greater than zero with at most two decimal places.'
                    : 'Enter an amount of zero or more with at most two decimal places.',
            ]);
        }
        $cents = self::cents($text);
        if ($positive && $cents <= 0) {
            throw ValidationException::withMessages([$field => 'Enter an amount greater than zero with at most two decimal places.']);
        }

        return $cents;
    }

    private function text(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function isAcademicYear(string $year): bool
    {
        return preg_match('/^(\d{4})-(\d{4})$/', $year, $match) === 1 && (int) $match[2] === (int) $match[1] + 1;
    }
}
