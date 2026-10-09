<?php

namespace App\Http\Controllers;

use App\Exceptions\SemesterReportRejected;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use App\Services\ActivityBudgetService;
use App\Services\FinancialReportPreviewGate;
use App\Services\FinancialWorkbookService;
use App\Services\OrganizationCashLedger;
use App\Services\SemesterReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Student Organization Financial Report desk. Every action is bound to the
 * signed-in SO account's own organization; no request field selects it.
 * Cash figures come only from the saved organization cash ledger; uploaded
 * workbooks are report documents and never post cash.
 */
class SoFinancialReportController extends Controller
{
    private const SEMESTERS = ['1st Semester', '2nd Semester', 'Midyear'];

    public function __construct(
        private readonly FinancialWorkbookService $workbooks,
        private readonly SemesterReportService $semesterReports,
        private readonly FinancialReportPreviewGate $previews,
        private readonly OrganizationCashLedger $ledger,
        private readonly ActivityBudgetService $budgets,
    ) {}

    /**
     * @param  array<string, mixed>  $context  shared Student Org desk payload
     */
    public function index(array $context): View
    {
        $organization = $this->organization();
        $filters = [
            'academic_year' => $this->yearFilter(request('academic_year')),
            'semester' => $this->semesterFilter(request('semester')),
        ];
        $currentYear = $this->budgets->period()['academic_year'];
        $managementYear = $filters['academic_year'] !== 'all' ? $filters['academic_year'] : $currentYear;
        [$fiscalStart, $fiscalEnd] = $this->budgets->dates($managementYear);
        $documents = $this->documents($organization);
        $flow = $this->ledger->cashFlow($organization, $filters);
        $reports = $this->reports($organization, $documents);
        $cutoff = now()->subYear();
        $isHistory = fn (array $report): bool => $report['submitted_at'] !== null
            && Carbon::parse($report['submitted_at'])->lessThanOrEqualTo($cutoff);

        return view('org.so-financial', array_merge($context, [
            'activeNav' => 'financial',
            'submissionLocks' => $filters['academic_year'] !== 'all' && $filters['semester'] !== 'all'
                ? app(\App\Services\ReportSubmissionWindowService::class)->locks($filters['academic_year'], $filters['semester'])
                : null,
            'financialDashboard' => [
                'organization' => $organization,
                'year_filter' => $filters['academic_year'],
                'semester_filter' => $filters['semester'],
                'current_year' => $currentYear,
                'years' => collect($flow['years'])
                    ->merge($documents->pluck('academic_year'))
                    ->push($currentYear)
                    ->filter()->unique()->sortDesc()->values()->all(),
                'semesters' => self::SEMESTERS,
                'period_label' => $this->filterLabel($filters),
                'beginning_balance' => $flow['beginning_balance'],
                'cash_inflow' => $flow['cash_inflow'],
                'cash_outflow' => $flow['cash_outflow'],
                'ending_balance' => $flow['ending_balance'],
                'activities' => $flow['activities'],
                'management_year' => $managementYear,
                'fiscal_start' => $fiscalStart,
                'fiscal_end' => $fiscalEnd,
                'funding' => $this->ledger->funding($organization, $managementYear),
                'income_activities' => $this->ledger->incomeActivities($organization, $managementYear)
                    ->map(fn (OrgActivity $activity): array => [
                        'id' => $activity->id,
                        'title' => $activity->title,
                        'date' => $activity->starts_at ? Carbon::parse($activity->starts_at)->toDateString() : null,
                    ])->all(),
                'current_reports' => array_values(array_filter($reports, fn (array $report): bool => ! $isHistory($report))),
                'history_reports' => array_values(array_filter($reports, $isHistory)),
                'template_url' => route('office.financial.template'),
                'export_url' => route('office.financial.export', $filters),
                'create_url' => route('office.financial.reports.store'),
                'opening_url' => route('office.financial.opening'),
                'income_url' => route('office.financial.income'),
            ],
        ]));
    }

    public function saveOpening(Request $request): RedirectResponse
    {
        $this->organization();
        $validated = $request->validate([
            'academic_year' => $this->academicYearRules(),
            'opening_balance' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999999999.99'],
        ], [], ['opening_balance' => 'opening cash balance']);

        $this->ledger->saveOpening(
            Auth::guard('office')->user(),
            $validated['academic_year'],
            (string) $validated['opening_balance']
        );

        return redirect()
            ->route('office.financial', ['academic_year' => $validated['academic_year']])
            ->with('success', 'Opening cash balance of ₱'.number_format((float) $validated['opening_balance'], 2).' saved for AY '.$validated['academic_year'].'.');
    }

