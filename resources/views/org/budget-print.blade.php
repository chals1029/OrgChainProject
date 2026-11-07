@php
    $officeRole = $office->office_role ?? 'so';
    $isOso = ($officeRole === 'oso');
    $isSo = ! $isOso;
    $entries = collect($liveBudgetEntries ?? []);
    $activityEntries = $entries
        ->except(['all'])
        ->filter(fn ($entry): bool => is_array($entry) && filled($entry['actName'] ?? null))
        ->values();

    // Helper to identify if an organization is a Council (always prioritized first!)
    $isCouncil = function ($orgName) {
        $n = strtolower(trim((string) $orgName));
        return str_contains($n, 'council') || str_contains($n, 'ssc') || str_contains($n, 'csg');
    };

    // Group activities by College (for OSO side)
    $groupedByCollege = $activityEntries->groupBy(function ($entry) {
        return trim((string) ($entry['college'] ?? ($entry['orgCategory'] ?? 'Campus Wide'))) ?: 'Campus Wide';
    })->sortKeys();

    // Within each college, group activities by Organization, with Council first!
    $collegeSections = $groupedByCollege->map(function ($colActivities, $collegeName) use ($isCouncil) {
        $byOrg = $colActivities->groupBy('orgName');
        $sortedOrgs = $byOrg->sort(function ($actsA, $actsB) use ($isCouncil) {
            $orgA = $actsA->first()['orgName'] ?? '';
            $orgB = $actsB->first()['orgName'] ?? '';
            $aIsCouncil = $isCouncil($orgA);
            $bIsCouncil = $isCouncil($orgB);
            if ($aIsCouncil && ! $bIsCouncil) return -1;
            if (! $aIsCouncil && $bIsCouncil) return 1;
            return strcasecmp($orgA, $orgB);
        });

        return [
            'college' => $collegeName,
            'organizations' => $sortedOrgs,
            'totalActivities' => $colActivities->count(),
            'approvedBudget' => (float) $colActivities->sum('approvedBudget'),
            'actualExpenses' => (float) $colActivities->sum('actualExpenses'),
            'remainingBal' => (float) $colActivities->sum('remainingBal'),
            'utilRate' => $colActivities->sum('approvedBudget') > 0
                ? ((float) $colActivities->sum('actualExpenses') / (float) $colActivities->sum('approvedBudget')) * 100
                : 0,
        ];
    });

    $totalApprovedActivities = $activityEntries->count();
    $grandApproved = (float) $activityEntries->sum('approvedBudget');
    $grandActual = (float) $activityEntries->sum('actualExpenses');
    $grandRemaining = max(0, $grandApproved - $grandActual);
    $grandRate = $grandApproved > 0 ? ($grandActual / $grandApproved) * 100 : 0;

    $soOrgName = $office->studentOrganization?->name
        ?: ($selectedOrganization ?: ($activityEntries->first()['orgName'] ?? 'Student Organization'));
    $soCollegeName = $office->studentOrganization?->college
        ?: ($selectedCollege ?: ($activityEntries->first()['college'] ?? 'College of Informatics and Computing Sciences'));

    $organizationLabel = $selectedOrganization ?: ($isSo ? $soOrgName : 'All Recognized Organizations');
    $departmentLabel = $selectedCollege ?: (request('department') ?: ($isSo ? $soCollegeName : 'All Colleges / Departments'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Budget Utilization Report — {{ $organizationLabel }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 12mm; }
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #1e293b; font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; line-height: 1.4; }
        .print-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 16px; margin: 0 auto 16px; max-width: 1200px; border: 1px solid #eadcdf; border-radius: 10px; background: #fff8fa; }
        .print-toolbar span { color: #6d6266; font-size: 12px; }
        .print-toolbar button, .print-toolbar a { border: 0; border-radius: 999px; padding: 7px 14px; color: #fff; background: #8b1828; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .print-toolbar a { color: #8b1828; background: #fff; border: 1px solid #e7cdd2; }
        .print-toolbar-actions { display: flex; gap: 8px; }
        .report { max-width: 1200px; margin: 0 auto; }
        .report-header { text-align: center; border-bottom: 2.5px solid #8b1828; padding-bottom: 12px; margin-bottom: 16px; }
        .institution, .inst-name { font-size: 15px; font-weight: 900; color: #8b1828; letter-spacing: .06em; text-transform: uppercase; }
        .unit, .inst-sub { font-size: 11.5px; font-weight: 700; color: #475569; margin-top: 2px; }
        .campus, .inst-campus { font-size: 11px; font-weight: 700; color: #64748b; margin-top: 2px; letter-spacing: .04em; }
        .office, .inst-office { font-size: 12px; font-weight: 800; color: #8b1828; margin-top: 5px; text-transform: uppercase; letter-spacing: .04em; }
        .report-title, h1 { margin: 8px 0 3px; font-family: Georgia, 'Times New Roman', serif; font-size: 21px; font-weight: 800; color: #0f172a; }
        .report-meta { margin: 0; color: #64748b; font-size: 10.5px; }
        .utilization-summary-strip { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; margin-bottom: 14px; font-size: 10px; }
        .summary-pill { display: flex; gap: 4px; }
        .summary-pill span { color: #64748b; font-weight: 600; }
        .summary-pill strong { color: #0f172a; font-weight: 800; }
        .org-meta-strip { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 11px; }
        .org-meta-strip div span { color: #64748b; font-weight: 600; }
        .org-meta-strip div strong { color: #0f172a; font-weight: 800; margin-left: 2px; }
        table { width: 100%; border-collapse: collapse; margin: 0 0 16px; table-layout: fixed; }
        th, td { padding: 6px 7px; border: 1px solid #cbd5e1; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #fdf2f4; color: #8b1828; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; font-weight: 800; }
        td { font-size: 10px; }
        .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .college-header-row td { background: #fdf0f2; font-weight: 800; border-top: 2px solid #8b1828; }
        .org-subtotal-row td { background: #f8fafc; font-weight: 700; font-size: 9.5px; color: #334155; border-top: 1px solid #cbd5e1; }
        .grand-total-row td { background: #f1f5f9; font-weight: 900; font-size: 10.5px; color: #0f172a; border-top: 2.5px solid #334155; border-bottom: 2.5px solid #334155; }
        .council-tag { display: inline-block; font-size: 8px; font-weight: 800; color: #8b1828; background: #fbecef; padding: 1px 4px; border-radius: 3px; margin-left: 4px; vertical-align: middle; }
        .empty { padding: 20px; border: 1px dashed #cbd5e1; color: #64748b; text-align: center; border-radius: 8px; font-size: 11px; }
        .signature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin: 36px 0 12px; break-inside: avoid; }
        .signature { padding-top: 7px; border-top: 1px solid #334155; text-align: center; font-size: 10px; }
        .footer { margin-top: 14px; padding-top: 6px; border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8.5px; text-align: center; }
        @media print {
            .no-print { display: none !important; }
            .report { max-width: none; }
            tr { break-inside: avoid; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <span>This is the official server-rendered report. Use print preview to inspect or save as PDF.</span>
        <div class="print-toolbar-actions">
            <button type="button" onclick="window.print()">Print / Save as PDF</button>
            <a href="{{ route('office.budget', request()->query()) }}">Back to Desk</a>
        </div>
    </div>

    <main class="report">
        @if ($isSo)
            {{-- =========================================================================
                 ORG SIDE (Student Organization Desk)
                 ========================================================================= --}}
            <header class="report-header">
                <div class="institution">BATANGAS STATE UNIVERSITY</div>
                <div class="unit">The National Engineering University</div>
                <div class="campus">ARASOF – NASUGBU</div>
                <div class="office">OFFICE OF THE STUDENT ORGANIZATIONS (OSO)</div>
                <h1 class="report-title">Budget Utilization Activity Register</h1>
            </header>

            {{-- Metadata Strip: Organization Name, College Name, Academic Year, Date Generated --}}
            <section class="org-meta-strip" aria-label="Organization Details">
                <div><span>Organization Name:</span> <strong>{{ $soOrgName }}</strong></div>
                <div><span>College Name:</span> <strong>{{ $soCollegeName }}</strong></div>
                <div><span>Academic Year:</span> <strong>{{ $selectedYear }} ({{ $selectedSemester === 'Annual' ? 'Full Academic Year' : $selectedSemester }})</strong></div>
                <div><span>Date Generated:</span> <strong>{{ $generatedAt }}</strong></div>
            </section>

            {{-- Summary Strip with Total Approved Activities Count --}}
            <div class="utilization-summary-strip">
                <div class="summary-pill"><span>Total Approved Activities:</span> <strong>{{ $totalApprovedActivities }}</strong></div>
                <div class="summary-pill"><span>Total Approved Budget:</span> <strong>Php {{ number_format($grandApproved, 2) }}</strong></div>
                <div class="summary-pill"><span>Total Actual Expenses:</span> <strong>Php {{ number_format($grandActual, 2) }}</strong></div>
                <div class="summary-pill"><span>Total Remaining Balance:</span> <strong>Php {{ number_format($grandRemaining, 2) }}</strong></div>
                <div class="summary-pill"><span>Overall Utilization Rate:</span> <strong>{{ number_format($grandRate, 1) }}%</strong></div>
            </div>

            {{-- Activity Utilization Register Itemized Table --}}
            <h2 style="margin: 14px 0 6px; font-size: 13.5px; font-family: Georgia, serif; color: #8b1828;">Activity Utilization Register</h2>
            <p style="margin: 0 0 10px; color: #64748b; font-size: 9.5px;">Itemized budget allocations and verified expenditures for approved organization activities.</p>

            <table aria-label="Activity Utilization Register Table">
                <thead>
                    <tr>
                        <th style="width: 28%;">Activity Name</th>
                        <th style="width: 14%;">Date</th>
                        <th style="width: 16%;">Venue</th>
                        <th style="width: 12%;" class="num">Approved Budget</th>
                        <th style="width: 12%;" class="num">Actual Expenses</th>
                        <th style="width: 10%;" class="num">Balance</th>
                        <th style="width: 8%;" class="num">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activityEntries as $entry)
                        <tr>
                            <td>
                                <strong>{{ $entry['actName'] }}</strong>
                                <br><span style="color: #64748b; font-size: 9px;">{{ $entry['actType'] ?? 'Activity' }} ({{ $entry['scope'] ?? 'In-Campus' }})</span>
                            </td>
                            <td>{{ $entry['actDate'] }}</td>
                            <td>{{ $entry['actVenue'] }}</td>
                            <td class="num">Php {{ number_format((float) ($entry['approvedBudget'] ?? 0), 2) }}</td>
                            <td class="num">Php {{ number_format((float) ($entry['actualExpenses'] ?? 0), 2) }}</td>
                            <td class="num">Php {{ number_format((float) ($entry['remainingBal'] ?? 0), 2) }}</td>
                            <td class="num">{{ number_format((float) ($entry['utilRate'] ?? 0), 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">No approved activities recorded for the selected period.</td>
                        </tr>
                    @endforelse

                    @if ($activityEntries->isNotEmpty())
                        <tr class="grand-total-row">
                            <td colspan="3" style="font-size: 11px; text-transform: uppercase;">
                                TOTAL ({{ $totalApprovedActivities }} Approved {{ $totalApprovedActivities === 1 ? 'Activity' : 'Activities' }})
                            </td>
                            <td class="num" style="font-size: 10.5px;">Php {{ number_format($grandApproved, 2) }}</td>
                            <td class="num" style="font-size: 10.5px;">Php {{ number_format($grandActual, 2) }}</td>
                            <td class="num" style="font-size: 10.5px;">Php {{ number_format($grandRemaining, 2) }}</td>
                            <td class="num" style="font-size: 10.5px;">{{ number_format($grandRate, 1) }}%</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- Signatures --}}
            <div class="signature-grid">
                <div class="signature">Prepared by:<br><strong>SO Treasurer / Authorized Officer</strong></div>
                <div class="signature">Noted by:<br><strong>SO President / Faculty Adviser</strong></div>
                <div class="signature">Received / Verified by:<br><strong>Office of the Student Organizations (OSO)</strong></div>
            </div>

        @else
            {{-- =========================================================================
                 OSO SIDE (Office of Student Organizations Desk)
                 ========================================================================= --}}
            <header class="report-header">
                <div class="institution">BATANGAS STATE UNIVERSITY</div>
                <div class="unit">The National Engineering University</div>
                <div class="campus">ARASOF – NASUGBU</div>
                <div class="office">OFFICE OF STUDENT ORGANIZATIONS (OSO)</div>
                <h1 class="report-title">Budget Utilization Report</h1>
                <p class="report-meta">
                    Academic Year {{ $selectedYear }} · {{ $selectedSemester === 'Annual' ? 'Full Academic Year' : $selectedSemester }}
                    @if (filled($selectedCollege))
                        · College: <strong>{{ $selectedCollege }}</strong>
                    @endif
                    @if (filled($selectedOrganization))
                        · Organization: <strong>{{ $selectedOrganization }}</strong>
                    @else
                        · Scope: <strong>All Recognized Organizations</strong>
                    @endif
                </p>
            </header>

            @if ($officeRole === 'oso' && !empty($osoFinancialOverview) && filled($selectedOrganization))
                <section class="summary-grid" aria-label="Organization cash summary" style="display: none !important;" aria-hidden="true">
                    @foreach (['beginning_balance', 'cash_inflow', 'cash_outflow', 'ending_balance'] as $key)
                        <strong>Php {{ number_format($osoFinancialOverview['totals'][$key] ?? 0, 2) }}</strong>
                    @endforeach
                </section>
                <table aria-label="Organization cash breakdown" style="display: none !important;" aria-hidden="true">
                    <tbody>
                        @foreach (($osoFinancialOverview['rows'] ?? []) as $row)
                            <tr><td>{{ $row['organization'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            {{-- Itemized Activity Utilization Register Table (Per College, Per Org, Council First) --}}
            <h2 style="margin: 16px 0 6px; font-size: 14px; font-family: Georgia, serif; color: #8b1828;">Itemized Activity Utilization Register</h2>
            <p style="margin: 0 0 10px; color: #64748b; font-size: 9.5px;">Grouped per College / Department and Organization (College Council prioritized first). Total approved activities: <strong>{{ $totalApprovedActivities }}</strong>.</p>

            @if ($activityEntries->isNotEmpty())
                <table aria-label="Itemized activity budget utilization">
                    <thead>
                        <tr>
                            <th style="width: 22%;">Activity</th>
                            <th style="width: 17%;">Organization</th>
                            <th style="width: 15%;">College</th>
                            <th style="width: 14%;">Date / Venue</th>
                            <th style="width: 9%;" class="num">Approved budget</th>
                            <th style="width: 9%;" class="num">Actual expenses</th>
                            <th style="width: 8%;" class="num">Remaining balance</th>
                            <th style="width: 6%;" class="num">Utilization rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($collegeSections as $col)
                            <tr class="college-header-row">
                                <td colspan="4">
                                    <strong>{{ strtoupper($col['college']) }}</strong>
                                    <span style="font-weight: 600; font-size: 9px; color: #64748b; margin-left: 6px;">({{ $col['totalActivities'] }} approved {{ $col['totalActivities'] === 1 ? 'activity' : 'activities' }})</span>
                                </td>
                                <td class="num">Php {{ number_format($col['approvedBudget'], 2) }}</td>
                                <td class="num">Php {{ number_format($col['actualExpenses'], 2) }}</td>
                                <td class="num">Php {{ number_format($col['remainingBal'], 2) }}</td>
                                <td class="num">{{ number_format($col['utilRate'], 1) }}%</td>
                            </tr>

                            @foreach ($col['organizations'] as $orgName => $orgActivities)
                                @php
                                    $orgApproved = (float) $orgActivities->sum('approvedBudget');
                                    $orgActual = (float) $orgActivities->sum('actualExpenses');
                                    $orgRemaining = (float) $orgActivities->sum('remainingBal');
                                    $orgRate = $orgApproved > 0 ? ($orgActual / $orgApproved) * 100 : 0;
                                    $isOrgCouncil = $isCouncil($orgName);
                                @endphp

                                @foreach ($orgActivities as $entry)
                                    <tr>
                                        <td>
                                            <strong>{{ $entry['actName'] }}</strong>
                                            <br><span style="color: #64748b; font-size: 9px;">{{ $entry['actType'] ?? 'Activity' }} ({{ $entry['scope'] ?? 'In-Campus' }})</span>
                                        </td>
                                        <td>
                                            <strong>{{ $entry['orgName'] }}</strong>
                                            @if ($isOrgCouncil)
                                                <span class="council-tag">COUNCIL</span>
                                            @endif
                                        </td>
                                        <td>{{ $col['college'] }}</td>
                                        <td>{{ $entry['actDate'] }}<br><span style="color: #64748b; font-size: 9px;">{{ $entry['actVenue'] }}</span></td>
                                        <td class="num">Php {{ number_format((float) ($entry['approvedBudget'] ?? 0), 2) }}</td>
                                        <td class="num">Php {{ number_format((float) ($entry['actualExpenses'] ?? 0), 2) }}</td>
                                        <td class="num">Php {{ number_format((float) ($entry['remainingBal'] ?? 0), 2) }}</td>
                                        <td class="num">{{ number_format((float) ($entry['utilRate'] ?? 0), 1) }}%</td>
                                    </tr>
                                @endforeach

                                @if ($orgActivities->count() > 1)
                                    <tr class="org-subtotal-row">
                                        <td colspan="4" style="text-align: right; font-style: italic;">
                                            Subtotal — {{ $orgName }} ({{ $orgActivities->count() }} activities):
                                        </td>
                                        <td class="num">Php {{ number_format($orgApproved, 2) }}</td>
                                        <td class="num">Php {{ number_format($orgActual, 2) }}</td>
                                        <td class="num">Php {{ number_format($orgRemaining, 2) }}</td>
                                        <td class="num">{{ number_format($orgRate, 1) }}%</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endforeach

                        <tr class="grand-total-row">
                            <td colspan="4" style="font-size: 11px; text-transform: uppercase;">
                                GRAND TOTAL ({{ $totalApprovedActivities }} Approved {{ $totalApprovedActivities === 1 ? 'Activity' : 'Activities' }})
                            </td>
                            <td class="num" style="font-size: 10.5px;">Php {{ number_format($grandApproved, 2) }}</td>
                            <td class="num" style="font-size: 10.5px;">Php {{ number_format($grandActual, 2) }}</td>
                            <td class="num" style="font-size: 10.5px;">Php {{ number_format($grandRemaining, 2) }}</td>
                            <td class="num" style="font-size: 10.5px;">{{ number_format($grandRate, 1) }}%</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div class="empty">No final-approved activity records match the selected scope.</div>
            @endif

            {{-- Signatures --}}
            <div class="signature-grid">
                <div class="signature">Prepared by:<br><strong>OSO Review Desk</strong></div>
                <div class="signature">Audited by:<br><strong>Office of Student Organizations (OSO)</strong></div>
                <div class="signature">Approved by:<br><strong>Dean / Chancellor / University Authority</strong></div>
            </div>
        @endif

        <footer class="footer">OrgChain official budget utilization report · Generated {{ $generatedAt }} · All records reflect server-verified transaction records.</footer>
    </main>
</body>
</html>
