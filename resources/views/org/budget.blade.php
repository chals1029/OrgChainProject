@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSdo = $role === 'sdo';
    $isOvcaa = $role === 'ovcaa';
    $isSo = $role === 'so';
    $canRecordExpense = $isSo;
    $selectedSemester = (string) request('semester', 'Annual');
    $approvedBudgetActivities = collect($approvedBudgetActivities ?? []);
    $defaultBudgetActivity = $approvedBudgetActivities->first() ?? '';
    $budgetOptionsForRole = collect($liveBudgetOptions ?? []);
    if ($isSo) {
        $budgetOptionsForRole = $budgetOptionsForRole
            ->reject(fn ($option) => ($option['key'] ?? null) === 'all')
            ->filter(function ($option) use ($liveBudgetEntries, $selectedYear, $selectedSemester): bool {
                $entry = data_get($liveBudgetEntries ?? [], $option['key'] ?? '');
                if (($entry['academic_year'] ?? null) !== $selectedYear) return false;
                return $selectedSemester === 'Annual' || ($entry['semester'] ?? null) === $selectedSemester;
            })
            ->values();
    }
    $budgetDefaultForRole = $isSo
        ? ($budgetOptionsForRole->first()['key'] ?? 'all')
        : ($liveBudgetDefault ?? 'all');
    $osoHasActivityRequest = $isOso && request()->has('activity_id');
    $osoSelectedActivity = null;
    $osoSelectedActivityKey = null;
    $osoNavigationFilters = [
        'academic_year' => $selectedYear,
        'semester' => $selectedSemester,
        'department' => request('department', ''),
        'organization' => $selectedOrganization ?? '',
    ];
    if ($osoHasActivityRequest
        && ($selectedOrganization ?? '') !== ''
        && is_scalar(request('activity_id'))
        && ctype_digit((string) request('activity_id'))
        && (int) request('activity_id') > 0) {
        foreach (($liveBudgetEntries ?? []) as $key => $entry) {
            if ($key === 'all'
                || (string) ($entry['activityId'] ?? '') !== (string) request('activity_id')
                || ($entry['orgName'] ?? '') !== $selectedOrganization
                || ($entry['academic_year'] ?? '') !== $selectedYear
                || ($selectedSemester !== 'Annual' && ($entry['semester'] ?? '') !== $selectedSemester)) {
                continue;
            }
            $osoSelectedActivity = $entry;
            $osoSelectedActivityKey = $key;
            break;
        }
    }
    if ($isOso) {
        $budgetDefaultForRole = $osoSelectedActivityKey ?? 'all';
    }
    $budgetPrintFilters = $isOso ? $osoNavigationFilters : request()->query();
    if ($osoSelectedActivity !== null) {
        $budgetPrintFilters['activity_id'] = $osoSelectedActivity['activityId'];
    }
@endphp

@section('title', ($isSo || $isOso) ? 'Budget Utilization' : 'Budget Utilization & Financial Auditing')

@section('header')
    <h1><strong>{{ ($isSo || $isOso) ? 'Budget Utilization' : 'Budget Utilization & Financial Intelligence' }}</strong></h1>
    @if ($isOso)
        <p class="org-welcome">Find an organization, choose an activity, then review its encoded expenses and receipts.</p>
    @elseif ($isSdo)
        <p class="org-welcome">Monitor sustainability budget disbursements, resource utilization rates, and environmental initiative expenditures.</p>
    @elseif ($isOvcaa)
        <p class="org-welcome">Executive institutional overview of university budget utilization, liquidation verifications, and compliance milestones.</p>
    @else
        <p class="org-welcome">Record, track, and liquidate actual expenses with verified receipts for your student organization activities.</p>
    @endif
@endsection

