@extends('org.layout')

@section('title', 'Analytics & Financial Intelligence')

@section('header')
    <h1><strong>Activity Performance and Reporting insights</h1>
    <p class="org-welcome">Comprehensive budget utilization, allocation trends, and activity financial breakdowns.</p>
@endsection

@section('actions')
    <button type="button" class="org-btn org-btn-primary org-btn-export-top" onclick="alert('Exporting Financial & Analytics Report for ' + document.getElementById('yearFilter').value + '...')">
        <i class="bi bi-download"></i> Export Report
    </button>
@endsection

@section('content')
    <style>
        /* Connected Filter Toolbar (Matching Mockup) */
        .org-connected-filter-bar {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #eae0e2;
            padding: 0.5rem 0.85rem 0.5rem 1.15rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(90, 15, 30, 0.04);
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .org-filter-controls-group {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .org-filter-calendar-badge {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #e0f2fe;
            color: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-right: 1rem;
            border: 1.5px solid #bae6fd;
            flex-shrink: 0;
        }

        .org-filter-segment {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            position: relative;
            font-size: 0.92rem;
            font-weight: 500;
            color: #4b4548;
        }

        .org-filter-label {
            color: #3b3336;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .org-filter-dropdown {
            appearance: none;
            -webkit-appearance: none;
            background: transparent;
            border: none;
            outline: none;
            font-family: inherit;
            font-size: 0.92rem;
            font-weight: 700;
            color: #1a1618;
            cursor: pointer;
            padding-right: 1.15rem;
        }

        .org-select-chevron {
            font-size: 0.72rem;
            color: #7a7074;
            pointer-events: none;
            margin-left: -0.85rem;
        }

        .org-filter-divider {
            width: 1.5px;
            height: 22px;
            background: #e2d8da;
            margin: 0 1.25rem;
            display: inline-block;
        }

        .org-btn-export-bar {
            background: #4a0d18;
            color: #ffffff;
            padding: 0.55rem 1.35rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.86rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.15s ease;
            box-shadow: 0 3px 12px rgba(74, 10, 21, 0.25);
        }

        .org-btn-export-bar:hover {
            background: #6a1020;
            transform: translateY(-1px);
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

        /* Health Scorecard */
        .org-health-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .org-health-item {
            background: #ffffff;
            border: 1.5px solid #f0e6e8;
            border-radius: 18px;
            padding: 1.35rem 1.4rem;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.02);
            position: relative;
            overflow: hidden;
        }

        .org-health-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #8b1828;
        }

        .org-health-item.is-green::before { background: #16a34a; }
        .org-health-item.is-blue::before { background: #2563eb; }
        .org-health-item.is-amber::before { background: #d97706; }

        .org-health-item span.lbl {
            font-size: 0.82rem;
            font-weight: 600;
            color: #635b5e;
            display: block;
            margin-bottom: 0.35rem;
        }

        .org-health-item strong.val {
            font-size: 1.55rem;
            font-weight: 800;
            color: #1a1618;
            display: block;
            letter-spacing: -0.02em;
        }

        .org-health-item small.sub {
            font-size: 0.78rem;
            color: #7a7074;
            display: block;
            margin-top: 0.3rem;
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

    {{-- Top Connected Filter Bar (Matching Mockup) --}}
    <div class="org-connected-filter-bar">
        <div class="org-filter-controls-group">
            <div class="org-filter-calendar-badge">
                <i class="bi bi-calendar2-date"></i>
            </div>
            
            <div class="org-filter-segment">
                <span class="org-filter-label">Year:</span>
                <select id="yearFilter" class="org-filter-dropdown" onchange="updateAnalyticsData()">
                    <option value="Select Year" selected>Select Year</option>
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                </select>
                <i class="bi bi-chevron-down org-select-chevron"></i>
            </div>

            <span class="org-filter-divider"></span>

            <div class="org-filter-segment">
                <span class="org-filter-label">Month:</span>
                <select id="monthFilter" class="org-filter-dropdown" onchange="updateAnalyticsData()">
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
                <i class="bi bi-chevron-down org-select-chevron"></i>
            </div>

            <span class="org-filter-divider"></span>

            <div class="org-filter-segment">
                <span class="org-filter-label">Scope:</span>
                <select id="scopeFilter" class="org-filter-dropdown" onchange="updateAnalyticsData()">
                    <option value="all" selected>All Scopes</option>
                    <option value="in_campus" >In-Campus</option>
                    <option value="local_off_campus">Off-Campus</option>
                </select>
                <i class="bi bi-chevron-down org-select-chevron"></i>
            </div>
        </div>

       
    </div>

    {{-- 1. Overall Budget Health KPIs --}}
    <div class="org-health-grid">
        <div class="org-health-item is-green">
            <span class="lbl"><i class="bi bi-check-circle-fill" style="color:#16a34a; margin-right: 0.2rem;"></i> Overall Budget Health</span>
            <strong class="val" id="kpiHealthScore">{{ $overview['healthScore'] }}% · Optimal</strong>
            <small class="sub">Low financial risk & compliant</small>
        </div>
        <div class="org-health-item">
            <span class="lbl"><i class="bi bi-wallet2" style="color:#8b1828; margin-right: 0.2rem;"></i> Total Budget Allocated</span>
            <strong class="val" id="kpiAllocated">₱{{ number_format($overview['totalAllocated']) }}</strong>
            <small class="sub">Approved institutional allocation</small>
        </div>
        <div class="org-health-item is-blue">
            <span class="lbl"><i class="bi bi-pie-chart-fill" style="color:#2563eb; margin-right: 0.2rem;"></i> Total Budget Utilized</span>
            <strong class="val" id="kpiUtilized">₱{{ number_format($overview['totalUtilized']) }}</strong>
            <small class="sub" id="kpiBurnRate">{{ $overview['burnRate'] }}% burn rate to date</small>
        </div>
        <div class="org-health-item is-amber">
            <span class="lbl"><i class="bi bi-cash-coin" style="color:#d97706; margin-right: 0.2rem;"></i> Remaining Reserve</span>
            <strong class="val" id="kpiRemaining" style="color: #dc2626;">₱{{ number_format($overview['remainingBalance']) }}</strong>
            <small class="sub">37.8% buffer for remaining terms</small>
        </div>
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

        function updateAnalyticsData() {
            const year = document.getElementById('yearFilter').value;
            const month = document.getElementById('monthFilter').value;
            const scope = document.getElementById('scopeFilter').value;

            document.getElementById('chartPeriodBadge').innerText = 'FY ' + year + ' · ' + (month === 'all' ? 'All Months' : month);

            // Filter Table rows
            const rows = document.querySelectorAll('#activityFinancialTableBody tr');
            rows.forEach(tr => {
                const trScope = tr.getAttribute('data-scope');
                const trMonth = tr.getAttribute('data-month');
                const trYear = tr.getAttribute('data-year');

                let matchScope = (scope === 'all' || trScope === scope);
                let matchMonth = (month === 'all' || trMonth === month);
                let matchYear = (trYear === year);

                if (matchScope && matchMonth && matchYear) {
                    tr.style.display = '';
                } else if (scope === 'all' && month === 'all') {
                    tr.style.display = '';
                } else {
                    tr.style.display = 'none';
                }
            });

            // Adjust chart data based on filter selection
            if (month !== 'all') {
                const idx = defaultMonthlyData.labels.indexOf(month);
                if (idx !== -1) {
                    utilVsAllocChartInstance.data.labels = [month];
                    utilVsAllocChartInstance.data.datasets[0].data = [defaultMonthlyData.allocated[idx]];
                    utilVsAllocChartInstance.data.datasets[1].data = [defaultMonthlyData.utilized[idx]];
                }
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

