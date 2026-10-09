@php
    $packet = $accomplishmentPacket ?? [];
    $reports = $packet['reports'] ?? [];
    $organization = (string) ($packet['organization'] ?? '');
    $semester = (string) ($packet['semester'] ?? '');
    $academicYear = (string) ($packet['academic_year'] ?? '');
    $signatories = $packet['signatories'] ?? [];
    $classifications = collect($reports)->pluck('classification')->filter()->unique()->values()->all();
    $classification = $packet['classification'] ?? implode(' / ', $classifications);
    $money = fn ($value) => 'PHP '.number_format((float) $value, 2);
    $sdgNames = [1 => 'No Poverty', 2 => 'Zero Hunger', 3 => 'Good Health and Well-being', 4 => 'Quality Education', 5 => 'Gender Equality', 6 => 'Clean Water and Sanitation', 7 => 'Affordable and Clean Energy', 8 => 'Decent Work and Economic Growth', 9 => 'Industry, Innovation and Infrastructure', 10 => 'Reduced Inequalities', 11 => 'Sustainable Cities and Communities', 12 => 'Responsible Consumption and Production', 13 => 'Climate Action', 14 => 'Life Below Water', 15 => 'Life on Land', 16 => 'Peace, Justice and Strong Institutions', 17 => 'Partnerships for the Goals'];
    $sdgLabel = function ($goal) use ($sdgNames) {
        $text = trim((string) $goal);
        if (preg_match('/^(?:SDG\s*)?(\d{1,2})$/i', $text, $match) && isset($sdgNames[(int) $match[1]])) {
            return 'SDG '.(int) $match[1].': '.$sdgNames[(int) $match[1]];
        }
        return $text;
    };
    $roles = ['secretary' => ['Prepared by:', 'Secretary'], 'auditor' => ['Audited by:', 'Auditor'], 'president' => ['Noted:', 'President'], 'adviser' => ['Noted:', 'Adviser'], 'coordinator' => ['Noted:', 'OSO Coordinator'], 'head' => ['Verified True and Correct:', 'Head, Student Organization']];
    $embedded = request()->boolean('embedded');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accomplishment & Financial Report — {{ $organization }} — {{ $semester }} AY {{ $academicYear }}</title>
    <link rel="stylesheet" href="{{ asset('css/org-accomplishment.css') }}?v={{ filemtime(public_path('css/org-accomplishment.css')) }}">
    <script src="{{ asset('js/org-accomplishment.js') }}?v={{ filemtime(public_path('js/org-accomplishment.js')) }}" defer></script>
