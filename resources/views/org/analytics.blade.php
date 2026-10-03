@extends('org.layout')

@section('title', 'Analytics & Financial Intelligence')

@php
    $role = $office->office_role ?? '';
    $isSo = $role === 'so';
    $isOso = $role === 'oso';
    $isSdo = $role === 'sdo';
    $isOvcaa = $role === 'ovcaa';
    $isOc = $role === 'oc';
@endphp

@section('header')
    <h1><strong>Activity Performance and Financial Insights</strong></h1>
    @if ($isSo)
        <p class="org-welcome">Live budget utilization, allocation tracking, and activity financial breakdown for {{ $selectedOrganization ?: 'your organization' }}.</p>
    @elseif ($isOso)
        <p class="org-welcome">Comprehensive budget utilization, allocation trends, and cross-organization financial breakdowns.</p>
    @elseif ($isSdo)
        <p class="org-welcome">Institutional sustainable development metrics, compliance verification, and budget tracking.</p>
    @elseif ($isOvcaa)
        <p class="org-welcome">Academic affairs oversight, college participation performance, and fund allocation trends.</p>
    @elseif ($isOc)
        <p class="org-welcome">Executive university-wide activity analytics, financial utilization health, and strategic allocations.</p>
    @else
        <p class="org-welcome">Comprehensive budget utilization, allocation trends, and activity financial breakdowns.</p>
    @endif
@endsection

@section('actions')
    <a href="{{ route('office.analytics.export') }}" class="org-btn org-btn-primary org-btn-export-top">
        <i class="bi bi-download"></i> Export Report
    </a>
@endsection

