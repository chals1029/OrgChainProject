@extends('portal.layout')

@section('content')
    @php
        $overallPct = $totalAllocated > 0 ? (int) min(100, round(($totalUtilized / $totalAllocated) * 100)) : 0;
        $totalActivities = $upcoming->count() + $recentActivities->count();
        $upcomingCount = $upcoming->count();
        $completedCount = $recentActivities->count();
        $firstName = trim(explode(' ', $student->name ?? '')[0] ?? '');
        if ($firstName === '') $firstName = 'Student';
        $communityReactions = [
            'like' => ['label' => 'Like', 'icon' => 'bi-hand-thumbs-up-fill', 'emoji' => '👍'],
            'love' => ['label' => 'Love', 'icon' => 'bi-heart-fill', 'emoji' => '❤️'],
            'care' => ['label' => 'Care', 'icon' => 'bi-emoji-heart-eyes-fill', 'emoji' => '🥰'],
            'haha' => ['label' => 'Haha', 'icon' => 'bi-emoji-laughing-fill', 'emoji' => '😆'],
            'wow' => ['label' => 'Wow', 'icon' => 'bi-emoji-surprise-fill', 'emoji' => '😮'],
            'sad' => ['label' => 'Sad', 'icon' => 'bi-emoji-tear-fill', 'emoji' => '😢'],
            'angry' => ['label' => 'Angry', 'icon' => 'bi-emoji-angry-fill', 'emoji' => '😡'],
        ];
    @endphp

    {{-- ============================ HOME PANEL ============================ --}}
    <div class="portal-panel {{ ($tab ?? 'home') === 'home' ? 'is-active' : '' }}" data-panel="home" @if(($tab ?? 'home') !== 'home') hidden @endif>

        {{-- Welcome hero --}}
        <section class="sp-hero liquid-glass">
            <div class="sp-hero-copy">
                <h2>Hello, {{ $firstName }}!</h2>
                <p class="sp-hero-lead">STAY CONNECTED.</p>
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
                <div class="sp-stat-top">
                    <div class="sp-stat-icon is-red"><i class="bi bi-collection-fill"></i></div>
                    <strong>{{ $totalActivities }}</strong>
                </div>
                <span>Total Activities</span>
                <small>All-time tracked</small>
            </article>
            <article class="sp-stat-card liquid-glass">
                <div class="sp-stat-top">
                    <div class="sp-stat-icon is-gold"><i class="bi bi-calendar-event-fill"></i></div>
                    <strong>{{ $upcomingCount }}</strong>
                </div>
                <span>Upcoming</span>
                <small>Scheduled ahead</small>
            </article>
            <article class="sp-stat-card liquid-glass">
                <div class="sp-stat-top">
                    <div class="sp-stat-icon is-green"><i class="bi bi-check2-circle"></i></div>
                    <strong>{{ $completedCount }}</strong>
                </div>
                <span>Completed</span>
                <small>Recently finished</small>
            </article>
            <article class="sp-stat-card liquid-glass">
                <div class="sp-stat-top">
                    <div class="sp-stat-icon is-violet"><i class="bi bi-pie-chart-fill"></i></div>
                    <strong>{{ $overallPct }}%</strong>
                </div>
                <span>Budget Used</span>
                <small>Of allocated funds</small>
            </article>
        </section>

        {{-- SDG highlights --}}
        @include('portal.sdg-highlights')


        {{-- Budget Utilization --}}
        <section class="sp-section">
            <div class="sp-section-head">
                <div>
                    <h2><i class="bi bi-wallet2"></i> Budget Utilization</h2>
                    <p>Transparent org funds for FY {{ $budgetItems->first()->fiscal_year ?? '2026' }} — sealed on OrgChain’s 3-node budget ledger.</p>
                </div>
            </div>

            <div class="sp-budget-grid">
                {{-- Left: Budget Summary Gauge Card --}}
                <div class="sp-budget-summary liquid-glass">
                    {{-- Top: Allocated --}}
                    <div class="sp-gauge-vert-item is-top">
                        <div class="sp-gauge-side-header">
                            <i class="bi bi-wallet2"></i>
                            <span>ALLOCATED:</span>
                        </div>
                        <strong class="sp-gauge-side-val">₱{{ number_format($totalAllocated) }}</strong>
                    </div>

                    {{-- Center: Overall Utilization Gauge --}}
                    <div class="sp-gauge-center-box">
                        <div class="sp-gauge-arch-wrap">
                            <svg viewBox="0 0 280 150" class="sp-gauge-svg" aria-hidden="true">
                                <defs>
                                    <linearGradient id="spGaugeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#6B0D1C"/>
                                        <stop offset="100%" stop-color="#9B1B30"/>
                                    </linearGradient>
                                    <linearGradient id="spGaugeShimmer" x1="-100%" y1="0%" x2="0%" y2="0%">
                                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0" />
                                        <stop offset="35%" stop-color="#ffffff" stop-opacity="0.08" />
                                        <stop offset="50%" stop-color="#ffffff" stop-opacity="0.65" />
                                        <stop offset="65%" stop-color="#ffffff" stop-opacity="0.08" />
                                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                                        <animate attributeName="x1" from="-100%" to="150%" dur="2.8s" repeatCount="indefinite" begin="0.6s" />
                                        <animate attributeName="x2" from="0%" to="250%" dur="2.8s" repeatCount="indefinite" begin="0.6s" />
                                    </linearGradient>
                                </defs>
                                <!-- Thin outer border arch -->
                                <path d="M 22 142 A 118 118 0 0 1 258 142" fill="none" stroke="#6B0D1C" stroke-width="1.5" />
                                <!-- Base track -->
                                <path d="M 38 142 A 102 102 0 0 1 242 142" fill="none" stroke="#E5E7EB" stroke-width="22" stroke-linecap="round" />
                                <!-- Progress fill -->
                                @php
                                    $archLength = 320.44;
                                    $clampedPct = min(100, max(0, $overallPct));
                                    $dashOffset = $archLength * (1 - ($clampedPct / 100));
                                @endphp
                                <path d="M 38 142 A 102 102 0 0 1 242 142" fill="none" stroke="url(#spGaugeGradient)" stroke-width="22" stroke-linecap="round" stroke-dasharray="{{ $archLength }}" stroke-dashoffset="{{ $dashOffset }}" class="sp-gauge-fill" />
                                <!-- Moving shimmer light beam effect -->
                                <path d="M 38 142 A 102 102 0 0 1 242 142" fill="none" stroke="url(#spGaugeShimmer)" stroke-width="18" stroke-linecap="round" stroke-dasharray="{{ $archLength }}" stroke-dashoffset="{{ $dashOffset }}" class="sp-gauge-shimmer" />
                            </svg>
                            <div class="sp-gauge-content">
                                <span class="sp-gauge-title">Overall Utilization</span>
                                <strong class="sp-gauge-percent">{{ $overallPct }}%</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Bottom: Remaining --}}
                    <div class="sp-gauge-vert-item is-bottom">
                        <div class="sp-gauge-side-header">
                            <i class="bi bi-wallet"></i>
                            <span>REMAINING:</span>
                        </div>
                        <strong class="sp-gauge-side-val">₱{{ number_format(max(0, $totalAllocated - $totalUtilized)) }}</strong>
                    </div>
                </div>

                {{-- Right: Category Breakdown Horizontal Bar Chart Card --}}
                <div class="sp-budget-chart-card liquid-glass">
                    <div class="sp-chart-card-head">
                        <h3><i class="bi bi-bar-chart-line-fill"></i> Category Breakdown</h3>
                        <span class="sp-chart-count">{{ count($budgetCategoryBreakdown ?? []) }} Categories</span>
                    </div>
                    <div class="sp-hbar-list">
                        @forelse (($budgetCategoryBreakdown ?? []) as $item)
                            @php
                                $pct = $item['allocated'] > 0 ? round(($item['utilized'] / $item['allocated']) * 100, 1) : 0;
                                $catIcon = $item['category'] === 'In-Campus' ? 'bi-buildings' : 'bi-house-door-fill';
                            @endphp
                            <div class="sp-hbar-item">
                                <span class="sp-hbar-label">
                                    <i class="bi {{ $catIcon }}" aria-hidden="true"></i>
                                    <strong>{{ $item['category'] }}</strong>
                                </span>
                                <div class="sp-hbar-progress">
                                    <div class="sp-hbar-track" aria-hidden="true">
                                        <div class="sp-hbar-fill" style="width: {{ min(100, max(0, $pct)) }}%"></div>
                                    </div>
                                    <strong class="sp-hbar-pct">{{ $pct }}%</strong>
                                </div>
                                <div class="sp-hbar-values">
                                    <div class="sp-hbar-value">
                                        <strong>₱{{ number_format($item['utilized'], 2) }}</strong>
                                        <span>Actual</span>
                                    </div>
                                    <div class="sp-hbar-value">
                                        <strong>₱{{ number_format($item['allocated'], 2) }}</strong>
                                        <span>Total / Budget</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="sp-empty-sm"><i class="bi bi-inbox"></i> No budget records yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @php
                $publicExpenses = $publicExpenses ?? collect();
                $budgetChainBlocks = $budgetChainBlocks ?? [];
            @endphp

            @if ($publicExpenses->isNotEmpty() || count($budgetChainBlocks) > 0)
                <div class="sp-budget-chain liquid-glass" style="margin-top:1rem;padding:1.1rem 1.25rem;">
                    <div class="sp-chart-card-head" style="margin-bottom:0.75rem;">
                        <h3><i class="bi bi-link-45deg"></i> On-Chain Budget Seals</h3>
                        <span class="sp-chart-count">3-node ledger</span>
                    </div>
                    <p style="margin:0 0 0.85rem;font-size:0.82rem;color:#6b6468;">Verified expenses are sealed to OrgChain’s permissioned budget blockchain so students can audit utilization publicly.</p>

                    @if ($publicExpenses->isNotEmpty())
                        <div style="overflow-x:auto;margin-bottom:0.9rem;">
                            <table style="width:100%;border-collapse:collapse;font-size:0.8rem;">
                                <thead>
                                    <tr style="text-align:left;border-bottom:1.5px solid #f0e6e8;color:#7a7074;">
                                        <th style="padding:0.45rem;">Expense</th>
                                        <th style="padding:0.45rem;">OR / Ref</th>
                                        <th style="padding:0.45rem;">Amount</th>
                                        <th style="padding:0.45rem;">Nodes</th>
                                        <th style="padding:0.45rem;">Hash</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($publicExpenses as $expense)
                                        <tr style="border-bottom:1px solid #f6eff0;">
                                            <td style="padding:0.5rem;">
                                                <strong>{{ $expense->item_name }}</strong>
                                                <small style="display:block;color:#786f73;">{{ $expense->activity_title }}</small>
                                            </td>
                                            <td style="padding:0.5rem;">{{ $expense->receipt_reference ?: '—' }}</td>
                                            <td style="padding:0.5rem;">₱{{ number_format((float) $expense->unit_cost * (int) $expense->quantity, 2) }}</td>
                                            <td style="padding:0.5rem;">{{ (int) $expense->nodes_confirmed }}/3</td>
                                            <td style="padding:0.5rem;font-family:ui-monospace,monospace;font-size:0.72rem;">{{ \Illuminate\Support\Str::limit($expense->chain_hash, 16, '…') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (count($budgetChainBlocks) > 0)
                        <div style="display:grid;gap:0.45rem;">
                            @foreach ($budgetChainBlocks as $block)
                                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:0.5rem;padding:0.55rem 0.7rem;border:1px solid #f0e6e8;border-radius:12px;background:#fdfafb;font-size:0.78rem;">
                                    <span><strong>#{{ $block['index'] ?? '—' }}</strong> {{ $block['item_name'] ?? 'Expense' }} · ₱{{ number_format((float) ($block['total'] ?? 0), 2) }}</span>
                                    <span style="font-family:ui-monospace,monospace;color:#15803d;">{{ \Illuminate\Support\Str::limit($block['block_hash'] ?? '', 18, '…') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </section>

        @include('portal.student-voice')

        {{-- Activities & Announcements (Upcoming, Recent, Announcements Side-by-Side Table Cards) --}}
        <section class="sp-section">
            <div class="sp-activities-grid">
                {{-- Left: Upcoming Activities Card --}}
                <div class="sp-activity-table-card liquid-glass" data-sp-paginated-card>
                    <div class="sp-table-card-head">
                        <div class="sp-table-card-title">
                            <h3><i class="bi bi-calendar-event"></i> Upcoming Activities</h3>
                            <p>Activities scheduled ahead.</p>
                        </div>
                        <span class="sp-section-tag">{{ $upcomingCount }} scheduled</span>
                    </div>

                    <div class="sp-table-responsive">
                        <table class="sp-act-table" data-sp-table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($upcoming as $activity)
                                    @php
                                        $starts = $activity->starts_at;
                                        $day = $starts?->format('j M') ?? 'TBA';
                                        $time = $starts?->format('g:i A') ?? '';
                                    @endphp
                                    <tr class="sp-act-row">
                                        <td class="sp-col-date">
                                            <div class="sp-tbl-date">
                                                <strong>{{ $day }}</strong>
                                                @if($time)<small>{{ $time }}</small>@endif
                                            </div>
                                        </td>
                                        <td class="sp-col-info">
                                            <span class="sp-tbl-act-title">{{ $activity->title }}</span>
                                            @if (!empty($activity->sdg_goals))
                                                <span class="sp-tbl-act-loc" style="display:block;margin-top:0.2rem;">
                                                    <i class="bi bi-globe2"></i> {{ collect($activity->sdg_goals)->implode(', ') }}
                                                </span>
                                            @endif
                                            @if (!empty($activity->location))
                                                <span class="sp-tbl-act-loc"><i class="bi bi-geo-alt"></i> {{ $activity->location }}</span>
                                            @endif
                                        </td>
                                        <td class="sp-col-status text-end">
                                            <span class="sp-chip sp-chip-{{ $activity->status }}">
                                                <i class="bi bi-circle-fill"></i> {{ ucfirst($activity->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="sp-act-empty-row">
                                        <td colspan="3" class="sp-table-empty">
                                            <i class="bi bi-calendar-x"></i> No upcoming activities yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="sp-table-pagination" data-sp-pagination></div>
                </div>

                {{-- Middle: Recent Activities Card --}}
                <div class="sp-activity-table-card liquid-glass" data-sp-paginated-card>
                    <div class="sp-table-card-head">
                        <div class="sp-table-card-title">
                            <h3><i class="bi bi-clock-history"></i> Recent Activities</h3>
                            <p>Already completed events.</p>
                        </div>
                        <span class="sp-section-tag">{{ $completedCount }} done</span>
                    </div>

                    <div class="sp-table-responsive">
                        <table class="sp-act-table" data-sp-table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentActivities as $activity)
                                    @php
                                        $starts = $activity->starts_at;
                                        $day = $starts?->format('j M') ?? '—';
                                        $year = $starts?->format('Y') ?? '';
                                    @endphp
                                    <tr class="sp-act-row">
                                        <td class="sp-col-date">
                                            <div class="sp-tbl-date">
                                                <strong>{{ $day }}</strong>
                                                @if($year)<small>{{ $year }}</small>@endif
                                            </div>
                                        </td>
                                        <td class="sp-col-info">
                                            <span class="sp-tbl-act-title">{{ $activity->title }}</span>
                                            @if (!empty($activity->location))
                                                <span class="sp-tbl-act-loc"><i class="bi bi-geo-alt"></i> {{ $activity->location }}</span>
                                            @endif
                                        </td>
                                        <td class="sp-col-status text-end">
                                            <span class="sp-chip sp-chip-completed">
                                                <i class="bi bi-check-circle-fill"></i> Completed
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="sp-act-empty-row">
                                        <td colspan="3" class="sp-table-empty">
                                            <i class="bi bi-archive"></i> No completed activities yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="sp-table-pagination" data-sp-pagination></div>
                </div>

                {{-- Right: Announcements Card --}}
                <div class="sp-activity-table-card liquid-glass" data-sp-paginated-card>
                    <div class="sp-table-card-head">
                        <div class="sp-table-card-title">
                            <h3><i class="bi bi-megaphone-fill"></i> Announcements</h3>
                            <p>Official notices & updates.</p>
                        </div>
                        <span class="sp-section-tag">{{ count($announcements ?? []) }} updates</span>
                    </div>

                    <div class="sp-table-responsive">
                        <table class="sp-act-table" data-sp-table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Announcement</th>
                                    <th class="text-end">Priority</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($announcements ?? []) as $announce)
                                    <tr class="sp-act-row">
                                        <td class="sp-col-date">
                                            <div class="sp-tbl-date">
                                                <strong>{{ $announce['time'] ?? 'Recent' }}</strong>
                                                <small><i class="bi bi-person-badge"></i> {{ $announce['author'] ?? 'Admin' }}</small>
                                            </div>
                                        </td>
                                        <td class="sp-col-info">
                                            <span class="sp-tbl-act-title">{{ $announce['title'] }}</span>
                                            <button type="button" class="sp-see-details-btn"
                                                data-modal-title="{{ $announce['title'] }}"
                                                data-modal-body="{{ $announce['body'] }}"
                                                data-modal-author="{{ $announce['author'] ?? 'OSO Admin' }}"
                                                data-modal-time="{{ $announce['time'] ?? 'Recent' }}"
                                                data-modal-priority="{{ $announce['priority'] ?? 'normal' }}">
                                                <span>See details</span>
                                                <i class="bi bi-arrow-right-short"></i>
                                            </button>
                                        </td>
                                        <td class="sp-col-status text-end">
                                            <span class="sp-chip sp-chip-{{ $announce['priority'] ?? 'normal' }}">
                                                <i class="bi bi-circle-fill"></i> {{ ucfirst($announce['priority'] ?? 'normal') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="sp-act-empty-row">
                                        <td colspan="3" class="sp-table-empty">
                                            <i class="bi bi-megaphone"></i> No announcements yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="sp-table-pagination" data-sp-pagination></div>
                </div>
            </div>
        </section>
    </div>

    {{-- ============================ ACTIVITIES PANEL ============================ --}}
    <div class="portal-panel {{ ($tab ?? '') === 'activities' ? 'is-active' : '' }}" data-panel="activities" @if(($tab ?? '') !== 'activities') hidden @endif>



        @php
            // Aggregate all activities with fallback sample data if empty
            $allPortalActivities = $upcoming->concat($recentActivities);
            if ($allPortalActivities->isEmpty()) {
                $allPortalActivities = collect([
                    (object)[
                        'id' => 'sample-1',
                        'title' => 'Supreme Student Council Leadership Summit 2026',
                        'description' => 'Annual empowerment forum for student organization executives, council heads, and youth leaders across all campuses.',
                        'status' => 'upcoming',
                        'location' => 'University Amphitheater & Zoom',
                        'starts_at' => \Carbon\Carbon::now()->addDays(5)->setHour(9)->setMinute(0),
                        'ends_at' => \Carbon\Carbon::now()->addDays(5)->setHour(17)->setMinute(0),
                    ],
                    (object)[
                        'id' => 'sample-2',
                        'title' => 'OrgChain Hackathon & Tech Expo 2026',
                        'description' => '24-hour innovation sprint creating blockchain, AI, and transparent governance solutions for academic institutions.',
                        'status' => 'upcoming',
                        'location' => 'CICS Innovation Lab, 3rd Floor',
                        'starts_at' => \Carbon\Carbon::now()->addDays(12)->setHour(8)->setMinute(30),
                        'ends_at' => \Carbon\Carbon::now()->addDays(13)->setHour(18)->setMinute(0),
                    ],
                    (object)[
                        'id' => 'sample-3',
                        'title' => 'Campus Blood Donation & Health Drive',
                        'description' => 'Red Cross Youth Council partnership drive providing free health screening and voluntary blood donation drive.',
                        'status' => 'ongoing',
                        'location' => 'Student Center Gymnasium',
                        'starts_at' => \Carbon\Carbon::now()->subHours(2),
                        'ends_at' => \Carbon\Carbon::now()->addHours(4),
                    ],
                    (object)[
                        'id' => 'sample-4',
                        'title' => 'University Cultural Festival & Battle of the Bands',
                        'description' => 'A vibrant celebration of music, dance, and creative arts showcasing student talent across all academic colleges.',
                        'status' => 'completed',
                        'location' => 'Main Gymnasium Oval',
                        'starts_at' => \Carbon\Carbon::now()->subDays(6)->setHour(14)->setMinute(0),
                        'ends_at' => \Carbon\Carbon::now()->subDays(6)->setHour(21)->setMinute(30),
                    ],
                    (object)[
                        'id' => 'sample-5',
                        'title' => 'General Assembly & Org Orientation 2026',
                        'description' => 'Official orientation covering guidelines for accredited student organizations, budget disbursement, and event filing.',
                        'status' => 'completed',
                        'location' => 'Gov. Feliciano Leviste Memorial Hall',
                        'starts_at' => \Carbon\Carbon::now()->subDays(14)->setHour(13)->setMinute(0),
                        'ends_at' => \Carbon\Carbon::now()->subDays(14)->setHour(17)->setMinute(0),
                    ],
                    (object)[
                        'id' => 'sample-6',
                        'title' => 'Red Spartan Leadership Training & Skills Workshop',
                        'description' => 'Capacity-building seminar on project management, parliamentary procedures, and student council governance.',
                        'status' => 'upcoming',
                        'location' => 'Audio-Visual Center 1',
                        'starts_at' => \Carbon\Carbon::now()->addDays(18)->setHour(10)->setMinute(0),
                        'ends_at' => \Carbon\Carbon::now()->addDays(18)->setHour(16)->setMinute(30),
                    ],
                    (object)[
                        'id' => 'sample-7',
                        'title' => 'Community Outreach & Book Drive for Public Schools',
                        'description' => 'Volunteer outreach distributing reading kits, textbooks, and learning supplies to partner community schools in Batangas.',
                        'status' => 'upcoming',
                        'location' => 'Batangas City Community Center',
                        'starts_at' => \Carbon\Carbon::now()->addDays(22)->setHour(8)->setMinute(0),
                        'ends_at' => \Carbon\Carbon::now()->addDays(22)->setHour(15)->setMinute(0),
                    ],
                    (object)[
                        'id' => 'sample-8',
                        'title' => 'Engineering & Technology Research Colloquium',
                        'description' => 'Symposium of senior student research capstones and innovative prototype demonstrations.',
                        'status' => 'completed',
                        'location' => 'CEAFA Multipurpose Hall',
                        'starts_at' => \Carbon\Carbon::now()->subDays(20)->setHour(9)->setMinute(0),
                        'ends_at' => \Carbon\Carbon::now()->subDays(20)->setHour(17)->setMinute(0),
                    ],
                    (object)[
                        'id' => 'sample-9',
                        'title' => 'BatStateU Mental Health & Wellness Forum',
                        'description' => 'Student wellness forum addressing stress management, psychological support, and peer counselling networks.',
                        'status' => 'ongoing',
                        'location' => 'Guidance & Counselling Center Hall',
                        'starts_at' => \Carbon\Carbon::now()->subHours(1),
                        'ends_at' => \Carbon\Carbon::now()->addHours(3),
                    ],
                    (object)[
                        'id' => 'sample-10',
                        'title' => 'University Environmental Clean-up & Tree Planting Drive',
                        'description' => 'Eco-sustainability initiative planting native trees and conducting coastal rehabilitation across Batangas.',
                        'status' => 'completed',
                        'location' => 'Verde Island Passage Coastal Area',
                        'starts_at' => \Carbon\Carbon::now()->subDays(28)->setHour(6)->setMinute(30),
                        'ends_at' => \Carbon\Carbon::now()->subDays(28)->setHour(12)->setMinute(0),
                    ],
                ]);
            }

            $actTotal = $allPortalActivities->count();
            $actUpcoming = $allPortalActivities->where('status', 'upcoming')->count();
            $actOngoing = $allPortalActivities->where('status', 'ongoing')->count();
            $actCompleted = $allPortalActivities->where('status', 'completed')->count();
        @endphp

        {{-- Filter & Control Toolbar --}}
        <section class="sp-act-toolbar-card liquid-glass">
            <div class="sp-act-toolbar-row">
                {{-- Search Box --}}
                <div class="sp-act-search-box">
                    <i class="bi bi-search"></i>
                    <input type="search" id="spActSearchInput" placeholder="Filter by title, location, or keyword..." aria-label="Filter activities">
                    <button type="button" class="sp-act-search-clear" id="spActSearchClear" aria-label="Clear search" hidden>
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                {{-- Status Pills --}}
                <div class="sp-act-filter-pills" role="tablist" aria-label="Filter activities by status">
                    <button type="button" class="sp-filter-pill is-active" data-act-filter="all" role="tab" aria-selected="true">
                        <span>All</span>
                        <span class="sp-pill-count">{{ $actTotal }}</span>
                    </button>
                    <button type="button" class="sp-filter-pill" data-act-filter="upcoming" role="tab" aria-selected="false">
                        <i class="bi bi-calendar-event"></i>
                        <span>Upcoming</span>
                        <span class="sp-pill-count">{{ $actUpcoming }}</span>
                    </button>
                    <button type="button" class="sp-filter-pill" data-act-filter="ongoing" role="tab" aria-selected="false">
                        <i class="bi bi-broadcast"></i>
                        <span>Ongoing</span>
                        <span class="sp-pill-count">{{ $actOngoing }}</span>
                    </button>
                    <button type="button" class="sp-filter-pill" data-act-filter="completed" role="tab" aria-selected="false">
                        <i class="bi bi-check2-circle"></i>
                        <span>Completed</span>
                        <span class="sp-pill-count">{{ $actCompleted }}</span>
                    </button>
                </div>

                {{-- View Toggle --}}
                <div class="sp-act-view-toggle">
                    <button type="button" class="sp-view-btn is-active" id="spViewGridBtn" title="Grid View" aria-label="Grid view">
                        <i class="bi bi-grid-fill"></i>
                    </button>
                    <button type="button" class="sp-view-btn" id="spViewListBtn" title="List View" aria-label="List view">
                        <i class="bi bi-list-ul"></i>
                    </button>
                </div>
            </div>
        </section>

        {{-- Activities Container (Grid View) --}}
        <div class="sp-act-cards-grid" id="spActivitiesGridView">
            @foreach ($allPortalActivities as $act)
                @php
                    $starts = $act->starts_at;
                    $ends = $act->ends_at;
                    $month = $starts ? $starts->format('M') : 'TBA';
                    $day = $starts ? $starts->format('d') : '--';
                    $weekday = $starts ? $starts->format('l') : 'Date TBA';
                    $timeRange = $starts ? $starts->format('g:i A') . ($ends ? ' - ' . $ends->format('g:i A') : '') : 'Schedule TBA';
                    $status = $act->status ?? 'upcoming';
                    $loc = $act->location ?? 'BatStateU Campus';
                    $desc = $act->description ?? 'No detailed description provided.';
                    $org = $act->organizer ?? 'BatStateU Student Organization';

                    $regCount = $act->participants['registered'] ?? ($status === 'completed' ? 420 : 185);
                    $capCount = $act->participants['capacity'] ?? ($status === 'completed' ? 420 : 250);
                    $budgetAlloc = $act->budget['allocated'] ?? 35000;
                    $budgetSpent = $act->budget['utilized'] ?? ($budgetAlloc * 0.91);

                    $actJson = json_encode([
                        'id' => $act->id,
                        'title' => $act->title,
                        'organizer' => $org,
                        'status' => $status,
                        'date' => $starts ? $starts->format('F j, Y (l)') : 'Date TBA',
                        'time' => $timeRange,
                        'location' => $loc,
                        'description' => $desc,
                        'objectives' => [
                            'Foster collaborative leadership and student empowerment across university campuses.',
                            'Deliver high-impact academic, cultural, and community service outcomes.',
                            'Ensure 100% transparent and auditable budget liquidation via OrgChain ledger.'
                        ],
                        'agenda' => [
                            ['time' => 'Assembly & Triage', 'title' => 'Registration, Attendance QR Scan & Kit Release', 'lead' => 'Secretariat Team'],
                            ['time' => 'Main Program', 'title' => $act->title . ' - Keynote & Sessions', 'lead' => $org],
                            ['time' => 'Evaluation & Wrap-up', 'title' => 'Student Feedback, Certificates & Documentation', 'lead' => 'Executive Committee']
                        ],
                        'participants' => [
                            'registered' => $regCount,
                            'capacity' => $capCount,
                            'target' => 'BatStateU Students & Org Officers',
                        ],
                        'photos' => [
                            ['url' => asset('voting-assets/img/Bg_SSC.jpg'), 'caption' => 'Plenary session & participant assembly', 'tag' => 'Assembly'],
                            ['url' => asset('voting-assets/img/ssc_pic.jpg'), 'caption' => 'Student leaders engaging in collaborative discussion', 'tag' => 'Delegates'],
                            ['url' => asset('voting-assets/img/HirayaNew.jpg'), 'caption' => 'Presentation and awarding highlights', 'tag' => 'Highlights']
                        ],
                        'budget' => [
                            'allocated' => $budgetAlloc,
                            'utilized' => $budgetSpent,
                            'remaining' => $budgetAlloc - $budgetSpent,
                            'rate' => round(($budgetSpent / max($budgetAlloc, 1)) * 100, 1),
                            'tx_hash' => '0x' . substr(md5($act->title . ($act->id ?? '1')), 0, 40),
                            'items' => !empty($publicExpenseItemsByActivity[$act->id])
                                ? collect($publicExpenseItemsByActivity[$act->id])->map(fn ($it) => [
                                    'category' => $it['name'],
                                    'allocated' => $it['total'],
                                    'spent' => $it['total'],
                                    'variance' => 0,
                                    'status' => 'Receipt Verified',
                                ])->all()
                                : [
                                    ['category' => 'Venue Logistics & Audio-Visual Setup', 'allocated' => $budgetAlloc * 0.45, 'spent' => $budgetSpent * 0.46, 'variance' => ($budgetAlloc * 0.45) - ($budgetSpent * 0.46), 'status' => 'Receipt Verified'],
                                    ['category' => 'Participant Handouts & Leadership Kits', 'allocated' => $budgetAlloc * 0.35, 'spent' => $budgetSpent * 0.34, 'variance' => ($budgetAlloc * 0.35) - ($budgetSpent * 0.34), 'status' => 'Receipt Verified'],
                                    ['category' => 'Refreshments & Volunteer Tokens', 'allocated' => $budgetAlloc * 0.20, 'spent' => $budgetSpent * 0.20, 'variance' => 0, 'status' => 'Audited by OSO']
                                ]
                        ]
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                @endphp
                <article class="sp-act-card liquid-glass"
                    data-act-item
                    data-status="{{ $status }}"
                    data-title="{{ strtolower($act->title) }}"
                    data-location="{{ strtolower($loc) }}"
                    data-desc="{{ strtolower($desc) }}">

                    <div class="sp-act-card-header">
                        <div class="sp-act-date-badge">
                            <span class="sp-act-date-month">{{ strtoupper($month) }}</span>
                            <strong class="sp-act-date-day">{{ $day }}</strong>
                        </div>
                        <div class="sp-act-card-meta">
                            <span class="sp-act-weekday">{{ $weekday }}</span>
                            <span class="sp-chip sp-chip-{{ $status }}">
                                <i class="bi bi-circle-fill"></i> {{ ucfirst($status) }}
                            </span>
                        </div>
                    </div>

                    <div class="sp-act-card-body">
                        <h3 class="sp-act-card-title">{{ $act->title }}</h3>
                        <div class="sp-act-card-details">
                            <p class="sp-act-detail-item">
                                <i class="bi bi-clock"></i>
                                <span>{{ $timeRange }}</span>
                            </p>
                            <p class="sp-act-detail-item">
                                <i class="bi bi-geo-alt"></i>
                                <span>{{ $loc }}</span>
                            </p>
                        </div>
                        <p class="sp-act-card-desc">{{ Str::limit($desc, 120) }}</p>

                        {{-- Quick metrics pill bar --}}
                        <div class="sp-act-quick-stats">
                            <span class="sp-stat-pill" title="Registered Participants">
                                <i class="bi bi-people-fill"></i>
                                <strong>{{ $regCount }}</strong> attendees
                            </span>
                            <span class="sp-stat-pill" title="Budget Allocation">
                                <i class="bi bi-wallet2"></i>
                                <strong>₱{{ number_format($budgetAlloc) }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="sp-act-card-footer">
                        <button type="button" class="sp-act-btn-details app-view-action" aria-label="View {{ $act->title }}"
                            data-act-modal-trigger
                            data-act-details="{{ $actJson }}"
                            data-title="{{ $act->title }}"
                            data-status="{{ $status }}"
                            data-date="{{ $starts ? $starts->format('F j, Y (l)') : 'Date TBA' }}"
                            data-time="{{ $timeRange }}"
                            data-location="{{ $loc }}"
                            data-desc="{{ $desc }}">
                            <i class="bi bi-eye" aria-hidden="true"></i> <span>View</span>
                        </button>

                        <button type="button" class="sp-act-btn-discuss" data-act-id="{{ $act->id }}" data-act-title="{{ $act->title }}" title="Discuss in Community">
                            <i class="bi bi-chat-dots-fill"></i>
                            <span>Discuss</span>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Activities Container (List View) --}}
        <div class="sp-act-list-view liquid-glass" id="spActivitiesListView" hidden>
            <div class="sp-table-responsive">
                <table class="sp-act-table sp-act-full-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Event Details</th>
                            <th>Venue</th>
                            <th>Participants</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allPortalActivities as $act)
                            @php
                                $starts = $act->starts_at;
                                $ends = $act->ends_at;
                                $dateStr = $starts ? $starts->format('M d, Y') : 'TBA';
                                $timeStr = $starts ? $starts->format('g:i A') . ($ends ? ' - ' . $ends->format('g:i A') : '') : 'TBA';
                                $status = $act->status ?? 'upcoming';
                                $loc = $act->location ?? 'Campus';
                                $desc = $act->description ?? '';
                                $org = $act->organizer ?? 'Student Organization';
                                $regCount = $act->participants['registered'] ?? ($status === 'completed' ? 420 : 185);
                                $capCount = $act->participants['capacity'] ?? ($status === 'completed' ? 420 : 250);
                                $budgetAlloc = $act->budget['allocated'] ?? 35000;
                                $budgetSpent = $act->budget['utilized'] ?? ($budgetAlloc * 0.91);

                                $actJson = json_encode([
                                    'id' => $act->id,
                                    'title' => $act->title,
                                    'organizer' => $org,
                                    'status' => $status,
                                    'date' => $starts ? $starts->format('F j, Y (l)') : 'Date TBA',
                                    'time' => $timeStr,
                                    'location' => $loc,
                                    'description' => $desc,
                                    'objectives' => [
                                        'Foster collaborative leadership and student empowerment across university campuses.',
                                        'Deliver high-impact academic, cultural, and community service outcomes.',
                                        'Ensure 100% transparent and auditable budget liquidation via OrgChain ledger.'
                                    ],
                                    'agenda' => [
                                        ['time' => 'Assembly & Triage', 'title' => 'Registration, Attendance QR Scan & Kit Release', 'lead' => 'Secretariat Team'],
                                        ['time' => 'Main Program', 'title' => $act->title . ' - Keynote & Sessions', 'lead' => $org],
                                        ['time' => 'Evaluation & Wrap-up', 'title' => 'Student Feedback, Certificates & Documentation', 'lead' => 'Executive Committee']
                                    ],
                                    'participants' => [
                                        'registered' => $regCount,
                                        'capacity' => $capCount,
                                        'target' => 'BatStateU Students & Org Officers',
                                    ],
                                    'photos' => [
                                        ['url' => asset('voting-assets/img/Bg_SSC.jpg'), 'caption' => 'Plenary session & participant assembly', 'tag' => 'Assembly'],
                                        ['url' => asset('voting-assets/img/ssc_pic.jpg'), 'caption' => 'Student leaders engaging in collaborative discussion', 'tag' => 'Delegates'],
                                        ['url' => asset('voting-assets/img/HirayaNew.jpg'), 'caption' => 'Presentation and awarding highlights', 'tag' => 'Highlights']
                                    ],
                                    'budget' => [
                                        'allocated' => $budgetAlloc,
                                        'utilized' => $budgetSpent,
                                        'remaining' => $budgetAlloc - $budgetSpent,
                                        'rate' => round(($budgetSpent / max($budgetAlloc, 1)) * 100, 1),
                                        'tx_hash' => '0x' . substr(md5($act->title . ($act->id ?? '1')), 0, 40),
                                        'items' => !empty($publicExpenseItemsByActivity[$act->id])
                                            ? collect($publicExpenseItemsByActivity[$act->id])->map(fn ($it) => [
                                                'category' => $it['name'],
                                                'allocated' => $it['total'],
                                                'spent' => $it['total'],
                                                'variance' => 0,
                                                'status' => 'Receipt Verified',
                                            ])->all()
                                            : [
                                                ['category' => 'Venue Logistics & Audio-Visual Setup', 'allocated' => $budgetAlloc * 0.45, 'spent' => $budgetSpent * 0.46, 'variance' => ($budgetAlloc * 0.45) - ($budgetSpent * 0.46), 'status' => 'Receipt Verified'],
                                                ['category' => 'Participant Handouts & Leadership Kits', 'allocated' => $budgetAlloc * 0.35, 'spent' => $budgetSpent * 0.34, 'variance' => ($budgetAlloc * 0.35) - ($budgetSpent * 0.34), 'status' => 'Receipt Verified'],
                                                ['category' => 'Refreshments & Volunteer Tokens', 'allocated' => $budgetAlloc * 0.20, 'spent' => $budgetSpent * 0.20, 'variance' => 0, 'status' => 'Audited by OSO']
                                            ]
                                    ]
                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            @endphp
                            <tr data-act-item
                                data-status="{{ $status }}"
                                data-title="{{ strtolower($act->title) }}"
                                data-location="{{ strtolower($loc) }}"
                                data-desc="{{ strtolower($desc) }}">
                                <td class="sp-col-date">
                                    <div class="sp-tbl-date">
                                        <strong>{{ $dateStr }}</strong>
                                        <small>{{ $timeStr }}</small>
                                    </div>
                                </td>
                                <td class="sp-col-info">
                                    <span class="sp-tbl-act-title">{{ $act->title }}</span>
                                    <small class="sp-tbl-act-sub">{{ Str::limit($desc, 80) }}</small>
                                </td>
                                <td class="sp-col-loc">
                                    <span class="sp-tbl-loc-text"><i class="bi bi-geo-alt"></i> {{ $loc }}</span>
                                </td>
                                <td class="sp-col-part">
                                    <span class="sp-tbl-part-text"><i class="bi bi-people-fill"></i> <strong>{{ $regCount }}</strong></span>
                                </td>
                                <td class="sp-col-budget">
                                    <span class="sp-tbl-budget-text"><i class="bi bi-wallet2"></i> ₱{{ number_format($budgetAlloc) }}</span>
                                </td>
                                <td class="sp-col-status">
                                    <span class="sp-chip sp-chip-{{ $status }}">
                                        <i class="bi bi-circle-fill"></i> {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td class="sp-col-action text-end">
                                    <button type="button" class="sp-act-list-action-btn"
                                        data-act-modal-trigger
                                        data-act-details="{{ $actJson }}"
                                        data-title="{{ $act->title }}"
                                        data-status="{{ $status }}"
                                        data-date="{{ $starts ? $starts->format('F j, Y (l)') : 'Date TBA' }}"
                                        data-time="{{ $timeStr }}"
                                        data-location="{{ $loc }}"
                                        data-desc="{{ $desc }}">
                                        Details
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Activity list pagination --}}
            <div class="sp-table-pagination" id="spActListPagination" style="display: none;"></div>
        </div>

        {{-- Empty Search / Filter State --}}
        <div class="sp-act-empty-state liquid-glass" id="spActEmptyState" hidden>
            <div class="sp-act-empty-icon">
                <i class="bi bi-calendar-x"></i>
            </div>
            <h3>No activities found</h3>
            <p>No events match your current filter or search criteria.</p>
            <button type="button" class="btn btn-primary" id="spActResetFilterBtn">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
            </button>
        </div>
    </div>

    {{-- ============================ ANNOUNCEMENTS PANEL ============================ --}}
    <div class="portal-panel {{ ($tab ?? '') === 'announcements' ? 'is-active' : '' }}" data-panel="announcements" @if(($tab ?? '') !== 'announcements') hidden @endif>

        {{-- Top Tab Bar & Category Nav --}}
        <div class="sp-announcements-nav-bar">
            <div class="sp-announcements-tabs" id="spAnnounceTabs">
                <button type="button" class="sp-announcements-tab-btn is-active" data-announce-tab="all">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>All Bulletins</span>
                </button>
                <button type="button" class="sp-announcements-tab-btn" data-announce-tab="general">
                    <i class="bi bi-megaphone"></i>
                    <span>General Announcements</span>
                </button>
                <button type="button" class="sp-announcements-tab-btn" data-announce-tab="tosa">
                    <i class="bi bi-award-fill"></i>
                    <span>Call for TOSA</span>
                </button>
                <button type="button" class="sp-announcements-tab-btn" data-announce-tab="activities">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>Activities</span>
                </button>
                <button type="button" class="sp-announcements-tab-btn" data-announce-tab="dates">
                    <i class="bi bi-calendar-event"></i>
                    <span>Important Dates</span>
                </button>
                <button type="button" class="sp-announcements-tab-btn" data-announce-tab="deadlines">
                    <i class="bi bi-clock-history"></i>
                    <span>Deadline Reminders</span>
                </button>
                <button type="button" class="sp-announcements-tab-btn" data-announce-tab="oso">
                    <i class="bi bi-info-circle"></i>
                    <span>OSO Notices</span>
                </button>
            </div>
            <div class="sp-announcements-readonly-badge">
                <i class="bi bi-lock-fill"></i>
                <span>Read Only</span>
            </div>
        </div>


        {{-- Announcements Table Card --}}
        <div class="sp-announcements-table-card liquid-glass">

            {{-- Filter & Search Toolbar --}}
            <div class="sp-announcements-filter-bar">
                <div class="sp-ann-filter-group">
                    <div class="sp-ann-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="search" id="spAnnounceSearchInput" placeholder="Search bulletins, keywords, authors..." autocomplete="off">
                    </div>
                    <select id="spAnnouncePriorityFilter" class="sp-ann-select" aria-label="Filter by priority">
                        <option value="all">All Priorities</option>
                        <option value="high">High Priority</option>
                        <option value="normal">Normal Priority</option>
                    </select>
                    <select id="spAnnounceSortSelect" class="sp-ann-select" aria-label="Sort bulletins">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
                <div class="sp-ann-count-badge" id="spAnnounceCountBadge">
                    {{ count($bulletins ?? []) }} bulletins
                </div>
            </div>

            {{-- Responsive Table --}}
            <div class="sp-table-responsive">
                <table class="sp-announcements-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Bulletin Headline</th>
                            <th>Issued By</th>
                            <th>Priority</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="spAnnouncementsTbody">
                        @php
                            $announcementsList = $bulletins ?? [];
                        @endphp

                        @foreach ($announcementsList as $item)
                            <tr class="sp-announce-tbl-row"
                                data-announcement-id="{{ $item['announcement_id'] ?? '' }}"
                                data-announce-cat="{{ $item['category'] }}"
                                data-announce-priority="{{ $item['priority'] }}"
                                data-announce-order="{{ $item['sort'] ?? 0 }}"
                                data-announce-search="{{ strtolower($item['title'] . ' ' . $item['body'] . ' ' . $item['author'] . ' ' . $item['tag']) }}"
                                data-modal-title="{{ $item['title'] }}"
                                data-modal-body="{{ $item['body'] }}"
                                data-modal-author="{{ $item['author'] }}"
                                data-modal-time="Posted on {{ $item['date'] }}"
                                data-modal-priority="{{ $item['priority'] }}">
                                <td class="sp-col-date">
                                    <div class="sp-tbl-date">
                                        <strong>{{ $item['day'] }}</strong>
                                        <small>{{ $item['year'] }}</small>
                                    </div>
                                </td>
                                <td class="sp-col-category">
                                    <span class="sp-announcement-category-pill {{ $item['pill_class'] }}">{{ $item['tag'] }}</span>
                                </td>
                                <td class="sp-col-content">
                                    <strong class="sp-ann-tbl-title">{{ $item['title'] }}</strong>
                                </td>
                                <td class="sp-col-author">
                                    <span class="sp-ann-author-badge">
                                        <i class="bi bi-person-badge"></i> {{ $item['author'] }}
                                    </span>
                                </td>
                                <td class="sp-col-priority">
                                    <span class="sp-chip sp-chip-{{ $item['priority'] }}">
                                        <i class="bi bi-circle-fill"></i> {{ ucfirst($item['priority']) }}
                                    </span>
                                </td>
                                <td class="sp-col-action text-end">
                                    <button type="button" class="sp-see-details-btn"
                                        data-modal-title="{{ $item['title'] }}"
                                        data-modal-body="{{ $item['body'] }}"
                                        data-modal-author="{{ $item['author'] }}"
                                        data-modal-time="Posted on {{ $item['date'] }}"
                                        data-modal-priority="{{ $item['priority'] }}">
                                        <span>Details</span>
                                        <i class="bi bi-arrow-right-short"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Empty state row --}}
                        <tr id="spAnnounceEmptyRow" style="display: none;">
                            <td colspan="6" class="text-center py-5">
                                <div style="padding: 2.5rem 1rem; color: #6b7280;">
                                    <i class="bi bi-inbox fs-1 d-block mb-2" style="opacity: 0.5;"></i>
                                    <strong style="font-size: 1rem; color: #1f2937;">No matching bulletins found</strong>
                                    <p style="font-size: 0.84rem; margin: 0.35rem 0 0;">Try adjusting your search query, priority, or category filter.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer Bar --}}
            <div class="sp-announcements-pagination-bar" id="spAnnouncePaginationBar">
                <div class="sp-ann-page-info">
                    Showing <span id="spAnnouncePageRange">0</span> of <span id="spAnnounceTotalItems">{{ count($bulletins ?? []) }}</span> bulletins
                </div>
                <div class="sp-ann-page-controls">
                    <button type="button" class="sp-ann-page-btn" id="spAnnouncePrevBtn" aria-label="Previous page">
                        Previous
                    </button>
                    <div id="spAnnouncePageNumbers" style="display: inline-flex; gap: 0.35rem;"></div>
                    <button type="button" class="sp-ann-page-btn" id="spAnnounceNextBtn" aria-label="Next page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ TOSA APPLICATIONS PANEL ============================ --}}
    <div class="portal-panel {{ ($tab ?? '') === 'tosa' ? 'is-active' : '' }}" data-panel="tosa" @if(($tab ?? '') !== 'tosa') hidden @endif>
        <link rel="stylesheet" href="{{ asset('css/document-preview.css') }}">
        <style>
            .portal-body[data-portal-tab="tosa"] .sp-main,
            .portal-body[data-portal-tab="tosa"] #portalMain { min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; }
            .portal-body[data-portal-tab="tosa"] .sp-flash,
            .portal-body[data-portal-tab="tosa"] .sp-flash span { min-width: 0; overflow-wrap: anywhere; }
            [data-panel="tosa"] { min-width: 0; width: 100%; max-width: 100%; grid-template-columns: minmax(0, 1fr); }
            [data-panel="tosa"] [hidden] { display: none !important; }
            [data-panel="tosa"] .sp-tosa-submissions-view { display: grid; gap: .4rem; min-width: 0; width: 100%; max-width: none; padding-bottom: .35rem; }
            [data-panel="tosa"] .sp-tosa-card { min-width: 0; padding: .6rem; margin: 0; border: 1px solid #ead8de; border-radius: 12px; background: #fff; overflow-wrap: anywhere; }
            [data-panel="tosa"] .sp-tosa-accordion { padding: 0; }
            [data-panel="tosa"] .sp-tosa-section-summary { display: flex; align-items: center; gap: .55rem; padding: .55rem .65rem; list-style: none; cursor: pointer; background: #fff8fa; border-radius: 12px; }
            [data-panel="tosa"] .sp-tosa-section-summary::-webkit-details-marker { display: none; }
            [data-panel="tosa"] .sp-tosa-section-summary:hover { background: #fdf0f2; }
            [data-panel="tosa"] .sp-tosa-section-copy { flex: 1; min-width: 0; }
            [data-panel="tosa"] .sp-tosa-section-heading { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem; color: #33272c; font-size: .9rem; }
            [data-panel="tosa"] .sp-tosa-section-copy > small { margin-top: .15rem; line-height: 1.4; }
            [data-panel="tosa"] .sp-tosa-section-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .45rem; border-radius: 999px; background: #f5e5e9; color: #8b1828; font-size: .65rem; font-weight: 700; }
            [data-panel="tosa"] .sp-tosa-section-chevron { flex: 0 0 auto; color: #8b1828; font-size: .8rem; }
            [data-panel="tosa"] .sp-tosa-accordion[open] > .sp-tosa-section-summary { border-radius: 12px 12px 0 0; }
            [data-panel="tosa"] .sp-tosa-accordion[open] > .sp-tosa-section-summary > .sp-tosa-section-chevron { transform: rotate(90deg); }
            [data-panel="tosa"] .sp-tosa-section-body { padding: .55rem; border-top: 1px solid #ead8de; }
            [data-panel="tosa"] .sp-tosa-master-form { display: flex; align-items: flex-start; justify-content: space-between; gap: .8rem; flex-wrap: wrap; }
            [data-panel="tosa"] .sp-tosa-master-copy { display: flex; align-items: flex-start; gap: .7rem; min-width: 0; flex: 1 1 22rem; }
            [data-panel="tosa"] .sp-tosa-master-copy > div { min-width: 0; }
            [data-panel="tosa"] .sp-tosa-master-label { display: block; color: #8b1828; font-size: .65rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
            [data-panel="tosa"] .sp-tosa-master-copy strong { display: block; margin-top: .15rem; font-size: .9rem; }
            [data-panel="tosa"] .sp-tosa-card p { margin: .25rem 0; font-size: .8rem; color: #786f73; line-height: 1.4; }
            [data-panel="tosa"] .sp-tosa-card small { display: block; color: #786f73; font-size: .72rem; margin-top: .2rem; }
            [data-panel="tosa"] .sp-tosa-card h3 { margin: 0; font-size: .9rem; }
            [data-panel="tosa"] .sp-tosa-requirements { display: grid; grid-template-columns: minmax(0, 1fr); gap: .45rem; }
            [data-panel="tosa"] .sp-tosa-row { min-width: 0; display: grid; grid-template-columns: minmax(0, 1fr) minmax(12rem, 17rem); align-items: start; gap: .65rem; padding: .65rem; border: 1px solid #f0e6e8; border-radius: 11px; }
            [data-panel="tosa"] .sp-tosa-row-main { display: flex; align-items: flex-start; gap: .6rem; min-width: 0; }
            [data-panel="tosa"] .sp-tosa-row-copy { min-width: 0; }
            [data-panel="tosa"] .sp-tosa-file-icon { display: grid; place-items: center; flex: 0 0 auto; width: 2rem; height: 2rem; border-radius: 8px; background: #fdf0f2; color: #8b1828; }
            [data-panel="tosa"] .sp-tosa-row-title { display: flex; align-items: baseline; flex-wrap: wrap; gap: .4rem; }
            [data-panel="tosa"] .sp-tosa-section-code { color: #8b1828; font-size: .65rem; font-weight: 900; text-transform: uppercase; }
            [data-panel="tosa"] .sp-tosa-saved-file { margin-top: .5rem; font-size: .78rem; color: #786f73; }
            [data-panel="tosa"] .sp-tosa-saved-file.is-uploaded > strong { color: #8b1828; }
            [data-panel="tosa"] .sp-tosa-filename { display: block; max-width: 100%; margin-top: .2rem; color: #8b1828; font-size: .78rem; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; overflow-wrap: anywhere; }
            [data-panel="tosa"] .sp-tosa-row-actions { display: grid; gap: .55rem; min-width: 0; }
            [data-panel="tosa"] .sp-tosa-document-links { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem; }
            [data-panel="tosa"] .sp-tosa-control { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; padding: .4rem .65rem; border: 1px solid #e6d5d9; border-radius: 8px; background: #fff; color: #8b1828; font: inherit; font-size: .75rem; font-weight: 700; text-decoration: none; cursor: pointer; box-sizing: border-box; max-width: 100%; }
            [data-panel="tosa"] .sp-tosa-control:hover { background: #fdf0f2; }
            [data-panel="tosa"] .sp-tosa-control-primary { background: #8b1828; border-color: #8b1828; color: #fff; }
            [data-panel="tosa"] .sp-tosa-control-primary:hover { background: #701321; }
            [data-panel="tosa"] .sp-tosa-control:disabled { opacity: .55; cursor: not-allowed; }
            [data-panel="tosa"] .sp-tosa-file-picker { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem; position: relative; }
            [data-panel="tosa"] .sp-tosa-file-input { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip-path: inset(50%); border: 0; }
            [data-panel="tosa"] .sp-tosa-file-input:focus-visible + label { outline: 2px solid #8b1828; outline-offset: 3px; }
            [data-panel="tosa"] .sp-tosa-selection { display: flex; align-items: center; gap: .5rem; order: -1; flex-basis: 100%; min-width: 0; padding: .5rem; border-radius: 8px; background: #fdf0f2; color: #8b1828; font-size: .75rem; }
            [data-panel="tosa"] .sp-tosa-selection > i { flex: 0 0 auto; font-size: 1.3rem; }
            [data-panel="tosa"] .sp-tosa-selection-copy { min-width: 0; }
            [data-panel="tosa"] .sp-tosa-selection .sp-tosa-filename { margin: 0; text-decoration: none; }
            [data-panel="tosa"] .sp-tosa-file-feedback { order: 1; flex-basis: 100%; margin: 0; color: #a12638; font-size: .75rem; overflow-wrap: anywhere; }
            [data-panel="tosa"] .sp-tosa-reference-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; list-style: none; margin: 0; padding: 0; }
            [data-panel="tosa"] .sp-tosa-reference-list li { min-width: 0; padding: .55rem .65rem; border: 1px solid #ead8de; border-radius: 10px; font-size: .8rem; }
            [data-panel="tosa"] .sp-tosa-reference-heading { display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem; }
            [data-panel="tosa"] .sp-tosa-reference-heading > strong { min-width: 0; }
            [data-panel="tosa"] .sp-tosa-reference-list .sp-tosa-document-links { margin-top: .5rem; }
            [data-panel="tosa"] .sp-tosa-submit-banner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .8rem; }
            [data-panel="tosa"] .sp-tosa-submit-banner > div { min-width: 0; flex: 1 1 22rem; }
            [data-panel="tosa"] #tosaSubmitForm { min-width: 0; margin: 0; }
            [data-panel="tosa"] [data-tosa-submit-wrapper] { position: relative; }
            [data-panel="tosa"] .sp-tosa-submit-tooltip { position: absolute; right: 0; bottom: calc(100% + .5rem); z-index: 80; width: max-content; max-width: min(20rem, calc(100vw - 3rem)); padding: .6rem .75rem; border-radius: 8px; background: #40151f; color: #fff; font-size: .75rem; line-height: 1.4; box-shadow: 0 4px 14px #40151f26; visibility: hidden; opacity: 0; pointer-events: none; }
            [data-panel="tosa"] [data-tosa-submit-wrapper]:hover .sp-tosa-submit-tooltip,
            [data-panel="tosa"] [data-tosa-submit-wrapper]:focus .sp-tosa-submit-tooltip { visibility: visible; opacity: 1; }
            [data-panel="tosa"] a:focus-visible, [data-panel="tosa"] button:focus-visible, [data-panel="tosa"] summary:focus-visible { outline: 2px solid #8b1828; outline-offset: 3px; }
            @media (max-width: 760px) {
                [data-panel="tosa"] .sp-tosa-row { grid-template-columns: minmax(0, 1fr); }
                [data-panel="tosa"] .sp-tosa-card:not(.sp-tosa-accordion) { padding: .65rem; }
                [data-panel="tosa"] .sp-tosa-row-actions { gap: .6rem; }
            }
            @media (max-width: 520px) {
                [data-panel="tosa"] .sp-tosa-reference-list { grid-template-columns: minmax(0, 1fr); }
                [data-panel="tosa"] .sp-tosa-section-summary { gap: .5rem; padding: .55rem .6rem; }
                [data-panel="tosa"] .sp-tosa-section-body { padding: .6rem; }
                [data-panel="tosa"] .sp-tosa-section-heading { font-size: .85rem; }
            }
        </style>
        <div class="sp-tosa-submissions-view">

            @if ($errors->any())
                <div role="alert" style="color: #a12638; margin-bottom: 1rem;">
                    <strong>Your application could not be submitted.</strong>
                    <p>{{ $tosaLocked ? 'Your submitted application and saved files remain unchanged.' : 'Previously saved files remain unchanged. Reselect any local files before trying to submit.' }}</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $tosaMasterForm = collect($tosaReferences)->firstWhere('key', 'application_form');
                $tosaOtherReferences = collect($tosaReferences)->reject(fn ($reference) => $reference['key'] === 'application_form');
            @endphp

            @if ($tosaMasterForm)
                <details class="sp-tosa-card sp-tosa-accordion" open aria-labelledby="tosaSubmissionsTitle">
                    <summary class="sp-tosa-section-summary">
                        <span class="sp-tosa-file-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                        <span class="sp-tosa-section-copy">
                            <strong id="tosaSubmissionsTitle" class="sp-tosa-section-heading">Submissions</strong>
                            <small>{{ $tosaLocked ? 'View and download your submitted documents.' : 'Prepare your application and supporting credentials for OSO review.' }}</small>
                        </span>
                        <i class="bi bi-chevron-right sp-tosa-section-chevron" aria-hidden="true"></i>
                    </summary>
                    <div class="sp-tosa-section-body sp-tosa-master-form" data-tosa-reference="{{ $tosaMasterForm['key'] }}" aria-labelledby="tosaMasterFormTitle">
                    <div class="sp-tosa-master-copy">
                        <span class="sp-tosa-file-icon" aria-hidden="true"><i class="bi bi-file-earmark-word"></i></span>
                        <div>
                            <span class="sp-tosa-master-label">Official master application / nomination form</span>
                            <strong id="tosaMasterFormTitle">{{ $tosaMasterForm['title'] }}</strong>
                            <p>Complete the form with the required photo and signatures, then upload it in section B.</p>
                        </div>
                    </div>
                    <div class="sp-tosa-document-links">
                        @if ($tosaMasterForm['available'])
                            @if ($tosaMasterForm['can_preview'])
                                <a href="{{ $tosaMasterForm['preview_url'] }}" class="sp-tosa-control app-view-action" data-document-preview
                                   data-document-preview-url="{{ $tosaMasterForm['preview_url'] }}" data-document-preview-title="{{ $tosaMasterForm['name'] }}"
                                   data-document-preview-type="{{ strtolower($tosaMasterForm['preview_format']) }}" data-document-preview-download="{{ $tosaMasterForm['download_url'] }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i> View
                                </a>
                            @endif
                            <a href="{{ $tosaMasterForm['download_url'] }}" class="sp-tosa-control" aria-label="Download original {{ $tosaMasterForm['name'] }}">
                                <i class="bi bi-download" aria-hidden="true"></i> Download
                            </a>
                        @else
                            <span>Official file unavailable.</span>
                        @endif
                    </div>
                    </div>
                </details>
            @endif

            <details class="sp-tosa-card sp-tosa-accordion" @if ($tosaRevision) open @endif aria-labelledby="tosaStatusTitle">
                <summary class="sp-tosa-section-summary">
                    <span class="sp-tosa-file-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                    <span class="sp-tosa-section-copy">
                        <span class="sp-tosa-section-heading"><strong id="tosaStatusTitle">Application status</strong><span class="sp-tosa-section-badge">{{ $tosaRevision ? 'Revision' : ($tosaApplicant ? ucfirst($tosaApplicant->status) : 'Not started') }}</span></span>
                        <small>{{ $tosaApplicant ? 'Review stage: '.($tosaApplicant->subsection === 'returned' ? 'Revision' : ucfirst($tosaApplicant->subsection)).'.' : 'Choose your completed files to start your application.' }}</small>
                    </span>
                    <i class="bi bi-chevron-right sp-tosa-section-chevron" aria-hidden="true"></i>
                </summary>
                <div class="sp-tosa-section-body">
                @if ($tosaApplicant)
                    <p>Review stage: {{ $tosaApplicant->subsection === 'returned' ? 'Revision' : ucfirst($tosaApplicant->subsection) }}.</p>
                    @if ($tosaApplicant->remarks)
                        <p>Reviewer remarks: {{ $tosaApplicant->remarks }}</p>
                    @endif
                @endif
                <p>{{ $tosaLocked ? 'Only an OSO Revision request allows changes.' : ($tosaRevision ? 'Update the requested files, then Resubmit for Review.' : 'Choose files, then Submit for Review. Files are saved together.') }}</p>
                </div>
            </details>


            <details class="sp-tosa-card sp-tosa-accordion" open aria-labelledby="tosaReferencesTitle">
                <summary class="sp-tosa-section-summary">
                    <span class="sp-tosa-file-icon" aria-hidden="true"><i class="bi bi-book"></i></span>
                    <span class="sp-tosa-section-copy">
                        <span class="sp-tosa-section-heading"><strong id="tosaReferencesTitle">Preparation guides &amp; workbook</strong><span class="sp-tosa-section-badge">{{ $tosaOtherReferences->count() }} references</span></span>
                        <small>Official guides and the reference workbook—not required uploads.</small>
                    </span>
                    <i class="bi bi-chevron-right sp-tosa-section-chevron" aria-hidden="true"></i>
                </summary>
                <div class="sp-tosa-section-body">
                <ul class="sp-tosa-reference-list" id="tosaReferenceList">
                    @foreach ($tosaOtherReferences as $reference)
                        <li data-tosa-reference="{{ $reference['key'] }}">
                            <div class="sp-tosa-reference-heading">
                                <span class="sp-tosa-file-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                                <strong>{{ $reference['title'] }}</strong>
                            </div>
                            <div class="sp-tosa-document-links">
                                @if ($reference['available'])
                                    @if ($reference['key'] === 'computation')
                                        <a href="{{ route('portal.tosa.workbook.reference') }}" class="sp-tosa-control app-view-action" target="_blank" rel="noopener" aria-label="View computation reference workbook"><i class="bi bi-eye" aria-hidden="true"></i> View</a>
                                    @elseif ($reference['can_preview'])
                                        <a href="{{ $reference['preview_url'] }}" class="sp-tosa-control app-view-action" aria-label="View {{ $reference['name'] }}" data-document-preview
                                           data-document-preview-url="{{ $reference['preview_url'] }}" data-document-preview-title="{{ $reference['name'] }}"
                                           data-document-preview-type="{{ strtolower($reference['preview_format']) }}" data-document-preview-download="{{ $reference['download_url'] }}">
                                            <i class="bi bi-eye" aria-hidden="true"></i> View
                                        </a>
                                    @endif
                                    <a href="{{ $reference['download_url'] }}" class="sp-tosa-control" aria-label="Download official {{ $reference['title'] }} {{ $reference['format'] }} reference">
                                        <i class="bi bi-download" aria-hidden="true"></i> Download
                                    </a>
                                @else
                                    <span>Official file unavailable.</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                </div>
            </details>

            <details id="tosaCredentialsSection" class="sp-tosa-card sp-tosa-accordion" @if ($errors->any() || $tosaRevision) open @endif aria-labelledby="tosaRequirementsTitle">
                <summary class="sp-tosa-section-summary">
                    <span class="sp-tosa-file-icon" aria-hidden="true"><i class="bi bi-cloud-upload"></i></span>
                    <span class="sp-tosa-section-copy">
                        <strong id="tosaRequirementsTitle" class="sp-tosa-section-heading">Required supporting credentials · A–G</strong>
                        <small>{{ $tosaLocked ? 'View your submitted application and supporting credentials.' : 'Upload your completed form and supporting credentials.' }}</small>
                    </span>
                    <i class="bi bi-chevron-right sp-tosa-section-chevron" aria-hidden="true"></i>
                </summary>
                <div class="sp-tosa-section-body">
                <div id="tosaRequirementsList" class="sp-tosa-requirements">
                    @foreach ($tosaDocs as $doc)
                        <article id="tosaRow_{{ $doc['key'] }}" class="sp-tosa-row" data-tosa-key="{{ $doc['key'] }}" data-tosa-code="{{ $doc['code'] }}" data-tosa-saved="{{ $doc['uploaded'] ? '1' : '0' }}" aria-labelledby="tosaTitle_{{ $doc['key'] }}">
                            <div class="sp-tosa-row-main">
                                <span class="sp-tosa-file-icon" aria-hidden="true"><i class="bi {{ $doc['bi'] }}"></i></span>
                                <div class="sp-tosa-row-copy">
                                    <div class="sp-tosa-row-title">
                                        <span class="sp-tosa-section-code">Section {{ $doc['code'] }}</span>
                                        <h3 id="tosaTitle_{{ $doc['key'] }}">{{ $doc['title'] }}</h3>
                                    </div>
                                    <p>{{ $doc['desc'] }}</p>
                                    <small>{{ $doc['format'] }} · Required · Up to 25 MB</small>
                                    <div class="sp-tosa-saved-file {{ $doc['uploaded'] ? 'is-uploaded' : '' }}" data-tosa-saved-file>
                                        <strong><i class="bi {{ $doc['uploaded'] ? 'bi-check-circle-fill' : 'bi-clock' }}" aria-hidden="true"></i> {{ $doc['uploaded'] ? 'Uploaded · Saved' : ($doc['file'] ? 'File unavailable' : 'Not uploaded') }}</strong>
                                        @if ($doc['uploaded'])
                                            <a href="{{ $doc['can_preview'] ? $doc['preview_url'] : $doc['download_url'] }}" class="sp-tosa-filename" data-document-file
                                               data-document-preview-type="{{ strtolower($doc['file_format'] ?? '') }}" data-document-preview-download="{{ $doc['download_url'] }}"
                                               aria-label="View saved {{ data_get($doc['file'], 'name') }}">{{ data_get($doc['file'], 'name') }}</a>
                                        @elseif ($doc['file'])
                                            <small>{{ data_get($doc['file'], 'name') }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="sp-tosa-row-actions">
                                <div class="sp-tosa-document-links" data-tosa-saved-links>
                                    @if ($doc['uploaded'])
                                        <a href="{{ $doc['download_url'] }}" class="sp-tosa-control" aria-label="Download your saved {{ $doc['title'] }}">
                                            <i class="bi bi-download" aria-hidden="true"></i> Download
                                        </a>
                                    @endif
                                </div>
                                @unless ($tosaLocked)
                                    <div class="sp-tosa-file-picker">
                                        <input type="file" id="tosaInput_{{ $doc['key'] }}" class="sp-tosa-file-input" name="documents[{{ $doc['key'] }}]" form="tosaSubmitForm" accept="{{ $doc['accept'] }}" aria-describedby="tosaFeedback_{{ $doc['key'] }}" @required(!$doc['uploaded'])>
                                        <label for="tosaInput_{{ $doc['key'] }}" class="sp-tosa-control" data-tosa-picker-label>
                                            <i class="bi bi-folder2-open" aria-hidden="true"></i> <span data-tosa-picker-text>{{ $doc['uploaded'] ? 'Replace file' : 'Choose file' }}</span>
                                        </label>
                                        <div class="sp-tosa-selection" data-tosa-selection hidden aria-live="polite">
                                            <i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i>
                                            <div class="sp-tosa-selection-copy">
                                                <a class="sp-tosa-filename" data-document-file data-tosa-selected-file hidden></a>
                                                <small data-tosa-selected-meta></small>
                                            </div>
                                        </div>
                                        <p id="tosaFeedback_{{ $doc['key'] }}" class="sp-tosa-file-feedback" data-tosa-file-feedback role="status" hidden></p>
                                        <button type="button" class="sp-tosa-control" data-tosa-cancel hidden>Cancel selection</button>
                                    </div>
                                @endunless
                            </div>
                        </article>
                    @endforeach
                </div>
                </div>
            </details>

            @if ($tosaLocked)
                <details class="sp-tosa-card sp-tosa-accordion" aria-labelledby="tosaSubmitStatus">
                    <summary class="sp-tosa-section-summary">
                        <span class="sp-tosa-file-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
                        <span class="sp-tosa-section-copy">
                            <strong id="tosaSubmitStatus" class="sp-tosa-section-heading">Application submitted · Locked</strong>
                            <small>Your files are read-only unless OSO requests Revision.</small>
                        </span>
                        <i class="bi bi-chevron-right sp-tosa-section-chevron" aria-hidden="true"></i>
                    </summary>
                    <div class="sp-tosa-section-body">
                        <p id="tosaSubmitHelp">Your packet is with OSO. View and Download remain available. Only Revision allows editing and resubmission.</p>
                    </div>
                </details>
            @else
            <div class="sp-tosa-card sp-tosa-submit-banner">
                <div>
                    <strong id="tosaSubmitStatus">{{ $tosaRevision ? 'Revision · Correct and resubmit' : ($tosaComplete ? 'Package ready for review' : 'Before you submit') }}</strong>
                    <p id="tosaSubmitHelp" aria-live="polite">Submit the complete packet for OSO review. Your application locks after submission.</p>
                </div>
                    <form method="post" action="{{ route('portal.tosa.submit') }}" enctype="multipart/form-data" id="tosaSubmitForm" data-confirm="manual" data-tosa-revision="{{ $tosaRevision ? '1' : '0' }}" data-confirm-message="Submit your complete A–G packet for OSO review? All files will be saved together and locked. Changes are allowed only if OSO requests Revision.">
                        @csrf
                        <span data-tosa-submit-wrapper tabindex="{{ $tosaComplete ? '-1' : '0' }}" aria-label="Submission requirements" aria-describedby="tosaSubmitTooltip">
                        <button type="submit" class="sp-tosa-control sp-tosa-control-primary" aria-describedby="tosaSubmitHelp" @disabled(!$tosaComplete)>
                            <i class="bi bi-send-check" aria-hidden="true"></i> {{ $tosaRevision ? 'Resubmit for Review' : 'Submit for Review' }}
                        </button>
                        <span id="tosaSubmitTooltip" class="sp-tosa-submit-tooltip" role="tooltip" @if ($tosaComplete) hidden @endif>Complete the required A–G files before submitting.</span>
                        </span>
                    </form>
            </div>
            @endif
        </div>
    </div>

    <dialog id="tosaStudentDocViewerModal" class="document-preview-dialog" data-document-preview-dialog aria-labelledby="tosaStudentDocViewerTitle">
        <div class="document-preview-box">
            <header class="document-preview-head">
                <div class="document-preview-heading">
                    <span class="document-preview-icon" aria-hidden="true"><i class="bi bi-file-earmark-richtext"></i></span>
                    <div class="document-preview-title">
                        <strong id="tosaStudentDocViewerTitle" data-document-preview-title>Document Preview</strong>
                        <small>TOSA · Student application documents</small>
                    </div>
                </div>
                <div class="document-preview-actions">
                    <a href="#" download class="document-preview-action" data-document-preview-download><i class="bi bi-download" aria-hidden="true"></i> Download</a>
                    <a href="#" target="_blank" rel="noopener" class="document-preview-action" data-document-preview-external><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Open in Tab</a>
                    <button type="button" class="document-preview-action" data-document-preview-close aria-label="Close document preview"><i class="bi bi-x-lg" aria-hidden="true"></i> Close</button>
                </div>
            </header>
            <div class="document-preview-body" data-document-preview-body></div>
        </div>
    </dialog>
    <script src="{{ asset('js/vendor/jszip.min.js') }}" defer></script>
    <script src="{{ asset('js/vendor/docx-preview.min.js') }}" defer></script>
    <script src="{{ asset('js/document-preview.js') }}?v={{ filemtime(public_path('js/document-preview.js')) }}" defer></script>
    <script src="{{ asset('js/tosa-documents.js') }}?v={{ filemtime(public_path('js/tosa-documents.js')) }}" defer></script>

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

            <form class="sp-composer liquid-glass" method="post" action="{{ route('portal.community.posts.store') }}" enctype="multipart/form-data" data-confirm-message="Publish this post to your selected audience?">
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

                {{-- Active Attachment Pills (Photo, Tagged, Activity, Feeling) --}}
                <div class="sp-composer-chips" id="spComposerChips" hidden>
                    <div class="sp-composer-chip sp-chip-photo" id="spChipPhoto" hidden>
                        <i class="bi bi-image-fill sp-icon-green"></i>
                        <span id="spChipPhotoName">image.jpg</span>
                        <button type="button" class="sp-chip-remove" id="spRemovePhotoBtn" aria-label="Remove photo">&times;</button>
                    </div>
                    <div class="sp-composer-chip sp-chip-activity" id="spChipActivity" hidden>
                        <i class="bi bi-calendar2-event-fill sp-icon-red"></i>
                        <span id="spChipActivityName">Activity</span>
                        <button type="button" class="sp-chip-remove" id="spRemoveActivityBtn" aria-label="Remove activity">&times;</button>
                    </div>
                    <div class="sp-composer-chip sp-chip-feeling" id="spChipFeeling" hidden>
                        <i class="bi bi-emoji-smile-fill sp-icon-amber"></i>
                        <span id="spChipFeelingName">Feeling</span>
                        <button type="button" class="sp-chip-remove" id="spRemoveFeelingBtn" aria-label="Remove feeling">&times;</button>
                    </div>
                </div>

                {{-- Hidden Form Fields --}}
                <select name="activity_id" id="spComposerActivitySelect" hidden>
                    <option value="">General post</option>
                    @foreach ($activities as $activity)
                        <option value="{{ $activity->id }}" @selected(old('activity_id') == $activity->id)>{{ $activity->title }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="feeling" id="spComposerFeelingInput" value="">
                <input type="hidden" name="audience" id="spComposerAudienceInput" value="{{ old('audience', 'public') }}">

                {{-- Modern Composer Bottom Toolbar (Picture 1) --}}
                <div class="sp-composer-bar">
                    <div class="sp-composer-actions">
                        {{-- 1. Photo / Video --}}
                        <label class="sp-bar-action-btn sp-action-photo" title="Add Photo or Video">
                            <input type="file" name="photo" accept="image/*,video/*" id="communityPhotoInput">
                            <i class="bi bi-image-fill sp-icon-green"></i>
                            <span>Photo / Video</span>
                        </label>

                        {{-- 3. Activity --}}
                        <div class="sp-action-dropdown-wrap">
                            <button type="button" class="sp-bar-action-btn sp-action-activity" id="spComposerActivityBtn" title="Link Activity">
                                <i class="bi bi-calendar2-event-fill sp-icon-red"></i>
                                <span>Activity</span>
                            </button>
                            <div class="sp-action-popover sp-activity-popover liquid-glass" id="spActivityPopover" hidden>
                                <div class="sp-popover-head">
                                    <strong>Select Campus Activity</strong>
                                    <button type="button" class="sp-popover-close" id="spActivityPopoverClose">&times;</button>
                                </div>
                                <div class="sp-popover-body">
                                    <div class="sp-act-popover-list">
                                        <button type="button" class="sp-act-option is-active" data-act-id="" data-act-title="General post">
                                            <i class="bi bi-chat-left-text"></i>
                                            <span>General post (No activity link)</span>
                                        </button>
                                        @foreach ($activities as $activity)
                                            <button type="button" class="sp-act-option" data-act-id="{{ $activity->id }}" data-act-title="{{ $activity->title }}">
                                                <i class="bi bi-calendar2-check-fill sp-icon-red"></i>
                                                <span>{{ $activity->title }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 4. Feeling / Activity --}}
                        <div class="sp-action-dropdown-wrap">
                            <button type="button" class="sp-bar-action-btn sp-action-feeling" id="spComposerFeelingBtn" title="Feeling / Activity">
                                <i class="bi bi-emoji-smile-fill sp-icon-amber"></i>
                                <span>Feeling / Activity</span>
                            </button>
                            <div class="sp-action-popover sp-feeling-popover liquid-glass" id="spFeelingPopover" hidden>
                                <div class="sp-popover-head">
                                    <strong>How are you feeling?</strong>
                                    <button type="button" class="sp-popover-close" id="spFeelingPopoverClose">&times;</button>
                                </div>
                                <div class="sp-feeling-grid">
                                    <button type="button" class="sp-feeling-btn" data-feeling="🎉 Celebrating">🎉 Celebrating</button>
                                    <button type="button" class="sp-feeling-btn" data-feeling="🔥 Inspired">🔥 Inspired</button>
                                    <button type="button" class="sp-feeling-btn" data-feeling="🎓 Proud">🎓 Proud</button>
                                    <button type="button" class="sp-feeling-btn" data-feeling="⚡ Energized">⚡ Energized</button>
                                    <button type="button" class="sp-feeling-btn" data-feeling="💡 Curious">💡 Curious</button>
                                    <button type="button" class="sp-feeling-btn" data-feeling="🤝 Supported">🤝 Supported</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right-Side Controls: Audience Selector & Post CTA Button --}}
                    <div class="sp-composer-right">
                        <div class="sp-action-dropdown-wrap">
                            <button type="button" class="sp-audience-pill-btn" id="spAudienceBtn" aria-haspopup="true" title="Select Post Audience">
                                <i class="bi bi-globe-americas" id="spAudienceIcon"></i>
                                <span id="spAudienceLabel">Public</span>
                                <i class="bi bi-chevron-down sp-chevron-icon"></i>
                            </button>
                            <div class="sp-action-popover sp-audience-popover liquid-glass" id="spAudiencePopover" hidden>
                                <button type="button" class="sp-audience-item is-active" data-audience="public" data-icon="bi-globe-americas" data-label="Public">
                                    <i class="bi bi-globe-americas"></i>
                                    <div>
                                        <strong>Public</strong>
                                        <small>Anyone on Campus</small>
                                    </div>
                                </button>
                                <button type="button" class="sp-audience-item" data-audience="department" data-icon="bi-building" data-label="Department">
                                    <i class="bi bi-building"></i>
                                    <div>
                                        <strong>Department</strong>
                                        <small>Your college peers</small>
                                    </div>
                                </button>
                                <button type="button" class="sp-audience-item" data-audience="org" data-icon="bi-shield-shaded" data-label="Org Only">
                                    <i class="bi bi-shield-shaded"></i>
                                    <div>
                                        <strong>Org Members</strong>
                                        <small>Officers and delegates</small>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary sp-composer-submit-btn">
                            <i class="bi bi-send-fill"></i>
                            <span>Post</span>
                        </button>
                    </div>
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
            @php
                $feedHasFilters = ($communityAudience ?? 'all') !== 'all' || ($communityPeriod ?? 'all') !== 'all';
                $feedQuery = [
                    'community_q' => $communitySearch ?? '',
                    'community_audience' => $communityAudience ?? 'all',
                    'community_period' => $communityPeriod ?? 'all',
                ];
            @endphp
            <div class="sp-section-head sp-feed-header-row">
                <div class="sp-feed-title-wrap">
                    <h2><i class="bi bi-chat-quote-fill"></i> Community feed</h2>
                    <p>See what's happening in our campus community.</p>
                </div>
                <div class="sp-feed-controls">
                    <div class="sp-feed-sort-dropdown-wrap">
                        <button type="button" class="sp-feed-sort-btn" id="spFeedSortBtn" title="Sort feed" aria-expanded="false" aria-controls="spFeedSortPopover">
                            <span id="spFeedSortLabel">{{ ['recent' => 'Most recent', 'top' => 'Top comments', 'popular' => 'Most liked'][$feedSort ?? 'recent'] }}</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="sp-action-popover sp-sort-popover liquid-glass" id="spFeedSortPopover" hidden>
                            @foreach (['recent' => 'Most recent', 'top' => 'Top comments', 'popular' => 'Most liked'] as $sortValue => $sortLabel)
                                <a class="sp-sort-opt {{ ($feedSort ?? 'recent') === $sortValue ? 'is-active' : '' }}" href="{{ route('portal.community', array_merge($feedQuery, ['sort' => $sortValue])) }}" @if (($feedSort ?? 'recent') === $sortValue) aria-current="true" @endif>{{ $sortLabel }}</a>
                            @endforeach
                        </div>
                    </div>
                    <details class="sp-feed-filter-dropdown" @if ($feedHasFilters) open @endif>
                        <summary class="sp-feed-filter-toggle-btn {{ $feedHasFilters ? 'is-active' : '' }}" id="spFeedFilterToggle" title="Feed filters" aria-label="Feed filters">
                            <i class="bi bi-sliders" aria-hidden="true"></i>
                        </summary>
                        <form method="get" action="{{ route('portal.community') }}" class="sp-feed-filter-panel liquid-glass" aria-label="Filter community posts">
                            <input type="hidden" name="sort" value="{{ $feedSort ?? 'recent' }}">
                            <input type="hidden" name="community_q" value="{{ $communitySearch ?? '' }}">
                            <label>Audience
                                <select name="community_audience">
                                    @foreach (['all' => 'All visible posts', 'public' => 'Public posts', 'department' => 'Department posts', 'org' => 'Organization posts'] as $value => $label)
                                        <option value="{{ $value }}" @selected(($communityAudience ?? 'all') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Posted
                                <select name="community_period">
                                    @foreach (['all' => 'Any time', 'day' => 'Last 24 hours', 'week' => 'Last 7 days', 'month' => 'Last 30 days'] as $value => $label)
                                        <option value="{{ $value }}" @selected(($communityPeriod ?? 'all') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="sp-feed-filter-actions">
                                <a href="{{ route('portal.community', ['community_q' => $communitySearch ?? '', 'sort' => $feedSort ?? 'recent']) }}">Clear filters</a>
                                <button type="submit">Apply filters</button>
                            </div>
                        </form>
                    </details>
                </div>
            </div>

            <div class="sp-feed">
                @forelse ($posts as $post)
                    <article class="sp-post liquid-glass community-post" data-post-id="{{ $post->id }}">
                        <header class="sp-post-head">
                            <div class="sp-avatar sp-avatar-sm sp-feed-avatar">
                                @if (!empty($post->student->avatar_path))
                                    <img src="{{ asset('storage/'.$post->student->avatar_path) }}" alt="">
                                @else
                                    <span>{{ $post->student?->initials() ?? '?' }}</span>
                                @endif
                            </div>
                            <div class="sp-post-meta">
                                <strong class="sp-post-author-name">{{ $post->student->name ?? 'Student' }}</strong>
                                @php
                                    [$audienceIcon, $audienceLabel] = match ($post->audience) {
                                        'department' => ['bi-building', 'Department'],
                                        'org' => ['bi-shield-shaded', 'Org Members'],
                                        default => ['bi-globe-americas', 'Public'],
                                    };
                                @endphp
                                <span class="sp-post-time-meta">{{ $post->created_at->diffForHumans() }} · <i class="bi {{ $audienceIcon }}" aria-hidden="true"></i> {{ $audienceLabel }}</span>
                            </div>
                            <div class="sp-post-top-actions">
                                @if ($post->isNewInFeed())
                                    <span class="sp-post-badge-new" data-post-new-until="{{ $post->created_at->copy()->addRealHours(24)->getTimestampMs() }}" title="Posted within the last 24 hours">New</span>
                                @endif
                                <div class="sp-post-menu-wrap">
                                    <button type="button" class="sp-post-menu-trigger" aria-label="Post options">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <div class="sp-action-popover sp-post-options-menu liquid-glass" hidden>
                                        @if ($post->student_id === $student->id)
                                            <form method="post" action="{{ route('portal.community.posts.destroy', $post) }}" data-confirm-message="Delete this post and its comments?" class="sp-post-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sp-post-menu-item is-danger">
                                                    <i class="bi bi-trash3"></i> Delete post
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="sp-post-menu-item" onclick="navigator.clipboard?.writeText(window.location.href); alert('Post link copied!')">
                                                <i class="bi bi-link-45deg"></i> Copy link
                                            </button>
                                            <button type="button" class="sp-post-menu-item">
                                                <i class="bi bi-flag"></i> Report post
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </header>

                        <p class="sp-post-body">{{ $post->body }}</p>

                        @if ($post->activity)
                            <div class="sp-post-linked-activity">
                                <i class="bi bi-calendar2-event-fill"></i>
                                <span>{{ $post->activity->title }}</span>
                            </div>
                        @endif

                        @if ($post->image_path)
                            <div class="sp-post-photo">
                                <img src="{{ route('portal.community.posts.image', $post) }}" alt="Post photo">
                            </div>
                        @endif

                        {{-- Dual Reaction & Comments Summary Row (Picture 1) --}}
                        <div class="sp-post-stats">
                            <div class="sp-post-reactions-left" data-likes-count>
                                <button type="button" class="sp-reaction-text sp-likers-btn" data-likers-url="{{ route('portal.community.posts.likers', $post) }}" title="View likes">
                                    {{ $post->actual_likes_count ?? $post->likes_count ?? 0 }} {{ ($post->actual_likes_count ?? $post->likes_count ?? 0) === 1 ? 'like' : 'likes' }}
                                </button>
                            </div>
                            <div class="sp-post-comments-count-right" data-comments-count>
                                <button type="button" class="sp-comments-count-btn" data-scroll-comments="{{ $post->id }}">{{ $post->actual_comments_count ?? $post->comments_count ?? $post->comments->count() }} {{ ($post->actual_comments_count ?? $post->comments_count ?? $post->comments->count()) === 1 ? 'comment' : 'comments' }}</button>
                            </div>
                        </div>

                        {{-- Persisted likes, comments and sharing --}}
                        <div class="sp-post-actions">
                            <div class="sp-like-action-wrap">
                                @php
                                    $myReaction = $post->liked_by_me ? ($post->likes->first()?->reaction ?? 'like') : null;
                                @endphp
                                <div class="sp-reactions-dock liquid-glass" role="toolbar" aria-label="Choose a reaction" hidden>
                                    @foreach ($communityReactions as $reaction => $reactionDisplay)
                                        <button type="button" class="sp-dock-reaction" data-reaction="{{ $reaction }}" data-label="{{ $reactionDisplay['label'] }}" data-icon="{{ $reactionDisplay['icon'] }}" title="{{ $reactionDisplay['label'] }}">
                                            <span class="sp-dock-emoji" aria-hidden="true">{{ $reactionDisplay['emoji'] }}</span>
                                            <span class="sp-dock-label">{{ $reactionDisplay['label'] }}</span>
                                        </button>
                                    @endforeach
                                </div>

                                <button
                                    type="button"
                                    class="sp-post-action community-like-btn {{ $post->liked_by_me ? 'is-liked' : '' }}"
                                    data-like-url="{{ route('portal.community.posts.like', $post) }}"
                                    data-reaction="{{ $myReaction ?? '' }}" aria-haspopup="true"
                                >
                                    <i class="bi {{ $myReaction ? ($communityReactions[$myReaction]['icon'] ?? 'bi-hand-thumbs-up-fill') : 'bi-hand-thumbs-up' }} sp-action-love-icon"></i>
                                    <span class="sp-like-label">{{ $myReaction ? ($communityReactions[$myReaction]['label'] ?? 'Like') : 'Like' }}</span>
                                </button>
                            </div>

                            <button type="button" class="sp-post-action sp-btn-comment-focus" onclick="document.getElementById('comment-{{ $post->id }}')?.focus()">
                                <i class="bi bi-chat"></i>
                                <span>Comment</span>
                            </button>
                            <button type="button" class="sp-post-action sp-btn-share" onclick="if(navigator.share){navigator.share({title:'BatStateU Post', url:window.location.href});}else{navigator.clipboard?.writeText(window.location.href);alert('Post link copied to clipboard!');}">
                                <i class="bi bi-share"></i>
                                <span>Share</span>
                            </button>
                        </div>

                        {{-- Comments Thread with Modern Bubble Cards --}}
                        <div class="sp-comments" data-comments>
                            @if ($post->comments->isNotEmpty())
                                @foreach ($post->comments as $comment)
                                    <div class="sp-comment-thread {{ $loop->index >= 5 ? 'sp-comment-extra' : '' }}" @if ($loop->index >= 5) hidden @endif>
                                        <div class="sp-avatar sp-avatar-sm sp-comment-avatar">
                                            <span>{{ strtoupper(substr($comment->student->name ?? 'J', 0, 2)) }}</span>
                                        </div>
                                        <div class="sp-comment-content">
                                            <div class="sp-comment-bubble">
                                                <strong>{{ $comment->student->name ?? 'Student' }}</strong>
                                                <p>{{ $comment->body }}</p>
                                            </div>
                                            <div class="sp-comment-subactions">
                                                <button type="button" class="sp-sub-btn">Like</button>
                                                <span class="sp-sub-dot">·</span>
                                                <button type="button" class="sp-sub-btn" onclick="document.getElementById('comment-{{ $post->id }}')?.focus()">Reply</button>
                                                <span class="sp-sub-dot">·</span>
                                                <span class="sp-sub-time">{{ $comment->created_at ? $comment->created_at->diffForHumans(null, true, true) : '1m' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                @if ($post->comments->count() > 5)
                                    <button type="button" class="sp-view-all-comments" data-view-comments>View all {{ $post->comments->count() }} comments</button>
                                @endif
                            @endif
                        </div>

                        {{-- Modern Comment Composer Capsule Input (Picture 1) --}}
                        <form class="sp-comment-form" method="post" data-comment-form action="{{ route('portal.community.posts.comment', $post) }}" data-confirm="manual" data-confirm-message="Post this comment?">
                            @csrf
                            <div class="sp-avatar sp-avatar-sm sp-comment-composer-avatar">
                                @if (!empty($student->avatar_path))
                                    <img src="{{ asset('storage/'.$student->avatar_path) }}" alt="">
                                @else
                                    <span>{{ $student->initials() ?? 'MV' }}</span>
                                @endif
                            </div>
                            <div class="sp-comment-input-capsule">
                                <input id="comment-{{ $post->id }}" type="text" name="body" maxlength="800" placeholder="Write a comment…" required autocomplete="off">
                            </div>
                            <button type="submit" class="sp-comment-submit-hidden" style="display: none;">Submit</button>
                        </form>
                    </article>
                @empty
                    <div class="sp-post liquid-glass" role="status">
                        <h3>{{ ($communitySearch ?? '') !== '' || $feedHasFilters ? 'No matching posts' : 'No posts yet' }}</h3>
                        <p>{{ ($communitySearch ?? '') !== '' || $feedHasFilters ? 'Try another search or adjust the feed filters.' : 'Posts shared with you will appear here.' }}</p>
                        @if (($communitySearch ?? '') !== '')
                            <a href="{{ route('portal.community', array_merge($feedQuery, ['community_q' => '', 'sort' => $feedSort ?? 'recent'])) }}">Clear search</a>
                        @endif
                    </div>
                @endforelse
            </div>

            <div class="sp-pagination">
                {{ $posts->appends(array_merge($feedQuery, ['sort' => $feedSort ?? 'recent']))->links('components.pagination') }}
            </div>
        </section>
    </div>

    {{-- ============================ GLOBAL ACTIVITY DETAILS MODAL ============================ --}}
    <div class="sp-modal-backdrop" id="spActivityModal" aria-hidden="true">
        <div class="sp-modal-box sp-act-modal-box sp-act-modal-wide liquid-glass" role="dialog" aria-modal="true" aria-labelledby="spActModalTitle">
            <div class="sp-modal-head">
                <div class="sp-modal-head-title">
                    <i class="bi bi-calendar2-event-fill"></i>
                    <span>Activity Full Details</span>
                </div>
                <button type="button" class="sp-modal-close-btn" id="spActModalCloseBtn" aria-label="Close modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="sp-modal-body sp-act-modal-body">
                <div class="sp-act-modal-header-hero">
                    <div class="sp-act-modal-top-meta">
                        <span class="sp-chip" id="spActModalStatus"></span>
                        <span class="sp-act-org-badge" id="spActModalOrg"><i class="bi bi-shield-check"></i> <span></span></span>
                    </div>
                    <h3 class="sp-act-modal-title" id="spActModalTitle"></h3>
                    <div class="sp-act-modal-quick-info">
                        <span><i class="bi bi-calendar3"></i> <span id="spActModalDate"></span></span>
                        <span><i class="bi bi-clock"></i> <span id="spActModalTime"></span></span>
                        <span><i class="bi bi-geo-alt"></i> <span id="spActModalLocation"></span></span>
                    </div>
                </div>

                <div class="sp-act-modal-tabs" role="tablist" aria-label="Activity details tabs">
                    <button type="button" class="sp-act-modal-tab is-active" data-act-tab="overview" role="tab" aria-selected="true">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Overview</span>
                    </button>
                    <button type="button" class="sp-act-modal-tab" data-act-tab="participants" role="tab" aria-selected="false">
                        <i class="bi bi-people"></i>
                        <span>Participants</span>
                        <span class="sp-tab-badge" id="spActTabPartCount">0</span>
                    </button>
                    <button type="button" class="sp-act-modal-tab" data-act-tab="photos" role="tab" aria-selected="false">
                        <i class="bi bi-images"></i>
                        <span>Event Photos</span>
                        <span class="sp-tab-badge" id="spActTabPhotoCount">0</span>
                    </button>
                    <button type="button" class="sp-act-modal-tab" data-act-tab="budget" role="tab" aria-selected="false">
                        <i class="bi bi-wallet2"></i>
                        <span>Budget & Transparency</span>
                        <span class="sp-tab-badge is-gold" id="spActTabBudgetPct">100%</span>
                    </button>
                </div>

                <div class="sp-act-tab-panel is-active" data-tab-panel="overview">
                    <div class="sp-act-overview-content">
                        <div class="sp-act-section-block">
                            <h4><i class="bi bi-info-circle-fill"></i> Event Description & Purpose</h4>
                            <p id="spActModalDesc" class="sp-act-desc-text"></p>
                        </div>
                        <div class="sp-act-section-block" id="spActModalObjectivesBlock">
                            <h4><i class="bi bi-bullseye"></i> Key Objectives</h4>
                            <ul class="sp-act-bullet-list" id="spActModalObjectives"></ul>
                        </div>
                        <div class="sp-act-section-block" id="spActModalAgendaBlock">
                            <h4><i class="bi bi-clock-history"></i> Program Flow & Agenda</h4>
                            <div class="sp-act-timeline" id="spActModalAgenda"></div>
                        </div>
                    </div>
                </div>

                <div class="sp-act-tab-panel" data-tab-panel="participants" hidden>
                    <div class="sp-act-participants-content">
                        <div class="sp-act-part-grid">
                            <div class="sp-act-part-stat">
                                <span class="sp-part-stat-label">Registered Count</span>
                                <strong class="sp-part-stat-val" id="spActPartRegistered">0</strong>
                                <small>Registered participants</small>
                            </div>
                            <div class="sp-act-part-stat">
                                <span class="sp-part-stat-label">Total Capacity</span>
                                <strong class="sp-part-stat-val" id="spActPartCapacity">0</strong>
                                <small>Max event slots</small>
                            </div>
                            <div class="sp-act-part-stat">
                                <span class="sp-part-stat-label">Capacity Filled</span>
                                <strong class="sp-part-stat-val" id="spActPartPct">0%</strong>
                                <small>Registration rate</small>
                            </div>
                            <div class="sp-act-part-stat">
                                <span class="sp-part-stat-label">Target Audience</span>
                                <strong class="sp-part-stat-val is-text" id="spActPartTarget">All Students</strong>
                                <small>Eligibility criteria</small>
                            </div>
                        </div>
                        <div class="sp-act-part-bar-card">
                            <div class="sp-act-part-bar-header">
                                <span><i class="bi bi-person-check-fill"></i> Seat Occupancy Progress</span>
                                <strong id="spActPartRatioText">0 / 0 Slots</strong>
                            </div>
                            <div class="sp-act-part-bar-track">
                                <div class="sp-act-part-bar-fill" id="spActPartBarFill" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sp-act-tab-panel" data-tab-panel="photos" hidden>
                    <div class="sp-act-photos-content">
                        <div class="sp-act-photos-head">
                            <div>
                                <h4><i class="bi bi-camera-fill"></i> Event Photo Documentation</h4>
                                <p>Official event gallery capturing student moments, guest speakers, and program milestones.</p>
                            </div>
                            <span class="sp-section-tag" id="spActPhotoCountTag">0 photos</span>
                        </div>
                        <div class="sp-act-photo-gallery" id="spActPhotoGallery"></div>
                        <div class="sp-act-no-photos" id="spActNoPhotos" hidden>
                            <i class="bi bi-camera"></i>
                            <strong>No photos uploaded yet</strong>
                            <p>Photo documentation will be published by the media committee within 24 hours of event completion.</p>
                        </div>
                    </div>
                </div>

                <div class="sp-act-tab-panel" data-tab-panel="budget" hidden>
                    <div class="sp-act-budget-content">
                        <div class="sp-act-budget-summary-grid">
                            <div class="sp-budget-card">
                                <span class="sp-budget-card-kicker"><i class="bi bi-wallet2"></i> ALLOCATED BUDGET</span>
                                <strong class="sp-budget-card-val" id="spActBudgetAllocated">₱0</strong>
                                <small>Approved by OSO Finance Desk</small>
                            </div>
                            <div class="sp-budget-card">
                                <span class="sp-budget-card-kicker"><i class="bi bi-receipt"></i> UTILIZED AMOUNT</span>
                                <strong class="sp-budget-card-val is-spent" id="spActBudgetUtilized">₱0</strong>
                                <small>Liquidated with Official Receipts</small>
                            </div>
                            <div class="sp-budget-card">
                                <span class="sp-budget-card-kicker"><i class="bi bi-piggy-bank"></i> REMAINING / SURPLUS</span>
                                <strong class="sp-budget-card-val is-surplus" id="spActBudgetRemaining">₱0</strong>
                                <small>Returned to Org Operating Fund</small>
                            </div>
                            <div class="sp-budget-card">
                                <span class="sp-budget-card-kicker"><i class="bi bi-percent"></i> UTILIZATION RATE</span>
                                <strong class="sp-budget-card-val is-rate" id="spActBudgetRate">0%</strong>
                                <small>Of total allocated funds</small>
                            </div>
                        </div>
                        <div class="sp-act-liquidation-box">
                            <div class="sp-liq-head">
                                <h4><i class="bi bi-file-earmark-spreadsheet-fill"></i> Itemized Expense Liquidation</h4>
                                <span class="sp-badge-verified"><i class="bi bi-check-circle-fill"></i> Audited by OSO</span>
                            </div>
                            <div class="sp-table-responsive">
                                <table class="sp-act-table sp-liq-table">
                                    <thead>
                                        <tr>
                                            <th>Expense Category</th>
                                            <th>Allocated</th>
                                            <th>Actual Spent</th>
                                            <th>Variance</th>
                                            <th class="text-end">Receipt Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="spActBudgetItemsTbody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="sp-act-ledger-stamp">
                            <div class="sp-ledger-stamp-left">
                                <i class="bi bi-link-45deg"></i>
                                <div>
                                    <strong>OrgChain Immutable Ledger Record</strong>
                                    <span id="spActTxHash">TX: 0x7f8b9a2c4e1d5f309a826471e8c9b0a1d4f2e7c9</span>
                                </div>
                            </div>
                            <div class="sp-ledger-stamp-right">
                                <span class="sp-chip sp-chip-completed"><i class="bi bi-shield-check"></i> Transparency Certified</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sp-modal-footer sp-act-modal-footer">
                    <button type="button" class="btn btn-outline" id="spActModalDiscussBtn">
                        <i class="bi bi-chat-dots-fill"></i>
                        <span>Discuss in Community</span>
                    </button>
                    <button type="button" class="btn btn-primary" id="spActModalDoneBtn">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Lightbox for Photo Previews --}}
    <div class="sp-lightbox-backdrop" id="spActLightbox" aria-hidden="true" hidden>
        <div class="sp-lightbox-container">
            <button type="button" class="sp-lightbox-close" id="spActLightboxClose" aria-label="Close photo preview">
                <i class="bi bi-x-lg"></i>
            </button>
            <img src="" alt="Event photo enlarged" id="spActLightboxImg">
            <p class="sp-lightbox-caption" id="spActLightboxCaption"></p>
        </div>
    </div>

    {{-- ============================ GLOBAL ANNOUNCEMENT DETAILS MODAL ============================ --}}
    <div class="sp-modal-backdrop" id="spAnnouncementModal" aria-hidden="true">
        <div class="sp-modal-box liquid-glass" role="dialog" aria-modal="true" aria-labelledby="spModalTitle">
            <div class="sp-modal-head">
                <div class="sp-modal-head-title">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>Announcement Details</span>
                </div>
                <button type="button" class="sp-modal-close-btn" id="spModalCloseBtn" aria-label="Close modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="sp-modal-body">
                <div class="sp-modal-top-row">
                    <span class="sp-chip" id="spModalPriority"></span>
                    <span class="sp-modal-time" id="spModalTime"><i class="bi bi-clock"></i> <span></span></span>
                </div>
                <h3 class="sp-modal-title" id="spModalTitle"></h3>
                <p class="sp-modal-text" id="spModalBody"></p>
                <div class="sp-modal-footer">
                    <span class="sp-modal-author"><i class="bi bi-person-badge"></i> Posted by: <strong id="spModalAuthor"></strong></span>
                    <button type="button" class="btn btn-primary sp-modal-done-btn" id="spModalDoneBtn">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
