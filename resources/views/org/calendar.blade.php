@extends('org.layout')

@section('title', 'Calendar')

@section('header')
    <h1>Calendar</h1>
    <p class="org-welcome">Track your org events &amp; activities</p>
@endsection

@section('actions')
    <a href="{{ route('office.activities') }}" class="org-btn org-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Event
    </a>
@endsection

@section('content')
    <div class="org-calendar-toolbar">
        <button type="button" class="org-filter-btn">All <i class="bi bi-chevron-down"></i></button>
        <div class="org-month-nav">
            <button type="button" aria-label="Previous month"><i class="bi bi-chevron-left"></i></button>
            <strong>{{ $monthLabel }}</strong>
            <button type="button" aria-label="Next month"><i class="bi bi-chevron-right"></i></button>
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
                <div class="org-cal-day {{ $day['inMonth'] ? '' : 'is-muted' }} {{ $day['hasEvent'] ? 'has-event' : '' }} {{ $day['date']->isToday() ? 'is-today' : '' }}">
                    <span>{{ $day['date']->day }}</span>
                    @if ($day['hasEvent'])
                        <i></i>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="org-cal-legend">
            <span><i class="org-legend-dot is-today"></i> Today</span>
            <span><i class="org-legend-dot has-event"></i> Scheduled activity</span>
        </div>
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
                            <small>{{ \Illuminate\Support\Carbon::parse($event['upcoming_at'])->format('M j, Y g:i A') }}</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
