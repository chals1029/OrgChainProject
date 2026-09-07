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
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 22px;
            border: 1.5px solid rgba(240, 230, 232, 0.95);
            padding: 1.25rem 1.4rem;
            box-shadow: 0 4px 20px rgba(90, 15, 30, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.3s ease;
            cursor: pointer;
        }

        .org-kpi-card::after {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 3.5px;
            background: linear-gradient(90deg, transparent, #8b1828, transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .org-kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 38px rgba(74, 10, 21, 0.11), 0 3px 10px rgba(74, 10, 21, 0.04);
            border-color: rgba(139, 24, 40, 0.25);
        }

        .org-kpi-card:hover::after {
            opacity: 1;
        }

        .org-kpi-card:active {
            transform: translateY(-1px) scale(0.995);
        }

        .org-kpi-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }

        .org-kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            background: linear-gradient(135deg, rgba(139, 24, 40, 0.12), rgba(139, 24, 40, 0.04));
            color: #8b1828;
            box-shadow: inset 0 0 0 1px rgba(139, 24, 40, 0.18);
            transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), background 0.3s ease, box-shadow 0.3s ease, color 0.3s ease;
        }

        .org-kpi-card:hover .org-kpi-icon {
            transform: scale(1.08) rotate(3deg);
            background: linear-gradient(135deg, rgba(139, 24, 40, 0.18), rgba(139, 24, 40, 0.06));
            box-shadow: inset 0 0 0 1px rgba(139, 24, 40, 0.28);
        }

        .org-kpi-icon.is-pink,
        .org-kpi-icon.is-green,
        .org-kpi-icon.is-blue,
        .org-kpi-icon.is-amber {
            background: linear-gradient(135deg, rgba(139, 24, 40, 0.12), rgba(139, 24, 40, 0.04));
            color: #8b1828;
            box-shadow: inset 0 0 0 1px rgba(139, 24, 40, 0.18);
        }

        .org-kpi-num {
            font-size: 1.95rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .org-kpi-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .org-kpi-sub {
            font-size: 0.76rem;
            color: #7a7074;
            margin: 0;
            line-height: 1.35;
        }

        /* =========================================================================
           OVCAA Executive Stat Cards — Student Portal Liquid-Glass System & Effects
           ========================================================================= */
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

        .ovcaa-stat-card.is-blue::after,
        .ovcaa-stat-card.is-green::after,
        .ovcaa-stat-card.is-amber::after,
        .ovcaa-stat-card.is-maroon::after {
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

        .ovcaa-stat-top strong {
            font-size: clamp(1.65rem, 2.3vw, 2.1rem);
            color: #1a1618;
            letter-spacing: -0.035em;
            font-variant-numeric: tabular-nums;
            font-weight: 850;
            line-height: 1;
            margin-left: auto;
        }

        .ovcaa-stat-card span.ovcaa-stat-title {
            font-weight: 800;
            color: #1e293b;
            font-size: 0.92rem;
            letter-spacing: -0.01em;
            display: block;
        }

        /* OVCAA Executive Overview Chart & Metrics */
        .ovcaa-overview-body {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .ovcaa-metrics-split {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.25rem;
            align-items: center;
            background: linear-gradient(135deg, rgba(253, 240, 242, 0.85) 0%, rgba(255, 255, 255, 0.98) 100%);
            border: 1.5px solid rgba(240, 230, 232, 0.95);
            border-radius: 18px;
            padding: 1.15rem 1.35rem;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .ovcaa-metrics-split::after {
            content: "";
            position: absolute;
            top: -20px;
            right: -20px;
            width: 130px;
            height: 130px;
            background: radial-gradient(circle, rgba(139, 24, 40, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .ovcaa-kpi-block span.ovcaa-kpi-subhead {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #8b1828;
            display: block;
            margin-bottom: 0.35rem;
        }

        .ovcaa-kpi-block h2 {
            font-size: 2.15rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1;
            letter-spacing: -0.04em;
            margin: 0 0 0.35rem 0;
            font-variant-numeric: tabular-nums;
        }

        .ovcaa-kpi-block p {
            font-size: 0.78rem;
            color: #64748b;
            margin: 0;
            font-weight: 600;
        }

        /* Circular Progress / Donut Ring Component */
        .ovcaa-donut-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            width: 98px;
            height: 98px;
            flex-shrink: 0;
        }

        .ovcaa-donut-svg {
            transform: rotate(-90deg);
            width: 98px;
            height: 98px;
        }

        .ovcaa-donut-track {
            fill: none;
            stroke: #f0e6e8;
            stroke-width: 8;
        }

        .ovcaa-donut-fill {
            fill: none;
            stroke: url(#ovcaaDonutGrad);
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 251.3;
            stroke-dashoffset: 150.8; /* 40% filled: 251.3 * (1 - 0.40) */
            transition: stroke-dashoffset 1.2s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .ovcaa-donut-content {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .ovcaa-donut-val {
            font-size: 1.2rem;
            font-weight: 900;
            color: #8b1828;
            line-height: 1;
            letter-spacing: -0.03em;
        }

        .ovcaa-donut-lbl {
            font-size: 0.58rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #7a7074;
            margin-top: 3px;
        }

        /* Segmented Progress Bar */
        .ovcaa-distribution-box {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            background: #faf7f8;
            border: 1px solid #f2e6e8;
            border-radius: 16px;
            padding: 0.95rem 1.1rem;
        }

        .ovcaa-dist-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
        }

        .ovcaa-dist-head span {
            font-weight: 700;
            color: #475569;
        }

        .ovcaa-dist-head strong {
            font-weight: 800;
            color: #0f172a;
        }

        .ovcaa-segmented-bar {
            display: flex;
            height: 10px;
            border-radius: 9999px;
            overflow: hidden;
            gap: 3px;
            background: #f0e6e8;
            padding: 2px;
        }

        .ovcaa-seg {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.4s ease, filter 0.2s ease;
        }

        .ovcaa-seg:hover {
            filter: brightness(1.15);
        }

        .ovcaa-seg.is-approved {
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .ovcaa-seg.is-queue {
            background: linear-gradient(90deg, #8b1828, #c43b52);
        }

        .ovcaa-seg.is-revision {
            background: linear-gradient(90deg, #d97706, #f59e0b);
        }

        .ovcaa-dist-chips {
            display: flex;
            gap: 0.45rem;
            flex-wrap: wrap;
            margin-top: 0.2rem;
        }

        .ovcaa-dist-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.65rem;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            transition: transform 0.15s ease;
        }

        .ovcaa-dist-chip:hover {
            transform: translateY(-1px);
        }

        .ovcaa-dist-chip.is-approved {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .ovcaa-dist-chip.is-queue {
            background: #fdf0f2;
            color: #8b1828;
            border: 1px solid #f0e6e8;
        }

        .ovcaa-dist-chip.is-revision {
            background: #fefce8;
            color: #b45309;
            border: 1px solid #fef08a;
        }

        .ovcaa-dist-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .ovcaa-dist-chip.is-approved .ovcaa-dist-dot { background: #16a34a; }
        .ovcaa-dist-chip.is-queue .ovcaa-dist-dot { background: #8b1828; }
        .ovcaa-dist-chip.is-revision .ovcaa-dist-dot { background: #d97706; }

        /* Action Queue & Endorsed Badge */
        .ovcaa-endorsed-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: linear-gradient(135deg, #7a1222, #8b1828);
            color: #ffffff;
            padding: 0.28rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            box-shadow: 0 2px 8px rgba(122, 18, 34, 0.25);
        }

        .ovcaa-action-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.95rem 1.1rem;
            border-radius: 16px;
            background: #faf7f8;
            border: 1.5px solid #f2e6e8;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1);
            gap: 1rem;
        }

        .ovcaa-action-item:hover {
            background: #ffffff;
            border-color: rgba(139, 24, 40, 0.35);
            box-shadow: 0 8px 24px rgba(139, 24, 40, 0.08);
            transform: translateY(-2px);
        }

        .ovcaa-action-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .ovcaa-action-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            background: #fdf0f2;
            color: #8b1828;
            border: 1px solid #f0e6e8;
        }

        .ovcaa-action-meta strong {
            display: block;
            font-size: 0.9rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ovcaa-action-meta small {
            display: block;
            font-size: 0.77rem;
            color: #64748b;
            margin-top: 0.2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ovcaa-action-right {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-shrink: 0;
        }

        .ovcaa-btn-decide {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.42rem 0.9rem;
            background: #7a1222;
            color: #ffffff !important;
            border-radius: 10px;
            font-size: 0.78rem;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(122, 18, 34, 0.25);
        }

        .ovcaa-btn-decide:hover {
            background: #62101c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(122, 18, 34, 0.35);
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 104px;
            height: 32px;
            padding: 0 0.5rem;
            border-radius: 9999px;
            background: #8b1828;
            color: #ffffff !important;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
            white-space: nowrap;
            box-sizing: border-box;
            transition: all 0.18s ease;
            box-shadow: 0 2px 6px rgba(139, 24, 40, 0.15);
            border: 1px solid transparent;
        }

        .org-btn-view-pill:hover {
            background: #6e101d;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 24, 40, 0.28);
            color: #ffffff !important;
        }

        .org-btn-view-pill:active {
            transform: translateY(0);
        }
        /* =========================================================
           OSO Officer Dedicated Analytics & Operations Dashboard
           ========================================================= */
        
        .oso-analytics-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .oso-stat-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.5rem;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .oso-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 26px rgba(139, 24, 40, 0.08);
            border-color: #f1c0c9;
        }

        .oso-stat-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .oso-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .oso-stat-icon.is-maroon { background: #fdf0f2; color: #8b1828; border: 1px solid #fae0e5; }
        .oso-stat-icon.is-amber { background: #fefce8; color: #b45309; border: 1px solid #fef08a; }
        .oso-stat-icon.is-emerald { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .oso-stat-icon.is-sky { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

        .oso-stat-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .oso-stat-badge.is-urgent { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .oso-stat-badge.is-positive { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .oso-stat-badge.is-neutral { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

        .oso-stat-val {
            font-size: 2.25rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin-bottom: 0.2rem;
        }

        .oso-stat-label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #2b2427;
            margin: 0 0 0.35rem;
        }

        .oso-stat-desc {
            font-size: 0.78rem;
            color: #786f73;
            margin: 0;
            line-height: 1.4;
        }

        /* 2-Column Analytics Grid */
        .oso-analytics-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .oso-analytics-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.65rem 1.5rem;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .oso-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.85rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f6eff0;
        }

        .oso-card-title-group h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0 0 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .oso-card-title-group p {
            font-size: 0.78rem;
            color: #786f73;
            margin: 0;
        }

        .oso-canvas-wrap {
            position: relative;
            width: 100%;
            flex: 1;
            min-height: 250px;
            height: 100%;
        }

        /* Donut Chart with Custom Legend */
        .oso-donut-split {
            display: grid;
            grid-template-columns: 170px 1fr;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .oso-donut-center-wrap {
            position: relative;
            width: 165px;
            height: 165px;
            margin: 0 auto;
        }

        .oso-donut-center-label {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            text-align: center;
        }

        .oso-donut-center-label strong {
            font-size: 1.45rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1;
        }

        .oso-donut-center-label small {
            font-size: 0.68rem;
            font-weight: 700;
            color: #786f73;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 0.2rem;
        }

        .oso-donut-legend-list {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .oso-donut-legend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            padding: 0.35rem 0.55rem;
            border-radius: 8px;
            background: #faf6f7;
        }

        .oso-donut-legend-left {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-weight: 600;
            color: #2b2427;
        }

        .oso-color-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .oso-donut-legend-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            color: #1a1618;
        }

        .oso-pct-pill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
            background: #ffffff;
            color: #635b5e;
            border: 1px solid #e8e2e4;
        }

        /* On-Campus vs Off-Campus Widget */
        .oso-scope-panel {
            background: #faf6f7;
            border: 1px solid #f0e6e8;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            margin-top: 0.5rem;
        }

        .oso-scope-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.76rem;
            font-weight: 800;
            color: #706569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.5rem;
        }

        .oso-scope-progress-bar {
            height: 8px;
            background: #fef08a;
            border-radius: 9999px;
            overflow: hidden;
            display: flex;
            margin-bottom: 0.65rem;
        }

        .oso-scope-bar-incampus {
            height: 100%;
            background: #8b1828;
            border-radius: 9999px 0 0 9999px;
        }

        .oso-scope-metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .oso-scope-stat-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 0.45rem 0.75rem;
            border-radius: 8px;
            border: 1px solid #f0e6e8;
        }

        .oso-scope-stat-box.is-incampus { border-left: 3px solid #8b1828; }
        .oso-scope-stat-box.is-offcampus { border-left: 3px solid #ca8a04; }

        .oso-scope-stat-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: #332d30;
        }

        .oso-scope-stat-num {
            font-size: 0.84rem;
            font-weight: 800;
            color: #1a1618;
        }

        /* Horizontal Ranking Progress Bars */
        .oso-rank-list {
            display: flex;
            flex-direction: column;
            gap: 0.95rem;
        }

        .oso-rank-item {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .oso-rank-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            flex-wrap: wrap;
            font-size: 0.84rem;
        }

        .oso-rank-name {
            font-weight: 700;
            color: #1a1618;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            min-width: 0;
            word-break: break-word;
        }

        .oso-rank-stat {
            font-size: 0.78rem;
            color: #635b5e;
            font-weight: 600;
            flex-shrink: 0;
        }

        .oso-rank-bar-bg {
            height: 8px;
            background: #f4ecee;
            border-radius: 9999px;
            overflow: hidden;
            position: relative;
        }

        .oso-rank-bar-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }

        /* Incomplete & Revision Causes List */
        .oso-causes-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 0.85rem;
        }

        .oso-cause-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 12px;
            background: #fdfafb;
            border: 1px solid #f6eaec;
            font-size: 0.82rem;
        }

        .oso-cause-left {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-weight: 600;
            color: #332d30;
            min-width: 0;
        }

        .oso-cause-pct {
            font-size: 0.78rem;
            font-weight: 800;
            color: #8b1828;
            background: #fdf0f2;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            border: 1px solid #fae0e5;
            flex-shrink: 0;
            white-space: nowrap;
        }

        /* Recent Transactions Table */
        .oso-transactions-table-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            margin-bottom: 1.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        .oso-trx-table {
            width: 100%;
            min-width: 780px;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.86rem;
        }

        .oso-trx-table thead th {
            background: #faf6f7;
            padding: 0.85rem 1.15rem;
            font-size: 0.76rem;
            font-weight: 800;
            color: #706569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #eee4e6;
            white-space: nowrap;
        }

        .oso-trx-table tbody tr {
            border-bottom: 1px solid #f6eff0;
            transition: background 0.15s ease;
        }

        .oso-trx-table tbody tr:hover {
            background: #fdf8f9;
        }

        .oso-trx-table td {
            padding: 0.95rem 1.15rem;
            vertical-align: middle;
            color: #1a1618;
        }

        .oso-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            white-space: nowrap;
        }

        .oso-type-proposal { background: #fdf0f2; color: #8b1828; border: 1px solid #fae0e5; }
        .oso-type-renewal { background: #fefce8; color: #a16207; border: 1px solid #fef08a; }
        .oso-type-fr { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .oso-type-ar { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .oso-type-tosa { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

        .oso-sla-pill {
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
        }

        .oso-sla-high { color: #dc2626; background: #fef2f2; }
        .oso-sla-normal { color: #475569; background: #f1f5f9; }

        /* ---------------------------------------------------------
           Interactive Dashboard Period & Date Filter Toolbar
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
        }

        /* Responsive Behavior for Laptops & Mobile */
        @media (max-width: 1280px) {
            .org-kpi-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            .oso-analytics-2col {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }
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
        }

        @media (max-width: 768px) {
            .oso-donut-split {
                grid-template-columns: 1fr;
                gap: 1.25rem;
                justify-items: center;
                text-align: center;
            }
            .oso-donut-legend-list {
                width: 100%;
            }
            .oso-analytics-card {
                padding: 1.25rem 1.35rem;
            }
            .oso-card-head {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .oso-scope-metrics-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 580px) {
            .org-kpi-row {
                grid-template-columns: 1fr;
                gap: 0.85rem;
            }
            .oso-cause-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.35rem;
            }
            .oso-cause-pct {
                align-self: flex-start;
            }
        }
    </style>

    @if ($isOso)
        {{-- ======================================================================
             DEDICATED OSO OFFICER ANALYTICS & OPERATIONS DASHBOARD
             ====================================================================== --}}
        <div class="org-dash-grid">

            {{-- 0. INTERACTIVE PERIOD & DATE FILTER TOOLBAR --}}
            <section class="oso-filter-bar-card" aria-label="Dashboard Filters">
                <div class="oso-filter-bar-left">
                    <div class="oso-filter-bar-title">
                        <i class="bi bi-funnel-fill"></i>
                        <span>Filters:</span>
                    </div>

                    {{-- Academic Year Filter --}}
                    <div class="oso-filter-group">
                        <label for="osoYearSelect" class="oso-filter-label"><i class="bi bi-calendar2-range"></i> Year</label>
                        <div class="oso-select-wrapper">
                            <select id="osoYearSelect" class="oso-filter-select" onchange="applyOsoFilters()">
                                <option value="2025-2026" selected>A.Y. 2025–2026 (Current)</option>
                                <option value="2024-2025">A.Y. 2024–2025</option>
                                <option value="2023-2024">A.Y. 2023–2024</option>
                                <option value="all">All Academic Years</option>
                            </select>
                            <i class="bi bi-chevron-down oso-select-arrow"></i>
                        </div>
                    </div>

                    {{-- Semester Filter --}}
                    <div class="oso-filter-group">
                        <label for="osoSemesterSelect" class="oso-filter-label"><i class="bi bi-bookmark"></i> Semester</label>
                        <div class="oso-select-wrapper">
                            <select id="osoSemesterSelect" class="oso-filter-select" onchange="applyOsoFilters()">
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
                        <label for="osoMonthSelect" class="oso-filter-label"><i class="bi bi-calendar3"></i> Month</label>
                        <div class="oso-select-wrapper">
                            <select id="osoMonthSelect" class="oso-filter-select" onchange="applyOsoFilters()">
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
                        <label for="osoScopeSelect" class="oso-filter-label"><i class="bi bi-geo-alt"></i> Scope</label>
                        <div class="oso-select-wrapper">
                            <select id="osoScopeSelect" class="oso-filter-select" onchange="applyOsoFilters()">
                                <option value="all" selected>All Scopes</option>
                                <option value="in-campus">On-Campus Only</option>
                                <option value="off-campus">Off-Campus Only</option>
                            </select>
                            <i class="bi bi-chevron-down oso-select-arrow"></i>
                        </div>
                    </div>
                </div>

                <div class="oso-filter-bar-right">
                    <button type="button" class="oso-filter-reset-btn" onclick="resetOsoFilters()" title="Reset all filters" aria-label="Reset all filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <span class="oso-filter-status-badge" id="osoActiveFilterBadge">
                        <i class="bi bi-check2-circle"></i> Live Insights
                    </span>
                </div>
            </section>

            {{-- 1. TOP EXECUTIVE KPI CARDS (Matching System Theme & Liquid-Glass System) --}}
            <div class="org-kpi-row">
                
                {{-- Card 1: Total Organizations --}}
                <article class="org-kpi-card">
                    <div class="org-kpi-head">
                        <div class="org-kpi-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="org-kpi-num" id="kpiTotalOrgsNum">12</div>
                    </div>
                    <h3 class="org-kpi-title">Total Organizations</h3>
                    <p class="org-kpi-sub" id="kpiTotalOrgsSub">8 Academic · 3 Non-Academic · 1 SSC</p>
                </article>

                {{-- Card 2: Pending Transactions --}}
                <article class="org-kpi-card">
                    <div class="org-kpi-head">
                        <div class="org-kpi-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="org-kpi-num" id="kpiPendingTrxNum">7</div>
                    </div>
                    <h3 class="org-kpi-title">Pending Transactions</h3>
                    <p class="org-kpi-sub" id="kpiPendingTrxSub">3 Proposals · 2 FR · 1 Renewal · 1 TOSA</p>
                </article>

                {{-- Card 3: Total Submissions --}}
                <article class="org-kpi-card">
                    <div class="org-kpi-head">
                        <div class="org-kpi-icon">
                            <i class="bi bi-journal-check"></i>
                        </div>
                        <div class="org-kpi-num" id="kpiTotalSubmissionsNum">48</div>
                    </div>
                    <h3 class="org-kpi-title">Total Submissions</h3>
                    <p class="org-kpi-sub" id="kpiTotalSubmissionsSub">Across all student org portfolios</p>
                </article>

                {{-- Card 4: Revision Rate --}}
                <article class="org-kpi-card">
                    <div class="org-kpi-head">
                        <div class="org-kpi-icon">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </div>
                        <div class="org-kpi-num" id="kpiRevisionRateNum">14.2%</div>
                    </div>
                    <h3 class="org-kpi-title">Revision Rate</h3>
                    <p class="org-kpi-sub" id="kpiRevisionRateSub">Returned for incomplete compliance</p>
                </article>

            </div>

            {{-- 2. MIDDLE ROW 1: Approval Status (Donut + On/Off Campus) & Submission Trend (Line Chart) --}}
            <div class="oso-analytics-2col">

                {{-- Card A: Approval Status + On-Campus/Off-Campus Breakdown (Required Table Item 3) --}}
                <section class="oso-analytics-card">
                    <div class="oso-card-head">
                        <div class="oso-card-title-group">
                            <h3><i class="bi bi-pie-chart-fill" style="color: #8b1828;"></i> Approval Status &amp; Scope Overview</h3>
                            <p>Current distribution of all 48 transactions and venue scopes</p>
                        </div>
                        <span class="oso-stat-badge is-neutral" id="osoApprovalPeriodBadge">A.Y. 2025–2026</span>
                    </div>

                    {{-- Donut Chart & Legend --}}
                    <div class="oso-donut-split">
                        <div class="oso-donut-center-wrap">
                            <canvas id="osoApprovalDonutChart"></canvas>
                            <div class="oso-donut-center-label">
                                <strong id="donutTotalCount">48</strong>
                                <small>Total</small>
                            </div>
                        </div>

                        <div class="oso-donut-legend-list">
                            <div class="oso-donut-legend-row">
                                <div class="oso-donut-legend-left">
                                    <span class="oso-color-dot" style="background: #10b981;"></span>
                                    <span>Approved</span>
                                </div>
                                <div class="oso-donut-legend-right">
                                    <span id="legendApprovedCount">27</span>
                                    <span class="oso-pct-pill" id="legendApprovedPct">56.2%</span>
                                </div>
                            </div>

                            <div class="oso-donut-legend-row">
                                <div class="oso-donut-legend-left">
                                    <span class="oso-color-dot" style="background: #f59e0b;"></span>
                                    <span>Pending Review</span>
                                </div>
                                <div class="oso-donut-legend-right">
                                    <span id="legendPendingCount">11</span>
                                    <span class="oso-pct-pill" id="legendPendingPct">22.9%</span>
                                </div>
                            </div>

                            <div class="oso-donut-legend-row">
                                <div class="oso-donut-legend-left">
                                    <span class="oso-color-dot" style="background: #e11d48;"></span>
                                    <span>For Revision</span>
                                </div>
                                <div class="oso-donut-legend-right">
                                    <span id="legendRevisionCount">7</span>
                                    <span class="oso-pct-pill" id="legendRevisionPct">14.6%</span>
                                </div>
                            </div>

                            <div class="oso-donut-legend-row">
                                <div class="oso-donut-legend-left">
                                    <span class="oso-color-dot" style="background: #64748b;"></span>
                                    <span>Rejected</span>
                                </div>
                                <div class="oso-donut-legend-right">
                                    <span id="legendRejectedCount">3</span>
                                    <span class="oso-pct-pill" id="legendRejectedPct">6.3%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- On-Campus vs Off-Campus Breakdown Total --}}
                    <div class="oso-scope-panel">
                        <div class="oso-scope-head">
                            <span><i class="bi bi-geo-alt-fill" style="color: #8b1828; margin-right: 0.25rem;"></i> On-Campus vs Off-Campus Submissions</span>
                            <span id="scopeTotalHeader">48 Activities Total</span>
                        </div>
                        <div class="oso-scope-progress-bar">
                            <div class="oso-scope-bar-incampus" id="scopeInCampusBar" style="width: 79.2%;"></div>
                        </div>
                        <div class="oso-scope-metrics-grid">
                            <div class="oso-scope-stat-box is-incampus">
                                <div>
                                    <span class="oso-scope-stat-name">On-Campus Activities</span>
                                    <small id="scopeInCampusPct" style="display: block; color: #786f73; font-size: 0.7rem;">79.2% of total</small>
                                </div>
                                <span class="oso-scope-stat-num" id="scopeInCampusCount" style="color: #8b1828;">38</span>
                            </div>
                            <div class="oso-scope-stat-box is-offcampus">
                                <div>
                                    <span class="oso-scope-stat-name">Off-Campus Activities</span>
                                    <small id="scopeOffCampusPct" style="display: block; color: #786f73; font-size: 0.7rem;">20.8% of total</small>
                                </div>
                                <span class="oso-scope-stat-num" id="scopeOffCampusCount" style="color: #ca8a04;">10</span>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Card B: Submission Trend (Required Table Item 4 - Line Chart) --}}
                <section class="oso-analytics-card">
                    <div class="oso-card-head">
                        <div class="oso-card-title-group">
                            <h3><i class="bi bi-graph-up" style="color: #8b1828;"></i> Submission Volume Trend</h3>
                            <p>Monthly frequency of proposals and compliance filings</p>
                        </div>
                        <div style="font-size: 0.78rem; font-weight: 700; color: #8b1828;" id="osoTrendPeakLabel">
                            <i class="bi bi-dot" style="font-size: 1.2rem;"></i> Peak: Sep (22)
                        </div>
                    </div>

                    <div class="oso-canvas-wrap">
                        <canvas id="osoSubmissionTrendChart"></canvas>
                    </div>
                </section>

            </div>

            {{-- 3. MIDDLE ROW 2: Transactions by Type (Bar) & Organization Activity (Horizontal Bar) --}}
            <div class="oso-analytics-2col">

                {{-- Card C: Transactions by Type (Required Table Item 5 - Bar Chart) --}}
                <section class="oso-analytics-card">
                    <div class="oso-card-head">
                        <div class="oso-card-title-group">
                            <h3><i class="bi bi-bar-chart-fill" style="color: #8b1828;"></i> Transactions by Type</h3>
                            <p>Volume breakdown across proposals, reports, renewals, and awards</p>
                        </div>
                    </div>

                    <div class="oso-canvas-wrap">
                        <canvas id="osoTransactionTypeChart"></canvas>
                    </div>
                </section>

                {{-- Card D: Organization Activity (Required Table Item 6 - Horizontal Bar) --}}
                <section class="oso-analytics-card">
                    <div class="oso-card-head">
                        <div class="oso-card-title-group">
                            <h3><i class="bi bi-trophy-fill" style="color: #8b1828;"></i> Organization Activity Ranking</h3>
                            <p>Most to least active student organizations by total submissions</p>
                        </div>
                        <a href="{{ route('office.activities') }}" class="org-dash-link">View All Orgs &rarr;</a>
                    </div>

                    <div class="oso-rank-list">
                        {{-- Org 1 --}}
                        <div class="oso-rank-item">
                            <div class="oso-rank-header">
                                <span class="oso-rank-name">
                                    <i class="bi bi-award-fill" style="color: #ca8a04;"></i> Supreme Student Council (SSC)
                                </span>
                                <span class="oso-rank-stat" id="rankOrgStat1"><strong>14</strong> submissions &middot; <span style="color: #16a34a;">92% pass</span></span>
                            </div>
                            <div class="oso-rank-bar-bg">
                                <div class="oso-rank-bar-fill" id="rankOrgBar1" style="width: 100%; background: #8b1828;"></div>
                            </div>
                        </div>

                        {{-- Org 2 --}}
                        <div class="oso-rank-item">
                            <div class="oso-rank-header">
                                <span class="oso-rank-name">
                                    <i class="bi bi-award" style="color: #94a3b8;"></i> Jr. Philippine Inst. of Civil Engineers (JPICE)
                                </span>
                                <span class="oso-rank-stat" id="rankOrgStat2"><strong>9</strong> submissions &middot; <span style="color: #16a34a;">88% pass</span></span>
                            </div>
                            <div class="oso-rank-bar-bg">
                                <div class="oso-rank-bar-fill" id="rankOrgBar2" style="width: 64%; background: #9b1b30;"></div>
                            </div>
                        </div>

                        {{-- Org 3 --}}
                        <div class="oso-rank-item">
                            <div class="oso-rank-header">
                                <span class="oso-rank-name">
                                    <i class="bi bi-award" style="color: #b45309;"></i> Jr. Philippine Computer Society (JPCS)
                                </span>
                                <span class="oso-rank-stat" id="rankOrgStat3"><strong>8</strong> submissions &middot; <span style="color: #16a34a;">85% pass</span></span>
                            </div>
                            <div class="oso-rank-bar-bg">
                                <div class="oso-rank-bar-fill" id="rankOrgBar3" style="width: 57%; background: #b8233d;"></div>
                            </div>
                        </div>

                        {{-- Org 4 --}}
                        <div class="oso-rank-item">
                            <div class="oso-rank-header">
                                <span class="oso-rank-name">
                                    <i class="bi bi-heart-pulse-fill" style="color: #dc2626;"></i> Red Cross Youth (RCY)
                                </span>
                                <span class="oso-rank-stat" id="rankOrgStat4"><strong>6</strong> submissions &middot; <span style="color: #16a34a;">100% pass</span></span>
                            </div>
                            <div class="oso-rank-bar-bg">
                                <div class="oso-rank-bar-fill" id="rankOrgBar4" style="width: 43%; background: #c43b52;"></div>
                            </div>
                        </div>

                        {{-- Org 5 --}}
                        <div class="oso-rank-item">
                            <div class="oso-rank-header">
                                <span class="oso-rank-name">
                                    <i class="bi bi-cpu-fill" style="color: #635b5e;"></i> Assoc. of Electronics Eng. Students (AECES)
                                </span>
                                <span class="oso-rank-stat" id="rankOrgStat5"><strong>5</strong> submissions &middot; <span style="color: #ca8a04;">80% pass</span></span>
                            </div>
                            <div class="oso-rank-bar-bg">
                                <div class="oso-rank-bar-fill" id="rankOrgBar5" style="width: 35%; background: #d45d71;"></div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            {{-- 4. MIDDLE ROW 3: Incomplete/Revision Rate (Donut/Bar) & Processing Time --}}
            <div class="oso-analytics-2col">

                {{-- Card E: Incomplete/Revision Rate & Root Causes (Required Table Item 7 - Donut/Bar) --}}
                <section class="oso-analytics-card">
                    <div class="oso-card-head">
                        <div class="oso-card-title-group">
                            <h3><i class="bi bi-shield-exclamation" style="color: #8b1828;"></i> Incomplete / Revision Analysis</h3>
                            <p>14.2% return rate and top compliance deficiencies</p>
                        </div>
                        <span class="oso-stat-badge is-neutral">Quality Audit</span>
                    </div>

                    <div>
                        {{-- Top Return Causes List --}}
                        <div style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #706569; margin-bottom: 0.4rem;">
                            Top Reasons for Returned Submissions
                        </div>
                        <div class="oso-causes-list">
                            <div class="oso-cause-item">
                                <div class="oso-cause-left">
                                    <i class="bi bi-pen-fill" style="color: #dc2626;"></i>
                                    <span>Missing Faculty Adviser / Dean Signature</span>
                                </div>
                                <span class="oso-cause-pct">42% of returns</span>
                            </div>

                            <div class="oso-cause-item">
                                <div class="oso-cause-left">
                                    <i class="bi bi-calculator-fill" style="color: #d97706;"></i>
                                    <span>Budget Itemization vs Receipt Discrepancy</span>
                                </div>
                                <span class="oso-cause-pct">28% of returns</span>
                            </div>

                            <div class="oso-cause-item">
                                <div class="oso-cause-left">
                                    <i class="bi bi-recycle" style="color: #16a34a;"></i>
                                    <span>Lacking SDO Waste Policy (WPCF) Form</span>
                                </div>
                                <span class="oso-cause-pct">18% of returns</span>
                            </div>

                            <div class="oso-cause-item">
                                <div class="oso-cause-left">
                                    <i class="bi bi-file-earmark-medical-fill" style="color: #2563eb;"></i>
                                    <span>Incomplete Safety Protocol &amp; Medical Clearance</span>
                                </div>
                                <span class="oso-cause-pct">12% of returns</span>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Card F: Processing Time Turnaround --}}
                <section class="oso-analytics-card">
                    <div class="oso-card-head">
                        <div class="oso-card-title-group">
                            <h3><i class="bi bi-clock-history" style="color: #8b1828;"></i> Average Processing Time (Days)</h3>
                            <p>Turnaround speed per transaction type vs. SLA benchmark (3.0 Days)</p>
                        </div>
                        <span class="oso-stat-badge is-positive">Target &le; 3.0d</span>
                    </div>

                    <div class="oso-canvas-wrap">
                        <canvas id="osoProcessingTimeChart"></canvas>
                    </div>
                </section>

            </div>

            {{-- 5. BOTTOM SECTION: Recent Transactions Table (Required Table Item 8 - Table, not chart) --}}
            <section class="oso-transactions-table-card">
                <div class="org-dash-card-header" style="padding: 1.25rem 1.65rem 1rem; border-bottom: 1px solid #f6eff0; margin-bottom: 0;">
                    <div>
                        <h3 class="org-dash-card-title">
                            <i class="bi bi-table" style="color: #8b1828;"></i> Recent Transactions Requiring Action
                        </h3>
                        <span style="font-size: 0.78rem; color: #786f73;">Latest submitted student organization documents in the OSO review pipeline</span>
                    </div>
                    <a href="{{ route('office.activities') }}" class="org-dash-link">
                        View Full Pipeline &rarr;
                    </a>
                </div>

                <div class="oso-table-container">
                    <table class="oso-trx-table">
                        <thead>
                            <tr>
                                <th>Transaction / Activity</th>
                                <th>Organization</th>
                                <th>Type</th>
                                <th>Date Submitted</th>
                                <th>Urgency / SLA</th>
                                <th>Status</th>
                                <th style="text-align: right; width: 120px; min-width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Trx 1 --}}
                            <tr class="oso-trx-row" data-year="2025-2026" data-sem="sem2" data-month="May" data-scope="off-campus">
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <strong>Leadership Summit 2026</strong>
                                        <small style="color: #786f73; font-size: 0.74rem;">TRX-2026-0089 &middot; Off-Campus</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="org-grid-org-chip" style="font-size: 0.74rem;">
                                        <i class="bi bi-building"></i> Supreme Student Council
                                    </span>
                                </td>
                                <td>
                                    <span class="oso-type-badge oso-type-proposal">
                                        <i class="bi bi-file-earmark-text-fill"></i> Activity Proposal
                                    </span>
                                </td>
                                <td><span style="font-size: 0.82rem; color: #554d50;">May 12, 2026</span></td>
                                <td><span class="oso-sla-pill oso-sla-high"><i class="bi bi-clock-fill"></i> 1 Day Left</span></td>
                                <td>
                                    <span class="org-status-pill org-status-yellow">
                                        <span class="org-status-dot"></span> For Approval
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => 'leadership-summit-2026']) }}" class="org-btn-view-pill">
                                        Review
                                    </a>
                                </td>
                            </tr>

                            {{-- Trx 2 --}}
                            <tr class="oso-trx-row" data-year="2025-2026" data-sem="sem2" data-month="May" data-scope="in-campus">
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <strong>Semestral Financial Report 2026</strong>
                                        <small style="color: #786f73; font-size: 0.74rem;">TRX-2026-0092 &middot; 2nd Semester</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="org-grid-org-chip" style="font-size: 0.74rem;">
                                        <i class="bi bi-building"></i> Jr. PICE Student Chapter
                                    </span>
                                </td>
                                <td>
                                    <span class="oso-type-badge oso-type-fr">
                                        <i class="bi bi-wallet2"></i> Financial Report (FR)
                                    </span>
                                </td>
                                <td><span style="font-size: 0.82rem; color: #554d50;">May 12, 2026</span></td>
                                <td><span class="oso-sla-pill oso-sla-normal"><i class="bi bi-clock"></i> 2 Days Left</span></td>
                                <td>
                                    <span class="org-status-pill org-status-blue">
                                        <span class="org-status-dot"></span> In Review
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.financial') }}" class="org-btn-view-pill">
                                        Audit FR
                                    </a>
                                </td>
                            </tr>

                            {{-- Trx 3 --}}
                            <tr class="oso-trx-row" data-year="2025-2026" data-sem="sem2" data-month="May" data-scope="in-campus">
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <strong>Accreditation Renewal Packet 2026-2027</strong>
                                        <small style="color: #786f73; font-size: 0.74rem;">TRX-2026-0095 &middot; Annual Filing</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="org-grid-org-chip" style="font-size: 0.74rem;">
                                        <i class="bi bi-building"></i> JPCS BatStateU
                                    </span>
                                </td>
                                <td>
                                    <span class="oso-type-badge oso-type-renewal">
                                        <i class="bi bi-patch-check-fill"></i> Renewal
                                    </span>
                                </td>
                                <td><span style="font-size: 0.82rem; color: #554d50;">May 11, 2026</span></td>
                                <td><span class="oso-sla-pill oso-sla-normal"><i class="bi bi-clock"></i> 3 Days Left</span></td>
                                <td>
                                    <span class="org-status-pill org-status-yellow">
                                        <span class="org-status-dot"></span> Pending Verification
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities') }}" class="org-btn-view-pill">
                                        Inspect
                                    </a>
                                </td>
                            </tr>

                            {{-- Trx 4 --}}
                            <tr class="oso-trx-row" data-year="2025-2026" data-sem="sem2" data-month="May" data-scope="in-campus">
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <strong>Campus Wellness Week</strong>
                                        <small style="color: #786f73; font-size: 0.74rem;">TRX-2026-0084 &middot; In-Campus</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="org-grid-org-chip" style="font-size: 0.74rem;">
                                        <i class="bi bi-building"></i> Red Cross Youth
                                    </span>
                                </td>
                                <td>
                                    <span class="oso-type-badge oso-type-proposal">
                                        <i class="bi bi-file-earmark-text-fill"></i> Activity Proposal
                                    </span>
                                </td>
                                <td><span style="font-size: 0.82rem; color: #554d50;">May 10, 2026</span></td>
                                <td><span class="oso-sla-pill oso-sla-normal"><i class="bi bi-check2"></i> Endorsed</span></td>
                                <td>
                                    <span class="org-status-pill org-status-blue">
                                        <span class="org-status-dot"></span> In Review (SDO)
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => 'campus-wellness-week']) }}" class="org-btn-view-pill">
                                        Details
                                    </a>
                                </td>
                            </tr>

                            {{-- Trx 5 --}}
                            <tr class="oso-trx-row" data-year="2025-2026" data-sem="sem2" data-month="May" data-scope="in-campus">
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <strong>Ten Outstanding Student Award (TOSA) Portfolio</strong>
                                        <small style="color: #786f73; font-size: 0.74rem;">TRX-2026-0099 &middot; TOSA 2026</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="org-grid-org-chip" style="font-size: 0.74rem;">
                                        <i class="bi bi-building"></i> Supreme Student Council
                                    </span>
                                </td>
                                <td>
                                    <span class="oso-type-badge oso-type-tosa">
                                        <i class="bi bi-trophy-fill"></i> TOSA
                                    </span>
                                </td>
                                <td><span style="font-size: 0.82rem; color: #554d50;">May 09, 2026</span></td>
                                <td><span class="oso-sla-pill oso-sla-normal"><i class="bi bi-clock"></i> 4 Days Left</span></td>
                                <td>
                                    <span class="org-status-pill org-status-yellow">
                                        <span class="org-status-dot"></span> Initial Screening
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities') }}" class="org-btn-view-pill">
                                        Evaluate
                                    </a>
                                </td>
                            </tr>

                            {{-- Trx 6 --}}
                            <tr class="oso-trx-row" data-year="2025-2026" data-sem="sem2" data-month="May" data-scope="in-campus">
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <strong>BatStateU Sportsfest 2026</strong>
                                        <small style="color: #786f73; font-size: 0.74rem;">TRX-2026-0078 &middot; In-Campus</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="org-grid-org-chip" style="font-size: 0.74rem;">
                                        <i class="bi bi-building"></i> Assoc. of Electronics Eng.
                                    </span>
                                </td>
                                <td>
                                    <span class="oso-type-badge oso-type-proposal">
                                        <i class="bi bi-file-earmark-text-fill"></i> Activity Proposal
                                    </span>
                                </td>
                                <td><span style="font-size: 0.82rem; color: #554d50;">May 08, 2026</span></td>
                                <td><span class="oso-sla-pill oso-sla-high"><i class="bi bi-arrow-counterclockwise"></i> Returned</span></td>
                                <td>
                                    <span class="org-status-pill org-status-red">
                                        <span class="org-status-dot"></span> For Revision
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => 'batstateu-sportsfest-2026']) }}" class="org-btn-view-pill">
                                        Remarks
                                    </a>
                                </td>
                            </tr>

                            {{-- Empty State Row --}}
                            <tr id="osoNoTransactionsRow" style="display: none;">
                                <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: #786f73;">
                                    <i class="bi bi-inbox" style="font-size: 1.75rem; color: #8b1828; display: block; margin-bottom: 0.5rem;"></i>
                                    <strong style="font-size: 0.95rem; color: #1a1618;">No transactions found for this period</strong>
                                    <p style="font-size: 0.78rem; margin: 0.25rem 0 0;">Try adjusting your Academic Year, Semester, Month, or Scope filters above.</p>
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
                    {{-- 1. Total Monitored --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-maroon" title="Total Monitored SDG Proposals">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-maroon">
                                <i class="bi bi-clipboard-data-fill"></i>
                            </div>
                            <strong>5</strong>
                        </div>
                        <span class="ovcaa-stat-title">Total Monitored</span>
                    </a>

                    {{-- 2. SDG Verified --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-green" title="SDG Verified & Endorsed">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-green">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <strong>2</strong>
                        </div>
                        <span class="ovcaa-stat-title">SDG Verified</span>
                    </a>

                    {{-- 3. Under SDG Review --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-blue" title="Under SDG & WPCF Review">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-blue">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <strong>2</strong>
                        </div>
                        <span class="ovcaa-stat-title">Under SDG Review</span>
                    </a>

                    {{-- 4. Needs SDG Revision --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-amber" title="Needs SDG Revision">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-amber">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <strong>1</strong>
                        </div>
                        <span class="ovcaa-stat-title">Needs SDG Revision</span>
                    </a>
                @elseif ($isOvcaa)
                    {{-- 1. Pending Final Approval --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-blue" title="Open Pending Final Approval Queue">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-blue">
                                <i class="bi bi-patch-check-fill"></i>
                            </div>
                            <strong>2</strong>
                        </div>
                        <span class="ovcaa-stat-title">Pending Final Approval</span>
                    </a>

                    {{-- 2. OVCAA Approved --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-green" title="View OVCAA Approved Activities">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-green">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <strong>2</strong>
                        </div>
                        <span class="ovcaa-stat-title">OVCAA Approved</span>
                    </a>

                    {{-- 3. Return for Revision --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-amber" title="View Activities Returned for Revision">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-amber">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </div>
                            <strong>1</strong>
                        </div>
                        <span class="ovcaa-stat-title">Return for Revision</span>
                    </a>

                    {{-- 4. Total Submissions --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-maroon" title="View All Semester Submissions">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-maroon">
                                <i class="bi bi-collection-fill"></i>
                            </div>
                            <strong>5</strong>
                        </div>
                        <span class="ovcaa-stat-title">Total Submissions</span>
                    </a>
                @else
                    {{-- Student Org Top KPI Cards --}}
                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-maroon" title="Total Activities">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-maroon">
                                <i class="bi bi-lightning-charge-fill"></i>
                            </div>
                            <strong>5</strong>
                        </div>
                        <span class="ovcaa-stat-title">Total Activities</span>
                    </a>

                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-green" title="Approved Activities">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-green">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <strong>2</strong>
                        </div>
                        <span class="ovcaa-stat-title">Approved</span>
                    </a>

                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-blue" title="Pending Activities">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-blue">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <strong>2</strong>
                        </div>
                        <span class="ovcaa-stat-title">Pending</span>
                    </a>

                    <a href="{{ route('office.activities') }}" class="ovcaa-stat-card liquid-glass is-amber" title="Activities Needing Action">
                        <div class="ovcaa-stat-top">
                            <div class="ovcaa-stat-icon is-amber">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <strong>1</strong>
                        </div>
                        <span class="ovcaa-stat-title">Needs Action</span>
                    </a>
                @endif
            </div>

            {{-- 2. Middle Section --}}
            <div class="org-dash-2col">
                @if ($isSdo)
                    {{-- Hidden SVG Defs for SDO Donut Gradient --}}
                    <svg style="width:0;height:0;position:absolute;" aria-hidden="true" focusable="false">
                        <defs>
                            <linearGradient id="sdoDonutGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#15803d" />
                                <stop offset="100%" stop-color="#22c55e" />
                            </linearGradient>
                        </defs>
                    </svg>

                    {{-- SDO: SDG Alignment & Monitoring Overview --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-leaf-fill" style="color: #8b1828;"></i> SDG Alignment Monitoring
                            </h3>
                            <a href="{{ route('office.activities') }}" class="org-dash-link">
                                Review Queue <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <p class="org-dash-card-sub" style="margin-top: -0.5rem; margin-bottom: 1rem; font-size: 0.8rem; color: #64748b;">
                            Campus SDG compliance and sustainability indicator monitoring.
                        </p>

                        <div class="ovcaa-overview-body">
                            {{-- Split Hero: Campus SDG Compliance & Donut Ring (80%) --}}
                            <div class="ovcaa-metrics-split">
                                <div class="ovcaa-kpi-block">
                                    <span class="ovcaa-kpi-subhead" style="color: #15803d;">Campus SDG Compliance</span>
                                    <h2>80% Verified</h2>
                                    <p>4 of 5 proposed activities aligned with UN SDGs</p>
                                </div>

                                {{-- Circular Progress / Donut Ring: 80% --}}
                                <div class="ovcaa-donut-wrap" title="SDG Compliance: 80% Verified">
                                    <svg class="ovcaa-donut-svg" viewBox="0 0 98 98">
                                        <circle class="ovcaa-donut-track" cx="49" cy="49" r="40"></circle>
                                        <circle class="ovcaa-donut-fill" cx="49" cy="49" r="40" style="stroke: url(#sdoDonutGrad); stroke-dashoffset: 50.3;"></circle>
                                    </svg>
                                    <div class="ovcaa-donut-content">
                                        <span class="ovcaa-donut-val" style="color: #15803d;">80%</span>
                                        <span class="ovcaa-donut-lbl">Verified</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Indicators --}}
                            <div class="ovcaa-distribution-box">
                                <div style="display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 0.25rem;">
                                    <span>SDG Indicators Met</span>
                                    <strong style="color: #15803d;">4 / 5 Activities (80%)</strong>
                                </div>
                                <div class="org-mini-progress" style="margin-bottom: 0.75rem; height: 7px;">
                                    <div class="org-mini-fill-green" style="width: 80%; background: linear-gradient(90deg, #15803d, #22c55e);"></div>
                                </div>

                                <div style="display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 0.25rem;">
                                    <span>WPCF Protocol Compliance</span>
                                    <strong style="color: #ca8a04;">3 Cleared · 1 Pending (75%)</strong>
                                </div>
                                <div class="org-mini-progress" style="margin-bottom: 0.25rem; height: 7px;">
                                    <div class="org-mini-fill-maroon" style="width: 75%; background: linear-gradient(90deg, #ca8a04, #eab308);"></div>
                                </div>
                            </div>

                            {{-- Sub-Stats & SDG Chips --}}
                            <div class="org-budget-sub-stats" style="margin-top: -0.25rem;">
                                <div class="org-budget-sub-box is-green" style="background: #f0fdf4; border: 1px solid #dcfce7; padding: 0.65rem 0.85rem;">
                                    <span style="color: #166534; font-size: 0.7rem;">Top Priority Goal</span>
                                    <strong style="color: #15803d; font-size: 0.88rem;">SDG 4 Education</strong>
                                </div>
                                <div class="org-budget-sub-box is-pink" style="background: #fdf0f2; border: 1px solid #fae1e5; padding: 0.65rem 0.85rem;">
                                    <span style="color: #8b1828; font-size: 0.7rem;">Awaiting SDG Review</span>
                                    <strong style="color: #8b1828; font-size: 0.88rem;">2 Proposals</strong>
                                </div>
                            </div>

                            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                <span class="org-chip" style="background: #f0fdf4; color: #166534; font-weight: 700; font-size: 0.7rem; padding: 0.2rem 0.55rem; border-radius: 6px; border: 1px solid #dcfce7;">SDG 3 · Health</span>
                                <span class="org-chip" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 0.7rem; padding: 0.2rem 0.55rem; border-radius: 6px; border: 1px solid #bfdbfe;">SDG 4 · Education</span>
                                <span class="org-chip" style="background: #fefce8; color: #a16207; font-weight: 700; font-size: 0.7rem; padding: 0.2rem 0.55rem; border-radius: 6px; border: 1px solid #fef08a;">SDG 11 · Cities</span>
                                <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-weight: 700; font-size: 0.7rem; padding: 0.2rem 0.55rem; border-radius: 6px; border: 1px solid #f0e6e8;">SDG 12 · Consumption</span>
                            </div>
                        </div>
                    </section>

                    {{-- SDO SDG Action Items --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-clipboard-check-fill" style="color: #8b1828;"></i> SDG Review Action Items
                            </h3>
                            <span class="ovcaa-endorsed-badge">
                                <i class="bi bi-clipboard-check-fill"></i> 3 Pending
                            </span>
                        </div>
                        <p class="org-dash-card-sub" style="margin-top: -0.5rem; margin-bottom: 1rem; font-size: 0.8rem; color: #64748b;">
                            Proposals requiring SDG indicator verification and waste protocol clearance.
                        </p>

                        <div class="org-action-items-list">
                            {{-- Action 1: Campus Wellness Week --}}
                            <div class="ovcaa-action-item">
                                <div class="ovcaa-action-left">
                                    <div class="ovcaa-action-icon" style="background: #f0fdf4; color: #15803d; border-color: #dcfce7;">
                                        <i class="bi bi-heart-pulse-fill"></i>
                                    </div>
                                    <div class="ovcaa-action-meta">
                                        <strong>Review Waste Protocol &amp; Health Plan</strong>
                                        <small>Campus Wellness Week · SDG 3 Health alignment</small>
                                    </div>
                                </div>
                                <div class="ovcaa-action-right">
                                    <span class="org-chip" style="background: #f0fdf4; color: #15803d; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 6px; border: 1px solid #dcfce7;">SDG 3</span>
                                    <a href="{{ route('office.activities', ['activity' => 'campus-wellness-week']) }}" class="ovcaa-btn-decide">
                                        Review <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </div>
                            </div>

                            {{-- Action 2: Leadership Summit 2026 --}}
                            <div class="ovcaa-action-item">
                                <div class="ovcaa-action-left">
                                    <div class="ovcaa-action-icon" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe;">
                                        <i class="bi bi-award-fill"></i>
                                    </div>
                                    <div class="ovcaa-action-meta">
                                        <strong>Verify Zero Single-Use Plastics Dossier</strong>
                                        <small>Leadership Summit 2026 · SDG 12 Consumption</small>
                                    </div>
                                </div>
                                <div class="ovcaa-action-right">
                                    <span class="org-chip" style="background: #eff6ff; color: #2563eb; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 6px; border: 1px solid #bfdbfe;">SDG 12</span>
                                    <a href="{{ route('office.activities', ['activity' => 'leadership-summit-2026']) }}" class="ovcaa-btn-decide">
                                        Review <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </div>
                            </div>

                            {{-- Action 3: BatStateU Sportsfest 2026 --}}
                            <div class="ovcaa-action-item">
                                <div class="ovcaa-action-left">
                                    <div class="ovcaa-action-icon" style="background: #fefce8; color: #b45309; border-color: #fef08a;">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </div>
                                    <div class="ovcaa-action-meta">
                                        <strong>Monitor Sustainability Revisions</strong>
                                        <small>BatStateU Sportsfest 2026 · Returned for revision</small>
                                    </div>
                                </div>
                                <div class="ovcaa-action-right">
                                    <span class="org-chip" style="background: #fefce8; color: #b45309; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 6px; border: 1px solid #fef08a;">Needs Fix</span>
                                    <a href="{{ route('office.activities', ['activity' => 'batstateu-sportsfest-2026']) }}" class="ovcaa-btn-decide">
                                        Review <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                @elseif ($isOvcaa)
                    {{-- Hidden SVG Defs for Donut Gradient --}}
                    <svg style="width:0;height:0;position:absolute;" aria-hidden="true" focusable="false">
                        <defs>
                            <linearGradient id="ovcaaDonutGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#7a1222" />
                                <stop offset="100%" stop-color="#c43b52" />
                            </linearGradient>
                        </defs>
                    </svg>

                    {{-- 1. Executive Approvals Overview (KPI + Donut + Segmented Bar) --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-patch-check-fill" style="color: #8b1828;"></i> Executive Approvals Overview
                            </h3>
                            <a href="{{ route('office.activities') }}" class="org-dash-link">
                                Approval Queue <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <p class="org-dash-card-sub" style="margin-top: -0.5rem; margin-bottom: 1rem; font-size: 0.8rem; color: #64748b;">
                            Final university authorization checkpoint for student organization activities.
                        </p>

                        <div class="ovcaa-overview-body">
                            {{-- Split Hero: KPI Large Metric & Donut Ring Approval Rate --}}
                            <div class="ovcaa-metrics-split">
                                <div class="ovcaa-kpi-block">
                                    <span class="ovcaa-kpi-subhead">Total Submitted Activities</span>
                                    <h2>5 Activities</h2>
                                    <p>AY 2025–2026 · Across All Recognized Portfolios</p>
                                </div>

                                {{-- Circular Progress / Donut Ring: Approval Rate (40%) --}}
                                <div class="ovcaa-donut-wrap" title="Approval Rate: 40% executed/approved">
                                    <svg class="ovcaa-donut-svg" viewBox="0 0 98 98">
                                        <circle class="ovcaa-donut-track" cx="49" cy="49" r="40"></circle>
                                        <circle class="ovcaa-donut-fill" cx="49" cy="49" r="40"></circle>
                                    </svg>
                                    <div class="ovcaa-donut-content">
                                        <span class="ovcaa-donut-val">40%</span>
                                        <span class="ovcaa-donut-lbl">Approved</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Approval Distribution: Segmented Progress Bar --}}
                            <div class="ovcaa-distribution-box">
                                <div class="ovcaa-dist-head">
                                    <span>Approval Distribution</span>
                                    <strong>5 Total Submissions</strong>
                                </div>
                                <div class="ovcaa-segmented-bar" title="40% Approved · 40% Governance Queue · 20% Revision">
                                    <div class="ovcaa-seg is-approved" style="width: 40%;" title="2 Approved (40%)"></div>
                                    <div class="ovcaa-seg is-queue" style="width: 40%;" title="2 In Governance Queue (40%)"></div>
                                    <div class="ovcaa-seg is-revision" style="width: 20%;" title="1 Return for Revision (20%)"></div>
                                </div>
                                <div class="ovcaa-dist-chips">
                                    <span class="ovcaa-dist-chip is-approved">
                                        <span class="ovcaa-dist-dot"></span> 2 Approved
                                    </span>
                                    <span class="ovcaa-dist-chip is-queue">
                                        <span class="ovcaa-dist-dot"></span> 2 Governance Queue
                                    </span>
                                    <span class="ovcaa-dist-chip is-revision">
                                        <span class="ovcaa-dist-dot"></span> 1 Revision
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- 2. Executive Action Queue --}}
                    <section class="org-dash-card">
                        <div class="org-dash-card-header">
                            <h3 class="org-dash-card-title">
                                <i class="bi bi-shield-lock-fill" style="color: #8b1828;"></i> Executive Action Queue
                            </h3>
                            <span class="ovcaa-endorsed-badge">
                                <i class="bi bi-patch-check-fill"></i> 2 Endorsed
                            </span>
                        </div>
                        <p class="org-dash-card-sub" style="margin-top: -0.5rem; margin-bottom: 1rem; font-size: 0.8rem; color: #64748b;">
                            Activities requiring executive decision and final university sign-off.
                        </p>

                        <div class="org-action-items-list">
                            {{-- Action 1: Campus Wellness Week --}}
                            <div class="ovcaa-action-item">
                                <div class="ovcaa-action-left">
                                    <div class="ovcaa-action-icon">
                                        <i class="bi bi-heart-pulse-fill"></i>
                                    </div>
                                    <div class="ovcaa-action-meta">
                                        <strong>Campus Wellness Week</strong>
                                        <small>SDO SDG clearance certified · Ready for final OVCAA signing</small>
                                    </div>
                                </div>
                                <div class="ovcaa-action-right">
                                    <span class="org-chip" style="background: #f0fdf4; color: #15803d; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 6px;">SDG Cleared</span>
                                    <a href="{{ route('office.activities', ['activity' => 'campus-wellness-week']) }}" class="ovcaa-btn-decide">
                                        Decide <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </div>
                            </div>

                            {{-- Action 2: Leadership Summit 2026 --}}
                            <div class="ovcaa-action-item">
                                <div class="ovcaa-action-left">
                                    <div class="ovcaa-action-icon">
                                        <i class="bi bi-trophy-fill"></i>
                                    </div>
                                    <div class="ovcaa-action-meta">
                                        <strong>Leadership Summit 2026</strong>
                                        <small>OSO clearance approved · Awaiting executive authorization</small>
                                    </div>
                                </div>
                                <div class="ovcaa-action-right">
                                    <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 6px; border: 1px solid #f0e6e8;">OSO Cleared</span>
                                    <a href="{{ route('office.activities', ['activity' => 'leadership-summit-2026']) }}" class="ovcaa-btn-decide">
                                        Decide <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </div>
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
                        <div class="org-stepper-track-fill" style="width: 75%; background: #16a34a;"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node is-done" style="background: #16a34a; border-color: #16a34a;"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done" style="background: #16a34a; border-color: #16a34a;"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done" style="background: #16a34a; border-color: #16a34a;"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done" style="background: #16a34a; border-color: #16a34a;"><i class="bi bi-check"></i></span>
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
                        <div class="org-stepper-track-fill" style="width: 75%; background: #16a34a;"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node is-done" style="background: #16a34a; border-color: #16a34a;"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done" style="background: #16a34a; border-color: #16a34a;"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done" style="background: #16a34a; border-color: #16a34a;"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done" style="background: #16a34a; border-color: #16a34a;"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node"></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="org-status-pill org-status-green">
                            <span class="org-status-dot"></span> OVCAA Approved
                        </span>
                    </div>
                </div>

                {{-- Row 3: Leadership Summit 2026 (Stage: OVCAA Review / In Review) --}}
                <div class="org-pipeline-row">
                    <span class="org-pipeline-title">Leadership Summit 2026</span>
                    <div class="org-pipeline-stepper">
                        <div class="org-stepper-track-bg"></div>
                        <div class="org-stepper-track-fill" style="width: 50%;"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node is-done"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-active-gold" title="In OVCAA Review"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="org-status-pill org-status-yellow">
                            <span class="org-status-dot"></span> OVCAA Review
                        </span>
                    </div>
                </div>

                {{-- Row 4: Campus Wellness Week (Stage: OVCAA Executive Action) --}}
                <div class="org-pipeline-row">
                    <span class="org-pipeline-title">Campus Wellness Week</span>
                    <div class="org-pipeline-stepper">
                        <div class="org-stepper-track-bg"></div>
                        <div class="org-stepper-track-fill" style="width: 50%;"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node is-done"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-done"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-active-gold" title="Awaiting OVCAA Final Signature"></span>
                            <span class="org-stepper-node"></span>
                            <span class="org-stepper-node"></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="org-status-pill org-status-blue">
                            <span class="org-status-dot"></span> Executive Action
                        </span>
                    </div>
                </div>

                {{-- Row 5: BatStateU Sportsfest 2026 (Stage: Return for Revision) --}}
                <div class="org-pipeline-row">
                    <span class="org-pipeline-title">BatStateU Sportsfest 2026</span>
                    <div class="org-pipeline-stepper">
                        <div class="org-stepper-track-bg"></div>
                        <div class="org-stepper-nodes">
                            <span class="org-stepper-node is-done"><i class="bi bi-check"></i></span>
                            <span class="org-stepper-node is-returned" style="border-color: #dc2626; color: #dc2626; background: #ffffff;" title="Returned by OSO">
                                <i class="bi bi-x" style="font-size: 0.9rem; line-height: 1;"></i>
                            </span>
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
                                <span class="org-status-dot"></span> OVCAA Review
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
                                <span class="org-status-dot"></span> Executive Action
                            </span>
                        </a>

                        <a href="{{ route('office.activities', ['activity' => 'volunteer-appreciation-day']) }}" class="org-upcoming-card-item">
                            <div class="org-upcoming-left">
                                <div class="org-date-badge" style="background: #15803d;">
                                    <strong>24</strong>
                                    <small>SEP</small>
                                </div>
                                <div class="org-upcoming-meta">
                                    <strong>Volunteer Appreciation Day</strong>
                                    <small><i class="bi bi-geo-alt-fill" style="color: #15803d;"></i> Main Amphitheater · In-Campus</small>
                                </div>
                            </div>
                            <span class="org-status-pill org-status-green">
                                <span class="org-status-dot"></span> OVCAA Approved
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

    @if ($isOso)
        {{-- Chart.js CDN & OSO Dashboard Interactive Script --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Global Chart Instances
            window.osoCharts = {
                donut: null,
                trend: null,
                type: null,
                time: null
            };

            const osoFilterData = {
                '2025-2026': {
                    totalOrgs: 12,
                    orgSub: '8 Academic · 3 Non-Academic · 1 SSC',
                    pendingTrx: 7,
                    pendingSub: '3 Proposals · 2 FR · 1 Renewal · 1 TOSA',
                    totalSubmissions: 48,
                    submissionsSub: 'Across all student org portfolios',
                    revisionRate: '14.2%',
                    revisionSub: 'Returned for incomplete compliance',
                    approvalDonut: [27, 11, 7, 3],
                    scope: { inCampus: 38, offCampus: 10 },
                    trend: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                        data: [4, 8, 12, 9, 15, 7, 14, 18, 22, 19]
                    },
                    typeBreakdown: [22, 10, 8, 5, 3],
                    orgRanking: [
                        { count: 14, pct: 100, pass: '92%' },
                        { count: 9, pct: 64, pass: '88%' },
                        { count: 8, pct: 57, pass: '85%' },
                        { count: 6, pct: 43, pass: '100%' },
                        { count: 5, pct: 35, pass: '80%' }
                    ]
                },
                '2024-2025': {
                    totalOrgs: 10,
                    orgSub: '7 Academic · 2 Non-Academic · 1 SSC',
                    pendingTrx: 1,
                    pendingSub: '1 Archived Compliance Review',
                    totalSubmissions: 64,
                    submissionsSub: 'Full academic year record',
                    revisionRate: '18.5%',
                    revisionSub: 'A.Y. 2024–2025 Historical Rate',
                    approvalDonut: [46, 3, 11, 4],
                    scope: { inCampus: 51, offCampus: 13 },
                    trend: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        data: [6, 9, 14, 12, 18, 11, 8, 15, 25, 21, 16, 10]
                    },
                    typeBreakdown: [31, 14, 11, 6, 2],
                    orgRanking: [
                        { count: 18, pct: 100, pass: '90%' },
                        { count: 14, pct: 78, pass: '86%' },
                        { count: 12, pct: 67, pass: '84%' },
                        { count: 10, pct: 56, pass: '95%' },
                        { count: 10, pct: 56, pass: '82%' }
                    ]
                },
                '2023-2024': {
                    totalOrgs: 9,
                    orgSub: '6 Academic · 2 Non-Academic · 1 SSC',
                    pendingTrx: 0,
                    pendingSub: 'All historical cycles closed',
                    totalSubmissions: 52,
                    submissionsSub: 'Full academic year record',
                    revisionRate: '21.0%',
                    revisionSub: 'A.Y. 2023–2024 Historical Rate',
                    approvalDonut: [38, 0, 9, 5],
                    scope: { inCampus: 42, offCampus: 10 },
                    trend: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        data: [5, 7, 10, 8, 14, 9, 6, 12, 19, 16, 12, 7]
                    },
                    typeBreakdown: [26, 10, 8, 5, 3],
                    orgRanking: [
                        { count: 15, pct: 100, pass: '87%' },
                        { count: 11, pct: 73, pass: '82%' },
                        { count: 10, pct: 67, pass: '80%' },
                        { count: 9, pct: 60, pass: '92%' },
                        { count: 7, pct: 47, pass: '78%' }
                    ]
                },
                'all': {
                    totalOrgs: 12,
                    orgSub: 'Recognized student org network',
                    pendingTrx: 7,
                    pendingSub: 'Current active action queue',
                    totalSubmissions: 164,
                    submissionsSub: 'Cumulative multi-year repository',
                    revisionRate: '17.4%',
                    revisionSub: 'All-time average compliance rate',
                    approvalDonut: [111, 14, 27, 12],
                    scope: { inCampus: 131, offCampus: 33 },
                    trend: {
                        labels: ['2023-Q1', '2023-Q2', '2023-Q3', '2023-Q4', '2024-Q1', '2024-Q2', '2024-Q3', '2024-Q4', '2025-Q1', '2025-Q2'],
                        data: [12, 23, 31, 19, 15, 30, 40, 26, 20, 28]
                    },
                    typeBreakdown: [79, 34, 27, 16, 8],
                    orgRanking: [
                        { count: 47, pct: 100, pass: '90%' },
                        { count: 32, pct: 68, pass: '84%' },
                        { count: 32, pct: 68, pass: '85%' },
                        { count: 25, pct: 53, pass: '96%' },
                        { count: 22, pct: 47, pass: '80%' }
                    ]
                }
            };

            // Dynamic Filter Engine
            function applyOsoFilters() {
                const yearSelect = document.getElementById('osoYearSelect');
                const semSelect = document.getElementById('osoSemesterSelect');
                const monthSelect = document.getElementById('osoMonthSelect');
                const scopeSelect = document.getElementById('osoScopeSelect');

                const selectedYear = yearSelect ? yearSelect.value : '2025-2026';
                const selectedSem = semSelect ? semSelect.value : 'all';
                const selectedMonth = monthSelect ? monthSelect.value : 'all';
                const selectedScope = scopeSelect ? scopeSelect.value : 'all';

                const data = osoFilterData[selectedYear] || osoFilterData['2025-2026'];

                // 1. Calculate Multipliers based on Month/Semester filter
                let multiplier = 1.0;
                if (selectedMonth !== 'all') {
                    multiplier = 0.22;
                } else if (selectedSem === 'sem1' || selectedSem === 'sem2') {
                    multiplier = 0.52;
                } else if (selectedSem === 'midyear') {
                    multiplier = 0.15;
                }

                const currentTotalSubs = Math.max(1, Math.round(data.totalSubmissions * multiplier * (selectedScope === 'all' ? 1.0 : (selectedScope === 'in-campus' ? 0.792 : 0.208))));
                const currentPending = (selectedYear === '2025-2026' && selectedMonth === 'all' && selectedScope === 'all') ? data.pendingTrx : Math.max(0, Math.round(data.pendingTrx * multiplier));

                // 2. Update KPI Cards
                const kpiTotalOrgs = document.getElementById('kpiTotalOrgsNum');
                const kpiTotalOrgsSub = document.getElementById('kpiTotalOrgsSub');
                if (kpiTotalOrgs) kpiTotalOrgs.textContent = data.totalOrgs;
                if (kpiTotalOrgsSub) kpiTotalOrgsSub.textContent = data.orgSub;

                const kpiPending = document.getElementById('kpiPendingTrxNum');
                const kpiPendingSub = document.getElementById('kpiPendingTrxSub');
                if (kpiPending) kpiPending.textContent = currentPending;
                if (kpiPendingSub) kpiPendingSub.textContent = (selectedMonth !== 'all' || selectedYear !== '2025-2026') ? `${currentPending} pending in selected period` : data.pendingSub;

                const kpiSubs = document.getElementById('kpiTotalSubmissionsNum');
                const kpiSubsSub = document.getElementById('kpiTotalSubmissionsSub');
                if (kpiSubs) kpiSubs.textContent = currentTotalSubs;
                if (kpiSubsSub) kpiSubsSub.textContent = selectedMonth !== 'all' ? `Submissions in ${selectedMonth}` : data.submissionsSub;

                const kpiRev = document.getElementById('kpiRevisionRateNum');
                const kpiRevSub = document.getElementById('kpiRevisionRateSub');
                if (kpiRev) kpiRev.textContent = data.revisionRate;
                if (kpiRevSub) kpiRevSub.textContent = data.revisionSub;

                // 3. Update Approval Donut Chart & Legends
                const donutDataset = [
                    Math.round(data.approvalDonut[0] * multiplier),
                    Math.max(0, Math.round(data.approvalDonut[1] * multiplier)),
                    Math.round(data.approvalDonut[2] * multiplier),
                    Math.max(0, Math.round(data.approvalDonut[3] * multiplier))
                ];
                const donutSum = donutDataset.reduce((a, b) => a + b, 0) || currentTotalSubs;

                if (window.osoCharts.donut) {
                    window.osoCharts.donut.data.datasets[0].data = donutDataset;
                    window.osoCharts.donut.update();
                }

                const donutLabel = document.getElementById('donutTotalCount');
                if (donutLabel) donutLabel.textContent = donutSum;

                const approvedCount = document.getElementById('legendApprovedCount');
                const approvedPct = document.getElementById('legendApprovedPct');
                if (approvedCount) approvedCount.textContent = donutDataset[0];
                if (approvedPct) approvedPct.textContent = donutSum ? `${((donutDataset[0] / donutSum) * 100).toFixed(1)}%` : '0%';

                const pendingCount = document.getElementById('legendPendingCount');
                const pendingPct = document.getElementById('legendPendingPct');
                if (pendingCount) pendingCount.textContent = donutDataset[1];
                if (pendingPct) pendingPct.textContent = donutSum ? `${((donutDataset[1] / donutSum) * 100).toFixed(1)}%` : '0%';

                const revCount = document.getElementById('legendRevisionCount');
                const revPct = document.getElementById('legendRevisionPct');
                if (revCount) revCount.textContent = donutDataset[2];
                if (revPct) revPct.textContent = donutSum ? `${((donutDataset[2] / donutSum) * 100).toFixed(1)}%` : '0%';

                const rejCount = document.getElementById('legendRejectedCount');
                const rejPct = document.getElementById('legendRejectedPct');
                if (rejCount) rejCount.textContent = donutDataset[3];
                if (rejPct) rejPct.textContent = donutSum ? `${((donutDataset[3] / donutSum) * 100).toFixed(1)}%` : '0%';

                // 4. Update Scope Panel
                const inCount = Math.round(data.scope.inCampus * multiplier * (selectedScope === 'off-campus' ? 0 : 1));
                const offCount = Math.round(data.scope.offCampus * multiplier * (selectedScope === 'in-campus' ? 0 : 1));
                const scopeTotal = inCount + offCount || 1;
                const inPct = ((inCount / scopeTotal) * 100).toFixed(1);
                const offPct = ((offCount / scopeTotal) * 100).toFixed(1);

                const scopeHeader = document.getElementById('scopeTotalHeader');
                if (scopeHeader) scopeHeader.textContent = `${scopeTotal} Activities Total`;

                const scopeBar = document.getElementById('scopeInCampusBar');
                if (scopeBar) scopeBar.style.width = `${inPct}%`;

                const scopeInCountEl = document.getElementById('scopeInCampusCount');
                const scopeInPctEl = document.getElementById('scopeInCampusPct');
                if (scopeInCountEl) scopeInCountEl.textContent = inCount;
                if (scopeInPctEl) scopeInPctEl.textContent = `${inPct}% of total`;

                const scopeOffCountEl = document.getElementById('scopeOffCampusCount');
                const scopeOffPctEl = document.getElementById('scopeOffCampusPct');
                if (scopeOffCountEl) scopeOffCountEl.textContent = offCount;
                if (scopeOffPctEl) scopeOffPctEl.textContent = `${offPct}% of total`;

                // 5. Update Trend Line Chart
                if (window.osoCharts.trend) {
                    window.osoCharts.trend.data.labels = data.trend.labels;
                    window.osoCharts.trend.data.datasets[0].data = data.trend.data;

                    // Dynamically calculate and update peak label
                    const maxVal = Math.max(...data.trend.data);
                    const maxIdx = data.trend.data.indexOf(maxVal);
                    const peakMonth = data.trend.labels[maxIdx] || 'Sep';

                    const peakEl = document.getElementById('osoTrendPeakLabel');
                    if (peakEl) {
                        peakEl.innerHTML = `<i class="bi bi-dot" style="font-size: 1.2rem;"></i> Peak: ${peakMonth} (${maxVal})`;
                    }

                    if (window.osoCharts.trend.options.scales.y) {
                        window.osoCharts.trend.options.scales.y.suggestedMax = Math.max(25, Math.ceil(maxVal / 5) * 5);
                        delete window.osoCharts.trend.options.scales.y.max;
                    }
                    window.osoCharts.trend.update();
                }

                // 6. Update Type Bar Chart
                if (window.osoCharts.type) {
                    const adjTypeData = data.typeBreakdown.map(v => Math.round(v * multiplier));
                    window.osoCharts.type.data.datasets[0].data = adjTypeData;
                    const maxTypeVal = Math.max(...adjTypeData);
                    if (window.osoCharts.type.options.scales.y) {
                        window.osoCharts.type.options.scales.y.suggestedMax = Math.max(25, Math.ceil((maxTypeVal * 1.2) / 5) * 5);
                        delete window.osoCharts.type.options.scales.y.max;
                    }
                    window.osoCharts.type.update();
                }

                // 7. Update Organization Ranking Bars
                if (data.orgRanking) {
                    data.orgRanking.forEach((org, idx) => {
                        const rankStat = document.getElementById(`rankOrgStat${idx + 1}`);
                        const rankBar = document.getElementById(`rankOrgBar${idx + 1}`);
                        const adjCount = Math.max(1, Math.round(org.count * multiplier));
                        if (rankStat) {
                            rankStat.innerHTML = `<strong>${adjCount}</strong> submissions &middot; <span style="color: #16a34a;">${org.pass} pass</span>`;
                        }
                        if (rankBar) {
                            rankBar.style.width = `${org.pct}%`;
                        }
                    });
                }

                // 8. Filter Table Rows
                let visibleCount = 0;
                const rows = document.querySelectorAll('.oso-trx-row');
                rows.forEach(row => {
                    const rowYear = row.getAttribute('data-year');
                    const rowSem = row.getAttribute('data-sem');
                    const rowMonth = row.getAttribute('data-month');
                    const rowScope = row.getAttribute('data-scope');

                    const matchYear = (selectedYear === 'all' || rowYear === selectedYear);
                    const matchSem = (selectedSem === 'all' || rowSem === selectedSem);
                    const matchMonth = (selectedMonth === 'all' || rowMonth === selectedMonth);
                    const matchScope = (selectedScope === 'all' || rowScope === selectedScope);

                    if (matchYear && matchSem && matchMonth && matchScope) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const noRowsEl = document.getElementById('osoNoTransactionsRow');
                if (noRowsEl) {
                    noRowsEl.style.display = (visibleCount === 0) ? '' : 'none';
                }

                // 9. Update Approval Card Period Badge
                const approvalPeriodBadge = document.getElementById('osoApprovalPeriodBadge');
                if (approvalPeriodBadge) {
                    let yearText = 'A.Y. 2025–2026';
                    if (selectedYear === '2024-2025') yearText = 'A.Y. 2024–2025';
                    else if (selectedYear === '2023-2024') yearText = 'A.Y. 2023–2024';
                    else if (selectedYear === 'all') yearText = 'All Academic Years';

                    if (selectedMonth !== 'all') {
                        approvalPeriodBadge.textContent = `${yearText} · ${selectedMonth}`;
                    } else if (selectedSem !== 'all') {
                        const semNames = { 'sem1': '1st Sem', 'sem2': '2nd Sem', 'midyear': 'Midyear' };
                        approvalPeriodBadge.textContent = `${yearText} · ${semNames[selectedSem] || selectedSem}`;
                    } else {
                        approvalPeriodBadge.textContent = yearText;
                    }
                }

                // 10. Update Active Filter Status Badge
                const badge = document.getElementById('osoActiveFilterBadge');
                if (badge) {
                    let text = 'Live Insights';
                    if (selectedYear !== '2025-2026' || selectedMonth !== 'all' || selectedSem !== 'all' || selectedScope !== 'all') {
                        const parts = [];
                        if (selectedYear !== '2025-2026') parts.push(selectedYear === 'all' ? 'All Years' : selectedYear);
                        if (selectedSem !== 'all') parts.push(selectedSem.toUpperCase());
                        if (selectedMonth !== 'all') parts.push(selectedMonth);
                        if (selectedScope !== 'all') parts.push(selectedScope === 'in-campus' ? 'In-Campus' : 'Off-Campus');
                        text = `Filtered: ${parts.join(' · ')}`;
                    }
                    badge.innerHTML = `<i class="bi bi-funnel-fill"></i> ${text}`;
                }
            }

            // Reset All Filters
            function resetOsoFilters() {
                const yearSelect = document.getElementById('osoYearSelect');
                const semSelect = document.getElementById('osoSemesterSelect');
                const monthSelect = document.getElementById('osoMonthSelect');
                const scopeSelect = document.getElementById('osoScopeSelect');

                if (yearSelect) yearSelect.value = '2025-2026';
                if (semSelect) semSelect.value = 'all';
                if (monthSelect) monthSelect.value = 'all';
                if (scopeSelect) scopeSelect.value = 'all';

                applyOsoFilters();
            }

            document.addEventListener('DOMContentLoaded', function () {
                Chart.defaults.font.family = "'Instrument Sans', system-ui, -apple-system, sans-serif";
                Chart.defaults.color = '#786f73';

                // 1. Approval Status Donut Chart
                const donutCanvas = document.getElementById('osoApprovalDonutChart');
                if (donutCanvas) {
                    window.osoCharts.donut = new Chart(donutCanvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Approved', 'Pending Review', 'For Revision', 'Rejected'],
                            datasets: [{
                                data: [27, 11, 7, 3],
                                backgroundColor: ['#10b981', '#f59e0b', '#e11d48', '#64748b'],
                                borderWidth: 3,
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
                                    displayColors: true,
                                    callbacks: {
                                        label: function (ctx) {
                                            const val = ctx.raw || 0;
                                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 48;
                                            const pct = ((val / total) * 100).toFixed(1);
                                            return ` ${ctx.label}: ${val} (${pct}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // 2. Submission Trend Line Chart
                const trendCanvas = document.getElementById('osoSubmissionTrendChart');
                if (trendCanvas) {
                    const ctx = trendCanvas.getContext('2d');
                    const grad = ctx.createLinearGradient(0, 0, 0, 220);
                    grad.addColorStop(0, 'rgba(139, 24, 40, 0.22)');
                    grad.addColorStop(1, 'rgba(139, 24, 40, 0.00)');

                    window.osoCharts.trend = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                            datasets: [{
                                label: 'Monthly Submissions',
                                data: [4, 8, 12, 9, 15, 7, 14, 18, 22, 19],
                                borderColor: '#8b1828',
                                borderWidth: 2.5,
                                backgroundColor: grad,
                                fill: true,
                                tension: 0.38,
                                pointBackgroundColor: '#8b1828',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4.5,
                                pointHoverRadius: 6.5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1a1618',
                                    padding: 10,
                                    cornerRadius: 8,
                                    displayColors: false,
                                    callbacks: {
                                        label: (ctx) => ` ${ctx.parsed.y} Submissions Filed`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11, weight: '600' } }
                                },
                                y: {
                                    beginAtZero: true,
                                    suggestedMax: 25,
                                    grid: { color: '#f5eaec', drawBorder: false },
                                    ticks: { stepSize: 5, font: { size: 11 } }
                                }
                            }
                        }
                    });
                }

                // 3. Transactions by Type Bar Chart
                const typeCanvas = document.getElementById('osoTransactionTypeChart');
                if (typeCanvas) {
                    window.osoCharts.type = new Chart(typeCanvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: ['Activity Proposal', 'Renewal', 'Financial (FR)', 'Accomplishment (AR)', 'TOSA Award'],
                            datasets: [{
                                label: 'Transactions',
                                data: [22, 10, 8, 5, 3],
                                backgroundColor: [
                                    '#8b1828',
                                    '#ca8a04',
                                    '#1d4ed8',
                                    '#7e22ce',
                                    '#15803d'
                                ],
                                borderRadius: 8,
                                maxBarThickness: 38
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1a1618',
                                    padding: 10,
                                    cornerRadius: 8,
                                    displayColors: false,
                                    callbacks: {
                                        label: (ctx) => ` ${ctx.parsed.y} Documents Filed`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { 
                                        font: { size: 11, weight: '600' },
                                        callback: function (val, index) {
                                            const labels = ['Proposals', 'Renewal', 'Fin. (FR)', 'Accomp. (AR)', 'TOSA'];
                                            return labels[index] || this.getLabelForValue(val);
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    suggestedMax: 25,
                                    grace: '10%',
                                    grid: { color: '#f5eaec' },
                                    ticks: { stepSize: 5, font: { size: 11 } }
                                }
                            }
                        }
                    });
                }

                // 4. Processing Time Analysis Bar/Line Chart
                const timeCanvas = document.getElementById('osoProcessingTimeChart');
                if (timeCanvas) {
                    window.osoCharts.time = new Chart(timeCanvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: ['Proposals', 'Renewal', 'Financial (FR)', 'Accomp. (AR)', 'TOSA'],
                            datasets: [
                                {
                                    type: 'line',
                                    label: 'Target SLA Benchmark (3.0 Days)',
                                    data: [3.0, 3.0, 3.0, 3.0, 3.0],
                                    borderColor: '#dc2626',
                                    borderWidth: 1.5,
                                    borderDash: [5, 5],
                                    pointRadius: 0,
                                    fill: false
                                },
                                {
                                    type: 'bar',
                                    label: 'Avg Turnaround (Days)',
                                    data: [1.8, 3.2, 2.5, 1.9, 2.1],
                                    backgroundColor: [
                                        '#10b981',
                                        '#f59e0b',
                                        '#10b981',
                                        '#10b981',
                                        '#10b981'
                                    ],
                                    borderRadius: 8,
                                    maxBarThickness: 34
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { 
                                    display: true, 
                                    position: 'top', 
                                    align: 'end',
                                    labels: { boxWidth: 12, boxHeight: 12, font: { size: 11, weight: '600' } } 
                                },
                                tooltip: {
                                    backgroundColor: '#1a1618',
                                    padding: 10,
                                    cornerRadius: 8
                                }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11, weight: '600' } }
                                },
                                y: {
                                    beginAtZero: true,
                                    max: 4.0,
                                    grid: { color: '#f5eaec' },
                                    ticks: {
                                        stepSize: 1,
                                        callback: (val) => `${val}d`
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endif
@endsection
