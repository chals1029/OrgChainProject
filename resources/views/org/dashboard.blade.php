@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSdo = $role === 'sdo';
    $isOvcaa = $role === 'ovcaa';
    $isSo = !$isOso && !$isSdo && !$isOvcaa;
@endphp

@section('title', 'Dashboard')

@section('header')
    <h1><strong>Dashboard</strong></h1>
    @if ($isOso)
        <p class="org-welcome">Welcome back, OSO Officer! </p>
    @elseif ($isSdo)
        <p class="org-welcome">Welcome back, Sustainable Development Office! </p>
    @elseif ($isOvcaa)
        <p class="org-welcome">Welcome back, OVCAA Reviewer! </p>
    @else
        <p class="org-welcome">Welcomeback Student Organization Representative!</p>
    @endif
@endsection

@section('actions')
    @if ($isSdo)
        <a href="{{ route('office.activities') }}" class="org-btn org-btn-primary" style="background: #15803d; box-shadow: 0 4px 14px rgba(21,128,61,0.25);">
            <i class="bi bi-leaf-fill"></i> SDG Document Review
        </a>
    @elseif ($isOvcaa)
        <a href="{{ route('office.activities') }}" class="org-btn org-btn-primary" style="background: #1d4ed8; box-shadow: 0 4px 14px rgba(29,78,216,0.25);">
            <i class="bi bi-patch-check-fill"></i> Final Approval Queue
        </a>
    @elseif ($isOso)
        <a href="{{ route('office.activities') }}" class="org-btn org-btn-primary">
            <i class="bi bi-file-earmark-check-fill"></i> Review Proposals
        </a>
    @else
        <a href="{{ route('office.activities.create') }}" class="org-btn org-btn-primary">
            <i class="bi bi-plus-lg"></i> Create Activity / Event
        </a>
    @endif
    <a href="{{ route('office.calendar') }}" class="org-btn org-btn-outline">
        <i class="bi bi-calendar3"></i> Open Calendar
    </a>
@endsection

