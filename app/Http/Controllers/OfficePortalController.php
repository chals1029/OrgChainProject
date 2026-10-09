<?php

namespace App\Http\Controllers;

use App\Exceptions\SemesterReportRejected;
use App\Models\ArchiveDocument;
use App\Models\ArchiveFolder;
use App\Models\ActivityComplianceDoc;
use App\Models\ActivityRegistration;
use App\Models\BudgetItem;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\OfficeAnnouncement;
use App\Models\OfficeTemplate;
use App\Models\OrgReportDocument;
use App\Models\OrgRenewalDocument;
use App\Models\OrgRenewalSubmission;
use App\Models\OrgRenewalWindow;
use App\Models\OrgReportStatus;
use App\Models\OfficeSetting;
use App\Models\OfficeUser;
use App\Models\StudentFeedback;
use App\Models\StudentOrganization;
use App\Models\TosaApplicant;
use App\Services\BudgetChainService;
use App\Services\DocxBuilder;
use App\Services\FinancialReportPreviewGate;
use App\Services\FinancialWorkbookService;
use App\Services\OrgWorkflowService;
use App\Services\OrgTimeService;
use App\Services\SemesterReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class OfficePortalController extends Controller
{
    /**
     * Shared desk payload for every Student Org page.
     *
     * @return array{office: mixed, brand: array{title: string, role: string}, navBadges: array{fr_attachments: int, ar_attachments: int}}
     */
    private function deskContext(): array
    {
        $office = Auth::guard('office')->user();
        $isOso = ($office?->office_role ?? '') === 'oso';
        $canAccessSemesterReports = $office instanceof OfficeUser
            && in_array($office->office_role, ['so', 'oso'], true);
        $officeSettings = OfficeSetting::publicValuesFor('oso');
        $brand = $this->brandFor($office->office_role);
        if (($office->office_role ?? '') === 'so') {
            $organization = $office->studentOrganization;
            $brand['title'] = $organization?->short_name ?: $organization?->name ?: $brand['title'];
        }
        if ($isOso) {
            $brand['title'] = data_get($officeSettings, 'general.office_name', $brand['title']);
        }

        return [
            'office' => $office,
            'brand' => $brand,
            'officeSettings' => $officeSettings,
            'officeUsers' => $isOso
                ? OfficeUser::query()->with('studentOrganization')->whereIn('office_role', ['so', 'oso', 'sdo', 'ovcaa', 'oc'])->latest('id')->get()
                : collect(),
            'accountOrganizations' => $isOso
                ? StudentOrganization::query()->orderBy('name')->get(['id', 'name', 'short_name'])
                : collect(),
            'navBadges' => [
                'fr_attachments' => $canAccessSemesterReports ? count($this->frAttachmentList()) : 0,
                'ar_attachments' => $canAccessSemesterReports ? count($this->arAttachmentList()) : 0,
                'reports_pending' => StudentFeedback::query()->where('status', 'pending')->count(),
            ],
        ];
    }

    /**
     * A student-organization desk is bound to one recognized organization.
     * OSO, SDO, and OVCAA are institution-wide desks and intentionally return
     * null here so their review and reporting views remain cross-organization.
     */
    private function assignedOrganizationName(?OfficeUser $office = null): ?string
    {
        $office ??= Auth::guard('office')->user();

        if (! $office instanceof OfficeUser || $office->office_role !== 'so') {
            return null;
        }

        return $office->organizationName();
    }

    private function organizationFilterForOffice(?string $requested = null): string
    {
        return $this->assignedOrganizationName()
            ?? trim((string) $requested);
    }

    /**
     * Apply the logged-in SO's organization boundary to an organization-name
     * query. Unassigned test/service accounts keep legacy behavior until an
     * organization is assigned by the system administrator.
     */
    private function constrainToOfficeOrganization($query)
    {
        $organization = $this->assignedOrganizationName();

        return $organization === null
            ? $query
            : $query->where('organization_name', $organization);
    }

    private function assertOfficeOrganizationName(?string $organization): void
    {
        $assigned = $this->assignedOrganizationName();
        if ($assigned !== null && trim((string) $organization) !== $assigned) {
            abort(403, 'This SO account is not assigned to that organization.');
        }
    }

    private function assertOfficeActivityOrganization(OrgActivity $activity): void
    {
        $this->assertOfficeOrganizationName($activity->organization_name);
    }

    /**
     * Return the reporting-period keys used consistently by office reports.
     * August through December belongs to the first semester; January through
     * May belongs to the second semester; June and July are midyear.
     *
     * @return array{date_key: ?string, academic_year: ?string, semester: ?string}
     */
    private function academicPeriod(?Carbon $date): array
    {
        if (! $date) {
            return [
                'date_key' => null,
                'academic_year' => null,
                'semester' => null,
            ];
        }

        $month = $date->month;
        $startYear = $month < 8 ? $date->year - 1 : $date->year;

        return [
            'date_key' => $date->format('Y-m-d'),
            'academic_year' => $startYear.'-'.($startYear + 1),
            'semester' => match (true) {
                $month >= 8 && $month <= 12 => '1st Semester',
                $month >= 1 && $month <= 5 => '2nd Semester',
                default => 'Midyear',
            },
        ];
    }

    private function semesterReports(): SemesterReportService
    {
        return app(SemesterReportService::class);
    }

    /**
     * @return list<string>
     */
    private function semesterReportTypes(): array
    {
        return $this->semesterReports()->types();
    }

    /**
     * @return array<string, mixed>
     */
    private function semesterReportBundle(string $organization, string $semester, string $academicYear): array
    {
        $statuses = $organization !== '' ? OrgReportStatus::query()
            ->whereIn('report_type', $this->semesterReportTypes())
            ->where('organization_name', $organization)->where('semester', $semester)
            ->where('academic_year', $academicYear)->latest('id')->get() : collect();
        $documents = $statuses->isNotEmpty() ? OrgReportDocument::query()
            ->whereIn('org_report_status_id', $statuses->groupBy('report_type')->map->first()->pluck('id'))
            ->with('reportStatus')->latest('id')->get() : collect();

        return [
            'organization' => $organization, 'college' => $this->semesterReports()->college($organization),
            'semester' => $semester, 'academic_year' => $academicYear,
            'submission_locks' => app(\App\Services\ReportSubmissionWindowService::class)->locks($academicYear, $semester),
            'reports' => $this->semesterReportRecords($statuses, $documents),
        ];
    }

    private function semesterReportRecords(Collection $statuses, Collection $documents): array
    {
        $labels = [
            'draft' => 'Not submitted', 'oso_review' => 'Pending review',
            'returned' => 'Returned for revision', 'rejected' => 'Rejected',
            'verified' => 'Verified / approved', 'archived' => 'Verified / approved · archived',
        ];
        $records = [];
        $isOso = Auth::guard('office')->user()?->office_role === 'oso';
        foreach ($this->semesterReportTypes() as $type) {
            $rows = $statuses->where('report_type', $type)->sortByDesc('id');
            $status = $rows->first();
            $state = $status?->status ?: 'draft';
            $viewable = $status && in_array($state, SemesterReportService::VIEWABLE_STATUSES, true);
            $files = $documents->where('org_report_status_id', $status?->id)
                ->where('report_type', $type)->where('organization_name', $status?->organization_name)
                ->where('semester', $status?->semester)->where('academic_year', $status?->academic_year)
                ->sortByDesc('id')->take(1)->values();
            if ($isOso) {
                $files = $viewable ? $files->filter(fn (OrgReportDocument $document): bool =>
                    $status->submitted_at ? $document->created_at->lte($status->submitted_at)
                        : in_array($state, ['verified', 'archived'], true))->values() : collect();
            }
            $hasDocument = $files->first()?->hasStoredFile() ?? false;
            $records[$type] = [
                'type' => $type, 'title' => $type === 'ar' ? 'Accomplishment Report' : 'Financial Report',
                'status' => $status, 'state' => $state, 'state_label' => $labels[$state] ?? ucfirst($state),
                'documents' => $files, 'has_document' => $hasDocument,
                'submitted_at' => $status?->submitted_at, 'opened_at' => $status?->opened_at,
                'reviewed_at' => $status?->reviewed_at, 'notes' => $status?->notes,
                'is_locked' => $this->semesterReports()->isLocked($rows),
                'can_review' => $state === 'oso_review' && ! $this->semesterReports()->isLocked($rows->filter(fn (OrgReportStatus $row): bool => $row->id !== $status->id)),
                'can_view' => (bool) $viewable,
            ];
        }
        return $records;
    }

    public function semesterReportDesk(string $defaultTab = 'ar'): View
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'oso', 403);
        $period = request()->validate([
            'organization' => ['nullable', 'string', 'max:255'],
            'academic_year' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['nullable', Rule::in(['1st Semester', '2nd Semester', 'Midyear'])],
            'tab' => ['nullable', Rule::in(['ar', 'fr'])],
        ]);
        $current = app(\App\Services\ActivityBudgetService::class)->period();
        $year = $period['academic_year'] ?? $current['academic_year'];
        $semester = $period['semester'] ?? $current['semester'];
        app(\App\Services\ActivityBudgetService::class)->dates($year, $semester);
        $organization = trim((string) ($period['organization'] ?? ''));
        $tab = $period['tab'] ?? $defaultTab;
        $organizations = StudentOrganization::query()->orderBy('name')->get(['name', 'college', 'short_name']);
        abort_if($organization !== '' && ! $organizations->contains('name', $organization), 404);
        $statuses = OrgReportStatus::query()->whereIn('report_type', $this->semesterReportTypes())
            ->where('semester', $semester)->where('academic_year', $year)->latest('id')->get();
        $currentStatuses = $statuses->unique(fn (OrgReportStatus $status): string => $status->organization_name.'|'.$status->report_type);
        $documents = OrgReportDocument::query()->whereIn('org_report_status_id', $currentStatuses->pluck('id'))
            ->with('reportStatus')->latest('id')->get()->groupBy('organization_name');
        $statusesByOrganization = $statuses->groupBy('organization_name');
        $directory = $organizations->map(function (StudentOrganization $org) use ($statusesByOrganization, $documents, $semester, $year, $tab): array {
            $reports = $this->semesterReportRecords($statusesByOrganization->get($org->name, collect()), $documents->get($org->name, collect()));
            $viewableTypes = array_keys(array_filter($reports, fn (array $report): bool => $report['can_view'] && $report['has_document']));
            $viewTab = in_array($tab, $viewableTypes, true) ? $tab : ($viewableTypes[0] ?? $tab);
            return [
                'organization' => $org->name, 'college' => $org->college, 'short_name' => $org->short_name,
                'reports' => $reports,
                'can_view_submitted' => $viewableTypes !== [],
                'view_url' => route('office.reports.index', ['organization' => $org->name, 'semester' => $semester, 'academic_year' => $year, 'tab' => $viewTab]),
            ];
        })->all();
        $selected = collect($directory)->firstWhere('organization', $organization);
        $years = OrgReportStatus::query()->whereIn('report_type', $this->semesterReportTypes())
            ->distinct()->pluck('academic_year')->filter()->all();
        $start = (int) substr($current['academic_year'], 0, 4);
        for ($offset = 0; $offset < 5; $offset++) {
            $years[] = ($start - $offset).'-'.($start - $offset + 1);
        }
        $years[] = $year;
        $years = array_values(array_unique($years));
        rsort($years);
        return view('org.semester-reports', array_merge($this->deskContext(), [
            'activeNav' => 'semester-reports',
            'organizations' => $organizations->pluck('name'), 'selectedOrganization' => $organization,
            'selectedSemester' => $semester, 'selectedYear' => $year, 'selectedTab' => $tab,
            'reportingYears' => $years, 'reportingSemesters' => ['1st Semester', '2nd Semester', 'Midyear'],
            'submissionLocks' => app(\App\Services\ReportSubmissionWindowService::class)->locks($year, $semester),
            'reportDirectory' => $directory,
            'reportBundle' => [
                'organization' => $organization, 'college' => $selected['college'] ?? null,
                'semester' => $semester, 'academic_year' => $year,
                'reports' => $selected['reports'] ?? $this->semesterReportRecords(collect(), collect()),
            ],
        ]));
    }

    private function reportRedirect(string $reportType, array $period): RedirectResponse
    {
        return redirect()->route(
            $reportType === 'ar' ? 'office.accomplishment' : 'office.financial',
            $period
        );
    }

    public function home(): View
    {
        return $this->dashboard();
    }

    /**
     * Derives the SO dashboard Pending Action Items from live data:
     * returned proposals first, then under-liquidated approvals,
     * then oldest in-review activities, then pending compliance docs.
     */
    private function buildDashboardActionItems($dbActivities): array
    {
        $items = [];
        $slug = fn (OrgActivity $a) => \Illuminate\Support\Str::slug($a->title) ?: 'activity-'.$a->id;

        foreach ($dbActivities->where('workflow_status', 'returned')->take(2) as $a) {
            $items[] = [
                'icon' => 'bi-exclamation-triangle-fill',
                'box' => 'background:#fef2f2;color:#dc2626;border-color:#fecaca;',
                'title' => 'Resubmit Activity Proposal',
                'sub' => $a->title.' – returned'.($a->returned_to ? ' by '.ucwords(str_replace('_', ' ', $a->returned_to)) : ''),
                'chip' => ['text' => 'URGENT', 'style' => 'background:#7a1222;color:#ffffff;'],
                'cta' => 'Fix',
                'url' => route('office.activities', ['activity' => $slug($a)]),
            ];
        }

        foreach ($dbActivities->whereIn('workflow_status', ['oc_approved', 'completed']) as $a) {
            if (count($items) >= 4) {
                break;
            }
            if ((int) $a->approved_budget > (int) $a->implemented_budget) {
                $items[] = [
                    'icon' => 'bi-receipt',
                    'box' => 'background:#eff6ff;color:#2563eb;border-color:#bfdbfe;',
                    'title' => 'Record Remaining Expenses',
                    'sub' => $a->title.' – ₱'.number_format(max(0, (int) $a->approved_budget - (int) $a->implemented_budget)).' remaining',
                    'chip' => null,
                    'cta' => 'Record',
                    'url' => route('office.budget'),
                ];
            }
        }

        $inReview = $dbActivities
            ->whereIn('workflow_status', ['college_review', 'oso_review', 'sdo_review', 'ovcaa_review', 'oc_review', 'verification'])
            ->sortBy(fn (OrgActivity $a) => optional($a->starts_at)->timestamp ?? PHP_INT_MAX);
        foreach ($inReview as $a) {
            if (count($items) >= 4) {
                break;
            }
            $items[] = [
                'icon' => 'bi-hourglass-split',
                'box' => 'background:#fefce8;color:#b45309;border-color:#fef08a;',
                'title' => 'Track Review: '.$a->title,
                'sub' => app(OrgWorkflowService::class)->label($a->workflow_status ?: 'created').' stage',
                'chip' => null,
                'cta' => 'View',
                'url' => route('office.activities', ['activity' => $slug($a)]),
            ];
        }

        if (count($items) < 4) {
            $pendingDocs = ActivityComplianceDoc::query()
                ->whereIn('org_activity_id', $dbActivities->pluck('id')->all())
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->unique(fn (ActivityComplianceDoc $doc): string => $doc->org_activity_id.'|'.($doc->doc_key ?: $doc->title))
                ->take(4 - count($items));
            foreach ($pendingDocs as $doc) {
                $activity = $dbActivities->firstWhere('id', $doc->org_activity_id);
                $items[] = [
                    'icon' => 'bi-file-earmark-medical',
                    'box' => 'background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff;',
                    'title' => 'Upload: '.$doc->title,
                    'sub' => ($activity?->title ?? 'Activity document').' – requirement pending',
                    'chip' => null,
                    'cta' => 'Upload',
                    'url' => $activity
                        ? route('office.activities', ['activity' => $slug($activity)])
                        : route('office.activities'),
                ];
            }
        }

        return array_slice($items, 0, 4);
    }

    public function dashboard(): View
    {
        $officeRole = Auth::guard('office')->user()?->office_role ?? '';
        $isOsoDashboard = $officeRole === 'oso';
        $pipeline = $this->pipelineActivities();
        $workflow = app(OrgWorkflowService::class);
        $upcomingFiltered = collect($pipeline)
            ->filter(function (array $item): bool {
                if (($item['upcoming_at'] ?? null) === null) {
                    return false;
                }

                return \Illuminate\Support\Carbon::parse($item['upcoming_at'])->isFuture()
                    || ! empty($item['force_upcoming']);
            })
            ->sortBy('upcoming_at')
            ->values();

        $upcomingList = $upcomingFiltered->isNotEmpty()
            ? $upcomingFiltered->take(4)->values()
            : collect($pipeline)->take(4)->values();

        $upcoming = $upcomingList->first() ?? collect($pipeline)->first();

        $dbActivities = $this->constrainToOfficeOrganization(OrgActivity::query())
            ->get()
            ->filter(fn (OrgActivity $activity): bool => $workflow->canViewActivityDetails(
                $officeRole,
                $activity->workflow_status ?: 'created',
            ))
            ->values();
        $approved = $dbActivities->whereIn('workflow_status', ['oc_approved'])->count();
        $pending = $dbActivities->whereNotIn('workflow_status', ['oc_approved'])->count();
        if (! $isOsoDashboard && $dbActivities->isEmpty()) {
            $approved = collect($pipeline)->whereIn('status_key', ['ovcaa_approved', 'completed', 'oc_approved'])->count();
            $pending = collect($pipeline)->whereIn('status_key', ['created', 'verification', 'pending', 'returned', 'oso_review', 'sdo_review', 'ovcaa_review', 'oc_review'])->count();
        }

        $reviewStages = ['college_review', 'oso_review', 'sdo_review', 'ovcaa_review', 'oc_review', 'verification', 'pending'];
        if ($dbActivities->isNotEmpty()) {
            $kpiTotal = $dbActivities->count();
            $kpiApproved = $dbActivities->whereIn('workflow_status', ['oc_approved', 'completed'])->count();
            $kpiInReview = $dbActivities->whereIn('workflow_status', $reviewStages)->count();
            $kpiReturned = $dbActivities->where('workflow_status', 'returned')->count();
            $kpiOvcaa = $dbActivities->where('workflow_status', 'ovcaa_review')->count();
            $kpiOc = $dbActivities->where('workflow_status', 'oc_review')->count();
        } elseif ($isOsoDashboard) {
            $kpiTotal = 0;
            $kpiApproved = 0;
            $kpiInReview = 0;
            $kpiReturned = 0;
            $kpiOvcaa = 0;
            $kpiOc = 0;
        } else {
            $pipe = collect($pipeline);
            $kpiTotal = $pipe->count();
            $kpiApproved = $pipe->whereIn('status_key', ['ovcaa_approved', 'completed', 'oc_approved'])->count();
            $kpiInReview = $pipe->whereIn('status_key', ['created', 'verification', 'pending', 'oso_review', 'sdo_review', 'ovcaa_review', 'oc_review', 'college_review'])->count();
            $kpiReturned = $pipe->where('status_key', 'returned')->count();
            $kpiOvcaa = $pipe->where('status_key', 'ovcaa_review')->count();
            $kpiOc = $pipe->where('status_key', 'oc_review')->count();
        }

        $fundYear = app(\App\Services\ActivityBudgetService::class)->period()['academic_year'];
        $fundAccounts = $this->constrainToOfficeOrganization(OrgFundAccount::query())
            ->where('fiscal_year', $fundYear)->get();
        $fundAccount = $fundAccounts->first();
        $fundBalances = $fundAccounts->map(fn (OrgFundAccount $account) =>
            app(\App\Services\OrganizationCashLedger::class)->balance($account));
        $totalFunds = round((float) $fundBalances->sum('total'), 2);
        $utilized = round((float) $fundBalances->sum('spent'), 2);
        $remaining = round((float) $fundBalances->sum('cash'), 2);

        $urgency = $this->constrainToOfficeOrganization(OrgActivity::query())
            ->whereIn('workflow_status', ['oso_review', 'returned', 'college_review'])
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->map(fn (OrgActivity $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'organization' => $a->organization_name,
                'status' => $workflow->label($a->workflow_status ?: 'created'),
                'due' => optional($a->starts_at)->format('M j, Y g:i A') ?? 'TBA',
                'sla_hours' => max(1, now()->diffInHours($a->starts_at ?? now()->addDays(3), false)),
            ]);

        $chartPayload = $this->dashboardChartPayload($dbActivities);

        // SO dashboard charts: per-category allocated vs utilized (deduped) + submission trend.
        $soBudgetChart = $this->constrainToOfficeOrganization(BudgetItem::query())
            ->orderByDesc('allocated')
            ->get()
            ->groupBy(fn (BudgetItem $i) => strtolower(trim((string) ($i->category ?: $i->title))))
            ->map(fn ($rows) => [
                'category' => $rows->first()->category ?: $rows->first()->title,
                'allocated' => (int) $rows->sum('allocated'),
                'utilized' => (int) $rows->sum('utilized'),
            ])
            ->values();

        // Recent updates feed: latest office announcements + latest activity movements.
    $annUpdates = OfficeAnnouncement::query()->latest()->limit(2)->get()->map(fn (OfficeAnnouncement $a) => [
        'chip' => 'OSO',
        'chip_style' => 'background: #e0f2fe; color: #0284c7;',
        'title' => $a->title,
        'date' => optional($a->updated_at)->format('M j, Y') ?? '',
        'sort' => optional($a->updated_at)->timestamp ?? 0,
    ]);
    $actUpdates = $dbActivities->sortByDesc(fn (OrgActivity $a) => optional($a->updated_at)->timestamp ?? 0)->take(3)->map(function (OrgActivity $a) {
        $label = app(OrgWorkflowService::class)->label($a->workflow_status ?: 'created');

        return [
            'chip' => strtoupper($a->workflow_status === 'oc_approved' ? 'OC' : ($a->workflow_status ?: 'SO')),
            'chip_style' => $a->workflow_status === 'oc_approved'
                ? 'background: #dcfce7; color: #15803d;'
                : 'background: #e0f2fe; color: #0284c7;',
            'title' => $a->title.' – '.$label,
            'date' => optional($a->updated_at)->format('M j, Y') ?? '',
            'sort' => optional($a->updated_at)->timestamp ?? 0,
        ];
    });
    $recentUpdates = $annUpdates->concat($actUpdates)->sortByDesc('sort')->take(4)->values();

    return view('org.dashboard', array_merge($this->deskContext(), [
            'activeNav' => 'dashboard',
            'stats' => [
                'total' => $isOsoDashboard ? $dbActivities->count() : ($dbActivities->count() ?: count($pipeline)),
                'approved' => $approved,
                'pending' => $pending,
                'in_review' => $kpiInReview,
                'returned' => $kpiReturned,
                'ovcaa_pending' => $kpiOvcaa,
                'oc_pending' => $kpiOc,
                'expenses' => $utilized,
            ],
            'actionItems' => $this->buildDashboardActionItems($dbActivities),
            'transparency' => [
                'allocated' => $totalFunds,
                'total_funds' => $totalFunds,
                'beginning_balance' => round((float) $fundBalances->sum('opening_balance'), 2),
                'utilized' => $utilized,
                'remaining' => $remaining,
                'percent' => $totalFunds > 0 ? (int) round(($utilized / $totalFunds) * 100) : 0,
                'remaining_percent' => $totalFunds > 0 ? (int) round(($remaining / $totalFunds) * 100) : 0,
            ],
            'fundAccount' => $fundAccount,
            'urgencyQueue' => $urgency,
            'chartPayload' => $chartPayload,
            'soBudgetChart' => $soBudgetChart,
            'osoOverview' => $this->buildOsoOverview($dbActivities),
        'recentUpdates' => $recentUpdates,
            'workflowStages' => OrgWorkflowService::FLOW,
            'upcoming' => $upcoming,
            'upcomingList' => $upcomingList,
            'tracker' => array_slice($pipeline, 0, 5),
            'updates' => $pipeline,
            'studentFeedback' => StudentFeedback::query()->latest()->limit(8)->get(),
        ]));
    }

    public function analytics(): View
    {
        return view('org.analytics', array_merge($this->deskContext(), $this->buildAnalyticsData(), [
            'activeNav' => 'analytics',
        ]));
    }

    /**
     * Real CSV export of the Analytics page (overview + college table + activities).
     */
    public function exportAnalytics(): StreamedResponse
    {
        $data = $this->buildAnalyticsData();
        $filename = 'analytics-financial-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            $money = fn ($n) => 'PHP '.number_format((float) $n, 2);

            fputcsv($out, ['OrgChain — Financial & Analytics Report']);
            fputcsv($out, ['Generated at', now()->toDateTimeString()]);
            fputcsv($out, []);

            $o = $data['overview'];
            fputcsv($out, ['OVERALL BUDGET HEALTH']);
            fputcsv($out, ['Health score', ($o['healthScore'] ?? 0).'%']);
            fputcsv($out, ['Total budget allocated', $money($o['totalAllocated'] ?? 0)]);
            fputcsv($out, ['Total budget utilized', $money($o['totalUtilized'] ?? 0)]);
            fputcsv($out, ['Remaining reserve', $money($o['remainingBalance'] ?? 0)]);
            fputcsv($out, ['Burn rate', ($o['burnRate'] ?? 0).'%']);
            fputcsv($out, ['Compliance rate', ($o['complianceRate'] ?? 0).'%']);
            fputcsv($out, []);

            fputcsv($out, ['COLLEGE PERFORMANCE']);
            fputcsv($out, ['College', 'Activities', 'Completion %', 'Utilization %', 'Approved budget', 'Implemented budget']);
            foreach ($data['collegeStats'] as $row) {
                fputcsv($out, [
                    $row['college'] ?? '',
                    $row['activities'] ?? 0,
                    ($row['completion_percent'] ?? 0).'%',
                    ($row['utilization_percent'] ?? 0).'%',
                    $money($row['approved_budget'] ?? 0),
                    $money($row['implemented_budget'] ?? 0),
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['ACTIVITY FINANCIALS']);
            fputcsv($out, ['Activity', 'Scope', 'College', 'Allocated', 'Utilized', 'Remaining', 'Burn rate %', 'Status', 'Month', 'Year']);
            foreach ($data['activityFinancials'] as $row) {
                fputcsv($out, [
                    $row['name'] ?? '',
                    $row['scope_label'] ?? '',
                    $row['college'] ?? '',
                    $money($row['allocated'] ?? 0),
                    $money($row['utilized'] ?? 0),
                    $money($row['remaining'] ?? 0),
                    $row['burn_rate'] ?? 0,
                    $row['status'] ?? '',
                    $row['month'] ?? '',
                    $row['year'] ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Canonical registry of recognized student orgs (AY 2025-2026)
     * from the database, merged with any org names already encoded
     * in budget records. Falls back to the config registry when the
     * table hasn't been seeded yet.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function recognizedOrgNames()
    {
        $assigned = $this->assignedOrganizationName();
        if ($assigned !== null) {
            return collect([$assigned]);
        }

        $registered = StudentOrganization::active()->orderBy('name')->pluck('name');

        if ($registered->isEmpty()) {
            $registered = collect(config('student_orgs.organizations', []))->pluck('name');
        }

        $submitted = OrgActivity::query()->whereNotNull('organization_name')->distinct()->pluck('organization_name');
        $encoded = BudgetItem::query()->whereNotNull('organization_name')->distinct()->pluck('organization_name');

        return $registered->merge($submitted)->merge($encoded)->filter()->unique()->sort()->values();
    }

    /**
     * Full org records (name + college) for autocomplete datalists.
     *
     * @return \Illuminate\Support\Collection<int, array{name: string, college: ?string}>
     */
    private function recognizedOrgsForForm()
    {
        $assigned = $this->assignedOrganizationName();
        $rows = StudentOrganization::active()->orderBy('name')->get(['name', 'college']);

        if ($rows->isEmpty()) {
            $rows = collect(config('student_orgs.organizations', []));
        }

        return $rows->map(fn ($org) => [
            'name' => is_array($org) ? ($org['name'] ?? '') : $org->name,
            'college' => is_array($org) ? ($org['college'] ?? null) : $org->college,
        ])->filter(fn ($org) => filled($org['name']) && ($assigned === null || $org['name'] === $assigned))->values();
    }

    /**
     * Normalize imported college names into the short department labels used
     * by the OSO dashboards and filters.
     */
    private function departmentLabel(?string $college): string
    {
        $display = trim((string) $college);
        if ($display === '') {
            return 'Unassigned';
        }

        $normalized = preg_replace('/[^a-z0-9]+/', ' ', strtolower($display)) ?? '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? '');

        if (str_contains($normalized, 'accountancy')
            || str_contains($normalized, 'international hospitality management')) {
            return 'CABEIHM';
        }
        if (str_contains($normalized, 'arts and sciences')) {
            return 'CAS';
        }
        if (str_contains($normalized, 'criminal justice education')) {
            return 'CCJE';
        }
        if (str_contains($normalized, 'health sciences')
            || str_contains($normalized, 'nursing and allied health sciences')) {
            return 'CHS';
        }
        if (str_contains($normalized, 'informatics and computing sciences')) {
            return 'CICS';
        }
        if (str_contains($normalized, 'teacher education')) {
            return 'CTE';
        }
        if (in_array($normalized, ['laboratory school', 'lab school'], true)) {
            return 'LAB SCHOOL';
        }
        if ($normalized === 'campus wide') {
            return 'Campus Wide';
        }

        return $display;
    }

    /**
     * Build one shared department list from the values used by activities,
     * budget items, and registered organizations. Each option keeps its raw
     * aliases so a short label can filter inconsistent imported values.
     *
     * @return list<array{value: string, label: string, values: list<string>}>
     */
    private function departmentOptions(): array
    {
        $catalog = [
            'CABEIHM' => [
                'CABEIHM',
                'College of Accountancy, Business, Economics, and International Hospitality Management',
                'College of Accountancy, Business, Economics and International Hospitality Management',
            ],
            'CAS' => ['CAS', 'College of Arts and Sciences'],
            'CCJE' => ['CCJE', 'College of Criminal Justice Education'],
            'CHS' => [
                'CHS',
                'College of Health Sciences',
                'College of Nursing and Allied Health Sciences',
            ],
            'CICS' => ['CICS', 'College of Informatics and Computing Sciences'],
            'CTE' => ['CTE', 'College of Teacher Education'],
            'LAB SCHOOL' => ['LAB SCHOOL', 'Laboratory School', 'Lab School'],
        ];

        $observed = collect()
            ->merge(OrgActivity::query()->whereNotNull('college')->pluck('college'))
            ->merge(BudgetItem::query()->whereNotNull('college')->pluck('college'))
            ->merge(StudentOrganization::query()->whereNotNull('college')->pluck('college'))
            ->map(fn ($college): string => trim((string) $college))
            ->filter()
            ->unique()
            ->values();

        foreach ($observed as $rawCollege) {
            $label = $this->departmentLabel($rawCollege);
            if (in_array($label, ['Unassigned', 'Campus Wide'], true)) {
                continue;
            }

            $catalog[$label] ??= [$label];
            $catalog[$label][] = $rawCollege;
            $catalog[$label] = array_values(array_unique($catalog[$label]));
        }

        return collect($catalog)
            ->map(fn (array $values, string $label): array => [
                'value' => $label,
                'label' => $label,
                'values' => array_values(array_unique($values)),
            ])
            ->sortBy(fn (array $option): string => $option['label'])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function departmentValues(?string $department): array
    {
        $department = trim((string) $department);
        if ($department === '') {
            return [];
        }

        $option = collect($this->departmentOptions())->firstWhere('value', $department);

        return $option['values'] ?? ['__no_matching_department__'];
    }

    /**
     * Live Budget Utilization dataset built from the database
     * (final-approved org_activities + budget_items +
     * expense_receipt_reviews + budget chain), shaped exactly like the
     * front-end expects. Pending activity workflows are deliberately excluded.
     */
    private function buildLiveBudgetDataset(?string $organizationFilter = null, ?array $departmentValues = null): array
    {
        $organizationFilter = $this->organizationFilterForOffice($organizationFilter);
        $service = app(\App\Services\ActivityBudgetService::class);
        $activities = OrgActivity::query()->visibleToStudents()
            ->when(filled($organizationFilter), fn ($q) => $q->where('organization_name', $organizationFilter))
            ->when($departmentValues !== null, fn ($q) => $q->whereIn('college', $departmentValues))->orderBy('id')->get();
        $receipts = $service->receipts()->whereIn('org_activity_id', $activities->pluck('id'))->latest('id')->get()->groupBy('org_activity_id');
        $events = DB::connection('mysql')->table('activity_workflow_events')->whereIn('org_activity_id', $activities->pluck('id'))->orderByDesc('id')->get()->groupBy('org_activity_id');
        $uploaders = OfficeUser::query()->pluck('name', 'id');
        $entries = [];
        foreach ($activities as $a) {
            $rows = $receipts->get($a->id, collect());
            $expenses = $rows->map(fn ($r) => [
                'id' => $r->id, 'cat' => $r->category ?: 'General', 'desc' => trim($r->item_name.' · '.$r->supplier),
                'date' => $r->expense_date->format('M j, Y'), 'qty' => $r->quantity, 'unitCost' => (float) $r->unit_cost,
                'amount' => round($r->quantity * (float) $r->unit_cost, 2),
                'status' => $r->verification_status === 'pending_seal' ? 'Pending seal' : 'Sealed',
                'receiptFile' => $r->receipt_name, 'receiptUrl' => route('office.budget.receipts.view', $r),
                'downloadUrl' => route('office.budget.receipts.view', ['review' => $r, 'download' => 1]),
                'receiptAttachments' => collect($r->receiptFiles())->values()->map(fn (array $file, int $index) => [
                    'name' => $file['name'],
                    'key' => ($file['disk'] ?? $r->receipt_disk ?? 'public').'|'.$file['path'],
                    'receiptUrl' => route('office.budget.receipts.view', ['review' => $r, 'attachment' => $index]),
                    'downloadUrl' => route('office.budget.receipts.view', ['review' => $r, 'attachment' => $index, 'download' => 1]),
                    'isImage' => in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']),
                ])->all(),
                'uploadedBy' => $uploaders->get($r->uploaded_by, 'Legacy upload'),
                'paymentMethod' => $r->payment_method ? strtoupper(str_replace('_', ' ', $r->payment_method)) : 'Not recorded',
                'scanSummary' => 'Receipt photo · details entered by SO',
                'uploadedAt' => OrgTimeService::format($r->created_at), 'hash' => $r->chain_hash,
                'retryUrl' => $r->verification_status === 'pending_seal' ? route('office.budget.receipts.retry', $r) : null,
                'isImage' => in_array(strtolower(pathinfo($r->receipt_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']),
            ])->values()->all();
            $documents = collect($expenses)->flatMap(function (array $expense) {
                $attachments = $expense['receiptAttachments'] ?? [];
                return collect($attachments ?: [[
                    'name' => $expense['receiptFile'], 'receiptUrl' => $expense['receiptUrl'],
                    'downloadUrl' => $expense['downloadUrl'], 'isImage' => $expense['isImage'],
                ]])->map(fn (array $file) => array_merge($expense, [
                    'name' => $file['name'], 'receiptUrl' => $file['receiptUrl'],
                    'downloadUrl' => $file['downloadUrl'], 'isImage' => $file['isImage'],
                    'match' => $expense['status'], 'size' => 'Receipt photo',
                ]));
            })->unique('key')->values()->all();
            $timeline = $rows->map(fn ($r) => [
                'title' => 'Receipt #'.$r->id.' · '.$r->item_name.' · Php '.number_format($r->quantity * (float) $r->unit_cost, 2),
                'date' => OrgTimeService::format($r->created_at), 'sort' => $r->created_at?->timestamp ?? 0,
                'green' => $r->verification_status !== 'pending_seal', 'receiptUrl' => route('office.budget.receipts.view', $r),
            ])->concat($events->get($a->id, collect())->map(fn ($event) => [
                'title' => strtoupper($event->role).' · '.app(OrgWorkflowService::class)->label($event->to_status).' · '.($event->actor_name ?: 'Office'),
                'date' => OrgTimeService::format($event->created_at), 'sort' => Carbon::parse($event->created_at)->timestamp,
                'green' => $event->to_status === 'oc_approved',
            ]))->sortByDesc('sort')->values()->all();
            $categoryAmounts = $rows->groupBy(fn ($r) => $r->category ?: 'General')->map(fn ($group) => round($group->sum(fn ($r) => $r->quantity * (float) $r->unit_cost), 2));
            $postedSpent = round((float) $categoryAmounts->sum(), 2);
            $legacySpent = max(0, round((float) $a->implemented_budget - $postedSpent, 2));
            if ($legacySpent > 0) $categoryAmounts['Earlier recorded spending'] = $legacySpent;
            $approved = (float) $a->approved_budget;
            $actual = round($postedSpent + $legacySpent, 2);
            $entries['activity-'.$a->id] = [
                'activityId' => $a->id, 'orgName' => $a->organization_name, 'orgCategory' => $a->college ?: 'Campus Wide',
                'orgUnit' => $a->college ?: 'Campus Wide', 'actName' => $a->title, 'actType' => 'Approved activity',
                'actDate' => $a->starts_at?->format('M j, Y') ?? 'TBA', 'actVenue' => $a->location ?: 'TBA',
                ...$this->academicPeriod($a->starts_at),
                'scope' => str_contains($a->activity_scope, 'off') ? 'Off-Campus' : 'In-Campus',
                'approvedBudget' => $approved, 'actualExpenses' => $actual, 'remainingBal' => round($approved - $actual, 2),
                'utilRate' => $approved > 0 ? round($actual / $approved * 100, 1) : 0,
                'rateStatus' => $actual > $approved ? 'Over budget' : 'Recorded utilization', 'rateStatusColor' => '#15803d', 'stepperStep' => 4,
                'categories' => ['Activity budget'],
                'donutData' => $categoryAmounts->values()->all() ?: [0], 'donutColors' => ['#8b1828', '#ca8a04', '#16a34a', '#1d4ed8'],
                'barAllocated' => [$approved],
                'barActual' => [$actual],
                'expenses' => $expenses, 'documents' => $documents, 'timeline' => $timeline,
                'verifier' => 'Blockchain receipt confirmations',
                'verifiedDate' => OrgTimeService::format($rows->where('verification_status', 'verified')->first()?->updated_at) ?: 'No confirmed receipts',
                'remarks' => 'Expenses reduce the activity balance and organization cash once. Earlier recorded spending is retained separately from receipt-supported spending.',
                'hash' => $rows->whereNotNull('chain_hash')->first()?->chain_hash ?? 'No sealed receipt',
                'receiptPackageUrl' => route('office.budget.receipts.package', ['activity_id' => $a->id]),
            ];
        }
        $all = collect($entries);
        $approved = (float) $all->sum('approvedBudget');
        $actual = (float) $all->sum('actualExpenses');
        $entries['all'] = [
            'orgName' => $organizationFilter ?: 'All Recognized Organizations', 'orgCategory' => 'Organization Portfolio',
            'orgUnit' => $organizationFilter ? 'Organization-wide' : 'All Colleges / Units',
            'actName' => 'Consolidated Portfolio', 'actType' => 'Approved activities', 'actDate' => 'All periods', 'actVenue' => 'All venues',
            'scope' => 'University-Wide', 'approvedBudget' => $approved, 'actualExpenses' => $actual,
            'remainingBal' => round($approved - $actual, 2), 'utilRate' => $approved > 0 ? round($actual / $approved * 100, 1) : 0,
            'rateStatus' => 'Portfolio View', 'rateStatusColor' => '#0284c7', 'stepperStep' => 4,
            'categories' => [], 'donutData' => [], 'donutColors' => [], 'barAllocated' => [], 'barActual' => [],
            'expenses' => $all->flatMap(fn ($e) => $e['expenses'])->all(), 'documents' => $all->flatMap(fn ($e) => $e['documents'])->all(),
            'timeline' => $all->flatMap(fn ($e) => $e['timeline'])->sortByDesc('sort')->values()->all(),
            'verifier' => 'Activity receipt history', 'verifiedDate' => 'See individual receipts', 'remarks' => 'All approved activity records.',
            'hash' => 'Select an activity for its receipt hashes',
        ];
        $order = array_merge(['all'], array_keys($all->all()));
        $options = array_map(fn ($key) => ['key' => $key, 'label' => $entries[$key]['actName'].' ('.$entries[$key]['scope'].')'], $order);
        return ['hasLive' => true, 'entries' => $entries, 'order' => $order, 'options' => $options, 'defaultKey' => 'all',
            'scopeTotals' => [(float) $all->where('scope', 'In-Campus')->sum('actualExpenses'), (float) $all->where('scope', 'Off-Campus')->sum('actualExpenses')]];
    }

    /**
     * Serves an encoded expense receipt file for viewing.
     */
    public function viewReceipt(ExpenseReceiptReview $review)
    {
        abort_unless(in_array(Auth::guard('office')->user()?->office_role, ['so', 'oso'], true), 403);
        $this->assertOfficeOrganizationName($review->organization_name);
        $attachmentIndex = max(0, (int) request()->query('attachment', 0));
        $attachment = $review->receiptFiles()[$attachmentIndex] ?? null;
        abort_unless(is_array($attachment) && filled($attachment['path'] ?? null), 404);
        $disk = Storage::disk($attachment['disk'] ?: $review->receipt_disk ?: 'public');
        abort_unless($disk->exists($attachment['path']), 404);
        $name = $attachment['name'] ?: $review->receipt_name;

        return request()->boolean('download')
            ? $disk->download($attachment['path'], $name)
            : $disk->response($attachment['path'], $name);
    }

    /**
     * Shared data builder for the Analytics page and its CSV export.
     */
    private function buildAnalyticsData(): array
    {
        $pipeline = $this->pipelineActivities();
        $workflow = app(OrgWorkflowService::class);

        $byStatus = collect($pipeline)
            ->groupBy('status_key')
            ->map(fn ($rows) => $rows->count())
            ->all();

        $organization = $this->organizationFilterForOffice(request('organization', ''));
        $dbActivities = $this->constrainToOfficeOrganization(OrgActivity::query())
            ->when($organization !== '', fn ($q) => $q->where('organization_name', $organization))
            ->get()
            ->filter(fn (OrgActivity $activity): bool => $workflow->canViewActivityDetails(
                Auth::guard('office')->user()?->office_role ?? '',
                $activity->workflow_status ?: 'created',
            ))
            ->values();
        $budgetItems = $this->constrainToOfficeOrganization(BudgetItem::query())
            ->when($organization !== '', fn ($q) => $q->where('organization_name', $organization))
            ->orderByDesc('utilized')
            ->get();

        $collegeStats = $dbActivities
            ->groupBy(fn (OrgActivity $a) => $a->college ?: 'Unassigned')
            ->map(function ($rows, $college) use ($budgetItems) {
                $approved = (int) $budgetItems->where('college', $college)->where('is_approved', true)->sum('allocated');
                $implemented = (int) $budgetItems->where('college', $college)->sum('utilized');
                $allocated = (int) $budgetItems->where('college', $college)->sum('allocated');

                return [
                    'college' => $college,
                    'activities' => $rows->count(),
                    'completed' => $rows->where('workflow_status', 'oc_approved')->count(),
                    'completion_percent' => $rows->count() > 0
                        ? (int) round(($rows->where('workflow_status', 'oc_approved')->count() / $rows->count()) * 100)
                        : 0,
                    'allocated' => $allocated,
                    'approved_budget' => $approved,
                    'implemented_budget' => $implemented,
                    'utilization_percent' => $allocated > 0 ? (int) round(($implemented / $allocated) * 100) : 0,
                ];
            })
            ->sortByDesc('utilization_percent')
            ->values();

        if ($collegeStats->isEmpty() && $organization === '') {
            $collegeStats = collect([
                ['college' => 'CICS', 'activities' => 3, 'completed' => 1, 'completion_percent' => 33, 'allocated' => 50000, 'approved_budget' => 45000, 'implemented_budget' => 32000, 'utilization_percent' => 64],
                ['college' => 'CHS', 'activities' => 2, 'completed' => 1, 'completion_percent' => 50, 'allocated' => 42500, 'approved_budget' => 42500, 'implemented_budget' => 24900, 'utilization_percent' => 59],
                ['college' => 'CAS', 'activities' => 2, 'completed' => 1, 'completion_percent' => 50, 'allocated' => 115000, 'approved_budget' => 115000, 'implemented_budget' => 62750, 'utilization_percent' => 55],
            ]);
        }

        $activityFinancials = $dbActivities->map(function (OrgActivity $a) use ($workflow) {
            $allocated = (int) ($a->approved_budget ?: 0);
            $utilized = (int) ($a->implemented_budget ?: 0);
            $startsAt = $a->starts_at instanceof Carbon
                ? $a->starts_at
                : ($a->starts_at ? Carbon::parse($a->starts_at) : null);
            $monthNumber = $startsAt?->month;
            $calendarYear = $startsAt?->year;
            $academicYearStart = $monthNumber !== null && $monthNumber < 8
                ? $calendarYear - 1
                : $calendarYear;
            $academicYear = $monthNumber !== null
                ? $academicYearStart.'-'.($academicYearStart + 1)
                : null;
            $semester = match (true) {
                $monthNumber >= 8 && $monthNumber <= 12 => 'sem1',
                $monthNumber >= 1 && $monthNumber <= 5 => 'sem2',
                $monthNumber >= 6 && $monthNumber <= 7 => 'midyear',
                default => null,
            };

            return [
                'id' => $a->id,
                'name' => $a->title,
                'scope' => $a->activity_scope ?: 'in_campus',
                'scope_label' => ($a->activity_scope === 'local_off_campus') ? 'Off-Campus' : 'In-Campus',
                'college' => $a->college,
                'organization' => $a->organization_name,
                'allocated' => $allocated,
                'utilized' => $utilized,
                'remaining' => max(0, $allocated - $utilized),
                'burn_rate' => $allocated > 0 ? round(($utilized / $allocated) * 100, 1) : 0,
                'status' => $workflow->label($a->workflow_status ?: 'created'),
                'status_style' => $a->workflow_status === 'oc_approved' ? 'green' : 'blue',
                'status_key' => $a->workflow_status ?: 'created',
                'month' => $startsAt?->format('M') ?? 'N/A',
                'month_number' => $monthNumber,
                'year' => $calendarYear ?? now()->year,
                'date_key' => $startsAt?->format('Y-m-d'),
                'academic_year' => $academicYear,
                'semester' => $semester,
            ];
        })->values()->all();

        if ($activityFinancials === [] && $organization === '') {
            $activityFinancials = [
                ['id' => 'demo-1', 'name' => 'Innovation Fair Booth Series', 'scope' => 'in_campus', 'scope_label' => 'In-Campus', 'college' => 'CICS', 'organization' => null, 'allocated' => 15000, 'utilized' => 15000, 'remaining' => 0, 'burn_rate' => 100, 'status' => 'OC Approved', 'status_style' => 'green', 'status_key' => 'oc_approved', 'month' => 'Jul', 'month_number' => 7, 'year' => 2026, 'date_key' => '2026-07-05', 'academic_year' => '2025-2026', 'semester' => 'midyear'],
                ['id' => 'demo-2', 'name' => 'Leadership Summit 2026', 'scope' => 'local_off_campus', 'scope_label' => 'Off-Campus', 'college' => 'CAS', 'organization' => null, 'allocated' => 75000, 'utilized' => 42750, 'remaining' => 32250, 'burn_rate' => 57, 'status' => 'OSO Review', 'status_style' => 'blue', 'status_key' => 'oso_review', 'month' => 'Sep', 'month_number' => 9, 'year' => 2026, 'date_key' => '2026-09-20', 'academic_year' => '2026-2027', 'semester' => 'sem1'],
            ];
        }

        $inCampus = collect($activityFinancials)->where('scope', 'in_campus');
        $offCampus = collect($activityFinancials)->where('scope', 'local_off_campus');
        $totalAllocated = (int) collect($activityFinancials)->sum('allocated');
        $totalUtilized = (int) collect($activityFinancials)->sum('utilized');

        // Live health + compliance (no hardcoded constants):
        // health blends budget discipline (burn near the 75% target) with
        // approval completion; compliance is the share of approved docs.
        $fallbackAllocated = $organization === '' ? 185000 : 0;
        $fallbackUtilized = $organization === '' ? 115150 : 0;
        $burnRate = ($totalAllocated ?: $fallbackAllocated) > 0
            ? round((($totalUtilized ?: $fallbackUtilized) / ($totalAllocated ?: $fallbackAllocated)) * 100, 1)
            : 0;
        $completionPct = $dbActivities->count() > 0
            ? round(($dbActivities->whereIn('workflow_status', ['oc_approved', 'completed'])->count() / $dbActivities->count()) * 100, 1)
            : 0;
        $budgetHealth = max(0, 100 - abs($burnRate - 75) * 2);
        $healthScore = (int) round((0.6 * $budgetHealth) + (0.4 * $completionPct));
        $docTotal = ActivityComplianceDoc::query()->whereIn('org_activity_id', $dbActivities->pluck('id'))->count();
        $docApproved = ActivityComplianceDoc::query()->whereIn('org_activity_id', $dbActivities->pluck('id'))->where('status', 'approved')->count();
        $complianceRate = $docTotal > 0 ? round(($docApproved / $docTotal) * 100, 1) : 100.0;

        return [
            'byStatus' => $byStatus,
            'pipeline' => $pipeline,
            'budgetItems' => $budgetItems->take(6),
            'activityFinancials' => $activityFinancials,
            'organizations' => $this->recognizedOrgNames(),
            'selectedOrganization' => $organization,
            'collegeStats' => $collegeStats,
            'topUtilization' => $collegeStats->sortByDesc('utilization_percent')->take(3)->values(),
            'topActivities' => $collegeStats->sortByDesc('activities')->take(3)->values(),
            'chartSeries' => [
                'labels' => $collegeStats->pluck('college')->map(fn ($c) => \Illuminate\Support\Str::limit($c, 18))->values(),
                'allocated' => $collegeStats->pluck('allocated')->values(),
                'implemented' => $collegeStats->pluck('implemented_budget')->values(),
                'approved' => $collegeStats->pluck('approved_budget')->values(),
                'activityCounts' => $collegeStats->pluck('activities')->values(),
            ],
            'overview' => [
                'healthScore' => $healthScore,
                'totalAllocated' => $totalAllocated ?: $fallbackAllocated,
                'totalUtilized' => $totalUtilized ?: $fallbackUtilized,
                'remainingBalance' => max(0, ($totalAllocated ?: $fallbackAllocated) - ($totalUtilized ?: $fallbackUtilized)),
                'burnRate' => $burnRate,
                'complianceRate' => $complianceRate,
                'inCampusAllocated' => (int) $inCampus->sum('allocated'),
                'inCampusUtilized' => (int) $inCampus->sum('utilized'),
                'inCampusRemaining' => (int) max(0, $inCampus->sum('allocated') - $inCampus->sum('utilized')),
                'offCampusAllocated' => (int) $offCampus->sum('allocated'),
                'offCampusUtilized' => (int) $offCampus->sum('utilized'),
                'offCampusRemaining' => (int) max(0, $offCampus->sum('allocated') - $offCampus->sum('utilized')),
            ],
        ];
    }

    public function activities(Request $request): View
    {
        $organization = trim((string) $request->query('organization', ''));
        $academicYear = trim((string) $request->query('academic_year', ''));
        $allActivities = $this->orgActivitiesList($organization, $academicYear);
        $activityAcademicYears = collect($this->activityAcademicYearOptions($organization))
            ->merge(collect($allActivities)->pluck('academic_year'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
        $selectedSlug = $request->query('activity');
        $selectedActivity = null;

        if ($selectedSlug) {
            $selectedActivity = collect($allActivities)->firstWhere('slug', $selectedSlug)
                ?? collect($allActivities)->firstWhere('id', (int) $selectedSlug);
        }

        if ($selectedActivity) {
            $selectedActivity['workflow_steps'] = $this->activityWorkflowSteps($selectedActivity);
        }

        return view('org.activities', array_merge($this->deskContext(), [
            'activeNav' => 'activities',
            'activities' => $allActivities,
            'selectedActivity' => $selectedActivity,
            'organizations' => $this->recognizedOrgNames(),
            'selectedOrganization' => $organization,
            'activityAcademicYears' => $activityAcademicYears,
            'selectedAcademicYear' => $academicYear,
            'forApprovalCount' => collect($allActivities)->where('filter_category', 'for_approval')->count(),
            'approvedCount' => collect($allActivities)->where('filter_category', 'approved')->count(),
            'inReviewCount' => collect($allActivities)->where('filter_category', 'in_review')->count(),
            'returnedCount' => collect($allActivities)->where('filter_category', 'returned')->count(),
        ]));
    }

    /**
     * Shape the persisted activity workflow into a compact, role-neutral
     * progress tracker for the activity detail page. The tracker is derived
     * from the same status/event rows used by the approval actions.
     *
     * @return list<array{key: string, label: string, owner: string, state: string, state_label: string, detail: string}>
     */
    private function activityWorkflowSteps(array $activity): array
    {
        $steps = [
            ['key' => 'created', 'label' => 'Created', 'owner' => 'SO Desk', 'role' => 'so'],
            ['key' => 'oso_review', 'label' => 'OSO Review', 'owner' => 'OSO Desk', 'role' => 'oso'],
            ['key' => 'sdo_review', 'label' => 'SDO Review', 'owner' => 'SDO Desk', 'role' => 'sdo'],
            ['key' => 'ovcaa_review', 'label' => 'OVCAA Review', 'owner' => 'OVCAA Desk', 'role' => 'ovcaa'],
            ['key' => 'oc_review', 'label' => 'OC Final Approval', 'owner' => 'OC Desk', 'role' => 'oc'],
        ];
        $status = (string) ($activity['workflow_status'] ?? $activity['status_key'] ?? 'created');
        $currentIndex = match ($status) {
            'college_review', 'oso_review' => 1,
            'sdo_review' => 2,
            'ovcaa_review' => 3,
            'oc_review', 'oc_approved' => 4,
            default => 0,
        };
        $returned = $status === 'returned';
        $reviewEvents = collect($activity['review_events'] ?? []);

        // Find return event if activity was returned for revision
        $returnEvent = $returned
            ? $reviewEvents->last(fn ($e) => data_get($e, 'to_status') === 'returned')
            : null;
        $returnActor = $returnEvent ? (trim((string) data_get($returnEvent, 'actor_name')) ?: strtoupper((string) data_get($returnEvent, 'role', 'Review Desk'))) : '';
        $returnTime = $returnEvent ? OrgTimeService::format(data_get($returnEvent, 'created_at')) : '';

        // Map events by the desk/role that performed the endorsement or completion
        $soEvent = $reviewEvents->first(fn ($e) => data_get($e, 'role') === 'so' && in_array(data_get($e, 'to_status'), ['oso_review', 'created'], true))
            ?? $reviewEvents->first(fn ($e) => data_get($e, 'role') === 'so');

        $osoEvent = $reviewEvents->last(fn ($e) =>
            data_get($e, 'role') === 'oso'
            || data_get($e, 'from_status') === 'oso_review'
            || (data_get($e, 'to_status') === 'sdo_review' && data_get($e, 'role') !== 'sdo')
        );

        $sdoEvent = $reviewEvents->last(fn ($e) =>
            data_get($e, 'role') === 'sdo'
            || data_get($e, 'from_status') === 'sdo_review'
            || (data_get($e, 'to_status') === 'ovcaa_review' && data_get($e, 'role') !== 'ovcaa')
        );

        $ovcaaEvent = $reviewEvents->last(fn ($e) =>
            data_get($e, 'role') === 'ovcaa'
            || data_get($e, 'from_status') === 'ovcaa_review'
            || (data_get($e, 'to_status') === 'oc_review' && data_get($e, 'role') !== 'oc')
        );

        $ocEvent = $reviewEvents->last(fn ($e) =>
            data_get($e, 'role') === 'oc'
            || data_get($e, 'to_status') === 'oc_approved'
            || data_get($e, 'from_status') === 'oc_review'
        );

        $eventMap = [
            'created' => $soEvent,
            'oso_review' => $osoEvent,
            'sdo_review' => $sdoEvent,
            'ovcaa_review' => $ovcaaEvent,
            'oc_review' => $ocEvent,
        ];

        // Fallback timestamps for activities created/seeded without explicit workflow event rows
        $createdTimestamp = $activity['submitted_at'] ?? $activity['created_at'] ?? null;
        $createdTimeFormatted = OrgTimeService::format($createdTimestamp);
        $approvedTimeFormatted = OrgTimeService::format($activity['approved_at'] ?? null);

        $steps = collect($steps)->map(function (array $step, int $index) use (
            $status,
            $currentIndex,
            $returned,
            $eventMap,
            $activity,
            $createdTimeFormatted,
            $approvedTimeFormatted,
            $returnActor,
            $returnTime
        ): array {
            if ($returned) {
                $state = $index === 0 ? 'returned' : 'waiting';
            } elseif ($status === 'oc_approved' || $index < $currentIndex) {
                $state = 'complete';
            } elseif ($index === $currentIndex) {
                $state = 'active';
            } else {
                $state = 'waiting';
            }

            $event = $eventMap[$step['key']] ?? null;
            $actor = trim((string) data_get($event, 'actor_name'));
            $timestamp = $event ? OrgTimeService::format(data_get($event, 'created_at')) : '';

            // Ensure completed steps always display actor and timestamp even when events table has gaps
            if ($state === 'complete') {
                if ($step['key'] === 'created') {
                    $actor = $actor !== '' ? $actor : ($activity['organization_alias'] ?? $activity['organization'] ?? 'SO Desk');
                    $timestamp = $timestamp !== '' ? $timestamp : $createdTimeFormatted;
                } elseif ($step['key'] === 'oc_review') {
                    $actor = $actor !== '' ? $actor : 'OC Desk';
                    $timestamp = $timestamp !== '' ? $timestamp : ($approvedTimeFormatted !== '' ? $approvedTimeFormatted : $createdTimeFormatted);
                } else {
                    $actor = $actor !== '' ? $actor : $step['owner'];
                    $timestamp = $timestamp !== '' ? $timestamp : $createdTimeFormatted;
                }
            }

            $detail = match ($state) {
                'complete' => filled($actor) && filled($timestamp)
                    ? $actor.' · '.$timestamp
                    : (filled($actor) ? $actor : (filled($timestamp) ? $timestamp : 'Completed')),
                'active' => $returned && $index === 0
                    ? 'Returned to SO for revision'
                    : 'Awaiting '.$step['owner'].' action',
                'returned' => filled($returnTime)
                    ? ($returnActor !== '' ? 'Returned by '.$returnActor.' · '.$returnTime : 'Returned · '.$returnTime)
                    : 'Returned to SO for revision',
                default => 'Waiting for prior step',
            };

            return [
                'key' => $step['key'],
                'label' => $step['label'],
                'owner' => $step['owner'],
                'state' => $state,
                'state_label' => match ($state) {
                    'complete' => 'Completed',
                    'active' => 'In progress',
                    'returned' => 'Returned',
                    default => 'Waiting',
                },
                'detail' => $detail,
            ];
        })->values()->all();

        return $steps;
    }

    public function createActivity(Request $request): View
    {
        $role = Auth::guard('office')->user()?->office_role ?? '';
        abort_unless(in_array($role, ['so', 'oso'], true), 403);
        $allActivities = $this->orgActivitiesList();
        $editSlug = $request->query('edit');
        abort_if($role !== 'so' && filled($editSlug), 403);
        $editActivity = null;
        $existingSubmission = null;

        if ($editSlug) {
            $editActivity = collect($allActivities)->firstWhere('slug', $editSlug)
                ?? collect($allActivities)->firstWhere('id', (int) $editSlug);

            $activityId = $editActivity['id'] ?? (is_numeric($editSlug) ? (int) $editSlug : null);
            if ($activityId) {
                $existingSubmission = InCampusActivitySubmission::query()
                    ->where('org_activity_id', $activityId)
                    ->latest('id')
                    ->first();
            }
        }

        $docType = in_array($request->query('type'), ['in_campus', 'local_off_campus'], true)
            ? $request->query('type')
            : ($existingSubmission?->activity_type ?: 'in_campus');

        $submission = $existingSubmission ?: new InCampusActivitySubmission([
            'activity_type' => $docType,
        ]);
        if ($submission->exists) {
            $submission->load('activity');
        }

        return view('org.activity-create', array_merge($this->deskContext(), $this->activityFormOrganizationContext(), [
            'activeNav' => 'activities',
            'editActivity' => $editActivity,
            'submission' => $submission,
            'inCampusRequirements' => $this->inCampusRequirements(),
            'offCampusRequirements' => $this->localOffCampusRequirements(),
            'recognizedOrgs' => $this->recognizedOrgsForForm(),
            'docType' => $docType,
        ]));
    }

    private function activityFormOrganizationContext(): array
    {
        $office = Auth::guard('office')->user();
        $organization = $office?->office_role === 'so' ? $office->studentOrganization : null;
        $windowId = $organization ? OrgRenewalWindow::query()->latest('id')->value('id') : null;

        return [
            'activityOrganization' => $organization,
            'activityRenewalStatus' => $organization && $windowId
                ? OrgRenewalSubmission::query()
                    ->where('renewal_window_id', $windowId)
                    ->where('organization_name', $organization->name)
                    ->latest('id')->value('status')
                : null,
        ];
    }

    /**
     * Stored activity uploads, including historical files outside the current checklist.
     */
    private function buildActivityUploadRows(InCampusActivitySubmission $submission): array
    {
        $rows = [];

        $attachments = $submission?->attachments ?? [];
        foreach ($attachments as $key => $meta) {
            if ($key === 'conditions' || $key === 'plan_reference' || empty($meta['path'])) {
                continue;
            }
            $rows[] = [
                'kind' => 'upload',
                'key' => $key,
                'name' => $meta['name'] ?? basename($meta['path']),
                'ext' => strtolower(pathinfo($meta['name'] ?? $meta['path'], PATHINFO_EXTENSION)),
                'status' => 'Uploaded',
                'date' => isset($meta['uploaded_at']) ? OrgTimeService::format($meta['uploaded_at']) : '',
                'url' => route('office.activities.attachments.file', [
                    'submission' => $submission->id,
                    'key' => $key,
                    'preview' => 1,
                ]),
                'download_url' => route('office.activities.attachments.file', [
                    'submission' => $submission->id,
                    'key' => $key,
                    'download' => 1,
                ]),
            ];
        }

        return $rows;
    }

    /**
     * Removes one uploaded file from an activity submission.
     */
    public function deleteAttachment(InCampusActivitySubmission $submission, string $key): RedirectResponse
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'so', 403);
        abort_if($key === 'conditions', 404);
        DB::connection('mysql')->transaction(function () use ($submission, $key) {
            $activity = $this->constrainToOfficeOrganization(OrgActivity::query())->lockForUpdate()->findOrFail($submission->org_activity_id);
            abort_unless(in_array($activity->workflow_status, ['created', 'returned'], true), 403);
            $locked = InCampusActivitySubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $attachments = $locked->attachments ?? [];
            abort_unless(isset($attachments[$key]['path']), 404);
            $path = $attachments[$key]['path'];
            unset($attachments[$key]);
            $locked->update(['attachments' => $attachments]);
            DB::connection('mysql')->afterCommit(fn () => Storage::disk('public')->delete($path));
        });

        return back()->with('success', 'Attachment removed.');
    }

    /**
     * Serve an uploaded activity document without exposing Laravel's random
     * storage filename. Preview requests stay inline; explicit downloads use
     * the original filename captured at upload time.
     */
    public function viewAttachment(Request $request, InCampusActivitySubmission $submission, string $key): BinaryFileResponse
    {
        abort_if($key === 'conditions', 404);
        $activity = $submission->activity;
        abort_unless($activity instanceof OrgActivity, 404);
        abort_unless(app(OrgWorkflowService::class)->canViewActivityDocuments(
            Auth::guard('office')->user()?->office_role ?? '',
            $activity->workflow_status ?: 'created',
        ), 403);
        $this->assertOfficeOrganizationName($submission->organization_name ?: $submission->activity?->organization_name);

        $attachments = is_array($submission->attachments) ? $submission->attachments : [];
        $meta = $attachments[$key] ?? null;
        abort_unless(is_array($meta) && ! empty($meta['path']), 404);

        $path = Storage::disk('public')->path((string) $meta['path']);
        abort_unless(is_file($path), 404);

        $filename = trim((string) ($meta['name'] ?? ''));
        $filename = str_replace(["\r", "\n", '"'], '_', $filename);
        if ($filename === '') {
            $filename = basename($path);
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        if ($request->boolean('download')) {
            return response()->download($path, $filename, ['Content-Type' => $mime]);
        }

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
        ]);
    }

    public function downloadActivityTemplates(Request $request): BinaryFileResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
    {
        $type = $request->query('type', 'in_campus');
        $type = in_array($type, ['in_campus', 'local_off_campus'], true) ? $type : 'in_campus';

        $sourceDir = $type === 'local_off_campus'
            ? (is_dir(base_path('Local Off Campus')) ? base_path('Local Off Campus') : storage_path('Template/Creation Of Activties/Off Campus'))
            : (is_dir(base_path('In Campus')) ? base_path('In Campus') : storage_path('Template/Creation Of Activties/In Campus Activities'));

        abort_unless(is_dir($sourceDir), 404, 'Template folder not found.');

        $requirements = $type === 'local_off_campus'
            ? $this->localOffCampusRequirements()
            : $this->inCampusRequirements();
        $templateNames = [];
        foreach ($requirements as $requirement) {
            $sourceFile = $requirement['source_file'];
            $templateNames[$sourceFile] = str_replace(['/', '\\'], '-', $requirement['title'])
                .'.'.pathinfo($sourceFile, PATHINFO_EXTENSION);
        }
        $files = array_keys($templateNames);

        // Single-file shortcut when only listing is requested
        if ($request->filled('file')) {
            $safe = basename((string) $request->query('file'));
            $path = $sourceDir.DIRECTORY_SEPARATOR.$safe;
            abort_unless(in_array($safe, $files, true) && is_file($path), 404);
            $displayName = $templateNames[$safe];

            if ($request->boolean('preview')) {
                // The WPCF supplied by SDO is a genuine binary Word 97-2003
                // document (.doc), which Chromium cannot render in-page. Keep
                // the original .doc for downloads, but use the local PDF
                // rendering for the explicit preview request.
                $previewPath = $this->activityTemplatePreviewPath($type, $safe);
                if ($previewPath) {
                    $previewName = pathinfo($displayName, PATHINFO_FILENAME).'.pdf';

                    return response()->file($previewPath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="'.addslashes($previewName).'"',
                    ]);
                }

                return response()->file($path, [
                    'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="'.addslashes($displayName).'"',
                ]);
            }

            return response()->download($path, $displayName);
        }
        foreach ($files as $file) {
            abort_unless(is_file($sourceDir.DIRECTORY_SEPARATOR.$file), 404, 'An official template is missing.');
        }

        $zipName = $type === 'local_off_campus'
            ? 'OrgChain-Local-Off-Campus-Documents.zip'
            : 'OrgChain-In-Campus-Documents.zip';
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipPath = $tempDir.DIRECTORY_SEPARATOR.$zipName;
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $built = false;
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($files as $file) {
                    $zip->addFile($sourceDir.DIRECTORY_SEPARATOR.$file, $templateNames[$file]);
                }
                $zip->close();
                $built = is_file($zipPath);
            }
        }

        if (! $built && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $psScript = 'Add-Type -AssemblyName System.IO.Compression.FileSystem; '
                .'$archive = [System.IO.Compression.ZipFile]::Open(\''
                .str_replace("'", "''", $zipPath).'\', \'Create\'); try { ';
            foreach ($files as $file) {
                $psScript .= '[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, \''
                    .str_replace("'", "''", $sourceDir.DIRECTORY_SEPARATOR.$file).'\', \''
                    .str_replace("'", "''", $templateNames[$file]).'\') | Out-Null; ';
            }
            $psScript .= '} finally { $archive.Dispose() }';
            @exec('powershell -NoProfile -Command "'.$psScript.'"');
            $built = is_file($zipPath);
        }

        if ($built) {
            return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
        }

        // Fallback: HTML pack with individual downloads (no zip extension)
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Download Documents</title>'
            .'<style>body{font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;color:#1a1618}'
            .'a{display:block;padding:10px 12px;margin:6px 0;border:1px solid #f0e6e8;border-radius:10px;text-decoration:none;color:#7a1222;font-weight:700}'
            .'a:hover{background:#fdf0f2}</style></head><body>'
            .'<h1>Download Documents</h1>'
            .'<p>'.($type === 'local_off_campus' ? 'Local Off-Campus' : 'In-Campus').' templates</p><ul style="list-style:none;padding:0">';

        foreach ($files as $file) {
            $url = route('office.activities.templates.download', ['type' => $type, 'file' => $file]);
            $html .= '<li><a href="'.e($url).'"><i></i> '.e($templateNames[$file]).'</a></li>';
        }

        $html .= '</ul><p><a href="'.e(route('office.activities.create')).'">← Back to Create Activity</a></p></body></html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Return a local, browser-renderable preview for official legacy Word
     * templates. The source document remains the download authority.
     */
    private function activityTemplatePreviewPath(string $type, string $file): ?string
    {
        $preview = match ($type.'|'.$file) {
            'in_campus|Waste-Policy-Compliance-Form-2026 (1).doc'
                => base_path('resources/office-template-previews/waste-policy-compliance-form.pdf'),
            default => null,
        };

        return $preview && is_file($preview) ? $preview : null;
    }

    public function editActivity(InCampusActivitySubmission $submission): View
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'so', 403);
        $this->assertOfficeOrganizationName($submission->organization_name ?: $submission->activity?->organization_name);
        $submission->load('activity');

        return view('org.activity-create', array_merge($this->deskContext(), $this->activityFormOrganizationContext(), [
            'activeNav' => 'activities',
            'submission' => $submission,
            'inCampusRequirements' => $this->inCampusRequirements(),
            'offCampusRequirements' => $this->localOffCampusRequirements(),
            'docType' => $submission->activity_type ?: 'in_campus',
            'docRows' => $this->buildActivityUploadRows($submission),
        ]));
    }

    public function storeActivity(Request $request): RedirectResponse
    {
        return $this->saveActivity($request);
    }

    public function updateActivity(Request $request, InCampusActivitySubmission $submission): RedirectResponse
    {
        return $this->saveActivity($request, $submission);
    }

    public function calendar(Request $request): View
    {
        $timezone = config('app.timezone');
        $now = Carbon::now($timezone);
        $month = $now->copy()->startOfMonth()->startOfDay();
        $requestedMonth = $request->query('month');
        if (is_string($requestedMonth) && preg_match('/^\d{4}-\d{2}$/', $requestedMonth)) {
            try {
                $parsed = Carbon::createFromFormat('!Y-m', $requestedMonth, $timezone);
                if ($parsed && $parsed->format('Y-m') === $requestedMonth) {
                    $month = $parsed;
                }
            } catch (\Throwable) {
                // Invalid month links keep the calendar on the current month.
            }
        }

        $choice = static function (string $key, array $choices, string $default) use ($request): string {
            $value = $request->query($key, $default);
            return in_array($value, $choices, true) ? $value : $default;
        };
        $search = $request->query('q', '');
        $filters = [
            'q' => is_string($search) ? trim($search) : '',
            'scope' => $choice('scope', ['all', 'in', 'off'], 'all'),
            'status' => $choice('status', ['all', 'pending', 'approved', 'completed', 'returned'], 'all'),
            'timing' => $choice('timing', ['all', 'upcoming', 'ongoing', 'past'], 'all'),
            'view' => $choice('view', ['auto', 'month', 'agenda'], 'auto'),
        ];
        $workflow = app(OrgWorkflowService::class);
        $role = Auth::guard('office')->user()?->office_role ?? '';

        // Persisted activities are the sole calendar source. Titles are not
        // identities, and a proposal's display status is not its approval.
        $events = $this->constrainToOfficeOrganization(OrgActivity::query())
            ->whereNotNull('starts_at')
            ->orderBy('starts_at')->orderBy('id')->get()
            ->filter(fn (OrgActivity $activity): bool => $workflow->canViewActivityDetails(
                $role, $activity->workflow_status ?: 'created'
            ))
            ->map(function (OrgActivity $activity) use ($timezone, $now, $workflow): array {
                $start = $activity->starts_at->copy()->setTimezone($timezone);
                $end = $activity->ends_at?->copy()->setTimezone($timezone);
                $effectiveEnd = $end && $end->greaterThanOrEqualTo($start) ? $end : $start;
                $workflowStatus = $activity->workflow_status ?: 'created';
                $statusKey = match (true) {
                    $workflowStatus === 'completed',
                    $workflowStatus === 'oc_approved' && $activity->status === 'completed' => 'completed',
                    $workflowStatus === 'oc_approved' => 'approved',
                    $workflowStatus === 'returned' => 'returned',
                    default => 'pending',
                };
                $timing = match (true) {
                    $statusKey === 'completed' => 'past',
                    $start->greaterThan($now) => 'upcoming',
                    $effectiveEnd->greaterThan($now) => 'ongoing',
                    default => 'past',
                };
                // Half-open intervals: an activity ending at midnight does
                // not occupy the following day. A missing end is a point.
                $lastDay = $effectiveEnd->greaterThan($start)
                    ? $effectiveEnd->copy()->subMicrosecond()->toDateString()
                    : $start->toDateString();
                $dateLabel = $start->format('M j, Y');
                if ($end && ! $end->isSameDay($start)) {
                    $dateLabel .= ' – '.$end->format('M j, Y');
                }

                return [
                    'id' => (int) $activity->id,
                    'title' => $activity->title,
                    'starts_at' => $start->toIso8601String(),
                    'ends_at' => $end?->toIso8601String(),
                    'date_key' => $start->toDateString(),
                    'last_date_key' => $lastDay,
                    'date_label' => $dateLabel,
                    'time_label' => $start->format('g:i A').($end
                        ? ' – '.$end->format('g:i A')
                        : ' · End time not set'),
                    'location' => $activity->location ?: 'Venue to be announced',
                    'status' => $statusKey === 'completed' ? 'Completed' : $workflow->label($workflowStatus),
                    'status_key' => $statusKey,
                    'workflow_status' => $workflowStatus,
                    'timing_key' => $timing,
                    'timing_label' => ucfirst($timing),
                    'scope_key' => str_contains(strtolower((string) $activity->activity_scope), 'off') ? 'off' : 'in',
                    'note' => $activity->description,
                    'detail_url' => route('office.activities', ['activity' => $activity->id]),
                ];
            })
            ->filter(static function (array $event) use ($filters): bool {
                return ($filters['scope'] === 'all' || $event['scope_key'] === $filters['scope'])
                    && ($filters['status'] === 'all' || $event['status_key'] === $filters['status'])
                    && ($filters['timing'] === 'all' || $event['timing_key'] === $filters['timing'])
                    && ($filters['q'] === ''
                        || mb_stripos($event['title'], $filters['q']) !== false
                        || mb_stripos($event['location'], $filters['q']) !== false);
            })
            ->values();

        $firstDay = $month->copy()->startOfWeek(Carbon::MONDAY);
        $lastDay = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $eventsByDate = [];
        foreach ($events as $event) {
            $from = max($firstDay->toDateString(), $event['date_key']);
            $to = min($lastDay->toDateString(), $event['last_date_key']);
            if ($from > $to) {
                continue;
            }
            $date = Carbon::parse($from, $timezone)->startOfDay();
            while ($date->toDateString() <= $to) {
                $eventsByDate[$date->toDateString()][] = $event;
                $date->addDay();
            }
        }
        $days = [];
        for ($date = $firstDay->copy(); $date <= $lastDay; $date->addDay()) {
            $days[] = [
                'date' => $date->copy(),
                'inMonth' => $date->format('Y-m') === $month->format('Y-m'),
                'events' => $eventsByDate[$date->toDateString()] ?? [],
            ];
        }

        return view('org.calendar', array_merge($this->deskContext(), [
            'activeNav' => 'calendar',
            'calendarFilters' => $filters,
            'monthKey' => $month->format('Y-m'),
            'monthLabel' => $month->format('F Y'),
            'todayKey' => $now->toDateString(),
            'todayMonth' => $now->format('Y-m'),
            'days' => $days,
            'agendaDays' => collect($days)->filter(fn (array $day): bool => $day['inMonth'] && count($day['events']))->values(),
            'events' => $events,
            'eventsByDate' => $eventsByDate,
            'upcomingEvents' => $events->where('timing_key', 'upcoming')->values(),
            'ongoingEvents' => $events->where('timing_key', 'ongoing')->values(),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]));
    }

    public function budget(): View
    {
        $budgetService = app(\App\Services\ActivityBudgetService::class);
        $selectedYear = (string) request('academic_year', $budgetService->period()['academic_year']);
        $selectedSemester = (string) request('semester', 'Annual');
        $budgetService->dates($selectedYear, $selectedSemester);
        $orgFilter = $this->organizationFilterForOffice(request('organization', ''));
        $department = trim((string) request('department', ''));
        $departmentValues = $department !== '' ? $this->departmentValues($department) : null;
        $osoFinancialOverview = Auth::guard('office')->user()?->office_role === 'oso'
            ? app(\App\Services\OsoFinancialOverviewService::class)->overview(
                $selectedYear, $selectedSemester, $orgFilter, $departmentValues
            )
            : null;
        $financialTransactions = null;
        if ($osoFinancialOverview !== null && $orgFilter !== '') {
            $transactions = collect($osoFinancialOverview['transactions']);
            $page = min(max(1, request()->integer('cash_page', 1)), max(1, (int) ceil($transactions->count() / 10)));
            $financialTransactions = new LengthAwarePaginator(
                $transactions->slice(($page - 1) * 10, 10)->values(), $transactions->count(), 10, $page,
                ['path' => route('office.budget'), 'pageName' => 'cash_page', 'query' => request()->query()]
            );
        }
        $budget = $this->budgetUtilizationData($departmentValues, $orgFilter);
        $items = BudgetItem::query()
            ->when($orgFilter !== '', fn ($q) => $q->where('organization_name', $orgFilter))
            ->when($departmentValues !== null, fn ($q) => $q->whereIn('college', $departmentValues ?: ['__no_matching_department__']))
            ->orderByDesc('utilized')
            ->get();

        $liveBudget = $this->buildLiveBudgetDataset($orgFilter, $departmentValues);
        $approvedBudgetActivities = collect($liveBudget['order'] ?? [])
            ->reject(fn ($key) => $key === 'all')
            ->map(fn ($key) => data_get($liveBudget['entries'] ?? [], $key.'.actName'))
            ->filter()
            ->values();

        $reportStatus = OrgReportStatus::query()
            ->where('report_type', 'budget')
            ->latest()
            ->first();

        return view('org.budget', array_merge($this->deskContext(), [
            'selectedYear' => $selectedYear,
            'osoFinancialOverview' => $osoFinancialOverview,
            'financialTransactions' => $financialTransactions,
            'approvedActivityChoices' => collect($liveBudget['entries'])->except('all')->values(),
            'activeNav' => 'budget',
            'budget' => $budget,
            'budgetItems' => $items,
            'organizations' => $osoFinancialOverview['organizations'] ?? $this->recognizedOrgNames(),
            'selectedOrganization' => $orgFilter,
            'liveBudgetEntries' => $liveBudget['entries'],
            'liveScopeTotals' => $liveBudget['scopeTotals'],
            'liveBudgetDefault' => $liveBudget['defaultKey'] ?? 'innovation',
            'liveBudgetOptions' => $liveBudget['options'],
            'approvedBudgetActivities' => $approvedBudgetActivities,
            'reportStatus' => $reportStatus,
        ]));
    }

    public function storeReceiptReview(Request $request): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'so', 403);
        $fileRules = ['file', 'max:5120', 'mimes:pdf,png,jpg,jpeg,webp,docx'];
        $common = [
            'org_activity_id' => ['nullable', 'integer', 'exists:mysql.org_activities,id'],
            'activity' => ['required_without:org_activity_id', 'nullable', 'string', 'max:255'],
            'receipt_reviewed' => ['accepted'],
        ];
        $isBatch = is_array($request->input('expenses'));
        if ($isBatch) {
            $validated = $request->validate($common + [
                'expenses' => ['required', 'array', 'min:1', 'max:20'],
                'expenses.*.request_key' => ['required', 'uuid'],
                'expenses.*.item_name' => ['required', 'string', 'max:255'],
                'expenses.*.category' => ['nullable', 'string', 'max:100'],
                'expenses.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
                'expenses.*.unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
                'expenses.*.expense_date' => ['required', 'date', 'before_or_equal:today'],
                'expenses.*.supplier' => ['nullable', 'string', 'max:255'],
                'expenses.*.receipt_reference' => ['required', 'string', 'max:120'],
                'expenses.*.receipt_scan_id' => ['nullable', 'uuid'],
                'expenses.*.receipt_type' => ['required', 'in:paper_receipt,ewallet_receipt,unknown'],
                'expenses.*.payment_method' => ['required', 'in:gcash,maya,cash,card,bank_transfer,unknown'],
                // The active SO client uploads one shared receipt set. The
                // per-row keys remain accepted for older clients.
                'receipts' => ['nullable', 'array', 'max:3'],
                'receipts.*' => $fileRules,
                // New clients submit expenses.*.receipts[]; receipt remains
                // accepted for older clients and saved records.
                'expenses.*.receipts' => ['nullable', 'array', 'max:6'],
                'expenses.*.receipts.*' => $fileRules,
                'expenses.*.receipt' => ['nullable', ...$fileRules],
            ]);
        } else {
            $validated = $request->validate($common + [
                'request_key' => ['required', 'uuid'],
                'item_name' => ['required', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:100'],
                'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
                'unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
                'expense_date' => ['required', 'date', 'before_or_equal:today'],
                'supplier' => ['nullable', 'string', 'max:255'],
                'receipt_reference' => ['required', 'string', 'max:120'],
                'receipt_scan_id' => ['nullable', 'uuid'],
                'receipt_type' => ['required', 'in:paper_receipt,ewallet_receipt,unknown'],
                'payment_method' => ['required', 'in:gcash,maya,cash,card,bank_transfer,unknown'],
                'receipt' => ['required', ...$fileRules],
            ]);
        }
        $query = $this->constrainToOfficeOrganization(OrgActivity::query())->visibleToStudents();
        if (! empty($validated['org_activity_id'])) {
            $activity = $query->find($validated['org_activity_id']);
        } else {
            $matches = $query->where('title', $validated['activity'])->get();
            $activity = $matches->count() === 1 ? $matches->first() : null;
        }
        if (! $activity) return back()->withInput()->withErrors(['activity' => 'Select a unique final-approved activity from the dropdown.']);
        $service = app(\App\Services\ActivityBudgetService::class);
        $sharedFiles = $isBatch ? $request->file('receipts', []) : [];
        if ($sharedFiles instanceof \Illuminate\Http\UploadedFile) $sharedFiles = [$sharedFiles];
        if (! is_array($sharedFiles)) $sharedFiles = [];
        $entries = $isBatch
            ? collect($validated['expenses'])->map(function (array $row, $index) use ($request, $sharedFiles) {
                $files = $sharedFiles;
                if (count($files) === 0) {
                    $uploadedRow = $request->file('expenses.'.$index, []);
                    $files = $uploadedRow['receipts'] ?? [];
                    if ($files instanceof \Illuminate\Http\UploadedFile) $files = [$files];
                    if (! is_array($files)) $files = [];
                    if (count($files) === 0 && ($uploadedRow['receipt'] ?? null) instanceof \Illuminate\Http\UploadedFile) {
                        $files = [$uploadedRow['receipt']];
                    }
                }
                if (count($files) === 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'receipts' => 'Upload at least one receipt or supporting document before recording expense items.',
                    ]);
                }
                return ['data' => $row, 'files' => array_values($files)];
            })->all()
            : [['data' => $validated, 'file' => $request->file('receipt')]];
        $receipts = $service->recordMany($activity, $office, $entries);
        $sealedCount = $receipts->filter(fn ($receipt) => $service->seal($receipt))->count();
        $count = $receipts->count();
        return redirect()->route('office.budget', [
            'organization' => $activity->organization_name, 'activity_id' => $activity->id,
            'academic_year' => $service->period($activity->starts_at)['academic_year'],
        ])->with('success', $sealedCount === $count
            ? ($count === 1 ? 'Receipt recorded and sealed. Activity and organization balances are updated.' : $count.' expense items recorded and sealed. Activity and organization balances are updated.')
            : $count.' expense item(s) saved and balances updated. Blockchain confirmation is pending; use Retry seal in receipt history.');
    }

    public function retryReceiptSeal(ExpenseReceiptReview $review): RedirectResponse
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'so', 403);
        $this->assertOfficeOrganizationName($review->organization_name);
        abort_unless($review->org_activity_id, 422);
        $sealed = app(\App\Services\ActivityBudgetService::class)->seal($review);
        return back()->with('success', $sealed ? 'Receipt seal confirmed. No additional budget deduction was made.' : 'Receipt is saved. Blockchain confirmation is still pending.');
    }

    public function printBudget(): View
    {
        $data = $this->budget()->getData();
        $year = $data['selectedYear'];
        $semester = (string) request('semester', 'Annual');
        $activityId = (int) request('activity_id', 0);
        $entries = collect($data['liveBudgetEntries'])->except('all')->filter(fn ($entry) =>
            ($entry['academic_year'] ?? null) === $year
            && ($semester === 'Annual' || ($entry['semester'] ?? null) === $semester)
            && (! $activityId || $entry['activityId'] === $activityId));
        $data['liveBudgetEntries'] = $entries->all();

        return view('org.budget-print', array_merge($data, [
            'generatedAt' => now()->format('M j, Y g:i A'),
        ]));
    }

    /**
     * Organization-assigned SO desks get the workbook-based Financial Report
     * page; OSO and unassigned SO accounts keep the semester receipt ledger.
     */
    public function financial(): View
    {
        if (Auth::guard('office')->user()?->office_role === 'oso') {
            return $this->semesterReportDesk('fr');
        }
        if ($this->assignedOrganizationName() !== null) {
            return app(SoFinancialReportController::class)->index($this->deskContext());
        }

        return $this->financialLedger();
    }

    private function financialLedger(): View
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && in_array($office->office_role, ['so', 'oso'], true), 403);
        $service = app(\App\Services\ActivityBudgetService::class);
        $organization = $this->organizationFilterForOffice(request('organization', ''));
        $year = (string) request('academic_year', $service->period()['academic_year']);
        $semester = (string) request('semester', $service->period()['semester']);
        $data = $service->financial($organization, $year, $semester);
        $bundle = $this->semesterReportBundle($organization, $semester, $year);
        $rows = $data['receiptRows'];
        $ledger = $rows->map(fn ($r) => [
            'date' => $r->expense_date->format('M j, Y'), 'desc' => $r->activity_title.' · '.$r->item_name,
            'cat' => $r->category ?: 'General', 'type' => 'outflow', 'amount' => round($r->quantity * (float) $r->unit_cost, 2),
            'ref' => $r->receipt_reference, 'status' => $r->verification_status === 'pending_seal' ? 'Pending seal' : 'Sealed',
            'url' => route('office.budget.receipts.view', $r),
        ])->all();
        $docs = $rows->map(fn ($r) => [
            'name' => $r->receipt_name, 'size' => 'Original receipt', 'date' => $r->expense_date->format('M j, Y'),
            'tag' => 'Receipt', 'url' => route('office.budget.receipts.view', $r),
        ])->all();
        $dataset = [
            'orgName' => $organization ?: 'All recognized organizations', 'orgCategory' => 'Semester records',
            'term' => $semester.' · '.$year, 'period' => $data['fromDate'].' – '.$data['toDate'],
            'advisor' => 'Not recorded', 'actName' => 'Semester expense compilation',
            'actType' => 'Receipt-supported expenses', 'actDate' => $data['fromDate'].' – '.$data['toDate'],
            'actVenue' => 'See activity records', 'actScope' => 'Approved activities',
            'revenue' => 0, 'expenses' => $data['periodExpenseTotal'], 'balance' => -$data['periodExpenseTotal'],
            'ledger' => $ledger, 'documents' => $docs,
            'verifiedBy' => !empty($bundle['reports']['fr']['status']?->reviewed_by) ? (OfficeUser::find($bundle['reports']['fr']['status']->reviewed_by)?->name ?? 'OSO reviewer') : 'Not reviewed',
            'verifiedOffice' => 'Office of Student Organizations (OSO)',
            'verifiedDate' => OrgTimeService::format($bundle['reports']['fr']['status']?->reviewed_at) ?: 'Not reviewed',
            'remarks' => 'The expense register includes receipts dated within this semester. Annual organization balances appear separately. Receipt sealing does not mean OSO has accepted the semester report.',
            'history' => $rows->map(fn ($r) => [
                'title' => 'Receipt #'.$r->id.' · '.$r->item_name.' · '.$r->verification_status,
                'date' => OrgTimeService::format($r->created_at), 'badge' => 'is-blue', 'icon' => 'bi-receipt',
            ])->all(),
        ];
        return view('org.financial', array_merge($this->deskContext(), $data, [
            'activeNav' => 'financial', 'organizations' => $this->recognizedOrgNames(),
            'selectedOrganization' => $organization, 'selectedSemester' => $semester, 'selectedYear' => $year,
            'reportBundle' => $bundle, 'reportType' => 'fr',
            'reportStatus' => $bundle['reports']['fr']['status'], 'financialDataset' => $dataset,
            'generatedAt' => now()->format('M j, Y g:i A'),
        ]));
    }

    public function printFinancial(): View
    {
        $data = $this->financialLedger()->getData();

        return view('org.financial-print', $data);
    }

    public function printAccomplishment(): View
    {
        $data = $this->accomplishment()->getData();
        if (Auth::guard('office')->user()?->office_role === 'oso') {
            $data = app(AccomplishmentReportController::class)->index($data)->getData();
        }

        return view('org.accomplishment-print', array_merge($data, [
            'generatedAt' => now()->format('M j, Y g:i A'),
        ]));
    }

    public function accomplishment(): View
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && in_array($office->office_role, ['so', 'oso'], true), 403);
        if ($office->office_role === 'oso') {
            return $this->semesterReportDesk('ar');
        }

        $currentPeriod = app(\App\Services\ActivityBudgetService::class)->period();
        $period = request()->validate([
            'semester' => ['nullable', Rule::in(['1st Semester', '2nd Semester', 'Midyear'])],
            'academic_year' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'organization' => ['nullable', 'string', 'max:255'],
        ]);
        $organization = $this->organizationFilterForOffice($period['organization'] ?? '');
        $semester = $period['semester'] ?? $currentPeriod['semester'];
        $year = $period['academic_year'] ?? $currentPeriod['academic_year'];
        app(\App\Services\ActivityBudgetService::class)->dates($year, $semester);

        return app(\App\Http\Controllers\AccomplishmentReportController::class)->index(array_merge($this->deskContext(), [
            'activeNav' => 'accomplishment',
            'organizations' => $this->recognizedOrgNames(),
            'selectedOrganization' => $organization,
            'selectedSemester' => $semester,
            'selectedYear' => $year,
            'reportBundle' => $this->semesterReportBundle($organization, $semester, $year),
            'reportType' => 'ar',
        ]));
    }

    public function updates(): View
    {
        $liveAnnouncements = OfficeAnnouncement::query()
            ->latest()
            ->get()
            ->map(fn (OfficeAnnouncement $a): array => [
                'id' => 'db-'.$a->id,
                'title' => $a->title,
                'type' => $a->type,
                'body' => $a->body,
                'author' => $a->author,
                'time' => optional($a->created_at)->diffForHumans() ?? 'Recently',
                'priority' => $a->priority,
                'attachment' => $a->attachment_name,
                'attachment_size' => null,
                'attachment_url' => $a->attachment_path ? route('office.updates.announcements.attachment', $a) : null,
            ])->all();

        $liveTemplates = OfficeTemplate::query()
            ->where('category', '!=', 'TOSA')
            ->latest()
            ->get()
            ->map(fn (OfficeTemplate $t): array => [
                'id' => 'db-'.$t->id,
                'name' => $t->name,
                'category' => $t->category,
                'format' => $t->format,
                'size' => $t->size,
                'downloads' => (int) $t->downloads,
                'icon' => 'file-earmark-arrow-down-fill',
                'color' => 'red',
                'updated' => optional($t->created_at)->format('M j, Y') ?? 'Recent',
                'description' => $t->description,
                'download_url' => route('office.updates.templates.download', $t),
                'preview_url' => route('office.updates.templates.document', [
                    'id' => 'db-'.$t->id,
                    'preview' => 1,
                ]),
            ])->all();

        return view('org.updates', array_merge($this->deskContext(), [
            'activeNav' => 'updates',
            'announcements' => array_merge($liveAnnouncements, $this->demoAnnouncements()),
            'templates' => array_merge(
                $liveTemplates,
                $this->officialTemplateDocuments(),
            ),
        ]));
    }

    /**
     * Demo announcements (also feeds the .docx notice download).
     *
     * @return list<array<string, mixed>>
     */
    private function demoAnnouncements(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Deadline Extension for Activity Proposals (AY 2026-2027)',
                'type' => 'Deadline',
                'body' => 'The deadline for submitting student activity proposals and financial plans for the 2nd Semester has been officially extended to April 15, 2026. All student organizations must comply with the updated clearance requirements and submit via OrgChain portal.',
                'author' => 'Office of Student Organizations (OSO)',
                'time' => '2 hours ago',
                'priority' => 'high',
                'attachment' => 'Activity_Proposal_Guidelines_AY2526.pdf',
                'attachment_size' => '1.2 MB',
            ],
            [
                'id' => 2,
                'title' => 'Revised Budget Allocation & Expense Liquidation Sheet',
                'type' => 'Guideline',
                'body' => 'A revised Budget Allocation template is now available for download. All new proposals requiring university student fund utilization must use this updated format with detailed line-item breakdowns.',
                'author' => 'Office of Student Organizations (OSO)',
                'time' => '1 day ago',
                'priority' => 'normal',
                'attachment' => 'Budget_Allocation_Sheet_v2.xlsx',
                'attachment_size' => '180 KB',
            ],
            [
                'id' => 3,
                'title' => 'Mandatory OSO Clearance & Risk Assessment Protocols',
                'type' => 'Reminder',
                'body' => 'All campus events, off-campus excursions, and general assemblies must secure verified OSO clearance at least 2 weeks before the scheduled event date. Ensure venue reservations and safety matrices are attached.',
                'author' => 'Office of Student Organizations (OSO)',
                'time' => '3 days ago',
                'priority' => 'high',
                'attachment' => null,
                'attachment_size' => null,
            ],
            [
                'id' => 4,
                'title' => 'Welcome to Academic Year 2026-2027: Accreditation & Calendar',
                'type' => 'General Announcement',
                'body' => 'We warmly welcome all student leaders and organizations to the new academic term. Please review the institutional calendar and ensure officer credentials are up-to-date in the system.',
                'author' => 'Office of Student Organizations (OSO)',
                'time' => '1 week ago',
                'priority' => 'normal',
                'attachment' => 'Accreditation_Notice_AY2026.pdf',
                'attachment_size' => '850 KB',
            ],
        ];
    }

    /**
     * Canonical official templates supplied with the project.
     *
     * The card catalogue is deliberately source-backed. A missing source is
     * omitted from the page instead of being replaced with a generated sample
     * or a text-file download that only looks like a template.
     *
     * @return list<array<string, mixed>>
     */
    private function officialTemplateDocuments(): array
    {
        $definitions = [
            [
                'id' => 'source-in-campus-checklist',
                'name' => 'In-Campus Activity Checklist',
                'category' => 'Compliance',
                'source_path' => 'In Campus/0. Checklist Template.docx',
                'description' => 'Official checklist of attachments for an in-campus activity request.',
                'icon' => 'file-earmark-check-fill',
                'color' => 'red',
            ],
            [
                'id' => 'source-in-campus-programme',
                'name' => 'Programme / Schedule of Activities',
                'category' => 'Proposal',
                'source_path' => 'In Campus/2. Programme.docx',
                'description' => 'Official programme format with activity flow, times, venues, and assigned roles.',
                'icon' => 'file-earmark-richtext-fill',
                'color' => 'blue',
            ],
            [
                'id' => 'source-in-campus-project-proposal',
                'name' => 'Project Proposal',
                'category' => 'Proposal',
                'source_path' => 'In Campus/3. Project Proposal.docx',
                'description' => 'Official in-campus project proposal format for the rationale, objectives, participants, duration, and safety plan.',
                'icon' => 'file-earmark-text-fill',
                'color' => 'red',
            ],
            [
                'id' => 'source-in-campus-budget-proposal',
                'name' => 'Budget Proposal',
                'category' => 'Finance',
                'source_path' => 'In Campus/4. Budget Proposal.docx',
                'description' => 'Official budget proposal format for funding requirements and sources of funds.',
                'icon' => 'file-earmark-spreadsheet-fill',
                'color' => 'green',
            ],
            [
                'id' => 'source-in-campus-request-letter',
                'name' => 'Activity Request Letter',
                'category' => 'Forms',
                'source_path' => 'In Campus/Sample Letter.docx',
                'description' => 'Official request-letter format for seeking approval to conduct an activity.',
                'icon' => 'file-earmark-text-fill',
                'color' => 'violet',
            ],
            [
                'id' => 'source-waste-policy-compliance',
                'name' => 'Waste Policy Compliance Form',
                'category' => 'Compliance',
                'source_path' => 'In Campus/Waste-Policy-Compliance-Form-2026 (1).doc',
                'preview_path' => 'resources/office-template-previews/waste-policy-compliance-form.pdf',
                'description' => 'Official SDO waste policy compliance form required for in-campus activities.',
                'icon' => 'file-earmark-pdf-fill',
                'color' => 'red',
            ],
            [
                'id' => 'source-local-off-campus-request',
                'name' => 'Local Off-Campus Activity Request',
                'category' => 'Proposal',
                'source_path' => 'Local Off Campus/BatStateU-FO-REQ-09_Request-for-the-Conduct-of-Local-Off-Campus-Activities-Rev.-02 (1) (1).docx',
                'description' => 'Official FO-REQ-09 request form for local off-campus activities.',
                'icon' => 'file-earmark-richtext-fill',
                'color' => 'red',
            ],
            [
                'id' => 'source-local-off-campus-checklist',
                'name' => 'Local Off-Campus Compliance Checklist',
                'category' => 'Compliance',
                'source_path' => 'Local Off Campus/Checklist of the Requirements.docx',
                'description' => 'Official checklist for local off-campus and CHED documentary compliance.',
                'icon' => 'file-earmark-check-fill',
                'color' => 'red',
            ],
            [
                'id' => 'source-tosa-application',
                'name' => 'TOSA Application Form 2025',
                'category' => 'TOSA',
                'source_path' => 'TOSA/TOSA Application Form 2025.docx',
                'description' => 'Official Ten Outstanding Students Awards nomination form supplied for the TOSA filing package.',
                'icon' => 'file-earmark-richtext-fill',
                'color' => 'violet',
            ],
            [
                'id' => 'source-tosa-computation',
                'name' => 'TOSA Computation Workbook',
                'category' => 'TOSA',
                'source_path' => 'TOSA/TOSA COMPUTATION.xlsx',
                'description' => 'Official spreadsheet workbook supplied for TOSA scoring and computation.',
                'icon' => 'file-earmark-spreadsheet-fill',
                'color' => 'green',
            ],
            [
                'id' => 'source-accomplishment-financial',
                'name' => 'Accomplishment & Financial Report',
                'category' => 'Reports',
                'source_path' => 'Reports/Accomplishment & Financial Report.docx',
                'description' => 'Official first-semester accomplishment and financial report format for student organizations.',
                'icon' => 'file-earmark-richtext-fill',
                'color' => 'gold',
            ],
            [
                'id' => 'source-accomplishment-particulars',
                'name' => 'Particulars of the Accomplishments',
                'category' => 'Reports',
                'source_path' => 'Reports/Particulars of the Accomplishments.docx',
                'description' => 'Official supporting format for documenting accomplishment particulars and results.',
                'icon' => 'file-earmark-text-fill',
                'color' => 'blue',
            ],
            [
                'id' => 'source-written-explanation',
                'name' => 'Written Explanation',
                'category' => 'Reports',
                'source_path' => 'Reports/Written Explanation.docx',
                'description' => 'Official written-explanation format included in the first-semester report package.',
                'icon' => 'file-earmark-text-fill',
                'color' => 'red',
            ],
        ];

        return array_values(array_filter(array_map(function (array $definition): ?array {
            $path = base_path($definition['source_path']);
            if (! is_file($path)) {
                return null;
            }

            $bytes = (int) filesize($path);
            $format = strtoupper(pathinfo($path, PATHINFO_EXTENSION));
            $id = (string) $definition['id'];

            return array_merge($definition, [
                'format' => $format ?: 'FILE',
                'size' => $bytes > 0 ? $this->formatFileSize($bytes) : '0 B',
                'downloads' => 0,
                'updated' => date('M j, Y', (int) filemtime($path)),
                'download_url' => route('office.updates.templates.document', ['id' => $id]),
                'preview_url' => route('office.updates.templates.document', ['id' => $id, 'preview' => 1]),
            ]);
        }, $definitions)));
    }

    /**
     * Download an announcement as a real Word (.docx) notice.
     */
    public function downloadAnnouncementDocument(string $id): Response
    {
        if (str_starts_with($id, 'db-')) {
            $a = OfficeAnnouncement::query()->findOrFail((int) substr($id, 3));
            $item = [
                'title' => $a->title,
                'type' => $a->type,
                'body' => $a->body,
                'author' => $a->author,
                'time' => optional($a->created_at)->format('M j, Y g:i A') ?? 'Recently',
                'attachment' => $a->attachment_name,
            ];
        } else {
            $item = collect($this->demoAnnouncements())->firstWhere('id', (int) $id);
            abort_unless($item, 404);
        }

        $doc = (new DocxBuilder())
            ->title($item['title'])
            ->p(($item['type'] ?? 'General').'  ·  '.($item['author'] ?? 'OSO').'  ·  '.($item['time'] ?? ''), true)
            ->spacer()
            ->p($item['body'] ?? '')
            ->spacer();
        if (! empty($item['attachment'])) {
            $doc->p('Attachment on file: '.$item['attachment']);
        }
        $doc->spacer()->p('Generated from OrgChain Updates on '.now()->format('M j, Y g:i A').'.');

        return response($doc->build(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$this->docxFileName($item['title']).'"',
        ]);
    }

    /**
     * Serve an official template from its original source file.
     *
     * Preview requests stay inline. Explicit downloads preserve the original
     * filename and file type; no generated substitute is returned.
     */
    public function downloadTemplateDocument(Request $request, string $id): Response|BinaryFileResponse
    {
        if (str_starts_with($id, 'db-')) {
            $t = OfficeTemplate::query()->findOrFail((int) substr($id, 3));
            abort_unless($t->file_path && Storage::disk('public')->exists($t->file_path), 404, 'Template source file not found.');

            $storedPath = Storage::disk('public')->path($t->file_path);
            $filename = str_replace(["\r", "\n", '"'], '_', $t->original_name ?: basename($t->file_path));
            $mime = $this->templateMimeType($storedPath);

            if ($request->boolean('preview')) {
                return response()->file($storedPath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
                ]);
            }

            $t->increment('downloads');

            return response()->download($storedPath, $filename, ['Content-Type' => $mime]);
        }

        $tpl = collect($this->officialTemplateDocuments())
            ->first(fn (array $template): bool => (string) ($template['id'] ?? '') === $id);
        abort_unless($tpl && ! empty($tpl['source_path']), 404, 'Official template source not found.');

        $sourcePath = base_path($tpl['source_path']);
        abort_unless(is_file($sourcePath), 404, 'Template source file not found.');

        $sourceFilename = basename($sourcePath);
        $mime = $this->templateMimeType($sourcePath);

        if ($request->boolean('preview')) {
            $previewPath = ! empty($tpl['preview_path']) ? base_path($tpl['preview_path']) : null;
            if ($previewPath && is_file($previewPath)) {
                return response()->file($previewPath, [
                    'Content-Type' => $this->templateMimeType($previewPath),
                    'Content-Disposition' => 'inline; filename="'.addslashes(pathinfo($sourceFilename, PATHINFO_FILENAME).'.pdf').'"',
                ]);
            }

            return response()->file($sourcePath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.addslashes($sourceFilename).'"',
            ]);
        }

        return response()->download($sourcePath, $sourceFilename, ['Content-Type' => $mime]);
    }

    private function docxFileName(string $name): string
    {
        $safe = preg_replace('/[^A-Za-z0-9]+/', '_', $name) ?: 'document';

        return trim($safe, '_').'.docx';
    }

    private function templateMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'pdf' => 'application/pdf',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppt' => 'application/vnd.ms-powerpoint',
            default => 'application/octet-stream',
        };
    }


    /**
     * Persist a new office announcement (OSO composer) with optional file.
     */
    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:64'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'in:normal,high'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:15360'],
        ]);

        $words = preg_split('/\s+/', trim($validated['body'])) ?: [];
        $headline = implode(' ', array_slice($words, 0, 9));
        if (count($words) > 9) {
            $headline .= '…';
        }

        $data = [
            'title' => $validated['type'].': '.$headline,
            'type' => $validated['type'],
            'body' => $validated['body'],
            'author' => Auth::guard('office')->user()?->name ?? 'Office of Student Organizations (OSO)',
            'priority' => $validated['priority'],
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('announcements', 'public');
            $data['attachment_name'] = $file->getClientOriginalName();
        }

        OfficeAnnouncement::query()->create($data);

        return redirect()->route('office.updates')->with('success', 'Announcement published.');
    }

    /**
     * Download an announcement attachment from storage.
     */
    public function downloadAnnouncementAttachment(OfficeAnnouncement $announcement): BinaryFileResponse
    {
        abort_unless($announcement->attachment_path && Storage::disk('public')->exists($announcement->attachment_path), 404);

        return Storage::disk('public')->download(
            $announcement->attachment_path,
            $announcement->attachment_name ?: basename($announcement->attachment_path)
        );
    }

    /**
     * Upload a new official template document (OSO).
     */
    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->osoOfficer();
        $category = $request->input('category');
        if (is_string($category) && strcasecmp(trim($category), 'TOSA') === 0) {
            $this->requireTosaUnlock($request, 3);
            $request->merge(['category' => 'TOSA']);
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:2000'],
            'template_file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,png,jpg,jpeg', 'max:20480'],
        ]);

        $file = $request->file('template_file');
        $ext = strtoupper($file->getClientOriginalExtension());

        OfficeTemplate::query()->create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'format' => $ext ?: 'PDF',
            'size' => $this->formatFileSize((int) $file->getSize()),
            'description' => $validated['description'],
            'file_path' => $file->store('templates', 'public'),
            'original_name' => $file->getClientOriginalName(),
        ]);

        return redirect()->route('office.updates')->with('success', 'Template uploaded & published.');
    }

    /**
     * Download a stored template file (increments the counter).
     */
    public function downloadTemplate(OfficeTemplate $template): BinaryFileResponse
    {
        abort_unless($template->file_path && Storage::disk('public')->exists($template->file_path), 404);

        $template->increment('downloads');

        return Storage::disk('public')->download(
            $template->file_path,
            $template->original_name ?: basename($template->file_path)
        );
    }

    private function settingsOfficer(): OfficeUser
    {
        $office = Auth::guard('office')->user();
        abort_unless(
            $office instanceof OfficeUser
                && in_array($office->office_role, ['so', 'oso', 'sdo', 'ovcaa', 'oc'], true),
            403
        );

        return $office;
    }

    private function osoOfficer(): OfficeUser
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'oso', 403);

        return $office;
    }

    private function officialOfficeEmailRules(?OfficeUser $office = null): array
    {
        return [
            'required', 'email', 'max:255',
            Rule::unique('office_users', 'email')->ignore($office?->id),
            function (string $attribute, mixed $value, \Closure $fail): void {
                $domain = Str::afterLast(strtolower((string) $value), '@');
                if (! in_array($domain, ['g.batstate-u.edu.ph', 'batstate-u.edu.ph'], true)) {
                    $fail('Use your official BatStateU email address.');
                }
            },
        ];
    }

    private function tosaOfficer(int $minimumLevel = 1): OfficeUser
    {
        $office = $this->settingsOfficer();
        abort_unless($office->tosaClearanceLevel() >= $minimumLevel, 403, 'This account does not have the required TOSA clearance.');

        return $office;
    }

    private function tosaUnlockState(Request $request): array
    {
        $security = OfficeSetting::valuesFor('oso')['security'];
        if (! $security['tosa_gate']) {
            return ['unlocked' => true, 'remaining' => 0];
        }

        $unlock = $request->session()->get('tosa_unlock', []);
        $signature = hash('sha256', json_encode($security));
        $duration = max(0, (int) $security['session_timeout']) * 60;
        $elapsed = now()->timestamp - (int) ($unlock['at'] ?? 0);
        $valid = filled($security['tosa_pin_hash'])
            && ($unlock['user_id'] ?? null) === Auth::guard('office')->id()
            && ($unlock['signature'] ?? null) === $signature
            && $elapsed >= 0
            && ($duration === 0 || $elapsed < $duration);

        if (! $valid) {
            $request->session()->forget('tosa_unlock');
        }

        return [
            'unlocked' => $valid,
            'remaining' => $valid && $duration > 0 ? $duration - $elapsed : 0,
        ];
    }

    private function requireTosaUnlock(Request $request, int $minimumLevel = 1): OfficeUser
    {
        $office = $this->tosaOfficer($minimumLevel);
        abort_unless($this->tosaUnlockState($request)['unlocked'], 403, 'Unlock TOSA with the security PIN before continuing.');

        return $office;
    }

    public function lockOsoTosa(Request $request): JsonResponse
    {
        $this->tosaOfficer();
        if (! data_get(OfficeSetting::valuesFor('oso'), 'security.tosa_gate')) {
            return response()->json(['ok' => false, 'message' => 'Enable the TOSA PIN gate before locking the session.'], 422);
        }
        $request->session()->forget('tosa_unlock');

        return response()->json(['ok' => true]);
    }

    /**
     * Settings are submitted with fetch(), so validation failures must remain
     * JSON instead of redirecting the modal back to the dashboard.
     */
    private function validateOsoJson(Request $request, array $rules): array|JsonResponse
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Please correct the highlighted settings and try again.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return $validator->validated();
    }

    /**
     * Save institutional settings. Personal account and password changes use
     * their own endpoints and do not create office settings records.
     */
    public function updateOsoSettings(Request $request): JsonResponse
    {
        $office = $this->osoOfficer();
        $sections = [
            'general' => ['system_name', 'office_name', 'university_name', 'campus_unit', 'contact_email', 'contact_phone', 'contact_location'],
            'security' => ['session_timeout', 'tosa_gate'],
        ];

        $validated = $this->validateOsoJson($request, [
            'section' => ['required', Rule::in(array_keys($sections))],
            'values' => ['required', 'array'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $section = $request->string('section')->toString();

        $rules = match ($section) {
            'general' => [
                'values.system_name' => ['required', 'string', 'max:120'],
                'values.office_name' => ['required', 'string', 'max:160'],
                'values.university_name' => ['required', 'string', 'max:200'],
                'values.campus_unit' => ['nullable', 'string', 'max:200'],
                'values.contact_email' => ['nullable', 'email', 'max:255'],
                'values.contact_phone' => ['nullable', 'string', 'max:80'],
                'values.contact_location' => ['nullable', 'string', 'max:255'],
            ],
            'security' => [
                'values.session_timeout' => ['required', 'integer', 'min:0', 'max:1440'],
                'values.tosa_gate' => ['required', 'boolean'],
            ],
        };

        $validated = $this->validateOsoJson($request, $rules);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $clean = collect($validated['values'])->only($sections[$section])->all();
        $setting = OfficeSetting::forScope('oso');
        $values = array_replace_recursive($setting->values ?? [], [$section => $clean]);
        $setting->update([
            'values' => $values,
            'updated_by' => $office->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => ucfirst($section).' settings saved.',
            'settings' => OfficeSetting::publicValuesFor('oso'),
        ]);
    }

    public function updateOsoAccount(Request $request): JsonResponse
    {
        $office = $this->settingsOfficer();
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $validated = $this->validateOsoJson($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->officialOfficeEmailRules($office),
            'office_title' => ['required', 'string', 'max:160'],
            'employee_id' => ['nullable', 'string', 'max:80'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $office = DB::transaction(function () use ($office, $validated): OfficeUser {
            $current = OfficeUser::query()->lockForUpdate()->findOrFail($office->id);
            abort_unless($current->is_active && (int) $current->auth_version === (int) $office->auth_version, 401);
            $current->update($validated);

            return $current;
        });
        $office->bindCurrentSession($request);

        return response()->json([
            'ok' => true,
            'message' => 'Account profile saved.',
            'user' => $office->accountMetadata(),
        ]);
    }

    public function updateOsoPassword(Request $request): JsonResponse
    {
        $office = $this->settingsOfficer();
        $validated = $this->validateOsoJson($request, [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $office = DB::transaction(function () use ($office, $validated): OfficeUser {
                $current = OfficeUser::query()->lockForUpdate()->findOrFail($office->id);
                abort_unless($current->is_active && (int) $current->auth_version === (int) $office->auth_version, 401);
                if (! Hash::check($validated['current_password'], (string) $current->password)) {
                    throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
                }
                if (Hash::check($validated['new_password'], (string) $current->password)) {
                    throw ValidationException::withMessages(['new_password' => 'Choose a password different from your current password.']);
                }
                $current->password = $validated['new_password'];
                $current->save();

                return $current;
            });
        } catch (ValidationException $exception) {
            return response()->json([
                'ok' => false,
                'message' => 'Please correct the highlighted settings and try again.',
                'errors' => $exception->errors(),
            ], 422);
        }
        $office->bindCurrentSession($request);

        return response()->json(['ok' => true, 'message' => 'Account password updated.']);
    }

    public function updateOsoPin(Request $request, string $type = 'tosa'): JsonResponse
    {
        $office = $this->osoOfficer();
        abort_unless($type === 'tosa', 404);

        $validated = $this->validateOsoJson($request, ['pin' => ['required', 'digits:4']]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $setting = OfficeSetting::forScope('oso');
        $values = $setting->values ?? [];
        $values['security']['tosa_pin_hash'] = Hash::make($validated['pin']);
        $setting->update([
            'values' => $values,
            'updated_by' => $office->id,
        ]);

        return response()->json(['ok' => true, 'message' => 'TOSA PIN updated and stored as a one-way hash.']);
    }

    public function verifyOsoTosaPin(Request $request): JsonResponse
    {
        $this->tosaOfficer();
        $validated = $this->validateOsoJson($request, ['pin' => ['required', 'digits:4']]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $security = OfficeSetting::valuesFor('oso')['security'];
        $configuredHash = $security['tosa_pin_hash'];
        $valid = filled($configuredHash) && Hash::check($validated['pin'], $configuredHash);
        if ($valid) {
            $request->session()->put('tosa_unlock', [
                'user_id' => Auth::guard('office')->id(),
                'at' => now()->timestamp,
                'signature' => hash('sha256', json_encode($security)),
            ]);
        } else {
            $request->session()->forget('tosa_unlock');
        }

        return response()->json([
            'ok' => $valid,
            'message' => $valid ? 'TOSA PIN accepted.' : ($configuredHash ? 'Invalid security PIN.' : 'The OSO must configure a TOSA PIN or disable the PIN gate.'),
        ], $valid ? 200 : 422);
    }

    public function storeOsoLogo(Request $request): JsonResponse
    {
        $office = $this->osoOfficer();
        $validated = $this->validateOsoJson($request, [
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $setting = OfficeSetting::forScope('oso');
        $values = $setting->values ?? [];
        $oldPath = $values['general']['logo_path'] ?? null;
        $path = $request->file('logo')->store('office-settings', 'public');
        $values['general']['logo_path'] = $path;
        $setting->update([
            'values' => $values,
            'updated_by' => $office->id,
        ]);

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Institutional logo uploaded.',
            'url' => asset('storage/'.$path),
        ]);
    }

    public function resetOsoLogo(): JsonResponse
    {
        $office = $this->osoOfficer();
        $setting = OfficeSetting::forScope('oso');
        $values = $setting->values ?? [];
        $oldPath = $values['general']['logo_path'] ?? null;
        $values['general']['logo_path'] = null;
        $setting->update([
            'values' => $values,
            'updated_by' => $office->id,
        ]);

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(['ok' => true, 'message' => 'Institutional logo reset to the default.']);
    }


    public function downloadOsoDataPackage(string $type): StreamedResponse
    {
        $this->osoOfficer();
        $type = Str::lower($type);
        if ($type === 'tosa-manifest') {
            $this->requireTosaUnlock(request());
        }

        $definition = match ($type) {
            'organization-roster' => [
                'filename' => 'orgchain-organization-roster-'.now()->format('Ymd-His').'.csv',
                'headers' => ['Name', 'Short Name', 'College', 'Academic Year', 'Status'],
                'rows' => StudentOrganization::query()->orderBy('name')->get()->map(fn (StudentOrganization $org) => [
                    $org->name,
                    $org->short_name,
                    $org->college,
                    $org->academic_year,
                    $org->is_active ? 'Active' : 'Inactive',
                ])->all(),
            ],
            'accomplishment-dossier' => [
                'filename' => 'orgchain-accomplishment-dossier-'.now()->format('Ymd-His').'.csv',
                'headers' => ['Activity', 'Organization', 'College', 'Start Date', 'Approved Budget', 'Implemented Budget', 'Workflow Status'],
                'rows' => OrgActivity::query()->orderByDesc('starts_at')->get()->map(fn (OrgActivity $activity) => [
                    $activity->title,
                    $activity->organization_name,
                    $activity->college,
                    optional($activity->starts_at)->format('Y-m-d H:i'),
                    number_format((float) $activity->approved_budget, 2, '.', ''),
                    number_format((float) $activity->implemented_budget, 2, '.', ''),
                    $activity->workflow_status,
                ])->all(),
            ],
            'tosa-manifest' => [
                'filename' => 'orgchain-tosa-manifest-'.now()->format('Ymd-His').'.csv',
                'headers' => ['Applicant', 'Student ID', 'Program', 'Organization', 'Stage', 'Submitted Requirements', 'Total Requirements'],
                'rows' => TosaApplicant::query()->orderBy('full_name')->get()->map(function (TosaApplicant $applicant) {
                    $requirements = is_array($applicant->requirements) ? $applicant->requirements : [];
                    $submitted = collect($requirements)->filter(fn ($value) => (bool) $value)->count();

                    return [
                        $applicant->full_name,
                        $applicant->sr_code,
                        $applicant->program,
                        $applicant->organization_name,
                        $applicant->subsection,
                        $submitted,
                        count($requirements),
                    ];
                })->all(),
            ],
            default => abort(404),
        };

        return response()->streamDownload(function () use ($definition): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $definition['headers']);
            foreach ($definition['rows'] as $row) {
                $safeRow = array_map(static function ($value) {
                    return is_string($value) && preg_match('/^[\s]*[=+\-@]/u', $value)
                        ? "'".$value
                        : $value;
                }, $row);
                fputcsv($out, $safeRow);
            }
            fclose($out);
        }, $definition['filename'], ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function downloadOsoSnapshot(): StreamedResponse
    {
        $this->osoOfficer();
        $snapshot = $this->osoSnapshotPayload();

        return response()->streamDownload(
            static function () use ($snapshot): void {
                echo json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            },
            'orgchain-oso-snapshot-'.now()->format('Ymd-His').'.json',
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    private function osoSnapshotPayload(): array
    {
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'scope' => 'oso',
            'settings' => OfficeSetting::publicValuesFor('oso'),
            'office_users' => OfficeUser::query()
                ->with('studentOrganization')
                ->whereIn('office_role', ['so', 'oso', 'sdo', 'ovcaa', 'oc'])
                ->orderBy('id')
                ->get()
                ->map(fn (OfficeUser $user) => $user->accountMetadata())
                ->all(),
            'counts' => [
                'organizations' => StudentOrganization::query()->count(),
                'activities' => OrgActivity::query()->count(),
                'tosa_applicants' => TosaApplicant::query()->count(),
            ],
        ];
        $payload['sha256'] = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $payload;
    }

    public function renewal(): View
    {
        $office = Auth::guard('office')->user();
        $role = $office?->office_role ?? '';
        abort_unless(in_array($role, ['so', 'oso'], true), 403);

        $window = OrgRenewalWindow::query()->latest('id')->first();
        $requiredDocs = $window?->requiredDocList() ?? OrgRenewalWindow::defaultRequiredDocs();
        $isOpen = $window?->isAcceptingSubmissions() ?? false;

        $submissions = collect();
        $mySubmission = null;
        $allOrganizations = collect();
        $orgStats = ['total' => 0, 'qualified' => 0, 'not_qualified' => 0, 'inactive' => 0];

        if ($role === 'oso') {
            $submissions = $window
                ? OrgRenewalSubmission::query()->with('documents')
                    ->where('renewal_window_id', $window->id)
                    ->whereIn('status', ['submitted', 'returned', 'approved', 'rejected'])
                    ->whereNotNull('submitted_at')
                    ->latest('id')->get()
                : collect();
            $submissionsByOrganization = $submissions->keyBy(fn (OrgRenewalSubmission $sub) => mb_strtolower($sub->organization_name));

            $allOrganizations = StudentOrganization::query()
                ->orderBy('name')
                ->get()
                ->map(function (StudentOrganization $org) use ($submissionsByOrganization, $requiredDocs) {
                    $sub = $submissionsByOrganization->get(mb_strtolower($org->name));
                    $review = $sub?->requiredDocumentReview($requiredDocs)
                        ?? ['required' => count($requiredDocs), 'verified' => 0, 'missing' => count($requiredDocs)];
                    // Final approval is authoritative, including approvals made before
                    // later template/checklist edits. Administrative flags stay separate.
                    $officiallyActive = (bool) $org->is_active && $sub?->status === 'approved';

                    return [
                        'id' => $org->id,
                        'name' => $org->name,
                        'short_name' => $org->short_name ?: $org->name,
                        'college' => $org->college ?: 'Campus Wide',
                        'is_active' => (bool) $org->is_active,
                        'is_qualified_for_renewal' => (bool) ($org->is_qualified_for_renewal ?? true),
                        'can_file_renewal' => (bool) $org->is_active && (bool) ($org->is_qualified_for_renewal ?? true),
                        'disqualification_reason' => $org->disqualification_reason,
                        'status_updated_at' => $org->status_updated_at ? $org->status_updated_at->format('M j, Y g:i A') : null,
                        'submission_status' => $sub?->status ?? 'none',
                        'submission_id' => $sub?->id,
                        'submitted_at' => $sub?->submitted_at,
                        'officially_active' => $officiallyActive,
                        'requirements_required' => $review['required'],
                        'requirements_verified' => $review['verified'],
                        'requirements_pending' => max(0, $review['required'] - $review['verified']),
                        'requirements_missing' => $review['missing'],
                        'docs_count' => $review['required'] - $review['missing'],
                        'status_url' => route('office.renewal.organization.status', $org),
                    ];
                });

            $orgStats = [
                'total' => $allOrganizations->count(),
                'qualified' => $allOrganizations->where('can_file_renewal', true)->count(),
                'not_qualified' => $allOrganizations->where('can_file_renewal', false)->count(),
                'inactive' => $allOrganizations->where('officially_active', false)->count(),
            ];
        } else {
            $assignedOrg = $this->assignedOrganizationName();
            $orgName = $assignedOrg
                ?: $this->organizationFilterForOffice(request('organization_name'))
                ?: 'College of Informatics and Computing Sciences Student Council (CICS-SC)';

            if ($window) {
                $mySubmission = OrgRenewalSubmission::query()
                    ->with('documents')
                    ->where('renewal_window_id', $window->id)
                    ->whereIn('status', ['submitted', 'returned', 'approved', 'rejected'])
                    ->whereNotNull('submitted_at')
                    ->where(function ($q) use ($office, $orgName) {
                        $q->where('submitted_by', $office?->id)
                            ->orWhere('organization_name', $orgName);
                    })
                    ->latest('id')
                    ->first();
            }
        }

        $defaultTargetOrg = 'College of Informatics and Computing Sciences Student Council (CICS-SC)';
        $targetOrg = $role === 'so'
            ? ($office?->studentOrganization?->name ?? ($orgName ?? $defaultTargetOrg))
            : $defaultTargetOrg;
        $targetOrgModel = $role === 'so'
            ? ($office?->studentOrganization ?? StudentOrganization::query()->where('name', $targetOrg)->first())
            : StudentOrganization::query()->where('name', $targetOrg)->first();
        $targetOrg = $targetOrgModel?->name ?? $targetOrg;
        $targetCollege = $targetOrgModel?->college ?? ($role === 'so' ? '' : 'College of Informatics and Computing Sciences');
        $officialRequirementFiles = [];
        foreach ($requiredDocs as $doc) {
            $file = $this->resolveRenewalTemplate($doc);
            $preview = $this->resolveRenewalTemplate($doc, true);
            $parameters = [
                'docKey' => $doc['key'], 'window_id' => $window?->id,
                'v' => substr(hash('sha256', ($doc['template_path'] ?? '').'|'.($doc['pdf_path'] ?? '')), 0, 16),
            ];
            $officialRequirementFiles[$doc['key']] = array_merge($doc, [
                'code' => $this->renewalRequirementCode($doc),
                'file_available' => $window !== null && $file !== null,
                'file_name' => $file['name'] ?? null,
                'preview_type' => $preview['extension'] ?? null,
                'preview_url' => route('office.renewal.requirements.file', $parameters + ['preview' => 1]),
                'download_url' => route('office.renewal.requirements.file', $parameters + ['download' => 1]),
            ]);
        }

        return view('org.renewal', array_merge($this->deskContext(), [
            'activeNav' => 'renewal',
            'renewalWindow' => $window,
            'requiredDocs' => $requiredDocs,
            'renewalIsOpen' => $isOpen,
            'renewalSubmissions' => $submissions,
            'myRenewalSubmission' => $mySubmission,
            'targetOrg' => $targetOrg,
            'targetCollege' => $targetCollege,
            'targetOrgModel' => $targetOrgModel,
            'allOrganizations' => $allOrganizations,
            'orgStats' => $orgStats,
            'officialRequirementFiles' => $officialRequirementFiles,
            'otherRenewalSubmissions' => $submissions->whereNotIn('id', $allOrganizations->pluck('submission_id')->filter())->values(),
            'orgChoices' => [$targetOrg],
        ]));
    }

    public function updateRenewalWindow(Request $request): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless(($office?->office_role ?? '') === 'oso', 403);
        $validated = $request->validate([
            'window_id' => ['nullable', 'integer'],
            'academic_year' => ['required', 'string', 'max:32'],
            'semester' => ['required', 'string', 'max:40'],
            'is_open' => ['required', 'boolean'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'required_docs' => ['prohibited'],
        ]);

        DB::connection('mysql')->transaction(function () use ($request, $validated, $office): void {
            $window = OrgRenewalWindow::query()->latest('id')->lockForUpdate()->first();
            if (($window?->id ?? 0) !== (int) ($validated['window_id'] ?? 0)) {
                throw ValidationException::withMessages(['renewal' => 'The renewal window changed. Refresh the dashboard before saving.']);
            }
            $isOpen = $request->boolean('is_open');
            $payload = [
                'academic_year' => trim($validated['academic_year']),
                'semester' => trim($validated['semester']),
                'is_open' => $isOpen,
                'opens_at' => $validated['opens_at'] ?? ($isOpen ? now() : null),
                'closes_at' => $validated['closes_at'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
                'notes' => $validated['notes'] ?? $window?->notes,
                'required_docs' => $window?->requiredDocList() ?? OrgRenewalWindow::defaultRequiredDocs(),
                'opened_by' => $isOpen ? $office->id : $window?->opened_by,
                'closed_by' => $isOpen ? null : $office->id,
            ];
            // A new period must not inherit last year's final approvals.
            if ($window && $window->academic_year === $payload['academic_year'] && $window->semester === $payload['semester']) {
                $window->update($payload);
            } else {
                OrgRenewalWindow::query()->create($payload);
            }
        });

        return redirect()->route('office.renewal')->with('success', 'Renewal window settings saved.');
    }

    public function storeRenewalRequirement(Request $request): RedirectResponse
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'oso', 403);
        $validated = $request->validate([
            'window_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg', 'max:20480'],
        ]);
        $storedPath = null;
        try {
            DB::connection('mysql')->transaction(function () use ($request, $validated, &$storedPath): void {
                $window = $this->lockedRenewalWindow($request);
                $docs = $window->requiredDocList();
                if (count($docs) >= 30) {
                    throw ValidationException::withMessages(['renewal' => 'A filing window can contain at most 30 requirements.']);
                }
                $code = trim($validated['code']);
                foreach ($docs as $doc) {
                    if (mb_strtolower($this->renewalRequirementCode($doc)) === mb_strtolower($code)) {
                        throw ValidationException::withMessages(['code' => 'This requirement code already exists.']);
                    }
                }
                $file = $request->file('document');
                $storedPath = $file->store('renewal-templates', 'public');
                if (! $storedPath) {
                    throw new \RuntimeException('The official template could not be stored.');
                }
                $docs[] = [
                    // Never reconnect an old signed upload to a newly added requirement.
                    'key' => substr(Str::slug($code, '_') ?: 'requirement', 0, 50).'_'.Str::lower((string) Str::ulid()),
                    'attachment_label' => $code,
                    'title' => trim($validated['title']),
                    'description' => trim((string) ($validated['description'] ?? '')),
                    'template_path' => $storedPath,
                    'template_name' => $file->getClientOriginalName(),
                ];
                $window->update(['required_docs' => $docs]);
            });
        } catch (\Throwable $error) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            throw $error;
        }
        return redirect()->route('office.renewal')->with('success', 'Official renewal requirement added.');
    }

    public function updateRenewalRequirement(Request $request, string $docKey): RedirectResponse
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'oso', 403);
        $validated = $request->validate([
            'window_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'code' => ['prohibited'],
            'key' => ['prohibited'],
        ]);
        DB::connection('mysql')->transaction(function () use ($request, $validated, $docKey): void {
            $window = $this->lockedRenewalWindow($request);
            $docs = $window->requiredDocList();
            $index = collect($docs)->search(fn ($doc) => $doc['key'] === $docKey);
            abort_if($index === false, 404);
            $docs[$index]['title'] = trim($validated['title']);
            $docs[$index]['description'] = trim((string) ($validated['description'] ?? ''));
            $window->update(['required_docs' => $docs]);
        });
        return redirect()->route('office.renewal')->with('success', 'Official renewal requirement updated.');
    }

    public function destroyRenewalRequirement(Request $request, string $docKey): RedirectResponse
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'oso', 403);
        $request->validate(['window_id' => ['required', 'integer']]);
        DB::connection('mysql')->transaction(function () use ($request, $docKey): void {
            $window = $this->lockedRenewalWindow($request);
            $docs = $window->requiredDocList();
            abort_unless(collect($docs)->contains('key', $docKey), 404);
            if (count($docs) <= 1) {
                throw ValidationException::withMessages(['renewal' => 'Keep at least one official renewal requirement.']);
            }
            // Remove the checklist entry, not signed documents or shared historical files.
            $window->update(['required_docs' => array_values(array_filter($docs, fn ($doc) => $doc['key'] !== $docKey))]);
        });
        return redirect()->route('office.renewal')->with('success', 'Official requirement removed from the current checklist.');
    }

    public function storeRenewalRequirementTemplate(Request $request): RedirectResponse
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'oso', 403);
        $validated = $request->validate([
            'window_id' => ['required', 'integer'],
            'doc_key' => ['required', 'string', 'max:80'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg', 'max:20480'],
        ]);
        $storedPath = null;
        try {
            DB::connection('mysql')->transaction(function () use ($request, $validated, &$storedPath): void {
                $window = $this->lockedRenewalWindow($request);
                $docs = $window->requiredDocList();
                $index = collect($docs)->search(fn ($doc) => $doc['key'] === $validated['doc_key']);
                abort_if($index === false, 404);
                $file = $request->file('document');
                $storedPath = $file->store('renewal-templates', 'public');
                if (! $storedPath) {
                    throw new \RuntimeException('The official template could not be stored.');
                }
                $docs[$index]['template_path'] = $storedPath;
                $docs[$index]['template_name'] = $file->getClientOriginalName();
                unset($docs[$index]['pdf_path']);
                $window->update(['required_docs' => $docs]);
            });
        } catch (\Throwable $error) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            throw $error;
        }
        return redirect()->route('office.renewal')->with('success', 'Official template replaced. Submitted organization files are unchanged.');
    }

    public function renewalRequirementFile(Request $request, string $docKey): BinaryFileResponse
    {
        abort_unless(in_array(Auth::guard('office')->user()?->office_role, ['oso', 'so'], true), 403);
        $request->validate(['window_id' => ['nullable', 'integer'], 'preview' => ['nullable', 'boolean'], 'download' => ['nullable', 'boolean']]);
        $window = $request->filled('window_id')
            ? OrgRenewalWindow::query()->findOrFail($request->integer('window_id'))
            : OrgRenewalWindow::query()->latest('id')->firstOrFail();
        $doc = collect($window->requiredDocList())->firstWhere('key', $docKey);
        abort_unless(is_array($doc), 404);
        $file = $this->resolveRenewalTemplate($doc, $request->boolean('preview') && ! $request->boolean('download'));
        abort_unless($file, 404, 'No official template is attached to this requirement.');
        if ($request->boolean('download')) {
            return response()->download($file['path'], $file['name'], ['Cache-Control' => 'private, no-store'])->setPrivate();
        }
        return response()->file($file['path'], ['Cache-Control' => 'private, no-store'])->setPrivate()->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE, $file['name'],
            preg_replace('/[^A-Za-z0-9._-]/', '_', Str::ascii($file['name']))
        );
    }

    private function lockedRenewalWindow(Request $request): OrgRenewalWindow
    {
        $window = OrgRenewalWindow::query()->latest('id')->lockForUpdate()->first();
        if (! $window || $window->id !== $request->integer('window_id')) {
            throw ValidationException::withMessages(['renewal' => 'The renewal window changed. Refresh the dashboard before saving.']);
        }
        return $window;
    }

    private function renewalRequirementCode(array $doc): string
    {
        $default = collect(OrgRenewalWindow::defaultRequiredDocs())->firstWhere('key', $doc['key']);
        return $doc['attachment_label'] ?? $default['attachment_label'] ?? $doc['key'];
    }

    /** @return array{path: string, name: string, extension: string}|null */
    private function resolveRenewalTemplate(array $doc, bool $preview = false): ?array
    {
        $templatePath = $doc['template_path'] ?? null;
        $pdfPath = $doc['pdf_path'] ?? null;
        $default = collect(OrgRenewalWindow::defaultRequiredDocs())->firstWhere('key', $doc['key']);
        // The old replacement endpoint retained the original paired PDF. It is
        // not a preview of a subsequently uploaded template.
        if ($pdfPath && $pdfPath === ($default['pdf_path'] ?? null)
            && $templatePath !== ($default['template_path'] ?? null)) {
            $pdfPath = null;
        }
        if ($preview && ! $pdfPath) {
            // Recover paired previews stripped by the previous bulk editor, but
            // never use an original PDF after the official Word file is replaced.
            if ($templatePath && $templatePath === ($default['template_path'] ?? null)) {
                $pdfPath = $default['pdf_path'] ?? null;
            }
        }
        $candidates = $preview ? array_filter([$pdfPath, $templatePath]) : array_filter([$templatePath]);
        foreach ($candidates as $relativePath) {
            $absolutePath = Storage::disk('public')->path($relativePath);
            if (! is_file($absolutePath)) {
                $absolutePath = public_path('templates/renewal/'.basename($relativePath));
            }
            if (is_file($absolutePath)) {
                return [
                    'path' => $absolutePath,
                    'name' => $relativePath === $templatePath ? ($doc['template_name'] ?? basename($relativePath)) : basename($relativePath),
                    'extension' => strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)),
                ];
            }
        }
        return null;
    }

    public function storeRenewalSubmission(Request $request): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless(($office?->office_role ?? '') === 'so', 403);

        $window = OrgRenewalWindow::query()->latest('id')->first();
        if (! $window || ! $window->isAcceptingSubmissions()) {
            return back()->withErrors(['renewal' => 'Renewal is locked. Wait for OSO to open the filing window.']);
        }
        $requiredDocs = $window->requiredDocList();
        $validated = $request->validate([
            'action' => ['required', 'in:submit'],
            'window_id' => ['required', 'integer'],
            'organization_name' => ['required', 'string', 'max:255'],
            'college' => ['nullable', 'string', 'max:255'],
            'adviser_name' => ['required', 'string', 'max:255'],
            'dean_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array:'.implode(',', array_column($requiredDocs, 'key'))],
            'documents.*' => [
                'bail', 'file',
                function ($attribute, $file, $fail): void {
                    if ($file->getSize() === 0) {
                        $fail('This document is empty. Select a completed document.');
                    }
                },
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg', 'max:20480',
            ],
        ]);

        $assignedOrganization = $this->assignedOrganizationName();
        if ($assignedOrganization !== null) {
            $validated['organization_name'] = $assignedOrganization;
        }
        $files = $request->file('documents', []);
        $storedPaths = [];
        try {
            DB::connection('mysql')->transaction(function () use ($window, $validated, $office, $files, $assignedOrganization, &$storedPaths): void {
                $lockedWindow = OrgRenewalWindow::query()->lockForUpdate()->findOrFail($window->id);
                if ((int) $validated['window_id'] !== (int) $lockedWindow->id
                    || (int) OrgRenewalWindow::query()->latest('id')->value('id') !== (int) $lockedWindow->id
                    || ! $lockedWindow->isAcceptingSubmissions()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['renewal' => 'The renewal window changed or closed. Reload the page before submitting.']);
                }
                $requiredDocs = $lockedWindow->requiredDocList();
                $existing = OrgRenewalSubmission::query()
                    ->where('renewal_window_id', $lockedWindow->id)
                    ->where('organization_name', $validated['organization_name'])
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    abort_unless((int) $existing->submitted_by === (int) $office->id || $assignedOrganization === $existing->organization_name, 403);
                    if (in_array($existing->status, OrgRenewalSubmission::TERMINAL_STATUSES, true)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['renewal' => 'This renewal packet was already '.$existing->status.' by OSO and can no longer be edited.']);
                    }
                }
                $orgModel = StudentOrganization::where('name', $validated['organization_name'])->first();
                if ($orgModel && (! ($orgModel->is_qualified_for_renewal ?? true) || ! $orgModel->is_active)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['renewal' => 'Renewal submission blocked: '.($orgModel->disqualification_reason ?: 'This organization is Not Qualified to Renew or Inactive by OSO.')]);
                }
                $documents = $existing?->documents()->get()->keyBy('doc_key') ?? collect();
                $unlisted = array_diff(array_keys($files), array_column($requiredDocs, 'key'));
                if ($unlisted) {
                    throw ValidationException::withMessages(['renewal' => 'The required document list changed. Reload the page before submitting.']);
                }
                $missing = [];
                foreach ($requiredDocs as $doc) {
                    $saved = $documents->get($doc['key']);
                    if (! isset($files[$doc['key']]) && (! $saved || ! $saved->hasStoredFile())) {
                        $missing[] = $doc['title'];
                    }
                }
                if ($missing) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['renewal' => 'Select all required documents before submitting: '.implode(', ', $missing).'.']);
                }

                $submission = $existing ?? new OrgRenewalSubmission([
                    'renewal_window_id' => $lockedWindow->id,
                    'organization_name' => $validated['organization_name'],
                ]);
                $submission->fill([
                    'college' => $validated['college'] ?? null,
                    'submitted_by' => $office->id,
                    'adviser_name' => $validated['adviser_name'],
                    'dean_name' => $validated['dean_name'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'review_remarks' => null,
                    'reviewed_at' => null,
                ])->save();
                foreach ($requiredDocs as $doc) {
                    if (! isset($files[$doc['key']])) {
                        continue;
                    }
                    $file = $files[$doc['key']];
                    $path = $file->store('renewal-documents', 'public');
                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('The renewal document could not be stored.');
                    }
                    $storedPaths[] = $path;
                    $submission->documents()->updateOrCreate(['doc_key' => $doc['key']], [
                        'title' => $doc['title'],
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'review_status' => OrgRenewalDocument::REVIEW_PENDING,
                        'review_remarks' => null,
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                    ]);
                }
            });
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($storedPaths);
            throw $error;
        }

        return redirect()->route('office.renewal')->with('success', 'Renewal packet submitted for review.');
    }

    public function archive(Request $request): View
    {
        $organization = $this->archiveOrganization();
        $savedFolders = $this->accessibleArchiveFolders($organization);
        $folderIds = $savedFolders->modelKeys();
        $folderIndex = $savedFolders->keyBy('id');
        $activities = OrgActivity::query()
            ->when($organization !== null, fn ($query) => $query->where('organization_name', $organization))
            ->with(['complianceDocs.submission'])
            ->orderBy('title')->get();
        $activityDocuments = $activities->mapWithKeys(fn (OrgActivity $activity) => [
            $activity->id => $activity->complianceDocs->map(function (ActivityComplianceDoc $document) use ($activity) {
                $file = $this->archivedActivityFile($document, $activity);
                if ($file === null) {
                    return null;
                }

                return [
                    'id' => 'act-doc-'.$document->id,
                    'name' => $document->title ?: pathinfo($file['name'], PATHINFO_FILENAME),
                    'original_name' => $file['name'],
                    'size' => $this->formatFileSize($file['size']),
                    'date' => $document->updated_at?->format('M j, Y'),
                    'author' => $activity->organization_name ?: 'Student Organization',
                    'type' => strtoupper(pathinfo($file['name'], PATHINFO_EXTENSION)),
                    'url' => route('office.archive.activity-documents.view', $document),
                    'download_url' => route('office.archive.activity-documents.download', $document),
                    'folder_id' => 'activity-'.$activity->id,
                    'folder_name' => $activity->title,
                    'file_size' => $file['size'],
                ];
            })->filter()->values(),
        ]);
        $folderId = $request->query('folder_id');
        $currentFolder = null;
        $parentFolderId = null;
        $breadcrumbs = [['id' => null, 'name' => 'Archive Vault']];
        $isActivityFolder = false;

        if ($folderId !== null && $folderId !== '') {
            abort_unless(is_scalar($folderId), 404);
            if (preg_match('/^activity-([1-9][0-9]*)$/', (string) $folderId, $matches)) {
                $activity = $activities->firstWhere('id', (int) $matches[1]);
                abort_unless($activity instanceof OrgActivity, 404);
                $isActivityFolder = true;
                $currentFolder = (object) [
                    'id' => 'activity-'.$activity->id, 'name' => $activity->title,
                    'organization_name' => $activity->organization_name,
                    'semester' => null, 'color' => 'blue', 'is_activity' => true,
                    'is_saved' => false, 'parent_id' => null,
                ];
                $breadcrumbs[] = ['id' => $currentFolder->id, 'name' => $currentFolder->name];
            } else {
                abort_unless(ctype_digit((string) $folderId), 404);
                $currentFolder = $folderIndex->get((int) $folderId);
                abort_unless($currentFolder instanceof ArchiveFolder, 404);
                $parentFolderId = $currentFolder->parent_id;
                $breadcrumbs = array_merge($breadcrumbs, $this->archiveBreadcrumbs($currentFolder, $folderIndex));
            }
        }

        $childCounts = $savedFolders->countBy(fn (ArchiveFolder $folder) => $folder->parent_id ?? 'root');
        $mapFolder = fn (ArchiveFolder $folder): array => [
            'id' => $folder->id, 'name' => $folder->name,
            'org' => $folder->organization_name, 'semester' => $folder->semester,
            'documents' => $folder->documents_count,
            'subfolders' => $childCounts->get($folder->id, 0),
            'icon' => 'folder-fill', 'color' => $folder->color ?: 'blue',
            'is_saved' => true, 'parent_id' => $folder->parent_id,
        ];
        $documentQuery = ArchiveDocument::query()->whereIn('archive_folder_id', $folderIds);
        if ($isActivityFolder) {
            $folders = collect();
            $documents = $activityDocuments->get($activity->id);
        } else {
            $folders = $savedFolders->where('parent_id', $currentFolder?->id)->map($mapFolder)->values();
            $documents = (clone $documentQuery)
                ->when($currentFolder !== null, fn ($query) => $query->where('archive_folder_id', $currentFolder->id))
                ->latest()->when($currentFolder === null, fn ($query) => $query->limit(24))
                ->get()->map(fn (ArchiveDocument $document): array => [
                    'id' => $document->id, 'name' => $document->name,
                    'original_name' => $document->original_name,
                    'size' => $this->formatFileSize($document->file_size),
                    'date' => $document->created_at->format('M j, Y'),
                    'author' => $document->uploaded_by ?: 'Student Organization',
                    'type' => strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION) ?: 'DOC'),
                    'url' => route('office.archive.documents.view', $document),
                    'download_url' => route('office.archive.documents.download', $document),
                    'folder_id' => $document->archive_folder_id,
                    'folder_name' => $folderIndex->get($document->archive_folder_id)->name,
                ]);
            if ($currentFolder === null) {
                $folders = $folders->concat($activities->map(fn (OrgActivity $activity): array => [
                    'id' => 'activity-'.$activity->id, 'name' => $activity->title,
                    'org' => $activity->organization_name ?: $activity->college,
                    'semester' => null, 'documents' => $activityDocuments->get($activity->id)->count(),
                    'subfolders' => 0, 'icon' => 'folder-fill',
                    'color' => match ($activity->workflow_status) {
                        'oc_approved' => 'green', 'returned' => 'gold', default => 'red',
                    },
                    'is_saved' => false, 'is_activity' => true, 'parent_id' => null,
                ]));
            }
        }

        $allSavedFolders = $savedFolders->map(fn (ArchiveFolder $folder): array => [
            'id' => $folder->id, 'name' => $folder->name, 'parent_id' => $folder->parent_id,
            'path' => implode(' / ', array_column($this->archiveBreadcrumbs($folder, $folderIndex), 'name')),
        ])->values();
        $activityFiles = $activityDocuments->flatten(1);

        return view('org.archive', array_merge($this->deskContext(), [
            'activeNav' => 'archive', 'assignedArchiveOrganization' => $organization,
            'currentFolder' => $currentFolder, 'currentFolderId' => $currentFolder?->id,
            'parentFolderId' => $parentFolderId, 'breadcrumbs' => $breadcrumbs,
            'folders' => $folders, 'documents' => $documents, 'allSavedFolders' => $allSavedFolders,
            'totalDocuments' => (clone $documentQuery)->count() + $activityFiles->count(),
            'totalFolders' => $savedFolders->count() + $activities->count(),
            'storageUsedFormatted' => $this->formatFileSize((int) (clone $documentQuery)->sum('file_size') + (int) $activityFiles->sum('file_size')),
            'currentSemester' => '2nd Semester',
        ]));
    }

    private function archiveOrganization(): ?string
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && in_array($office->office_role, ['so', 'oso'], true), 403);
        if ($office->office_role === 'oso') {
            return null;
        }

        $organization = $office->studentOrganization;
        abort_unless($organization instanceof StudentOrganization && trim($organization->name) !== '', 403);

        return $organization->name;
    }

    private function accessibleArchiveFolders(?string $organization): Collection
    {
        $folders = ArchiveFolder::query()
            ->when($organization !== null, fn ($query) => $query->where('organization_name', $organization))
            ->withCount('documents')->orderBy('name')->get();
        $index = $folders->keyBy('id');

        // Do not expose descendants whose ancestry escapes the assigned organization.
        return $folders->filter(function (ArchiveFolder $folder) use ($index, $organization) {
            $visited = [];
            while ($folder->parent_id !== null) {
                if (isset($visited[$folder->id])) {
                    return false;
                }
                $visited[$folder->id] = true;
                $parent = $index->get($folder->parent_id);
                if (! $parent instanceof ArchiveFolder
                    || ($organization !== null && $parent->organization_name !== $folder->organization_name)) {
                    return false;
                }
                $folder = $parent;
            }

            return true;
        })->values();
    }

    private function archiveBreadcrumbs(ArchiveFolder $folder, Collection $index): array
    {
        $breadcrumbs = [];
        do {
            array_unshift($breadcrumbs, ['id' => $folder->id, 'name' => $folder->name]);
            $folder = $folder->parent_id === null ? null : $index->get($folder->parent_id);
        } while ($folder instanceof ArchiveFolder);

        return $breadcrumbs;
    }

    public function viewArchiveDocument(Request $request, ArchiveDocument $document): BinaryFileResponse
    {
        return $this->archiveDocumentResponse($document, false);
    }

    public function downloadArchiveDocument(Request $request, ArchiveDocument $document): BinaryFileResponse
    {
        return $this->archiveDocumentResponse($document, true);
    }

    private function archiveDocumentResponse(ArchiveDocument $document, bool $download): BinaryFileResponse
    {
        $folders = $this->accessibleArchiveFolders($this->archiveOrganization());
        abort_unless($folders->contains('id', $document->archive_folder_id), 404);
        $disk = $document->file_disk ?: 'public';
        abort_unless(in_array($disk, ['public', 'local'], true), 404);
        $path = $this->archiveFilePath($disk, $document->file_path);
        abort_if($path === null, 404);

        return $this->archiveFileResponse($path, $document->original_name, $document->mime_type, $download);
    }

    public function viewArchivedActivityDocument(Request $request, ActivityComplianceDoc $document): BinaryFileResponse
    {
        return $this->archivedActivityResponse($document, false);
    }

    public function downloadArchivedActivityDocument(Request $request, ActivityComplianceDoc $document): BinaryFileResponse
    {
        return $this->archivedActivityResponse($document, true);
    }

    private function archivedActivityResponse(ActivityComplianceDoc $document, bool $download): BinaryFileResponse
    {
        $organization = $this->archiveOrganization();
        $activity = $document->activity;
        abort_unless($activity instanceof OrgActivity
            && ($organization === null || $activity->organization_name === $organization), 404);
        $file = $this->archivedActivityFile($document, $activity);
        abort_if($file === null, 404);

        return $this->archiveFileResponse($file['path'], $file['name'], mime_content_type($file['path']) ?: 'application/octet-stream', $download);
    }

    private function archivedActivityFile(ActivityComplianceDoc $document, OrgActivity $activity): ?array
    {
        $submission = $document->submission;
        if (! $submission instanceof InCampusActivitySubmission || $submission->org_activity_id !== $document->org_activity_id
            || ($submission->organization_name !== null && $submission->organization_name !== $activity->organization_name)) {
            return null;
        }
        $metadata = $submission->attachments[$document->doc_key] ?? null;
        if (! is_array($metadata) || empty($metadata['path'])) {
            return null;
        }
        $path = $this->archiveFilePath('public', (string) $metadata['path']);
        if ($path === null) {
            return null;
        }

        return ['path' => $path, 'name' => $metadata['name'] ?? basename($path), 'size' => filesize($path)];
    }

    private function archiveFilePath(string $disk, string $storedPath): ?string
    {
        $relativePath = str_replace('\\', '/', $storedPath);
        if ($relativePath === '' || str_starts_with($relativePath, '/') || preg_match('/^[a-z]:/i', $relativePath)
            || preg_match('~(^|/)\.\.(/|$)~', $relativePath)) {
            return null;
        }
        $storage = Storage::disk($disk);
        if ($storedPath === '' || ! $storage->exists($storedPath)) {
            return null;
        }
        $path = realpath($storage->path($storedPath));
        $root = realpath($storage->path(''));
        if ($path === false || $root === false || ! is_file($path)
            || ! str_starts_with(str_replace('\\', '/', $path), rtrim(str_replace('\\', '/', $root), '/').'/')) {
            return null;
        }

        return $path;
    }

    private function archiveFileResponse(string $path, string $name, string $mime, bool $download): BinaryFileResponse
    {
        $name = str_replace(["\r", "\n", '"', '/', '\\'], '_', $name);
        $name = $name !== '' ? $name : basename($path);
        $response = response()->file($path, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff']);
        $response->setContentDisposition($download ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE, $name);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function tosa(): View
    {
        $office = $this->tosaOfficer();
        $unlock = $this->tosaUnlockState(request());
        $subsection = request('subsection', 'all');
        $applicants = $unlock['unlocked']
            ? TosaApplicant::query()
                ->when($subsection !== 'all', fn ($q) => $q->where('subsection', $subsection))
                ->orderByRaw('CASE WHEN gwa IS NULL THEN 1 ELSE 0 END, gwa ASC, full_name ASC')
                ->get()
            : collect();
        $tosaTemplates = OfficeTemplate::query()
            ->where('category', 'TOSA')
            ->latest()
            ->get()
            ->mapWithKeys(fn (OfficeTemplate $template): array => [
                $template->name => [
                    'id' => $template->id,
                    'name' => $template->original_name ?: $template->name,
                    'url' => route('office.updates.templates.download', $template),
                    'size' => $template->size,
                ],
            ])
            ->all();

        return view('org.tosa', array_merge($this->deskContext(), [
            'activeNav' => 'tosa',
            'tosaUnlocked' => $unlock['unlocked'],
            'tosaPinGateEnabled' => (bool) data_get(OfficeSetting::valuesFor('oso'), 'security.tosa_gate'),
            'tosaUnlockSecondsRemaining' => $unlock['remaining'],
            'tosaClearanceLevel' => $office->tosaClearanceLevel(),
            'tosaCanReview' => $office->tosaClearanceLevel() >= 2,
            'tosaCanManageTemplates' => $office->office_role === 'oso' && $office->tosaClearanceLevel() >= 3,
            'tosaApplicants' => $applicants,
            'tosaTemplates' => $tosaTemplates,
            'tosaSubsections' => [
                'all' => 'All',
                'pending' => 'Pending',
                'screening' => 'Screening',
                'interview' => 'Interview',
                'accepted' => 'Accepted',
                'rejected' => 'Rejected',
                'returned' => 'Returned',
            ],
            'selectedSubsection' => $subsection,
            'subsectionCounts' => $unlock['unlocked']
                ? TosaApplicant::query()
                    ->selectRaw('subsection, COUNT(*) as total')
                    ->groupBy('subsection')
                    ->pluck('total', 'subsection')
                : collect(),
        ]));
    }

    /**
     * Persist the official sample/template attached to a TOSA requirement.
     * Master clearance is required for OSO template management.
     */
    public function storeTosaRequirementTemplate(Request $request): RedirectResponse
    {
        $office = $this->requireTosaUnlock($request, 3);
        abort_unless($office->office_role === 'oso', 403);
        $validated = $request->validate([
            'requirement_id' => ['required', 'integer', 'min:1'],
            'requirement_title' => ['required', 'string', 'max:255'],
            'template_file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,png,jpg,jpeg', 'max:20480'],
        ]);

        $file = $request->file('template_file');
        $path = $file->store('tosa-templates', 'public');
        $template = OfficeTemplate::query()
            ->where('category', 'TOSA')
            ->where('name', $validated['requirement_title'])
            ->first();
        $oldPath = $template?->file_path;
        $data = [
            'name' => $validated['requirement_title'],
            'category' => 'TOSA',
            'format' => strtoupper($file->getClientOriginalExtension()) ?: 'PDF',
            'size' => $this->formatFileSize((int) $file->getSize()),
            'description' => 'Official TOSA sample/template for requirement #'.$validated['requirement_id'].'.',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'downloads' => $template?->downloads ?? 0,
        ];

        if ($template) {
            $template->update($data);
        } else {
            OfficeTemplate::query()->create($data);
        }

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('office.tosa')->with('success', 'Official template uploaded for '.$validated['requirement_title'].'.');
    }

    public function storeArchiveFolder(Request $request): RedirectResponse
    {
        $organization = $this->archiveOrganization();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'semester' => ['nullable', 'in:1st Semester,2nd Semester,Midyear'],
            'color' => ['nullable', 'in:red,green,blue,violet,gold'],
        ]);

        if (($validated['parent_id'] ?? null) !== null) {
            $parent = $this->accessibleArchiveFolders($organization)->firstWhere('id', (int) $validated['parent_id']);
            abort_unless($parent instanceof ArchiveFolder, 404);
            $validated['organization_name'] = $parent->organization_name;
            $validated['semester'] = $validated['semester'] ?? $parent->semester;
            $validated['color'] = $validated['color'] ?? $parent->color;
        } else {
            $validated['organization_name'] = $organization ?? $validated['organization_name'] ?? 'General Organization';
        }
        $validated['semester'] = $validated['semester'] ?? '2nd Semester';
        $validated['color'] = $validated['color'] ?? 'blue';

        $folder = ArchiveFolder::query()->create($validated);

        $defaultRoute = !empty($validated['parent_id'])
            ? route('office.archive', ['folder_id' => $validated['parent_id']])
            : route('office.archive');

        $back = $this->officeReturnPath($request, 'office.archive');
        if ($back === route('office.archive') && !empty($validated['parent_id'])) {
            $back = $defaultRoute;
        }

        return redirect()->to($back)->with('success', 'Archive folder "'.$folder->name.'" created.');
    }

    public function storeArchiveDocument(Request $request): RedirectResponse
    {
        $organization = $this->archiveOrganization();

        $validated = $request->validate([
            'archive_folder_id' => ['required', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,png,jpg,jpeg', 'max:20480'],
        ]);
        $folder = $this->accessibleArchiveFolders($organization)->firstWhere('id', (int) $validated['archive_folder_id']);
        abort_unless($folder instanceof ArchiveFolder, 404);
        $file = $request->file('document');
        if (strtolower($file->getClientOriginalExtension()) === 'docx') {
            $this->validateArchiveDocx($file);
        }
        $path = $file->store("archive/{$folder->id}", 'local');
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('The archive document could not be stored.');
        }
        try {
            $doc = ArchiveDocument::query()->create([
                'archive_folder_id' => $folder->id,
                'name' => ($validated['name'] ?? null) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_disk' => 'local',
                'mime_type' => strtolower($file->getClientOriginalExtension()) === 'docx'
                    ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    : ($file->getMimeType() ?: 'application/octet-stream'),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::guard('office')->user()?->name,
            ]);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        $defaultRoute = route('office.archive', ['folder_id' => $validated['archive_folder_id']]);
        $back = $this->officeReturnPath($request, 'office.archive');
        if ($back === route('office.archive')) {
            $back = $defaultRoute;
        }

        return redirect()->to($back)->with('success', 'Document "'.$doc->name.'" uploaded to the archive.');
    }

    private function validateArchiveDocx(\Illuminate\Http\UploadedFile $file): void
    {
        $reject = static function (): never {
            throw ValidationException::withMessages(['document' => 'Upload a genuine, readable Word DOCX document.']);
        };
        if (! class_exists(ZipArchive::class)) {
            $reject();
        }
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            $reject();
        }
        try {
            $uncompressed = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $name = (string) ($stat['name'] ?? '');
                $size = (int) ($stat['size'] ?? 0);
                if (str_contains($name, '..') || str_starts_with($name, '/')
                    || $size > 20 * 1024 * 1024 || ($uncompressed += $size) > 50 * 1024 * 1024) {
                    $reject();
                }
            }
            $xml = $zip->getFromName('word/document.xml');
            $types = $zip->getFromName('[Content_Types].xml');
            if (! is_string($xml) || ! is_string($types)
                || ! str_contains($types, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml')) {
                $reject();
            }
            $document = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try {
                $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            if (! $loaded || $document->doctype !== null || $document->documentElement?->localName !== 'document'
                || ! in_array($document->documentElement?->namespaceURI, [
                    'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                    'http://purl.oclc.org/ooxml/wordprocessingml/main',
                ], true)) {
                $reject();
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Keep archive uploads on the office page that initiated them. The explicit
     * path is preferred; same-site office referrers cover older forms or
     * clients that omit the hidden return field.
     */
    private function officeReturnPath(Request $request, string $fallbackRoute): string
    {
        $back = trim((string) $request->input('redirect_to', ''));
        if (str_starts_with($back, '/office-desk')) {
            return $back;
        }

        $referrerPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
        if (is_string($referrerPath) && str_starts_with($referrerPath, '/office-desk')) {
            return $referrerPath;
        }

        return route($fallbackRoute);
    }

    /**
     * Demo FR attachment list (count drives sidebar badge).
     *
     * @return list<array{name: string, type: string}>
     */
    private function frAttachmentList(): array
    {
        return [
            ['name' => 'Financial Statement.pdf', 'type' => 'PDF'],
            ['name' => 'Cash Collection Summary.xlsx', 'type' => 'XLSX'],
            ['name' => 'Card Disbursement Log.pdf', 'type' => 'PDF'],
            ['name' => 'Receipt Bundle.zip', 'type' => 'ZIP'],
        ];
    }

    /**
     * Demo AR attachment list (count drives sidebar badge).
     *
     * @return list<array{name: string, type: string}>
     */
    private function arAttachmentList(): array
    {
        return [
            ['name' => 'Narrative Report.pdf', 'type' => 'PDF'],
            ['name' => 'Photo Documentation.zip', 'type' => 'ZIP'],
            ['name' => 'Attendance Sheets.pdf', 'type' => 'PDF'],
        ];
    }

    /**
     * Account balance highlights for FR overview.
     *
     * @param  array<string, mixed>  $budget
     * @return array{current_cash: int, total_cash_collection: int, total_card_disbursement: int}
     */
    private function accountBalanceData(array $budget): array
    {
        $collection = max((int) $budget['allocated'], 81000);
        $disbursement = max((int) $budget['used'], 25250);

        return [
            'current_cash' => max(0, $collection - $disbursement),
            'total_cash_collection' => $collection,
            'total_card_disbursement' => $disbursement,
        ];
    }

    /**
     * Demo budget utilization payload for the Student Org desk.
     *
     * @return array<string, mixed>
     */
    private function budgetUtilizationData(?array $departmentValues = null, ?string $organizationFilter = null): array
    {
        $organizationFilter = $this->organizationFilterForOffice($organizationFilter);
        $budgetItems = BudgetItem::query()
            ->when($departmentValues !== null, fn ($q) => $q->whereIn('college', $departmentValues ?: ['__no_matching_department__']))
            ->when(trim((string) $organizationFilter) !== '', fn ($q) => $q->where('organization_name', trim((string) $organizationFilter)))
            ->get();
        $dbAllocated = (int) $budgetItems->sum('allocated');
        $dbUsed = (int) $budgetItems->sum('utilized');

        $hasScopeFilter = $departmentValues !== null || trim((string) $organizationFilter) !== '';
        $allocated = $dbAllocated > 0 ? $dbAllocated : ($hasScopeFilter ? 0 : 81000);
        $used = $dbUsed > 0 ? $dbUsed : ($hasScopeFilter ? 0 : 25250);
        $remaining = max(0, $allocated - $used);
        $percent = $allocated > 0 ? (int) round(($used / $allocated) * 100) : 0;

        $activities = $hasScopeFilter ? [] : [
            [
                'title' => 'Innovation Fair Booth Series',
                'budget' => 22000,
                'spent' => 9800,
                'remaining' => 12200,
                'percent' => 45,
                'expenses' => [
                    [
                        'name' => 'Portable sound system rental',
                        'date' => 'Jul 12, 2026',
                        'qty' => 1,
                        'total' => 5800,
                        'receipt' => true,
                    ],
                    [
                        'name' => 'Booth backdrop & print materials',
                        'date' => 'Jul 10, 2026',
                        'qty' => 1,
                        'total' => 4000,
                        'receipt' => true,
                    ],
                ],
            ],
            [
                'title' => 'Volunteer Appreciation Day',
                'budget' => 12500,
                'spent' => 12500,
                'remaining' => 0,
                'percent' => 100,
                'expenses' => [
                    [
                        'name' => 'Certificates & tokens',
                        'date' => 'Mar 1, 2026',
                        'qty' => 40,
                        'total' => 7800,
                        'receipt' => true,
                    ],
                    [
                        'name' => 'Refreshments',
                        'date' => 'Mar 2, 2026',
                        'qty' => 1,
                        'total' => 4700,
                        'receipt' => true,
                    ],
                ],
            ],
        ];

        return [
            'allocated' => $allocated,
            'used' => $used,
            'remaining' => $remaining,
            'percent' => $percent,
            'approved_count' => count($activities),
            'covered_count' => count($activities),
            'activities' => $activities,
            'activity_options' => collect($activities)->map(fn (array $row): array => [
                'title' => $row['title'],
                'remaining' => $row['remaining'],
            ])->all(),
        ];
    }

    private function saveActivity(Request $request, ?InCampusActivitySubmission $submission = null): RedirectResponse
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'so', 403);
        if ($submission) {
            $this->assertOfficeOrganizationName($submission->organization_name ?: $submission->activity?->organization_name);
        }
        if ($submission && ! in_array($submission->activity?->workflow_status, ['created', 'returned'], true)) {
            return back()->withErrors(['activity' => 'This activity is already under review or approved. It must be returned to SO before editing.']);
        }
        $activityType = $request->input('activity_type', 'in_campus');
        $requirementSet = $activityType === 'local_off_campus'
            ? $this->localOffCampusRequirements()
            : $this->inCampusRequirements();
        $validated = $request->validate([
            'activity_type' => ['required', 'in:in_campus,local_off_campus'],
            'submission_action' => ['required', 'in:submit'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'approved_budget' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'rationale' => ['nullable', 'string', 'max:10000'],
            'objectives' => ['nullable', 'string', 'max:10000'],
            'sdg_goals' => ['nullable', 'array', 'max:17'],
            'sdg_goals.*' => ['string', Rule::in(array_map(fn ($n) => 'SDG '.$n, range(1, 17)))],
            'participants' => ['nullable', 'string', 'max:10000'],
            'safety_plan' => ['nullable', 'string', 'max:10000'],
            'plan_reference' => ['nullable', 'string', 'max:500'],
            'attachments' => ['nullable', 'array:'.implode(',', array_column($requirementSet, 'key'))],
            'attachments.*' => [
                'bail', 'file',
                function ($attribute, $file, $fail): void {
                    if ($file->getSize() === 0) {
                        $fail('This document is empty. Select a completed document.');
                    }
                },
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,png,jpg,jpeg', 'max:20480',
            ],
            'supporting_documents' => ['prohibited'],
        ], [
            'approved_budget.required' => 'Set the allocated budget before submitting the activity.',
            'approved_budget.integer' => 'The allocated budget must be a whole number.',
            'approved_budget.min' => 'The allocated budget must be greater than zero.',
        ]);
        $assignedOrganization = $this->assignedOrganizationName();
        if ($assignedOrganization !== null) {
            $validated['organization_name'] = $assignedOrganization;
        }
        if (Carbon::parse($validated['ends_at'] ?? $validated['starts_at'])->isPast()) {
            return back()->withInput()->withErrors(['ends_at' => 'You cannot submit an activity that has already ended. Correct the activity schedule first.']);
        }
        if (empty(trim($validated['organization_name'] ?? ''))) {
            return back()->withInput()->withErrors(['organization_name' => 'Select the organization responsible for this activity.']);
        }
        $files = $request->file('attachments', []);
        $storedPaths = [];
        try {
            DB::connection('mysql')->transaction(function () use ($request, $validated, $submission, $activityType, $requirementSet, $files, &$storedPaths): void {
                $activity = $submission ? OrgActivity::query()->lockForUpdate()->findOrFail($submission->org_activity_id) : new OrgActivity();
                if ($activity->exists && ! in_array($activity->workflow_status, ['created', 'returned'], true)) {
                    throw ValidationException::withMessages(['activity' => 'Activity is locked while under review or approved.']);
                }
                $submission = $submission
                    ? InCampusActivitySubmission::query()->lockForUpdate()->findOrFail($submission->id)
                    : new InCampusActivitySubmission();
                $attachments = $submission->attachments ?? [];
                $disk = Storage::disk('public');
                $missing = [];
                foreach ($requirementSet as $requirement) {
                    $key = $requirement['key'];
                    $savedPath = $attachments[$key]['path'] ?? null;
                    if (! empty($requirement['required_on_submit']) && ! isset($files[$key])
                        && (! filled($savedPath) || ! $disk->fileExists($savedPath) || $disk->size($savedPath) === 0)) {
                        $missing[] = $requirement['title'];
                    }
                }
                if ($missing) {
                    throw ValidationException::withMessages(['attachments' => 'Upload the required checklist items: '.implode(', ', $missing).'.']);
                }
                $from = $activity->workflow_status ?: 'created';
                $orgName = $validated['organization_name'];
                $college = StudentOrganization::query()->where('name', $orgName)->value('college');
                $activity->fill([
                    'title' => $validated['title'],
                    'description' => $validated['rationale'] ?? null,
                    'location' => $validated['location'],
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'] ?? null,
                    'approved_budget' => $validated['approved_budget'],
                    'status' => 'upcoming',
                    'organization_name' => $orgName,
                    'college' => $college ?? $activity->college,
                    'activity_scope' => $activityType,
                    'sdg_goals' => array_values($validated['sdg_goals'] ?? []),
                    'workflow_status' => 'oso_review',
                    'returned_to' => null,
                ])->save();
                if ($request->has('plan_reference')) {
                    $attachments['plan_reference'] = trim((string) $request->input('plan_reference'));
                }
                unset($attachments['conditions']);
                $newUploadKeys = [];
                $folder = $activityType === 'local_off_campus' ? 'off-campus-activities' : 'in-campus-activities';
                foreach ($files as $key => $file) {
                    $path = $file->store("{$folder}/{$activity->id}", 'public');
                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('The activity document could not be stored.');
                    }
                    $storedPaths[] = $path;
                    $newUploadKeys[] = (string) $key;
                    $attachments[$key] = [
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                        'uploaded_at' => now()->toIso8601String(),
                    ];
                }
                $submission->fill([
                    'org_activity_id' => $activity->id,
                    'status' => 'submitted',
                    'activity_type' => $activityType,
                    'organization_name' => $orgName,
                    'rationale' => $validated['rationale'] ?? null,
                    'objectives' => $validated['objectives'] ?? null,
                    'participants' => $validated['participants'] ?? null,
                    'safety_plan' => $validated['safety_plan'] ?? null,
                    'attachments' => $attachments,
                    'submitted_at' => now(),
                ])->save();
                app(OrgWorkflowService::class)->syncSubmission($activity);
                app(OrgWorkflowService::class)->event($activity, 'so', $from, 'oso_review', 'Activity package submitted.');
                $this->syncActivityComplianceDocs($activity, $submission, $requirementSet, $attachments, $newUploadKeys);
            });
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($storedPaths);
            throw $error;
        }
        $typeLabel = $activityType === 'local_off_campus' ? 'local off-campus' : 'in-campus';

        return redirect()->route('office.activities')->with('success', "Your {$typeLabel} activity was submitted for review.");
    }

    /**
     * Keep review rows synchronized with the submitted required documents.
     * A replacement upload returns that document to pending review.
     *
     * @param list<array<string, mixed>> $requirements
     * @param array<string, mixed> $attachments
     * @param list<string> $newUploadKeys
     */
    private function syncActivityComplianceDocs(
        OrgActivity $activity,
        InCampusActivitySubmission $submission,
        array $requirements,
        array $attachments,
        array $newUploadKeys,
    ): void {
        foreach ($requirements as $requirement) {
            $key = (string) ($requirement['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $hasFile = is_array($attachments[$key] ?? null) && ! empty($attachments[$key]['path']);
            $shouldSync = $hasFile || ! empty($requirement['required_on_submit']);

            if (! $shouldSync) {
                continue;
            }

            $compliance = ActivityComplianceDoc::query()->firstOrNew([
                'org_activity_id' => $activity->id,
                'doc_key' => $key,
            ]);
            $isNew = ! $compliance->exists;
            $compliance->submission_id = $submission->id;
            $compliance->title = $requirement['title'] ?? str($key)->replace('_', ' ')->title();
            if ($isNew || in_array($key, $newUploadKeys, true)) {
                $compliance->status = 'pending';
                $compliance->returned_to = null;
                if (in_array($key, $newUploadKeys, true)) {
                    $compliance->remarks = null;
                }
            } else {
                $compliance->status = $compliance->status ?: 'pending';
            }
            $compliance->save();
        }
    }

    /**
     * Exact official activity document checklists for initial filing.
     *
     * @return list<array{key: string, title: string, description: string, group: string, phase: string, required_on_submit: bool, tokens: list<string>, source_file: string}>
     */
    private function inCampusRequirements(): array
    {
        return app(\App\Services\ActivityRequirements::class)->inCampusRequirements();
    }

    /**
     * @return list<array{key: string, title: string, description: string, group: string, phase: string, required_on_submit: bool, tokens: list<string>, source_file: string}>
     */
    private function localOffCampusRequirements(): array
    {
        return app(\App\Services\ActivityRequirements::class)->localOffCampusRequirements();
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 1).' MB';
    }

    /**
     * @return array{title: string, role: string}
     */
    private function brandFor(string $role): array
    {
        return match ($role) {
            'oso' => ['title' => 'Office of Student Organization', 'role' => 'OSO Officer'],
            'sdo' => ['title' => 'Sustainable Development Office', 'role' => 'SDO Officer'],
            'ovcaa' => ['title' => 'OVCAA Desk', 'role' => 'OVCAA Reviewer'],
            'oc' => ['title' => 'Office of the Chancellor', 'role' => 'OC Final Approval Officer'],
            default => ['title' => 'Student Organization', 'role' => 'Student Org Representative'],
        };
    }

    private function activityDocumentLockMessage(string $role): string
    {
        return match ($role) {
            'oso' => 'Submitted documents are locked until the Student Organization submits this activity to OSO Review.',
            'sdo' => 'Submitted documents are locked until OSO endorses this activity to SDO Review.',
            'ovcaa' => 'Submitted documents are locked until SDO completes its document check and endorses this activity to OVCAA.',
            'oc' => 'Submitted documents are locked until OVCAA endorses the complete package to the Office of the Chancellor.',
            default => 'Submitted documents are not available at this workflow stage.',
        };
    }

    /**
     * Demo workflow rows matching the Student Org module screens.
     *
     * @return list<array<string, mixed>>
     */
    private function pipelineActivities(): array
    {
        $workflow = app(OrgWorkflowService::class);
        $officeRole = Auth::guard('office')->user()?->office_role ?? '';
        $db = $this->constrainToOfficeOrganization(OrgActivity::query())->orderByDesc('starts_at')->get();

        if ($db->isNotEmpty()) {
            return $db
                ->filter(fn (OrgActivity $activity): bool => $workflow->canViewActivityDetails(
                    $officeRole,
                    $activity->workflow_status ?: 'created',
                ))
                ->take(12)
                ->map(function (OrgActivity $activity) use ($workflow, $officeRole): array {
                $statusKey = $activity->workflow_status ?: 'created';
                $canViewDocuments = $workflow->canViewActivityDocuments($officeRole, $statusKey);

                return [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'status' => $workflow->label($statusKey),
                    'status_key' => $statusKey,
                    'stage' => max(1, array_search($statusKey === 'returned' ? 'created' : $statusKey, OrgWorkflowService::FLOW, true) + 1 ?: 1),
                    'stages' => count(OrgWorkflowService::FLOW),
                    'date' => optional($activity->starts_at)->format('M j, Y') ?? 'TBA',
                    'budget' => (int) ($activity->approved_budget ?: 0),
                    'location' => $activity->location ?: 'TBA',
                    'college' => $activity->college,
                    'organization' => $activity->organization_name,
                    'activity_scope' => $activity->activity_scope,
                    'upcoming_at' => optional($activity->starts_at)?->format('Y-m-d H:i:s'),
                    'docs' => $canViewDocuments
                        ? ActivityComplianceDoc::query()->where('org_activity_id', $activity->id)->pluck('title')->all()
                        : [],
                    'note' => $activity->returned_to ? 'Returned to '.$activity->returned_to : $activity->description,
                    'returned_to' => $activity->returned_to,
                ];
                })
                ->values()
                ->all();
        }

        $demo = [
            [
                'title' => 'Innovation Fair Booth Series',
                'status' => 'OC Approved',
                'status_key' => 'oc_approved',
                'stage' => 7,
                'stages' => 7,
                'date' => 'Jul 4, 2026',
                'budget' => 15000,
                'location' => 'Gymnasium',
                'upcoming_at' => '2026-07-04 09:00:00',
                'activity_scope' => 'in_campus',
                'docs' => ['Activity Proposal.pdf', 'Budget Breakdown.xlsx'],
                'note' => 'Booth setup open for student orientation.',
            ],
            [
                'title' => 'Leadership Summit 2026',
                'status' => 'OSO Review',
                'status_key' => 'oso_review',
                'stage' => 3,
                'stages' => 7,
                'date' => 'Sep 20, 2026',
                'budget' => 75000,
                'location' => 'Taal Building',
                'upcoming_at' => '2026-09-20 06:00:00',
                'activity_scope' => 'local_off_campus',
                'docs' => ['Concept Note.pdf'],
                'note' => null,
            ],
            [
                'title' => 'Campus Wellness Week',
                'status' => 'SDO Review',
                'status_key' => 'sdo_review',
                'stage' => 4,
                'stages' => 7,
                'date' => 'Sep 8, 2026',
                'budget' => 42500,
                'location' => 'Gymnasium',
                'upcoming_at' => '2026-09-08 10:00:00',
                'activity_scope' => 'in_campus',
                'docs' => ['Wellness Plan.pdf'],
                'note' => null,
            ],
            [
                'title' => 'Student Media Workshop',
                'status' => 'Returned for Revision',
                'status_key' => 'returned',
                'stage' => 1,
                'stages' => 7,
                'date' => 'Oct 3, 2026',
                'budget' => 9800,
                'location' => 'Taal Building',
                'upcoming_at' => '2026-10-03 13:00:00',
                'activity_scope' => 'local_off_campus',
                'docs' => ['Workshop Outline.pdf'],
                'note' => 'Returned to so for revision.',
                'returned_to' => 'so',
            ],
        ];

        return collect($demo)
            ->filter(fn (array $activity): bool => $workflow->canViewActivityDetails(
                $officeRole,
                (string) ($activity['status_key'] ?? 'created'),
            ))
            ->values()
            ->all();
    }

    private function activityAcademicYearOptions(?string $organization = null): array
    {
        $organization = $this->organizationFilterForOffice($organization);
        $years = OrgActivity::query()
            ->when($organization !== '', fn ($q) => $q->where('organization_name', $organization))
            ->whereNotNull('starts_at')
            ->pluck('starts_at')
            ->map(function ($date): ?string {
                try {
                    return $this->academicPeriod($date instanceof Carbon ? $date : Carbon::parse($date))['academic_year'];
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter();

        return $years
            ->push($this->academicPeriod(now())['academic_year'])
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function orgActivitiesList(?string $organization = null, ?string $academicYear = null): array
    {
        $workflow = app(OrgWorkflowService::class);
        $officeRole = Auth::guard('office')->user()?->office_role ?? '';
        $organization = $this->organizationFilterForOffice($organization);
        $academicYear = trim((string) $academicYear);
        $yearBounds = null;
        if (preg_match('/^(\d{4})-(\d{4})$/', $academicYear, $matches)) {
            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];
            if ($endYear === $startYear + 1) {
                $yearBounds = [
                    Carbon::create($startYear, 8, 1)->startOfDay(),
                    Carbon::create($endYear, 7, 31)->endOfDay(),
                ];
            }
        }
        $db = OrgActivity::query()
            ->when($organization !== '', fn ($q) => $q->where('organization_name', $organization))
            ->when($yearBounds !== null, fn ($q) => $q->whereBetween('starts_at', $yearBounds))
            ->orderByDesc('starts_at')
            ->get();

        if ($db->isEmpty()) {
            if ($organization !== '' || $yearBounds !== null) {
                return [];
            }

            $demo = collect($this->demoOrgActivitiesList())
                ->filter(fn (array $activity): bool => $workflow->canViewActivityDetails(
                    $officeRole,
                    (string) ($activity['status_key'] ?? 'created'),
                ))
                ->map(function (array $activity) use ($workflow, $officeRole): array {
                    $statusKey = (string) ($activity['status_key'] ?? 'created');
                    $canViewDocuments = $workflow->canViewActivityDocuments($officeRole, $statusKey);
                    $reviewDeskRole = in_array($officeRole, ['oso', 'sdo', 'ovcaa', 'oc'], true);

                    $activity['documents'] = $canViewDocuments ? ($activity['documents'] ?? []) : [];
                    $activity['documents_locked'] = $reviewDeskRole && ! $canViewDocuments;
                    $activity['documents_lock_message'] = $activity['documents_locked']
                        ? $this->activityDocumentLockMessage($officeRole)
                        : null;

                    return $activity;
                });

            return $organization !== ''
                ? $demo->filter(fn (array $activity): bool => ($activity['organization'] ?? '') === $organization)->values()->all()
                : $demo->all();
        }

        // Keep every submission version, newest first. A compliance row can
        // still point at an older submission after an edit, so selecting only
        // the newest row can make a real uploaded file disappear from OSO.
        $submissionFiles = InCampusActivitySubmission::query()
            ->latest('id')
            ->get()
            ->groupBy('org_activity_id');
        $rsvpsByActivity = ActivityRegistration::query()->orderByDesc('created_at')->get()->groupBy('org_activity_id');
        $organizationAliases = StudentOrganization::query()->pluck('short_name', 'name')->all();

        return $db->map(function (OrgActivity $activity) use ($workflow, $officeRole, $submissionFiles, $rsvpsByActivity, $organizationAliases): array {
            $statusKey = $activity->workflow_status ?: 'created';
            $canViewDocuments = $workflow->canViewActivityDocuments($officeRole, $statusKey);
            $reviewDeskRole = in_array($officeRole, ['oso', 'sdo', 'ovcaa', 'oc'], true);
            $filter = match (true) {
                $statusKey === 'oc_approved' => 'approved',
                $statusKey === 'returned' => 'returned',
                in_array($statusKey, ['oso_review', 'sdo_review', 'ovcaa_review', 'oc_review', 'college_review'], true) => 'for_approval',
                default => 'in_review',
            };
            $badge = match ($filter) {
                'approved' => 'purple',
                'returned' => 'red',
                'for_approval' => 'yellow',
                default => 'blue',
            };

            $activitySubmissions = $submissionFiles->get($activity->id, collect());
            $submission = $activitySubmissions->first();
            $requirementTitles = collect($activity->activity_scope === 'local_off_campus'
                ? $this->localOffCampusRequirements()
                : $this->inCampusRequirements())->keyBy('key');
            $complianceDocs = ActivityComplianceDoc::query()
                ->where('org_activity_id', $activity->id)
                ->latest('id')
                ->get();

            // A compliance row is only a review status. The attachment JSON
            // plus the public disk are the source of truth for whether a file
            // can actually be previewed. Resolve the row's submission first,
            // then fall back through older versions for legacy edits.
            $hasStoredFile = static function (mixed $file): bool {
                return is_array($file)
                    && ! empty($file['path'])
                    && Storage::disk('public')->exists((string) $file['path']);
            };
            $resolveAttachment = static function (ActivityComplianceDoc $doc) use ($activitySubmissions, $hasStoredFile): array {
                $candidates = collect();
                if ($doc->submission_id) {
                    $candidates->push($activitySubmissions->firstWhere('id', $doc->submission_id));
                }
                $candidates = $candidates
                    ->merge($activitySubmissions)
                    ->filter()
                    ->unique('id');

                foreach ($candidates as $candidate) {
                    $candidateAttachments = is_array($candidate->attachments) ? $candidate->attachments : [];
                    $file = $candidateAttachments[$doc->doc_key] ?? null;
                    if ($hasStoredFile($file)) {
                        return [$candidate, $file];
                    }
                }

                return [null, null];
            };
            $documentedKeys = [];
            $docs = $complianceDocs
                ->map(function (ActivityComplianceDoc $doc) use ($resolveAttachment, $hasStoredFile, &$documentedKeys): ?array {
                    [$fileSubmission, $file] = $resolveAttachment($doc);
                    if (! $fileSubmission || ! $hasStoredFile($file)) {
                        // Do not present seeded/orphan status-only rows as
                        // submitted documents. There is no file for OSO to
                        // review until the SO uploads one.
                        return null;
                    }

                    $documentedKeys[] = (string) $doc->doc_key;
                    $style = match ($doc->status) {
                        'approved' => 'green',
                        'returned' => 'red',
                        default => 'yellow',
                    };
                    $uploadedOn = OrgTimeService::format($doc->updated_at) ?: 'Recent';
                    if (is_array($file) && ! empty($file['uploaded_at'])) {
                        try {
                            $uploadedOn = OrgTimeService::format($file['uploaded_at']) ?: $uploadedOn;
                        } catch (\Throwable) {
                            // Keep the compliance timestamp when legacy metadata is malformed.
                        }
                    }

                    return [
                        'id' => $doc->id,
                         'name' => $doc->title,
                         'type' => strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) ?: 'file',
                         'status' => ucfirst($doc->status ?: 'pending'),
                        'status_style' => $style,
                        'uploaded_on' => $uploadedOn,
                        'note' => $doc->remarks,
                        'url' => route('office.activities.attachments.file', [
                            'submission' => $fileSubmission->id,
                            'key' => $doc->doc_key,
                            'preview' => 1,
                        ]),
                        'download_url' => route('office.activities.attachments.file', [
                            'submission' => $fileSubmission->id,
                            'key' => $doc->doc_key,
                            'download' => 1,
                        ]),
                    ];
                })
                ->filter()
                ->values()
                ->all();

            // Preserve review access to historical uploads without a compliance
            // row. The attachment JSON remains the source of truth for the file.
            $projectedKeys = [];
            foreach ($activitySubmissions as $sourceSubmission) {
                $sourceAttachments = is_array($sourceSubmission->attachments) ? $sourceSubmission->attachments : [];
                foreach ($sourceAttachments as $key => $file) {
                    $key = (string) $key;
                    if ($key === 'conditions'
                        || in_array($key, $documentedKeys, true)
                        || in_array($key, $projectedKeys, true)
                        || ! $hasStoredFile($file)) {
                        continue;
                    }

                    $projectedKeys[] = $key;
                    $name = $file['name'] ?? basename((string) $file['path']);
                    $uploadedOn = 'Recent';
                    if (! empty($file['uploaded_at'])) {
                        try {
                            $uploadedOn = OrgTimeService::format($file['uploaded_at']) ?: $uploadedOn;
                        } catch (\Throwable) {
                            // Keep the neutral label for legacy metadata.
                        }
                    }

                    $docs[] = [
                        'id' => null,
                        'name' => data_get($requirementTitles->get($key), 'title', $name),
                        'type' => strtolower(pathinfo((string) $name, PATHINFO_EXTENSION)) ?: 'file',
                        'status' => 'Pending',
                        'status_style' => 'yellow',
                        'uploaded_on' => $uploadedOn,
                        'note' => 'Uploaded by the Student Organization; pending OSO review.',
                        'url' => route('office.activities.attachments.file', [
                            'submission' => $sourceSubmission->id,
                            'key' => $key,
                            'preview' => 1,
                        ]),
                        'download_url' => route('office.activities.attachments.file', [
                            'submission' => $sourceSubmission->id,
                            'key' => $key,
                            'download' => 1,
                        ]),
                    ];
                }
            }

            $academicYear = $this->academicPeriod($activity->starts_at)['academic_year'];

            $planReference = is_array($submission?->attachments)
                ? ($submission->attachments['plan_reference'] ?? null)
                : null;
            $isPlanVerified = (is_array($submission?->attachments) && !empty($submission->attachments['plan_of_activities_verified']))
                || in_array($statusKey, ['sdo_review', 'ovcaa_review', 'oc_review', 'oc_approved', 'completed'], true);

            $orgRenewal = OrgRenewalSubmission::query()
                ->where('organization_name', $activity->organization_name)
                ->latest('id')
                ->first();
            $orgPlanDoc = $orgRenewal?->documents()->where('doc_key', 'plan_of_activities')->first();
            $planDocumentUrl = ($orgPlanDoc && Storage::disk('public')->exists($orgPlanDoc->file_path))
                ? Storage::disk('public')->url($orgPlanDoc->file_path)
                : '/templates/renewal/Attachment I_ Plan of Activities.pdf';
            $planDocumentSource = $orgPlanDoc ? 'Uploaded Organization Renewal Dossier' : 'Official BatStateU Attachment I Template';

            return [
                'id' => $activity->id,
                'slug' => (string) $activity->id,
                'submission_id' => $submission?->id,
                'title' => $activity->title,
                'status' => $workflow->label($statusKey),
                'status_key' => $statusKey,
                'badge_style' => $badge,
                'filter_category' => $filter,
                'date' => optional($activity->starts_at)->format('M j, Y') ?? 'TBA',
                'academic_year' => $academicYear,
                'location' => $activity->location ?: 'TBA',
                'timestamp_note' => optional($activity->updated_at)->format('M j, Y g:i A') ?? 'Synced from database',
                'activity_type' => ($activity->activity_scope === 'local_off_campus') ? 'Off-Campus Activity' : 'In-Campus Activity',
                'start_time' => optional($activity->starts_at)->format('F j, Y g:i A') ?? 'TBA',
                'end_time' => optional($activity->ends_at)->format('F j, Y g:i A') ?? 'TBA',
                'budget' => (float) $activity->approved_budget,
                'organization' => $activity->organization_name ?: 'Student Organization',
                'organization_alias' => $organizationAliases[$activity->organization_name] ?? ($activity->organization_name ?: 'Student Organization'),
                'college' => $activity->college,
                'program' => $activity->program,
                'rationale' => $submission?->rationale ?: ($activity->description ?: 'No rationale recorded.'),
                'participants_plan' => $submission?->participants,
                'safety_plan' => $submission?->safety_plan,
                'plan_reference' => $planReference,
                'plan_of_activities_verified' => $isPlanVerified,
                'plan_document_url' => $planDocumentUrl,
                'plan_document_source' => $planDocumentSource,
                'objectives' => array_values(array_filter([
                    $submission?->objectives,
                    $activity->college ? 'College: '.$activity->college : null,
                    ! empty($activity->sdg_goals) ? 'SDG: '.implode(', ', $activity->sdg_goals) : null,
                    ! empty($activity->core_values) ? 'Core values: '.implode(', ', $activity->core_values) : null,
                ])) ?: ['Complete compliance documents and secure office endorsements.'],
            'documents' => $canViewDocuments ? $docs : [],
            'documents_locked' => $reviewDeskRole && ! $canViewDocuments,
            'documents_lock_message' => $reviewDeskRole && ! $canViewDocuments
                ? $this->activityDocumentLockMessage($officeRole)
                : null,
            'sdg_goals' => $activity->sdg_goals ?: [],
            'sdo_review_notes' => $activity->sdo_review_notes,
            'review_events' => DB::connection('mysql')->table('activity_workflow_events')->where('org_activity_id', $activity->id)->orderBy('id')->get()->toArray(),
            'workflow_status' => $statusKey,
            'returned_to' => $activity->returned_to,
            'rsvp_count' => ($rsvpsByActivity[$activity->id] ?? collect())->count(),
            'rsvp_roster' => ($rsvpsByActivity[$activity->id] ?? collect())->take(20)->map(fn ($r) => [
                'name' => $r->full_name ?: ($r->sr_code ?: 'Student'),
                'sr_code' => $r->sr_code,
                'year_level' => $r->year_level,
                'date' => optional($r->created_at)->format('M j, Y g:i A') ?? '',
            ])->values()->all(),
            'created_at' => $activity->created_at,
            'submitted_at' => $submission?->submitted_at ?? $submission?->created_at ?? $activity->created_at,
            'approved_at' => $activity->approved_at,
            ];
        })
        ->filter(fn (array $activity): bool => $workflow->canViewActivityDetails(
            $officeRole,
            (string) ($activity['status_key'] ?? 'created'),
        ))
        ->values()
        ->all();
}

    /**
     * @return list<array<string, mixed>>
     */
    private function demoOrgActivitiesList(): array
    {
        return [[
            'id' => null,
            'slug' => 'demo-activity',
            'title' => 'Demo Activity (seed PreOralDemoSeeder)',
            'status' => 'Created',
            'status_key' => 'created',
            'badge_style' => 'blue',
            'filter_category' => 'in_review',
            'date' => 'TBA',
            'location' => 'TBA',
            'timestamp_note' => 'No DB activities found',
            'activity_type' => 'In-Campus Activity',
            'start_time' => 'TBA',
            'end_time' => 'TBA',
            'organization' => 'Student Organization',
            'rationale' => 'Run php artisan db:seed --class=PreOralDemoSeeder',
            'objectives' => ['Seed demo data to populate activities.'],
            'documents' => [],
        ]];
    }
    public function advanceActivity(OrgActivity $activity): RedirectResponse
    {
        $role = Auth::guard('office')->user()?->office_role ?? 'so';
        abort_unless(in_array($role, ['oso', 'sdo', 'ovcaa', 'oc'], true), 403);
        $workflow = app(OrgWorkflowService::class);
        if (!$workflow->canAct($role, $activity->workflow_status ?: 'created')) {
            return back()->withErrors(['workflow' => 'This activity is no longer awaiting review at this desk.']);
        }
        $review = request()->validate([
            'documents_reviewed' => [in_array($role, ['oso', 'sdo'], true) ? 'accepted' : 'nullable'],
            'plan_of_activities_verified' => ['nullable'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $workflow = app(OrgWorkflowService::class);

        if (!$workflow->canAct($role, $activity->workflow_status ?: 'created')) {
            return back()->withErrors([
                'workflow' => 'This activity has already moved to the next workflow desk and cannot be endorsed from this desk again.',
            ]);
        }

        if ($role === 'oso') {
            $notes = trim((string) ($review['review_notes'] ?? ''));
            $verifiedTag = '[Verified against Approved Plan of Activities (Attachment I)]';
            $review['review_notes'] = $notes !== '' ? "{$verifiedTag} {$notes}" : $verifiedTag;

            $submission = InCampusActivitySubmission::where('org_activity_id', $activity->id)->latest('id')->first();
            if ($submission) {
                $attachments = is_array($submission->attachments) ? $submission->attachments : [];
                $attachments['plan_of_activities_verified'] = true;
                $attachments['plan_of_activities_verified_at'] = now()->toIso8601String();
                $submission->update(['attachments' => $attachments]);
            }
        }

        try {
            $workflow->advance($activity, $role, $review);
            $workflow->syncSubmission($activity);
        } catch (\Throwable $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Activity advanced to '.$workflow->label($activity->workflow_status).'.');
    }

    public function returnActivity(Request $request, OrgActivity $activity): RedirectResponse
    {
        $validated = $request->validate([
            'returned_to' => ['required', 'in:so'],
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        $workflow = app(OrgWorkflowService::class);
        $workflow->returnForRevision($activity, $validated['returned_to'], $validated['remarks'] ?? null);
        $workflow->syncSubmission($activity);

        return back()->with('success', 'Activity returned to '.$validated['returned_to'].' for revision.');
    }

    public function updateComplianceDoc(Request $request, OrgActivity $activity, ActivityComplianceDoc $doc): RedirectResponse
    {
        abort_unless(Auth::guard('office')->user()?->office_role === 'oso' && $activity->workflow_status === 'oso_review', 403);
        abort_unless((int) $doc->org_activity_id === (int) $activity->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,returned'],
            'returned_to' => ['nullable', 'in:so,college_reviewer,oso,sdo'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $doc->update([
            'status' => $validated['status'],
            'returned_to' => $validated['status'] === 'returned' ? ($validated['returned_to'] ?? 'so') : null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', 'Compliance document status updated.');
    }



    public function storeReportDocument(Request $request, string $reportType): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'so', 403);

        $reportType = strtolower($reportType);
        abort_unless(in_array($reportType, $this->semesterReportTypes(), true), 404);

        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'semester' => ['required', Rule::in(['1st Semester', '2nd Semester', 'Midyear'])],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'name' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'file', $reportType === 'ar' ? 'mimes:pdf,doc,docx' : 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,png,jpg,jpeg', 'max:20480'],
        ]);
        $this->assertOfficeOrganizationName($validated['organization_name']);

        $period = [
            'organization' => $validated['organization_name'],
            'semester' => $validated['semester'], 'academic_year' => $validated['academic_year'],
        ];
        $file = $request->file('document');
        $financialSummary = null;
        if ($reportType === 'fr' && strtolower($file->getClientOriginalExtension()) === 'xlsx') {
            try {
                $financialSummary = app(FinancialWorkbookService::class)->summarize((string) $file->getRealPath());
            } catch (\Throwable) {
                $financialSummary = null;
            }
        }
        $path = null;
        try {
            DB::connection('mysql')->transaction(function () use ($validated, $reportType, $file, $financialSummary, $office, &$path): void {
                StudentOrganization::on('mysql')->where('name', $validated['organization_name'])->lockForUpdate()->first();
                $statuses = OrgReportStatus::query()->where('report_type', $reportType)
                    ->where('organization_name', $validated['organization_name'])
                    ->where('semester', $validated['semester'])->where('academic_year', $validated['academic_year'])
                    ->orderBy('id')->lockForUpdate()->get();
                if ($this->semesterReports()->isLocked($statuses)) {
                    throw new SemesterReportRejected(SemesterReportRejected::LOCKED);
                }
                $status = $statuses->last() ?? $this->semesterReports()->ensure(
                    $reportType, $validated['organization_name'], $validated['semester'], $validated['academic_year']
                );
                $folder = 'semester-reports/'.$reportType.'/'.Str::slug($validated['organization_name']).'/'.str_replace('-', '_', $validated['academic_year']).'/'.Str::slug($validated['semester']);
                $path = $file->store($folder, 'public');
                if (! $path) {
                    throw new \RuntimeException('The report file could not be stored.');
                }
                OrgReportDocument::query()->create([
                    'org_report_status_id' => $status->id, 'report_type' => $reportType,
                    'organization_name' => $validated['organization_name'],
                    'semester' => $validated['semester'], 'academic_year' => $validated['academic_year'],
                    'name' => ($validated['name'] ?? null) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'original_name' => $file->getClientOriginalName(), 'file_path' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'file_size' => $file->getSize(), 'financial_summary' => $financialSummary, 'uploaded_by' => $office->name,
                ]);
            });
        } catch (SemesterReportRejected $rejected) {
            return $this->reportRedirect($reportType, $period)->withErrors([
                'report' => 'This '.strtoupper($reportType).' is under review or has a final decision. Only a report returned for revision can be changed.',
            ]);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }
        return $this->reportRedirect($reportType, $period)
            ->with('success', strtoupper($reportType).' document saved. Submit it when it is ready for OSO review.');
    }

    public function submitSemesterReport(Request $request, string $reportType): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'so', 403);
        abort_unless(in_array($reportType, $this->semesterReportTypes(), true), 404);
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'semester' => ['required', Rule::in(['1st Semester', '2nd Semester', 'Midyear'])],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->assertOfficeOrganizationName($validated['organization_name']);
        $period = [
            'organization' => $validated['organization_name'],
            'semester' => $validated['semester'], 'academic_year' => $validated['academic_year'],
        ];
        try {
            $this->semesterReports()->submitReport(
                $reportType, $validated['organization_name'], $validated['semester'], $validated['academic_year'],
                $validated['notes'] ?? null,
                function (OrgReportStatus $status, OrgReportDocument $document) use ($office, $reportType): void {
                    if ($reportType !== 'fr') {
                        return;
                    }
                    $gate = app(FinancialReportPreviewGate::class);
                    if ((! $document->isWorkbook() && ! $gate->isInlineViewable($document))
                        || ! $gate->wasViewed($office, $document)) {
                        throw new SemesterReportRejected(SemesterReportRejected::GUARD, 'View the latest Financial Report file for this period before submitting it to OSO.');
                    }
                },
            );
        } catch (SemesterReportRejected $rejected) {
            $message = match ($rejected->reason) {
                SemesterReportRejected::LOCKED => strtoupper($reportType).' is already under review or has a final decision. Only a report returned for revision can be changed; rejected reports cannot be resubmitted.',
                SemesterReportRejected::MISSING => 'Save a readable current '.strtoupper($reportType).' document before submitting it to OSO.',
                default => $rejected->getMessage(),
            };
            return $this->reportRedirect($reportType, $period)->withInput()->withErrors(['report' => $message]);
        }
        return $this->reportRedirect($reportType, $period)
            ->with('success', strtoupper($reportType).' submitted to OSO for review.');
    }

    public function reviewSemesterReport(Request $request, string $reportType): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'oso', 403);
        abort_unless(in_array($reportType, $this->semesterReportTypes(), true), 404);
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'semester' => ['required', Rule::in(['1st Semester', '2nd Semester', 'Midyear'])],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'decision' => ['required', Rule::in(['accept', 'return', 'reject'])],
            'notes' => ['required_if:decision,return,reject', 'nullable', 'string', 'max:1000'],
        ]);
        $period = [
            'organization' => $validated['organization_name'], 'semester' => $validated['semester'],
            'academic_year' => $validated['academic_year'], 'tab' => $reportType,
        ];
        try {
            DB::connection('mysql')->transaction(function () use ($validated, $office, $reportType): void {
                StudentOrganization::on('mysql')->where('name', $validated['organization_name'])->lockForUpdate()->first();
                $statuses = OrgReportStatus::query()->where('report_type', $reportType)
                    ->where('organization_name', $validated['organization_name'])
                    ->where('semester', $validated['semester'])->where('academic_year', $validated['academic_year'])
                    ->orderBy('id')->lockForUpdate()->get();
                $status = $statuses->last();
                if (! $status || $status->status !== 'oso_review'
                    || $this->semesterReports()->isLocked($statuses->filter(fn (OrgReportStatus $row): bool => $row->id !== $status->id))) {
                    throw new SemesterReportRejected(SemesterReportRejected::GUARD, 'Only the current '.strtoupper($reportType).' waiting for OSO review can be decided.');
                }
                if ($validated['decision'] !== 'accept') {
                    $status->update([
                        'status' => $validated['decision'] === 'return' ? 'returned' : 'rejected',
                        'returned_to' => $validated['decision'] === 'return' ? 'so' : null,
                        'notes' => $validated['notes'], 'reviewed_at' => now(), 'reviewed_by' => $office->id,
                    ]);
                    return;
                }
                $document = OrgReportDocument::query()->where('org_report_status_id', $status->id)
                    ->where('report_type', $reportType)->where('organization_name', $status->organization_name)
                    ->where('semester', $status->semester)->where('academic_year', $status->academic_year)
                    ->latest('id')->lockForUpdate()->first();
                if (! $document || ! $document->hasStoredFile() || ! $status->submitted_at
                    || $document->created_at->gt($status->submitted_at)) {
                    throw new SemesterReportRejected(SemesterReportRejected::MISSING, 'The submitted '.strtoupper($reportType).' has no readable current file to verify and archive.');
                }
                $folder = ArchiveFolder::query()->firstOrCreate([
                    'name' => $status->organization_name.' — '.$status->academic_year.' '.$status->semester.' '.strtoupper($reportType),
                    'organization_name' => $status->organization_name, 'semester' => $status->semester,
                ], ['color' => 'green']);
                ArchiveDocument::query()->firstOrCreate([
                    'archive_folder_id' => $folder->id, 'file_path' => $document->file_path,
                ], [
                    'name' => $document->name, 'original_name' => $document->original_name,
                    'mime_type' => $document->mime_type, 'file_size' => $document->file_size,
                    'uploaded_by' => $document->uploaded_by ?: $office->name,
                ]);
                $status->update([
                    'status' => 'archived', 'returned_to' => null,
                    'notes' => $validated['notes'] ?? 'Verified by OSO and archived.',
                    'reviewed_at' => now(), 'reviewed_by' => $office->id,
                    'archived_at' => now(), 'archive_folder_id' => $folder->id,
                ]);
            });
        } catch (SemesterReportRejected $rejected) {
            return redirect()->route('office.reports.index', $period)->withErrors(['report' => $rejected->getMessage()]);
        }
        $decision = match ($validated['decision']) {
            'return' => 'returned to SO for revision', 'reject' => 'rejected',
            default => 'verified by OSO and archived',
        };
        return redirect()->route('office.reports.index', $period)
            ->with('success', strtoupper($reportType).' '.$decision.'.');
    }

    public function viewReportDocument(Request $request, OrgReportDocument $document): Response|BinaryFileResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && in_array($office->office_role, ['so', 'oso'], true), 403);
        if ($office->office_role === 'so') {
            $this->assertOfficeOrganizationName($document->organization_name);
        }
        if ($office->office_role === 'oso') {
            $this->authorizePublishedReportDocument($document);
        }

        abort_unless($document->hasStoredFile(), 404);
        $path = Storage::disk('public')->path($document->file_path);
        $nativeReport = $document->accomplishment_summary !== null
            ? app(\App\Http\Controllers\AccomplishmentReportController::class)
            : null;
        $nativeReport?->authorizeDocument($document);

        if ($office->office_role === 'oso') {
            $this->markReportOpened($document, $office);
        }

        if ($request->boolean('download')) {
            return response()->download($path, $document->original_name);
        }
        if ($nativeReport !== null) {
            return response($nativeReport->document($document));
        }


        $response = response()->file($path, ['Content-Type' => $document->mime_type]);
        $previews = app(FinancialReportPreviewGate::class);
        if ($office->office_role === 'so'
            && $document->report_type === 'fr'
            && ! $document->isWorkbook()
            && $previews->isInlineViewable($document)) {
            $previews->mark($office, $document);
        }

        return $response;
    }

    private function authorizePublishedReportDocument(OrgReportDocument $document): OrgReportStatus
    {
        $status = $document->reportStatus;
        abort_unless($status && $status->report_type === $document->report_type
            && $status->organization_name === $document->organization_name
            && $status->semester === $document->semester && $status->academic_year === $document->academic_year
            && in_array($status->status, SemesterReportService::VIEWABLE_STATUSES, true), 403);
        $latestId = OrgReportDocument::query()->where('org_report_status_id', $status->id)
            ->where('report_type', $document->report_type)->where('organization_name', $document->organization_name)
            ->where('semester', $document->semester)->where('academic_year', $document->academic_year)
            ->latest('id')->value('id');
        $historicalArchive = in_array($status->status, ['verified', 'archived'], true) && $status->archive_folder_id
            && ArchiveDocument::query()->where('archive_folder_id', $status->archive_folder_id)
                ->where('file_path', $document->file_path)->exists();
        abort_unless((int) $latestId === $document->id || $historicalArchive, 403);
        abort_unless($status->submitted_at ? $document->created_at->lte($status->submitted_at)
            : in_array($status->status, ['verified', 'archived'], true), 403);
        return $status;
    }

    private function markReportOpened(OrgReportDocument $document, OfficeUser $office): void
    {
        OrgReportStatus::query()->whereKey($document->org_report_status_id)
            ->where('report_type', $document->report_type)->where('status', 'oso_review')
            ->whereNull('opened_at')->update(['opened_at' => now(), 'opened_by' => $office->id]);
    }

    public function previewReportWorkbook(OrgReportDocument $document): JsonResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'oso', 403);
        $this->authorizePublishedReportDocument($document);
        abort_unless($document->report_type === 'fr' && $document->isWorkbook(), 422);
        abort_unless($document->hasStoredFile(), 404);
        try {
            $preview = app(FinancialWorkbookService::class)->preview(
                Storage::disk('public')->path($document->file_path),
                is_array($document->financial_summary) ? $document->financial_summary : null,
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'The workbook could not be read. Download the original file instead.'], 422);
        }
        $this->markReportOpened($document, $office);
        return response()->json([
            'title' => $document->name, 'original_name' => $document->original_name,
            'academic_year' => $document->academic_year, 'semester' => $document->semester,
            'sheets' => $preview['sheets'], 'cash_inflow' => (float) ($preview['summary']['cash_inflow'] ?? 0),
            'cash_outflow' => (float) ($preview['summary']['cash_outflow'] ?? 0),
            'balance' => (float) ($preview['summary']['ending_balance'] ?? 0),
            'download_url' => route('office.reports.documents.view', ['document' => $document, 'download' => 1]),
        ]);
    }

    public function updateReportStatus(Request $request, OrgReportStatus $report): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'oso', 403);
        abort_if(in_array($report->report_type, $this->semesterReportTypes(), true), 422, 'Use the report-specific OSO review workflow for AR and FR.');

        $validated = $request->validate([
            'status' => ['required', 'in:draft,ready_for_review,oso_review,verified,returned,archived'],
            'returned_to' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update($validated);

        return back()->with('success', strtoupper($report->report_type).' report status updated by system workflow.');
    }

    public function sendOrgReminder(OrgActivity $activity): RedirectResponse
    {
        $email = 'so.office@g.batstate-u.edu.ph';
        $sent = app(OrgWorkflowService::class)->sendSlaReminder(
            $email,
            $activity->organization_name ?: 'Student Organization',
            $activity->title,
            optional($activity->starts_at)->format('M j, Y g:i A') ?? 'the deadline'
        );

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Reminder emailed to the student organization.'
                : 'Reminder logged; mail could not be delivered (check mail config).'
        );
    }

    public function updateTosaSubsection(Request $request, TosaApplicant $applicant): RedirectResponse|JsonResponse
    {
        $this->requireTosaUnlock($request, 2);
        $validated = $request->validate([
            'subsection' => ['required', 'in:pending,screening,interview,accepted,rejected,returned'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'gwa' => ['nullable', 'numeric', 'between:1.00,5.00'],
            'gwa_verified' => ['nullable', 'boolean'],
        ]);
        if ($request->has('gwa_verified')) {
            $validated['gwa_verified'] = $request->boolean('gwa_verified');
        }

        $applicant->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'subsection' => $applicant->subsection]);
        }

        return back()->with('success', 'Applicant moved to '.$validated['subsection'].'.');
    }

    /**
     * Live OSO oversight dataset: every activity as a filterable row plus
     * pre-aggregated KPIs, ranking, and the action transactions table.
     * Replaces the old hardcoded dashboard figures.
     */
    private function buildOsoOverview($dbActivities): array
    {
        $role = Auth::guard('office')->user()?->office_role;
        $hideSemesterReportMetrics = in_array($role, ['sdo', 'ovcaa', 'oc'], true);
        $shortMap = StudentOrganization::query()->pluck('short_name', 'name')->all();
        $collegeMap = StudentOrganization::query()->pluck('college', 'name')->all();
        $shortOf = fn (?string $name) => $shortMap[$name ?? ''] ?? \Illuminate\Support\Str::limit($name ?: 'Student Organization', 26);

        $reviewStages = ['college_review', 'oso_review', 'sdo_review', 'ovcaa_review', 'oc_review', 'verification'];
        $bucketOf = fn (?string $st) => in_array($st, ['oc_approved', 'completed'], true)
            ? 'approved'
            : ($st === 'returned' ? 'revision' : 'pending');

        $rows = $dbActivities->map(function (OrgActivity $a) use ($bucketOf, $collegeMap, $shortOf) {
            // Submission dashboards are periodized by the date the record was
            // filed, not by the date the activity will happen. The schedule is
            // still used below for the SLA/urgency calculation.
            $ref = $a->created_at ?? $a->starts_at ?? now();
            $m = (int) $ref->format('n');
            $y = (int) $ref->format('Y');
            $ay = $m >= 8 ? $y.'-'.($y + 1) : ($y - 1).'-'.$y;
            $college = trim((string) ($a->college ?: ($collegeMap[$a->organization_name] ?? '')));

            return [
                'id' => $a->id,
                'title' => $a->title,
                'org' => $shortOf($a->organization_name),
                'college' => $this->departmentLabel($college),
                'scope' => str_contains(strtolower((string) $a->activity_scope), 'off') ? 'off-campus' : 'in-campus',
                'kind' => 'proposal',
                'bucket' => $bucketOf($a->workflow_status),
                'status' => $a->workflow_status ?: 'created',
                'ay' => $ay,
                'year' => $y,
                'sem' => in_array($m, [8, 9, 10, 11, 12], true) ? 'sem1' : (in_array($m, [1, 2, 3, 4, 5], true) ? 'sem2' : 'midyear'),
                'mon' => $ref->format('M'),
                'ym' => $ref->format('Y-m'),
                'date' => $a->created_at ? $a->created_at->format('M j, Y') : '',
                'url' => route('office.activities', ['activity' => $a->id]),
            ];
        })->values();

        $approved = $rows->where('bucket', 'approved')->count();
        $pending = $rows->where('bucket', 'pending')->count();
        $revision = $rows->where('bucket', 'revision')->count();
        $total = $rows->count();

        $inCampus = $rows->where('scope', 'in-campus')->count();
        $offCampus = $rows->where('scope', 'off-campus')->count();

        // Calendar-year charts always run January through December, with zeros
        // retained. Starting on day 1 also avoids month-end date overflow.
        $trendMonths = collect(range(0, 11))->map(fn ($i) => now()->startOfYear()->addMonths($i)->format('Y-m'))->values();
        $trend = [
            'labels' => $trendMonths->map(fn ($ym) => \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M'))->values()->all(),
            'data' => $trendMonths->map(fn ($ym) => $rows->where('ym', $ym)->count())->values()->all(),
        ];

        $byOrg = $rows->reject(fn ($r) => ($r['org'] ?? '') === 'Student Organization')->groupBy('org')->map(fn ($items, $org) => [
            'name' => $org,
            'count' => $items->count(),
            'pass' => $items->count() > 0 ? (int) round(($items->where('bucket', 'approved')->count() / $items->count()) * 100).'%' : '0%',
        ])->sort(function (array $left, array $right): int {
            return ($right['count'] <=> $left['count']) ?: strcasecmp($left['name'], $right['name']);
        })->values();
        $toRanking = static function ($groups): array {
            $top = $groups->first()['count'] ?? 0;

            return $groups->take(5)->map(fn ($r) => [
                'name' => $r['name'],
                'count' => $r['count'],
                'pct' => $top > 0 ? (int) round(($r['count'] / $top) * 100) : 0,
                'pass' => $r['pass'],
            ])->values()->all();
        };
        $ranking = $toRanking($byOrg);
        $byCollege = $rows->reject(fn ($r) => in_array($r['college'] ?? '', ['Unassigned', 'Campus Wide'], true))
            ->groupBy('college')->map(fn ($items, $college) => [
            'name' => $college,
            'count' => $items->count(),
            'pass' => $items->count() > 0 ? (int) round(($items->where('bucket', 'approved')->count() / $items->count()) * 100).'%' : '0%',
        ])->sort(function (array $left, array $right): int {
            return ($right['count'] <=> $left['count']) ?: strcasecmp($left['name'], $right['name']);
        })->values();
        $collegeRanking = $toRanking($byCollege);

        $pendingRows = $rows->whereIn('bucket', ['pending', 'revision'])->sortByDesc('id')->take(8)->values();
        $transactions = $pendingRows->map(function ($r) use ($dbActivities) {
            $a = $dbActivities->firstWhere('id', $r['id']);
            $days = $a?->starts_at ? now()->diffInDays($a->starts_at, false) : null;
            if ($r['bucket'] === 'revision') {
                $sla = ['text' => 'Returned', 'class' => 'oso-sla-high', 'icon' => 'bi-arrow-counterclockwise'];
            } elseif ($days === null) {
                $sla = ['text' => 'No date set', 'class' => 'oso-sla-normal', 'icon' => 'bi-clock'];
            } elseif ($days < 0) {
                $sla = ['text' => 'Overdue', 'class' => 'oso-sla-high', 'icon' => 'bi-exclamation-triangle-fill'];
            } elseif ($days <= 7) {
                $sla = ['text' => $days.' Day'.($days === 1 ? '' : 's').' Left', 'class' => 'oso-sla-high', 'icon' => 'bi-clock-fill'];
            } else {
                $sla = ['text' => $days.' Days Left', 'class' => 'oso-sla-normal', 'icon' => 'bi-clock'];
            }

            return array_merge($r, [
                'scopeLabel' => $r['scope'] === 'off-campus' ? 'Off-Campus' : 'In-Campus',
                'statusLabel' => app(OrgWorkflowService::class)->label($r['status']),
                'sla' => $sla,
            ]);
        })->all();

        $windowIds = OrgRenewalWindow::query()->pluck('id');
        $renewalRows = OrgRenewalSubmission::query()->get();
        $renewalPending = $renewalRows
            ->whereIn('status', ['submitted', 'oso_review'])
            ->when($windowIds->isNotEmpty(), fn ($rows) => $rows->whereIn('renewal_window_id', $windowIds))
            ->count();

        $reportRows = OrgReportStatus::query()
            ->whereIn('report_type', $this->semesterReportTypes())
            ->get();
        $currentReports = $reportRows->sortByDesc('id')->unique(fn (OrgReportStatus $row): string => implode('|', [
            $row->organization_name, $row->semester, $row->academic_year, $row->report_type,
        ]));
        $pendingReports = $currentReports->where('status', 'oso_review')->count();

        $tosaRows = TosaApplicant::query()->get();
        $renewalPending = (int) $renewalPending;
        $tosaPending = $tosaRows->whereIn('subsection', ['pending', 'screening', 'interview'])->count();

        // A common period payload lets the browser apply the same year,
        // semester, month, and scope filters to every OSO transaction type.
        $periodOf = function ($date): array {
            $date = $date instanceof Carbon ? $date : ($date ? Carbon::parse($date) : null);
            if (! $date) {
                return ['year' => null, 'sem' => null, 'mon' => null];
            }

            return [
                'year' => $date->year,
                'sem' => in_array($date->month, [8, 9, 10, 11, 12], true)
                    ? 'sem1'
                    : (in_array($date->month, [1, 2, 3, 4, 5], true) ? 'sem2' : 'midyear'),
                'mon' => $date->format('M'),
            ];
        };
        $typeRows = $rows->map(fn (array $row): array => [
            'kind' => 'proposal',
            'year' => $row['year'],
            'sem' => $row['sem'],
            'mon' => $row['mon'],
            'scope' => $row['scope'],
        ])->values();
        foreach ($renewalRows as $row) {
            $period = $periodOf($row->submitted_at ?? $row->created_at);
            $typeRows->push(array_merge(['kind' => 'renewal', 'scope' => null], $period));
        }
        foreach ($reportRows as $row) {
            $period = $periodOf($row->submitted_at ?? $row->created_at);
            $typeRows->push(array_merge(['kind' => $row->report_type, 'scope' => null], $period));
        }
        foreach ($tosaRows as $row) {
            $period = $periodOf($row->created_at);
            $typeRows->push(array_merge(['kind' => 'tosa', 'scope' => null], $period));
        }

        // Real average turnaround for reports and TOSA decisions. A draft or
        // still-open record intentionally has no processing-time bar.
        $averageDays = static function ($records, string $start, string $end): ?float {
            $durations = $records->map(function ($record) use ($start, $end): ?float {
                $from = $record->{$start};
                $to = $record->{$end};
                if (! $from || ! $to) {
                    return null;
                }

                return Carbon::parse($from)->diffInHours(Carbon::parse($to)) / 24;
            })->filter(fn ($days) => $days !== null);

            return $durations->isNotEmpty() ? round($durations->avg(), 1) : null;
        };
        $reportReviewed = $reportRows->filter(fn (OrgReportStatus $row): bool => (bool) $row->submitted_at && (bool) $row->reviewed_at);
        $financialAvg = $averageDays($reportReviewed->where('report_type', 'fr'), 'submitted_at', 'reviewed_at');
        $accomplishmentAvg = $averageDays($reportReviewed->where('report_type', 'ar'), 'submitted_at', 'reviewed_at');
        $tosaReviewed = $tosaRows->whereIn('subsection', ['accepted', 'rejected']);
        $tosaAvg = $averageDays($tosaReviewed, 'created_at', 'updated_at');

        $pendingSubmissions = $pending + $revision;
        $pendingTrx = $pendingSubmissions + $renewalPending + $pendingReports + $tosaPending;
        $currentAcademicYear = $this->academicPeriod(now())['academic_year'];

        // Real average turnaround: decided activities (created -> last update)
        // and reviewed renewal packets (submitted -> last update), in days.
        $decided = $dbActivities->whereIn('workflow_status', ['oc_approved', 'completed', 'returned']);
        $proposalAvg = $decided->count() > 0
            ? round($decided->avg(fn (OrgActivity $a) => ($a->created_at && $a->updated_at)
                ? $a->created_at->diffInHours($a->updated_at) / 24 : 0), 1)
            : null;
        $reviewedRenewals = OrgRenewalSubmission::query()
            ->whereIn('status', ['approved', 'returned'])
            ->whereNotNull('submitted_at')->get();
        $renewalAvg = $reviewedRenewals->count() > 0
            ? round($reviewedRenewals->avg(fn ($s) => $s->submitted_at->diffInHours($s->updated_at) / 24), 1)
            : null;

        // Real return-cause analysis from reviewer remarks on returned docs.
        $causeBuckets = [
            'Missing Faculty Adviser / Dean Signature' => ['sign', 'adviser', 'dean'],
            'Budget Itemization vs Receipt Discrepancy' => ['budget', 'receipt', 'discrep', 'amount', 'expense'],
            'Lacking SDO Waste Policy (WPCF) Form' => ['waste', 'wpcf'],
            'Incomplete Safety Protocol & Medical Clearance' => ['safety', 'medical', 'clearance', 'protocol'],
        ];
        $causeCounts = array_fill_keys(array_keys($causeBuckets), 0);
        $causeOther = 0;
        $returnedRemarks = ActivityComplianceDoc::query()
            ->where('status', 'returned')->whereNotNull('remarks')->pluck('remarks');
        foreach ($returnedRemarks as $remark) {
            $text = strtolower((string) $remark);
            $matched = false;
            foreach ($causeBuckets as $label => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($text, $kw)) {
                        $causeCounts[$label]++;
                        $matched = true;
                        break 2;
                    }
                }
            }
            if (! $matched) {
                $causeOther++;
            }
        }
        $causeTotal = array_sum($causeCounts) + $causeOther;
        $returnCauses = $causeTotal > 0 ? collect($causeCounts)
            ->map(fn ($c, $label) => [
                'label' => $label,
                'count' => $c,
                'pct' => $causeTotal > 0 ? round(($c / $causeTotal) * 100).'% of returns' : '—',
            ])
            ->when($causeOther > 0, fn ($col) => $col->push([
                'label' => 'Other compliance remarks',
                'count' => $causeOther,
                'pct' => $causeTotal > 0 ? round(($causeOther / $causeTotal) * 100).'% of returns' : '—',
            ]))
            ->sortByDesc('count')->values()->all() : [];

        $typeLabels = $hideSemesterReportMetrics
            ? ['Activity Proposal', 'Renewal', 'TOSA Award']
            : ['Activity Proposal', 'Renewal', 'Financial (FR)', 'Accomplishment (AR)', 'TOSA Award'];
        $allTypeCounts = [
            'proposal' => $typeRows->where('kind', 'proposal')->count(),
            'renewal' => $typeRows->where('kind', 'renewal')->count(),
            'fr' => $typeRows->where('kind', 'fr')->count(),
            'ar' => $typeRows->where('kind', 'ar')->count(),
            'tosa' => $typeRows->where('kind', 'tosa')->count(),
        ];
        $typeBreakdown = $hideSemesterReportMetrics
            ? [$allTypeCounts['proposal'], $allTypeCounts['renewal'], $allTypeCounts['tosa']]
            : [$allTypeCounts['proposal'], $allTypeCounts['renewal'], $allTypeCounts['fr'], $allTypeCounts['ar'], $allTypeCounts['tosa']];
        $processingLabels = $hideSemesterReportMetrics
            ? ['Proposals', 'Renewal', 'TOSA']
            : ['Proposals', 'Renewal', 'Financial (FR)', 'Accomp. (AR)', 'TOSA'];
        $processingTime = $hideSemesterReportMetrics
            ? [$proposalAvg, $renewalAvg, $tosaAvg]
            : [$proposalAvg, $renewalAvg, $financialAvg, $accomplishmentAvg, $tosaAvg];

        return [
            'rows' => $rows->values()->all(),
            'approvalDonut' => [$approved, $pending, $revision],
            'scope' => ['inCampus' => $inCampus, 'offCampus' => $offCampus],
            'trend' => $trend,
            'ranking' => $ranking,
            'collegeRanking' => $collegeRanking,
            'transactions' => $transactions,
            'kpis' => [
                'totalOrgs' => StudentOrganization::active()->count(),
                'pendingTrx' => $pendingTrx,
                'pendingSub' => $pendingSubmissions.' Proposals · '.$renewalPending.' Renewal · '.$pendingReports.' AR/FR Reports · '.$tosaPending.' TOSA',
                'totalSubmissions' => $total,
                'revisionRate' => $total > 0 ? round(($revision / $total) * 100, 1).'%' : '0%',
            ],
            'orgSub' => 'Recognized orgs · A.Y. '.($currentAcademicYear ?: '—'),
            'currentAcademicYear' => $currentAcademicYear,
            'typeLabels' => $typeLabels,
            'typeBreakdown' => $typeBreakdown,
            'typeRows' => $typeRows->values()->all(),
            'processingLabels' => $processingLabels,
            'processingTime' => $processingTime,
            'returnCauses' => $returnCauses,
        ];
    }

    /**
     * OSO detail page for one submitted renewal packet, checked against the
     * checklist of the packet's own renewal window.
     */
    public function showRenewalSubmission(OrgRenewalSubmission $submission): View
    {
        abort_unless((Auth::guard('office')->user()?->office_role ?? '') === 'oso', 403);
        abort_unless(in_array($submission->status, ['submitted', 'returned', 'approved', 'rejected'], true)
            && $submission->submitted_at !== null, 404);

        $submission->load(['documents', 'window']);
        $window = $submission->window;
        $requiredDocs = $window?->requiredDocList() ?? OrgRenewalWindow::defaultRequiredDocs();
        $review = $submission->requiredDocumentReview($requiredDocs);

        return view('org.renewal-submission', array_merge($this->deskContext(), [
            'activeNav' => 'renewal',
            'submission' => $submission,
            'renewalWindow' => $window,
            'requiredDocs' => $requiredDocs,
            'organization' => StudentOrganization::query()->where('name', $submission->organization_name)->first(),
            'verifiedCount' => $review['verified'],
            'missingCount' => $review['missing'],
            'canApproveRenewal' => $submission->canBeApproved($requiredDocs),
            'canReviewDocuments' => $submission->status === 'submitted',
        ]));
    }

    /**
     * OSO verifies, returns (For Revision), or rejects one renewal document.
     */
    public function reviewRenewalDocument(Request $request, OrgRenewalDocument $document): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless(($office?->office_role ?? '') === 'oso', 403);

        $validated = $request->validate([
            'decision' => ['required', Rule::in([
                OrgRenewalDocument::REVIEW_VERIFIED,
                OrgRenewalDocument::REVIEW_RETURNED,
                OrgRenewalDocument::REVIEW_REJECTED,
            ])],
            'remarks' => [
                Rule::requiredIf(fn () => in_array($request->input('decision'), [OrgRenewalDocument::REVIEW_RETURNED, OrgRenewalDocument::REVIEW_REJECTED], true)),
                'nullable',
                'string',
                'max:2000',
            ],
            'file_version' => ['required', 'string', 'size:64'],
        ]);

        $error = DB::connection('mysql')->transaction(function () use ($document, $validated, $office): ?string {
            $submission = OrgRenewalSubmission::query()->lockForUpdate()->findOrFail($document->submission_id);
            if ($submission->status !== 'submitted') {
                return 'Documents can only be reviewed while the renewal packet is submitted for OSO review.';
            }

            $document = OrgRenewalDocument::query()->lockForUpdate()->findOrFail($document->id);
            if (! $document->hasStoredFile()) {
                return 'This document is missing its stored file. Ask the organization to upload it before review.';
            }
            if (! hash_equals($document->fileVersion(), $validated['file_version'])) {
                return 'This document was replaced after you opened it. Reload the page and review the new file.';
            }

            $remarks = trim((string) ($validated['remarks'] ?? ''));
            $document->update([
                'review_status' => $validated['decision'],
                'review_remarks' => $remarks !== '' ? $remarks : null,
                'reviewed_at' => now(),
                'reviewed_by' => $office->id,
            ]);

            return null;
        });

        if ($error !== null) {
            return back()->withErrors(['renewal' => $error]);
        }

        $label = OrgRenewalDocument::reviewStatusLabels()[$validated['decision']];

        return back()->with('success', "{$document->title} marked {$label}.");
    }

    /**
     * Serve an uploaded renewal document to OSO or the owning SO desk.
     */
    public function renewalDocumentFile(OrgRenewalDocument $document)
    {
        $office = Auth::guard('office')->user();
        $role = $office?->office_role ?? '';
        abort_unless(in_array($role, ['so', 'oso'], true), 403);

        $submission = $document->submission;
        abort_unless($submission instanceof OrgRenewalSubmission, 404);
        if ($role === 'so') {
            $assigned = $this->assignedOrganizationName($office);
            abort_unless(
                (int) $submission->submitted_by === (int) $office->id
                    || ($assigned !== null && $assigned === $submission->organization_name),
                403
            );
        }

        $disk = Storage::disk('public');
        abort_unless($document->hasStoredFile(), 404);
        $name = $document->file_name ?: basename($document->file_path);

        return request()->boolean('download')
            ? $disk->download($document->file_path, $name)
            : $disk->response($document->file_path, $name);
    }

    /**
     * OSO final decision on a submitted renewal packet. Approval requires every
     * required document of the packet's own window to be uploaded and verified.
     */
    public function reviewRenewalSubmission(Request $request, OrgRenewalSubmission $submission): RedirectResponse
    {
        abort_unless((Auth::guard('office')->user()?->office_role ?? '') === 'oso', 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,returned,rejected'],
            'remarks' => [
                Rule::requiredIf(fn () => in_array($request->input('decision'), ['returned', 'rejected'], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $error = DB::connection('mysql')->transaction(function () use ($submission, $validated): ?string {
            $locked = OrgRenewalSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($locked->status !== 'submitted') {
                return 'Only submitted renewal packets can be decided.';
            }

            if ($validated['decision'] === 'approved') {
                $locked->load(['documents', 'window']);
                $requiredDocs = $locked->window?->requiredDocList() ?? OrgRenewalWindow::defaultRequiredDocs();
                if (! $locked->canBeApproved($requiredDocs)) {
                    return 'Verify every required document before approving this renewal packet.';
                }
            }

            $remarks = trim((string) ($validated['remarks'] ?? ''));
            $locked->update([
                'status' => $validated['decision'],
                'review_remarks' => $remarks !== '' ? $remarks : null,
                'reviewed_at' => now(),
            ]);

            return null;
        });

        if ($error !== null) {
            return back()->withErrors(['renewal' => $error]);
        }

        return back()->with('success', match ($validated['decision']) {
            'approved' => "Renewal packet for {$submission->organization_name} approved.",
            'rejected' => "Renewal packet for {$submission->organization_name} rejected.",
            default => "Renewal packet for {$submission->organization_name} returned for revision.",
        });
    }

    /**
     * OSO updates an organization's qualification status (Qualified vs Not Qualified) and Active/Inactive state.
     */
    public function updateOrganizationQualification(Request $request, StudentOrganization $organization): RedirectResponse
    {
        abort_unless((Auth::guard('office')->user()?->office_role ?? '') === 'oso', 403);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'is_qualified_for_renewal' => ['required', 'boolean'],
            'disqualification_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $isQualified = (bool) $validated['is_qualified_for_renewal'];
        $isActive = (bool) $validated['is_active'];

        $reason = $validated['disqualification_reason'] ?? null;
        if (! $isQualified && empty(trim((string) $reason))) {
            $reason = 'Flagged as not qualified by OSO coordinator.';
        } elseif ($isQualified) {
            $reason = null;
        }

        $organization->update([
            'is_active' => $isActive,
            'is_qualified_for_renewal' => $isQualified,
            'disqualification_reason' => $reason,
            'status_updated_at' => now(),
        ]);

        $statusLabel = $isQualified ? 'Qualified to Renew' : 'Not Qualified to Renew';
        $activeLabel = $isActive ? 'Active' : 'Inactive';

        return redirect()->route('office.renewal')
            ->with('success', "{$organization->name} status updated: {$statusLabel} ({$activeLabel}).");
    }

    /**
     * OSO-only student reports queue (Student Voice submissions to verify).
     */
    public function studentReports(Request $request): View
    {
        abort_unless((Auth::guard('office')->user()?->office_role ?? '') === 'oso', 403);

        $status = trim((string) $request->query('status', ''));
        $college = trim((string) $request->query('college', ''));
        $topic = trim((string) $request->query('topic', ''));

        $base = StudentFeedback::query();
        $reports = (clone $base)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($college !== '', fn ($q) => $q->where('college', $college))
            ->when($topic !== '', fn ($q) => $q->where('topic', $topic))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('org.reports', array_merge($this->deskContext(), [
            'activeNav' => 'reports',
            'reports' => $reports,
            'stats' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'verified' => (clone $base)->where('status', 'verified')->count(),
                'dismissed' => (clone $base)->where('status', 'dismissed')->count(),
            ],
            'colleges' => StudentFeedback::query()
                ->whereNotNull('college')->where('college', '!=', '')
                ->distinct()->orderBy('college')->pluck('college'),
            'topics' => StudentFeedback::query()
                ->whereNotNull('topic')->where('topic', '!=', '')
                ->distinct()->orderBy('topic')->pluck('topic'),
            'filters' => ['status' => $status, 'college' => $college, 'topic' => $topic],
        ]));
    }

    /**
     * OSO decision on a student report (verify or dismiss with a note).
     */
    public function reviewStudentReport(Request $request, StudentFeedback $report): RedirectResponse
    {
        abort_unless((Auth::guard('office')->user()?->office_role ?? '') === 'oso', 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:verified,dismissed'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->status = $validated['decision'];
        $report->review_note = $validated['review_note'] ?? null;
        $report->verified_by = Auth::guard('office')->id();
        $report->verified_at = now();
        $report->save();

        return back()->with('success', $validated['decision'] === 'verified'
            ? 'Student report verified.'
            : 'Student report dismissed.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, OrgActivity>  $activities
     * @return array<string, mixed>
     */
    private function dashboardChartPayload($activities): array
    {
        $workflow = app(OrgWorkflowService::class);
        $labels = [];
        $counts = [];
        foreach (OrgWorkflowService::FLOW as $status) {
            $labels[] = $workflow->label($status);
            $counts[] = $activities->where('workflow_status', $status)->count();
        }

        // A report needs the full month sequence, including months with no
        // filings, so a drop to zero remains visible in the line chart.
        $months = collect(range(0, 11))->map(fn ($i) => now()->startOfYear()->addMonths($i));
        $trend = $months->map(function ($month) use ($activities) {
            $key = $month->format('Y-m');

            return $activities->filter(fn ($a) => optional($a->created_at)->format('Y-m') === $key)->count();
        })->values();

        return [
            'approvalLabels' => $labels,
            'approvalCounts' => $counts,
            'trendLabels' => $months->map(fn ($m) => $m->format('M'))->values(),
            'trendYear' => now()->year,
            'trendCounts' => $trend,
            'typeLabels' => ['In-Campus', 'Off-Campus', 'Compliance', 'Reports'],
            'typeCounts' => [
                $activities->where('activity_scope', 'in_campus')->count(),
                $activities->where('activity_scope', 'local_off_campus')->count(),
                ActivityComplianceDoc::query()->count(),
                OrgReportStatus::query()->count(),
            ],
        ];
    }
}
