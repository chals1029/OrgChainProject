@extends('org.layout')

@section('title', 'Calendar')

@section('header')
    <h1><strong>Calendar</strong></h1>
    <p class="org-welcome">Track activities, submission deadlines, and scheduled meetings.</p>
@endsection

@section('actions')
   
    <a href="{{ route('office.activities.create') }}" class="org-btn org-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Event
    </a>
@endsection

@section('content')
    <style>
        .org-cal-view-toggle {
            display: inline-flex;
            background: #ffffff;
            border: 1.5px solid #f0e6e8;
            border-radius: 9999px;
            padding: 3px;
        }

        .org-toggle-pill {
            border: none;
            background: transparent;
            color: #554d50;
            padding: 0.35rem 1.15rem;
            border-radius: 9999px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-toggle-pill.is-active {
            background: #7a1222;
            color: #ffffff;
        }

        /* Scope Bar */
        .org-cal-scope-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .org-cal-legend-dots {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: #554d50;
        }

        .org-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .org-dot-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .org-dot-indicator.is-red { background: #8b1828; }
        .org-dot-indicator.is-blue { background: #2563eb; }

        .org-scope-filter-group {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: #554d50;
        }

        .org-scope-pill-btn {
            border: 1.5px solid #e8dedf;
            background: #ffffff;
            color: #554d50;
            padding: 0.3rem 0.95rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-scope-pill-btn.is-active {
            background: #7a1222;
            border-color: #7a1222;
            color: #ffffff;
        }

        .org-scope-check-label {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            cursor: pointer;
            font-size: 0.82rem;
            color: #554d50;
        }

        /* 2-Column Calendar & Upcoming List */
        .org-cal-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.25rem;
            align-items: start;
            width: 100%;
        }

        .org-cal-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .org-cal-header-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .org-cal-nav-btn {
            background: #faf4f5;
            border: 1px solid #f0e6e8;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #554d50;
            transition: all 0.15s ease;
        }

        .org-cal-nav-btn:hover {
            background: #7a1222;
            color: #ffffff;
            border-color: #7a1222;
        }

        .org-cal-month-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
        }

        /* Calendar Days Grid */
        .org-cal-grid-wrapper {
            width: 100%;
            min-width: 0;
        }

        .org-cal-grid-table {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 6px;
            width: 100%;
            min-width: 0;
        }

        .org-cal-dow-header {
            text-align: center;
            font-size: 0.72rem;
            font-weight: 800;
            color: #7a7074;
            padding-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .org-cal-day-cell {
            min-height: 72px;
            min-width: 0;
            background: #ffffff;
            border: 1px solid #f5edee;
            border-radius: 12px;
            padding: 0.45rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        .org-cal-day-cell:hover {
            border-color: #d8c2c7;
            background: #fffafa;
        }

        .org-cal-day-cell.is-selected {
            border: 2px solid #7a1222;
            box-shadow: 0 0 0 2px rgba(122, 18, 34, 0.15);
        }

        .org-cal-day-num {
            font-size: 0.82rem;
            font-weight: 700;
            color: #3b3336;
        }

        .org-cal-day-cell.is-selected .org-cal-day-num {
            color: #7a1222;
            font-weight: 800;
        }

        .org-cal-event-pill {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.35rem;
            border-radius: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }

        .org-cal-event-pill.is-red {
            background: #fdf0f2;
            color: #8b1828;
            border: 1px solid #f8d7dc;
        }

        .org-cal-event-pill.is-blue {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        .org-cal-event-pill span.dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .org-cal-event-pill.is-red span.dot { background: #8b1828; }
        .org-cal-event-pill.is-blue span.dot { background: #2563eb; }

        /* Upcoming Sidebar List */
        .org-upcoming-sidebar-head {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            margin-bottom: 1.15rem;
        }

        .org-upcoming-side-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            max-height: 480px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 0.35rem;
            scrollbar-width: thin;
            scrollbar-color: #d8c2c7 transparent;
        }

        .org-upcoming-side-list::-webkit-scrollbar {
            width: 5px;
        }

        .org-upcoming-side-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .org-upcoming-side-list::-webkit-scrollbar-thumb {
            background: #e2d5d7;
            border-radius: 9999px;
        }

        .org-upcoming-side-list::-webkit-scrollbar-thumb:hover {
            background: #7a1222;
        }

        .org-side-event-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.55rem 0.65rem;
            border-radius: 12px;
            border: 1px solid transparent;
            border-bottom: 1px solid #f8f1f2;
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            background: transparent;
            text-align: left;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-side-event-item:hover {
            background: #fdfafb;
            border-color: #f2dfe2;
            transform: translateX(2px);
        }

        .org-side-event-item:last-child {
            border-bottom: 1px solid transparent;
        }

        .org-side-event-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
            flex: 1;
        }

        .org-side-date-badge {
            width: 40px;
            text-align: center;
            line-height: 1.1;
            flex-shrink: 0;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 0.35rem 0.2rem;
        }

        .org-side-date-badge small {
            display: block;
            font-size: 0.65rem;
            font-weight: 800;
            color: #7a7074;
            text-transform: uppercase;
        }

        .org-side-date-badge strong {
            display: block;
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a1618;
        }

        .org-side-event-info {
            min-width: 0;
            flex: 1;
            overflow: hidden;
        }

        .org-side-event-info strong {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: #1a1618;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.35;
        }

        .org-side-event-info small {
            display: block;
            font-size: 0.74rem;
            color: #7a7074;
            margin-top: 0.15rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .org-side-event-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }

        .org-side-event-dot.is-red { background: #8b1828; }
        .org-side-event-dot.is-blue { background: #2563eb; }

        @media (max-width: 992px) {
            .org-cal-main-grid {
                grid-template-columns: 1fr;
            }

            .org-upcoming-side-list {
                max-height: 380px;
            }
        }

        @media (max-width: 540px) {
            .org-cal-card {
                padding: 1rem;
            }

            .org-cal-grid-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 4px;
            }

            .org-cal-grid-table {
                min-width: 380px;
            }
        }

        /* Selected Day Bottom Card */
        .org-selected-day-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
        }

        .org-selected-day-head {
            margin-bottom: 0.95rem;
        }

        .org-selected-day-head h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0 0 0.2rem;
        }

        .org-selected-day-head small {
            font-size: 0.78rem;
            color: #7a7074;
        }

        .org-selected-event-card {
            background: #fdf2f4;
            border: 1px solid #fce3e6;
            border-radius: 16px;
            padding: 1.15rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .org-selected-event-title-row {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .org-selected-event-title-row strong {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1a1618;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .org-selected-event-meta {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 0.8rem;
            color: #554d50;
            font-weight: 600;
            margin-top: 0.15rem;
        }
    </style>

    {{-- Scope Filter Bar --}}
    <div class="org-cal-scope-bar">
        <div class="org-cal-legend-dots">
            <span class="org-legend-item"><span class="org-dot-indicator is-red"></span> In-Campus</span>
            <span class="org-legend-item"><span class="org-dot-indicator is-blue"></span> Off-Campus</span>
        </div>

        <div class="org-scope-filter-group">
            <span>Scope:</span>
            <button type="button" class="org-scope-pill-btn is-active" data-scope="all" aria-pressed="true">All</button>
            <button type="button" class="org-scope-pill-btn" data-scope="in" aria-pressed="false">In-Campus</button>
            <button type="button" class="org-scope-pill-btn" data-scope="off" aria-pressed="false">Off-Campus</button>
        </div>
    </div>

    <div class="org-cal-main-grid">
        <section class="org-cal-card">
            <div class="org-cal-header-nav">
                <a href="{{ route('office.calendar', ['month' => $previousMonth]) }}" class="org-cal-nav-btn" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a>
                <h2 class="org-cal-month-title">{{ $monthLabel }}</h2>
                <a href="{{ route('office.calendar', ['month' => $nextMonth]) }}" class="org-cal-nav-btn" aria-label="Next month"><i class="bi bi-chevron-right"></i></a>
            </div>

            <div class="org-cal-grid-wrapper">
                <div class="org-cal-grid-table" id="orgCalGrid">
                    <div class="org-cal-dow-header">MON</div>
                    <div class="org-cal-dow-header">TUE</div>
                    <div class="org-cal-dow-header">WED</div>
                    <div class="org-cal-dow-header">THU</div>
                    <div class="org-cal-dow-header">FRI</div>
                    <div class="org-cal-dow-header">SAT</div>
                    <div class="org-cal-dow-header">SUN</div>

                    @php
                        $todayKey = now()->toDateString();
                        $firstWithEvents = collect($days)->first(fn ($d) => $d['inMonth'] && count($d['events']) > 0);
                        $defaultKey = ($firstWithEvents['date'] ?? null)?->toDateString() ?? $todayKey;
                    @endphp

                    @foreach ($days as $day)
                        @php
                            $dateKey = $day['date']->toDateString();
                            $isSelected = $dateKey === $defaultKey;
                            $scopeList = collect($day['events'])
                                ->pluck('scope_key')
                                ->filter()
                                ->unique()
                                ->values()
                                ->implode(',');
                        @endphp
                        <div
                            class="org-cal-day-cell {{ $day['inMonth'] ? '' : 'is-muted' }} {{ $isSelected ? 'is-selected' : '' }}"
                            data-date-key="{{ $dateKey }}"
                            data-date-label="{{ $day['date']->format('F j, Y') }}"
                            data-scopes="{{ $scopeList }}"
                            role="button"
                            tabindex="0"
                        >
                            <span class="org-cal-day-num">{{ $day['inMonth'] ? $day['date']->day : '' }}</span>
                            @foreach ($day['events'] as $event)
                                @php
                                    $eventScope = $event['scope_key'] ?? 'in';
                                    $pillClass = $eventScope === 'off' ? 'is-blue' : 'is-red';
                                @endphp
                                <span class="org-cal-event-pill {{ $pillClass }}" data-event-scope="{{ $eventScope }}" data-event-item title="{{ $event['title'] }}">
                                    <span class="dot"></span> <span>{{ \Illuminate\Support\Str::limit($event['title'], 16) }}</span>
                                </span>
                            @endforeach
                            <span class="org-cal-event-pill is-red org-cal-event-more" data-more-count hidden></span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="org-cal-card">
            <h3 class="org-upcoming-sidebar-head">
                <i class="bi bi-calendar-check" style="color: #8b1828;"></i> Upcoming on Calendar
            </h3>
            <div class="org-upcoming-side-list" id="orgUpcomingList">
                @forelse (($upcomingEvents ?? $events->take(8)) as $event)
                    <button type="button" class="org-side-event-item" data-date-key="{{ $event['date_key'] }}" data-event-scope="{{ $event['scope_key'] ?? 'in' }}" data-event-item title="{{ $event['title'] }} ({{ $event['time_label'] }} · {{ $event['location'] }})">
                        <div class="org-side-event-left">
                            <div class="org-side-date-badge">
                                <small>{{ \Carbon\Carbon::parse($event['starts_at'])->format('M') }}</small>
                                <strong>{{ \Carbon\Carbon::parse($event['starts_at'])->format('j') }}</strong>
                            </div>
                            <div class="org-side-event-info">
                                <strong>{{ $event['title'] }}</strong>
                                <small>{{ $event['time_label'] }} · {{ $event['location'] }}</small>
                            </div>
                        </div>
                        <span class="org-side-event-dot {{ ($event['scope_key'] ?? 'in') === 'off' ? 'is-blue' : 'is-red' }}"></span>
                    </button>
                @empty
                    <p id="orgUpcomingNoEvents" style="color:#7a7074;font-size:0.9rem;">No upcoming events. Add an activity to populate the calendar.</p>
                @endforelse
                @if ($events->isNotEmpty())
                    <p id="orgUpcomingScopeEmpty" hidden style="color:#7a7074;font-size:0.9rem;">No upcoming events match this scope.</p>
                @endif
            </div>
        </section>
    </div>

    {{-- Full schedule details modal (opens when a day or event is clicked) --}}
    <dialog id="scheduleDetailModal" style="position:fixed;inset:0;margin:auto;border:none;border-radius:18px;padding:0;max-width:520px;width:calc(100% - 2rem);max-height:calc(100% - 2rem);box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="padding:1.25rem 1.35rem;max-height:calc(100vh - 4rem);overflow:auto;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:0.35rem;">
                <h3 id="schedModalTitle" style="margin:0;font-size:1.1rem;font-weight:800;color:#1a1618;">Schedule</h3>
                <button type="button" onclick="closeScheduleModal()" style="border:none;background:#f6eff0;border-radius:9999px;width:30px;height:30px;font-size:1rem;line-height:1;cursor:pointer;color:#7a7074;">&times;</button>
            </div>
            <div id="schedModalListWrap" hidden>
                <p style="margin:0 0 0.6rem;font-size:0.84rem;color:#7a7074;">Multiple schedules this day — pick one:</p>
                <div id="schedModalList" style="display:grid;gap:0.5rem;"></div>
            </div>
            <div id="schedModalDetailWrap">
                <div style="margin-bottom:0.9rem;"><span class="org-status-pill" id="schedModalStatus" style="font-size:0.72rem;padding:0.15rem 0.65rem;">Scheduled</span></div>
                <div style="display:grid;gap:0.55rem;font-size:0.88rem;color:#3f3538;">
                    <div><i class="bi bi-calendar-event" style="color:#8b1828;"></i> <strong>Date:</strong> <span id="schedModalDate">—</span></div>
                    <div><i class="bi bi-clock"></i> <strong>Time:</strong> <span id="schedModalTime">—</span></div>
                    <div><i class="bi bi-geo-alt-fill" style="color:#8b1828;"></i> <strong>Venue:</strong> <span id="schedModalVenue">—</span></div>
                </div>
                <div style="margin-top:0.9rem;">
                    <div style="font-size:0.78rem;font-weight:800;color:#7a7074;margin-bottom:0.3rem;">DETAILS</div>
                    <p id="schedModalNote" style="margin:0;font-size:0.9rem;line-height:1.65;color:#1a1618;white-space:pre-line;">—</p>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;gap:0.6rem;margin-top:1.1rem;">
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="schedModalBackBtn" onclick="backToScheduleList()" hidden>← All that day</button>
                <span></span>
                <div style="display:flex;gap:0.6rem;">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="closeScheduleModal()">Close</button>
                    <a class="org-btn org-btn-primary org-btn-sm" id="schedModalActivitiesLink" href="#" style="text-decoration:none;">View in Activities →</a>
                </div>
            </div>
        </div>
    </dialog>

    <script type="application/json" id="orgCalEventsJson">{!! json_encode($events->groupBy('date_key')) !!}</script>
    <script>
        (function () {
            let activeScope = 'all';
            let monthLoading = false;
            window.orgCalendarActiveScope = activeScope;

            function calEvents() {
                try {
                    return JSON.parse(document.getElementById('orgCalEventsJson').textContent || '{}');
                } catch (err) { return {}; }
            }

            function highlightDay(dateKey) {
                document.querySelectorAll('.org-cal-day-cell').forEach((cell) => {
                    cell.classList.toggle('is-selected', cell.dataset.dateKey === dateKey);
                });
            }

            function applyScopeFilter() {
                document.querySelectorAll('.org-cal-day-cell').forEach((cell) => {
                    const eventItems = Array.from(cell.querySelectorAll('[data-event-item]'));
                    const matchingItems = activeScope === 'all'
                        ? eventItems
                        : eventItems.filter((item) => item.dataset.eventScope === activeScope);
                    const show = activeScope === 'all' || eventItems.length === 0 || matchingItems.length > 0;

                    eventItems.forEach((item) => { item.style.display = 'none'; });
                    matchingItems.slice(0, 2).forEach((item) => { item.style.display = 'flex'; });

                    const more = cell.querySelector('[data-more-count]');
                    if (more) {
                        const remaining = Math.max(0, matchingItems.length - 2);
                        more.textContent = remaining > 0 ? `+${remaining} more` : '';
                        more.hidden = remaining === 0;
                    }

                    cell.style.opacity = show ? '1' : '0.28';
                    cell.style.pointerEvents = show ? '' : 'none';
                    cell.dataset.scopeHidden = show ? 'false' : 'true';
                });

                let visibleUpcoming = 0;
                document.querySelectorAll('#orgUpcomingList [data-event-item]').forEach((item) => {
                    const show = activeScope === 'all' || item.dataset.eventScope === activeScope;
                    item.hidden = !show;
                    if (show) visibleUpcoming++;
                });
                const scopeEmpty = document.getElementById('orgUpcomingScopeEmpty');
                if (scopeEmpty) scopeEmpty.hidden = activeScope === 'all' || visibleUpcoming > 0;
            }

            function bindDayCells() {
                document.querySelectorAll('.org-cal-day-cell').forEach((cell) => {
                    if (cell.dataset.calBound) return;
                    cell.dataset.calBound = '1';
                    cell.addEventListener('click', () => {
                        if (cell.dataset.scopeHidden === 'true' || !cell.dataset.dateKey || !cell.querySelector('.org-cal-day-num')?.textContent) return;
                        highlightDay(cell.dataset.dateKey);
                        openDayModal(cell.dataset.dateKey, cell.dataset.dateLabel);
                    });
                    cell.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            cell.click();
                        }
                    });
                });
            }

            function bindUpcoming() {
                document.querySelectorAll('#orgUpcomingList [data-date-key]').forEach((btn) => {
                    if (btn.dataset.calBound) return;
                    btn.dataset.calBound = '1';
                btn.addEventListener('click', () => {
                        if (btn.hidden) return;
                        const key = btn.dataset.dateKey;
                        const cell = document.querySelector(`.org-cal-day-cell[data-date-key="${key}"]`);
                        highlightDay(key);
                        openDayModal(key, cell?.dataset.dateLabel || key);
                        cell?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                });
            }

            /* Month arrows swap months without a full page reload. */
            function bindMonthNav() {
                document.querySelectorAll('.org-cal-nav-btn[href]').forEach((link) => {
                    if (link.dataset.calBound) return;
                    link.dataset.calBound = '1';
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        loadMonth(link.href);
                    });
                });
            }

            async function loadMonth(url) {
                if (monthLoading) return;
                monthLoading = true;
                const grid = document.getElementById('orgCalGrid');
                const prevOpacity = grid.style.opacity;
                grid.style.opacity = '0.35';
                grid.style.pointerEvents = 'none';
                try {
                    const res = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw new Error('month load failed');
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const swap = (sel) => {
                        const fresh = doc.querySelector(sel);
                        const current = document.querySelector(sel);
                        if (fresh && current) current.replaceWith(fresh);
                    };
                    swap('#orgCalGrid');
                    swap('.org-cal-month-title');
                    swap('#orgUpcomingList');
                    swap('#orgCalEventsJson');
                    const freshNav = doc.querySelectorAll('.org-cal-nav-btn[href]');
                    const curNav = document.querySelectorAll('.org-cal-nav-btn[href]');
                    freshNav.forEach((fresh, i) => {
                        if (curNav[i]) curNav[i].setAttribute('href', fresh.getAttribute('href'));
                    });
                    history.pushState({ calMonth: url }, '', url);
                    bindDayCells();
                    bindUpcoming();
                    applyScopeFilter();
                } catch (err) {
                    location.href = url; // fall back to full load
                } finally {
                    monthLoading = false;
                    const liveGrid = document.getElementById('orgCalGrid');
                    liveGrid.style.opacity = prevOpacity || '';
                    liveGrid.style.pointerEvents = '';
                }
            }

            window.addEventListener('popstate', () => location.reload());

            document.querySelectorAll('.org-scope-pill-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    activeScope = btn.dataset.scope;
                    window.orgCalendarActiveScope = activeScope;
                    document.querySelectorAll('.org-scope-pill-btn').forEach((b) => {
                        b.classList.toggle('is-active', b === btn);
                        b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                    });
                    applyScopeFilter();
                });
            });

            bindDayCells();
            bindUpcoming();
            bindMonthNav();
            applyScopeFilter();

            const initial = document.querySelector('.org-cal-day-cell.is-selected');
            if (initial) {
                initial.classList.add('is-selected');
            }
        })();

        let schedModalDayKey = null;

        function openDayModal(dateKey, dateLabel) {
            const eventsEl = document.getElementById('orgCalEventsJson');
            const eventsByDate = JSON.parse(eventsEl.textContent || '{}');
            const activeScope = window.orgCalendarActiveScope || 'all';
            const items = (eventsByDate[dateKey] || []).filter((event) => activeScope === 'all' || (event.scope_key || 'in') === activeScope);
            schedModalDayKey = dateKey;
            if (items.length === 1) {
                showScheduleDetails(items[0], dateKey, false);
            } else {
                showScheduleList(dateKey, dateLabel, items);
            }
            document.getElementById('scheduleDetailModal').showModal();
        }

        function showScheduleList(dateKey, dateLabel, items) {
            document.getElementById('schedModalTitle').textContent = dateLabel || dateKey;
            document.getElementById('schedModalDetailWrap').hidden = true;
            document.getElementById('schedModalBackBtn').hidden = true;
            const wrap = document.getElementById('schedModalListWrap');
            const list = document.getElementById('schedModalList');
            wrap.hidden = false;
            list.innerHTML = '';
            if (!items.length) {
                list.innerHTML = '<p style="margin:0;font-size:0.88rem;color:#7a7074;">No activities on this day.</p>';
                return;
            }
            items.forEach((event, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:0.6rem;text-align:left;padding:0.65rem 0.8rem;border:1px solid #f0e6e8;border-radius:12px;background:#fdfafb;cursor:pointer;font:inherit;';
                const left = document.createElement('span');
                const title = document.createElement('strong');
                title.style.display = 'block';
                title.style.fontSize = '0.88rem';
                title.textContent = event.title || 'Untitled';
                const meta = document.createElement('small');
                meta.style.color = '#7a7074';
                meta.textContent = (event.time_label || '') + (event.location ? ' · ' + event.location : '');
                left.appendChild(title);
                left.appendChild(meta);
                const arrow = document.createElement('span');
                arrow.style.color = '#8b1828';
                arrow.style.fontWeight = '800';
                arrow.textContent = '→';
                btn.appendChild(left);
                btn.appendChild(arrow);
                btn.addEventListener('click', () => showScheduleDetails(event, dateKey, true));
                list.appendChild(btn);
            });
        }

        function showScheduleDetails(event, dateKey, fromList) {
            document.getElementById('schedModalListWrap').hidden = true;
            document.getElementById('schedModalDetailWrap').hidden = false;
            document.getElementById('schedModalBackBtn').hidden = !fromList;
            document.getElementById('schedModalTitle').textContent = event.title || 'Schedule';
            document.getElementById('schedModalStatus').textContent = event.status || 'Scheduled';
            document.getElementById('schedModalDate').textContent = event.date_label || dateKey;
            document.getElementById('schedModalTime').textContent = event.time_label || 'Time to be announced';
            document.getElementById('schedModalVenue').textContent = event.location || 'Venue to be announced';
            document.getElementById('schedModalNote').textContent = event.note || 'No additional details for this schedule yet.';
            document.getElementById('schedModalActivitiesLink').href = @json(route('office.activities')) + '?q=' + encodeURIComponent(event.title || '');
        }

        function backToScheduleList() {
            const eventsEl = document.getElementById('orgCalEventsJson');
            const eventsByDate = JSON.parse(eventsEl.textContent || '{}');
            const cell = document.querySelector(`.org-cal-day-cell[data-date-key="${schedModalDayKey}"]`);
            showScheduleList(schedModalDayKey, cell?.dataset.dateLabel || schedModalDayKey, eventsByDate[schedModalDayKey] || []);
        }

        function closeScheduleModal() {
            const dialog = document.getElementById('scheduleDetailModal');
            if (dialog) dialog.close();
        }
    </script>
@endsection
