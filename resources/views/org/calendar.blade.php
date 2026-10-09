@extends('org.layout')

@section('title', 'Calendar')

@section('header')
    <h1><strong>Calendar</strong></h1>
    <p class="org-welcome">Review activity schedules, approval status, and upcoming events.</p>
@endsection

@section('actions')
    @if ($office->office_role === 'so')
        <a href="{{ route('office.activities.create') }}" class="org-btn org-btn-primary">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Create Activity
        </a>
    @endif
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/org-calendar.css') }}?v={{ filemtime(public_path('css/org-calendar.css')) }}">
    @php
        $calendarUrl = route('office.calendar');
        $calendarLink = static function (array $overrides = []) use ($calendarFilters, $monthKey): string {
            $query = array_merge($calendarFilters, ['month' => $monthKey], $overrides);
            return route('office.calendar', array_filter($query, fn ($value) => $value !== '' && $value !== 'all' && $value !== 'auto'));
        };
        $hasFilters = $calendarFilters['q'] !== '' || $calendarFilters['scope'] !== 'all'
            || $calendarFilters['status'] !== 'all' || $calendarFilters['timing'] !== 'all';
        $statusIcon = static fn (string $status) => match ($status) {
            'approved' => 'bi-check-circle', 'completed' => 'bi-check-circle-fill',
            'returned' => 'bi-arrow-counterclockwise', default => 'bi-hourglass-split',
        };
        $calendarPayload = [
            'events' => $events->keyBy('id')->all(),
            'days' => collect($eventsByDate)->map(fn ($items) => array_column($items, 'id'))->all(),
        ];
    @endphp
    <div id="orgCalendarRoot" class="org-calendar" data-view="{{ $calendarFilters['view'] }}" data-month="{{ $monthKey }}" data-calendar-url="{{ $calendarUrl }}">
        <form class="cal-filters cal-card" method="get" action="{{ $calendarUrl }}" id="orgCalendarFilters">
            <input type="hidden" name="month" value="{{ $monthKey }}">
            <input type="hidden" name="view" value="{{ $calendarFilters['view'] }}">
            <label class="cal-field cal-search">
                <span>Activity or venue</span>
                <input type="search" name="q" value="{{ $calendarFilters['q'] }}" placeholder="Search activity or venue…" maxlength="200">
            </label>
            <label class="cal-field">
                <span>Scope</span>
                <select name="scope">
                    <option value="all" @selected($calendarFilters['scope'] === 'all')>All activities</option>
                    <option value="in" @selected($calendarFilters['scope'] === 'in')>In-campus</option>
                    <option value="off" @selected($calendarFilters['scope'] === 'off')>Off-campus</option>
                </select>
            </label>
            <label class="cal-field">
                <span>Approval status</span>
                <select name="status">
                    @foreach (['all' => 'All statuses', 'pending' => 'Pending approval', 'approved' => 'Approved', 'completed' => 'Completed', 'returned' => 'Returned for revision'] as $key => $label)
                        <option value="{{ $key }}" @selected($calendarFilters['status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="cal-field">
                <span>Schedule</span>
                <select name="timing">
                    @foreach (['all' => 'All schedules', 'upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'past' => 'Past'] as $key => $label)
                        <option value="{{ $key }}" @selected($calendarFilters['timing'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="cal-filter-actions">
                <button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
                @if ($hasFilters)
                    <a href="{{ $calendarLink(['q' => '', 'scope' => 'all', 'status' => 'all', 'timing' => 'all']) }}" class="cal-clear" data-cal-nav>Clear filters</a>
                @endif
            </div>
        </form>

        <p id="orgCalendarFeedback" class="cal-feedback" role="status" aria-live="polite"></p>
        <div class="cal-layout">
            <section class="cal-card cal-schedule" aria-labelledby="orgCalendarMonth">
                <div class="cal-toolbar">
                    <div class="cal-month-navigation">
                        <a href="{{ $calendarLink(['month' => $previousMonth]) }}" class="cal-nav" aria-label="Previous month" data-cal-nav><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                        <h2 id="orgCalendarMonth">{{ $monthLabel }}</h2>
                        <a href="{{ $calendarLink(['month' => $nextMonth]) }}" class="cal-nav" aria-label="Next month" data-cal-nav><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="cal-view-controls">
                        <a href="{{ $calendarLink(['month' => $todayMonth]) }}" class="cal-today" data-cal-nav data-cal-today>Today</a>
                        <nav class="cal-view-toggle" aria-label="Calendar view">
                            <a href="{{ $calendarLink(['view' => 'month']) }}" data-cal-nav data-cal-view="month" @if($calendarFilters['view'] !== 'agenda') aria-current="page" @endif><i class="bi bi-calendar3" aria-hidden="true"></i> Month</a>
                            <a href="{{ $calendarLink(['view' => 'agenda']) }}" data-cal-nav data-cal-view="agenda" @if($calendarFilters['view'] === 'agenda') aria-current="page" @endif><i class="bi bi-list-ul" aria-hidden="true"></i> Agenda</a>
                        </nav>
                    </div>
                </div>
                <div class="cal-legend"><span><i class="cal-dot cal-dot-in"></i> In-campus</span><span><i class="cal-dot cal-dot-off"></i> Off-campus</span><span class="cal-readonly"><i class="bi bi-lock" aria-hidden="true"></i> Read-only schedule</span></div>

                <div class="cal-month-view" id="orgCalMonthView">
                    <div class="cal-grid" aria-label="{{ $monthLabel }} activity schedule">
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                            <span class="cal-weekday">{{ $weekday }}</span>
                        @endforeach
                        @foreach ($days as $day)
                            @php $dateKey = $day['date']->toDateString(); @endphp
                            <button type="button" @class(['cal-day', 'is-outside' => !$day['inMonth'], 'is-today' => $dateKey === $todayKey]) data-cal-day="{{ $dateKey }}" data-date-label="{{ $day['date']->format('l, F j, Y') }}" aria-label="{{ $day['date']->format('l, F j, Y') }}: {{ count($day['events']) }} {{ count($day['events']) === 1 ? 'activity' : 'activities' }}">
                                <span class="cal-day-number">{{ $day['date']->day }}<span class="cal-sr-only">{{ $dateKey === $todayKey ? ' (Today)' : '' }}</span></span>
                                @foreach (array_slice($day['events'], 0, 2) as $event)
                                    <span class="cal-day-event cal-scope-{{ $event['scope_key'] }}" title="{{ $event['title'] }} · {{ $event['status'] }} · {{ $event['date_label'] }} · {{ $event['time_label'] }}">
                                        <i class="bi {{ $statusIcon($event['status_key']) }}" aria-hidden="true"></i>
                                        <span>{{ $event['date_key'] < $dateKey ? '↳ ' : '' }}{{ $event['title'] }}</span>
                                    </span>
                                @endforeach
                                @if (count($day['events']) > 2)<span class="cal-more">+{{ count($day['events']) - 2 }} more</span>@endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="cal-agenda-view" id="orgCalAgendaView">
                    @forelse ($agendaDays as $day)
                        <section class="cal-agenda-day" aria-label="{{ $day['date']->format('l, F j, Y') }}" data-agenda-date="{{ $day['date']->toDateString() }}">
                            <div @class(['cal-agenda-date', 'is-today' => $day['date']->toDateString() === $todayKey])><span>{{ $day['date']->format('D') }}</span><strong>{{ $day['date']->day }}</strong><span>{{ $day['date']->format('M') }}</span></div>
                            <div class="cal-agenda-events">
                                @foreach ($day['events'] as $event)
                                    <button type="button" class="cal-event cal-scope-{{ $event['scope_key'] }}" data-cal-event="{{ $event['id'] }}">
                                        <span class="cal-event-heading"><strong>{{ $event['title'] }}</strong><span class="cal-status cal-status-{{ $event['status_key'] }}">{{ $event['status'] }}</span></span>
                                        <span class="cal-event-meta">{{ $event['date_key'] < $day['date']->toDateString() ? 'Continues · ' : '' }}{{ $event['date_label'] }} · {{ $event['time_label'] }}</span>
                                        <span class="cal-event-meta"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $event['location'] }}</span>
                                        <span class="cal-event-tags"><span class="cal-timing cal-timing-{{ $event['timing_key'] }}">{{ $event['timing_label'] }}</span><span>{{ $event['scope_key'] === 'off' ? 'Off-campus' : 'In-campus' }}</span></span>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="cal-empty"><i class="bi bi-calendar2" aria-hidden="true"></i><strong>No matching activities this month</strong><p>{{ $hasFilters ? 'Try another month or clear your filters.' : 'Activity schedules will appear here when your desk has permitted activities.' }}</p></div>
                    @endforelse
                </div>
                @if ($agendaDays->isEmpty())<p class="cal-month-empty">No {{ $hasFilters ? 'matching ' : '' }}activities in {{ $monthLabel }}.</p>@endif
                <p class="cal-footnote">Approval and scheduled time are separate. Changes to an activity must follow the approval workflow.</p>
            </section>

            <aside class="cal-sidebar">
                @foreach (['ongoing' => ['label' => 'Ongoing now', 'items' => $ongoingEvents], 'upcoming' => ['label' => 'Upcoming activities', 'items' => $upcomingEvents]] as $sectionKey => $section)
                    <section class="cal-card cal-side-card" aria-labelledby="cal-{{ $sectionKey }}-heading">
                        <div class="cal-side-heading"><h2 id="cal-{{ $sectionKey }}-heading">{{ $section['label'] }}</h2><span class="cal-count">{{ $section['items']->count() }}</span></div>
                        <div class="cal-side-list" data-calendar-list="{{ $sectionKey }}">
                            @forelse ($section['items'] as $event)
                                <button type="button" class="cal-event cal-scope-{{ $event['scope_key'] }}" data-cal-event="{{ $event['id'] }}">
                                    <strong>{{ $event['title'] }}</strong>
                                    <span class="cal-event-meta">{{ $event['date_label'] }}</span>
                                    <span class="cal-event-meta">{{ $event['time_label'] }}</span>
                                    <span class="cal-event-meta"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $event['location'] }}</span>
                                    <span class="cal-event-tags"><span class="cal-status cal-status-{{ $event['status_key'] }}">{{ $event['status'] }}</span><span>{{ $event['scope_key'] === 'off' ? 'Off-campus' : 'In-campus' }}</span></span>
                                </button>
                            @empty
                                <p class="cal-side-empty">{{ $sectionKey === 'ongoing' ? 'No ongoing schedules' : 'No upcoming activities' }}{{ $hasFilters ? ' match these filters' : '' }}.</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </aside>
        </div>

        <dialog id="orgCalendarDialog" class="cal-dialog" aria-labelledby="calDialogTitle">
            <div class="cal-dialog-header"><h2 id="calDialogTitle">Activity schedule</h2><button type="button" class="cal-nav" data-cal-close aria-label="Close schedule details"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
            <div class="cal-dialog-body">
                <div id="calDialogList" hidden></div>
                <div id="calDialogDetails" hidden>
                    <div class="cal-detail-badges"><span id="calDetailStatus" class="cal-status"></span><span id="calDetailTiming" class="cal-timing"></span></div>
                    <dl class="cal-detail-fields"><dt>Dates</dt><dd id="calDetailDate"></dd><dt>Time</dt><dd id="calDetailTime"></dd><dt>Venue</dt><dd id="calDetailVenue"></dd><dt>Scope</dt><dd id="calDetailScope"></dd></dl>
                    <p id="calDetailNote" class="cal-detail-note"></p>
                </div>
            </div>
            <div class="cal-dialog-footer"><button type="button" class="org-btn org-btn-ghost org-btn-sm" id="calDialogBack" hidden>← All that day</button><a href="{{ route('office.activities') }}" class="org-btn org-btn-primary org-btn-sm" id="calDetailLink" hidden>View activity <i class="bi bi-arrow-right" aria-hidden="true"></i></a><button type="button" class="org-btn org-btn-ghost org-btn-sm" data-cal-close>Close</button></div>
        </dialog>
        <script type="application/json" id="orgCalendarData">{!! json_encode($calendarPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
    </div>
    <script src="{{ asset('js/org-calendar.js') }}?v={{ filemtime(public_path('js/org-calendar.js')) }}" defer></script>
@endsection
