@extends('portal.layout')

@section('content')
    @php
        $overallPct = $totalAllocated > 0 ? (int) min(100, round(($totalUtilized / $totalAllocated) * 100)) : 0;
    @endphp

    <div class="portal-panel {{ ($tab ?? 'home') === 'home' ? 'is-active' : '' }}" data-panel="home" @if(($tab ?? 'home') !== 'home') hidden @endif>
        <section class="portal-section">
            <div class="portal-section-head">
                <h2>Budget Utilization</h2>
                <p>Transparent org funds for FY {{ $budgetItems->first()->fiscal_year ?? '2026' }}.</p>
            </div>

            <div class="portal-budget-summary liquid-glass">
                <div>
                    <span class="portal-stat-label">Allocated</span>
                    <strong>₱{{ number_format($totalAllocated) }}</strong>
                </div>
                <div>
                    <span class="portal-stat-label">Utilized</span>
                    <strong>₱{{ number_format($totalUtilized) }}</strong>
                </div>
                <div>
                    <span class="portal-stat-label">Used</span>
                    <strong>{{ $overallPct }}%</strong>
                </div>
                <div class="portal-progress">
                    <span style="width: {{ $overallPct }}%"></span>
                </div>
            </div>

            <div class="portal-card-grid">
                @forelse ($budgetItems as $item)
                    <article class="portal-card liquid-glass">
                        <div class="portal-card-top">
                            <span class="portal-chip">{{ $item->category }}</span>
                            <span class="portal-pct">{{ $item->utilizationPercent() }}%</span>
                        </div>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->notes }}</p>
                        <div class="portal-progress">
                            <span style="width: {{ $item->utilizationPercent() }}%"></span>
                        </div>
                        <div class="portal-money-row">
                            <span>Used ₱{{ number_format($item->utilized) }}</span>
                            <span>Left ₱{{ number_format($item->remaining()) }}</span>
                        </div>
                    </article>
                @empty
                    <p class="portal-empty liquid-glass">No budget records yet.</p>
                @endforelse
            </div>
        </section>

        <section class="portal-section">
            <div class="portal-section-head">
                <h2>Upcoming</h2>
                <p>Activities coming up.</p>
            </div>
            <div class="portal-list">
                @forelse ($upcoming as $activity)
                    <article class="portal-list-item liquid-glass">
                        <div>
                            <span class="portal-chip portal-chip-{{ $activity->status }}">{{ ucfirst($activity->status) }}</span>
                            <h3>{{ $activity->title }}</h3>
                            <p>{{ $activity->description }}</p>
                        </div>
                        <div class="portal-list-meta">
                            <span>{{ optional($activity->starts_at)->format('M j · g:i A') ?? 'TBA' }}</span>
                            <span>{{ $activity->location ?? 'Campus' }}</span>
                        </div>
                    </article>
                @empty
                    <p class="portal-empty liquid-glass">No upcoming activities yet.</p>
                @endforelse
            </div>
        </section>

        <section class="portal-section">
            <div class="portal-section-head">
                <h2>Recent Activities</h2>
                <p>Already completed.</p>
            </div>
            <div class="portal-list">
                @forelse ($recentActivities as $activity)
                    <article class="portal-list-item liquid-glass">
                        <div>
                            <span class="portal-chip portal-chip-completed">Completed</span>
                            <h3>{{ $activity->title }}</h3>
                            <p>{{ $activity->description }}</p>
                        </div>
                        <div class="portal-list-meta">
                            <span>{{ optional($activity->starts_at)->format('M j, Y') ?? '—' }}</span>
                            <span>{{ $activity->location ?? 'Campus' }}</span>
                        </div>
                    </article>
                @empty
                    <p class="portal-empty liquid-glass">No completed activities yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="portal-panel {{ ($tab ?? '') === 'community' ? 'is-active' : '' }}" data-panel="community" @if(($tab ?? '') !== 'community') hidden @endif>
        <section class="portal-section">
            <div class="portal-section-head">
                <h2>Share an experience</h2>
                <p>Post about an activity — photo, likes, and comments.</p>
            </div>

            <form class="community-composer liquid-glass" method="post" action="{{ route('portal.community.posts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="composer-row">
                    <div class="portal-avatar composer-avatar" aria-hidden="true">
                        <span>{{ $student->initials() }}</span>
                    </div>
                    <textarea name="body" rows="3" maxlength="2000" placeholder="What was the activity like for you?" required>{{ old('body') }}</textarea>
                </div>

                <div class="composer-tools">
                    <label class="composer-select">
                        <span>Related activity</span>
                        <select name="activity_id">
                            <option value="">General post</option>
                            @foreach ($activities as $activity)
                                <option value="{{ $activity->id }}" @selected(old('activity_id') == $activity->id)>{{ $activity->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="composer-photo">
                        <input type="file" name="photo" accept="image/*" id="communityPhotoInput">
                        <span id="communityPhotoLabel">Add photo</span>
                    </label>

                    <button type="submit" class="btn btn-primary">Post</button>
                </div>

                @error('body')
                    <p class="portal-error">{{ $message }}</p>
                @enderror
                @error('photo')
                    <p class="portal-error">{{ $message }}</p>
                @enderror
            </form>
        </section>

        <section class="portal-section">
            <div class="portal-section-head">
                <h2>Community feed</h2>
                <p>What students are saying.</p>
            </div>

            <div class="community-feed">
                @forelse ($posts as $post)
                    <article class="community-post liquid-glass" data-post-id="{{ $post->id }}">
                        <header class="community-post-head">
                            <div class="portal-avatar" aria-hidden="true">
                                <span>{{ $post->student->initials() }}</span>
                            </div>
                            <div>
                                <strong>{{ $post->student->name }}</strong>
                                <span>{{ $post->created_at->diffForHumans() }}</span>
                                @if ($post->activity)
                                    <em class="community-activity-tag">{{ $post->activity->title }}</em>
                                @endif
                            </div>
                            @if ($post->student_id === $student->id)
                                <form method="post" action="{{ route('portal.community.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="community-delete">Delete</button>
                                </form>
                            @endif
                        </header>

                        <p class="community-post-body">{{ $post->body }}</p>

                        @if ($post->image_path)
                            <div class="community-post-photo">
                                <img src="{{ asset('storage/'.$post->image_path) }}" alt="Post photo">
                            </div>
                        @endif

                        <div class="community-stats">
                            <span data-likes-count>{{ $post->likes_count }} likes</span>
                            <span data-comments-count>{{ $post->comments_count }} comments</span>
                        </div>

                        <div class="community-actions">
                            <button
                                type="button"
                                class="community-action community-like-btn {{ $post->liked_by_me ? 'is-liked' : '' }}"
                                data-like-url="{{ route('portal.community.posts.like', $post) }}"
                            >
                                {{ $post->liked_by_me ? 'Liked' : 'Like' }}
                            </button>
                            <button type="button" class="community-action" onclick="document.getElementById('comment-{{ $post->id }}')?.focus()">Comment</button>
                        </div>

                        <div class="community-comments" data-comments>
                            @foreach ($post->comments->take(5) as $comment)
                                <div class="community-comment">
                                    <strong>{{ $comment->student->name }}</strong>
                                    <span>{{ $comment->body }}</span>
                                </div>
                            @endforeach
                        </div>

                        <form class="community-comment-form" data-comment-form action="{{ route('portal.community.posts.comment', $post) }}">
                            @csrf
                            <input id="comment-{{ $post->id }}" type="text" name="body" maxlength="800" placeholder="Write a comment…" required>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </form>
                    </article>
                @empty
                    <p class="portal-empty liquid-glass">No posts yet. Be the first to share.</p>
                @endforelse
            </div>

            <div class="portal-pagination">
                {{ $posts->links() }}
            </div>
        </section>
    </div>
@endsection
