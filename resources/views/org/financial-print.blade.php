<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Financial Report — {{ $selectedSemester }} {{ $selectedYear }}</title>
    <style>
        @page { size:A4 landscape; margin:14mm; }
        body { font:12px/1.45 Arial,sans-serif;color:#241d20;margin:24px; }
        h1,h2 { color:#7a1222; } h1 { font-family:Georgia,serif; }
        table { width:100%;border-collapse:collapse;table-layout:fixed;margin:16px 0; }
        th,td { border:1px solid #d8c2c7;padding:7px;text-align:left;overflow-wrap:anywhere;vertical-align:top; }
        th { background:#faf4f5; } thead { display:table-header-group; }
        tr { break-inside:avoid; } header { border-bottom:2px solid #7a1222; }
        .num { text-align:right; } .sign { display:flex;gap:50px;margin-top:50px; }
        .sign div { flex:1;border-top:1px solid #555;padding-top:8px; }
        @media print { .no-print { display:none; } body { margin:0; } }
    </style>
</head>
<body>
    <p class="no-print"><button type="button" onclick="window.print()">Print / Save as PDF</button> <a href="{{ route('office.financial', request()->query()) }}">Back</a></p>
    <header>
        <strong>BATANGAS STATE UNIVERSITY · The National Engineering University</strong>
        <h1>Semester Financial Report</h1>
        <p>{{ $selectedOrganization ?: 'All recognized organizations' }}<br>{{ $selectedSemester }} · Academic Year {{ $selectedYear }} · {{ $fromDate }} to {{ $toDate }}</p>
        <p>Generated {{ $generatedAt }} by {{ $office->name }}</p>
    </header>
    @if (($office->office_role ?? '') === 'so')
        @include('org.partials.fund-balances')
    @endif
    <h2>Receipt-supported expense register</h2>
    <p>Expenses in the selected semester: <strong>Php {{ number_format($periodExpenseTotal, 2) }}</strong>. The annual balances above include earlier recorded spending and allocations.</p>
    <table>
        <thead><tr><th style="width:10%;">Date / Receipt</th><th style="width:20%;">Activity / Organization</th><th style="width:23%;">Item / Supplier</th><th style="width:5%;">Qty</th><th style="width:9%;">Unit cost</th><th style="width:10%;">Amount</th><th style="width:23%;">Original receipt / Seal</th></tr></thead>
        <tbody>
        @forelse($receiptRows as $receipt)
            <tr>
                <td>{{ $receipt->expense_date->format('M j, Y') }}<br>{{ $receipt->receipt_reference }}</td>
                <td>{{ $receipt->activity_title }}<br>{{ $receipt->organization_name }}</td>
                <td>{{ $receipt->item_name }}<br>{{ $receipt->supplier }}</td>
                <td class="num">{{ $receipt->quantity }}</td>
                <td class="num">{{ number_format((float) $receipt->unit_cost, 2) }}</td>
                <td class="num">{{ number_format($receipt->quantity * (float) $receipt->unit_cost, 2) }}</td>
                <td>{{ $receipt->receipt_name }}<br>{{ $receipt->verification_status }}<br>{{ $receipt->chain_hash ?: 'Pending blockchain confirmation' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No recorded receipts match this reporting period.</td></tr>
        @endforelse
        </tbody>
    </table>
    <p>FR review: {{ $reportBundle['reports']['fr']['state_label'] ?? 'Not submitted' }}. Receipt confirmation and OSO acceptance of the Financial Report are separate records.</p>
    <div class="sign"><div>Prepared by: SO authorized officer</div><div>Reviewed by: Office of Student Organizations</div></div>
</body>
</html>
