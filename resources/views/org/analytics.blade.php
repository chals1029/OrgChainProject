@extends('org.layout')

@section('title', 'Analytics & Financial Intelligence')

@section('header')
    <h1><strong>Activity Performance and Reporting insights</strong></h1>
    <p class="org-welcome">Comprehensive budget utilization, allocation trends, and activity financial breakdowns.</p>
@endsection

@section('actions')
    <button type="button" class="org-btn org-btn-primary org-btn-export-top" onclick="alert('Exporting Financial & Analytics Report for ' + document.getElementById('yearFilter').value + '...')">
        <i class="bi bi-download"></i> Export Report
    </button>
@endsection

@section('content')
    <style>
        /* ---------------------------------------------------------
           Interactive Dashboard Period & Date Filter Toolbar (Unslop & Impeccable Style)
           --------------------------------------------------------- */
        .oso-filter-bar-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 0.75rem 1.15rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            flex-wrap: nowrap;
            margin-bottom: 1.25rem;
        }

        .oso-filter-bar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: nowrap;
            flex: 1;
            min-width: 0;
        }

        .oso-filter-bar-title {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.82rem;
            font-weight: 800;
            color: #8b1828;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-right: 0.15rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .oso-filter-group {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-shrink: 0;
        }

        .oso-filter-label {
            font-size: 0.74rem;
            font-weight: 700;
            color: #706569;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .oso-select-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .oso-filter-select {
            appearance: none;
            -webkit-appearance: none;
            background: #fdfafb;
            border: 1.5px solid #f0e0e3;
            border-radius: 9999px;
            padding: 0.36rem 1.85rem 0.36rem 0.82rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #2b2427;
            cursor: pointer;
            outline: none;
            font-family: inherit;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            white-space: nowrap;
        }

        .oso-filter-select:hover {
            background: #ffffff;
            border-color: #8b1828;
        }

        .oso-filter-select:focus {
            background: #ffffff;
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.12);
        }

        .oso-select-arrow {
            position: absolute;
            right: 0.7rem;
            pointer-events: none;
            font-size: 0.65rem;
            color: #8b1828;
            transition: transform 0.15s ease;
        }

        .oso-filter-bar-right {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            flex-shrink: 0;
        }

        .oso-filter-reset-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            padding: 0;
            border-radius: 50%;
            background: #faf4f5;
            border: 1.5px solid #ebd5d8;
            color: #7a2030;
            font-size: 0.92rem;
            cursor: pointer;
            transition: all 0.18s ease;
            flex-shrink: 0;
        }

        .oso-filter-reset-btn:hover {
            background: #8b1828;
            color: #ffffff;
            border-color: #8b1828;
            box-shadow: 0 2px 8px rgba(139, 24, 40, 0.2);
            transform: rotate(-45deg);
        }

        .oso-filter-reset-btn:hover i {
            transform: rotate(-180deg);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .oso-filter-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .oso-filter-status-badge.is-filtered {
            background: #fefce8;
            color: #b45309;
            border-color: #fef08a;
        }

        .org-btn-export-top {
            box-shadow: 0 4px 14px rgba(139, 24, 40, 0.25);
        }

        /* Analytics Card Layout */
        .org-analytics-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.75rem;
        }

        .org-analytics-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #f0e6e8;
            padding: 1.75rem 2rem;
            box-shadow: 0 6px 24px rgba(90, 15, 30, 0.03);
            margin-bottom: 1.75rem;
        }

        .org-card-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.35rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #f6eff0;
        }

        .org-card-header-flex h2 {
            font-size: 1.18rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .org-header-badge {
            font-size: 0.76rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            background: #fdf0f2;
            color: #8b1828;
        }

        /* Health Scorecard — Matching Dashboard Liquid-Glass System & Effects */
        .org-health-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .ovcaa-stat-card {
            border-radius: 22px;
            padding: 1.15rem 1.25rem 1.2rem;
            display: grid;
            gap: 0.35rem;
            position: relative;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1.5px solid rgba(240, 230, 232, 0.95);
            box-shadow: 0 4px 20px rgba(90, 15, 30, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.3s ease;
            cursor: pointer;
            text-decoration: none !important;
            color: inherit;
        }

        .ovcaa-stat-card::after {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 3.5px;
            opacity: 0;
            transition: opacity 0.3s ease;
            background: linear-gradient(90deg, transparent, #8b1828, transparent);
        }

        .ovcaa-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 44px rgba(74, 10, 21, 0.12), 0 4px 12px rgba(74, 10, 21, 0.04);
            border-color: rgba(139, 24, 40, 0.25);
        }

        .ovcaa-stat-card:hover::after {
            opacity: 1;
        }

        .ovcaa-stat-card:active {
            transform: translateY(-2px) scale(0.99);
        }

        .ovcaa-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.25rem;
            gap: 0.5rem;
        }

        .ovcaa-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            background: linear-gradient(135deg, rgba(139, 24, 40, 0.12), rgba(139, 24, 40, 0.04));
            color: #8b1828;
            box-shadow: inset 0 0 0 1px rgba(139, 24, 40, 0.2);
        }

        .ovcaa-stat-card:hover .ovcaa-stat-icon {
            transform: scale(1.08) rotate(3deg);
        }

        .ovcaa-stat-icon.is-blue,
        .ovcaa-stat-icon.is-green,
        .ovcaa-stat-icon.is-amber,
        .ovcaa-stat-icon.is-maroon {
            background: linear-gradient(135deg, rgba(139, 24, 40, 0.12), rgba(139, 24, 40, 0.04));
            color: #8b1828;
            box-shadow: inset 0 0 0 1px rgba(139, 24, 40, 0.2);
        }

        .ovcaa-stat-top strong.val {
            font-size: clamp(1.4rem, 1.8vw, 1.85rem);
            color: #1a1618;
            letter-spacing: -0.035em;
            font-variant-numeric: tabular-nums;
            font-weight: 850;
            line-height: 1;
            margin-left: auto;
            text-align: right;
        }

        .ovcaa-stat-card span.ovcaa-stat-title {
            font-weight: 800;
            color: #1e293b;
            font-size: 0.92rem;
            letter-spacing: -0.01em;
            display: block;
        }

        /* In-Campus vs Off-Campus Split Cards */
        .org-scope-comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .org-scope-card {
            background: #fdfafb;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.5rem 1.75rem;
        }

        .org-scope-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .org-scope-head h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0;
            color: #1a1618;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .org-scope-metrics-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0e6e8;
            margin-bottom: 1rem;
        }

        .org-scope-metric-box span {
            font-size: 0.78rem;
            color: #635b5e;
            display: block;
            margin-bottom: 0.2rem;
            font-weight: 600;
        }

        .org-scope-metric-box strong {
            font-size: 1.2rem;
            font-weight: 800;
            color: #1a1618;
            display: block;
        }

        .org-scope-progress-bar {
            height: 8px;
            border-radius: 9999px;
            background: #f1e8e9;
            overflow: hidden;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .org-scope-progress-fill {
            height: 100%;
            border-radius: 9999px;
            background: #8b1828;
            transition: width 0.6s ease;
        }

        .org-scope-card.is-off-campus .org-scope-progress-fill {
            background: #d97706;
        }

        /* Financial Activity Table */
        .org-fin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }

        .org-fin-table th {
            padding: 0.75rem 1rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #7a7074;
            border-bottom: 1.5px solid #f0e6e8;
            background: #faf6f7;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .org-fin-table td {
            padding: 1rem 1rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-scope-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .org-scope-pill.is-in {
            background: #fdf0f2;
            color: #8b1828;
            border: 1px solid #f8d7dc;
        }

        .org-scope-pill.is-off {
            background: #fefce8;
            color: #b45309;
            border: 1px solid #fef08a;
        }

        .chart-container-wrap {
            position: relative;
            height: 310px;
            width: 100%;
        }

        @media (max-width: 992px) {
            .oso-filter-bar-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .oso-filter-bar-right {
                width: 100%;
                justify-content: space-between;
                padding-top: 0.5rem;
                border-top: 1px solid #f6eff0;
            }
            .oso-filter-bar-left {
                width: 100%;
            }
            .org-health-grid {
                grid-template-columns: 1fr 1fr;
            }
            .org-analytics-grid-2 {
                grid-template-columns: 1fr;
            }
            .org-scope-comparison-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .org-health-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    {{-- Interactive Period & Date Filter Toolbar (Matching OSO Dashboard Filter Style) --}}
    <section class="oso-filter-bar-card" aria-label="Analytics Intelligence Filters">
        <div class="oso-filter-bar-left">
            <div class="oso-filter-bar-title">
                <i class="bi bi-funnel-fill"></i>
                <span>Filters:</span>
            </div>

            {{-- Academic Year Filter --}}
            <div class="oso-filter-group">
                <label for="yearFilter" class="oso-filter-label"><i class="bi bi-calendar2-range"></i> Year</label>
                <div class="oso-select-wrapper">
                    <select id="yearFilter" class="oso-filter-select" onchange="updateAnalyticsData()">
                        <option value="2026" selected>A.Y. 2025–2026 (Current)</option>
                        <option value="2025">A.Y. 2024–2025</option>
                        <option value="2024">A.Y. 2023–2024</option>
                        <option value="all">All Academic Years</option>
                    </select>
                    <i class="bi bi-chevron-down oso-select-arrow"></i>
                </div>
            </div>

            {{-- Semester Filter --}}
            <div class="oso-filter-group">
                <label for="semesterFilter" class="oso-filter-label"><i class="bi bi-bookmark"></i> Semester</label>
                <div class="oso-select-wrapper">
                    <select id="semesterFilter" class="oso-filter-select" onchange="updateAnalyticsData()">
                        <option value="all" selected>All Semesters</option>
                        <option value="sem1">1st Semester</option>
                        <option value="sem2">2nd Semester</option>
                        <option value="midyear">Midyear Term</option>
                    </select>
                    <i class="bi bi-chevron-down oso-select-arrow"></i>
                </div>
            </div>

            {{-- Month Filter --}}
            <div class="oso-filter-group">
                <label for="monthFilter" class="oso-filter-label"><i class="bi bi-calendar3"></i> Month</label>
                <div class="oso-select-wrapper">
                    <select id="monthFilter" class="oso-filter-select" onchange="updateAnalyticsData()">
                        <option value="all" selected>All Months</option>
                        <option value="Jan">January</option>
                        <option value="Feb">February</option>
                        <option value="Mar">March</option>
                        <option value="Apr">April</option>
                        <option value="May">May</option>
                        <option value="Jun">June</option>
                        <option value="Jul">July</option>
                        <option value="Aug">August</option>
                        <option value="Sep">September</option>
                        <option value="Oct">October</option>
                        <option value="Nov">November</option>
                        <option value="Dec">December</option>
                    </select>
                    <i class="bi bi-chevron-down oso-select-arrow"></i>
                </div>
            </div>

            {{-- Scope Filter --}}
            <div class="oso-filter-group">
                <label for="scopeFilter" class="oso-filter-label"><i class="bi bi-geo-alt"></i> Scope</label>
                <div class="oso-select-wrapper">
                    <select id="scopeFilter" class="oso-filter-select" onchange="updateAnalyticsData()">
                        <option value="all" selected>All Scopes</option>
                        <option value="in_campus">In-Campus Only</option>
                        <option value="local_off_campus">Off-Campus Only</option>
                    </select>
                    <i class="bi bi-chevron-down oso-select-arrow"></i>
                </div>
            </div>
        </div>

        <div class="oso-filter-bar-right">
            <button type="button" class="oso-filter-reset-btn" onclick="resetAnalyticsFilters()" title="Reset all filters" aria-label="Reset all filters">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
            <span class="oso-filter-status-badge" id="analyticsActiveFilterBadge">
                <i class="bi bi-check2-circle"></i> Live Insights
            </span>
        </div>
    </section>

    {{-- 1. Overall Budget Health KPIs (Dashboard Liquid-Glass Cards & Effects) --}}
    <div class="org-health-grid">
        {{-- Card 1: Overall Budget Health --}}
        <article class="ovcaa-stat-card liquid-glass is-green" title="Overall Budget Health: Optimal">
            <div class="ovcaa-stat-top">
                <div class="ovcaa-stat-icon is-green">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <strong class="val" id="kpiHealthScore">{{ $overview['healthScore'] }}% · Optimal</strong>
            </div>
            <span class="ovcaa-stat-title">Overall Budget Health</span>
        </article>

        {{-- Card 2: Total Budget Allocated --}}
        <article class="ovcaa-stat-card liquid-glass is-maroon" title="Total Budget Allocated">
            <div class="ovcaa-stat-top">
                <div class="ovcaa-stat-icon is-maroon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <strong class="val" id="kpiAllocated">₱{{ number_format($overview['totalAllocated']) }}</strong>
            </div>
            <span class="ovcaa-stat-title">Total Budget Allocated</span>
        </article>

        {{-- Card 3: Total Budget Utilized --}}
        <article class="ovcaa-stat-card liquid-glass is-blue" title="Total Budget Utilized">
            <div class="ovcaa-stat-top">
                <div class="ovcaa-stat-icon is-blue">
                    <i class="bi bi-pie-chart-fill"></i>
                </div>
                <strong class="val" id="kpiUtilized">₱{{ number_format($overview['totalUtilized']) }}</strong>
            </div>
            <span class="ovcaa-stat-title">Total Budget Utilized</span>
        </article>

        {{-- Card 4: Remaining Reserve --}}
        <article class="ovcaa-stat-card liquid-glass is-amber" title="Remaining Reserve">
            <div class="ovcaa-stat-top">
                <div class="ovcaa-stat-icon is-amber">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <strong class="val" id="kpiRemaining">₱{{ number_format($overview['remainingBalance']) }}</strong>
            </div>
            <span class="ovcaa-stat-title">Remaining Reserve</span>
        </article>
    </div>

    {{-- 2. Budget Utilization vs Budget Allocation Graph & Trend Analysis --}}
    <div class="org-analytics-grid-2">
        {{-- Card: Budget Utilization vs Budget Allocation --}}
        <div class="org-analytics-card" style="margin-bottom: 0;">
            <div class="org-card-header-flex">
                <h2>
                    <span class="org-card-icon" style="background:#fdf0f2; color:#8b1828; width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;">
                        <i class="bi bi-bar-chart-fill"></i>
                    </span>
                    Budget Utilization vs Allocation
                </h2>
                <span class="org-header-badge" id="chartPeriodBadge">FY 2026 · All Months</span>
            </div>
            <div class="chart-container-wrap">
                <canvas id="utilVsAllocChart"></canvas>
            </div>
        </div>

        {{-- Card: Trend Analysis (Fund Allocations & Expenditures Over Time) --}}
        <div class="org-analytics-card" style="margin-bottom: 0;">
            <div class="org-card-header-flex">
                <h2>
                    <span class="org-card-icon" style="background:#fdf0f2; color:#8b1828; width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;">
                        <i class="bi bi-graph-up-arrow"></i>
                    </span>
                    Trend Analysis: Funds Over Time
                </h2>
                <span class="org-header-badge">Cumulative Spending</span>
            </div>
            <div class="chart-container-wrap">
                <canvas id="trendAnalysisChart"></canvas>
            </div>
        </div>
    </div>

    {{-- 3. In-Campus and Off-Campus Activity Financial Overview --}}
    <div class="org-analytics-card" style="margin-top: 1.75rem;">
        <div class="org-card-header-flex">
            <h2>
                <span class="org-card-icon" style="background:#fdf0f2; color:#8b1828; width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;">
                    <i class="bi bi-building-check"></i>
                </span>
                In-Campus & Off-Campus Financial Overview
            </h2>
            <span class="org-header-badge">Scope Performance</span>
        </div>

        <div class="org-scope-comparison-grid">
            {{-- In-Campus Overview --}}
            <div class="org-scope-card">
                <div class="org-scope-head">
                    <h3><i class="bi bi-building"></i> In-Campus Activities</h3>
                    <span class="org-scope-pill is-in">4 Activities</span>
                </div>
                <div class="org-scope-metrics-row">
                    <div class="org-scope-metric-box">
                        <span>Allocated</span>
                        <strong>₱{{ number_format($overview['inCampusAllocated']) }}</strong>
                    </div>
                    <div class="org-scope-metric-box">
                        <span>Utilized</span>
                        <strong>₱{{ number_format($overview['inCampusUtilized']) }}</strong>
                    </div>
                    <div class="org-scope-metric-box">
                        <span>Remaining</span>
                        <strong style="color:#8b1828;">₱{{ number_format($overview['inCampusRemaining']) }}</strong>
                    </div>
                </div>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:700;">
                        <span>In-Campus Utilization</span>
                        <span style="color:#8b1828;">65.8%</span>
                    </div>
                    <div class="org-scope-progress-bar">
                        <div class="org-scope-progress-fill" style="width: 65.8%;"></div>
                    </div>
                    <small style="font-size:0.76rem; color:#635b5e;">100% compliant with university waste policies & venue protocols.</small>
                </div>
            </div>

            {{-- Off-Campus Overview --}}
            <div class="org-scope-card is-off-campus">
                <div class="org-scope-head">
                    <h3><i class="bi bi-bus-front"></i> Off-Campus Activities</h3>
                    <span class="org-scope-pill is-off">1 Activity</span>
                </div>
                <div class="org-scope-metrics-row">
                    <div class="org-scope-metric-box">
                        <span>Allocated</span>
                        <strong>₱{{ number_format($overview['offCampusAllocated']) }}</strong>
                    </div>
                    <div class="org-scope-metric-box">
                        <span>Utilized</span>
                        <strong>₱{{ number_format($overview['offCampusUtilized']) }}</strong>
                    </div>
                    <div class="org-scope-metric-box">
                        <span>Remaining</span>
                        <strong style="color:#b45309;">₱{{ number_format($overview['offCampusRemaining']) }}</strong>
                    </div>
                </div>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:700;">
                        <span>Off-Campus Utilization</span>
                        <span style="color:#b45309;">57.0%</span>
                    </div>
                    <div class="org-scope-progress-bar">
                        <div class="org-scope-progress-fill" style="width: 57.0%;"></div>
                    </div>
                    <small style="font-size:0.76rem; color:#635b5e;">CHED Compliance Report & Parent Consent Waivers 100% verified.</small>
                </div>
            </div>
        </div>

        {{-- Detailed Activity Financial Breakdown Matrix --}}
        <div style="overflow-x:auto; margin-top: 1.25rem;">
            <table class="org-fin-table">
                <thead>
                    <tr>
                        <th>Activity Title</th>
                        <th>Scope</th>
                        <th>Allocated</th>
                        <th>Utilized</th>
                        <th>Remaining</th>
                        <th>Burn Rate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="activityFinancialTableBody">
                    @foreach ($activityFinancials as $row)
                        <tr data-scope="{{ $row['scope'] }}" data-month="{{ $row['month'] }}" data-year="{{ $row['year'] }}">
                            <td>
                                <strong>{{ $row['name'] }}</strong>
                                <small style="display:block; color:#786f73; font-size:0.76rem;">Target Date: {{ $row['month'] }} {{ $row['year'] }}</small>
                            </td>
                            <td>
                                <span class="org-scope-pill {{ $row['scope'] === 'in_campus' ? 'is-in' : 'is-off' }}">
                                    {{ $row['scope_label'] }}
                                </span>
                            </td>
                            <td>₱{{ number_format($row['allocated']) }}</td>
                            <td>₱{{ number_format($row['utilized']) }}</td>
                            <td>
                                <span style="color: {{ $row['remaining'] > 0 ? '#16a34a' : '#786f73' }}; font-weight:700;">
                                    ₱{{ number_format($row['remaining']) }}
                                </span>
                            </td>
                            <td>
                                <span style="font-weight:700; color: {{ $row['burn_rate'] >= 80 ? '#8b1828' : '#2563eb' }};">
                                    {{ $row['burn_rate'] }}%
                                </span>
                            </td>
                            <td>
                                <span class="org-status-pill org-status-{{ $row['status_style'] }}">
                                    <span class="org-status-dot"></span> {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Load Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let utilVsAllocChartInstance = null;
        let trendChartInstance = null;

        const defaultMonthlyData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            allocated: [15000, 10000, 20000, 15000, 45000, 10000, 20000, 80000, 45000, 15000, 10000, 15000],
            utilized:  [12000, 8500, 18500, 11000, 32000, 7500, 15000, 48000, 24000, 8000, 4000, 9500],
            cumulativeAlloc: [15000, 25000, 45000, 60000, 105000, 115000, 135000, 215000, 260000, 275000, 285000, 300000],
            cumulativeUtil:  [12000, 20500, 39000, 50000, 82000, 89500, 104500, 152500, 176500, 184500, 188500, 198000]
        };

        function initCharts() {
            const ctx1 = document.getElementById('utilVsAllocChart').getContext('2d');
            utilVsAllocChartInstance = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: defaultMonthlyData.labels,
                    datasets: [
                        {
                            label: 'Budget Allocated (₱)',
                            data: defaultMonthlyData.allocated,
                            backgroundColor: 'rgba(224, 168, 178, 0.75)',
                            borderColor: '#c43b52',
                            borderWidth: 1.5,
                            borderRadius: 6,
                        },
                        {
                            label: 'Budget Utilized (₱)',
                            data: defaultMonthlyData.utilized,
                            backgroundColor: '#8b1828',
                            borderColor: '#6f1020',
                            borderWidth: 1.5,
                            borderRadius: 6,
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
                                boxWidth: 14,
                                font: { family: 'inherit', size: 12, weight: '600' },
                                color: '#1a1618'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) { return '₱' + (val / 1000) + 'k'; },
                                color: '#7a7074',
                                font: { family: 'inherit', size: 11 }
                            },
                            grid: { color: 'rgba(155, 27, 48, 0.05)' }
                        },
                        x: {
                            ticks: { color: '#7a7074', font: { family: 'inherit', size: 11 } },
                            grid: { display: false }
                        }
                    }
                }
            });

            const ctx2 = document.getElementById('trendAnalysisChart').getContext('2d');
            trendChartInstance = new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: defaultMonthlyData.labels,
                    datasets: [
                        {
                            label: 'Cumulative Allocation (₱)',
                            data: defaultMonthlyData.cumulativeAlloc,
                            borderColor: '#d97706',
                            backgroundColor: 'rgba(217, 119, 6, 0.06)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3
                        },
                        {
                            label: 'Cumulative Spending (₱)',
                            data: defaultMonthlyData.cumulativeUtil,
                            borderColor: '#8b1828',
                            backgroundColor: 'rgba(139, 24, 40, 0.08)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4
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
                                boxWidth: 14,
                                font: { family: 'inherit', size: 12, weight: '600' },
                                color: '#1a1618'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) { return '₱' + (val / 1000) + 'k'; },
                                color: '#7a7074',
                                font: { family: 'inherit', size: 11 }
                            },
                            grid: { color: 'rgba(155, 27, 48, 0.05)' }
                        },
                        x: {
                            ticks: { color: '#7a7074', font: { family: 'inherit', size: 11 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        function resetAnalyticsFilters() {
            document.getElementById('yearFilter').value = '2026';
            document.getElementById('semesterFilter').value = 'all';
            document.getElementById('monthFilter').value = 'all';
            document.getElementById('scopeFilter').value = 'all';
            updateAnalyticsData();
        }

        function updateAnalyticsData() {
            const year = document.getElementById('yearFilter').value;
            const semester = document.getElementById('semesterFilter').value;
            const month = document.getElementById('monthFilter').value;
            const scope = document.getElementById('scopeFilter').value;

            // Semester to months mapping
            const semesterMonths = {
                'sem1': ['Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'sem2': ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                'midyear': ['Jun', 'Jul']
            };

            // Build period badge text
            let periodText = (year === 'all' ? 'All Years' : 'FY ' + year);
            if (month !== 'all') {
                periodText += ' · ' + month;
            } else if (semester !== 'all') {
                const semNames = { 'sem1': '1st Sem', 'sem2': '2nd Sem', 'midyear': 'Midyear' };
                periodText += ' · ' + (semNames[semester] || semester);
            } else {
                periodText += ' · All Months';
            }
            const chartBadge = document.getElementById('chartPeriodBadge');
            if (chartBadge) {
                chartBadge.innerText = periodText;
            }

            // Filter Table rows
            const rows = document.querySelectorAll('#activityFinancialTableBody tr');
            let visibleCount = 0;

            rows.forEach(tr => {
                const trScope = tr.getAttribute('data-scope');
                const trMonth = tr.getAttribute('data-month');
                const trYear = tr.getAttribute('data-year');

                let matchScope = (scope === 'all' || trScope === scope || (scope === 'in_campus' && trScope === 'in_campus') || (scope === 'local_off_campus' && trScope === 'local_off_campus'));
                let matchMonth = (month === 'all' || trMonth === month);
                let matchSemester = (semester === 'all' || (semesterMonths[semester] && semesterMonths[semester].includes(trMonth)));
                let matchYear = (year === 'all' || trYear === year);

                if (matchScope && matchMonth && matchSemester && matchYear) {
                    tr.style.display = '';
                    visibleCount++;
                } else {
                    tr.style.display = 'none';
                }
            });

            // Update live filter status badge
            const statusBadge = document.getElementById('analyticsActiveFilterBadge');
            const isFiltered = (year !== '2026' || semester !== 'all' || month !== 'all' || scope !== 'all');
            if (statusBadge) {
                if (isFiltered) {
                    statusBadge.classList.add('is-filtered');
                    statusBadge.innerHTML = '<i class="bi bi-funnel-fill"></i> Filtered (' + visibleCount + ' ' + (visibleCount === 1 ? 'result' : 'results') + ')';
                } else {
                    statusBadge.classList.remove('is-filtered');
                    statusBadge.innerHTML = '<i class="bi bi-check2-circle"></i> Live Insights';
                }
            }

            // Adjust Chart Data
            if (!utilVsAllocChartInstance) return;

            if (month !== 'all') {
                const idx = defaultMonthlyData.labels.indexOf(month);
                if (idx !== -1) {
                    utilVsAllocChartInstance.data.labels = [month];
                    utilVsAllocChartInstance.data.datasets[0].data = [defaultMonthlyData.allocated[idx]];
                    utilVsAllocChartInstance.data.datasets[1].data = [defaultMonthlyData.utilized[idx]];
                }
            } else if (semester !== 'all' && semesterMonths[semester]) {
                const targetMonths = semesterMonths[semester];
                const filteredLabels = [];
                const filteredAlloc = [];
                const filteredUtil = [];

                targetMonths.forEach(m => {
                    const idx = defaultMonthlyData.labels.indexOf(m);
                    if (idx !== -1) {
                        filteredLabels.push(m);
                        filteredAlloc.push(defaultMonthlyData.allocated[idx]);
                        filteredUtil.push(defaultMonthlyData.utilized[idx]);
                    }
                });

                utilVsAllocChartInstance.data.labels = filteredLabels;
                utilVsAllocChartInstance.data.datasets[0].data = filteredAlloc;
                utilVsAllocChartInstance.data.datasets[1].data = filteredUtil;
            } else {
                utilVsAllocChartInstance.data.labels = defaultMonthlyData.labels;
                utilVsAllocChartInstance.data.datasets[0].data = defaultMonthlyData.allocated;
                utilVsAllocChartInstance.data.datasets[1].data = defaultMonthlyData.utilized;
            }
            utilVsAllocChartInstance.update();
        }

        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
        });
    </script>
@endsection

