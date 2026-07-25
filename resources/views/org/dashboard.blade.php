@extends('org.layout')

@section('title', 'Dashboard')

@section('header')
    <h1>Dashboard</h1>
    <p class="org-welcome">Welcome, {{ $brand['role'] }}</p>
@endsection

@section('actions')
    <a href="{{ route('office.activities') }}" class="org-btn org-btn-primary">
        <i class="bi bi-plus-lg"></i> Create An Activity/Event
    </a>
    <a href="{{ route('office.calendar') }}" class="org-btn org-btn-ghost">
        <i class="bi bi-calendar3"></i> Open Calendar
    </a>
@endsection

@section('content')
    <section class="org-stats">
        <article class="org-stat-card liquid-glass">
            <span class="org-stat-icon is-red"><i class="bi bi-collection-fill"></i></span>
            <strong>{{ $stats['total'] }}</strong>
            <span>Total Activities</span>
            <small>{{ $stats['approved'] }} approved or completed</small>
        </article>
        <article class="org-stat-card liquid-glass">
            <span class="org-stat-icon is-green"><i class="bi bi-check-circle-fill"></i></span>
            <strong>{{ $stats['approved'] }}</strong>
            <span>Approved</span>
            <small>Ready for semester report</small>
        </article>
        <article class="org-stat-card liquid-glass">
            <span class="org-stat-icon is-gold"><i class="bi bi-hourglass-split"></i></span>
            <strong>{{ $stats['pending'] }}</strong>
            <span>Pending</span>
            <small>In OSO/OVCAA workflow</small>
        </article>
        <article class="org-stat-card liquid-glass">
            <span class="org-stat-icon is-violet"><i class="bi bi-cash-stack"></i></span>
            <strong>Php {{ number_format($stats['expenses']) }}</strong>
            <span>Expenses Recorded</span>
            <small>Activities with utilization</small>
        </article>
    </section>

    @if ($upcoming)
        @php
            $upAt = \Illuminate\Support\Carbon::parse($upcoming['upcoming_at'] ?? 'now');
        @endphp
        <section class="org-upcoming liquid-glass">
            <div class="org-upcoming-copy">
                <p class="org-eyebrow"><i class="bi bi-stars"></i> Next upcoming</p>
                <h2>{{ $upcoming['title'] }}</h2>
                <p class="org-upcoming-meta">
                    <i class="bi bi-clock"></i> {{ $upAt->format('M j, Y \a\t g:i A') }}
                </p>
                @if (!empty($upcoming['archive_ready']))
                    <span class="org-chip"><i class="bi bi-archive"></i> {{ $upcoming['archive_ready'] }} Archive Ready</span>
                @endif
            </div>
            <div class="org-upcoming-side">
                <span class="org-status org-status-{{ $upcoming['status_key'] }}">{{ $upcoming['pending_label'] ?? $upcoming['status'] }}</span>
                <span class="org-hero-date" aria-hidden="true">
                    <strong>{{ $upAt->format('j') }}</strong>
                    <small>{{ $upAt->format('M') }}</small>
                </span>
            </div>
            <div class="org-upcoming-bar" aria-hidden="true"></div>
        </section>
    @endif

    <section class="org-panel liquid-glass">
        <div class="org-panel-head">
            <h2><i class="bi bi-kanban-fill"></i> Status Tracker</h2>
            <span>{{ count($tracker) }} in pipeline</span>
        </div>
        <ul class="org-tracker">
            @foreach ($tracker as $item)
                <li>
                    <div class="org-tracker-main">
                        <strong>{{ $item['title'] }}</strong>
                        <div class="org-stage-rail" data-stage="{{ $item['stage'] }}" data-stages="{{ $item['stages'] }}">
                            @for ($i = 1; $i <= $item['stages']; $i++)
                                <span class="{{ $i <= $item['stage'] ? 'is-on' : '' }}"></span>
                            @endfor
                        </div>
                        <small>{{ $item['stage'] }}/{{ $item['stages'] }} stages · {{ (int) round(($item['stage'] / max(1, $item['stages'])) * 100) }}%</small>
                    </div>
                    <span class="org-status org-status-{{ $item['status_key'] }}">{{ $item['status'] }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="org-panel liquid-glass">
        <div class="org-panel-head">
            <h2><i class="bi bi-activity"></i> Latest Updates</h2>
            <span>{{ count($updates) }} recent</span>
        </div>
        <ul class="org-updates">
            @foreach ($updates as $item)
                <li>
                    <span class="org-dot org-dot-{{ $item['status_key'] }}"></span>
                    <div>
                        <strong>{{ $item['title'] }}</strong>
                        <small>{{ $item['status'] }}</small>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
