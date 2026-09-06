@extends('org.layout')

@section('title', 'Accomplishment Report')

@section('header')
    <h1><strong>Accomplishment Report</strong></h1>
    <p class="org-welcome">Document planned objectives, completed student activities, narrative documentations, and OSO compliance audits.</p>
@endsection

@section('actions')
    <div style="display: flex; gap: 0.6rem; align-items: center;">
        <button type="button" class="org-btn org-btn-outline" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button type="button" class="org-btn-create-folder" onclick="openCreateFolderModal()">
            <i class="bi bi-folder-plus"></i> Create Folder
        </button>
    </div>
@endsection

@section('content')
    <style>
        .org-acc-container {
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
        }

        /* Top Action Button */
        .org-btn-create-folder {
            background: #7a1222;
            color: #ffffff;
            padding: 0.55rem 1.35rem;
            border-radius: 9999px;
            font-size: 0.86rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            box-shadow: 0 4px 14px rgba(122, 18, 34, 0.25);
            transition: all 0.15s ease;
        }

        .org-btn-create-folder:hover {
            background: #600c19;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(122, 18, 34, 0.35);
        }

        /* 0. Top Interactive Filter Toolbar (Report Period) */
        .org-acc-filter-bar {
            background: #ffffff;
            border-radius: 18px;
            border: 1.5px solid #f0e6e8;
            padding: 0.9rem 1.4rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .org-acc-filter-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-wrap: wrap;
        }

        .org-acc-filter-title {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.82rem;
            font-weight: 800;
            color: #7a1222;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .org-acc-select {
            padding: 0.48rem 0.9rem;
            border-radius: 10px;
            border: 1.5px solid #e8dedf;
            background: #ffffff;
            font-size: 0.84rem;
            font-weight: 600;
            color: #1a1618;
            cursor: pointer;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .org-acc-select:focus {
            border-color: #7a1222;
        }

        .org-acc-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 700;
            background: #fdf0f2;
            color: #7a1222;
            border: 1px solid #f8d7dc;
        }

        /* General Card Base */
        .org-acc-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            position: relative;
        }

        .org-acc-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .org-acc-card-head h3 {
            font-size: 1rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Top 4 KPI Cards (Matching Dashboard Style) */
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

        /* Accomplishment Folders & Expandable Accordion */
        .org-acc-folders-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .org-acc-folder-box {
            border-radius: 16px;
            border: 1.5px solid #f0e6e8;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(90, 15, 30, 0.02);
            transition: all 0.2s ease;
        }

        .org-acc-folder-box:hover {
            border-color: #e2d2d5;
        }

        .org-acc-folder-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.95rem 1.35rem;
            background: #ffffff;
            cursor: pointer;
            gap: 1rem;
            flex-wrap: wrap;
            transition: background 0.15s ease;
        }

        .org-acc-folder-box.is-open .org-acc-folder-header {
            background: #faf4f5;
            border-bottom: 1.5px solid #f5eaec;
        }

        .org-acc-folder-left {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .org-folder-icon-wrap {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #fef3c7;
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .org-folder-meta strong {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1a1618;
        }

        .org-folder-meta small {
            display: block;
            font-size: 0.75rem;
            color: #7a7074;
            margin-top: 0.1rem;
        }

        .org-acc-folder-files {
            padding: 1.15rem 1.35rem;
            background: #ffffff;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.9rem;
        }

        .org-doc-card {
            background: #faf7f8;
            border: 1.5px solid #f0e6e8;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            transition: all 0.15s ease;
        }

        .org-doc-card:hover {
            border-color: #d8c2c6;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(90, 15, 30, 0.05);
            transform: translateY(-1px);
        }

        .org-doc-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            overflow: hidden;
        }

        .org-doc-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .org-doc-meta strong {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1a1618;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
        }

        .org-doc-meta small {
            display: block;
            font-size: 0.7rem;
            color: #7a7074;
        }

        .org-doc-btn {
            background: #ffffff;
            border: 1px solid #e8dedf;
            border-radius: 8px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #554d50;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-doc-btn:hover {
            background: #7a1222;
            color: #ffffff;
            border-color: #7a1222;
        }

        /* 2-Column Charts Section (Activity Status Donut & Activities by Type Bar) */
        .org-acc-charts-2col {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 1.25rem;
        }

        .org-chart-box {
            position: relative;
            width: 100%;
            height: 230px;
        }

        /* Activity Details Data Table */
        .org-table-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .org-tab-buttons {
            display: inline-flex;
            background: #faf4f5;
            padding: 0.25rem;
            border-radius: 12px;
            border: 1px solid #f0e6e8;
            gap: 0.25rem;
        }

        .org-tab-btn {
            border: none;
            background: transparent;
            padding: 0.4rem 0.95rem;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #665c60;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-tab-btn.is-active {
            background: #ffffff;
            color: #7a1222;
            box-shadow: 0 2px 6px rgba(90, 15, 30, 0.08);
        }

        .org-search-input {
            padding: 0.45rem 0.85rem;
            border-radius: 10px;
            border: 1.5px solid #e8dedf;
            font-size: 0.82rem;
            outline: none;
            width: 240px;
            transition: border-color 0.15s ease;
        }

        .org-search-input:focus {
            border-color: #7a1222;
        }

        .org-acc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        .org-acc-table th {
            padding: 0.75rem 0.9rem;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #7a7074;
            border-bottom: 1.5px solid #f2e9eb;
            background: #faf6f7;
            letter-spacing: 0.03em;
        }

        .org-acc-table td {
            padding: 0.85rem 0.9rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-acc-table tr:hover td {
            background: #fffafa;
        }

        /* 2-Column Bottom Section (Verification Details & Narrative Summary) */
        .org-bottom-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .org-info-fields-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .org-info-field {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .org-info-field small {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #7a7074;
        }

        .org-info-field strong {
            font-size: 0.92rem;
            font-weight: 700;
            color: #1a1618;
            line-height: 1.25;
        }

        /* Modal Overlay for Creating Folder */
        .org-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(26, 10, 13, 0.45);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .org-modal-box {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #f0e6e8;
            width: 100%;
            max-width: 480px;
            padding: 1.75rem 2rem;
            box-shadow: 0 16px 40px rgba(90, 15, 30, 0.18);
        }

        .org-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #f6eff0;
        }

        .org-modal-head h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .org-modal-close-btn {
            background: transparent;
            border: none;
            font-size: 1.2rem;
            color: #7a7074;
            cursor: pointer;
        }

        .org-modal-field {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 1rem;
        }

        .org-modal-field label {
            font-size: 0.84rem;
            font-weight: 700;
            color: #3b3336;
        }

        .org-modal-field input,
        .org-modal-field select {
            padding: 0.65rem 0.95rem;
            border-radius: 12px;
            border: 1.5px solid #e8dedf;
            font-size: 0.9rem;
            outline: none;
        }

        .org-modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f6eff0;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .org-acc-charts-2col,
            .org-bottom-2col,
            .org-acc-folder-files {
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
            .org-info-fields-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="org-acc-container">

        {{-- 0. Report Period (Dropdown / Filter Toolbar) --}}
        <section class="org-acc-filter-bar" aria-label="Accomplishment Report Period">
            <div class="org-acc-filter-left">
                <div class="org-acc-filter-title">
                    <i class="bi bi-calendar2-range-fill"></i>
                    <span>Report Period:</span>
                </div>

                {{-- Academic Year Selector --}}
                <select id="accYearSelect" class="org-acc-select" onchange="switchAccomplishmentData()">
                    <option value="2025-2026" selected>A.Y. 2025–2026</option>
                    <option value="2026-2027">A.Y. 2026–2027</option>
                    <option value="2024-2025">A.Y. 2024–2025</option>
                </select>

                {{-- Semester Selector --}}
                <select id="accSemSelect" class="org-acc-select" onchange="switchAccomplishmentData()">
                    <option value="1st Semester" selected>1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Midyear">Midyear</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 0.6rem;">
                {{-- Report Status Badge --}}
                <span class="org-acc-badge-pill" id="accStatusBadge" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;">
                    <i class="bi bi-patch-check-fill"></i> Verified &amp; Cleared
                </span>
                <span class="org-acc-badge-pill" id="accPeriodLabelBadge">
                    <i class="bi bi-calendar-check"></i> 1st Semester · A.Y. 2025–2026
                </span>
            </div>
        </section>

        {{-- 1. Total Activities & Milestone KPI Row (Dashboard Style) --}}
        <div class="org-kpi-row">
            {{-- Total Activities --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-pink">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiTotalActivities">8</div>
                </div>
                <h3 class="org-kpi-title">Total Activities</h3>
                <p class="org-kpi-sub" id="kpiTotalActivitiesSub">Recorded in semester dossier</p>
            </article>

            {{-- Completed Activities --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-green">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiCompletedActivities">6</div>
                </div>
                <h3 class="org-kpi-title">Completed Activities</h3>
                <p class="org-kpi-sub" id="kpiCompletedSub">75% project completion rate</p>
            </article>

            {{-- Ongoing / In-Progress --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-blue">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiOngoingActivities">2</div>
                </div>
                <h3 class="org-kpi-title">Ongoing / In-Progress</h3>
                <p class="org-kpi-sub" id="kpiOngoingSub">Currently executing phase</p>
            </article>

            {{-- Participants Reached --}}
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-amber">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiParticipantsCount">1,420</div>
                </div>
                <h3 class="org-kpi-title">Total Participants</h3>
                <p class="org-kpi-sub" id="kpiParticipantsSub">Direct student beneficiaries</p>
            </article>
        </div>

        {{-- 2. Accomplishment Folders & Documents (Expandable Section / Folder List) --}}
        <section class="org-acc-card" aria-label="Accomplishment Folders and Documents">
            <div class="org-acc-card-head">
                <h3><i class="bi bi-folder-fill" style="color: #7a1222;"></i> Accomplishment Folders &amp; Documentation</h3>
                <span class="org-acc-badge-pill" id="folderCountBadge">3 Folders Available</span>
            </div>

            <div class="org-acc-folders-list" id="accomplishmentFoldersList">
                {{-- Folder 1: Primary Term Folder (Open by default) --}}
                <div class="org-acc-folder-box is-open" id="folderBox1">
                    <div class="org-acc-folder-header" onclick="toggleFolder(1)">
                        <div class="org-acc-folder-left">
                            <div class="org-folder-icon-wrap">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                            <div class="org-folder-meta">
                                <strong>1st Semester Accomplishment Portfolio</strong>
                                <small>Academic Year 2025–2026 · 6 Verified Documents</small>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Verified</span>
                            <button type="button" class="org-btn-create-folder" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;" onclick="event.stopPropagation(); triggerUploadDoc('1st Semester Accomplishment Portfolio')">
                                <i class="bi bi-cloud-arrow-up-fill"></i> Upload Doc
                            </button>
                        </div>
                    </div>

                    {{-- Accomplishment Documents List inside Folder --}}
                    <div class="org-acc-folder-files" id="folderFiles1">
                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div class="org-doc-meta">
                                    <strong title="Semestral Narrative Accomplishment Report.pdf">Narrative Report.pdf</strong>
                                    <small>4.2 MB · Oct 12, 2026</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" title="View Document" onclick="alert('Viewing Narrative Report...')">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>

                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon" style="background: #e0f2fe; color: #0284c7;"><i class="bi bi-images"></i></div>
                                <div class="org-doc-meta">
                                    <strong title="High-Resolution Photo Documentation & Press Releases.pdf">Photo Documentation.pdf</strong>
                                    <small>18.6 MB · Oct 10, 2026</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" title="View Document" onclick="alert('Viewing Photo Documentation...')">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>

                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon" style="background: #dcfce7; color: #16a34a;"><i class="bi bi-person-check-fill"></i></div>
                                <div class="org-doc-meta">
                                    <strong title="Certified Student Attendance & Evaluation Sheets.pdf">Attendance Sheets.pdf</strong>
                                    <small>2.8 MB · Oct 08, 2026</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" title="View Document" onclick="alert('Viewing Attendance Sheets...')">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>

                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon" style="background: #fef3c7; color: #d97706;"><i class="bi bi-award-fill"></i></div>
                                <div class="org-doc-meta">
                                    <strong title="Certificate of Completion & Speaker Plaques.pdf">Speaker Certificates.pdf</strong>
                                    <small>1.5 MB · Sep 28, 2026</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" title="View Document" onclick="alert('Viewing Speaker Certificates...')">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>

                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                                <div class="org-doc-meta">
                                    <strong title="Event Evaluation Survey Analytics & Feedback.pdf">Evaluation Analytics.pdf</strong>
                                    <small>1.1 MB · Sep 30, 2026</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" title="View Document" onclick="alert('Viewing Evaluation Analytics...')">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>

                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon" style="background: #f3e8ff; color: #7e22ce;"><i class="bi bi-patch-check-fill"></i></div>
                                <div class="org-doc-meta">
                                    <strong title="SDO & OSO Compliance Audit Endorsement.pdf">OSO Audit Seal.pdf</strong>
                                    <small>850 KB · Oct 14, 2026</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" title="View Document" onclick="alert('Viewing OSO Audit Seal...')">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Folder 2: 2nd Semester Folder --}}
                <div class="org-acc-folder-box" id="folderBox2">
                    <div class="org-acc-folder-header" onclick="toggleFolder(2)">
                        <div class="org-acc-folder-left">
                            <div class="org-folder-icon-wrap">
                                <i class="bi bi-folder2"></i>
                            </div>
                            <div class="org-folder-meta">
                                <strong>2nd Semester Accomplishment Portfolio</strong>
                                <small>Academic Year 2024–2025 · 4 Verified Documents</small>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Archived</span>
                            <button type="button" class="org-btn-create-folder" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;" onclick="event.stopPropagation(); triggerUploadDoc('2nd Semester Accomplishment Portfolio')">
                                <i class="bi bi-cloud-arrow-up-fill"></i> Upload Doc
                            </button>
                        </div>
                    </div>
                    <div class="org-acc-folder-files" id="folderFiles2" style="display: none;">
                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div class="org-doc-meta">
                                    <strong>Narrative_Report_2ndSem_2425.pdf</strong>
                                    <small>3.9 MB · Jun 20, 2025</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" onclick="alert('Opening Document...')"><i class="bi bi-eye-fill"></i></button>
                        </div>
                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon" style="background: #e0f2fe; color: #0284c7;"><i class="bi bi-images"></i></div>
                                <div class="org-doc-meta">
                                    <strong>Photo_Documentation_2ndSem.pdf</strong>
                                    <small>14.2 MB · Jun 18, 2025</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" onclick="alert('Opening Document...')"><i class="bi bi-eye-fill"></i></button>
                        </div>
                    </div>
                </div>

                {{-- Folder 3: Midyear Folder --}}
                <div class="org-acc-folder-box" id="folderBox3">
                    <div class="org-acc-folder-header" onclick="toggleFolder(3)">
                        <div class="org-acc-folder-left">
                            <div class="org-folder-icon-wrap">
                                <i class="bi bi-folder2"></i>
                            </div>
                            <div class="org-folder-meta">
                                <strong>Midyear Leadership &amp; Community Engagement</strong>
                                <small>Academic Year 2024–2025 · 3 Verified Documents</small>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Archived</span>
                            <button type="button" class="org-btn-create-folder" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;" onclick="event.stopPropagation(); triggerUploadDoc('Midyear Leadership')">
                                <i class="bi bi-cloud-arrow-up-fill"></i> Upload Doc
                            </button>
                        </div>
                    </div>
                    <div class="org-acc-folder-files" id="folderFiles3" style="display: none;">
                        <div class="org-doc-card">
                            <div class="org-doc-left">
                                <div class="org-doc-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div class="org-doc-meta">
                                    <strong>Midyear_Community_Outreach.pdf</strong>
                                    <small>2.4 MB · Aug 05, 2025</small>
                                </div>
                            </div>
                            <button type="button" class="org-doc-btn" onclick="alert('Opening Document...')"><i class="bi bi-eye-fill"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 3. Activity Status (Donut Chart) & Activities by Type (Bar Chart) --}}
        <div class="org-acc-charts-2col">
            {{-- Activity Status (Donut Chart) --}}
            <section class="org-acc-card" aria-label="Activity Execution Status">
                <div class="org-acc-card-head">
                    <h3><i class="bi bi-pie-chart-fill" style="color: #16a34a;"></i> Activity Status Distribution</h3>
                    <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">75% Completed</span>
                </div>
                <div class="org-chart-box">
                    <canvas id="activityStatusChart"></canvas>
                </div>
            </section>

            {{-- Activities by Type (Bar Chart) --}}
            <section class="org-acc-card" aria-label="Activities Categorized by Type">
                <div class="org-acc-card-head">
                    <h3><i class="bi bi-bar-chart-fill" style="color: #7a1222;"></i> Activities by Type</h3>
                    <span class="org-acc-badge-pill">4 Categories</span>
                </div>
                <div class="org-chart-box">
                    <canvas id="activitiesByTypeChart"></canvas>
                </div>
            </section>
        </div>

        {{-- 4. Activity Details (Data Table) --}}
        <section class="org-acc-card" aria-label="Detailed Activity Accomplishment Table">
            <div class="org-acc-card-head">
                <h3><i class="bi bi-table" style="color: #7a1222;"></i> Activity Accomplishment Matrix</h3>
                <span class="org-acc-badge-pill" id="tableCountBadge">8 Activities</span>
            </div>

            <div class="org-table-controls">
                <div class="org-tab-buttons">
                    <button type="button" class="org-tab-btn is-active" onclick="filterActivityTable('all', this)">All Activities (8)</button>
                    <button type="button" class="org-tab-btn" onclick="filterActivityTable('Completed', this)">Completed (6)</button>
                    <button type="button" class="org-tab-btn" onclick="filterActivityTable('Ongoing', this)">Ongoing (2)</button>
                </div>
                <input type="text" id="activitySearchInput" class="org-search-input" placeholder="Search activity, type, venue..." onkeyup="searchActivityTable(this.value)">
            </div>

            <div style="overflow-x: auto; width: 100%;">
                <table class="org-acc-table">
                    <thead>
                        <tr>
                            <th>Activity Name</th>
                            <th>Date &amp; Venue</th>
                            <th>Type / Category</th>
                            <th>Participants</th>
                            <th>SDG Target</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="activityTableBody">
                        {{-- Populated dynamically via JS --}}
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 5. Verification Details & Remarks Information Panel --}}
        <div class="org-bottom-2col">
            {{-- Verification Details Information Panel --}}
            <section class="org-acc-card" aria-label="Verification Details">
                <div class="org-acc-card-head">
                    <h3><i class="bi bi-person-check-fill" style="color: #16a34a;"></i> Verification Details</h3>
                    <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">
                        <i class="bi bi-shield-lock-fill"></i> Officially Verified
                    </span>
                </div>
                <div class="org-info-fields-grid">
                    <div class="org-info-field">
                        <small>Verified By</small>
                        <strong id="verAuditor">Prof. Maria Christina A. Del Rosario, PhD</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Designation / Office</small>
                        <strong id="verOffice">Director, Office of Student Organizations (OSO)</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Verification Date</small>
                        <strong id="verDate">October 14, 2026 · 04:30 PM</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Accreditation Protocol Ref.</small>
                        <strong style="font-family: monospace; font-size: 0.78rem; color: #7a1222;">AR-BATSTATEU-2026-0042</strong>
                    </div>
                </div>
            </section>

            {{-- Remarks & Compliance Notes --}}
            <section class="org-acc-card" aria-label="Auditor Remarks and Evaluation">
                <div class="org-acc-card-head">
                    <h3><i class="bi bi-chat-square-text-fill" style="color: #7a1222;"></i> Remarks &amp; Compliance Notes</h3>
                    <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Passed</span>
                </div>
                <div style="background: #faf4f5; border: 1.5px solid #f0e6e8; border-radius: 14px; padding: 1rem 1.15rem; font-size: 0.84rem; color: #40363a; line-height: 1.5;" id="remarksText">
                    "All narrative reports, high-resolution photo evidence, and certified participant manifests comply with university accreditation standards. 100% of planned institutional objectives were achieved."
                </div>
                <div style="margin-top: 0.85rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.76rem; color: #7a7074;">
                    <i class="bi bi-patch-check-fill" style="color: #16a34a;"></i>
                    <span>Endorsed for OVCAA Accreditation &amp; TOSA Awards Entry.</span>
                </div>
            </section>
        </div>

    </div>

    {{-- Create Folder Modal --}}
    <div class="org-modal-overlay" id="createFolderModal">
        <div class="org-modal-box">
            <div class="org-modal-head">
                <h3><i class="bi bi-folder-plus" style="color: #7a1222;"></i> Create Accomplishment Folder</h3>
                <button type="button" class="org-modal-close-btn" onclick="closeCreateFolderModal()">&times;</button>
            </div>
            <form onsubmit="handleCreateFolderSubmit(event)">
                <div class="org-modal-field">
                    <label>Folder Name *</label>
                    <input type="text" id="newFolderName" required placeholder="e.g., Midyear Leadership & Engagement Folder">
                </div>
                <div class="org-modal-field">
                    <label>Semester</label>
                    <select id="newFolderSemester">
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Midyear">Midyear</option>
                    </select>
                </div>
                <div class="org-modal-field">
                    <label>Academic Year</label>
                    <select id="newFolderAY">
                        <option value="AY 2025-2026">AY 2025–2026</option>
                        <option value="AY 2026-2027">AY 2026–2027</option>
                        <option value="AY 2024-2025">AY 2024–2025</option>
                    </select>
                </div>
                <div class="org-modal-actions">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeCreateFolderModal()">Cancel</button>
                    <button type="submit" class="org-btn org-btn-primary">Create Folder</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Accomplishment Dataset Dictionary
        const accomplishmentDatasets = {
            '2025-2026_1st Semester': {
                periodLabel: '1st Semester · A.Y. 2025–2026',
                statusText: 'Verified & Cleared',
                statusBadgeStyle: 'background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;',
                totalActivities: 8,
                completedActivities: 6,
                ongoingActivities: 2,
                participants: 1420,
                statusDonut: {
                    labels: ['Completed', 'Ongoing', 'Cancelled / Postponed'],
                    data: [6, 2, 0],
                    colors: ['#16a34a', '#0284c7', '#dc2626']
                },
                typeBar: {
                    labels: ['Workshop', 'Seminar', 'Community Outreach', 'General Assembly'],
                    data: [3, 2, 2, 1],
                    colors: ['#7a1222', '#d97706', '#0284c7', '#16a34a']
                },
                activities: [
                    { name: 'Innovation Fair Booth Series', date: 'Jul 05–08, 2026', venue: 'CEAFA Gymnasium', type: 'Workshop', participants: 450, sdg: 'SDG 9 · Innovation', status: 'Completed' },
                    { name: 'Leadership Summit 2026', date: 'Aug 14–16, 2026', venue: 'Tagaytay City ICC', type: 'Seminar', participants: 180, sdg: 'SDG 4 · Quality Edu', status: 'Completed' },
                    { name: 'Volunteer Appreciation Day', date: 'Mar 15, 2026', venue: 'Student Center', type: 'General Assembly', participants: 120, sdg: 'SDG 17 · Partnerships', status: 'Completed' },
                    { name: 'Campus Wellness Week', date: 'May 18–22, 2026', venue: 'Campus Grounds', type: 'Community Outreach', participants: 320, sdg: 'SDG 3 · Good Health', status: 'Completed' },
                    { name: 'Python & AI Bootcamp', date: 'Sep 10–12, 2026', venue: 'Computer Lab 3', type: 'Workshop', participants: 95, sdg: 'SDG 4 · Quality Edu', status: 'Completed' },
                    { name: 'Clean & Green Tree Planting Drive', date: 'Sep 28, 2026', venue: 'Alangilan Eco Park', type: 'Community Outreach', participants: 155, sdg: 'SDG 15 · Life on Land', status: 'Completed' },
                    { name: 'BatStateU Sportsfest 2026', date: 'Sep 20–24, 2026', venue: 'Athletic Field', type: 'Workshop', participants: 80, sdg: 'SDG 3 · Good Health', status: 'Ongoing' },
                    { name: 'Cybersecurity Awareness Forum', date: 'Oct 25, 2026', venue: 'Main Auditorium', type: 'Seminar', participants: 20, sdg: 'SDG 9 · Innovation', status: 'Ongoing' }
                ],
                auditor: 'Prof. Maria Christina A. Del Rosario, PhD',
                office: 'Director, Office of Student Organizations (OSO)',
                verDate: 'October 14, 2026 · 04:30 PM',
                remarks: '"All narrative reports, high-resolution photo evidence, and certified participant manifests comply with university accreditation standards. 100% of planned institutional objectives were achieved."'
            },
            '2024-2025_2nd Semester': {
                periodLabel: '2nd Semester · A.Y. 2024–2025',
                statusText: 'Archived & Verified',
                statusBadgeStyle: 'background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;',
                totalActivities: 5,
                completedActivities: 5,
                ongoingActivities: 0,
                participants: 980,
                statusDonut: {
                    labels: ['Completed', 'Ongoing', 'Cancelled / Postponed'],
                    data: [5, 0, 0],
                    colors: ['#16a34a', '#0284c7', '#dc2626']
                },
                typeBar: {
                    labels: ['Workshop', 'Seminar', 'Community Outreach', 'General Assembly'],
                    data: [2, 1, 1, 1],
                    colors: ['#7a1222', '#d97706', '#0284c7', '#16a34a']
                },
                activities: [
                    { name: 'CodeSprint Hackathon 2025', date: 'Apr 12–14, 2025', venue: 'CEAFA Amphitheater', type: 'Workshop', participants: 310, sdg: 'SDG 9 · Innovation', status: 'Completed' },
                    { name: 'Women in Tech Career Talk', date: 'May 05, 2025', venue: 'AVR 2', type: 'Seminar', participants: 220, sdg: 'SDG 5 · Gender Equality', status: 'Completed' },
                    { name: 'Barangay Computer Literacy Outreach', date: 'May 22, 2025', venue: 'Brgy. Alangilan Hall', type: 'Community Outreach', participants: 150, sdg: 'SDG 4 · Quality Edu', status: 'Completed' },
                    { name: 'Web Development Masterclass', date: 'Jun 02, 2025', venue: 'Online / Zoom', type: 'Workshop', participants: 180, sdg: 'SDG 9 · Innovation', status: 'Completed' },
                    { name: 'Year-End General Assembly & Turnover', date: 'Jun 20, 2025', venue: 'Student Center', type: 'General Assembly', participants: 120, sdg: 'SDG 17 · Partnerships', status: 'Completed' }
                ],
                auditor: 'Engr. Daniel Ramirez',
                office: 'Student Activities Coordinator, OSO',
                verDate: 'June 25, 2025 · 02:00 PM',
                remarks: '"Completed semestral turnover and accomplishment reporting. All milestone deliverables verified."'
            }
        };

        let statusChartInst = null;
        let typeChartInst = null;
        let currentActivityList = [];

        function initAccomplishmentCharts(data) {
            // 1. Status Donut Chart
            const ctxStatus = document.getElementById('activityStatusChart').getContext('2d');
            if (statusChartInst) statusChartInst.destroy();
            statusChartInst = new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: data.statusDonut.labels,
                    datasets: [{
                        data: data.statusDonut.data,
                        backgroundColor: data.statusDonut.colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11, family: 'Inter, sans-serif' } }
                        }
                    },
                    cutout: '65%'
                }
            });

            // 2. Activities by Type Bar Chart
            const ctxType = document.getElementById('activitiesByTypeChart').getContext('2d');
            if (typeChartInst) typeChartInst.destroy();
            typeChartInst = new Chart(ctxType, {
                type: 'bar',
                data: {
                    labels: data.typeBar.labels,
                    datasets: [{
                        label: 'Activities Completed',
                        data: data.typeBar.data,
                        backgroundColor: '#7a1222',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 10 } },
                            grid: { color: '#f5eaec' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        function renderActivityTable(items) {
            const tbody = document.getElementById('activityTableBody');
            tbody.innerHTML = '';

            items.forEach(item => {
                const tr = document.createElement('tr');
                const isCompleted = item.status === 'Completed';
                const statusClass = isCompleted ? 'background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;' : 'background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;';

                tr.innerHTML = `
                    <td><strong>${item.name}</strong></td>
                    <td>
                        <div>${item.date}</div>
                        <small style="color:#7a7074;"><i class="bi bi-geo-alt"></i> ${item.venue}</small>
                    </td>
                    <td><span style="background: #faf4f5; border: 1px solid #f0e6e8; padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.74rem; font-weight: 600;">${item.type}</span></td>
                    <td><strong>${item.participants} Students</strong></td>
                    <td><span style="font-size: 0.74rem; font-weight: 700; color: #7a1222;">${item.sdg}</span></td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.74rem; font-weight: 700; ${statusClass}">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: ${isCompleted ? '#16a34a' : '#2563eb'};"></span>
                            ${item.status}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="org-doc-btn" title="View Activity Dossier" onclick="alert('Viewing accomplishment dossier for: ${item.name}')">
                            <i class="bi bi-folder-symlink-fill"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('tableCountBadge').textContent = items.length + ' Activities';
        }

        function switchAccomplishmentData() {
            const year = document.getElementById('accYearSelect').value;
            const sem = document.getElementById('accSemSelect').value;
            const key = `${year}_${sem}`;

            const data = accomplishmentDatasets[key] || accomplishmentDatasets['2025-2026_1st Semester'];
            currentActivityList = data.activities;

            // 1. Period Badges
            document.getElementById('accPeriodLabelBadge').innerHTML = `<i class="bi bi-calendar-check"></i> ${data.periodLabel}`;
            const statusBadge = document.getElementById('accStatusBadge');
            statusBadge.innerHTML = `<i class="bi bi-patch-check-fill"></i> ${data.statusText}`;
            statusBadge.style = data.statusBadgeStyle;

            // 2. KPIs
            document.getElementById('kpiTotalActivities').textContent = data.totalActivities;
            document.getElementById('kpiCompletedActivities').textContent = data.completedActivities;
            document.getElementById('kpiOngoingActivities').textContent = data.ongoingActivities;
            document.getElementById('kpiParticipantsCount').textContent = data.participants.toLocaleString();

            // 3. Charts
            initAccomplishmentCharts(data);

            // 4. Activity Table
            renderActivityTable(data.activities);

            // 5. Verification Details & Remarks
            document.getElementById('verAuditor').textContent = data.auditor;
            document.getElementById('verOffice').textContent = data.office;
            document.getElementById('verDate').textContent = data.verDate;
            document.getElementById('remarksText').textContent = data.remarks;
        }

        function filterActivityTable(status, btn) {
            document.querySelectorAll('.org-tab-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');

            if (status === 'all') {
                renderActivityTable(currentActivityList);
            } else {
                renderActivityTable(currentActivityList.filter(a => a.status === status));
            }
        }

        function searchActivityTable(query) {
            const q = query.toLowerCase().trim();
            if (!q) {
                renderActivityTable(currentActivityList);
                return;
            }
            const filtered = currentActivityList.filter(a =>
                a.name.toLowerCase().includes(q) ||
                a.type.toLowerCase().includes(q) ||
                a.venue.toLowerCase().includes(q) ||
                a.sdg.toLowerCase().includes(q)
            );
            renderActivityTable(filtered);
        }

        function toggleFolder(id) {
            const box = document.getElementById('folderBox' + id);
            const files = document.getElementById('folderFiles' + id);
            if (box) {
                box.classList.toggle('is-open');
                if (files) {
                    files.style.display = box.classList.contains('is-open') ? 'grid' : 'none';
                }
            }
        }

        function triggerUploadDoc(folderName) {
            alert('Upload Document Dialog: Choose narrative, photos, or attendance sheets to upload into "' + folderName + '"');
        }

        function openCreateFolderModal() {
            const modal = document.getElementById('createFolderModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeCreateFolderModal() {
            const modal = document.getElementById('createFolderModal');
            if (modal) modal.style.display = 'none';
        }

        function handleCreateFolderSubmit(e) {
            e.preventDefault();
            const name = document.getElementById('newFolderName').value;
            const sem = document.getElementById('newFolderSemester').value;
            const ay = document.getElementById('newFolderAY').value;

            alert('Accomplishment folder "' + name + '" for ' + sem + ' (' + ay + ') created successfully!');
            closeCreateFolderModal();
        }

        document.addEventListener('DOMContentLoaded', () => {
            switchAccomplishmentData();
        });
    </script>
@endsection
