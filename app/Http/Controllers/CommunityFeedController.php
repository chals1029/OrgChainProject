<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityCommentLike;
use App\Models\CommunityLike;
use App\Models\CommunityPost;
use App\Models\UserAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommunityFeedController extends Controller
{
    private const REACTIONS = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];

    public function store(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        $request->merge([
            'body' => trim((string) $request->input('body')),
        ]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'activity_id' => ['nullable', 'integer', 'exists:org_activities,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'feeling' => ['nullable', 'string', 'max:100'],
            'tagged_users' => ['nullable', 'string', 'max:500'],
            'audience' => ['nullable', 'string', 'in:public,department,org'],
        ], [
            'body.required' => 'Write something about your experience.',
            'photo.max' => 'Photo must be 4MB or smaller.',
        ]);

        $imagePath = null;
        if ($request->hasFile('photo')) {
            $imagePath = $request->file('photo')->store('community', 'public');
        }

        CommunityPost::create([
            'student_id' => $student->id,
            'activity_id' => $validated['activity_id'] ?? null,
            'body' => $validated['body'],
            'image_path' => $imagePath,
            'feeling' => $validated['feeling'] ?: null,
            'tagged_users' => $validated['tagged_users'] ?: null,
            'audience' => $validated['audience'] ?? 'public',
        ]);

        return redirect()
            ->route('portal.community')
            ->with('status', 'Your post is live in the community feed.');
    }

    public function like(Request $request, CommunityPost $post): JsonResponse|RedirectResponse
    {
        $student = Auth::guard('student')->user();
        abort_unless($student instanceof UserAccount && $this->canViewPost($post, $student), 404);

        $validated = $request->validate([
            'reaction' => ['nullable', 'string', 'in:'.implode(',', self::REACTIONS)],
        ]);
        $requestedReaction = strtolower(trim((string) ($validated['reaction'] ?? 'like')));
        $reaction = null;

        DB::transaction(function () use ($post, $student, $requestedReaction, &$reaction): void {
            $existing = CommunityLike::query()
                ->where('post_id', $post->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->reaction === $requestedReaction) {
                $existing->delete();
                $reaction = null;
            } elseif ($existing) {
                $existing->update(['reaction' => $requestedReaction]);
                $reaction = $requestedReaction;
            } else {
                CommunityLike::create([
                    'post_id' => $post->id,
                    'student_id' => $student->id,
                    'reaction' => $requestedReaction,
                ]);
                $reaction = $requestedReaction;
            }

            // Keep the denormalized count correct even if an older row drifted.
            $post->forceFill(['likes_count' => $post->likes()->count()])->save();
        });

        $post->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'liked' => $reaction !== null,
                'reaction' => $reaction,
                'likes_count' => (int) $post->likes_count,
            ]);
        }

        return back();
    }

    public function comment(Request $request, CommunityPost $post): JsonResponse|RedirectResponse
    {
        $student = Auth::guard('student')->user();
        abort_unless($student instanceof UserAccount && $this->canViewPost($post, $student), 404);

        $request->merge([
            'body' => trim((string) $request->input('body')),
        ]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:800'],
        ]);

        $comment = CommunityComment::create([
            'post_id' => $post->id,
            'student_id' => $student->id,
            'body' => $validated['body'],
        ]);

        $post->forceFill(['comments_count' => $post->comments()->count()])->save();
        $post->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'comments_count' => (int) $post->comments_count,
                'comment' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'student_name' => $student->name,
                    'student_initials' => $student->initials(),
                    'student_avatar_url' => $student->avatar_path
                        ? asset('storage/'.$student->avatar_path)
                        : null,
                    'created_at' => optional($comment->created_at)->toIso8601String(),
                    'like_url' => route('portal.community.comments.like', $comment),
                    'likes_count' => 0,
                    'liked_by_me' => false,
                ],
            ]);
        }

        return back()->with('status', 'Comment added.');
    }

    public function likeComment(Request $request, CommunityComment $comment): JsonResponse|RedirectResponse
    {
        $student = Auth::guard('student')->user();
        $post = $comment->post;
        abort_unless($student instanceof UserAccount && $post && $this->canViewPost($post, $student), 404);

        $liked = false;

        DB::transaction(function () use ($comment, $student, &$liked): void {
            $existing = CommunityCommentLike::query()
                ->where('comment_id', $comment->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                return;
            }

            CommunityCommentLike::create([
                'comment_id' => $comment->id,
                'student_id' => $student->id,
            ]);
            $liked = true;
        });

        $likesCount = $comment->likes()->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'liked' => $liked,
                'likes_count' => $likesCount,
            ]);
        }

        return back();
    }

    public function likers(CommunityPost $post): JsonResponse
    {
        $student = Auth::guard('student')->user();
        abort_unless($student instanceof UserAccount && $this->canViewPost($post, $student), 404);

        $likers = CommunityLike::query()
            ->where('post_id', $post->id)
            ->with('student')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (CommunityLike $like): array => [
                'name' => $like->student->name ?? 'Student',
                'initials' => $like->student ? $like->student->initials() : 'S',
                'reaction' => $like->reaction ?: 'like',
            ]);

        return response()->json([
            'ok' => true,
            'likes_count' => (int) $post->likes_count,
            'views_count' => (int) ($post->views_count ?? 0),
            'comments_count' => (int) ($post->comments_count ?? 0),
            'likers' => $likers,
        ]);
    }

    public function destroy(CommunityPost $post): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        abort_unless($post->student_id === $student->id, 403);

        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return back()->with('status', 'Post removed.');
    }

    private function canViewPost(CommunityPost $post, UserAccount $student): bool
    {
        if ((int) $post->student_id === (int) $student->id) {
            return true;
        }

        $audience = (string) ($post->audience ?: 'public');
        if ($audience === 'public') {
            return true;
        }

        $author = $post->student;
        if (! $author) {
            return false;
        }

        if ($audience === 'department') {
            $studentColleges = [
                trim((string) $student->college),
                trim((string) $student->displayCollege()),
            ];
            $authorColleges = [
                trim((string) $author->college),
                trim((string) $author->displayCollege()),
            ];

            foreach ($studentColleges as $studentCollege) {
                foreach ($authorColleges as $authorCollege) {
                    if ($studentCollege !== '' && $authorCollege !== '' && strcasecmp($studentCollege, $authorCollege) === 0) {
                        return true;
                    }
                }
            }

            return false;
        }

        if ($audience === 'org') {
            $studentOrgId = trim((string) $student->org_id);
            $authorOrgId = trim((string) $author->org_id);

            return $studentOrgId !== '' && $studentOrgId === $authorOrgId;
        }

        return false;
    }
}
