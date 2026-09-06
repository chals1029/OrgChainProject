@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSdo = $role === 'sdo';
    $isOvcaa = $role === 'ovcaa';
    $isSo = !$isOso && !$isSdo && !$isOvcaa;
    $canRecordExpense = $isSo;
@endphp

@section('title', 'Budget Utilization & Financial Auditing')

@section('header')
    <h1><strong>Budget Utilization & Financial Intelligence</strong></h1>
    @if ($isOso)
        <p class="org-welcome">Comprehensive monitoring of student organization approved budgets, expense liquidations, audit verification, and ledger histories.</p>
    @elseif ($isSdo)
        <p class="org-welcome">Monitor sustainability budget disbursements, resource utilization rates, and environmental initiative expenditures.</p>
    @elseif ($isOvcaa)
        <p class="org-welcome">Executive institutional overview of university budget utilization, liquidation verifications, and compliance milestones.</p>
    @else
        <p class="org-welcome">Record, track, and liquidate actual expenses with verified receipts for your student organization activities.</p>
    @endif
@endsection

@section('actions')
    <button type="button" class="org-btn org-btn-outline" onclick="window.print()" title="Print this budget report">
        <i class="bi bi-printer"></i> Print Statement
    </button>
    <button type="button" class="org-btn org-btn-primary" onclick="alert('Exporting Official Budget Utilization & Expense Liquidation Report (PDF/Excel)...')">
        <i class="bi bi-file-earmark-arrow-down"></i> Export Report
    </button>
@endsection

