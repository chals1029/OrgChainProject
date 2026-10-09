@extends('org.layout')

@section('title', 'Financial Report & Liquidation')

@section('header')
    <h1><strong>Financial Report &amp; Liquidation</strong></h1>
    <p class="org-welcome">Auditable financial statements, itemized ledger entries, supporting receipts, and official OSO verification.</p>
@endsection

@section('actions')
    <div style="display: flex; flex-wrap:wrap; gap: 0.6rem; align-items: center;">
        <a href="{{ route('office.updates.templates.document', 'source-accomplishment-financial') }}" class="org-btn org-btn-outline">
            <i class="bi bi-file-earmark-word"></i> Official Report Format
        </a>
        <a href="{{ route('office.budget', request()->query()) }}" class="org-btn org-btn-outline">
            <i class="bi bi-bar-chart-line"></i> Budget Utilization
        </a>
        <a href="{{ route('office.financial.print', request()->query()) }}" target="_blank" rel="noopener" class="org-btn org-btn-primary">
            <i class="bi bi-file-earmark-pdf-fill"></i> Print / Save PDF
        </a>
        <a href="{{ route('office.budget.receipts.package', ['organization' => $selectedOrganization, 'academic_year' => $selectedYear, 'semester' => $selectedSemester]) }}" class="org-btn org-btn-outline">Download receipt compilation</a>
    </div>
@endsection

