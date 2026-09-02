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

        {{-- Budget Utilization --}}
        <section class="sp-section">
            <div class="sp-section-head">
                <div>
                    <h2><i class="bi bi-wallet2"></i> Budget Utilization</h2>
                    <p>Transparent org funds for FY {{ $budgetItems->first()->fiscal_year ?? '2026' }}.</p>
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
                        <span class="sp-chart-count">{{ $budgetItems->count() }} Categories</span>
                    </div>
                    <div class="sp-hbar-list">
                        @forelse ($budgetItems as $item)
                            @php
                                $pct = $item->utilizationPercent();
                                $catLower = strtolower($item->category ?? '');
                                $catIcon = match(true) {
                                    str_contains($catLower, 'program') => 'bi-laptop',
                                    str_contains($catLower, 'operation') => 'bi-gear-fill',
                                    str_contains($catLower, 'extension') => 'bi-broadcast',
                                    str_contains($catLower, 'sport') => 'bi-trophy-fill',
                                    default => 'bi-tag-fill'
                                };
                            @endphp
                            <div class="sp-hbar-item">
                                <div class="sp-hbar-meta">
                                    <span class="sp-hbar-label">
                                        <i class="bi {{ $catIcon }}"></i>
                                        <strong>{{ $item->category ?: $item->title }}</strong>
                                    </span>
                                    <div class="sp-hbar-nums">
                                        <span class="sp-hbar-amount">₱{{ number_format($item->utilized) }} / ₱{{ number_format($item->allocated) }}</span>
                                        <strong class="sp-hbar-pct">{{ $pct }}%</strong>
                                    </div>
                                </div>
                                <div class="sp-hbar-track">
                                    <div class="sp-hbar-fill" style="width: {{ min(100, $pct) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="sp-empty-sm"><i class="bi bi-inbox"></i> No budget records yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

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
                                @forelse (($announcements ?? [
                                    [
                                        'title' => 'Deadline Extension for Activity Proposals',
                                        'body' => 'The deadline for submitting activity proposals for the 2nd Semester has been extended to April 15, 2026. All organizations must comply with the updated requirements.',
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
                                    [
                                        'title' => 'Accreditation Renewal Documents',
                                        'body' => 'Submission of constitution and by-laws amendments is required for the upcoming accreditation cycle.',
                                        'author' => 'OSO Admin',
                                        'time' => '5 days ago',
                                        'priority' => 'normal',
                                    ],
                                    [
                                        'title' => 'University Week Booth Space Reservation',
                                        'body' => 'First come, first served booth reservations open on Monday at the student portal kiosk.',
                                        'author' => 'Events Comm',
                                        'time' => '1 week ago',
                                        'priority' => 'normal',
                                    ],
                                    [
                                        'title' => 'Leadership Training Seminar 2026',
                                        'body' => 'Mandatory leadership seminar for all incoming executive committee officers.',
                                        'author' => 'OSO Admin',
                                        'time' => '2 weeks ago',
                                        'priority' => 'high',
                                    ],
                                ]) as $announce)
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
                            'is_rsvp' => false,
                            'breakdown' => [
                                ['label' => '1st & 2nd Year', 'count' => round($regCount * 0.55), 'pct' => 55],
                                ['label' => '3rd & 4th Year', 'count' => round($regCount * 0.45), 'pct' => 45]
                            ]
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
                            'items' => [
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
                        <button type="button" class="sp-act-btn-details"
                            data-act-modal-trigger
                            data-act-details="{{ $actJson }}"
                            data-title="{{ $act->title }}"
                            data-status="{{ $status }}"
                            data-date="{{ $starts ? $starts->format('F j, Y (l)') : 'Date TBA' }}"
                            data-time="{{ $timeRange }}"
                            data-location="{{ $loc }}"
                            data-desc="{{ $desc }}">
                            <span>View details</span>
                            <i class="bi bi-arrow-right"></i>
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
                                        'is_rsvp' => false,
                                        'breakdown' => [
                                            ['label' => '1st & 2nd Year', 'count' => round($regCount * 0.55), 'pct' => 55],
                                            ['label' => '3rd & 4th Year', 'count' => round($regCount * 0.45), 'pct' => 45]
                                        ]
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
                                        'items' => [
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

            {{-- List View Pagination (< 1 2 3 >) --}}
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
                    Showing 1–5 of 9 bulletins
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
                            $announcementsList = [
                                [
                                    'id' => 1,
                                    'tag' => 'Call for TOSA',
                                    'pill_class' => 'sp-pill-tosa',
                                    'category' => 'tosa',
                                    'title' => 'Call for Ten Outstanding Student Award (TOSA) 2026',
                                    'body' => 'The Office of Student Organizations (OSO) is now accepting applications for the Ten Outstanding Student Award (TOSA) 2026. All interested and qualified student leaders are encouraged to apply.',
                                    'day' => '10 May',
                                    'year' => '2026',
                                    'date' => 'May 10, 2026',
                                    'order' => 1,
                                    'author' => 'OSO Admin',
                                    'priority' => 'high'
                                ],
                                [
                                    'id' => 2,
                                    'tag' => 'Activities',
                                    'pill_class' => 'sp-pill-activities',
                                    'category' => 'activities',
                                    'title' => 'University Week Pavilion, Exhibits & Cultural Showcase Activities',
                                    'body' => 'Official schedule of activities, booth floor plans, and stage performance guidelines for the upcoming University Week celebration across all campuses.',
                                    'day' => '12 May',
                                    'year' => '2026',
                                    'date' => 'May 12, 2026',
                                    'order' => 2,
                                    'author' => 'Events Committee',
                                    'priority' => 'normal'
                                ],
                                [
                                    'id' => 3,
                                    'tag' => 'Important Dates',
                                    'pill_class' => 'sp-pill-dates',
                                    'category' => 'dates',
                                    'title' => 'Important Dates for TOSA 2026 & Midterm Schedule',
                                    'body' => 'Please be guided by the schedule of activities for the TOSA 2026 nomination submissions, pre-qualification screening, and final panel interviews. Mark your calendars and stay updated.',
                                    'day' => '08 May',
                                    'year' => '2026',
                                    'date' => 'May 8, 2026',
                                    'order' => 3,
                                    'author' => 'OSO Admin',
                                    'priority' => 'normal'
                                ],
                                [
                                    'id' => 4,
                                    'tag' => 'Activities',
                                    'pill_class' => 'sp-pill-activities',
                                    'category' => 'activities',
                                    'title' => 'Midterm Leadership & Youth Governance Summit 2026',
                                    'body' => 'Registration and activity mechanics for the annual 2-day Student Leadership Summit at the University Amphitheater. Participating organizations must send 3 official delegates.',
                                    'day' => '14 May',
                                    'year' => '2026',
                                    'date' => 'May 14, 2026',
                                    'order' => 4,
                                    'author' => 'Student Affairs',
                                    'priority' => 'normal'
                                ],
                                [
                                    'id' => 5,
                                    'tag' => 'Deadline Reminders',
                                    'pill_class' => 'sp-pill-deadlines',
                                    'category' => 'deadlines',
                                    'title' => 'Reminders: Upcoming Deadlines for Activity Proposals & TOSA Requirements',
                                    'body' => 'Don\'t forget the important deadlines for TOSA 2026 portfolio submissions, project clearance forms, and event liquidation reports. Late submissions will strictly not be accepted.',
                                    'day' => '15 May',
                                    'year' => '2026',
                                    'date' => 'May 15, 2026',
                                    'order' => 5,
                                    'author' => 'OSO Admin',
                                    'priority' => 'high'
                                ],
                                [
                                    'id' => 6,
                                    'tag' => 'OSO Notices',
                                    'pill_class' => 'sp-pill-oso',
                                    'category' => 'oso',
                                    'title' => 'OSO Notice: Guidelines, Accreditation Renewal and Selection Updates',
                                    'body' => 'Please review the updated guidelines and policies for the Ten Outstanding Student Award (TOSA) 2026 and semester organization renewals. Your compliance ensures a fair and transparent selection process.',
                                    'day' => '05 May',
                                    'year' => '2026',
                                    'date' => 'May 5, 2026',
                                    'order' => 6,
                                    'author' => 'OSO Admin',
                                    'priority' => 'normal'
                                ],
                                [
                                    'id' => 7,
                                    'tag' => 'Call for TOSA',
                                    'pill_class' => 'sp-pill-tosa',
                                    'category' => 'tosa',
                                    'title' => 'TOSA 2026 Category Guidelines & Scoring Criteria Released',
                                    'body' => 'Full scoring rubrics and portfolio submission mechanics have been published for Academic Excellence, Leadership, and Community Engagement pillars.',
                                    'day' => '03 May',
                                    'year' => '2026',
                                    'date' => 'May 3, 2026',
                                    'order' => 7,
                                    'author' => 'TOSA Secretariat',
                                    'priority' => 'normal'
                                ],
                                [
                                    'id' => 8,
                                    'tag' => 'Activities',
                                    'pill_class' => 'sp-pill-activities',
                                    'category' => 'activities',
                                    'title' => 'Inter-Campus Esports and Hackathon Championship 2026',
                                    'body' => 'Official registration details and team brackets for the upcoming university-wide tech challenge and gaming festival at the Central Amphitheater.',
                                    'day' => '02 May',
                                    'year' => '2026',
                                    'date' => 'May 2, 2026',
                                    'order' => 8,
                                    'author' => 'CICS Council',
                                    'priority' => 'normal'
                                ],
                                [
                                    'id' => 9,
                                    'tag' => 'Deadline Reminders',
                                    'pill_class' => 'sp-pill-deadlines',
                                    'category' => 'deadlines',
                                    'title' => 'Final Clearance for Term 2 Organization Venue Allocations',
                                    'body' => 'All campus student councils and clubs must finalize facility request slips and security permits before the close of business Friday.',
                                    'day' => '01 May',
                                    'year' => '2026',
                                    'date' => 'May 1, 2026',
                                    'order' => 9,
                                    'author' => 'Facilities Management',
                                    'priority' => 'high'
                                ]
                            ];
                        @endphp

                        @foreach ($announcementsList as $item)
                            <tr class="sp-announce-tbl-row"
                                data-announce-cat="{{ $item['category'] }}"
                                data-announce-priority="{{ $item['priority'] }}"
                                data-announce-order="{{ $item['order'] }}"
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
                    Showing <span id="spAnnouncePageRange">1–5</span> of <span id="spAnnounceTotalItems">9</span> bulletins
                </div>
                <div class="sp-ann-page-controls">
                    <button type="button" class="sp-ann-page-btn" id="spAnnouncePrevBtn" aria-label="Previous page">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div id="spAnnouncePageNumbers" style="display: inline-flex; gap: 0.35rem;"></div>
                    <button type="button" class="sp-ann-page-btn" id="spAnnounceNextBtn" aria-label="Next page">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ TOSA APPLICATIONS (SUBMISSIONS) PANEL ============================ --}}
    <div class="portal-panel {{ ($tab ?? '') === 'tosa' ? 'is-active' : '' }}" data-panel="tosa" @if(($tab ?? '') !== 'tosa') hidden @endif>
        <div class="sp-tosa-submissions-view">
            {{-- Header Title --}}
            <div class="sp-tosa-sub-header">
                <div>
                    <h1>Submissions</h1>
                    <p>Upload all the required documents for your TOSA application.</p>
                </div>
                <div class="sp-tosa-readonly-pill">
                    <i class="bi bi-lock-fill"></i>
                    <span>Read Only</span>
                </div>
            </div>

            {{-- Application Progress - 5-Step Timeline Stepper (Exact Mockup Match) --}}
            <div class="sp-tosa-stepper-container">
                <div class="sp-tosa-stepper-row">
                    {{-- Step 1: Submitted --}}
                    <div class="sp-tosa-timeline-step is-inprogress" id="tosaStepNode_1">
                        <div class="sp-tosa-node-circle" id="tosaStepCircle_1">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h5 class="sp-tosa-node-title" id="tosaStepTitle_1">1. Submitted</h5>
                        <span class="sp-tosa-node-pill pill-inprogress" id="tosaStepPill_1">In Progress</span>
                        <div class="sp-tosa-node-time" id="tosaStepTime_1">
                            <span>Pending Documents</span>
                        </div>
                    </div>

                    {{-- Connector Line 1 to 2 --}}
                    <div class="sp-tosa-timeline-line is-dashed" id="tosaStepLine_1"></div>

                    {{-- Step 2: Under CTC Recording --}}
                    <div class="sp-tosa-timeline-step is-pending" id="tosaStepNode_2">
                        <div class="sp-tosa-node-circle" id="tosaStepCircle_2">
                            <i class="bi bi-folder2"></i>
                        </div>
                        <h5 class="sp-tosa-node-title" id="tosaStepTitle_2">2. Under CTC Recording</h5>
                        <span class="sp-tosa-node-pill pill-pending" id="tosaStepPill_2">Pending</span>
                        <div class="sp-tosa-node-time" id="tosaStepTime_2"></div>
                    </div>

                    {{-- Connector Line 2 to 3 --}}
                    <div class="sp-tosa-timeline-line is-dashed" id="tosaStepLine_2"></div>

                    {{-- Step 3: Under OSO Verification --}}
                    <div class="sp-tosa-timeline-step is-pending" id="tosaStepNode_3">
                        <div class="sp-tosa-node-circle" id="tosaStepCircle_3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h5 class="sp-tosa-node-title" id="tosaStepTitle_3">3. Under OSO Verification</h5>
                        <span class="sp-tosa-node-pill pill-pending" id="tosaStepPill_3">Pending</span>
                        <div class="sp-tosa-node-time" id="tosaStepTime_3"></div>
                    </div>

                    {{-- Connector Line 3 to 4 --}}
                    <div class="sp-tosa-timeline-line is-dashed" id="tosaStepLine_3"></div>

                    {{-- Step 4: Under Committee Evaluation --}}
                    <div class="sp-tosa-timeline-step is-pending" id="tosaStepNode_4">
                        <div class="sp-tosa-node-circle" id="tosaStepCircle_4">
                            <i class="bi bi-people"></i>
                        </div>
                        <h5 class="sp-tosa-node-title" id="tosaStepTitle_4">4. Under Committee<br>Evaluation</h5>
                        <span class="sp-tosa-node-pill pill-pending" id="tosaStepPill_4">Pending</span>
                        <div class="sp-tosa-node-time" id="tosaStepTime_4"></div>
                    </div>

                    {{-- Connector Line 4 to 5 --}}
                    <div class="sp-tosa-timeline-line is-dashed" id="tosaStepLine_4"></div>

                    {{-- Step 5: Qualified --}}
                    <div class="sp-tosa-timeline-step is-pending" id="tosaStepNode_5">
                        <div class="sp-tosa-node-circle" id="tosaStepCircle_5">
                            <i class="bi bi-award"></i>
                        </div>
                        <h5 class="sp-tosa-node-title" id="tosaStepTitle_5">5. Qualified</h5>
                        <span class="sp-tosa-node-pill pill-pending" id="tosaStepPill_5">Pending</span>
                        <div class="sp-tosa-node-time" id="tosaStepTime_5"></div>
                    </div>
                </div>
            </div>

            {{-- Important Reminder Box (Blue) --}}
            <div class="sp-tosa-notice-box">
                <div class="sp-tosa-info-icon">
                    <i class="bi bi-info-lg"></i>
                </div>
                <div class="sp-tosa-notice-content">
                    <h4>Important Reminder</h4>
                    <p>Please upload clear and complete documents in PDF format only.<br>Ensure that all files follow the required guidelines.</p>
                </div>
            </div>

            {{-- Submissions Table Card --}}
            <div class="sp-tosa-table-card">
                <div class="sp-table-responsive">
                    <table class="sp-tosa-table">
                        <thead>
                            <tr>
                                <th class="sp-col-num">#</th>
                                <th class="sp-col-req">Requirement</th>
                                <th class="sp-col-status">Status</th>
                                <th class="sp-col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="spTosaSubmissionsTbody">
                            {{-- Row 1: Application Form --}}
                            <tr id="tosaRow_1" data-tosa-id="1">
                                <td class="sp-col-num">1</td>
                                <td class="sp-col-req">
                                    <div class="sp-req-flex">
                                        <div class="sp-req-icon is-red">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <div class="sp-req-info">
                                            <strong>Application Form</strong>
                                            <p>Upload the accomplished TOSA Application Form.</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="sp-col-status">
                                    <div class="sp-status-wrap is-pending" id="tosaStatus_1">
                                        <div class="sp-status-heading">
                                            <i class="bi bi-clock"></i>
                                            <strong>Pending</strong>
                                        </div>
                                        <small id="tosaTime_1">Not uploaded yet</small>
                                    </div>
                                </td>
                                <td class="sp-col-actions">
                                    <input type="file" accept=".pdf" id="tosaInput_1" style="display:none;" onchange="window.handleTosaDocUpload?.(1, this)">
                                    <div class="sp-actions-wrap" id="tosaActions_1">
                                        <button type="button" class="sp-tosa-btn-upload" onclick="document.getElementById('tosaInput_1')?.click()">
                                            <i class="bi bi-upload"></i>
                                            <span>Upload</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Row 2: Certificate of Grades --}}
                            <tr id="tosaRow_2" data-tosa-id="2">
                                <td class="sp-col-num">2</td>
                                <td class="sp-col-req">
                                    <div class="sp-req-flex">
                                        <div class="sp-req-icon is-orange">
                                            <i class="bi bi-file-earmark-bar-graph"></i>
                                        </div>
                                        <div class="sp-req-info">
                                            <strong>Certificate of Grades</strong>
                                            <p>Upload your Certified True Copy (CTC) of Grades.</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="sp-col-status">
                                    <div class="sp-status-wrap is-pending" id="tosaStatus_2">
                                        <div class="sp-status-heading">
                                            <i class="bi bi-clock"></i>
                                            <strong>Pending</strong>
                                        </div>
                                        <small id="tosaTime_2">Not uploaded yet</small>
                                    </div>
                                </td>
                                <td class="sp-col-actions">
                                    <input type="file" accept=".pdf" id="tosaInput_2" style="display:none;" onchange="window.handleTosaDocUpload?.(2, this)">
                                    <div class="sp-actions-wrap" id="tosaActions_2">
                                        <button type="button" class="sp-tosa-btn-upload" onclick="document.getElementById('tosaInput_2')?.click()">
                                            <i class="bi bi-upload"></i>
                                            <span>Upload</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Row 3: Good Moral Certificate --}}
                            <tr id="tosaRow_3" data-tosa-id="3">
                                <td class="sp-col-num">3</td>
                                <td class="sp-col-req">
                                    <div class="sp-req-flex">
                                        <div class="sp-req-icon is-green">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <div class="sp-req-info">
                                            <strong>Good Moral Certificate</strong>
                                            <p>Upload your Certificate of Good Moral Character.</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="sp-col-status">
                                    <div class="sp-status-wrap is-pending" id="tosaStatus_3">
                                        <div class="sp-status-heading">
                                            <i class="bi bi-clock"></i>
                                            <strong>Pending</strong>
                                        </div>
                                        <small id="tosaTime_3">Not uploaded yet</small>
                                    </div>
                                </td>
                                <td class="sp-col-actions">
                                    <input type="file" accept=".pdf" id="tosaInput_3" style="display:none;" onchange="window.handleTosaDocUpload?.(3, this)">
                                    <div class="sp-actions-wrap" id="tosaActions_3">
                                        <button type="button" class="sp-tosa-btn-upload" onclick="document.getElementById('tosaInput_3')?.click()">
                                            <i class="bi bi-upload"></i>
                                            <span>Upload</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Row 4: Organization Certificate --}}
                            <tr id="tosaRow_4" data-tosa-id="4">
                                <td class="sp-col-num">4</td>
                                <td class="sp-col-req">
                                    <div class="sp-req-flex">
                                        <div class="sp-req-icon is-purple">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <div class="sp-req-info">
                                            <strong>Organization Certificate</strong>
                                            <p>Upload your Organization Membership Certificate.</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="sp-col-status">
                                    <div class="sp-status-wrap is-pending" id="tosaStatus_4">
                                        <div class="sp-status-heading">
                                            <i class="bi bi-clock"></i>
                                            <strong>Pending</strong>
                                        </div>
                                        <small id="tosaTime_4">Not uploaded yet</small>
                                    </div>
                                </td>
                                <td class="sp-col-actions">
                                    <input type="file" accept=".pdf" id="tosaInput_4" style="display:none;" onchange="window.handleTosaDocUpload?.(4, this)">
                                    <div class="sp-actions-wrap" id="tosaActions_4">
                                        <button type="button" class="sp-tosa-btn-upload" onclick="document.getElementById('tosaInput_4')?.click()">
                                            <i class="bi bi-upload"></i>
                                            <span>Upload</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Row 5: Leadership Portfolio --}}
                            <tr id="tosaRow_5" data-tosa-id="5">
                                <td class="sp-col-num">5</td>
                                <td class="sp-col-req">
                                    <div class="sp-req-flex">
                                        <div class="sp-req-icon is-blue">
                                            <i class="bi bi-briefcase"></i>
                                        </div>
                                        <div class="sp-req-info">
                                            <strong>Leadership Portfolio</strong>
                                            <p>Upload your Leadership Portfolio.</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="sp-col-status">
                                    <div class="sp-status-wrap is-pending" id="tosaStatus_5">
                                        <div class="sp-status-heading">
                                            <i class="bi bi-clock"></i>
                                            <strong>Pending</strong>
                                        </div>
                                        <small id="tosaTime_5">Not uploaded yet</small>
                                    </div>
                                </td>
                                <td class="sp-col-actions">
                                    <input type="file" accept=".pdf" id="tosaInput_5" style="display:none;" onchange="window.handleTosaDocUpload?.(5, this)">
                                    <div class="sp-actions-wrap" id="tosaActions_5">
                                        <button type="button" class="sp-tosa-btn-upload" onclick="document.getElementById('tosaInput_5')?.click()">
                                            <i class="bi bi-upload"></i>
                                            <span>Upload</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Row 6: Recommendation Letter --}}
                            <tr id="tosaRow_6" data-tosa-id="6">
                                <td class="sp-col-num">6</td>
                                <td class="sp-col-req">
                                    <div class="sp-req-flex">
                                        <div class="sp-req-icon is-teal">
                                            <i class="bi bi-envelope"></i>
                                        </div>
                                        <div class="sp-req-info">
                                            <strong>Recommendation Letter</strong>
                                            <p>Upload two (2) Recommendation Letters.</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="sp-col-status">
                                    <div class="sp-status-wrap is-pending" id="tosaStatus_6">
                                        <div class="sp-status-heading">
                                            <i class="bi bi-clock"></i>
                                            <strong>Pending</strong>
                                        </div>
                                        <small id="tosaTime_6">Not uploaded yet</small>
                                    </div>
                                </td>
                                <td class="sp-col-actions">
                                    <input type="file" accept=".pdf" id="tosaInput_6" style="display:none;" onchange="window.handleTosaDocUpload?.(6, this)">
                                    <div class="sp-actions-wrap" id="tosaActions_6">
                                        <button type="button" class="sp-tosa-btn-upload" onclick="document.getElementById('tosaInput_6')?.click()">
                                            <i class="bi bi-upload"></i>
                                            <span>Upload</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Row 7: Essay --}}
                            <tr id="tosaRow_7" data-tosa-id="7">
                                <td class="sp-col-num">7</td>
                                <td class="sp-col-req">
                                    <div class="sp-req-flex">
                                        <div class="sp-req-icon is-pink">
                                            <i class="bi bi-pen"></i>
                                        </div>
                                        <div class="sp-req-info">
                                            <strong>Essay</strong>
                                            <p>Upload your TOSA Essay.</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="sp-col-status">
                                    <div class="sp-status-wrap is-missing" id="tosaStatus_7">
                                        <div class="sp-status-heading">
                                            <i class="bi bi-x-circle"></i>
                                            <strong>Missing</strong>
                                        </div>
                                        <small id="tosaTime_7">Required</small>
                                    </div>
                                </td>
                                <td class="sp-col-actions">
                                    <input type="file" accept=".pdf" id="tosaInput_7" style="display:none;" onchange="window.handleTosaDocUpload?.(7, this)">
                                    <div class="sp-actions-wrap" id="tosaActions_7">
                                        <button type="button" class="sp-tosa-btn-upload" onclick="document.getElementById('tosaInput_7')?.click()">
                                            <i class="bi bi-upload"></i>
                                            <span>Upload</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bottom Yellow Notice Banner --}}
            <div class="sp-tosa-bottom-banner">
                <div class="sp-tosa-bottom-left">
                    <div class="sp-tosa-bulb-icon">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                    <div class="sp-tosa-bottom-text">
                        <strong>Before you submit</strong>
                        <p>Make sure all required documents are uploaded and follow the file guidelines to avoid delays in your application.</p>
                    </div>
                </div>
                <button type="button" class="sp-tosa-btn-guidelines" id="spTosaGuidelinesBtn">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>View File Guidelines</span>
                </button>
            </div>
        </div>
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
                    <div class="sp-composer-chip sp-chip-tags" id="spChipTags" hidden>
                        <i class="bi bi-person-fill-add sp-icon-blue"></i>
                        <span id="spChipTagsName">Tagged</span>
                        <button type="button" class="sp-chip-remove" id="spRemoveTagsBtn" aria-label="Remove tags">&times;</button>
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
                <input type="hidden" name="audience" id="spComposerAudienceInput" value="public">
                <input type="hidden" name="tagged_users" id="spComposerTaggedInput" value="">

                {{-- Modern Composer Bottom Toolbar (Picture 1) --}}
                <div class="sp-composer-bar">
                    <div class="sp-composer-actions">
                        {{-- 1. Photo / Video --}}
                        <label class="sp-bar-action-btn sp-action-photo" title="Add Photo or Video">
                            <input type="file" name="photo" accept="image/*,video/*" id="communityPhotoInput">
                            <i class="bi bi-image-fill sp-icon-green"></i>
                            <span>Photo / Video</span>
                        </label>

                        {{-- 2. Tag people --}}
                        <div class="sp-action-dropdown-wrap">
                            <button type="button" class="sp-bar-action-btn sp-action-tag" id="spComposerTagBtn" title="Tag people">
                                <i class="bi bi-person-fill-add sp-icon-blue"></i>
                                <span>Tag people</span>
                            </button>
                            <div class="sp-action-popover liquid-glass" id="spTagPopover" hidden>
                                <div class="sp-popover-head">
                                    <strong>Tag Classmates & Officers</strong>
                                    <button type="button" class="sp-popover-close" id="spTagPopoverClose">&times;</button>
                                </div>
                                <div class="sp-popover-body">
                                    <input type="text" class="sp-popover-search" id="spTagSearchInput" placeholder="Type student name or council...">
                                    <div class="sp-tag-quick-list" id="spTagQuickList">
                                        <button type="button" class="sp-tag-option" data-tag="SSC Executive Council">🏛️ SSC Executive Council</button>
                                        <button type="button" class="sp-tag-option" data-tag="CICS Student Council">💻 CICS Student Council</button>
                                        <button type="button" class="sp-tag-option" data-tag="Red Cross Youth">🩺 Red Cross Youth</button>
                                        <button type="button" class="sp-tag-option" data-tag="CEAFA Student Council">⚙️ CEAFA Council</button>
                                    </div>
                                </div>
                            </div>
                        </div>

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
            <div class="sp-section-head sp-feed-header-row">
                <div class="sp-feed-title-wrap">
                    <h2><i class="bi bi-chat-quote-fill"></i> Community feed</h2>
                    <p>See what's happening in our campus community.</p>
                </div>
                <div class="sp-feed-controls">
                    <div class="sp-feed-sort-dropdown-wrap">
                        <button type="button" class="sp-feed-sort-btn" id="spFeedSortBtn" title="Sort feed">
                            <span id="spFeedSortLabel">Most recent</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="sp-action-popover sp-sort-popover liquid-glass" id="spFeedSortPopover" hidden>
                            <button type="button" class="sp-sort-opt is-active" data-sort="recent">Most recent</button>
                            <button type="button" class="sp-sort-opt" data-sort="top">Top comments</button>
                            <button type="button" class="sp-sort-opt" data-sort="popular">Most liked</button>
                        </div>
                    </div>
                    <button type="button" class="sp-feed-filter-toggle-btn" id="spFeedFilterToggle" title="Feed filters">
                        <i class="bi bi-sliders"></i>
                    </button>
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
                                    <span>{{ $post->student->initials() ?? 'MV' }}</span>
                                @endif
                            </div>
                            <div class="sp-post-meta">
                                <strong class="sp-post-author-name">{{ $post->student->name ?? 'Michelle Vivas' }}</strong>
                                <span class="sp-post-time-meta">{{ $post->created_at->diffForHumans() }} · <i class="bi bi-globe-americas"></i></span>
                            </div>
                            <div class="sp-post-top-actions">
                                <span class="sp-post-badge-new">New</span>
                                <div class="sp-post-menu-wrap">
                                    <button type="button" class="sp-post-menu-trigger" aria-label="Post options">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <div class="sp-action-popover sp-post-options-menu liquid-glass" hidden>
                                        @if ($post->student_id === $student->id)
                                            <form method="post" action="{{ route('portal.community.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?')" class="sp-post-delete-form">
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

                        @if ($post->activity || empty($post->activity_id))
                            <div class="sp-post-linked-activity">
                                <i class="bi bi-calendar2-event-fill"></i>
                                <span>{{ $post->activity->title ?? 'General Assembly 2026' }}</span>
                            </div>
                        @endif

                        @if ($post->image_path)
                            <div class="sp-post-photo">
                                <img src="{{ asset('storage/'.$post->image_path) }}" alt="Post photo">
                            </div>
                        @endif

                        {{-- Dual Reaction & Comments Summary Row (Picture 1) --}}
                        <div class="sp-post-stats">
                            <div class="sp-post-reactions-left" data-likes-count>
                                <span class="sp-reaction-icons-stack">
                                    <i class="bi bi-hand-thumbs-up-fill sp-react-like"></i>
                                    <i class="bi bi-heart-fill sp-react-love"></i>
                                </span>
                                <span class="sp-reaction-text">
                                    @if ($post->likes_count > 0)
                                        {{ $post->liked_by_me ? 'You and ' . max(1, $post->likes_count - 1) . ' others' : $post->likes_count . ' likes' }}
                                    @else
                                        You and 12 others
                                    @endif
                                </span>
                            </div>
                            <div class="sp-post-comments-count-right" data-comments-count>
                                <span>{{ max(3, $post->comments_count ?? $post->comments->count()) }} comments</span>
                            </div>
                        </div>

                        {{-- Action Buttons Bar (Love/Like with Hold Reactions Flyout, Comment, Share) --}}
                        <div class="sp-post-actions">
                            <div class="sp-like-action-wrap">
                                {{-- Floating Reactions Dock (Hold / Long-press or Hover Flyout) --}}
                                <div class="sp-reactions-dock liquid-glass" role="toolbar" aria-label="Reactions" hidden>
                                    <button type="button" class="sp-dock-reaction" data-reaction="like" data-label="Like" data-icon="bi-hand-thumbs-up-fill" data-color="#1877f2" title="Like">
                                        <span class="sp-dock-emoji">👍</span>
                                        <span class="sp-dock-label">Like</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="love" data-label="Love" data-icon="bi-heart-fill" data-color="#e11d48" title="Love">
                                        <span class="sp-dock-emoji">❤️</span>
                                        <span class="sp-dock-label">Love</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="care" data-label="Care" data-icon="bi-emoji-heart-eyes-fill" data-color="#f59e0b" title="Care">
                                        <span class="sp-dock-emoji">🥰</span>
                                        <span class="sp-dock-label">Care</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="haha" data-label="Haha" data-icon="bi-emoji-laughing-fill" data-color="#f59e0b" title="Haha">
                                        <span class="sp-dock-emoji">😆</span>
                                        <span class="sp-dock-label">Haha</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="wow" data-label="Wow" data-icon="bi-emoji-surprise-fill" data-color="#f59e0b" title="Wow">
                                        <span class="sp-dock-emoji">😮</span>
                                        <span class="sp-dock-label">Wow</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="sad" data-label="Sad" data-icon="bi-emoji-tear-fill" data-color="#f59e0b" title="Sad">
                                        <span class="sp-dock-emoji">😢</span>
                                        <span class="sp-dock-label">Sad</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="angry" data-label="Angry" data-icon="bi-emoji-angry-fill" data-color="#ea580c" title="Angry">
                                        <span class="sp-dock-emoji">😡</span>
                                        <span class="sp-dock-label">Angry</span>
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    class="sp-post-action community-like-btn {{ $post->liked_by_me ? 'is-liked' : '' }}"
                                    data-like-url="{{ route('portal.community.posts.like', $post) }}"
                                >
                                    <i class="bi {{ $post->liked_by_me ? 'bi-heart-fill' : 'bi-hand-thumbs-up' }} sp-action-love-icon"></i>
                                    <span class="sp-like-label">{{ $post->liked_by_me ? 'Love' : 'Like' }}</span>
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
                                @foreach ($post->comments->take(5) as $comment)
                                    <div class="sp-comment-thread">
                                        <div class="sp-avatar sp-avatar-sm sp-comment-avatar">
                                            <span>{{ strtoupper(substr($comment->student->name ?? 'J', 0, 2)) }}</span>
                                        </div>
                                        <div class="sp-comment-content">
                                            <div class="sp-comment-bubble">
                                                <strong>{{ $comment->student->name ?? 'John Dela Cruz' }}</strong>
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
                            @else
                                {{-- Picture 1 Mockup Comment Item --}}
                                <div class="sp-comment-thread">
                                    <div class="sp-avatar sp-avatar-sm sp-comment-avatar">
                                        <img src="{{ asset('voting-assets/img/candidate-sample.jpg') }}" alt="" onerror="this.outerHTML='<span>JC</span>'">
                                    </div>
                                    <div class="sp-comment-content">
                                        <div class="sp-comment-bubble">
                                            <strong>John Dela Cruz</strong>
                                            <p>Looking forward to it! 🙌</p>
                                        </div>
                                        <div class="sp-comment-subactions">
                                            <button type="button" class="sp-sub-btn">Like</button>
                                            <span class="sp-sub-dot">·</span>
                                            <button type="button" class="sp-sub-btn" onclick="document.getElementById('comment-{{ $post->id }}')?.focus()">Reply</button>
                                            <span class="sp-sub-dot">·</span>
                                            <span class="sp-sub-time">1m</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Modern Comment Composer Capsule Input (Picture 1) --}}
                        <form class="sp-comment-form" method="post" data-comment-form action="{{ route('portal.community.posts.comment', $post) }}">
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
                                <div class="sp-comment-inline-tools">
                                    <button type="button" class="sp-comment-tool-btn" title="Emoji" onclick="const inp=document.getElementById('comment-{{ $post->id }}'); if(inp){inp.value+=' ❤️ '; inp.focus();}"><i class="bi bi-emoji-smile"></i></button>
                                    <button type="button" class="sp-comment-tool-btn" title="Attach Photo"><i class="bi bi-camera"></i></button>
                                    <button type="button" class="sp-comment-tool-btn" title="GIF"><i class="bi bi-filetype-gif"></i></button>
                                    <button type="button" class="sp-comment-tool-btn" title="Sticker"><i class="bi bi-sticky"></i></button>
                                </div>
                            </div>
                            <button type="submit" class="sp-comment-submit-hidden" style="display: none;">Submit</button>
                        </form>
                    </article>
                @empty
                    <article class="sp-post liquid-glass community-post">
                        <header class="sp-post-head">
                            <div class="sp-avatar sp-avatar-sm sp-feed-avatar">
                                <span>MV</span>
                            </div>
                            <div class="sp-post-meta">
                                <strong class="sp-post-author-name">Michelle Vivas</strong>
                                <span class="sp-post-time-meta">Just now · <i class="bi bi-globe-americas"></i></span>
                            </div>
                            <div class="sp-post-top-actions">
                                <span class="sp-post-badge-new">New</span>
                                <div class="sp-post-menu-wrap">
                                    <button type="button" class="sp-post-menu-trigger" aria-label="Post options">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                </div>
                            </div>
                        </header>

                        <p class="sp-post-body">I'm excited for the General Assembly 2026! This is a chance for all of us to be heard and work together for better initiatives. See you there, BatStateU! ❤️</p>

                        <div class="sp-post-linked-activity">
                            <i class="bi bi-calendar2-event-fill"></i>
                            <span>General Assembly 2026</span>
                        </div>

                        {{-- Reactions Stats Row --}}
                        <div class="sp-post-stats">
                            <div class="sp-post-reactions-left">
                                <span class="sp-reaction-icons-stack">
                                    <i class="bi bi-hand-thumbs-up-fill sp-react-like"></i>
                                    <i class="bi bi-heart-fill sp-react-love"></i>
                                </span>
                                <span class="sp-reaction-text">You and 12 others</span>
                            </div>
                            <div class="sp-post-comments-count-right">
                                <span>3 comments</span>
                            </div>
                        </div>

                        {{-- Action Buttons with Hold Reactions Flyout --}}
                        <div class="sp-post-actions">
                            <div class="sp-like-action-wrap">
                                {{-- Floating Reactions Dock --}}
                                <div class="sp-reactions-dock liquid-glass" role="toolbar" aria-label="Reactions" hidden>
                                    <button type="button" class="sp-dock-reaction" data-reaction="like" data-label="Like" data-icon="bi-hand-thumbs-up-fill" data-color="#1877f2" title="Like">
                                        <span class="sp-dock-emoji">👍</span>
                                        <span class="sp-dock-label">Like</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="love" data-label="Love" data-icon="bi-heart-fill" data-color="#e11d48" title="Love">
                                        <span class="sp-dock-emoji">❤️</span>
                                        <span class="sp-dock-label">Love</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="care" data-label="Care" data-icon="bi-emoji-heart-eyes-fill" data-color="#f59e0b" title="Care">
                                        <span class="sp-dock-emoji">🥰</span>
                                        <span class="sp-dock-label">Care</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="haha" data-label="Haha" data-icon="bi-emoji-laughing-fill" data-color="#f59e0b" title="Haha">
                                        <span class="sp-dock-emoji">😆</span>
                                        <span class="sp-dock-label">Haha</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="wow" data-label="Wow" data-icon="bi-emoji-surprise-fill" data-color="#f59e0b" title="Wow">
                                        <span class="sp-dock-emoji">😮</span>
                                        <span class="sp-dock-label">Wow</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="sad" data-label="Sad" data-icon="bi-emoji-tear-fill" data-color="#f59e0b" title="Sad">
                                        <span class="sp-dock-emoji">😢</span>
                                        <span class="sp-dock-label">Sad</span>
                                    </button>
                                    <button type="button" class="sp-dock-reaction" data-reaction="angry" data-label="Angry" data-icon="bi-emoji-angry-fill" data-color="#ea580c" title="Angry">
                                        <span class="sp-dock-emoji">😡</span>
                                        <span class="sp-dock-label">Angry</span>
                                    </button>
                                </div>

                                <button type="button" class="sp-post-action community-like-btn is-liked">
                                    <i class="bi bi-heart-fill sp-action-love-icon"></i>
                                    <span class="sp-like-label">Love</span>
                                </button>
                            </div>

                            <button type="button" class="sp-post-action">
                                <i class="bi bi-chat"></i>
                                <span>Comment</span>
                            </button>
                            <button type="button" class="sp-post-action">
                                <i class="bi bi-share"></i>
                                <span>Share</span>
                            </button>
                        </div>

                        {{-- Comments Thread --}}
                        <div class="sp-comments">
                            <div class="sp-comment-thread">
                                <div class="sp-avatar sp-avatar-sm sp-comment-avatar">
                                    <img src="{{ asset('voting-assets/img/candidate-sample.jpg') }}" alt="" onerror="this.outerHTML='<span>JC</span>'">
                                </div>
                                <div class="sp-comment-content">
                                    <div class="sp-comment-bubble">
                                        <strong>John Dela Cruz</strong>
                                        <p>Looking forward to it! 🙌</p>
                                    </div>
                                    <div class="sp-comment-subactions">
                                        <button type="button" class="sp-sub-btn">Like</button>
                                        <span class="sp-sub-dot">·</span>
                                        <button type="button" class="sp-sub-btn">Reply</button>
                                        <span class="sp-sub-dot">·</span>
                                        <span class="sp-sub-time">1m</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Comment Composer Bar --}}
                        <form class="sp-comment-form" onsubmit="event.preventDefault();">
                            <div class="sp-avatar sp-avatar-sm sp-comment-composer-avatar">
                                <span>{{ $student->initials() ?? 'MV' }}</span>
                            </div>
                            <div class="sp-comment-input-capsule">
                                <input type="text" placeholder="Write a comment…" autocomplete="off">
                                <div class="sp-comment-inline-tools">
                                    <button type="button" class="sp-comment-tool-btn" title="Emoji"><i class="bi bi-emoji-smile"></i></button>
                                    <button type="button" class="sp-comment-tool-btn" title="Attach Photo"><i class="bi bi-camera"></i></button>
                                    <button type="button" class="sp-comment-tool-btn" title="GIF"><i class="bi bi-filetype-gif"></i></button>
                                    <button type="button" class="sp-comment-tool-btn" title="Sticker"><i class="bi bi-sticky"></i></button>
                                </div>
                            </div>
                        </form>
                    </article>
                @endforelse
            </div>

            <div class="sp-pagination">
                {{ $posts->links() }}
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
                                <small id="spActPartSub">Active RSVP list</small>
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
                        <div class="sp-act-demographics-grid">
                            <div class="sp-act-demo-card">
                                <h4><i class="bi bi-pie-chart-fill"></i> Attendee Demographics (By Year)</h4>
                                <div class="sp-act-demo-bars" id="spActDemoBars"></div>
                            </div>
                            <div class="sp-act-trust-card">
                                <div class="sp-trust-badge-icon"><i class="bi bi-patch-check-fill"></i></div>
                                <h4>OrgChain Verified Participation</h4>
                                <p>Attendance is digitally recorded through student QR badge check-in, providing immutable proof of leadership & extracurricular involvement.</p>
                                <div class="sp-trust-status">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <span>Cryptographically Logged to Ledger</span>
                                </div>
                            </div>
                        </div>
                        <div class="sp-act-rsvp-box">
                            <div>
                                <strong>Want to participate in this event?</strong>
                                <p>Register your attendance to receive official event updates and a verified certificate of participation.</p>
                            </div>
                            <button type="button" class="btn btn-primary sp-rsvp-action-btn" id="spActRsvpBtn">
                                <i class="bi bi-bookmark-check-fill"></i>
                                <span id="spActRsvpBtnText">RSVP for Event</span>
                            </button>
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
