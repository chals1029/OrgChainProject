<?php

namespace App\Http\Controllers;

use App\Models\ArchiveDocument;
use App\Models\ArchiveFolder;
use App\Models\BudgetItem;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivity;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
            $upcoming = collect($pipeline)->firstWhere('title', 'Volunteer Appreciation Day')
                ?? collect($pipeline)->first();
        }

        $approved = collect($pipeline)->whereIn('status_key', ['ovcaa_approved', 'completed'])->count();
        $pending = collect($pipeline)->whereIn('status_key', ['created', 'verification', 'pending', 'returned'])->count();
        $expenses = (int) BudgetItem::query()->sum('utilized');

        return view('org.dashboard', array_merge($this->deskContext(), [
            'activeNav' => 'dashboard',
            'stats' => [
                'total' => count($pipeline),
                'approved' => $approved,
                'pending' => $pending,
                'expenses' => $expenses > 0 ? $expenses : 25250,
            ],
            'upcoming' => $upcoming,
            'tracker' => array_slice($pipeline, 0, 4),
            'updates' => $pipeline,
        ]));
    }

    public function analytics(): View
    {
        $pipeline = $this->pipelineActivities();

        $byStatus = collect($pipeline)
            ->groupBy('status_key')
            ->map(fn ($rows) => $rows->count())
            ->all();

        return view('org.analytics', array_merge($this->deskContext(), [
            'activeNav' => 'analytics',
            'byStatus' => $byStatus,
            'pipeline' => $pipeline,
            'budgetItems' => BudgetItem::query()->orderByDesc('utilized')->limit(6)->get(),
        ]));
    }

    public function activities(): View
    {
        return view('org.activities', array_merge($this->deskContext(), [
            'activeNav' => 'activities',
            'activities' => $this->pipelineActivities(),
            'submissions' => InCampusActivitySubmission::query()
                ->with('activity')
                ->latest()
                ->get(),
        ]));
    }

    public function createActivity(): View
    {
        return view('org.activity-create', array_merge($this->deskContext(), [
            'activeNav' => 'activities',
            'submission' => new InCampusActivitySubmission([
                'activity_type' => 'in_campus',
                'status' => 'draft',
            ]),
            'inCampusRequirements' => $this->inCampusRequirements(),
            'offCampusRequirements' => $this->localOffCampusRequirements(),
        ]));
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
        return view('org.budget', array_merge($this->deskContext(), [
            'activeNav' => 'budget',
            'budget' => $this->budgetUtilizationData(),
            'receiptReviews' => ExpenseReceiptReview::query()->latest()->limit(8)->get(),
        ]));
    }

    public function storeReceiptReview(Request $request): RedirectResponse
    {
        if ($request->input('receipt_detected') === '0') {
            return back()
                ->withInput()
                ->withErrors(['receipt' => 'Expense submission rejected: No valid receipt was detected in the uploaded file.']);
        }

        $validated = $request->validate([
            'activity' => ['required', 'string', 'max:255'],
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_cost' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'receipt' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'receipt_reviewed' => ['accepted'],
            'ocr_confidence' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], [
            'receipt_reviewed.accepted' => 'Review the detected receipt details and confirm that they match the original receipt.',
        ]);

        $file = $request->file('receipt');
        $path = $file->store('expense-receipts', 'public');

        ExpenseReceiptReview::query()->create([
            'activity_title' => $validated['activity'],
            'item_name' => $validated['item_name'],
            'category' => $validated['category'] ?? null,
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'],
            'expense_date' => $validated['expense_date'],
            'receipt_path' => $path,
            'receipt_name' => $file->getClientOriginalName(),
            'ocr_confidence' => $validated['ocr_confidence'] ?? null,
            'student_confirmed' => true,
            'verification_status' => 'ready_for_review',
        ]);

        return redirect()
            ->route('office.budget')
            ->with('success', 'Expense receipt saved and sent for review.');
    }

    public function financial(): View
    {
        $budget = $this->budgetUtilizationData();
        $account = $this->accountBalanceData($budget);

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
                ];
            }
        }

        $selectedSemester = request('semester', '1st Semester');
        $selectedYear = request('academic_year', '2025-2026');

        return view('org.financial', array_merge($this->deskContext(), [
            'activeNav' => 'financial',
            'budget' => $budget,
            'account' => $account,
            'lines' => $lines,
            'semesters' => ['1st Semester', '2nd Semester', 'Midyear'],
            'academicYears' => ['2024-2025', '2025-2026', '2026-2027'],
            'selectedSemester' => $selectedSemester,
            'selectedYear' => $selectedYear,
            'frAttachments' => $this->frAttachmentList(),
            'generatedAt' => now()->format('M j, Y g:i A'),
        ]));
    }

    public function accomplishment(): View
    {
        return view('org.accomplishment', array_merge($this->deskContext(), [
            'activeNav' => 'accomplishment',
            'arAttachments' => $this->arAttachmentList(),
            'semesters' => ['1st Semester', '2nd Semester', 'Midyear'],
            'academicYears' => ['2024-2025', '2025-2026', '2026-2027'],
            'selectedSemester' => request('semester', '1st Semester'),
            'selectedYear' => request('academic_year', '2025-2026'),
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
                    'title' => 'Deadline Extension for Activity Proposals',
                    'body' => 'The deadline for submitting activity proposals for the 2nd Semester has been extended to April 15, 2026. All organizations must comply with the updated requirements.',
                    'author' => 'OSO Admin',
                    'time' => '2 hours ago',
                    'priority' => 'high',
                ],
                [
                    'title' => 'New Template for Budget Allocation',
                    'body' => 'A revised Budget Allocation template is now available for download. All new proposals must use this updated format.',
                    'author' => 'OSO Admin',
                    'time' => '1 day ago',
                    'priority' => 'normal',
                ],
                [
                    'title' => 'Reminder: OSO Clearance for Events',
                    'body' => 'All organizations must secure OSO clearance at least 2 weeks before the scheduled event date. Please plan accordingly.',
                    'author' => 'OSO Admin',
                    'time' => '3 days ago',
                    'priority' => 'normal',
                ],
                [
                    'title' => 'Welcome to the New Semester',
                    'body' => 'We welcome all student organizations to the 1st Semester of AY 2026-2027. Please review the updated guidelines for activity proposals.',
                    'author' => 'OSO Admin',
                    'time' => '1 week ago',
                    'priority' => 'normal',
                ],
            ],
            'templates' => [
                ['name' => 'Activity Proposal Form', 'category' => 'Proposal', 'size' => '245 KB', 'downloads' => 42, 'icon' => 'file-earmark-pdf-fill', 'color' => 'red'],
                ['name' => 'Budget Allocation Sheet', 'category' => 'Finance', 'size' => '180 KB', 'downloads' => 38, 'icon' => 'file-earmark-spreadsheet-fill', 'color' => 'green'],
                ['name' => 'Attendance Sheet Template', 'category' => 'Forms', 'size' => '120 KB', 'downloads' => 28, 'icon' => 'file-earmark-text-fill', 'color' => 'blue'],
                ['name' => 'Accomplishment Report Template', 'category' => 'Report', 'size' => '210 KB', 'downloads' => 31, 'icon' => 'file-earmark-richtext-fill', 'color' => 'gold'],
                ['name' => 'Letter of Request Template', 'category' => 'Forms', 'size' => '95 KB', 'downloads' => 25, 'icon' => 'file-earmark-text-fill', 'color' => 'violet'],
            ],
        ]));
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

        return view('org.archive', array_merge($this->deskContext(), [
            'activeNav' => 'archive',
            'totalDocuments' => 28 + $savedDocuments->count(),
            'totalFolders' => 8 + $savedFolders->count(),
            'currentSemester' => '2nd Semester',
            'folders' => $savedFolders->concat($demoFolders),
            'documents' => $savedDocuments->concat($demoDocuments),
            'savedFolders' => $savedFolders,
            'selectedFolder' => 'BSIT Society',
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
            ->route('office.activities.edit', $submission)
            ->with('success', $isSubmitting
                ? "Your {$typeLabel} activity was submitted for review."
                : "Your {$typeLabel} activity draft was saved.");
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
        $db = OrgActivity::query()->orderByDesc('starts_at')->limit(3)->get();

        $demo = [
            [
                'title' => 'Innovation Fair Booth Series',
                'status' => 'OVCAA Approved',
                'status_key' => 'ovcaa_approved',
                'stage' => 4,
                'stages' => 4,
                'date' => 'Jul 4, 2026',
                'budget' => 15000,
                'location' => 'Gymnasium',
                'upcoming_at' => '2026-07-04 09:00:00',
                'docs' => ['Activity Proposal.pdf', 'Budget Breakdown.xlsx', 'Risk Assessment.pdf'],
                'note' => 'Booth setup and project exhibits open for student orientation.',
            ],
            [
                'title' => 'Leadership Summit 2026',
                'status' => 'Created',
                'status_key' => 'created',
                'stage' => 1,
                'stages' => 4,
                'date' => 'Aug 12, 2026',
                'budget' => 85000,
                'location' => 'Taal Building',
                'upcoming_at' => '2026-08-12 09:00:00',
                'docs' => ['Concept Note.pdf', 'Speaker Lineup.pdf'],
                'note' => null,
            ],
            [
                'title' => 'Volunteer Appreciation Day',
                'status' => 'Completed',
                'status_key' => 'completed',
                'stage' => 4,
                'stages' => 4,
                'date' => 'Mar 2, 2026',
                'budget' => 12500,
                'location' => 'Mini Forest',
                'upcoming_at' => '2026-03-02 14:00:00',
                'force_upcoming' => true,
                'docs' => ['Program Flow.pdf', 'Attendance Sheet.pdf', 'Expense Report.pdf'],
                'note' => null,
                'pending_label' => 'Pending',
                'archive_ready' => 2,
            ],
            [
                'title' => 'Campus Wellness Week',
                'status' => 'Verification',
                'status_key' => 'verification',
                'stage' => 2,
                'stages' => 4,
                'date' => 'Sep 8, 2026',
                'budget' => 22000,
                'location' => 'Gymnasium',
                'upcoming_at' => '2026-09-08 10:00:00',
                'docs' => ['Wellness Plan.pdf', 'Partner MOA.pdf'],
                'note' => null,
            ],
            [
                'title' => 'Student Media Workshop',
                'status' => 'Returned for Revision',
                'status_key' => 'returned',
                'stage' => 1,
                'stages' => 4,
                'date' => 'Oct 3, 2026',
                'budget' => 9800,
                'location' => 'Taal Building',
                'upcoming_at' => '2026-10-03 13:00:00',
                'docs' => ['Workshop Outline.pdf'],
                'note' => 'Returned for revision. Update and resubmit.',
            ],
        ];

        if ($db->isEmpty()) {
            return $demo;
        }

        return $demo;
    }
}
