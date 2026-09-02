@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSdo = $role === 'sdo';
    $isOvcaa = $role === 'ovcaa';
    $isSo = !$isOso && !$isSdo && !$isOvcaa;
@endphp

@section('title', $selectedActivity
    ? ($selectedActivity['title'] . ($isOso ? ' - Review Proposal' : ($isSdo ? ' - SDG Review' : ($isOvcaa ? ' - Final Approval' : ' - Activity Details'))))
    : ($isOso ? 'Proposals & Activity Reviews' : ($isSdo ? 'SDG Document Review' : ($isOvcaa ? 'Final Approval Queue' : 'Activities'))))

@section('header')
    @if ($selectedActivity)
        <a href="{{ route('office.activities') }}" class="org-back-link">
            <i class="bi bi-arrow-left"></i>
            @if ($isOso) Back to Proposals
            @elseif ($isSdo) Back to SDG Review Queue
            @elseif ($isOvcaa) Back to Final Approval Queue
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
                        @if ($isSdo)
                        <span class="org-chip" style="background: #f0fdf4; color: #15803d; font-weight: 700; font-size: 0.76rem; border: 1px solid #bbf7d0;">
                            <i class="bi bi-leaf-fill"></i> SDG Alignment Check
                        </span>
                        @elseif ($isOvcaa)
                        <span class="org-chip" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 0.76rem; border: 1px solid #bfdbfe;">
                            <i class="bi bi-patch-check-fill"></i> Final Approval
                        </span>
                        @endif
                    </div>
                @endif
                <h1 class="org-detail-main-title">
                    {{ $selectedActivity['title'] }}
                    <span class="org-verified-icon" title="Verified on Chain"><i class="bi bi-shield-check"></i></span>
                </h1>
                <div class="org-detail-meta-row">
                    <span class="org-status-pill org-status-{{ $selectedActivity['badge_style'] }}" id="detailStatusBadge">
                        <span class="org-status-dot"></span> <span id="detailStatusText">{{ $selectedActivity['status'] }}</span>
                    </span>
                    <span class="org-detail-timestamp">{{ $selectedActivity['status'] === 'OVCAA Approved' ? 'Approved on ' : ($selectedActivity['status'] === 'Return for Revision' ? 'Returned on ' : 'Submitted on ') }}{{ $selectedActivity['timestamp_note'] }}</span>
                </div>
            </div>
        </div>
    @else
        @if ($isOso)
            <h1><strong>Proposals &amp; Activity Reviews</strong></h1>
            <p class="org-welcome">Review submitted student organization activity proposals, evaluate compliance documents, endorse workflows, or return for revision.</p>
        @elseif ($isSdo)
            <h1><strong>SDG Document Review</strong></h1>
            <p class="org-welcome">Check and monitor submitted activity documents for alignment with the UN Sustainable Development Goals (SDGs). Endorse compliant proposals to OVCAA.</p>
        @elseif ($isOvcaa)
            <h1><strong>Final Approval Queue</strong></h1>
            <p class="org-welcome">Review OSO-endorsed and SDO-verified activity proposals. Exercise final approval authority or return proposals with remarks.</p>
        @else
            <h1><strong>Activities</strong></h1>
            <p class="org-welcome">View and manage organization activities, workflow approvals, and compliance documents.</p>
        @endif
    @endif
@endsection

