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
    $generatedAtStamp = $packet['generated_at'] ?? now()->format('M j, Y · g:i A');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accomplishment & Financial Report — {{ $organization }} — {{ $semester }} AY {{ $academicYear }}</title>
    <link rel="stylesheet" href="{{ asset('css/org-accomplishment.css') }}?v={{ filemtime(public_path('css/org-accomplishment.css')) }}">
    <script src="{{ asset('js/org-accomplishment.js') }}?v={{ filemtime(public_path('js/org-accomplishment.js')) }}" defer></script>
    <style>
        .ar-institution { text-align: center; padding-bottom: 9px; border-bottom: 2.5px solid #8b1828; margin-bottom: 18px; font-size: 10pt; line-height: 1.35; color: #332b2e; }
        .ar-institution strong { display: block; font-size: 14pt; color: #8b1828; letter-spacing: 0.04em; }
        .ar-institution em { display: block; color: #475569; font-style: normal; font-size: 10.5pt; font-weight: 700; }
        .ar-institution div { font-size: 9.5pt; color: #64748b; font-weight: 600; }
        .ar-print-footer { border-top: 1px solid #cbd5e1; margin-top: 24px; padding-top: 8px; font-size: 8.5pt; color: #64748b; text-align: center; }
    </style>
</head>
<body class="ar-packet">
    @if (!$embedded)
        <header class="ar-print-toolbar">
            <span>Saved native accomplishment packet · {{ $semester }} · AY {{ $academicYear }}</span>
            <nav aria-label="Report actions" style="display: flex; align-items: center; gap: 8px;">
                <button type="button" data-ar-print-page>Print / Save as PDF</button>
                @if (!empty($exportUrl))<a href="{{ $exportUrl }}">Download Word</a>@endif
                <a href="{{ route('office.accomplishment', ['organization' => $organization, 'semester' => $semester, 'academic_year' => $academicYear]) }}">Back to desk</a>
                <button type="button" onclick="if (window.opener) { window.close(); } else if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ route('office.accomplishment', ['organization' => $organization, 'semester' => $semester, 'academic_year' => $academicYear]) }}'; }" style="border: 1px solid #e7cdd2; background: #fff; color: #8b1828; width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; font-size: 1.15rem; line-height: 1; margin-left: 4px;" aria-label="Close" title="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </nav>
        </header>
    @endif

    <main class="ar-packet-main">
        @if (empty($reports))
            <div class="ar-page-shell">
                <section class="ar-paper" data-ar-paper>
                    <header class="ar-institution">
                        <div>Republic of the Philippines</div>
                        <strong>BATANGAS STATE UNIVERSITY</strong>
                        <em>The National Engineering University</em>
                        <div>ARASOF – NASUGBU</div>
                        <div style="font-weight:700;color:#8b1828;">Office of the Student Organizations (OSO)</div>
                    </header>
                    <h1>Accomplishment Report</h1>
                    <p class="ar-print-period">{{ $semester }} · Academic Year {{ $academicYear }}</p>
                    <p>{{ $organization }}</p>
                    <p>No native activity reports have been saved for this period. There is no accomplishment packet to print.</p>
                </section>
            </div>
        @else
            {{-- Cover Page --}}
            <div class="ar-page-shell">
                <section class="ar-paper" data-ar-paper aria-label="Report cover">
                    <div class="ar-cover">
                        <header class="ar-institution">
                            <div>Republic of the Philippines</div>
                            <strong>BATANGAS STATE UNIVERSITY</strong>
                            <em>The National Engineering University</em>
                            <div>ARASOF – NASUGBU</div>
                            <div style="font-weight:700;color:#8b1828;">Office of the Student Organizations (OSO)</div>
                        </header>
                        <div>
                            <div class="ar-cover-organization">{{ $organization }}</div>
                            <h1>ACCOMPLISHMENT &amp;<br>FINANCIAL REPORT</h1>
                            <p class="ar-cover-period">{{ $semester }}<br>Academic Year {{ $academicYear }}</p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Particulars of the Accomplishments (Landscape Table) --}}
            <div class="ar-page-shell">
                <section class="ar-paper is-landscape" data-ar-paper aria-labelledby="arParticularsTitle">
                    <header class="ar-institution">
                        <div>Republic of the Philippines</div>
                        <strong>BATANGAS STATE UNIVERSITY</strong>
                        <em>The National Engineering University</em>
                        <div>ARASOF – NASUGBU</div>
                        <div style="font-weight:700;color:#8b1828;">Office of the Student Organizations (OSO)</div>
                    </header>
                    <h1 id="arParticularsTitle">STUDENT ORGANIZATION ACCOMPLISHMENT REPORT</h1>
                    <p class="ar-print-period">{{ $semester }}, Academic Year {{ $academicYear }}</p>
                    <p><strong>Name of Organization:</strong> {{ $organization }}</p>
                    <h2 style="text-align:left; margin: 18px 0 8px;">PARTICULARS OF THE ACCOMPLISHMENTS</h2>
                    <table class="ar-print-table ar-particulars">
                        <colgroup>
                            <col style="width:12%"><col style="width:15%"><col style="width:11%"><col style="width:10%"><col style="width:7%"><col style="width:14%"><col style="width:9%"><col style="width:11%"><col style="width:11%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Brief Description</th>
                                <th>Sustainable Development Goals</th>
                                <th>Persons Involved/ Participants</th>
                                <th>No. of Participants</th>
                                <th>Date/Day/ Venue/ Time</th>
                                <th>Expenses</th>
                                <th>Problems Encountered</th>
                                <th>Recommendations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td><strong>{{ $report['title'] }}</strong></td>
                                    <td class="ar-print-text">{{ $report['brief_description'] }}</td>
                                    <td>
                                        @foreach (($report['sdg_goals'] ?? []) as $goal)
                                            <div>{{ $sdgLabel($goal) }}</div>
                                        @endforeach
                                    </td>
                                    <td class="ar-print-text">{{ $report['people_involved'] }}</td>
                                    <td>
                                        Total: {{ (int) $report['participants'] }}<br>
                                        Male: {{ (int) $report['male_participants'] }}<br>
                                        Female: {{ (int) $report['female_participants'] }}
                                    </td>
                                    <td>
                                        {{ $report['date_label'] ?? '' }}<br>
                                        {{ $report['time_label'] ?? '' }}<br>
                                        {{ $report['venue'] ?? '' }}
                                    </td>
                                    <td class="ar-number">{{ $money(data_get($report, 'financial.total_expenses', 0)) }}</td>
                                    <td class="ar-print-text">{{ $report['problems_encountered'] }}</td>
                                    <td class="ar-print-text">{{ $report['recommendations'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            </div>

            {{-- Signatures and Endorsement Page (1 page only, immediately after Particulars) --}}
            <div class="ar-page-shell">
                <section class="ar-paper is-landscape" data-ar-paper aria-label="Signatures and Endorsements">
                    <header class="ar-institution">
                        <div>Republic of the Philippines</div>
                        <strong>BATANGAS STATE UNIVERSITY</strong>
                        <em>The National Engineering University</em>
                        <div>ARASOF – NASUGBU</div>
                        <div style="font-weight:700;color:#8b1828;">Office of the Student Organizations (OSO)</div>
                    </header>
                    <h1 style="margin: 10px 0 4px; font-size: 14pt; text-align: center;">STUDENT ORGANIZATION ACCOMPLISHMENT REPORT</h1>
                    <p class="ar-print-period" style="margin: 0 0 4px; text-align: center;">{{ $semester }}, Academic Year {{ $academicYear }}</p>
                    <p style="margin: 0 0 16px; text-align: center;"><strong>Name of Organization:</strong> {{ $organization }}</p>

                    @php
                        $secretaryName = trim((string) ($signatories['secretary'] ?? data_get($reports, '0.signatories.secretary', '')));
                        $deputySecretaryName = trim((string) ($signatories['deputy_secretary_general'] ?? $signatories['deputy_secretary'] ?? data_get($reports, '0.signatories.deputy_secretary_general', data_get($reports, '0.signatories.deputy_secretary', ''))));
                        $presidentName = trim((string) ($signatories['president'] ?? data_get($reports, '0.signatories.president', '')));
                        $adviserName = trim((string) ($signatories['adviser'] ?? data_get($reports, '0.signatories.adviser', '')));
                    @endphp

                    <div class="ar-endorsements-wrap" style="max-width: 680px; margin: 18px 0 0; text-align: left;">
                        {{-- 1. Prepared by Section --}}
                        <div style="margin-bottom: 22px;">
                            <div style="font-weight: 700; font-size: 10pt; margin-bottom: 10px; color: #111;">Prepared by:</div>
                            <div style="display: grid; grid-template-columns: 280px 280px; gap: 40px; align-items: flex-start;">
                                {{-- Secretary --}}
                                <div>
                                    <div style="border-bottom: 1px solid #111; min-height: 24px; font-weight: 700; text-align: center; padding-bottom: 2px; font-size: 10pt;">
                                        {{ $secretaryName }}
                                    </div>
                                    <div style="text-align: center; font-size: 9pt; color: #222; margin-top: 4px;">
                                        Secretary, {{ $organization }}
                                    </div>
                                </div>

                                {{-- Deputy Secretary General (Sa konting isod ng prepared by) --}}
                                <div>
                                    <div style="border-bottom: 1px solid #111; min-height: 24px; font-weight: 700; text-align: center; padding-bottom: 2px; font-size: 10pt;">
                                        {{ $deputySecretaryName }}
                                    </div>
                                    <div style="text-align: center; font-size: 9pt; color: #222; margin-top: 4px;">
                                        Deputy Secretary General, {{ $organization }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Noted by Section --}}
                        <div style="margin-bottom: 22px;">
                            <div style="font-weight: 700; font-size: 10pt; margin-bottom: 10px; color: #111;">Noted by:</div>
                            <div style="display: grid; grid-template-columns: 280px; gap: 18px; align-items: flex-start;">
                                {{-- President --}}
                                <div>
                                    <div style="border-bottom: 1px solid #111; min-height: 24px; font-weight: 700; text-align: center; padding-bottom: 2px; font-size: 10pt;">
                                        {{ $presidentName }}
                                    </div>
                                    <div style="text-align: center; font-size: 9pt; color: #222; margin-top: 4px;">
                                        President, {{ $organization }}
                                    </div>
                                </div>

                                {{-- Adviser --}}
                                <div>
                                    <div style="border-bottom: 1px solid #111; min-height: 24px; font-weight: 700; text-align: center; padding-bottom: 2px; font-size: 10pt;">
                                        {{ $adviserName }}
                                    </div>
                                    <div style="text-align: center; font-size: 9pt; color: #222; margin-top: 4px;">
                                        Adviser, {{ $organization }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 3. Verified true and correct Section --}}
                        <div>
                            <div style="font-weight: 700; font-size: 10pt; margin-bottom: 10px; color: #111;">Verified true and correct:</div>
                            <div style="display: grid; grid-template-columns: 280px; align-items: flex-start;">
                                <div>
                                    <div style="border-bottom: 1px solid #111; min-height: 24px; font-weight: 700; text-align: center; padding-bottom: 2px; font-size: 10pt;">
                                        Dr. DYEMNAH INASIAH P. BENTIR
                                    </div>
                                    <div style="text-align: center; font-size: 9pt; color: #222; margin-top: 4px;">
                                        Head, Office of Student Organization
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </section>
            </div>

            {{-- Narrative & Details for Each Activity --}}
            @foreach ($reports as $report)
                {{-- Narrative Page --}}
                <div class="ar-page-shell">
                    <section class="ar-paper" data-ar-paper aria-label="Activity narrative: {{ $report['title'] }}">
                        <header class="ar-institution">
                            <div>Republic of the Philippines</div>
                            <strong>BATANGAS STATE UNIVERSITY</strong>
                            <em>The National Engineering University</em>
                            <div>ARASOF – NASUGBU</div>
                            <div style="font-weight:700;color:#8b1828;">Office of the Student Organizations (OSO) · {{ $organization }}</div>
                        </header>
                        <p class="ar-print-period">{{ $semester }} · Academic Year {{ $academicYear }}</p>
                        <h1>NARRATIVE REPORT</h1>

                        <h2>I. Background of the Activity</h2>
                        <table class="ar-background">
                            <tbody>
                                <tr><th>A. Title of the Activity</th><td>{{ $report['title'] }}</td></tr>
                                <tr><th>B. Sponsor of the Activity</th><td class="ar-print-text">{{ $report['sponsor'] }}</td></tr>
                                <tr><th>C. Date / Day / Venue / Time</th><td>{{ $report['date_label'] ?? '' }}<br>{{ $report['venue'] ?? '' }}<br>{{ $report['time_label'] ?? '' }}</td></tr>
                                <tr><th>D. Objectives</th><td class="ar-print-text">{{ $report['objectives'] }}</td></tr>
                                <tr><th>E. Sustainable Development Goals</th><td>@foreach (($report['sdg_goals'] ?? []) as $goal)<div>{{ $sdgLabel($goal) }}</div>@endforeach</td></tr>
                                <tr><th>F. Number of Participants</th><td>Total Number: {{ (int) $report['participants'] }}<br>Male: {{ (int) $report['male_participants'] }}<br>Female: {{ (int) $report['female_participants'] }}</td></tr>
                                <tr><th>G. Persons Involved / Participants</th><td class="ar-print-text">{{ $report['people_involved'] }}</td></tr>
                            </tbody>
                        </table>

                        <h2>II. Highlights of the Activity</h2>
                        <div class="ar-print-text ar-print-narrative">{{ $report['narrative'] }}</div>

                        <h3>A. Brief Overview of the Activity</h3>
                        <div class="ar-print-text ar-print-narrative">{{ $report['brief_description'] }}</div>


                    </section>
                </div>

                {{-- Captioned Documentation Page --}}
                @foreach (($report['evidence'] ?? []) as $image)
                    <div class="ar-page-shell">
                        <section class="ar-paper" data-ar-paper aria-label="Captioned documentation">
                            <header class="ar-institution">
                                <div>Republic of the Philippines</div>
                                <strong>BATANGAS STATE UNIVERSITY</strong>
                                <em>The National Engineering University</em>
                                <div>ARASOF – NASUGBU</div>
                                <div style="font-weight:700;color:#8b1828;">Office of the Student Organizations (OSO) · {{ $organization }}</div>
                            </header>
                            @if ($loop->first)
                                <h2>B. Documentation</h2>
                                <p><strong>{{ $report['title'] }}</strong></p>
                            @endif
                            <figure class="ar-print-photo">
                                <img src="{{ $image['url'] }}" alt="{{ $image['caption'] }}" loading="eager">
                                <figcaption>{{ $image['caption'] }}</figcaption>
                                <a href="{{ $image['url'] }}" target="_blank" rel="noopener">{{ $image['name'] }}</a>
                            </figure>
                        </section>
                    </div>
                @endforeach

                {{-- Financial Report for the Activity --}}
                @php $financial = $report['financial'] ?? []; @endphp
                <div class="ar-page-shell">
                    <section class="ar-paper" data-ar-paper aria-label="Activity financial report">
                        <header class="ar-institution">
                            <div>Republic of the Philippines</div>
                            <strong>BATANGAS STATE UNIVERSITY</strong>
                            <em>The National Engineering University</em>
                            <div>ARASOF – NASUGBU</div>
                            <div style="font-weight:700;color:#8b1828;">Office of the Student Organizations (OSO) · {{ $organization }}</div>
                        </header>
                        <h2>C. Financial Report / Expenses for the Activity</h2>
                        <p><strong>{{ $report['title'] }}</strong></p>

                        <h3>Expenses</h3>
                        <table class="ar-print-table">
                            <colgroup>
                                <col style="width:35%"><col style="width:12%"><col style="width:17%"><col style="width:18%"><col style="width:18%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Cost</th>
                                    <th>Total Amount</th>
                                    <th>Receipt No.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($financial['expenses'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['particulars'] }}@if (!empty($row['date']))<br><small>{{ $row['date'] }}</small>@endif</td>
                                        <td class="ar-number">{{ $row['quantity'] ?? '—' }}</td>
                                        <td class="ar-number">{{ isset($row['unit_cost']) ? $money($row['unit_cost']) : '—' }}</td>
                                        <td class="ar-number">{{ $money($row['total']) }}</td>
                                        <td>{{ $row['reference'] ?: 'No reference recorded' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">No activity-linked expenses recorded in the canonical ledger.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">Total Expenses</td>
                                    <td class="ar-number">{{ $money($financial['total_expenses'] ?? 0) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div style="margin: 16px 0 20px;">
                            <p style="margin: 0 0 4px;"><strong>REMAINING FUND:</strong></p>
                            <p style="margin: 0 0 4px;">Remaining Fund = Collection – Expenses</p>
                            <p style="margin: 0; color: #444;">** Expenses incurred for this come from the remaining funds of the council</p>
                        </div>
                    </section>
                </div>

                {{-- Attachments Page (Receipt Scans on Next Page, No Tables) --}}
                @foreach (($financial['receipt_attachments'] ?? []) as $attachment)
                    <div class="ar-page-shell">
                        <section class="ar-paper" data-ar-paper aria-label="Attachments">
                            <header class="ar-institution">
                                <div>Republic of the Philippines</div>
                                <strong>BATANGAS STATE UNIVERSITY</strong>
                                <em>The National Engineering University</em>
                                <div>ARASOF – NASUGBU</div>
                                <div style="font-weight:700;color:#8b1828;">Office of the Student Organizations (OSO) · {{ $organization }}</div>
                            </header>
                            @if ($loop->first)
                                <h2 style="text-align: center; margin: 18px 0 6px;">ATTACHMENTS</h2>
                                <p style="text-align: center; margin: 0 0 16px;"><strong>{{ $report['title'] }}</strong></p>
                            @endif
                            <figure class="ar-print-photo">
                                <img src="{{ $attachment['url'] }}" alt="{{ $attachment['caption'] ?? 'Receipt' }}" loading="eager">
                                @if (!empty($attachment['caption']))
                                    <figcaption>{{ $attachment['caption'] }}</figcaption>
                                @endif
                                @if (!empty($attachment['name']))
                                    <a href="{{ $attachment['url'] }}" target="_blank" rel="noopener">{{ $attachment['name'] }}</a>
                                @endif
                            </figure>
                        </section>
                    </div>
                @endforeach
            @endforeach
        @endif
    </main>
</body>
</html>
