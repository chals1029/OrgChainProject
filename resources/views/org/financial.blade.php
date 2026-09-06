@extends('org.layout')

@section('title', 'Financial Report & Liquidation')

@section('header')
    <h1><strong>Financial Report &amp; Liquidation</strong></h1>
    <p class="org-welcome">Comprehensive financial statements, cash flow analytics, supporting receipts, and official OSO audit verification.</p>
@endsection

@section('actions')
    <div style="display: flex; gap: 0.6rem; align-items: center;">
        <button type="button" class="org-btn org-btn-outline" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button type="button" class="org-btn org-btn-primary" onclick="alert('Exporting Official Financial Report PDF with OSO Verification Seal...')">
            <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
        </button>
    </div>
@endsection

@section('content')
    <style>
        .org-fin-container {
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
        }

        /* 0. Top Interactive Filter Toolbar */
        .org-fin-filter-bar {
            background: #ffffff;
            border-radius: 18px;
            border: 1.5px solid #f0e6e8;
            padding: 0.9rem 1.4rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .org-fin-filter-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-wrap: wrap;
        }

        .org-fin-filter-title {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.82rem;
            font-weight: 800;
            color: #7a1222;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .org-fin-select {
            padding: 0.48rem 0.9rem;
            border-radius: 10px;
            border: 1.5px solid #e8dedf;
            background: #ffffff;
            font-size: 0.84rem;
            font-weight: 600;
            color: #1a1618;
            cursor: pointer;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .org-fin-select:focus {
            border-color: #7a1222;
        }

        .org-fin-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 700;
            background: #fdf0f2;
            color: #7a1222;
            border: 1px solid #f8d7dc;
        }

        /* General Card Base */
        .org-fin-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            position: relative;
        }

        .org-fin-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .org-fin-card-head h3 {
            font-size: 1rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* 1 & 2. Info Panels Grid */
        .org-info-panels-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .org-info-fields-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .org-info-field {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .org-info-field small {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #7a7074;
        }

        .org-info-field strong {
            font-size: 0.92rem;
            font-weight: 700;
            color: #1a1618;
            line-height: 1.25;
        }

        /* 3. Status Stepper Card */
        .org-stepper-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
        }

        .org-stepper-track {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
            position: relative;
            margin-top: 0.75rem;
        }

        .org-step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .org-step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 17px;
            left: calc(50% + 18px);
            width: calc(100% - 36px);
            height: 2px;
            background: #e8dedf;
            z-index: 1;
        }

        .org-step-item.is-done:not(:last-child)::after {
            background: #16a34a;
        }

        .org-step-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #faf4f5;
            border: 2px solid #e8dedf;
            color: #7a7074;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
            transition: all 0.2s ease;
        }

        .org-step-item.is-done .org-step-circle {
            background: #16a34a;
            border-color: #16a34a;
            color: #ffffff;
        }

        .org-step-item.is-active .org-step-circle {
            background: #7a1222;
            border-color: #7a1222;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(122, 18, 34, 0.15);
        }

        .org-step-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #2b2427;
            margin-bottom: 0.15rem;
        }

        .org-step-desc {
            font-size: 0.68rem;
            color: #786f73;
        }

        /* 4, 5, 6. Top Financial KPI Cards (Exact Dashboard Style) */
        .org-kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }

        .org-kpi-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.25rem 1.4rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .org-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(90, 15, 30, 0.06);
        }

        .org-kpi-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }

        .org-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .org-kpi-icon.is-green { background: #dcfce7; color: #16a34a; }
        .org-kpi-icon.is-amber { background: #fef3c7; color: #d97706; }
        .org-kpi-icon.is-blue { background: #e0f2fe; color: #0284c7; }
        .org-kpi-icon.is-pink { background: #fee2e2; color: #dc2626; }

        .org-kpi-num {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1;
        }

        .org-kpi-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
        }

        .org-kpi-sub {
            font-size: 0.76rem;
            color: #7a7074;
            margin: 0;
        }

        /* 7, 8, 9. Financial Charts Section (3 Grid Charts) */
        .org-fin-charts-3col {
            display: grid;
            grid-template-columns: 1fr 1fr 1.25fr;
            gap: 1.25rem;
        }

        .org-chart-box {
            position: relative;
            width: 100%;
            height: 230px;
        }

        /* 10. Financial Details Data Table */
        .org-table-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .org-tab-buttons {
            display: inline-flex;
            background: #faf4f5;
            padding: 0.25rem;
            border-radius: 12px;
            border: 1px solid #f0e6e8;
            gap: 0.25rem;
        }

        .org-tab-btn {
            border: none;
            background: transparent;
            padding: 0.4rem 0.95rem;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #665c60;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-tab-btn.is-active {
            background: #ffffff;
            color: #7a1222;
            box-shadow: 0 2px 6px rgba(90, 15, 30, 0.08);
        }

        .org-search-input {
            padding: 0.45rem 0.85rem;
            border-radius: 10px;
            border: 1.5px solid #e8dedf;
            font-size: 0.82rem;
            outline: none;
            width: 220px;
            transition: border-color 0.15s ease;
        }

        .org-search-input:focus {
            border-color: #7a1222;
        }

        .org-fin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        .org-fin-table th {
            padding: 0.75rem 0.9rem;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #7a7074;
            border-bottom: 1.5px solid #f2e9eb;
            background: #faf6f7;
            letter-spacing: 0.03em;
        }

        .org-fin-table td {
            padding: 0.85rem 0.9rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-fin-table tr:hover td {
            background: #fffafa;
        }

        .org-fin-table tfoot td {
            font-weight: 800;
            font-size: 0.9rem;
            border-top: 2px solid #e8dedf;
            background: #ffffff;
            padding-top: 1rem;
        }

        .org-flow-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .org-flow-in {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .org-flow-out {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* 11. Supporting Documents Section */
        .org-docs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .org-doc-card {
            background: #faf7f8;
            border: 1.5px solid #f0e6e8;
            border-radius: 14px;
            padding: 0.95rem 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            transition: all 0.15s ease;
        }

        .org-doc-card:hover {
            border-color: #d8c2c6;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(90, 15, 30, 0.05);
            transform: translateY(-1px);
        }

        .org-doc-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            overflow: hidden;
        }

        .org-doc-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .org-doc-meta strong {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1a1618;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 170px;
        }

        .org-doc-meta small {
            display: block;
            font-size: 0.7rem;
            color: #7a7074;
        }

        .org-doc-btn {
            background: #ffffff;
            border: 1px solid #e8dedf;
            border-radius: 8px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #554d50;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-doc-btn:hover {
            background: #7a1222;
            color: #ffffff;
            border-color: #7a1222;
        }

        /* 12, 13, 14. Bottom 3-Column Grid (Verification, Remarks, History) */
        .org-bottom-3col {
            display: grid;
            grid-template-columns: 1fr 1fr 1.15fr;
            gap: 1.25rem;
        }

        .org-timeline-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
            padding-left: 0.5rem;
        }

        .org-timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            position: relative;
        }

        .org-timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 14px;
            top: 26px;
            width: 2px;
            height: calc(100% + 4px);
            background: #f0e6e8;
        }

        .org-tl-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            flex-shrink: 0;
            z-index: 2;
        }

        .org-tl-badge.is-green { background: #dcfce7; color: #16a34a; }
        .org-tl-badge.is-blue { background: #e0f2fe; color: #0284c7; }
        .org-tl-badge.is-maroon { background: #fdf0f2; color: #7a1222; }

        .org-tl-content strong {
            display: block;
            font-size: 0.82rem;
            color: #1a1618;
        }

        .org-tl-content small {
            display: block;
            font-size: 0.72rem;
            color: #7a7074;
            margin-top: 0.1rem;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .org-info-panels-grid,
            .org-fin-charts-3col,
            .org-bottom-3col,
            .org-docs-grid {
                grid-template-columns: 1fr;
            }
            .org-kpi-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .org-kpi-row {
                grid-template-columns: 1fr;
            }
            .org-stepper-track {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .org-info-fields-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="org-fin-container">

        {{-- 0. Top Interactive Filter Toolbar --}}
        <section class="org-fin-filter-bar" aria-label="Financial Report Controls">
            <div class="org-fin-filter-left">
                <div class="org-fin-filter-title">
                    <i class="bi bi-funnel-fill"></i>
                    <span>Report Scope:</span>
                </div>

                {{-- Activity Selection Filter --}}
                <select id="finActivitySelect" class="org-fin-select" onchange="switchFinancialReport(this.value)">
                    <option value="consolidated" selected>📊 Consolidated Semester Report</option>
                    <option value="innovation">🚀 Innovation Fair Booth Series</option>
                    <option value="leadership">👑 Leadership Summit 2026</option>
                    <option value="wellness">🌿 Campus Wellness Week</option>
                    <option value="sportsfest">🏅 BatStateU Sportsfest 2026</option>
                </select>

                {{-- Academic Year Selector --}}
                <select id="finYearSelect" class="org-fin-select" onchange="switchFinancialReport(document.getElementById('finActivitySelect').value)">
                    <option value="2025-2026" selected>A.Y. 2025–2026</option>
                    <option value="2026-2027">A.Y. 2026–2027</option>
                </select>

                {{-- Semester Selector --}}
                <select id="finSemSelect" class="org-fin-select" onchange="switchFinancialReport(document.getElementById('finActivitySelect').value)">
                    <option value="1st Semester" selected>1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Midyear">Midyear</option>
                </select>
            </div>

            <div>
                <span class="org-fin-badge-pill" id="finReportPeriodBadge">
                    <i class="bi bi-check2-circle"></i>
                    <span id="finReportPeriodText">1st Semester · A.Y. 2025–2026</span>
                </span>
            </div>
        </section>

        {{-- 1 & 2. Organization Information & Activity/Project Information Panels --}}
        <div class="org-info-panels-grid">
            {{-- 1. Organization Information Panel --}}
            <section class="org-fin-card" aria-label="Organization Information">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-building-check" style="color: #7a1222;"></i> Organization Information</h3>
                    <span class="org-fin-badge-pill" id="orgCategoryBadge">Academic Org</span>
                </div>
                <div class="org-info-fields-grid">
                    <div class="org-info-field">
                        <small>Organization Name</small>
                        <strong id="orgNameVal">Association of Computing Machinery (ACM)</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Academic Year &amp; Term</small>
                        <strong id="orgTermVal">A.Y. 2025–2026 · 1st Semester</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Report Period</small>
                        <strong id="orgPeriodVal">Aug 01, 2025 – Dec 15, 2025</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Faculty Advisor</small>
                        <strong id="orgAdvisorVal">Engr. Maria Santos, MIT</strong>
                    </div>
                </div>
            </section>

            {{-- 2. Activity / Project Information Panel --}}
            <section class="org-fin-card" aria-label="Activity and Project Information">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-folder2-open" style="color: #7a1222;"></i> Activity / Project Scope</h3>
                    <span class="org-fin-badge-pill" id="actScopePill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Institutional Scope</span>
                </div>
                <div class="org-info-fields-grid">
                    <div class="org-info-field">
                        <small>Activity / Project Name</small>
                        <strong id="actNameVal">Consolidated Student Organization Portfolio</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Activity Type / Category</small>
                        <strong id="actTypeVal">Semestral Financial Liquidation</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Execution Date</small>
                        <strong id="actDateVal">Aug 2025 – Dec 2025</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Venue / Campus Location</small>
                        <strong id="actVenueVal">BatStateU Alangilan Campus</strong>
                    </div>
                </div>
            </section>
        </div>

        {{-- 3. Report Status Badge & Stepper --}}
        <section class="org-stepper-card" aria-label="Financial Report Status">
            <div class="org-fin-card-head" style="margin-bottom: 0.5rem;">
                <h3><i class="bi bi-shield-check" style="color: #7a1222;"></i> Report Status &amp; Verification Workflow</h3>
                <span class="org-fin-badge-pill" id="stepperCurrentBadge" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;">
                    <i class="bi bi-patch-check-fill"></i> Verified &amp; Audit Cleared
                </span>
            </div>
            <div class="org-stepper-track" id="workflowStepperTrack">
                <div class="org-step-item is-done" id="stepDraft">
                    <div class="org-step-circle"><i class="bi bi-check-lg"></i></div>
                    <div class="org-step-title">1. Draft</div>
                    <div class="org-step-desc">Entries Compiled</div>
                </div>
                <div class="org-step-item is-done" id="stepSubmitted">
                    <div class="org-step-circle"><i class="bi bi-check-lg"></i></div>
                    <div class="org-step-title">2. Submitted</div>
                    <div class="org-step-desc">Transmitted to OSO</div>
                </div>
                <div class="org-step-item is-done" id="stepReview">
                    <div class="org-step-circle"><i class="bi bi-check-lg"></i></div>
                    <div class="org-step-title">3. Under Verification</div>
                    <div class="org-step-desc">Receipts Audited</div>
                </div>
                <div class="org-step-item is-done" id="stepVerified">
                    <div class="org-step-circle"><i class="bi bi-check-lg"></i></div>
                    <div class="org-step-title">4. Verified</div>
                    <div class="org-step-desc">Audit Signed Off</div>
                </div>
                <div class="org-step-item is-active" id="stepApproval">
                    <div class="org-step-circle"><i class="bi bi-shield-check"></i></div>
                    <div class="org-step-title">5. Final Settlement</div>
                    <div class="org-step-desc">Ledger Sealed</div>
                </div>
            </div>
        </section>

        {{-- 4, 5, 6. Total Revenue, Total Expenses, Remaining Balance (Exact Dashboard Card Style) --}}
        <div class="org-kpi-row">
            {{-- 4. Total Revenue / Funds Received --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-green">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiTotalRevenue">₱185,000</div>
                </div>
                <h3 class="org-kpi-title">Total Funds Received</h3>
                <p class="org-kpi-sub" id="kpiRevenueSub">Institutional allocations &amp; sponsorships</p>
            </article>

            {{-- 5. Total Expenses --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-amber">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiTotalExpenses">₱115,150</div>
                </div>
                <h3 class="org-kpi-title">Total Expenses</h3>
                <p class="org-kpi-sub" id="kpiExpensesSub">100% liquidated with official receipts</p>
            </article>

            {{-- 6. Remaining Balance --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-blue">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiRemainingBalance">₱69,850</div>
                </div>
                <h3 class="org-kpi-title">Remaining Balance</h3>
                <p class="org-kpi-sub" id="kpiBalanceSub">Net surplus balance available for rollover</p>
            </article>
        </div>

        {{-- 7, 8, 9. Income / Fund Sources, Expense Breakdown, Income vs. Expenses Charts --}}
        <div class="org-fin-charts-3col">
            {{-- 7. Income / Fund Sources (Donut Chart) --}}
            <section class="org-fin-card" aria-label="Income and Fund Sources">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-pie-chart-fill" style="color: #16a34a;"></i> Fund Sources</h3>
                </div>
                <div class="org-chart-box">
                    <canvas id="incomeSourcesChart"></canvas>
                </div>
            </section>

            {{-- 8. Expense Breakdown (Donut Chart) --}}
            <section class="org-fin-card" aria-label="Expense Breakdown">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-pie-chart" style="color: #d97706;"></i> Expense Breakdown</h3>
                </div>
                <div class="org-chart-box">
                    <canvas id="expenseBreakdownChart"></canvas>
                </div>
            </section>

            {{-- 9. Income vs Expenses (Bar Chart) --}}
            <section class="org-fin-card" aria-label="Income vs Expenses Comparison">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-bar-chart-fill" style="color: #7a1222;"></i> Inflow vs. Outflow</h3>
                </div>
                <div class="org-chart-box">
                    <canvas id="incomeVsExpensesChart"></canvas>
                </div>
            </section>
        </div>

        {{-- 10. Financial Details (Data Table) --}}
        <section class="org-fin-card" aria-label="Financial Details and Itemized Transactions">
            <div class="org-fin-card-head">
                <h3><i class="bi bi-table" style="color: #7a1222;"></i> Itemized Financial Ledger</h3>
                <span class="org-fin-badge-pill" id="tableRecordCountBadge">8 Transactions</span>
            </div>

            <div class="org-table-controls">
                <div class="org-tab-buttons">
                    <button type="button" class="org-tab-btn is-active" onclick="filterLedger('all', this)">All Flows</button>
                    <button type="button" class="org-tab-btn" onclick="filterLedger('inflow', this)">Inflows Only</button>
                    <button type="button" class="org-tab-btn" onclick="filterLedger('outflow', this)">Outflows Only</button>
                </div>
                <input type="text" id="ledgerSearchInput" class="org-search-input" placeholder="Search payee, item, OR #..." onkeyup="searchLedger(this.value)">
            </div>

            <div style="overflow-x: auto; width: 100%;">
                <table class="org-fin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description / Payee</th>
                            <th>Category</th>
                            <th>Flow Type</th>
                            <th>Amount</th>
                            <th>Receipt / Ref No.</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerTableBody">
                        {{-- Populated dynamically via switchFinancialReport --}}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">NET SURPLUS / SUMMARY</td>
                            <td id="tableFooterNet" style="color: #16a34a;">+₱69,850</td>
                            <td colspan="2">Reconciled with Bank &amp; OSO</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        {{-- 11. Supporting Documents (Document/File List) --}}
        <section class="org-fin-card" aria-label="Supporting Documents and Receipts">
            <div class="org-fin-card-head">
                <h3><i class="bi bi-file-earmark-check" style="color: #7a1222;"></i> Supporting Documents &amp; Vouchers</h3>
                <span class="org-fin-badge-pill" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;">
                    <i class="bi bi-patch-check"></i> 100% Digitally Verified
                </span>
            </div>
            <div class="org-docs-grid" id="supportingDocsGrid">
                {{-- Populated dynamically --}}
            </div>
        </section>

        {{-- 12, 13, 14. Verification Details, Revision/Remarks, and Report History --}}
        <div class="org-bottom-3col">
            {{-- 12. Verification Details --}}
            <section class="org-fin-card" aria-label="Verification Details">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-person-check-fill" style="color: #16a34a;"></i> Verification Details</h3>
                </div>
                <div class="org-info-fields-grid" style="grid-template-columns: 1fr;">
                    <div class="org-info-field">
                        <small>Verified By</small>
                        <strong id="verByVal">Prof. Rodolfo M. Mendoza, CPA</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Designation / Office</small>
                        <strong id="verOfficeVal">OSO Chief Financial Auditor</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Verification Date</small>
                        <strong id="verDateVal">October 12, 2026 · 03:45 PM</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Audit Protocol Hash</small>
                        <strong style="font-family: monospace; font-size: 0.78rem; color: #7a1222;">SHA256: 9e4b78...f42c1</strong>
                    </div>
                </div>
            </section>

            {{-- 13. Revision / Remarks --}}
            <section class="org-fin-card" aria-label="Auditor Remarks and Compliance Notes">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-chat-square-text-fill" style="color: #7a1222;"></i> Auditor Remarks</h3>
                    <span class="org-fin-badge-pill" id="remarksPill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Passed</span>
                </div>
                <div style="background: #faf4f5; border: 1.5px solid #f0e6e8; border-radius: 14px; padding: 1rem 1.15rem; font-size: 0.84rem; color: #40363a; line-height: 1.5;" id="remarksContent">
                    "All itemized expenses and BIR-registered vendor receipts match the approved semestral activity proposal. Zero unliquidated cash advances found. 100% compliant with university financial guidelines."
                </div>
                <div style="margin-top: 0.85rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.76rem; color: #7a7074;">
                    <i class="bi bi-shield-lock-fill" style="color: #16a34a;"></i>
                    <span>Official Seal stamped &amp; digitally recorded.</span>
                </div>
            </section>

            {{-- 14. Report History (Timeline) --}}
            <section class="org-fin-card" aria-label="Report History and Activity Log">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-clock-history" style="color: #0284c7;"></i> Report History</h3>
                </div>
                <div class="org-timeline-list" id="reportHistoryTimeline">
                    {{-- Populated dynamically --}}
                </div>
            </section>
        </div>

    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Financial Dataset Dictionary
        const financialDatasets = {
            consolidated: {
                orgName: 'Association of Computing Machinery (ACM)',
                orgCategory: 'Academic Org',
                term: 'A.Y. 2025–2026 · 1st Semester',
                period: 'Aug 01, 2025 – Dec 15, 2025',
                advisor: 'Engr. Maria Santos, MIT',
                actName: 'Consolidated Student Organization Portfolio',
                actType: 'Semestral Financial Liquidation',
                actDate: 'Aug 2025 – Dec 2025',
                actVenue: 'BatStateU Alangilan Campus',
                actScope: 'Institutional Scope',
                stepperStep: 4,
                stepperStatusText: 'Verified & Audit Cleared',
                stepperBadgeStyle: 'background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;',
                revenue: 185000,
                revenueSub: 'Institutional allocations & sponsorships',
                expenses: 115150,
                expensesSub: '100% liquidated with official receipts',
                balance: 69850,
                balanceSub: 'Net surplus balance available for rollover',
                incomeSources: {
                    labels: ['Institutional Grant', 'Membership Dues', 'Corporate Sponsorships', 'Fundraising'],
                    data: [100000, 40000, 30000, 15000],
                    colors: ['#7a1222', '#16a34a', '#0284c7', '#d97706']
                },
                expenseBreakdown: {
                    labels: ['Venue & Logistics', 'Food & Catering', 'Materials & Kits', 'Honoraria', 'Transportation'],
                    data: [38000, 32150, 22000, 15000, 8000],
                    colors: ['#8b1828', '#d97706', '#0284c7', '#16a34a', '#9333ea']
                },
                inflowVsOutflow: {
                    labels: ['Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    inflows: [60000, 45000, 35000, 25000, 20000],
                    outflows: [25000, 32000, 28150, 18000, 12000]
                },
                ledger: [
                    { date: 'Aug 10, 2025', desc: 'University Semestral Allocation', cat: 'Institutional', type: 'inflow', amount: 100000, ref: 'BUR-2025-081', status: 'Cleared' },
                    { date: 'Aug 18, 2025', desc: 'Membership Fees Collection (200 Members)', cat: 'Dues', type: 'inflow', amount: 40000, ref: 'OR-ACM-0014', status: 'Deposited' },
                    { date: 'Aug 28, 2025', desc: 'Innovation Fair Venue Rental & Audio Setup', cat: 'Venue & Logistics', type: 'outflow', amount: 15000, ref: 'OR-INV-8910', status: 'Verified' },
                    { date: 'Sep 12, 2025', desc: 'Tech Titan Corp Sponsorship Grant', cat: 'Sponsorship', type: 'inflow', amount: 30000, ref: 'CHK-TT-902', status: 'Cleared' },
                    { date: 'Sep 25, 2025', desc: 'Leadership Summit Hotel & Workshop Hall', cat: 'Venue & Logistics', type: 'outflow', amount: 42750, ref: 'OR-HTL-4412', status: 'Verified' },
                    { date: 'Oct 14, 2025', desc: 'Volunteer Appreciation Catering & Certificates', cat: 'Food & Catering', type: 'outflow', amount: 12500, ref: 'OR-CAT-9921', status: 'Verified' },
                    { date: 'Nov 05, 2025', desc: 'Campus Wellness Kits & Medical Supplies', cat: 'Materials', type: 'outflow', amount: 24900, ref: 'OR-MED-3180', status: 'Verified' },
                    { date: 'Nov 22, 2025', desc: 'Sportsfest Uniforms, Trophies & Hydration', cat: 'Logistics', type: 'outflow', amount: 20000, ref: 'OR-SPT-1142', status: 'Verified' }
                ],
                documents: [
                    { name: 'Consolidated_Financial_Report_AY2526.pdf', size: '3.8 MB', date: 'Dec 18, 2025', tag: 'Official FR' },
                    { name: 'BIR_Official_Receipts_Compilation.pdf', size: '12.4 MB', date: 'Dec 16, 2025', tag: 'Receipts' },
                    { name: 'Bank_Passbook_Reconciliation_Ledger.pdf', size: '1.9 MB', date: 'Dec 15, 2025', tag: 'Bank Record' },
                    { name: 'Sponsorship_Agreements_Signed.pdf', size: '2.4 MB', date: 'Sep 15, 2025', tag: 'Legal' },
                    { name: 'Disbursement_Vouchers_Batch_1_to_8.pdf', size: '4.1 MB', date: 'Dec 10, 2025', tag: 'Vouchers' },
                    { name: 'Student_Council_Auditor_Clearance.pdf', size: '890 KB', date: 'Dec 14, 2025', tag: 'Clearance' }
                ],
                verifiedBy: 'Prof. Rodolfo M. Mendoza, CPA',
                verifiedOffice: 'OSO Chief Financial Auditor',
                verifiedDate: 'October 12, 2026 · 03:45 PM',
                remarks: '"All itemized expenses and BIR-registered vendor receipts match the approved semestral activity proposal. Zero unliquidated cash advances found. 100% compliant with university financial guidelines."',
                history: [
                    { title: 'Official Audit Cleared & Ledger Sealed', date: 'Dec 20, 2025 · 04:30 PM', badge: 'is-green', icon: 'bi-patch-check-fill' },
                    { title: 'OSO Desk Verification Completed', date: 'Dec 18, 2025 · 02:15 PM', badge: 'is-blue', icon: 'bi-check-circle' },
                    { title: 'Supporting Receipts Uploaded', date: 'Dec 16, 2025 · 11:00 AM', badge: 'is-maroon', icon: 'bi-file-earmark-arrow-up' },
                    { title: 'Draft Financial Report Created', date: 'Dec 10, 2025 · 09:30 AM', badge: 'is-maroon', icon: 'bi-pencil' }
                ]
            },
            innovation: {
                orgName: 'Association of Computing Machinery (ACM)',
                orgCategory: 'Academic Org',
                term: 'A.Y. 2025–2026 · 1st Semester',
                period: 'Jul 01, 2026 – Jul 10, 2026',
                advisor: 'Engr. Maria Santos, MIT',
                actName: 'Innovation Fair Booth Series',
                actType: 'In-Campus Project',
                actDate: 'Jul 05, 2026 – Jul 08, 2026',
                actVenue: 'CEAFA Gymnasium, Alangilan',
                actScope: 'In-Campus Only',
                stepperStep: 4,
                stepperStatusText: 'Verified & Audited',
                stepperBadgeStyle: 'background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;',
                revenue: 15000,
                revenueSub: 'University Innovation Grant',
                expenses: 15000,
                expensesSub: '100% liquidated with official receipts',
                balance: 0,
                balanceSub: 'Zero deficit · fully liquidated',
                incomeSources: {
                    labels: ['University Grant', 'Booth Registration'],
                    data: [12000, 3000],
                    colors: ['#7a1222', '#0284c7']
                },
                expenseBreakdown: {
                    labels: ['Sound & Lighting', 'Display Boards', 'Electrical Extensions'],
                    data: [8000, 4500, 2500],
                    colors: ['#8b1828', '#d97706', '#0284c7']
                },
                inflowVsOutflow: {
                    labels: ['Jul 01', 'Jul 04', 'Jul 06', 'Jul 08'],
                    inflows: [12000, 3000, 0, 0],
                    outflows: [0, 8000, 4500, 2500]
                },
                ledger: [
                    { date: 'Jul 01, 2026', desc: 'Innovation Grant Disbursement', cat: 'Grant', type: 'inflow', amount: 12000, ref: 'BUR-26-001', status: 'Cleared' },
                    { date: 'Jul 04, 2026', desc: 'Sound & Lighting Rental', cat: 'Logistics', type: 'outflow', amount: 8000, ref: 'OR-SL-102', status: 'Verified' },
                    { date: 'Jul 05, 2026', desc: 'Participant Registration Collection', cat: 'Fees', type: 'inflow', amount: 3000, ref: 'OR-ACM-092', status: 'Deposited' },
                    { date: 'Jul 06, 2026', desc: 'Display Boards & Tarpaulins', cat: 'Materials', type: 'outflow', amount: 4500, ref: 'OR-PR-772', status: 'Verified' },
                    { date: 'Jul 08, 2026', desc: 'Electrical Accessories & Cables', cat: 'Materials', type: 'outflow', amount: 2500, ref: 'OR-EL-419', status: 'Verified' }
                ],
                documents: [
                    { name: 'Innovation_Fair_Receipts_Official.pdf', size: '2.1 MB', date: 'Jul 10, 2026', tag: 'Receipts' },
                    { name: 'Sound_System_Rental_Contract.pdf', size: '1.2 MB', date: 'Jul 04, 2026', tag: 'Contract' },
                    { name: 'Liquidation_Summary_Sheet.pdf', size: '850 KB', date: 'Jul 10, 2026', tag: 'Summary' }
                ],
                verifiedBy: 'Engr. Daniel Ramirez',
                verifiedOffice: 'Student Activities Coordinator',
                verifiedDate: 'July 10, 2026 · 02:15 PM',
                remarks: '"Fully compliant liquidation with zero remaining balance. All receipts match CEAFA venue requirements."',
                history: [
                    { title: 'Financial Audit Cleared & Signed', date: 'Jul 10, 2026 · 02:15 PM', badge: 'is-green', icon: 'bi-patch-check-fill' },
                    { title: 'Receipts Audited by OSO Desk', date: 'Jul 09, 2026 · 11:30 AM', badge: 'is-blue', icon: 'bi-check-circle' },
                    { title: 'Liquidation Submitted', date: 'Jul 08, 2026 · 05:00 PM', badge: 'is-maroon', icon: 'bi-file-earmark-arrow-up' }
                ]
            },
            leadership: {
                orgName: 'Association of Computing Machinery (ACM)',
                orgCategory: 'Academic Org',
                term: 'A.Y. 2025–2026 · 1st Semester',
                period: 'Aug 01, 2026 – Aug 25, 2026',
                advisor: 'Engr. Maria Santos, MIT',
                actName: 'Leadership Summit 2026',
                actType: 'Off-Campus Project',
                actDate: 'Aug 14, 2026 – Aug 16, 2026',
                actVenue: 'Tagaytay City International Convention Center',
                actScope: 'Off-Campus Approved',
                stepperStep: 3,
                stepperStatusText: 'Under OSO Audit Review',
                stepperBadgeStyle: 'background: #fefce8; color: #b45309; border-color: #fef08a;',
                revenue: 75000,
                revenueSub: 'Off-campus development subsidy',
                expenses: 42750,
                expensesSub: 'Disbursed for Phase 1 accommodation',
                balance: 32250,
                balanceSub: 'Surplus uncommitted balance',
                incomeSources: {
                    labels: ['University Subsidy', 'Delegate Fees', 'Partner Sponsors'],
                    data: [45000, 20000, 10000],
                    colors: ['#7a1222', '#0284c7', '#16a34a']
                },
                expenseBreakdown: {
                    labels: ['Convention Hall', 'Bus Transportation', 'Conference Kits', 'Speaker Honorarium'],
                    data: [25000, 10000, 4750, 3000],
                    colors: ['#8b1828', '#d97706', '#0284c7', '#16a34a']
                },
                inflowVsOutflow: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    inflows: [45000, 20000, 10000, 0],
                    outflows: [10000, 25000, 4750, 3000]
                },
                ledger: [
                    { date: 'Aug 02, 2026', desc: 'University Subsidy Initial Tranche', cat: 'Subsidy', type: 'inflow', amount: 45000, ref: 'BUR-26-089', status: 'Cleared' },
                    { date: 'Aug 05, 2026', desc: 'Bus Transportation Deposit (3 Buses)', cat: 'Transport', type: 'outflow', amount: 10000, ref: 'OR-BUS-112', status: 'Verified' },
                    { date: 'Aug 10, 2026', desc: 'Delegate Registration Remittance', cat: 'Fees', type: 'inflow', amount: 20000, ref: 'OR-ACM-102', status: 'Deposited' },
                    { date: 'Aug 14, 2026', desc: 'Convention Hall & Meals Final Payment', cat: 'Venue', type: 'outflow', amount: 25000, ref: 'OR-TGY-991', status: 'Verified' },
                    { date: 'Aug 16, 2026', desc: 'Conference Kits & Badges', cat: 'Materials', type: 'outflow', amount: 4750, ref: 'OR-KIT-204', status: 'Verified' },
                    { date: 'Aug 18, 2026', desc: 'Guest Speaker Honorarium', cat: 'Honoraria', type: 'outflow', amount: 3000, ref: 'OR-SPK-009', status: 'Pending Review' }
                ],
                documents: [
                    { name: 'Off_Campus_CHED_Permit_Signed.pdf', size: '1.8 MB', date: 'Aug 12, 2026', tag: 'Permit' },
                    { name: 'Hotel_Convention_BIR_Receipt.pdf', size: '3.4 MB', date: 'Aug 17, 2026', tag: 'Receipt' },
                    { name: 'Passenger_Insurance_Manifest.pdf', size: '1.1 MB', date: 'Aug 13, 2026', tag: 'Insurance' }
                ],
                verifiedBy: 'Dr. Evelyn Morales, CPA',
                verifiedOffice: 'OSO Senior Auditor',
                verifiedDate: 'August 20, 2026 · 10:00 AM',
                remarks: '"Phase 1 receipts verified. Pending final speaker honorarium acknowledgement receipt."',
                history: [
                    { title: 'Auditor Review In Progress', date: 'Aug 20, 2026 · 10:00 AM', badge: 'is-blue', icon: 'bi-hourglass-split' },
                    { title: 'Financial Liquidation Submitted', date: 'Aug 18, 2026 · 04:30 PM', badge: 'is-maroon', icon: 'bi-file-earmark-arrow-up' }
                ]
            },
            wellness: {
                orgName: 'Association of Computing Machinery (ACM)',
                orgCategory: 'Academic Org',
                term: 'A.Y. 2025–2026 · 1st Semester',
                period: 'May 10, 2026 – May 25, 2026',
                advisor: 'Engr. Maria Santos, MIT',
                actName: 'Campus Wellness Week',
                actType: 'In-Campus Project',
                actDate: 'May 18, 2026 – May 22, 2026',
                actVenue: 'Student Center Grounds',
                actScope: 'In-Campus Only',
                stepperStep: 4,
                stepperStatusText: 'Verified & Audited',
                stepperBadgeStyle: 'background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;',
                revenue: 42500,
                revenueSub: 'University Health & Wellness Grant',
                expenses: 24900,
                expensesSub: '58.5% burn rate compliant',
                balance: 17600,
                balanceSub: 'Unused allocation returned to fund',
                incomeSources: {
                    labels: ['Health Grant', 'Community Donation'],
                    data: [35000, 7500],
                    colors: ['#7a1222', '#16a34a']
                },
                expenseBreakdown: {
                    labels: ['Medical Supplies', 'Wellness Speakers', 'Refreshments & Fruit Kits'],
                    data: [12900, 7000, 5000],
                    colors: ['#8b1828', '#0284c7', '#d97706']
                },
                inflowVsOutflow: {
                    labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5'],
                    inflows: [35000, 7500, 0, 0, 0],
                    outflows: [5000, 8000, 4900, 4000, 3000]
                },
                ledger: [
                    { date: 'May 10, 2026', desc: 'University Health Grant', cat: 'Grant', type: 'inflow', amount: 35000, ref: 'BUR-26-050', status: 'Cleared' },
                    { date: 'May 12, 2026', desc: 'Alumni Association Health Donation', cat: 'Donation', type: 'inflow', amount: 7500, ref: 'CHK-ALM-11', status: 'Cleared' },
                    { date: 'May 18, 2026', desc: 'First Aid & Mental Health Toolkits', cat: 'Supplies', type: 'outflow', amount: 12900, ref: 'OR-MED-990', status: 'Verified' },
                    { date: 'May 20, 2026', desc: 'Licensed Psychologist Honoraria', cat: 'Honoraria', type: 'outflow', amount: 7000, ref: 'OR-DOC-021', status: 'Verified' },
                    { date: 'May 22, 2026', desc: 'Fresh Fruits & Healthy Refreshments', cat: 'Food', type: 'outflow', amount: 5000, ref: 'OR-FRT-881', status: 'Verified' }
                ],
                documents: [
                    { name: 'Wellness_Medical_Supplies_OR.pdf', size: '2.8 MB', date: 'May 23, 2026', tag: 'Receipt' },
                    { name: 'Doctor_Honorarium_Voucher.pdf', size: '940 KB', date: 'May 22, 2026', tag: 'Voucher' }
                ],
                verifiedBy: 'Dr. Evelyn Morales, CPA',
                verifiedOffice: 'OSO Senior Auditor',
                verifiedDate: 'May 25, 2026 · 09:30 AM',
                remarks: '"Health program liquidation complete. Unused balance of ₱17,600 successfully reconciled."',
                history: [
                    { title: 'Liquidation Audit Cleared', date: 'May 25, 2026 · 09:30 AM', badge: 'is-green', icon: 'bi-patch-check-fill' },
                    { title: 'Liquidation Form Submitted', date: 'May 23, 2026 · 03:00 PM', badge: 'is-maroon', icon: 'bi-file-earmark-arrow-up' }
                ]
            },
            sportsfest: {
                orgName: 'Association of Computing Machinery (ACM)',
                orgCategory: 'Academic Org',
                term: 'A.Y. 2025–2026 · 1st Semester',
                period: 'Sep 01, 2026 – Sep 30, 2026',
                advisor: 'Engr. Maria Santos, MIT',
                actName: 'BatStateU Sportsfest 2026',
                actType: 'In-Campus Project',
                actDate: 'Sep 20, 2026 – Sep 24, 2026',
                actVenue: 'University Athletic Field',
                actScope: 'In-Campus Only',
                stepperStep: 3,
                stepperStatusText: 'Verification In Progress',
                stepperBadgeStyle: 'background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe;',
                revenue: 40000,
                revenueSub: 'Sports development fund',
                expenses: 20000,
                expensesSub: '50% disbursed for team equipment',
                balance: 20000,
                balanceSub: 'Remaining balance for closing banquet',
                incomeSources: {
                    labels: ['Sports Fund', 'Jersey Sponsorship'],
                    data: [30000, 10000],
                    colors: ['#7a1222', '#0284c7']
                },
                expenseBreakdown: {
                    labels: ['Sports Uniforms', 'Trophies & Medals', 'Hydration & Ice'],
                    data: [12000, 5000, 3000],
                    colors: ['#8b1828', '#d97706', '#16a34a']
                },
                inflowVsOutflow: {
                    labels: ['Wk 1', 'Wk 2', 'Wk 3', 'Wk 4'],
                    inflows: [30000, 10000, 0, 0],
                    outflows: [12000, 5000, 3000, 0]
                },
                ledger: [
                    { date: 'Sep 02, 2026', desc: 'University Sports Development Fund', cat: 'Grant', type: 'inflow', amount: 30000, ref: 'BUR-26-092', status: 'Cleared' },
                    { date: 'Sep 08, 2026', desc: 'Jersey Sponsor Donation', cat: 'Sponsorship', type: 'inflow', amount: 10000, ref: 'CHK-JRS-44', status: 'Cleared' },
                    { date: 'Sep 15, 2026', desc: 'Customized ACM Athletic Jerseys', cat: 'Equipment', type: 'outflow', amount: 12000, ref: 'OR-SPT-901', status: 'Verified' },
                    { date: 'Sep 18, 2026', desc: 'Tournament Trophies & Gold Medals', cat: 'Awards', type: 'outflow', amount: 5000, ref: 'OR-TRP-221', status: 'Verified' },
                    { date: 'Sep 22, 2026', desc: 'Mineral Water & Electrolytes Delivery', cat: 'Hydration', type: 'outflow', amount: 3000, ref: 'OR-ICE-330', status: 'Verified' }
                ],
                documents: [
                    { name: 'Sportsfest_Official_Receipts.pdf', size: '3.1 MB', date: 'Sep 25, 2026', tag: 'Receipts' },
                    { name: 'Trophy_Vendor_Invoice.pdf', size: '1.1 MB', date: 'Sep 19, 2026', tag: 'Invoice' }
                ],
                verifiedBy: 'Prof. Rodolfo M. Mendoza, CPA',
                verifiedOffice: 'OSO Chief Financial Auditor',
                verifiedDate: 'September 28, 2026 · 04:00 PM',
                remarks: '"Equipment and jersey receipts valid. Awaiting closing ceremony receipts to seal final audit."',
                history: [
                    { title: 'Mid-Event Audit Reviewed', date: 'Sep 28, 2026 · 04:00 PM', badge: 'is-blue', icon: 'bi-hourglass-split' },
                    { title: 'Partial Liquidation Submitted', date: 'Sep 26, 2026 · 01:15 PM', badge: 'is-maroon', icon: 'bi-file-earmark-arrow-up' }
                ]
            }
        };

        // Chart instances
        let incomeChartInst = null;
        let expenseChartInst = null;
        let comparisonChartInst = null;
        let currentLedgerData = [];

        function initCharts(data) {
            // 1. Income Sources Donut Chart
            const ctxIncome = document.getElementById('incomeSourcesChart').getContext('2d');
            if (incomeChartInst) incomeChartInst.destroy();
            incomeChartInst = new Chart(ctxIncome, {
                type: 'doughnut',
                data: {
                    labels: data.incomeSources.labels,
                    datasets: [{
                        data: data.incomeSources.data,
                        backgroundColor: data.incomeSources.colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11, family: 'Inter, sans-serif' } }
                        }
                    },
                    cutout: '65%'
                }
            });

            // 2. Expense Breakdown Donut Chart
            const ctxExpense = document.getElementById('expenseBreakdownChart').getContext('2d');
            if (expenseChartInst) expenseChartInst.destroy();
            expenseChartInst = new Chart(ctxExpense, {
                type: 'doughnut',
                data: {
                    labels: data.expenseBreakdown.labels,
                    datasets: [{
                        data: data.expenseBreakdown.data,
                        backgroundColor: data.expenseBreakdown.colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11, family: 'Inter, sans-serif' } }
                        }
                    },
                    cutout: '65%'
                }
            });

            // 3. Inflow vs Outflow Bar Chart
            const ctxComp = document.getElementById('incomeVsExpensesChart').getContext('2d');
            if (comparisonChartInst) comparisonChartInst.destroy();
            comparisonChartInst = new Chart(ctxComp, {
                type: 'bar',
                data: {
                    labels: data.inflowVsOutflow.labels,
                    datasets: [
                        {
                            label: 'Funds Inflow',
                            data: data.inflowVsOutflow.inflows,
                            backgroundColor: '#16a34a',
                            borderRadius: 6
                        },
                        {
                            label: 'Expenses Outflow',
                            data: data.inflowVsOutflow.outflows,
                            backgroundColor: '#7a1222',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f5eaec' },
                            ticks: {
                                callback: val => '₱' + (val >= 1000 ? (val / 1000) + 'k' : val),
                                font: { size: 10 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11, family: 'Inter, sans-serif' } }
                        }
                    }
                }
            });
        }

        function renderLedgerTable(items) {
            const tbody = document.getElementById('ledgerTableBody');
            tbody.innerHTML = '';
            
            let totalIn = 0;
            let totalOut = 0;

            items.forEach(item => {
                if (item.type === 'inflow') totalIn += item.amount;
                else totalOut += item.amount;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.date}</strong></td>
                    <td>${item.desc}</td>
                    <td><span style="background: #faf4f5; border: 1px solid #f0e6e8; padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.74rem; font-weight: 600;">${item.cat}</span></td>
                    <td>
                        <span class="org-flow-pill ${item.type === 'inflow' ? 'org-flow-in' : 'org-flow-out'}">
                            <i class="bi ${item.type === 'inflow' ? 'bi-arrow-down-left' : 'bi-arrow-up-right'}"></i>
                            ${item.type === 'inflow' ? 'Inflow' : 'Outflow'}
                        </span>
                    </td>
                    <td><strong style="color: ${item.type === 'inflow' ? '#16a34a' : '#7a1222'};">${item.type === 'inflow' ? '+' : '-'}₱${item.amount.toLocaleString()}</strong></td>
                    <td>
                        <a href="javascript:void(0)" onclick="alert('Viewing digital copy for receipt ${item.ref}...')" style="color: #7a1222; font-weight: 700; text-decoration: none;">
                            <i class="bi bi-paperclip"></i> ${item.ref}
                        </a>
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.76rem; font-weight: 700; color: #16a34a;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #16a34a;"></span> ${item.status}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            const net = totalIn - totalOut;
            const footEl = document.getElementById('tableFooterNet');
            footEl.textContent = (net >= 0 ? '+₱' : '-₱') + Math.abs(net).toLocaleString();
            footEl.style.color = net >= 0 ? '#16a34a' : '#dc2626';

            document.getElementById('tableRecordCountBadge').textContent = items.length + ' Transactions';
        }

        function renderSupportingDocs(docs) {
            const grid = document.getElementById('supportingDocsGrid');
            grid.innerHTML = '';

            docs.forEach(doc => {
                const card = document.createElement('div');
                card.className = 'org-doc-card';
                card.innerHTML = `
                    <div class="org-doc-left">
                        <div class="org-doc-icon">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                        </div>
                        <div class="org-doc-meta">
                            <strong title="${doc.name}">${doc.name}</strong>
                            <small>${doc.size} · ${doc.date}</small>
                        </div>
                    </div>
                    <button type="button" class="org-doc-btn" title="View Document" onclick="alert('Opening document: ${doc.name}')">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                `;
                grid.appendChild(card);
            });
        }

        function renderTimeline(history) {
            const container = document.getElementById('reportHistoryTimeline');
            container.innerHTML = '';

            history.forEach(item => {
                const div = document.createElement('div');
                div.className = 'org-timeline-item';
                div.innerHTML = `
                    <div class="org-tl-badge ${item.badge}">
                        <i class="bi ${item.icon}"></i>
                    </div>
                    <div class="org-tl-content">
                        <strong>${item.title}</strong>
                        <small>${item.date}</small>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        function switchFinancialReport(key) {
            const data = financialDatasets[key] || financialDatasets.consolidated;
            currentLedgerData = data.ledger;

            // 1. Organization & Activity Information
            document.getElementById('orgNameVal').textContent = data.orgName;
            document.getElementById('orgCategoryBadge').textContent = data.orgCategory;
            document.getElementById('orgTermVal').textContent = data.term;
            document.getElementById('orgPeriodVal').textContent = data.period;
            document.getElementById('orgAdvisorVal').textContent = data.advisor;

            document.getElementById('actNameVal').textContent = data.actName;
            document.getElementById('actTypeVal').textContent = data.actType;
            document.getElementById('actDateVal').textContent = data.actDate;
            document.getElementById('actVenueVal').textContent = data.actVenue;
            document.getElementById('actScopePill').textContent = data.actScope;

            // 2. Stepper & Status Badge
            const stepperBadge = document.getElementById('stepperCurrentBadge');
            stepperBadge.innerHTML = `<i class="bi bi-patch-check-fill"></i> ${data.stepperStatusText}`;
            stepperBadge.style = data.stepperBadgeStyle;

            // 3. Top 3 KPI Cards
            document.getElementById('kpiTotalRevenue').textContent = '₱' + data.revenue.toLocaleString();
            document.getElementById('kpiRevenueSub').textContent = data.revenueSub;
            document.getElementById('kpiTotalExpenses').textContent = '₱' + data.expenses.toLocaleString();
            document.getElementById('kpiExpensesSub').textContent = data.expensesSub;
            document.getElementById('kpiRemainingBalance').textContent = '₱' + data.balance.toLocaleString();
            document.getElementById('kpiBalanceSub').textContent = data.balanceSub;

            // 4. Charts
            initCharts(data);

            // 5. Ledger Table
            renderLedgerTable(data.ledger);

            // 6. Supporting Documents
            renderSupportingDocs(data.documents);

            // 7. Verification Details, Remarks & History
            document.getElementById('verByVal').textContent = data.verifiedBy;
            document.getElementById('verOfficeVal').textContent = data.verifiedOffice;
            document.getElementById('verDateVal').textContent = data.verifiedDate;
            document.getElementById('remarksContent').textContent = data.remarks;
            renderTimeline(data.history);

            // Top Badge Text
            const sem = document.getElementById('finSemSelect').value;
            const year = document.getElementById('finYearSelect').value;
            document.getElementById('finReportPeriodText').textContent = `${sem} · A.Y. ${year}`;
        }

        function filterLedger(type, btn) {
            document.querySelectorAll('.org-tab-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');

            if (type === 'all') {
                renderLedgerTable(currentLedgerData);
            } else {
                renderLedgerTable(currentLedgerData.filter(i => i.type === type));
            }
        }

        function searchLedger(query) {
            const q = query.toLowerCase().trim();
            if (!q) {
                renderLedgerTable(currentLedgerData);
                return;
            }
            const filtered = currentLedgerData.filter(i => 
                i.desc.toLowerCase().includes(q) || 
                i.cat.toLowerCase().includes(q) || 
                i.ref.toLowerCase().includes(q)
            );
            renderLedgerTable(filtered);
        }

        // Initialize on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', () => {
            switchFinancialReport('consolidated');
        });
    </script>
@endsection
