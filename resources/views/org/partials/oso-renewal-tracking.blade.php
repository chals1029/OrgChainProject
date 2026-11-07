@php
    $trackingLabels = [
        'approved' => ['Renewed', 'green'],
        'submitted' => ['Pending filing', 'amber'],
        'returned' => ['Pending filing', 'amber'],
        'rejected' => ['Not yet submitted', 'muted'],
        'none' => ['Not yet submitted', 'muted'],
        'draft' => ['Not yet submitted', 'muted'],
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
    .tosa-pagination { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-top:.85rem; }
    .tosa-pagination .tosa-actions { margin-left:auto; display:inline-flex; align-items:center; gap:.4rem; flex-wrap:nowrap; }
    .tosa-note { color:#6b6064; font-size:.84rem; margin:.3rem 0; line-height:1.45; }
</style>
<div id="osoRenewalDashboard" class="or-dashboard">
    @if ($window)
        <section class="or-track-metrics" aria-label="Renewal status totals">
            @foreach ([
                ['Renewed', $trackingCounts->get('approved', 0)],
                ['Pending filing', $trackingCounts->get('submitted', 0) + $trackingCounts->get('returned', 0)],
                ['Not yet submitted', $trackingCounts->get('none', 0) + $trackingCounts->get('draft', 0) + $trackingCounts->get('rejected', 0)],
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
                    <option value="submitted">Pending filing</option>
                    <option value="none">Not yet submitted</option>
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
                            $filterStatus = in_array($status, ['submitted', 'returned'], true) ? 'submitted' : (in_array($status, ['none', 'draft', 'rejected'], true) ? 'none' : $status);
                            [$label, $color] = $trackingLabels[$status] ?? ['Not yet submitted', 'muted'];
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
        <div class="tosa-pagination" id="renewalTrackingPagination">
            <span class="tosa-note" id="renewalTrackingPageNote">Page 1 of 1</span>
            <div class="tosa-actions" id="renewalTrackingActions">
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="renewalTrackingPrev" style="display:none;">Previous</button>
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="renewalTrackingNext" style="display:none;">Next</button>
            </div>
        </div>
    </section>
</div>
<script>
    (() => {
        const search = document.getElementById('renewalTrackingSearch');
        const status = document.getElementById('renewalTrackingStatus');
        const rows = [...document.querySelectorAll('[data-tracking-row]')];
        const empty = document.getElementById('renewalTrackingEmpty');
        const pagination = document.getElementById('renewalTrackingPagination');
        const pageNote = document.getElementById('renewalTrackingPageNote');
        const prevBtn = document.getElementById('renewalTrackingPrev');
        const nextBtn = document.getElementById('renewalTrackingNext');

        const PAGE_SIZE = 10;
        let currentPage = 1;
        let matched = [];

        function renderPage() {
            const total = matched.length;
            const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            if (total === 0) {
                pagination.style.display = 'none';
                return;
            }
            pagination.style.display = 'flex';
            pageNote.textContent = `Page ${currentPage} of ${totalPages}`;

            prevBtn.style.display = currentPage > 1 ? '' : 'none';
            nextBtn.style.display = currentPage < totalPages ? '' : 'none';

            const start = (currentPage - 1) * PAGE_SIZE;
            const end = start + PAGE_SIZE;

            matched.forEach((row, index) => {
                row.hidden = !(index >= start && index < end);
            });
        }

        function filterTracking() {
            const query = search.value.trim().toLocaleLowerCase();
            matched = [];
            rows.forEach(row => {
                const matches = (!query || row.dataset.search.toLocaleLowerCase().includes(query))
                    && (status.value === 'all' || row.dataset.status === status.value);
                if (matches) {
                    matched.push(row);
                } else {
                    row.hidden = true;
                 }
            });
            empty.hidden = matched.length !== 0;
            currentPage = 1;
            renderPage();
        }

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage();
            }
        });

        nextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(matched.length / PAGE_SIZE);
            if (currentPage < totalPages) {
                currentPage++;
                renderPage();
            }
        });

        search.addEventListener('input', filterTracking);
        status.addEventListener('change', filterTracking);
        filterTracking();
    })();
</script>
