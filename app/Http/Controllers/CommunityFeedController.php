<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityLike;
use App\Models\CommunityPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommunityFeedController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'activity_id' => ['nullable', 'integer', 'exists:org_activities,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
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
        ]);

        return redirect()
            ->route('portal.community')
            ->with('status', 'Your post is live in the community feed.');
    }

    public function like(Request $request, CommunityPost $post): JsonResponse|RedirectResponse
    {
        $student = Auth::guard('student')->user();
        $liked = false;

        DB::transaction(function () use ($post, $student, &$liked) {
            $existing = CommunityLike::query()
                ->where('post_id', $post->id)
                ->where('student_id', $student->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $post->decrement('likes_count');
                $liked = false;
            } else {
                CommunityLike::create([
                    'post_id' => $post->id,
                    'student_id' => $student->id,
                ]);
                $post->increment('likes_count');
                $liked = true;
            }
        });

        $post->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'liked' => $liked,
                'likes_count' => (int) $post->likes_count,
            ]);
        }

        return back();
    }

    public function comment(Request $request, CommunityPost $post): JsonResponse|RedirectResponse
    {
        $student = Auth::guard('student')->user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:800'],
        ]);

        $comment = CommunityComment::create([
            'post_id' => $post->id,
            'student_id' => $student->id,
            'body' => $validated['body'],
        ]);

        $post->increment('comments_count');
        $post->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'comments_count' => (int) $post->comments_count,
                'comment' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'student_name' => $student->name,
                ],
            ]);
        }

        return back()->with('status', 'Comment added.');
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
}
