@extends('system-admin.layout')

@section('title', 'System Overview')

@section('content')
    <section class="sys-page-heading">
        <div>
            <span class="sys-eyebrow">Maintenance workspace</span>
            <h1>System overview</h1>
            <p>Check platform health, manage office access, and review maintenance activity without entering an operational review desk.</p>
        </div>
        <div class="sys-heading-actions">
            <span class="sys-live-chip"><span></span> Live application</span>
            <form method="post" action="{{ route('system-admin.cache.clear') }}" onsubmit="return confirm('Clear application caches now? The next request may take slightly longer while caches rebuild.');">
                @csrf
                <button type="submit" class="sys-secondary-btn"><i class="bi bi-arrow-repeat"></i> Clear caches</button>
            </form>
        </div>
    </section>

    <section class="sys-stat-grid" aria-label="System totals">
        <article class="sys-stat-card sys-stat-card-maroon">
            <div class="sys-stat-icon"><i class="bi bi-person-check-fill"></i></div>
            <div><span>Active office accounts</span><strong>{{ number_format($stats['active_office_users']) }}</strong><small>Across SO, OSO, SDO, and OVCAA</small></div>
        </article>
        <article class="sys-stat-card sys-stat-card-blue">
            <div class="sys-stat-icon"><i class="bi bi-diagram-3-fill"></i></div>
            <div><span>Pending workflows</span><strong>{{ number_format($stats['pending_workflows']) }}</strong><small>Activities awaiting an office action</small></div>
        </article>
        <article class="sys-stat-card sys-stat-card-green">
            <div class="sys-stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div><span>Receipt records</span><strong>{{ number_format($stats['receipt_records']) }}</strong><small>Stored financial review entries</small></div>
        </article>
        <article class="sys-stat-card sys-stat-card-gold">
            <div class="sys-stat-icon"><i class="bi bi-award-fill"></i></div>
            <div><span>Top 10 Outstanding Students</span><strong>{{ number_format($stats['tosa_applicants']) }}</strong><small>Candidate dossiers in the system</small></div>
        </article>
    </section>

    <section class="sys-panel sys-health-panel" aria-labelledby="healthTitle">
        <div class="sys-panel-heading">
            <div>
                <span class="sys-panel-kicker">Read-only diagnostics</span>
                <h2 id="healthTitle">Platform health</h2>
            </div>
            <span class="sys-panel-caption"><i class="bi bi-clock-history"></i> Checked on page load</span>
        </div>
        <div class="sys-health-grid">
            @foreach ($healthChecks as $check)
                <article class="sys-health-item is-{{ $check['status'] }}">
                    <div class="sys-health-icon"><i class="bi {{ $check['icon'] }}"></i></div>
                    <div class="sys-health-copy"><strong>{{ $check['label'] }}</strong><span>{{ $check['detail'] }}</span></div>
                    <span class="sys-status-pill is-{{ $check['status'] }}">{{ ucfirst($check['status']) }}</span>
                </article>
            @endforeach
        </div>
    </section>

    <section class="sys-panel" id="officeAccounts" aria-labelledby="officeAccountsTitle">
        <div class="sys-panel-heading">
            <div>
                <span class="sys-panel-kicker">Access governance</span>
                <h2 id="officeAccountsTitle">Office accounts</h2>
            </div>
            <span class="sys-panel-caption">{{ $stats['total_office_users'] }} total records</span>
        </div>
        <div class="sys-table-wrap">
            <table class="sys-table">
                <thead><tr><th>Officer</th><th>Role</th><th>Account</th><th>Last updated</th><th class="sys-table-action">Action</th></tr></thead>
                <tbody>
                    @forelse ($officeUsers as $user)
                        <tr>
                            <td><div class="sys-person-cell"><span class="sys-person-avatar">{{ $user->initials() }}</span><span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span></div></td>
                            <td><span class="sys-role-chip is-{{ $user->office_role }}">{{ $user->roleLabel() }}</span></td>
                            <td><span class="sys-account-state {{ $user->is_active ? 'is-active' : 'is-disabled' }}"><i class="bi {{ $user->is_active ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' }}"></i> {{ $user->is_active ? 'Active' : 'Disabled' }}</span></td>
                            <td class="sys-muted">{{ $user->updated_at?->diffForHumans() ?? 'Not recorded' }}</td>
                            <td class="sys-table-action">
                                <form method="post" action="{{ route('system-admin.office-users.status', $user) }}" onsubmit="return confirm('{{ $user->is_active ? 'Disable' : 'Activate' }} this office account?');">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                    <button type="submit" class="sys-table-btn {{ $user->is_active ? 'is-danger' : 'is-success' }}">{{ $user->is_active ? 'Disable' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="sys-empty-cell">No office accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="sys-panel" id="auditTrail" aria-labelledby="auditTrailTitle">
        <div class="sys-panel-heading">
            <div>
                <span class="sys-panel-kicker">Accountability</span>
                <h2 id="auditTrailTitle">Recent maintenance activity</h2>
            </div>
            <span class="sys-panel-caption">Last 12 events</span>
        </div>
        <div class="sys-audit-list">
            @forelse ($auditLogs as $log)
                <div class="sys-audit-row">
                    <span class="sys-audit-icon"><i class="bi {{ str_contains($log->event, 'login') ? 'bi-person-check' : (str_contains($log->event, 'cache') ? 'bi-arrow-repeat' : 'bi-shield-check') }}"></i></span>
                    <div class="sys-audit-copy"><strong>{{ ucwords(str_replace('_', ' ', $log->event)) }}</strong><span>{{ $log->target ?: 'Application' }} @if($log->admin) · {{ $log->admin->name }} @endif</span></div>
                    <time datetime="{{ optional($log->created_at)->toIso8601String() }}">{{ optional($log->created_at)->diffForHumans() }}</time>
                </div>
            @empty
                <div class="sys-empty-state"><i class="bi bi-journal"></i><strong>No maintenance events yet</strong><span>Successful admin sign-ins and maintenance actions will appear here.</span></div>
            @endforelse
        </div>
    </section>
@endsection