@section('actions')
    @if ($selectedActivity)
        @if ($isOso)
            <button type="button" class="org-btn org-btn-primary" onclick="approveProposal('{{ $selectedActivity['title'] }}')">
                <i class="bi bi-check2-circle"></i> Endorse to SDO
            </button>
            <button type="button" class="org-btn org-btn-outline" style="color: #dc2626; border-color: #fca5a5;" onclick="openReturnModal()">
                <i class="bi bi-arrow-counterclockwise"></i> Return for Revision
            </button>
            <button type="button" class="org-btn org-btn-outline" onclick="markInReview('{{ $selectedActivity['title'] }}')">
                <i class="bi bi-hourglass-split"></i> In Review
            </button>
        @elseif ($isSdo)
            <button type="button" class="org-btn org-btn-primary" style="background: #15803d; box-shadow: 0 4px 14px rgba(21,128,61,0.25);" onclick="sdoEndorse('{{ $selectedActivity['title'] }}')">
                <i class="bi bi-leaf-fill"></i> Endorse to OVCAA
            </button>
            <button type="button" class="org-btn org-btn-outline" style="color: #dc2626; border-color: #fca5a5;" onclick="openReturnModal()">
                <i class="bi bi-arrow-counterclockwise"></i> Return for Revision
            </button>
            <button type="button" class="org-btn org-btn-outline" onclick="markInReview('{{ $selectedActivity['title'] }}')">
                <i class="bi bi-hourglass-split"></i> Mark Under Review
            </button>
        @elseif ($isOvcaa)
            <button type="button" class="org-btn org-btn-primary" style="background: #1d4ed8; box-shadow: 0 4px 14px rgba(29,78,216,0.25);" onclick="ovcaaApprove('{{ $selectedActivity['title'] }}')">
                <i class="bi bi-patch-check-fill"></i> Final Approve
            </button>
            <button type="button" class="org-btn org-btn-outline" style="color: #dc2626; border-color: #fca5a5;" onclick="openReturnModal()">
                <i class="bi bi-arrow-counterclockwise"></i> Return for Revision
            </button>
        @else
            <a href="{{ route('office.activities.create', ['edit' => $selectedActivity['slug']]) }}" class="org-btn org-btn-outline">
                Edit Activity
            </a>
            <button type="button" class="org-btn-more-options" aria-label="More options">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
        @endif
    @else
        @if ($isOso)
            <button type="button" class="org-btn org-btn-outline" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Summary
            </button>
        @elseif ($isSdo)
            <button type="button" class="org-btn org-btn-outline" onclick="window.print()">
                <i class="bi bi-printer"></i> Print SDG Summary
            </button>
        @elseif ($isOvcaa)
            <button type="button" class="org-btn org-btn-outline" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Approval Summary
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
        
        /* Top Filter Pills */
        .org-filter-pills-row {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
            padding-bottom: 0.35rem;
        }

        .org-filter-pill-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1.15rem;
            border-radius: 9999px;
            font-size: 0.86rem;
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
    </style>

    @if ($selectedActivity)
        {{-- ============================ ACTIVITY DETAILS VIEW ============================ --}}
        <div class="org-activity-details-view">
            
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
                    </div>

                    <div class="org-info-col">
                        <div class="org-info-group">
                            <label>Organization / Council</label>
                            <p>{{ $selectedActivity['organization'] }}</p>
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

            {{-- Section 2: Documents --}}
            <section class="org-detail-card">
                <div class="org-card-title-row">
                    <div class="org-card-icon">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <h2>Submitted Compliance Documents</h2>
                </div>

                <div class="org-docs-table-wrap">
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
                            @foreach ($selectedActivity['documents'] as $doc)
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
                                        <button type="button" class="doc-action-btn" onclick="alert('Viewing document: {{ $doc['name'] }}')">
                                            <i class="bi bi-eye-fill"></i> Read
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="org-doc-guideline-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        @if ($isSdo)
                            <strong>SDG Monitoring Desk:</strong> Verify all attached documents against university sustainability targets and the Waste Policy Compliance Form (WPCF).
                        @elseif ($isOvcaa)
                            <strong>OVCAA Executive Desk:</strong> Review all verified attachments and endorsements before granting final executive university approval.
                        @elseif ($isOso)
                            <strong>OSO Desk:</strong> Ensure all initial document submissions are complete and valid before endorsing to the Sustainable Development Office.
                        @else
                            If your document is returned for revision, please replace or resubmit the updated file.
                            <strong>Once all documents are complete and approved by OSO, SDO, and OVCAA, your activity will be marked as completed.</strong>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Section 3: SDO Role - SDG Alignment & Sustainability Evaluation Card --}}
            @if ($isSdo || $isOvcaa)
            <section class="org-detail-card" style="border-left: 4px solid #15803d;">
                <div class="org-card-title-row">
                    <div class="org-card-icon" style="background: #f0fdf4; color: #15803d;">
                        <i class="bi bi-leaf-fill"></i>
                    </div>
                    <div>
                        <h2>Sustainable Development Goals (SDGs) Alignment Assessment</h2>
                        <span style="font-size: 0.78rem; color: #7a7074;">SDO Monitoring &amp; Document Evaluation Protocol</span>
                    </div>
                    @if ($isSdo)
                    <span class="org-chip" style="margin-left: auto; background: #dcfce7; color: #15803d; font-weight: 800; font-size: 0.75rem; border: 1px solid #bbf7d0;">
                        <i class="bi bi-shield-check"></i> SDO Action Required
                    </span>
                    @endif
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #554d50; margin-bottom: 0.5rem; letter-spacing: 0.03em;">
                            Target UN Sustainable Development Goals (SDGs)
                        </label>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <span class="org-chip" style="background: #4c9f38; color: #ffffff; font-weight: 700; font-size: 0.76rem; border-radius: 8px; padding: 0.35rem 0.75rem;">
                                <i class="bi bi-heart-pulse-fill"></i> SDG 3: Good Health &amp; Well-Being
                            </span>
                            <span class="org-chip" style="background: #c5192d; color: #ffffff; font-weight: 700; font-size: 0.76rem; border-radius: 8px; padding: 0.35rem 0.75rem;">
                                <i class="bi bi-book-fill"></i> SDG 4: Quality Education
                            </span>
                            <span class="org-chip" style="background: #fd9d24; color: #ffffff; font-weight: 700; font-size: 0.76rem; border-radius: 8px; padding: 0.35rem 0.75rem;">
                                <i class="bi bi-buildings-fill"></i> SDG 11: Sustainable Cities &amp; Communities
                            </span>
                            <span class="org-chip" style="background: #bf8b2e; color: #ffffff; font-weight: 700; font-size: 0.76rem; border-radius: 8px; padding: 0.35rem 0.75rem;">
                                <i class="bi bi-recycle"></i> SDG 12: Responsible Consumption &amp; Production
                            </span>
                        </div>
                    </div>

                    <div style="background: #fafaf9; border: 1.5px solid #e7e5e4; border-radius: 14px; padding: 1rem 1.25rem;">
                        <h4 style="font-size: 0.88rem; font-weight: 700; color: #1c1917; margin: 0 0 0.65rem; display: flex; align-items: center; gap: 0.45rem;">
                            <i class="bi bi-check2-square" style="color: #15803d;"></i> SDO Document Compliance Checklist
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; font-size: 0.82rem; color: #44403c;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-check-circle-fill" style="color: #16a34a;"></i>
                                <span>Waste Policy Compliance Form (WPCF)</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-check-circle-fill" style="color: #16a34a;"></i>
                                <span>Single-Use Plastics Ban Compliance</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-check-circle-fill" style="color: #16a34a;"></i>
                                <span>Health, Medical &amp; Safety Protocols</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-check-circle-fill" style="color: #16a34a;"></i>
                                <span>Educational Impact &amp; Inclusion Metrics</span>
                            </div>
                        </div>
                    </div>

                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 1rem 1.25rem;">
                        <strong style="display: block; font-size: 0.86rem; color: #166534; margin-bottom: 0.35rem;">
                            <i class="bi bi-chat-left-quote-fill"></i> SDO Evaluation &amp; Monitoring Notes
                        </strong>
                        <p style="margin: 0; font-size: 0.82rem; color: #15803d; line-height: 1.5;">
                            All uploaded event guidelines, budget sheets, and program schedules have been reviewed. The event exhibits strong alignment with BatStateU's sustainability agenda, promoting student education (SDG 4) and responsible resource consumption (SDG 12).
                        </p>
                    </div>
                </div>
            </section>
            @endif

            {{-- Section 4: OVCAA Role - Final Executive Approval & Governance Dossier --}}
            @if ($isOvcaa)
            <section class="org-detail-card" style="border-left: 4px solid #1d4ed8;">
                <div class="org-card-title-row">
                    <div class="org-card-icon" style="background: #eff6ff; color: #1d4ed8;">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    <div>
                        <h2>OVCAA Executive Approval Dossier</h2>
                        <span style="font-size: 0.78rem; color: #7a7074;">Final Authority &amp; Complete Approval Trail</span>
                    </div>
                    <span class="org-chip" style="margin-left: auto; background: #dbeafe; color: #1e40af; font-weight: 800; font-size: 0.75rem; border: 1px solid #bfdbfe;">
                        <i class="bi bi-award-fill"></i> Final University Authority
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.85rem 1rem;">
                            <span style="display: block; font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Step 1: Student Org</span>
                            <strong style="display: block; font-size: 0.88rem; color: #0f172a; margin: 0.2rem 0;">Proposal Submitted</strong>
                            <small style="color: #16a34a; font-weight: 700;"><i class="bi bi-check2"></i> Complete Dossier</small>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.85rem 1rem;">
                            <span style="display: block; font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Step 2: OSO Desk</span>
                            <strong style="display: block; font-size: 0.88rem; color: #0f172a; margin: 0.2rem 0;">Initial Endorsement</strong>
                            <small style="color: #16a34a; font-weight: 700;"><i class="bi bi-check2"></i> Compliance Verified</small>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.85rem 1rem;">
                            <span style="display: block; font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Step 3: SDO Desk</span>
                            <strong style="display: block; font-size: 0.88rem; color: #0f172a; margin: 0.2rem 0;">SDG Certified</strong>
                            <small style="color: #16a34a; font-weight: 700;"><i class="bi bi-check2"></i> SDG 4 &amp; 12 Aligned</small>
                        </div>
                    </div>

                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 1rem 1.25rem;">
                        <strong style="display: block; font-size: 0.86rem; color: #1e40af; margin-bottom: 0.35rem;">
                            <i class="bi bi-info-circle-fill"></i> Executive Determination Summary
                        </strong>
                        <p style="margin: 0; font-size: 0.82rem; color: #1d4ed8; line-height: 1.5;">
                            This activity has satisfied all university prerequisite clearances. As OVCAA, grant final approval to authorize the activity on the official university calendar and blockchain record, or return with executive instructions.
                        </p>
                    </div>
                </div>
            </section>
            @endif
        </div>

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
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #1a1618; margin-bottom: 0.4rem;">Revision Remarks *</label>
                    <textarea id="returnRemarksInput" rows="4" style="width: 100%; border-radius: 12px; border: 1.5px solid #e8dedf; padding: 0.75rem; font-size: 0.88rem; font-family: inherit; resize: vertical;" placeholder="Explain what documents need updating (e.g. Please update budget breakdown or clarify SDG 12 waste plan)..."></textarea>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button type="button" onclick="closeReturnModal()" style="padding: 0.65rem 1.25rem; border-radius: 9999px; border: 1.5px solid #e8dedf; background: #ffffff; font-weight: 700; font-size: 0.86rem; color: #554d50; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmReturn()" style="padding: 0.65rem 1.5rem; border-radius: 9999px; border: none; background: #dc2626; font-weight: 700; font-size: 0.86rem; color: #ffffff; cursor: pointer; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.25);">
                        Return Proposal
                    </button>
                </div>
            </div>
        </div>

        <script>
            function approveProposal(title) {
                alert(`OSO Desk: Activity proposal "${title}" has been reviewed and endorsed to SDO for SDG monitoring.`);
                const badge = document.getElementById('detailStatusBadge');
                const text = document.getElementById('detailStatusText');
                if (badge && text) {
                    badge.className = 'org-status-pill org-status-yellow';
                    text.textContent = 'For SDO Review';
                }
            }

            function sdoEndorse(title) {
                alert(`SDO Desk: SDG Alignment and documents for "${title}" have been verified! Endorsed to OVCAA for final approval.`);
                const badge = document.getElementById('detailStatusBadge');
                const text = document.getElementById('detailStatusText');
                if (badge && text) {
                    badge.className = 'org-status-pill org-status-blue';
                    text.textContent = 'For OVCAA Approval';
                }
            }

            function ovcaaApprove(title) {
                alert(`OVCAA Final Authority: Activity proposal "${title}" has been GRANTED FINAL APPROVAL and recorded on OrgChain!`);
                const badge = document.getElementById('detailStatusBadge');
                const text = document.getElementById('detailStatusText');
                if (badge && text) {
                    badge.className = 'org-status-pill org-status-green';
                    text.textContent = 'OVCAA Approved';
                }
            }

            function markInReview(title) {
                alert(`Activity proposal "${title}" has been marked as In Review.`);
                const badge = document.getElementById('detailStatusBadge');
                const text = document.getElementById('detailStatusText');
                if (badge && text) {
                    badge.className = 'org-status-pill org-status-blue';
                    text.textContent = 'In Review';
                }
            }

            function openReturnModal() {
                const modal = document.getElementById('returnRevisionModal');
                if (modal) modal.style.display = 'flex';
            }

            function closeReturnModal() {
                const modal = document.getElementById('returnRevisionModal');
                if (modal) modal.style.display = 'none';
            }

            function confirmReturn() {
                const remarks = document.getElementById('returnRemarksInput').value.trim();
                if (!remarks) {
                    alert('Please enter revision remarks before returning.');
                    return;
                }
                alert('Proposal has been returned for revision with remarks: ' + remarks);
                closeReturnModal();
                const badge = document.getElementById('detailStatusBadge');
                const text = document.getElementById('detailStatusText');
                if (badge && text) {
                    badge.className = 'org-status-pill org-status-red';
                    text.textContent = 'Return for Revision';
                }
            }
        </script>

    @else
        {{-- ============================ ACTIVITIES LIST VIEW ============================ --}}
        
        {{-- Top Filter Pills (Excluded Draft and Completed as requested) --}}
        <div class="org-filter-pills-row" id="orgFilterPills">
            <button type="button" class="org-filter-pill-btn is-active" data-filter="all">
                All Activities ({{ count($activities) }})
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

        {{-- Activity Cards List --}}
        <div class="org-activity-rows-container" id="orgActivityList">
            @foreach ($activities as $item)
                <a href="{{ route('office.activities', ['activity' => $item['slug']]) }}" class="org-activity-row-card" data-category="{{ $item['filter_category'] }}">
                    <div class="org-activity-row-left">
                        <div class="org-activity-icon-badge">
                            @if ($isSdo)
                                <i class="bi bi-leaf-fill"></i>
                            @elseif ($isOvcaa)
                                <i class="bi bi-patch-check-fill"></i>
                            @else
                                <i class="bi bi-lightning-charge-fill"></i>
                            @endif
                        </div>
                        <div class="org-activity-row-info">
                            <h3 class="org-activity-row-title">{{ $item['title'] }}</h3>
                            <div class="org-activity-row-meta">
                                <span><i class="bi bi-calendar3"></i> {{ $item['date'] }}</span>
                                <span><i class="bi bi-geo-alt-fill"></i> {{ $item['location'] }}</span>
                                @if (!empty($item['organization']))
                                    <span><i class="bi bi-building"></i> {{ $item['organization'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="org-activity-row-right">
                        <div class="org-activity-row-status-col">
                            <span class="org-status-pill org-status-{{ $item['badge_style'] }}">
                                <span class="org-status-dot"></span> {{ $item['status'] }}
                            </span>
                            <small class="org-status-timestamp">{{ $item['timestamp_note'] }}</small>
                        </div>
                        <div class="org-activity-chevron-btn" aria-hidden="true">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination Footer --}}
        <div class="org-pagination-footer">
            <span id="orgActivityCountText">Showing 1 to {{ count($activities) }} of {{ count($activities) }} activities</span>
            <div class="org-pagination-controls">
                <button type="button" class="org-page-btn" aria-label="Previous page"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="org-page-btn is-active">1</button>
                <button type="button" class="org-page-btn">2</button>
                <button type="button" class="org-page-btn" aria-label="Next page"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>

        {{-- Front-End Filter Interactivity --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const filterBtns = document.querySelectorAll('#orgFilterPills .org-filter-pill-btn');
                const activityCards = document.querySelectorAll('#orgActivityList .org-activity-row-card');
                const countText = document.getElementById('orgActivityCountText');

                filterBtns.forEach(btn => {
                    btn.addEventListener('click', function () {
                        filterBtns.forEach(b => b.classList.remove('is-active'));
                        this.classList.add('is-active');

                        const filter = this.getAttribute('data-filter');
                        let visibleCount = 0;

                        activityCards.forEach(card => {
                            const category = card.getAttribute('data-category');
                            if (filter === 'all' || category === filter) {
                                card.style.display = 'flex';
                                visibleCount++;
                            } else {
                                card.style.display = 'none';
                            }
                        });

                        if (countText) {
                            countText.textContent = `Showing 1 to ${visibleCount} of ${visibleCount} activities`;
                        }
                    });
                });
            });
        </script>
    @endif
@endsection
