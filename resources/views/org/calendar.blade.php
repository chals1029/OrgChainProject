@extends('org.layout')

@section('title', 'Calendar')

@section('header')
    <h1>Calendar</h1>
    <p class="org-welcome">Track your org events &amp; activities</p>
@endsection

@section('actions')
    <a href="{{ route('office.activities.create') }}" class="org-btn org-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Event
    </a>
@endsection

@section('content')
    <div class="org-calendar-toolbar">
        <button type="button" class="org-filter-btn">All <i class="bi bi-chevron-down"></i></button>
        <div class="org-month-nav">
            <a href="{{ route('office.calendar', ['month' => $previousMonth]) }}" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a>
            <strong>{{ $monthLabel }}</strong>
            <a href="{{ route('office.calendar', ['month' => $nextMonth]) }}" aria-label="Next month"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <section class="org-calendar liquid-glass">
        <div class="org-cal-head">
            @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)
                <span>{{ $dow }}</span>
            @endforeach
        </div>
        <div class="org-cal-grid">
            @foreach ($days as $day)
                @php $hasEvents = count($day['events']) > 0; @endphp
                <button
                    type="button"
                    class="org-cal-day {{ $day['inMonth'] ? '' : 'is-muted' }} {{ $hasEvents ? 'has-event' : '' }} {{ $day['date']->isToday() ? 'is-today' : '' }}"
                    data-calendar-date="{{ $day['date']->toDateString() }}"
                    data-calendar-events-b64="{{ base64_encode(json_encode($day['events'])) }}"
                    aria-label="{{ $day['date']->format('F j, Y') }}{{ $hasEvents ? ': '.count($day['events']).' activity or activities' : '' }}"
                >
                    <span>{{ $day['date']->day }}</span>
                    @if ($hasEvents)
                        <i></i>
                    @endif
                </button>
            @endforeach
        </div>
        <div class="org-cal-legend">
            <span><i class="org-legend-dot is-today"></i> Today</span>
            <span><i class="org-legend-dot has-event"></i> Scheduled activity</span>
        </div>
    </section>

    <section class="org-calendar-detail liquid-glass" id="calendarDetail" aria-live="polite">
        <div class="org-calendar-detail-empty" id="calendarDetailEmpty">
            <i class="bi bi-calendar2-week"></i>
            <div>
                <strong>Select a date with an activity</strong>
                <p>Activity details will appear here.</p>
            </div>
        </div>
        <div id="calendarDetailContent" hidden></div>
    </section>

    @if ($events->isNotEmpty())
        <section class="org-panel liquid-glass" style="margin-top: 1rem;">
            <div class="org-panel-head">
                <h2><i class="bi bi-calendar-event-fill"></i> Upcoming on calendar</h2>
            </div>
            <ul class="org-updates">
                @foreach ($events as $event)
                    <li>
                        <span class="org-dot org-dot-{{ $event['status_key'] }}"></span>
                        <div>
                            <strong>{{ $event['title'] }}</strong>
                            <small>{{ \Illuminate\Support\Carbon::parse($event['starts_at'])->format('M j, Y g:i A') }} · {{ $event['location'] }}</small>
                        </div>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const detail = document.getElementById('calendarDetailContent');
            const empty = document.getElementById('calendarDetailEmpty');
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            })[character]);

            document.querySelectorAll('.org-cal-day').forEach((day) => {
                day.addEventListener('click', () => {
                    document.querySelectorAll('.org-cal-day.is-selected').forEach((item) => item.classList.remove('is-selected'));
                    day.classList.add('is-selected');

                    const rawB64 = day.dataset.calendarEventsB64 || '';
                    let events = [];
                    if (rawB64) {
                        try {
                            events = JSON.parse(decodeURIComponent(escape(atob(rawB64))));
                        } catch (_) {
                            try { events = JSON.parse(atob(rawB64)); } catch (__) {}
                        }
                    }

                    const dateLabel = day.getAttribute('aria-label') || 'Selected Date';

                    if (!events || !events.length) {
                        empty.hidden = false;
                        detail.hidden = true;
                        const emptyText = empty.querySelector('p');
                        if (emptyText) {
                            emptyText.textContent = `No activities scheduled for ${dateLabel.split(':')[0]}. Click "Add Event" to create one.`;
                        }
                        return;
                    }

                    empty.hidden = true;
                    detail.hidden = false;
                    detail.innerHTML = `
                        <div class="org-calendar-detail-head">
                            <div>
                                <span class="org-eyebrow"><i class="bi bi-calendar-event"></i> ${escapeHtml(events[0].date_label || events[0].date_key)}</span>
                                <h2>${events.length} ${events.length === 1 ? 'activity' : 'activities'} scheduled</h2>
                            </div>
                        </div>
                        <div class="org-calendar-event-list">
                            ${events.map((event) => `
                                <article class="org-calendar-event-card">
                                    <span class="org-calendar-event-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                                    <div>
                                        <div class="org-calendar-event-title">
                                            <h3>${escapeHtml(event.title)}</h3>
                                            <span class="org-status org-status-${escapeHtml(event.status_key || 'created')}">${escapeHtml(event.status || 'Scheduled')}</span>
                                        </div>
                                        <p>
                                            <span><i class="bi bi-clock"></i> ${escapeHtml(event.time_label || 'Time TBD')}</span>
                                            <span><i class="bi bi-geo-alt-fill"></i> ${escapeHtml(event.location || 'Venue TBD')}</span>
                                        </p>
                                        ${event.note ? `<small>${escapeHtml(event.note)}</small>` : ''}
                                    </div>
                                </article>
                            `).join('')}
                        </div>`;

                    document.getElementById('calendarDetail')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });

            const firstEventDay = document.querySelector('.org-cal-day.has-event') || document.querySelector('.org-cal-day.is-today');
            firstEventDay?.click();
        });
    </script>
@endpush
