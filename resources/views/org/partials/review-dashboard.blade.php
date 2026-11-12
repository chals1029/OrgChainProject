@php
    $deskLabel = strtoupper($office->office_role);
    $finalDesk = $office->office_role === 'oc';
    $rate = $reviewOverview['forwardedPercent'];
@endphp

<style>
    .org-review-overview .org-dash-card-header { flex-wrap: wrap; gap: 0.75rem; }
    .org-review-progress { margin-bottom: 1.15rem; }
    .org-review-progress-heading { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; margin-bottom: 0.55rem; }
    .org-review-progress-label { color: #51464a; font-size: 0.82rem; font-weight: 700; }
    .org-review-progress-value { color: #8b1828; font-size: 1.1rem; font-weight: 800; white-space: nowrap; }
    .org-review-progress-track { height: 0.6rem; overflow: hidden; border-radius: 999px; background: #f1e7e9; }
    .org-review-progress-fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg, #8b1828, #b12b3d); transition: width 0.4s ease; }
    .org-review-metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 160px), 1fr)); gap: 0.75rem; }
    .org-review-metric { display: flex; align-items: flex-start; gap: 0.7rem; min-width: 0; padding: 0.9rem; border: 1px solid #f0e6e8; border-radius: 12px; background: #fcf9fa; }
    .org-review-metric-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 2rem; height: 2rem; border-radius: 8px; font-size: 1rem; }
    .org-review-metric-icon.is-pending { color: #925400; background: #fff2d9; }
    .org-review-metric-icon.is-endorsed { color: #167047; background: #e4f5eb; }
    .org-review-metric-icon.is-approved { color: #8b1828; background: #f7e8eb; }
    .org-review-metric-content { display: flex; flex-direction: column; min-width: 0; gap: 0.15rem; }
    .org-review-metric-label { color: #51464a; font-size: 0.76rem; font-weight: 700; line-height: 1.3; }
    .org-review-metric-value { color: #1a1618; font-size: 1.25rem; font-weight: 800; line-height: 1.2; }
    .org-review-metric-detail { color: #6f6468; font-size: 0.72rem; line-height: 1.35; }
    @media (prefers-reduced-motion: reduce) { .org-review-progress-fill { transition: none; } }
</style>

<section class="org-dash-card org-review-overview" aria-label="{{ $deskLabel }} review overview">
    <div class="org-dash-card-header">
        <h3 class="org-dash-card-title"><i class="bi bi-shield-check" style="color: #8b1828;" aria-hidden="true"></i> {{ $deskLabel }} Review Overview</h3>
        <span class="ovcaa-endorsed-badge">{{ $reviewOverview['forwarded'] }}/{{ $reviewOverview['total'] }} {{ $finalDesk ? 'Approved' : 'Endorsed' }}</span>
    </div>
    <div class="org-review-progress">
        <div class="org-review-progress-heading">
            <span class="org-review-progress-label">{{ $finalDesk ? 'Final approval rate' : 'Forwarded & endorsed rate' }}</span>
            <span class="org-review-progress-value">{{ $rate === null ? 'No data' : $rate.'%' }}</span>
        </div>
        @if ($rate !== null)
            <div class="org-review-progress-track" role="progressbar" aria-label="{{ $finalDesk ? 'Final approval rate' : 'Forwarded and endorsed rate' }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $rate }}">
                <div class="org-review-progress-fill" style="width: {{ $rate }}%;"></div>
            </div>
        @else
            <div class="org-review-progress-track" aria-hidden="true"></div>
        @endif
    </div>
    <div class="org-review-metrics">
        <div class="org-review-metric">
            <span class="org-review-metric-icon is-pending"><i class="bi bi-clock" aria-hidden="true"></i></span>
            <div class="org-review-metric-content">
                <span class="org-review-metric-label">{{ $deskLabel === 'SDO' ? 'Pending Evaluation' : 'Action Pending' }}</span>
                <strong class="org-review-metric-value">{{ $reviewOverview['pending'] }}</strong>
                <span class="org-review-metric-detail">{{ $deskLabel === 'SDO' ? 'Activities awaiting SDO review' : 'Awaiting '.$deskLabel.' '.($finalDesk ? 'final approval' : 'review') }}</span>
            </div>
        </div>
        <div class="org-review-metric">
            <span class="org-review-metric-icon is-endorsed"><i class="bi bi-check-circle" aria-hidden="true"></i></span>
            <div class="org-review-metric-content">
                <span class="org-review-metric-label">{{ $deskLabel === 'SDO' ? 'SDG Compliant' : ($finalDesk ? 'Approved by OC' : 'Endorsed Beyond '.$deskLabel) }}</span>
                <strong class="org-review-metric-value">{{ $reviewOverview['forwarded'] }}</strong>
                @if ($deskLabel === 'SDO')
                    <span class="org-review-metric-detail">Activities that passed evaluation</span>
                @endif
            </div>
        </div>
        <div class="org-review-metric">
            <span class="org-review-metric-icon is-approved"><i class="bi bi-check-all" aria-hidden="true"></i></span>
            <div class="org-review-metric-content">
                <span class="org-review-metric-label">{{ $deskLabel === 'SDO' ? 'Total Reviewed' : 'OC Final Approval' }}</span>
                <strong class="org-review-metric-value">{{ $deskLabel === 'SDO' ? $reviewOverview['total'] : $reviewOverview['finalApproved'] }}</strong>
                @if ($deskLabel === 'SDO')
                    <span class="org-review-metric-detail">All submitted activities</span>
                @endif
            </div>
        </div>
    </div>
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
