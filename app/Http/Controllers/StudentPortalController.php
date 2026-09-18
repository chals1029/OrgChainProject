<?php

namespace App\Http\Controllers;

use App\Models\BudgetItem;
use App\Models\CommunityPost;
use App\Models\ExpenseReceiptReview;
use App\Models\OrgActivity;
use App\Models\StudentFeedback;
use App\Services\BudgetChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function home(Request $request): View
    {
        return $this->portal($request, 'home');
    }

    public function community(Request $request): View
    {
        return $this->portal($request, 'community');
    }

    public function storeFeedback(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        StudentFeedback::query()->create([
            'student_id' => $student?->id,
            'author_name' => $student?->full_name ?? $student?->name ?? 'Student',
            'program' => null,
            'college' => $student?->college,
            'topic' => $validated['category'] ?? 'campus',
            'body' => $validated['message'],
            'is_anonymous' => false,
            'visibility' => 'oso',
        ]);

        return back()->with('status', 'Thanks — your feedback was sent to OSO.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'college' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:50'],
        ]);

        $student->fill([
            'full_name' => $validated['name'],
            'college' => $validated['college'] ?? null,
            'year_level' => $validated['year_level'] ?? null,
        ])->save();

        return back()->with('status', 'Profile updated.');
    }

    private function portal(Request $request, string $tab): View
    {
        $student = Auth::guard('student')->user();
        $statusFilter = trim((string) $request->query('status', ''));

        // Default activities to the student's college/department; ?college= (empty) = All.
        $studentCollege = trim((string) ($student?->displayCollege() ?: $student?->college ?: ''));
        if ($request->has('college')) {
            $collegeFilter = trim((string) $request->query('college', ''));
        } else {
            $collegeFilter = $studentCollege;
        }

        $budgetItems = BudgetItem::query()
            ->orderByDesc('allocated')
            ->get();

        $totalAllocated = (int) $budgetItems->sum('allocated');
        $totalUtilized = (int) $budgetItems->sum('utilized');

        $publicExpenses = ExpenseReceiptReview::query()
            ->whereNotNull('chain_hash')
            ->latest()
            ->limit(12)
            ->get();

        $budgetChainBlocks = app(BudgetChainService::class)->recentBlocks(8);

        if ($totalAllocated <= 0 && $publicExpenses->isNotEmpty()) {
            $totalUtilized = (int) round($publicExpenses->sum(fn ($e) => (float) $e->unit_cost * (int) $e->quantity));
            $totalAllocated = max($totalUtilized, (int) BudgetItem::query()->sum('allocated') ?: $totalUtilized);
        }

        $upcomingQuery = OrgActivity::query()
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->when($collegeFilter !== '', fn ($q) => $q->where('college', $collegeFilter))
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->orderBy('starts_at')
            ->limit(50);

        $upcoming = $upcomingQuery->get();

        $recentActivities = OrgActivity::query()
            ->where('status', 'completed')
            ->when($collegeFilter !== '', fn ($q) => $q->where('college', $collegeFilter))
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get();

        $posts = CommunityPost::query()
            ->with(['student', 'activity', 'comments.student'])
            ->withExists([
                'likes as liked_by_me' => fn ($q) => $q->where('student_id', $student->id),
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $activities = OrgActivity::query()
            ->when($collegeFilter !== '', fn ($q) => $q->where('college', $collegeFilter))
            ->orderByDesc('starts_at')
            ->limit(20)
            ->get();

        $sdgHighlights = OrgActivity::query()
            ->whereNotNull('sdg_goals')
            ->when($collegeFilter !== '', fn ($q) => $q->where('college', $collegeFilter))
            ->orderByDesc('starts_at')
            ->limit(12)
            ->get()
            ->flatMap(fn (OrgActivity $a) => collect($a->sdg_goals ?: [])->map(fn ($sdg) => [
                'sdg' => $sdg,
                'title' => $a->title,
                'college' => $a->college,
            ]))
            ->groupBy('sdg')
            ->map(fn ($rows, $sdg) => [
                'sdg' => $sdg,
                'count' => $rows->count(),
                'samples' => $rows->take(3)->pluck('title')->values(),
            ])
            ->values();

        $colleges = OrgActivity::query()
            ->whereNotNull('college')
            ->where('college', '!=', '')
            ->distinct()
            ->orderBy('college')
            ->pluck('college');

        if ($studentCollege !== '' && ! $colleges->contains($studentCollege)) {
            $colleges = $colleges->prepend($studentCollege)->unique()->values();
        }

        $announcements = [
            [
                'title' => 'Deadline Extension for Activity Proposals',
                'body' => 'The deadline for submitting activity proposals for the 2nd Semester has been extended to April 15, 2026.',
                'author' => 'OSO Admin',
                'time' => '2 hrs ago',
                'priority' => 'high',
            ],
            [
                'title' => 'General Assembly & Org Orientation',
                'body' => 'All student leaders and active members are invited to attend the annual assembly at the University Amphitheater.',
                'author' => 'Student Affairs',
                'time' => 'Yesterday',
                'priority' => 'normal',
            ],
            [
                'title' => 'Budget Liquidation Submission Guidelines',
                'body' => 'Please submit all receipts and liquidation reports within 5 working days following completed events.',
                'author' => 'Finance Desk',
                'time' => '3 days ago',
                'priority' => 'normal',
            ],
        ];

        return view('portal.index', [
            'student' => $student,
            'tab' => $tab,
            'budgetItems' => $budgetItems,
            'totalAllocated' => $totalAllocated,
            'totalUtilized' => $totalUtilized,
            'publicExpenses' => $publicExpenses,
            'budgetChainBlocks' => $budgetChainBlocks,
            'upcoming' => $upcoming,
            'recentActivities' => $recentActivities,
            'posts' => $posts,
            'activities' => $activities,
            'announcements' => $announcements,
            'sdgHighlights' => $sdgHighlights,
            'colleges' => $colleges,
            'selectedCollege' => $collegeFilter,
            'collegeFilterExplicit' => $request->has('college'),
            'selectedStatus' => $statusFilter,
            'overview' => [
                'activities' => $activities->count(),
                'upcoming' => $upcoming->count(),
                'completed' => $recentActivities->count(),
                'sdg_tags' => $sdgHighlights->count(),
            ],
        ]);
    }
}