</head>
<body class="ar-packet">
    @if (!$embedded)
        <header class="ar-print-toolbar"><span>Saved native accomplishment packet · {{ $semester }} · AY {{ $academicYear }}</span><nav aria-label="Report actions"><button type="button" data-ar-print-page>Print / Save as PDF</button>@if (!empty($exportUrl))<a href="{{ $exportUrl }}">Download Word</a>@endif<a href="{{ route('office.accomplishment', ['organization' => $organization, 'semester' => $semester, 'academic_year' => $academicYear]) }}">Back to desk</a></nav></header>
    @endif
    <main class="ar-packet-main">
        @if (empty($reports))
            <div class="ar-page-shell"><section class="ar-paper" data-ar-paper><h1>Accomplishment Report</h1><p class="ar-print-period">{{ $semester }} · Academic Year {{ $academicYear }}</p><p>{{ $organization }}</p><p>No native activity reports have been saved for this period. There is no accomplishment packet to print.</p></section></div>
        @else
            <div class="ar-page-shell"><section class="ar-paper" data-ar-paper aria-label="Report cover"><div class="ar-cover"><header class="ar-institution">Republic of the Philippines<strong>BATANGAS STATE UNIVERSITY</strong><em>The National Engineering University</em><div>Office of Student Organizations</div></header><div><div class="ar-cover-organization">{{ $organization }}</div><h1>ACCOMPLISHMENT &<br>FINANCIAL REPORT</h1><p class="ar-cover-period">{{ $semester }}<br>Academic Year {{ $academicYear }}</p>@if (!empty($classification))<p>{{ $classification }}</p>@endif</div><div class="ar-cover-note">Native semester accomplishment packet<br>@if (!empty($packet['generated_at']))Generated: {{ $packet['generated_at'] }}@endif<br>Signatory names, where provided, do not constitute signed approval.</div></div></section></div>

            <div class="ar-page-shell"><section class="ar-paper is-landscape" data-ar-paper aria-labelledby="arParticularsTitle">
                <h1 id="arParticularsTitle">STUDENT ORGANIZATION ACCOMPLISHMENT REPORT</h1><p class="ar-print-period">{{ $semester }}, Academic Year {{ $academicYear }}</p><p><strong>Name of Organization:</strong> {{ $organization }}<br><strong>Classification:</strong> {{ $classification }}</p><h2 style="text-align:center">PARTICULARS OF THE ACCOMPLISHMENTS</h2>
                <table class="ar-print-table ar-particulars"><colgroup><col style="width:12%"><col style="width:15%"><col style="width:11%"><col style="width:10%"><col style="width:7%"><col style="width:14%"><col style="width:9%"><col style="width:11%"><col style="width:11%"></colgroup><thead><tr><th>Activity</th><th>Brief Description</th><th>Sustainable Development Goals</th><th>Persons Involved/ Participants</th><th>No. of Participants</th><th>Date/Day/ Venue/ Time</th><th>Expenses</th><th>Problems Encountered</th><th>Recommendations</th></tr></thead><tbody>
                    @foreach ($reports as $report)<tr><td><strong>{{ $report['title'] }}</strong></td><td class="ar-print-text">{{ $report['brief_description'] }}</td><td>@foreach (($report['sdg_goals'] ?? []) as $goal)<div>{{ $sdgLabel($goal) }}</div>@endforeach</td><td class="ar-print-text">{{ $report['people_involved'] }}</td><td>Total: {{ (int) $report['participants'] }}<br>Male: {{ (int) $report['male_participants'] }}<br>Female: {{ (int) $report['female_participants'] }}</td><td>{{ $report['date_label'] ?? '' }}<br>{{ $report['time_label'] ?? '' }}<br>{{ $report['venue'] ?? '' }}</td><td class="ar-number">{{ $money(data_get($report, 'financial.total_expenses', 0)) }}</td><td class="ar-print-text">{{ $report['problems_encountered'] }}</td><td class="ar-print-text">{{ $report['recommendations'] }}</td></tr>@endforeach
                </tbody></table>
                <div class="ar-print-signatures is-summary">@foreach (['president' => ['Prepared by:', 'President'], 'adviser' => ['Noted by:', 'Adviser'], 'head' => ['Certified True and Correct:', 'Head, Student Organization']] as $key => $role)<div class="ar-print-signature">{{ $role[0] }}<strong>{{ $signatories[$key] ?? '' }}</strong><small>{{ $role[1] }}@if ($key !== 'head'), {{ $organization }}@endif</small></div>@endforeach</div>
            </section></div>

            @foreach ($reports as $report)
                <div class="ar-page-shell"><section class="ar-paper" data-ar-paper aria-label="Activity narrative: {{ $report['title'] }}">
                    <header class="ar-institution">Republic of the Philippines<strong>BATANGAS STATE UNIVERSITY</strong><em>The National Engineering University</em><div>{{ $organization }}</div></header><p class="ar-print-period">{{ $semester }} · Academic Year {{ $academicYear }}</p><h1>NARRATIVE REPORT</h1>
                    <h2>I. Background of the Activity</h2>
                    <table class="ar-background"><tbody>
                        <tr><th>A. Title of the Activity</th><td>{{ $report['title'] }}</td></tr>
                        <tr><th>B. Sponsor of the Activity</th><td class="ar-print-text">{{ $report['sponsor'] }}</td></tr>
                        <tr><th>C. Date / Day / Venue / Time</th><td>{{ $report['date_label'] ?? '' }}<br>{{ $report['venue'] ?? '' }}<br>{{ $report['time_label'] ?? '' }}</td></tr>
                        <tr><th>D. Objectives</th><td class="ar-print-text">{{ $report['objectives'] }}</td></tr>
                        <tr><th>E. Sustainable Development Goals</th><td>@foreach (($report['sdg_goals'] ?? []) as $goal)<div>{{ $sdgLabel($goal) }}</div>@endforeach</td></tr>
                        <tr><th>F. Number of Participants</th><td>Total Number: {{ (int) $report['participants'] }}<br>Male: {{ (int) $report['male_participants'] }}<br>Female: {{ (int) $report['female_participants'] }}</td></tr>
                        <tr><th>G. Persons Involved / Participants</th><td class="ar-print-text">{{ $report['people_involved'] }}</td></tr>
                    </tbody></table>
                    <h2>II. Highlights of the Activity</h2><div class="ar-print-text ar-print-narrative">{{ $report['narrative'] }}</div>
                    <h3>A. Brief Overview of the Activity</h3><div class="ar-print-text ar-print-narrative">{{ $report['brief_description'] }}</div>
                    <h3>Problems Encountered</h3><div class="ar-print-text">{{ $report['problems_encountered'] }}</div><h3>Recommendations</h3><div class="ar-print-text">{{ $report['recommendations'] }}</div>
                </section></div>

                @foreach (($report['evidence'] ?? []) as $image)
                    <div class="ar-page-shell"><section class="ar-paper" data-ar-paper aria-label="Captioned documentation"><header class="ar-institution"><strong>{{ $organization }}</strong>{{ $semester }} · AY {{ $academicYear }}</header><h2>B. Documentation</h2><p><strong>{{ $report['title'] }}</strong></p><figure class="ar-print-photo"><img src="{{ $image['url'] }}" alt="{{ $image['caption'] }}" loading="eager"><figcaption>{{ $image['caption'] }}</figcaption><a href="{{ $image['url'] }}" target="_blank" rel="noopener">{{ $image['name'] }}</a></figure></section></div>
                @endforeach

                @php $financial = $report['financial'] ?? []; @endphp
                <div class="ar-page-shell"><section class="ar-paper" data-ar-paper aria-label="Activity financial report"><header class="ar-institution"><strong>{{ $organization }}</strong>{{ $semester }} · AY {{ $academicYear }}</header><h2>C. Financial Report / Expenses for the Activity</h2><p><strong>{{ $report['title'] }}</strong></p>
                    @foreach (['collections' => ['Collection', 'total_collection'], 'expenses' => ['Expenses', 'total_expenses']] as $key => $labels)
                        <h3>{{ $labels[0] }}</h3><table class="ar-print-table"><colgroup><col style="width:35%"><col style="width:12%"><col style="width:17%"><col style="width:18%"><col style="width:18%"></colgroup><thead><tr><th>Particulars</th><th>Quantity</th><th>Unit Cost</th><th>Total Cost</th><th>Receipt No. / Reference</th></tr></thead><tbody>@forelse (($financial[$key] ?? []) as $row)<tr><td>{{ $row['particulars'] }}@if (!empty($row['date']))<br><small>{{ $row['date'] }}</small>@endif</td><td class="ar-number">{{ $row['quantity'] ?? '—' }}</td><td class="ar-number">{{ isset($row['unit_cost']) ? $money($row['unit_cost']) : '—' }}</td><td class="ar-number">{{ $money($row['total']) }}</td><td>{{ $row['reference'] ?: 'No reference recorded' }}</td></tr>@empty<tr><td colspan="5">No activity-linked {{ strtolower($labels[0]) }} recorded in the canonical ledger.</td></tr>@endforelse</tbody><tfoot><tr><td colspan="3">Total {{ $labels[0] }}</td><td class="ar-number">{{ $money($financial[$labels[1]] ?? 0) }}</td><td></td></tr></tfoot></table>
                    @endforeach
                    <p><strong>ACTIVITY NET COLLECTION:</strong> {{ $money($financial['net_collection'] ?? 0) }}<br>Activity collections − activity expenses</p><p class="ar-print-source">This is the net of recorded activity-linked collections and expenses, not the organization’s opening balance or annual remaining fund. Unlinked organization income is excluded. Legacy non-itemized spending, where present, is labeled in the ledger rows; no receipt is invented.@if (!empty($financial['recorded_at']))<br>Financial snapshot recorded: {{ $financial['recorded_at'] }}@endif</p>
                    @if (!empty($financial['source_note']))<p class="ar-print-source ar-print-text">{{ $financial['source_note'] }}</p>@endif
                    <h3>Attachment / Receipt References</h3><ul class="ar-receipt-references">@forelse (($financial['expenses'] ?? []) as $row)<li>{{ $row['particulars'] }} — {{ $row['reference'] ?: 'No receipt reference recorded' }}@if (!empty($row['receipt_url'])) · <a href="{{ $row['receipt_url'] }}" target="_blank" rel="noopener">View authorized supporting receipt</a>@endif</li>@empty<li>No expense receipts are recorded for this activity.</li>@endforelse</ul>
                    @if (!empty($financial['receipt_attachments']))
                        <table class="ar-print-table ar-receipt-references"><thead><tr><th>Receipt reference</th><th>Posting reference</th><th>Available supporting scan</th></tr></thead><tbody>@foreach ($financial['receipt_attachments'] as $attachment)<tr><td>{{ $attachment['reference'] ?? '' }}</td><td>{{ $attachment['posting_reference'] ?? '' }}</td><td><a href="{{ $attachment['url'] }}" target="_blank" rel="noopener">{{ $attachment['name'] }}</a></td></tr>@endforeach</tbody></table>
                    @endif
                    <div class="ar-print-signatures">@foreach ($roles as $key => $role)<div class="ar-print-signature">{{ $role[0] }}<strong>{{ data_get($report, 'signatories.'.$key, '') }}</strong><small>{{ $role[1] }}@if (in_array($key, ['secretary', 'auditor', 'president', 'adviser'])), {{ $organization }}@endif</small></div>@endforeach</div><footer class="ar-print-footer">Names are provided by the organization. Blank signatory lines are intentionally retained; this native printout does not create signed approval.</footer>
                </section></div>
                @foreach (($financial['receipt_attachments'] ?? []) as $attachment)
                    <div class="ar-page-shell"><section class="ar-paper" data-ar-paper aria-label="Supporting receipt scan"><header class="ar-institution"><strong>{{ $organization }}</strong>{{ $semester }} · AY {{ $academicYear }}</header><h2>Attachment: Official Receipt / Supporting Scan</h2><p><strong>{{ $report['title'] }}</strong><br>Receipt reference: {{ $attachment['reference'] ?? '' }}<br>Posting reference: {{ $attachment['posting_reference'] ?? '' }}</p><figure class="ar-print-photo"><img src="{{ $attachment['url'] }}" alt="{{ $attachment['caption'] }}" loading="eager"><figcaption>{{ $attachment['caption'] }}</figcaption><a href="{{ $attachment['url'] }}" target="_blank" rel="noopener">{{ $attachment['name'] }}</a></figure></section></div>
                @endforeach
            @endforeach
        @endif
    </main>
</body>
</html>