@section('content')
    <style>
        /* Force containment on parent grid/shell to prevent blowout */
        .org-content,
        .org-main {
            min-width: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            overflow-x: hidden !important;
        }

        /* Global Analytics Layout Wrapper */
        .org-analytics-container {
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow-x: hidden;
        }

        .org-analytics-container > * {
            min-width: 0;
            max-width: 100%;
        }

        /* ---------------------------------------------------------
           Interactive Dashboard Period & Date Filter Toolbar
           --------------------------------------------------------- */
        .oso-filter-bar-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 0.85rem 1.15rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            flex-wrap: wrap;
            width: 100%;
            min-width: 0;
        }

        .oso-filter-bar-left {
            display: flex;
            align-items: center;
            gap: 0.65rem 0.85rem;
            flex-wrap: wrap;
            flex: 1 1 auto;
            min-width: 0;
        }

        .oso-filter-bar-title {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8rem;
            font-weight: 800;
            color: #8b1828;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-right: 0.15rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .oso-filter-group {
            display: inline-flex;
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
            max-width: 100%;
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
            max-width: 220px;
            text-overflow: ellipsis;
            overflow: hidden;
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

        .oso-filter-static-org {
            display: inline-flex;
            align-items: center;
            background: #fdf0f2;
            border: 1.5px solid #f8d7dc;
            border-radius: 9999px;
            padding: 0.36rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 800;
            color: #8b1828;
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .oso-filter-bar-right {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            flex-shrink: 0;
            margin-left: auto;
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

        /* ---------------------------------------------------------
           Health Scorecard — 4 Top KPI Cards
           --------------------------------------------------------- */
        .org-health-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1.15rem;
            width: 100%;
            min-width: 0;
        }

        .ovcaa-stat-card {
            border-radius: 20px;
            padding: 1.15rem 1.25rem 1.2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1.5px solid rgba(240, 230, 232, 0.95);
            box-shadow: 0 4px 20px rgba(90, 15, 30, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.3s ease;
            cursor: default;
            min-width: 0;
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
            transform: translateY(-4px);
            box-shadow: 0 14px 34px rgba(74, 10, 21, 0.09), 0 4px 12px rgba(74, 10, 21, 0.04);
            border-color: rgba(139, 24, 40, 0.25);
        }

        .ovcaa-stat-card:hover::after {
            opacity: 1;
        }

        .ovcaa-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            min-width: 0;
        }

        .ovcaa-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .ovcaa-stat-card.is-green .ovcaa-stat-icon {
            background: rgba(22, 163, 74, 0.12);
            color: #16a34a;
            box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.2);
        }

        .ovcaa-stat-card.is-maroon .ovcaa-stat-icon {
            background: rgba(139, 24, 40, 0.12);
            color: #8b1828;
            box-shadow: inset 0 0 0 1px rgba(139, 24, 40, 0.2);
        }

        .ovcaa-stat-card.is-blue .ovcaa-stat-icon {
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.2);
        }

        .ovcaa-stat-card.is-amber .ovcaa-stat-icon {
            background: rgba(217, 119, 6, 0.12);
            color: #d97706;
            box-shadow: inset 0 0 0 1px rgba(217, 119, 6, 0.2);
        }

        .ovcaa-stat-card:hover .ovcaa-stat-icon {
            transform: scale(1.08) rotate(3deg);
        }

        .ovcaa-stat-top strong.val {
            font-size: clamp(1.15rem, 1.4vw, 1.6rem);
            color: #1a1618;
            letter-spacing: -0.035em;
            font-variant-numeric: tabular-nums;
            font-weight: 850;
            line-height: 1.1;
            margin-left: auto;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .ovcaa-stat-card span.ovcaa-stat-title {
            font-weight: 800;
            color: #1e293b;
            font-size: 0.86rem;
            letter-spacing: -0.01em;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ---------------------------------------------------------
           Analytics Card General
           --------------------------------------------------------- */
        .org-analytics-card {
            background: #ffffff;
            border-radius: 22px;
            border: 1.5px solid #f0e6e8;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 6px 24px rgba(90, 15, 30, 0.03);
            width: 100%;
            min-width: 0;
            overflow: hidden;
        }

        .org-card-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.2rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f6eff0;
            min-width: 0;
        }

        .org-card-header-flex h2 {
            font-size: 1.12rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 0;
        }

        .org-header-badge {
            font-size: 0.76rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            background: #fdf0f2;
            color: #8b1828;
            white-space: nowrap;
        }

        /* ---------------------------------------------------------
           2-Column Visual Charts Grid
           --------------------------------------------------------- */
        .org-analytics-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
            width: 100%;
            min-width: 0;
        }

        .org-analytics-grid-2 > * {
            min-width: 0;
            width: 100%;
        }

        .chart-container-wrap {
            position: relative;
            height: 300px;
            width: 100%;
            min-width: 0;
        }

        /* ---------------------------------------------------------
           In-Campus vs Off-Campus Split Cards
           --------------------------------------------------------- */
        .org-scope-comparison-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
            width: 100%;
            min-width: 0;
            margin-bottom: 1.25rem;
        }

        .org-scope-comparison-grid > * {
            min-width: 0;
            width: 100%;
        }

        .org-scope-card {
            background: #fdfafb;
            border-radius: 18px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.5rem;
            min-width: 0;
        }

        .org-scope-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 1.15rem;
        }

        .org-scope-head h3 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            color: #1a1618;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .org-scope-metrics-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #f0e6e8;
            margin-bottom: 0.85rem;
            min-width: 0;
        }

        .org-scope-metric-box {
            min-width: 0;
            overflow: hidden;
        }

        .org-scope-metric-box span {
            font-size: 0.76rem;
            color: #635b5e;
            display: block;
            margin-bottom: 0.2rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .org-scope-metric-box strong {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1a1618;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .org-scope-progress-bar {
            height: 8px;
            border-radius: 9999px;
            background: #f1e8e9;
            overflow: hidden;
            margin-top: 0.45rem;
            margin-bottom: 0.45rem;
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

        /* ---------------------------------------------------------
           Responsive Tables
           --------------------------------------------------------- */
        .org-table-responsive {
            width: 100%;
            min-width: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #f0e6e8;
            border-radius: 14px;
            background: #ffffff;
        }

        .org-fin-table-scroll {
            max-height: 26rem;
            margin-top: 1.25rem;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #f0e6e8;
            border-radius: 14px;
            scrollbar-width: thin;
            scrollbar-color: #d7b7bd transparent;
            width: 100%;
            min-width: 0;
        }

        .org-fin-table-scroll:focus-visible,
        .org-table-responsive:focus-visible {
            outline: 3px solid rgba(139, 24, 40, 0.2);
            outline-offset: 3px;
        }

        .org-fin-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            font-size: 0.86rem;
            text-align: left;
        }

        .org-fin-table th {
            position: sticky;
            top: 0;
            z-index: 2;
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
            padding: 0.85rem 1rem;
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
            white-space: nowrap;
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

        .org-fin-not-set {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: #b45309;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .oso-filter-static-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fdf0f2;
            border: 1.5px solid #f8d7dc;
            border-radius: 12px;
            padding: 0.45rem 0.85rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: #8b1828;
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* College Performance Table & Rankings */
        .org-college-table {
            width: 100%;
            min-width: 620px;
            border-collapse: collapse;
            font-size: 0.86rem;
            text-align: left;
        }

        .org-college-table th {
            padding: 0.75rem 1rem;
            font-size: 0.76rem;
            font-weight: 700;
            color: #7a7074;
            border-bottom: 1.5px solid #f0e6e8;
            background: #faf6f7;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .org-college-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
            white-space: nowrap;
        }

        .org-college-table tr:last-child td {
            border-bottom: none;
        }

        .org-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.2rem 0.6rem;
            border-radius: 8px;
            background: #f1e8e9;
            color: #554d50;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .org-pct-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.2rem 0.65rem;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .org-pct-badge.is-green {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .org-pct-badge.is-blue {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
        }

        .org-pct-badge.is-amber {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fef3c7;
        }

        .org-ranking-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.25rem;
            width: 100%;
            min-width: 0;
        }

        @media (max-width: 768px) {
            .org-ranking-grid {
                grid-template-columns: 1fr;
            }
        }

        .org-ranking-card {
            background: #faf6f7;
            border-radius: 14px;
            border: 1px solid #f0e6e8;
            padding: 1rem 1.15rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            min-width: 0;
        }

        .org-ranking-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.82rem;
            font-weight: 800;
            color: #1a1618;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .org-ranking-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .org-ranking-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #ffffff;
            border: 1px solid #f0e6e8;
            border-radius: 10px;
            padding: 0.55rem 0.85rem;
            font-size: 0.84rem;
        }

        .org-ranking-num {
            font-size: 0.75rem;
            font-weight: 800;
            color: #8b1828;
            background: #fdf0f2;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .org-ranking-name {
            font-weight: 700;
            color: #1a1618;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .org-ranking-empty {
            font-size: 0.8rem;
            color: #7a7074;
            padding: 0.5rem 0;
        }

        /* ---------------------------------------------------------
           Responsive Media Queries
           --------------------------------------------------------- */
        @media (max-width: 1200px) {
            .org-health-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .org-analytics-grid-2 {
                grid-template-columns: 1fr;
            }
            .org-scope-comparison-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .oso-filter-bar-card {
                padding: 0.75rem 0.9rem;
            }
            .oso-filter-bar-right {
                width: 100%;
                justify-content: space-between;
                padding-top: 0.5rem;
                border-top: 1px solid #f6eff0;
            }
            .oso-filter-select {
                max-width: 160px;
            }
            .org-analytics-card {
                padding: 1.25rem 1.25rem;
            }
        }

        @media (max-width: 600px) {
            .org-health-grid {
                grid-template-columns: 1fr;
            }
            .org-scope-metrics-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }
    </style>

    <div class="org-analytics-container">
    {{-- Interactive Period & Date Filter Toolbar (Matching OSO Dashboard Filter Style) --}}
    <section class="oso-filter-bar-card" aria-label="Analytics Intelligence Filters">
        <div class="oso-filter-bar-left">
            <div class="oso-filter-bar-title">
                <i class="bi bi-funnel-fill"></i>
                <span>Filters:</span>
            </div>

            {{-- Cross-organization filter for OSO, SDO, OVCAA, and OC; locked select for Student Org --}}
            <div class="oso-filter-group">
                <label for="organizationFilter" class="oso-filter-label"><i class="bi bi-building"></i> Organization</label>
                <div class="oso-select-wrapper">
                    @if ($isSo)
                        <select id="organizationFilter" class="oso-filter-select" disabled title="Locked to your assigned Student Org" style="cursor: not-allowed; opacity: 0.95; background: #fdf0f2; color: #8b1828; font-weight: 700;">
                            <option value="{{ $selectedOrganization }}" selected>{{ $selectedOrganization ?: 'Assigned Student Org' }}</option>
                        </select>
                        <i class="bi bi-lock-fill oso-select-arrow" style="color: #8b1828;"></i>
                    @else
                        <select id="organizationFilter" class="oso-filter-select" onchange="updateAnalyticsOrganizationFilter(this.value)">
                            <option value="">All Organizations</option>
                            @foreach (($organizations ?? collect()) as $organizationName)
                                <option value="{{ $organizationName }}" @selected(($selectedOrganization ?? '') === $organizationName)>{{ $organizationName }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down oso-select-arrow"></i>
                    @endif
                </div>
            </div>

            {{-- Calendar year filter for January–December charts --}}
            <div class="oso-filter-group">
                <label for="yearFilter" class="oso-filter-label"><i class="bi bi-calendar2-range"></i> Year</label>
                <div class="oso-select-wrapper">
                    <select id="yearFilter" class="oso-filter-select" onchange="updateAnalyticsData()">
                        @foreach (collect($activityFinancials ?? [])->pluck('year')->merge(range(now()->year - 2, now()->year))->filter()->unique()->sortDesc() as $calendarYear)
                            <option value="{{ $calendarYear }}" @selected((int) $calendarYear === now()->year)>{{ $calendarYear }} · Jan–Dec{{ (int) $calendarYear === now()->year ? ' (Current)' : '' }}</option>
                        @endforeach
                        <option value="all">All Calendar Years</option>
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
        <article class="ovcaa-stat-card liquid-glass is-green" title="Overall Budget Health: {{ $overview['healthScore'] }}%">
            <div class="ovcaa-stat-top">
                <div class="ovcaa-stat-icon is-green">
                    <i class="bi bi-heart-pulse-fill"></i>
                </div>
                <strong class="val" id="kpiHealthScore">{{ $overview['healthScore'] }}% · {{ $overview['healthScore'] >= 80 ? 'Optimal' : ($overview['healthScore'] >= 60 ? 'Watch' : 'Needs Attention') }}</strong>
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

    {{-- College Performance (controller-driven; below the cards) --}}
    @isset($collegeStats)
        <section class="org-analytics-card" style="margin-bottom: 0;" aria-label="College Performance">
            <div class="org-card-header-flex">
                <h2>
                    <span class="org-card-icon" style="background:#fdf0f2; color:#8b1828; width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;">
                        <i class="bi bi-building"></i>
                    </span>
                    @if ($isSo)
                        College Division Performance ({{ $collegeStats->first()['college'] ?? 'Your College' }})
                    @else
                        College Performance & University Breakdown
                    @endif
                </h2>
                <span class="org-header-badge">
                    @if ($isSo)
                        Assigned College
                    @else
                        Academic Divisions
                    @endif
                </span>
            </div>
            @empty($collegeStats)
                <p style="margin:0; color:#7a7074; font-size:0.88rem; padding: 1.5rem 0; text-align: center;">No college performance data available for this period.</p>
            @else
                <div class="org-table-responsive">
                    <table class="org-college-table">
                        <thead>
                            <tr>
                                <th>College</th>
                                <th style="text-align: center;">Activities</th>
                                <th style="text-align: center;">Completion</th>
                                <th style="text-align: center;">Utilization</th>
                                <th style="text-align: right;">Approved Budget</th>
                                <th style="text-align: right;">Implemented</th>
                            </tr>
                        </thead>
                        <tbody id="collegePerformanceTableBody">
                            @foreach ($collegeStats as $row)
                                <tr>
                                    <td style="font-weight: 700;">{{ $row['college'] ?? '—' }}</td>
                                    <td style="text-align: center;">
                                        <span class="org-count-badge">{{ $row['activities'] ?? 0 }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="org-pct-badge {{ ($row['completion_percent'] ?? 0) >= 70 ? 'is-green' : (($row['completion_percent'] ?? 0) >= 40 ? 'is-blue' : 'is-amber') }}">
                                            {{ $row['completion_percent'] ?? 0 }}%
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="org-pct-badge {{ ($row['utilization_percent'] ?? 0) >= 70 ? 'is-green' : (($row['utilization_percent'] ?? 0) >= 40 ? 'is-blue' : 'is-amber') }}">
                                            {{ $row['utilization_percent'] ?? 0 }}%
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">₱{{ number_format($row['approved_budget'] ?? 0) }}</td>
                                    <td style="text-align: right; font-weight: 700; color: #8b1828;">₱{{ number_format($row['implemented_budget'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endempty

            @if (!empty($topUtilization) || !empty($topActivities))
                <div class="org-ranking-grid">
                    @isset($topUtilization)
                        <div class="org-ranking-card">
                            <div class="org-ranking-header">
                                <i class="bi bi-graph-up-arrow" style="color: #059669;"></i>
                                <span>Top Utilization</span>
                            </div>
                            <div class="org-ranking-list" id="topUtilizationList">
                                @forelse ($topUtilization as $index => $item)
                                    <div class="org-ranking-item">
                                        <span class="org-ranking-num">#{{ $index + 1 }}</span>
                                        <span class="org-ranking-name">{{ $item['college'] ?? '—' }}</span>
                                        <span class="org-pct-badge is-green">{{ $item['utilization_percent'] ?? 0 }}%</span>
                                    </div>
                                @empty
                                    <div class="org-ranking-empty">No data available</div>
                                @endforelse
                            </div>
                        </div>
                    @endisset
                    @isset($topActivities)
                        <div class="org-ranking-card">
                            <div class="org-ranking-header">
                                <i class="bi bi-award-fill" style="color: #2563eb;"></i>
                                <span>Top by Activities</span>
                            </div>
                            <div class="org-ranking-list" id="topActivitiesList">
                                @forelse ($topActivities as $index => $item)
                                    <div class="org-ranking-item">
                                        <span class="org-ranking-num">#{{ $index + 1 }}</span>
                                        <span class="org-ranking-name">{{ $item['college'] ?? '—' }}</span>
                                        <span class="org-count-badge">{{ $item['activities'] ?? 0 }} activities</span>
                                    </div>
                                @empty
                                    <div class="org-ranking-empty">No data available</div>
                                @endforelse
                            </div>
                        </div>
                    @endisset
                </div>
            @endif
        </section>
    @endisset

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
                <span class="org-header-badge" id="chartPeriodBadge">{{ now()->year }} · Jan–Dec · All Semesters</span>
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
            <div class="org-scope-card" data-scope-card="in_campus">
                <div class="org-scope-head">
                    <h3><i class="bi bi-building"></i> In-Campus Activities</h3>
                    <span class="org-scope-pill is-in" data-scope-count>0 Activities</span>
                </div>
                <div class="org-scope-metrics-row">
                    <div class="org-scope-metric-box">
                        <span>Allocated</span>
                        <strong data-scope-allocated>₱0</strong>
                    </div>
                    <div class="org-scope-metric-box">
                        <span>Utilized</span>
                        <strong data-scope-utilized>₱0</strong>
                    </div>
                    <div class="org-scope-metric-box">
                        <span>Remaining</span>
                        <strong style="color:#8b1828;" data-scope-remaining>₱0</strong>
                    </div>
                </div>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:700;">
                        <span>In-Campus Utilization</span>
                        <span style="color:#8b1828;" data-scope-rate>0%</span>
                    </div>
                    <div class="org-scope-progress-bar">
                        <div class="org-scope-progress-fill" data-scope-progress style="width: 0%;"></div>
                    </div>
                    <small style="font-size:0.76rem; color:#635b5e;">Filtered activity totals from the selected period.</small>
                </div>
            </div>

            {{-- Off-Campus Overview --}}
            <div class="org-scope-card is-off-campus" data-scope-card="local_off_campus">
                <div class="org-scope-head">
                    <h3><i class="bi bi-bus-front"></i> Off-Campus Activities</h3>
                    <span class="org-scope-pill is-off" data-scope-count>0 Activities</span>
                </div>
                <div class="org-scope-metrics-row">
                    <div class="org-scope-metric-box">
                        <span>Allocated</span>
                        <strong data-scope-allocated>₱0</strong>
                    </div>
                    <div class="org-scope-metric-box">
                        <span>Utilized</span>
                        <strong data-scope-utilized>₱0</strong>
                    </div>
                    <div class="org-scope-metric-box">
                        <span>Remaining</span>
                        <strong style="color:#b45309;" data-scope-remaining>₱0</strong>
                    </div>
                </div>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:700;">
                        <span>Off-Campus Utilization</span>
                        <span style="color:#b45309;" data-scope-rate>0%</span>
                    </div>
                    <div class="org-scope-progress-bar">
                        <div class="org-scope-progress-fill" data-scope-progress style="width: 0%;"></div>
                    </div>
                    <small style="font-size:0.76rem; color:#635b5e;">Filtered activity totals from the selected period.</small>
                </div>
            </div>
        </div>

        {{-- Detailed Activity Financial Breakdown Matrix --}}
        <div class="org-fin-table-scroll" role="region" aria-label="Activity financial breakdown" tabindex="0">
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
                    @foreach ($activityFinancials as $index => $row)
                        <tr data-row-index="{{ $index }}" data-scope="{{ $row['scope'] }}" data-month="{{ $row['month'] }}" data-year="{{ $row['year'] }}" data-academic-year="{{ $row['academic_year'] ?? '' }}" data-semester="{{ $row['semester'] ?? '' }}">
                            <td>
                                <strong>{{ $row['name'] }}</strong>
                                <small style="display:block; color:#786f73; font-size:0.76rem;">Target Date: {{ $row['month'] }} {{ $row['year'] }}</small>
                            </td>
                            <td>
                                <span class="org-scope-pill {{ $row['scope'] === 'in_campus' ? 'is-in' : 'is-off' }}">
                                    {{ $row['scope_label'] }}
                                </span>
                            </td>
                            <td>
                                @if ((int) $row['allocated'] > 0)
                                    ₱{{ number_format($row['allocated']) }}
                                @else
                                    <span class="org-fin-not-set"><i class="bi bi-exclamation-circle"></i> Not set</span>
                                @endif
                            </td>
                            <td>₱{{ number_format($row['utilized']) }}</td>
                            <td>
                                @if ((int) $row['allocated'] > 0)
                                    <span style="color: {{ $row['remaining'] > 0 ? '#16a34a' : '#786f73' }}; font-weight:700;">
                                        ₱{{ number_format($row['remaining']) }}
                                    </span>
                                @else
                                    <span class="org-fin-not-set" title="Edit the activity to set its allocated budget">—</span>
                                @endif
                            </td>
                            <td>
                                @if ((int) $row['allocated'] > 0)
                                    <span style="font-weight:700; color: {{ $row['burn_rate'] >= 80 ? '#8b1828' : '#2563eb' }};">
                                        {{ $row['burn_rate'] }}%
                                    </span>
                                @else
                                    <span class="org-fin-not-set" title="Burn rate becomes available after a budget is set">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="org-status-pill org-status-{{ $row['status_style'] }}">
                                    <span class="org-status-dot"></span> {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="analyticsEmptyRow" style="display:none;">
                        <td colspan="7" style="text-align:center;color:#7a7074;padding:1.6rem;">No activity records match the selected filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    {{-- Load Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let utilVsAllocChartInstance = null;
        let trendChartInstance = null;
        const analyticsRows = @json($activityFinancials ?? []);
        const currentCalendarYear = @json((string) now()->year);
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const semesterNames = { sem1: '1st Semester', sem2: '2nd Semester', midyear: 'Midyear' };
        const semesterMonths = { sem1: [8, 9, 10, 11, 12], sem2: [1, 2, 3, 4, 5], midyear: [6, 7] };

        const reportRows = analyticsRows.map((row, index) => {
            const year = Number(row.year) || 0;
            const monthNumber = Number(row.month_number) || monthNames.indexOf(row.month) + 1;
            const academicYear = row.academic_year || (monthNumber >= 8
                ? `${year}-${year + 1}`
                : `${year - 1}-${year}`);

            return {
                ...row,
                __index: index,
                allocated: Number(row.allocated) || 0,
                utilized: Number(row.utilized) || 0,
                remaining: Number(row.remaining) || 0,
                burnRate: Number(row.burn_rate) || 0,
                year,
                monthNumber,
                academicYear,
                academicYearEnd: Number(String(academicYear).split('-')[1]) || year,
                semester: row.semester || (semesterMonths.sem1.includes(monthNumber)
                    ? 'sem1'
                    : semesterMonths.sem2.includes(monthNumber) ? 'sem2' : semesterMonths.midyear.includes(monthNumber) ? 'midyear' : null)
            };
        });

        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[character]);

        const money = (value) => `₱${Math.round(Number(value) || 0).toLocaleString('en-US')}`;

        function getAnalyticsFilters() {
            return {
                year: document.getElementById('yearFilter')?.value || currentCalendarYear,
                semester: document.getElementById('semesterFilter')?.value || 'all',
                month: document.getElementById('monthFilter')?.value || 'all',
                scope: document.getElementById('scopeFilter')?.value || 'all'
            };
        }

        function rowMatchesFilters(row, filters) {
            const yearMatches = filters.year === 'all' || row.year === Number(filters.year);
            const semesterMatches = filters.semester === 'all' || row.semester === filters.semester;
            const monthMatches = filters.month === 'all' || row.month === filters.month;
            const scopeMatches = filters.scope === 'all' || row.scope === filters.scope;

            return yearMatches && semesterMatches && monthMatches && scopeMatches;
        }

        function summarizeRows(rows) {
            const allocated = rows.reduce((sum, row) => sum + row.allocated, 0);
            const utilized = rows.reduce((sum, row) => sum + row.utilized, 0);
            const remaining = Math.max(0, allocated - utilized);
            const completed = rows.filter((row) => ['oc_approved', 'completed'].includes(row.status_key)).length;
            const completion = rows.length ? (completed / rows.length) * 100 : 0;
            const burnRate = allocated > 0 ? (utilized / allocated) * 100 : 0;
            const budgetHealth = allocated > 0 ? Math.max(0, 100 - Math.abs(burnRate - 75) * 2) : 0;
            const health = rows.length ? Math.round((0.6 * budgetHealth) + (0.4 * completion)) : 0;

            return { allocated, utilized, remaining, completed, completion, burnRate, health };
        }

        function healthLabel(score, rows) {
            if (!rows.length) return 'No Data';
            if (score >= 80) return 'Optimal';
            if (score >= 60) return 'Watch';
            return 'Needs Attention';
        }

        function aggregateByCollege(rows) {
            const groups = new Map();
            rows.forEach((row) => {
                const college = row.college || 'Unassigned';
                if (!groups.has(college)) groups.set(college, []);
                groups.get(college).push(row);
            });

            return Array.from(groups, ([college, group]) => {
                const totals = summarizeRows(group);
                return {
                    college,
                    activities: group.length,
                    completed: totals.completed,
                    completion: group.length ? Math.round((totals.completed / group.length) * 100) : 0,
                    allocated: totals.allocated,
                    utilized: totals.utilized,
                    utilization: totals.allocated > 0 ? Math.round((totals.utilized / totals.allocated) * 100) : 0
                };
            }).sort((a, b) => b.utilization - a.utilization || b.activities - a.activities || a.college.localeCompare(b.college));
        }

        function renderCollegePerformance(rows) {
            const tbody = document.getElementById('collegePerformanceTableBody');
            if (!tbody) return;

            const colleges = aggregateByCollege(rows);
            if (!colleges.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="padding:1.5rem 0.5rem;text-align:center;color:#7a7074;">No college performance data matches the selected filters.</td></tr>';
            } else {
                tbody.innerHTML = colleges.map((item) => {
                    const compClass = item.completion >= 70 ? 'is-green' : (item.completion >= 40 ? 'is-blue' : 'is-amber');
                    const utilClass = item.utilization >= 70 ? 'is-green' : (item.utilization >= 40 ? 'is-blue' : 'is-amber');
                    return `
                    <tr>
                        <td style="font-weight:700;">${escapeHtml(item.college)}</td>
                        <td style="text-align:center;"><span class="org-count-badge">${item.activities}</span></td>
                        <td style="text-align:center;"><span class="org-pct-badge ${compClass}">${item.completion}%</span></td>
                        <td style="text-align:center;"><span class="org-pct-badge ${utilClass}">${item.utilization}%</span></td>
                        <td style="text-align:right;font-weight:600;">${money(item.allocated)}</td>
                        <td style="text-align:right;font-weight:700;color:#8b1828;">${money(item.utilized)}</td>
                    </tr>
                    `;
                }).join('');
            }

            const utilizationList = document.getElementById('topUtilizationList');
            if (utilizationList) {
                const topUtil = colleges.slice(0, 3);
                if (!topUtil.length) {
                    utilizationList.innerHTML = '<div class="org-ranking-empty">No data available</div>';
                } else {
                    utilizationList.innerHTML = topUtil.map((item, index) => `
                        <div class="org-ranking-item">
                            <span class="org-ranking-num">#${index + 1}</span>
                            <span class="org-ranking-name">${escapeHtml(item.college)}</span>
                            <span class="org-pct-badge is-green">${item.utilization}%</span>
                        </div>
                    `).join('');
                }
            }

            const activityList = document.getElementById('topActivitiesList');
            if (activityList) {
                const topAct = [...colleges]
                    .sort((a, b) => b.activities - a.activities || a.college.localeCompare(b.college))
                    .slice(0, 3);
                if (!topAct.length) {
                    activityList.innerHTML = '<div class="org-ranking-empty">No data available</div>';
                } else {
                    activityList.innerHTML = topAct.map((item, index) => `
                        <div class="org-ranking-item">
                            <span class="org-ranking-num">#${index + 1}</span>
                            <span class="org-ranking-name">${escapeHtml(item.college)}</span>
                            <span class="org-count-badge">${item.activities} activities</span>
                        </div>
                    `).join('');
                }
            }
        }

        function renderScopeCards(rows) {
            document.querySelectorAll('[data-scope-card]').forEach((card) => {
                const scopeRows = rows.filter((row) => row.scope === card.dataset.scopeCard);
                const totals = summarizeRows(scopeRows);
                const rate = totals.allocated > 0 ? (totals.utilized / totals.allocated) * 100 : 0;
                const countLabel = `${scopeRows.length} ${scopeRows.length === 1 ? 'Activity' : 'Activities'}`;

                card.querySelector('[data-scope-count]')?.replaceChildren(document.createTextNode(countLabel));
                card.querySelector('[data-scope-allocated]')?.replaceChildren(document.createTextNode(money(totals.allocated)));
                card.querySelector('[data-scope-utilized]')?.replaceChildren(document.createTextNode(money(totals.utilized)));
                card.querySelector('[data-scope-remaining]')?.replaceChildren(document.createTextNode(money(totals.remaining)));
                card.querySelector('[data-scope-rate]')?.replaceChildren(document.createTextNode(`${rate.toFixed(1)}%`));
                const progress = card.querySelector('[data-scope-progress]');
                if (progress) progress.style.width = `${Math.min(100, rate)}%`;
            });
        }

        function updateKpis(rows) {
            const totals = summarizeRows(rows);
            const health = document.getElementById('kpiHealthScore');
            const allocated = document.getElementById('kpiAllocated');
            const utilized = document.getElementById('kpiUtilized');
            const remaining = document.getElementById('kpiRemaining');

            if (health) health.textContent = `${totals.health}% · ${healthLabel(totals.health, rows)}`;
            if (allocated) allocated.textContent = money(totals.allocated);
            if (utilized) utilized.textContent = money(totals.utilized);
            if (remaining) remaining.textContent = money(totals.remaining);
        }

        function updateActivityRows(rows) {
            const matchingIndexes = new Set(rows.map((row) => row.__index));
            const tableRows = document.querySelectorAll('#activityFinancialTableBody tr[data-row-index]');
            tableRows.forEach((row) => {
                row.style.display = matchingIndexes.has(Number(row.dataset.rowIndex)) ? '' : 'none';
            });
            const emptyRow = document.getElementById('analyticsEmptyRow');
            if (emptyRow) emptyRow.style.display = rows.length ? 'none' : '';
        }

        function buildMonthAxis(filters) {
            // Keep all twelve months visible even when a semester/month filter
            // leaves empty buckets. All-years mode aggregates each calendar month.
            return monthNames.map((label, index) => ({
                label,
                monthNumber: index + 1,
                year: filters.year === 'all' ? null : Number(filters.year),
            }));
        }

        function buildMonthlySeries(rows, filters) {
            const axis = buildMonthAxis(filters);
            const allocated = [];
            const utilized = [];

            axis.forEach((point) => {
                const monthRows = rows.filter((row) => {
                    if (row.monthNumber !== point.monthNumber) return false;
                    return point.year === null || row.year === point.year;
                });
                allocated.push(monthRows.reduce((sum, row) => sum + row.allocated, 0));
                utilized.push(monthRows.reduce((sum, row) => sum + row.utilized, 0));
            });

            let allocatedRunning = 0;
            let utilizedRunning = 0;
            const cumulativeAlloc = allocated.map((value) => {
                allocatedRunning += value;
                return allocatedRunning;
            });
            const cumulativeUtil = utilized.map((value) => {
                utilizedRunning += value;
                return utilizedRunning;
            });

            return { labels: axis.map((point) => point.label), allocated, utilized, cumulativeAlloc, cumulativeUtil };
        }

        function updateCharts(rows, filters) {
            const series = buildMonthlySeries(rows, filters);
            if (utilVsAllocChartInstance) {
                utilVsAllocChartInstance.data.labels = series.labels;
                utilVsAllocChartInstance.data.datasets[0].data = series.allocated;
                utilVsAllocChartInstance.data.datasets[1].data = series.utilized;
                utilVsAllocChartInstance.update();
            }
            if (trendChartInstance) {
                trendChartInstance.data.labels = series.labels;
                trendChartInstance.data.datasets[0].data = series.cumulativeAlloc;
                trendChartInstance.data.datasets[1].data = series.cumulativeUtil;
                trendChartInstance.update();
            }
        }

        function initCharts() {
            const ctx1 = document.getElementById('utilVsAllocChart').getContext('2d');
            utilVsAllocChartInstance = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Budget Allocated (₱)',
                            data: [],
                            backgroundColor: 'rgba(224, 168, 178, 0.75)',
                            borderColor: '#c43b52',
                            borderWidth: 1.5,
                            borderRadius: 6,
                        },
                        {
                            label: 'Budget Utilized (₱)',
                            data: [],
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
                            ticks: { autoSkip: false, maxRotation: 45, color: '#7a7074', font: { family: 'inherit', size: 11 } },
                            grid: { display: false }
                        }
                    }
                }
            });

            const ctx2 = document.getElementById('trendAnalysisChart').getContext('2d');
            trendChartInstance = new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Cumulative Allocation (₱)',
                            data: [],
                            borderColor: '#d97706',
                            backgroundColor: 'rgba(217, 119, 6, 0.06)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3
                        },
                        {
                            label: 'Cumulative Spending (₱)',
                            data: [],
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
                            ticks: { autoSkip: false, maxRotation: 45, color: '#7a7074', font: { family: 'inherit', size: 11 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        function resetAnalyticsFilters() {
            document.getElementById('yearFilter').value = currentCalendarYear;
            document.getElementById('semesterFilter').value = 'all';
            document.getElementById('monthFilter').value = 'all';
            document.getElementById('scopeFilter').value = 'all';
            updateAnalyticsData();
        }

        function updateAnalyticsOrganizationFilter(organization) {
            const url = new URL(window.location.href);
            if (organization) {
                url.searchParams.set('organization', organization);
            } else {
                url.searchParams.delete('organization');
            }
            window.location.assign(url.toString());
        }

        function updateAnalyticsData() {
            const filters = getAnalyticsFilters();
            const filteredRows = reportRows.filter((row) => rowMatchesFilters(row, filters));

            // Build period badge text
            const periodText = filters.year === 'all'
                ? 'All Calendar Years · Jan–Dec'
                : `${filters.year} · Jan–Dec`;
            let detailText = 'All Semesters';
            if (filters.month !== 'all') {
                detailText = filters.month;
            } else if (filters.semester !== 'all') {
                detailText = semesterNames[filters.semester] || filters.semester;
            }
            if (filters.scope !== 'all') {
                detailText += filters.scope === 'in_campus' ? ' · In-Campus' : ' · Off-Campus';
            }
            const chartBadge = document.getElementById('chartPeriodBadge');
            if (chartBadge) {
                chartBadge.textContent = `${periodText} · ${detailText}`;
            }

            // Every report block is derived from the same filtered rows.
            updateKpis(filteredRows);
            renderCollegePerformance(filteredRows);
            renderScopeCards(filteredRows);
            updateActivityRows(filteredRows);
            updateCharts(filteredRows, filters);

            // Update live filter status badge.
            const statusBadge = document.getElementById('analyticsActiveFilterBadge');
            const isFiltered = filters.year !== currentCalendarYear || filters.semester !== 'all' || filters.month !== 'all' || filters.scope !== 'all';
            if (statusBadge) {
                statusBadge.classList.toggle('is-filtered', isFiltered);
                statusBadge.innerHTML = isFiltered
                    ? `<i class="bi bi-funnel-fill"></i> Filtered (${filteredRows.length} ${filteredRows.length === 1 ? 'result' : 'results'})`
                    : `<i class="bi bi-check2-circle"></i> Live Insights (${filteredRows.length} results)`;
            }
        }

        window.addEventListener('resize', function() {
            if (utilVsAllocChartInstance) {
                utilVsAllocChartInstance.resize();
            }
            if (trendChartInstance) {
                trendChartInstance.resize();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            updateAnalyticsData();
        });
    </script>
@endsection
