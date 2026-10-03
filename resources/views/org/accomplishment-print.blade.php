@php
    $officeRole = $office->office_role ?? 'so';
    $officeLabel = $officeRole === 'oso'
        ? 'Office of Student Organizations (OSO)'
        : 'Student Organization (SO)';
    $rows = collect($accomplishmentRows ?? []);
    $snapshot = $reportSnapshot ?? [];
    $folders = collect($arFolders ?? []);
    $organizationLabel = $selectedOrganization ?: 'All recognized organizations';
    $genderLabel = ($selectedGender ?? 'all') === 'all' ? 'All participants' : ucfirst((string) $selectedGender).' participants';
    $packageState = data_get($reportBundle ?? [], 'state_label', 'Draft — not submitted');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accomplishment Report — {{ $organizationLabel }}</title>
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
        table { width: 100%; border-collapse: collapse; margin: 0 0 15px; table-layout: fixed; }
        th, td { padding: 6px 7px; border: 1px solid #dfd1d4; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #f8eff1; color: #5f1824; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; }
        td { font-size: 10px; }
        .num { text-align: right; white-space: nowrap; }
        .muted { color: #766b6f; }
        .empty { padding: 13px; border: 1px dashed #cdb9be; color: #766b6f; text-align: center; }
        .status-line { margin: 0 0 9px; color: #5e5458; }
        .status-line strong { color: #8b1828; }
        .signature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 35px; margin: 46px 0 12px; break-inside: avoid; }
        .signature { padding-top: 7px; border-top: 1px solid #332b2e; text-align: center; font-size: 10px; }
        .footer { margin-top: 18px; padding-top: 7px; border-top: 1px solid #dfd1d4; color: #766b6f; font-size: 9px; }
        @media print {
            body { font-size: 10px; }
            .no-print { display: none !important; }
            .report { max-width: none; }
            .report-header { break-after: avoid; }
            tr { break-inside: avoid; }
            a { color: inherit; text-decoration: none; }
        }
        @media (max-width: 720px) {
            .print-toolbar { align-items: flex-start; flex-direction: column; }
            .print-toolbar-actions { width: 100%; }
            .print-toolbar button, .print-toolbar a { flex: 1; text-align: center; }
            .meta-grid, .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .report { padding: 0 12px; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <span>This is the official server-rendered report. Only this report document will be printed.</span>
        <div class="print-toolbar-actions">
            <button type="button" onclick="window.print()">Print / Save as PDF</button>
            <a href="{{ route('office.accomplishment', request()->query()) }}">Back</a>
        </div>
    </div>

    <main class="report">
        <header class="report-header">
            <div class="institution">Batangas State University</div>
            <div class="unit">The National Engineering University · OrgChain Office Reporting</div>
            <div class="report-kicker">{{ $officeLabel }}</div>
            <h1>Semester Accomplishment Report</h1>
            <p class="report-subtitle">Documented activities, objectives achieved, participant reach, and supporting evidence.</p>
        </header>

        <section class="meta-grid" aria-label="Report details">
            <div class="meta-item"><span>Organization scope</span><strong>{{ $organizationLabel }}</strong></div>
            <div class="meta-item"><span>Reporting period</span><strong>{{ $selectedSemester }} · A.Y. {{ $selectedYear }}</strong></div>
            <div class="meta-item"><span>Prepared by</span><strong>{{ $office->name ?? $officeLabel }}</strong></div>
            <div class="meta-item"><span>Generated</span><strong>{{ $generatedAt }}</strong></div>
        </section>

        <p class="status-line">Report package status: <strong>{{ $packageState }}</strong> · Participant filter: <strong>{{ $genderLabel }}</strong>@if (!empty($selectedSdg)) · SDG: <strong>{{ $selectedSdg }}</strong>@endif @if (!empty($selectedCoreValue)) · Core value: <strong>{{ $selectedCoreValue }}</strong>@endif</p>

        <section class="summary-grid" aria-label="Accomplishment summary">
            <div class="summary-card"><span>Activities documented</span><strong>{{ number_format((int) ($snapshot['activities'] ?? $rows->count())) }}</strong></div>
            <div class="summary-card"><span>Participant reach</span><strong>{{ number_format((int) ($snapshot['participants'] ?? $rows->sum('participants'))) }}</strong></div>
            <div class="summary-card"><span>Narrative records</span><strong>{{ number_format((int) ($snapshot['narratives'] ?? 0)) }}</strong></div>
            <div class="summary-card"><span>Evidence references</span><strong>{{ number_format((int) ($snapshot['evidence'] ?? $rows->sum('evidenceCount'))) }}</strong></div>
        </section>

        <h2>Activity Accomplishment Register</h2>
        @if ($rows->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th style="width:16%;">Activity</th>
                        <th style="width:10%;">Implementation</th>
                        <th style="width:13%;">Date / Venue</th>
                        <th style="width:21%;">Objective / Result</th>
                        <th style="width:9%;" class="num">Participants</th>
                        <th style="width:10%;" class="num">Approved budget</th>
                        <th style="width:10%;" class="num">Implemented</th>
                        <th style="width:6%;" class="num">Evidence</th>
                        <th style="width:5%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td><strong>{{ $row['title'] ?? 'Untitled activity' }}</strong><br><span class="muted">{{ $row['college'] ?? 'Campus Wide' }}</span></td>
                            <td>{{ $row['typeLabel'] ?? 'General' }}</td>
                            <td>{{ $row['dateLabel'] ?? 'TBA' }}<br>{{ $row['venue'] ?? 'TBA' }}</td>
                            <td>{{ $row['objectives'] ?: 'No post-activity objective/result narrative recorded.' }}</td>
                            <td class="num">{{ number_format((int) ($row['participants'] ?? 0)) }}<br><span class="muted">M {{ $row['male'] ?? 0 }} / F {{ $row['female'] ?? 0 }}</span></td>
                            <td class="num">Php {{ number_format((float) ($row['approvedBudget'] ?? 0), 2) }}</td>
                            <td class="num">Php {{ number_format((float) ($row['implementedBudget'] ?? 0), 2) }}</td>
                            <td class="num">{{ (int) ($row['evidenceCount'] ?? 0) }}</td>
                            <td>{{ $row['statusLabel'] ?? 'Recorded' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">No final-approved activity accomplishment records are available for the selected filters.</div>
        @endif

        <h2>Evidence Document Register</h2>
        @if ($folders->isNotEmpty())
            <table>
                <thead>
                    <tr><th style="width:25%;">Folder</th><th style="width:34%;">Document</th><th style="width:12%;">Type</th><th style="width:13%;">Size</th><th style="width:16%;">Uploaded</th></tr>
                </thead>
                <tbody>
                    @foreach ($folders as $folder)
                        @forelse (($folder['documents'] ?? []) as $document)
                            <tr>
                                <td>{{ $folder['name'] ?? 'Evidence folder' }}<br><span class="muted">{{ $folder['semester'] ?? '' }}</span></td>
                                <td>{{ $document['name'] ?? 'Unnamed document' }}</td>
                                <td>{{ $document['type'] ?? 'FILE' }}</td>
                                <td>{{ $document['size'] ?? '—' }}</td>
                                <td>{{ $document['date'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td>{{ $folder['name'] ?? 'Evidence folder' }}</td><td colspan="4" class="muted">No documents recorded in this folder.</td></tr>
                        @endforelse
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">No archived evidence folders are available. Upload the accomplishment evidence package before final archiving.</div>
        @endif

        <div class="signature-grid">
            <div class="signature">Prepared by<br><strong>{{ $officeRole === 'oso' ? 'OSO Review Desk' : 'SO Authorized Officer' }}</strong></div>
            <div class="signature">Reviewed by<br><strong>{{ $officeRole === 'oso' ? 'Office of Student Organizations (OSO)' : 'Office of Student Organizations (OSO)' }}</strong></div>
            <div class="signature">Report status<br><strong>{{ $packageState }}</strong></div>
        </div>

        <footer class="footer">OrgChain official semester accomplishment report · Generated {{ $generatedAt }} · This document reflects the selected filters and server-side records at generation time.</footer>
    </main>
</body>
</html>
