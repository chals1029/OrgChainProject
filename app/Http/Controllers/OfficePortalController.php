<?php

namespace App\Http\Controllers;

use App\Models\BudgetItem;
use App\Models\OrgActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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
        ]));
    }

    public function calendar(): View
    {
        $month = Carbon::now()->startOfMonth();
        $cursor = $month->copy()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        while ($cursor <= $end) {
            $days[] = [
                'date' => $cursor->copy(),
                'inMonth' => $cursor->month === $month->month,
                'hasEvent' => $cursor->day === 14 && $cursor->month === $month->month,
            ];
            $cursor->addDay();
        }

        return view('org.calendar', array_merge($this->deskContext(), [
            'activeNav' => 'calendar',
            'monthLabel' => $month->format('F Y'),
            'days' => $days,
            'events' => collect($this->pipelineActivities())
                ->filter(fn (array $item): bool => ($item['upcoming_at'] ?? null) !== null)
                ->values(),
        ]));
    }

    public function budget(): View
    {
        return view('org.budget', array_merge($this->deskContext(), [
            'activeNav' => 'budget',
            'budget' => $this->budgetUtilizationData(),
        ]));
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
                'upcoming_at' => null,
                'docs' => ['Activity Proposal.pdf', 'Budget Breakdown.xlsx', 'Risk Assessment.pdf'],
                'note' => null,
            ],
            [
                'title' => 'Leadership Summit 2026',
                'status' => 'Created',
                'status_key' => 'created',
                'stage' => 1,
                'stages' => 4,
                'date' => 'Aug 12, 2026',
                'budget' => 85000,
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
