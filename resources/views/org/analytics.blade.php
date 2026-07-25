@extends('org.layout')

@section('title', 'Analytics')

@section('header')
    <h1>Analytics</h1>
    <p class="org-welcome">Pipeline health and budget utilization</p>
@endsection

@section('content')
    <section class="org-stats">
        @foreach ([
            'created' => ['Created', 'bi-pencil-square', 'is-gold'],
            'verification' => ['Verification', 'bi-shield-check', 'is-blue'],
            'ovcaa_approved' => ['OVCAA Approved', 'bi-patch-check-fill', 'is-violet'],
            'completed' => ['Completed', 'bi-check2-circle', 'is-green'],
            'returned' => ['Returned', 'bi-arrow-counterclockwise', 'is-red'],
        ] as $key => [$label, $icon, $tone])
            <article class="org-stat-card liquid-glass">
                <span class="org-stat-icon {{ $tone }}"><i class="bi {{ $icon }}"></i></span>
                <strong>{{ $byStatus[$key] ?? 0 }}</strong>
                <span>{{ $label }}</span>
                <small>Current pipeline count</small>
            </article>
        @endforeach
    </section>

    <section class="org-panel liquid-glass">
        <div class="org-panel-head">
            <h2><i class="bi bi-pie-chart-fill"></i> Budget utilization</h2>
            <span>{{ $budgetItems->count() }} items</span>
        </div>
        <ul class="org-budget-list">
            @forelse ($budgetItems as $item)
                <li>
                    <div>
                        <strong>{{ $item->title }}</strong>
                        <small>{{ $item->category }} · FY {{ $item->fiscal_year }}</small>
                    </div>
                    <div class="org-budget-bar">
                        <span style="width: {{ $item->utilizationPercent() }}%"></span>
                    </div>
                    <div class="org-budget-nums">
                        <span class="org-budget-pct">{{ $item->utilizationPercent() }}%</span>
                        <em>Php {{ number_format($item->utilized) }} / {{ number_format($item->allocated) }}</em>
                    </div>
                </li>
            @empty
                <li class="org-empty">No budget records yet.</li>
            @endforelse
        </ul>
    </section>
@endsection
