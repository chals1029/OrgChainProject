<?php

namespace App\Http\Controllers;

use App\Models\BudgetItem;
use App\Models\CommunityPost;
use App\Models\OrgActivity;
use App\Models\UserAccount;
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

    private function portal(Request $request, string $tab): View
    {
        $student = Auth::guard('student')->user();

        $budgetItems = BudgetItem::query()
            ->orderByDesc('allocated')
            ->get();

        $totalAllocated = (int) $budgetItems->sum('allocated');
        $totalUtilized = (int) $budgetItems->sum('utilized');

        $upcoming = OrgActivity::query()
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->orderBy('starts_at')
            ->limit(50)
            ->get();

        $recentActivities = OrgActivity::query()
            ->where('status', 'completed')
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
            ->orderByDesc('starts_at')
            ->limit(20)
            ->get();

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
            'upcoming' => $upcoming,
            'recentActivities' => $recentActivities,
            'posts' => $posts,
            'activities' => $activities,
            'announcements' => $announcements,
        ]);
    }
}
