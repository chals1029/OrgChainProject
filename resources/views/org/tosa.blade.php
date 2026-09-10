@extends('org.layout')

@section('title', 'TOSA Module · Restricted Office Access')

@section('header')
    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
        <h1><strong>TOSA Module</strong></h1>
        <span id="tosaHeaderBadge" class="tosa-badge-locked">
            <i class="bi bi-shield-lock-fill"></i> Restricted Access
        </span>
    </div>
    <p class="org-welcome">Ten Outstanding Students Awards (TOSA) executive review desk, applicant dossier verification, and requirements governance.</p>
@endsection

@section('actions')
    <div id="tosaUnlockedActions" style="display: none; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
        <span class="tosa-session-chip">
            <i class="bi bi-clock-history"></i> <span id="tosaTimerText">Auto-lock in 15:00</span>
        </span>
        <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="lockTosaSession()" title="Lock TOSA Module">
            <i class="bi bi-lock-fill"></i> Lock Session
        </button>
    </div>
@endsection

@section('content')
    <style>
        /* ==========================================================================
           TOSA Module — Impeccable & Unslop Design System
           ========================================================================== */
        :root {
            --tosa-maroon: #8b1828;
            --tosa-maroon-dark: #62101c;
            --tosa-maroon-light: #fdf0f2;
            --tosa-maroon-border: #f2dfe2;
            --tosa-ink-dark: #1a1618;
            --tosa-ink-body: #3f3538;
            --tosa-ink-muted: #7a7074;
            --tosa-border: #f0e6e8;
            --tosa-border-subtle: #f9f2f4;
            --tosa-radius-lg: 20px;
            --tosa-radius-md: 14px;
            --tosa-radius-sm: 10px;
            --tosa-shadow-sm: 0 4px 16px rgba(90, 15, 30, 0.03);
            --tosa-shadow-md: 0 8px 24px rgba(90, 15, 30, 0.06);
            --tosa-shadow-hover: 0 12px 32px rgba(90, 15, 30, 0.08);
        }

        .tosa-badge-locked {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .tosa-badge-unlocked {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .tosa-session-chip {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--tosa-ink-muted);
            background: #ffffff;
            border: 1.5px solid var(--tosa-border);
            padding: 0.4rem 0.75rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        /* --------------------------------------------------------------------------
           1. PIN Security Gateway Card
           -------------------------------------------------------------------------- */
        .tosa-pin-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 520px;
            padding: 2rem 1rem;
        }

        .tosa-pin-card {
            background: #ffffff;
            border: 1.5px solid var(--tosa-border);
            border-radius: 24px;
            padding: 2.75rem 2.25rem;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(139, 24, 40, 0.08), 0 4px 16px rgba(0, 0, 0, 0.03);
            animation: tosaScaleUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            box-sizing: border-box;
        }

        @keyframes tosaScaleUp {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .tosa-pin-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, #c43b52, #6f1020);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto 1.25rem;
            box-shadow: 0 8px 24px rgba(139, 24, 40, 0.25);
        }

        .tosa-pin-card h2 {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--tosa-ink-dark);
            margin: 0 0 0.4rem;
            letter-spacing: -0.02em;
        }

        .tosa-pin-card p {
            font-size: 0.88rem;
            color: var(--tosa-ink-muted);
            margin: 0 0 1.75rem;
            line-height: 1.5;
        }

        .tosa-pin-boxes {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .tosa-pin-input {
            width: 54px;
            height: 60px;
            border-radius: 14px;
            border: 2px solid var(--tosa-border);
            background: #ffffff;
            font-size: 1.75rem;
            font-weight: 800;
            text-align: center;
            color: var(--tosa-ink-dark);
            outline: none;
            transition: all 0.15s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            font-family: inherit;
        }

        .tosa-pin-input:focus {
            border-color: var(--tosa-maroon);
            background: var(--tosa-maroon-light);
            box-shadow: 0 0 0 4px rgba(139, 24, 40, 0.08);
            transform: translateY(-2px);
        }

        .tosa-pin-btn {
            width: 100%;
            padding: 0.85rem 1.5rem;
            background: var(--tosa-maroon);
            color: #ffffff;
            border: none;
            border-radius: 9999px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(139, 24, 40, 0.28);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: inherit;
        }

        .tosa-pin-btn:hover {
            background: var(--tosa-maroon-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(139, 24, 40, 0.35);
        }

        .tosa-pin-footer-links {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.35rem;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .tosa-pin-footer-links a,
        .tosa-pin-footer-links button {
            color: var(--tosa-ink-muted);
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: inherit;
            font-weight: inherit;
            padding: 0;
            transition: color 0.15s ease;
        }

        .tosa-pin-footer-links a:hover,
        .tosa-pin-footer-links button:hover {
            color: var(--tosa-maroon);
        }

        .tosa-pin-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        /* --------------------------------------------------------------------------
           2. Unlocked TOSA Workspace & Navigation Tabs
           -------------------------------------------------------------------------- */
        .tosa-workspace {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .tosa-tabs-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #ffffff;
            border: 1.5px solid var(--tosa-border);
            border-radius: var(--tosa-radius-lg);
            padding: 0.5rem;
            box-shadow: var(--tosa-shadow-sm);
            overflow-x: auto;
            scrollbar-width: none;
        }

        .tosa-tabs-nav::-webkit-scrollbar {
            display: none;
        }

        .tosa-tab-btn {
            background: transparent;
            border: none;
            padding: 0.65rem 1.15rem;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--tosa-ink-muted);
            cursor: pointer;
            transition: all 0.18s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-family: inherit;
        }

        .tosa-tab-btn:hover {
            color: var(--tosa-ink-dark);
            background: #fdfafb;
        }

        .tosa-tab-btn.is-active {
            background: var(--tosa-maroon);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(139, 24, 40, 0.25);
        }

        .tosa-tab-btn .tosa-tab-count {
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 800;
            background: rgba(0, 0, 0, 0.08);
            color: inherit;
        }

        .tosa-tab-btn.is-active .tosa-tab-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* 3. KPI Summary Row */
        .tosa-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
        }

        .tosa-kpi-card {
            background: #ffffff;
            border: 1.5px solid var(--tosa-border);
            border-radius: var(--tosa-radius-lg);
            padding: 1.25rem 1.4rem;
            box-shadow: var(--tosa-shadow-sm);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .tosa-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--tosa-shadow-md);
        }

        .tosa-kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .tosa-kpi-icon.is-amber { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .tosa-kpi-icon.is-blue { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .tosa-kpi-icon.is-green { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .tosa-kpi-icon.is-maroon { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

        .tosa-kpi-num {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--tosa-ink-dark);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .tosa-kpi-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--tosa-ink-muted);
        }

        /* --------------------------------------------------------------------------
           TOSA Requirements Screen — Impeccable Design System (Matching Mockup)
           -------------------------------------------------------------------------- */
        .tosa-req-header-wrap {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .tosa-req-breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--tosa-ink-muted);
            margin-bottom: 0.35rem;
        }

        .tosa-req-breadcrumb .active {
            color: var(--tosa-ink-dark);
            font-weight: 800;
        }

        .tosa-req-subtitle {
            font-size: 0.88rem;
            color: var(--tosa-ink-muted);
            margin: 0;
            font-weight: 500;
        }

        .tosa-btn-add-req {
            background: #8b1828;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 0.65rem 1.35rem;
            font-size: 0.88rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 14px rgba(139, 24, 40, 0.25);
            font-family: inherit;
        }

        .tosa-btn-add-req:hover {
            background: #6f1020;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(139, 24, 40, 0.35);
        }

        /* 4 KPI Cards Grid */
        .tosa-req-kpis-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .tosa-req-kpi-box {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1.15rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .tosa-req-kpi-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 24, 40, 0.05);
        }

        .tosa-req-kpi-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .tosa-req-kpi-icon.is-blue { background: #e0f2fe; color: #0284c7; }
        .tosa-req-kpi-icon.is-green { background: #dcfce7; color: #16a34a; }
        .tosa-req-kpi-icon.is-amber { background: #fef3c7; color: #d97706; }
        .tosa-req-kpi-icon.is-purple { background: #f3e8ff; color: #9333ea; }

        .tosa-req-kpi-num {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .tosa-req-kpi-text {
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
        }

        /* Requirements Split Layout */
        .tosa-req-main-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.5rem;
            align-items: start;
        }

        /* Left Main Requirements Card */
        .tosa-req-card {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.02);
        }

        .tosa-req-table-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #8b1828;
            margin: 0 0 1.25rem;
            letter-spacing: -0.01em;
        }

        .tosa-req-toolbar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .tosa-req-search-box {
            position: relative;
            flex: 1;
            min-width: 220px;
        }

        .tosa-req-search-input {
            width: 100%;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 2.25rem 0.6rem 1rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: #1a1618;
            outline: none;
            transition: all 0.15s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .tosa-req-search-input:focus {
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.08);
        }

        .tosa-req-search-box .search-icon {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.9rem;
        }

        .tosa-req-select {
            background: #ffffff url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") no-repeat right 0.85rem center/12px auto;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 2.25rem 0.6rem 1rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            font-family: inherit;
            transition: all 0.15s ease;
        }

        .tosa-req-select:focus {
            border-color: #8b1828;
        }

        .tosa-btn-bulk {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 1rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: #8b1828;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .tosa-btn-bulk:hover {
            border-color: #8b1828;
            background: #fdf0f2;
        }

        /* Requirements Table */
        .tosa-req-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .tosa-req-table th {
            padding: 0.85rem 0.95rem;
            font-size: 0.76rem;
            font-weight: 800;
            color: #475569;
            border-bottom: 1.5px solid #ede8ea;
            text-align: left;
            white-space: nowrap;
        }

        .tosa-req-table td {
            padding: 1.05rem 0.95rem;
            border-bottom: 1px solid #f1ecee;
            vertical-align: middle;
            color: #334155;
        }

        .tosa-req-table tbody tr:last-child td {
            border-bottom: none;
        }

        .tosa-req-table tbody tr:hover {
            background: #fdfafb;
        }

        .tosa-req-item-cell {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .tosa-req-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .tosa-req-icon-badge.is-blue { background: #e0f2fe; color: #0284c7; }
        .tosa-req-icon-badge.is-amber { background: #fef3c7; color: #d97706; }
        .tosa-req-icon-badge.is-green { background: #dcfce7; color: #16a34a; }
        .tosa-req-icon-badge.is-purple { background: #f3e8ff; color: #9333ea; }
        .tosa-req-icon-badge.is-orange { background: #ffedd5; color: #ea580c; }
        .tosa-req-icon-badge.is-rose { background: #ffe4e6; color: #e11d48; }
        .tosa-req-icon-badge.is-teal { background: #ccfbf1; color: #0d9488; }

        .tosa-req-item-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.15rem;
            line-height: 1.25;
        }

        .tosa-req-item-desc {
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.35;
        }

        /* Badges */
        .tosa-type-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.28rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.74rem;
            font-weight: 700;
            white-space: nowrap;
            line-height: 1;
        }

        .tosa-type-badge.is-form { background: #e0f2fe; color: #0284c7; }
        .tosa-type-badge.is-doc { background: #ffedd5; color: #c2410c; }

        .tosa-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.28rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.74rem;
            font-weight: 700;
            white-space: nowrap;
            line-height: 1;
        }

        .tosa-status-badge.is-active { background: #dcfce7; color: #15803d; }
        .tosa-status-badge.is-inactive { background: #fef3c7; color: #b45309; }

        /* Switch Toggle */
        .tosa-switch {
            position: relative;
            display: inline-block;
            width: 38px;
            height: 22px;
        }

        .tosa-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .tosa-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .2s;
            border-radius: 9999px;
        }

        .tosa-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .2s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }

        input:checked + .tosa-slider {
            background-color: #22c55e;
        }

        input:checked + .tosa-slider:before {
            transform: translateX(16px);
        }

        /* Action Icon Buttons */
        .tosa-row-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            margin: 0 auto;
        }

        .tosa-action-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.84rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .tosa-action-btn:hover {
            background: #f8fafc;
            color: #1e293b;
            border-color: #cbd5e1;
        }

        .tosa-action-btn.is-delete {
            color: #dc2626;
        }

        .tosa-action-btn.is-delete:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        .tosa-action-btn.is-drag {
            cursor: grab;
            color: #94a3b8;
        }

        /* Right Sidebar Panels */
        .tosa-req-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .tosa-side-panel {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 16px;
            padding: 1.35rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .tosa-side-panel-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0 0 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tosa-side-panel-title.has-gear {
            color: #8b1828;
        }

        .tosa-side-field {
            margin-bottom: 1rem;
        }

        .tosa-side-field:last-child {
            margin-bottom: 0;
        }

        .tosa-side-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 0.35rem;
        }

        .tosa-side-select {
            width: 100%;
            background: #ffffff url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") no-repeat right 0.85rem center/12px auto;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.55rem 2.25rem 0.55rem 0.85rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: #1e293b;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            font-family: inherit;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }

        .tosa-side-select:focus {
            border-color: #8b1828;
        }

        .tosa-side-instructions-list {
            padding-left: 1.15rem;
            margin: 0;
            font-size: 0.82rem;
            color: #475569;
            line-height: 1.55;
        }

        .tosa-side-instructions-list li {
            margin-bottom: 0.55rem;
        }

        .tosa-side-instructions-list li:last-child {
            margin-bottom: 0;
        }

        .tosa-legend-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            font-size: 0.82rem;
        }

        .tosa-legend-item {
            display: grid;
            grid-template-columns: 85px 1fr;
            align-items: center;
            gap: 0.5rem;
        }

        .tosa-legend-desc {
            color: #64748b;
            font-size: 0.8rem;
        }

        /* ==========================================================================
           TOSA Application Submissions — Exact Mockup Match UI
           ========================================================================== */
        .tosa-sub-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .tosa-sub-stat-card {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1.15rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .tosa-sub-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 24, 40, 0.05);
        }

        .tosa-sub-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .tosa-sub-stat-icon.is-blue { background: #e0f2fe; color: #0284c7; }
        .tosa-sub-stat-icon.is-green { background: #dcfce7; color: #16a34a; }
        .tosa-sub-stat-icon.is-red { background: #fee2e2; color: #dc2626; }
        .tosa-sub-stat-icon.is-amber { background: #fef3c7; color: #d97706; }

        .tosa-sub-stat-num {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .tosa-sub-stat-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
        }

        .tosa-sub-table-card {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.02);
            margin-bottom: 1.5rem;
        }

        .tosa-sub-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .tosa-sub-toolbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            flex-wrap: wrap;
        }

        .tosa-sub-search-box {
            position: relative;
            min-width: 250px;
            flex: 1;
            max-width: 320px;
        }

        .tosa-sub-search-box input {
            width: 100%;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 2.25rem 0.6rem 1rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: #1a1618;
            outline: none;
            transition: all 0.15s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .tosa-sub-search-box input:focus {
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.08);
        }

        .tosa-sub-search-box .search-icon {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.9rem;
        }

        .tosa-sub-select {
            background: #ffffff url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") no-repeat right 0.85rem center/12px auto;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 2.25rem 0.6rem 1rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            font-family: inherit;
            transition: all 0.15s ease;
        }

        .tosa-sub-select:focus {
            border-color: #8b1828;
        }

        .tosa-btn-export-report {
            background: #ffffff;
            border: 1.5px solid #8b1828;
            border-radius: 10px;
            padding: 0.6rem 1.25rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: #8b1828;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
            white-space: nowrap;
        }

        .tosa-btn-export-report:hover {
            background: #fdf0f2;
            transform: translateY(-1px);
        }

        /* Submissions Table */
        .tosa-submissions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .tosa-submissions-table th {
            padding: 0.95rem 0.85rem;
            font-size: 0.76rem;
            font-weight: 800;
            color: #1e293b;
            border-bottom: 1.5px solid #ede8ea;
            text-align: left;
            white-space: nowrap;
            background: #fafbfc;
        }

        .tosa-submissions-table th:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .tosa-submissions-table th:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .tosa-submissions-table td {
            padding: 1.1rem 0.85rem;
            border-bottom: 1px solid #f1ecee;
            vertical-align: middle;
            color: #334155;
        }

        .tosa-submissions-table tbody tr:last-child td {
            border-bottom: none;
        }

        .tosa-submissions-table tbody tr:hover {
            background: #fdfafb;
        }

        .tosa-applicant-cell {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .tosa-sub-avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            border: 1px solid #e2e8f0;
        }

        .tosa-sub-app-name {
            font-size: 0.88rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.15rem;
        }

        .tosa-sub-app-id {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
        }

        .tosa-prog-name {
            font-size: 0.86rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.15rem;
        }

        .tosa-prog-year {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
        }

        .tosa-sub-progress-meta {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .tosa-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.85rem;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
            line-height: 1;
        }

        .tosa-status-pill.is-complete { background: #dcfce7; color: #15803d; }
        .tosa-status-pill.is-missing { background: #fee2e2; color: #b91c1c; }
        .tosa-status-pill.is-review { background: #e0f2fe; color: #0369a1; }
        .tosa-status-pill.is-approved { background: #dcfce7; color: #15803d; }
        .tosa-status-pill.is-returned { background: #ffedd5; color: #c2410c; }
        .tosa-status-pill.is-rejected { background: #fee2e2; color: #dc2626; }

        /* Action Buttons */
        .tosa-sub-actions-cell {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }

        .tosa-sub-btn {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            width: 74px;
            min-width: 74px;
            max-width: 74px;
            height: 52px;
            min-height: 52px;
            max-height: 52px;
            box-sizing: border-box;
            padding: 0.35rem 0.25rem;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
            color: #334155;
            font-family: inherit;
        }

        .tosa-sub-btn:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .tosa-sub-btn i {
            font-size: 1.05rem;
            line-height: 1;
            display: block;
        }

        .tosa-sub-btn span {
            font-size: 0.68rem;
            font-weight: 700;
            line-height: 1.15;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 24px;
            width: 100%;
        }

        .tosa-sub-btn.is-view i { color: #334155; }
        .tosa-sub-btn.is-download i { color: #334155; }
        .tosa-sub-btn.is-return i { color: #ea580c; }
        .tosa-sub-btn.is-reject i { color: #dc2626; }
        .tosa-sub-btn.is-approve i { color: #16a34a; }

        .tosa-sub-btn.is-approve:hover {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .tosa-sub-btn.is-reject:hover {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .tosa-sub-btn.is-return:hover {
            border-color: #fed7aa;
            background: #fff7ed;
        }

        /* Bottom Legend and Info Notice */
        .tosa-sub-bottom-wrap {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1.5rem;
            align-items: center;
            margin-top: 1.5rem;
        }

        .tosa-sub-legend-row {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e293b;
            flex-wrap: wrap;
        }

        .tosa-sub-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .tosa-sub-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .tosa-sub-dot.is-green { background: #16a34a; }
        .tosa-sub-dot.is-red { background: #dc2626; }
        .tosa-sub-dot.is-blue { background: #0284c7; }
        .tosa-sub-dot.is-orange { background: #ea580c; }

        .tosa-sub-info-notice {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.85rem 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.82rem;
            color: #475569;
            line-height: 1.45;
        }

        .tosa-sub-info-notice i {
            color: #0284c7;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        @media (max-width: 1080px) {
            .tosa-sub-stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .tosa-sub-bottom-wrap {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .tosa-sub-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ==========================================================================
           TOSA Review Queue — Impeccable & Unslop Design System
           ========================================================================== */
        .tosa-queue-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .tosa-queue-stat-card {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1.15rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .tosa-queue-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.08);
        }

        .tosa-queue-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .tosa-queue-stat-icon.is-amber { background: #fef3c7; color: #d97706; }
        .tosa-queue-stat-icon.is-red { background: #fee2e2; color: #dc2626; }
        .tosa-queue-stat-icon.is-orange { background: #ffedd5; color: #ea580c; }
        .tosa-queue-stat-icon.is-blue { background: #e0f2fe; color: #0284c7; }

        .tosa-queue-stat-num {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .tosa-queue-stat-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
        }

        .tosa-queue-table-card {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.02);
            margin-bottom: 1.5rem;
        }

        .tosa-queue-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .tosa-queue-toolbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            flex-wrap: wrap;
        }

        .tosa-queue-search-box {
            position: relative;
            min-width: 250px;
            flex: 1;
            max-width: 320px;
        }

        .tosa-queue-search-box input {
            width: 100%;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 2.25rem 0.6rem 1rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: #1a1618;
            outline: none;
            transition: all 0.15s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .tosa-queue-search-box input:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1);
        }

        .tosa-queue-search-box .search-icon {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.9rem;
        }

        .tosa-queue-select {
            background: #ffffff url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") no-repeat right 0.85rem center/12px auto;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 2.25rem 0.6rem 1rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            font-family: inherit;
            transition: all 0.15s ease;
        }

        .tosa-queue-select:focus {
            border-color: #d97706;
        }

        .tosa-queue-pills-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }

        .tosa-queue-filter-pill {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 9999px;
            padding: 0.35rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-family: inherit;
        }

        .tosa-queue-filter-pill:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
            color: #1e293b;
        }

        .tosa-queue-filter-pill.is-active {
            background: #8b1828;
            border-color: #8b1828;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(139, 24, 40, 0.2);
        }

        /* Queue Table */
        .tosa-queue-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .tosa-queue-table th {
            padding: 0.95rem 0.85rem;
            font-size: 0.76rem;
            font-weight: 800;
            color: #1e293b;
            border-bottom: 1.5px solid #ede8ea;
            text-align: left;
            white-space: nowrap;
            background: #fafbfc;
        }

        .tosa-queue-table th:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .tosa-queue-table th:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .tosa-queue-table td {
            padding: 1.1rem 0.85rem;
            border-bottom: 1px solid #f1ecee;
            vertical-align: middle;
            color: #334155;
        }

        .tosa-queue-table tbody tr:last-child td {
            border-bottom: none;
        }

        .tosa-queue-table tbody tr:hover {
            background: #fdfafb;
        }

        .tosa-queue-priority-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.55rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .tosa-queue-priority-badge.is-urgent { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .tosa-queue-priority-badge.is-normal { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .tosa-queue-priority-badge.is-low { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }

        .tosa-queue-flag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .tosa-queue-flag.is-missing { background: #fee2e2; color: #dc2626; }
        .tosa-queue-flag.is-returned { background: #ffedd5; color: #ea580c; }
        .tosa-queue-flag.is-review { background: #fef3c7; color: #b45309; }

        .tosa-queue-btn-action {
            background: #8b1828;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 0.95rem;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
            box-shadow: 0 2px 6px rgba(139, 24, 40, 0.2);
        }

        .tosa-queue-btn-action:hover {
            background: #6f1020;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(139, 24, 40, 0.3);
        }

        @media (max-width: 1080px) {
            .tosa-queue-stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .tosa-queue-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ==========================================================================
           TOSA Activity Log (Tab 5) — Impeccable & Unslop Design System
           ========================================================================== */
        .tosa-log-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .tosa-log-stat-card {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1.15rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .tosa-log-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 24, 40, 0.08);
        }

        .tosa-log-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .tosa-log-stat-icon.is-maroon { background: #fdf0f2; color: #8b1828; }
        .tosa-log-stat-icon.is-green { background: #dcfce7; color: #16a34a; }
        .tosa-log-stat-icon.is-amber { background: #fef3c7; color: #d97706; }
        .tosa-log-stat-icon.is-blue { background: #e0f2fe; color: #0284c7; }

        .tosa-log-stat-num {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .tosa-log-stat-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
        }

        .tosa-log-table-card {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.02);
            margin-bottom: 1.5rem;
        }

        .tosa-log-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .tosa-log-toolbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            flex-wrap: wrap;
        }

        .tosa-log-search-box {
            position: relative;
            min-width: 260px;
            flex: 1;
            max-width: 340px;
        }

        .tosa-log-search-box input {
            width: 100%;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 2.25rem 0.6rem 1rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: #1a1618;
            outline: none;
            transition: all 0.15s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .tosa-log-search-box input:focus {
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.08);
        }

        .tosa-log-search-box .search-icon {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.9rem;
        }

        .tosa-log-select {
            background: #ffffff url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") no-repeat right 0.85rem center/12px auto;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 2.25rem 0.6rem 1rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            font-family: inherit;
            transition: all 0.15s ease;
        }

        .tosa-log-select:focus {
            border-color: #8b1828;
        }

        .tosa-log-pills-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }

        .tosa-log-filter-pill {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 9999px;
            padding: 0.35rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-family: inherit;
        }

        .tosa-log-filter-pill:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
            color: #1e293b;
        }

        .tosa-log-filter-pill.is-active {
            background: #8b1828;
            border-color: #8b1828;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(139, 24, 40, 0.2);
        }

        .tosa-log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .tosa-log-table th {
            padding: 0.95rem 0.85rem;
            font-size: 0.76rem;
            font-weight: 800;
            color: #1e293b;
            border-bottom: 1.5px solid #ede8ea;
            text-align: left;
            white-space: nowrap;
            background: #fafbfc;
        }

        .tosa-log-table th:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .tosa-log-table th:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .tosa-log-table td {
            padding: 1.05rem 0.85rem;
            border-bottom: 1px solid #f1ecee;
            vertical-align: middle;
            color: #334155;
        }

        .tosa-log-table tbody tr:last-child td {
            border-bottom: none;
        }

        .tosa-log-table tbody tr:hover {
            background: #fdfafb;
        }

        .tosa-log-actor-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .tosa-log-actor-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
            font-weight: 800;
        }

        .tosa-log-actor-avatar.is-user {
            background: #fdf0f2;
            color: #8b1828;
            border: 1.5px solid #f2dfe2;
        }

        .tosa-log-actor-avatar.is-system {
            background: #f0fdf4;
            color: #16a34a;
            border: 1.5px solid #bbf7d0;
        }

        .tosa-log-actor-avatar.is-sec {
            background: #eff6ff;
            color: #2563eb;
            border: 1.5px solid #bfdbfe;
        }

        .tosa-log-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .tosa-log-cat-pill.is-approval { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .tosa-log-cat-pill.is-revision { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
        .tosa-log-cat-pill.is-req { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .tosa-log-cat-pill.is-security { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .tosa-log-cat-pill.is-system { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .tosa-log-hash-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.76rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.25rem 0.55rem;
            border-radius: 6px;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .tosa-log-hash-badge:hover {
            border-color: #8b1828;
            color: #8b1828;
            background: #fdf0f2;
        }

        .tosa-log-verified-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.72rem;
            font-weight: 800;
            color: #16a34a;
            background: #f0fdf4;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 1080px) {
            .tosa-log-stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .tosa-log-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ==========================================================================
           TOSA Overview (Tab 1) — Harmonized Executive Dashboard
           ========================================================================== */
        .tosa-ov-main-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 1.5rem;
        }

        .tosa-ov-card {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 18px;
            padding: 1.4rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            margin-bottom: 1.5rem;
        }

        .tosa-ov-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f1ecee;
        }

        .tosa-ov-card-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tosa-ov-link-btn {
            background: none;
            border: none;
            color: #8b1828;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-family: inherit;
        }

        .tosa-ov-link-btn:hover {
            text-decoration: underline;
        }

        .tosa-ov-prog-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            margin-bottom: 0.75rem;
        }

        .tosa-ov-prog-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #334155;
            min-width: 170px;
        }

        .tosa-ov-prog-bar {
            flex: 1;
            height: 8px;
            border-radius: 9999px;
            background: #f1f5f9;
            overflow: hidden;
            position: relative;
        }

        .tosa-ov-prog-fill {
            height: 100%;
            border-radius: 9999px;
            background: #8b1828;
            transition: width 0.4s ease;
        }

        .tosa-ov-prog-val {
            font-size: 0.8rem;
            font-weight: 800;
            color: #1e293b;
            min-width: 45px;
            text-align: right;
        }

        .tosa-ov-triage-item {
            background: #fdfafb;
            border: 1.5px solid #ede8ea;
            border-radius: 12px;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.65rem;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .tosa-ov-triage-item:hover {
            border-color: #8b1828;
            background: #fff;
        }

        .tosa-ov-feed-item {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8fafc;
        }

        .tosa-ov-feed-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .tosa-ov-feed-dot {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .tosa-ov-feed-content {
            flex: 1;
            min-width: 0;
        }

        @media (max-width: 1080px) {
            .tosa-ov-main-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ==========================================================================
           TOSA Modal Dialogs (Centered, Glassmorphic Backdrop, Impeccable)
           ========================================================================== */
        .tosa-modal {
            border: none;
            border-radius: 24px;
            padding: 0;
            background: transparent;
            max-width: 900px;
            width: 95%;
            margin: auto;
            position: fixed;
            inset: 0;
            outline: none;
            box-shadow: none;
            overflow: visible;
            z-index: 10000;
        }

        .tosa-modal[open] {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tosa-modal::backdrop {
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        .tosa-modal-box {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #ede8ea;
            padding: 1.85rem;
            box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.25), 0 0 1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-sizing: border-box;
            animation: tosaModalScaleUp 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        @keyframes tosaModalScaleUp {
            from { opacity: 0; transform: scale(0.96) translateY(8px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .tosa-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 1rem;
            border-bottom: 1.5px solid #ede8ea;
            margin-bottom: 1.25rem;
        }

        .tosa-modal-close-btn {
            background: none;
            border: none;
            font-size: 1.4rem;
            line-height: 1;
            color: #64748b;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 8px;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tosa-modal-close-btn:hover {
            color: #8b1828;
            background: #fdf0f2;
        }

        .tosa-review-grid {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 1.5rem;
        }

        .tosa-review-col-left {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .tosa-review-col-right {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            background: #fdfafb;
            border: 1.5px solid #ede8ea;
            border-radius: 18px;
            padding: 1.35rem;
        }

        .tosa-dossier-card {
            background: #fdfafb;
            border: 1.5px solid #ede8ea;
            border-radius: 16px;
            padding: 1.2rem;
        }

        .tosa-doc-item {
            background: #ffffff;
            border: 1.5px solid #ede8ea;
            border-radius: 12px;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.65rem;
            transition: border-color 0.15s ease;
        }

        .tosa-doc-item:hover {
            border-color: #8b1828;
        }

        .tosa-checklist-label {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            font-size: 0.86rem;
            font-weight: 700;
            color: #1a1618;
            cursor: pointer;
            padding: 0.5rem 0.65rem;
            border-radius: 8px;
            transition: background 0.15s ease;
        }

        .tosa-checklist-label:hover {
            background: #f2dfe2;
        }

        .tosa-checklist-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #8b1828;
            margin-top: 0.1rem;
            cursor: pointer;
        }

        .tosa-remarks-textarea {
            width: 100%;
            border: 1.5px solid #ede8ea;
            border-radius: 12px;
            padding: 0.75rem 0.95rem;
            font-size: 0.86rem;
            color: #1a1618;
            background: #ffffff;
            outline: none;
            box-sizing: border-box;
            resize: vertical;
            min-height: 90px;
            font-family: inherit;
            transition: border-color 0.15s ease;
        }

        .tosa-remarks-textarea:focus {
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        .tosa-decision-actions {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.65rem;
            margin-top: 0.5rem;
        }

        .tosa-btn-decision {
            padding: 0.75rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.84rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border: none;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .tosa-btn-decision.is-approve {
            background: #16a34a;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        }

        .tosa-btn-decision.is-approve:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        .tosa-btn-decision.is-revision {
            background: #d97706;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
        }

        .tosa-btn-decision.is-revision:hover {
            background: #b45309;
            transform: translateY(-1px);
        }

        .tosa-btn-decision.is-reject {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .tosa-btn-decision.is-reject:hover {
            background: #dc2626;
            color: #ffffff;
            transform: translateY(-1px);
        }

        @media (max-width: 1080px) {
            .tosa-req-main-layout {
                grid-template-columns: 1fr;
            }
            .tosa-req-kpis-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .tosa-review-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .tosa-req-kpis-grid {
                grid-template-columns: 1fr;
            }
            .tosa-decision-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    {{-- =========================================================================
         STATE A: PIN Security Gateway Screen
         ========================================================================= --}}
    <div id="tosaPinScreen" class="tosa-pin-container">
        <section class="tosa-pin-card">
            <div class="tosa-pin-icon-wrap">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <h2>TOSA Security Gateway</h2>
            <p>Enter your authorized 4-digit OSO security PIN to unlock applicant dossiers and requirements management.</p>

            <div id="tosaPinAlert" class="tosa-pin-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span id="tosaPinAlertText">Invalid Security PIN. Please try again.</span>
            </div>

            <form id="tosaPinForm" onsubmit="handlePinSubmit(event)">
                <div class="tosa-pin-boxes">
                    <input type="password" maxlength="1" class="tosa-pin-input" id="pinBox1" inputmode="numeric" pattern="[0-9]*" autofocus oninput="onPinDigitInput(this, 1)" onkeydown="onPinKeyDown(event, 1)">
                    <input type="password" maxlength="1" class="tosa-pin-input" id="pinBox2" inputmode="numeric" pattern="[0-9]*" oninput="onPinDigitInput(this, 2)" onkeydown="onPinKeyDown(event, 2)">
                    <input type="password" maxlength="1" class="tosa-pin-input" id="pinBox3" inputmode="numeric" pattern="[0-9]*" oninput="onPinDigitInput(this, 3)" onkeydown="onPinKeyDown(event, 3)">
                    <input type="password" maxlength="1" class="tosa-pin-input" id="pinBox4" inputmode="numeric" pattern="[0-9]*" oninput="onPinDigitInput(this, 4)" onkeydown="onPinKeyDown(event, 4)">
                </div>

                <button type="submit" class="tosa-pin-btn" id="tosaUnlockBtn">
                    <i class="bi bi-key-fill"></i> Unlock TOSA Module
                </button>
            </form>

            <div class="tosa-pin-footer-links">
                <button type="button" onclick="openForgotPinModal()">Forgot PIN?</button>
                <span style="color: #cbd5e1;">•</span>
                <span style="color: var(--tosa-ink-muted); font-size: 0.78rem;">Demo PIN: <strong>1234</strong></span>
            </div>
        </section>
    </div>

    {{-- =========================================================================
         STATE B: Unlocked TOSA Workspace
         ========================================================================= --}}
    <div id="tosaWorkspace" class="tosa-workspace" style="display: none;">
        
        {{-- Navigation Tabs --}}
        <nav class="tosa-tabs-nav" role="tablist" aria-label="TOSA Modules">
            <button type="button" class="tosa-tab-btn is-active" id="tabBtnOverview" onclick="switchTosaTab('overview')">
                <i class="bi bi-grid-1x2-fill"></i> Overview
            </button>
            <button type="button" class="tosa-tab-btn" id="tabBtnRequirements" onclick="switchTosaTab('requirements')">
                <i class="bi bi-card-checklist"></i> Requirements <span class="tosa-tab-count" id="badgeReqCount">5</span>
            </button>
            <button type="button" class="tosa-tab-btn" id="tabBtnApplicants" onclick="switchTosaTab('applicants')">
                <i class="bi bi-people-fill"></i> Applicant Submissions <span class="tosa-tab-count" id="badgeAppCount">74</span>
            </button>
            <button type="button" class="tosa-tab-btn" id="tabBtnQueue" onclick="switchTosaTab('queue')">
                <i class="bi bi-hourglass-split"></i> Review Queue <span class="tosa-tab-count" id="badgeQueueCount">32</span>
            </button>
            <button type="button" class="tosa-tab-btn" id="tabBtnLog" onclick="switchTosaTab('log')">
                <i class="bi bi-journal-text"></i> Activity Log
            </button>
        </nav>

        {{-- TAB 1: OVERVIEW (HARMONIZED EXECUTIVE DASHBOARD) --}}
        <div id="tosaTabSectionOverview" class="tosa-tab-pane">
            
            {{-- Master Top KPI Cards Row --}}
            <div class="tosa-kpi-row" style="margin-bottom: 1.5rem;">
                <div class="tosa-kpi-card" onclick="switchTosaTab('applicants')" style="cursor: pointer;" title="View all candidate submissions">
                    <div class="tosa-kpi-icon is-blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="tosa-kpi-num" id="ovKpiTotalApplicants">52</div>
                        <div class="tosa-kpi-label">Total Candidates</div>
                    </div>
                </div>

                <div class="tosa-kpi-card" onclick="switchTosaTab('requirements')" style="cursor: pointer;" title="Manage document requirements">
                    <div class="tosa-kpi-icon is-maroon">
                        <i class="bi bi-card-checklist"></i>
                    </div>
                    <div>
                        <div class="tosa-kpi-num" id="ovKpiRequirements">5</div>
                        <div class="tosa-kpi-label">Active Requirements</div>
                    </div>
                </div>

                <div class="tosa-kpi-card" onclick="switchTosaTab('queue')" style="cursor: pointer;" title="Open prioritized review queue">
                    <div class="tosa-kpi-icon is-amber">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="tosa-kpi-num" id="ovKpiReviewQueue">34</div>
                        <div class="tosa-kpi-label">In Review Queue</div>
                    </div>
                </div>

                <div class="tosa-kpi-card" onclick="switchTosaTab('log')" style="cursor: pointer;" title="Inspect cryptographic activity log">
                    <div class="tosa-kpi-icon is-green">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="tosa-kpi-num" id="ovKpiAuditedEvents">28</div>
                        <div class="tosa-kpi-label">Audited Events</div>
                    </div>
                </div>
            </div>

            {{-- Main Harmonized Dashboard Grid --}}
            <div class="tosa-ov-main-grid">
                
                {{-- LEFT COLUMN: Applicant Submissions & Review Queue Triage Funnel --}}
                <div>
                    {{-- 1. Applicant Status Breakdown Funnel --}}
                    <section class="tosa-ov-card">
                        <div class="tosa-ov-card-header">
                            <h3 class="tosa-ov-card-title">
                                <i class="bi bi-pie-chart-fill" style="color: #8b1828;"></i>
                                <span>Applicant Submissions Funnel</span>
                            </h3>
                            <button type="button" class="tosa-ov-link-btn" onclick="switchTosaTab('applicants')">
                                <span>View Directory</span> <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.85rem; margin-bottom: 1.25rem;">
                            <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 0.85rem 1rem; text-align: center;">
                                <div style="font-size: 1.45rem; font-weight: 800; color: #15803d;" id="ovStatComplete">18</div>
                                <div style="font-size: 0.76rem; font-weight: 700; color: #166534; text-transform: uppercase;">Complete / Endorsed</div>
                                <div style="font-size: 0.72rem; color: #4ade80; font-weight: 600; margin-top: 0.2rem;">34.6% of pool</div>
                            </div>
                            <div style="background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 0.85rem 1rem; text-align: center;">
                                <div style="font-size: 1.45rem; font-weight: 800; color: #dc2626;" id="ovStatMissing">22</div>
                                <div style="font-size: 0.76rem; font-weight: 700; color: #991b1b; text-transform: uppercase;">Missing Docs</div>
                                <div style="font-size: 0.72rem; color: #f87171; font-weight: 600; margin-top: 0.2rem;">42.3% of pool</div>
                            </div>
                            <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 0.85rem 1rem; text-align: center;">
                                <div style="font-size: 1.45rem; font-weight: 800; color: #d97706;" id="ovStatReview">12</div>
                                <div style="font-size: 0.76rem; font-weight: 700; color: #92400e; text-transform: uppercase;">In Review / Revision</div>
                                <div style="font-size: 0.72rem; color: #fbbf24; font-weight: 600; margin-top: 0.2rem;">23.1% of pool</div>
                            </div>
                        </div>

                        {{-- Academic Program Distribution --}}
                        <h4 style="font-size: 0.86rem; font-weight: 800; color: #1a1618; margin: 0 0 0.85rem; display: flex; align-items: center; justify-content: space-between;">
                            <span>Academic Program Diversity</span>
                            <span style="font-size: 0.76rem; color: #64748b; font-weight: 600;">7 Programs Represented</span>
                        </h4>

                        <div class="tosa-ov-prog-row">
                            <span class="tosa-ov-prog-label">BS Information Technology</span>
                            <div class="tosa-ov-prog-bar"><div class="tosa-ov-prog-fill" style="width: 23.1%;"></div></div>
                            <span class="tosa-ov-prog-val">12 <small style="color: #64748b; font-size: 0.7rem;">(23%)</small></span>
                        </div>
                        <div class="tosa-ov-prog-row">
                            <span class="tosa-ov-prog-label">BS Computer Science</span>
                            <div class="tosa-ov-prog-bar"><div class="tosa-ov-prog-fill" style="width: 17.3%; background: #0284c7;"></div></div>
                            <span class="tosa-ov-prog-val">9 <small style="color: #64748b; font-size: 0.7rem;">(17%)</small></span>
                        </div>
                        <div class="tosa-ov-prog-row">
                            <span class="tosa-ov-prog-label">BS Business Administration</span>
                            <div class="tosa-ov-prog-bar"><div class="tosa-ov-prog-fill" style="width: 15.4%; background: #d97706;"></div></div>
                            <span class="tosa-ov-prog-val">8 <small style="color: #64748b; font-size: 0.7rem;">(15%)</small></span>
                        </div>
                        <div class="tosa-ov-prog-row">
                            <span class="tosa-ov-prog-label">BS Information Systems</span>
                            <div class="tosa-ov-prog-bar"><div class="tosa-ov-prog-fill" style="width: 13.5%; background: #7e22ce;"></div></div>
                            <span class="tosa-ov-prog-val">7 <small style="color: #64748b; font-size: 0.7rem;">(13%)</small></span>
                        </div>
                        <div class="tosa-ov-prog-row">
                            <span class="tosa-ov-prog-label">BS Mechanical Engineering</span>
                            <div class="tosa-ov-prog-bar"><div class="tosa-ov-prog-fill" style="width: 11.5%; background: #ea580c;"></div></div>
                            <span class="tosa-ov-prog-val">6 <small style="color: #64748b; font-size: 0.7rem;">(12%)</small></span>
                        </div>
                        <div class="tosa-ov-prog-row">
                            <span class="tosa-ov-prog-label">BS Education &amp; Accountancy</span>
                            <div class="tosa-ov-prog-bar"><div class="tosa-ov-prog-fill" style="width: 19.2%; background: #16a34a;"></div></div>
                            <span class="tosa-ov-prog-val">10 <small style="color: #64748b; font-size: 0.7rem;">(19%)</small></span>
                        </div>
                    </section>

                    {{-- 2. Urgent Review Queue Triage Highlights --}}
                    <section class="tosa-ov-card">
                        <div class="tosa-ov-card-header">
                            <h3 class="tosa-ov-card-title">
                                <i class="bi bi-lightning-charge-fill" style="color: #d97706;"></i>
                                <span>Priority Review Queue (Top Items)</span>
                            </h3>
                            <button type="button" class="tosa-ov-link-btn" onclick="switchTosaTab('queue')">
                                <span>Go to Queue</span> <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                        <div class="tosa-ov-triage-item">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span class="tosa-queue-priority-badge is-urgent">URGENT</span>
                                <div>
                                    <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Dela Cruz, John Paul</strong>
                                    <small style="font-size: 0.76rem; color: #64748b;">BS Computer Science (3rd Year) • Missing 2 Requirements</small>
                                </div>
                            </div>
                            <button type="button" class="tosa-queue-btn-action" onclick="openReviewModal(2)" style="padding: 0.4rem 0.8rem; font-size: 0.76rem;">
                                <i class="bi bi-search"></i> Review
                            </button>
                        </div>

                        <div class="tosa-ov-triage-item">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span class="tosa-queue-priority-badge is-urgent">URGENT</span>
                                <div>
                                    <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Villanueva, Kenneth</strong>
                                    <small style="font-size: 0.76rem; color: #64748b;">BS Information Systems (3rd Year) • Missing 1 Requirement</small>
                                </div>
                            </div>
                            <button type="button" class="tosa-queue-btn-action" onclick="openReviewModal(4)" style="padding: 0.4rem 0.8rem; font-size: 0.76rem;">
                                <i class="bi bi-search"></i> Review
                            </button>
                        </div>

                        <div class="tosa-ov-triage-item">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span class="tosa-queue-priority-badge is-urgent">ACTION</span>
                                <div>
                                    <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Garcia, Miguel Angelo</strong>
                                    <small style="font-size: 0.76rem; color: #64748b;">BS Mechanical Engineering (3rd Year) • Returned for Revision</small>
                                </div>
                            </div>
                            <button type="button" class="tosa-queue-btn-action" onclick="openReviewModal(6)" style="padding: 0.4rem 0.8rem; font-size: 0.76rem;">
                                <i class="bi bi-search"></i> Review
                            </button>
                        </div>
                    </section>
                </div>

                {{-- RIGHT COLUMN: Requirements Governance, Cycle Timeline & Live Activity --}}
                <div>
                    {{-- 3. Requirements Governance Summary --}}
                    <section class="tosa-ov-card">
                        <div class="tosa-ov-card-header">
                            <h3 class="tosa-ov-card-title">
                                <i class="bi bi-card-checklist" style="color: #8b1828;"></i>
                                <span>Requirements Governance</span>
                            </h3>
                            <button type="button" class="tosa-ov-link-btn" onclick="switchTosaTab('requirements')">
                                <span>Manage All (5)</span> <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; margin-bottom: 1rem;">
                            <div style="background: #fdfafb; border: 1px solid #ede8ea; border-radius: 10px; padding: 0.65rem 0.85rem;">
                                <div style="font-size: 0.74rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Required Items</div>
                                <strong style="font-size: 1.15rem; color: #8b1828;" id="ovReqMandatory">5 Required</strong>
                            </div>
                            <div style="background: #fdfafb; border: 1px solid #ede8ea; border-radius: 10px; padding: 0.65rem 0.85rem;">
                                <div style="font-size: 0.74rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Templates Uploaded</div>
                                <strong style="font-size: 1.15rem; color: #16a34a;" id="ovReqTemplates">5 Attached</strong>
                            </div>
                        </div>

                        <div style="font-size: 0.82rem; color: #475569; line-height: 1.45; margin-bottom: 0.85rem;">
                            All 5 document criteria are actively enforced across candidate submissions. Submissions with missing required items are automatically routed to the Review Queue.
                        </div>

                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.78rem;" onclick="openAddRequirementModal()">
                                <i class="bi bi-plus-circle"></i> Add Requirement
                            </button>
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.78rem;" onclick="bulkExportTemplates()">
                                <i class="bi bi-download"></i> Download Package
                            </button>
                        </div>
                    </section>

                    {{-- 4. Institutional Cycle AY 2026–2027 --}}
                    <section class="tosa-ov-card">
                        <div class="tosa-ov-card-header">
                            <h3 class="tosa-ov-card-title">
                                <i class="bi bi-trophy-fill" style="color: #8b1828;"></i>
                                <span>TOSA Cycle AY 2026–2027</span>
                            </h3>
                            <span class="tosa-badge-unlocked" style="font-size: 0.68rem;"><i class="bi bi-shield-check"></i> On Schedule</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0.85rem; background: #fdfafb; border: 1.5px solid #ede8ea; border-radius: 12px;">
                                <div style="display: flex; align-items: center; gap: 0.65rem;">
                                    <div style="width: 30px; height: 30px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
                                        <i class="bi bi-check2"></i>
                                    </div>
                                    <div>
                                        <strong style="font-size: 0.84rem; color: #1a1618; display: block;">Phase 1: Nominations</strong>
                                        <small style="font-size: 0.72rem; color: #64748b;">Closed • 52 Candidates</small>
                                    </div>
                                </div>
                                <span class="tosa-status-pill is-complete" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">Completed</span>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0.85rem; background: #fffdfa; border: 1.5px solid #fef3c7; border-radius: 12px;">
                                <div style="display: flex; align-items: center; gap: 0.65rem;">
                                    <div style="width: 30px; height: 30px; border-radius: 8px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                    <div>
                                        <strong style="font-size: 0.84rem; color: #1a1618; display: block;">Phase 2: Verification</strong>
                                        <small style="font-size: 0.72rem; color: #64748b;">Active • 34 in Review Queue</small>
                                    </div>
                                </div>
                                <span class="tosa-status-pill is-review" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">In Progress</span>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0.85rem; background: #ffffff; border: 1.5px solid #ede8ea; border-radius: 12px; opacity: 0.75;">
                                <div style="display: flex; align-items: center; gap: 0.65rem;">
                                    <div style="width: 30px; height: 30px; border-radius: 8px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
                                        <i class="bi bi-award"></i>
                                    </div>
                                    <div>
                                        <strong style="font-size: 0.84rem; color: #1a1618; display: block;">Phase 3: Awarding</strong>
                                        <small style="font-size: 0.72rem; color: #64748b;">Upcoming • Sept 28, 2026</small>
                                    </div>
                                </div>
                                <span class="tosa-status-pill" style="background: #f1f5f9; color: #64748b; font-size: 0.7rem; padding: 0.2rem 0.5rem;">Upcoming</span>
                            </div>
                        </div>
                    </section>

                    {{-- 5. Real-Time Immutable Activity Stream --}}
                    <section class="tosa-ov-card">
                        <div class="tosa-ov-card-header">
                            <h3 class="tosa-ov-card-title">
                                <i class="bi bi-journal-text" style="color: #8b1828;"></i>
                                <span>Recent Audit Trail</span>
                            </h3>
                            <button type="button" class="tosa-ov-link-btn" onclick="switchTosaTab('log')">
                                <span>Full Log</span> <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                        <div id="ovRecentActivityStream">
                            {{-- Populated via JS --}}
                        </div>
                    </section>
                </div>

            </div>
        </div>

        {{-- TAB 2: REQUIREMENTS MANAGEMENT --}}
        <div id="tosaTabSectionRequirements" class="tosa-tab-pane" style="display: none;">
            
            {{-- Header --}}
            <div class="tosa-req-header-wrap">
                <div>
                    <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--tosa-ink-dark); margin: 0 0 0.25rem; letter-spacing: -0.02em;">Requirements</h2>
                    <p class="tosa-req-subtitle">Manage and configure all document requirements for TOSA applications.</p>
                </div>
                <button type="button" class="tosa-btn-add-req" onclick="openAddRequirementModal()">
                    <i class="bi bi-plus-lg"></i> Add New Requirement
                </button>
            </div>

            {{-- 4 Summary KPI Cards --}}
            <div class="tosa-req-kpis-grid">
                <div class="tosa-req-kpi-box">
                    <div class="tosa-req-kpi-icon is-blue">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <div class="tosa-req-kpi-num" id="reqTotalCounter">5</div>
                        <div class="tosa-req-kpi-text">Total Requirements</div>
                    </div>
                </div>

                <div class="tosa-req-kpi-box">
                    <div class="tosa-req-kpi-icon is-green">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="tosa-req-kpi-num" id="reqRequiredCounter">5</div>
                        <div class="tosa-req-kpi-text">Required</div>
                    </div>
                </div>

                <div class="tosa-req-kpi-box">
                    <div class="tosa-req-kpi-icon is-amber">
                        <i class="bi bi-dash-circle"></i>
                    </div>
                    <div>
                        <div class="tosa-req-kpi-num" id="reqOptionalCounter">0</div>
                        <div class="tosa-req-kpi-text">Optional</div>
                    </div>
                </div>

                <div class="tosa-req-kpi-box">
                    <div class="tosa-req-kpi-icon is-purple">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>
                    <div>
                        <div class="tosa-req-kpi-num" id="reqTemplatesCounter">5</div>
                        <div class="tosa-req-kpi-text">Templates Uploaded</div>
                    </div>
                </div>
            </div>

            {{-- Split Main Layout: Left Table + Right Sidebar --}}
            <div class="tosa-req-main-layout">
                
                {{-- Left: Document Requirements Table Card --}}
                <div class="tosa-req-card">
                    <h3 class="tosa-req-table-title">Document Requirements</h3>

                    {{-- Toolbar: Search + Status + Bulk Actions --}}
                    <div class="tosa-req-toolbar">
                        <div class="tosa-req-search-box">
                            <input type="text" class="tosa-req-search-input" id="reqSearchInput" placeholder="Search requirements..." oninput="handleReqSearch(this.value)">
                            <i class="bi bi-search search-icon"></i>
                        </div>
                        <select class="tosa-req-select" id="reqStatusFilter" onchange="handleReqStatusFilter(this.value)">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <div style="position: relative;">
                            <button type="button" class="tosa-btn-bulk" onclick="toggleBulkDropdown()">
                                Bulk Actions <i class="bi bi-chevron-down" style="font-size: 0.72rem;"></i>
                            </button>
                            <div id="bulkDropdownMenu" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 0.35rem; background: #ffffff; border: 1.5px solid #ede8ea; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 200px; z-index: 50; overflow: hidden;">
                                <button type="button" onclick="bulkSetStatus('Active')" style="width: 100%; text-align: left; padding: 0.65rem 1rem; border: none; background: none; font-size: 0.82rem; font-weight: 700; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;"><i class="bi bi-check-circle" style="color: #16a34a;"></i> Mark All Active</button>
                                <button type="button" onclick="bulkSetStatus('Inactive')" style="width: 100%; text-align: left; padding: 0.65rem 1rem; border: none; background: none; font-size: 0.82rem; font-weight: 700; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;"><i class="bi bi-pause-circle" style="color: #d97706;"></i> Mark All Inactive</button>
                                <button type="button" onclick="bulkExportTemplates()" style="width: 100%; text-align: left; padding: 0.65rem 1rem; border: none; background: none; font-size: 0.82rem; font-weight: 700; color: #8b1828; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; border-top: 1px solid #f1f5f9;"><i class="bi bi-download"></i> Export All Templates</button>
                            </div>
                        </div>
                    </div>

                    {{-- Requirements Table --}}
                    <div style="overflow-x: auto;">
                        <table class="tosa-req-table">
                            <thead>
                                <tr>
                                    <th style="width: 44px; text-align: center;">#</th>
                                    <th>Requirement</th>
                                    <th style="text-align: center;">Type</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="text-align: center;">Required</th>
                                    <th style="text-align: center;">File Format</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tosaRequirementsTbody">
                                {{-- Populated via JS --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer Info --}}
                    <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #f1ecee; font-size: 0.82rem; color: #64748b; font-weight: 600;" id="reqEntriesInfo">
                        Showing 1 to 5 of 5 entries
                    </div>
                </div>

                {{-- Right: Sidebar Instructions & Legend --}}
                <div class="tosa-req-sidebar">

                    {{-- 1. Instructions Panel --}}
                    <div class="tosa-side-panel">
                        <h4 class="tosa-side-panel-title" style="color: #0284c7;">
                            <i class="bi bi-info-circle-fill"></i> Instructions
                        </h4>
                        <ul class="tosa-side-instructions-list">
                            <li>Add new requirements using the &ldquo;Add New Requirement&rdquo; button.</li>
                            <li>Set whether the requirement is Required or Optional.</li>
                            <li>Upload template and sample files for each requirement.</li>
                            <li>Changes will be reflected immediately for all applicants.</li>
                        </ul>
                    </div>

                    {{-- 3. Legend Panel --}}
                    <div class="tosa-side-panel">
                        <h4 class="tosa-side-panel-title" style="color: #8b1828;">
                            Legend
                        </h4>
                        <div class="tosa-legend-list">
                            <div class="tosa-legend-item">
                                <span class="tosa-status-badge is-active"><i class="bi bi-circle-fill" style="font-size: 0.4rem;"></i> Active</span>
                                <span class="tosa-legend-desc">Requirement is active and visible.</span>
                            </div>
                            <div class="tosa-legend-item">
                                <span class="tosa-status-badge is-inactive"><i class="bi bi-circle-fill" style="font-size: 0.4rem;"></i> Inactive</span>
                                <span class="tosa-legend-desc">Requirement is inactive/hidden.</span>
                            </div>
                            <div class="tosa-legend-item">
                                <span class="tosa-type-badge is-form">Form</span>
                                <span class="tosa-legend-desc">Applicant will fill out a form.</span>
                            </div>
                            <div class="tosa-legend-item">
                                <span class="tosa-type-badge is-doc" style="background: #f3e8ff; color: #7e22ce;">Document</span>
                                <span class="tosa-legend-desc">Applicant will upload a document.</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        {{-- TAB 3: APPLICANT SUBMISSIONS (EXACT MOCKUP MATCH UI) --}}
        <div id="tosaTabSectionApplicants" class="tosa-tab-pane" style="display: none;">
            
            {{-- Section Description --}}
            <p style="font-size: 0.92rem; color: #475569; margin: 0 0 1.25rem; font-weight: 500;">
                Review and manage all applicant submissions for TOSA.
            </p>

            {{-- 4 Stat Summary Cards --}}
            <div class="tosa-sub-stat-grid">
                <div class="tosa-sub-stat-card">
                    <div class="tosa-sub-stat-icon is-blue">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <div class="tosa-sub-stat-num" id="subKpiTotalApplicants">52</div>
                        <div class="tosa-sub-stat-label">Total Applicants</div>
                    </div>
                </div>

                <div class="tosa-sub-stat-card">
                    <div class="tosa-sub-stat-icon is-green">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="tosa-sub-stat-num" id="subKpiComplete">18</div>
                        <div class="tosa-sub-stat-label">Complete Submissions</div>
                    </div>
                </div>

                <div class="tosa-sub-stat-card">
                    <div class="tosa-sub-stat-icon is-red">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                    <div>
                        <div class="tosa-sub-stat-num" id="subKpiMissing">22</div>
                        <div class="tosa-sub-stat-label">Missing Documents</div>
                    </div>
                </div>

                <div class="tosa-sub-stat-card">
                    <div class="tosa-sub-stat-icon is-amber">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div>
                        <div class="tosa-sub-stat-num" id="subKpiPending">12</div>
                        <div class="tosa-sub-stat-label">Pending Review</div>
                    </div>
                </div>
            </div>

            {{-- Main Table Container Card --}}
            <div class="tosa-sub-table-card">
                {{-- Toolbar: Search + Filter Dropdowns + Export Button --}}
                <div class="tosa-sub-toolbar">
                    <div class="tosa-sub-toolbar-left">
                        <div class="tosa-sub-search-box">
                            <input type="text" id="tosaApplicantSearch" placeholder="Search applicant name or ID..." oninput="handleApplicantSearch(this.value)">
                            <i class="bi bi-search search-icon"></i>
                        </div>
                        <select class="tosa-sub-select" id="tosaStatusFilter" onchange="handleStatusFilterChange(this.value)">
                            <option value="">All Status</option>
                            <option value="Complete">Complete</option>
                            <option value="Missing Documents">Missing Documents</option>
                            <option value="Under Review">Under Review</option>
                            <option value="Approved">Approved</option>
                            <option value="Returned">Returned</option>
                        </select>
                        <select class="tosa-sub-select" id="tosaProgramFilter" onchange="handleProgramFilterChange(this.value)">
                            <option value="">All Programs</option>
                            <option value="BS Information Technology">BS Information Technology</option>
                            <option value="BS Computer Science">BS Computer Science</option>
                            <option value="BS Business Administration">BS Business Administration</option>
                            <option value="BS Information Systems">BS Information Systems</option>
                            <option value="BS Education">BS Education</option>
                            <option value="BS Mechanical Engineering">BS Mechanical Engineering</option>
                            <option value="BS Accountancy">BS Accountancy</option>
                        </select>
                        <select class="tosa-sub-select" id="tosaYearFilter" onchange="handleYearFilterChange(this.value)">
                            <option value="">All Year Levels</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" class="tosa-btn-export-report" onclick="exportApplicantReport()">
                            <i class="bi bi-box-arrow-in-down"></i> Export Report
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div style="overflow-x: auto;">
                    <table class="tosa-submissions-table">
                        <thead>
                            <tr>
                                <th style="width: 44px; text-align: center;">#</th>
                                <th>Applicant</th>
                                <th>Program / Year Level</th>
                                <th>Submitted</th>
                                <th>Missing</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;">Date Submitted</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tosaApplicantsTbody">
                            {{-- Populated dynamically via JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Row --}}
                <div class="tosa-pagination-bar" id="tosaPagination" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #f1ecee; display: flex; align-items: center; justify-content: space-between;">
                    <div style="font-size: 0.82rem; color: #64748b; font-weight: 600;" id="tosaPaginationInfo">
                        Showing <strong>1</strong> to <strong>7</strong> of <strong>52</strong> entries
                    </div>
                    <div style="display: flex; gap: 0.35rem; align-items: center;" id="tosaPaginationNav">
                        {{-- Buttons rendered via JS --}}
                    </div>
                </div>
            </div>

            {{-- Bottom Row: Status Legend + Info Notice Banner --}}
            <div class="tosa-sub-bottom-wrap">
                <div class="tosa-sub-legend-row">
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.84rem;">Status Legend:</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot is-green"></span> Complete</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot is-red"></span> Missing Documents</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot is-blue"></span> Under Review</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot is-orange"></span> Returned</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot is-green"></span> Approved</span>
                </div>
                <div class="tosa-sub-info-notice">
                    <i class="bi bi-info-circle"></i>
                    <span>Review each submission carefully. You can return submissions for correction, reject incomplete ones, or approve complete submissions.</span>
                </div>
            </div>

        </div>

        {{-- TAB 4: REVIEW QUEUE (IMPECCABLE & UNSLOP UI) --}}
        <div id="tosaTabSectionQueue" class="tosa-tab-pane" style="display: none;">
            
            {{-- Section Description --}}
            <p style="font-size: 0.92rem; color: #475569; margin: 0 0 1.25rem; font-weight: 500;">
                Prioritized evaluation queue for candidate dossiers requiring immediate OSO compliance validation, document inspection, or revision endorsement.
            </p>

            {{-- 4 Queue Metric KPI Cards --}}
            <div class="tosa-queue-stat-grid">
                <div class="tosa-queue-stat-card">
                    <div class="tosa-queue-stat-icon is-amber">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="tosa-queue-stat-num" id="queueKpiTotal">34</div>
                        <div class="tosa-queue-stat-label">Total In Queue</div>
                    </div>
                </div>

                <div class="tosa-queue-stat-card">
                    <div class="tosa-queue-stat-icon is-red">
                        <i class="bi bi-file-earmark-x-fill"></i>
                    </div>
                    <div>
                        <div class="tosa-queue-stat-num" id="queueKpiMissing">22</div>
                        <div class="tosa-queue-stat-label">Missing Requirements</div>
                    </div>
                </div>

                <div class="tosa-queue-stat-card">
                    <div class="tosa-queue-stat-icon is-orange">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <div class="tosa-queue-stat-num" id="queueKpiReturned">7</div>
                        <div class="tosa-queue-stat-label">Returned for Revision</div>
                    </div>
                </div>

                <div class="tosa-queue-stat-card">
                    <div class="tosa-queue-stat-icon is-blue">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                    <div>
                        <div class="tosa-queue-stat-num" id="queueKpiUnderReview">5</div>
                        <div class="tosa-queue-stat-label">Pending Initial Audit</div>
                    </div>
                </div>
            </div>

            {{-- Main Queue Table Container Card --}}
            <div class="tosa-queue-table-card">
                {{-- Toolbar: Search + Select + Export --}}
                <div class="tosa-queue-toolbar">
                    <div class="tosa-queue-toolbar-left">
                        <div class="tosa-queue-search-box">
                            <input type="text" id="tosaQueueSearch" placeholder="Search candidate name or ID in queue..." oninput="handleQueueSearch(this.value)">
                            <i class="bi bi-search search-icon"></i>
                        </div>
                        <select class="tosa-queue-select" id="tosaQueueStatusFilter" onchange="handleQueueStatusFilter(this.value)">
                            <option value="">All Queue Statuses</option>
                            <option value="Missing Documents">Missing Documents</option>
                            <option value="Returned">Returned / Revision</option>
                            <option value="Under Review">Under Review</option>
                        </select>
                        <select class="tosa-queue-select" id="tosaQueueProgramFilter" onchange="handleQueueProgramFilter(this.value)">
                            <option value="">All Programs</option>
                            <option value="BS Information Technology">BS Information Technology</option>
                            <option value="BS Computer Science">BS Computer Science</option>
                            <option value="BS Business Administration">BS Business Administration</option>
                            <option value="BS Information Systems">BS Information Systems</option>
                            <option value="BS Education">BS Education</option>
                            <option value="BS Mechanical Engineering">BS Mechanical Engineering</option>
                            <option value="BS Accountancy">BS Accountancy</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" class="tosa-btn-export-report" onclick="exportQueueReport()">
                            <i class="bi bi-file-earmark-arrow-down"></i> Export Queue
                        </button>
                    </div>
                </div>

                {{-- Fast-Triage Filter Pills --}}
                <div class="tosa-queue-pills-row">
                    <button type="button" class="tosa-queue-filter-pill is-active" id="qPillAll" onclick="setQueueQuickFilter('')">
                        <i class="bi bi-grid-fill"></i> All Items (<span id="qPillAllCount">34</span>)
                    </button>
                    <button type="button" class="tosa-queue-filter-pill" id="qPillMissing" onclick="setQueueQuickFilter('Missing Documents')">
                        <i class="bi bi-exclamation-circle-fill" style="color: #dc2626;"></i> Missing Attachments (22)
                    </button>
                    <button type="button" class="tosa-queue-filter-pill" id="qPillReturned" onclick="setQueueQuickFilter('Returned')">
                        <i class="bi bi-arrow-repeat" style="color: #ea580c;"></i> Returned for Revision (7)
                    </button>
                    <button type="button" class="tosa-queue-filter-pill" id="qPillReview" onclick="setQueueQuickFilter('Under Review')">
                        <i class="bi bi-hourglass-split" style="color: #d97706;"></i> Initial Review (5)
                    </button>
                </div>

                {{-- Table --}}
                <div style="overflow-x: auto;">
                    <table class="tosa-queue-table">
                        <thead>
                            <tr>
                                <th style="width: 44px; text-align: center;">#</th>
                                <th style="width: 70px; text-align: center;">Priority</th>
                                <th>Candidate</th>
                                <th>Program / Year Level</th>
                                <th>Queue Flag</th>
                                <th>Submitted</th>
                                <th style="text-align: center;">Queued Date</th>
                                <th style="text-align: center;">Verification Action</th>
                            </tr>
                        </thead>
                        <tbody id="tosaQueueTbody">
                            {{-- Populated dynamically via JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Row --}}
                <div class="tosa-pagination-bar" id="tosaQueuePagination" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #f1ecee; display: flex; align-items: center; justify-content: space-between;">
                    <div style="font-size: 0.82rem; color: #64748b; font-weight: 600;" id="tosaQueuePaginationInfo">
                        Showing <strong>1</strong> to <strong>7</strong> of <strong>34</strong> queue entries
                    </div>
                    <div style="display: flex; gap: 0.35rem; align-items: center;" id="tosaQueuePaginationNav">
                        {{-- Buttons rendered via JS --}}
                    </div>
                </div>
            </div>

        </div>

        {{-- TAB 5: ACTIVITY LOG (IMMUTABLE AUDIT TRAIL) --}}
        <div id="tosaTabSectionLog" class="tosa-tab-pane" style="display: none;">
            
            {{-- Section Description --}}
            <p style="font-size: 0.92rem; color: #475569; margin: 0 0 1.25rem; font-weight: 500;">
                Immutable cryptographic audit trail of all administrative actions, applicant endorsements, requirement modifications, and reviewer decisions.
            </p>

            {{-- 4 Log Metric KPI Cards --}}
            <div class="tosa-log-stat-grid">
                <div class="tosa-log-stat-card">
                    <div class="tosa-log-stat-icon is-maroon">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <div class="tosa-log-stat-num" id="logKpiTotal">28</div>
                        <div class="tosa-log-stat-label">Total Audited Events</div>
                    </div>
                </div>

                <div class="tosa-log-stat-card">
                    <div class="tosa-log-stat-icon is-green">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div>
                        <div class="tosa-log-stat-num" id="logKpiChainValid">100%</div>
                        <div class="tosa-log-stat-label">Chain Validated</div>
                    </div>
                </div>

                <div class="tosa-log-stat-card">
                    <div class="tosa-log-stat-icon is-amber">
                        <i class="bi bi-shield-shaded"></i>
                    </div>
                    <div>
                        <div class="tosa-log-stat-num" id="logKpiOverrides">0</div>
                        <div class="tosa-log-stat-label">Sensitive Violations</div>
                    </div>
                </div>

                <div class="tosa-log-stat-card">
                    <div class="tosa-log-stat-icon is-blue">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <div class="tosa-log-stat-num" id="logKpiSessions">1</div>
                        <div class="tosa-log-stat-label">Active Reviewer Session</div>
                    </div>
                </div>
            </div>

            {{-- Main Log Container Card --}}
            <div class="tosa-log-table-card">
                {{-- Toolbar: Search + Select + Export --}}
                <div class="tosa-log-toolbar">
                    <div class="tosa-log-toolbar-left">
                        <div class="tosa-log-search-box">
                            <input type="text" id="tosaLogSearch" placeholder="Search activity, actor, target dossier, or hash..." oninput="handleLogSearch(this.value)">
                            <i class="bi bi-search search-icon"></i>
                        </div>
                        <select class="tosa-log-select" id="tosaLogCategoryFilter" onchange="handleLogCategoryFilter(this.value)">
                            <option value="">All Action Categories</option>
                            <option value="Approval">Approvals &amp; Endorsements</option>
                            <option value="Revision">Revision Requests</option>
                            <option value="Requirement">Requirement Governance</option>
                            <option value="Security">Security &amp; Clearance</option>
                            <option value="System">System Integrity</option>
                        </select>
                        <select class="tosa-log-select" id="tosaLogActorFilter" onchange="handleLogActorFilter(this.value)">
                            <option value="">All Actors</option>
                            <option value="OSO Review Officer">OSO Review Officer</option>
                            <option value="System Integrity">System Integrity</option>
                            <option value="Student Affairs Directorate">Student Affairs Directorate</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" class="tosa-btn-export-report" onclick="exportActivityLogReport()">
                            <i class="bi bi-file-earmark-lock2"></i> Export Audit Trail
                        </button>
                    </div>
                </div>

                {{-- Fast-Category Filter Pills --}}
                <div class="tosa-log-pills-row">
                    <button type="button" class="tosa-log-filter-pill is-active" id="logPillAll" onclick="setLogQuickFilter('')">
                        <i class="bi bi-grid-fill"></i> All Events (<span id="logPillAllCount">28</span>)
                    </button>
                    <button type="button" class="tosa-log-filter-pill" id="logPillApproval" onclick="setLogQuickFilter('Approval')">
                        <i class="bi bi-check-circle-fill" style="color: #16a34a;"></i> Approvals (<span id="logPillApprovalCount">12</span>)
                    </button>
                    <button type="button" class="tosa-log-filter-pill" id="logPillRevision" onclick="setLogQuickFilter('Revision')">
                        <i class="bi bi-arrow-repeat" style="color: #ea580c;"></i> Revisions (<span id="logPillRevisionCount">7</span>)
                    </button>
                    <button type="button" class="tosa-log-filter-pill" id="logPillReq" onclick="setLogQuickFilter('Requirement')">
                        <i class="bi bi-card-checklist" style="color: #7e22ce;"></i> Requirements (<span id="logPillReqCount">5</span>)
                    </button>
                    <button type="button" class="tosa-log-filter-pill" id="logPillSec" onclick="setLogQuickFilter('Security')">
                        <i class="bi bi-shield-check" style="color: #0284c7;"></i> Security &amp; Ledger (<span id="logPillSecCount">4</span>)
                    </button>
                </div>

                {{-- Table --}}
                <div style="overflow-x: auto;">
                    <table class="tosa-log-table">
                        <thead>
                            <tr>
                                <th style="width: 44px; text-align: center;">#</th>
                                <th>Timestamp</th>
                                <th>Authorized Actor</th>
                                <th style="text-align: center;">Action Category</th>
                                <th>Target Dossier / Component</th>
                                <th>Cryptographic Hash</th>
                                <th style="text-align: center;">Chain Status</th>
                            </tr>
                        </thead>
                        <tbody id="tosaActivityLogTbody">
                            {{-- Populated dynamically via JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Row --}}
                <div class="tosa-pagination-bar" id="tosaLogPagination" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #f1ecee; display: flex; align-items: center; justify-content: space-between;">
                    <div style="font-size: 0.82rem; color: #64748b; font-weight: 600;" id="tosaLogPaginationInfo">
                        Showing <strong>1</strong> to <strong>7</strong> of <strong>28</strong> log entries
                    </div>
                    <div style="display: flex; gap: 0.35rem; align-items: center;" id="tosaLogPaginationNav">
                        {{-- Buttons rendered via JS --}}
                    </div>
                </div>
            </div>

            {{-- Bottom Notice & Security Ledger Info --}}
            <div class="tosa-sub-bottom-wrap">
                <div class="tosa-sub-legend-row">
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.84rem;">Audit Categories:</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot is-green"></span> Approvals</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot is-orange"></span> Revisions</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot is-blue"></span> Security / Ledger</span>
                    <span class="tosa-sub-legend-item"><span class="tosa-sub-dot" style="background: #7e22ce;"></span> Requirements</span>
                </div>
                <div class="tosa-sub-info-notice">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>All log events are cryptographically hashed using SHA-256 and appended to the tamper-evident TOSA institutional ledger. Click any hash to copy.</span>
                </div>
            </div>

        </div>

    </div>

    {{-- =========================================================================
         MODAL 1: Two-Column Review Applicant Modal Dialog
         ========================================================================= --}}
    <dialog class="tosa-modal" id="applicantReviewModal">
        <div class="tosa-modal-box">
            <div class="tosa-modal-header">
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--tosa-ink-dark); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-award-fill" style="color: var(--tosa-maroon);"></i> TOSA Candidate Review Dossier
                    </h3>
                    <p style="margin: 0.2rem 0 0; font-size: 0.8rem; color: var(--tosa-ink-muted);">Comprehensive verification of credentials, advocacy metrics, and compliance.</p>
                </div>
                <button type="button" class="arc-modal-close" onclick="closeReviewModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--tosa-ink-muted); cursor: pointer;">&times;</button>
            </div>

            <div class="tosa-review-grid">
                {{-- LEFT COLUMN: Applicant Submission Dossier --}}
                <div class="tosa-review-col-left">
                    <div class="tosa-dossier-card">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <div class="tosa-avatar" id="revAvatar" style="width: 52px; height: 52px; font-size: 1.15rem;">JD</div>
                            <div>
                                <h4 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--tosa-ink-dark);" id="revName">Juan Dela Cruz</h4>
                                <span style="font-size: 0.82rem; font-weight: 700; color: var(--tosa-maroon);" id="revOrg">BSIT Society</span>
                                <div style="font-size: 0.76rem; color: var(--tosa-ink-muted); margin-top: 0.15rem;" id="revMeta">SR-Code: 21-04921 • BSIT 4th Year</div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.82rem; border-top: 1px solid var(--tosa-border); padding-top: 0.85rem;">
                            <div>
                                <span style="display: block; font-size: 0.7rem; text-transform: uppercase; color: var(--tosa-ink-muted); font-weight: 800;">Award Category</span>
                                <strong style="color: var(--tosa-ink-dark);" id="revCategory">Leadership &amp; Technology</strong>
                            </div>
                            <div>
                                <span style="display: block; font-size: 0.7rem; text-transform: uppercase; color: var(--tosa-ink-muted); font-weight: 800;">Date Submitted</span>
                                <strong style="color: var(--tosa-ink-dark);" id="revDate">Sept 5, 2026</strong>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--tosa-ink-dark); margin: 0 0 0.75rem;">
                            <i class="bi bi-files"></i> Submitted Documents Package
                        </h4>
                        
                        <div id="revDocListContainer">
                            {{-- Document Items --}}
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN: OSO Review & Checklist Panel --}}
                <div class="tosa-review-col-right">
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--tosa-ink-dark); margin: 0 0 0.35rem;">
                            <i class="bi bi-check2-square" style="color: var(--tosa-maroon);"></i> Requirements Checklist
                        </h4>
                        <p style="margin: 0 0 0.85rem; font-size: 0.76rem; color: var(--tosa-ink-muted);">Verify each submitted document against university criteria.</p>

                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <label class="tosa-checklist-label">
                                <input type="checkbox" id="chkReq1" checked>
                                <span>Certificate of Registration (Authenticated)</span>
                            </label>
                            <label class="tosa-checklist-label">
                                <input type="checkbox" id="chkReq2" checked>
                                <span>Activity Proposal &amp; Advocacy Portfolio</span>
                            </label>
                            <label class="tosa-checklist-label">
                                <input type="checkbox" id="chkReq3" checked>
                                <span>Budget &amp; Financial Breakdown Plan</span>
                            </label>
                            <label class="tosa-checklist-label">
                                <input type="checkbox" id="chkReq4" checked>
                                <span>Participant &amp; Beneficiary Masterlist</span>
                            </label>
                            <label class="tosa-checklist-label">
                                <input type="checkbox" id="chkReq5" checked>
                                <span>Community Accomplishment Evidence</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="revRemarksInput" style="display: block; font-size: 0.82rem; font-weight: 800; color: var(--tosa-ink-dark); margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.03em;">
                            Reviewer Remarks &amp; Feedback
                        </label>
                        <textarea id="revRemarksInput" class="tosa-remarks-textarea" placeholder="Provide clear institutional evaluation notes, revision instructions, or endorsement reasons..."></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.76rem; font-weight: 800; color: var(--tosa-ink-muted); margin-bottom: 0.45rem; text-transform: uppercase;">
                            Final Determination
                        </label>
                        <div class="tosa-decision-actions">
                            <button type="button" class="tosa-btn-decision is-approve" onclick="submitDecision('Approved')">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                            <button type="button" class="tosa-btn-decision is-revision" onclick="submitDecision('For Revision')">
                                <i class="bi bi-arrow-repeat"></i> Request Revision
                            </button>
                            <button type="button" class="tosa-btn-decision is-reject" onclick="submitDecision('Rejected')">
                                <i class="bi bi-x-circle"></i> Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    {{-- =========================================================================
         MODAL 2: Add / Edit Requirement Modal Dialog
         ========================================================================= --}}
    <dialog class="tosa-modal" id="addRequirementModal" style="max-width: 540px;">
        <div class="tosa-modal-box">
            <div class="tosa-modal-header">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #1a1618; display: flex; align-items: center; gap: 0.5rem;" id="modalReqTitle">
                    <i class="bi bi-plus-circle-fill" style="color: #8b1828;"></i> Add New Requirement
                </h3>
                <button type="button" onclick="closeAddRequirementModal()" style="background: none; border: none; font-size: 1.4rem; color: #64748b; cursor: pointer;">&times;</button>
            </div>

            <form onsubmit="handleAddRequirementSubmit(event)">
                <input type="hidden" id="editReqId" value="">
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.35rem;">
                        Requirement Name *
                    </label>
                    <input type="text" id="newReqTitle" required class="tosa-req-search-input" style="border-radius: 10px;" placeholder="e.g. Certificate of Registration">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.35rem;">
                        Description / Guidelines
                    </label>
                    <textarea id="newReqDesc" rows="3" class="tosa-remarks-textarea" placeholder="Explain formatting specifications and submission instructions..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.35rem;">
                            Requirement Type
                        </label>
                        <select id="newReqType" class="tosa-side-select">
                            <option value="Document">Document Upload</option>
                            <option value="Form">Online Form</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.35rem;">
                            Accepted File Format
                        </label>
                        <select id="newReqFormat" class="tosa-side-select">
                            <option value="PDF">PDF</option>
                            <option value="DOCX">DOCX</option>
                            <option value="XLSX">XLSX</option>
                            <option value="PDF, DOCX">PDF, DOCX</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1rem; background: #fdfafb; border: 1.5px solid #ede8ea; border-radius: 12px; margin-bottom: 1.25rem;">
                    <div>
                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Mandatory Obligation</strong>
                        <small style="font-size: 0.76rem; color: #64748b;">Applicant cannot submit dossier without this document.</small>
                    </div>
                    <label class="tosa-switch">
                        <input type="checkbox" id="newReqRequiredCheck" checked>
                        <span class="tosa-slider"></span>
                    </label>
                </div>

                <div style="display: flex; justify-content: center; gap: 0.75rem; border-top: 1px solid #ede8ea; padding-top: 1.25rem;">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="closeAddRequirementModal()">Cancel</button>
                    <button type="submit" class="tosa-btn-add-req" style="padding: 0.55rem 1.2rem; font-size: 0.84rem;">Save Requirement</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- =========================================================================
         MODAL 3: Upload Template Modal
         ========================================================================= --}}
    <dialog class="tosa-modal" id="uploadTemplateModal" style="max-width: 480px;">
        <div class="tosa-modal-box">
            <div class="tosa-modal-header">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #1a1618; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-upload" style="color: #8b1828;"></i> Upload Official Template
                </h3>
                <button type="button" onclick="closeUploadTemplateModal()" style="background: none; border: none; font-size: 1.4rem; color: #64748b; cursor: pointer;">&times;</button>
            </div>

            <form onsubmit="handleTemplateUploadSubmit(event)">
                <input type="hidden" id="uploadReqId" value="">
                <p style="font-size: 0.86rem; color: #64748b; margin: 0 0 1rem;" id="uploadReqNameLabel">
                    Select a downloadable sample/template file for <strong>Application Form</strong>.
                </p>

                <div style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 2rem 1.5rem; text-align: center; background: #fdfafb; margin-bottom: 1.25rem;">
                    <i class="bi bi-file-earmark-arrow-up" style="font-size: 2.2rem; color: #8b1828; display: block; margin-bottom: 0.5rem;"></i>
                    <strong style="font-size: 0.92rem; color: #1a1618; display: block; margin-bottom: 0.25rem;">Drag &amp; drop template file here</strong>
                    <span style="font-size: 0.78rem; color: #64748b; display: block; margin-bottom: 1rem;">Supports PDF, DOCX, XLSX up to 10MB</span>
                    <input type="file" id="templateFileInput" style="display: none;" onchange="handleFileSelected(this)">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="document.getElementById('templateFileInput').click()">
                        Browse Files
                    </button>
                    <div id="selectedFileName" style="font-size: 0.8rem; font-weight: 700; color: #16a34a; margin-top: 0.6rem; display: none;"></div>
                </div>

                <div style="display: flex; justify-content: center; gap: 0.75rem;">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="closeUploadTemplateModal()">Cancel</button>
                    <button type="submit" class="tosa-btn-add-req" style="padding: 0.55rem 1.2rem; font-size: 0.84rem;">Upload &amp; Attach</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- =========================================================================
         MODAL 4: Forgot PIN Information Modal
         ========================================================================= --}}
    <dialog class="tosa-modal" id="forgotPinModal" style="max-width: 440px;">
        <div class="tosa-modal-box" style="text-align: center;">
            <div style="width: 52px; height: 52px; border-radius: 50%; background: #fdf0f2; color: #8b1828; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
                <i class="bi bi-key-fill"></i>
            </div>
            <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--tosa-ink-dark); margin: 0 0 0.5rem;">TOSA PIN Recovery</h3>
            <p style="font-size: 0.86rem; color: var(--tosa-ink-muted); line-height: 1.5; margin: 0 0 1.25rem;">
                TOSA security credentials are encrypted on the institutional ledger. If you have misplaced your PIN, please contact the <strong>BSU OSO Directorate</strong> or use the demo PIN <strong>1234</strong>.
            </p>
            <button type="button" class="org-btn org-btn-primary org-btn-sm" style="width: 100%;" onclick="closeForgotPinModal()">
                I Understand
            </button>
        </div>
    </dialog>

    {{-- Toast Container --}}
    <div id="tosaToastContainer" class="tosa-toast-container"></div>

    {{-- =========================================================================
         TOSA JavaScript Engine
         ========================================================================= --}}
    <script>
        // Data Store
        const AUTHORIZED_PINS = ['1234', '2026'];
        const PAGE_SIZE = 7;
        let isTosaUnlocked = false;
        let currentApplicantPage = 1;
        let currentApplicantSearch = '';
        let currentApplicantStatus = '';
        let currentApplicantProgram = '';
        let currentApplicantYear = '';
        let currentApplicantOrg = '';
        let currentReqSearch = '';
        let currentReqStatus = '';
        let activeReviewApplicantId = null;
        let autoLockInterval = null;
        let secondsRemaining = 900; // 15 minutes

        // TOSA Applicants Data Store (Empty State — No Data Yet)
        let tosaApplicants = [];
        let tosaQualifiedApplicants = [];

        // Requirements List (Exact 5 items matching official TOSA requirements)
        let tosaRequirements = [
            { id: 1, title: 'Curriculum Vitae', desc: 'Comprehensive Curriculum Vitae (CV) detailing academic qualifications, leadership experience, and background.', type: 'Document', status: 'Active', required: true, format: 'PDF', iconColor: 'is-blue', iconName: 'bi-person-lines-fill', templateUploaded: true },
            { id: 2, title: 'Good Moral Certificate', desc: 'Certificate of Good Moral Character officially issued by the Office of Student Affairs / Guidance Services.', type: 'Document', status: 'Active', required: true, format: 'PDF', iconColor: 'is-green', iconName: 'bi-shield-check', templateUploaded: true },
            { id: 3, title: 'Scholastic Record or a copy of all grades issued by Registration Services', desc: 'Official Scholastic Record / Certified True Copy of all grades issued by Registration Services.', type: 'Document', status: 'Active', required: true, format: 'PDF', iconColor: 'is-amber', iconName: 'bi-file-earmark-ruled', templateUploaded: true },
            { id: 4, title: 'Copies of certificates, together with proof of legitimacy duly certified by the Records Office', desc: 'Copies of awards, seminar, and leadership certificates, together with proof of legitimacy duly certified by the Records Office.', type: 'Document', status: 'Active', required: true, format: 'PDF', iconColor: 'is-purple', iconName: 'bi-award', templateUploaded: true },
            { id: 5, title: 'Electronic or scanned copies of the complete application documents', desc: 'High-resolution electronic or scanned copies of the complete application documents and supporting attachments.', type: 'Document', status: 'Active', required: true, format: 'PDF', iconColor: 'is-teal', iconName: 'bi-folder-check', templateUploaded: true }
        ];

        // Activity Log Store (Tamper-Evident Immutable Audit Trail)
        let tosaActivityLogs = [
            { id: 1, time: 'Just now', date: 'May 20, 2025', actor: 'OSO Review Officer', role: 'Lead Reviewer', category: 'Security', action: 'Session Clearance Unlocked', target: 'PIN Security Gateway Node #01', hash: '8f4a21e019b52a7732fa89b2110c9e78a487cb10' },
            { id: 2, time: '15 mins ago', date: 'May 20, 2025', actor: 'System Integrity', role: 'Ledger Daemon', category: 'System', action: 'Synchronized Master Ledger', target: 'Zero Candidate Dossiers in Queue', hash: 'a49c0133df87612cb78912ef3456789012abcdef' },
            { id: 3, time: '1 hr ago', date: 'May 20, 2025', actor: 'Student Affairs Directorate', role: 'Executive Desk', category: 'Requirement', action: 'Updated Format Requirements', target: 'PDF & DOCX Standard Enforced', hash: '5c8192ab47de10293847561029384756bcde0123' },
            { id: 4, time: 'Yesterday 03:40 PM', date: 'May 19, 2025', actor: 'Student Affairs Directorate', role: 'Executive Desk', category: 'Requirement', action: 'Attached Official Template', target: 'Personal Essay Guidelines.docx', hash: '4567890123abcdef8901234567abcdef12345678' },
            { id: 5, time: 'Yesterday 11:20 AM', date: 'May 19, 2025', actor: 'System Integrity', role: 'Ledger Daemon', category: 'Security', action: 'Automated Cryptographic Seal', target: 'SHA-256 Merkle Root Verified', hash: '1234567890abcdefabcdef1234567890abcdef12' },
            { id: 6, time: 'May 18, 2025', date: 'May 18, 2025', actor: 'Student Affairs Directorate', role: 'Executive Desk', category: 'Requirement', action: 'Requirement Criteria Modified', target: 'Recommendation Letter (Faculty/Org)', hash: '3456789012abcdefabcdef901234567812345678' },
            { id: 7, time: 'May 17, 2025', date: 'May 17, 2025', actor: 'System Integrity', role: 'Ledger Daemon', category: 'System', action: 'Integrity Check Completed', target: 'Zero Tampering Detected (100% OK)', hash: 'abcdef90123456781234567890abcdef12345678' },
            { id: 8, time: 'May 15, 2025', date: 'May 15, 2025', actor: 'Student Affairs Directorate', role: 'Executive Desk', category: 'Requirement', action: 'Attached Official Template', target: 'Application Form Template v2.pdf', hash: '8901234567abcdefabcdef567890123412345678' },
            { id: 9, time: 'May 13, 2025', date: 'May 13, 2025', actor: 'Student Affairs Directorate', role: 'Executive Desk', category: 'Requirement', action: 'Initialized TOSA AY 26-27 Matrix', target: '5 Required Criteria Established', hash: '9012345678abcdefabcdef123456789034567890' },
            { id: 10, time: 'May 12, 2025', date: 'May 12, 2025', actor: 'System Integrity', role: 'Ledger Daemon', category: 'Security', action: 'Genesis Block Created', target: 'TOSA AY 2026–2027 Ledger Initialized', hash: '0000000000abcdef1234567890abcdefabcdef01' }
        ];

        // -------------------------------------------------------------------------
        // PIN Verification Handlers
        // -------------------------------------------------------------------------
        function onPinDigitInput(el, boxNum) {
            if (el.value.length === 1 && boxNum < 4) {
                const nextBox = document.getElementById(`pinBox${boxNum + 1}`);
                if (nextBox) nextBox.focus();
            }

            // Auto-submit if 4 digits entered
            const pin = getEnteredPin();
            if (pin.length === 4) {
                validatePin(pin);
            }
        }

        function onPinKeyDown(e, boxNum) {
            if (e.key === 'Backspace' && !e.target.value && boxNum > 1) {
                const prevBox = document.getElementById(`pinBox${boxNum - 1}`);
                if (prevBox) {
                    prevBox.focus();
                    prevBox.value = '';
                }
            }
        }

        function getEnteredPin() {
            return [1, 2, 3, 4].map(num => (document.getElementById(`pinBox${num}`)?.value || '')).join('');
        }

        function handlePinSubmit(e) {
            e.preventDefault();
            const pin = getEnteredPin();
            validatePin(pin);
        }

        function validatePin(pin) {
            if (AUTHORIZED_PINS.includes(pin)) {
                unlockTosaSession();
            } else {
                const alertEl = document.getElementById('tosaPinAlert');
                if (alertEl) {
                    alertEl.style.display = 'flex';
                    document.getElementById('tosaPinAlertText').textContent = 'Invalid Security PIN. Please try again.';
                }
                // Clear inputs
                [1, 2, 3, 4].forEach(num => {
                    const box = document.getElementById(`pinBox${num}`);
                    if (box) box.value = '';
                });
                document.getElementById('pinBox1')?.focus();
            }
        }

        function unlockTosaSession() {
            isTosaUnlocked = true;
            sessionStorage.setItem('tosa_unlocked', 'true');

            document.getElementById('tosaPinScreen').style.display = 'none';
            document.getElementById('tosaWorkspace').style.display = 'flex';
            document.getElementById('tosaUnlockedActions').style.display = 'flex';

            const badge = document.getElementById('tosaHeaderBadge');
            if (badge) {
                badge.className = 'tosa-badge-unlocked';
                badge.innerHTML = '<i class="bi bi-shield-check"></i> Session Unlocked';
            }

            renderAllTosaData();
            startAutoLockTimer();
            showTosaToast('TOSA Module successfully unlocked! Authorized OSO Reviewer session active.', 'success');
        }

        function lockTosaSession() {
            isTosaUnlocked = false;
            sessionStorage.removeItem('tosa_unlocked');

            document.getElementById('tosaPinScreen').style.display = 'flex';
            document.getElementById('tosaWorkspace').style.display = 'none';
            document.getElementById('tosaUnlockedActions').style.display = 'none';

            const badge = document.getElementById('tosaHeaderBadge');
            if (badge) {
                badge.className = 'tosa-badge-locked';
                badge.innerHTML = '<i class="bi bi-shield-lock-fill"></i> Restricted Access';
            }

            // Clear inputs
            [1, 2, 3, 4].forEach(num => {
                const box = document.getElementById(`pinBox${num}`);
                if (box) box.value = '';
            });
            document.getElementById('tosaPinAlert').style.display = 'none';
            document.getElementById('pinBox1')?.focus();

            if (autoLockInterval) clearInterval(autoLockInterval);
            showTosaToast('TOSA Module locked.', 'info');
        }

        function startAutoLockTimer() {
            if (autoLockInterval) clearInterval(autoLockInterval);
            secondsRemaining = 900; // 15 mins

            autoLockInterval = setInterval(() => {
                secondsRemaining--;
                const mins = Math.floor(secondsRemaining / 60);
                const secs = secondsRemaining % 60;
                const timerText = document.getElementById('tosaTimerText');
                if (timerText) {
                    timerText.textContent = `Auto-lock in ${mins}:${secs < 10 ? '0' : ''}${secs}`;
                }

                if (secondsRemaining <= 0) {
                    clearInterval(autoLockInterval);
                    lockTosaSession();
                    showTosaToast('TOSA session auto-locked due to inactivity.', 'warning');
                }
            }, 1000);
        }

        // -------------------------------------------------------------------------
        // Tabs Switching
        // -------------------------------------------------------------------------
        function switchTosaTab(tabId) {
            const tabs = ['overview', 'requirements', 'applicants', 'queue', 'log'];
            tabs.forEach(t => {
                const pane = document.getElementById(`tosaTabSection${t.charAt(0).toUpperCase() + t.slice(1)}`);
                const btn = document.getElementById(`tabBtn${t.charAt(0).toUpperCase() + t.slice(1)}`);
                if (pane) pane.style.display = t === tabId ? 'block' : 'none';
                if (btn) btn.classList.toggle('is-active', t === tabId);
            });

            if (tabId === 'overview') {
                updateKpiCounters();
            } else if (tabId === 'applicants') {
                currentApplicantStatus = '';
                currentApplicantProgram = '';
                currentApplicantYear = '';
                currentApplicantSearch = '';
                if (document.getElementById('tosaStatusFilter')) document.getElementById('tosaStatusFilter').value = '';
                if (document.getElementById('tosaProgramFilter')) document.getElementById('tosaProgramFilter').value = '';
                if (document.getElementById('tosaYearFilter')) document.getElementById('tosaYearFilter').value = '';
                if (document.getElementById('tosaApplicantSearch')) document.getElementById('tosaApplicantSearch').value = '';
                renderApplicantsTable();
            } else if (tabId === 'requirements') {
                renderRequirementsTable();
            } else if (tabId === 'queue') {
                renderQueueTable();
            } else if (tabId === 'log') {
                renderActivityLogTable();
            }
        }

        // -------------------------------------------------------------------------
        // Data Rendering Engines
        // -------------------------------------------------------------------------
        function renderAllTosaData() {
            updateKpiCounters();
            renderApplicantsTable();
            renderRequirementsTable();
            renderQueueTable();
            renderActivityLogTable();
        }

        function updateKpiCounters() {
            const total = tosaApplicants.length;
            const complete = tosaApplicants.filter(a => a.submitted === a.total).length;
            const missing = tosaApplicants.filter(a => a.missing > 0).length;
            const pending = tosaApplicants.filter(a => a.status === 'Under Review').length;
            const inQueue = tosaApplicants.filter(a => a.status === 'Under Review' || a.status === 'Missing Documents' || a.status === 'Returned').length;
            const totalLogs = tosaActivityLogs.length;

            // Overview Master Top KPIs
            if (document.getElementById('ovKpiTotalApplicants')) document.getElementById('ovKpiTotalApplicants').textContent = total;
            if (document.getElementById('ovKpiRequirements')) document.getElementById('ovKpiRequirements').textContent = tosaRequirements.length;
            if (document.getElementById('ovKpiReviewQueue')) document.getElementById('ovKpiReviewQueue').textContent = inQueue;
            if (document.getElementById('ovKpiAuditedEvents')) document.getElementById('ovKpiAuditedEvents').textContent = totalLogs;

            // Overview Funnel & Requirements Highlights
            if (document.getElementById('ovStatComplete')) document.getElementById('ovStatComplete').textContent = complete;
            if (document.getElementById('ovStatMissing')) document.getElementById('ovStatMissing').textContent = missing;
            if (document.getElementById('ovStatReview')) document.getElementById('ovStatReview').textContent = pending;

            const requiredCount = tosaRequirements.filter(r => r.required).length;
            const optionalCount = tosaRequirements.length - requiredCount;
            const templateCount = tosaRequirements.filter(r => r.templateUploaded).length;

            if (document.getElementById('ovReqMandatory')) document.getElementById('ovReqMandatory').textContent = `${requiredCount} Required`;
            if (document.getElementById('ovReqTemplates')) document.getElementById('ovReqTemplates').textContent = `${templateCount} Attached`;

            // Applicant Submissions Sub-KPIs (Exact Mockup Match)
            if (document.getElementById('subKpiTotalApplicants')) document.getElementById('subKpiTotalApplicants').textContent = total;
            if (document.getElementById('subKpiComplete')) document.getElementById('subKpiComplete').textContent = complete;
            if (document.getElementById('subKpiMissing')) document.getElementById('subKpiMissing').textContent = missing;
            if (document.getElementById('subKpiPending')) document.getElementById('subKpiPending').textContent = pending;

            if (document.getElementById('badgeAppCount')) document.getElementById('badgeAppCount').textContent = total;
            if (document.getElementById('badgeQueueCount')) document.getElementById('badgeQueueCount').textContent = inQueue;
            if (document.getElementById('badgeReqCount')) document.getElementById('badgeReqCount').textContent = tosaRequirements.length;

            // Requirements Tab KPIs
            const totalReq = tosaRequirements.length;
            const elTotal = document.getElementById('reqTotalCounter');
            const elReq = document.getElementById('reqRequiredCounter');
            const elOpt = document.getElementById('reqOptionalCounter');
            const elTemp = document.getElementById('reqTemplatesCounter');

            if (elTotal) elTotal.textContent = totalReq;
            if (elReq) elReq.textContent = requiredCount;
            if (elOpt) elOpt.textContent = optionalCount;
            if (elTemp) elTemp.textContent = templateCount;

            // Render Overview Recent Activity Stream
            renderOverviewActivityStream();
        }

        function renderOverviewActivityStream() {
            const streamContainer = document.getElementById('ovRecentActivityStream');
            if (!streamContainer) return;

            const recentLogs = tosaActivityLogs.slice(0, 4);
            streamContainer.innerHTML = recentLogs.map(log => {
                let dotIcon = 'bi-journal-check';
                let dotBg = '#fdf0f2';
                let dotColor = '#8b1828';

                if (log.category === 'Approval') {
                    dotIcon = 'bi-check-circle-fill';
                    dotBg = '#dcfce7';
                    dotColor = '#16a34a';
                } else if (log.category === 'Revision') {
                    dotIcon = 'bi-arrow-repeat';
                    dotBg = '#ffedd5';
                    dotColor = '#ea580c';
                } else if (log.category === 'Requirement') {
                    dotIcon = 'bi-card-checklist';
                    dotBg = '#f3e8ff';
                    dotColor = '#7e22ce';
                } else if (log.category === 'Security') {
                    dotIcon = 'bi-shield-check';
                    dotBg = '#e0f2fe';
                    dotColor = '#0284c7';
                }

                return `
                    <div class="tosa-ov-feed-item">
                        <div class="tosa-ov-feed-dot" style="background: ${dotBg}; color: ${dotColor};">
                            <i class="bi ${dotIcon}"></i>
                        </div>
                        <div class="tosa-ov-feed-content">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.15rem;">
                                <strong style="font-size: 0.84rem; color: #1a1618;">${escapeHtml(log.action)}</strong>
                                <span style="font-size: 0.72rem; color: #64748b; font-weight: 600;">${escapeHtml(log.time)}</span>
                            </div>
                            <div style="font-size: 0.78rem; color: #475569; margin-bottom: 0.25rem;">
                                ${escapeHtml(log.target)} • <span style="font-weight: 600; color: #1a1618;">${escapeHtml(log.actor)}</span>
                            </div>
                            <div>
                                <span class="tosa-log-hash-badge" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;" onclick="copyLogHash('${log.hash}')" title="Click to copy hash">
                                    <i class="bi bi-hash"></i> ${escapeHtml(log.hash.substring(0, 16))}...
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderApplicantsTable() {
            const tbody = document.getElementById('tosaApplicantsTbody');
            if (!tbody) return;

            const filtered = tosaApplicants.filter(a => {
                const searchLower = currentApplicantSearch;
                const matchSearch = !searchLower || 
                    a.name.toLowerCase().includes(searchLower) || 
                    (a.studentId && a.studentId.toLowerCase().includes(searchLower)) || 
                    (a.program && a.program.toLowerCase().includes(searchLower)) ||
                    (a.yearLevel && a.yearLevel.toLowerCase().includes(searchLower));
                
                const matchStatus = !currentApplicantStatus || a.status === currentApplicantStatus;
                const matchProgram = !currentApplicantProgram || a.program === currentApplicantProgram;
                const matchYear = !currentApplicantYear || a.yearLevel === currentApplicantYear;
                return matchSearch && matchStatus && matchProgram && matchYear;
            });

            const totalFiltered = filtered.length;
            const totalPages = Math.ceil(totalFiltered / PAGE_SIZE) || 1;
            if (currentApplicantPage > totalPages) currentApplicantPage = totalPages;

            const startIdx = (currentApplicantPage - 1) * PAGE_SIZE;
            const endIdx = Math.min(startIdx + PAGE_SIZE, totalFiltered);
            const pageItems = filtered.slice(startIdx, endIdx);

            if (pageItems.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                            <i class="bi bi-people" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                            <strong>No TOSA Applicants Found</strong>
                            <p style="margin: 0.2rem 0 0; font-size: 0.8rem;">Try clearing your search query or dropdown filters.</p>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = pageItems.map((a, index) => {
                    const globalIndex = startIdx + index + 1;

                    // Submitted cell format
                    let submittedHtml = '';
                    if (a.submitted === a.total) {
                        submittedHtml = `
                            <div class="tosa-sub-progress-meta">
                                <i class="bi bi-check-circle-fill" style="color: #16a34a; font-size: 1.15rem;"></i>
                                <div>
                                    <strong style="font-size: 0.88rem; color: #1e293b;">${a.submitted} / ${a.total}</strong>
                                    <div style="font-size: 0.74rem; color: #64748b;">Complete</div>
                                </div>
                            </div>
                        `;
                    } else {
                        submittedHtml = `
                            <div class="tosa-sub-progress-meta">
                                <div style="width: 20px; height: 20px; border-radius: 50%; background: #ea580c; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; flex-shrink: 0;">
                                    <i class="bi bi-arrow-up-short"></i>
                                </div>
                                <div>
                                    <strong style="font-size: 0.88rem; color: #1e293b;">${a.submitted} / ${a.total}</strong>
                                    <div style="font-size: 0.74rem; color: #64748b;">Incomplete</div>
                                </div>
                            </div>
                        `;
                    }

                    // Missing cell format
                    let missingHtml = '';
                    if (a.missing === 0) {
                        missingHtml = `
                            <div class="tosa-sub-progress-meta">
                                <i class="bi bi-check-circle-fill" style="color: #16a34a; font-size: 1.15rem;"></i>
                                <strong style="font-size: 0.86rem; color: #1e293b;">None</strong>
                            </div>
                        `;
                    } else {
                        const docText = a.missing === 1 ? 'Document' : 'Documents';
                        missingHtml = `
                            <div class="tosa-sub-progress-meta">
                                <div style="width: 20px; height: 20px; border-radius: 50%; background: #dc2626; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; flex-shrink: 0;">
                                    <i class="bi bi-arrow-down-short"></i>
                                </div>
                                <div>
                                    <strong style="font-size: 0.86rem; color: #1e293b;">${a.missing}</strong>
                                    <div style="font-size: 0.74rem; color: #64748b;">${docText}</div>
                                </div>
                            </div>
                        `;
                    }

                    // Status pill class
                    let statusClass = 'is-complete';
                    if (a.status === 'Missing Documents') statusClass = 'is-missing';
                    else if (a.status === 'Under Review') statusClass = 'is-review';
                    else if (a.status === 'Approved') statusClass = 'is-approved';
                    else if (a.status === 'Returned') statusClass = 'is-returned';
                    else if (a.status === 'Rejected') statusClass = 'is-rejected';

                    return `
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #1e293b;">${globalIndex}</td>
                            <td>
                                <div class="tosa-applicant-cell">
                                    <div class="tosa-sub-avatar-circle">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <div class="tosa-sub-app-name">${escapeHtml(a.name)}</div>
                                        <div class="tosa-sub-app-id">${escapeHtml(a.studentId || a.sr || '')}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="tosa-prog-name">${escapeHtml(a.program || '')}</div>
                                <div class="tosa-prog-year">${escapeHtml(a.yearLevel || '')}</div>
                            </td>
                            <td>${submittedHtml}</td>
                            <td>${missingHtml}</td>
                            <td style="text-align: center;">
                                <span class="tosa-status-pill ${statusClass}">${escapeHtml(a.status)}</span>
                            </td>
                            <td style="text-align: center;">
                                <div style="font-weight: 600; font-size: 0.82rem; color: #1e293b;">${escapeHtml(a.date)}</div>
                                <div style="font-size: 0.76rem; color: #64748b;">${escapeHtml(a.time || '')}</div>
                            </td>
                            <td style="text-align: center;">
                                <div class="tosa-sub-actions-cell">
                                    <button type="button" class="tosa-sub-btn is-view" onclick="viewApplicantFiles(${a.id})" title="View Submitted Files">
                                        <i class="bi bi-eye"></i>
                                        <span>View Files</span>
                                    </button>
                                    <button type="button" class="tosa-sub-btn is-download" onclick="downloadApplicantFiles(${a.id})" title="Download All Documents">
                                        <i class="bi bi-download"></i>
                                        <span>Download All</span>
                                    </button>
                                    <button type="button" class="tosa-sub-btn is-return" onclick="returnApplicantSubmission(${a.id})" title="Return for Revision">
                                        <i class="bi bi-arrow-return-left"></i>
                                        <span>Return</span>
                                    </button>
                                    <button type="button" class="tosa-sub-btn is-reject" onclick="rejectApplicantSubmission(${a.id})" title="Reject Submission">
                                        <i class="bi bi-x-circle"></i>
                                        <span>Reject</span>
                                    </button>
                                    <button type="button" class="tosa-sub-btn is-approve" onclick="approveApplicantSubmission(${a.id})" title="Approve Submission">
                                        <i class="bi bi-check-circle"></i>
                                        <span>Approve</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            // Render Pagination Bar
            const paginationBar = document.getElementById('tosaPagination');
            const paginationInfo = document.getElementById('tosaPaginationInfo');
            const paginationNav = document.getElementById('tosaPaginationNav');

            if (paginationBar && paginationInfo && paginationNav) {
                if (totalFiltered > 0) {
                    paginationBar.style.display = 'flex';
                    paginationInfo.innerHTML = `Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${totalFiltered}</strong> entries`;

                    let navHtml = '';
                    navHtml += `<button type="button" class="tosa-page-btn" ${currentApplicantPage === 1 ? 'disabled' : ''} onclick="goToApplicantPage(${currentApplicantPage - 1})"><i class="bi bi-chevron-left"></i></button>`;

                    for (let p = 1; p <= totalPages; p++) {
                        if (totalPages <= 7 || p === 1 || p === totalPages || (p >= currentApplicantPage - 1 && p <= currentApplicantPage + 1)) {
                            navHtml += `<button type="button" class="tosa-page-btn ${p === currentApplicantPage ? 'is-active' : ''}" onclick="goToApplicantPage(${p})">${p}</button>`;
                        } else if (p === currentApplicantPage - 2 || p === currentApplicantPage + 2) {
                            navHtml += `<span style="padding: 0 0.35rem; color: #94a3b8; font-weight: 700;">&hellip;</span>`;
                        }
                    }

                    navHtml += `<button type="button" class="tosa-page-btn ${currentApplicantPage === totalPages ? 'disabled' : ''} onclick="goToApplicantPage(${currentApplicantPage + 1})"><i class="bi bi-chevron-right"></i></button>`;
                    paginationNav.innerHTML = navHtml;
                } else {
                    paginationBar.style.display = 'none';
                }
            }
        }

        function goToApplicantPage(p) {
            currentApplicantPage = p;
            renderApplicantsTable();
        }

        function handleApplicantSearch(val) {
            currentApplicantSearch = val.toLowerCase().trim();
            currentApplicantPage = 1;
            renderApplicantsTable();
        }

        function handleStatusFilterChange(val) {
            currentApplicantStatus = val;
            currentApplicantPage = 1;
            renderApplicantsTable();
        }

        function handleProgramFilterChange(val) {
            currentApplicantProgram = val;
            currentApplicantPage = 1;
            renderApplicantsTable();
        }

        function handleYearFilterChange(val) {
            currentApplicantYear = val;
            currentApplicantPage = 1;
            renderApplicantsTable();
        }

        function filterByStatusFromKpi(status) {
            currentApplicantStatus = status;
            if (document.getElementById('tosaStatusFilter')) {
                document.getElementById('tosaStatusFilter').value = status;
            }
            currentApplicantPage = 1;
            switchTosaTab('applicants');
            renderApplicantsTable();
        }

        // -------------------------------------------------------------------------
        // Application Submissions Action Handlers
        // -------------------------------------------------------------------------
        function viewApplicantFiles(id) {
            openReviewModal(id);
        }

        function downloadApplicantFiles(id) {
            const applicant = tosaApplicants.find(a => a.id === id);
            if (!applicant) return;
            showTosaToast(`Downloading all submitted documents for ${applicant.name}... (ZIP package)`, 'success');
        }

        function returnApplicantSubmission(id) {
            const applicant = tosaApplicants.find(a => a.id === id);
            if (!applicant) return;
            applicant.status = 'Returned';
            renderApplicantsTable();
            updateKpiCounters();
            showTosaToast(`Submission returned to ${applicant.name} for required revisions.`, 'warning');
        }

        function rejectApplicantSubmission(id) {
            const applicant = tosaApplicants.find(a => a.id === id);
            if (!applicant) return;
            applicant.status = 'Rejected';
            renderApplicantsTable();
            updateKpiCounters();
            showTosaToast(`Submission for ${applicant.name} has been rejected.`, 'error');
        }

        function approveApplicantSubmission(id) {
            const applicant = tosaApplicants.find(a => a.id === id);
            if (!applicant) return;
            applicant.status = 'Approved';
            renderApplicantsTable();
            updateKpiCounters();
            showTosaToast(`Submission for ${applicant.name} successfully approved!`, 'success');
        }

        function exportApplicantReport() {
            showTosaToast('Exporting complete TOSA Application Submissions report (PDF & Excel format)...', 'info');
        }

        // -------------------------------------------------------------------------
        // Requirements Management (Matching Mockup Engine)
        // -------------------------------------------------------------------------
        function renderRequirementsTable() {
            const tbody = document.getElementById('tosaRequirementsTbody');
            if (!tbody) return;

            const filtered = tosaRequirements.filter(req => {
                const matchSearch = !currentReqSearch || 
                    req.title.toLowerCase().includes(currentReqSearch) || 
                    req.desc.toLowerCase().includes(currentReqSearch);
                const matchStatus = !currentReqStatus || req.status === currentReqStatus;
                return matchSearch && matchStatus;
            });

            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                            <i class="bi bi-file-earmark-x" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                            <strong>No Requirements Found</strong>
                            <p style="margin: 0.2rem 0 0; font-size: 0.8rem;">Try clearing your search query or filter.</p>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = filtered.map((req, index) => {
                    const typeBadge = req.type === 'Form' 
                        ? `<span class="tosa-type-badge is-form">Form</span>`
                        : `<span class="tosa-type-badge is-doc">Document</span>`;
                    
                    const statusBadge = req.status === 'Active'
                        ? `<span class="tosa-status-badge is-active"><i class="bi bi-circle-fill" style="font-size: 0.4rem;"></i> Active</span>`
                        : `<span class="tosa-status-badge is-inactive"><i class="bi bi-circle-fill" style="font-size: 0.4rem;"></i> Inactive</span>`;

                    const switchChecked = req.required ? 'checked' : '';

                    return `
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #64748b;">${index + 1}</td>
                            <td>
                                <div class="tosa-req-item-cell">
                                    <div class="tosa-req-icon-badge ${req.iconColor}">
                                        <i class="bi ${req.iconName}"></i>
                                    </div>
                                    <div>
                                        <div class="tosa-req-item-title">${escapeHtml(req.title)}</div>
                                        <div class="tosa-req-item-desc">${escapeHtml(req.desc)}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">${typeBadge}</td>
                            <td style="text-align: center;">${statusBadge}</td>
                            <td style="text-align: center;">
                                <label class="tosa-switch" style="margin: 0 auto; display: inline-block;">
                                    <input type="checkbox" ${switchChecked} onchange="toggleReqRequired(${req.id}, this.checked)">
                                    <span class="tosa-slider"></span>
                                </label>
                            </td>
                            <td style="text-align: center; font-weight: 700; color: #475569; font-size: 0.82rem;">${escapeHtml(req.format)}</td>
                            <td style="text-align: center;">
                                <div class="tosa-row-actions" style="justify-content: center;">
                                    <button type="button" class="tosa-action-btn" title="Edit Requirement" onclick="openEditRequirementModal(${req.id})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="tosa-action-btn" title="Upload Template / Sample" onclick="openUploadTemplateModal(${req.id})">
                                        <i class="bi bi-upload"></i>
                                    </button>
                                    <button type="button" class="tosa-action-btn is-delete" title="Delete Requirement" onclick="deleteRequirement(${req.id})">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                    <span class="tosa-action-btn is-drag" title="Reorder">
                                        <i class="bi bi-list"></i>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            const entriesInfo = document.getElementById('reqEntriesInfo');
            if (entriesInfo) {
                entriesInfo.textContent = `Showing 1 to ${filtered.length} of ${filtered.length} entries`;
            }

            updateKpiCounters();
        }

        function handleReqSearch(val) {
            currentReqSearch = val.toLowerCase().trim();
            renderRequirementsTable();
        }

        function handleReqStatusFilter(val) {
            currentReqStatus = val;
            renderRequirementsTable();
        }

        function toggleReqRequired(id, isRequired) {
            const req = tosaRequirements.find(r => r.id === id);
            if (!req) return;
            req.required = isRequired;
            updateKpiCounters();
            showTosaToast(`"${req.title}" marked as ${isRequired ? 'Required' : 'Optional'}.`, 'info');
        }

        function toggleBulkDropdown() {
            const menu = document.getElementById('bulkDropdownMenu');
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            }
        }

        // Close bulk dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.tosa-btn-bulk') && !e.target.closest('#bulkDropdownMenu')) {
                const menu = document.getElementById('bulkDropdownMenu');
                if (menu) menu.style.display = 'none';
            }
        });

        function bulkSetStatus(status) {
            tosaRequirements.forEach(r => r.status = status);
            renderRequirementsTable();
            toggleBulkDropdown();
            showTosaToast(`All requirements marked as ${status}.`, 'success');
        }

        function bulkExportTemplates() {
            toggleBulkDropdown();
            showTosaToast('Exporting complete TOSA requirement package (.ZIP)...', 'success');
        }

        function openAddRequirementModal() {
            document.getElementById('editReqId').value = '';
            document.getElementById('modalReqTitle').innerHTML = '<i class="bi bi-plus-circle-fill" style="color: #8b1828;"></i> Add New Requirement';
            document.getElementById('newReqTitle').value = '';
            document.getElementById('newReqDesc').value = '';
            document.getElementById('newReqType').value = 'Document';
            document.getElementById('newReqFormat').value = 'PDF';
            document.getElementById('newReqRequiredCheck').checked = true;
            document.getElementById('addRequirementModal')?.showModal();
        }

        function openEditRequirementModal(id) {
            const req = tosaRequirements.find(r => r.id === id);
            if (!req) return;

            document.getElementById('editReqId').value = req.id;
            document.getElementById('modalReqTitle').innerHTML = '<i class="bi bi-pencil-square" style="color: #8b1828;"></i> Edit Requirement';
            document.getElementById('newReqTitle').value = req.title;
            document.getElementById('newReqDesc').value = req.desc;
            document.getElementById('newReqType').value = req.type;
            document.getElementById('newReqFormat').value = req.format;
            document.getElementById('newReqRequiredCheck').checked = req.required;
            document.getElementById('addRequirementModal')?.showModal();
        }

        function closeAddRequirementModal() {
            document.getElementById('addRequirementModal')?.close();
        }

        function handleAddRequirementSubmit(e) {
            e.preventDefault();
            const editId = document.getElementById('editReqId').value;
            const title = document.getElementById('newReqTitle').value.trim();
            const desc = document.getElementById('newReqDesc').value.trim() || 'Official TOSA document requirement.';
            const type = document.getElementById('newReqType').value;
            const format = document.getElementById('newReqFormat').value;
            const required = document.getElementById('newReqRequiredCheck').checked;

            if (!title) return;

            if (editId) {
                // Edit existing
                const req = tosaRequirements.find(r => r.id == editId);
                if (req) {
                    req.title = title;
                    req.desc = desc;
                    req.type = type;
                    req.format = format;
                    req.required = required;
                    showTosaToast(`Requirement "${title}" updated successfully!`, 'success');
                }
            } else {
                // Add new
                const colorMap = ['is-blue', 'is-amber', 'is-green', 'is-purple', 'is-orange', 'is-rose', 'is-teal'];
                const iconMap = ['bi-file-earmark-text', 'bi-file-earmark-check', 'bi-award', 'bi-person-badge', 'bi-heart'];
                
                const newReq = {
                    id: Date.now(),
                    title: title,
                    desc: desc,
                    type: type,
                    status: 'Active',
                    required: required,
                    format: format,
                    iconColor: colorMap[tosaRequirements.length % colorMap.length],
                    iconName: type === 'Form' ? 'bi-file-earmark-text' : iconMap[tosaRequirements.length % iconMap.length],
                    templateUploaded: true
                };
                tosaRequirements.push(newReq);

                // Add activity log
                tosaActivityLogs.unshift({
                    id: Date.now(),
                    time: 'Just now',
                    date: 'May 20, 2025',
                    actor: 'Student Affairs Directorate',
                    role: 'Executive Desk',
                    category: 'Requirement',
                    action: 'Added New Requirement',
                    target: title,
                    hash: Math.random().toString(16).substring(2, 10) + Math.random().toString(16).substring(2, 10) + Math.random().toString(16).substring(2, 10) + 'f902'
                });
                renderActivityLogTable();
                updateKpiCounters();
                showTosaToast(`Requirement "${title}" added successfully!`, 'success');
            }

            renderRequirementsTable();
            closeAddRequirementModal();
        }

        function deleteRequirement(id) {
            const req = tosaRequirements.find(r => r.id === id);
            if (!req) return;
            if (confirm(`Are you sure you want to delete the requirement "${req.title}"?`)) {
                tosaRequirements = tosaRequirements.filter(r => r.id !== id);
                renderRequirementsTable();
                showTosaToast(`Requirement "${req.title}" deleted.`, 'info');
            }
        }

        function openUploadTemplateModal(id) {
            const req = tosaRequirements.find(r => r.id === id);
            if (!req) return;
            document.getElementById('uploadReqId').value = req.id;
            document.getElementById('uploadReqNameLabel').innerHTML = `Select a downloadable sample/template file for <strong>${escapeHtml(req.title)}</strong>.`;
            document.getElementById('selectedFileName').style.display = 'none';
            document.getElementById('uploadTemplateModal')?.showModal();
        }

        function closeUploadTemplateModal() {
            document.getElementById('uploadTemplateModal')?.close();
        }

        function handleFileSelected(input) {
            if (input.files && input.files[0]) {
                const fn = document.getElementById('selectedFileName');
                fn.textContent = `Selected: ${input.files[0].name} (${(input.files[0].size / 1024 / 1024).toFixed(2)} MB)`;
                fn.style.display = 'block';
            }
        }

        function handleTemplateUploadSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('uploadReqId').value;
            const req = tosaRequirements.find(r => r.id == id);
            if (req) {
                req.templateUploaded = true;
                updateKpiCounters();
                showTosaToast(`Official template uploaded for "${req.title}"!`, 'success');
            }
            closeUploadTemplateModal();
        }

        // -------------------------------------------------------------------------
        // Review Queue & Two-Column Modal (Impeccable & Fast Triage Engine)
        // -------------------------------------------------------------------------
        let currentQueueSearch = '';
        let currentQueueStatus = '';
        let currentQueueProgram = '';
        let currentQueueQuickFilter = '';
        let currentQueuePage = 1;
        const QUEUE_PAGE_SIZE = 7;

        function renderQueueTable() {
            const tbody = document.getElementById('tosaQueueTbody');
            if (!tbody) return;

            // Base queue items (all non-complete/approved)
            const allQueueItems = tosaApplicants.filter(a => a.status === 'Missing Documents' || a.status === 'Returned' || a.status === 'Under Review' || a.status === 'Pending' || a.status === 'For Revision');

            // Update KPI counters for Review Queue
            const totalQ = allQueueItems.length;
            const missingQ = allQueueItems.filter(a => a.status === 'Missing Documents').length;
            const returnedQ = allQueueItems.filter(a => a.status === 'Returned' || a.status === 'For Revision').length;
            const reviewQ = allQueueItems.filter(a => a.status === 'Under Review' || a.status === 'Pending').length;

            if (document.getElementById('queueKpiTotal')) document.getElementById('queueKpiTotal').textContent = totalQ;
            if (document.getElementById('queueKpiMissing')) document.getElementById('queueKpiMissing').textContent = missingQ;
            if (document.getElementById('queueKpiReturned')) document.getElementById('queueKpiReturned').textContent = returnedQ;
            if (document.getElementById('queueKpiUnderReview')) document.getElementById('queueKpiUnderReview').textContent = reviewQ;
            if (document.getElementById('qPillAllCount')) document.getElementById('qPillAllCount').textContent = totalQ;
            if (document.getElementById('badgeQueueCount')) document.getElementById('badgeQueueCount').textContent = totalQ;

            // Filter
            const filtered = allQueueItems.filter(a => {
                const searchLower = currentQueueSearch;
                const matchSearch = !searchLower || 
                    a.name.toLowerCase().includes(searchLower) || 
                    (a.studentId && a.studentId.toLowerCase().includes(searchLower)) || 
                    (a.program && a.program.toLowerCase().includes(searchLower));

                const matchStatus = !currentQueueStatus || a.status === currentQueueStatus;
                const matchProgram = !currentQueueProgram || a.program === currentQueueProgram;
                const matchQuick = !currentQueueQuickFilter || a.status === currentQueueQuickFilter;

                return matchSearch && matchStatus && matchProgram && matchQuick;
            });

            const totalFiltered = filtered.length;
            const totalPages = Math.ceil(totalFiltered / QUEUE_PAGE_SIZE) || 1;
            if (currentQueuePage > totalPages) currentQueuePage = totalPages;

            const startIdx = (currentQueuePage - 1) * QUEUE_PAGE_SIZE;
            const endIdx = Math.min(startIdx + QUEUE_PAGE_SIZE, totalFiltered);
            const pageItems = filtered.slice(startIdx, endIdx);

            if (pageItems.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem 1.5rem; color: #94a3b8;">
                            <div style="width: 56px; height: 56px; border-radius: 50%; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 0.75rem;">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <strong style="font-size: 1rem; color: #1e293b; display: block; margin-bottom: 0.25rem;">All Queue Items Cleared!</strong>
                            <p style="margin: 0; font-size: 0.82rem;">There are no candidate submissions matching your search criteria in the active review queue.</p>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = pageItems.map((a, index) => {
                    const globalIndex = startIdx + index + 1;

                    // Priority & Flags
                    let priorityBadge = `<span class="tosa-queue-priority-badge is-normal">NORMAL</span>`;
                    let flagHtml = `<span class="tosa-queue-flag is-review"><i class="bi bi-hourglass-split"></i> Initial Review</span>`;

                    if (a.status === 'Missing Documents') {
                        priorityBadge = `<span class="tosa-queue-priority-badge is-urgent">URGENT</span>`;
                        const docCountText = a.missing === 1 ? '1 Document' : `${a.missing} Documents`;
                        flagHtml = `<span class="tosa-queue-flag is-missing"><i class="bi bi-exclamation-circle-fill"></i> Missing ${docCountText}</span>`;
                    } else if (a.status === 'Returned' || a.status === 'For Revision') {
                        priorityBadge = `<span class="tosa-queue-priority-badge is-urgent">ACTION</span>`;
                        flagHtml = `<span class="tosa-queue-flag is-returned"><i class="bi bi-arrow-repeat"></i> Returned / Revision</span>`;
                    } else if (a.status === 'Under Review' || a.status === 'Pending') {
                        priorityBadge = `<span class="tosa-queue-priority-badge is-normal">IN REVIEW</span>`;
                        flagHtml = `<span class="tosa-queue-flag is-review"><i class="bi bi-shield-check"></i> Verification Ready</span>`;
                    }

                    // Progress
                    const submittedHtml = `
                        <div class="tosa-sub-progress-meta">
                            <strong style="font-size: 0.86rem; color: #1e293b;">${a.submitted} / ${a.total}</strong>
                            <span style="font-size: 0.74rem; color: #64748b;">${a.submitted === a.total ? 'Complete' : 'Incomplete'}</span>
                        </div>
                    `;

                    return `
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #1e293b;">${globalIndex}</td>
                            <td style="text-align: center;">${priorityBadge}</td>
                            <td>
                                <div class="tosa-applicant-cell">
                                    <div class="tosa-sub-avatar-circle">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <div class="tosa-sub-app-name">${escapeHtml(a.name)}</div>
                                        <div class="tosa-sub-app-id">${escapeHtml(a.studentId || a.sr || '')}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="tosa-prog-name">${escapeHtml(a.program || '')}</div>
                                <div class="tosa-prog-year">${escapeHtml(a.yearLevel || '')}</div>
                            </td>
                            <td>${flagHtml}</td>
                            <td>${submittedHtml}</td>
                            <td style="text-align: center;">
                                <div style="font-weight: 600; font-size: 0.82rem; color: #1e293b;">${escapeHtml(a.date)}</div>
                                <div style="font-size: 0.74rem; color: #64748b;">${escapeHtml(a.time || '10:00 AM')}</div>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; align-items: center; gap: 0.45rem; justify-content: center;">
                                    <button type="button" class="tosa-queue-btn-action" onclick="openReviewModal(${a.id})" title="Review Applicant Dossier">
                                        <i class="bi bi-search"></i> Review
                                    </button>
                                    <button type="button" class="tosa-sub-btn is-return" onclick="returnApplicantSubmission(${a.id})" style="width: 42px; min-width: 42px; height: 36px; min-height: 36px;" title="Return for Revision">
                                        <i class="bi bi-arrow-return-left" style="font-size: 0.95rem;"></i>
                                    </button>
                                    <button type="button" class="tosa-sub-btn is-approve" onclick="approveApplicantSubmission(${a.id})" style="width: 42px; min-width: 42px; height: 36px; min-height: 36px;" title="Fast Approve">
                                        <i class="bi bi-check-circle" style="font-size: 0.95rem;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            // Queue Pagination Bar
            const paginationBar = document.getElementById('tosaQueuePagination');
            const paginationInfo = document.getElementById('tosaQueuePaginationInfo');
            const paginationNav = document.getElementById('tosaQueuePaginationNav');

            if (paginationBar && paginationInfo && paginationNav) {
                if (totalFiltered > 0) {
                    paginationBar.style.display = 'flex';
                    paginationInfo.innerHTML = `Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${totalFiltered}</strong> queue entries`;

                    let navHtml = '';
                    navHtml += `<button type="button" class="tosa-page-btn" ${currentQueuePage === 1 ? 'disabled' : ''} onclick="goToQueuePage(${currentQueuePage - 1})"><i class="bi bi-chevron-left"></i></button>`;

                    for (let p = 1; p <= totalPages; p++) {
                        if (totalPages <= 7 || p === 1 || p === totalPages || (p >= currentQueuePage - 1 && p <= currentQueuePage + 1)) {
                            navHtml += `<button type="button" class="tosa-page-btn ${p === currentQueuePage ? 'is-active' : ''}" onclick="goToQueuePage(${p})">${p}</button>`;
                        } else if (p === currentQueuePage - 2 || p === currentQueuePage + 2) {
                            navHtml += `<span style="padding: 0 0.35rem; color: #94a3b8; font-weight: 700;">&hellip;</span>`;
                        }
                    }

                    navHtml += `<button type="button" class="tosa-page-btn ${currentQueuePage === totalPages ? 'disabled' : ''} onclick="goToQueuePage(${currentQueuePage + 1})"><i class="bi bi-chevron-right"></i></button>`;
                    paginationNav.innerHTML = navHtml;
                } else {
                    paginationBar.style.display = 'none';
                }
            }
        }

        function handleQueueSearch(val) {
            currentQueueSearch = val.toLowerCase().trim();
            currentQueuePage = 1;
            renderQueueTable();
        }

        function handleQueueStatusFilter(val) {
            currentQueueStatus = val;
            currentQueuePage = 1;
            renderQueueTable();
        }

        function handleQueueProgramFilter(val) {
            currentQueueProgram = val;
            currentQueuePage = 1;
            renderQueueTable();
        }

        function setQueueQuickFilter(filterVal) {
            currentQueueQuickFilter = filterVal;
            currentQueuePage = 1;

            const pillIds = [
                { id: 'qPillAll', val: '' },
                { id: 'qPillMissing', val: 'Missing Documents' },
                { id: 'qPillReturned', val: 'Returned' },
                { id: 'qPillReview', val: 'Under Review' }
            ];

            pillIds.forEach(p => {
                const el = document.getElementById(p.id);
                if (el) el.classList.toggle('is-active', p.val === filterVal);
            });

            renderQueueTable();
        }

        function goToQueuePage(p) {
            currentQueuePage = p;
            renderQueueTable();
        }

        function exportQueueReport() {
            showTosaToast('Exporting prioritized Review Queue triage report (PDF & XLSX)...', 'info');
        }

        function openReviewModal(applicantId) {
            const applicant = tosaApplicants.find(a => a.id === applicantId);
            if (!applicant) return;

            activeReviewApplicantId = applicantId;

            document.getElementById('revName').textContent = applicant.name;
            document.getElementById('revOrg').textContent = applicant.program || applicant.org;
            document.getElementById('revMeta').textContent = `ID: ${applicant.studentId || applicant.sr || ''} • ${applicant.program || applicant.org} (${applicant.yearLevel || 'Candidate'})`;
            document.getElementById('revCategory').textContent = applicant.category || 'Leadership & Technology';
            document.getElementById('revDate').textContent = applicant.date;
            document.getElementById('revAvatar').textContent = applicant.name.split(' ').map(n => n[0]).join('').substring(0, 2);
            document.getElementById('revRemarksInput').value = applicant.remarks || '';

            // Render Documents List (Matching 5 Official Requirements)
            const docList = [
                { name: 'Curriculum_Vitae_Official.pdf', type: 'PDF', size: '1.8 MB' },
                { name: 'Good_Moral_Certificate_Signed.pdf', type: 'PDF', size: '1.1 MB' },
                { name: 'Scholastic_Record_RegistrationServices.pdf', type: 'PDF', size: '2.4 MB' },
                { name: 'Certified_Certificates_RecordsOffice.pdf', type: 'PDF', size: '3.5 MB' },
                { name: 'Complete_Application_Dossier_Scanned.pdf', type: 'PDF', size: '4.2 MB' }
            ];

            const docContainer = document.getElementById('revDocListContainer');
            if (docContainer) {
                docContainer.innerHTML = docList.map(doc => `
                    <div class="tosa-doc-item">
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <div style="width: 34px; height: 34px; border-radius: 8px; background: #fdf0f2; color: #8b1828; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                            </div>
                            <div>
                                <strong style="font-size: 0.85rem; color: var(--tosa-ink-dark); display: block;">${doc.name}</strong>
                                <small style="font-size: 0.72rem; color: var(--tosa-ink-muted);">${doc.size} • Verified on Chain</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.4rem;">
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.76rem;" onclick="viewTosaDocPreview('${doc.name}')">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </div>
                    </div>
                `).join('');
            }

            document.getElementById('applicantReviewModal')?.showModal();
        }

        function closeReviewModal() {
            document.getElementById('applicantReviewModal')?.close();
            activeReviewApplicantId = null;
        }

        function submitDecision(newStatus) {
            if (!activeReviewApplicantId) return;

            const applicant = tosaApplicants.find(a => a.id === activeReviewApplicantId);
            if (!applicant) return;

            const remarks = document.getElementById('revRemarksInput').value.trim() || 'Reviewed by OSO Office.';
            applicant.status = newStatus;
            applicant.remarks = remarks;

            const category = newStatus === 'Approved' ? 'Approval' : (newStatus === 'For Revision' ? 'Revision' : 'Security');
            const actionText = newStatus === 'Approved' ? 'Approved Application Dossier' : (newStatus === 'For Revision' ? 'Requested Document Revision' : 'Rejected Application Submission');

            // Prepend to Activity Log
            tosaActivityLogs.unshift({
                id: Date.now(),
                time: 'Just now',
                date: 'May 20, 2025',
                actor: 'OSO Review Officer',
                role: 'Lead Reviewer',
                category: category,
                action: actionText,
                target: `${applicant.name} (${applicant.program || applicant.org})`,
                hash: Math.random().toString(16).substring(2, 10) + Math.random().toString(16).substring(2, 10) + Math.random().toString(16).substring(2, 10) + 'ab12'
            });

            renderAllTosaData();
            closeReviewModal();
            showTosaToast(`Application for ${applicant.name} updated to "${newStatus}"!`, newStatus === 'Approved' ? 'success' : (newStatus === 'For Revision' ? 'warning' : 'info'));
        }

        function viewTosaDocPreview(docName) {
            showTosaToast(`Viewing preview of "${docName}" (Chain Authenticated)`, 'info');
        }

        // -------------------------------------------------------------------------
        // Activity Log Engine (Live Filters, Pagination, Hash Copy, Verified Ledger)
        // -------------------------------------------------------------------------
        let currentLogSearch = '';
        let currentLogCategory = '';
        let currentLogActor = '';
        let currentLogQuickFilter = '';
        let currentLogPage = 1;
        const LOG_PAGE_SIZE = 7;

        function renderActivityLogTable() {
            const tbody = document.getElementById('tosaActivityLogTbody');
            if (!tbody) return;

            // KPI Counts
            const totalLogs = tosaActivityLogs.length;
            const approvalCount = tosaActivityLogs.filter(l => l.category === 'Approval').length;
            const revisionCount = tosaActivityLogs.filter(l => l.category === 'Revision').length;
            const reqCount = tosaActivityLogs.filter(l => l.category === 'Requirement').length;
            const secCount = tosaActivityLogs.filter(l => l.category === 'Security' || l.category === 'System').length;

            if (document.getElementById('logKpiTotal')) document.getElementById('logKpiTotal').textContent = totalLogs;
            if (document.getElementById('logPillAllCount')) document.getElementById('logPillAllCount').textContent = totalLogs;
            if (document.getElementById('logPillApprovalCount')) document.getElementById('logPillApprovalCount').textContent = approvalCount;
            if (document.getElementById('logPillRevisionCount')) document.getElementById('logPillRevisionCount').textContent = revisionCount;
            if (document.getElementById('logPillReqCount')) document.getElementById('logPillReqCount').textContent = reqCount;
            if (document.getElementById('logPillSecCount')) document.getElementById('logPillSecCount').textContent = secCount;

            // Filter Logs
            const filtered = tosaActivityLogs.filter(log => {
                const searchLower = currentLogSearch;
                const matchSearch = !searchLower || 
                    log.action.toLowerCase().includes(searchLower) || 
                    log.target.toLowerCase().includes(searchLower) || 
                    log.actor.toLowerCase().includes(searchLower) ||
                    log.hash.toLowerCase().includes(searchLower);

                const matchCat = !currentLogCategory || log.category === currentLogCategory;
                const matchActor = !currentLogActor || log.actor === currentLogActor;
                const matchQuick = !currentLogQuickFilter || log.category === currentLogQuickFilter;

                return matchSearch && matchCat && matchActor && matchQuick;
            });

            const totalFiltered = filtered.length;
            const totalPages = Math.ceil(totalFiltered / LOG_PAGE_SIZE) || 1;
            if (currentLogPage > totalPages) currentLogPage = totalPages;

            const startIdx = (currentLogPage - 1) * LOG_PAGE_SIZE;
            const endIdx = Math.min(startIdx + LOG_PAGE_SIZE, totalFiltered);
            const pageItems = filtered.slice(startIdx, endIdx);

            if (pageItems.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                            <i class="bi bi-journal-x" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                            <strong>No Audit Log Records Found</strong>
                            <p style="margin: 0.2rem 0 0; font-size: 0.8rem;">Try adjusting your search criteria or category filter.</p>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = pageItems.map((log, index) => {
                    const globalIndex = startIdx + index + 1;

                    // Actor avatar styling
                    let avatarClass = 'is-user';
                    let avatarIcon = 'bi-person-fill';
                    if (log.actor.includes('System')) {
                        avatarClass = 'is-system';
                        avatarIcon = 'bi-cpu-fill';
                    } else if (log.actor.includes('Directorate')) {
                        avatarClass = 'is-sec';
                        avatarIcon = 'bi-building-fill-gear';
                    }

                    // Category Pill
                    let catPill = `<span class="tosa-log-cat-pill is-system"><i class="bi bi-info-circle"></i> System</span>`;
                    if (log.category === 'Approval') {
                        catPill = `<span class="tosa-log-cat-pill is-approval"><i class="bi bi-check-circle-fill"></i> Approval</span>`;
                    } else if (log.category === 'Revision') {
                        catPill = `<span class="tosa-log-cat-pill is-revision"><i class="bi bi-arrow-repeat"></i> Revision</span>`;
                    } else if (log.category === 'Requirement') {
                        catPill = `<span class="tosa-log-cat-pill is-req"><i class="bi bi-card-checklist"></i> Requirement</span>`;
                    } else if (log.category === 'Security') {
                        catPill = `<span class="tosa-log-cat-pill is-security"><i class="bi bi-shield-check"></i> Security</span>`;
                    }

                    // Shortened hash
                    const shortHash = log.hash.substring(0, 14);

                    return `
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #1e293b;">${globalIndex}</td>
                            <td>
                                <div style="font-size: 0.82rem; font-weight: 800; color: #1a1618;">${escapeHtml(log.time)}</div>
                                <div style="font-size: 0.74rem; color: #64748b;">${escapeHtml(log.date || 'May 20, 2025')}</div>
                            </td>
                            <td>
                                <div class="tosa-log-actor-cell">
                                    <div class="tosa-log-actor-avatar ${avatarClass}">
                                        <i class="bi ${avatarIcon}"></i>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.86rem; font-weight: 800; color: #1a1618;">${escapeHtml(log.actor)}</div>
                                        <div style="font-size: 0.74rem; color: #64748b; font-weight: 500;">${escapeHtml(log.role || 'Authorized Session')}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">${catPill}</td>
                            <td>
                                <div style="font-size: 0.86rem; font-weight: 800; color: #8b1828; margin-bottom: 0.15rem;">${escapeHtml(log.action)}</div>
                                <div style="font-size: 0.78rem; color: #475569; font-weight: 500;">${escapeHtml(log.target)}</div>
                            </td>
                            <td>
                                <button type="button" class="tosa-log-hash-badge" onclick="copyLogHash('${log.hash}')" title="Click to copy full SHA-256 hash">
                                    <i class="bi bi-hash"></i> <span>SHA256: ${escapeHtml(shortHash)}...</span>
                                    <i class="bi bi-copy" style="font-size: 0.7rem; margin-left: 0.2rem; color: #94a3b8;"></i>
                                </button>
                            </td>
                            <td style="text-align: center;">
                                <span class="tosa-log-verified-tag">
                                    <i class="bi bi-patch-check-fill"></i> Verified
                                </span>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            // Pagination Bar
            const paginationBar = document.getElementById('tosaLogPagination');
            const paginationInfo = document.getElementById('tosaLogPaginationInfo');
            const paginationNav = document.getElementById('tosaLogPaginationNav');

            if (paginationBar && paginationInfo && paginationNav) {
                if (totalFiltered > 0) {
                    paginationBar.style.display = 'flex';
                    paginationInfo.innerHTML = `Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${totalFiltered}</strong> log entries`;

                    let navHtml = '';
                    navHtml += `<button type="button" class="tosa-page-btn" ${currentLogPage === 1 ? 'disabled' : ''} onclick="goToLogPage(${currentLogPage - 1})"><i class="bi bi-chevron-left"></i></button>`;

                    for (let p = 1; p <= totalPages; p++) {
                        if (totalPages <= 7 || p === 1 || p === totalPages || (p >= currentLogPage - 1 && p <= currentLogPage + 1)) {
                            navHtml += `<button type="button" class="tosa-page-btn ${p === currentLogPage ? 'is-active' : ''}" onclick="goToLogPage(${p})">${p}</button>`;
                        } else if (p === currentLogPage - 2 || p === currentLogPage + 2) {
                            navHtml += `<span style="padding: 0 0.35rem; color: #94a3b8; font-weight: 700;">&hellip;</span>`;
                        }
                    }

                    navHtml += `<button type="button" class="tosa-page-btn ${currentLogPage === totalPages ? 'disabled' : ''} onclick="goToLogPage(${currentLogPage + 1})"><i class="bi bi-chevron-right"></i></button>`;
                    paginationNav.innerHTML = navHtml;
                } else {
                    paginationBar.style.display = 'none';
                }
            }
        }

        function handleLogSearch(val) {
            currentLogSearch = val.toLowerCase().trim();
            currentLogPage = 1;
            renderActivityLogTable();
        }

        function handleLogCategoryFilter(val) {
            currentLogCategory = val;
            currentLogPage = 1;
            renderActivityLogTable();
        }

        function handleLogActorFilter(val) {
            currentLogActor = val;
            currentLogPage = 1;
            renderActivityLogTable();
        }

        function setLogQuickFilter(filterVal) {
            currentLogQuickFilter = filterVal;
            currentLogPage = 1;

            const pillIds = [
                { id: 'logPillAll', val: '' },
                { id: 'logPillApproval', val: 'Approval' },
                { id: 'logPillRevision', val: 'Revision' },
                { id: 'logPillReq', val: 'Requirement' },
                { id: 'logPillSec', val: 'Security' }
            ];

            pillIds.forEach(p => {
                const el = document.getElementById(p.id);
                if (el) el.classList.toggle('is-active', p.val === filterVal);
            });

            renderActivityLogTable();
        }

        function goToLogPage(p) {
            currentLogPage = p;
            renderActivityLogTable();
        }

        function copyLogHash(hash) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(hash).then(() => {
                    showTosaToast(`Hash copied to clipboard: ${hash.substring(0, 18)}...`, 'success');
                }).catch(() => {
                    showTosaToast(`Hash: ${hash}`, 'info');
                });
            } else {
                showTosaToast(`Hash: ${hash}`, 'info');
            }
        }

        function exportActivityLogReport() {
            showTosaToast('Exporting cryptographic TOSA Immutable Audit Trail (JSON & Signed PDF)...', 'info');
        }

        // -------------------------------------------------------------------------
        // Toast Helpers & Modal Helpers
        // -------------------------------------------------------------------------
        function openForgotPinModal() {
            document.getElementById('forgotPinModal')?.showModal();
        }

        function closeForgotPinModal() {
            document.getElementById('forgotPinModal')?.close();
        }

        function exportTosaSummary() {
            showTosaToast('Exporting official TOSA applicant roster (Excel/PDF)...', 'success');
        }

        function showTosaToast(message, type = 'info') {
            const container = document.getElementById('tosaToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `tosa-toast is-${type}`;
            const icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
            toast.innerHTML = `<i class="bi ${icon}"></i> <span>${escapeHtml(message)}</span>`;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(12px)';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // -------------------------------------------------------------------------
        // Initialization on DOM Load
        // -------------------------------------------------------------------------
        document.addEventListener('DOMContentLoaded', () => {
            const wasUnlocked = sessionStorage.getItem('tosa_unlocked') === 'true';
            if (wasUnlocked) {
                unlockTosaSession();
            } else {
                document.getElementById('pinBox1')?.focus();
            }
        });
    </script>
@endsection
