@php
    $osoDirectoryFilters = array_filter([
        'academic_year' => $selectedYear,
        'semester' => $selectedSemester,
        'department' => request('department'),
    ], fn ($value) => $value !== null && $value !== '');
    $osoActivitiesByOrganization = collect($liveBudgetEntries ?? [])
        ->except('all')
        ->filter(fn ($entry) =>
            ! empty($entry['activityId'])
            && ($entry['academic_year'] ?? null) === $selectedYear
            && ($selectedSemester === 'Annual' || ($entry['semester'] ?? null) === $selectedSemester))
        ->map(function ($entry) {
            $entry['encodedExpenses'] = round(collect($entry['expenses'] ?? [])->sum('amount'), 2);
            return $entry;
        })
        ->groupBy('orgName');
    $osoDirectoryActivities = $osoActivitiesByOrganization->get($selectedOrganization, collect())->values();
@endphp

<link rel="stylesheet" href="{{ asset('css/oso-budget-activity-details.css') }}?v={{ filemtime(public_path('css/oso-budget-activity-details.css')) }}">

<style>
    .oso-cash-overview { display: grid; gap: 1.1rem; min-width: 0; }
    .oso-cash-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .oso-cash-heading h2 { margin: 0; font-size: 1.1rem; font-weight: 800; color: #1a1618; }
    .oso-cash-heading p { margin: .35rem 0 0; color: #786f73; font-size: .8rem; }
    .oso-cash-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
    .oso-cash-metric { display: grid; align-content: start; gap: .45rem; padding: 1rem; border: 1px solid #eadde0; border-radius: 14px; min-width: 0; background: #fff; }
    .oso-cash-metric span { color: #786f73; font-size: .78rem; font-weight: 700; }
    .oso-cash-metric strong { font-size: clamp(1.1rem, 1.6vw, 1.45rem); font-weight: 800; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .oso-cash-metric.is-inflow { background: #f0faf5; color: #157347; }
    .oso-cash-metric.is-outflow { background: #fff4f5; color: #8b1828; }
    .oso-cash-metric.is-ending { background: #8b1828; color: #fff; border-color: #8b1828; }
    .oso-cash-metric.is-ending span { color: #f6dce2; }
    .oso-cash-approved { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .6rem 1rem; padding: .8rem 1rem; border: 1px solid #eadde0; border-radius: 12px; background: #fdfafb; }
    .oso-cash-approved strong { color: #8b1828; font-variant-numeric: tabular-nums; }
    .oso-cash-note { margin: 0; font-size: .76rem; color: #786f73; line-height: 1.5; }
    .oso-cash-table-wrap { max-height: 520px; overflow: auto; border: 1px solid #eadde0; border-radius: 12px; }
    .oso-cash-table { width: 100%; min-width: 850px; border-collapse: collapse; font-size: .78rem; }
    .oso-cash-table th, .oso-cash-table td { padding: .85rem .9rem; text-align: left; border-bottom: 1px solid #f0e6e8; vertical-align: top; }
    .oso-cash-table th { position: sticky; top: 0; background: #fbf5f7; color: #786f73; font-size: .7rem; z-index: 1; }
    .oso-cash-table .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .oso-cash-table small { display: block; color: #786f73; margin-top: .25rem; }
    .oso-cash-table .org-btn { white-space: nowrap; }
    .oso-directory-table { min-width: 850px; table-layout: fixed; }
    .oso-directory-table .oso-directory-name { position: static; background: transparent; color: #1a1618; overflow-wrap: anywhere; }
    .oso-cash-overview [hidden] { display: none !important; }
    .oso-budget-directory { display: grid; gap: .85rem; min-width: 0; }
    .oso-budget-directory h3 { margin: 0; font-size: 1rem; }
    .oso-directory-head { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: .5rem 1rem; }
    .oso-directory-head .oso-cash-note { margin-top: .25rem; }
    .oso-directory-table th:first-child { width: 28%; }
    .oso-directory-table th:last-child { width: 13%; }
    .oso-directory-table .org-btn { padding: .4rem .65rem; font-size: .72rem; border-radius: 999px; }
    .oso-directory-pages { padding-top: .2rem; }
    .oso-directory-pages .org-report-page-btn { width: auto; min-width: 32px; padding: .35rem .6rem; }
    .oso-directory-search { display: flex; align-items: end; flex-wrap: wrap; gap: .6rem; }
    .oso-directory-search label { display: grid; gap: .4rem; flex: 1 1 240px; min-width: 0; font-size: .8rem; font-weight: 700; }
    .oso-directory-search input { width: 100%; min-width: 0; box-sizing: border-box; padding: .7rem .8rem; border: 1px solid #d8c8cd; border-radius: 10px; color: #1a1618; background: #fff; font: inherit; font-weight: 400; }
    .oso-directory-count { margin: 0; color: #786f73; font-size: .8rem; }
    .oso-directory-empty { margin: 0; padding: 1rem; border: 1px dashed #d8c8cd; border-radius: 12px; color: #786f73; font-size: .85rem; line-height: 1.5; }
    .oso-activity-list { display: grid; gap: .85rem; margin: 0; padding: 0; list-style: none; }
    .oso-activity-card { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1rem; align-items: center; padding: 1rem; border: 1px solid #eadde0; border-radius: 12px; background: #fff; }
    .oso-activity-card h4 { margin: 0 0 .4rem; color: #1a1618; font-size: .95rem; overflow-wrap: anywhere; }
    .oso-activity-meta { margin: 0; color: #786f73; font-size: .78rem; line-height: 1.6; overflow-wrap: anywhere; }
    .oso-activity-amounts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .7rem; margin: .85rem 0 0; }
    .oso-activity-amounts div { min-width: 0; }
    .oso-activity-amounts dt { color: #786f73; font-size: .72rem; }
    .oso-activity-amounts dd { margin: .25rem 0 0; color: #1a1618; font-size: .9rem; font-weight: 800; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .oso-activity-action { display: grid; gap: .6rem; justify-items: start; }
    .oso-activity-action small { color: #786f73; font-size: .76rem; }
    .oso-cash-disclosure { padding-top: .8rem; border-top: 1px solid #eadde0; min-width: 0; }
    .oso-cash-disclosure summary { cursor: pointer; font-size: .9rem; font-weight: 700; color: #1a1618; }
    .oso-cash-disclosure[open] summary { margin-bottom: 1rem; }
    .oso-cash-table .is-inflow { color: #157347; }
    .oso-cash-table .is-outflow { color: #8b1828; }
    .oso-cash-transactions { display: grid; gap: .8rem; min-width: 0; }
    .oso-cash-transactions h3 { margin: 0; font-size: .95rem; }
    .oso-cash-pages { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; font-size: .76rem; color: #786f73; }
    .oso-cash-pages nav { display: flex; gap: .5rem; }
    body.oso-budget-details-open { overflow: hidden; }
    .oso-budget-details-dialog [hidden] { display: none !important; }
    .oso-budget-details-dialog { box-sizing: border-box; position: fixed; inset: 0; margin: auto; width: min(920px, calc(100vw - 2rem)); max-width: none; max-height: 90dvh; padding: 0; border: 1px solid #eadde0; border-radius: 22px; color: #1a1618; background: #fff; box-shadow: 0 24px 70px rgba(45, 12, 20, .24); animation: none; transform: none; }
    .oso-budget-details-dialog::backdrop { background: rgba(36, 24, 28, .5); backdrop-filter: blur(3px); }
    .oso-budget-details-shell { display: flex; flex-direction: column; max-height: calc(90dvh - 2px); min-width: 0; }
    .oso-budget-details-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.5rem; border-bottom: 1px solid #f0e6e8; flex: 0 0 auto; }
    .oso-budget-details-head > div { min-width: 0; }
    .oso-budget-details-kicker { margin: 0 0 .55rem; color: #9d4456; font-size: .65rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
    .oso-budget-details-head h2 { margin: 0; font-size: 1.2rem; font-weight: 800; line-height: 1.4; overflow-wrap: anywhere; }
    .oso-budget-details-college { margin: .45rem 0 0; color: #786f73; font-size: .8rem; overflow-wrap: anywhere; }
    .oso-budget-details-dismiss { flex: 0 0 auto; display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; color: #786f73; background: #faf6f7; font-size: 1.3rem; cursor: pointer; }
    .oso-budget-details-body { min-height: 0; min-width: 0; overflow: auto; overscroll-behavior: contain; }
    .oso-budget-details-metrics { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: .75rem; margin: 0; padding: 1rem 1.5rem; background: #fbf8f9; }
    .oso-budget-details-metrics > div { min-width: 0; padding: .8rem; border: 1px solid #f0e6e8; border-radius: 12px; background: #fff; }
    .oso-budget-details-metrics dt, .oso-budget-details-amounts dt { color: #786f73; font-size: .72rem; }
    .oso-budget-details-metrics dt { display: flex; align-items: center; gap: .5rem; }
    .oso-budget-details-metrics dt > i { display: grid; place-items: center; flex: 0 0 26px; width: 26px; height: 26px; border-radius: 8px; color: #8b1828; background: #fdf0f2; font-size: .9rem; }
    .oso-budget-details-metrics .is-period { color: #6431a6; background: #f0e8ff; }
    .oso-budget-details-metrics .is-approved { color: #157347; background: #eaf7f0; }
    .oso-budget-details-metrics dd { margin: .4rem 0 0; font-size: .85rem; font-weight: 800; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .oso-budget-details-activities { display: grid; gap: 1rem; padding: 1.25rem 1.5rem 1.5rem; }
    .oso-budget-details-list-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
    .oso-budget-details-list-head h3 { margin: 0 0 .3rem; font-size: .95rem; }
    .oso-budget-details-count { flex: 0 0 auto; padding: .35rem .6rem; border-radius: 999px; color: #786f73; background: #faf6f7; font-size: .72rem; }
    .oso-budget-details-list { display: grid; gap: .75rem; margin: 0; padding: 0; list-style: none; }
    .oso-budget-details-activity { display: grid; grid-template-columns: 36px minmax(0, 1fr) auto 16px; gap: 1rem; align-items: center; padding: 1rem; border: 1px solid #eadde0; border-radius: 14px; color: #1a1618; background: #fff; text-decoration: none; }
    .oso-budget-details-activity:hover { border-color: #b86778; background: #fffafb; }
    .oso-budget-details-activity:focus-visible, .oso-budget-details-dismiss:focus-visible { outline: 3px solid #8b1828; outline-offset: 3px; }
    .oso-budget-details-number { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 9px; background: #8b1828; color: #fff; font-size: .75rem; font-weight: 800; }
    .oso-budget-details-activity strong { display: block; font-size: .85rem; line-height: 1.5; overflow-wrap: anywhere; }
    .oso-budget-details-activity small { display: block; margin-top: .2rem; color: #786f73; font-size: .72rem; line-height: 1.5; }
    .oso-budget-details-amounts { display: grid; grid-template-columns: repeat(2, minmax(85px, 1fr)); gap: 1.25rem; margin: 0; }
    .oso-budget-details-amounts dd { margin: .3rem 0 0; color: #8b1828; font-size: .8rem; font-weight: 800; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .oso-budget-details-arrow { color: #8b1828; }
    .oso-budget-details-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.5rem; border-top: 1px solid #f0e6e8; flex: 0 0 auto; }
    .oso-budget-details-foot .org-btn { padding: .65rem 1rem; border: 1px solid #eadde0; border-radius: 999px; color: #786f73; background: #fff; font-size: .75rem; font-weight: 700; cursor: pointer; }
    @media (max-width: 600px) {
        .oso-budget-details-dialog { width: calc(100vw - 1rem); max-height: calc(100dvh - 1rem); border-radius: 16px; }
        .oso-budget-details-shell { max-height: calc(100dvh - 1rem - 2px); }
        .oso-budget-details-head, .oso-budget-details-foot { padding: 1rem; }
        .oso-budget-details-head h2 { font-size: 1rem; }
        .oso-budget-details-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 1rem; }
        .oso-budget-details-metrics > div:first-child { grid-column: 1 / -1; }
        .oso-budget-details-activities { padding: 1rem; }
        .oso-budget-details-activity { grid-template-columns: 36px minmax(0, 1fr) 16px; gap: .65rem; padding: .85rem; }
        .oso-budget-details-amounts { grid-column: 2; grid-row: 2; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .oso-budget-details-arrow { grid-column: 3; grid-row: 1 / 3; }
        .oso-budget-details-foot .oso-cash-note { max-width: 55%; }
        .oso-budget-details-foot .org-btn { flex: 0 0 auto; }
    }
    @media (max-width: 1100px) { .oso-cash-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) {
        .oso-cash-grid { grid-template-columns: 1fr; }
        .oso-cash-metric strong { font-size: 1.4rem; }
        .oso-directory-table, .oso-directory-table tbody, .oso-directory-table tr, .oso-directory-table th, .oso-directory-table td { display: block; width: auto; min-width: 0; }
        .oso-directory-table th:first-child, .oso-directory-table th:last-child { width: auto; }
        .oso-directory-table thead { display: none; }
        .oso-directory-table tr + tr { border-top: 1px solid #d8c8cd; }
        .oso-directory-table th, .oso-directory-table td { border-bottom: 0; padding: .5rem .85rem; }
        .oso-directory-table th { padding-top: 1rem; font-size: .85rem; }
        .oso-directory-table td.num { display: flex; justify-content: space-between; gap: .75rem; white-space: normal; overflow-wrap: anywhere; }
        .oso-directory-table td.num::before { content: attr(data-label); color: #786f73; text-align: left; font-weight: 400; }
        .oso-directory-table td:last-child { padding-bottom: 1rem; }
        .oso-directory-table .org-btn { display: inline-flex; }
        .oso-activity-card { grid-template-columns: minmax(0, 1fr); }
        .oso-activity-amounts { grid-template-columns: minmax(0, 1fr); gap: .5rem; }
        .oso-activity-amounts div { display: flex; justify-content: space-between; gap: .75rem; }
        .oso-activity-amounts dd { margin: 0; }
        .oso-activity-action { width: 100%; }
    }
</style>

<section class="org-info-card oso-cash-overview" id="osoFinancialOverview" aria-label="Organization Financial Overview">
    <div class="oso-cash-heading">
        <div>
            <h2><i class="bi bi-bank" aria-hidden="true"></i> {{ $selectedOrganization !== '' ? $selectedOrganization.' · Budget Utilization' : 'Organization Financial Overview' }}</h2>
            <p>AY {{ $selectedYear }} · {{ $selectedSemester === 'Annual' ? 'Full academic year' : $selectedSemester }} · {{ $selectedOrganization ?: 'All organizations' }}</p>
        </div>
        @if ($selectedOrganization === '')
            <span class="org-info-pill-badge">Read-only · {{ $osoFinancialOverview['totals']['organization_count'] }} {{ $osoFinancialOverview['totals']['organization_count'] === 1 ? 'organization' : 'organizations' }}</span>
        @endif
    </div>

    <div class="oso-cash-grid">
        @foreach ([
            'beginning_balance' => ['Beginning cash balance', ''],
            'cash_inflow' => ['Cash inflow', 'is-inflow'],
            'cash_outflow' => ['Cash outflow', 'is-outflow'],
            'ending_balance' => ['Ending cash balance', 'is-ending'],
        ] as $key => [$label, $class])
            <article class="oso-cash-metric {{ $class }}">
                <span>{{ $label }}</span>
                <strong data-cash-metric="{{ $key }}">₱{{ number_format($osoFinancialOverview['totals'][$key], 2) }}</strong>
            </article>
        @endforeach
    </div>
    <div class="oso-cash-approved">
        <span>Approved activity budgets <small>— separate from cash spending</small></span>
        <strong data-cash-metric="approved_budget">₱{{ number_format($osoFinancialOverview['totals']['approved_budget'], 2) }}</strong>
    </div>
    <p class="oso-cash-note">Beginning + inflow − outflow = ending cash. Shared with SO Financial Report; approved budgets are not cash spending. Searches do not change these totals.</p>

    @if ($selectedOrganization === '')
        <section class="oso-budget-directory" id="osoOrganizationDirectory" data-budget-directory="organizations" aria-labelledby="osoOrganizationDirectoryHeading">
            <div class="oso-directory-head">
                <div>
                    <h3 id="osoOrganizationDirectoryHeading">Organizations</h3>
                    <p class="oso-cash-note">Review recorded cash balances and approved activity budgets.</p>
                </div>
                <p class="oso-directory-count" data-directory-count role="status" aria-live="polite" aria-atomic="true">{{ count($osoFinancialOverview['rows']) }} of {{ count($osoFinancialOverview['rows']) }} organizations</p>
            </div>
            <form class="oso-directory-search" data-directory-form role="search">
                <label for="osoOrganizationSearch">Search organizations or colleges
                    <input id="osoOrganizationSearch" type="search" data-directory-query placeholder="Organization or college name" aria-controls="osoOrganizationRows" autocomplete="off">
                </label>
            </form>
            <div class="oso-cash-table-wrap">
                <table class="oso-cash-table oso-directory-table">
                    <thead>
                        <tr>
                            <th scope="col">Organization</th>
                            <th scope="col" class="num">Beginning cash</th>
                            <th scope="col" class="num">Inflow</th>
                            <th scope="col" class="num">Outflow</th>
                            <th scope="col" class="num">Ending cash</th>
                            <th scope="col" class="num">Approved budgets</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody id="osoOrganizationRows">
                        @forelse ($osoFinancialOverview['rows'] as $row)
                            <tr data-cash-organization="{{ $row['organization'] }}" data-directory-row data-search-value="{{ $row['organization'].' '.$row['college'] }}">
                                <th scope="row" class="oso-directory-name">
                                    {{ $row['organization'] }}
                                    <small>{{ $row['college'] ?: 'College not recorded' }}</small>
                                    @unless ($row['has_account'])<small>No saved cash account for this year</small>@endunless
                                </th>
                                @foreach (['beginning_balance' => 'Beginning cash', 'cash_inflow' => 'Inflow', 'cash_outflow' => 'Outflow', 'ending_balance' => 'Ending cash', 'approved_budget' => 'Approved budgets'] as $key => $label)
                                    <td class="num" data-label="{{ $label }}">₱{{ number_format($row[$key], 2) }}</td>
                                @endforeach
                                <td><a class="org-btn org-btn-outline org-btn-sm" href="{{ route('office.budget', [...$osoDirectoryFilters, 'organization' => $row['organization']]) }}" data-budget-organization-details="osoBudgetOrganization{{ $loop->index }}" data-organization-name="{{ $row['organization'] }}" data-organization-college="{{ $row['college'] ?: 'College not recorded' }}" aria-haspopup="dialog" aria-controls="osoBudgetDetailsDialog" aria-label="View Details for {{ $row['organization'] }}">View Details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No organizations match the selected reporting-period and department filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="oso-directory-empty" data-directory-no-match hidden>No organizations or colleges match your search. Clear the search to view all organizations in this reporting scope.</p>
            <div class="org-report-pagination oso-directory-pages" data-directory-pagination aria-label="Organization pagination">
                <span data-directory-page-info></span>
                <nav class="org-report-pagination-nav" data-directory-pages aria-label="Organization pages"></nav>
            </div>
        </section>
    @else
        <section class="oso-budget-directory" id="osoActivityDirectory" data-budget-directory="activities" aria-labelledby="osoActivityDirectoryHeading">
            <div>
                <h3 id="osoActivityDirectoryHeading">Approved activities · {{ $selectedOrganization }}</h3>
                <p class="oso-cash-note">Select an activity to view its encoded expenses and receipts for this reporting period.</p>
            </div>
            <form class="oso-directory-search" data-directory-form role="search">
                <label for="osoActivitySearch">Search approved activities
                    <input id="osoActivitySearch" type="search" data-directory-query placeholder="Activity name, venue or scope" aria-controls="osoActivityRows" autocomplete="off">
                </label>
            </form>
            <p class="oso-directory-count" data-directory-count role="status" aria-live="polite" aria-atomic="true">{{ $osoDirectoryActivities->count() }} of {{ $osoDirectoryActivities->count() }} approved activities</p>
            <ul class="oso-activity-list" id="osoActivityRows">
                @foreach ($osoDirectoryActivities as $entry)
                    <li class="oso-activity-card" data-directory-row data-activity-id="{{ $entry['activityId'] }}" data-search-value="{{ $entry['actName'].' '.$entry['actVenue'].' '.$entry['scope'].' '.$entry['actDate'] }}">
                        <div>
                            <h4>{{ $entry['actName'] }}</h4>
                            <p class="oso-activity-meta">{{ $entry['actDate'] }} · {{ $entry['scope'] }} · {{ $entry['semester'] }}</p>
                            <p class="oso-activity-meta">Venue: {{ $entry['actVenue'] }}</p>
                            <dl class="oso-activity-amounts">
                                <div><dt>Approved budget</dt><dd>₱{{ number_format($entry['approvedBudget'], 2) }}</dd></div>
                                <div><dt>Actual spending</dt><dd>₱{{ number_format($entry['actualExpenses'], 2) }}</dd></div>
                                <div><dt>Remaining budget</dt><dd>₱{{ number_format($entry['remainingBal'], 2) }}</dd></div>
                            </dl>
                        </div>
                        <div class="oso-activity-action">
                            <small>{{ count($entry['expenses'] ?? []) }} encoded {{ count($entry['expenses'] ?? []) === 1 ? 'expense' : 'expenses' }}</small>
                            <a class="org-btn org-btn-outline org-btn-sm" href="{{ route('office.budget', [...$osoDirectoryFilters, 'organization' => $selectedOrganization, 'activity_id' => $entry['activityId']]) }}" aria-label="View Expenses for {{ $entry['actName'] }}">View Expenses</a>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if ($osoDirectoryActivities->isEmpty())
                <p class="oso-directory-empty">No final-approved activities for {{ $selectedOrganization }} in AY {{ $selectedYear }} · {{ $selectedSemester === 'Annual' ? 'Full academic year' : $selectedSemester }}. No activity expenses are shown.</p>
            @endif
            <p class="oso-directory-empty" data-directory-no-match hidden>No approved activities match your search. Clear the search to view all approved activities in this reporting scope.</p>
        </section>

        <details class="oso-cash-disclosure" id="osoCashTransactions" @if (request()->has('cash_page')) open @endif>
            <summary>Cash transactions · {{ $selectedOrganization }} (optional)</summary>
            <div class="oso-cash-transactions">
            <div class="oso-cash-table-wrap" tabindex="0" aria-label="Organization cash transactions; scroll for additional columns">
                <table class="oso-cash-table">
                    <thead><tr><th scope="col">Date</th><th scope="col">Type</th><th scope="col">Activity / Fund</th><th scope="col">Description / Party</th><th scope="col">Reference</th><th scope="col" class="num">Amount</th></tr></thead>
                    <tbody>
                        @forelse ($financialTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction['date'] ? \Carbon\Carbon::parse($transaction['date'])->format('M j, Y') : 'Date not recorded' }}</td>
                                <td>{{ $transaction['direction'] === 'inflow' ? 'Cash inflow' : 'Cash outflow' }}</td>
                                <td>{{ $transaction['event'] }}<small>{{ $transaction['account'] }}</small></td>
                                <td>{{ $transaction['description'] }}<small>{{ $transaction['payee'] }}</small></td>
                                <td>{{ $transaction['reference'] ?: $transaction['ref'] }}</td>
                                <td class="num {{ $transaction['direction'] === 'inflow' ? 'is-inflow' : 'is-outflow' }}">₱{{ number_format($transaction['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No cash inflows or outflows recorded for this organization in the selected period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($financialTransactions->total() > 0)
                <div class="oso-cash-pages">
                    <span>Showing {{ $financialTransactions->firstItem() }}–{{ $financialTransactions->lastItem() }} of {{ $financialTransactions->total() }} transactions</span>
                    <nav aria-label="Cash transaction pages">
                        @if ($financialTransactions->previousPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $financialTransactions->previousPageUrl() }}#osoCashTransactions">Previous</a>@endif
                        @if ($financialTransactions->nextPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $financialTransactions->nextPageUrl() }}#osoCashTransactions">Next</a>@endif
                    </nav>
                </div>
            @endif
                <p class="oso-cash-note">Cash history is separate from activity expense itemization. Use View Expenses above to open an activity's receipts. OSO cannot edit organization cash here.</p>
            </div>
        </details>
    @endif
</section>

@if ($selectedOrganization === '')
    <dialog class="oso-budget-details-dialog" id="osoBudgetDetailsDialog" aria-labelledby="osoBudgetDetailsTitle">
        <div class="oso-budget-details-shell">
            <header class="oso-budget-details-head">
                <div>
                    <p class="oso-budget-details-kicker" data-budget-details-kicker>Organization activities</p>
                    <h2 id="osoBudgetDetailsTitle" tabindex="-1"></h2>
                    <p class="oso-budget-details-college" data-budget-details-college></p>
                </div>
                <button class="oso-budget-details-dismiss" type="button" data-budget-details-close aria-label="Close organization details"><i class="bi bi-x" aria-hidden="true"></i></button>
            </header>
            <div class="oso-budget-details-body" data-budget-details-body></div>
            <footer class="oso-budget-details-foot">
                <p class="oso-cash-note" data-budget-details-footnote>Activities shown belong only to this organization.</p>
                <button class="org-btn org-btn-outline org-btn-sm" type="button" data-budget-details-close>Close details</button>
            </footer>
        </div>
    </dialog>
    @foreach ($osoFinancialOverview['rows'] as $row)
        @php
            $organizationActivities = $osoActivitiesByOrganization->get($row['organization'], collect());
        @endphp
        <template id="osoBudgetOrganization{{ $loop->index }}">
            <dl class="oso-budget-details-metrics">
                <div><dt><i class="bi bi-calendar3 is-period" aria-hidden="true"></i>Period</dt><dd>AY {{ str_replace('-', '–', $selectedYear) }} · {{ $selectedSemester === 'Annual' ? 'Full Fiscal Year' : $selectedSemester }}</dd></div>
                <div><dt><i class="bi bi-check-circle-fill is-approved" aria-hidden="true"></i>Total approved</dt><dd data-budget-details-approved>₱{{ number_format($organizationActivities->sum('approvedBudget'), 2) }}</dd></div>
                <div><dt><i class="bi bi-receipt" aria-hidden="true"></i>Total encoded expenses</dt><dd data-budget-details-spent>₱{{ number_format($organizationActivities->sum('encodedExpenses'), 2) }}</dd></div>
            </dl>
            <section class="oso-budget-details-activities" aria-label="Approved activities">
                <div class="oso-budget-details-list-head">
                    <div>
                        <h3>Approved activities</h3>
                        <p class="oso-cash-note">Select an activity to view its encoded expenses and receipts.</p>
                    </div>
                    <span class="oso-budget-details-count">{{ $organizationActivities->count() }} {{ $organizationActivities->count() === 1 ? 'activity' : 'activities' }}</span>
                </div>
                <ol class="oso-budget-details-list">
                    @foreach ($organizationActivities as $entry)
                        <li>
                            <a class="oso-budget-details-activity" href="{{ route('office.budget', [...$osoDirectoryFilters, 'organization' => $row['organization'], 'activity_id' => $entry['activityId']]) }}" data-budget-details-activity="{{ $entry['activityId'] }}" aria-label="View expenses and receipts for {{ $entry['actName'] }}">
                                <span class="oso-budget-details-number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <div>
                                    <strong>{{ $entry['actName'] }}</strong>
                                    <small>{{ $entry['actDate'] }} · {{ count($entry['expenses'] ?? []) }} encoded {{ count($entry['expenses'] ?? []) === 1 ? 'expense' : 'expenses' }}</small>
                                </div>
                                <dl class="oso-budget-details-amounts">
                                    <div><dt>Approved</dt><dd>₱{{ number_format($entry['approvedBudget'], 2) }}</dd></div>
                                    <div><dt>Spent</dt><dd>₱{{ number_format($entry['encodedExpenses'], 2) }}</dd></div>
                                </dl>
                                <i class="bi bi-chevron-right oso-budget-details-arrow" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ol>
                @if ($organizationActivities->isEmpty())
                    <p class="oso-directory-empty">No final-approved activities for this organization in the selected period.</p>
                @endif
            </section>
        </template>
        @foreach ($organizationActivities as $entry)
            <template id="osoBudgetActivity{{ $entry['activityId'] }}">
                @include('org.partials.oso-budget-activity-details', ['entry' => $entry])
                <script type="application/json" data-budget-detail-receipts>@json($entry['expenses'] ?? [])</script>
            </template>
        @endforeach
    @endforeach
@endif

<script defer src="{{ asset('js/oso-budget-directory.js') }}?v={{ filemtime(public_path('js/oso-budget-directory.js')) }}"></script>
