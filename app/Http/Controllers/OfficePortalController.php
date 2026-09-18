<?php

namespace App\Http\Controllers;

use App\Models\ArchiveDocument;
use App\Models\ArchiveFolder;
use App\Models\ActivityComplianceDoc;
use App\Models\BudgetItem;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\OrgRenewalDocument;
use App\Models\OrgRenewalSubmission;
use App\Models\OrgRenewalWindow;
use App\Models\OrgReportStatus;
use App\Models\StudentFeedback;
use App\Models\TosaApplicant;
use App\Services\BudgetChainService;
use App\Services\OrgWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

        return [
            'office' => $office,
            'brand' => $this->brandFor($office->office_role),
            'navBadges' => [
                'fr_attachments' => count($this->frAttachmentList()),
                'ar_attachments' => count($this->arAttachmentList()),
            ],
        ];
    }

    public function home(): View
    {
        return $this->dashboard();
    }

    public function dashboard(): View
    {
        $pipeline = $this->pipelineActivities();
        $workflow = app(OrgWorkflowService::class);
        $upcoming = collect($pipeline)
            ->filter(function (array $item): bool {
                if (($item['upcoming_at'] ?? null) === null) {
                    return false;
                }

                return \Illuminate\Support\Carbon::parse($item['upcoming_at'])->isFuture()
                    || ! empty($item['force_upcoming']);
            })
            ->sortBy('upcoming_at')
            ->first();

        if (! $upcoming) {
            $upcoming = collect($pipeline)->first();
        }

        $dbActivities = OrgActivity::query()->get();
        $approved = $dbActivities->whereIn('workflow_status', ['oc_approved'])->count()
            ?: collect($pipeline)->whereIn('status_key', ['ovcaa_approved', 'completed', 'oc_approved'])->count();
        $pending = $dbActivities->whereNotIn('workflow_status', ['oc_approved'])->count()
            ?: collect($pipeline)->whereIn('status_key', ['created', 'verification', 'pending', 'returned', 'oso_review', 'sdo_review'])->count();

        $fundAccount = OrgFundAccount::query()->orderByDesc('total_funds')->first();
        $totalFunds = (int) ($fundAccount?->total_funds ?: BudgetItem::query()->sum('allocated') ?: 185000);
        $utilized = (int) (BudgetItem::query()->sum('utilized') ?: 115150);
        $remaining = max(0, $totalFunds - $utilized);

        $urgency = OrgActivity::query()
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

        return view('org.dashboard', array_merge($this->deskContext(), [
            'activeNav' => 'dashboard',
            'stats' => [
                'total' => max(5, $dbActivities->count()),
                'approved' => $approved,
                'pending' => $pending,
                'expenses' => $utilized,
            ],
            'transparency' => [
                'allocated' => $totalFunds,
                'total_funds' => $totalFunds,
                'beginning_balance' => (int) ($fundAccount?->beginning_balance ?: 25000),
                'utilized' => $utilized,
                'remaining' => $remaining,
                'percent' => $totalFunds > 0 ? (int) round(($utilized / $totalFunds) * 100) : 0,
                'remaining_percent' => $totalFunds > 0 ? (int) round(($remaining / $totalFunds) * 100) : 0,
            ],
            'fundAccount' => $fundAccount,
            'urgencyQueue' => $urgency,
            'chartPayload' => $chartPayload,
            'workflowStages' => OrgWorkflowService::FLOW,
            'upcoming' => $upcoming,
            'tracker' => array_slice($pipeline, 0, 5),
            'updates' => $pipeline,
            'studentFeedback' => StudentFeedback::query()->latest()->limit(8)->get(),
        ]));
    }

    public function analytics(): View
    {
        $pipeline = $this->pipelineActivities();
        $workflow = app(OrgWorkflowService::class);

        $byStatus = collect($pipeline)
            ->groupBy('status_key')
            ->map(fn ($rows) => $rows->count())
            ->all();

        $dbActivities = OrgActivity::query()->get();
        $budgetItems = BudgetItem::query()->orderByDesc('utilized')->get();

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

        if ($collegeStats->isEmpty()) {
            $collegeStats = collect([
                ['college' => 'CICS', 'activities' => 3, 'completed' => 1, 'completion_percent' => 33, 'allocated' => 50000, 'approved_budget' => 45000, 'implemented_budget' => 32000, 'utilization_percent' => 64],
                ['college' => 'CHS', 'activities' => 2, 'completed' => 1, 'completion_percent' => 50, 'allocated' => 42500, 'approved_budget' => 42500, 'implemented_budget' => 24900, 'utilization_percent' => 59],
                ['college' => 'CAS', 'activities' => 2, 'completed' => 1, 'completion_percent' => 50, 'allocated' => 115000, 'approved_budget' => 115000, 'implemented_budget' => 62750, 'utilization_percent' => 55],
            ]);
        }

        $activityFinancials = $dbActivities->map(function (OrgActivity $a) use ($workflow) {
            $allocated = (int) ($a->approved_budget ?: 0);
            $utilized = (int) ($a->implemented_budget ?: 0);

            return [
                'name' => $a->title,
                'scope' => $a->activity_scope ?: 'in_campus',
                'scope_label' => ($a->activity_scope === 'local_off_campus') ? 'Off-Campus' : 'In-Campus',
                'college' => $a->college,
                'allocated' => $allocated,
                'utilized' => $utilized,
                'remaining' => max(0, $allocated - $utilized),
                'burn_rate' => $allocated > 0 ? round(($utilized / $allocated) * 100, 1) : 0,
                'status' => $workflow->label($a->workflow_status ?: 'created'),
                'status_style' => $a->workflow_status === 'oc_approved' ? 'green' : 'blue',
                'month' => optional($a->starts_at)->format('M') ?? 'N/A',
                'year' => (int) (optional($a->starts_at)->format('Y') ?? now()->year),
            ];
        })->values()->all();

        if ($activityFinancials === []) {
            $activityFinancials = [
                ['name' => 'Innovation Fair Booth Series', 'scope' => 'in_campus', 'scope_label' => 'In-Campus', 'college' => 'CICS', 'allocated' => 15000, 'utilized' => 15000, 'remaining' => 0, 'burn_rate' => 100, 'status' => 'OC Approved', 'status_style' => 'green', 'month' => 'Jul', 'year' => 2026],
                ['name' => 'Leadership Summit 2026', 'scope' => 'local_off_campus', 'scope_label' => 'Off-Campus', 'college' => 'CAS', 'allocated' => 75000, 'utilized' => 42750, 'remaining' => 32250, 'burn_rate' => 57, 'status' => 'OSO Review', 'status_style' => 'blue', 'month' => 'Sep', 'year' => 2026],
            ];
        }

        $inCampus = collect($activityFinancials)->where('scope', 'in_campus');
        $offCampus = collect($activityFinancials)->where('scope', 'local_off_campus');
        $totalAllocated = (int) collect($activityFinancials)->sum('allocated');
        $totalUtilized = (int) collect($activityFinancials)->sum('utilized');

        return view('org.analytics', array_merge($this->deskContext(), [
            'activeNav' => 'analytics',
            'byStatus' => $byStatus,
            'pipeline' => $pipeline,
            'budgetItems' => $budgetItems->take(6),
            'activityFinancials' => $activityFinancials,
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
                'healthScore' => 84,
                'totalAllocated' => $totalAllocated ?: 185000,
                'totalUtilized' => $totalUtilized ?: 115150,
                'remainingBalance' => max(0, ($totalAllocated ?: 185000) - ($totalUtilized ?: 115150)),
                'burnRate' => ($totalAllocated ?: 185000) > 0 ? round((($totalUtilized ?: 115150) / ($totalAllocated ?: 185000)) * 100, 1) : 0,
                'complianceRate' => 96.4,
                'inCampusAllocated' => (int) $inCampus->sum('allocated'),
                'inCampusUtilized' => (int) $inCampus->sum('utilized'),
                'inCampusRemaining' => (int) max(0, $inCampus->sum('allocated') - $inCampus->sum('utilized')),
                'offCampusAllocated' => (int) $offCampus->sum('allocated'),
                'offCampusUtilized' => (int) $offCampus->sum('utilized'),
                'offCampusRemaining' => (int) max(0, $offCampus->sum('allocated') - $offCampus->sum('utilized')),
            ],
        ]));
    }

    public function activities(Request $request): View
    {
        $allActivities = $this->orgActivitiesList();
        $selectedSlug = $request->query('activity');
        $selectedActivity = null;

        if ($selectedSlug) {
            $selectedActivity = collect($allActivities)->firstWhere('slug', $selectedSlug)
                ?? collect($allActivities)->firstWhere('id', (int) $selectedSlug)
                ?? collect($allActivities)->first();
        }

        return view('org.activities', array_merge($this->deskContext(), [
            'activeNav' => 'activities',
            'activities' => $allActivities,
            'selectedActivity' => $selectedActivity,
            'forApprovalCount' => collect($allActivities)->where('filter_category', 'for_approval')->count(),
            'approvedCount' => collect($allActivities)->where('filter_category', 'approved')->count(),
            'inReviewCount' => collect($allActivities)->where('filter_category', 'in_review')->count(),
            'returnedCount' => collect($allActivities)->where('filter_category', 'returned')->count(),
        ]));
    }

    public function createActivity(Request $request): View
    {
        $allActivities = $this->orgActivitiesList();
        $editSlug = $request->query('edit');
        $editActivity = null;

        if ($editSlug) {
            $editActivity = collect($allActivities)->firstWhere('slug', $editSlug)
                ?? collect($allActivities)->firstWhere('id', (int) $editSlug);
        }

        return view('org.activity-create', array_merge($this->deskContext(), [
            'activeNav' => 'activities',
            'editActivity' => $editActivity,
            'submission' => new InCampusActivitySubmission([
                'activity_type' => 'in_campus',
                'status' => 'draft',
            ]),
            'inCampusRequirements' => $this->inCampusRequirements(),
            'offCampusRequirements' => $this->localOffCampusRequirements(),
        ]));
    }

    public function downloadActivityTemplates(Request $request): BinaryFileResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
    {
        $type = $request->query('type', 'in_campus');
        $type = in_array($type, ['in_campus', 'local_off_campus'], true) ? $type : 'in_campus';

        $sourceDir = $type === 'local_off_campus'
            ? base_path('Local Off Campus')
            : base_path('In Campus');

        abort_unless(is_dir($sourceDir), 404, 'Template folder not found.');

        $files = collect(scandir($sourceDir) ?: [])
            ->reject(fn ($name) => in_array($name, ['.', '..'], true))
            ->filter(fn ($name) => is_file($sourceDir.DIRECTORY_SEPARATOR.$name))
            ->values();

        abort_if($files->isEmpty(), 404, 'No template documents available.');

        // Single-file shortcut when only listing is requested
        if ($request->filled('file')) {
            $safe = basename((string) $request->query('file'));
            $path = $sourceDir.DIRECTORY_SEPARATOR.$safe;
            abort_unless(is_file($path), 404);

            return response()->download($path, $safe);
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
                    $zip->addFile($sourceDir.DIRECTORY_SEPARATOR.$file, $file);
                }
                $zip->close();
                $built = is_file($zipPath);
            }
        }

        if (! $built && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $psSource = str_replace("'", "''", $sourceDir.'\\*');
            $psDest = str_replace("'", "''", $zipPath);
            $cmd = 'powershell -NoProfile -Command "Compress-Archive -Path \''.$psSource.'\' -DestinationPath \''.$psDest.'\' -Force"';
            @exec($cmd);
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
            $html .= '<li><a href="'.e($url).'"><i></i> '.e($file).'</a></li>';
        }

        $html .= '</ul><p><a href="'.e(route('office.activities.create')).'">← Back to Create Activity</a></p></body></html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function editActivity(InCampusActivitySubmission $submission): View
    {
        $submission->load('activity');

        return view('org.activity-create', array_merge($this->deskContext(), [
            'activeNav' => 'activities',
            'submission' => $submission,
            'inCampusRequirements' => $this->inCampusRequirements(),
            'offCampusRequirements' => $this->localOffCampusRequirements(),
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
        $requestedMonth = $request->query('month');
        try {
            $month = $requestedMonth
                ? Carbon::createFromFormat('Y-m', $requestedMonth)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            $month = Carbon::now()->startOfMonth();
        }

        $cursor = $month->copy()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $pipelineEvents = collect($this->pipelineActivities())
            ->filter(fn (array $item): bool => ! empty($item['upcoming_at']) || ! empty($item['date']))
            ->map(function (array $item): array {
                $startsAt = Carbon::parse($item['upcoming_at'] ?? $item['date']);

                return [
                    'title' => $item['title'],
                    'starts_at' => $startsAt->toIso8601String(),
                    'date_key' => $startsAt->toDateString(),
                    'date_label' => $startsAt->format('M j, Y'),
                    'time_label' => ! empty($item['upcoming_at']) ? $startsAt->format('g:i A') : 'Time to be announced',
                    'location' => $item['location'] ?? 'Venue to be announced',
                    'status' => $item['status'] ?? 'Scheduled',
                    'status_key' => $item['status_key'] ?? 'created',
                    'note' => $item['note'] ?? null,
                ];
            });

        $savedEvents = OrgActivity::query()
            ->whereNotNull('starts_at')
            ->get()
            ->map(function (OrgActivity $activity): array {
                $startsAt = $activity->starts_at;

                return [
                    'title' => $activity->title,
                    'starts_at' => $startsAt->toIso8601String(),
                    'date_key' => $startsAt->toDateString(),
                    'date_label' => $startsAt->format('M j, Y'),
                    'time_label' => $startsAt->format('g:i A'),
                    'location' => $activity->location ?: 'Venue to be announced',
                    'status' => ucfirst($activity->status),
                    'status_key' => $activity->status === 'draft' ? 'created' : 'verification',
                    'note' => $activity->description,
                ];
            });

        $events = $pipelineEvents
            ->merge($savedEvents)
            ->unique(fn (array $event): string => $event['title'].'|'.$event['starts_at'])
            ->sortBy('starts_at')
            ->values();
        $eventsByDate = $events->groupBy('date_key');

        $days = [];
        while ($cursor <= $end) {
            $date = $cursor->copy();
            $days[] = [
                'date' => $date,
                'inMonth' => $date->month === $month->month,
                'events' => $eventsByDate->get($date->toDateString(), collect())->values()->all(),
            ];
            $cursor->addDay();
        }

        return view('org.calendar', array_merge($this->deskContext(), [
            'activeNav' => 'calendar',
            'monthLabel' => $month->format('F Y'),
            'days' => $days,
            'events' => $events,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]));
    }

    public function budget(): View
    {
        $orgFilter = trim((string) request('organization', ''));
        $budget = $this->budgetUtilizationData();
        $items = BudgetItem::query()
            ->when($orgFilter !== '', fn ($q) => $q->where('organization_name', $orgFilter))
            ->orderByDesc('utilized')
            ->get();

        $reportStatus = OrgReportStatus::query()
            ->where('report_type', 'budget')
            ->latest()
            ->first();

        return view('org.budget', array_merge($this->deskContext(), [
            'activeNav' => 'budget',
            'budget' => $budget,
            'budgetItems' => $items,
            'organizations' => BudgetItem::query()->whereNotNull('organization_name')->distinct()->pluck('organization_name'),
            'selectedOrganization' => $orgFilter,
            'reportStatus' => $reportStatus,
            'receiptReviews' => ExpenseReceiptReview::query()->latest()->limit(8)->get(),
            'budgetChainBlocks' => app(BudgetChainService::class)->recentBlocks(6),
        ]));
    }

    public function storeReceiptReview(Request $request): RedirectResponse
    {
        if ($request->input('receipt_detected') === '0'
            || in_array($request->input('ocr_quality'), ['blurry', 'unreadable'], true)) {
            return back()
                ->withInput()
                ->withErrors(['receipt' => 'Receipt is incomplete, blurry, or missing OR/reference number. Please retake a clearer photo.']);
        }

        $validated = $request->validate([
            'activity' => ['required', 'string', 'max:255'],
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_cost' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'receipt_reference' => ['nullable', 'string', 'max:120'],
            'ocr_quality' => ['nullable', 'in:complete,partial,blurry,unreadable'],
            'receipt' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'receipt_reviewed' => ['accepted'],
            'ocr_confidence' => ['nullable', 'integer', 'min:0', 'max:100'],
            'receipt_detected' => ['nullable', 'in:0,1'],
        ], [
            'receipt_reviewed.accepted' => 'Review the detected receipt details and confirm that they match the original receipt.',
        ]);

        $file = $request->file('receipt');
        $path = $file->store('expense-receipts', 'public');

        $total = (float) $validated['unit_cost'] * (int) $validated['quantity'];
        $seal = app(BudgetChainService::class)->sealExpense([
            'activity_title' => $validated['activity'],
            'item_name' => $validated['item_name'],
            'supplier' => $validated['supplier'] ?? null,
            'organization_name' => $validated['organization_name'] ?? null,
            'receipt_reference' => $validated['receipt_reference'] ?? null,
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'],
            'total' => $total,
            'expense_date' => $validated['expense_date'],
        ]);

        ExpenseReceiptReview::query()->create([
            'activity_title' => $validated['activity'],
            'item_name' => $validated['item_name'],
            'supplier' => $validated['supplier'] ?? null,
            'organization_name' => $validated['organization_name'] ?? null,
            'category' => $validated['category'] ?? null,
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'],
            'expense_date' => $validated['expense_date'],
            'receipt_path' => $path,
            'receipt_name' => $file->getClientOriginalName(),
            'receipt_reference' => $validated['receipt_reference'] ?? null,
            'ocr_confidence' => $validated['ocr_confidence'] ?? null,
            'ocr_quality' => $validated['ocr_quality'] ?? 'complete',
            'chain_hash' => $seal['block_hash'],
            'previous_hash' => $seal['previous_hash'],
            'nodes_confirmed' => $seal['nodes_confirmed'],
            'student_confirmed' => true,
            'verification_status' => empty($validated['receipt_reference']) ? 'needs_reference' : 'ready_for_review',
        ]);

        return redirect()
            ->route('office.budget')
            ->with('success', 'Expense sealed to budget blockchain ('.$seal['nodes_confirmed'].'/3 nodes). Hash: '.substr($seal['block_hash'], 0, 12).'…');
    }

    public function financial(): View
    {
        $budget = $this->budgetUtilizationData();
        $account = $this->accountBalanceData($budget);
        $fundAccount = OrgFundAccount::query()->with('sources')->orderByDesc('total_funds')->first();
        $fundSourceFilter = trim((string) request('fund_source', ''));

        $lines = [];
        foreach ($budget['activities'] as $activity) {
            foreach ($activity['expenses'] as $expense) {
                $lines[] = [
                    'activity' => $activity['title'],
                    'item' => $expense['name'],
                    'date' => $expense['date'],
                    'qty' => $expense['qty'],
                    'total' => $expense['total'],
                    'receipt' => $expense['receipt'],
                    'supplier' => $expense['supplier'] ?? ($expense['name'] ?? 'Store'),
                ];
            }
        }

        $sources = $fundAccount?->sources ?? collect();
        if ($fundSourceFilter !== '') {
            $sources = $sources->where('category', $fundSourceFilter)->values();
        }

        $inflow = (int) ($fundAccount?->total_funds_received ?: ($sources->sum('amount') ?: 60000));
        $outflow = (int) BudgetItem::query()->sum('utilized') ?: (int) $account['total_card_disbursement'];

        $selectedSemester = request('semester', '1st Semester');
        $selectedYear = request('academic_year', '2025-2026');
        $reportStatus = OrgReportStatus::query()->where('report_type', 'fr')->latest()->first();

        return view('org.financial', array_merge($this->deskContext(), [
            'activeNav' => 'financial',
            'budget' => $budget,
            'account' => $account,
            'fundAccount' => $fundAccount,
            'fundSources' => $sources,
            'fundSourceFilter' => $fundSourceFilter,
            'fundSourceOptions' => [
                'ssc_fee' => 'SSC Fee',
                'fundraising' => 'Fundraising',
                'sponsorship' => 'Sponsorship',
            ],
            'inflowOutflow' => [
                'labels' => ['Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'inflows' => [
                    (int) round($inflow * 0.35),
                    (int) round($inflow * 0.25),
                    (int) round($inflow * 0.20),
                    (int) round($inflow * 0.12),
                    (int) round($inflow * 0.08),
                ],
                'outflows' => [
                    (int) round($outflow * 0.22),
                    (int) round($outflow * 0.28),
                    (int) round($outflow * 0.24),
                    (int) round($outflow * 0.16),
                    (int) round($outflow * 0.10),
                ],
                'summary_labels' => ['Inflow (Funds Received)', 'Outflow (Disbursements)'],
                'summary_values' => [$inflow, $outflow],
            ],
            'reportStatus' => $reportStatus,
            'lines' => $lines,
            'semesters' => ['1st Semester', '2nd Semester', 'Midyear'],
            'academicYears' => ['2024-2025', '2025-2026', '2026-2027'],
            'selectedSemester' => $selectedSemester,
            'selectedYear' => $selectedYear,
            'frAttachments' => $this->frAttachmentList(),
            'generatedAt' => now()->format('M j, Y g:i A'),
        ]));
    }

    public function printFinancial(): View
    {
        $data = $this->financial()->getData();

        return view('org.financial-print', $data);
    }

    public function accomplishment(): View
    {
        $gender = request('gender'); // male | female | all
        $sdg = trim((string) request('sdg', ''));
        $coreValue = trim((string) request('core_value', ''));

        $rows = OrgActivity::query()
            ->when($sdg !== '', fn ($q) => $q->whereJsonContains('sdg_goals', $sdg))
            ->when($coreValue !== '', fn ($q) => $q->whereJsonContains('core_values', $coreValue))
            ->orderByDesc('starts_at')
            ->get()
            ->map(function (OrgActivity $a) use ($gender) {
                $male = (int) $a->male_participants;
                $female = (int) $a->female_participants;
                $participants = match ($gender) {
                    'male' => $male,
                    'female' => $female,
                    default => $male + $female,
                };

                return [
                    'title' => $a->title,
                    'college' => $a->college,
                    'sdg_goals' => $a->sdg_goals ?: [],
                    'core_values' => $a->core_values ?: [],
                    'male' => $male,
                    'female' => $female,
                    'participants' => $participants,
                    'status' => $a->workflow_status,
                ];
            });

        return view('org.accomplishment', array_merge($this->deskContext(), [
            'activeNav' => 'accomplishment',
            'arAttachments' => $this->arAttachmentList(),
            'semesters' => ['1st Semester', '2nd Semester', 'Midyear'],
            'academicYears' => ['2024-2025', '2025-2026', '2026-2027'],
            'selectedSemester' => request('semester', '1st Semester'),
            'selectedYear' => request('academic_year', '2025-2026'),
            'selectedGender' => $gender ?: 'all',
            'selectedSdg' => $sdg,
            'selectedCoreValue' => $coreValue,
            'sdgOptions' => ['SDG 3', 'SDG 4', 'SDG 5', 'SDG 8', 'SDG 9', 'SDG 11', 'SDG 16'],
            'coreValueOptions' => ['Excellence', 'Integrity', 'Service', 'Innovation', 'Leadership', 'Compassion', 'Teamwork', 'Justice'],
            'accomplishmentRows' => $rows,
            'reportStatus' => OrgReportStatus::query()->where('report_type', 'ar')->latest()->first(),
            'highlights' => [
                'Activities completed this period',
                'Community engagement reach',
                'Officer development sessions',
            ],
        ]));
    }

    public function updates(): View
    {
        return view('org.updates', array_merge($this->deskContext(), [
            'activeNav' => 'updates',
            'announcements' => [
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
            ],
            'templates' => [
                [
                    'id' => 1,
                    'name' => 'Activity Proposal Form',
                    'category' => 'Proposal',
                    'format' => 'PDF',
                    'size' => '245 KB',
                    'downloads' => 142,
                    'icon' => 'file-earmark-pdf-fill',
                    'color' => 'red',
                    'updated' => 'Aug 28, 2026',
                    'description' => 'Official standard proposal form for on-campus and off-campus student org activities.'
                ],
                [
                    'id' => 2,
                    'name' => 'Budget Allocation Sheet',
                    'category' => 'Finance',
                    'format' => 'XLSX',
                    'size' => '180 KB',
                    'downloads' => 98,
                    'icon' => 'file-earmark-spreadsheet-fill',
                    'color' => 'green',
                    'updated' => 'Aug 15, 2026',
                    'description' => 'Pre-formatted spreadsheet template with automated subtotal formulas for itemized org budgeting.'
                ],
                [
                    'id' => 3,
                    'name' => 'Attendance Sheet Template',
                    'category' => 'Forms',
                    'format' => 'DOCX',
                    'size' => '120 KB',
                    'downloads' => 64,
                    'icon' => 'file-earmark-text-fill',
                    'color' => 'blue',
                    'updated' => 'Jul 10, 2026',
                    'description' => 'Standardized sign-in roster for general assemblies, workshops, and student meetings.'
                ],
                [
                    'id' => 4,
                    'name' => 'Accomplishment Report Template',
                    'category' => 'Report',
                    'format' => 'DOCX',
                    'size' => '210 KB',
                    'downloads' => 88,
                    'icon' => 'file-earmark-richtext-fill',
                    'color' => 'gold',
                    'updated' => 'Sep 01, 2026',
                    'description' => 'Comprehensive narrative & metric template for end-of-semester accomplishment submissions.'
                ],
                [
                    'id' => 5,
                    'name' => 'Letter of Request Template',
                    'category' => 'Forms',
                    'format' => 'DOCX',
                    'size' => '95 KB',
                    'downloads' => 52,
                    'icon' => 'file-earmark-text-fill',
                    'color' => 'violet',
                    'updated' => 'Jul 22, 2026',
                    'description' => 'Formal letterhead format for requesting venue permits, equipment loan, and excuses.'
                ],
                [
                    'id' => 6,
                    'name' => 'Liquidation & Expense Matrix',
                    'category' => 'Finance',
                    'format' => 'XLSX',
                    'size' => '155 KB',
                    'downloads' => 76,
                    'icon' => 'file-earmark-spreadsheet-fill',
                    'color' => 'green',
                    'updated' => 'Aug 04, 2026',
                    'description' => 'Official financial liquidation report form with receipt verification table.'
                ],
            ],
        ]));
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

        if ($role === 'oso') {
            $submissions = OrgRenewalSubmission::query()
                ->with('documents')
                ->when($window, fn ($q) => $q->where('renewal_window_id', $window->id))
                ->latest()
                ->get();
        } else {
            $orgName = request('organization_name')
                ?: BudgetItem::query()->whereNotNull('organization_name')->value('organization_name')
                ?: 'Student Organization';

            if ($window) {
                $mySubmission = OrgRenewalSubmission::query()
                    ->with('documents')
                    ->where('renewal_window_id', $window->id)
                    ->where(function ($q) use ($office, $orgName) {
                        $q->where('submitted_by', $office?->id)
                            ->orWhere('organization_name', $orgName);
                    })
                    ->latest('id')
                    ->first();
            }
        }

        return view('org.renewal', array_merge($this->deskContext(), [
            'activeNav' => 'renewal',
            'renewalWindow' => $window,
            'requiredDocs' => $requiredDocs,
            'renewalIsOpen' => $isOpen,
            'renewalSubmissions' => $submissions,
            'myRenewalSubmission' => $mySubmission,
            'orgChoices' => BudgetItem::query()->whereNotNull('organization_name')->distinct()->orderBy('organization_name')->pluck('organization_name'),
        ]));
    }

    public function updateRenewalWindow(Request $request): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless(($office?->office_role ?? '') === 'oso', 403);

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:32'],
            'semester' => ['required', 'string', 'max:40'],
            'is_open' => ['nullable', 'boolean'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $isOpen = $request->boolean('is_open');
        $window = OrgRenewalWindow::query()->latest('id')->first();

        $payload = [
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'is_open' => $isOpen,
            'opens_at' => $validated['opens_at'] ?? ($isOpen ? now() : null),
            'closes_at' => $validated['closes_at'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'required_docs' => $window?->required_docs ?: OrgRenewalWindow::defaultRequiredDocs(),
        ];

        if ($isOpen) {
            $payload['opened_by'] = $office->id;
            $payload['closed_by'] = null;
        } else {
            $payload['closed_by'] = $office->id;
        }

        if ($window) {
            $window->update($payload);
        } else {
            OrgRenewalWindow::query()->create($payload);
        }

        return redirect()
            ->route('office.renewal')
            ->with('success', $isOpen
                ? 'Renewal window is now OPEN for student organizations.'
                : 'Renewal window is LOCKED. SO desks cannot submit.');
    }

    public function storeRenewalSubmission(Request $request): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless(($office?->office_role ?? '') === 'so', 403);

        $window = OrgRenewalWindow::query()->latest('id')->first();
        if (! $window || ! $window->isAcceptingSubmissions()) {
            return back()->withErrors(['renewal' => 'Renewal is locked. Wait for OSO to open the filing window.']);
        }

        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'college' => ['nullable', 'string', 'max:255'],
            'adviser_name' => ['required', 'string', 'max:255'],
            'dean_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'action' => ['nullable', 'in:draft,submit'],
        ]);

        $action = $validated['action'] ?? 'draft';
        $submission = OrgRenewalSubmission::query()->updateOrCreate(
            [
                'renewal_window_id' => $window->id,
                'organization_name' => $validated['organization_name'],
            ],
            [
                'college' => $validated['college'] ?? null,
                'submitted_by' => $office->id,
                'adviser_name' => $validated['adviser_name'],
                'dean_name' => $validated['dean_name'],
                'notes' => $validated['notes'] ?? null,
                'status' => $action === 'submit' ? 'submitted' : 'draft',
                'submitted_at' => $action === 'submit' ? now() : null,
            ]
        );

        if ($action === 'submit') {
            $required = collect($window->requiredDocList())->pluck('key');
            $uploaded = $submission->documents()->pluck('doc_key');
            $missing = $required->diff($uploaded);
            if ($missing->isNotEmpty()) {
                $submission->update(['status' => 'draft', 'submitted_at' => null]);

                return back()
                    ->withInput()
                    ->withErrors(['renewal' => 'Upload all '.$missing->count().' remaining required document(s) before submitting.']);
            }
        }

        return redirect()
            ->route('office.renewal')
            ->with('success', $action === 'submit'
                ? 'Renewal packet submitted for Adviser → Dean → OSO review.'
                : 'Renewal draft saved.');
    }

    public function storeRenewalDocument(Request $request): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless(($office?->office_role ?? '') === 'so', 403);

        $window = OrgRenewalWindow::query()->latest('id')->first();
        if (! $window || ! $window->isAcceptingSubmissions()) {
            return back()->withErrors(['renewal' => 'Renewal is locked by OSO.']);
        }

        $validated = $request->validate([
            'submission_id' => ['required', 'integer', 'exists:org_renewal_submissions,id'],
            'doc_key' => ['required', 'string', 'max:80'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],
        ]);

        $submission = OrgRenewalSubmission::query()
            ->where('id', $validated['submission_id'])
            ->where('renewal_window_id', $window->id)
            ->where('submitted_by', $office->id)
            ->firstOrFail();

        $docMeta = collect($window->requiredDocList())->firstWhere('key', $validated['doc_key']);
        if (! $docMeta) {
            return back()->withErrors(['document' => 'Unknown document type.']);
        }

        $file = $request->file('document');
        $path = $file->store('renewal-documents', 'public');

        OrgRenewalDocument::query()->updateOrCreate(
            [
                'submission_id' => $submission->id,
                'doc_key' => $validated['doc_key'],
            ],
            [
                'title' => $docMeta['title'],
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
            ]
        );

        return redirect()->route('office.renewal')->with('success', $docMeta['title'].' uploaded.');
    }

    public function archive(): View
    {
        $savedFolders = ArchiveFolder::query()
            ->withCount('documents')
            ->latest()
            ->get()
            ->map(fn (ArchiveFolder $folder): array => [
                'id' => $folder->id,
                'name' => $folder->name,
                'org' => $folder->organization_name,
                'semester' => $folder->semester,
                'documents' => $folder->documents_count,
                'icon' => 'folder-fill',
                'color' => $folder->color,
                'is_saved' => true,
            ]);
        $savedDocuments = ArchiveDocument::query()
            ->with('folder')
            ->latest()
            ->get()
            ->map(fn (ArchiveDocument $document): array => [
                'name' => $document->name,
                'size' => $this->formatFileSize($document->file_size),
                'date' => $document->created_at->format('M j, Y'),
                'author' => $document->uploaded_by ?: 'Student Organization',
                'type' => strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION)),
                'url' => asset('storage/'.$document->file_path),
                'folder_id' => $document->archive_folder_id,
                'folder_name' => $document->folder?->name,
            ]);
        $demoFolders = collect([
            ['name' => 'BSIT Society', 'org' => 'BSIT Society', 'semester' => '2nd Semester', 'documents' => 5, 'icon' => 'folder-fill', 'color' => 'violet'],
            ['name' => 'Student Government', 'org' => 'Student Government', 'semester' => '2nd Semester', 'documents' => 3, 'icon' => 'folder-fill', 'color' => 'blue'],
            ['name' => 'Red Cross Youth', 'org' => 'Red Cross Youth', 'semester' => '2nd Semester', 'documents' => 4, 'icon' => 'folder-fill', 'color' => 'red'],
            ['name' => 'Peer Counselors', 'org' => 'Peer Counselors', 'semester' => '2nd Semester', 'documents' => 2, 'icon' => 'folder-fill', 'color' => 'green'],
        ]);
        $demoDocuments = collect([
            ['name' => 'Innovation Fair - Activity Proposal.pdf', 'size' => '2.4 MB', 'date' => 'Apr 6, 2026', 'author' => 'Maria Santos', 'type' => 'PDF', 'folder_name' => 'Student Government'],
            ['name' => 'Innovation Fair - Budget Allocation.xlsx', 'size' => '890 KB', 'date' => 'Apr 6, 2026', 'author' => 'Maria Santos', 'type' => 'XLSX', 'folder_name' => 'Student Government'],
            ['name' => 'Innovation Fair - Attendance Report.pdf', 'size' => '1.2 MB', 'date' => 'Apr 5, 2026', 'author' => 'Ana Gonzales', 'type' => 'PDF', 'folder_name' => 'Student Government'],

            ['name' => 'BSIT CodeFest - Event Guidelines.pdf', 'size' => '1.8 MB', 'date' => 'Apr 3, 2026', 'author' => 'Juan Dela Cruz', 'type' => 'PDF', 'folder_name' => 'BSIT Society'],
            ['name' => 'BSIT CodeFest - Financial Report.xlsx', 'size' => '720 KB', 'date' => 'Apr 3, 2026', 'author' => 'Juan Dela Cruz', 'type' => 'XLSX', 'folder_name' => 'BSIT Society'],
            ['name' => 'BSIT Seminar - Certificate Template.pdf', 'size' => '3.1 MB', 'date' => 'Apr 1, 2026', 'author' => 'Mark Ramos', 'type' => 'PDF', 'folder_name' => 'BSIT Society'],
            ['name' => 'BSIT General Assembly - Minutes.pdf', 'size' => '950 KB', 'date' => 'Mar 28, 2026', 'author' => 'Sarah Lim', 'type' => 'PDF', 'folder_name' => 'BSIT Society'],
            ['name' => 'BSIT Membership Roster 2026.xlsx', 'size' => '540 KB', 'date' => 'Mar 25, 2026', 'author' => 'Juan Dela Cruz', 'type' => 'XLSX', 'folder_name' => 'BSIT Society'],

            ['name' => 'Blood Donation Drive - Activity Permit.pdf', 'size' => '1.5 MB', 'date' => 'Mar 20, 2026', 'author' => 'Elena Cruz', 'type' => 'PDF', 'folder_name' => 'Red Cross Youth'],
            ['name' => 'First Aid Workshop - Program Flow.pdf', 'size' => '820 KB', 'date' => 'Mar 18, 2026', 'author' => 'Elena Cruz', 'type' => 'PDF', 'folder_name' => 'Red Cross Youth'],
            ['name' => 'Youth Leadership Summit - Budget.xlsx', 'size' => '610 KB', 'date' => 'Mar 15, 2026', 'author' => 'Carlos Reyes', 'type' => 'XLSX', 'folder_name' => 'Red Cross Youth'],
            ['name' => 'Red Cross Youth - Annual Accomplishment Report.pdf', 'size' => '4.2 MB', 'date' => 'Mar 10, 2026', 'author' => 'Elena Cruz', 'type' => 'PDF', 'folder_name' => 'Red Cross Youth'],

            ['name' => 'Mental Health Awareness - Proposal.pdf', 'size' => '2.1 MB', 'date' => 'Mar 5, 2026', 'author' => 'Grace Tan', 'type' => 'PDF', 'folder_name' => 'Peer Counselors'],
            ['name' => 'Peer Counseling Session Log.xlsx', 'size' => '430 KB', 'date' => 'Mar 1, 2026', 'author' => 'Grace Tan', 'type' => 'XLSX', 'folder_name' => 'Peer Counselors'],
        ]);

        $activityFolders = OrgActivity::query()
            ->orderBy('title')
            ->get()
            ->map(function (OrgActivity $activity) {
                $docs = max(2, (int) ActivityComplianceDoc::query()->where('org_activity_id', $activity->id)->count() + 2);

                return [
                    'id' => 'activity-'.$activity->id,
                    'name' => $activity->title,
                    'org' => $activity->organization_name ?: ($activity->college ?: 'Student Organization'),
                    'semester' => 'AY 2025-2026',
                    'documents' => $docs,
                    'icon' => 'folder-fill',
                    'color' => match ($activity->workflow_status) {
                        'oc_approved' => 'green',
                        'returned' => 'gold',
                        default => 'red',
                    },
                    'is_saved' => false,
                    'is_activity' => true,
                ];
            });

        $folders = $activityFolders->concat($savedFolders)->concat($demoFolders);
        if ($folders->isEmpty()) {
            $folders = $demoFolders;
        }

        return view('org.archive', array_merge($this->deskContext(), [
            'activeNav' => 'archive',
            'totalDocuments' => 28 + $savedDocuments->count() + $activityFolders->sum('documents'),
            'totalFolders' => $folders->count(),
            'currentSemester' => '2nd Semester',
            'folders' => $folders,
            'activityFolders' => $activityFolders,
            'documents' => $savedDocuments->concat($demoDocuments),
            'savedFolders' => $savedFolders,
            'selectedFolder' => $activityFolders->first()['name'] ?? 'BSIT Society',
        ]));
    }

    public function tosa(): View
    {
        $subsection = request('subsection', 'all');
        $applicants = TosaApplicant::query()
            ->when($subsection !== 'all', fn ($q) => $q->where('subsection', $subsection))
            ->orderBy('full_name')
            ->get();

        return view('org.tosa', array_merge($this->deskContext(), [
            'activeNav' => 'tosa',
            'tosaApplicants' => $applicants,
            'tosaSubsections' => [
                'all' => 'All',
                'pending' => 'Pending',
                'screening' => 'Screening',
                'interview' => 'Interview',
                'accepted' => 'Accepted',
                'rejected' => 'Rejected',
            ],
            'selectedSubsection' => $subsection,
            'subsectionCounts' => TosaApplicant::query()
                ->selectRaw('subsection, COUNT(*) as total')
                ->groupBy('subsection')
                ->pluck('total', 'subsection'),
        ]));
    }

    public function storeArchiveFolder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'in:1st Semester,2nd Semester,Midyear'],
            'color' => ['required', 'in:red,green,blue,violet,gold'],
        ]);
        ArchiveFolder::query()->create($validated);

        return redirect()->route('office.archive')->with('success', 'Archive folder created.');
    }

    public function storeArchiveDocument(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'archive_folder_id' => ['required', 'exists:archive_folders,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,png,jpg,jpeg', 'max:20480'],
        ]);
        $file = $request->file('document');
        $path = $file->store("archive/{$validated['archive_folder_id']}", 'public');

        ArchiveDocument::query()->create([
            'archive_folder_id' => $validated['archive_folder_id'],
            'name' => $validated['name'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::guard('office')->user()?->name,
        ]);

        return redirect()->route('office.archive')->with('success', 'Document uploaded to the archive.');
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
    private function budgetUtilizationData(): array
    {
        $dbAllocated = (int) BudgetItem::query()->sum('allocated');
        $dbUsed = (int) BudgetItem::query()->sum('utilized');

        $allocated = $dbAllocated > 0 ? $dbAllocated : 81000;
        $used = $dbUsed > 0 ? $dbUsed : 25250;
        $remaining = max(0, $allocated - $used);
        $percent = $allocated > 0 ? (int) round(($used / $allocated) * 100) : 0;

        $activities = [
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
        $isSubmitting = $request->input('submission_action') === 'submit';
        $activityType = $request->input('activity_type', 'in_campus');

        $rules = [
            'activity_type' => ['required', 'in:in_campus,local_off_campus'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'rationale' => ['nullable', 'string', 'max:10000'],
            'objectives' => ['nullable', 'string', 'max:10000'],
            'participants' => ['nullable', 'string', 'max:10000'],
            'safety_plan' => ['nullable', 'string', 'max:10000'],
            'conditions' => ['nullable', 'array'],
            'conditions.*' => ['boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
        ];

        if ($activityType === 'in_campus') {
            $rules['programme_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['project_proposal_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['budget_proposal_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['faculty_in_charge_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['medical_request_html'] = ['nullable', 'string'];
            $rules['insurance_request_html'] = ['nullable', 'string'];
            $rules['resolution_html'] = ['nullable', 'string'];
            $rules['sample_letter_html'] = ['nullable', 'string'];
            $rules['wpcf_html'] = ['nullable', 'string'];
            $rules['approved_plan_html'] = ['nullable', 'string'];
            $rules['class_schedule_html'] = ['nullable', 'string'];
            $rules['meeting_minutes_html'] = ['nullable', 'string'];
        } else {
            $rules['off_campus_req_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['cert_compliance_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['ched_report_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['travel_matrix_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['passenger_matrix_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['course_activities_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
            $rules['faculty_in_charge_html'] = [$isSubmitting ? 'required' : 'nullable', 'string'];
        }

        $validated = $request->validate($rules, [
            'programme_html.required' => 'Complete the editable Programme before submitting.',
            'project_proposal_html.required' => 'Complete the editable Project Proposal before submitting.',
            'budget_proposal_html.required' => 'Complete the editable Budget Proposal before submitting.',
            'faculty_in_charge_html.required' => 'Complete the Faculty-in-Charge document before submitting.',
            'off_campus_req_html.required' => 'Complete the Request for Conduct of Local Off-Campus Activities (FO-REQ-09) before submitting.',
            'cert_compliance_html.required' => 'Complete the Certificate of Compliance before submitting.',
            'ched_report_html.required' => 'Complete the CHED Compliance Report before submitting.',
            'travel_matrix_html.required' => 'Complete the Matrix of Travel and Tour before submitting.',
            'passenger_matrix_html.required' => 'Complete the Matrix of Passenger before submitting.',
            'course_activities_html.required' => 'Complete the Course Activities schedule before submitting.',
        ]);

        if ($activityType === 'in_campus') {
            $requiredUploads = [];
            $conditionalUploads = [
                'medical_clearance' => 'medical',
                'insurance' => 'insurance',
                'curriculum_vitae' => 'guest_speaker',
                'waiver' => 'late_or_weekend',
                'reservation_form' => 'university_facility',
            ];
        } else {
            $requiredUploads = ['parents_consent', 'checklist_requirements'];
            $conditionalUploads = [
                'medical_clearance' => 'medical',
                'insurance' => 'insurance',
                'vehicle_registration' => 'transport',
                'tour_operator_cert' => 'tour_operator',
            ];
        }

        if ($isSubmitting) {
            $existing = $submission?->attachments ?? [];
            $files = $request->file('attachments', []);
            $missing = collect($requiredUploads)
                ->filter(fn (string $key): bool => ! isset($files[$key]) && empty($existing[$key]['path']));

            foreach ($conditionalUploads as $key => $condition) {
                if ($request->boolean("conditions.{$condition}") && ! isset($files[$key]) && empty($existing[$key]['path'])) {
                    $missing->push($key);
                }
            }

            if ($missing->isNotEmpty()) {
                return back()
                    ->withInput()
                    ->withErrors(['attachments' => 'Upload the required checklist items: '.implode(', ', $missing->map(fn (string $item) => str($item)->replace('_', ' ')->title())->all()).'.']);
            }
        }

        $submission = DB::transaction(function () use ($request, $validated, $submission, $isSubmitting, $activityType): InCampusActivitySubmission {
            $activity = $submission?->activity ?? new OrgActivity();
            $activity->fill([
                'title' => $validated['title'],
                'description' => $validated['rationale'] ?? null,
                'location' => $validated['location'],
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'] ?? null,
                'status' => $isSubmitting ? 'upcoming' : 'draft',
            ]);
            $activity->save();

            $submission ??= new InCampusActivitySubmission();
            $attachments = $submission->attachments ?? [];
            $attachments['conditions'] = collect($validated['conditions'] ?? [])
                ->map(fn (mixed $value): bool => (bool) $value)
                ->all();

            $folder = $activityType === 'local_off_campus' ? 'off-campus-activities' : 'in-campus-activities';
            foreach ($request->file('attachments', []) as $key => $file) {
                $attachments[$key] = [
                    'path' => $file->store("{$folder}/{$activity->id}", 'public'),
                    'name' => $file->getClientOriginalName(),
                    'uploaded_at' => now()->toIso8601String(),
                ];
            }

            $submission->fill([
                'org_activity_id' => $activity->id,
                'status' => $isSubmitting ? 'submitted' : 'draft',
                'activity_type' => $activityType,
                'organization_name' => $validated['organization_name'] ?? null,
                'rationale' => $validated['rationale'] ?? null,
                'objectives' => $validated['objectives'] ?? null,
                'participants' => $validated['participants'] ?? null,
                'safety_plan' => $validated['safety_plan'] ?? null,
                'programme_html' => $validated['programme_html'] ?? null,
                'project_proposal_html' => $validated['project_proposal_html'] ?? null,
                'budget_proposal_html' => $validated['budget_proposal_html'] ?? null,
                'faculty_in_charge_html' => $validated['faculty_in_charge_html'] ?? null,
                'medical_request_html' => $validated['medical_request_html'] ?? null,
                'insurance_request_html' => $validated['insurance_request_html'] ?? null,
                'resolution_html' => $validated['resolution_html'] ?? null,
                'sample_letter_html' => $validated['sample_letter_html'] ?? null,
                'wpcf_html' => $validated['wpcf_html'] ?? null,
                'approved_plan_html' => $validated['approved_plan_html'] ?? null,
                'class_schedule_html' => $validated['class_schedule_html'] ?? null,
                'meeting_minutes_html' => $validated['meeting_minutes_html'] ?? null,
                'off_campus_req_html' => $validated['off_campus_req_html'] ?? null,
                'cert_compliance_html' => $validated['cert_compliance_html'] ?? null,
                'ched_report_html' => $validated['ched_report_html'] ?? null,
                'travel_matrix_html' => $validated['travel_matrix_html'] ?? null,
                'passenger_matrix_html' => $validated['passenger_matrix_html'] ?? null,
                'course_activities_html' => $validated['course_activities_html'] ?? null,
                'attachments' => $attachments,
                'submitted_at' => $isSubmitting ? now() : null,
            ]);
            $submission->save();

            return $submission;
        });

        $typeLabel = $activityType === 'local_off_campus' ? 'local off-campus' : 'in-campus';

        return redirect()
            ->route('office.activities')
            ->with('success', $isSubmitting
                ? "Your {$typeLabel} activity was submitted for review."
                : "Your {$typeLabel} activity changes have been saved.");
    }

    /**
     * @return list<array{key: string, title: string, description: string, group: string, condition: ?string}>
     */
    private function inCampusRequirements(): array
    {
        return [
            ['key' => 'wpcf', 'title' => 'Waste Policy Compliance Form (WPCF)', 'description' => 'Editable Waste Policy Compliance Form.', 'group' => 'editor', 'condition' => null],
            ['key' => 'programme', 'title' => 'Programme', 'description' => 'Editable programme and schedule of activities.', 'group' => 'editor', 'condition' => null],
            ['key' => 'project_proposal', 'title' => 'Project Proposal', 'description' => 'Editable proposal prepared by the president and noted by the adviser.', 'group' => 'editor', 'condition' => null],
            ['key' => 'budget_proposal', 'title' => 'Budget Proposal', 'description' => 'Editable funding requirements and source of funds.', 'group' => 'editor', 'condition' => null],
            ['key' => 'faculty_in_charge', 'title' => 'Faculty-in-Charge', 'description' => 'Editable designation and before/during/after duties.', 'group' => 'editor', 'condition' => null],
            ['key' => 'medical_request', 'title' => 'Medical Request Sample Letter', 'description' => 'Editable medical request letter for university clinic or medical personnel.', 'group' => 'editor', 'condition' => null],
            ['key' => 'insurance_request', 'title' => 'Insurance Request Sample Letter', 'description' => 'Editable insurance request sample letter for participant coverage.', 'group' => 'editor', 'condition' => null],
            ['key' => 'resolution', 'title' => 'Resolution of the Organization', 'description' => 'Editable officer-signed approval resolution for the activity.', 'group' => 'editor', 'condition' => null],
            ['key' => 'sample_letter', 'title' => 'Sample Request Letter', 'description' => 'Editable general activity request letter to university authorities.', 'group' => 'editor', 'condition' => null],
            ['key' => 'approved_plan', 'title' => 'Approved Plan of Activities', 'description' => 'Editable copy of the plan submitted in the renewal or recognition process.', 'group' => 'editor', 'condition' => null],
            ['key' => 'class_schedule', 'title' => 'Class Schedule & Participant Roster', 'description' => 'Editable participant class schedules and participant manifest.', 'group' => 'editor', 'condition' => null],
            ['key' => 'meeting_minutes', 'title' => 'Minutes and Attendance', 'description' => 'Editable briefing and consultation record with officers, students, or faculty.', 'group' => 'editor', 'condition' => null],
            ['key' => 'medical_clearance', 'title' => 'Medical Clearance', 'description' => 'Required for physical activities, team-building, sports, or related tasks.', 'group' => 'conditional', 'condition' => 'medical'],
            ['key' => 'insurance', 'title' => 'Insurance', 'description' => 'Required when the activity involves travel or physical activities.', 'group' => 'conditional', 'condition' => 'insurance'],
            ['key' => 'curriculum_vitae', 'title' => 'Curriculum Vitae', 'description' => 'Required when judges or guest speakers are involved.', 'group' => 'conditional', 'condition' => 'guest_speaker'],
            ['key' => 'waiver', 'title' => 'Notarized Waiver / Parental Consent', 'description' => 'Required for activities beyond 10:00 PM or scheduled on weekends.', 'group' => 'conditional', 'condition' => 'late_or_weekend'],
            ['key' => 'reservation_form', 'title' => 'Reservation Form', 'description' => 'Required when using a university facility.', 'group' => 'conditional', 'condition' => 'university_facility'],
        ];
    }

    /**
     * @return list<array{key: string, title: string, description: string, group: string, condition: ?string}>
     */
    private function localOffCampusRequirements(): array
    {
        return [
            ['key' => 'off_campus_req', 'title' => 'Request for Conduct of Local Off-Campus Activities (FO-REQ-09)', 'description' => 'Editable official request form for off-campus activities.', 'group' => 'editor', 'condition' => null],
            ['key' => 'cert_compliance', 'title' => 'Certificate of Compliance', 'description' => 'Editable certificate guaranteeing compliance with safety guidelines.', 'group' => 'editor', 'condition' => null],
            ['key' => 'ched_report', 'title' => 'CHED Compliance Report', 'description' => 'Editable report aligning with CHED Memorandum Orders on off-campus activities.', 'group' => 'editor', 'condition' => null],
            ['key' => 'travel_matrix', 'title' => 'Matrix of Travel and Tour', 'description' => 'Editable detailed itinerary, destination list, and travel schedule.', 'group' => 'editor', 'condition' => null],
            ['key' => 'passenger_matrix', 'title' => 'Format for Matrix of Passenger', 'description' => 'Editable passenger list with emergency contacts and seat manifests.', 'group' => 'editor', 'condition' => null],
            ['key' => 'course_activities', 'title' => 'Course Activities Schedule', 'description' => 'Editable course activity plan and academic learning objectives.', 'group' => 'editor', 'condition' => null],
            ['key' => 'faculty_in_charge', 'title' => 'Faculty-in-Charge Designation', 'description' => 'Editable faculty designation with supervising responsibilities.', 'group' => 'editor', 'condition' => null],
            ['key' => 'parents_consent', 'title' => 'Parent’s Consent Form (Waiver)', 'description' => 'BatStateU FO-SOA-03 signed parent/guardian consent forms.', 'group' => 'governance', 'condition' => null],
            ['key' => 'checklist_requirements', 'title' => 'Signed Checklist of Requirements', 'description' => 'Official checklist of requirements for local off-campus activities.', 'group' => 'governance', 'condition' => null],
            ['key' => 'medical_clearance', 'title' => 'Medical Clearance / Health Declaration', 'description' => 'Required for strenuous off-campus activities or field trips.', 'group' => 'conditional', 'condition' => 'medical'],
            ['key' => 'insurance', 'title' => 'Group Personal Accident Insurance', 'description' => 'Insurance policy covering all participants for off-campus travel.', 'group' => 'conditional', 'condition' => 'insurance'],
            ['key' => 'vehicle_registration', 'title' => 'Vehicle Registration & Driver License', 'description' => 'Required when hiring private vehicle or bus transportation.', 'group' => 'conditional', 'condition' => 'transport'],
            ['key' => 'tour_operator_cert', 'title' => 'DOT Tour Operator Certificate & Contract', 'description' => 'Required when partnering with a third-party tour agency.', 'group' => 'conditional', 'condition' => 'tour_operator'],
        ];
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
            default => ['title' => 'Student Organization', 'role' => 'Student Org Representative'],
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
        $db = OrgActivity::query()->orderByDesc('starts_at')->limit(12)->get();

        if ($db->isNotEmpty()) {
            return $db->map(function (OrgActivity $activity) use ($workflow): array {
                $statusKey = $activity->workflow_status ?: 'created';

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
                    'upcoming_at' => optional($activity->starts_at)?->format('Y-m-d H:i:s'),
                    'docs' => ActivityComplianceDoc::query()->where('org_activity_id', $activity->id)->pluck('title')->all(),
                    'note' => $activity->returned_to ? 'Returned to '.$activity->returned_to : $activity->description,
                    'returned_to' => $activity->returned_to,
                ];
            })->values()->all();
        }

        $demo = [
            [
                'title' => 'Innovation Fair Booth Series',
                'status' => 'OC Approved',
                'status_key' => 'oc_approved',
                'stage' => 6,
                'stages' => 6,
                'date' => 'Jul 4, 2026',
                'budget' => 15000,
                'location' => 'Gymnasium',
                'upcoming_at' => '2026-07-04 09:00:00',
                'docs' => ['Activity Proposal.pdf', 'Budget Breakdown.xlsx'],
                'note' => 'Booth setup open for student orientation.',
            ],
            [
                'title' => 'Leadership Summit 2026',
                'status' => 'OSO Review',
                'status_key' => 'oso_review',
                'stage' => 3,
                'stages' => 6,
                'date' => 'Sep 20, 2026',
                'budget' => 75000,
                'location' => 'Taal Building',
                'upcoming_at' => '2026-09-20 06:00:00',
                'docs' => ['Concept Note.pdf'],
                'note' => null,
            ],
            [
                'title' => 'Campus Wellness Week',
                'status' => 'SDO Review',
                'status_key' => 'sdo_review',
                'stage' => 4,
                'stages' => 6,
                'date' => 'Sep 8, 2026',
                'budget' => 42500,
                'location' => 'Gymnasium',
                'upcoming_at' => '2026-09-08 10:00:00',
                'docs' => ['Wellness Plan.pdf'],
                'note' => null,
            ],
            [
                'title' => 'Student Media Workshop',
                'status' => 'Returned for Revision',
                'status_key' => 'returned',
                'stage' => 1,
                'stages' => 6,
                'date' => 'Oct 3, 2026',
                'budget' => 9800,
                'location' => 'Taal Building',
                'upcoming_at' => '2026-10-03 13:00:00',
                'docs' => ['Workshop Outline.pdf'],
                'note' => 'Returned to so for revision.',
                'returned_to' => 'so',
            ],
        ];

        return $demo;
    }

    private function orgActivitiesList(): array
    {
        $workflow = app(OrgWorkflowService::class);
        $db = OrgActivity::query()->orderByDesc('starts_at')->get();

        if ($db->isEmpty()) {
            return $this->demoOrgActivitiesList();
        }

        return $db->map(function (OrgActivity $activity) use ($workflow): array {
            $statusKey = $activity->workflow_status ?: 'created';
            $filter = match (true) {
                $statusKey === 'oc_approved' => 'approved',
                $statusKey === 'returned' => 'returned',
                in_array($statusKey, ['oso_review', 'sdo_review', 'ovcaa_review', 'college_review'], true) => 'for_approval',
                default => 'in_review',
            };
            $badge = match ($filter) {
                'approved' => 'purple',
                'returned' => 'red',
                'for_approval' => 'yellow',
                default => 'blue',
            };

            $docs = ActivityComplianceDoc::query()
                ->where('org_activity_id', $activity->id)
                ->get()
                ->map(function (ActivityComplianceDoc $doc): array {
                    $style = match ($doc->status) {
                        'approved' => 'green',
                        'returned' => 'red',
                        default => 'yellow',
                    };

                    return [
                        'id' => $doc->id,
                        'name' => $doc->title,
                        'type' => 'pdf',
                        'status' => ucfirst($doc->status ?: 'pending'),
                        'status_style' => $style,
                        'uploaded_on' => optional($doc->updated_at)->format('M j, Y g:i A') ?? 'Recent',
                        'note' => $doc->remarks,
                    ];
                })
                ->values()
                ->all();

            if ($docs === []) {
                $docs = [[
                    'name' => 'Activity Proposal',
                    'type' => 'pdf',
                    'status' => 'Pending',
                    'status_style' => 'yellow',
                    'uploaded_on' => optional($activity->updated_at)->format('M j, Y g:i A') ?? 'Recent',
                    'note' => null,
                ]];
            }

            return [
                'id' => $activity->id,
                'slug' => (string) $activity->id,
                'title' => $activity->title,
                'status' => $workflow->label($statusKey),
                'status_key' => $statusKey,
                'badge_style' => $badge,
                'filter_category' => $filter,
                'date' => optional($activity->starts_at)->format('M j, Y') ?? 'TBA',
                'location' => $activity->location ?: 'TBA',
                'timestamp_note' => optional($activity->updated_at)->format('M j, Y g:i A') ?? 'Synced from database',
                'activity_type' => ($activity->activity_scope === 'local_off_campus') ? 'Off-Campus Activity' : 'In-Campus Activity',
                'start_time' => optional($activity->starts_at)->format('F j, Y g:i A') ?? 'TBA',
                'end_time' => optional($activity->ends_at)->format('F j, Y g:i A') ?? 'TBA',
                'organization' => $activity->organization_name ?: 'Student Organization',
                'college' => $activity->college,
                'program' => $activity->program,
                'rationale' => $activity->description ?: 'Activity proposal submitted through OrgChain desk.',
                'objectives' => array_values(array_filter([
                    $activity->college ? 'College: '.$activity->college : null,
                    ! empty($activity->sdg_goals) ? 'SDG: '.implode(', ', $activity->sdg_goals) : null,
                    ! empty($activity->core_values) ? 'Core values: '.implode(', ', $activity->core_values) : null,
                ])) ?: ['Complete compliance documents and secure office endorsements.'],
                'documents' => $docs,
                'workflow_status' => $statusKey,
                'returned_to' => $activity->returned_to,
            ];
        })->values()->all();
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
        $workflow = app(OrgWorkflowService::class);

        try {
            $workflow->advance($activity, $role);
            $workflow->syncSubmission($activity);
        } catch (\Throwable $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Activity advanced to '.$workflow->label($activity->workflow_status).'.');
    }

    public function returnActivity(Request $request, OrgActivity $activity): RedirectResponse
    {
        $validated = $request->validate([
            'returned_to' => ['required', 'in:so,college_reviewer,oso,sdo'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $workflow = app(OrgWorkflowService::class);
        $workflow->returnForRevision($activity, $validated['returned_to'], $validated['remarks'] ?? null);
        $workflow->syncSubmission($activity);

        return back()->with('success', 'Activity returned to '.$validated['returned_to'].' for revision.');
    }

    public function updateComplianceDoc(Request $request, OrgActivity $activity, ActivityComplianceDoc $doc): RedirectResponse
    {
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

    public function updateFunds(Request $request, OrgFundAccount $account): RedirectResponse
    {
        $validated = $request->validate([
            'total_funds' => ['required', 'integer', 'min:0'],
            'beginning_balance' => ['required', 'integer', 'min:0'],
            'total_funds_received' => ['required', 'integer', 'min:0'],
        ]);

        $account->update($validated);

        return back()->with('success', 'Total funds and balances updated.');
    }

    public function updateReportStatus(Request $request, OrgReportStatus $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,ready_for_review,oso_review,sdo_review,ovcaa_review,verified,returned'],
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

    public function updateTosaSubsection(Request $request, TosaApplicant $applicant): RedirectResponse
    {
        $validated = $request->validate([
            'subsection' => ['required', 'in:pending,screening,interview,accepted,rejected'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $applicant->update($validated);

        return back()->with('success', 'Applicant moved to '.$validated['subsection'].'.');
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

        if (array_sum($counts) === 0) {
            $labels = ['Created', 'OSO Review', 'SDO Review', 'OVCAA Review', 'OC Approved'];
            $counts = [2, 2, 1, 1, 2];
        }

        $months = collect(range(0, 5))->map(fn ($i) => now()->subMonths(5 - $i)->format('M'));
        $trend = $months->map(fn ($m, $i) => 4 + $i + ($counts[$i % count($counts)] ?? 1))->values();

        return [
            'approvalLabels' => $labels,
            'approvalCounts' => $counts,
            'trendLabels' => $months->values(),
            'trendCounts' => $trend,
            'typeLabels' => ['In-Campus', 'Off-Campus', 'Compliance', 'Reports'],
            'typeCounts' => [
                $activities->where('activity_scope', 'in_campus')->count() ?: 5,
                $activities->where('activity_scope', 'local_off_campus')->count() ?: 2,
                ActivityComplianceDoc::query()->count() ?: 8,
                OrgReportStatus::query()->count() ?: 3,
            ],
        ];
    }
}

