@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSdo = $role === 'sdo';
    $isOvcaa = $role === 'ovcaa';
    $isOc = $role === 'oc';
    $isSo = !$isOso && !$isSdo && !$isOvcaa && !$isOc;
@endphp

@section('title', $selectedActivity
    ? ($selectedActivity['title'] . ($isOso ? ' - Proposal Details' : ($isSdo ? ' - Document Review' : (($isOvcaa || $isOc) ? ' - Approval Details' : ' - Activity Details'))))
    : ($isOso ? 'Proposals & Activity Reviews' : ($isSdo ? 'SDO Document Review' : ($isOvcaa ? 'OVCAA Review Queue' : ($isOc ? 'OC Final Approval Queue' : 'Activities')))))

@section('header')
    @if ($selectedActivity)
        <a href="{{ route('office.activities') }}" class="org-back-link">
            <i class="bi bi-arrow-left"></i>
            @if ($isOso) Back to Proposals
            @elseif ($isSdo) Back to SDO Document Review Queue
            @elseif ($isOvcaa) Back to Final Approval Queue
            @elseif ($isOc) Back to OC Final Approval Queue
            @else Back to Activities
            @endif
        </a>
        <div class="org-detail-title-row">
            <div>
                @if (!$isSo && !empty($selectedActivity['organization']))
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span class="org-chip" style="background: #fdf0f2; color: #8b1828; font-weight: 700; font-size: 0.76rem; border: 1px solid #f8d7dc;">
                            <i class="bi bi-building"></i> {{ $selectedActivity['organization'] }}
                        </span>
                        @if ($isOvcaa)
                        <span class="org-chip" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 0.76rem; border: 1px solid #bfdbfe;">
                            <i class="bi bi-patch-check-fill"></i> OVCAA Review
                        </span>
                        @elseif ($isOc)
                        <span class="org-chip" style="background: #f8fafc; color: #334155; font-weight: 700; font-size: 0.76rem; border: 1px solid #cbd5e1;">
                            <i class="bi bi-shield-check"></i> OC Final Approval
                        </span>
                        @endif
                    </div>
                @endif
                <h1 class="org-detail-main-title">
                    {{ $selectedActivity['title'] }}
                </h1>
                <div class="org-detail-meta-row">
                    <span class="org-detail-timestamp">{{ $selectedActivity['status'] === 'OC Approved' ? 'Approved on ' : ($selectedActivity['status'] === 'Return for Revision' ? 'Returned on ' : 'Submitted on ') }}{{ $selectedActivity['timestamp_note'] }}</span>
                </div>
            </div>
        </div>
    @else
        @if ($isOso)
            <h1><strong>Proposals &amp; Activity Reviews</strong></h1>
            <p class="org-welcome">Review submitted student organization activity proposals, evaluate compliance documents, endorse workflows, or return for revision.</p>
        @elseif ($isSdo)
            <h1><strong>SDO Document Review</strong></h1>
            <p class="org-welcome">Review the organization-submitted DOCX files and Waste Policy Compliance Form (WPCF), then endorse the complete package to OVCAA. Objectives and SDGs are entered by the organization.</p>
        @elseif ($isOvcaa)
            <h1><strong>OVCAA Review Queue</strong></h1>
            <p class="org-welcome">Review SDO-verified activity proposals and endorse compliant packages to the Office of the Chancellor.</p>
        @elseif ($isOc)
            <h1><strong>OC Final Approval Queue</strong></h1>
            <p class="org-welcome">Review the complete OSO, SDO, and OVCAA-cleared activity package before granting the final university approval.</p>
        @else
            <h1><strong>Activities</strong></h1>
            <p class="org-welcome">View and manage organization activities, workflow approvals, and compliance documents.</p>
        @endif
    @endif
@endsection

@section('actions')
    @if ($selectedActivity)
        @php
            $activityId = $selectedActivity['id'] ?? null;
            $workflowStatus = (string) ($selectedActivity['workflow_status'] ?? $selectedActivity['status_key'] ?? 'created');
            $documentsLocked = (bool) ($selectedActivity['documents_locked'] ?? false);
            $documentsLockMessage = (string) ($selectedActivity['documents_lock_message'] ?? 'Submitted documents are not available at this workflow stage.');
            $canAdvanceActivity = $activityId
                && ($isOso || $isSdo || $isOvcaa || $isOc)
                && app(\App\Services\OrgWorkflowService::class)->canAct($role, $workflowStatus);
        @endphp
        @if ($activityId && ($isOso || $isSdo || $isOvcaa || $isOc))
            @if ($canAdvanceActivity)
                <form id="activityAdvanceForm" method="post" action="{{ route('office.activities.advance', $activityId) }}" style="display:inline;" data-advance-form onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                    @csrf
                    <button type="submit" class="org-btn org-btn-primary" @if($isSdo) style="background:#15803d;box-shadow:0 4px 14px rgba(21,128,61,0.25);" @elseif($isOvcaa) style="background:#1d4ed8;box-shadow:0 4px 14px rgba(29,78,216,0.25);" @elseif($isOc) style="background:#334155;box-shadow:0 4px 14px rgba(51,65,85,0.22);" @endif>
                        @if ($isOso)
                            <i class="bi bi-check2-circle"></i> Endorse / Advance
                        @elseif ($isSdo)
                            <i class="bi bi-leaf-fill"></i> Endorse to OVCAA
                        @elseif ($isOvcaa)
                            <i class="bi bi-patch-check-fill"></i> Endorse to OC
                        @else
                            <i class="bi bi-shield-check"></i> Final Approve
                        @endif
                    </button>
                </form>
                <button type="button" class="org-btn org-btn-outline" style="color: #dc2626; border-color: #fca5a5;" onclick="openReturnModal()">
                    <i class="bi bi-arrow-counterclockwise"></i> Return for Revision
                </button>
            @else
                <button type="button" class="org-btn org-action-disabled" disabled aria-disabled="true" title="This activity is already with the next workflow desk.">
                    <i class="bi bi-hourglass-split"></i> Already endorsed
                </button>
            @endif
        @elseif ($activityId && $isSo && in_array($workflowStatus, ['created', 'returned'], true))
            <a href="{{ !empty($selectedActivity['submission_id']) ? route('office.activities.edit', $selectedActivity['submission_id']) : route('office.activities.create', ['edit' => $selectedActivity['slug']]) }}" class="org-btn org-btn-outline">
                Edit Activity
            </a>
        @else
            <span class="org-chip">Submitted activity · editing locked</span>
        @endif
    @else
        @if ($isOso || $isSdo || $isOvcaa || $isOc)
            <button type="button" class="org-btn org-btn-outline" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Summary
            </button>
        @else
            <a href="{{ route('office.activities.create') }}" class="org-btn org-btn-primary">
                <i class="bi bi-plus-lg"></i> Create An Activity
            </a>
        @endif
    @endif
@endsection

