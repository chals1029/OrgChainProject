@php
    $deskLabel = strtoupper($office->office_role);
    $finalDesk = $office->office_role === 'oc';
    $forwardedLabel = $finalDesk ? 'Finally approved' : 'Forwarded beyond '.$deskLabel;
    $rate = $reviewOverview['forwardedPercent'];
@endphp

<section class="org-dash-card" aria-label="{{ $deskLabel }} review overview">
    <div class="org-dash-card-header">
        <h3 class="org-dash-card-title"><i class="bi bi-clipboard-data-fill" aria-hidden="true"></i> {{ $deskLabel }} Review Overview</h3>
    </div>
    <p class="org-dash-card-sub">All recorded packages visible to this desk, based on their current workflow stage.</p>
    <div class="ovcaa-overview-body">
        <div class="ovcaa-metrics-split">
            <div class="ovcaa-kpi-block">
                <span class="ovcaa-kpi-subhead">{{ $forwardedLabel }}</span>
                <h2>{{ $rate === null ? 'No data' : $rate.'%' }}</h2>
                <p>{{ $reviewOverview['forwarded'] }} of {{ $reviewOverview['total'] }} visible activity packages</p>
            </div>
            @if ($rate !== null)
                <div class="ovcaa-donut-wrap" role="img" aria-label="{{ $forwardedLabel }}: {{ $rate }}%" style="color:#8b1828;">
                    <svg class="ovcaa-donut-svg" viewBox="0 0 98 98" aria-hidden="true">
                        <circle class="ovcaa-donut-track" cx="49" cy="49" r="40"></circle>
                        <circle class="ovcaa-donut-fill" cx="49" cy="49" r="40" style="stroke:currentColor;stroke-dashoffset:{{ 251.3 * (1 - $rate / 100) }};"></circle>
                    </svg>
                    <div class="ovcaa-donut-content"><span class="ovcaa-donut-val">{{ $rate }}%</span><span class="ovcaa-donut-lbl">{{ $finalDesk ? 'Approved' : 'Forwarded' }}</span></div>
                </div>
            @endif
        </div>
        @if ($reviewOverview['total'] === 0)
            <p class="org-dash-card-sub">No activity packages have reached this desk. Review statistics will appear when records are available.</p>
        @else
            <div class="org-budget-sub-stats">
                <div class="org-budget-sub-box"><span>Awaiting {{ $deskLabel }} review</span><strong>{{ $reviewOverview['pending'] }}</strong></div>
                @if (! $finalDesk)
                    <div class="org-budget-sub-box"><span>Awaiting OC final approval</span><strong>{{ $reviewOverview['finalPending'] }}</strong></div>
                @endif
                <div class="org-budget-sub-box"><span>Finally approved by OC</span><strong>{{ $reviewOverview['finalApproved'] }}</strong></div>
            </div>
        @endif
    </div>
    <p class="org-dash-card-sub">Workflow progress is not an independent verification of individual document files.</p>
</section>

<section class="org-dash-card org-review-queue" aria-label="{{ $deskLabel }} review queue">
    <div class="org-dash-card-header">
        <h3 class="org-dash-card-title"><i class="bi bi-clipboard-check-fill" aria-hidden="true"></i> {{ $deskLabel }} {{ $finalDesk ? 'Final Approval' : 'Review' }} Queue</h3>
        <span class="ovcaa-endorsed-badge">{{ $reviewOverview['queueCount'] }} pending</span>
    </div>
    <p class="org-dash-card-sub">Oldest pending packages first. Open a package to inspect its current documents before making a decision.</p>
    <div class="org-action-items-list">
        @forelse ($reviewOverview['queue'] as $item)
            <div class="ovcaa-action-item">
                <div class="ovcaa-action-left">
                    <div class="ovcaa-action-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></div>
                    <div class="ovcaa-action-meta"><strong>{{ $item['title'] }}</strong><small>{{ $item['organization'] ?: 'Organization not recorded' }} · {{ $item['status'] }}</small></div>
                </div>
                <a href="{{ route('office.activities', ['activity' => $item['id']]) }}" class="app-view-action" aria-label="View {{ $item['title'] }}"><i class="bi bi-eye" aria-hidden="true"></i> View</a>
            </div>
        @empty
            <p class="org-dash-card-sub">No activity packages are awaiting {{ $deskLabel }} {{ $finalDesk ? 'final approval' : 'review' }}.</p>
        @endforelse
    </div>
    @if ($reviewOverview['queueCount'] > count($reviewOverview['queue']))
        <p class="org-dash-card-sub">Showing {{ count($reviewOverview['queue']) }} of {{ $reviewOverview['queueCount'] }} pending packages.</p>
    @endif
    <a href="{{ route('office.activities') }}" class="org-dash-link">View all packages <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
</section>
