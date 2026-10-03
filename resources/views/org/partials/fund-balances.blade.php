@php
    $isSoDesk = ($office->office_role ?? '') === 'so';
@endphp
<section class="org-detail-card" style="background:#fff;padding:1rem;border-radius:16px;min-width:0;" aria-label="Organization fund balances">
    <h3 style="margin:0 0 .5rem;">{{ $isSoDesk ? 'Recorded fund balance' : 'Organization funds' }} · {{ $selectedYear }}</h3>
    <p style="font-size:.8rem;color:#65585c;">{{ $isSoDesk ? 'This is the organization ledger, not a live bank or GCash balance. Approved allocations reserve funds; recorded expenses reduce the balance once.' : 'Approval reserves an allocation. Recorded expenses reduce cash and the activity balance once. These are annual balances; the expense register uses the selected reporting period.' }}</p>
    <style>
        @media screen {.org-fund-balance-scroll {max-height:360px;overflow:auto;}}
        @media print {.org-fund-balance-scroll {overflow:visible;max-height:none;}}
        .so-fund-balance-list {display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;}
        .so-fund-balance-card {border:1px solid #f0e0e3;border-radius:14px;padding:.85rem;background:#fffafb;}
        .so-fund-balance-card > span {display:block;font-size:.68rem;font-weight:800;letter-spacing:.03em;text-transform:uppercase;color:#8b1828;}
        .so-fund-balance-card > strong {display:block;margin:.2rem 0 .75rem;font-size:.9rem;color:#2b2427;overflow-wrap:anywhere;}
        .so-fund-balance-metrics {display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem;}
        .so-fund-balance-metrics div {display:grid;gap:.1rem;min-width:0;}
        .so-fund-balance-metrics small {font-size:.68rem;color:#786f73;}
        .so-fund-balance-metrics b {font-size:.82rem;color:#2b2427;white-space:nowrap;}
    </style>
    @if ($isSoDesk)
        <div class="so-fund-balance-list">
            @forelse ($accountBalances as $balance)
                <article class="so-fund-balance-card">
                    <span>Organization</span>
                    <strong>{{ $balance['organization'] }}</strong>
                    <div class="so-fund-balance-metrics">
                        <div><small>Total funds</small><b>Php {{ number_format($balance['total'], 2) }}</b></div>
                        <div><small>Allocated</small><b>Php {{ number_format($balance['allocated'], 2) }}</b></div>
                        <div><small>Spent</small><b>Php {{ number_format($balance['spent'], 2) }}</b></div>
                        <div><small>Reserved</small><b>Php {{ number_format($balance['reserved'], 2) }}</b></div>
                        <div><small>Recorded ledger cash</small><b>Php {{ number_format($balance['cash'], 2) }}</b></div>
                        <div><small>Available to allocate</small><b>Php {{ number_format($balance['available'], 2) }}</b></div>
                    </div>
                </article>
            @empty
                <p style="margin:0;padding:.75rem;border:1px dashed #e8b4bc;border-radius:12px;">No fund account is configured for this organization and academic year.</p>
            @endforelse
        </div>
    @else
        <div class="org-fund-balance-scroll" style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:650px;font-size:.8rem;">
                <thead><tr style="text-align:left;"><th>Organization</th><th>Total funds</th><th>Allocated</th><th>Spent</th><th>Reserved balance</th><th>Recorded ledger cash</th><th>Available to allocate</th></tr></thead>
                <tbody>
                @forelse ($accountBalances as $balance)
                    <tr>
                        <td style="padding:.7rem .3rem;">{{ $balance['organization'] }}</td>
                        @foreach (['total', 'allocated', 'spent', 'reserved', 'cash', 'available'] as $field)
                            <td style="padding:.7rem .3rem;white-space:nowrap;">Php {{ number_format($balance[$field], 2) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:.75rem;">No fund account is configured for this organization and academic year.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</section>
