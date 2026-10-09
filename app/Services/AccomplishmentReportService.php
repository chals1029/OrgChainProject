<?php

namespace App\Services;

use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgActivityAccomplishment;
use App\Models\OrgCashIncome;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Native publication reads canonical cash postings, but never posts any cash. */
class AccomplishmentReportService
{
    public const SEMESTERS = ['1st Semester', '2nd Semester', 'Midyear'];
    public const SIGNATORIES = ['secretary', 'auditor', 'president', 'adviser', 'coordinator', 'head'];
    public const TEXT_FIELDS = ['sponsor', 'brief_description', 'objectives', 'narrative', 'people_involved', 'problems_encountered', 'recommendations'];

    public function __construct(
        private readonly SemesterReportService $semesterReports,
        private readonly AccomplishmentDocumentService $documents,
    ) {}

    public function validatePeriod(string $semester, string $year): void
    {
        Validator::make(['semester' => $semester, 'academic_year' => $year], [
            'semester' => ['required', Rule::in(self::SEMESTERS)],
            'academic_year' => ['required', function (string $field, mixed $value, \Closure $fail): void {
                if (! preg_match('/^(\d{4})-(\d{4})$/', (string) $value, $match) || (int) $match[2] !== (int) $match[1] + 1) {
                    $fail('Choose a consecutive academic year, for example 2025-2026.');
                }
            }],
        ])->validate();
    }

    public function entries(string $organization, string $semester, string $year): Builder
    {
        return OrgActivityAccomplishment::query()->where('organization_name', $organization)
            ->where('semester', $semester)->where('academic_year', $year)->orderBy('id');
    }

    public function eligibleActivities(string $organization): Builder
    {
        return OrgActivity::query()->where('organization_name', $organization)
            ->whereIn('workflow_status', ['oc_approved', 'completed'])
            ->whereRaw('COALESCE(ends_at, starts_at) <= ?', [now(OrgTimeService::timezone())])
            ->whereNotIn('id', OrgActivityAccomplishment::query()->select('org_activity_id'))
            ->orderByDesc('starts_at')->orderBy('id');
    }

    public function locked(string $organization, string $semester, string $year): bool
    {
        return OrgReportStatus::query()->where('organization_name', $organization)
            ->where('semester', $semester)->where('academic_year', $year)
            ->where('report_type', 'ar')
            ->whereIn('status', SemesterReportService::LOCKED_STATUSES)->exists();
    }

    public function nativeDocument(string $organization, string $semester, string $year): ?OrgReportDocument
    {
        return OrgReportDocument::query()->where('organization_name', $organization)->where('semester', $semester)
            ->where('academic_year', $year)->where('report_type', 'ar')->whereNotNull('accomplishment_summary')->latest('id')->first();
    }

    public function activityContext(OrgActivity $activity): array
    {
        $proposal = InCampusActivitySubmission::query()->where('org_activity_id', $activity->id)->latest('id')->first();
        $starts = $activity->starts_at?->copy()->setTimezone(OrgTimeService::timezone());
        $ends = $activity->ends_at?->copy()->setTimezone(OrgTimeService::timezone()) ?: $starts;
        return [
            'id' => (int) $activity->id, 'title' => (string) $activity->title,
            'starts_at' => $starts?->toIso8601String(), 'ends_at' => $ends?->toIso8601String(),
            'date_label' => $starts ? $starts->format('F j, Y (l)').($ends && ! $starts->isSameDay($ends) ? ' – '.$ends->format('F j, Y (l)') : '') : '',
            'time_label' => $starts ? $starts->format('g:i A').' – '.$ends->format('g:i A').' '.OrgTimeService::zoneLabel() : '',
            'venue' => (string) $activity->location, 'sdg_goals' => array_values($activity->sdg_goals ?? []),
            'objectives' => (string) ($proposal?->objectives ?? ''), 'people_involved' => (string) ($proposal?->participants ?? ''),
        ];
    }