@section('content')
    <style>
        /* =========================================================
           Activities List & Detail Styles (Pixel-Perfect Matching)
           ========================================================= */
        
        /* Top Toolbar & Filter Pills */
        .org-controls-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .org-filter-pills-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.2rem;
            flex-wrap: wrap;
        }

        .org-filter-pill-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            font-size: 0.84rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid #e8e2e4;
            background: #ffffff;
            color: #4b4548;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .org-filter-pill-btn:hover {
            border-color: #d8c2c7;
            background: #fdf8f9;
            color: #1a1517;
        }

        .org-filter-pill-btn.is-active {
            background: #8b1828;
            color: #ffffff;
            border-color: #8b1828;
            box-shadow: 0 4px 14px rgba(139, 24, 40, 0.22);
        }

        .org-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-left: auto;
            flex-wrap: wrap;
        }

        .org-organization-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.22rem 0.35rem 0.22rem 0.75rem;
            border: 1px solid #e8e2e4;
            border-radius: 9999px;
            background: #ffffff;
            color: #7a1222;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .org-organization-filter label {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .org-organization-filter select {
            border: 0;
            border-radius: 9999px;
            background: transparent;
            color: #1a1618;
            font: inherit;
            font-size: 0.78rem;
            font-weight: 700;
            max-width: 150px;
            outline: none;
            padding: 0.25rem 1.6rem 0.25rem 0.1rem;
            cursor: pointer;
        }

        .org-proposals-search-box {
            position: relative;
            display: inline-flex;
            align-items: center;
            background: transparent;
            border: none;
            padding: 0;
        }

        .org-proposals-search-box i.bi-search {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8c8286;
            font-size: 0.82rem;
            pointer-events: none;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .org-proposals-search-box input {
            padding: 0.45rem 1rem 0.45rem 2.3rem;
            font-size: 0.84rem;
            font-family: inherit;
            border: 1px solid #e8e2e4;
            border-radius: 9999px;
            background: #ffffff;
            color: #1a1618;
            width: 210px;
            transition: all 0.2s ease;
            outline: none;
            box-sizing: border-box;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            line-height: 1.4;
        }

        .org-proposals-search-box input:focus {
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.1);
            width: 250px;
            background: #ffffff;
        }

        /* View Mode Switcher Toggle */
        .org-view-toggle {
            display: inline-flex;
            align-items: center;
            background: #f4ecee;
            padding: 0.2rem;
            border-radius: 10px;
            border: 1px solid #e8e0e2;
            gap: 0.15rem;
        }

        .org-view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            background: transparent;
            color: #635b5e;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-view-btn i {
            font-size: 0.9rem;
        }

        .org-view-btn:hover {
            color: #1a1618;
        }

        .org-view-btn.is-active {
            background: #ffffff;
            color: #8b1828;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        /* ----------------------------------------------------
           Grid View Cards (Unslop & Tasteful Craft)
           ---------------------------------------------------- */
        .org-activity-grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .org-grid-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease, border-color 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .org-grid-card:hover {
            transform: translateY(-3px);
            border-color: #f1c0c9;
            box-shadow: 0 10px 28px rgba(139, 24, 40, 0.09);
        }

        .org-grid-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.9rem;
        }

        .org-grid-org-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #fdf0f2;
            color: #8b1828;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            border: 1px solid #fae0e5;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .org-grid-org-chip.org-alias-chip {
            background: #f7f7f7;
            color: #111111;
            border-color: #dedede;
        }

        .org-grid-card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0 0 0.5rem;
            line-height: 1.35;
            letter-spacing: -0.01em;
        }

        .org-grid-card-type {
            font-size: 0.78rem;
            color: #786f73;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.85rem;
        }

        .org-grid-card-meta {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            font-size: 0.82rem;
            color: #635b5e;
            padding: 0.75rem 0;
            border-top: 1px dashed #f2e9eb;
            border-bottom: 1px dashed #f2e9eb;
            margin-bottom: 1rem;
        }

        .org-grid-card-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .org-grid-card-meta i {
            color: #8b1828;
            font-size: 0.85rem;
        }

        .org-grid-card-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 0.25rem;
        }

        .org-grid-doc-badge {
            font-size: 0.75rem;
            font-weight: 600;
            color: #635b5e;
            background: #faf6f7;
            padding: 0.25rem 0.55rem;
            border-radius: 6px;
            border: 1px solid #f0e6e8;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .org-grid-action-link {
            font-size: 0.82rem;
            font-weight: 700;
            color: #8b1828;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: transform 0.15s ease;
        }

        .org-grid-card:hover .org-grid-action-link {
            transform: translateX(3px);
        }

        /* ----------------------------------------------------
           List Table View (Crisp, High-Craft Data Table)
           ---------------------------------------------------- */
        .org-activity-table-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1.5px solid #f0e6e8;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            margin-bottom: 1.75rem;
        }

        .org-table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .org-proposals-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        .org-proposals-table thead th {
            background: #faf6f7;
            padding: 0.85rem 1.15rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #706569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #eee4e6;
            white-space: nowrap;
        }

        .org-proposals-table tbody tr {
            border-bottom: 1px solid #f6eff0;
            transition: background 0.15s ease;
            cursor: pointer;
        }

        .org-proposals-table tbody tr:last-child {
            border-bottom: none;
        }

        .org-proposals-table tbody tr:hover {
            background: #fdf8f9;
        }

        .org-proposals-table td {
            padding: 1rem 1.15rem;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-table-title-cell {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .org-table-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fdf0f2;
            color: #961b2e;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            border: 1px solid #fae0e5;
        }

        .org-table-title-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            min-width: 0;
        }

        .org-table-main-title {
            font-weight: 700;
            color: #1a1618;
            font-size: 0.92rem;
            margin: 0;
            text-decoration: none;
        }

        .org-table-sub-text {
            font-size: 0.78rem;
            color: #786f73;
        }

        .org-table-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            width: 96px;
            height: 32px;
            padding: 0 0.5rem;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1;
            color: #8b1828;
            background: #fdf0f2;
            border: 1px solid #f8d7dc;
            text-decoration: none;
            transition: all 0.18s ease;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .org-table-action-btn:hover {
            background: #8b1828;
            color: #ffffff;
            border-color: #8b1828;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(139, 24, 40, 0.15);
        }

        .org-table-action-btn:active {
            transform: translateY(0);
        }

        /* Empty State */
        .org-empty-state {
            padding: 3.5rem 1.5rem;
            text-align: center;
            background: #ffffff;
            border-radius: 18px;
            border: 1.5px dashed #e8dedf;
            margin-bottom: 1.75rem;
            display: none;
        }

        .org-empty-state-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #fdf0f2;
            color: #8b1828;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .org-empty-state h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0 0 0.35rem;
        }

        .org-empty-state p {
            font-size: 0.86rem;
            color: #786f73;
            margin: 0 0 1.25rem;
        }

        /* Activity List Rows */
        .org-activity-rows-container {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            margin-bottom: 1.75rem;
        }

        .org-activity-row-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.25rem 1.6rem;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 18px rgba(90, 15, 30, 0.03);
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease, border-color 0.2s ease;
            cursor: pointer;
        }

        .org-activity-row-card:hover {
            transform: translateY(-2px);
            border-color: #f1c0c9;
            box-shadow: 0 8px 26px rgba(139, 24, 40, 0.08);
            background: #ffffff;
        }

        .org-activity-row-left {
            display: flex;
            align-items: center;
            gap: 1.15rem;
            min-width: 0;
        }

        .org-activity-icon-badge {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #fdf0f2;
            color: #961b2e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            border: 1px solid #fae0e5;
        }

        .org-activity-row-info {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-width: 0;
        }

        .org-activity-row-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .org-activity-row-meta {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 0.86rem;
            color: #635b5e;
            font-weight: 500;
        }

        .org-activity-row-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .org-activity-row-meta i {
            color: #b91c1c;
            font-size: 0.92rem;
        }

        .org-activity-row-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-shrink: 0;
        }

        .org-activity-row-status-col {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.3rem;
        }

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

        /* Status Colors Matching Screenshot */
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

        .org-status-timestamp {
            font-size: 0.76rem;
            color: #786f73;
            font-weight: 500;
        }

        .org-activity-chevron-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid #ece4e6;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8c8286;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .org-activity-row-card:hover .org-activity-chevron-btn {
            border-color: #d1b8bd;
            color: #8b1828;
            background: #fdf5f6;
        }

        /* Pagination Footer */
        .org-pagination-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.25rem 1.5rem;
            font-size: 0.86rem;
            color: #635b5e;
        }

        .org-pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .org-page-btn {
            min-width: 34px;
            height: 34px;
            padding: 0 0.5rem;
            border-radius: 8px;
            border: 1px solid #e8e2e4;
            background: #ffffff;
            color: #4b4548;
            font-size: 0.84rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-page-btn.is-active {
            background: #8b1828;
            color: #ffffff;
            border-color: #8b1828;
        }

        .org-page-btn:hover:not(.is-active) {
            background: #fdf8f9;
            border-color: #d8c2c7;
        }

        .org-page-btn:disabled {
            opacity: 0.45;
            cursor: default;
        }

        .org-page-gap {
            min-width: 1.25rem;
            text-align: center;
            color: #8c8286;
        }

        /* =========================================================
           Activity Details Screen Styles (Image 2 Matching)
           ========================================================= */
        
        .org-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: #8b1828;
            text-decoration: none;
            margin-bottom: 0.6rem;
            transition: color 0.15s ease;
        }

        .org-back-link:hover {
            color: #6a101e;
            text-decoration: underline;
        }

        .org-detail-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .org-detail-main-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1a1618;
            letter-spacing: -0.02em;
            margin: 0 0 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .org-verified-icon {
            font-size: 1.15rem;
            color: #8c8286;
        }

        .org-detail-meta-row {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .org-detail-timestamp {
            font-size: 0.82rem;
            color: #635b5e;
            font-weight: 500;
        }

        .org-btn-outline {
            padding: 0.55rem 1.25rem;
            border-radius: 9999px;
            border: 1.5px solid #8b1828;
            background: #ffffff;
            color: #8b1828;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .org-btn-outline:hover {
            background: #8b1828;
            color: #ffffff;
        }

        .org-btn-more-options {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid #e2d8da;
            background: #ffffff;
            color: #635b5e;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.15s ease;
        }

        .org-btn-more-options:hover {
            border-color: #c4b0b4;
            color: #1a1618;
        }

        /* Detail Cards */
        .org-detail-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #f0e6e8;
            padding: 1.75rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 24px rgba(90, 15, 30, 0.03);
        }

        .org-card-title-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #f6eff0;
        }

        .org-card-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fdf0f2;
            color: #961b2e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
        }

        .org-card-title-row h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
        }

        .org-btn-outline-red-sm {
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            border: 1.5px solid #8b1828;
            background: #ffffff;
            color: #8b1828;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
            margin-left: auto;
        }

        .org-btn-outline-red-sm:hover {
            background: #8b1828;
            color: #ffffff;
        }

        /* 2-Column Info Grid */
        .org-info-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 2.25rem;
        }

        .org-info-col {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .org-info-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .org-info-group label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #82787c;
            text-transform: none;
        }

        .org-info-group p {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1618;
            margin: 0;
            line-height: 1.45;
        }

        .org-objectives-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .org-objectives-list li {
            position: relative;
            padding-left: 1.1rem;
            font-size: 0.92rem;
            font-weight: 500;
            color: #332d30;
            line-height: 1.45;
        }

        .org-objectives-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #8b1828;
            font-weight: bold;
        }

        /* Documents Table */
        .org-docs-table-wrap {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 1.25rem;
        }

        .org-docs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }

        .org-docs-table th {
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #7a7074;
            border-bottom: 1px solid #f2e9eb;
            background: #faf6f7;
        }

        .org-docs-table td {
            padding: 1.1rem 1rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-doc-name-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
        }

        .doc-type-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        .doc-type-pdf { background: #dc2626; }
        .doc-type-xlsx { background: #16a34a; }
        .doc-type-docx { background: #2563eb; }

        .doc-note-text {
            font-size: 0.76rem;
            color: #786f73;
            margin-top: 0.25rem;
            display: block;
        }

        .doc-actions-cell {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .doc-action-btn {
            background: transparent;
            border: none;
            color: #4b4548;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.4rem;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .doc-action-btn:hover {
            color: #8b1828;
            background: #fdf2f4;
        }

        .doc-action-btn.btn-delete:hover {
            color: #dc2626;
            background: #fef2f2;
        }

        /* OSO document review preview. Keep the original file download inside
           the preview so a reviewer can inspect the submission before acting. */
        .activity-document-preview-dialog {
            width: min(1120px, calc(100vw - 2rem));
            max-width: none;
            padding: 0;
            border: 0;
            border-radius: 18px;
            background: transparent;
            box-shadow: 0 22px 70px rgba(38, 23, 27, 0.28);
        }

        .activity-document-preview-dialog::backdrop {
            background: rgba(36, 24, 28, 0.64);
            backdrop-filter: blur(3px);
        }

        .activity-document-preview-box {
            display: flex;
            max-height: min(92vh, 980px);
            flex-direction: column;
            overflow: hidden;
            border-radius: 18px;
            background: #ffffff;
        }

        .activity-document-preview-head,
        .activity-document-preview-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.95rem 1.15rem;
            background: #ffffff;
        }

        .activity-document-preview-head {
            border-bottom: 1px solid #eee2e5;
        }

        .activity-document-preview-head strong {
            color: #30272a;
            font-size: 0.96rem;
        }

        .activity-document-preview-head small {
            display: block;
            margin-top: 0.18rem;
            color: #8a7b80;
            font-size: 0.72rem;
        }

        .activity-document-preview-close {
            width: 32px;
            height: 32px;
            border: 1px solid #eadde0;
            border-radius: 50%;
            background: #ffffff;
            color: #6f6064;
            cursor: pointer;
            font-size: 1.15rem;
            line-height: 1;
        }

        .activity-document-preview-close:hover {
            color: #8b1828;
            border-color: #d9b7be;
            background: #fdf5f6;
        }

        .activity-document-preview-body {
            min-height: 360px;
            max-height: 72vh;
            overflow: auto;
            padding: 1.25rem;
            background: #e9e6e5;
            scrollbar-width: thin;
        }

        .activity-document-preview-body .docx-wrapper {
            padding: 0 !important;
            background: transparent !important;
        }

        .activity-document-preview-body .docx {
            margin: 0 auto 1.25rem !important;
            box-shadow: 0 7px 24px rgba(42, 27, 30, 0.16) !important;
        }

        .activity-document-preview-body iframe,
        .activity-document-preview-body img {
            display: block;
            width: 100%;
            min-height: 62vh;
            border: 0;
            border-radius: 10px;
            background: #ffffff;
        }

        .activity-document-preview-body img {
            min-height: 0;
            max-height: 62vh;
            object-fit: contain;
        }

        .activity-document-preview-loading,
        .activity-document-preview-error {
            display: grid;
            min-height: 330px;
            place-items: center;
            padding: 2rem;
            color: #77696d;
            font-size: 0.86rem;
            line-height: 1.5;
            text-align: center;
        }

        .activity-document-preview-loading i,
        .activity-document-preview-error i {
            display: block;
            margin-bottom: 0.5rem;
            color: #8b1828;
            font-size: 1.4rem;
        }

        .activity-document-preview-download {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.68rem 1rem;
            border-radius: 9999px;
            background: #8b1828;
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 5px 16px rgba(139, 24, 40, 0.22);
        }

        .activity-document-preview-download:hover {
            background: #71101e;
            color: #ffffff;
        }

        @media (max-width: 640px) {
            .activity-document-preview-dialog {
                width: calc(100vw - 1rem);
            }

            .activity-document-preview-body {
                padding: 0.65rem;
            }

            .activity-document-preview-foot {
                flex-wrap: wrap;
                justify-content: flex-end;
            }
        }

        /* Yellow Warning Box */
        .org-doc-guideline-box {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 14px;
            padding: 0.95rem 1.15rem;
            color: #92400e;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .org-doc-guideline-box i {
            font-size: 1.1rem;
            color: #d97706;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        .org-doc-guideline-box strong {
            color: #78350f;
        }

        /* Save Button Row */
        .org-detail-action-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
            margin-bottom: 2rem;
        }

        .org-btn-save-changes {
            padding: 0.75rem 2.25rem;
            background: #8b1828;
            color: #ffffff;
            border: none;
            border-radius: 9999px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(139, 24, 40, 0.25);
            transition: all 0.2s ease;
        }

        .org-btn-save-changes:hover {
            background: #71101e;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(139, 24, 40, 0.35);
        }

        .org-action-disabled,
        .org-action-disabled:hover,
        .org-action-disabled:focus {
            background: #f5f1f2;
            color: #8c8286;
            border: 1px solid #e5dcdf;
            box-shadow: none;
            cursor: not-allowed;
            opacity: 0.9;
            transform: none;
        }

        /* Live per-activity workflow tracker. It uses the persisted approval
           state/event history and stays readable on narrow SO screens. */
        .org-activity-workflow-card {
            background: linear-gradient(135deg, #fffafb 0%, #ffffff 62%);
            border: 1.5px solid #f0e0e3;
            border-radius: 20px;
            padding: 1.15rem 1.25rem 1.3rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 5px 20px rgba(90, 15, 30, 0.035);
        }

        .org-activity-workflow-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .org-activity-workflow-heading {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
        }

        .org-activity-workflow-heading h2 {
            margin: 0;
            color: #1a1618;
            font-size: 1rem;
            line-height: 1.2;
        }

        .org-activity-workflow-heading p {
            margin: 0.25rem 0 0;
            color: #7a7074;
            font-size: 0.78rem;
            line-height: 1.4;
        }

        .org-activity-workflow-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #8b1828;
            background: #fdf0f2;
            border: 1px solid #f7dce1;
        }

        .org-activity-workflow-current {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex: 0 0 auto;
            padding: 0.38rem 0.7rem;
            border-radius: 9999px;
            border: 1px solid #f4d4da;
            color: #8b1828;
            background: #fff5f6;
            font-size: 0.72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .org-activity-workflow-current.is-returned {
            color: #b45309;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .org-activity-workflow-track {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .org-activity-workflow-step {
            min-width: 0;
            position: relative;
            text-align: center;
            padding: 0 0.45rem;
        }

        .org-activity-workflow-step::after {
            content: '';
            position: absolute;
            z-index: 0;
            top: 1rem;
            left: calc(50% + 1.1rem);
            right: calc(-50% + 1.1rem);
            height: 2px;
            background: #eadfe2;
        }

        .org-activity-workflow-step.is-complete::after {
            background: #86efac;
        }

        .org-activity-workflow-step:last-child::after {
            display: none;
        }

        .org-activity-workflow-node {
            position: relative;
            z-index: 1;
            width: 2rem;
            height: 2rem;
            margin: 0 auto 0.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2px solid #e8dfe1;
            color: #928589;
            background: #ffffff;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .org-activity-workflow-step.is-complete .org-activity-workflow-node {
            color: #15803d;
            border-color: #86efac;
            background: #ecfdf5;
        }

        .org-activity-workflow-step.is-active .org-activity-workflow-node {
            color: #8b1828;
            border-color: #c43b52;
            background: #fff1f3;
            box-shadow: 0 0 0 4px rgba(196, 59, 82, 0.1);
        }

        .org-activity-workflow-step.is-returned .org-activity-workflow-node {
            color: #b45309;
            border-color: #fbbf24;
            background: #fffbeb;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
        }

        .org-activity-workflow-step strong,
        .org-activity-workflow-step > span:not(.org-activity-workflow-node),
        .org-activity-workflow-step small {
            display: block;
        }

        .org-activity-workflow-step strong {
            color: #2b2528;
            font-size: 0.78rem;
            line-height: 1.25;
        }

        .org-activity-workflow-step > span:not(.org-activity-workflow-node) {
            margin-top: 0.18rem;
            color: #7a7074;
            font-size: 0.7rem;
            line-height: 1.25;
        }

        .org-activity-workflow-step small {
            margin-top: 0.35rem;
            color: #968b8f;
            font-size: 0.66rem;
            line-height: 1.3;
        }

        .org-activity-workflow-step.is-active strong {
            color: #8b1828;
        }

        .org-activity-workflow-step.is-returned strong {
            color: #b45309;
        }

        .org-activity-workflow-return-note {
            margin: 0 0 0.85rem;
            padding: 0.55rem 0.7rem;
            border: 1px solid #fde68a;
            border-radius: 10px;
            color: #92400e;
            background: #fffbeb;
            font-size: 0.76rem;
            line-height: 1.4;
        }

        @media (max-width: 900px) {
            .org-info-grid-2col {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .org-activity-row-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .org-activity-row-right {
                width: 100%;
                justify-content: space-between;
            }

            .org-activity-row-status-col {
                align-items: flex-start;
            }
        }

        /* Responsive helpers for detail grids (SDO checklist, OVCAA trail) */
        .org-sdo-checklist-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
            font-size: 0.82rem;
            color: #44403c;
        }

        .org-ovcaa-trail-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media (max-width: 640px) {
            .org-sdo-checklist-grid {
                grid-template-columns: 1fr;
            }

            .org-ovcaa-trail-grid {
                grid-template-columns: 1fr;
            }

            .org-activity-workflow-head {
                flex-direction: column;
            }

            .org-activity-workflow-track {
                grid-template-columns: 1fr;
                gap: 0.85rem;
            }

            .org-activity-workflow-step {
                display: grid;
                grid-template-columns: 2.1rem minmax(0, 1fr);
                column-gap: 0.7rem;
                text-align: left;
                align-items: start;
                padding: 0;
            }

            .org-activity-workflow-step::after {
                top: 2.1rem;
                bottom: -0.85rem;
                left: 0.95rem;
                right: auto;
                width: 2px;
                height: auto;
            }

            .org-activity-workflow-node {
                grid-row: 1 / span 3;
                margin: 0;
            }
        }
    </style>

    @if ($errors->any())
        <div style="margin-bottom:1rem;padding:0.85rem 1rem;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-weight:700;">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($selectedActivity)
        {{-- ============================ ACTIVITY DETAILS VIEW ============================ --}}
        <div class="org-activity-details-view">

            @if (!empty($selectedActivity['workflow_steps']))
                @php
                    $workflowStatus = (string) ($selectedActivity['workflow_status'] ?? 'created');
                    $workflowReturned = $workflowStatus === 'returned';
                    $workflowCurrent = $workflowReturned
                        ? 'Returned for Revision'
                        : ($workflowStatus === 'oc_approved' ? 'OC Approved' : app(\App\Services\OrgWorkflowService::class)->label($workflowStatus));
                @endphp
                <section class="org-activity-workflow-card" aria-labelledby="activityWorkflowTitle">
                    <div class="org-activity-workflow-head">
                        <div class="org-activity-workflow-heading">
                            <span class="org-activity-workflow-icon" aria-hidden="true"><i class="bi bi-diagram-3-fill"></i></span>
                            <div>
                                <h2 id="activityWorkflowTitle">Workflow</h2>
                                <p>Live approval progress for this activity.</p>
                            </div>
                        </div>
                        <span class="org-activity-workflow-current @if($workflowReturned) is-returned @endif">
                            <i class="bi {{ $workflowReturned ? 'bi-arrow-counterclockwise' : ($workflowStatus === 'oc_approved' ? 'bi-check2-circle' : 'bi-hourglass-split') }}"></i>
                            {{ $workflowCurrent }}
                        </span>
                    </div>

                    @if ($workflowReturned)
                        <p class="org-activity-workflow-return-note"><i class="bi bi-info-circle-fill"></i> This activity was returned to the Student Organization for revision. Update the package and resubmit it to OSO.</p>
                    @endif

                    <ol class="org-activity-workflow-track">
                        @foreach ($selectedActivity['workflow_steps'] as $step)
                            <li class="org-activity-workflow-step is-{{ $step['state'] }}" @if($step['state'] === 'active') aria-current="step" @endif>
                                <span class="org-activity-workflow-node" aria-hidden="true">
                                    @if ($step['state'] === 'complete')
                                        <i class="bi bi-check2"></i>
                                    @elseif ($step['state'] === 'active')
                                        <i class="bi bi-hourglass-split"></i>
                                    @elseif ($step['state'] === 'returned')
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>
                                <strong>{{ $step['label'] }}</strong>
                                <span>{{ $step['owner'] }} · {{ $step['state_label'] }}</span>
                                <small>{{ $step['detail'] }}</small>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
            
            {{-- Section 1: Activity Information --}}
            <section class="org-detail-card">
                <div class="org-card-title-row">
                    <div class="org-card-icon">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <h2>Activity Information</h2>
                </div>

                <div class="org-info-grid-2col">
                    <div class="org-info-col">
                        <div class="org-info-group">
                            <label>Activity Type</label>
                            <p>{{ $selectedActivity['activity_type'] }}</p>
                        </div>
                        <div class="org-info-group">
                            <label>Start Date and Time</label>
                            <p>{{ $selectedActivity['start_time'] }}</p>
                        </div>
                        <div class="org-info-group">
                            <label>End Date and Time</label>
                            <p>{{ $selectedActivity['end_time'] }}</p>
                        </div>
                        <div class="org-info-group">
                            <label>Venue / Destination</label>
                            <p>{{ $selectedActivity['location'] }}</p>
                        </div>
                        <div class="org-info-group">
                            <label>Proposed activity budget</label>
                            <p>Php {{ number_format($selectedActivity['budget'] ?? 0, 2) }}</p>
                        </div>
                        @if (!empty($selectedActivity['participants_plan']))
                        <div class="org-info-group"><label>Participants / Audience</label><p style="white-space:pre-wrap;">{{ $selectedActivity['participants_plan'] }}</p></div>
                        @endif
                        @if (!empty($selectedActivity['safety_plan']))
                        <div class="org-info-group"><label>Safety plan</label><p style="white-space:pre-wrap;">{{ $selectedActivity['safety_plan'] }}</p></div>
                        @endif
                    </div>

                    <div class="org-info-col">
                        <div class="org-info-group">
                            <label>Organization / Council</label>
                            <p>{{ $selectedActivity['organization'] }}</p>
                        </div>
                        <div class="org-info-group">
                            <label>Plan of Activities Reference (Attachment I)</label>
                            @if (!empty($selectedActivity['plan_reference']))
                                <p style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 0.45rem;">
                                    <i class="bi bi-calendar-check" style="color: #8b1828;"></i>
                                    <span>{{ $selectedActivity['plan_reference'] }}</span>
                                    @if (!empty($selectedActivity['plan_of_activities_verified']))
                                        <span class="org-status-pill org-status-green" style="font-size: 0.72rem; padding: 0.15rem 0.5rem; margin-left: 0.25rem;">
                                            <i class="bi bi-patch-check-fill"></i> Verified
                                        </span>
                                    @endif
                                </p>
                            @else
                                <p style="color: #64748b; font-style: italic;">
                                    Not specified in initial proposal
                                </p>
                            @endif
                        </div>
                        <div class="org-info-group">
                            <label>Rationale</label>
                            <p>{{ $selectedActivity['rationale'] }}</p>
                        </div>
                        <div class="org-info-group">
                            <label>Objectives</label>
                            <ul class="org-objectives-list">
                                @foreach ($selectedActivity['objectives'] as $obj)
                                    <li>{{ $obj }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Section 2: Review Endorsement Section --}}
            @if (($isOso || $isSdo) && $canAdvanceActivity)
                <section class="org-detail-card" style="border: 1.5px solid {{ $isOso ? '#8b1828' : '#15803d' }}; background: #fffcfd;">
                    <div class="org-card-title-row" style="margin-bottom: 1.15rem;">
                        <div class="org-card-icon" style="background: {{ $isOso ? '#fdf0f2' : '#dcfce7' }}; color: {{ $isOso ? '#8b1828' : '#15803d' }};">
                            <i class="bi {{ $isOso ? 'bi-shield-check' : 'bi-leaf-fill' }}"></i>
                        </div>
                        <div>
                            <h2 style="font-size: 1.05rem; margin: 0; color: #1a1618;">
                                {{ $isOso ? 'OSO Verification & Endorsement Checklist' : 'SDO Waste Policy & Environmental Review' }}
                            </h2>
                            <span style="font-size: 0.78rem; color: #786f73;">
                                {{ $isOso ? 'Mandatory compliance checks before advancing activity to SDO' : 'Verify waste management protocols before advancing to OVCAA' }}
                            </span>
                        </div>
                    </div>

                    @if ($isOso)
                        {{-- Policy 1: Plan of Activities (Attachment I) Verification Box --}}
                        <div style="background: #ffffff; border: 1.5px solid #fed7aa; border-radius: 14px; padding: 1.15rem; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.65rem; margin-bottom: 0.65rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 6px; background: #ea580c; color: #fff; font-size: 0.78rem; font-weight: 800;">1</span>
                                    <strong style="font-size: 0.92rem; color: #9a3412;">Plan of Activities Verification (Renewal Attachment I)</strong>
                                </div>
                                <button type="button"
                                    class="org-btn org-btn-ghost org-btn-sm"
                                    style="border-color: #fdba74; color: #c2410c; background: #fff7ed;"
                                    data-activity-document-preview
                                    data-doc-preview-url="{{ $selectedActivity['plan_document_url'] ?? '/templates/renewal/Attachment I_ Plan of Activities.pdf' }}"
                                    data-doc-preview-title="{{ $selectedActivity['organization'] }} — Approved Plan of Activities (Attachment I)"
                                    data-doc-preview-type="PDF"
                                    data-doc-preview-download-url="{{ $selectedActivity['plan_document_url'] ?? '/templates/renewal/Attachment I_ Plan of Activities.pdf' }}">
                                    <i class="bi bi-eye"></i> View Approved Plan of Activities (Attachment I)
                                </button>
                            </div>

                            <div style="padding: 0.65rem 0.85rem; background: #fff7ed; border-radius: 8px; margin-bottom: 0.85rem; font-size: 0.84rem;">
                                <strong style="color: #9a3412;">SO Plan Reference:</strong>
                                <span style="color: #1e293b; font-weight: 600;">
                                    {{ $selectedActivity['plan_reference'] ?: 'None specified in activity proposal' }}
                                </span>
                            </div>

                            <label style="display: flex; align-items: flex-start; gap: 0.65rem; font-size: 0.86rem; color: #1e293b; cursor: pointer;">
                                <input type="checkbox" name="plan_of_activities_verified" value="1" required form="activityAdvanceForm" style="margin-top: 0.2rem; transform: scale(1.15);">
                                <span>
                                    <strong>Verified against Approved Plan of Activities (Attachment I)</strong> — I confirm this proposed activity is included in the organization's approved Renewal Plan of Activities (or an official justification has been accepted).
                                </span>
                            </label>

                            <div style="margin-top: 0.65rem; font-size: 0.76rem; color: #786f73; display: flex; align-items: center; gap: 0.4rem;">
                                <i class="bi bi-exclamation-circle-fill" style="color: #ea580c;"></i>
                                <span>If this activity is <em>NOT</em> in their approved Plan of Activities, click <strong>Return for Revision</strong> below to require an Adviser Justification / Realignment Letter.</span>
                            </div>
                        </div>

                        {{-- Policy 2: Document Completeness --}}
                        <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 1.15rem; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.65rem;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 6px; background: #475569; color: #fff; font-size: 0.78rem; font-weight: 800;">2</span>
                                <strong style="font-size: 0.92rem; color: #1e293b;">Checklist &amp; Pre-Activity Documents</strong>
                            </div>
                            <label style="display: flex; align-items: flex-start; gap: 0.65rem; font-size: 0.86rem; color: #1e293b; cursor: pointer;">
                                <input type="checkbox" name="documents_reviewed" value="1" required form="activityAdvanceForm" style="margin-top: 0.2rem; transform: scale(1.15);">
                                <span>
                                    <strong>All required compliance documents reviewed</strong> — I have inspected all attached forms, proposals, and resolutions and confirm this package is complete and ready for SDO review.
                                </span>
                            </label>
                        </div>

                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #1a1618;">
                            OSO Review Notes / Endorsement Remarks (optional)
                            <textarea name="review_notes" form="activityAdvanceForm" rows="2" style="display: block; width: 100%; margin-top: 0.35rem; border-radius: 10px; border: 1.5px solid #e8dedf; padding: 0.65rem; font-size: 0.86rem; font-family: inherit;" maxlength="5000" placeholder="e.g., Verified against AY 2026-2027 Attachment I Item #2. Endorsed for environmental compliance check."></textarea>
                        </label>
                    @else
                        {{-- SDO Desk --}}
                        <label style="display: flex; align-items: flex-start; gap: 0.65rem; font-size: 0.88rem; color: #1e293b; cursor: pointer;">
                            <input type="checkbox" name="documents_reviewed" value="1" required form="activityAdvanceForm" style="margin-top: 0.2rem; transform: scale(1.15);">
                            <span>
                                I have opened and checked the submitted DOCX files and Waste Policy Compliance Form (WPCF), confirming environmental compliance.
                            </span>
                        </label>
                    @endif
                </section>
            @endif
            <section class="org-detail-card">
                <div class="org-card-title-row">
                    <div class="org-card-icon">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <h2>Submitted Compliance Documents</h2>
                </div>

                <div class="org-docs-table-wrap">
                    @if ($documentsLocked)
                        <div style="display:flex; flex-direction:column; align-items:center; gap:.45rem; padding:2rem 1rem; text-align:center; color:#64748b; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:14px;">
                            <i class="bi bi-lock-fill" style="font-size:1.45rem; color:#64748b;"></i>
                            <strong style="font-size:.86rem; color:#334155;">Documents locked until your review stage</strong>
                            <span style="font-size:.78rem; max-width:46rem;">{{ $documentsLockMessage }}</span>
                        </div>
                    @else
                    <table class="org-docs-table">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Type</th>
                                <th>Uploaded Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($selectedActivity['documents'] as $doc)
                                <tr>
                                    <td>
                                        <div class="org-doc-name-cell">
                                            <span class="doc-type-icon doc-type-{{ $doc['type'] }}">{{ $doc['type'] }}</span>
                                            <div>
                                                <span>{{ $doc['name'] }}</span>
                                                @if (!empty($doc['note']))
                                                    <small class="doc-note-text">{{ $doc['note'] }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td><span style="font-weight: 700; font-size: 0.76rem; color: #7a7074;">{{ strtoupper($doc['type']) }}</span></td>
                                    <td><span style="font-size: 0.82rem; color: #554d50;">{{ $doc['uploaded_on'] }}</span></td>
                                    <td>
                                        <span class="org-status-pill org-status-{{ $doc['status_style'] }}">
                                            <span class="org-status-dot"></span> {{ $doc['status'] }}
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <button
                                            type="button"
                                            class="doc-action-btn"
                                            data-activity-document-preview
                                            data-doc-preview-url="{{ $doc['url'] }}"
                                            data-doc-preview-title="{{ $doc['name'] }}"
                                            data-doc-preview-type="{{ strtoupper($doc['type']) }}"
                                            data-doc-preview-download-url="{{ $doc['download_url'] ?? $doc['url'] }}"
                                            aria-label="Preview {{ $doc['name'] }}"
                                        >
                                            <i class="bi bi-eye-fill"></i> Preview
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding: 1.5rem 1rem; text-align: center;">
                                        <div style="display:flex; flex-direction:column; align-items:center; gap:0.3rem; color:#8d8286;">
                                            <i class="bi bi-file-earmark-x" style="font-size:1.35rem; color:#b8aaae;"></i>
                                            <strong style="font-size:0.82rem; color:#554d50;">No uploaded documents yet</strong>
                                            <span style="font-size:0.76rem;">Files uploaded by the Student Organization will appear here when they are available to this desk.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @endif
                </div>

                <div class="org-doc-guideline-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        @if ($documentsLocked)
                            <strong>Document access is locked for this desk.</strong> {{ $documentsLockMessage }}
                        @elseif ($isSdo)
                            <strong>SDO Document Review Desk:</strong> Check the submitted DOCX files and Waste Policy Compliance Form (WPCF). The organization-entered objectives and SDGs are read-only here; no SDG re-entry is required.
                        @elseif ($isOvcaa)
                            <strong>OVCAA Review Desk:</strong> Review the OSO and SDO-checked documents together with the organization-entered objectives and SDGs before endorsing the complete package to the Office of the Chancellor.
                        @elseif ($isOc)
                            <strong>OC Final Approval Desk:</strong> Review the complete activity package and the OSO, SDO, and OVCAA review history before granting final approval.
                        @elseif ($isOso)
                            <strong>OSO Desk:</strong> Ensure all initial document submissions are complete and valid before endorsing to the Sustainable Development Office.
                        @else
                            If your document is returned for revision, please replace or resubmit the updated file.
                            <strong>Once all documents are complete and approved by OSO, SDO, OVCAA, and OC, your activity will be marked as completed.</strong>
                        @endif
                    </div>
                </div>
            </section>

        </div>

        {{-- Document preview modal. The original response is fetched as a blob
             so Word files render here instead of navigating to a download. --}}
        <dialog class="activity-document-preview-dialog" id="activityDocumentPreviewModal" aria-labelledby="activityDocumentPreviewTitle">
            <div class="activity-document-preview-box">
                <div class="activity-document-preview-head">
                    <div>
                        <strong id="activityDocumentPreviewTitle"><i class="bi bi-file-earmark-richtext"></i> Document preview</strong>
                        <small id="activityDocumentPreviewStatus" aria-live="polite">Loading the original submitted document…</small>
                    </div>
                    <button type="button" class="activity-document-preview-close" data-activity-document-preview-close aria-label="Close document preview">&times;</button>
                </div>
                <div class="activity-document-preview-body" id="activityDocumentPreviewBody">
                    <div class="activity-document-preview-loading"><div><i class="bi bi-hourglass-split"></i>Loading document…</div></div>
                </div>
                <div class="activity-document-preview-foot">
                    <button type="button" class="org-btn org-btn-outline" data-activity-document-preview-close>Close</button>
                    <a class="activity-document-preview-download" id="activityDocumentPreviewDownload" href="#" download>
                        <i class="bi bi-download"></i> Download original
                    </a>
                </div>
            </div>
        </dialog>

        <script src="{{ asset('js/vendor/jszip.min.js') }}"></script>
        <script src="{{ asset('js/vendor/docx-preview.min.js') }}"></script>
        <script>
            (function () {
                const modal = document.getElementById('activityDocumentPreviewModal');
                const body = document.getElementById('activityDocumentPreviewBody');
                const title = document.getElementById('activityDocumentPreviewTitle');
                const status = document.getElementById('activityDocumentPreviewStatus');
                const download = document.getElementById('activityDocumentPreviewDownload');
                let activeTrigger = null;
                let objectUrl = null;
                let requestId = 0;

                if (!modal || !body || !title || !status || !download) return;

                const revokeObjectUrl = () => {
                    if (objectUrl) {
                        URL.revokeObjectURL(objectUrl);
                        objectUrl = null;
                    }
                };

                const extensionFrom = (url, fallback = '') => {
                    try {
                        const pathname = new URL(url, window.location.href).pathname;
                        return pathname.split('.').pop().toLowerCase() || fallback.toLowerCase();
                    } catch (error) {
                        return fallback.toLowerCase();
                    }
                };

                const showMessage = (message, error = false) => {
                    body.replaceChildren();
                    const wrapper = document.createElement('div');
                    wrapper.className = `activity-document-preview-${error ? 'error' : 'loading'}`;
                    const content = document.createElement('div');
                    const icon = document.createElement('i');
                    icon.className = `bi ${error ? 'bi-exclamation-triangle' : 'bi-hourglass-split'}`;
                    const text = document.createElement('span');
                    text.textContent = message;
                    content.append(icon, text);
                    wrapper.appendChild(content);
                    body.appendChild(wrapper);
                };

                const renderDocument = async (url, type) => {
                    revokeObjectUrl();
                    body.replaceChildren();

                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/octet-stream, application/pdf, image/*, application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        },
                    });
                    if (!response.ok) throw new Error(`Preview request failed (${response.status}).`);

                    const blob = await response.blob();
                    const contentType = (response.headers.get('content-type') || '').split(';')[0].toLowerCase();
                    const extension = extensionFrom(url, type);
                    const isPdf = contentType === 'application/pdf' || extension === 'pdf';
                    const isImage = contentType.startsWith('image/');
                    const isDocx = contentType.includes('wordprocessingml') || extension === 'docx';
                    const isLegacyDoc = !isDocx && (extension === 'doc' || contentType.includes('msword'));

                    if (isPdf) {
                        objectUrl = URL.createObjectURL(blob);
                        const frame = document.createElement('iframe');
                        frame.src = objectUrl;
                        frame.title = 'PDF document preview';
                        frame.loading = 'lazy';
                        body.appendChild(frame);
                        return 'PDF';
                    }

                    if (isImage) {
                        objectUrl = URL.createObjectURL(blob);
                        const image = document.createElement('img');
                        image.src = objectUrl;
                        image.alt = 'Submitted document preview';
                        body.appendChild(image);
                        return 'image';
                    }

                    if (isLegacyDoc) {
                        throw new Error('Legacy .doc files cannot be rendered in the browser.');
                    }

                    if (!isDocx || !window.docx || typeof window.docx.renderAsync !== 'function') {
                        throw new Error('This file type is download-only in the browser.');
                    }

                    await window.docx.renderAsync(blob, body, null, {
                        breakPages: true,
                        ignoreWidth: false,
                        ignoreHeight: false,
                        renderHeaders: true,
                        renderFooters: true,
                        renderFootnotes: true,
                        useBase64URL: true,
                    });
                    return 'DOCX';
                };

                const closePreview = () => {
                    requestId += 1;
                    revokeObjectUrl();
                    if (modal.open) modal.close();
                    if (activeTrigger) activeTrigger.focus();
                    activeTrigger = null;
                };

                const openPreview = async (trigger) => {
                    const url = trigger.dataset.docPreviewUrl;
                    if (!url) return;

                    activeTrigger = trigger;
                    const currentRequestId = ++requestId;
                    const documentName = trigger.dataset.docPreviewTitle || 'Submitted document';
                    const documentType = trigger.dataset.docPreviewType || '';
                    title.replaceChildren();
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-file-earmark-richtext';
                    title.append(icon, document.createTextNode(` ${documentName}`));
                    status.textContent = `Loading ${documentType || 'file'} from the original submission…`;
                    download.href = trigger.dataset.docPreviewDownloadUrl || url;
                    download.setAttribute('download', '');
                    showMessage('Loading document…');
                    if (!modal.open) modal.showModal();

                    try {
                        const renderedType = await renderDocument(url, documentType);
                        if (currentRequestId !== requestId) return;
                        status.textContent = renderedType === 'DOCX'
                            ? 'Rendered from the original DOCX submission.'
                            : 'Rendered from the original submitted file.';
                    } catch (error) {
                        if (currentRequestId !== requestId) return;
                        status.textContent = 'Preview is not available for this file type.';
                        showMessage(`${error.message} Use Download original below to open it in the appropriate app.`, true);
                    }
                };

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest?.('[data-activity-document-preview]');
                    if (trigger) {
                        event.preventDefault();
                        openPreview(trigger);
                        return;
                    }

                    if (event.target.closest?.('[data-activity-document-preview-close]')) {
                        closePreview();
                    }
                });

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closePreview();
                });

                modal.addEventListener('close', () => {
                    requestId += 1;
                    revokeObjectUrl();
                    body.replaceChildren();
                });
            })();
        </script>

        {{-- Return For Revision Modal --}}
        <div id="returnRevisionModal" class="org-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="org-modal-box" style="background: #ffffff; border-radius: 20px; border: 1.5px solid #f0e6e8; padding: 2rem; max-width: 500px; width: 92%; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #1a1618; margin: 0;">Return for Revision</h3>
                        <span style="font-size: 0.78rem; color: #7a7074;">Specify feedback for the student organization</span>
                    </div>
                </div>
                @if (!empty($selectedActivity['id']))
                    <form method="post" action="{{ route('office.activities.return', $selectedActivity['id']) }}" id="returnRevisionForm">
                        @csrf
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #1a1618; margin-bottom: 0.4rem;">Return to *</label>
                            <select name="returned_to" required style="width: 100%; border-radius: 12px; border: 1.5px solid #e8dedf; padding: 0.65rem 0.75rem; font-size: 0.88rem;">
                                <option value="so">Student Organization (SO)</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 0.85rem;">
                            <span style="display: block; font-size: 0.76rem; font-weight: 700; color: #786f73; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.04em;">Quick Feedback Presets</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.74rem; border-color: #fed7aa; color: #c2410c; background: #fff7ed; padding: 0.25rem 0.65rem;" onclick="setReturnRemarks('This activity is not found in your organization\'s approved Renewal Plan of Activities (Attachment I). In accordance with OSO policy, please provide an official Adviser Justification and Realignment Letter, or revise the activity to match your approved plan.')">
                                    <i class="bi bi-tag-fill"></i> Unlisted in Plan of Activities
                                </button>
                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.74rem; border-color: #fecaca; color: #dc2626; background: #fef2f2; padding: 0.25rem 0.65rem;" onclick="setReturnRemarks('Incomplete compliance documents. Please re-upload complete and signed copies of all required pre-activity documents as flagged.')">
                                    <i class="bi bi-tag-fill"></i> Incomplete Documents
                                </button>
                            </div>
                        </div>
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #1a1618; margin-bottom: 0.4rem;">Revision Remarks *</label>
                            <textarea id="returnRemarksInput" name="remarks" required rows="4" style="width: 100%; border-radius: 12px; border: 1.5px solid #e8dedf; padding: 0.75rem; font-size: 0.88rem; font-family: inherit; resize: vertical;" placeholder="Explain what documents need updating..."></textarea>
                        </div>
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                            <button type="button" onclick="closeReturnModal()" style="padding: 0.65rem 1.25rem; border-radius: 9999px; border: 1.5px solid #e8dedf; background: #ffffff; font-weight: 700; font-size: 0.86rem; color: #554d50; cursor: pointer;">
                                Cancel
                            </button>
                            <button type="submit" style="padding: 0.65rem 1.5rem; border-radius: 9999px; border: none; background: #dc2626; font-weight: 700; font-size: 0.86rem; color: #ffffff; cursor: pointer; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.25);">
                                Return Proposal
                            </button>
                        </div>
                    </form>
                @else
                    <p style="color:#7a7074;">This demo row has no database id. Seed activities first.</p>
                    <button type="button" onclick="closeReturnModal()">Close</button>
                @endif
            </div>
        </div>

        <script>
            function openReturnModal() {
                const modal = document.getElementById('returnRevisionModal');
                if (modal) modal.style.display = 'flex';
            }

            function closeReturnModal() {
                const modal = document.getElementById('returnRevisionModal');
                if (modal) modal.style.display = 'none';
            }

            function setReturnRemarks(text) {
                const el = document.getElementById('returnRemarksInput');
                if (el) {
                    el.value = text;
                    el.focus();
                }
            }
        </script>

    @else
        {{-- ============================ ACTIVITIES & PROPOSALS LIST VIEW ============================ --}}
        
        {{-- Controls Toolbar: Filter Pills, Search Bar & Grid/Table Switcher --}}
        <div class="org-controls-toolbar">
            <div class="org-filter-pills-row" id="orgFilterPills">
                <button type="button" class="org-filter-pill-btn is-active" data-filter="all">
                    All {{ $isOso ? 'Proposals' : 'Activities' }} ({{ count($activities) }})
                </button>
                <button type="button" class="org-filter-pill-btn" data-filter="for_approval">
                    For Approval ({{ $forApprovalCount }})
                </button>
                <button type="button" class="org-filter-pill-btn" data-filter="approved">
                    Approved ({{ $approvedCount }})
                </button>
                <button type="button" class="org-filter-pill-btn" data-filter="in_review">
                    In Review ({{ $inReviewCount }})
                </button>
                <button type="button" class="org-filter-pill-btn" data-filter="returned">
                    Returned ({{ $returnedCount }})
                </button>
            </div>

            <div class="org-toolbar-actions">
                <form method="get" action="{{ route('office.activities') }}" class="org-organization-filter" aria-label="Proposal organization filter">
                    <input type="hidden" name="academic_year" value="{{ $selectedAcademicYear ?? '' }}">
                    <label for="orgOrganizationFilter"><i class="bi bi-building"></i> Organization</label>
                    <select id="orgOrganizationFilter" name="organization" onchange="this.form.submit()">
                        <option value="">All Organizations</option>
                        @foreach (($organizations ?? collect()) as $organizationName)
                            <option value="{{ $organizationName }}" @selected(($selectedOrganization ?? '') === $organizationName)>{{ $organizationName }}</option>
                        @endforeach
                    </select>
                </form>

                <form method="get" action="{{ route('office.activities') }}" class="org-organization-filter" aria-label="Academic year filter">
                    <input type="hidden" name="organization" value="{{ $selectedOrganization ?? '' }}">
                    <label for="orgAcademicYearFilter"><i class="bi bi-calendar2-range"></i> Year</label>
                    <select id="orgAcademicYearFilter" name="academic_year" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        @foreach (($activityAcademicYears ?? collect()) as $academicYear)
                            <option value="{{ $academicYear }}" @selected(($selectedAcademicYear ?? '') === $academicYear)>{{ $academicYear }}</option>
                        @endforeach
                    </select>
                </form>

                {{-- Live Search Input --}}
                <div class="org-proposals-search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="orgProposalSearch" placeholder="Search {{ $isOso ? 'proposals' : 'activities' }}..." aria-label="Search proposals" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false">
                </div>

                {{-- Grid vs List Table View Switcher --}}
                <div class="org-view-toggle" role="group" aria-label="View layout switcher">
                    <button type="button" class="org-view-btn is-active" id="viewToggleGrid" data-view="grid" title="Grid View">
                        <i class="bi bi-grid-fill"></i>
                        <span>Grid</span>
                    </button>
                    <button type="button" class="org-view-btn" id="viewToggleTable" data-view="table" title="List Table View">
                        <i class="bi bi-view-list"></i>
                        <span>Table</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- 1. GRID VIEW (Cards Grid) --}}
        <div class="org-activity-grid-container" id="orgActivityGrid">
            @foreach ($activities as $item)
                <a href="{{ route('office.activities', ['activity' => $item['slug']]) }}" 
                   class="org-grid-card org-item-element" 
                   data-category="{{ $item['filter_category'] }}"
                   data-search="{{ strtolower($item['title'] . ' ' . ($item['organization'] ?? '') . ' ' . ($item['college'] ?? '') . ' ' . ($item['location'] ?? '') . ' ' . ($item['activity_type'] ?? '') . ' ' . $item['status']) }}">
                    
                    <div>
                        <div class="org-grid-card-head">
                            @if (!empty($item['organization']))
                                <span class="org-grid-org-chip org-alias-chip" title="{{ $item['organization'] }}">
                                    <i class="bi bi-building"></i> {{ $item['organization_alias'] ?? $item['organization'] }}
                                </span>
                            @else
                                <span></span>
                            @endif
                        </div>

                        <h3 class="org-grid-card-title">{{ $item['title'] }}</h3>
                        
                        @if (!empty($item['activity_type']))
                            <div class="org-grid-card-type">
                                <i class="bi bi-tag-fill" style="color: #8b1828;"></i> {{ $item['activity_type'] }}
                            </div>
                        @endif

                        <div class="org-grid-card-meta">
                            <span><i class="bi bi-calendar3"></i> {{ $item['date'] }}</span>
                            <span><i class="bi bi-geo-alt-fill"></i> {{ $item['location'] }}</span>
                        </div>
                    </div>

                    <div class="org-grid-card-foot">
                        <span class="org-grid-doc-badge">
                            <i class="bi bi-file-earmark-text-fill" style="color: #8b1828;"></i>
                            {{ count($item['documents'] ?? []) }} Docs attached
                        </span>
                        <span class="org-grid-action-link">
                            View Details <i class="bi bi-arrow-right"></i>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- 2. LIST TABLE VIEW (Structured Data Table) --}}
        <div class="org-activity-table-card" id="orgActivityTableWrap" style="display: none;">
            <div class="org-table-responsive">
                <table class="org-proposals-table">
                    <thead>
                        <tr>
                            <th>{{ $isOso ? 'Activity Proposal' : 'Activity Name' }}</th>
                            <th>Organization</th>
                            <th>Type</th>
                            <th>Schedule &amp; Location</th>
                            <th>Documents</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="orgProposalsTableBody">
                        @foreach ($activities as $item)
                            <tr class="org-table-row org-item-element" 
                                onclick="window.location.href='{{ route('office.activities', ['activity' => $item['slug']]) }}'"
                                data-category="{{ $item['filter_category'] }}"
                                data-search="{{ strtolower($item['title'] . ' ' . ($item['organization'] ?? '') . ' ' . ($item['college'] ?? '') . ' ' . ($item['location'] ?? '') . ' ' . ($item['activity_type'] ?? '') . ' ' . $item['status']) }}">
                                <td>
                                    <div class="org-table-title-cell">
                                        <div class="org-table-icon">
                                            @if ($isSdo)
                                                <i class="bi bi-leaf-fill"></i>
                                            @elseif ($isOvcaa)
                                                <i class="bi bi-patch-check-fill"></i>
                                            @elseif ($isOc)
                                                <i class="bi bi-shield-check"></i>
                                            @else
                                                <i class="bi bi-file-earmark-check-fill"></i>
                                            @endif
                                        </div>
                                        <div class="org-table-title-wrap">
                                            <a href="{{ route('office.activities', ['activity' => $item['slug']]) }}" class="org-table-main-title">
                                                {{ $item['title'] }}
                                            </a>
                                            <span class="org-table-sub-text">{{ $item['timestamp_note'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if (!empty($item['organization']))
                                        <span class="org-grid-org-chip org-alias-chip" style="max-width: 170px;" title="{{ $item['organization'] }}">
                                            <i class="bi bi-building"></i> {{ $item['organization_alias'] ?? $item['organization'] }}
                                        </span>
                                    @else
                                        <span style="color: #8c8286;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size: 0.8rem; font-weight: 600; color: #4b4548;">
                                        {{ $item['activity_type'] ?? 'General' }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem; font-size: 0.8rem; color: #554d50;">
                                        <span><i class="bi bi-calendar3" style="color: #8b1828; margin-right: 0.25rem;"></i> {{ $item['date'] }}</span>
                                        <span style="color: #786f73;"><i class="bi bi-geo-alt-fill" style="color: #8b1828; margin-right: 0.25rem;"></i> {{ $item['location'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="org-grid-doc-badge">
                                        <i class="bi bi-file-earmark-text" style="color: #8b1828;"></i> {{ count($item['documents'] ?? []) }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('office.activities', ['activity' => $item['slug']]) }}" class="org-table-action-btn" onclick="event.stopPropagation();">
                                        View Details <i class="bi bi-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Empty Search / Filter State --}}
        <div class="org-empty-state" id="orgEmptyState">
            <div class="org-empty-state-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h3>No matching {{ $isOso ? 'proposals' : 'activities' }} found</h3>
            <p>Try adjusting your search query or filter category to find what you're looking for.</p>
            <button type="button" class="org-btn org-btn-outline" id="orgResetFilterBtn" style="padding: 0.4rem 1.2rem; font-size: 0.84rem;">
                Reset Filters
            </button>
        </div>

        {{-- Pagination Footer --}}
        <div class="org-pagination-footer" id="orgPaginationFooter">
            <span id="orgActivityCountText">Showing 0 of 0 {{ $isOso ? 'proposals' : 'activities' }}</span>
            <div class="org-pagination-controls" id="orgActivityPaginationControls" aria-label="Activity pages"></div>
        </div>

        {{-- Front-End View Toggle, Live Search & Filter Script --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const gridView = document.getElementById('orgActivityGrid');
                const tableView = document.getElementById('orgActivityTableWrap');
                const btnGrid = document.getElementById('viewToggleGrid');
                const btnTable = document.getElementById('viewToggleTable');
                const searchInput = document.getElementById('orgProposalSearch');
                const filterBtns = document.querySelectorAll('#orgFilterPills .org-filter-pill-btn');
                const emptyState = document.getElementById('orgEmptyState');
                const countText = document.getElementById('orgActivityCountText');
                const resetBtn = document.getElementById('orgResetFilterBtn');
                const paginationFooter = document.getElementById('orgPaginationFooter');
                const paginationControls = document.getElementById('orgActivityPaginationControls');
                const isOso = {{ $isOso ? 'true' : 'false' }};
                const entityName = isOso ? 'proposals' : 'activities';
                const pageSize = 9;

                let currentView = localStorage.getItem('org_proposal_view_mode') || 'grid';
                let currentFilter = 'all';
                let currentSearch = '';
                let currentPage = 1;

                function setViewMode(mode) {
                    currentView = mode;
                    localStorage.setItem('org_proposal_view_mode', mode);

                    if (mode === 'grid') {
                        gridView.style.display = 'grid';
                        tableView.style.display = 'none';
                        btnGrid.classList.add('is-active');
                        btnTable.classList.remove('is-active');
                    } else {
                        gridView.style.display = 'none';
                        tableView.style.display = 'block';
                        btnGrid.classList.remove('is-active');
                        btnTable.classList.add('is-active');
                    }
                    applyFilters(false);
                }

                btnGrid.addEventListener('click', () => setViewMode('grid'));
                btnTable.addEventListener('click', () => setViewMode('table'));

                // Initialize view preference
                setViewMode(currentView);

                function matches(item) {
                    const matchesCategory = currentFilter === 'all' || item.getAttribute('data-category') === currentFilter;
                    const matchesSearch = !currentSearch || item.getAttribute('data-search').includes(currentSearch);
                    return matchesCategory && matchesSearch;
                }

                function renderPagination(total) {
                    if (!paginationControls) return;
                    const totalPages = Math.max(1, Math.ceil(total / pageSize));
                    currentPage = Math.min(Math.max(currentPage, 1), totalPages);
                    paginationControls.innerHTML = '';

                    if (totalPages <= 1) return;

                    const addButton = (label, page, disabled = false, active = false, aria = label) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = `org-page-btn${active ? ' is-active' : ''}`;
                        button.innerHTML = label;
                        button.disabled = disabled;
                        button.setAttribute('aria-label', aria);
                        if (active) button.setAttribute('aria-current', 'page');
                        button.addEventListener('click', () => {
                            currentPage = page;
                            applyFilters(false);
                        });
                        paginationControls.appendChild(button);
                    };

                    addButton('<i class="bi bi-chevron-left"></i>', currentPage - 1, currentPage === 1, false, 'Previous page');
                    for (let page = 1; page <= totalPages; page += 1) {
                        if (totalPages > 7 && page > 2 && page < totalPages - 1 && Math.abs(page - currentPage) > 1) {
                            if (page === 3 || page === totalPages - 2) {
                                const gap = document.createElement('span');
                                gap.className = 'org-page-gap';
                                gap.textContent = '…';
                                paginationControls.appendChild(gap);
                            }
                            continue;
                        }
                        addButton(String(page), page, false, page === currentPage, `Page ${page}`);
                    }
                    addButton('<i class="bi bi-chevron-right"></i>', currentPage + 1, currentPage === totalPages, false, 'Next page');
                }

                function applyFilters(resetPage = true) {
                    const gridItems = gridView.querySelectorAll('.org-grid-card');
                    const tableItems = tableView.querySelectorAll('.org-table-row');
                    if (resetPage) currentPage = 1;

                    const matchingGridItems = Array.from(gridItems).filter(matches);
                    const matchingTableItems = Array.from(tableItems).filter(matches);
                    const matchingItems = currentView === 'grid' ? matchingGridItems : matchingTableItems;
                    const visibleCount = matchingItems.length;
                    currentPage = Math.min(currentPage, Math.max(1, Math.ceil(visibleCount / pageSize)));
                    const startIndex = (currentPage - 1) * pageSize;
                    const pageItems = new Set(matchingItems.slice(startIndex, startIndex + pageSize));

                    gridItems.forEach(item => {
                        item.style.display = pageItems.has(item) ? 'flex' : 'none';
                    });
                    tableItems.forEach(item => {
                        item.style.display = pageItems.has(item) ? '' : 'none';
                    });

                    // Handle empty state & count
                    if (visibleCount === 0) {
                        emptyState.style.display = 'block';
                        if (currentView === 'grid') gridView.style.display = 'none';
                        if (currentView === 'table') tableView.style.display = 'none';
                        if (paginationFooter) paginationFooter.style.display = 'none';
                    } else {
                        emptyState.style.display = 'none';
                        if (currentView === 'grid') gridView.style.display = 'grid';
                        if (currentView === 'table') tableView.style.display = 'block';
                        if (paginationFooter) paginationFooter.style.display = 'flex';
                    }

                    if (countText) {
                        const first = visibleCount ? startIndex + 1 : 0;
                        const last = visibleCount ? Math.min(startIndex + pageSize, visibleCount) : 0;
                        countText.textContent = `Showing ${first} to ${last} of ${visibleCount} ${entityName}`;
                    }
                    renderPagination(visibleCount);
                }

                // Filter Pill Click Handlers
                filterBtns.forEach(btn => {
                    btn.addEventListener('click', function () {
                        filterBtns.forEach(b => b.classList.remove('is-active'));
                        this.classList.add('is-active');
                        currentFilter = this.getAttribute('data-filter');
                        applyFilters();
                    });
                });

                // Search Input Handler
                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        currentSearch = this.value.toLowerCase().trim();
                        applyFilters();
                    });
                }

                // Reset Filters
                if (resetBtn) {
                    resetBtn.addEventListener('click', function () {
                        currentFilter = 'all';
                        currentSearch = '';
                        currentPage = 1;
                        if (searchInput) searchInput.value = '';
                        filterBtns.forEach(b => {
                            b.classList.toggle('is-active', b.getAttribute('data-filter') === 'all');
                        });
                        applyFilters();
                    });
                }
            });
        </script>
    @endif
@endsection
