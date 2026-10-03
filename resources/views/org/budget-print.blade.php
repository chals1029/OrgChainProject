@php
    $officeRole = $office->office_role ?? 'so';
    $officeLabel = $officeRole === 'oso'
        ? 'Office of Student Organizations (OSO)'
        : 'Student Organization (SO)';
    $entries = collect($liveBudgetEntries ?? []);
    $activityEntries = $entries
        ->except(['all'])
        ->filter(fn ($entry): bool => is_array($entry) && filled($entry['actName'] ?? null))
        ->values();
    $portfolio = $entries->get('all', []);
    $approvedTotal = (float) ($portfolio['approvedBudget'] ?? $activityEntries->sum('approvedBudget'));
    $actualTotal = (float) ($portfolio['actualExpenses'] ?? $activityEntries->sum('actualExpenses'));
    $remainingTotal = max(0, $approvedTotal - $actualTotal);
    $utilization = $approvedTotal > 0 ? ($actualTotal / $approvedTotal) * 100 : 0;
    $allExpenses = $activityEntries->flatMap(function (array $entry): array {
        return collect($entry['expenses'] ?? [])->map(function (array $expense) use ($entry): array {
            return array_merge($expense, [
                'activity' => $entry['actName'],
                'organization' => $entry['orgName'] ?? '—',
            ]);
        })->all();
    })->values();
    $organizationLabel = $selectedOrganization ?: 'All recognized organizations';
    $departmentLabel = request('department') ?: 'All colleges / units';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Budget Utilization Report — {{ $organizationLabel }}</title>
    <style>
        @page { size: A4 landscape; margin: 13mm 12mm; }
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #211b1d; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.4; }
        .print-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 16px; margin: 0 auto 20px; max-width: 1180px; border: 1px solid #eadcdf; border-radius: 10px; background: #fff8fa; }
        .print-toolbar span { color: #6d6266; font-size: 12px; }
        .print-toolbar button, .print-toolbar a { border: 0; border-radius: 999px; padding: 8px 14px; color: #fff; background: #8b1828; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .print-toolbar a { color: #8b1828; background: #fff; border: 1px solid #e7cdd2; }
        .print-toolbar-actions { display: flex; gap: 8px; }
        .report { max-width: 1180px; margin: 0 auto; }
        .report-header { border-bottom: 3px solid #8b1828; padding-bottom: 14px; }
        .institution { color: #8b1828; font-size: 16px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .unit { margin-top: 2px; color: #63585c; font-size: 11px; font-weight: 700; }
        .report-kicker { margin: 16px 0 3px; color: #8b1828; font-size: 9px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; font-family: Georgia, 'Times New Roman', serif; color: #211b1d; font-size: 25px; }
        .report-subtitle { margin: 5px 0 0; color: #63585c; font-size: 11px; }
        .meta-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 16px 0; }
        .meta-item { padding: 9px 10px; border: 1px solid #e7d9dc; border-radius: 7px; background: #fffafb; }
        .meta-item span { display: block; margin-bottom: 3px; color: #766b6f; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .meta-item strong { display: block; color: #2d2528; font-size: 11px; overflow-wrap: anywhere; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 0 0 20px; }
        .summary-card { padding: 11px 12px; border: 1px solid #d9c2c7; border-top: 3px solid #8b1828; border-radius: 7px; }
        .summary-card span { display: block; color: #766b6f; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .summary-card strong { display: block; margin-top: 3px; color: #8b1828; font-size: 17px; }
        h2 { margin: 20px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #d9c2c7; color: #8b1828; font-family: Georgia, 'Times New Roman', serif; font-size: 16px; }
        h3 { margin: 0; color: #2d2528; font-size: 13px; }
        .section-note { margin: -3px 0 8px; color: #766b6f; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 0 0 15px; table-layout: fixed; }
        th, td { padding: 6px 7px; border: 1px solid #dfd1d4; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #f8eff1; color: #5f1824; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; }
        td { font-size: 10px; }
        .num { text-align: right; white-space: nowrap; }
        .status { color: #19713b; font-weight: 700; }
        .activity-block { margin: 16px 0; padding: 10px; border: 1px solid #e3d6d9; border-radius: 8px; break-inside: avoid; }
        .activity-heading { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
        .activity-heading small { color: #766b6f; font-size: 10px; text-align: right; }
        .activity-meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 8px; }
        .activity-meta div { color: #5e5458; font-size: 10px; }
        .activity-meta strong { display: block; color: #2d2528; font-size: 9px; text-transform: uppercase; }
        .empty { padding: 13px; border: 1px dashed #cdb9be; color: #766b6f; text-align: center; }
        .hash { margin: 5px 0 0; color: #5f1824; font-family: 'Courier New', monospace; font-size: 9px; overflow-wrap: anywhere; }
        .signature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 35px; margin: 46px 0 12px; break-inside: avoid; }
        .signature { padding-top: 7px; border-top: 1px solid #332b2e; text-align: center; font-size: 10px; }
        .footer { margin-top: 18px; padding-top: 7px; border-top: 1px solid #dfd1d4; color: #766b6f; font-size: 9px; }
        @media print {
            body { font-size: 10px; }
            .no-print { display: none !important; }
            .report { max-width: none; }
            .report-header { break-after: avoid; }
            .activity-block, tr { break-inside: avoid; }
            a { color: inherit; text-decoration: none; }
        }
        @media (max-width: 720px) {
            .print-toolbar, .activity-heading { align-items: flex-start; flex-direction: column; }
            .print-toolbar-actions { width: 100%; }
            .print-toolbar button, .print-toolbar a { flex: 1; text-align: center; }
            .meta-grid, .summary-grid, .activity-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .report { padding: 0 12px; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <span>This is the official server-rendered report. Only this report document will be printed.</span>
        <div class="print-toolbar-actions">
            <button type="button" onclick="window.print()">Print / Save as PDF</button>
            <a href="{{ route('office.budget', request()->query()) }}">Back</a>
        </div>
    </div>

    <main class="report">
        <header class="report-header">
            <div class="institution">Batangas State University</div>
            <div class="unit">The National Engineering University · OrgChain Office Reporting</div>
            <div class="report-kicker">{{ $officeLabel }}</div>
            <h1>Budget Utilization Report</h1>
            <p class="report-subtitle">Approved activity allocations, actual utilization, verified receipts, and ledger references.</p>
        </header>

        <section class="meta-grid" aria-label="Report details">
            <div class="meta-item"><span>Organization scope</span><strong>{{ $organizationLabel }}</strong></div>
            <div class="meta-item"><span>Department scope</span><strong>{{ $departmentLabel }}</strong></div>
            <div class="meta-item"><span>Prepared by</span><strong>{{ $office->name ?? $officeLabel }}</strong></div>
            <div class="meta-item"><span>Generated</span><strong>{{ $generatedAt }}</strong></div>
        </section>

        <section class="summary-grid" aria-label="Budget summary">
            <div class="summary-card"><span>Approved budget</span><strong>Php {{ number_format($approvedTotal, 2) }}</strong></div>
            <div class="summary-card"><span>Actual expenses</span><strong>Php {{ number_format($actualTotal, 2) }}</strong></div>
            <div class="summary-card"><span>Remaining balance</span><strong>Php {{ number_format($remainingTotal, 2) }}</strong></div>
            <div class="summary-card"><span>Utilization rate</span><strong>{{ number_format($utilization, 1) }}%</strong></div>
        </section>

        <h2>Activity Utilization Register</h2>
        <p class="section-note">Only activities with final approval are included in this report.</p>
        @if ($activityEntries->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th style="width:22%;">Activity</th>
                        <th style="width:14%;">Organization</th>
                        <th style="width:11%;">Scope</th>
                        <th style="width:12%;">Date / Venue</th>
                        <th style="width:12%;" class="num">Approved</th>
                        <th style="width:12%;" class="num">Actual</th>
                        <th style="width:10%;" class="num">Balance</th>
                        <th style="width:7%;" class="num">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activityEntries as $entry)
                        <tr>
                            <td><strong>{{ $entry['actName'] }}</strong><br><span>{{ $entry['actType'] ?? 'Activity' }}</span></td>
                            <td>{{ $entry['orgName'] ?? '—' }}</td>
                            <td>{{ $entry['scope'] ?? '—' }}</td>
                            <td>{{ $entry['actDate'] ?? '—' }}<br>{{ $entry['actVenue'] ?? '—' }}</td>
                            <td class="num">Php {{ number_format((float) ($entry['approvedBudget'] ?? 0), 2) }}</td>
                            <td class="num">Php {{ number_format((float) ($entry['actualExpenses'] ?? 0), 2) }}</td>
                            <td class="num">Php {{ number_format((float) ($entry['remainingBal'] ?? 0), 2) }}</td>
                            <td class="num">{{ number_format((float) ($entry['utilRate'] ?? 0), 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">No final-approved activity records are available for the selected scope.</div>
        @endif

        <h2>Verified Expense Register</h2>
        @if ($allExpenses->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th style="width:19%;">Activity</th>
                        <th style="width:15%;">Organization</th>
                        <th style="width:16%;">Category / Description</th>
                        <th style="width:12%;">Date</th>
                        <th style="width:8%;" class="num">Qty</th>
                        <th style="width:12%;" class="num">Amount</th>
                        <th style="width:10%;">Status</th>
                        <th style="width:8%;">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allExpenses as $expense)
                        <tr>
                            <td>{{ $expense['activity'] }}</td>
                            <td>{{ $expense['organization'] }}</td>
                            <td>{{ $expense['cat'] ?? 'General' }}<br>{{ $expense['desc'] ?? '—' }}</td>
                            <td>{{ $expense['date'] ?? '—' }}</td>
                            <td class="num">{{ $expense['qty'] ?? '—' }}</td>
                            <td class="num">Php {{ number_format((float) ($expense['amount'] ?? 0), 2) }}</td>
                            <td class="status">{{ $expense['status'] ?? 'Verified' }}</td>
                            <td>{{ $expense['receiptFile'] ?? 'Receipt on file' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">No verified expense receipts are recorded for the selected final-approved activities.</div>
        @endif

        <h2>Audit References</h2>
        @forelse ($activityEntries as $entry)
            <div class="activity-block">
                <div class="activity-heading">
                    <h3>{{ $entry['actName'] }}</h3>
                    <small>{{ $entry['verifier'] ?? 'OSO Audit Desk' }} · {{ $entry['verifiedDate'] ?? 'Pending' }}</small>
                </div>
                <div>{{ $entry['remarks'] ?? 'Live figures from approved budget and verified receipts.' }}</div>
                <div class="hash">Ledger hash: {{ $entry['hash'] ?? 'Pending seal' }}</div>
            </div>
        @empty
            <div class="empty">No activity audit references are available.</div>
        @endforelse

        <div class="signature-grid">
            <div class="signature">Prepared by<br><strong>{{ $officeRole === 'oso' ? 'OSO Review Desk' : 'SO Treasurer / Authorized Officer' }}</strong></div>
            <div class="signature">Reviewed by<br><strong>{{ $officeRole === 'oso' ? 'OSO Audit Desk' : 'Office of Student Organizations (OSO)' }}</strong></div>
            <div class="signature">Report status<br><strong>Generated from server records</strong></div>
        </div>

        <footer class="footer">OrgChain official budget utilization report · Generated {{ $generatedAt }} · This document reflects the selected filters and server-side records at generation time.</footer>
    </main>
</body>
</html>