    public function save(Request $request, OfficeUser $actor, ?OrgActivityAccomplishment $existing = null): OrgActivityAccomplishment
    {
        $organization = trim((string) $actor->studentOrganization?->name);
        abort_unless($actor->office_role === 'so' && $organization !== '', 403);
        if ($existing) {
            abort_unless($existing->organization_name === $organization, 403);
        }
        $rules = [
            'org_activity_id' => ['required', 'integer'], 'academic_year' => ['required', 'string'],
            'semester' => ['required', Rule::in(self::SEMESTERS)], 'classification' => ['nullable', 'string', 'max:255'],
            'male_participants' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'female_participants' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'signatories' => ['nullable', 'array:'.implode(',', self::SIGNATORIES)],
            'photos' => ['nullable', 'array', 'max:20'], 'photos.*' => ['array:file,caption'],
            'photos.*.file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'photos.*.caption' => ['required', 'string', 'max:2000'],
            'keep_evidence' => ['nullable', 'array', 'max:20'], 'keep_evidence.*' => ['required', 'uuid', 'distinct'],
            'evidence_captions' => ['nullable', 'array'], 'evidence_captions.*' => ['required', 'string', 'max:2000'],
        ];
        foreach (self::TEXT_FIELDS as $field) {
            $rules[$field] = ['required', 'string', 'max:'.($field === 'sponsor' ? '255' : '30000')];
        }
        foreach (self::SIGNATORIES as $role) {
            $rules['signatories.'.$role] = ['nullable', 'string', 'max:255'];
        }
        $data = $request->validate($rules);
        $this->validatePeriod($data['semester'], $data['academic_year']);
        foreach (self::TEXT_FIELDS as $field) {
            $data[$field] = trim($data[$field]);
            if ($data[$field] === '') {
                $this->refuse($field, 'Complete this field; enter “None” if not applicable.');
            }
        }
        if ($existing && ((int) $existing->org_activity_id !== (int) $data['org_activity_id'] || $existing->semester !== $data['semester'] || $existing->academic_year !== $data['academic_year'])) {
            $this->refuse('report', 'An existing report cannot be moved to another activity or reporting period.');
        }
        $uploads = [];
        foreach ($request->file('photos', []) as $index => $photo) {
            $file = $photo['file'];
            $image = $this->imageInfo((string) $file->getRealPath());
            if (! $image) {
                $this->refuse('photos.'.$index.'.file', 'Upload a decodable JPEG or PNG image (up to 10 MB).');
            }
            $caption = trim((string) $data['photos'][$index]['caption']);
            if ($caption === '') {
                $this->refuse('photos.'.$index.'.caption', 'Every image requires a caption.');
            }
            $uploads[] = ['file' => $file, 'caption' => $caption, 'image' => $image];
        }
        $newPaths = [];
        $oldPaths = [];
        try {
            $entry = DB::connection('mysql')->transaction(function () use ($actor, $organization, $existing, $data, $uploads, &$newPaths, &$oldPaths): OrgActivityAccomplishment {
                // Serialize native period creation and generated document reuse for this organization.
                StudentOrganization::on('mysql')->whereKey($actor->student_organization_id)->lockForUpdate()->firstOrFail();
                $lockedStatuses = OrgReportStatus::query()->where('organization_name', $organization)
                    ->where('semester', $data['semester'])->where('academic_year', $data['academic_year'])
                    ->where('report_type', 'ar')->orderBy('id')->lockForUpdate()->get();
                if ($this->semesterReports->isLocked($lockedStatuses)) {
                    $this->refuse('report', 'This Accomplishment Report is submitted to OSO, completed, or rejected. Only a returned AR can be revised.');
                }
                $status = $lockedStatuses->last()
                    ?? $this->semesterReports->ensure('ar', $organization, $data['semester'], $data['academic_year']);
                $entry = $existing ? OrgActivityAccomplishment::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail() : new OrgActivityAccomplishment;
                $activity = OrgActivity::query()->whereKey($data['org_activity_id'])->lockForUpdate()->first();
                if (! $activity || $activity->organization_name !== $organization) {
                    $this->refuse('org_activity_id', 'Choose an activity belonging to your organization.');
                }
                if (! $existing) {
                    $ended = $activity->ends_at ?: $activity->starts_at;
                    if (! in_array($activity->workflow_status, ['oc_approved', 'completed'], true) || ! $ended || $ended->gt(now(OrgTimeService::timezone()))) {
                        $this->refuse('org_activity_id', 'Only fully approved activities that have actually ended can be reported.');
                    }
                    if (OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->exists()) {
                        $this->refuse('org_activity_id', 'This activity already has an accomplishment report. Edit that report instead.');
                    }
                }
                $oldEvidence = collect($entry->evidence ?? [])->keyBy('id');
                $keep = $data['keep_evidence'] ?? [];
                $evidence = [];
                foreach ($keep as $id) {
                    $item = $oldEvidence->get($id);
                    if (! $item) {
                        $this->refuse('keep_evidence', 'Only this report’s saved evidence may be retained.');
                    }
                    $item['caption'] = trim((string) ($data['evidence_captions'][$id] ?? $item['caption'] ?? ''));
                    if ($item['caption'] === '' || ! $this->storedImage($item)) {
                        $this->refuse('evidence_captions', 'Retained evidence must have a caption and a readable real image. Replace missing files.');
                    }
                    $evidence[] = $item;
                }
                if (count($evidence) + count($uploads) < 1 || count($evidence) + count($uploads) > 20) {
                    $this->refuse('photos', 'Keep or upload between one and twenty captioned evidence images.');
                }
                foreach ($uploads as $upload) {
                    $id = (string) Str::uuid();
                    $path = 'accomplishment-evidence/'.$actor->student_organization_id.'/'.$id.'.'.$upload['image']['extension'];
                    $newPaths[] = ['local', $path];
                    if (! $upload['file']->storeAs(dirname($path), basename($path), 'local')) {
                        $this->refuse('photos', 'The evidence could not be stored. Try again.');
                    }
                    $evidence[] = ['id' => $id, 'path' => $path, 'name' => basename(str_replace('\\', '/', $upload['file']->getClientOriginalName())), 'caption' => $upload['caption'], 'mime' => $upload['image']['mime']];
                }
                foreach ($oldEvidence as $id => $item) {
                    if (! in_array($id, $keep, true)) {
                        $oldPaths[] = ['local', $item['path']];
                    }
                }
                $snapshot = $existing ? $entry->activity_snapshot : $this->activityContext($activity);
                $snapshot['classification'] = trim((string) ($data['classification'] ?? ''));
                $fields = array_intersect_key($data, array_flip(self::TEXT_FIELDS));
                $entry->fill([...$fields, 'org_activity_id' => $activity->id, 'organization_name' => $organization,
                    'academic_year' => $data['academic_year'], 'semester' => $data['semester'],
                    'male_participants' => $data['male_participants'], 'female_participants' => $data['female_participants'],
                    'signatories' => array_map(fn ($name) => trim((string) $name), $data['signatories'] ?? []),
                    'activity_snapshot' => $snapshot, 'financial_snapshot' => $entry->financial_snapshot ?? [], 'evidence' => $evidence,
                    'created_by' => $entry->created_by ?: $actor->id, 'updated_by' => $actor->id,
                ])->save();
                $entries = $this->entries($organization, $data['semester'], $data['academic_year'])->lockForUpdate()->get();
                foreach ($entries as $periodEntry) {
                    foreach ($periodEntry->financial_snapshot['receipt_attachments'] ?? [] as $attachment) {
                        $oldPaths[] = ['local', $attachment['path']];
                    }
                    $source = OrgActivity::query()->find($periodEntry->org_activity_id);
                    $periodEntry->financial_snapshot = $this->finance($periodEntry, $source, $actor->student_organization_id, $newPaths);
                    $periodEntry->updated_by = $actor->id;
                    $periodEntry->save();
                    $this->assertComplete($periodEntry);
                }
                $packet = $this->packet($organization, $data['semester'], $data['academic_year'], $entries->map(fn ($row) => $this->report($row, false))->all());
                $path = 'semester-reports/ar/'.$actor->student_organization_id.'/'.str_replace('-', '_', $data['academic_year']).'/'.Str::slug($data['semester']).'/'.Str::uuid().'.docx';
                $newPaths[] = ['public', $path];
                $this->documents->generate($packet, Storage::disk('public')->path($path));
                $document = $this->nativeDocument($organization, $data['semester'], $data['academic_year']) ?? new OrgReportDocument;
                if ($document->exists && $document->file_path) {
                    $oldPaths[] = ['public', $document->file_path];
                }
                $name = $organization.' – '.$data['semester'].' AY '.$data['academic_year'].' Accomplishment Report';
                $document->fill(['org_report_status_id' => $status->id, 'report_type' => 'ar',
                    'organization_name' => $organization, 'semester' => $data['semester'], 'academic_year' => $data['academic_year'],
                    'name' => Str::limit($name, 255, ''), 'original_name' => Str::substr(Str::slug($name), 0, 220).'.docx', 'file_path' => $path,
                    'mime_type' => AccomplishmentDocumentService::MIME_TYPE, 'file_size' => filesize(Storage::disk('public')->path($path)),
                    'accomplishment_summary' => $packet, 'uploaded_by' => $actor->name,
                ])->save();
                return $entry->refresh();
            });
        } catch (Throwable $exception) {
            $this->deleteOwned($newPaths);
            throw $exception;
        }
        DB::connection('mysql')->afterCommit(fn () => $this->deleteOwned($oldPaths));
        return $entry;
    }

