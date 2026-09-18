<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Financial Report — {{ $selectedSemester }} AY {{ $selectedYear }}</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; color: #1a1618; margin: 32px; }
        .letterhead { text-align: center; border-bottom: 3px solid #7a1222; padding-bottom: 12px; margin-bottom: 24px; }
        .letterhead h1 { margin: 0; color: #7a1222; font-size: 22px; }
        .letterhead p { margin: 4px 0; font-size: 13px; }
        h2 { color: #7a1222; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 13px; }
        th, td { border: 1px solid #d8c2c7; padding: 8px; text-align: left; }
        th { background: #faf4f5; }
        .kpi { display: flex; gap: 16px; margin: 16px 0; }
        .kpi div { flex: 1; border: 1px solid #d8c2c7; padding: 12px; border-radius: 8px; }
        .sign { display: flex; justify-content: space-between; margin-top: 48px; }
        .sign div { width: 30%; text-align: center; border-top: 1px solid #333; padding-top: 8px; font-size: 12px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <p class="no-print"><button onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('office.financial') }}">Back</a></p>

    <div class="letterhead">
        <h1>Batangas State University</h1>
        <p>The National Engineering University · OrgChain Student Organization Desk</p>
        <p><strong>Official Financial Report</strong></p>
        <p>{{ $selectedSemester }} · Academic Year {{ $selectedYear }}</p>
        <p>Generated {{ $generatedAt }}</p>
    </div>

    <div class="kpi">
        <div><strong>Current Cash</strong><br>Php {{ number_format($account['current_cash'], 2) }}</div>
        <div><strong>Total Cash Collection</strong><br>Php {{ number_format($account['total_cash_collection'], 2) }}</div>
        <div><strong>Total Card Disbursement</strong><br>Php {{ number_format($account['total_card_disbursement'], 2) }}</div>
    </div>

    @if (!empty($fundAccount))
        <h2>Fund Account</h2>
        <p>
            Organization: <strong>{{ $fundAccount->organization_name }}</strong><br>
            Beginning balance: Php {{ number_format($fundAccount->beginning_balance, 2) }}<br>
            Total funds: Php {{ number_format($fundAccount->total_funds, 2) }}<br>
            Total funds received: Php {{ number_format($fundAccount->total_funds_received, 2) }}
        </p>
    @endif

    @if (!empty($inflowOutflow))
        <h2>Inflow vs Outflow</h2>
        <table>
            <thead><tr><th>Type</th><th>Amount</th></tr></thead>
            <tbody>
                @php
                    $summaryLabels = $inflowOutflow['summary_labels'] ?? $inflowOutflow['labels'] ?? [];
                    $summaryValues = $inflowOutflow['summary_values'] ?? $inflowOutflow['values'] ?? [];
                @endphp
                @foreach ($summaryLabels as $i => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>Php {{ number_format($summaryValues[$i] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if (!empty($inflowOutflow['inflows']))
            <table>
                <thead><tr><th>Month</th><th>Inflow</th><th>Outflow</th></tr></thead>
                <tbody>
                    @foreach ($inflowOutflow['labels'] as $i => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>Php {{ number_format($inflowOutflow['inflows'][$i] ?? 0, 2) }}</td>
                            <td>Php {{ number_format($inflowOutflow['outflows'][$i] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <h2>Expense Lines</h2>
    <table>
        <thead>
            <tr>
                <th>Activity</th>
                <th>Item</th>
                <th>Date</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lines as $line)
                <tr>
                    <td>{{ $line['activity'] }}</td>
                    <td>{{ $line['item'] }}</td>
                    <td>{{ $line['date'] }}</td>
                    <td>{{ $line['qty'] }}</td>
                    <td>Php {{ number_format($line['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No expense lines recorded for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="sign">
        <div>Prepared by<br>SO Treasurer</div>
        <div>Reviewed by<br>OSO Officer</div>
        <div>Noted by<br>OVCAA</div>
    </div>
</body>
</html>
