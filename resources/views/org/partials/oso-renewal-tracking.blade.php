@php
    $trackingLabels = [
        'approved' => ['Renewed', 'green'],
        'submitted' => ['Pending Review', 'amber'],
        'returned' => ['Returned for Revision', 'red'],
        'rejected' => ['Rejected', 'red'],
        'none' => ['Not Renewed', 'muted'],
        'draft' => ['Not Renewed', 'muted'],
        'unavailable' => ['No renewal period', 'muted'],
    ];
    $trackingCounts = collect($allOrganizations)->countBy('submission_status');
@endphp
<link rel="stylesheet" href="{{ asset('css/oso-renewal.css') }}?v={{ filemtime(public_path('css/oso-renewal.css')) }}">
<style>
    #osoRenewalDashboard .or-track-metrics { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.75rem; }
    #osoRenewalDashboard .or-track-metric { padding:1rem; background:#fff; border:1px solid #f0e6e8; border-radius:16px; }
    #osoRenewalDashboard .or-track-metric strong { display:block; color:#1a1618; font-size:1.45rem; line-height:1.2; }
    #osoRenewalDashboard .or-track-metric span { display:block; margin-top:.3rem; color:#6f6569; font-size:.75rem; font-weight:700; }
    #osoRenewalDashboard .or-track-table { min-width:680px; }
    @media (max-width:900px) { #osoRenewalDashboard .or-track-metrics { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width:560px) { #osoRenewalDashboard .or-track-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>
<div id="osoRenewalDashboard" class="or-dashboard">
    @if ($window)
        <section class="or-track-metrics" aria-label="Renewal status totals">
            @foreach ([
                ['Renewed', $trackingCounts->get('approved', 0)],
                ['Pending Review', $trackingCounts->get('submitted', 0)],
                ['Not Renewed', $trackingCounts->get('none', 0) + $trackingCounts->get('draft', 0)],
                ['Returned for Revision', $trackingCounts->get('returned', 0)],
                ['Rejected', $trackingCounts->get('rejected', 0)],
            ] as [$label, $count])
                <article class="or-track-metric"><strong>{{ $count }}</strong><span>{{ $label }}</span></article>
            @endforeach
        </section>
    @endif
    <section class="or-card" aria-labelledby="renewalTrackingHeading">
        <header class="or-card-head">
            <div>
                <h2 id="renewalTrackingHeading">Organization Renewal Status</h2>
                <p class="or-subtitle">@if ($window) Current renewal window: A.Y. {{ $window->academic_year }} · {{ $window->semester }}. Includes every registered organization. @else No renewal window is configured. Create one on the Renewal Dashboard to track its status. @endif</p>
            </div>
        </header>
        <div class="or-filters">
            <label class="or-search"><i class="bi bi-search" aria-hidden="true"></i><input id="renewalTrackingSearch" type="search" placeholder="Search organization or college…" aria-label="Search organization or college"></label>
            <select id="renewalTrackingStatus" aria-label="Filter renewal status">
                <option value="all">All statuses</option>
                @if ($window)
                    <option value="approved">Renewed</option>
                    <option value="submitted">Pending Review</option>
                    <option value="none">Not Renewed</option>
                    <option value="returned">Returned for Revision</option>
                    <option value="rejected">Rejected</option>
                @endif
            </select>
        </div>
        <div class="or-table-scroll">
            <table class="or-table or-track-table">
                <thead><tr><th scope="col">Organization</th><th scope="col">Renewal Status</th><th scope="col">Submitted</th><th scope="col">Application</th></tr></thead>
                <tbody>
                    @foreach ($allOrganizations as $organization)
                        @php
                            $status = $window ? $organization['submission_status'] : 'unavailable';
                            $filterStatus = $status === 'draft' ? 'none' : $status;
                            [$label, $color] = $trackingLabels[$status] ?? [ucfirst($status), 'muted'];
                        @endphp
                        <tr data-tracking-row data-status="{{ $filterStatus }}" data-search="{{ $organization['name'].' '.$organization['short_name'].' '.$organization['college'] }}">
                            <td><div class="or-org"><div class="or-org-copy"><strong class="or-name">{{ $organization['name'] }}</strong><small class="or-college">{{ $organization['college'] }}</small></div></div></td>
                            <td><span class="or-pill is-{{ $color }}">{{ $label }}</span></td>
                            <td>{{ $organization['submitted_at']?->format('M j, Y') ?? 'Not submitted' }}</td>
                            <td>@if ($organization['submission_id'])<a class="app-view-action" href="{{ route('office.renewal.submissions.show', $organization['submission_id']) }}" aria-label="View renewal application for {{ $organization['name'] }}"><i class="bi bi-eye" aria-hidden="true"></i> View</a>@else<span class="or-helper">No application</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p id="renewalTrackingEmpty" class="or-empty" @if ($allOrganizations->isNotEmpty()) hidden @endif>No organizations match these filters.</p>
        <footer class="or-table-footer"><small id="renewalTrackingCount" role="status" aria-live="polite">{{ $allOrganizations->count() }} organizations</small></footer>
    </section>
</div>
<script>
    (() => {
        const search = document.getElementById('renewalTrackingSearch');
        const status = document.getElementById('renewalTrackingStatus');
        const rows = [...document.querySelectorAll('[data-tracking-row]')];
        const empty = document.getElementById('renewalTrackingEmpty');
        const count = document.getElementById('renewalTrackingCount');
        function filterTracking() {
            const query = search.value.trim().toLocaleLowerCase();
            let shown = 0;
            rows.forEach(row => {
                const matches = (!query || row.dataset.search.toLocaleLowerCase().includes(query))
                    && (status.value === 'all' || row.dataset.status === status.value);
                row.hidden = !matches;
                if (matches) shown++;
            });
            empty.hidden = shown !== 0;
            count.textContent = `${shown} ${shown === 1 ? 'organization' : 'organizations'}`;
        }
        search.addEventListener('input', filterTracking);
        status.addEventListener('change', filterTracking);
    })();
</script>