    private function finance(OrgActivityAccomplishment $entry, ?OrgActivity $activity, int $organizationId, array &$newPaths): array
    {
        $collections = [];
        $expenses = [];
        $attachments = [];
        $in = $out = 0;
        // cashFlow display rows are title-grouped; canonical models retain the actual activity ID.
        foreach (OrgCashIncome::query()->where('organization_name', $entry->organization_name)->where('org_activity_id', $entry->org_activity_id)->orderBy('transaction_date')->orderBy('id')->get() as $income) {
            $amount = OrganizationCashLedger::cents($income->amount);
            $in += $amount;
            $collections[] = ['date' => $income->transaction_date?->toDateString(), 'particulars' => $income->purpose.' — '.$income->received_from,
                'quantity' => 1, 'unit_cost' => OrganizationCashLedger::decimal($amount), 'total' => OrganizationCashLedger::decimal($amount),
                'reference' => (string) $income->reference, 'posting_reference' => 'INC-'.$income->id];
        }
        foreach (ExpenseReceiptReview::query()->where('org_activity_id', $entry->org_activity_id)->where('organization_name', $entry->organization_name)
            ->whereIn('verification_status', ActivityBudgetService::POSTED)->orderBy('expense_date')->orderBy('id')->get() as $receipt) {
            $unit = OrganizationCashLedger::cents($receipt->unit_cost);
            $amount = $unit * (int) $receipt->quantity;
            $out += $amount;
            $row = ['date' => $receipt->expense_date?->toDateString(), 'particulars' => $receipt->item_name.' — '.$receipt->supplier,
                'quantity' => (int) $receipt->quantity, 'unit_cost' => OrganizationCashLedger::decimal($unit), 'total' => OrganizationCashLedger::decimal($amount),
                'reference' => (string) $receipt->receipt_reference, 'posting_reference' => 'EXP-'.$receipt->id];
            foreach ($receipt->receiptFiles() as $file) {
                if (! in_array($file['disk'], ['local', 'public'], true) || ! $this->safeRelative($file['path'])) {
                    continue;
                }
                $source = Storage::disk($file['disk'])->path($file['path']);
                $image = $this->imageInfo($source);
                if (! $image) {
                    continue;
                }
                $id = (string) Str::uuid();
                $path = 'accomplishment-evidence/'.$organizationId.'/'.$id.'.'.$image['extension'];
                $newPaths[] = ['local', $path];
                $stream = fopen($source, 'rb');
                try {
                    if ($stream === false || ! Storage::disk('local')->put($path, $stream)) {
                        $this->refuse('report', 'A supporting receipt scan could not be stored.');
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
                $attachments[] = ['id' => $id, 'path' => $path, 'name' => basename(str_replace('\\', '/', $file['name'])),
                    'caption' => 'Receipt '.($receipt->receipt_reference ?: 'EXP-'.$receipt->id).' — '.$receipt->item_name, 'reference' => $receipt->receipt_reference,
                    'posting_reference' => 'EXP-'.$receipt->id, 'mime' => $image['mime']];
                $row['receipt_url'] ??= route('office.accomplishment.evidence', ['report' => $entry->id, 'evidence' => $id]);
            }
            $expenses[] = $row;
        }
        $gap = max(0, OrganizationCashLedger::cents($activity?->implemented_budget) - $out);
        if ($gap > 0) {
            $expenses[] = ['date' => $entry->activity_snapshot['starts_at'] ? Carbon::parse($entry->activity_snapshot['starts_at'])->toDateString() : null,
                'particulars' => 'Legacy spending recorded before itemized receipts (non-itemized; no receipt claimed)',
                'quantity' => 1, 'unit_cost' => OrganizationCashLedger::decimal($gap), 'total' => OrganizationCashLedger::decimal($gap),
                'reference' => '', 'posting_reference' => 'LEGACY-'.$entry->org_activity_id];
            $out += $gap;
        }
        return ['collections' => $collections, 'expenses' => $expenses, 'total_collection' => OrganizationCashLedger::decimal($in),
            'total_expenses' => OrganizationCashLedger::decimal($out), 'net_collection' => OrganizationCashLedger::decimal($in - $out),
            'recorded_at' => now()->toIso8601String(), 'receipt_attachments' => $attachments,
            'source_note' => 'Actual activity-linked income and posted expense receipts; legacy spending is separately labeled. Net = activity collections − activity expenses. Organization opening cash and unrelated income are excluded.'];
    }

    public function report(OrgActivityAccomplishment $entry, bool $public = true): array
    {
        $snapshot = $entry->activity_snapshot;
        $result = [...array_intersect_key($snapshot, array_flip(['title', 'starts_at', 'ends_at', 'date_label', 'time_label', 'venue', 'sdg_goals', 'classification'])),
            'id' => $entry->id, 'activity_id' => $entry->org_activity_id, 'organization' => $entry->organization_name,
            'academic_year' => $entry->academic_year, 'semester' => $entry->semester,
            ...array_intersect_key($entry->getAttributes(), array_flip(self::TEXT_FIELDS)),
            'male_participants' => $entry->male_participants, 'female_participants' => $entry->female_participants,
            'participants' => $entry->male_participants + $entry->female_participants, 'signatories' => $entry->signatories,
            'financial' => $entry->financial_snapshot, 'evidence' => $entry->evidence,
            'update_url' => route('office.accomplishment.reports.update', $entry), 'preview_url' => route('office.accomplishment.reports.preview', $entry),
            'created_at' => $entry->created_at?->toIso8601String(), 'updated_at' => $entry->updated_at?->toIso8601String()];
        return $public ? $this->publicReport($result) : $result;
    }

    public function publicReport(array $report): array
    {
        $images = function (array $items) use ($report): array {
            return array_map(fn (array $image): array => [
                ...array_intersect_key($image, array_flip(['id', 'name', 'caption', 'reference', 'posting_reference'])),
                'url' => route('office.accomplishment.evidence', ['report' => $report['id'], 'evidence' => $image['id']]),
            ], $items);
        };
        $report['evidence'] = $images($report['evidence'] ?? []);
        $report['financial']['receipt_attachments'] = $images($report['financial']['receipt_attachments'] ?? []);
        $report['update_url'] = route('office.accomplishment.reports.update', ['report' => $report['id']]);
        $report['preview_url'] = route('office.accomplishment.reports.preview', ['report' => $report['id']]);
        $receipts = collect($report['financial']['receipt_attachments'])->keyBy('posting_reference');
        foreach ($report['financial']['expenses'] as &$expense) {
            unset($expense['receipt_url']);
            $receipt = $receipts->get($expense['posting_reference'] ?? '');
            if ($receipt) {
                $expense['receipt_url'] = $receipt['url'];
            }
        }
        unset($expense);
        return $report;
    }

    public function packet(string $organization, string $semester, string $year, array $reports): array
    {
        return ['organization' => $organization, 'academic_year' => $year, 'semester' => $semester,
            'classification' => implode(', ', array_unique(array_filter(array_column($reports, 'classification')))),
            'generated_at' => now()->toIso8601String(), 'reports' => $reports, 'signatories' => $reports[0]['signatories'] ?? []];
    }

    public function publicPacket(array $packet): array
    {
        $packet['reports'] = array_map(fn (array $report): array => $this->publicReport($report), $packet['reports'] ?? []);
        return $packet;
    }

    public function assertSubmittable(string $organization, string $semester, string $academicYear): void
    {
        $entries = $this->entries($organization, $semester, $academicYear)->get();
        if ($entries->isEmpty()) {
            return;
        }
        $document = $this->nativeDocument($organization, $semester, $academicYear);
        if (! $document || ! $document->hasStoredFile() || ! is_array($document->accomplishment_summary)) {
            $this->refuse('report', 'Save a complete native Accomplishment Report packet with a readable Word file before submitting.');
        }
        if ((int) $document->org_report_status_id !== (int) $this->semesterReports->find('ar', $organization, $semester, $academicYear)?->id) {
            $this->refuse('report', 'The generated native packet is not staged in the current Accomplishment Report. Save it again.');
        }
        $snapshot = $document->accomplishment_summary;
        if (($snapshot['organization'] ?? null) !== $organization || ($snapshot['semester'] ?? null) !== $semester || ($snapshot['academic_year'] ?? null) !== $academicYear) {
            $this->refuse('report', 'The native packet does not match this reporting period. Save it again.');
        }
        if (! is_array($snapshot['reports'] ?? null) || count($snapshot['reports']) !== $entries->count()) {
            $this->refuse('report', 'The native packet snapshot is incomplete. Save the activity report again.');
        }
        $saved = collect($snapshot['reports'] ?? [])->keyBy('id');
        if ($saved->count() !== $entries->count()) {
            $this->refuse('report', 'The generated packet does not contain all native entries. Save the report again.');
        }
        foreach ($entries as $entry) {
            $this->assertComplete($entry);
            $row = $saved->get($entry->id);
            // MySQL canonicalizes JSON object key order; compare values, not insertion order.
            $current = $this->report($entry, false);
            unset($current['update_url'], $current['preview_url']);
            if (is_array($row)) {
                unset($row['update_url'], $row['preview_url']);
            }
            if (! $row || $current != $row) {
                $this->refuse('report', 'A native entry differs from the generated packet. Save it again before submitting.');
            }
        }
        $this->documents->assertReadable(Storage::disk('public')->path($document->file_path));
    }

    public function assertComplete(OrgActivityAccomplishment $entry): void
    {
        foreach (self::TEXT_FIELDS as $field) {
            if (trim((string) $entry->$field) === '') {
                $this->refuse('report', 'Complete all official fields before submitting the native report.');
            }
        }
        if (! is_array($entry->activity_snapshot) || trim((string) ($entry->activity_snapshot['title'] ?? '')) === '' || ! is_array($entry->financial_snapshot)
            || ! isset($entry->financial_snapshot['total_collection'], $entry->financial_snapshot['total_expenses'], $entry->financial_snapshot['net_collection'])
            || $entry->male_participants === null || $entry->female_participants === null || $entry->male_participants < 0 || $entry->female_participants < 0) {
            $this->refuse('report', 'The native report is missing its actual participant counts or publication snapshots.');
        }
        foreach (['title', 'starts_at', 'ends_at', 'date_label', 'time_label', 'venue'] as $field) {
            if (trim((string) ($entry->activity_snapshot[$field] ?? '')) === '') {
                $this->refuse('report', 'The approved activity context is incomplete (title, dates, time or venue).');
            }
        }
        $financial = $entry->financial_snapshot;
        if (! is_array($financial['collections'] ?? null) || ! is_array($financial['expenses'] ?? null)
            || ! is_array($financial['receipt_attachments'] ?? null)
            || trim((string) ($financial['recorded_at'] ?? '')) === '' || trim((string) ($financial['source_note'] ?? '')) === '') {
            $this->refuse('report', 'The native financial snapshot is incomplete. Save the activity report again.');
        }
        $totals = [];
        foreach (['collections', 'expenses'] as $direction) {
            $total = 0;
            foreach ($financial[$direction] as $row) {
                if (! is_array($row) || trim((string) ($row['particulars'] ?? '')) === '' || ! isset($row['quantity'], $row['unit_cost'], $row['total'])
                    || ! preg_match('/^\d+$/', (string) $row['quantity']) || (int) $row['quantity'] < 1
                    || ! preg_match('/^\d+\.\d{2}$/', (string) $row['unit_cost']) || ! preg_match('/^\d+\.\d{2}$/', (string) $row['total'])
                    || OrganizationCashLedger::cents($row['unit_cost']) * (int) $row['quantity'] !== OrganizationCashLedger::cents($row['total'])) {
                    $this->refuse('report', 'The native financial particulars are incomplete or inconsistent. Save the activity report again.');
                }
                $total += OrganizationCashLedger::cents($row['total']);
            }
            $totals[$direction] = $total;
        }
        if (OrganizationCashLedger::decimal($totals['collections']) !== $financial['total_collection']
            || OrganizationCashLedger::decimal($totals['expenses']) !== $financial['total_expenses']
            || OrganizationCashLedger::decimal($totals['collections'] - $totals['expenses']) !== $financial['net_collection']) {
            $this->refuse('report', 'The native financial totals do not match their actual particulars. Save the activity report again.');
        }
        if (! is_array($entry->evidence) || count($entry->evidence) < 1 || count($entry->evidence) > 20) {
            $this->refuse('report', 'Every activity must have one to twenty captioned evidence images.');
        }
        foreach (array_merge($entry->evidence, $entry->financial_snapshot['receipt_attachments'] ?? []) as $image) {
            if (! is_array($image) || trim((string) ($image['id'] ?? '')) === '' || trim((string) ($image['name'] ?? '')) === ''
                || trim((string) ($image['caption'] ?? '')) === '' || ! $this->storedImage($image)) {
                $this->refuse('report', 'A captioned native evidence or receipt image is missing or unreadable. Replace it and save again.');
            }
        }
    }

    public function storedImage(array $image): ?array
    {
        return $this->ownedEvidencePath((string) ($image['path'] ?? '')) ? $this->imageInfo(Storage::disk('local')->path($image['path'])) : null;
    }

    public function imageInfo(string $absolute): ?array
    {
        if (! is_file($absolute) || ! is_readable($absolute) || filesize($absolute) <= 0 || filesize($absolute) > 10 * 1024 * 1024) {
            return null;
        }
        $info = @getimagesize($absolute);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) || $info[0] <= 0 || $info[1] <= 0 || $info[0] * $info[1] > 40000000) {
            return null;
        }
        // A recognized header alone is not proof that the image can be decoded.
        $decoded = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($absolute) : @imagecreatefromjpeg($absolute);
        if ($decoded === false) {
            return null;
        }
        imagedestroy($decoded);
        return ['width' => $info[0], 'height' => $info[1], 'extension' => $info[2] === IMAGETYPE_PNG ? 'png' : 'jpg', 'mime' => $info['mime']];
    }

    public function ownedEvidencePath(string $path): bool
    {
        return preg_match('~^accomplishment-evidence/[0-9]+/[0-9a-f-]{36}\.(?:jpg|png)$~D', $path) === 1;
    }

    private function safeRelative(string $path): bool
    {
        return $path !== '' && ! str_contains($path, '\\') && ! str_starts_with($path, '/') && ! str_contains($path, ':') && ! in_array('..', explode('/', $path), true);
    }

    private function deleteOwned(array $files): void
    {
        foreach ($files as [$disk, $path]) {
            if (($disk === 'local' && $this->ownedEvidencePath($path)) || ($disk === 'public' && str_starts_with($path, 'semester-reports/ar/') && $this->safeRelative($path))) {
                if ($disk === 'public' && (OrgReportDocument::query()->where('file_path', $path)->exists()
                    || \App\Models\ArchiveDocument::query()->where('file_path', $path)->exists())) {
                    continue;
                }
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function refuse(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
