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
            grid-template-columns: 1.8fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .org-cal-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
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
        .org-cal-grid-table {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
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
            gap: 0.75rem;
        }

        .org-side-event-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid #f8f1f2;
        }

        .org-side-event-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .org-side-event-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .org-side-date-badge {
            width: 38px;
            text-align: center;
            line-height: 1.1;
            flex-shrink: 0;
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

        .org-side-event-info strong {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: #1a1618;
        }

        .org-side-event-info small {
            display: block;
            font-size: 0.74rem;
            color: #7a7074;
            margin-top: 0.1rem;
        }

        .org-side-event-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .org-side-event-dot.is-red { background: #8b1828; }
        .org-side-event-dot.is-blue { background: #2563eb; }

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
            <button type="button" class="org-scope-pill-btn is-active" data-scope="all">All</button>
            <button type="button" class="org-scope-pill-btn" data-scope="in">In-Campus</button>
            <button type="button" class="org-scope-pill-btn" data-scope="off">Off-Campus</button>
        </div>
    </div>

    <div class="org-cal-main-grid">
        <section class="org-cal-card">
            <div class="org-cal-header-nav">
                <a href="{{ route('office.calendar', ['month' => $previousMonth]) }}" class="org-cal-nav-btn" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a>
                <h2 class="org-cal-month-title">{{ $monthLabel }}</h2>
                <a href="{{ route('office.calendar', ['month' => $nextMonth]) }}" class="org-cal-nav-btn" aria-label="Next month"><i class="bi bi-chevron-right"></i></a>
            </div>

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
                    $defaultKey = $firstWithEvents['date']->toDateString() ?? $todayKey;
                @endphp

                @foreach ($days as $day)
                    @php
                        $dateKey = $day['date']->toDateString();
                        $isSelected = $dateKey === $defaultKey;
                        $scopeList = collect($day['events'])->map(function ($ev) {
                            $status = strtolower((string) ($ev['status_key'] ?? ''));
                            return str_contains($status, 'off') || str_contains(strtolower((string) ($ev['note'] ?? '')), 'off') ? 'off' : 'in';
                        })->unique()->values()->implode(',');
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
                        @foreach (array_slice($day['events'], 0, 2) as $event)
                            @php
                                $pillClass = str_contains(strtolower((string) ($event['status_key'] ?? '')), 'off') ? 'is-blue' : 'is-red';
                            @endphp
                            <span class="org-cal-event-pill {{ $pillClass }}" title="{{ $event['title'] }}">
                                <span class="dot"></span> {{ \Illuminate\Support\Str::limit($event['title'], 16) }}
                            </span>
                        @endforeach
                        @if (count($day['events']) > 2)
                            <span class="org-cal-event-pill is-red">+{{ count($day['events']) - 2 }} more</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="org-cal-card">
            <h3 class="org-upcoming-sidebar-head">
                <i class="bi bi-calendar-check" style="color: #8b1828;"></i> Upcoming on Calendar
            </h3>
            <div class="org-upcoming-side-list" id="orgUpcomingList">
                @forelse ($events->take(8) as $event)
                    <button type="button" class="org-side-event-item" data-date-key="{{ $event['date_key'] }}" style="width:100%;background:transparent;border:0;text-align:left;cursor:pointer;">
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
                        <span class="org-side-event-dot is-red"></span>
                    </button>
                @empty
                    <p style="color:#7a7074;font-size:0.9rem;">No upcoming events. Add an activity to populate the calendar.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="org-selected-day-card" id="orgSelectedDayCard">
        <div class="org-selected-day-head">
            <h3 id="orgSelectedDayTitle"><i class="bi bi-calendar-event" style="color: #8b1828;"></i> Select a day</h3>
            <small id="orgSelectedDayCount">0 items scheduled</small>
        </div>
        <div id="orgSelectedDayEvents"></div>
    </section>

    <script type="application/json" id="orgCalEventsJson">{!! json_encode($events->groupBy('date_key')) !!}</script>
    <script>
        (function () {
            const eventsByDate = JSON.parse(document.getElementById('orgCalEventsJson').textContent || '{}');
            const detailRoot = document.getElementById('orgSelectedDayEvents');
            const titleEl = document.getElementById('orgSelectedDayTitle');
            const countEl = document.getElementById('orgSelectedDayCount');
            let activeScope = 'all';

            function renderDay(dateKey, dateLabel) {
                document.querySelectorAll('.org-cal-day-cell').forEach((cell) => {
                    cell.classList.toggle('is-selected', cell.dataset.dateKey === dateKey);
                });

                const items = eventsByDate[dateKey] || [];
                titleEl.innerHTML = '<i class="bi bi-calendar-event" style="color:#8b1828;"></i> ' + (dateLabel || dateKey);
                countEl.textContent = items.length + (items.length === 1 ? ' item scheduled' : ' items scheduled');

                if (!items.length) {
                    detailRoot.innerHTML = '<p style="color:#7a7074;margin:0;">No activities on this day.</p>';
                    return;
                }

                detailRoot.innerHTML = items.map((event) => {
                    const href = @json(route('office.activities')) + '?q=' + encodeURIComponent(event.title || '');
                    return `
                        <a class="org-selected-event-card" href="${href}" style="display:block;text-decoration:none;color:inherit;margin-bottom:0.75rem;">
                            <div class="org-selected-event-title-row">
                                <strong><span class="org-dot-indicator is-red"></span> ${event.title}</strong>
                                <span class="org-status-pill" style="font-size:0.72rem;padding:0.15rem 0.65rem;">${event.status || 'Scheduled'}</span>
                            </div>
                            <div class="org-selected-event-meta">
                                <span><i class="bi bi-clock"></i> ${event.time_label || ''}</span>
                                <span><i class="bi bi-geo-alt-fill" style="color:#8b1828;"></i> ${event.location || ''}</span>
                            </div>
                        </a>`;
                }).join('');
            }

            document.querySelectorAll('.org-cal-day-cell').forEach((cell) => {
                cell.addEventListener('click', () => {
                    if (!cell.dataset.dateKey || !cell.querySelector('.org-cal-day-num')?.textContent) return;
                    renderDay(cell.dataset.dateKey, cell.dataset.dateLabel);
                });
                cell.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        cell.click();
                    }
                });
            });

            document.querySelectorAll('#orgUpcomingList [data-date-key]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const key = btn.dataset.dateKey;
                    const cell = document.querySelector(`.org-cal-day-cell[data-date-key="${key}"]`);
                    renderDay(key, cell?.dataset.dateLabel || key);
                    cell?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            });

            document.querySelectorAll('.org-scope-pill-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    activeScope = btn.dataset.scope;
                    document.querySelectorAll('.org-scope-pill-btn').forEach((b) => b.classList.toggle('is-active', b === btn));
                    document.querySelectorAll('.org-cal-day-cell').forEach((cell) => {
                        const scopes = (cell.dataset.scopes || '').split(',').filter(Boolean);
                        const show = activeScope === 'all' || scopes.length === 0 || scopes.includes(activeScope);
                        cell.style.opacity = show ? '1' : '0.35';
                    });
                });
            });

            const initial = document.querySelector('.org-cal-day-cell.is-selected') || document.querySelector('.org-cal-day-cell[data-date-key]');
            if (initial) {
                renderDay(initial.dataset.dateKey, initial.dataset.dateLabel);
            }
        })();
    </script>
@endsection
