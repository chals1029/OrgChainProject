@extends('portal.layout')

@section('content')
    @php
        $overallPct = $totalAllocated > 0 ? (int) min(100, round(($totalUtilized / $totalAllocated) * 100)) : 0;
        $totalActivities = $upcoming->count() + $recentActivities->count();
        $upcomingCount = $upcoming->count();
        $completedCount = $recentActivities->count();
        $firstName = trim(explode(' ', $student->name ?? '')[0] ?? '');
        if ($firstName === '') $firstName = 'Student';
    @endphp

    {{-- ============================ HOME PANEL ============================ --}}
    <div class="portal-panel {{ ($tab ?? 'home') === 'home' ? 'is-active' : '' }}" data-panel="home" @if(($tab ?? 'home') !== 'home') hidden @endif>

        {{-- Welcome hero --}}
        <section class="sp-hero liquid-glass">
            <div class="sp-hero-copy">
                <p class="sp-eyebrow"><i class="bi bi-stars"></i> Welcome back</p>
                <h2>Hello, {{ $firstName }}!</h2>
                <p class="sp-hero-lead">Here's what's happening with your organization today — track activities, budgets, and community updates all in one place.</p>
            </div>
            <div class="sp-hero-side">
                <div class="sp-hero-mark">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
            </div>
        </section>

        {{-- Stats overview --}}
        <section class="sp-stats">
            <article class="sp-stat-card liquid-glass">
                <div class="sp-stat-icon is-red"><i class="bi bi-collection-fill"></i></div>
                <strong>{{ $totalActivities }}</strong>
                <span>Total Activities</span>
                <small>All-time tracked</small>
            </article>
            <article class="sp-stat-card liquid-glass">
                <div class="sp-stat-icon is-gold"><i class="bi bi-calendar-event-fill"></i></div>
                <strong>{{ $upcomingCount }}</strong>
                <span>Upcoming</span>
                <small>Scheduled ahead</small>
            </article>
            <article class="sp-stat-card liquid-glass">
                <div class="sp-stat-icon is-green"><i class="bi bi-check2-circle"></i></div>
                <strong>{{ $completedCount }}</strong>
                <span>Completed</span>
                <small>Recently finished</small>
            </article>
            <article class="sp-stat-card liquid-glass">
                <div class="sp-stat-icon is-violet"><i class="bi bi-pie-chart-fill"></i></div>
                <strong>{{ $overallPct }}%</strong>
                <span>Budget Used</span>
                <small>Of allocated funds</small>
            </article>
        </section>

        {{-- Budget Utilization --}}
        <section class="sp-section">
            <div class="sp-section-head">
                <div>
                    <h2><i class="bi bi-wallet2"></i> Budget Utilization</h2>
                    <p>Transparent org funds for FY {{ $budgetItems->first()->fiscal_year ?? '2026' }}.</p>
                </div>
                <span class="sp-section-tag">{{ $overallPct }}% used</span>
            </div>

            <div class="sp-budget-summary liquid-glass">
                <div class="sp-budget-metric">
                    <span class="sp-stat-label">Allocated</span>
                    <strong>₱{{ number_format($totalAllocated) }}</strong>
                </div>
                <div class="sp-budget-metric">
                    <span class="sp-stat-label">Utilized</span>
                    <strong>₱{{ number_format($totalUtilized) }}</strong>
                </div>
                <div class="sp-budget-metric">
                    <span class="sp-stat-label">Remaining</span>
                    <strong>₱{{ number_format(max(0, $totalAllocated - $totalUtilized)) }}</strong>
                </div>
                <div class="sp-budget-bar-wrap">
                    <div class="sp-budget-bar-head">
                        <span>Overall utilization</span>
                        <strong>{{ $overallPct }}%</strong>
                    </div>
                    <div class="sp-progress">
                        <span style="width: {{ $overallPct }}%"></span>
                    </div>
                </div>
            </div>

            <div class="sp-card-grid">
                @forelse ($budgetItems as $item)
                    <article class="sp-card liquid-glass">
                        <div class="sp-card-top">
                            <span class="sp-chip"><i class="bi bi-tag-fill"></i> {{ $item->category }}</span>
                            <span class="sp-pct">{{ $item->utilizationPercent() }}%</span>
                        </div>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->notes }}</p>
                        <div class="sp-progress">
                            <span style="width: {{ $item->utilizationPercent() }}%"></span>
                        </div>
                        <div class="sp-money-row">
                            <span><i class="bi bi-arrow-up-circle"></i> Used ₱{{ number_format($item->utilized) }}</span>
                            <span><i class="bi bi-dash-circle"></i> Left ₱{{ number_format($item->remaining()) }}</span>
                        </div>
                    </article>
                @empty
                    <p class="sp-empty liquid-glass"><i class="bi bi-inbox"></i> No budget records yet.</p>
                @endforelse
            </div>
        </section>

        {{-- Upcoming Activities --}}
        <section class="sp-section">
            <div class="sp-section-head">
                <div>
                    <h2><i class="bi bi-calendar-event"></i> Upcoming Activities</h2>
                    <p>Activities scheduled ahead.</p>
                </div>
                <span class="sp-section-tag">{{ $upcomingCount }} scheduled</span>
            </div>

            <div class="sp-card-grid sp-card-grid-2">
                @forelse ($upcoming as $activity)
                    @php
                        $starts = $activity->starts_at;
                        $day = $starts?->format('j') ?? '?';
                        $mon = $starts?->format('M') ?? 'TBA';
                        $time = $starts?->format('g:i A') ?? 'TBA';
                    @endphp
                    <article class="sp-activity-card liquid-glass">
                        <div class="sp-activity-row">
                            <div class="sp-date-badge">
                                <strong>{{ $day }}</strong>
                                <small>{{ $mon }}</small>
                            </div>
                            <div class="sp-activity-body">
                                <div class="sp-activity-top">
                                    <span class="sp-chip sp-chip-{{ $activity->status }}">
                                        <i class="bi bi-circle-fill"></i> {{ ucfirst($activity->status) }}
                                    </span>
                                </div>
                                <h3>{{ $activity->title }}</h3>
                                <p>{{ $activity->description }}</p>
                                <div class="sp-activity-meta">
                                    <span><i class="bi bi-clock"></i> {{ $time }}</span>
                                    @if (!empty($activity->location))
                                        <span><i class="bi bi-geo-alt"></i> {{ $activity->location }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="sp-empty liquid-glass"><i class="bi bi-calendar-x"></i> No upcoming activities yet.</p>
                @endforelse
            </div>
        </section>

        {{-- Recent Activities --}}
        <section class="sp-section">
            <div class="sp-section-head">
                <div>
                    <h2><i class="bi bi-clock-history"></i> Recent Activities</h2>
                    <p>Already completed events.</p>
                </div>
                <span class="sp-section-tag">{{ $completedCount }} done</span>
            </div>

            <div class="sp-card-grid sp-card-grid-2">
                @forelse ($recentActivities as $activity)
                    @php
                        $starts = $activity->starts_at;
                        $day = $starts?->format('j') ?? '?';
                        $mon = $starts?->format('M') ?? '—';
                        $year = $starts?->format('Y') ?? '';
                    @endphp
                    <article class="sp-activity-card liquid-glass">
                        <div class="sp-activity-row">
                            <div class="sp-date-badge sp-date-badge-completed">
                                <strong>{{ $day }}</strong>
                                <small>{{ $mon }}</small>
                            </div>
                            <div class="sp-activity-body">
                                <div class="sp-activity-top">
                                    <span class="sp-chip sp-chip-completed">
                                        <i class="bi bi-check-circle-fill"></i> Completed
                                    </span>
                                </div>
                                <h3>{{ $activity->title }}</h3>
                                <p>{{ $activity->description }}</p>
                                <div class="sp-activity-meta">
                                    <span><i class="bi bi-calendar3"></i> {{ $mon }} {{ $day }}, {{ $year }}</span>
                                    @if (!empty($activity->location))
                                        <span><i class="bi bi-geo-alt"></i> {{ $activity->location }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="sp-empty liquid-glass"><i class="bi bi-archive"></i> No completed activities yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ============================ COMMUNITY PANEL ============================ --}}
    <div class="portal-panel {{ ($tab ?? '') === 'community' ? 'is-active' : '' }}" data-panel="community" @if(($tab ?? '') !== 'community') hidden @endif>

        {{-- Post composer --}}
        <section class="sp-section">
            <div class="sp-section-head">
                <div>
                    <h2><i class="bi bi-pencil-square"></i> Share an experience</h2>
                    <p>Post about an activity — share a photo, gather likes, and spark discussion.</p>
                </div>
            </div>

            <form class="sp-composer liquid-glass" method="post" action="{{ route('portal.community.posts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="sp-composer-row">
                    <div class="sp-avatar sp-avatar-sm" aria-hidden="true">
                        @if (!empty($student->avatar_path))
                            <img src="{{ asset('storage/'.$student->avatar_path) }}" alt="">
                        @else
                            <span>{{ $student->initials() }}</span>
                        @endif
                    </div>
                    <textarea name="body" rows="3" maxlength="2000" placeholder="What was the activity like for you?" required>{{ old('body') }}</textarea>
                </div>

                <div class="sp-composer-tools">
                    <label class="sp-composer-select">
                        <span><i class="bi bi-link-45deg"></i> Related activity</span>
                        <select name="activity_id">
                            <option value="">General post</option>
                            @foreach ($activities as $activity)
                                <option value="{{ $activity->id }}" @selected(old('activity_id') == $activity->id)>{{ $activity->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="sp-composer-photo">
                        <input type="file" name="photo" accept="image/*" id="communityPhotoInput">
                        <span id="communityPhotoLabel"><i class="bi bi-image"></i> Add photo</span>
                    </label>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send-fill"></i> Post
                    </button>
                </div>

                @error('body')
                    <p class="sp-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                @enderror
                @error('photo')
                    <p class="sp-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </form>
        </section>

        {{-- Community feed --}}
        <section class="sp-section">
            <div class="sp-section-head">
                <div>
                    <h2><i class="bi bi-chat-heart-fill"></i> Community feed</h2>
                    <p>What students are saying right now.</p>
                </div>
            </div>

            <div class="sp-feed">
                @forelse ($posts as $post)
                    <article class="sp-post liquid-glass community-post" data-post-id="{{ $post->id }}">
                        <header class="sp-post-head">
                            <div class="sp-avatar sp-avatar-sm" aria-hidden="true">
                                @if (!empty($post->student->avatar_path))
                                    <img src="{{ asset('storage/'.$post->student->avatar_path) }}" alt="">
                                @else
                                    <span>{{ $post->student->initials() }}</span>
                                @endif
                            </div>
                            <div class="sp-post-meta">
                                <strong>{{ $post->student->name }}</strong>
                                <span><i class="bi bi-clock"></i> {{ $post->created_at->diffForHumans() }}</span>
                                @if ($post->activity)
                                    <em class="sp-activity-tag"><i class="bi bi-link-45deg"></i> {{ $post->activity->title }}</em>
                                @endif
                            </div>
                            @if ($post->student_id === $student->id)
                                <form method="post" action="{{ route('portal.community.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?')" class="sp-post-delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sp-post-delete" title="Delete post">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            @endif
                        </header>

                        <p class="sp-post-body">{{ $post->body }}</p>

                        @if ($post->image_path)
                            <div class="sp-post-photo">
                                <img src="{{ asset('storage/'.$post->image_path) }}" alt="Post photo">
                            </div>
                        @endif

                        <div class="sp-post-stats">
                            <span data-likes-count><i class="bi bi-heart-fill"></i> {{ $post->likes_count }} likes</span>
                            <span data-comments-count><i class="bi bi-chat-dots"></i> {{ $post->comments_count }} comments</span>
                        </div>

                        <div class="sp-post-actions">
                            <button
                                type="button"
                                class="sp-post-action community-like-btn {{ $post->liked_by_me ? 'is-liked' : '' }}"
                                data-like-url="{{ route('portal.community.posts.like', $post) }}"
                            >
                                <i class="bi {{ $post->liked_by_me ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                <span class="sp-like-label">{{ $post->liked_by_me ? 'Liked' : 'Like' }}</span>
                            </button>
                            <button type="button" class="sp-post-action" onclick="document.getElementById('comment-{{ $post->id }}')?.focus()">
                                <i class="bi bi-chat"></i>
                                <span>Comment</span>
                            </button>
                        </div>

                        <div class="sp-comments" data-comments>
                            @foreach ($post->comments->take(5) as $comment)
                                <div class="sp-comment">
                                    <span class="sp-comment-avatar" aria-hidden="true">{{ strtoupper(substr($comment->student->name ?? '?', 0, 1)) }}</span>
                                    <div class="sp-comment-body">
                                        <div class="sp-comment-head">
                                            <strong>{{ $comment->student->name }}</strong>
                                        </div>
                                        <span>{{ $comment->body }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <form class="sp-comment-form" data-comment-form action="{{ route('portal.community.posts.comment', $post) }}">
                            @csrf
                            <input id="comment-{{ $post->id }}" type="text" name="body" maxlength="800" placeholder="Write a comment…" required>
                            <button type="submit" class="btn btn-primary" aria-label="Send comment">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </form>
                    </article>
                @empty
                    <p class="sp-empty liquid-glass"><i class="bi bi-chat-quote"></i> No posts yet. Be the first to share.</p>
                @endforelse
            </div>

            <div class="sp-pagination">
                {{ $posts->links() }}
            </div>
        </section>
    </div>
@endsection