@section('actions')
    <a id="budgetPrintLink" href="{{ route('office.budget.print', $budgetPrintFilters) }}" target="_blank" rel="noopener" class="org-btn org-btn-primary" title="Open the budget utilization report">
        <i class="bi bi-file-earmark-arrow-down"></i> Print / Export Report
    </a>
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
            width: 100%;
            min-width: 0;
        }

        .org-budget-container > * {
            min-width: 0;
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


        .org-report-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-top: 0.9rem;
            padding-top: 0.8rem;
            border-top: 1px solid #f6eff0;
            color: #786f73;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .org-report-pagination-nav {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .org-report-page-btn {
            min-width: 28px;
            height: 28px;
            padding: 0 0.45rem;
            border: 1px solid #e8dadd;
            border-radius: 8px;
            background: #ffffff;
            color: #7a1222;
            font-size: 0.72rem;
            font-weight: 800;
            cursor: pointer;
        }

        .org-report-page-btn:hover:not(:disabled),
        .org-report-page-btn.is-active {
            background: #7a1222;
            border-color: #7a1222;
            color: #ffffff;
        }

        .org-report-page-btn:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .org-info-panels-grid,
            .org-budget-charts-grid {
                grid-template-columns: 1fr;
            }
            .org-kpi-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .org-report-pagination {
                align-items: flex-start;
                flex-direction: column;
            }

            .org-report-pagination-nav {
                align-self: flex-end;
            }

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
                padding: 0.9rem 1rem;
            }
            .org-budget-filter-left {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.7rem;
            }
            .org-filter-group-pill {
                width: 100%;
                align-items: flex-start;
                flex-direction: column;
                gap: 0.3rem;
            }
            .org-select-pill-wrap,
            .org-select-pill {
                width: 100%;
            }
            .org-select-pill {
                min-height: 44px;
            }
            .org-info-meta-list {
                grid-template-columns: 1fr;
            }
            .org-info-card,
            .org-stepper-card {
                padding: 1rem;
            }
        }

        .so-secondary-details {
            background: #fff;
            border: 1.5px solid #f0e6e8;
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            overflow: hidden;
        }

        .so-secondary-details > summary {
            cursor: pointer;
            list-style: none;
            padding: 0.9rem 1.1rem;
            color: #7a1222;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .so-secondary-details > summary::-webkit-details-marker {
            display: none;
        }

        .so-secondary-details > summary::before {
            content: "▸";
            display: inline-block;
            margin-right: 0.45rem;
            transition: transform 0.15s ease;
        }

        .so-secondary-details[open] > summary::before {
            transform: rotate(90deg);
        }

        .so-secondary-details > .org-budget-charts-grid {
            padding: 0 1.1rem 1.1rem;
        }

        .oso-budget-breadcrumbs {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            color: #786f73;
            font-size: 0.84rem;
            overflow-wrap: anywhere;
        }

        .oso-budget-breadcrumbs a {
            color: #8b1828;
            font-weight: 700;
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
                <div class="org-filter-group-pill" @if ($isOso) hidden style="display:none;" @endif>
                    <label for="budgetActivitySelector" class="org-filter-label-text"><i class="bi bi-bar-chart-line"></i> {{ $isSo ? 'Approved activity' : 'Portfolio / Activity' }}</label>
                    <div class="org-select-pill-wrap">
                        <select id="budgetActivitySelector" class="org-select-pill" onchange="selectBudgetActivity(this.value)">
                            @if ($budgetOptionsForRole->isNotEmpty())
                                @foreach ($budgetOptionsForRole as $opt)
                                    <option value="{{ $opt['key'] }}" @selected($budgetDefaultForRole === $opt['key'])>{{ $opt['label'] }}</option>
                                @endforeach
                            @else
                                <option value="all" selected>{{ $isSo ? 'No approved activities yet' : 'Full Institutional Org Portfolio (Consolidated)' }}</option>
                            @endif
                        </select>
                        <i class="bi bi-chevron-down org-select-pill-arrow"></i>
                    </div>
                </div>

                {{-- Organization Filter --}}
                @unless ($isSo)
                <form method="get" action="{{ route('office.budget') }}" class="org-filter-group-pill" style="margin:0;{{ $isOso ? 'display:none;' : '' }}" @if ($isOso) hidden @endif>
                    <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                    <input type="hidden" name="semester" id="budgetOrgSemester" value="{{ request('semester', 'Annual') }}">
                    <input type="hidden" name="department" value="{{ request('department', '') }}">
                    <label for="budgetOrgSelector" class="org-filter-label-text"><i class="bi bi-building"></i> Organization</label>
                    <div class="org-select-pill-wrap">
                        <select id="budgetOrgSelector" name="organization" class="org-select-pill" onchange="this.form.submit()">
                            <option value="">All Organizations</option>
                            @foreach (($organizations ?? collect()) as $orgName)
                                <option value="{{ $orgName }}" @selected(($selectedOrganization ?? '') === $orgName)>{{ $orgName }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down org-select-pill-arrow"></i>
                    </div>
                </form>
                @endunless

                {{-- Academic Year Filter --}}
                <div class="org-filter-group-pill">
                    <label for="budgetYearSelector" class="org-filter-label-text"><i class="bi bi-calendar2-range"></i> Year</label>
                    <div class="org-select-pill-wrap">
                        <select id="budgetYearSelector" class="org-select-pill" onchange="updateFilterPeriod()">
                            @if ($isOso && $osoFinancialOverview !== null)
                                @foreach ($osoFinancialOverview['years'] as $year)
                                    <option value="{{ $year }}" @selected($selectedYear === $year)>A.Y. {{ str_replace('-', '–', $year) }}</option>
                                @endforeach
                            @else
                                <option value="2025-2026">A.Y. 2025–2026</option>
                                <option value="2026-2027">A.Y. 2026–2027</option>
                                <option value="2024-2025">A.Y. 2024–2025</option>
                                <option value="2023-2024">A.Y. 2023–2024</option>
                            @endif
                        </select>
                        <i class="bi bi-chevron-down org-select-pill-arrow"></i>
                    </div>
                </div>

                {{-- Budget Period Filter --}}
                <div class="org-filter-group-pill">
                    <label for="budgetTermSelector" class="org-filter-label-text"><i class="bi bi-bookmark"></i> Period</label>
                    <div class="org-select-pill-wrap">
                        <select id="budgetTermSelector" class="org-select-pill" onchange="updateFilterPeriod()">
                            <option value="Annual" selected>Full Fiscal Year</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Midyear">Midyear</option>
                        </select>
                        <i class="bi bi-chevron-down org-select-pill-arrow"></i>
                    </div>
                </div>
            </div>

        </section>
        @if ($isOso && (($selectedOrganization ?? '') !== '' || $osoHasActivityRequest))
            <nav class="oso-budget-breadcrumbs" aria-label="Budget navigation">
                <a href="{{ route('office.budget', array_merge($osoNavigationFilters, ['organization' => ''])) }}">All organizations</a>
                @if (($selectedOrganization ?? '') !== '')
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('office.budget', $osoNavigationFilters) }}">Organization activities — {{ $selectedOrganization }}</a>
                @endif
                @if ($osoSelectedActivity !== null)
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">{{ $osoSelectedActivity['actName'] }}</span>
                @endif
            </nav>
        @endif
        @if ($osoHasActivityRequest && $osoSelectedActivity === null)
            <section class="org-info-card" aria-label="Unavailable activity">
                <h2 class="org-info-card-title">Activity unavailable</h2>
                <p>This approved activity is not available for the selected organization, academic year, and semester. Select an activity from the organization’s approved activity list.</p>
                <a class="org-btn org-btn-outline" href="{{ route('office.budget', $osoNavigationFilters) }}">{{ ($selectedOrganization ?? '') !== '' ? 'Return to organization activities' : 'Return to all organizations' }}</a>
            </section>
        @endif
        @if ($isOso && !$osoHasActivityRequest && $osoFinancialOverview !== null)
            @include('org.budget-financial-overview')
        @endif
        @if ($isSo)
            <p class="org-welcome" style="margin:0;">
                <a href="{{ route('office.financial', ['academic_year' => $selectedYear]) }}">Manage organization cash in Financial Report</a>.
                Approved budgets reserve cash; recorded receipt expenses appear there once as cash outflow.
            </p>
        @endif

        @if ($canRecordExpense)
        {{-- SO only: Record Expense with a receipt photo and manually entered details --}}
        <section class="so-expense-card" aria-label="Record Expense">
            <div class="so-expense-head">
                <div>
                    <h3><i class="bi bi-journal-plus"></i> Record Expense</h3>
                    <span>Upload shared receipts/supporting documents, enter the item details, and submit.</span>
                </div>
                <span class="org-info-pill-badge">SO Encoding</span>
            </div>

            @if ($errors->any())
                <div class="so-alert is-error"><i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first() }}</div>
            @endif

            @if ($approvedBudgetActivities->isEmpty())
                <div class="so-alert"><i class="bi bi-hourglass-split"></i> No final-approved activities are available yet. Budget utilization can be recorded after the activity completes the approval workflow.</div>
            @endif

            <form method="post" action="{{ route('office.budget.receipts.store') }}" enctype="multipart/form-data" id="soExpenseForm" class="so-expense-form" data-org-upload-form>
                @csrf
                <input type="hidden" name="batch_request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <input type="hidden" name="receipt_reviewed" value="1">

                {{-- Shared receipt set: one upload supports every item row below. --}}
                <div class="so-receipt-capture" id="soReceiptCapture">
                    <div class="so-receipt-capture-copy">
                        <strong><i class="bi bi-paperclip"></i> Receipts / Supporting Documents</strong>
                        <p>Upload receipts (up to 3). These shared documents support every item you encode below. If one receipt lists many items, add one input row per item—do not upload the receipt again.</p>
                    </div>
                    <div class="so-receipt-upload-board">
                        <div class="so-receipt-dropzone" id="soReceiptDropzone" role="button" tabindex="0" aria-controls="soReceiptInput" aria-label="Upload receipts and supporting documents">
                            <i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i>
                            <button type="button" class="org-btn org-btn-outline" id="soUploadGalleryBtn" aria-controls="soReceiptInput">
                                <i class="bi bi-folder2-open"></i> Upload receipts (up to 3)
                            </button>
                            <span>Drag and drop files here, or click to browse</span>
                            <small>Supports JPG, PNG, WebP, or PDF · max 5 MB each</small>
                            <button type="button" class="org-btn org-btn-primary" id="soOpenCameraBtn" aria-controls="soCameraModal">
                                <i class="bi bi-camera"></i> Open Camera
                            </button>
                        </div>
                        <div class="so-receipt-previews" id="soReceiptPreview" data-previews hidden></div>
                    </div>
                    <input type="file" name="receipts[]" id="soReceiptInput" class="so-receipt-file-input" accept="image/jpeg,image/png,image/webp,application/pdf,.pdf" multiple required data-receipt-input aria-label="Receipts and supporting documents">
                    <input type="file" id="soCameraInput" class="so-camera-file-input" accept="image/*" capture="environment" aria-label="Take a receipt photo">
                    <span id="soReceiptUploadStatus" class="org-upload-status so-receipt-upload-status" aria-live="polite">No receipt selected.</span>
                </div>

                {{-- Live camera modal: real permission prompt + viewfinder + shutter --}}
                <div id="soCameraModal" hidden style="position:fixed;inset:0;z-index:9999;background:rgba(10,5,7,.82);align-items:center;justify-content:center;padding:1rem;">
                    <div class="so-camera-dialog" style="background:#fff;border-radius:18px;max-width:520px;width:100%;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.35);">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.8rem 1rem;border-bottom:1px solid #f0e6e8;">
                            <strong style="font-size:0.92rem;"><i class="bi bi-camera-fill" style="color:#8b1828;"></i> Capture Receipt</strong>
                            <button type="button" id="soCameraCloseBtn" class="org-btn org-btn-ghost org-btn-sm">Close</button>
                        </div>
                        <div class="so-camera-video-stage" style="background:#000;position:relative;">
                            <video id="soCameraVideo" playsinline muted autoplay style="display:block;width:100%;max-height:60vh;object-fit:cover;"></video>
                            <div id="soCameraStarting" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.85rem;font-weight:700;gap:0.5rem;">
                                <i class="bi bi-camera-fill"></i> Starting camera…
                            </div>
                        </div>
                        <p id="soCameraError" hidden style="margin:0;padding:0.6rem 1rem;font-size:0.8rem;font-weight:700;color:#b91c1c;background:#fef2f2;"></p>
                        <div class="so-camera-controls" style="display:flex;gap:0.6rem;padding:0.9rem 1rem;">
                            <button type="button" id="soCameraSnapBtn" class="org-btn org-btn-primary" style="flex:1;justify-content:center;" disabled>
                                <i class="bi bi-camera"></i> <span id="soCameraSnapLabel">Starting camera…</span>
                            </button>
                            <button type="button" id="soCameraPickerBtn" class="org-btn org-btn-ghost org-btn-sm" title="Pick a photo from files instead">
                                <i class="bi bi-image"></i> Files
                            </button>
                        </div>
                    </div>
                </div>

                <div class="so-batch-activity">
                    <label>
                        <span>Activity / Project *</span>
                        <select name="org_activity_id" id="receiptActivityId" required onchange="selectBudgetActivity('activity-'+this.value)" @disabled($approvedBudgetActivities->isEmpty())>
                            <option value="">Select an approved activity</option>
                            @foreach ($approvedActivityChoices as $choice)
                                <option value="{{ $choice['activityId'] }}" @selected((int) old('org_activity_id', request('activity_id')) === $choice['activityId'])>{{ $choice['actName'] }} — {{ $choice['orgName'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Organization Name</span>
                        <input type="text" id="receiptOrganization" readonly value="{{ $selectedOrganization }}" placeholder="Filled from the selected activity">
                    </label>
                </div>

                <div class="so-expense-items-head">
                    <div>
                        <strong>
                            <i class="bi bi-list-check"></i> Expense items and receipts
                            <span class="so-items-count-badge" id="soItemsCountBadge">1 item</span>
                        </strong>
                        <p>Add one input row for every purchased item listed on the shared receipts. Each row becomes one budget-history entry; the uploaded receipt set is used for the whole submission.</p>
                    </div>
                    <button type="button" class="org-btn org-btn-outline" id="soAddExpenseRow"><i class="bi bi-plus-lg"></i> Add item</button>
                </div>

                <div id="soExpenseItems" class="so-expense-items">
                    <article class="so-expense-item" id="soExpenseRow0" data-expense-row data-row-index="0">
                        <div class="so-expense-row-head">
                            <div><span class="so-item-number">Item 1</span><strong>Item details</strong><small>Enter the information shown on the shared receipts.</small></div>
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-remove-row hidden>Remove</button>
                        </div>
                        <input type="hidden" name="expenses[0][request_key]" value="{{ old('expenses.0.request_key', (string) \Illuminate\Support\Str::uuid()) }}" data-field="request_key">
                        <div class="so-form-grid">
                    <label>
                        <span>Expense description *</span>
                        <input type="text" name="expenses[0][item_name]" id="soItemName" value="{{ old('expenses.0.item_name') }}" required maxlength="255" placeholder="e.g. Refreshments for volunteers" data-field="item_name">
                    </label>
                    <label>
                        <span>Category</span>
                        <select name="expenses[0][category]" data-field="category">
                            <option value="">Select category</option>
                            @foreach (['Equipment Rental', 'Supplies', 'Food & Refreshments', 'Transportation', 'Printing', 'Honoraria', 'Other'] as $cat)
                                <option value="{{ $cat }}" @selected(old('expenses.0.category') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Quantity *</span>
                        <input type="number" name="expenses[0][quantity]" id="soQuantity" value="{{ old('expenses.0.quantity', 1) }}" min="1" max="100000" required data-field="quantity">
                    </label>
                    <label>
                        <span>Unit Cost (₱) *</span>
                        <input type="number" name="expenses[0][unit_cost]" id="soUnitCost" value="{{ old('expenses.0.unit_cost') }}" min="0.01" step="0.01" required placeholder="Receipt total" data-field="unit_cost">
                        <small style="font-weight:500;color:#7a7074;">Quantity 1 means the whole receipt total. For identical items, enter their quantity and per-item price.</small>
                    </label>
                    <label>
                        <span>Expense Date *</span>
                        <input type="date" name="expenses[0][expense_date]" id="soExpenseDate" value="{{ old('expenses.0.expense_date') }}" max="{{ now()->toDateString() }}" required data-field="expense_date">
                    </label>
                    <label>
                        <span>Merchant / recipient</span>
                        <input type="text" name="expenses[0][supplier]" id="soSupplier" value="{{ old('expenses.0.supplier') }}" maxlength="255" placeholder="Store or vendor" data-field="supplier">
                    </label>
                    <label>
                        <span>OR / Receipt Reference No. *</span>
                        <input type="text" name="expenses[0][receipt_reference]" id="soReceiptReference" value="{{ old('expenses.0.receipt_reference') }}" required maxlength="120" placeholder="OR / invoice / reference" data-field="receipt_reference">
                    </label>
                    <label>
                        <span>Receipt type</span>
                        <select name="expenses[0][receipt_type]" id="soReceiptType" required data-field="receipt_type">
                            <option value="unknown">Not identified / other</option>
                            <option value="paper_receipt">Store receipt / invoice</option>
                            <option value="ewallet_receipt">E-wallet payment receipt</option>
                        </select>
                    </label>
                    <label>
                        <span>Payment method</span>
                        <select name="expenses[0][payment_method]" id="soPaymentMethod" required data-field="payment_method">
                            <option value="unknown">Not shown / unknown</option>
                            <option value="gcash">GCash</option>
                            <option value="maya">Maya</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank transfer</option>
                        </select>
                    </label>
                </div>
                    </article>
                </div>

                <template id="soExpenseRowTemplate">
                    <article class="so-expense-item" data-expense-row data-row-index="__INDEX__">
                        <div class="so-expense-row-head">
                            <div><span class="so-item-number">Item __NUMBER__</span><strong>Item details</strong><small>Enter the information shown on the shared receipts.</small></div>
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-remove-row>Remove</button>
                        </div>
                        <div class="so-form-grid">
                            <input type="hidden" name="expenses[__INDEX__][request_key]" value="__REQUEST_KEY__" data-field="request_key">
                            <label><span>Expense description *</span><input type="text" name="expenses[__INDEX__][item_name]" required maxlength="255" placeholder="e.g. Printed materials" data-field="item_name"></label>
                            <label><span>Category</span><select name="expenses[__INDEX__][category]" data-field="category"><option value="">Select category</option>@foreach (['Equipment Rental', 'Supplies', 'Food & Refreshments', 'Transportation', 'Printing', 'Honoraria', 'Other'] as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach</select></label>
                            <label><span>Quantity *</span><input type="number" name="expenses[__INDEX__][quantity]" value="1" min="1" max="100000" required data-field="quantity"></label>
                            <label><span>Unit Cost (₱) *</span><input type="number" name="expenses[__INDEX__][unit_cost]" min="0.01" step="0.01" required placeholder="Receipt total" data-field="unit_cost"></label>
                            <label><span>Expense Date *</span><input type="date" name="expenses[__INDEX__][expense_date]" max="{{ now()->toDateString() }}" required data-field="expense_date"></label>
                            <label><span>Merchant / recipient</span><input type="text" name="expenses[__INDEX__][supplier]" maxlength="255" placeholder="Store or recipient" data-field="supplier"></label>
                            <label><span>OR / Receipt Reference No. *</span><input type="text" name="expenses[__INDEX__][receipt_reference]" required maxlength="120" placeholder="OR / invoice / reference" data-field="receipt_reference"></label>
                            <label><span>Receipt type</span><select name="expenses[__INDEX__][receipt_type]" required data-field="receipt_type"><option value="unknown">Not identified / other</option><option value="paper_receipt">Store receipt / invoice</option><option value="ewallet_receipt">E-wallet payment receipt</option></select></label>
                            <label><span>Payment method</span><select name="expenses[__INDEX__][payment_method]" required data-field="payment_method"><option value="unknown">Not shown / unknown</option><option value="gcash">GCash</option><option value="maya">Maya</option><option value="cash">Cash</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option></select></label>
                        </div>
                    </article>
                </template>

                <div class="so-form-actions">
                    <button type="reset" class="org-btn org-btn-ghost">Clear</button>
                    <button type="submit" class="org-btn org-btn-primary" @disabled($approvedBudgetActivities->isEmpty())><i class="bi bi-send-fill"></i> Record expense</button>
                </div>
            </form>

        </section>
        @endif


        <style>
            .so-receipt-capture { border: 1.5px solid #f0dfe3; border-radius: 16px; padding: 1rem 1.1rem; background: #fffafb; display: grid; gap: 0.85rem; min-width: 0; }
            .so-receipt-capture-copy strong { display: flex; align-items: center; gap: 0.4rem; color: #7a1222; font-size: 0.92rem; }
            .so-receipt-capture-copy p { margin: 0.25rem 0 0; font-size: 0.8rem; color: #786f73; }
            .so-receipt-upload-board { display: grid; grid-template-columns: minmax(240px, .9fr) minmax(0, 1.7fr); gap: .75rem; align-items: stretch; }
            .so-receipt-dropzone { display: grid; place-items: center; align-content: center; gap: .38rem; min-height: 168px; padding: 1rem; border: 1.5px dashed #e8b4bc; border-radius: 12px; background: #fff; color: #786f73; text-align: center; cursor: pointer; }
            .so-receipt-dropzone:hover, .so-receipt-dropzone:focus-visible, .so-receipt-dropzone.is-dragging { border-color: #8b1828; background: #fff5f6; }
            .so-receipt-dropzone > i { color: #8b1828; font-size: 2rem; }
            .so-receipt-dropzone .org-btn { min-height: 42px; }
            .so-receipt-dropzone span { font-size: .76rem; font-weight: 700; }
            .so-receipt-dropzone small { font-size: .68rem; font-weight: 500; }
            .so-receipt-file-input, .so-camera-file-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
            .so-receipt-upload-status { display: block; min-height: 1.1rem; }
            .so-receipt-previews { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .65rem; align-content: start; min-width: 0; }
            .so-receipt-preview { position: relative; display: grid; gap: .45rem; align-content: start; padding: .55rem; border-radius: 12px; background: #fff; border: 1px solid #f0e6e8; min-width: 0; }
            .so-receipt-preview img, .so-receipt-preview-file { width: 100%; height: 130px; object-fit: contain; border-radius: 9px; border: 1px solid #f0e6e8; background: #faf7f8; }
            .so-receipt-preview-file { display: grid; place-items: center; color: #8b1828; font-size: 2.2rem; }
            .so-receipt-preview-meta { display: grid; gap: .12rem; min-width: 0; }
            .so-receipt-preview span { min-width: 0; font-size: .74rem; font-weight: 700; color: #2b2427; overflow-wrap: anywhere; }
            .so-receipt-preview small { font-size: .68rem; font-weight: 500; color: #7a7074; }
            .so-receipt-preview-remove { position: absolute; top: .35rem; right: .35rem; display: grid; place-items: center; width: 1.55rem; height: 1.55rem; border: 0; border-radius: 50%; background: #a71935; color: #fff; font: inherit; font-size: 1.1rem; line-height: 1; font-weight: 800; cursor: pointer; box-shadow: 0 2px 5px rgba(90,15,30,.16); }
            .so-expense-card { background: #fff; border: 1.5px solid #f0e6e8; border-radius: 20px; padding: 1.25rem 1.4rem; box-shadow: 0 4px 16px rgba(90,15,30,.03); display: grid; gap: 1rem; min-width: 0; }
            .so-expense-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
            .so-expense-head h3 { margin: 0; font-size: 1.02rem; font-weight: 800; color: #1a1618; display: flex; align-items: center; gap: .45rem; }
            .so-expense-head h3 i { color: #8b1828; }
            .so-expense-head > div { min-width: 0; }
            .so-expense-head span { font-size: .78rem; color: #786f73; }
            .so-alert { display: flex; align-items: center; gap: .5rem; padding: .7rem .9rem; border-radius: 12px; font-size: .84rem; font-weight: 700; }
            .so-alert.is-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
            .so-alert.is-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
            .so-expense-form { display: grid; gap: 1rem; }
            .so-form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
            .so-batch-activity { display: grid; grid-template-columns: minmax(0, 2fr) minmax(220px, 1fr); gap: .8rem; }
            .so-batch-activity label, .so-expense-item label { display: grid; align-content: start; gap: .35rem; font-size: .78rem; font-weight: 800; color: #2b2427; }
            .so-expense-items-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; padding: .85rem 0 .1rem; }
            .so-expense-items-head strong { display: flex; align-items: center; gap: .45rem; color: #7a1222; font-size: .92rem; flex-wrap: wrap; }
            .so-expense-items-head p { margin: .25rem 0 0; font-size: .78rem; color: #786f73; }
            .so-items-count-badge { display: inline-flex; align-items: center; font-size: 0.72rem; font-weight: 700; color: #8b1828; background: #fdf0f2; border: 1px solid #f8d7dc; border-radius: 999px; padding: 0.12rem 0.55rem; margin-left: 0.35rem; }
            .so-expense-items { display: grid; gap: .85rem; min-width: 0; }
            .so-expense-items:has(> .so-expense-item:nth-child(3)),
            .so-expense-items.is-scrollable {
                max-height: min(580px, 68vh);
                overflow-y: auto;
                overflow-x: hidden;
                padding-right: 0.35rem;
                padding-bottom: 0.25rem;
                scrollbar-width: thin;
                scrollbar-color: #d7b7bd #fbf6f7;
                border-radius: 12px;
                scroll-behavior: smooth;
                overscroll-behavior: contain;
            }
            .so-expense-items:has(> .so-expense-item:nth-child(3))::-webkit-scrollbar,
            .so-expense-items.is-scrollable::-webkit-scrollbar {
                width: 6px;
            }
            .so-expense-items:has(> .so-expense-item:nth-child(3))::-webkit-scrollbar-track,
            .so-expense-items.is-scrollable::-webkit-scrollbar-track {
                background: #fbf6f7;
                border-radius: 9999px;
            }
            .so-expense-items:has(> .so-expense-item:nth-child(3))::-webkit-scrollbar-thumb,
            .so-expense-items.is-scrollable::-webkit-scrollbar-thumb {
                background: #d7b7bd;
                border-radius: 9999px;
            }
            .so-expense-items:has(> .so-expense-item:nth-child(3))::-webkit-scrollbar-thumb:hover,
            .so-expense-items.is-scrollable::-webkit-scrollbar-thumb:hover {
                background: #8b1828;
            }
            .so-expense-item { display: grid; gap: .75rem; padding: .9rem; border: 1px solid #f0dfe3; border-radius: 15px; background: #fffafb; min-width: 0; }
            .so-expense-row-head { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
            .so-expense-row-head > div { display: grid; gap: .18rem; }
            .so-item-number { color: #8b1828; font-size: .7rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
            .so-expense-row-head strong { color: #2b2427; font-size: .88rem; }
            .so-expense-row-head small { color: #786f73; font-size: .74rem; font-weight: 500; }
            .so-form-grid label { display: grid; align-content: start; gap: .35rem; font-size: .78rem; font-weight: 800; color: #2b2427; }
            #soExpenseForm [hidden] { display: none !important; }
            #soExpenseForm :focus-visible { outline: 2px solid #8b1828; outline-offset: 3px; }
            .so-form-grid input, .so-form-grid select { width: 100%; box-sizing: border-box; min-width: 0; min-height: 44px; padding: .65rem .8rem; font: inherit; font-weight: 500; border: 1.5px solid #f0e0e3; border-radius: 12px; background: #fdfafb; color: #2b2427; }
            .so-span-2 { grid-column: span 2; }
            .so-hint-pill { font-style: normal; font-weight: 700; font-size: .68rem; color: #8b1828; background: #fdf0f2; border-radius: 999px; padding: .1rem .5rem; margin-left: .3rem; }
            .so-form-actions { display: flex; justify-content: flex-end; gap: .6rem; }
            .so-form-actions .org-btn { min-height: 44px; }
            .so-queue h4 { margin: 0 0 .5rem; font-size: .88rem; color: #1a1618; display: flex; gap: .4rem; align-items: center; }
            .so-queue h4 i { color: #8b1828; }
            .so-queue ul { list-style: none; margin: 0; padding: 0; display: grid; gap: .5rem; }
            .so-queue li { display: flex; justify-content: space-between; gap: .75rem; align-items: center; padding: .6rem .8rem; border: 1px solid #f0e6e8; border-radius: 12px; background: #fdfafb; }
            .so-queue small { display: block; color: #786f73; font-size: .74rem; }
            .so-queue-right { text-align: right; display: grid; gap: .15rem; }
            .so-queue-right a { font-size: .74rem; color: #8b1828; text-decoration: none; font-weight: 700; }
            #soCameraModal { overscroll-behavior: contain; }
            #soCameraModal[hidden] { display: none !important; }
            .so-camera-dialog { max-height: calc(100dvh - 2rem); overflow-y: auto !important; overscroll-behavior: contain; }
            .so-camera-video-stage { flex: 0 1 auto; }
            .so-camera-controls .org-btn { min-height: 44px; }
            @media (max-width: 900px) {
                .so-form-grid { grid-template-columns: 1fr 1fr; }
                .so-batch-activity { grid-template-columns: 1fr; }
                .so-span-2 { grid-column: 1 / -1; }
            }
            @media (max-width: 640px) {
                .so-expense-card { padding: 1rem; border-radius: 16px; }
                .so-expense-head { flex-direction: column; gap: .55rem; }
                .so-expense-head .org-info-pill-badge { align-self: flex-start; }
                .so-expense-items-head { flex-direction: column; }
                .so-expense-items-head #soAddExpenseRow { width: 100%; min-height: 46px; }
                .so-expense-row-head { align-items: stretch; }
                .so-expense-row-head .org-btn { min-height: 44px; }
                .so-receipt-upload-board { grid-template-columns: 1fr; }
                .so-receipt-dropzone { min-height: 150px; }
                .so-receipt-dropzone .org-btn { width: 100%; min-height: 46px; }
                .so-receipt-previews { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .so-receipt-preview img { height: 120px; }
                .so-form-grid { grid-template-columns: 1fr; }
                .so-batch-activity input, .so-batch-activity select { min-height: 46px; font-size: 16px; }
                .so-form-grid input, .so-form-grid select { min-height: 46px; font-size: 16px; }
                .so-form-actions { flex-direction: column-reverse; }
                .so-form-actions .org-btn { width: 100%; min-height: 46px; }
                #soCameraModal { align-items: flex-end !important; padding: .5rem; }
                .so-camera-dialog { max-height: calc(100dvh - 1rem); border-radius: 16px !important; }
                #soCameraModal #soCameraVideo { max-height: 48dvh !important; }
                .so-camera-controls { flex-direction: column; }
                .so-camera-controls .org-btn { width: 100%; min-height: 46px; }
                .so-camera-controls #soCameraPickerBtn { min-height: 44px; }
                .so-queue li { flex-direction: column; align-items: flex-start; }
                .so-queue-right { text-align: left; width: 100%; }
                .so-queue-right a { overflow-wrap: anywhere; }
            }
        </style>
        <script src="{{ asset('js/receipt-upload.js') }}?v={{ filemtime(public_path('js/receipt-upload.js')) }}"></script>
        @if (!$isOso || $osoSelectedActivity !== null)

        {{-- 1 & 2. Organization Information & Activity / Project Information Panels --}}
        <div class="org-info-panels-grid">
            {{-- Organization Information Panel --}}
            <section class="org-info-card" aria-label="Organization Information">
                <div class="org-info-card-head">
                    <h3 class="org-info-card-title">
                        <i class="bi bi-building" style="color: #8b1828;"></i> Organization Information
                    </h3>
                    <span class="org-info-pill-badge" id="orgCategoryBadge">—</span>
                </div>
                <div class="org-info-meta-list">
                    <div class="org-info-meta-item">
                        <span class="lbl">Organization Name</span>
                        <strong class="val" id="orgNameVal">—</strong>
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
                        <strong class="val" id="orgUnitVal">—</strong>
                    </div>
                </div>
            </section>

            {{-- Activity / Project Information Panel --}}
            <section class="org-info-card" aria-label="Activity and Project Information">
                <div class="org-info-card-head">
                    <h3 class="org-info-card-title">
                        <i class="bi bi-clipboard2-check-fill" style="color: #8b1828;"></i> Activity / Project Information
                    </h3>
                    <span class="org-info-pill-badge" id="actScopePill">—</span>
                </div>
                <div class="org-info-meta-list">
                    <div class="org-info-meta-item">
                        <span class="lbl">Activity / Project Name</span>
                        <strong class="val" id="actNameVal">—</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">Type &amp; Category</span>
                        <strong class="val" id="actTypeVal">—</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">Execution Date</span>
                        <strong class="val" id="actDateVal">—</strong>
                    </div>
                    <div class="org-info-meta-item">
                        <span class="lbl">Designated Venue</span>
                        <strong class="val" id="actVenueVal">—</strong>
                    </div>
                </div>
            </section>
        </div>

        {{-- 3, 4, 5, 6. Approved Budget, Actual Expenses, Remaining Balance & Utilization Rate --}}
        <div class="org-kpi-row">
            {{-- 3. Approved Budget --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-pink">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiApprovedBudget">—</div>
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
                    <div class="org-kpi-num" id="kpiActualExpenses">—</div>
                </div>
                <h3 class="org-kpi-title">Actual Expenses</h3>
                <p class="org-kpi-sub" id="kpiActualSub">Receipts plus any earlier recorded spending</p>
            </article>

            {{-- 5. Remaining Balance --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-green">
                        <i class="bi bi-piggy-bank"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiRemainingBal">—</div>
                </div>
                <h3 class="org-kpi-title">Remaining Balance</h3>
                <p class="org-kpi-sub" id="kpiRemainingSub">Approved allocation less recorded spending</p>
            </article>

            {{-- 6. Budget Utilization Rate --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-blue">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiUtilRate">—</div>
                </div>
                <h3 class="org-kpi-title">Budget Utilization Rate</h3>
                <div class="org-mini-progress">
                    <div class="org-mini-fill" id="kpiUtilProgressFill" style="width: 100%; background: #0284c7;"></div>
                </div>
                <p class="org-kpi-sub" id="kpiRateSub">Recorded spending ÷ approved allocation</p>
            </article>
        </div>


        {{-- 7 & 8. Expense Breakdown (Donut Chart) & Budget vs. Actual Expenses (Bar Chart) --}}
        @section('budget-chart-details')
        @if ($isSo || $isOso)
            <details class="so-secondary-details" @if ($isOso) id="osoBudgetCharts" @endif>
                <summary>Show budget charts and trend details</summary>
        @endif
        <div class="org-budget-charts-grid">
            {{-- 7. Expense Breakdown (Donut Chart) --}}
            <section class="org-budget-chart-card" aria-label="Expense Breakdown by Scope">
                <div class="org-budget-chart-head">
                    <h3><i class="bi bi-pie-chart" style="color: #8b1828;"></i> Expense Breakdown <small style="font-weight:600;color:#786f73;font-size:0.72rem;">{{ $isOso ? 'Organization portfolio by scope' : 'by Scope' }}</small></h3>
                    <span class="org-info-pill-badge" id="donutTotalBadge">—</span>
                </div>
                <div class="org-chart-canvas-wrap">
                    <div class="org-donut-flex-layout">
                        <div class="org-donut-canvas-hold">
                            <canvas id="expenseDonutChart"></canvas>
                            <div class="org-donut-center-text">
                                <strong id="donutCenterAmount">—</strong>
                                <small>Disbursed</small>
                            </div>
                        </div>
                        <div class="org-legend-list" id="donutCustomLegend"></div>
                    </div>
                    <p style="margin:0.75rem 0 0;font-size:0.74rem;color:#786f73;">{{ $isOso ? 'Organization portfolio-wide scope split for the selected year and semester, not just this activity. The Budget vs. Actual chart and encoded expense table show the selected activity.' : 'Portfolio-wide scope split — per-activity itemization lives in the Budget vs. Actual chart and the Expense Details table.' }}</p>
                </div>
            </section>

            {{-- 8. Budget vs. Actual Expenses (Bar Chart) --}}
            <section class="org-budget-chart-card" aria-label="Approved Budget vs Actual Expenses">
                <div class="org-budget-chart-head">
                    <h3><i class="bi bi-bar-chart-fill" style="color: #8b1828;"></i> Budget vs. Actual Expenses</h3>
                    <span class="org-info-pill-badge">Approved vs. recorded</span>
                </div>
                <div class="org-chart-canvas-wrap">
                    <canvas id="budgetVsActualBarChart"></canvas>
                </div>
            </section>
        </div>
        @if ($isSo || $isOso)
            </details>
        @endif
        @endsection
        @unless ($isOso)
            @yield('budget-chart-details')
        @endunless

        {{-- 9. Expense Details (Data Table) --}}
        <section class="org-expense-table-card" aria-label="Detailed Expense Entries Table">
            <div class="org-table-header-flex">
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: #1a1618; margin: 0 0 0.15rem; display: flex; align-items: center; gap: 0.45rem;">
                        <i class="bi bi-receipt-cutoff" style="color: #8b1828;"></i> Expense Details &amp; Itemization
                    </h3>
                    <span style="font-size: 0.76rem; color: #786f73;">Recorded receipts, including any awaiting a blockchain seal</span>
                </div>
                <div>
                    <input type="text" id="expenseTableSearch" class="org-table-search-input" placeholder="Search item, category, amount..." autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="filterExpenseTable()">
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
            <div class="org-report-pagination" id="budgetExpensePagination" aria-label="Expense details pagination">
                <span id="budgetExpensePaginationInfo"></span>
                <nav class="org-report-pagination-nav" id="budgetExpensePaginationNav" aria-label="Expense detail pages"></nav>
            </div>
        </section>
        @if ($isOso)
            @yield('budget-chart-details')
        @endif
        @endif


    </div>

    @if (!$isOso || $osoSelectedActivity !== null)
        @include('org.partials.budget-receipt-preview')

        {{-- Load Chart.js only for pages with activity details. --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif
    <script>
        // Only final-approved activities are supplied by the controller.
        // Never fall back to demo activities when the approved set is empty.
        const liveBudgetEntries = @json(($isOso && $osoSelectedActivity === null) ? null : ($liveBudgetEntries ?? null));
        const liveScopeTotals = @json(($isOso && $osoSelectedActivity === null) ? null : ($liveScopeTotals ?? null));
        const liveBudgetDefault = @json($budgetDefaultForRole);
        const isOsoBudget = @json($isOso);
        const osoSelectedActivityKey = @json($osoSelectedActivityKey);

        const budgetDataset = (liveBudgetEntries && Object.keys(liveBudgetEntries).length > 0) ? liveBudgetEntries : {};

        let donutChartInstance = null;
        let barChartInstance = null;
        let activeBudgetRows = [];
        let activeBudgetPeriod = { year: @json($selectedYear), term: @json($selectedSemester) };
        let budgetPeriodInitialized = false;
        let currentBudgetExpenseItems = [];
        let currentBudgetExpenseQuery = '';
        let currentBudgetExpensePage = 1;
        const BUDGET_EXPENSE_PAGE_SIZE = 5;

        /* Portfolio-wide scope split: every activity rolls up to exactly
           In-Campus or Off-Campus (the consolidated rollup entry is skipped). */
        const SCOPE_LABELS = ['In-Campus', 'Off-Campus'];
        const SCOPE_COLORS = ['#8b1828', '#1d4ed8'];

        function parseBudgetDate(value) {
            if (!value) return null;
            const text = String(value).replace(/–|—/g, '-');
            const match = text.match(/(Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\s+\d{1,2}(?:\s*-\s*\d{1,2})?,?\s+(\d{4})/i);
            if (!match) return null;
            const month = new Date(`${match[1]} 1, ${match[2]}`).getMonth() + 1;
            const year = Number(match[2]);
            if (!month || !year) return null;
            return { month, year };
        }

        function budgetAcademicPeriod(data) {
            if (data?.academic_year && data?.semester) {
                return { year: data.academic_year, semester: data.semester };
            }
            const parsed = parseBudgetDate(data?.actDate);
            if (!parsed) return { year: null, semester: null };
            const startYear = parsed.month < 8 ? parsed.year - 1 : parsed.year;
            return {
                year: `${startYear}-${startYear + 1}`,
                semester: parsed.month >= 8 && parsed.month <= 12
                    ? '1st Semester'
                    : (parsed.month <= 5 ? '2nd Semester' : 'Midyear')
            };
        }

        function budgetRowMatchesPeriod(data, year, term) {
            const period = budgetAcademicPeriod(data);
            if (!period.year || period.year !== year) return false;
            return term === 'Annual' || period.semester === term;
        }

        function budgetRowsForPeriod() {
            const { year, term } = activeBudgetPeriod;
            return Object.entries(budgetDataset)
                .filter(([key, data]) => key !== 'all' && data && budgetRowMatchesPeriod(data, year, term))
                .map(([key, data]) => ({ key, data }));
        }

        function emptyBudgetData(message = 'No records match the selected reporting period') {
            return {
                orgName: 'No matching records',
                orgCategory: 'Filtered portfolio',
                orgUnit: 'No matching activities',
                actName: message,
                actType: 'Budget period filter',
                actDate: `${activeBudgetPeriod.term} · A.Y. ${activeBudgetPeriod.year}`,
                actVenue: '—',
                scope: 'No matching scope',
                approvedBudget: 0,
                actualExpenses: 0,
                remainingBal: 0,
                utilRate: 0,
                rateStatus: 'No Data',
                rateStatusColor: '#64748b',
                stepperStep: 1,
                categories: [],
                donutData: [],
                donutColors: [],
                barAllocated: [],
                barActual: [],
                expenses: []
            };
        }

        function consolidateBudgetRows(rows) {
            if (!rows.length) return emptyBudgetData();
            const categories = new Map();
            rows.forEach(({ data }) => {
                const category = data.orgUnit || data.orgCategory || 'General';
                const current = categories.get(category) || { allocated: 0, actual: 0 };
                current.allocated += Number(data.approvedBudget) || 0;
                current.actual += Number(data.actualExpenses) || 0;
                categories.set(category, current);
            });
            const approvedBudget = rows.reduce((sum, row) => sum + (Number(row.data.approvedBudget) || 0), 0);
            const actualExpenses = rows.reduce((sum, row) => sum + (Number(row.data.actualExpenses) || 0), 0);
            const first = rows[0].data;
            return {
                ...first,
                orgName: 'Filtered recognized organizations',
                orgCategory: 'Filtered portfolio',
                orgUnit: 'Selected reporting period',
                actName: 'Consolidated filtered portfolio',
                actType: 'Budget period rollup',
                actDate: `${activeBudgetPeriod.term} · A.Y. ${activeBudgetPeriod.year}`,
                actVenue: 'All matching venues',
                scope: 'Filtered Portfolio',
                approvedBudget,
                actualExpenses,
                remainingBal: Math.round((approvedBudget - actualExpenses) * 100) / 100,
                utilRate: approvedBudget > 0 ? Number(((actualExpenses / approvedBudget) * 100).toFixed(1)) : 0,
                rateStatus: 'Filtered portfolio',
                rateStatusColor: '#0284c7',
                categories: Array.from(categories.keys()),
                donutData: Array.from(categories.values()).map((entry) => entry.actual),
                barAllocated: Array.from(categories.values()).map((entry) => entry.allocated),
                barActual: Array.from(categories.values()).map((entry) => entry.actual),
                expenses: rows.flatMap((row) => row.data.expenses || [])
            };
        }

        function budgetDisplayData(key) {
            if (isOsoBudget) {
                return key === osoSelectedActivityKey && key ? budgetDataset[key] : null;
            }
            const matchingRows = budgetRowsForPeriod();
            if (key === 'all') return consolidateBudgetRows(matchingRows);
            const selected = budgetDataset[key];
            return selected && budgetRowMatchesPeriod(selected, activeBudgetPeriod.year, activeBudgetPeriod.term)
                ? selected
                : emptyBudgetData('This activity has no records in the selected period');
        }

        function scopeSplit() {
            if (budgetPeriodInitialized && !activeBudgetRows.length) return [0, 0];
            if (activeBudgetRows.length) {
                const inCampus = activeBudgetRows
                    .filter(({ data }) => !/off/i.test(data.scope || ''))
                    .reduce((sum, row) => sum + (Number(row.data.actualExpenses) || 0), 0);
                const offCampus = activeBudgetRows
                    .filter(({ data }) => /off/i.test(data.scope || ''))
                    .reduce((sum, row) => sum + (Number(row.data.actualExpenses) || 0), 0);
                return [inCampus, offCampus];
            }
            if (liveScopeTotals && liveScopeTotals.length === 2 && !activeBudgetPeriod.year) return liveScopeTotals;
            let inC = 0;
            let offC = 0;
            Object.entries(budgetDataset).forEach(([key, d]) => {
                if (!d || key === 'all' || typeof d.actualExpenses !== 'number') return;
                if (/off/i.test(d.scope || '')) offC += d.actualExpenses;
                else inC += d.actualExpenses;
            });
            return [inC, offC];
        }

        function renderScopeDonut() {
            const split = scopeSplit();
            const total = split[0] + split[1];
            if (donutChartInstance) {
                donutChartInstance.data.labels = SCOPE_LABELS;
                donutChartInstance.data.datasets[0].data = split;
                donutChartInstance.data.datasets[0].backgroundColor = SCOPE_COLORS;
                donutChartInstance.update();
            }
            document.getElementById('donutTotalBadge').textContent = '₱' + total.toLocaleString() + ' Total';
            document.getElementById('donutCenterAmount').textContent = '₱' + (total >= 1000 ? Math.round(total / 1000) + 'k' : total);

            const legendContainer = document.getElementById('donutCustomLegend');
            if (legendContainer) {
                legendContainer.innerHTML = SCOPE_LABELS.map((label, i) => {
                    const amt = split[i];
                    const pct = total ? ((amt / total) * 100).toFixed(1) : '0.0';
                    return `
                        <div class="org-legend-row">
                            <div class="org-legend-left">
                                <span class="org-legend-color-dot" style="background: ${SCOPE_COLORS[i]};"></span>
                                <span>${label}</span>
                            </div>
                            <div class="org-legend-right">₱${amt.toLocaleString()} (${pct}%)</div>
                        </div>`;
                }).join('');
            }
        }

        // Keep the chart readable while retaining the full college/unit name
        // in the tooltip and the organization information panel.
        const CHART_LABEL_ALIASES = {
            'College of Accountancy, Business, Economics, and International Hospitality Management': 'CABEIHM',
            'College of Arts and Sciences': 'CAS',
            'College of Criminal Justice Education': 'CCJE',
            'College of Health Sciences': 'CHS',
            'College of Informatics and Computing Sciences': 'CICS',
            'College of Teacher Education': 'CTE',
            'Laboratory School': 'Laboratory School',
            'All Colleges / Units': 'All Units',
        };

        function shortChartLabel(label) {
            const normalized = String(label || '').trim();
            if (CHART_LABEL_ALIASES[normalized]) return CHART_LABEL_ALIASES[normalized];
            return normalized.length > 20 ? normalized.slice(0, 18) + '…' : normalized;
        }

        function initBudgetCharts() {
            if (donutChartInstance || barChartInstance || typeof Chart === 'undefined') return;
            if (isOsoBudget && (!osoSelectedActivityKey || !document.getElementById('osoBudgetCharts')?.open)) return;
            // 1. Donut Chart Initialization (scope split: In-Campus vs Off-Campus)
            const initKey = isOsoBudget ? osoSelectedActivityKey : (budgetDataset.all ? 'all' : Object.keys(budgetDataset)[0]);
            const initData = budgetDataset[initKey] || { categories: [], donutData: [] };
            const ctxDonut = document.getElementById('expenseDonutChart').getContext('2d');
            donutChartInstance = new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: SCOPE_LABELS,
                    datasets: [{
                        data: scopeSplit(),
                        backgroundColor: SCOPE_COLORS,
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
                    labels: initData.categories.map(shortChartLabel),
                    fullLabels: initData.categories,
                    datasets: [
                        {
                            label: 'Approved Budget',
                            data: initData.barAllocated,
                            backgroundColor: 'rgba(202, 138, 4, 0.75)',
                            borderColor: '#ca8a04',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            maxBarThickness: 28
                        },
                        {
                            label: 'Actual Expenses',
                            data: initData.barActual,
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
                                title: (items) => items[0]?.chart?.data?.fullLabels?.[items[0].dataIndex] || items[0]?.label || '',
                                label: (ctx) => ` ${ctx.dataset.label}: ₱${ctx.parsed.y.toLocaleString()}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                autoSkip: true,
                                maxTicksLimit: 8,
                                maxRotation: 0,
                                minRotation: 0,
                                font: { size: 10.5, weight: '600' },
                                color: '#786f73'
                            }
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

        function budgetPaginate(items, page, pageSize) {
            const safeItems = Array.isArray(items) ? items : [];
            const totalPages = Math.max(1, Math.ceil(safeItems.length / pageSize));
            const safePage = Math.min(Math.max(Number(page) || 1, 1), totalPages);
            const start = safeItems.length ? (safePage - 1) * pageSize : 0;
            const end = Math.min(start + pageSize, safeItems.length);

            return {
                items: safeItems.slice(start, end),
                total: safeItems.length,
                page: safePage,
                totalPages,
                start,
                end
            };
        }

        function renderBudgetPagination({ barId, infoId, navId, total, page, pageSize, label, handler }) {
            const bar = document.getElementById(barId);
            const info = document.getElementById(infoId);
            const nav = document.getElementById(navId);
            if (!bar || !info || !nav) return;

            if (!total || total <= pageSize) {
                bar.style.display = 'none';
                info.textContent = '';
                nav.innerHTML = '';
                return;
            }

            const totalPages = Math.ceil(total / pageSize);
            const safePage = Math.min(Math.max(Number(page) || 1, 1), totalPages);
            const start = ((safePage - 1) * pageSize) + 1;
            const end = Math.min(safePage * pageSize, total);
            info.innerHTML = `Showing <strong>${start}</strong> to <strong>${end}</strong> of <strong>${total}</strong> ${label}`;

            let html = `<button type="button" class="org-report-page-btn" ${safePage === 1 ? 'disabled' : ''} onclick="${handler}(${safePage - 1})" aria-label="Previous page"><i class="bi bi-chevron-left"></i></button>`;
            for (let p = 1; p <= totalPages; p += 1) {
                if (totalPages <= 7 || p === 1 || p === totalPages || (p >= safePage - 1 && p <= safePage + 1)) {
                    html += `<button type="button" class="org-report-page-btn ${p === safePage ? 'is-active' : ''}" onclick="${handler}(${p})" aria-label="Page ${p}" ${p === safePage ? 'aria-current="page"' : ''}>${p}</button>`;
                } else if (p === safePage - 2 || p === safePage + 2) {
                    html += '<span aria-hidden="true">&hellip;</span>';
                }
            }
            html += `<button type="button" class="org-report-page-btn" ${safePage === totalPages ? 'disabled' : ''} onclick="${handler}(${safePage + 1})" aria-label="Next page"><i class="bi bi-chevron-right"></i></button>`;

            bar.style.display = 'flex';
            nav.innerHTML = html;
        }

        function renderBudgetExpenseTable() {
            const tbody = document.getElementById('expenseDetailsTableBody');
            if (!tbody) return;

            const query = currentBudgetExpenseQuery.toLowerCase().trim();
            const filtered = currentBudgetExpenseItems.filter((expense) => {
                if (!query) return true;
                return [expense.cat, expense.desc, expense.date, expense.qty, expense.amount, expense.status]
                    .some((value) => String(value ?? '').toLowerCase().includes(query));
            });
            const page = budgetPaginate(filtered, currentBudgetExpensePage, BUDGET_EXPENSE_PAGE_SIZE);
            currentBudgetExpensePage = page.page;

            if (!page.items.length) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:1.25rem;color:#786f73;font-size:0.85rem;">No encoded expenses match the current search or reporting period.</td></tr>';
            } else {
                tbody.innerHTML = page.items.map(exp => `
                    <tr>
                        <td>
                            <span class="org-cat-badge">
                                <i class="bi bi-tag-fill"></i> ${escBudget(exp.cat)}
                            </span>
                        </td>
                        <td><strong>${escBudget(exp.desc)}</strong></td>
                        <td><span style="color: #554d50; font-size: 0.8rem;">${escBudget(exp.date)}</span></td>
                        <td><span style="font-weight: 600;">${exp.qty}</span></td>
                        <td><strong style="color: #1a1618;">₱${Number(exp.amount || 0).toLocaleString()}</strong></td>
                        <td>
                            ${exp.receiptUrl
                                ? `<a href="${escBudget(exp.receiptUrl)}" data-budget-receipt-preview="${escBudget(exp.id)}" aria-haspopup="dialog" aria-controls="budgetReceiptPreviewDialog" class="org-receipt-link-pill" style="text-decoration:none;">${escBudget(exp.status)}${(exp.receiptAttachments?.length || 1) > 1 ? ' · ' + exp.receiptAttachments.length + ' files' : ''}</a>`
                                : `<span>${escBudget(exp.status)}</span>`}
                        </td>
                        <td style="text-align: right;">
                            ${exp.receiptUrl
                                ? `<a href="${escBudget(exp.receiptUrl)}" data-budget-receipt-preview="${escBudget(exp.id)}" aria-haspopup="dialog" aria-controls="budgetReceiptPreviewDialog" class="org-file-action-btn" style="text-decoration:none;"><i class="bi bi-eye"></i> View${(exp.receiptAttachments?.length || 1) > 1 ? ' (' + exp.receiptAttachments.length + ')' : ''}</a>`
                                : '<span>Original unavailable</span>'}
                        </td>
                    </tr>
                `).join('');
            }

            renderBudgetPagination({
                barId: 'budgetExpensePagination',
                infoId: 'budgetExpensePaginationInfo',
                navId: 'budgetExpensePaginationNav',
                total: page.total,
                page: page.page,
                pageSize: BUDGET_EXPENSE_PAGE_SIZE,
                label: 'expense entries',
                handler: 'goToBudgetExpensePage'
            });
        }

        function escBudget(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
        }


        function selectBudgetActivity(key) {
            const selected = budgetDataset[key];
            if (isOsoBudget) {
                if (!selected || key === 'all' || selected.orgName !== @json($selectedOrganization) || !budgetRowMatchesPeriod(selected, activeBudgetPeriod.year, activeBudgetPeriod.term)) return;
                const url = new URL(@json(route('office.budget', $osoNavigationFilters)));
                url.searchParams.set('activity_id', selected.activityId);
                window.location.assign(url);
                return;
            }
            if (key !== 'all' && selected) {
                const period = budgetAcademicPeriod(selected);
                if (period.year !== activeBudgetPeriod.year) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('academic_year', period.year);
                    url.searchParams.set('semester', 'Annual');
                    url.searchParams.set('activity_id', selected.activityId);
                    window.location.assign(url);
                    return;
                }
                if (!budgetRowMatchesPeriod(selected, activeBudgetPeriod.year, activeBudgetPeriod.term)) {
                    document.getElementById('budgetTermSelector').value = 'Annual';
                    updateFilterPeriod();
                }
            }
            switchActivityData(key);
        }

        function switchActivityData(key) {
            if (isOsoBudget) key = osoSelectedActivityKey;
            const data = budgetDisplayData(key);
            if (!data) return;
            document.getElementById('budgetActivitySelector').value = key;
            const selected = key === 'all' ? null : budgetDataset[key];
            const receiptSelect = document.getElementById('receiptActivityId');
            if (selected && receiptSelect) receiptSelect.value = selected.activityId;
            const orgInput = document.getElementById('receiptOrganization');
            if (selected && orgInput) orgInput.value = selected.orgName;
            if (!isOsoBudget) {
                const printUrl = new URL(@json(route('office.budget.print')));
                printUrl.searchParams.set('organization', @json($selectedOrganization));
                if (selected) printUrl.searchParams.set('activity_id', selected.activityId);
                printUrl.searchParams.set('academic_year', activeBudgetPeriod.year);
                printUrl.searchParams.set('semester', activeBudgetPeriod.term);
                const reportDepartment = @json(request('department', ''));
                if (reportDepartment) printUrl.searchParams.set('department', reportDepartment);
                document.getElementById('budgetPrintLink').href = printUrl;
            }

            // 1. Organization & Activity Information
            document.getElementById('orgNameVal').textContent = data.orgName;
            document.getElementById('orgCategoryBadge').textContent = data.orgCategory;
            const orgUnitEl = document.getElementById('orgUnitVal');
            if (orgUnitEl) orgUnitEl.textContent = data.orgUnit || data.college || data.orgCategory || 'All Colleges / Units';
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


            // 4. Update Donut Chart & Custom Legend (scope split stays portfolio-wide:
            //    per-activity itemization is shown in the bar chart + table below)
            renderScopeDonut();

            // 5. Update Bar Chart
            if (barChartInstance) {
                barChartInstance.data.labels = data.categories.map(shortChartLabel);
                barChartInstance.data.fullLabels = data.categories;
                barChartInstance.data.datasets[0].data = data.barAllocated;
                barChartInstance.data.datasets[1].data = data.barActual;
                barChartInstance.update();
            }

            // 6. Update the paginated expense table.
            currentBudgetExpenseItems = Array.isArray(data.expenses) ? data.expenses : [];
            currentBudgetExpenseQuery = '';
            currentBudgetExpensePage = 1;
            const expenseSearch = document.getElementById('expenseTableSearch');
            if (expenseSearch) expenseSearch.value = '';
            renderBudgetExpenseTable();
        }

        function filterExpenseTable() {
            currentBudgetExpenseQuery = document.getElementById('expenseTableSearch')?.value || '';
            currentBudgetExpensePage = 1;
            renderBudgetExpenseTable();
        }

        function goToBudgetExpensePage(page) {
            currentBudgetExpensePage = page;
            renderBudgetExpenseTable();
        }


        function updateFilterPeriod() {
            const yr = document.getElementById('budgetYearSelector').value;
            const term = document.getElementById('budgetTermSelector').value;
            const orgSemester = document.getElementById('budgetOrgSemester');
            if (orgSemester) orgSemester.value = term;
            if (yr !== @json($selectedYear) || (@json($isOso) && term !== @json($selectedSemester))) {
                const url = new URL(window.location.href);
                url.searchParams.set('academic_year', yr);
                url.searchParams.set('semester', term);
                url.searchParams.delete('activity_id');
                url.searchParams.delete('cash_page');
                window.location.assign(url);
                return;
            }
            activeBudgetPeriod = { year: yr, term };
            budgetPeriodInitialized = true;
            if (isOsoBudget && !osoSelectedActivityKey) return;
            activeBudgetRows = budgetRowsForPeriod();
            document.getElementById('orgAyVal').textContent = yr;
            document.getElementById('orgPeriodVal').textContent = term;
            switchActivityData(document.getElementById('budgetActivitySelector')?.value || liveBudgetDefault);
        }

        document.getElementById('expenseDetailsTableBody')?.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-budget-receipt-preview]');
            if (!trigger || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            const expense = currentBudgetExpenseItems.find(item => String(item.id) === trigger.dataset.budgetReceiptPreview);
            if (!expense || typeof window.openBudgetReceiptPreview !== 'function') return;
            event.preventDefault();
            window.openBudgetReceiptPreview(expense, trigger);
        });

        document.getElementById('osoBudgetCharts')?.addEventListener('toggle', function () {
            if (!this.open || !osoSelectedActivityKey) return;
            initBudgetCharts();
        });

        document.addEventListener('DOMContentLoaded', function () {
            const yearSelect = document.getElementById('budgetYearSelector');
            const year = @json($selectedYear);
            if (!Array.from(yearSelect.options).some(o => o.value === year)) yearSelect.add(new Option('A.Y. '+year, year));
            yearSelect.value = year;
            document.getElementById('budgetTermSelector').value = @json(request('semester', 'Annual'));
            if (isOsoBudget && !osoSelectedActivityKey) return;
            const requestedId = @json((int) request('activity_id', 0));
            const requestedKey = isOsoBudget ? osoSelectedActivityKey : (requestedId && budgetDataset['activity-'+requestedId] ? 'activity-'+requestedId : null);
            if (requestedKey && !isOsoBudget) {
                const requestedPeriod = budgetAcademicPeriod(budgetDataset[requestedKey]);
                if (requestedPeriod.year && requestedPeriod.year !== year) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('academic_year', requestedPeriod.year);
                    url.searchParams.set('semester', 'Annual');
                    window.location.assign(url);
                    return;
                }
                document.getElementById('budgetActivitySelector').value = requestedKey;
            }
            updateFilterPeriod();
            initBudgetCharts();
            if (requestedKey && !isOsoBudget) selectBudgetActivity(requestedKey);
            else switchActivityData(document.getElementById('budgetActivitySelector')?.value || liveBudgetDefault);
        });
    </script>
@endsection