    public function storeIncome(Request $request): RedirectResponse
    {
        $this->organization();
        $validated = $request->validate([
            'academic_year' => $this->academicYearRules(),
            'transaction_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999999999.99'],
            'purpose' => ['required', 'string', 'max:255'],
            'received_from' => ['required', 'string', 'max:255'],
            'reference' => ['required', 'string', 'max:120'],
            'request_key' => ['required', 'uuid'],
            'org_activity_id' => ['nullable', 'integer'],
        ], [], ['received_from' => 'payer or source', 'org_activity_id' => 'activity', 'transaction_date' => 'date received']);

        $income = $this->ledger->recordIncome(Auth::guard('office')->user(), [
            ...$validated,
            'amount' => (string) $validated['amount'],
            'purpose' => trim($validated['purpose']),
            'received_from' => trim($validated['received_from']),
            'reference' => trim($validated['reference']),
            'org_activity_id' => $validated['org_activity_id'] ?? null,
        ]);
        $amount = '₱'.number_format((float) $validated['amount'], 2);

        return redirect()
            ->route('office.financial', ['academic_year' => $validated['academic_year']])
            ->with('success', $income->wasRecentlyCreated
                ? 'Cash inflow of '.$amount.' (reference '.trim($validated['reference']).') recorded for AY '.$validated['academic_year'].'.'
                : 'This cash inflow was already recorded; it was not added again.');
    }

    public function template(): BinaryFileResponse
    {
        $this->organization();
        $path = $this->workbooks->templatePath();
        abort_unless(is_file($path), 404, 'The Financial Report template is not available.');

        return response()->download($path, 'Financial Report_New Format.xlsx', [
            'Content-Type' => FinancialWorkbookService::MIME_TYPE,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $organization = $this->organization();
        $validated = $request->validate([
            'academic_year' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value !== 'all' && ! preg_match('/^\d{4}-\d{4}$/', (string) $value)) {
                    $fail('Choose a valid academic year.');
                }
            }],
            'semester' => ['nullable', Rule::in([...self::SEMESTERS, 'all'])],
            'beginning_balance' => ['prohibited'],
        ], ['beginning_balance.prohibited' => 'The export uses the saved opening cash balance. Change it by saving the annual opening balance under Organization Cash.']);
        $filters = [
            'academic_year' => $this->yearFilter($validated['academic_year'] ?? null),
            'semester' => $this->semesterFilter($validated['semester'] ?? null),
        ];
        $flow = $this->ledger->cashFlow($organization, $filters);

        if ($flow['selected']->isEmpty()) {
            return response()->download($this->workbooks->templatePath(), 'Financial Report_New Format (blank template).xlsx', [
                'Content-Type' => FinancialWorkbookService::MIME_TYPE,
            ]);
        }

        $path = $this->workbooks->export(
            $flow['selected']->map(fn (array $entry): array => [
                'summary' => $entry['summary'],
                'period_label' => $this->periodLabel($entry['academic_year'], $entry['semester']),
            ])->values()->all(),
            (float) $flow['beginning_balance'],
            $organization
        );
        $filename = trim((string) preg_replace('/[^\pL\pN ._-]+/u', ' ', 'Financial Report - '.$organization.' - '.$this->filterLabel($filters)));

        return response()->download($path, preg_replace('/\s+/', ' ', $filename).'.xlsx', [
            'Content-Type' => FinancialWorkbookService::MIME_TYPE,
        ])->deleteFileAfterSend();
    }

    public function store(Request $request): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        $organization = $this->organization();
        $titleField = 'name';
        $fileField = 'document';
        $validated = $request->validate([
            $titleField => ['required', 'string', 'max:255'],
            'academic_year' => $this->academicYearRules(),
            'semester' => ['required', Rule::in(self::SEMESTERS)],
            $fileField => ['required', 'file', 'extensions:xlsx', 'max:10240'],
        ], [], [$titleField => 'report name', $fileField => 'workbook']);
        $validated['name'] = trim((string) $validated[$titleField]);

        $file = $request->file($fileField);
        try {
            $summary = $this->workbooks->summarize((string) $file->getRealPath());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([$fileField => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([$fileField => 'The workbook could not be read. Upload the completed Financial Report as a regular .xlsx file.']);
        }

        $folder = 'semester-reports/fr/'.Str::slug($organization).'/'.str_replace('-', '_', $validated['academic_year']).'/'.Str::slug($validated['semester']);
        $storedPath = null;
        try {
            DB::connection('mysql')->transaction(function () use ($office, $organization, $validated, $file, $fileField, $folder, $summary, &$storedPath): void {
                StudentOrganization::on('mysql')->whereKey($office->student_organization_id)->lockForUpdate()->firstOrFail();
                $locked = OrgReportStatus::query()
                    ->where('report_type', 'fr')
                    ->where('organization_name', $organization)
                    ->where('semester', $validated['semester'])
                    ->where('academic_year', $validated['academic_year'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                if ($this->semesterReports->isLocked($locked)) {
                    throw ValidationException::withMessages([
                        'report' => 'This Financial Report is submitted to OSO, completed, or rejected. Only a returned FR can be revised.',
                    ]);
                }
                $frStatus = $locked->last()
                    ?? $this->semesterReports->ensure('fr', $organization, $validated['semester'], $validated['academic_year']);

                $storedPath = $file->store($folder, 'public') ?: null;
                if ($storedPath === null) {
                    throw ValidationException::withMessages([$fileField => 'The workbook could not be stored. Try again.']);
                }

                OrgReportDocument::query()->create([
                    'org_report_status_id' => $frStatus->id,
                    'report_type' => 'fr',
                    'organization_name' => $organization,
                    'semester' => $validated['semester'],
                    'academic_year' => $validated['academic_year'],
                    'name' => $validated['name'],
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $storedPath,
                    'mime_type' => FinancialWorkbookService::MIME_TYPE,
                    'file_size' => $file->getSize(),
                    'financial_summary' => $summary,
                    'uploaded_by' => $office->name,
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }
            throw $exception;
        }

        return redirect()
            ->route('office.financial', ['academic_year' => $validated['academic_year'], 'semester' => $validated['semester']])
            ->with('success', 'Financial report “'.$validated['name'].'” created for '.$this->periodLabel($validated['academic_year'], $validated['semester']).'. View it, then submit the FR to OSO.');
    }

    public function preview(OrgReportDocument $document): JsonResponse
    {
        $this->ownedDocument($document);
        if (! $document->hasStoredFile()) {
            return response()->json(['message' => 'The stored file for this report is missing. Upload the financial report again.'], 404);
        }
        if (! $document->isWorkbook()) {
            return response()->json(['message' => 'This report is not an Excel workbook. Download the original file to view it.'], 422);
        }

        try {
            $preview = $this->workbooks->preview(
                Storage::disk('public')->path($document->file_path),
                is_array($document->financial_summary) ? $document->financial_summary : null
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'The workbook could not be read. Download the original file instead.'], 422);
        }

        $response = response()->json([
            'title' => $document->name,
            'original_name' => $document->original_name,
            'academic_year' => $document->academic_year,
            'semester' => $document->semester,
            'sheets' => $preview['sheets'],
            'cash_inflow' => (float) ($preview['summary']['cash_inflow'] ?? 0),
            'cash_outflow' => (float) ($preview['summary']['cash_outflow'] ?? 0),
            'balance' => (float) ($preview['summary']['ending_balance'] ?? 0),
            'download_url' => route('office.reports.documents.view', ['document' => $document, 'download' => 1]),
        ]);
        $this->previews->mark(Auth::guard('office')->user(), $document);

        return $response;
    }

    public function submit(OrgReportDocument $document): RedirectResponse
    {
        $organization = $this->ownedDocument($document);
        $redirect = redirect()->route('office.financial', [
            'academic_year' => $document->academic_year,
            'semester' => $document->semester,
        ]);
        $fail = fn (string $message): RedirectResponse => $redirect->withErrors(['report' => $message]);

        if (! $document->hasStoredFile()) {
            return $fail('The stored file for “'.$document->name.'” is missing. Upload the financial report again before submitting.');
        }

        $office = Auth::guard('office')->user();
        $period = $this->periodLabel($document->academic_year, $document->semester);
        try {
            $this->semesterReports->submitReport(
                'fr',
                $organization,
                $document->semester,
                $document->academic_year,
                null,
                function (OrgReportStatus $status, OrgReportDocument $latestDocument) use ($document, $office, $period): void {
                    $refuse = fn (string $message) => throw new SemesterReportRejected(SemesterReportRejected::GUARD, $message, ['fr']);
                    if ((int) $status->id !== (int) $document->org_report_status_id) {
                        $refuse('This financial report is not part of the current '.$period.' Financial Report. Upload it again for this period.');
                    }
                    if ((int) $latestDocument->id !== (int) $document->id) {
                        $refuse('A newer financial report exists for '.$period.'. Submit the newest revision.');
                    }
                    if (! ($latestDocument->isWorkbook() || $this->previews->isInlineViewable($latestDocument))) {
                        $refuse('This file type cannot be viewed here, so it cannot be submitted from this page. Download the original, or create the report from the Financial Report workbook.');
                    }
                    if (! $this->previews->wasViewed($office, $latestDocument)) {
                        $refuse('View “'.$latestDocument->name.'” before submitting it. If the file changed since you last viewed it, view it again.');
                    }
                },
                create: false,
            );
        } catch (SemesterReportRejected $rejected) {
            return $fail(match ($rejected->reason) {
                SemesterReportRejected::LOCKED => 'This Financial Report is already submitted to OSO, completed, or rejected. Only a returned FR can be resubmitted.',
                SemesterReportRejected::GUARD => $rejected->getMessage(),
                default => 'The current Financial Report file for '.$period.' is missing. Upload the financial report again before submitting.',
            });
        }

        return $redirect->with('success', 'Financial Report (FR) submitted to OSO for review.');
    }

    private function organization(): string
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'so', 403);
        $organization = trim((string) $office->organizationName());
        abort_if($organization === '', 403, 'This SO account is not assigned to an organization.');

        return $organization;
    }

    private function ownedDocument(OrgReportDocument $document): string
    {
        $organization = $this->organization();
        abort_unless($document->report_type === 'fr', 404);
        abort_unless($document->organization_name === $organization, 403, 'This financial report belongs to another organization.');

        return $organization;
    }

    /**
     * @return list<mixed>
     */
    private function academicYearRules(): array
    {
        return ['required', 'regex:/^\d{4}-\d{4}$/', function (string $attribute, mixed $value, \Closure $fail): void {
            [$start, $end] = array_map('intval', explode('-', (string) $value) + [1 => 0]);
            if ($end !== $start + 1) {
                $fail('Use consecutive years for the academic year, for example 2025-2026.');
            }
        }];
    }

    /**
     * Explicit "all" keeps the combined view; anything else that is not a
     * consecutive academic year falls back to the current academic year.
     */
    private function yearFilter(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === 'all') {
            return 'all';
        }

        return preg_match('/^(\d{4})-(\d{4})$/', $value, $match) && (int) $match[2] === (int) $match[1] + 1
            ? $value
            : $this->budgets->period()['academic_year'];
    }

    private function semesterFilter(mixed $value): string
    {
        return in_array($value, self::SEMESTERS, true) ? $value : 'all';
    }

    private function periodLabel(string $academicYear, string $semester): string
    {
        return $semester.' AY '.$academicYear;
    }

    /**
     * @param  array{academic_year: string, semester: string}  $filters
     */
    private function filterLabel(array $filters): string
    {
        return match (true) {
            $filters['academic_year'] === 'all' && $filters['semester'] === 'all' => 'All reporting periods',
            $filters['academic_year'] === 'all' => $filters['semester'].' · All academic years',
            $filters['semester'] === 'all' => 'AY '.$filters['academic_year'].' · All semesters',
            default => $this->periodLabel($filters['academic_year'], $filters['semester']),
        };
    }

    /**
     * @return Collection<int, OrgReportDocument>
     */
    private function documents(string $organization): Collection
    {
        return OrgReportDocument::query()
            ->with('reportStatus')
            ->where('report_type', 'fr')
            ->where('organization_name', $organization)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Stored workbook metadata, or a read-only inspection of a legacy
     * workbook uploaded before metadata existed.
     *
     * @return array<string, mixed>|null
     */
    private function summary(OrgReportDocument $document): ?array
    {
        if (is_array($document->financial_summary)) {
            return $document->financial_summary;
        }
        if (! $document->isWorkbook() || ! $document->hasStoredFile()) {
            return null;
        }

        try {
            return $this->workbooks->summarize(Storage::disk('public')->path($document->file_path));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  Collection<int, OrgReportDocument>  $documents  newest first
     * @return list<array<string, mixed>>
     */
    private function reports(string $organization, Collection $documents): array
    {
        $statusesByPeriod = OrgReportStatus::query()
            ->where('report_type', 'fr')
            ->where('organization_name', $organization)
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (OrgReportStatus $status): string => $status->academic_year.'|'.$status->semester);
        $newestByStatus = $documents->groupBy('org_report_status_id')->map(fn (Collection $rows): int => (int) $rows->max('id'));
        $submissionLocks = app(\App\Services\ReportSubmissionWindowService::class)
            ->lockedPeriods('fr', $documents->pluck('academic_year')->unique()->all());

        return $documents->map(function (OrgReportDocument $document) use ($statusesByPeriod, $newestByStatus, $submissionLocks): array {
            $periodKey = $document->academic_year.'|'.$document->semester;
            $statuses = $statusesByPeriod->get($periodKey, collect());
            $currentStatus = $statuses->first();
            $locked = $this->semesterReports->isLocked($statuses);
            $submissionLocked = $submissionLocks[$periodKey] ?? false;
            $status = $document->reportStatus;
            $state = $status?->status ?: 'draft';
            $submittedAt = $status?->submitted_at;
            if ($submittedAt && $document->created_at && $submittedAt->lt($document->created_at)) {
                $submittedAt = null;
            }

            $hasFile = $document->hasStoredFile();
            $summary = $hasFile ? $this->summary($document) : null;
            $period = $this->periodLabel((string) $document->academic_year, (string) $document->semester);
            $isCurrent = $currentStatus !== null
                && (int) $currentStatus->id === (int) $document->org_report_status_id;
            $viewable = $document->isWorkbook() || $this->previews->isInlineViewable($document);

            $help = match (true) {
                ! $hasFile => 'The stored file is missing. Upload the financial report again before submitting.',
                $state === 'oso_review' => $status?->opened_at ? 'Opened by OSO — waiting for the review decision.' : 'Submitted to OSO — waiting for review.',
                $state === 'verified' => 'Accepted by OSO.',
                $state === 'archived' => 'Accepted by OSO and archived.',
                $state === 'rejected' => 'Rejected by OSO. This Financial Report is locked and cannot be revised or resubmitted.',
                $locked => 'This period’s Financial Report is already submitted to OSO, completed, or rejected.',
                ! $isCurrent || $newestByStatus->get($document->org_report_status_id) !== (int) $document->id => 'A newer financial report exists for '.$period.'. Submit the newest revision.',
                ! $viewable => 'This file type cannot be viewed here, so it cannot be submitted from this page. Download the original, or create the report from the Financial Report workbook.',
                $submissionLocked => 'OSO has locked FR submissions for '.$period.'. You can still prepare and view the report; wait for OSO to reopen submissions.',
                $state === 'returned' => 'Returned by OSO. Revise and submit the Financial Report for '.$period.'.',
                default => 'View this Financial Report, then submit it to OSO for '.$period.'.',
            };
            $canSubmit = $hasFile
                && in_array($state, ['draft', 'returned'], true)
                && ! $locked
                && ! $submissionLocked
                && $isCurrent
                && $newestByStatus->get($document->org_report_status_id) === (int) $document->id
                && $viewable;

            return [
                'id' => $document->id,
                'title' => $document->name,
                'original_name' => $document->original_name,
                'academic_year' => $document->academic_year,
                'semester' => $document->semester,
                'submitted_at' => $submittedAt?->toIso8601String(),
                'created_at' => $document->created_at?->toIso8601String(),
                'opened_at' => $status?->opened_at?->toIso8601String(),
                'opened_by' => $status?->opened_by,
                'reviewed_at' => $status?->reviewed_at?->toIso8601String(),
                'reviewed_by' => $status?->reviewed_by,
                'archived_at' => $status?->archived_at?->toIso8601String(),
                'notes' => $status?->notes,
                'status' => $state,
                'status_label' => $this->statusLabel($state, $status),
                'balance' => $summary !== null ? round((float) ($summary['ending_balance'] ?? 0), 2) : null,
                'preview_url' => route('office.financial.reports.preview', $document),
                'download_url' => route('office.reports.documents.view', ['document' => $document, 'download' => 1]),
                'submit_url' => route('office.financial.reports.submit', $document),
                'can_submit' => $canSubmit,
                'submission_locked' => $submissionLocked,
                'submit_help' => $help,
                'has_file' => $hasFile,
                'is_workbook' => $document->isWorkbook(),
            ];
        })->values()->all();
    }

    private function statusLabel(string $state, ?OrgReportStatus $status): string
    {
        return match ($state) {
            'draft' => 'Not submitted',
            'returned' => 'Returned for revision',
            'rejected' => 'Rejected by OSO',
            'oso_review' => $status?->opened_at ? 'Opened by OSO' : 'Submitted to OSO',
            'verified' => 'Accepted by OSO',
            'archived' => 'Archived',
            default => Str::headline($state),
        };
    }
}
