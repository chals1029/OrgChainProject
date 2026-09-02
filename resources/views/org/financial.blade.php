@extends('org.layout')

@section('title', 'Financial Report')

@section('header')
    <h1><strong>Financial Report</strong></h1>
    <p class="org-welcome">Compile Budget Utilization entries into a transparent semester financial report.</p>
@endsection

@section('actions')
    <button type="button" class="org-btn org-btn-primary" onclick="window.print()">
        <i class="bi bi-printer"></i> Print Report
    </button>
@endsection

@section('content')
    <style>
        .org-fin-page-grid {
            display: flex;
            flex-direction: column;
            gap: 1.4rem;
        }

        .org-fin-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.75rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
        }

        .org-fin-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .org-fin-card-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a1618;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }

        /* Report Period Card */
        .org-period-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1.2fr;
            gap: 1rem;
            align-items: flex-end;
        }

        .org-period-field {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .org-period-field label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #554d50;
        }

        .org-period-field select {
            padding: 0.65rem 0.85rem;
            border-radius: 12px;
            border: 1.5px solid #e8dedf;
            font-size: 0.86rem;
            outline: none;
            background: #ffffff;
            color: #1a1618;
            font-weight: 600;
        }

        .org-period-summary-box {
            background: #faf4f5;
            border: 1px solid #f0e6e8;
            border-radius: 14px;
            padding: 0.65rem 1rem;
            text-align: right, center;
        }

        .org-period-summary-box small {
            display: block;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #7a7074;
            letter-spacing: 0.04em;
        }

        .org-period-summary-box strong {
            display: block;
            font-size: 0.88rem;
            font-weight: 800;
            color: #7a1222;
        }

        .org-period-summary-box span {
            display: block;
            font-size: 0.74rem;
            color: #554d50;
        }

        /* Cash Flow Summary Card */
        .org-cf-metrics-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.35rem;
        }

        .org-cf-metric-col {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .org-cf-label {
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #7a7074;
            letter-spacing: 0.04em;
        }

        .org-cf-amount {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1.1;
        }

        .org-cf-amount.is-inflow { color: #16a34a; }
        .org-cf-amount.is-outflow { color: #ca8a04; }

        .org-cf-sub {
            font-size: 0.76rem;
            color: #7a7074;
        }

        .org-cf-banner {
            background: #7a1222;
            border-radius: 16px;
            padding: 1.25rem 1.6rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .org-cf-banner-left span {
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: block;
            opacity: 0.9;
        }

        .org-cf-banner-left small {
            font-size: 0.74rem;
            opacity: 0.8;
            display: block;
            margin-top: 0.15rem;
        }

        .org-cf-banner-right {
            text-align: right;
        }

        .org-cf-banner-right h2 {
            font-size: 2.1rem;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .org-cf-banner-right small {
            font-size: 0.74rem;
            opacity: 0.8;
            display: block;
            margin-top: 0.25rem;
        }

        /* Activity-Level Table */
        .org-table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .org-fin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
            text-align: left;
        }

        .org-fin-table th {
            padding: 0.75rem 0.85rem;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #7a7074;
            border-bottom: 1.5px solid #f2e9eb;
            background: #faf6f7;
            letter-spacing: 0.03em;
        }

        .org-fin-table td {
            padding: 0.95rem 0.85rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-fin-table tr:hover td {
            background: #fffafa;
        }

        .org-fin-table tfoot td {
            font-weight: 800;
            font-size: 0.92rem;
            border-top: 2px solid #e8dedf;
            border-bottom: none;
            background: #ffffff;
            padding-top: 1rem;
        }

        .org-chip-scope-ic {
            background: #fdf0f2;
            color: #8b1828;
            border: 1px solid #f8d7dc;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .org-chip-scope-oc {
            background: #fefce8;
            color: #ca8a04;
            border: 1px solid #fef08a;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .org-burn-bar-wrap {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .org-burn-mini-track {
            width: 60px;
            height: 5px;
            border-radius: 9999px;
            background: #f1e8e9;
            overflow: hidden;
        }

        .org-burn-mini-fill-green {
            height: 100%;
            background: #16a34a;
            border-radius: 9999px;
        }

        .org-burn-mini-fill-amber {
            height: 100%;
            background: #d97706;
            border-radius: 9999px;
        }

        /* Status Pills Matching activities.blade.php */
        .org-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: 0.01em;
        }

        .org-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .org-status-purple {
            background: #f3e8ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }
        .org-status-purple .org-status-dot { background: #7e22ce; }

        .org-status-yellow {
            background: #fefce8;
            color: #b45309;
            border: 1px solid #fef08a;
        }
        .org-status-yellow .org-status-dot { background: #d97706; }

        .org-status-blue {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
        }
        .org-status-blue .org-status-dot { background: #2563eb; }

        .org-status-red {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .org-status-red .org-status-dot { background: #dc2626; }

        .org-status-green {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .org-status-green .org-status-dot { background: #16a34a; }

        /* Bottom Action Bar */
        .org-fin-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .org-btn-submit-report {
            background: #7a1222;
            color: #ffffff;
            border: none;
            border-radius: 9999px;
            padding: 0.75rem 2.25rem;
            font-size: 0.92rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            box-shadow: 0 4px 16px rgba(122, 18, 34, 0.25);
            transition: all 0.15s ease;
        }

        .org-btn-submit-report:hover {
            background: #600c19;
            transform: translateY(-1px);
        }
    </style>

    <div class="org-fin-page-grid">
        {{-- 1. Report Period Card --}}
        <section class="org-fin-card">
            <div class="org-fin-card-header" style="margin-bottom: 0.85rem;">
                <h3 class="org-fin-card-title">
                    <i class="bi bi-calendar2-range" style="color: #8b1828;"></i> Report Period
                </h3>
            </div>

            <div class="org-period-grid">
                <div class="org-period-field">
                    <label>Semester</label>
                    <select id="finPeriodSemester" onchange="updateSelectedPeriod()">
                        <option value="All Semester" selected>All Semester</option>
                        <option value="1st Semester" >1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Midyear">Midyear</option>
                        
                    </select>
                </div>

                <div class="org-period-field">
                    <label>Academic Year</label>
                    <select id="finPeriodYear" onchange="updateSelectedPeriod()">
                        <option value="All Years" selected>All Years</option>
                        <option value="2025-2026">2025-2026</option>
                        <option value="2026-2027">2026-2027</option>
                        
                    </select>
                </div>

                <div class="org-period-field">
                    <label>Scope</label>
                    <select id="finPeriodScope" onchange="updateSelectedPeriod()">
                        <option value="All Scopes" selected>All Scopes</option>
                        <option value="In-Campus Only">In-Campus Only</option>
                        <option value="Off-Campus Only">Off-Campus Only</option>
                    </select>
                </div>

                <div class="org-period-summary-box">
                    <small>Selected Period</small>
                    <strong id="summarySemYear">All Semester  · All Year</strong>
                    <span id="summaryScope">All Scopes</span>
                </div>
            </div>
        </section>

        {{-- 2. Cash Flow Summary Card --}}
        <section class="org-fin-card">
            <div class="org-fin-card-header">
                <h3 class="org-fin-card-title">
                    <i class="bi bi-wallet2" style="color: #8b1828;"></i> Cash Flow Summary
                </h3>
                <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-weight: 700; font-size: 0.74rem;">
                    Highlighted for FR
                </span>
            </div>

            <div class="org-cf-metrics-row">
                <div class="org-cf-metric-col">
                    <span class="org-cf-label">Beginning Balance</span>
                    <strong class="org-cf-amount">₱0.00</strong>
                    <small class="org-cf-sub">Opening balance at start of semester</small>
                </div>

                <div class="org-cf-metric-col">
                    <span class="org-cf-label">Total Cash Inflow</span>
                    <strong class="org-cf-amount is-inflow">₱185,000.00</strong>
                    <small class="org-cf-sub">Institutional allocation received</small>
                </div>

                <div class="org-cf-metric-col">
                    <span class="org-cf-label">Total Cash Outflow</span>
                    <strong class="org-cf-amount is-outflow">₱115,150.00</strong>
                    <small class="org-cf-sub">Actual expenses disbursed</small>
                </div>
            </div>

            <div class="org-cf-banner">
                <div class="org-cf-banner-left">
                    <span>Ending Cash Balance</span>
                    <small>Beginning + Inflow – Outflow</small>
                </div>
                <div class="org-cf-banner-right">
                    <h2>₱69,850.00</h2>
                    <small>Available as of <span id="bannerSemYear">1st Semester · AY 2025-2026</span></small>
                </div>
            </div>
        </section>

        {{-- 3. Activity-Level Financial Report Entries Table --}}
        <section class="org-fin-card">
            <div class="org-fin-card-header">
                <h3 class="org-fin-card-title">
                    <i class="bi bi-table" style="color: #8b1828;"></i> Activity-Level Financial Report Entries
                </h3>
            </div>

            <div class="org-table-responsive">
                <table class="org-fin-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Scope</th>
                            <th>Allocated</th>
                            <th>Disbursed</th>
                            <th>Balance</th>
                            <th>Burn Rate</th>
                            <th>Budget Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Innovation Fair Booth Series</strong></td>
                            <td><span class="org-chip-scope-ic">In-Campus</span></td>
                            <td>₱15,000</td>
                            <td>₱15,000</td>
                            <td>₱0</td>
                            <td>
                                <div class="org-burn-bar-wrap">
                                    <div class="org-burn-mini-track"><div class="org-burn-mini-fill-green" style="width: 100%;"></div></div>
                                    <span>100%</span>
                                </div>
                            </td>
                            <td>
                                <span class="org-status-pill org-status-green">
                                    <span class="org-status-dot"></span> Recorded
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Volunteer Appreciation Day</strong></td>
                            <td><span class="org-chip-scope-ic">In-Campus</span></td>
                            <td>₱12,500</td>
                            <td>₱12,500</td>
                            <td>₱0</td>
                            <td>
                                <div class="org-burn-bar-wrap">
                                    <div class="org-burn-mini-track"><div class="org-burn-mini-fill-green" style="width: 100%;"></div></div>
                                    <span>100%</span>
                                </div>
                            </td>
                            <td>
                                <span class="org-status-pill org-status-green">
                                    <span class="org-status-dot"></span> Recorded
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Leadership Summit 2026</strong></td>
                            <td><span class="org-chip-scope-oc">Off-Campus</span></td>
                            <td>₱75,000</td>
                            <td>₱42,750</td>
                            <td><strong style="color: #16a34a;">₱32,250</strong></td>
                            <td>
                                <div class="org-burn-bar-wrap">
                                    <div class="org-burn-mini-track"><div class="org-burn-mini-fill-amber" style="width: 57%;"></div></div>
                                    <span>57%</span>
                                </div>
                            </td>
                            <td>
                                <span class="org-status-pill org-status-green">
                                    <span class="org-status-dot"></span> Recorded
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Campus Wellness Week</strong></td>
                            <td><span class="org-chip-scope-ic">In-Campus</span></td>
                            <td>₱42,500</td>
                            <td>₱24,900</td>
                            <td><strong style="color: #16a34a;">₱17,600</strong></td>
                            <td>
                                <div class="org-burn-bar-wrap">
                                    <div class="org-burn-mini-track"><div class="org-burn-mini-fill-amber" style="width: 58.5%;"></div></div>
                                    <span>58.5%</span>
                                </div>
                            </td>
                            <td>
                                <span class="org-status-pill org-status-yellow">
                                    <span class="org-status-dot"></span> Pending
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>BatStateU Sportsfest 2026</strong></td>
                            <td><span class="org-chip-scope-ic">In-Campus</span></td>
                            <td>₱40,000</td>
                            <td>₱20,000</td>
                            <td><strong style="color: #16a34a;">₱20,000</strong></td>
                            <td>
                                <div class="org-burn-bar-wrap">
                                    <div class="org-burn-mini-track"><div class="org-burn-mini-fill-amber" style="width: 50%;"></div></div>
                                    <span>50%</span>
                                </div>
                            </td>
                            <td>
                                <span class="org-status-pill org-status-yellow">
                                    <span class="org-status-dot"></span> Pending
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>TOTAL</td>
                            <td></td>
                            <td>₱185,000</td>
                            <td>₱115,150</td>
                            <td>₱69,850</td>
                            <td>62%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        {{-- 4. Bottom Action Footer --}}
        <div class="org-fin-action-bar">
            @if (($office->office_role ?? '') !== 'oso')
            <button type="button" class="org-btn-submit-report" onclick="alert('Financial Report successfully submitted to OSO Review Desk!');">
                <i class="bi bi-send-check-fill"></i> Submit Financial Report to OSO
            </button>
            @endif
            <button type="button" class="org-btn org-btn-outline" onclick="alert('Exporting Financial Report to PDF...');">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </button>
        </div>
    </div>

    <script>
        function updateSelectedPeriod() {
            const sem = document.getElementById('finPeriodSemester').value;
            const year = document.getElementById('finPeriodYear').value;
            const scope = document.getElementById('finPeriodScope').value;

            const periodLabel = `${sem} · AY ${year}`;
            document.getElementById('summarySemYear').textContent = periodLabel;
            document.getElementById('summaryScope').textContent = scope;
            
            const banner = document.getElementById('bannerSemYear');
            if (banner) {
                banner.textContent = periodLabel;
            }
        }
    </script>
@endsection