@section('content')
    <style>
        /* ---------------------------------------------------------
           Budget Utilization Theme & Tokens (Unslop / Impeccable Style)
           --------------------------------------------------------- */
        .org-budget-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Top Filter & Organization Switcher Card */
        .org-budget-filter-bar {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 0.95rem 1.4rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .org-budget-filter-left {
            display: flex;
            align-items: center;
            gap: 1.15rem;
            flex-wrap: wrap;
            flex: 1;
        }

        .org-budget-filter-title {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.84rem;
            font-weight: 800;
            color: #8b1828;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-right: 0.25rem;
        }

        .org-filter-group-pill {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .org-filter-label-text {
            font-size: 0.76rem;
            font-weight: 700;
            color: #706569;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .org-select-pill-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .org-select-pill {
            appearance: none;
            -webkit-appearance: none;
            background: #fdfafb;
            border: 1.5px solid #f0e0e3;
            border-radius: 9999px;
            padding: 0.42rem 2.1rem 0.42rem 0.95rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #2b2427;
            cursor: pointer;
            outline: none;
            font-family: inherit;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .org-select-pill:hover {
            background: #ffffff;
            border-color: #8b1828;
        }

        .org-select-pill:focus {
            background: #ffffff;
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.12);
        }

        .org-select-pill-arrow {
            position: absolute;
            right: 0.75rem;
            pointer-events: none;
            font-size: 0.65rem;
            color: #8b1828;
        }

        .org-budget-filter-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .org-live-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            font-size: 0.74rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .org-live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #16a34a;
            box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }

        /* 1 & 2. Organization & Activity Information Cards Grid */
        .org-info-panels-grid {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 1.25rem;
        }

        .org-info-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.6rem;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .org-info-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f6eff0;
        }

        .org-info-card-title {
            font-size: 0.96rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .org-info-pill-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.2rem 0.65rem;
            border-radius: 9999px;
            background: #fdf0f2;
            color: #8b1828;
            border: 1px solid #f8d7dc;
        }

        .org-info-meta-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem 1.25rem;
        }

        .org-info-meta-item span.lbl {
            font-size: 0.74rem;
            font-weight: 600;
            color: #786f73;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            display: block;
            margin-bottom: 0.2rem;
        }

        .org-info-meta-item strong.val {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a1618;
            display: block;
        }

        /* 11. Financial Report Status Stepper Card */
        .org-stepper-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.25rem 1.6rem;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
        }

        .org-stepper-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
        }

        .org-stepper-track {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            position: relative;
            gap: 0.5rem;
        }

        .org-step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .org-step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            font-weight: 800;
            background: #f1e8e9;
            color: #786f73;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            margin-bottom: 0.45rem;
            transition: all 0.2s ease;
        }

        .org-step-item.is-done .org-step-circle {
            background: #16a34a;
            color: #ffffff;
        }

        .org-step-item.is-active .org-step-circle {
            background: #8b1828;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(139, 24, 40, 0.15);
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

        /* 3, 4, 5, 6. Top Financial KPI Cards & Utilization Rate Progress Bar */
        .org-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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

        .org-kpi-icon.is-pink { background: #fee2e2; color: #dc2626; }
        .org-kpi-icon.is-amber { background: #fef3c7; color: #d97706; }
        .org-kpi-icon.is-green { background: #dcfce7; color: #16a34a; }
        .org-kpi-icon.is-blue { background: #e0f2fe; color: #0284c7; }

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

        .org-mini-progress {
            height: 5px;
            background: #f1e8e9;
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 0.15rem;
            margin-bottom: 0.15rem;
        }

        .org-mini-fill {
            height: 100%;
            border-radius: 9999px;
            background: #0284c7;
            transition: width 0.6s ease;
        }

        /* 7 & 8. Charts Grid (Expense Breakdown Donut & Budget vs Actual Bar Chart) */
        .org-budget-charts-grid {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 1.25rem;
        }

        .org-budget-chart-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.65rem;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
        }

        .org-budget-chart-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f6eff0;
        }

        .org-budget-chart-head h3 {
            font-size: 1.02rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .org-chart-canvas-wrap {
            position: relative;
            width: 100%;
            height: 270px;
        }

        .org-donut-flex-layout {
            display: grid;
            grid-template-columns: 155px 1fr;
            align-items: center;
            gap: 1.25rem;
            height: 100%;
        }

        .org-donut-canvas-hold {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }

        .org-donut-center-text {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            text-align: center;
        }

        .org-donut-center-text strong {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1;
        }

        .org-donut-center-text small {
            font-size: 0.65rem;
            color: #786f73;
            text-transform: uppercase;
            font-weight: 700;
            margin-top: 0.15rem;
        }

        .org-legend-list {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .org-legend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            padding: 0.35rem 0.55rem;
            border-radius: 8px;
            background: #faf6f7;
        }

        .org-legend-left {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 600;
            color: #2b2427;
        }

        .org-legend-color-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .org-legend-right {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-weight: 700;
            color: #1a1618;
        }

        /* 9. Expense Details Data Table Card */
        .org-expense-table-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.65rem;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
        }

        .org-table-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .org-table-search-input {
            padding: 0.42rem 0.85rem;
            border-radius: 9999px;
            border: 1.5px solid #f0e0e3;
            background: #fdfafb;
            font-size: 0.8rem;
            font-family: inherit;
            outline: none;
            width: 240px;
            transition: all 0.15s ease;
        }

        .org-table-search-input:focus {
            background: #ffffff;
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.12);
        }

        .org-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            text-align: left;
        }

        .org-data-table th {
            padding: 0.75rem 0.95rem;
            font-size: 0.74rem;
            font-weight: 700;
            color: #786f73;
            background: #faf6f7;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5px solid #f0e6e8;
        }

        .org-data-table td {
            padding: 0.85rem 0.95rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-cat-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.65rem;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 700;
            background: #fdf2f4;
            color: #8b1828;
        }

        .org-receipt-link-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            font-size: 0.72rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-receipt-link-pill:hover {
            background: #16a34a;
            color: #ffffff;
        }

        /* 10, 12, 13. Bottom 3-Column Split: Supporting Documents, Verification Details & Activity Log */
        .org-budget-bottom-3col {
            display: grid;
            grid-template-columns: 1.15fr 1fr 1.15fr;
            gap: 1.25rem;
        }

        .org-subpanel-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.5rem;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
        }

        .org-subpanel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f6eff0;
        }

        .org-subpanel-head h3 {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        /* Document File List */
        .org-file-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .org-file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0.85rem;
            border-radius: 12px;
            background: #faf6f7;
            border: 1px solid #f0e4e6;
            transition: all 0.15s ease;
        }

        .org-file-item:hover {
            background: #ffffff;
            border-color: #8b1828;
            box-shadow: 0 2px 8px rgba(90, 15, 30, 0.05);
        }

        .org-file-left {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            overflow: hidden;
        }

        .org-file-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .org-file-meta strong {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #1a1618;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 170px;
        }

        .org-file-meta small {
            display: block;
            font-size: 0.7rem;
            color: #786f73;
        }

        .org-file-action-btn {
            background: #ffffff;
            border: 1px solid #e2d4d7;
            border-radius: 8px;
            padding: 0.3rem 0.55rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: #8b1828;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.15s ease;
        }

        .org-file-action-btn:hover {
            background: #8b1828;
            color: #ffffff;
            border-color: #8b1828;
        }

        /* Verification Panel */
        .org-audit-seal-box {
            background: #fdfafb;
            border: 1.5px solid #f2e4e7;
            border-radius: 16px;
            padding: 1.15rem 1.25rem;
            margin-bottom: 1rem;
        }

        .org-audit-seal-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }

        .org-audit-badge-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .org-audit-remarks-quote {
            font-size: 0.8rem;
            line-height: 1.45;
            color: #4b4548;
            font-style: italic;
            border-left: 3px solid #16a34a;
            padding-left: 0.75rem;
            margin: 0 0 0.85rem;
        }

        .org-hash-code {
            font-family: ui-monospace, monospace;
            font-size: 0.7rem;
            background: #ffffff;
            border: 1px solid #ebd8dc;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            color: #786f73;
            display: block;
            word-break: break-all;
        }

        /* Activity Log Timeline */
        .org-timeline-list {
            position: relative;
            padding-left: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .org-timeline-list::before {
            content: '';
            position: absolute;
            top: 6px;
            bottom: 6px;
            left: 5px;
            width: 2px;
            background: #f0e0e3;
        }

        .org-timeline-item {
            position: relative;
        }

        .org-timeline-item::before {
            content: '';
            position: absolute;
            left: -1.25rem;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #8b1828;
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 2px rgba(139, 24, 40, 0.15);
        }

        .org-timeline-item.is-green::before {
            background: #16a34a;
            box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.15);
        }

        .org-timeline-item strong {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1a1618;
            display: block;
        }

        .org-timeline-item small {
            font-size: 0.72rem;
            color: #786f73;
            display: block;
            margin-top: 0.15rem;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .org-info-panels-grid,
            .org-budget-charts-grid,
            .org-budget-bottom-3col {
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
            .org-budget-filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <div class="org-budget-container">

        {{-- 0. Interactive Filter Toolbar (Matching OSO Unslop Style) --}}
        <section class="org-budget-filter-bar" aria-label="Budget Controls">
            <div class="org-budget-filter-left">
                <div class="org-budget-filter-title">
                    <i class="bi bi-funnel-fill"></i>
                    <span>Scope:</span>
                </div>

                {{-- Activity Selection Filter --}}
                <div class="org-filter-group-pill">
                    <label for="budgetActivitySelector" class="org-filter-label-text"><i class="bi bi-calendar-event"></i> Activity / Project</label>
                    <div class="org-select-pill-wrap">
                        <select id="budgetActivitySelector" class="org-select-pill" onchange="switchActivityData(this.value)">
                            <option value="innovation" selected>Innovation Fair Booth Series (In-Campus)</option>
                            <option value="summit">Leadership Summit 2026 (Off-Campus)</option>
                            <option value="wellness">Campus Wellness Week (In-Campus)</option>
                            <option value="volunteer">Volunteer Appreciation Day (In-Campus)</option>
                            <option value="sportsfest">BatStateU Sportsfest 2026 (In-Campus)</option>
                            <option value="all">Full Institutional Org Portfolio (Consolidated)</option>
                        </select>
                        <i class="bi bi-chevron-down org-select-pill-arrow"></i>
                    </div>
                </div>

                {{-- Academic Year Filter --}}
                <div class="org-filter-group-pill">
                    <label for="budgetYearSelector" class="org-filter-label-text"><i class="bi bi-calendar2-range"></i> Year</label>
                    <div class="org-select-pill-wrap">
                        <select id="budgetYearSelector" class="org-select-pill" onchange="updateFilterPeriod()">
                            <option value="2025-2026" selected>A.Y. 2025–2026</option>
                            <option value="2024-2025">A.Y. 2024–2025</option>
                            <option value="2023-2024">A.Y. 2023–2024</option>
                        </select>
                        <i class="bi bi-chevron-down org-select-pill-arrow"></i>
                    </div>
                </div>

                {{-- Budget Period Filter --}}
                <div class="org-filter-group-pill">
                    <label for="budgetTermSelector" class="org-filter-label-text"><i class="bi bi-bookmark"></i> Period</label>
                    <div class="org-select-pill-wrap">
                        <select id="budgetTermSelector" class="org-select-pill" onchange="updateFilterPeriod()">
                            <option value="1st Semester" selected>1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Annual">Full Fiscal Year</option>
                        </select>
                        <i class="bi bi-chevron-down org-select-pill-arrow"></i>
                    </div>
                </div>
            </div>

            <div class="org-budget-filter-right">
                <span class="org-live-badge">
                    <span class="org-live-dot"></span>
                    <span id="budgetLiveStatusText">Audited &amp; Synchronized</span>
                </span>
            </div>
        </section>

        {{-- 1 & 2. Organization Information & Activity / Project Information Panels --}}
        <div class="org-info-panels-grid">
            {{-- Organization Information Panel --}}
            <section class="org-info-card" aria-label="Organization Information">
                <div class="org-info-card-head">
                    <h3 class="org-info-card-title">
                        <i class="bi bi-building" style="color: #8b1828;"></i> Organization Information
                    </h3>
                    <span class="org-info-pill-badge" id="orgCategoryBadge">Academic Org</span>
                </div>
                <div class="org-info-meta-list">
                    <div class="org-info-meta-item">
                        <span class="lbl">Organization Name</span>
                        <strong class="val" id="orgNameVal">Supreme Student Council (SSC)</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">Academic Year</span>
                        <strong class="val" id="orgAyVal">A.Y. 2025–2026</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">Budget Period</span>
                        <strong class="val" id="orgPeriodVal">1st Semester (Aug–Dec)</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">College / Unit</span>
                        <strong class="val">Alangilan Campus (CICS)</strong>
                    </div>
                </div>
            </section>

            {{-- Activity / Project Information Panel --}}
            <section class="org-info-card" aria-label="Activity and Project Information">
                <div class="org-info-card-head">
                    <h3 class="org-info-card-title">
                        <i class="bi bi-clipboard2-check-fill" style="color: #8b1828;"></i> Activity / Project Information
                    </h3>
                    <span class="org-info-pill-badge" id="actScopePill">In-Campus</span>
                </div>
                <div class="org-info-meta-list">
                    <div class="org-info-meta-item">
                        <span class="lbl">Activity / Project Name</span>
                        <strong class="val" id="actNameVal">Innovation Fair Booth Series</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">Type &amp; Category</span>
                        <strong class="val" id="actTypeVal">Academic &amp; Technology Exhibition</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">Execution Date</span>
                        <strong class="val" id="actDateVal">July 2–4, 2026</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">Designated Venue</span>
                        <strong class="val" id="actVenueVal">Gov. Feliciano Leviste Hall</strong>
                    </div>
                </div>
            </section>
        </div>

        {{-- 11. Financial Report Status Stepper --}}
        <section class="org-stepper-card" aria-label="Financial Report Status">
            <div class="org-stepper-head">
                <h3 style="font-size: 0.98rem; font-weight: 800; color: #1a1618; margin: 0; display: flex; align-items: center; gap: 0.45rem;">
                    <i class="bi bi-shield-check" style="color: #8b1828;"></i> Financial Report Status Workflow
                </h3>
                <span class="org-info-pill-badge" id="stepperCurrentBadge" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;">
                    <i class="bi bi-patch-check-fill"></i> Verified &amp; Audited
                </span>
            </div>

            <div class="org-stepper-track" id="workflowStepperTrack">
                <div class="org-step-item is-done">
                    <div class="org-step-circle"><i class="bi bi-check-lg"></i></div>
                    <div class="org-step-title">1. Draft</div>
                    <div class="org-step-desc">Budget Allocated</div>
                </div>
                <div class="org-step-item is-done">
                    <div class="org-step-circle"><i class="bi bi-check-lg"></i></div>
                    <div class="org-step-title">2. Submitted</div>
                    <div class="org-step-desc">Receipts Encoded</div>
                </div>
                <div class="org-step-item is-done">
                    <div class="org-step-circle"><i class="bi bi-check-lg"></i></div>
                    <div class="org-step-title">3. Under Review</div>
                    <div class="org-step-desc">OCR Cross-Check</div>
                </div>
                <div class="org-step-item is-active" id="stepVerified">
                    <div class="org-step-circle"><i class="bi bi-patch-check"></i></div>
                    <div class="org-step-title">4. Verified</div>
                    <div class="org-step-desc">OSO Audit Cleared</div>
                </div>
                <div class="org-step-item" id="stepRevision">
                    <div class="org-step-circle">5</div>
                    <div class="org-step-title">5. Final Settlement</div>
                    <div class="org-step-desc">Ledger Sealed</div>
                </div>
            </div>
        </section>

        {{-- 3, 4, 5, 6. Approved Budget, Actual Expenses, Remaining Balance & Utilization Rate --}}
        <div class="org-kpi-row">
            {{-- 3. Approved Budget --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-pink">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiApprovedBudget">₱15,000</div>
                </div>
                <h3 class="org-kpi-title">Approved Budget</h3>
                <p class="org-kpi-sub" id="kpiApprovedSub">Total sanctioned allocation</p>
            </article>

            {{-- 4. Actual Expenses --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-amber">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiActualExpenses">₱15,000</div>
                </div>
                <h3 class="org-kpi-title">Actual Expenses</h3>
                <p class="org-kpi-sub" id="kpiActualSub">100% liquidated with official receipts</p>
            </article>

            {{-- 5. Remaining Balance --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-green">
                        <i class="bi bi-piggy-bank"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiRemainingBal">₱0</div>
                </div>
                <h3 class="org-kpi-title">Remaining Balance</h3>
                <p class="org-kpi-sub" id="kpiRemainingSub">Zero deficit · within allocation</p>
            </article>

            {{-- 6. Budget Utilization Rate --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-blue">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiUtilRate">100.0%</div>
                </div>
                <h3 class="org-kpi-title">Budget Utilization Rate</h3>
                <div class="org-mini-progress">
                    <div class="org-mini-fill" id="kpiUtilProgressFill" style="width: 100%; background: #0284c7;"></div>
                </div>
                <p class="org-kpi-sub" id="kpiRateSub">Burn rate compliant with timeline</p>
            </article>
        </div>

        {{-- 7 & 8. Expense Breakdown (Donut Chart) & Budget vs. Actual Expenses (Bar Chart) --}}
        <div class="org-budget-charts-grid">
            {{-- 7. Expense Breakdown (Donut Chart) --}}
            <section class="org-budget-chart-card" aria-label="Expense Breakdown by Category">
                <div class="org-budget-chart-head">
                    <h3><i class="bi bi-pie-chart" style="color: #8b1828;"></i> Expense Breakdown</h3>
                    <span class="org-info-pill-badge" id="donutTotalBadge">₱15,000 Total</span>
                </div>
                <div class="org-chart-canvas-wrap">
                    <div class="org-donut-flex-layout">
                        <div class="org-donut-canvas-hold">
                            <canvas id="expenseDonutChart"></canvas>
                            <div class="org-donut-center-text">
                                <strong id="donutCenterAmount">₱15k</strong>
                                <small>Disbursed</small>
                            </div>
                        </div>
                        <div class="org-legend-list" id="donutCustomLegend">
                            <div class="org-legend-row">
                                <div class="org-legend-left">
                                    <span class="org-legend-color-dot" style="background: #8b1828;"></span>
                                    <span>Equipment &amp; AV</span>
                                </div>
                                <div class="org-legend-right">₱8,000 (53.3%)</div>
                            </div>
                            <div class="org-legend-row">
                                <div class="org-legend-left">
                                    <span class="org-legend-color-dot" style="background: #ca8a04;"></span>
                                    <span>Supplies &amp; Materials</span>
                                </div>
                                <div class="org-legend-right">₱4,500 (30.0%)</div>
                            </div>
                            <div class="org-legend-row">
                                <div class="org-legend-left">
                                    <span class="org-legend-color-dot" style="background: #16a34a;"></span>
                                    <span>Food &amp; Catering</span>
                                </div>
                                <div class="org-legend-right">₱2,500 (16.7%)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 8. Budget vs. Actual Expenses (Bar Chart) --}}
            <section class="org-budget-chart-card" aria-label="Approved Budget vs Actual Expenses">
                <div class="org-budget-chart-head">
                    <h3><i class="bi bi-bar-chart-fill" style="color: #8b1828;"></i> Budget vs. Actual Expenses</h3>
                    <span class="org-info-pill-badge">Category Variance</span>
                </div>
                <div class="org-chart-canvas-wrap">
                    <canvas id="budgetVsActualBarChart"></canvas>
                </div>
            </section>
        </div>

        {{-- 9. Expense Details (Data Table) --}}
        <section class="org-expense-table-card" aria-label="Detailed Expense Entries Table">
            <div class="org-table-header-flex">
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: #1a1618; margin: 0 0 0.15rem; display: flex; align-items: center; gap: 0.45rem;">
                        <i class="bi bi-receipt-cutoff" style="color: #8b1828;"></i> Expense Details &amp; Itemization
                    </h3>
                    <span style="font-size: 0.76rem; color: #786f73;">Itemized disbursements with verified receipt attachments</span>
                </div>
                <div>
                    <input type="text" id="expenseTableSearch" class="org-table-search-input" placeholder="Search item, category, amount..." onkeyup="filterExpenseTable()">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="org-data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description / Merchant</th>
                            <th>Date</th>
                            <th>Quantity</th>
                            <th>Amount (₱)</th>
                            <th>Receipt Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="expenseDetailsTableBody">
                        {{-- Rows injected dynamically via switchActivityData() --}}
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 10, 12, 13. Supporting Documents, Verification Details & Transaction History --}}
        <div class="org-budget-bottom-3col">
            {{-- 10. Supporting Documents (File / Document List) --}}
            <section class="org-subpanel-card" aria-label="Supporting Documents List">
                <div class="org-subpanel-head">
                    <h3><i class="bi bi-paperclip" style="color: #8b1828;"></i> Supporting Documents</h3>
                    <span class="org-info-pill-badge" id="docsCountBadge">3 Files</span>
                </div>
                <div class="org-file-list" id="supportingDocsList">
                    {{-- File items dynamically rendered --}}
                </div>
            </section>

            {{-- 12. Verification Details (Information Panel) --}}
            <section class="org-subpanel-card" aria-label="Verification and Audit Remarks">
                <div class="org-subpanel-head">
                    <h3><i class="bi bi-patch-check-fill" style="color: #16a34a;"></i> Verification Details</h3>
                    <span class="org-info-pill-badge" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Audited</span>
                </div>
                <div class="org-audit-seal-box">
                    <div class="org-audit-seal-row">
                        <div class="org-audit-badge-icon">
                            <i class="bi bi-shield-fill-check"></i>
                        </div>
                        <div>
                            <strong style="font-size: 0.88rem; color: #1a1618; display: block;" id="verifierName">Engr. OSO Officer Desk</strong>
                            <small style="font-size: 0.72rem; color: #786f73; display: block;" id="verifiedDate">Aug 15, 2026 · 10:45 AM</small>
                        </div>
                    </div>
                    <div class="org-audit-remarks-quote" id="auditRemarksText">
                        "All itemized expenses and BIR-registered vendor receipts match the approved activity proposal. 100% compliant with university financial liquidation policy."
                    </div>
                    <span style="font-size: 0.68rem; font-weight: 700; color: #786f73; text-transform: uppercase; margin-bottom: 0.2rem; display: block;">Ledger Verification Hash:</span>
                    <span class="org-hash-code" id="auditHashVal">0x8f2a9c4b10e5d8a7c29e419b348d216f407b8a5e</span>
                </div>
            </section>

            {{-- 13. Transaction History (Timeline / Activity Log) --}}
            <section class="org-subpanel-card" aria-label="Transaction and Audit History">
                <div class="org-subpanel-head">
                    <h3><i class="bi bi-clock-history" style="color: #8b1828;"></i> Transaction History</h3>
                    <span class="org-info-pill-badge">Audit Trail</span>
                </div>
                <div class="org-timeline-list" id="transactionTimeline">
                    {{-- Timeline items dynamically rendered --}}
                </div>
            </section>
        </div>

    </div>

    {{-- Load Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data Registry for all 5 Activities & Consolidated Portfolio
        const budgetDataset = {
            innovation: {
                orgName: 'Supreme Student Council (SSC)',
                orgCategory: 'Academic Council',
                actName: 'Innovation Fair Booth Series',
                actType: 'Academic & Technology Exhibition',
                actDate: 'July 2–4, 2026',
                actVenue: 'Gov. Feliciano Leviste Hall',
                scope: 'In-Campus',
                approvedBudget: 15000,
                actualExpenses: 15000,
                remainingBal: 0,
                utilRate: 100.0,
                rateStatus: 'Optimal',
                rateStatusColor: '#16a34a',
                stepperStep: 4,
                categories: ['Equipment & AV', 'Supplies & Materials', 'Food & Catering'],
                donutData: [8000, 4500, 2500],
                donutColors: ['#8b1828', '#ca8a04', '#16a34a'],
                barAllocated: [8000, 4500, 2500],
                barActual: [8000, 4500, 2500],
                expenses: [
                    { cat: 'Equipment & AV', desc: 'AV Equipment & Stage Speaker Rental', date: 'Jul 3, 2026', qty: '1 set', amount: 8000, status: 'OCR Verified', receiptFile: 'Receipt_AV_Rental_OR8821.pdf' },
                    { cat: 'Supplies & Materials', desc: 'Booth Tarpaulins & Eco-Backdrops', date: 'Jul 2, 2026', qty: '5 pcs', amount: 4500, status: 'OCR Verified', receiptFile: 'Invoice_Backdrops_INV409.pdf' },
                    { cat: 'Food & Catering', desc: 'Packed Snacks & Bottled Water for Booth Facilitators', date: 'Jul 4, 2026', qty: '50 packs', amount: 2500, status: 'Audited by OSO', receiptFile: 'Receipt_Catering_OR9910.pdf' }
                ],
                documents: [
                    { name: 'Receipt_AV_Rental_OR8821.pdf', size: '1.4 MB', date: 'Jul 4, 2026', match: '99.8% Match' },
                    { name: 'Invoice_Backdrops_INV409.pdf', size: '820 KB', date: 'Jul 3, 2026', match: '100% Match' },
                    { name: 'Receipt_Catering_OR9910.pdf', size: '640 KB', date: 'Jul 5, 2026', match: '99.4% Match' }
                ],
                verifier: 'Engr. OSO Officer Desk',
                verifiedDate: 'Jul 10, 2026 · 02:15 PM',
                remarks: '"All liquidation documents, official BIR-registered receipts, and equipment rental vouchers match the approved proposal. 100% compliant with university policies."',
                hash: '0x8f2a9c4b10e5d8a7c29e419b348d216f407b8a5e',
                timeline: [
                    { title: 'Financial Report Audited & Closed', date: 'Jul 10, 2026 · 02:15 PM', green: true },
                    { title: 'All 3 Expense Receipts OCR Verified', date: 'Jul 05, 2026 · 11:30 AM', green: true },
                    { title: 'Actual Expenses Encoded & Uploaded', date: 'Jul 04, 2026 · 04:00 PM', green: false },
                    { title: 'Approved Budget Allocation Released (₱15,000)', date: 'Jun 20, 2026 · 09:00 AM', green: false }
                ]
            },
            summit: {
                orgName: 'Jr. Philippine Inst. of Civil Engineers (JPICE)',
                orgCategory: 'Academic Org',
                actName: 'Leadership Summit 2026',
                actType: 'Off-Campus Leadership Training',
                actDate: 'August 12–14, 2026',
                actVenue: 'Camp Benjamin, Alfonso, Cavite',
                scope: 'Off-Campus (CHED Approved)',
                approvedBudget: 75000,
                actualExpenses: 42750,
                remainingBal: 32250,
                utilRate: 57.0,
                rateStatus: 'Under Liquidation',
                rateStatusColor: '#ca8a04',
                stepperStep: 3,
                categories: ['Transportation', 'Food & Lodging', 'Supplies & Kits', 'Honoraria'],
                donutData: [18000, 15750, 6000, 3000],
                donutColors: ['#8b1828', '#ca8a04', '#1d4ed8', '#16a34a'],
                barAllocated: [25000, 30000, 12000, 8000],
                barActual: [18000, 15750, 6000, 3000],
                expenses: [
                    { cat: 'Transportation', desc: 'Coaster Bus Charter (2 Units with Insurance)', date: 'Aug 12, 2026', qty: '2 buses', amount: 18000, status: 'OCR Verified', receiptFile: 'Bus_Charter_Contract_OR7712.pdf' },
                    { cat: 'Food & Lodging', desc: 'Camp Benjamin Full Board Buffet (Day 1-2)', date: 'Aug 13, 2026', qty: '45 pax', amount: 15750, status: 'OCR Verified', receiptFile: 'Camp_Benjamin_OR9918.pdf' },
                    { cat: 'Supplies & Kits', desc: 'Leadership Workbooks, Lanyards & Badges', date: 'Aug 10, 2026', qty: '45 kits', amount: 6000, status: 'Audited by OSO', receiptFile: 'Supplies_Receipt_OR3310.pdf' },
                    { cat: 'Honoraria', desc: 'Guest Speaker Token & Resource Person Honorarium', date: 'Aug 14, 2026', qty: '2 speakers', amount: 3000, status: 'Signed Voucher', receiptFile: 'Honorarium_Voucher_V881.pdf' }
                ],
                documents: [
                    { name: 'CHED_Regional_Endorsement_RO4A.pdf', size: '2.8 MB', date: 'Aug 05, 2026', match: 'Verified' },
                    { name: 'Bus_Charter_Contract_OR7712.pdf', size: '1.9 MB', date: 'Aug 12, 2026', match: '99.5% Match' },
                    { name: 'Camp_Benjamin_OR9918.pdf', size: '2.1 MB', date: 'Aug 14, 2026', match: '99.9% Match' }
                ],
                verifier: 'Engr. OSO Officer Desk / OVCAA Audit',
                verifiedDate: 'Aug 18, 2026 · 11:00 AM',
                remarks: '"CHED endorsement compliance, insurance policies, and transportation receipts are 100% verified. Liquidation tranche 1 audited successfully."',
                hash: '0x3c81e9fa22d0b67489ac8715b630e2f91d84b721',
                timeline: [
                    { title: 'Tranche 1 Liquidation Verified by OSO', date: 'Aug 18, 2026 · 11:00 AM', green: true },
                    { title: 'CHED Compliance Waiver Packet Audited', date: 'Aug 14, 2026 · 03:30 PM', green: true },
                    { title: 'Bus Charter & Venue Invoices Uploaded', date: 'Aug 13, 2026 · 09:15 AM', green: false },
                    { title: 'Approved Budget Released: ₱75,000', date: 'Aug 01, 2026 · 10:00 AM', green: false }
                ]
            },
            wellness: {
                orgName: 'Red Cross Youth (RCY BatStateU)',
                orgCategory: 'Non-Academic & Civic',
                actName: 'Campus Wellness Week',
                actType: 'Health, Safety & Mental Wellness Caravan',
                actDate: 'May 18–20, 2026',
                actVenue: 'University Gymnasium & Clinic Quad',
                scope: 'In-Campus',
                approvedBudget: 42500,
                actualExpenses: 24900,
                remainingBal: 17600,
                utilRate: 58.6,
                rateStatus: 'Healthy Buffer',
                rateStatusColor: '#2563eb',
                stepperStep: 3,
                categories: ['Equipment & Audio', 'Supplies & First Aid', 'Refreshments'],
                donutData: [14500, 6400, 4000],
                donutColors: ['#8b1828', '#16a34a', '#ca8a04'],
                barAllocated: [20000, 12500, 10000],
                barActual: [14500, 6400, 4000],
                expenses: [
                    { cat: 'Equipment & Audio', desc: 'Stage Sound System & Acoustic Setup', date: 'May 18, 2026', qty: '1 set', amount: 14500, status: 'OCR Verified', receiptFile: 'Sound_System_OR2991.pdf' },
                    { cat: 'Supplies & First Aid', desc: 'Medical Diagnostic Consumables & Blood Drive Kits', date: 'May 19, 2026', qty: '120 kits', amount: 6400, status: 'OCR Verified', receiptFile: 'Medical_Supplies_OR4481.pdf' },
                    { cat: 'Refreshments', desc: 'Hydration Station Drinks & Donor Tokens', date: 'May 20, 2026', qty: '150 pax', amount: 4000, status: 'Audited by OSO', receiptFile: 'Hydration_OR5502.pdf' }
                ],
                documents: [
                    { name: 'Sound_System_OR2991.pdf', size: '1.2 MB', date: 'May 19, 2026', match: '99.7% Match' },
                    { name: 'Medical_Supplies_OR4481.pdf', size: '1.5 MB', date: 'May 20, 2026', match: '100% Match' },
                    { name: 'Hydration_OR5502.pdf', size: '890 KB', date: 'May 21, 2026', match: '99.2% Match' }
                ],
                verifier: 'Sustainable Development Office (SDO) / OSO',
                verifiedDate: 'May 25, 2026 · 09:30 AM',
                remarks: '"Waste management protocols and medical supply liquidations verified with 100% compliance. Remaining buffer of ₱17,600 returned to revolving pool."',
                hash: '0x99a14c6e83d7120fa84bb2503e18c64188f294ab',
                timeline: [
                    { title: 'SDO & OSO Joint Financial Audit Passed', date: 'May 25, 2026 · 09:30 AM', green: true },
                    { title: 'Medical Kits & Hydration Receipts Uploaded', date: 'May 21, 2026 · 04:45 PM', green: true },
                    { title: 'Sound System Service Completed', date: 'May 18, 2026 · 08:00 AM', green: false },
                    { title: 'Approved Budget Allocation Released (₱42,500)', date: 'May 05, 2026 · 10:30 AM', green: false }
                ]
            },
            volunteer: {
                orgName: 'Assoc. of Electronics Eng. Students (AECES)',
                orgCategory: 'Academic Org',
                actName: 'Volunteer Appreciation Day',
                actType: 'Community Extension & Volunteer Recognition',
                actDate: 'March 14, 2026',
                actVenue: 'Audio-Visual Center (AVC)',
                scope: 'In-Campus',
                approvedBudget: 12500,
                actualExpenses: 12500,
                remainingBal: 0,
                utilRate: 100.0,
                rateStatus: 'Optimal',
                rateStatusColor: '#16a34a',
                stepperStep: 4,
                categories: ['Food & Catering', 'Tokens & Awards', 'Supplies'],
                donutData: [7500, 3200, 1800],
                donutColors: ['#8b1828', '#ca8a04', '#16a34a'],
                barAllocated: [7500, 3200, 1800],
                barActual: [7500, 3200, 1800],
                expenses: [
                    { cat: 'Food & Catering', desc: 'Catering & Packed Lunches for Volunteers', date: 'Mar 14, 2026', qty: '60 packs', amount: 7500, status: 'OCR Verified', receiptFile: 'Catering_Receipt_OR8812.pdf' },
                    { cat: 'Tokens & Awards', desc: 'Wooden Plaque Awards & Certificates', date: 'Mar 12, 2026', qty: '45 pcs', amount: 3200, status: 'OCR Verified', receiptFile: 'Plaques_OR3301.pdf' },
                    { cat: 'Supplies', desc: 'Ribbons, Badges & Program Handouts', date: 'Mar 13, 2026', qty: '1 set', amount: 1800, status: 'Audited by OSO', receiptFile: 'Supplies_OR1102.pdf' }
                ],
                documents: [
                    { name: 'Catering_Receipt_OR8812.pdf', size: '1.1 MB', date: 'Mar 15, 2026', match: '100% Match' },
                    { name: 'Plaques_OR3301.pdf', size: '940 KB', date: 'Mar 14, 2026', match: '99.5% Match' }
                ],
                verifier: 'Engr. OSO Officer Desk',
                verifiedDate: 'Mar 18, 2026 · 03:00 PM',
                remarks: '"Volunteer token liquidation and catering receipts 100% reconciled against attendance rosters."',
                hash: '0x17b38d99c402ef81a533b679102c488f2190a4bc',
                timeline: [
                    { title: 'Liquidation Approved & Certified Closed', date: 'Mar 18, 2026 · 03:00 PM', green: true },
                    { title: 'Receipts & Attendance Log Verified', date: 'Mar 15, 2026 · 11:15 AM', green: true },
                    { title: 'Approved Budget Released: ₱12,500', date: 'Mar 01, 2026 · 09:00 AM', green: false }
                ]
            },
            sportsfest: {
                orgName: 'Supreme Student Council (SSC)',
                orgCategory: 'University Council',
                actName: 'BatStateU Sportsfest 2026',
                actType: 'Intramural Athletics & Tournament',
                actDate: 'September 22–26, 2026',
                actVenue: 'University Track & Field Oval',
                scope: 'In-Campus',
                approvedBudget: 40000,
                actualExpenses: 20000,
                remainingBal: 20000,
                utilRate: 50.0,
                rateStatus: 'In Execution',
                rateStatusColor: '#2563eb',
                stepperStep: 2,
                categories: ['Sports Equipment', 'Hydration & Medics', 'Medals & Trophies'],
                donutData: [10000, 6000, 4000],
                donutColors: ['#8b1828', '#ca8a04', '#16a34a'],
                barAllocated: [18000, 12000, 10000],
                barActual: [10000, 6000, 4000],
                expenses: [
                    { cat: 'Sports Equipment', desc: 'Basketballs, Volley Nets & Scoreboards Rental', date: 'Sep 22, 2026', qty: '1 set', amount: 10000, status: 'OCR Verified', receiptFile: 'Sports_Rental_OR9910.pdf' },
                    { cat: 'Hydration & Medics', desc: 'Electrolyte Stations & First Aid Tents', date: 'Sep 23, 2026', qty: '200 pax', amount: 6000, status: 'OCR Verified', receiptFile: 'Hydration_OR8801.pdf' },
                    { cat: 'Medals & Trophies', desc: 'Championship Cups & Gold/Silver/Bronze Medals', date: 'Sep 20, 2026', qty: '65 pcs', amount: 4000, status: 'Audited by OSO', receiptFile: 'Trophies_OR2291.pdf' }
                ],
                documents: [
                    { name: 'Sports_Rental_OR9910.pdf', size: '1.6 MB', date: 'Sep 24, 2026', match: '99.6% Match' },
                    { name: 'Trophies_OR2291.pdf', size: '1.2 MB', date: 'Sep 22, 2026', match: '100% Match' }
                ],
                verifier: 'Sports & Student Affairs / OSO',
                verifiedDate: 'Sep 28, 2026 · 04:00 PM',
                remarks: '"Tranche 1 sports equipment and tournament medical supplies liquidation verified successfully."',
                hash: '0x44d188ac29b107ef8933b4918230fa672199b081',
                timeline: [
                    { title: 'Tranche 1 Verified & Logged', date: 'Sep 28, 2026 · 04:00 PM', green: true },
                    { title: 'Equipment Invoices Encoded', date: 'Sep 24, 2026 · 01:20 PM', green: false },
                    { title: 'Approved Budget Allocation Released (₱40,000)', date: 'Sep 10, 2026 · 10:00 AM', green: false }
                ]
            },
            all: {
                orgName: 'Batangas State University (Recognized Org Network)',
                orgCategory: 'Consolidated Institutional Portfolio',
                actName: 'All Accredited Activities (5 Projects Combined)',
                actType: 'Multi-Activity Portfolio Operations',
                actDate: 'Full Academic Year 2025–2026',
                actVenue: 'Institutional Network Venues',
                scope: 'University-Wide (4 IC · 1 OC)',
                approvedBudget: 185000,
                actualExpenses: 115150,
                remainingBal: 69850,
                utilRate: 62.2,
                rateStatus: 'Optimal & Compliant',
                rateStatusColor: '#16a34a',
                stepperStep: 4,
                categories: ['Equipment & Audio', 'Transportation', 'Food & Catering', 'Supplies & Kits', 'Honoraria & Plaq'],
                donutData: [32500, 24000, 25750, 23900, 9000],
                donutColors: ['#8b1828', '#ca8a04', '#16a34a', '#1d4ed8', '#7e22ce'],
                barAllocated: [46000, 35000, 42000, 42000, 20000],
                barActual: [32500, 24000, 25750, 23900, 9000],
                expenses: [
                    { cat: 'Equipment & Audio', desc: 'Innovation Fair & Sportsfest AV Sound Systems', date: 'Jul–Sep 2026', qty: '4 events', amount: 32500, status: 'OCR Verified', receiptFile: 'Consolidated_AV_Receipts.pdf' },
                    { cat: 'Transportation', desc: 'Leadership Summit Bus Charters & Logistics', date: 'Aug 2026', qty: '2 buses', amount: 24000, status: 'OCR Verified', receiptFile: 'Bus_Charter_OR7712.pdf' },
                    { cat: 'Food & Catering', desc: 'Meals, Buffets & Volunteer Hydration Kits', date: 'May–Sep 2026', qty: '500 pax', amount: 25750, status: 'OCR Verified', receiptFile: 'Catering_Ledger_Verified.pdf' },
                    { cat: 'Supplies & Kits', desc: 'Tarpaulins, Medical Kits & First Aid Consumables', date: 'Mar–Sep 2026', qty: '350 units', amount: 23900, status: 'Audited by OSO', receiptFile: 'Procurement_Vouchers_2026.pdf' },
                    { cat: 'Honoraria & Plaq', desc: 'Keynote Speaker Honoraria & Trophies', date: 'Mar–Aug 2026', qty: '12 items', amount: 9000, status: 'Signed Vouchers', receiptFile: 'Honorarium_Plaques_Ledger.pdf' }
                ],
                documents: [
                    { name: 'Consolidated_Financial_Audit_AY2025_2026.pdf', size: '4.8 MB', date: 'Sep 28, 2026', match: 'Certified' },
                    { name: 'Official_BIR_Receipts_Tranche_1_2.pdf', size: '8.2 MB', date: 'Sep 28, 2026', match: '100% Audited' },
                    { name: 'CHED_Local_OffCampus_Certificate.pdf', size: '2.4 MB', date: 'Aug 10, 2026', match: 'Verified' }
                ],
                verifier: 'Engr. OSO Officer Desk / Chief Auditor',
                verifiedDate: 'Sep 30, 2026 · 05:00 PM',
                remarks: '"Institutional audit complete for all 5 student organization activity portfolios. 100% transparency verification achieved with zero liquidation deficits."',
                hash: '0x7f4a9b2c18d09e3a6541f87c2901b54a883e619d',
                timeline: [
                    { title: 'University Financial Ledger Reconciled & Audited', date: 'Sep 30, 2026 · 05:00 PM', green: true },
                    { title: 'All 5 Activities Completed Tranche Liquidations', date: 'Sep 28, 2026 · 04:00 PM', green: true },
                    { title: 'Mid-Year Comprehensive Audit Check Passed', date: 'Jul 15, 2026 · 02:00 PM', green: true },
                    { title: 'Institutional Budget Fund Allocation Released (₱185,000)', date: 'Feb 01, 2026 · 09:00 AM', green: false }
                ]
            }
        };

        let donutChartInstance = null;
        let barChartInstance = null;

        function initBudgetCharts() {
            // 1. Donut Chart Initialization
            const ctxDonut = document.getElementById('expenseDonutChart').getContext('2d');
            donutChartInstance = new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: budgetDataset.innovation.categories,
                    datasets: [{
                        data: budgetDataset.innovation.donutData,
                        backgroundColor: budgetDataset.innovation.donutColors,
                        borderWidth: 2.5,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1a1618',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ` ₱${ctx.parsed.toLocaleString()} (${((ctx.parsed / ctx.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1)}%)`
                            }
                        }
                    }
                }
            });

            // 2. Bar Chart Initialization (Budget vs Actual)
            const ctxBar = document.getElementById('budgetVsActualBarChart').getContext('2d');
            barChartInstance = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: budgetDataset.innovation.categories,
                    datasets: [
                        {
                            label: 'Approved Budget',
                            data: budgetDataset.innovation.barAllocated,
                            backgroundColor: 'rgba(202, 138, 4, 0.75)',
                            borderColor: '#ca8a04',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            maxBarThickness: 28
                        },
                        {
                            label: 'Actual Expenses',
                            data: budgetDataset.innovation.barActual,
                            backgroundColor: '#8b1828',
                            borderColor: '#6f1020',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            maxBarThickness: 28
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: { family: 'inherit', size: 11, weight: '700' },
                                color: '#1a1618'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1a1618',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ` ${ctx.dataset.label}: ₱${ctx.parsed.y.toLocaleString()}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10.5, weight: '600' }, color: '#786f73' }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f5eaec', drawBorder: false },
                            ticks: {
                                callback: (val) => '₱' + (val >= 1000 ? (val / 1000) + 'k' : val),
                                font: { size: 11 },
                                color: '#786f73'
                            }
                        }
                    }
                }
            });
        }

        function switchActivityData(key) {
            const data = budgetDataset[key] || budgetDataset.innovation;

            // 1. Organization & Activity Information
            document.getElementById('orgNameVal').textContent = data.orgName;
            document.getElementById('orgCategoryBadge').textContent = data.orgCategory;
            document.getElementById('actNameVal').textContent = data.actName;
            document.getElementById('actTypeVal').textContent = data.actType;
            document.getElementById('actDateVal').textContent = data.actDate;
            document.getElementById('actVenueVal').textContent = data.actVenue;
            document.getElementById('actScopePill').textContent = data.scope;

            // 2. Summary KPIs & Rate
            const appBudgetEl = document.getElementById('kpiApprovedBudget');
            if (appBudgetEl) appBudgetEl.textContent = '₱' + data.approvedBudget.toLocaleString();

            const actExpEl = document.getElementById('kpiActualExpenses');
            if (actExpEl) actExpEl.textContent = '₱' + data.actualExpenses.toLocaleString();

            const remBalEl = document.getElementById('kpiRemainingBal');
            if (remBalEl) remBalEl.textContent = '₱' + data.remainingBal.toLocaleString();

            const utilRateEl = document.getElementById('kpiUtilRate');
            if (utilRateEl) utilRateEl.textContent = data.utilRate.toFixed(1) + '%';

            const expCountBadge = document.getElementById('kpiExpenseCountBadge');
            if (expCountBadge) expCountBadge.textContent = data.expenses.length + ' Line Items';

            const fill = document.getElementById('kpiUtilProgressFill');
            if (fill) {
                fill.style.width = Math.min(data.utilRate, 100) + '%';
                fill.style.background = data.rateStatusColor || '#0284c7';
            }

            const rateBadge = document.getElementById('kpiRateStatusBadge');
            if (rateBadge) {
                rateBadge.textContent = data.rateStatus;
            }

            // 3. Workflow Stepper
            const stepTrack = document.getElementById('workflowStepperTrack');
            const stepVerified = document.getElementById('stepVerified');
            const stepRev = document.getElementById('stepRevision');
            const stepperBadge = document.getElementById('stepperCurrentBadge');

            if (data.stepperStep >= 4) {
                stepVerified.className = 'org-step-item is-done';
                stepVerified.innerHTML = '<div class="org-step-circle"><i class="bi bi-check-lg"></i></div><div class="org-step-title">4. Verified</div><div class="org-step-desc">Audit Signed Off</div>';
                stepRev.className = 'org-step-item is-active';
                stepRev.innerHTML = '<div class="org-step-circle"><i class="bi bi-shield-check"></i></div><div class="org-step-title">5. Final Settlement</div><div class="org-step-desc">Ledger Sealed</div>';
                stepperBadge.className = 'org-info-pill-badge';
                stepperBadge.style = 'background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;';
                stepperBadge.innerHTML = '<i class="bi bi-patch-check-fill"></i> Verified &amp; Audited';
            } else if (data.stepperStep === 3) {
                stepVerified.className = 'org-step-item is-active';
                stepVerified.innerHTML = '<div class="org-step-circle"><i class="bi bi-hourglass-split"></i></div><div class="org-step-title">4. Under Review</div><div class="org-step-desc">Audit In Progress</div>';
                stepRev.className = 'org-step-item';
                stepRev.innerHTML = '<div class="org-step-circle">5</div><div class="org-step-title">5. Final Settlement</div><div class="org-step-desc">Pending Clearance</div>';
                stepperBadge.className = 'org-info-pill-badge';
                stepperBadge.style = 'background: #fefce8; color: #b45309; border-color: #fef08a;';
                stepperBadge.innerHTML = '<i class="bi bi-clock-history"></i> Verification In Progress';
            } else {
                stepVerified.className = 'org-step-item';
                stepVerified.innerHTML = '<div class="org-step-circle">4</div><div class="org-step-title">4. Verification</div><div class="org-step-desc">Awaiting Submission</div>';
                stepRev.className = 'org-step-item';
                stepRev.innerHTML = '<div class="org-step-circle">5</div><div class="org-step-title">5. Final Settlement</div><div class="org-step-desc">Pending</div>';
                stepperBadge.className = 'org-info-pill-badge';
                stepperBadge.style = 'background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe;';
                stepperBadge.innerHTML = '<i class="bi bi-pencil-square"></i> In Execution';
            }

            // 4. Update Donut Chart & Custom Legend
            if (donutChartInstance) {
                donutChartInstance.data.labels = data.categories;
                donutChartInstance.data.datasets[0].data = data.donutData;
                donutChartInstance.data.datasets[0].backgroundColor = data.donutColors;
                donutChartInstance.update();
            }

            const totalDonutSum = data.donutData.reduce((a, b) => a + b, 0);
            document.getElementById('donutTotalBadge').textContent = '₱' + totalDonutSum.toLocaleString() + ' Total';
            document.getElementById('donutCenterAmount').textContent = '₱' + (totalDonutSum >= 1000 ? Math.round(totalDonutSum / 1000) + 'k' : totalDonutSum);

            const legendContainer = document.getElementById('donutCustomLegend');
            if (legendContainer) {
                legendContainer.innerHTML = data.categories.map((cat, i) => {
                    const amt = data.donutData[i];
                    const pct = totalDonutSum ? ((amt / totalDonutSum) * 100).toFixed(1) : '0.0';
                    return `
                        <div class="org-legend-row">
                            <div class="org-legend-left">
                                <span class="org-legend-color-dot" style="background: ${data.donutColors[i] || '#8b1828'};"></span>
                                <span>${cat}</span>
                            </div>
                            <div class="org-legend-right">₱${amt.toLocaleString()} (${pct}%)</div>
                        </div>
                    `;
                }).join('');
            }

            // 5. Update Bar Chart
            if (barChartInstance) {
                barChartInstance.data.labels = data.categories;
                barChartInstance.data.datasets[0].data = data.barAllocated;
                barChartInstance.data.datasets[1].data = data.barActual;
                barChartInstance.update();
            }

            // 6. Update Expense Details Data Table
            const tbody = document.getElementById('expenseDetailsTableBody');
            if (tbody) {
                tbody.innerHTML = data.expenses.map(exp => `
                    <tr>
                        <td>
                            <span class="org-cat-badge">
                                <i class="bi bi-tag-fill"></i> ${exp.cat}
                            </span>
                        </td>
                        <td>
                            <strong>${exp.desc}</strong>
                        </td>
                        <td><span style="color: #554d50; font-size: 0.8rem;">${exp.date}</span></td>
                        <td><span style="font-weight: 600;">${exp.qty}</span></td>
                        <td><strong style="color: #1a1618;">₱${exp.amount.toLocaleString()}</strong></td>
                        <td>
                            <span class="org-receipt-link-pill" onclick="previewReceiptModal('${exp.receiptFile}', '${exp.desc}', '₱${exp.amount.toLocaleString()}')">
                                <i class="bi bi-file-earmark-check"></i> ${exp.status}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="org-file-action-btn" onclick="previewReceiptModal('${exp.receiptFile}', '${exp.desc}', '₱${exp.amount.toLocaleString()}')">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            // 7. Update Supporting Documents
            const docsList = document.getElementById('supportingDocsList');
            document.getElementById('docsCountBadge').textContent = data.documents.length + ' Files';
            if (docsList) {
                docsList.innerHTML = data.documents.map(doc => `
                    <div class="org-file-item">
                        <div class="org-file-left">
                            <div class="org-file-icon">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                            </div>
                            <div class="org-file-meta">
                                <strong title="${doc.name}">${doc.name}</strong>
                                <small>${doc.size} · ${doc.date} · <span style="color: #16a34a; font-weight: 700;">${doc.match}</span></small>
                            </div>
                        </div>
                        <button type="button" class="org-file-action-btn" onclick="alert('Viewing file: ' + '${doc.name}')">
                            <i class="bi bi-download"></i>
                        </button>
                    </div>
                `).join('');
            }

            // 8. Update Verification Details
            document.getElementById('verifierName').textContent = data.verifier;
            document.getElementById('verifiedDate').textContent = data.verifiedDate;
            document.getElementById('auditRemarksText').textContent = data.remarks;
            document.getElementById('auditHashVal').textContent = data.hash;

            // 9. Update Transaction Timeline
            const timeline = document.getElementById('transactionTimeline');
            if (timeline) {
                timeline.innerHTML = data.timeline.map(t => `
                    <div class="org-timeline-item ${t.green ? 'is-green' : ''}">
                        <strong>${t.title}</strong>
                        <small>${t.date}</small>
                    </div>
                `).join('');
            }
        }

        function filterExpenseTable() {
            const query = document.getElementById('expenseTableSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#expenseDetailsTableBody tr');
            rows.forEach(tr => {
                const text = tr.innerText.toLowerCase();
                tr.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function updateFilterPeriod() {
            const yr = document.getElementById('budgetYearSelector').value;
            const term = document.getElementById('budgetTermSelector').value;
            document.getElementById('orgAyVal').textContent = yr;
            document.getElementById('orgPeriodVal').textContent = term;
        }

        function previewReceiptModal(filename, desc, amount) {
            alert('Receipt Document Viewer\n\nFile: ' + filename + '\nDescription: ' + desc + '\nAmount Liquidated: ' + amount + '\n\nVerification: 100% Cryptographic Match against OrgChain Audit Ledger.');
        }

        document.addEventListener('DOMContentLoaded', function () {
            initBudgetCharts();
            switchActivityData('innovation');
        });
    </script>
@endsection