@section('content')
    @include('org.partials.semester-report-status', [
        'reportType' => 'fr',
        'reportBundle' => $reportBundle ?? [],
        'organizations' => $organizations ?? collect(),
        'selectedOrganization' => $selectedOrganization ?? '',
        'selectedSemester' => $selectedSemester ?? '1st Semester',
        'selectedYear' => $selectedYear ?? '2025-2026',
    ])
    <style>
        .org-fin-container {
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
        }

        /* 0. Top Interactive Filter Toolbar */
        .org-fin-filter-bar {
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

        .org-fin-filter-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-wrap: wrap;
        }

        .org-fin-filter-title {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.82rem;
            font-weight: 800;
            color: #7a1222;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .org-fin-select {
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

        .org-fin-select:focus {
            border-color: #7a1222;
        }

        .org-fin-badge-pill {
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
        .org-fin-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            position: relative;
        }

        .org-fin-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .org-fin-card-head h3 {
            font-size: 1rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* 1 & 2. Info Panels Grid */
        .org-info-panels-grid {
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

        /* 10. Financial Details Data Table */
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
            width: 220px;
            transition: border-color 0.15s ease;
        }

        .org-search-input:focus {
            border-color: #7a1222;
        }

        .org-fin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        .org-fin-table th {
            padding: 0.75rem 0.9rem;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #7a7074;
            border-bottom: 1.5px solid #f2e9eb;
            background: #faf6f7;
            letter-spacing: 0.03em;
        }

        .org-fin-table td {
            padding: 0.85rem 0.9rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-fin-table tr:hover td {
            background: #fffafa;
        }

        .org-fin-table tfoot td {
            font-weight: 800;
            font-size: 0.9rem;
            border-top: 2px solid #e8dedf;
            background: #ffffff;
            padding-top: 1rem;
        }

        .org-flow-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .org-flow-in {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .org-flow-out {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* 11. Supporting Documents Section */
        .org-docs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .org-doc-card {
            background: #faf7f8;
            border: 1.5px solid #f0e6e8;
            border-radius: 14px;
            padding: 0.95rem 1.1rem;
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
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
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
            max-width: 170px;
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
            width: 30px;
            height: 30px;
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

        /* 12, 13, 14. Bottom 3-Column Grid (Verification, Remarks, History) */
        .org-bottom-3col {
            display: grid;
            grid-template-columns: 1fr 1fr 1.15fr;
            gap: 1.25rem;
        }

        .org-timeline-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
            padding-left: 0.5rem;
        }

        .org-timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            position: relative;
        }

        .org-timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 14px;
            top: 26px;
            width: 2px;
            height: calc(100% + 4px);
            background: #f0e6e8;
        }

        .org-tl-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            flex-shrink: 0;
            z-index: 2;
        }

        .org-tl-badge.is-green { background: #dcfce7; color: #16a34a; }
        .org-tl-badge.is-blue { background: #e0f2fe; color: #0284c7; }
        .org-tl-badge.is-maroon { background: #fdf0f2; color: #7a1222; }

        .org-tl-content strong {
            display: block;
            font-size: 0.82rem;
            color: #1a1618;
        }

        .org-tl-content small {
            display: block;
            font-size: 0.72rem;
            color: #7a7074;
            margin-top: 0.1rem;
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

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .org-info-panels-grid,
            .org-bottom-3col,
            .org-docs-grid {
                grid-template-columns: 1fr;
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

            .org-info-fields-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="org-fin-container">
        {{-- OSO reviews the financial report; organization fund setup belongs in Budget Utilization. --}}
        @if (($office->office_role ?? '') === 'so')
            @include('org.partials.fund-balances')
        @endif

        {{-- Report filters live in the Financial Report status panel above. --}}

        {{-- 1 & 2. Organization Information & Activity/Project Information Panels --}}
        <div class="org-info-panels-grid">
            {{-- 1. Organization Information Panel --}}
            <section class="org-fin-card" aria-label="Organization Information">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-building-check" style="color: #7a1222;"></i> Organization Information</h3>
                    <span class="org-fin-badge-pill" id="orgCategoryBadge">{{ $financialDataset['orgCategory'] }}</span>
                </div>
                <div class="org-info-fields-grid">
                    <div class="org-info-field">
                        <small>Organization Name</small>
                        <strong id="orgNameVal">{{ $financialDataset['orgName'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Academic Year &amp; Term</small>
                        <strong id="orgTermVal">{{ $financialDataset['term'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Report Period</small>
                        <strong id="orgPeriodVal">{{ $financialDataset['period'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Faculty Advisor</small>
                        <strong id="orgAdvisorVal">{{ $financialDataset['advisor'] }}</strong>
                    </div>
                </div>
            </section>

            {{-- 2. Activity / Project Information Panel --}}
            <section class="org-fin-card" aria-label="Activity and Project Information">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-folder2-open" style="color: #7a1222;"></i> Activity / Project Scope</h3>
                    <span class="org-fin-badge-pill" id="actScopePill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">{{ $financialDataset['actScope'] }}</span>
                </div>
                <div class="org-info-fields-grid">
                    <div class="org-info-field">
                        <small>Activity / Project Name</small>
                        <strong id="actNameVal">{{ $financialDataset['actName'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Activity Type / Category</small>
                        <strong id="actTypeVal">{{ $financialDataset['actType'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Execution Date</small>
                        <strong id="actDateVal">{{ $financialDataset['actDate'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Venue / Campus Location</small>
                        <strong id="actVenueVal">{{ $financialDataset['actVenue'] }}</strong>
                    </div>
                </div>
            </section>
        </div>

        {{-- 7. Financial Details (Data Table) --}}
        <section class="org-fin-card" aria-label="Financial Details and Itemized Transactions">
            <div class="org-fin-card-head">
                <h3><i class="bi bi-table" style="color: #7a1222;"></i> Itemized Financial Ledger</h3>
                <span class="org-fin-badge-pill" id="tableRecordCountBadge">8 Transactions</span>
            </div>

            <div class="org-table-controls">
                <div class="org-tab-buttons">
                    <button type="button" class="org-tab-btn is-active" onclick="filterLedger('all', this)">All Flows</button>
                    <button type="button" class="org-tab-btn" onclick="filterLedger('inflow', this)">Inflows Only</button>
                    <button type="button" class="org-tab-btn" onclick="filterLedger('outflow', this)">Outflows Only</button>
                </div>
                <input type="text" id="ledgerSearchInput" class="org-search-input" placeholder="Search payee, item, OR #..." autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="searchLedger(this.value)">
            </div>

            <div style="overflow-x: auto; width: 100%;">
                <table class="org-fin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description / Payee</th>
                            <th>Category</th>
                            <th>Flow Type</th>
                            <th>Amount</th>
                            <th>Receipt / Ref No.</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerTableBody">
                        {{-- Populated dynamically via switchFinancialReport --}}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">TOTAL RECEIPT-SUPPORTED EXPENSES</td>
                            <td id="tableFooterNet">Php {{ number_format($periodExpenseTotal, 2) }}</td>
                            <td colspan="2">Separate from the organization’s annual cash balance</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="org-report-pagination" id="financialLedgerPagination" aria-label="Financial ledger pagination">
                <span id="financialLedgerPaginationInfo"></span>
                <nav class="org-report-pagination-nav" id="financialLedgerPaginationNav" aria-label="Financial ledger pages"></nav>
            </div>
        </section>

        {{-- 11. Supporting Documents (Document/File List) --}}
        <section class="org-fin-card" aria-label="Supporting Documents and Receipts">
            <div class="org-fin-card-head">
                <h3><i class="bi bi-file-earmark-check" style="color: #7a1222;"></i> Supporting Documents &amp; Vouchers</h3>
                <span class="org-fin-badge-pill" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;">
                    <i class="bi bi-receipt"></i> {{ $receiptRows->count() }} recorded receipts
                </span>
            </div>
            <div class="org-docs-grid" id="supportingDocsGrid">
                {{-- Populated dynamically --}}
            </div>
            <div class="org-report-pagination" id="financialDocumentsPagination" aria-label="Financial document pagination">
                <span id="financialDocumentsPaginationInfo"></span>
                <nav class="org-report-pagination-nav" id="financialDocumentsPaginationNav" aria-label="Financial document pages"></nav>
            </div>
        </section>

        {{-- 12, 13, 14. Verification Details, Revision/Remarks, and Report History --}}
        <div class="org-bottom-3col">
            {{-- 12. Verification Details --}}
            <section class="org-fin-card" aria-label="Verification Details">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-person-check-fill" style="color: #16a34a;"></i> Verification Details</h3>
                </div>
                <div class="org-info-fields-grid" style="grid-template-columns: 1fr;">
                    <div class="org-info-field">
                        <small>Verified By</small>
                        <strong id="verByVal">{{ $financialDataset['verifiedBy'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Designation / Office</small>
                        <strong id="verOfficeVal">{{ $financialDataset['verifiedOffice'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Verification Date</small>
                        <strong id="verDateVal">{{ $financialDataset['verifiedDate'] }}</strong>
                    </div>
                    <div class="org-info-field">
                        <small>Receipt hashes</small>
                        <strong>See each receipt’s seal in Budget Utilization or the compilation register.</strong>
                    </div>
                </div>
            </section>

            {{-- 13. Revision / Remarks --}}
            <section class="org-fin-card" aria-label="Auditor Remarks and Compliance Notes">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-chat-square-text-fill" style="color: #7a1222;"></i> Report Notes</h3>
                    <span class="org-fin-badge-pill" id="remarksPill" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">Passed</span>
                </div>
                <div style="background: #faf4f5; border: 1.5px solid #f0e6e8; border-radius: 14px; padding: 1rem 1.15rem; font-size: 0.84rem; color: #40363a; line-height: 1.5;" id="remarksContent">
                    {{ $financialDataset['remarks'] }}
                </div>
                <div style="margin-top: 0.85rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.76rem; color: #7a7074;">
                    <i class="bi bi-shield-lock-fill" style="color: #16a34a;"></i>
                    <span>Semester acceptance is recorded separately by OSO.</span>
                </div>
            </section>

            {{-- 14. Report History (Timeline) --}}
            <section class="org-fin-card" aria-label="Report History and Activity Log">
                <div class="org-fin-card-head">
                    <h3><i class="bi bi-clock-history" style="color: #0284c7;"></i> Report History</h3>
                </div>
                <div class="org-timeline-list" id="reportHistoryTimeline">
                    {{-- Populated dynamically --}}
                </div>
                <div class="org-report-pagination" id="financialHistoryPagination" aria-label="Financial report history pagination">
                    <span id="financialHistoryPaginationInfo"></span>
                    <nav class="org-report-pagination-nav" id="financialHistoryPaginationNav" aria-label="Financial report history pages"></nav>
                </div>
            </section>
        </div>

    </div>

    <script>
        window.frLive = @json($frLive ?? null);
        // Financial Dataset Dictionary
        const financialDatasets = { consolidated: @json($financialDataset) };
        function escFinancial(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
        }

        let currentLedgerData = [];
        let currentLedgerFilter = 'all';
        let currentLedgerSearch = '';
        let currentFinancialDocuments = [];
        let currentFinancialHistory = [];
        let currentLedgerPage = 1;
        let currentFinancialDocumentsPage = 1;
        let currentFinancialHistoryPage = 1;
        const FINANCIAL_PAGE_SIZE = 7;
        const FINANCIAL_DOCUMENT_PAGE_SIZE = 4;
        const FINANCIAL_HISTORY_PAGE_SIZE = 5;

        function financialPaginate(items, page, pageSize) {
            const safeItems = Array.isArray(items) ? items : [];
            const totalPages = Math.max(1, Math.ceil(safeItems.length / pageSize));
            const safePage = Math.min(Math.max(Number(page) || 1, 1), totalPages);
            const start = safeItems.length ? (safePage - 1) * pageSize : 0;
            const end = Math.min(start + pageSize, safeItems.length);
            return { items: safeItems.slice(start, end), total: safeItems.length, page: safePage, totalPages, start, end };
        }

        function renderFinancialPagination({ barId, infoId, navId, total, page, pageSize, label, handler }) {
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

        function parseFinancialDate(value) {
            if (!value) return null;
            const parsed = new Date(value);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        }

        function financialAcademicPeriod(date) {
            const month = date.getMonth() + 1;
            const startYear = month < 8 ? date.getFullYear() - 1 : date.getFullYear();
            const semester = month >= 8 && month <= 12
                ? '1st Semester'
                : (month <= 5 ? '2nd Semester' : 'Midyear');
            return { year: `${startYear}-${startYear + 1}`, semester };
        }

        function matchesFinancialPeriod(value, year, semester) {
            const date = parseFinancialDate(value);
            if (!date) return false;
            const period = financialAcademicPeriod(date);
            return period.year === year && period.semester === semester;
        }

        function formatFinancialPeriod(ledger, year, semester) {
            const dates = ledger
                .map((item) => parseFinancialDate(item.date))
                .filter(Boolean)
                .sort((a, b) => a - b);
            if (!dates.length) return `${semester} · A.Y. ${year}`;
            const format = (date) => date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            return dates.length === 1
                ? format(dates[0])
                : `${format(dates[0])} – ${format(dates[dates.length - 1])}`;
        }

        function filteredFinancialData(data, year, semester) {
            const sourceLedger = Array.isArray(data.ledger) ? data.ledger : [];
            const ledger = sourceLedger.filter((item) => matchesFinancialPeriod(item.date, year, semester));
            const documents = (Array.isArray(data.documents) ? data.documents : [])
                .filter((item) => matchesFinancialPeriod(item.date, year, semester));
            const revenue = ledger
                .filter((item) => item.type === 'inflow')
                .reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
            const expenses = ledger
                .filter((item) => item.type === 'outflow')
                .reduce((sum, item) => sum + (Number(item.amount) || 0), 0);

            return {
                ...data,
                ledger,
                documents,
                revenue,
                expenses,
                balance: revenue - expenses,
                term: `${semester} · A.Y. ${year}`,
                period: formatFinancialPeriod(ledger, year, semester),
                actDate: formatFinancialPeriod(ledger, year, semester)
            };
        }

        function renderLedgerTable(items) {
            const tbody = document.getElementById('ledgerTableBody');
            const safeItems = Array.isArray(items) ? items : [];
            const page = financialPaginate(safeItems, currentLedgerPage, FINANCIAL_PAGE_SIZE);
            currentLedgerPage = page.page;
            tbody.innerHTML = '';
            let totalIn = 0;
            let totalOut = 0;

            safeItems.forEach(item => {
                if (item.type === 'inflow') totalIn += item.amount;
                else totalOut += item.amount;
            });

            page.items.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${escFinancial(item.date)}</strong></td>
                    <td>${escFinancial(item.desc)}</td>
                    <td><span style="background: #faf4f5; border: 1px solid #f0e6e8; padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.74rem; font-weight: 600;">${escFinancial(item.cat)}</span></td>
                    <td>
                        <span class="org-flow-pill ${item.type === 'inflow' ? 'org-flow-in' : 'org-flow-out'}">
                            <i class="bi ${item.type === 'inflow' ? 'bi-arrow-down-left' : 'bi-arrow-up-right'}"></i>
                            ${item.type === 'inflow' ? 'Inflow' : 'Outflow'}
                        </span>
                    </td>
                    <td><strong style="color: ${item.type === 'inflow' ? '#16a34a' : '#7a1222'};">${item.type === 'inflow' ? '+' : '-'}₱${item.amount.toLocaleString()}</strong></td>
                    <td>
                        ${item.url
                            ? `<a href="${escFinancial(item.url)}" target="_blank" rel="noopener" style="color: #7a1222; font-weight: 700; text-decoration: none;">
                                <i class="bi bi-paperclip"></i> ${escFinancial(item.ref)}
                               </a>`
                            : `<span style="color:#a39a9d; font-weight: 600;">
                                <i class="bi bi-paperclip"></i> ${escFinancial(item.ref)}
                               </span>`}
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.76rem; font-weight: 700; color: #16a34a;">
                            ${escFinancial(item.status)}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            if (!safeItems.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align:center;color:#7a7074;padding:1.6rem;">
                            No financial transactions match the selected academic year and semester.
                        </td>
                    </tr>`;
            }

            const net = totalIn - totalOut;
            const footEl = document.getElementById('tableFooterNet');
            footEl.textContent = '₱' + totalOut.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            footEl.style.color = '#7a1222';

            document.getElementById('tableRecordCountBadge').textContent = safeItems.length + ' Transactions';
            renderFinancialPagination({
                barId: 'financialLedgerPagination',
                infoId: 'financialLedgerPaginationInfo',
                navId: 'financialLedgerPaginationNav',
                total: page.total,
                page: page.page,
                pageSize: FINANCIAL_PAGE_SIZE,
                label: 'ledger transactions',
                handler: 'goToFinancialLedgerPage'
            });
        }

        function renderSupportingDocs(docs) {
            const grid = document.getElementById('supportingDocsGrid');
            const page = financialPaginate(docs, currentFinancialDocumentsPage, FINANCIAL_DOCUMENT_PAGE_SIZE);
            currentFinancialDocumentsPage = page.page;
            grid.innerHTML = '';

            page.items.forEach(doc => {
                const card = document.createElement('div');
                card.className = 'org-doc-card';
                card.innerHTML = `
                    <div class="org-doc-left">
                        <div class="org-doc-icon">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                        </div>
                        <div class="org-doc-meta">
                            <strong title="${escFinancial(doc.name)}">${escFinancial(doc.name)}</strong>
                            <small>${escFinancial(doc.size)} · ${escFinancial(doc.date)}</small>
                        </div>
                    </div>
                    ${doc.url
                        ? `<a href="${escFinancial(doc.url)}" target="_blank" rel="noopener" class="org-doc-btn" title="View Document" style="text-decoration:none;display:inline-flex;">
                            <i class="bi bi-eye-fill"></i>
                           </a>`
                        : `<span class="org-doc-btn" style="opacity:.4;" title="No file on record">
                            <i class="bi bi-eye-slash"></i>
                           </span>`}
                `;
                grid.appendChild(card);
            });

            if (!page.total) {
                grid.innerHTML = '<p style="margin:0;color:#7a7074;font-size:0.84rem;">No supporting documents match the selected academic period.</p>';
            }
            renderFinancialPagination({
                barId: 'financialDocumentsPagination',
                infoId: 'financialDocumentsPaginationInfo',
                navId: 'financialDocumentsPaginationNav',
                total: page.total,
                page: page.page,
                pageSize: FINANCIAL_DOCUMENT_PAGE_SIZE,
                label: 'supporting documents',
                handler: 'goToFinancialDocumentsPage'
            });
        }

        function renderTimeline(history) {
            const container = document.getElementById('reportHistoryTimeline');
            const page = financialPaginate(history, currentFinancialHistoryPage, FINANCIAL_HISTORY_PAGE_SIZE);
            currentFinancialHistoryPage = page.page;
            container.innerHTML = '';

            page.items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'org-timeline-item';
                div.innerHTML = `
                    <div class="org-tl-badge ${item.badge}">
                        <i class="bi ${item.icon}"></i>
                    </div>
                    <div class="org-tl-content">
                        <strong>${escFinancial(item.title)}</strong>
                        <small>${escFinancial(item.date)}</small>
                    </div>
                `;
                container.appendChild(div);
            });

            if (!page.total) {
                container.innerHTML = '<p style="margin:0;color:#7a7074;font-size:0.84rem;">No report history is available for this period.</p>';
            }
            renderFinancialPagination({
                barId: 'financialHistoryPagination',
                infoId: 'financialHistoryPaginationInfo',
                navId: 'financialHistoryPaginationNav',
                total: page.total,
                page: page.page,
                pageSize: FINANCIAL_HISTORY_PAGE_SIZE,
                label: 'history events',
                handler: 'goToFinancialHistoryPage'
            });
        }

        // Live DB overrides merged into the consolidated report view.
        (function applyLiveFrData() {
            const live = window.frLive || null;
            const base = (typeof financialDatasets !== 'undefined' && financialDatasets.consolidated) || null;
            if (!live || !base) return;
            if (live.organizationFilterActive) {
                const organizationLabel = live.organizationLabel || 'Selected Organization';
                base.orgName = organizationLabel;
                base.orgCategory = 'Organization Portfolio';
                base.actName = organizationLabel + ' Financial Report';
                base.actType = 'Organization Financial Liquidation';
                base.revenueSub = 'Organization budget allocations';
                base.expensesSub = 'Recorded organization disbursements';
                base.balanceSub = 'Remaining organization allocation';
                base.ledger = Array.isArray(live.ledger) ? live.ledger : [];
                base.documents = Array.isArray(live.docsLive) ? live.docsLive : [];
                base.history = Array.isArray(live.history)
                    ? live.history.map((h) => ({ title: h.title, date: h.date, badge: 'is-blue', icon: 'bi-check-circle' }))
                    : [];
                return;
            }
            if (Number.isFinite(Number(live.revenue))) {
                base.revenue = live.revenue;
            }
            if (Number.isFinite(Number(live.expenses))) {
                base.expenses = live.expenses;
            }
            if (Number.isFinite(Number(live.balance))) {
                base.balance = live.balance;
            }
            if (Array.isArray(live.ledger) && live.ledger.length) base.ledger = live.ledger;
            if (Array.isArray(live.ledgerLive) && live.ledgerLive.length) base.ledger = [...live.ledgerLive, ...base.ledger];
            if (Array.isArray(live.docsLive) && live.docsLive.length) base.documents = [...live.docsLive, ...(base.documents || [])];
            if (Array.isArray(live.history) && live.history.length) {
                base.history = live.history.map((h) => ({ title: h.title, date: h.date, badge: 'is-blue', icon: 'bi-check-circle' }));
            }
        })();

        const activeFinancialPeriod = {
            year: @json($selectedYear),
            semester: @json($selectedSemester),
        };

        function switchFinancialReport(key) {
            const baseData = financialDatasets[key] || financialDatasets.consolidated;
            const sem = activeFinancialPeriod.semester;
            const year = activeFinancialPeriod.year;
            const data = filteredFinancialData(baseData, year, sem);
            currentLedgerData = data.ledger;
            currentFinancialDocuments = Array.isArray(data.documents) ? data.documents : [];
            currentFinancialHistory = Array.isArray(data.history) ? data.history : [];
            currentLedgerPage = 1;
            currentFinancialDocumentsPage = 1;
            currentFinancialHistoryPage = 1;

            // 1. Organization & Activity Information
            document.getElementById('orgNameVal').textContent = data.orgName;
            document.getElementById('orgCategoryBadge').textContent = data.orgCategory;
            document.getElementById('orgTermVal').textContent = `${sem} · A.Y. ${year}`;
            document.getElementById('orgPeriodVal').textContent = data.period;
            document.getElementById('orgAdvisorVal').textContent = data.advisor;

            document.getElementById('actNameVal').textContent = data.actName;
            document.getElementById('actTypeVal').textContent = data.actType;
            document.getElementById('actDateVal').textContent = data.actDate;
            document.getElementById('actVenueVal').textContent = data.actVenue;
            document.getElementById('actScopePill').textContent = data.actScope;

            // 2. Ledger Table
            renderLedgerTable(data.ledger);

            // 3. Supporting Documents
            renderSupportingDocs(currentFinancialDocuments);

            // 4. Verification Details, Remarks & History
            document.getElementById('verByVal').textContent = data.verifiedBy;
            document.getElementById('verOfficeVal').textContent = data.verifiedOffice;
            document.getElementById('verDateVal').textContent = data.verifiedDate;
            document.getElementById('remarksContent').textContent = data.remarks;
            renderTimeline(currentFinancialHistory);

            document.getElementById('remarksPill').textContent = data.ledger.length ? 'Period filtered' : 'No records';
            document.getElementById('remarksPill').style.background = data.ledger.length ? '#f0fdf4' : '#fff7ed';
            document.getElementById('remarksPill').style.color = data.ledger.length ? '#16a34a' : '#c2410c';
            document.getElementById('remarksPill').style.borderColor = data.ledger.length ? '#bbf7d0' : '#fed7aa';
            renderLedgerView();
        }

        function renderLedgerView() {
            let rows = currentLedgerData;
            if (currentLedgerFilter !== 'all') {
                rows = rows.filter((item) => item.type === currentLedgerFilter);
            }
            const query = currentLedgerSearch.toLowerCase().trim();
            if (query) {
                rows = rows.filter((item) => [item.desc, item.cat, item.ref]
                    .some((value) => String(value || '').toLowerCase().includes(query)));
            }
            renderLedgerTable(rows);
        }

        function filterLedger(type, btn) {
            document.querySelectorAll('.org-tab-btn').forEach(b => b.classList.remove('is-active'));
            if (btn) btn.classList.add('is-active');
            currentLedgerFilter = type;
            currentLedgerPage = 1;
            renderLedgerView();
        }

        function searchLedger(query) {
            currentLedgerSearch = query || '';
            currentLedgerPage = 1;
            renderLedgerView();
        }

        function goToFinancialLedgerPage(page) {
            currentLedgerPage = page;
            renderLedgerView();
        }

        function goToFinancialDocumentsPage(page) {
            currentFinancialDocumentsPage = page;
            renderSupportingDocs(currentFinancialDocuments);
        }

        function goToFinancialHistoryPage(page) {
            currentFinancialHistoryPage = page;
            renderTimeline(currentFinancialHistory);
        }

        // Initialize on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', () => {
            switchFinancialReport('consolidated');
        });
    </script>
@endsection