@section('content')
    <style>
        /* Dashboard Container & Cards */
        .org-dash-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Top 4 KPI Cards */
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
        .org-kpi-icon.is-green { background: #dcfce7; color: #16a34a; }
        .org-kpi-icon.is-blue { background: #e0f2fe; color: #0284c7; }
        .org-kpi-icon.is-amber { background: #fef3c7; color: #d97706; }

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

        /* 2-Column Middle Section */
        .org-dash-2col {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 1.25rem;
        }

        /* Budget Snapshot Card */
        .org-dash-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
        }

        .org-dash-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
        }

        .org-dash-card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }

        .org-dash-link {
            font-size: 0.82rem;
            font-weight: 700;
            color: #8b1828;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .org-dash-link:hover {
            text-decoration: underline;
        }

        .org-budget-hero-box {
            background: #7a1222;
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            color: #ffffff;
            margin-bottom: 1.15rem;
        }

        .org-budget-hero-box span {
            font-size: 0.8rem;
            opacity: 0.9;
            display: block;
            margin-bottom: 0.25rem;
        }

        .org-budget-hero-box h2 {
            font-size: 1.95rem;
            font-weight: 800;
            margin: 0 0 0.25rem 0;
            line-height: 1;
        }

        .org-budget-hero-box small {
            font-size: 0.76rem;
            opacity: 0.85;
            display: block;
        }

        .org-budget-stat-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.84rem;
            color: #554d50;
            margin-bottom: 0.35rem;
        }

        .org-budget-stat-row strong {
            font-weight: 700;
            color: #1a1618;
        }

        .org-budget-stat-row strong.is-green {
            color: #16a34a;
        }

        .org-mini-progress {
            height: 6px;
            background: #f1e8e9;
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 0.9rem;
        }

        .org-mini-fill-maroon {
            height: 100%;
            background: #7a1222;
        }

        .org-mini-fill-green {
            height: 100%;
            background: #16a34a;
        }

        .org-budget-sub-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.4rem;
        }

        .org-budget-sub-box {
            padding: 0.75rem 0.85rem;
            border-radius: 12px;
            background: #faf4f5;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .org-budget-sub-box span {
            font-size: 0.72rem;
            color: #7a7074;
            font-weight: 600;
        }

        .org-budget-sub-box strong {
            font-size: 0.98rem;
            font-weight: 800;
            color: #1a1618;
        }

        .org-budget-sub-box.is-green strong {
            color: #16a34a;
        }

        /* Pending Action Items List */
        .org-action-items-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .org-action-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            border-radius: 14px;
            background: #faf4f5;
            border: 1px solid #f2e6e8;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .org-action-item:hover {
            background: #f5eaec;
            border-color: #ebd5d8;
            transform: translateX(2px);
        }

        .org-action-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .org-action-left i {
            font-size: 1.15rem;
        }

        .org-action-item.is-red .org-action-left i { color: #dc2626; }
        .org-action-item.is-yellow .org-action-left i { color: #d97706; }
        .org-action-item.is-blue .org-action-left i { color: #2563eb; }
        .org-action-item.is-purple .org-action-left i { color: #7e22ce; }
        .org-action-item.is-green .org-action-left i { color: #15803d; }

        .org-action-info strong {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a1618;
            line-height: 1.25;
        }

        .org-action-info small {
            display: block;
            font-size: 0.76rem;
            color: #7a7074;
            margin-top: 0.15rem;
        }

        .org-action-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .org-chip-urgent {
            background: #fee2e2;
            color: #dc2626;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 0.15rem 0.45rem;
            border-radius: 6px;
            text-transform: uppercase;
        }

        /* 3. Approval Workflow Pipeline Section */
        .org-pipeline-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
        }

        .org-pipeline-header-row {
            display: grid;
            grid-template-columns: 220px 1fr 140px;
            gap: 1.5rem;
            align-items: center;
            padding-bottom: 0.75rem;
            border-bottom: 1.5px solid #f4ecee;
            font-size: 0.78rem;
            font-weight: 800;
            color: #7a7074;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .org-stage-names {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            text-align: center;
        }

        .org-pipeline-row {
            display: grid;
            grid-template-columns: 220px 1fr 140px;
            gap: 1.5rem;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f8f1f2;
        }

        .org-pipeline-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .org-pipeline-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a1618;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .org-pipeline-stepper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .org-stepper-track-bg {
            position: absolute;
            left: 10%;
            right: 10%;
            height: 4px;
            background: #f0e6e8;
            border-radius: 9999px;
            z-index: 1;
        }

        .org-stepper-track-fill {
            position: absolute;
            left: 10%;
            height: 4px;
            background: #7a1222;
            border-radius: 9999px;
            z-index: 2;
        }

        .org-stepper-nodes {
            position: relative;
            z-index: 3;
            width: 100%;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            justify-items: center;
        }

        .org-stepper-node {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            background: #ffffff;
            border: 2px solid #d4c4c7;
        }

        .org-stepper-node.is-done {
            background: #7a1222;
            border-color: #7a1222;
            color: #ffffff;
        }

        .org-stepper-node.is-active-gold {
            background: #ca8a04;
            border-color: #ca8a04;
            box-shadow: 0 0 0 3px rgba(202, 138, 4, 0.2);
        }

        .org-stepper-node.is-muted-node {
            background: #7a1222;
            border-color: #7a1222;
        }

        .org-stepper-node.is-returned {
            background: #dc2626;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
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

        /* Bottom Row */
        .org-dash-bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .org-upcoming-card-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0.85rem;
            border-radius: 14px;
            background: #faf4f5;
            margin-bottom: 0.65rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .org-upcoming-card-item:hover {
            background: #f5eaec;
            transform: translateX(2px);
        }

        .org-upcoming-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .org-date-badge {
            width: 44px;
            height: 44px;
            background: #7a1222;
            color: #ffffff;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            line-height: 1;
            flex-shrink: 0;
        }

        .org-date-badge strong {
            font-size: 1.05rem;
            font-weight: 800;
        }

        .org-date-badge small {
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 700;
        }

        .org-upcoming-meta strong {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a1618;
        }

        .org-upcoming-meta small {
            display: block;
            font-size: 0.76rem;
            color: #7a7074;
            margin-top: 0.15rem;
        }

        /* Recent Updates List */
        .org-recent-updates-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .org-recent-update-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f8f1f2;
        }

        .org-recent-update-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .org-role-chip {
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        .org-role-chip.is-oso { background: #e0f2fe; color: #0369a1; }
        .org-role-chip.is-sdo { background: #dcfce7; color: #15803d; }
        .org-role-chip.is-system { background: #f3e8ff; color: #7e22ce; }
        .org-role-chip.is-ovcaa { background: #dbeafe; color: #1d4ed8; }

        .org-recent-update-text strong {
            display: block;
            font-size: 0.86rem;
            font-weight: 700;
            color: #1a1618;
        }

        .org-recent-update-text small {
            display: block;
            font-size: 0.76rem;
            color: #8a8084;
            margin-top: 0.1rem;
        }

        /* OSO Specific Dashboard Styles */
        .oso-kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }

        .oso-kpi-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .oso-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(90, 15, 30, 0.06);
        }

        .oso-kpi-badge {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
        }

        .oso-kpi-badge.is-pink { background: #fdf0f2; color: #8b1828; }
        .oso-kpi-badge.is-yellow { background: #fef9c3; color: #ca8a04; }
        .oso-kpi-badge.is-green { background: #dcfce7; color: #16a34a; }

        .oso-kpi-card.is-pink .oso-kpi-num { color: #8b1828; }
        .oso-kpi-card.is-yellow .oso-kpi-num { color: #ca8a04; }
        .oso-kpi-card.is-green .oso-kpi-num { color: #16a34a; }

        .oso-kpi-num {
            font-size: 2.15rem;
            font-weight: 800;
            line-height: 1;
        }

        .oso-kpi-title {
            font-size: 0.98rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0.25rem 0 0.05rem;
        }

        .oso-kpi-sub {
            font-size: 0.78rem;
            color: #7a7074;
            margin: 0;
        }

        .oso-middle-row {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 1.25rem;
        }

        .oso-chart-legend-container {
            display: flex;
            align-items: center;
            gap: 2.5rem;
            padding: 0.75rem 0 1.25rem;
        }

        .oso-pie-chart-wrap {
            position: relative;
            width: 130px;
            height: 130px;
            flex-shrink: 0;
        }

        .oso-pie-chart {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: conic-gradient(
                #8b1828 0% 40%, 
                #ffffff 40% 40.5%, 
                #10b981 40.5% 80%, 
                #ffffff 80% 80.5%, 
                #ca8a04 80.5% 99.5%, 
                #ffffff 99.5% 100%
            );
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }

        .oso-legend-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .oso-legend-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .oso-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .oso-legend-dot.is-maroon { background: #8b1828; }
        .oso-legend-dot.is-green { background: #10b981; }
        .oso-legend-dot.is-yellow { background: #ca8a04; }

        .oso-legend-text strong {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a1618;
            line-height: 1.2;
        }

        .oso-legend-text small {
            display: block;
            font-size: 0.76rem;
            color: #7a7074;
        }

        .oso-scope-split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
            margin-top: 0.65rem;
        }

        .oso-scope-box {
            border-radius: 14px;
            padding: 1rem 0.85rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2rem;
        }

        .oso-scope-box.is-incampus {
            background: #fdf0f2;
            border: 1px solid #fae1e5;
        }

        .oso-scope-box.is-offcampus {
            background: #fef9c3;
            border: 1px solid #fef08a;
        }

        .oso-scope-box-label {
            font-size: 0.78rem;
            color: #7a7074;
            font-weight: 600;
        }

        .oso-scope-box-num {
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .oso-scope-box.is-incampus .oso-scope-box-num {
            color: #8b1828;
        }

        .oso-scope-box.is-offcampus .oso-scope-box-num {
            color: #ca8a04;
        }

        .oso-scope-box-pct {
            font-size: 0.75rem;
            color: #7a7074;
        }

        .oso-table-container {
            overflow-x: auto;
        }

        .oso-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .oso-table th {
            font-size: 0.74rem;
            font-weight: 800;
            color: #7a7074;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.75rem 0.85rem;
            border-bottom: 1.5px solid #f0e6e8;
        }

        .oso-table td {
            padding: 0.95rem 0.85rem;
            font-size: 0.86rem;
            color: #1a1618;
            border-bottom: 1px solid #faf0f2;
            vertical-align: middle;
        }

        .oso-table tr:last-child td {
            border-bottom: none;
        }

        .org-btn-view-pill {
            padding: 0.35rem 1.35rem;
            border-radius: 9999px;
            background: #7a1222;
            color: #ffffff !important;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .org-btn-view-pill:hover {
            background: #600e1b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(122, 18, 34, 0.25);
        }
    </style>

    @if ($isOso)
        {{-- DEDICATED OSO OFFICER DASHBOARD --}}
        <div class="org-dash-grid">
            {{-- 1. Top 3 KPI Cards for OSO --}}
            <div class="oso-kpi-row">
                {{-- Card 1: Total Organizations --}}
                <article class="oso-kpi-card is-pink">
                    <div class="oso-kpi-num">12</div>
                    <h3 class="oso-kpi-title">Total Organizations</h3>
                    <p class="oso-kpi-sub">Student orgs monitored</p>
                </article>

                {{-- Card 2: Pending Proposals --}}
                <article class="oso-kpi-card is-yellow">
                    <div class="oso-kpi-num">2</div>
                    <h3 class="oso-kpi-title">Pending Proposals</h3>
                    <p class="oso-kpi-sub">For Approval or In Review</p>
                </article>

                {{-- Card 3: Upcoming Activities --}}
                <article class="oso-kpi-card is-green">
                    <div class="oso-kpi-num">2</div>
                    <h3 class="oso-kpi-title">Upcoming Activities</h3>
                    <p class="oso-kpi-sub">Activities with future dates</p>
                </article>
            </div>

            {{-- 2. Middle Row: Proposal Status Overview & Upcoming Activities --}}
            <div class="oso-middle-row">
                {{-- Left: Proposal Status Overview --}}
                <section class="org-dash-card">
                    <div class="org-dash-card-header">
                        <h3 class="org-dash-card-title">Proposal Status Overview</h3>
                    </div>

                    <div class="oso-chart-legend-container">
                        <div class="oso-pie-chart-wrap">
                            <div class="oso-pie-chart"></div>
                        </div>

                        <div class="oso-legend-list">
                            <div class="oso-legend-item">
                                <span class="oso-legend-dot is-maroon"></span>
                                <div class="oso-legend-text">
                                    <strong>Pending Review</strong>
                                    <small>2 activities · 40%</small>
                                </div>
                            </div>

                            <div class="oso-legend-item">
                                <span class="oso-legend-dot is-green"></span>
                                <div class="oso-legend-text">
                                    <strong>Approved</strong>
                                    <small>2 activities · 40%</small>
                                </div>
                            </div>

                            <div class="oso-legend-item">
                                <span class="oso-legend-dot is-yellow"></span>
                                <div class="oso-legend-text">
                                    <strong>Returned</strong>
                                    <small>1 activity · 20%</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="border-top: 1px solid #f2e6e8; padding-top: 1rem; margin-top: 0.25rem;">
                        <span style="font-size: 0.72rem; font-weight: 800; letter-spacing: 0.05em; color: #7a7074; text-transform: uppercase;">IN-CAMPUS VS OFF-CAMPUS</span>
                        
                        <div class="oso-scope-split-grid">
                            <div class="oso-scope-box is-incampus">
                                <span class="oso-scope-box-label">In-Campus</span>
                                <span class="oso-scope-box-num">4</span>
                                <span class="oso-scope-box-pct">80% of total</span>
                            </div>

                            <div class="oso-scope-box is-offcampus">
                                <span class="oso-scope-box-label">Off-Campus</span>
                                <span class="oso-scope-box-num">1</span>
                                <span class="oso-scope-box-pct">20% of total</span>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Right: Upcoming Activities --}}
                <section class="org-dash-card">
                    <div class="org-dash-card-header">
                        <h3 class="org-dash-card-title">Upcoming Activities</h3>
                        <a href="{{ route('office.calendar') }}" class="org-dash-link">
                            View All →
                        </a>
                    </div>

                    <div class="org-upcoming-list">
                        <a href="{{ route('office.activities', ['activity' => 'campus-wellness-week']) }}" class="org-upcoming-card-item">
                            <div class="org-upcoming-left">
                                <div class="org-date-badge">
                                    <strong>8</strong>
                                    <small>SEP</small>
                                </div>
                                <div class="org-upcoming-meta">
                                    <strong>Campus Wellness Week</strong>
                                    <small style="color: #7a7074; font-size: 0.76rem; display: block; margin-bottom: 0.2rem;">In-Campus</small>
                                    <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px;">In-Campus</span>
                                </div>
                            </div>
                            <span class="org-status-pill org-status-blue">
                                <span class="org-status-dot"></span> In Review
                            </span>
                        </a>

                        <a href="{{ route('office.activities', ['activity' => 'batstateu-sportsfest-2026']) }}" class="org-upcoming-card-item">
                            <div class="org-upcoming-left">
                                <div class="org-date-badge">
                                    <strong>15</strong>
                                    <small>OCT</small>
                                </div>
                                <div class="org-upcoming-meta">
                                    <strong>BatStateU Sportsfest 2026</strong>
                                    <small style="color: #7a7074; font-size: 0.76rem; display: block; margin-bottom: 0.2rem;">In-Campus</small>
                                    <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px;">In-Campus</span>
                                </div>
                            </div>
                            <span class="org-status-pill org-status-red">
                                <span class="org-status-dot"></span> Return for Revision
                            </span>
                        </a>
                    </div>
                </section>
            </div>

            {{-- 3. Bottom Row: Recent Proposals Card with Table --}}
            <section class="org-dash-card">
                <div class="org-dash-card-header">
                    <h3 class="org-dash-card-title">Recent Proposals</h3>
                    <a href="{{ route('office.activities') }}" class="org-dash-link">
                        View All →
                    </a>
                </div>

                <div class="oso-table-container">
                    <table class="oso-table">
                        <thead>
                            <tr>
                                <th>ACTIVITY</th>
                                <th>ORGANIZATION</th>
                                <th>SCOPE</th>
                                <th>STATUS</th>
                                <th>DATE SUBMITTED</th>
                                <th style="text-align: right;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Row 1: Innovation Fair Booth Series --}}
                            <tr>
                                <td><strong>Innovation Fair Booth Series</strong></td>
                                <td style="color: #63575b;">BSU Student Council</td>
                                <td>
                                    <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-size: 0.74rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px;">In-Campus</span>
                                </td>
                                <td>
                                    <span class="org-status-pill org-status-green">
                                        <span class="org-status-dot"></span> Completed
                                    </span>
                                </td>
                                <td style="color: #7a7074; font-size: 0.82rem;">Jul 4, 2026</td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => 'innovation-fair-booth-series']) }}" class="org-btn-view-pill">View</a>
                                </td>
                            </tr>

                            {{-- Row 2: Volunteer Appreciation Day --}}
                            <tr>
                                <td><strong>Volunteer Appreciation Day</strong></td>
                                <td style="color: #63575b;">BSU Student Council</td>
                                <td>
                                    <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-size: 0.74rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px;">In-Campus</span>
                                </td>
                                <td>
                                    <span class="org-status-pill org-status-green">
                                        <span class="org-status-dot"></span> Completed
                                    </span>
                                </td>
                                <td style="color: #7a7074; font-size: 0.82rem;">Mar 2, 2026</td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => 'volunteer-appreciation-day']) }}" class="org-btn-view-pill">View</a>
                                </td>
                            </tr>

                            {{-- Row 3: Leadership Summit 2026 --}}
                            <tr>
                                <td><strong>Leadership Summit 2026</strong></td>
                                <td style="color: #63575b;">BSU Student Council</td>
                                <td>
                                    <span class="org-chip" style="background: #fef9c3; color: #ca8a04; font-size: 0.74rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px;">Off-Campus</span>
                                </td>
                                <td>
                                    <span class="org-status-pill org-status-yellow">
                                        <span class="org-status-dot"></span> For Approval
                                    </span>
                                </td>
                                <td style="color: #7a7074; font-size: 0.82rem;">May 12, 2026</td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => 'leadership-summit-2026']) }}" class="org-btn-view-pill">View</a>
                                </td>
                            </tr>

                            {{-- Row 4: Campus Wellness Week --}}
                            <tr>
                                <td><strong>Campus Wellness Week</strong></td>
                                <td style="color: #63575b;">BSU Student Council</td>
                                <td>
                                    <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-size: 0.74rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px;">In-Campus</span>
                                </td>
                                <td>
                                    <span class="org-status-pill org-status-blue">
                                        <span class="org-status-dot"></span> In Review
                                    </span>
                                </td>
                                <td style="color: #7a7074; font-size: 0.82rem;">Aug 20, 2026</td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => 'campus-wellness-week']) }}" class="org-btn-view-pill">View</a>
                                </td>
                            </tr>

                            {{-- Row 5: BatStateU Sportsfest 2026 --}}
                            <tr>
                                <td><strong>BatStateU Sportsfest 2026</strong></td>
                                <td style="color: #63575b;">BSU Student Council</td>
                                <td>
                                    <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-size: 0.74rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px;">In-Campus</span>
                                </td>
                                <td>
                                    <span class="org-status-pill org-status-red">
                                        <span class="org-status-dot"></span> Return for Revision
                                    </span>
                                </td>
                                <td style="color: #7a7074; font-size: 0.82rem;">May 11, 2026</td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => 'batstateu-sportsfest-2026']) }}" class="org-btn-view-pill">View</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @else
        {{-- SDO, OVCAA, AND STUDENT ORG DASHBOARDS --}}
        <div class="org-dash-grid">
            {{-- 1. Top 4 KPI Cards --}}
            <div class="org-kpi-row">
                @if ($isSdo)
                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-pink">
                            <i class="bi bi-leaf-fill"></i>
                        </div>
                        <div class="org-kpi-num">5</div>
                        <h3 class="org-kpi-title">Total Monitored</h3>
                        <p class="org-kpi-sub">All semester proposals</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-green">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div class="org-kpi-num">2</div>
                        <h3 class="org-kpi-title">SDG Verified</h3>
                        <p class="org-kpi-sub">Endorsed to OVCAA</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-blue">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="org-kpi-num">2</div>
                        <h3 class="org-kpi-title">Under SDG Review</h3>
                        <p class="org-kpi-sub">Checking WPCF &amp; goals</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-amber">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="org-kpi-num">1</div>
                        <h3 class="org-kpi-title">Needs SDG Revision</h3>
                        <p class="org-kpi-sub">Missing sustainability doc</p>
                    </article>
                @elseif ($isOvcaa)
                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-blue">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <div class="org-kpi-num">2</div>
                        <h3 class="org-kpi-title">Pending Final Approval</h3>
                        <p class="org-kpi-sub">Awaiting executive action</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-green">
                            <i class="bi bi-check2"></i>
                        </div>
                        <div class="org-kpi-num">2</div>
                        <h3 class="org-kpi-title">OVCAA Approved</h3>
                        <p class="org-kpi-sub">Authorized &amp; on chain</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-amber">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </div>
                        <div class="org-kpi-num">1</div>
                        <h3 class="org-kpi-title">Returned for Revision</h3>
                        <p class="org-kpi-sub">Executive remarks sent</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-pink">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <div class="org-kpi-num">5</div>
                        <h3 class="org-kpi-title">Total Submissions</h3>
                        <p class="org-kpi-sub">1st Semester AY 2025-26</p>
                    </article>
                @else
                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-pink">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <div class="org-kpi-num">5</div>
                        <h3 class="org-kpi-title">Total Activities</h3>
                        <p class="org-kpi-sub">2 completed · 2 in progress</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-green">
                            <i class="bi bi-check2"></i>
                        </div>
                        <div class="org-kpi-num">2</div>
                        <h3 class="org-kpi-title">Approved</h3>
                        <p class="org-kpi-sub">Budget &amp; AR submitted</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-blue">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="org-kpi-num">2</div>
                        <h3 class="org-kpi-title">Pending</h3>
                        <p class="org-kpi-sub">Awaiting OSO / OVCAA action</p>
                    </article>

                    <article class="org-kpi-card">
                        <div class="org-kpi-icon is-amber">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="org-kpi-num">1</div>
                        <h3 class="org-kpi-title">Needs Action</h3>
                        <p class="org-kpi-sub">Returned for revision</p>
                    </article>
                @endif
            </div>

            {{-- 2. Middle Section --}}
            <div class="org-dash-2col">
                @if ($isSdo)
                    {{-- SDO: SDG Alignment & Monitoring Overview --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-leaf-fill" style="color: #15803d;"></i> SDG Alignment Monitoring
                            </h3>
                            <a href="{{ route('office.activities') }}" class="org-dash-link">
                                Review Queue →
                            </a>
                        </div>

                        <div class="org-budget-hero-box" style="background: linear-gradient(135deg, #14532d 0%, #15803d 100%);">
                            <span style="color: #dcfce7;">Campus SDG Compliance</span>
                            <h2>80% Verified</h2>
                            <small style="color: #dcfce7; opacity: 0.95;">4 of 5 proposed activities aligned with UN SDGs</small>
                        </div>

                        <div class="org-budget-stat-row">
                            <span>SDG Indicators Met</span>
                            <strong style="color: #15803d;">4 / 5 Activities (80%)</strong>
                        </div>
                        <div class="org-mini-progress">
                            <div class="org-mini-fill-green" style="width: 80%; background: #15803d;"></div>
                        </div>

                        <div class="org-budget-stat-row">
                            <span>WPCF Protocol Compliance</span>
                            <strong style="color: #ca8a04;">3 Cleared · 1 Pending</strong>
                        </div>
                        <div class="org-mini-progress">
                            <div class="org-mini-fill-maroon" style="width: 75%; background: #ca8a04;"></div>
                        </div>

                        <div class="org-budget-sub-stats">
                            <div class="org-budget-sub-box is-green" style="background: #f0fdf4; border: 1px solid #dcfce7;">
                                <span style="color: #166534;">Top Priority Goal</span>
                                <strong style="color: #15803d; font-size: 0.88rem;">SDG 4 Education</strong>
                            </div>
                            <div class="org-budget-sub-box is-pink" style="background: #fdf0f2; border: 1px solid #fae1e5;">
                                <span style="color: #8b1828;">Awaiting SDG Review</span>
                                <strong style="color: #8b1828; font-size: 0.88rem;">2 Proposals</strong>
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 1rem;">
                            <span class="org-chip" style="background: #f0fdf4; color: #166534; font-weight: 700; font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 6px;">SDG 3 · Good Health</span>
                            <span class="org-chip" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 6px;">SDG 4 · Quality Education</span>
                            <span class="org-chip" style="background: #fefce8; color: #a16207; font-weight: 700; font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 6px;">SDG 11 · Sustainable Cities</span>
                            <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-weight: 700; font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 6px;">SDG 12 · Consumption</span>
                        </div>
                    </section>

                    {{-- SDO SDG Action Items --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-clipboard-check-fill" style="color: #15803d;"></i> SDG Review Action Items
                            </h3>
                            <span class="org-badge-count" style="background: #15803d; color: #ffffff; width: 22px; height: 22px; border-radius: 50%; font-size: 0.75rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 800;">3</span>
                        </div>

                        <div class="org-action-card-list">
                            {{-- Action 1 --}}
                            <a href="{{ route('office.activities', ['activity' => 'campus-wellness-week']) }}" class="org-action-card-item">
                                <div class="org-action-card-left">
                                    <div class="org-action-card-bullet" style="background: #f0fdf4; color: #15803d;">
                                        <i class="bi bi-heart-pulse-fill"></i>
                                    </div>
                                    <div class="org-action-card-text">
                                        <strong>Review Waste Protocol &amp; Health Plan</strong>
                                        <small>Campus Wellness Week · SDG 3 Health alignment</small>
                                    </div>
                                </div>
                                <div class="org-action-card-right">
                                    <span class="org-chip" style="background: #f0fdf4; color: #15803d; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px;">SDG 3</span>
                                    <i class="bi bi-chevron-right org-action-chevron"></i>
                                </div>
                            </a>

                            {{-- Action 2 --}}
                            <a href="{{ route('office.activities', ['activity' => 'leadership-summit-2026']) }}" class="org-action-card-item">
                                <div class="org-action-card-left">
                                    <div class="org-action-card-bullet" style="background: #eff6ff; color: #2563eb;">
                                        <i class="bi bi-award-fill"></i>
                                    </div>
                                    <div class="org-action-card-text">
                                        <strong>Verify Zero Single-Use Plastics Dossier</strong>
                                        <small>Leadership Summit 2026 · SDG 12 Consumption</small>
                                    </div>
                                </div>
                                <div class="org-action-card-right">
                                    <span class="org-chip" style="background: #eff6ff; color: #2563eb; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px;">SDG 12</span>
                                    <i class="bi bi-chevron-right org-action-chevron"></i>
                                </div>
                            </a>

                            {{-- Action 3 --}}
                            <a href="{{ route('office.activities', ['activity' => 'batstateu-sportsfest-2026']) }}" class="org-action-card-item">
                                <div class="org-action-card-left">
                                    <div class="org-action-card-bullet" style="background: #fef2f2; color: #dc2626;">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </div>
                                    <div class="org-action-card-text">
                                        <strong>Monitor Sustainability Revisions</strong>
                                        <small>BatStateU Sportsfest 2026 · Returned for revision</small>
                                    </div>
                                </div>
                                <div class="org-action-card-right">
                                    <span class="org-chip" style="background: #fee2e2; color: #dc2626; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px;">Needs Fix</span>
                                    <i class="bi bi-chevron-right org-action-chevron"></i>
                                </div>
                            </a>
                        </div>
                    </section>
                @elseif ($isOvcaa)
                    {{-- OVCAA: Executive Approvals Overview --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-patch-check-fill" style="color: #1d4ed8;"></i> Executive Approvals Overview
                            </h3>
                            <a href="{{ route('office.activities') }}" class="org-dash-link">
                                Approval Queue <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <p class="org-dash-card-sub">Final university authorization checkpoint for student events.</p>

                        <div class="org-budget-stats" style="margin-top: 0.5rem;">
                            <div>
                                <small>TOTAL SUBMITTED</small>
                                <strong>5 Activities</strong>
                            </div>
                            <div style="text-align: right;">
                                <small>APPROVAL RATE</small>
                                <strong style="color: #1d4ed8;">40% Executed</strong>
                            </div>
                        </div>

                        <div class="org-progress-bar-wrap" style="margin: 0.85rem 0 1rem;">
                            <div class="org-progress-bar-fill" style="width: 40%; background: #1d4ed8;"></div>
                        </div>

                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <span class="org-chip" style="background: #eff6ff; color: #1e40af; font-weight: 700; font-size: 0.74rem;">2 Approved on Chain</span>
                            <span class="org-chip" style="background: #fefce8; color: #854d0e; font-weight: 700; font-size: 0.74rem;">2 In Governance Queue</span>
                            <span class="org-chip" style="background: #fef2f2; color: #991b1b; font-weight: 700; font-size: 0.74rem;">1 Revision Remark</span>
                        </div>
                    </section>

                    {{-- OVCAA Executive Action Queue --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-shield-lock-fill" style="color: #1d4ed8;"></i> Executive Action Queue
                            </h3>
                            <span class="org-chip" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 0.75rem;">
                                2 Endorsed
                            </span>
                        </div>

                        <div class="org-action-items-list">
                            <div class="org-action-item">
                                <i class="bi bi-patch-check-fill org-action-icon is-blue"></i>
                                <div class="org-action-info">
                                    <strong>Campus Wellness Week</strong>
                                    <small>SDO SDG clearance certified. Ready for final OVCAA signing.</small>
                                </div>
                                <a href="{{ route('office.activities', ['activity' => 'campus-wellness-week']) }}" class="org-btn-sm-action">Decide</a>
                            </div>

                            <div class="org-action-item">
                                <i class="bi bi-patch-check-fill org-action-icon is-blue"></i>
                                <div class="org-action-info">
                                    <strong>Leadership Summit 2026</strong>
                                    <small>OSO clearance approved. Awaiting final executive authorization.</small>
                                </div>
                                <a href="{{ route('office.activities', ['activity' => 'leadership-summit-2026']) }}" class="org-btn-sm-action">Decide</a>
                            </div>
                        </div>
                    </section>
                @else
                    {{-- Student Org: Budget Snapshot --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-coin" style="color: #ca8a04;"></i> Budget Snapshot
                            </h3>
                            <a href="{{ route('office.budget') }}" class="org-dash-link">
                                View Details →
                            </a>
                        </div>

                        <div class="org-budget-hero-box">
                            <span>Total Allocated</span>
                            <h2>₱185,000</h2>
                            <small>AY 2025-2026 · 1st Semester</small>
                        </div>

                        <div class="org-budget-stat-row">
                            <span>Utilized</span>
                            <strong>₱115,150 (62%)</strong>
                        </div>
                        <div class="org-mini-progress">
                            <div class="org-mini-fill-maroon" style="width: 62%;"></div>
                        </div>

                        <div class="org-budget-stat-row">
                            <span>Remaining</span>
                            <strong class="is-green">₱69,850 (38%)</strong>
                        </div>
                        <div class="org-mini-progress">
                            <div class="org-mini-fill-green" style="width: 38%;"></div>
                        </div>

                        <div class="org-budget-sub-stats">
                            <div class="org-budget-sub-box is-pink">
                                <span>Activities w/ Budget</span>
                                <strong>3 / 5</strong>
                            </div>
                            <div class="org-budget-sub-box is-green">
                                <span>Budget Compliant</span>
                                <strong>2 / 2</strong>
                            </div>
                        </div>
                    </section>

                    {{-- Student Org: Pending Action Items --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-list-check" style="color: #8b1828;"></i> Pending Action Items
                            </h3>
                            <span class="org-badge-count" style="background: #7a1222; color: #ffffff; width: 22px; height: 22px; border-radius: 50%; font-size: 0.75rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 800;">4</span>
                        </div>

                        <div class="org-action-items-list">
                            <a href="{{ route('office.activities', ['activity' => 'batstateu-sportsfest-2026']) }}" class="org-action-item is-red">
                                <div class="org-action-left">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <div class="org-action-info">
                                        <strong>Resubmit Activity Proposal</strong>
                                        <small>BatStateU Sportsfest 2026 – returned by OSO</small>
                                    </div>
                                </div>
                                <div class="org-action-right">
                                    <span class="org-chip-urgent" style="background: #7a1222; color: #ffffff; padding: 0.2rem 0.6rem; border-radius: 9999px; font-weight: 800; font-size: 0.68rem;">URGENT</span>
                                    <i class="bi bi-chevron-right" style="color: #7a7074; font-size: 0.85rem;"></i>
                                </div>
                            </a>

                            <a href="{{ route('office.accomplishment') }}" class="org-action-item is-yellow">
                                <div class="org-action-left">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <div class="org-action-info">
                                        <strong>Submit Accomplishment Report</strong>
                                        <small>Campus Wellness Week – due Sep 15, 2026</small>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: #7a7074; font-size: 0.85rem;"></i>
                            </a>

                            <a href="{{ route('office.budget') }}" class="org-action-item is-blue">
                                <div class="org-action-left">
                                    <i class="bi bi-receipt"></i>
                                    <div class="org-action-info">
                                        <strong>Record Remaining Expenses</strong>
                                        <small>Leadership Summit 2026 – ₱32,250 remaining</small>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: #7a7074; font-size: 0.85rem;"></i>
                            </a>

                            <a href="{{ route('office.activities', ['activity' => 'leadership-summit-2026']) }}" class="org-action-item is-purple">
                                <div class="org-action-left">
                                    <i class="bi bi-file-earmark-medical"></i>
                                    <div class="org-action-info">
                                        <strong>Upload Risk Assessment</strong>
                                        <small>Leadership Summit 2026 – document pending</small>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: #7a7074; font-size: 0.85rem;"></i>
                            </a>
                        </div>
                    </section>
                @endif
            </div>

            {{-- 3. Approval Workflow Pipeline (Full Width Card) --}}
            <section class="org-pipeline-card">
                <div class="org-dash-card-header">
                    <h3 class="org-dash-card-title">
                        <i class="bi bi-diagram-3-fill" style="color: #8b1828;"></i> Approval Workflow Pipeline
                    </h3>
                    <a href="{{ route('office.activities') }}" class="org-dash-link">
                        View All →
                    </a>
                </div>

                <div class="org-pipeline-header-row">
                    <span>Activity Name</span>
                    <div class="org-stage-names">
                        <span>Created</span>
                        <span>OSO Review</span>
                        <span>OVCAA Review</span>
                        <span>Approved</span>
                        <span>Completed</span>
                    </div>
                    <span style="text-align: right;">Status</span>
                </div>

                {{-- Row 1: Innovation Fair Booth Series (OVCAA Approved) --}}
                <div class="org-pipeline-row">
                    <span class="org-pipeline-title">Innovation Fair Booth Series</span>
                    <div class="org-pipeline-stepper">
                        <div class="org-stepper-track-bg"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="org-status-pill org-status-green">
                            <span class="org-status-dot"></span> OVCAA Approved
                        </span>
                    </div>
                </div>

                {{-- Row 2: Volunteer Appreciation Day (OVCAA Approved) --}}
                <div class="org-pipeline-row">
                    <span class="org-pipeline-title">Volunteer Appreciation Day</span>
                    <div class="org-pipeline-stepper">
                        <div class="org-stepper-track-bg"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="org-status-pill org-status-green">
                            <span class="org-status-dot"></span> OVCAA Approved
                        </span>
                    </div>
                </div>

                {{-- Row 3: Leadership Summit 2026 (Stage: For OSO Review) --}}
                <div class="org-pipeline-row">
                    <span class="org-pipeline-title">Leadership Summit 2026</span>
                    <div class="org-pipeline-stepper">
                        <div class="org-stepper-track-bg"></div>
                        <div class="org-stepper-track-fill" style="width: 20%;"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node is-done"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-active-gold"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="org-status-pill org-status-yellow">
                            <span class="org-status-dot"></span> For OSO Review
                        </span>
                    </div>
                </div>

                {{-- Row 4: Campus Wellness Week (Stage: In Review) --}}
                <div class="org-pipeline-row">
                    <span class="org-pipeline-title">Campus Wellness Week</span>
                    <div class="org-pipeline-stepper">
                        <div class="org-stepper-track-bg"></div>
                        <div class="org-stepper-track-fill" style="width: 40%;"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node is-done"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-active-gold"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="org-status-pill org-status-blue">
                            <span class="org-status-dot"></span> In Review
                        </span>
                    </div>
                </div>

                {{-- Row 5: BatStateU Sportsfest 2026 (Stage: Return for Revision) --}}
                <div class="org-pipeline-row">
                    <span class="org-pipeline-title">BatStateU Sportsfest 2026</span>
                    <div class="org-pipeline-stepper">
                        <div class="org-stepper-track-bg"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node is-returned" style="border-color: #dc2626; color: #dc2626; background: #ffffff;">
                                <i class="bi bi-record-circle" style="font-size: 0.65rem;"></i>
                            </span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="org-status-pill org-status-red">
                            <span class="org-status-dot"></span> Return for Revision
                        </span>
                    </div>
                </div>
            </section>

            {{-- 4. Bottom Row: Upcoming Activities & Recent Updates --}}
            <div class="org-dash-bottom-grid">
                {{-- Upcoming Activities --}}
                <section class="org-dash-card">
                    <div class="org-dash-card-header">
                        <h3 class="org-dash-card-title">
                            <i class="bi bi-stars" style="color: #ca8a04;"></i> Upcoming Activities
                        </h3>
                        <a href="{{ route('office.calendar') }}" class="org-dash-link">
                            View All →
                        </a>
                    </div>

                    <div class="org-upcoming-list">
                        <a href="{{ route('office.activities', ['activity' => 'leadership-summit-2026']) }}" class="org-upcoming-card-item">
                            <div class="org-upcoming-left">
                                <div class="org-date-badge">
                                    <strong>12</strong>
                                    <small>AUG</small>
                                </div>
                                <div class="org-upcoming-meta">
                                    <strong>Leadership Summit 2026</strong>
                                    <small><i class="bi bi-geo-alt-fill" style="color: #8b1828;"></i> Taal Building · Off-Campus</small>
                                </div>
                            </div>
                            <span class="org-status-pill org-status-yellow">
                                <span class="org-status-dot"></span> For OSO Review
                            </span>
                        </a>

                        <a href="{{ route('office.activities', ['activity' => 'campus-wellness-week']) }}" class="org-upcoming-card-item">
                            <div class="org-upcoming-left">
                                <div class="org-date-badge">
                                    <strong>8</strong>
                                    <small>SEP</small>
                                </div>
                                <div class="org-upcoming-meta">
                                    <strong>Campus Wellness Week</strong>
                                    <small><i class="bi bi-geo-alt-fill" style="color: #8b1828;"></i> Gymnasium · In-Campus</small>
                                </div>
                            </div>
                            <span class="org-status-pill org-status-blue">
                                <span class="org-status-dot"></span> In Review
                            </span>
                        </a>
                    </div>
                </section>

                {{-- Recent Updates --}}
                <section class="org-dash-card">
                    <div class="org-dash-card-header">
                        <h3 class="org-dash-card-title">
                            <i class="bi bi-bell-fill" style="color: #8b1828;"></i> Recent Updates
                        </h3>
                        <a href="{{ route('office.updates') }}" class="org-dash-link">
                            View All →
                        </a>
                    </div>

                    <div class="org-recent-updates-list">
                        <div class="org-recent-update-item">
                            <span class="org-role-chip is-oso" style="background: #e0f2fe; color: #0284c7;">OSO</span>
                            <div class="org-recent-update-text">
                                <strong>Semester Financial Report Submission Deadline</strong>
                                <small>Sep 1, 2026</small>
                            </div>
                        </div>

                        <div class="org-recent-update-item">
                            <span class="org-role-chip is-system" style="background: #f3e8ff; color: #7e22ce;">SYSTEM</span>
                            <div class="org-recent-update-text">
                                <strong>OrgChain Module v2.1 — New Features Released</strong>
                                <small>Aug 28, 2026</small>
                            </div>
                        </div>

                        <div class="org-recent-update-item">
                            <span class="org-role-chip is-ovcaa" style="background: #dcfce7; color: #15803d;">OVCAA</span>
                            <div class="org-recent-update-text">
                                <strong>Innovation Fair Booth Series – OVCAA Approved</strong>
                                <small>May 10, 2026</small>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    @endif
@endsection
