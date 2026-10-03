@extends('org.layout')

@section('title', 'Accomplishment Report')

@section('header')
    <h1><strong>Accomplishment Report</strong></h1>
    <p class="org-welcome">Record what was actually delivered: objectives achieved, participation, evidence, financial references, and recommendations.</p>
@endsection

@section('actions')
    <div style="display: flex; gap: 0.6rem; align-items: center;">
        <a href="{{ route('office.updates.templates.document', 'source-accomplishment-financial') }}" class="org-btn org-btn-outline">
            <i class="bi bi-file-earmark-word"></i> Official Report Format
        </a>
        <a href="{{ route('office.accomplishment.print', request()->query()) }}" target="_blank" rel="noopener" class="org-btn org-btn-outline">
            <i class="bi bi-printer"></i> Print Report
        </a>
    </div>
@endsection

@section('content')
    @include('org.partials.semester-report-workflow', [
        'reportType' => 'ar',
        'reportBundle' => $reportBundle ?? [],
        'reportQueue' => $reportQueue ?? [],
        'organizations' => $organizations ?? collect(),
        'selectedOrganization' => $selectedOrganization ?? '',
        'selectedSemester' => $selectedSemester ?? '1st Semester',
        'selectedYear' => $selectedYear ?? '2025-2026',
    ])
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

        /* Report snapshot metric cards */
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

        .org-folder-pagination,
        .org-acc-folder-files > form {
            grid-column: 1 / -1;
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

        .org-report-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-top: 0.9rem;
            padding-top: 0.8rem;
            border-top: 1px solid #f6eff0;
            color: #7a7074;
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
            border: 1px solid #e8dedf;
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

        /* 2-Column Bottom Section (Report Review & Recommendations) */
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

        #accomplishmentDocumentPreviewModal::backdrop {
            background: rgba(26, 10, 13, 0.52);
            backdrop-filter: blur(4px);
        }

        #accomplishmentDocumentPreviewModal {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            right: auto !important;
            bottom: auto !important;
            margin: 0 !important;
            transform: translate(-50%, -50%) !important;
            max-height: calc(100vh - 2rem);
        }

        #accomplishmentDocumentPreviewBody .docx-wrapper {
            background: #ffffff !important;
            padding: 2rem 2.2rem !important;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
        }

        #accomplishmentDocumentPreviewBody iframe,
        #accomplishmentDocumentPreviewBody img {
            display: block;
            width: 100%;
            min-height: 58vh;
            border: 0;
            border-radius: 12px;
            background: #ffffff;
        }

        #accomplishmentDocumentPreviewBody img {
            min-height: 0;
            max-height: 58vh;
            object-fit: contain;
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
            .org-bottom-2col,
            .org-acc-folder-files {
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
            .org-info-fields-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="org-acc-container">

        <form method="get" action="{{ route('office.accomplishment') }}" class="org-acc-filter-bar" aria-label="Accomplishment Filters">
            <div class="org-acc-filter-left">
                <div class="org-acc-filter-title">
                    <i class="bi bi-funnel-fill"></i>
                    <span>Filters:</span>
                </div>
                <select name="gender" class="org-acc-select" onchange="this.form.submit()">
                    <option value="all" @selected(($selectedGender ?? 'all') === 'all')>All Genders</option>
                    <option value="male" @selected(($selectedGender ?? '') === 'male')>Male</option>
                    <option value="female" @selected(($selectedGender ?? '') === 'female')>Female</option>
                </select>
                <select name="sdg" class="org-acc-select" onchange="this.form.submit()">
                    <option value="">All SDGs</option>
                    @foreach (($sdgOptions ?? []) as $sdgOpt)
                        <option value="{{ $sdgOpt }}" @selected(($selectedSdg ?? '') === $sdgOpt)>{{ $sdgOpt }}</option>
                    @endforeach
                </select>
                <select name="core_value" class="org-acc-select" onchange="this.form.submit()">
                    <option value="">All Core Values</option>
                    @foreach (($coreValueOptions ?? []) as $cvOpt)
                        <option value="{{ $cvOpt }}" @selected(($selectedCoreValue ?? '') === $cvOpt)>{{ $cvOpt }}</option>
                    @endforeach
                </select>
                <select name="organization" class="org-acc-select" onchange="this.form.submit()" aria-label="Organization">
                    <option value="">All Organizations</option>
                    @foreach (($organizations ?? collect()) as $organizationName)
                        <option value="{{ $organizationName }}" @selected(($selectedOrganization ?? '') === $organizationName)>{{ $organizationName }}</option>
                    @endforeach
                </select>
            </div>
            @if (!empty($selectedSdg) || !empty($selectedCoreValue) || !empty($selectedOrganization) || (($selectedGender ?? 'all') !== 'all'))
                <a href="{{ route('office.accomplishment') }}" class="org-acc-badge-pill">Clear filters</a>
            @endif
        </form>

        <section class="org-acc-card" aria-label="Accomplishment Report Profile">
            <div class="org-acc-card-head">
                <h3><i class="bi bi-file-earmark-richtext" style="color:#8b1828;"></i> Report Dossier</h3>
                <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">
                    Post-activity record
                </span>
            </div>
            <p style="margin:0 0 1.1rem; color:#554d50; font-size:0.88rem; line-height:1.55;">
                This report documents actual results after an activity: what was delivered, who was reached, which objectives were addressed, and which files support the record. Activity approval status is maintained in Activities; this page is the evidence and narrative record.
            </p>
            <div class="org-info-fields-grid">
                <div class="org-info-field">
                    <small>Report type</small>
                    <strong>Semester accomplishment compilation</strong>
                </div>
                <div class="org-info-field">
                    <small>Prepared for</small>
                    <strong>Office of Student Organizations (OSO)</strong>
                </div>
                <div class="org-info-field">
                    <small>Activity records covered</small>
                    <strong>{{ $reportSnapshot['activities'] ?? 0 }} documented activities</strong>
                </div>
                <div class="org-info-field">
                    <small>Evidence basis</small>
                    <strong>Objectives, participant records, and uploaded documents</strong>
                </div>
            </div>
        </section>

        {{-- Report Period --}}
        <section class="org-acc-filter-bar" aria-label="Accomplishment Report Period">
            <div class="org-acc-filter-left">
                <div class="org-acc-filter-title">
                    <i class="bi bi-calendar2-range-fill"></i>
                    <span>Reporting Period:</span>
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
                <span class="org-acc-badge-pill" id="accPeriodLabelBadge">
                    <i class="bi bi-calendar-check"></i> 1st Semester · A.Y. 2025–2026
                </span>
            </div>
        </section>

        {{-- Report snapshot --}}
        <div class="org-kpi-row">
            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-pink">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiReportActivities">{{ $reportSnapshot['activities'] ?? 0 }}</div>
                </div>
                <h3 class="org-kpi-title">Activities Documented</h3>
                <p class="org-kpi-sub">Included in this accomplishment report</p>
            </article>

            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-green">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiReportParticipants">{{ number_format($reportSnapshot['participants'] ?? 0) }}</div>
                </div>
                <h3 class="org-kpi-title">Participant Reach</h3>
                <p class="org-kpi-sub">Recorded student beneficiaries</p>
            </article>

            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-blue">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiReportNarratives">{{ $reportSnapshot['narratives'] ?? 0 }}</div>
                </div>
                <h3 class="org-kpi-title">Narrative Records</h3>
                <p class="org-kpi-sub">Post-activity narratives submitted</p>
            </article>

            <article class="org-kpi-card">
                <div class="org-kpi-head">
                    <div class="org-kpi-icon is-amber">
                        <i class="bi bi-paperclip"></i>
                    </div>
                    <div class="org-kpi-num" id="kpiReportEvidence">{{ $reportSnapshot['evidence'] ?? 0 }}</div>
                </div>
                <h3 class="org-kpi-title">Evidence References</h3>
                <p class="org-kpi-sub">Compliance and supporting files linked</p>
            </article>
        </div>

        {{-- 2. Evidence & Supporting Documents (Expandable Section / Folder List) --}}
        <section class="org-acc-card" aria-label="Evidence and Supporting Documents">
            <div class="org-acc-card-head">
                <h3><i class="bi bi-folder-fill" style="color: #7a1222;"></i> Evidence &amp; Supporting Documents</h3>
                <span class="org-acc-badge-pill" id="folderCountBadge">{{ count($arFolders ?? []) }} Folder(s) Available</span>
            </div>

            <div class="org-acc-folders-list" id="accomplishmentFoldersList">
                @forelse (($arFolders ?? []) as $idx => $folder)
                    <div class="org-acc-folder-box {{ $idx === 0 ? 'is-open' : '' }}" id="folderBox{{ $folder['id'] }}">
                        <div class="org-acc-folder-header" onclick="toggleFolder({{ $folder['id'] }})">
                            <div class="org-acc-folder-left">
                                <div class="org-folder-icon-wrap">
                                    <i class="bi bi-folder2{{ $idx === 0 ? '-open' : '' }}"></i>
                                </div>
                                <div class="org-folder-meta">
                                    <strong>{{ $folder['name'] }}</strong>
                                    <small>{{ $folder['semester'] ?? '' }} · {{ count($folder['documents']) }} document(s)</small>
                                </div>
                            </div>
                        </div>
                        <div class="org-acc-folder-files" id="folderFiles{{ $folder['id'] }}" @if($idx !== 0) style="display: none;" @endif>
                            @forelse ($folder['documents'] as $doc)
                                <div class="org-doc-card">
                                    <div class="org-doc-left">
                                        <div class="org-doc-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                                        <div class="org-doc-meta">
                                            <strong title="{{ $doc['name'] }}">{{ $doc['name'] }}</strong>
                                            <small>{{ $doc['size'] }} · {{ $doc['date'] }}</small>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="org-doc-btn"
                                        title="Preview Document"
                                        aria-label="Preview {{ $doc['name'] }}"
                                        data-preview-title="{{ $doc['name'] }}"
                                        data-preview-format="{{ $doc['type'] ?? 'FILE' }}"
                                        data-preview-url="{{ $doc['url'] }}"
                                        onclick="openAccomplishmentDocumentPreview(this)">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </div>
                            @empty
                                <p style="font-size:0.82rem;color:#7a7074;margin:0;">No evidence files in this folder yet — upload the narrative, photos, attendance sheet, or other supporting record.</p>
                            @endforelse
                            <div class="org-report-pagination org-folder-pagination" data-folder-pagination="{{ $folder['id'] }}" aria-label="Folder document pagination">
                                <span data-folder-pagination-info></span>
                                <nav class="org-report-pagination-nav" data-folder-pagination-nav aria-label="Folder document pages"></nav>
                            </div>
                        </div>
                    </div>
                @empty
                    <p style="font-size:0.85rem;color:#7a7074;margin:0;">No archived evidence folders yet. Upload the AR document above; OSO creates the archive folder after accepting the complete AR + FR package.</p>
                @endforelse
            </div>
        </section>

        {{-- Objectives, results, participation, and evidence register --}}
        <section class="org-acc-card" aria-label="Detailed Activity Accomplishment Table">
            <div class="org-acc-card-head">
                <h3><i class="bi bi-table" style="color: #7a1222;"></i> Objectives, Results &amp; Evidence Register</h3>
                <span class="org-acc-badge-pill" id="tableCountBadge">0 Records</span>
            </div>

            <div class="org-table-controls">
                <span style="font-size:0.8rem;color:#7a7074;">Use the evidence folders below to attach the narrative, photos, attendance sheets, and supporting records.</span>
                <input type="text" id="activitySearchInput" class="org-search-input" placeholder="Search activity, objective, evidence..." autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="searchActivityTable(this.value)">
            </div>

            <div style="overflow-x: auto; width: 100%;">
                <table class="org-acc-table">
                    <thead>
                        <tr>
                            <th>Activity Name</th>
                            <th>Implementation</th>
                            <th>Objective / Result</th>
                            <th>Participants</th>
                            <th>Evidence</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="activityTableBody">
                        {{-- Populated dynamically via JS --}}
                    </tbody>
                </table>
            </div>
            <div class="org-report-pagination" id="accomplishmentActivityPagination" aria-label="Accomplishment activity pagination">
                <span id="accomplishmentActivityPaginationInfo"></span>
                <nav class="org-report-pagination-nav" id="accomplishmentActivityPaginationNav" aria-label="Accomplishment activity pages"></nav>
            </div>
        </section>

        {{-- 5. Report Review Details & Recommendations --}}
        <div class="org-bottom-2col">
            {{-- Report review details --}}
            <section class="org-acc-card" aria-label="Report Review Details">
                <div class="org-acc-card-head">
                    <h3><i class="bi bi-person-check-fill" style="color: #16a34a;"></i> Report Review Details</h3>
                    <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">
                        <i class="bi bi-shield-lock-fill"></i> Review record
                    </span>
                </div>
                <div class="org-info-fields-grid">
                    <div class="org-info-field">
                        <small>Reviewed By</small>
                        <strong id="verAuditor">Prof. Maria Christina A. Del Rosario, PhD</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Designation / Office</small>
                        <strong id="verOffice">Director, Office of Student Organizations (OSO)</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Reviewed On</small>
                        <strong id="verDate">October 14, 2026 · 04:30 PM</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Report Reference</small>
                        <strong style="font-family: monospace; font-size: 0.78rem; color: #7a1222;">AR-BATSTATEU-2026-0042</strong>
                    </div>
                </div>
            </section>

            {{-- Reviewer remarks and recommendations --}}
            <section class="org-acc-card" aria-label="Reviewer Remarks and Recommendations">
                <div class="org-acc-card-head">
                    <h3><i class="bi bi-chat-square-text-fill" style="color: #7a1222;"></i> Reviewer Remarks &amp; Recommendations</h3>
                    <span class="org-acc-badge-pill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Recorded</span>
                </div>
                <div style="background: #faf4f5; border: 1.5px solid #f0e6e8; border-radius: 14px; padding: 1rem 1.15rem; font-size: 0.84rem; color: #40363a; line-height: 1.5;" id="remarksText">
                    Review notes and recommendations are recorded here after the narrative, participant record, and supporting evidence are checked.
                </div>
                <div style="margin-top: 0.85rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.76rem; color: #7a7074;">
                    <i class="bi bi-patch-check-fill" style="color: #16a34a;"></i>
                    <span>Ready for OSO review and archive when the evidence set is complete.</span>
                </div>
            </section>
        </div>

        </div>

        {{-- Document Preview Modal --}}
        <dialog id="accomplishmentDocumentPreviewModal" style="width:min(920px, calc(100% - 2rem)); max-width:920px; border:0; border-radius:20px; padding:0; box-shadow:0 24px 70px rgba(31, 14, 20, 0.28);">
            <div style="background:#ffffff; border:1px solid #f0e6e8; border-radius:20px; overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid #f0e6e8;">
                    <div style="min-width:0;">
                        <span style="display:block;color:#7a1222;font-size:0.72rem;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">Document Preview</span>
                        <strong id="accomplishmentDocumentPreviewTitle" style="display:block;margin-top:0.2rem;color:#1a1618;font-size:1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Supporting document</strong>
                    </div>
                    <button type="button" class="org-modal-close-btn" aria-label="Close document preview" onclick="closeAccomplishmentDocumentPreview()">&times;</button>
                </div>
                <div id="accomplishmentDocumentPreviewBody" style="background:#f7f2f3;min-height:220px;max-height:68vh;overflow:auto;padding:1rem;">
                    <p style="margin:0;color:#7a7074;font-size:0.85rem;">Loading preview…</p>
                </div>
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.65rem;padding:0.9rem 1.25rem;border-top:1px solid #f0e6e8;">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeAccomplishmentDocumentPreview()">Close</button>
                    <a id="accomplishmentDocumentPreviewDownload" class="org-btn org-btn-primary" href="#" target="_blank" rel="noopener" style="text-decoration:none;">
                        <i class="bi bi-download"></i> Download Document
                    </a>
                </div>
            </div>
        </dialog>

    <script src="{{ asset('js/vendor/jszip.min.js') }}"></script>
    <script src="{{ asset('js/vendor/docx-preview.min.js') }}"></script>
    <script>
        window.arLive = @json($accomplishmentRows ?? []);
        window.arLiveLoaded = true;
        const activityDossierBase = @json(route('office.activities'));
        // Accomplishment Dataset Dictionary
        const accomplishmentDatasets = {
            '2025-2026_1st Semester': {
                periodLabel: '1st Semester · A.Y. 2025–2026',
                totalActivities: 8,
                participants: 1420,
                narratives: 8,
                evidence: 16,
                activities: [
                    { name: 'Innovation Fair Booth Series', date: 'Jul 05–08, 2026', venue: 'CEAFA Gymnasium', type: 'Workshop', participants: 450, sdg: 'SDG 9 · Innovation', objectives: 'Showcase student-led prototypes and connect participants with innovation mentors.', narrative: 'Booth demonstrations and mentor consultations were delivered across the four-day fair.', evidenceCount: 3 },
                    { name: 'Leadership Summit 2026', date: 'Aug 14–16, 2026', venue: 'Tagaytay City ICC', type: 'Seminar', participants: 180, sdg: 'SDG 4 · Quality Edu', objectives: 'Develop student officers\' leadership and governance skills.', narrative: 'Three-day workshops, panel sessions, and officer planning activities were completed.', evidenceCount: 2 },
                    { name: 'Volunteer Appreciation Day', date: 'Mar 15, 2026', venue: 'Student Center', type: 'General Assembly', participants: 120, sdg: 'SDG 17 · Partnerships', objectives: 'Recognize volunteer contributions and document partner engagement.', narrative: 'Volunteer recognition and partner feedback activities were completed.', evidenceCount: 2 },
                    { name: 'Campus Wellness Week', date: 'May 18–22, 2026', venue: 'Campus Grounds', type: 'Community Outreach', participants: 320, sdg: 'SDG 3 · Good Health', objectives: 'Promote accessible wellness activities for the campus community.', narrative: 'Wellness booths, peer activities, and referral information reached 320 participants.', evidenceCount: 3 },
                    { name: 'Python & AI Bootcamp', date: 'Sep 10–12, 2026', venue: 'Computer Lab 3', type: 'Workshop', participants: 95, sdg: 'SDG 4 · Quality Edu', objectives: 'Build beginner competence in Python and responsible AI use.', narrative: 'Participants completed guided exercises and a small applied project.', evidenceCount: 2 },
                    { name: 'Clean & Green Tree Planting Drive', date: 'Sep 28, 2026', venue: 'Alangilan Eco Park', type: 'Community Outreach', participants: 155, sdg: 'SDG 15 · Life on Land', objectives: 'Contribute to campus and community environmental stewardship.', narrative: 'Tree planting and site clean-up were completed with community volunteers.', evidenceCount: 2 },
                    { name: 'BatStateU Sportsfest 2026', date: 'Sep 20–24, 2026', venue: 'Athletic Field', type: 'Workshop', participants: 80, sdg: 'SDG 3 · Good Health', objectives: 'Encourage inclusive physical activity and student participation.', narrative: 'Inter-organization sports activities are being documented for the final report.', evidenceCount: 1 },
                    { name: 'Cybersecurity Awareness Forum', date: 'Oct 25, 2026', venue: 'Main Auditorium', type: 'Seminar', participants: 20, sdg: 'SDG 9 · Innovation', objectives: 'Improve awareness of safe digital practices among student leaders.', narrative: 'Forum materials and attendance records are pending final upload.', evidenceCount: 1 }
                ],
                auditor: 'Prof. Maria Christina A. Del Rosario, PhD',
                office: 'Director, Office of Student Organizations (OSO)',
                verDate: 'October 14, 2026 · 04:30 PM',
                remarks: '"All narrative reports, high-resolution photo evidence, and certified participant manifests comply with university accreditation standards. 100% of planned institutional objectives were achieved."'
            },
            '2024-2025_2nd Semester': {
                periodLabel: '2nd Semester · A.Y. 2024–2025',
                totalActivities: 5,
                participants: 980,
                narratives: 5,
                evidence: 11,
                activities: [
                    { name: 'CodeSprint Hackathon 2025', date: 'Apr 12–14, 2025', venue: 'CEAFA Amphitheater', type: 'Workshop', participants: 310, sdg: 'SDG 9 · Innovation', objectives: 'Create working prototypes through a collaborative student hackathon.', narrative: 'Teams presented working prototypes and received mentor feedback.', evidenceCount: 2 },
                    { name: 'Women in Tech Career Talk', date: 'May 05, 2025', venue: 'AVR 2', type: 'Seminar', participants: 220, sdg: 'SDG 5 · Gender Equality', objectives: 'Expose students to inclusive technology careers and role models.', narrative: 'Career talks and an open mentorship forum were delivered.', evidenceCount: 2 },
                    { name: 'Barangay Computer Literacy Outreach', date: 'May 22, 2025', venue: 'Brgy. Alangilan Hall', type: 'Community Outreach', participants: 150, sdg: 'SDG 4 · Quality Edu', objectives: 'Provide basic digital literacy support to community learners.', narrative: 'Hands-on sessions covered basic productivity and online safety skills.', evidenceCount: 3 },
                    { name: 'Web Development Masterclass', date: 'Jun 02, 2025', venue: 'Online / Zoom', type: 'Workshop', participants: 180, sdg: 'SDG 9 · Innovation', objectives: 'Strengthen practical web development skills through guided practice.', narrative: 'Participants completed a guided front-end build and shared outputs.', evidenceCount: 2 },
                    { name: 'Year-End General Assembly & Turnover', date: 'Jun 20, 2025', venue: 'Student Center', type: 'General Assembly', participants: 120, sdg: 'SDG 17 · Partnerships', objectives: 'Document annual accomplishments and transfer responsibilities to the next officers.', narrative: 'Annual highlights, turnover notes, and next-cycle priorities were presented.', evidenceCount: 2 }
                ],
                auditor: 'Engr. Daniel Ramirez',
                office: 'Student Activities Coordinator, OSO',
                verDate: 'June 25, 2025 · 02:00 PM',
                remarks: '"Completed semestral turnover and accomplishment reporting. All milestone deliverables verified."'
            }
        };

        let currentActivityList = [];
        let currentActivityPage = 1;
        const ACCOMPLISHMENT_PAGE_SIZE = 7;
        const ACCOMPLISHMENT_FOLDER_PAGE_SIZE = 5;

        function accomplishmentPaginate(items, page, pageSize) {
            const safeItems = Array.isArray(items) ? items : [];
            const totalPages = Math.max(1, Math.ceil(safeItems.length / pageSize));
            const safePage = Math.min(Math.max(Number(page) || 1, 1), totalPages);
            const start = safeItems.length ? (safePage - 1) * pageSize : 0;
            const end = Math.min(start + pageSize, safeItems.length);
            return { items: safeItems.slice(start, end), total: safeItems.length, page: safePage, totalPages, start, end };
        }

        function renderAccomplishmentPagination({ barId, infoId, navId, total, page, pageSize, label, handler }) {
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
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[character]);

        function renderActivityTable(items) {
            const tbody = document.getElementById('activityTableBody');
            tbody.innerHTML = '';
            const page = accomplishmentPaginate(items, currentActivityPage, ACCOMPLISHMENT_PAGE_SIZE);
            currentActivityPage = page.page;

            if (!page.total) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center;color:#7a7074;padding:1.6rem;">
                            No accomplishment records match this reporting period or search.
                        </td>
                    </tr>`;
                document.getElementById('tableCountBadge').textContent = '0 Records';
                renderAccomplishmentPagination({
                    barId: 'accomplishmentActivityPagination',
                    infoId: 'accomplishmentActivityPaginationInfo',
                    navId: 'accomplishmentActivityPaginationNav',
                    total: 0,
                    page: 1,
                    pageSize: ACCOMPLISHMENT_PAGE_SIZE,
                    label: 'accomplishment records',
                    handler: 'goToAccomplishmentActivityPage'
                });
                return;
            }

            page.items.forEach(item => {
                const tr = document.createElement('tr');
                const objective = (item.objectives || '').trim();
                const narrative = (item.narrative || '').trim();
                const evidenceCount = Number(item.evidenceCount) || 0;
                const resultText = narrative
                    ? escapeHtml(narrative)
                    : '<span style="color:#c2410c;">Result narrative not recorded yet.</span>';
                const objectiveText = objective
                    ? `<small style="display:block;margin-top:0.35rem;color:#7a7074;"><strong>Planned objective:</strong> ${escapeHtml(objective)}</small>`
                    : '';
                const evidenceText = evidenceCount
                    ? `${evidenceCount} linked file${evidenceCount === 1 ? '' : 's'}`
                    : 'Evidence pending';
                const approvedBudget = Number(item.approvedBudget) || 0;
                const implementedBudget = Number(item.implementedBudget) || 0;
                const financialReference = approvedBudget || implementedBudget
                    ? `<small style="display:block;margin-top:0.35rem;color:#7a7074;">Budget ref: ₱${approvedBudget.toLocaleString()} approved · ₱${implementedBudget.toLocaleString()} utilized</small>`
                    : '';
                const evidenceStyle = evidenceCount
                    ? 'background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0;'
                    : 'background:#fff7ed; color:#c2410c; border:1px solid #fed7aa;';
                const activityUrl = item.slug
                    ? escapeHtml(`${activityDossierBase}?activity=${encodeURIComponent(item.slug)}`)
                    : '';

                tr.innerHTML = `
                    <td><strong>${escapeHtml(item.name)}</strong></td>
                    <td>
                        <div>${escapeHtml(item.date)}</div>
                        <small style="color:#7a7074;"><i class="bi bi-geo-alt"></i> ${escapeHtml(item.venue)}</small>
                        <div style="margin-top:0.35rem;"><span style="background:#faf4f5;border:1px solid #f0e6e8;padding:0.15rem 0.5rem;border-radius:6px;font-size:0.72rem;font-weight:600;">${escapeHtml(item.type)}</span></div>
                        ${financialReference}
                    </td>
                    <td>
                        <div style="font-size:0.8rem;line-height:1.45;">${resultText}${objectiveText}</div>
                        ${item.sdg && item.sdg !== '—' ? `<small style="display:block;margin-top:0.35rem;color:#7a1222;font-weight:700;">${escapeHtml(item.sdg)}</small>` : ''}
                    </td>
                    <td><strong>${Number(item.participants || 0).toLocaleString()} Students</strong></td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.72rem;font-weight:700;${evidenceStyle}">
                            <i class="bi bi-${evidenceCount ? 'check2-circle' : 'exclamation-circle'}"></i> ${evidenceText}
                        </span>
                        ${narrative ? '<small style="display:block;margin-top:0.35rem;color:#7a7074;">Narrative recorded</small>' : ''}
                    </td>
                    <td>
                        ${activityUrl
                            ? `<a href="${activityUrl}" class="org-doc-btn" title="Open Activity Dossier" style="text-decoration:none;display:inline-flex;">
                                <i class="bi bi-folder-symlink-fill"></i>
                               </a>`
                            : `<button type="button" class="org-doc-btn" title="View Activity Dossier" onclick="alert('Open the activity dossier to review the full accomplishment record.')">
                                <i class="bi bi-folder-symlink-fill"></i>
                               </button>`}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('tableCountBadge').textContent = page.total + ' Records';
            renderAccomplishmentPagination({
                barId: 'accomplishmentActivityPagination',
                infoId: 'accomplishmentActivityPaginationInfo',
                navId: 'accomplishmentActivityPaginationNav',
                total: page.total,
                page: page.page,
                pageSize: ACCOMPLISHMENT_PAGE_SIZE,
                label: 'accomplishment records',
                handler: 'goToAccomplishmentActivityPage'
            });
        }

        const accomplishmentFolderPages = {};

        function renderAccomplishmentFolderPage(folderId, requestedPage = 1) {
            const folder = document.getElementById(`folderBox${folderId}`);
            if (!folder) return;
            const docs = Array.from(folder.querySelectorAll('.org-acc-folder-files > .org-doc-card'));
            const pagination = folder.querySelector('[data-folder-pagination]');
            const info = folder.querySelector('[data-folder-pagination-info]');
            const nav = folder.querySelector('[data-folder-pagination-nav]');
            if (!pagination || !info || !nav) return;

            const page = accomplishmentPaginate(docs, requestedPage, ACCOMPLISHMENT_FOLDER_PAGE_SIZE);
            accomplishmentFolderPages[folderId] = page.page;
            docs.forEach((doc, index) => {
                doc.style.display = index >= page.start && index < page.end ? '' : 'none';
            });

            if (page.total <= ACCOMPLISHMENT_FOLDER_PAGE_SIZE) {
                pagination.style.display = 'none';
                info.textContent = '';
                nav.innerHTML = '';
                return;
            }

            info.innerHTML = `Showing <strong>${page.start + 1}</strong> to <strong>${page.end}</strong> of <strong>${page.total}</strong> folder documents`;
            const totalPages = page.totalPages;
            let html = `<button type="button" class="org-report-page-btn" ${page.page === 1 ? 'disabled' : ''} onclick="goToAccomplishmentFolderPage(${JSON.stringify(String(folderId))}, ${page.page - 1})" aria-label="Previous folder page"><i class="bi bi-chevron-left"></i></button>`;
            for (let p = 1; p <= totalPages; p += 1) {
                if (totalPages <= 7 || p === 1 || p === totalPages || (p >= page.page - 1 && p <= page.page + 1)) {
                    html += `<button type="button" class="org-report-page-btn ${p === page.page ? 'is-active' : ''}" onclick="goToAccomplishmentFolderPage(${JSON.stringify(String(folderId))}, ${p})" aria-label="Folder page ${p}" ${p === page.page ? 'aria-current="page"' : ''}>${p}</button>`;
                } else if (p === page.page - 2 || p === page.page + 2) {
                    html += '<span aria-hidden="true">&hellip;</span>';
                }
            }
            html += `<button type="button" class="org-report-page-btn" ${page.page === totalPages ? 'disabled' : ''} onclick="goToAccomplishmentFolderPage(${JSON.stringify(String(folderId))}, ${page.page + 1})" aria-label="Next folder page"><i class="bi bi-chevron-right"></i></button>`;
            pagination.style.display = 'flex';
            nav.innerHTML = html;
        }

        function initAccomplishmentFolderPagination() {
            document.querySelectorAll('.org-acc-folder-box[id^="folderBox"]').forEach((folder) => {
                renderAccomplishmentFolderPage(folder.id.replace('folderBox', ''), 1);
            });
        }

        function goToAccomplishmentFolderPage(folderId, page) {
            renderAccomplishmentFolderPage(folderId, page);
        }

        function goToAccomplishmentActivityPage(page) {
            currentActivityPage = page;
            const query = document.getElementById('activitySearchInput')?.value || '';
            searchActivityTable(query, false);
        }

        function emptyAccomplishmentDataset(year, sem) {
            return {
                periodLabel: `No records · ${sem} · A.Y. ${year}`,
                totalActivities: 0,
                participants: 0,
                narratives: 0,
                evidence: 0,
                activities: [],
                auditor: 'Office of Student Organizations (OSO)',
                office: 'No records for selected reporting period',
                verDate: 'Not available',
                remarks: 'No completed activity records match the selected academic year and semester.'
            };
        }

        function rowAcademicPeriod(row) {
            if (row?.academic_year && row?.semester) {
                return { year: row.academic_year, semester: row.semester };
            }
            if (!row?.date_key) return { year: null, semester: null };
            const date = new Date(`${row.date_key}T00:00:00`);
            if (Number.isNaN(date.getTime())) return { year: null, semester: null };
            const month = date.getMonth() + 1;
            const startYear = month < 8 ? date.getFullYear() - 1 : date.getFullYear();
            return {
                year: `${startYear}-${startYear + 1}`,
                semester: month >= 8 && month <= 12
                    ? '1st Semester'
                    : (month <= 5 ? '2nd Semester' : 'Midyear')
            };
        }

        function buildLiveAccomplishmentDataset(rows, year, sem) {
            const matchingRows = rows.filter((row) => {
                const period = rowAcademicPeriod(row);
                return period.year === year && period.semester === sem;
            });

            const activities = matchingRows.map((r) => ({
                name: r.title,
                slug: r.slug || '',
                date: r.dateLabel || 'TBA',
                venue: r.venue || 'TBA',
                type: r.typeLabel || 'General',
                participants: Number(r.participants) || 0,
                sdg: ((r.sdg_goals || []).join(', ')) || '—',
                narrative: r.narrative || '',
                objectives: r.objectives || '',
                evidenceCount: Number(r.evidenceCount) || 0,
                approvedBudget: Number(r.approvedBudget) || 0,
                implementedBudget: Number(r.implementedBudget) || 0,
                remainingBudget: Number(r.remainingBudget) || 0
            }));

            return {
                periodLabel: matchingRows.length
                    ? `Live records · ${sem} · A.Y. ${year}`
                    : `No records · ${sem} · A.Y. ${year}`,
                totalActivities: activities.length,
                participants: activities.reduce((sum, row) => sum + row.participants, 0),
                narratives: activities.filter((row) => String(row.narrative || '').trim()).length,
                evidence: activities.reduce((sum, row) => sum + row.evidenceCount, 0),
                activities,
                auditor: 'Office of Student Organizations (OSO)',
                office: matchingRows.length ? 'Live activity records' : 'No records for selected reporting period',
                verDate: new Date().toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }),
                remarks: matchingRows.length
                    ? '"Synced live from the organization activities database. Add the narrative and supporting files for each completed activity before final submission."'
                    : 'No completed activity records match the selected academic year and semester.'
            };
        }

        function switchAccomplishmentData() {
            const year = document.getElementById('accYearSelect').value;
            const sem = document.getElementById('accSemSelect').value;
            const key = `${year}_${sem}`;
            const liveRows = Array.isArray(window.arLive) ? window.arLive : [];

            // Live rows remain available for every period change. The previous
            // implementation consumed them on the first render, so every
            // later period silently fell back to the default dataset.
            const data = liveRows.length
                ? buildLiveAccomplishmentDataset(liveRows, year, sem)
                : (accomplishmentDatasets[key] || emptyAccomplishmentDataset(year, sem));
            currentActivityList = data.activities;

            // 1. Reporting period
            document.getElementById('accPeriodLabelBadge').innerHTML = `<i class="bi bi-calendar-check"></i> ${data.periodLabel}`;

            // 2. Report snapshot metrics
            document.getElementById('kpiReportActivities').textContent = data.totalActivities;
            document.getElementById('kpiReportParticipants').textContent = data.participants.toLocaleString();
            document.getElementById('kpiReportNarratives').textContent = data.narratives ?? 0;
            document.getElementById('kpiReportEvidence').textContent = data.evidence ?? 0;

            // 3. Objectives, results, participation, and evidence register
            currentActivityPage = 1;
            renderActivityTable(data.activities);

            // 4. Report review notes
            document.getElementById('verAuditor').textContent = data.auditor;
            document.getElementById('verOffice').textContent = data.office;
            document.getElementById('verDate').textContent = data.verDate;
            document.getElementById('remarksText').textContent = data.remarks;
        }

        function searchActivityTable(query, resetPage = true) {
            const q = query.toLowerCase().trim();
            if (resetPage) currentActivityPage = 1;
            if (!q) {
                renderActivityTable(currentActivityList);
                return;
            }
            const filtered = currentActivityList.filter(a =>
                String(a.name || '').toLowerCase().includes(q) ||
                String(a.type || '').toLowerCase().includes(q) ||
                String(a.venue || '').toLowerCase().includes(q) ||
                String(a.sdg || '').toLowerCase().includes(q) ||
                String(a.objectives || '').toLowerCase().includes(q) ||
                String(a.narrative || '').toLowerCase().includes(q) ||
                String(a.evidenceCount || '').toLowerCase().includes(q)
            );
            renderActivityTable(filtered);
        }

        let accomplishmentPreviewRequest = 0;

        function showAccomplishmentPreviewMessage(message, tone = '#7a7074') {
            const body = document.getElementById('accomplishmentDocumentPreviewBody');
            if (!body) return;
            body.innerHTML = '';
            const messageEl = document.createElement('p');
            messageEl.style.margin = '0';
            messageEl.style.color = tone;
            messageEl.style.fontSize = '0.85rem';
            messageEl.textContent = message;
            body.appendChild(messageEl);
        }

        function openAccomplishmentDocumentPreview(button) {
            const title = button?.dataset?.previewTitle || 'Supporting document';
            const format = (button?.dataset?.previewFormat || 'FILE').toUpperCase();
            const url = button?.dataset?.previewUrl || '';
            const dialog = document.getElementById('accomplishmentDocumentPreviewModal');
            const titleEl = document.getElementById('accomplishmentDocumentPreviewTitle');
            const downloadEl = document.getElementById('accomplishmentDocumentPreviewDownload');
            const body = document.getElementById('accomplishmentDocumentPreviewBody');
            if (!dialog || !body) return;

            titleEl.textContent = title;
            downloadEl.href = url || '#';
            downloadEl.style.display = url ? 'inline-flex' : 'none';
            showAccomplishmentPreviewMessage('Preparing preview…');
            if (!dialog.open) dialog.showModal();

            const requestId = ++accomplishmentPreviewRequest;
            if (!url) {
                showAccomplishmentPreviewMessage('This document does not have a stored file link.', '#c2410c');
                return;
            }

            if (format === 'PDF') {
                body.innerHTML = '';
                const frame = document.createElement('iframe');
                frame.src = url;
                frame.title = `${title} PDF preview`;
                frame.loading = 'lazy';
                body.appendChild(frame);
                return;
            }

            if (['PNG', 'JPG', 'JPEG', 'WEBP', 'GIF'].includes(format)) {
                body.innerHTML = '';
                const image = document.createElement('img');
                image.src = url;
                image.alt = title;
                body.appendChild(image);
                return;
            }

            if (format !== 'DOCX') {
                showAccomplishmentPreviewMessage(`In-page preview is unavailable for .${format.toLowerCase()} files. Use Download Document to open it in the appropriate app.`, '#c2410c');
                return;
            }

            (async () => {
                try {
                    if (!window.docx || typeof window.docx.renderAsync !== 'function') {
                        throw new Error('Preview engine unavailable.');
                    }
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const blob = await response.blob();
                    if (requestId !== accomplishmentPreviewRequest) return;
                    body.innerHTML = '';
                    await window.docx.renderAsync(blob, body, null, { breakPages: false });
                } catch (error) {
                    if (requestId !== accomplishmentPreviewRequest) return;
                    showAccomplishmentPreviewMessage('In-page preview is unavailable for this Word file. Use Download Document to open it in Microsoft Word or another compatible app.', '#c2410c');
                }
            })();
        }

        function closeAccomplishmentDocumentPreview() {
            accomplishmentPreviewRequest++;
            const dialog = document.getElementById('accomplishmentDocumentPreviewModal');
            if (dialog?.open) dialog.close();
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
            initAccomplishmentFolderPagination();
            switchAccomplishmentData();
        });
    </script>
@endsection
