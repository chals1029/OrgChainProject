@extends('org.layout')

@php
    $selectedTab = $selectedTab ?? 'ar';
    $selectedOrganization = $selectedOrganization ?? '';
    $selectedYear = $selectedYear ?? '';
    $selectedSemester = $selectedSemester ?? '';
    $bundle = $reportBundle ?? [];
    $record = $bundle['reports'][$selectedTab] ?? [];
    $documents = !empty($record['can_view']) ? collect($record['documents'] ?? []) : collect();
    $reportDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->setTimezone(config('app.timezone'))->format('M j, Y g:i A') : 'Not yet';
    $previewReports = $documents->mapWithKeys(function ($document) use ($selectedYear, $selectedSemester, $selectedTab) {
        $nativeAr = $selectedTab === 'ar' && $document->accomplishment_summary !== null;
        return [(string) $document->id => [
            'id' => $document->id,
            'title' => $document->name ?: $document->original_name,
            'original_name' => $document->original_name,
            'academic_year' => $selectedYear,
            'semester' => $selectedSemester,
            'has_file' => $document->hasStoredFile(),
            'is_workbook' => !$nativeAr && $document->isWorkbook(),
            'is_native_ar' => $nativeAr,
            'preview_url' => $nativeAr
                ? route('office.reports.documents.view', ['document' => $document, 'embedded' => 1])
                : route('office.reports.documents.workbook', ['document' => $document]),
            'download_url' => route('office.reports.documents.view', ['document' => $document, 'download' => 1]),
        ]];
    });
    $previewData = ['reports' => $previewReports, 'reviewer_mode' => true, 'conversion_url' => route('office.documents.preview.convert')];
@endphp

@section('title', 'AR & FR Reports')
@section('header')
    <h1><strong>AR &amp; FR Reports</strong></h1>
    <p class="org-welcome">Browse organizations and review their Accomplishment and Financial Reports for the selected semester.</p>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/so-financial.css') }}?v={{ filemtime(public_path('css/so-financial.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/semester-reports.css') }}?v={{ filemtime(public_path('css/semester-reports.css')) }}">
    <div class="sr-browser">
        @if ($errors->any())
            <div class="sr-notice is-error" role="alert"><strong>The report action could not be completed.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if (session('success'))<div class="sr-notice" role="status">{{ session('success') }}</div>@endif

        <section class="sr-card sr-submission-card" id="reportSubmissionControls" aria-labelledby="reportSubmissionControlsTitle">
            <div class="sr-head">
                <div>
                    <h2 id="reportSubmissionControlsTitle"><i class="bi bi-lock"></i> Submission controls</h2>
                    <p class="sr-muted">AY {{ $selectedYear }} · {{ $selectedSemester }} · Applies to all organizations in this period.</p>
                </div>
            </div>
            <p class="sr-muted">Lock AR and FR separately. Existing submissions remain available for review.</p>
            <div class="sr-submission-controls">
                @foreach (['ar' => 'Accomplishment Report (AR)', 'fr' => 'Financial Report (FR)'] as $type => $title)
                    @php($submissionLocked = (bool) ($submissionLocks[$type] ?? false))
                    <div class="sr-submission-row" data-submission-control="{{ $type }}" data-submission-locked="{{ $submissionLocked ? 'true' : 'false' }}">
                        <div>
                            <div class="sr-submission-title"><strong id="srSubmissionLabel{{ $type }}">{{ $title }}</strong><span class="sr-submission-state {{ $submissionLocked ? 'is-closed' : 'is-open' }}">{{ $submissionLocked ? 'Closed' : 'Open' }}</span></div>
                            <small>{{ $submissionLocked ? 'Closed — Organizations cannot submit new reports' : 'Open — Organizations can submit completed reports' }}</small>
                        </div>
                        <form method="post" action="{{ route('office.reports.submission-lock', ['reportType' => $type]) }}" class="sr-actions" data-sr-submission-form="{{ $type }}" data-confirm="manual">
                            @csrf
                            <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                            <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                            <input type="hidden" name="organization" value="{{ $selectedOrganization }}">
                            <input type="hidden" name="tab" value="{{ $selectedTab }}">
                            <input type="hidden" name="is_locked" value="{{ $submissionLocked ? '0' : '1' }}">
                            <button type="submit" class="sr-submission-switch" role="switch" aria-checked="{{ $submissionLocked ? 'false' : 'true' }}" aria-labelledby="srSubmissionLabel{{ $type }}" data-sr-submission-button @if (!$submissionLocked) aria-haspopup="dialog" aria-controls="srLockDialog" @endif>
                                <span class="sr-switch-thumb" aria-hidden="true"></span>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sr-card" id="srOrganizationDirectory" aria-labelledby="srDirectoryTitle">
            <div class="sr-head"><div><h2 id="srDirectoryTitle">Organization report directory</h2><p class="sr-muted">AY {{ $selectedYear }} · {{ $selectedSemester }}</p></div><span class="sr-muted" id="srDirectoryCount" role="status">{{ count($reportDirectory ?? []) }} organizations</span></div>
            <form method="get" action="{{ route('office.reports.index') }}" class="sr-filters" data-report-period-form>
                <input type="hidden" name="tab" value="{{ $selectedTab }}">
                <label>Organization<select name="organization"><option value="">All organizations</option>@foreach (($organizations ?? []) as $name)<option value="{{ $name }}" @selected($selectedOrganization === $name)>{{ $name }}</option>@endforeach</select></label>
                <label>Academic year<select name="academic_year">@foreach (($reportingYears ?? []) as $year)<option value="{{ $year }}" @selected($selectedYear === (string) $year)>{{ $year }}</option>@endforeach</select></label>
                <label>Semester<select name="semester">@foreach (($reportingSemesters ?? []) as $semester)<option value="{{ $semester }}" @selected($selectedSemester === $semester)>{{ $semester }}</option>@endforeach</select></label>
            </form>
            <div class="sr-directory-wrap">
                <table class="sr-directory">
                    <thead><tr><th scope="col">Organization</th><th scope="col">Accomplishment Report (AR)</th><th scope="col">Financial Report (FR)</th><th scope="col">Reports</th></tr></thead>
                    <tbody>
                        @forelse (($reportDirectory ?? []) as $entry)
                            <?php
                                $modalReports = [];
                                foreach (['ar', 'fr'] as $modalType) {
                                    $item = $entry['reports'][$modalType] ?? [];
                                    $document = !empty($item['can_view']) ? ($item['documents']->first() ?? null) : null;
                                    $nativeAr = $modalType === 'ar' && $document && $document->accomplishment_summary !== null;
                                    $modalReports[$modalType] = [
                                        'type' => $modalType, 'title' => $item['title'] ?? strtoupper($modalType),
                                        'state' => $item['state'] ?? 'draft', 'state_label' => $item['state_label'] ?? 'Not submitted',
                                        'can_view' => !empty($item['can_view']), 'can_review' => !empty($item['can_review']),
                                        'has_document' => !empty($item['has_document']), 'notes' => $item['notes'] ?? '',
                                        'filename' => $document?->original_name,
                                        'submission_date' => $reportDate($item['submitted_at'] ?? null),
                                        'is_workbook' => $document && !$nativeAr && $document->isWorkbook(),
                                        'preview_url' => $document ? route('office.reports.documents.view', ['document' => $document, 'embedded' => 1]) : null,
                                        'workbook_url' => $document && $modalType === 'fr' && !$nativeAr && $document->isWorkbook() ? route('office.reports.documents.workbook', ['document' => $document]) : null,
                                        'download_url' => $document ? route('office.reports.documents.view', ['document' => $document, 'download' => 1]) : null,
                                        'review_url' => route('office.reports.review', ['reportType' => $modalType]),
                                    ];
                                }
                                $modalData = ['organization' => $entry['organization'], 'college' => $entry['college'] ?? '', 'academic_year' => $selectedYear, 'semester' => $selectedSemester, 'reports' => $modalReports];
                            ?>
                            <tr data-sr-directory-row data-reports="{{ json_encode($modalData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}" data-search="{{ \Illuminate\Support\Str::lower($entry['organization'].' '.($entry['short_name'] ?? '').' '.($entry['college'] ?? '')) }}" @class(['is-selected' => $selectedOrganization === $entry['organization']]) @if ($loop->index >= 5) hidden @endif>
                                <td data-label="Organization"><strong>{{ $entry['organization'] }}</strong><small>{{ $entry['short_name'] ?? '' }}@if (!empty($entry['short_name']) && !empty($entry['college'])) · @endif{{ $entry['college'] ?? '' }}</small></td>
                                @foreach (['ar', 'fr'] as $type)
                                    @php($directoryReport = $entry['reports'][$type] ?? [])
                                    <td data-label="{{ strtoupper($type) }} status">
                                        <span class="sr-status is-{{ $directoryReport['state'] ?? 'draft' }}">{{ empty($directoryReport['can_view']) && ($directoryReport['state'] ?? '') !== 'returned' ? 'Not submitted' : ($directoryReport['state_label'] ?? 'Submitted') }}</span>
                                        @if (!empty($directoryReport['notes']))<small>{{ $directoryReport['notes'] }}</small>@endif
                                    </td>
                                @endforeach
                                <td data-label="Reports">
                                    <button type="button" class="app-view-action" data-oso-view aria-label="View reports for {{ $entry['organization'] }}"><i class="bi bi-eye" aria-hidden="true"></i> View</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="sr-empty">No registered organizations are available.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="sr-empty" id="srDirectoryNoMatch" hidden>No organizations match your search.</p>
            <div class="sr-directory-pagination" id="srDirectoryPagination" hidden>
                <span class="sr-muted" id="srDirectoryPageInfo" role="status" aria-live="polite"></span>
                <nav aria-label="Organization report pages">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="srDirectoryPrevious">Previous</button>
                    <span class="sr-muted" id="srDirectoryPageNumber"></span>
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="srDirectoryNext">Next</button>
                </nav>
            </div>
        </section>

    </div>

    <style>
        .oso-reports-modal { box-sizing:border-box; position:fixed !important; top:50% !important; left:50% !important; transform:translate(-50%, -50%) !important; width:min(1180px, calc(100vw - 3rem)) !important; height:min(880px, calc(100dvh - 3rem)) !important; max-width:none; max-height:none; margin:0 !important; padding:0; border:1px solid #eadde2; border-radius:20px !important; background:#fff; color:#30282b; box-shadow:0 25px 80px rgba(45,15,24,.28); overflow:hidden; }
        .oso-reports-modal[open] { display:flex; flex-direction:column; }
        .org-content > .oso-reports-modal { animation:none; opacity:1; }
        .oso-reports-modal::backdrop { background:rgba(45,25,32,.48); backdrop-filter:blur(4px); }
        .oso-reports-modal [hidden] { display:none !important; }
        .oso-modal-header { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex:none; padding:1.5rem 1.5rem 1.25rem; border-bottom:1px solid #f1e4e8; }
        .oso-modal-label { margin:0 0 .35rem; color:#852d49; font-size:.72rem; font-weight:800; letter-spacing:.09em; }
        .oso-modal-header h2 { margin:0; font-size:1.35rem; font-weight:800; }
        .oso-modal-college { margin:.3rem 0 .75rem; color:#76686d; }
        .oso-modal-semester { display:inline-block; padding:.35rem .75rem; border-radius:999px; background:#f6e9ed; color:#842b47; font-size:.8rem; font-weight:700; }
        .oso-modal-body { overflow-y:auto; flex:1; min-height:0; padding:1.5rem; }
        .oso-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); align-items:stretch; gap:1rem; }
        .oso-modal-card { display:flex; flex-direction:column; min-width:0; padding:1.25rem; border:1px solid #eadde2; border-radius:14px; overflow-wrap:anywhere; }
        .oso-modal-card h3 { margin:0; font-size:1.05rem; }
        .oso-card-heading { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; }
        .oso-card-heading .sr-status:last-child { margin-left:auto; }
        .oso-card-abbr { padding:.4rem .55rem; border-radius:8px; background:#f6e9ed; color:#842b47; font-weight:800; }
        .oso-modal-preview { display:flex; flex:1; flex-direction:column; align-items:center; justify-content:center; min-height:180px; margin:1rem 0; padding:1rem; background:#faf7f8; border-radius:10px; color:#7a7074; text-align:center; }
        .oso-modal-preview i { color:#a04c66; font-size:2.5rem; }
        .oso-modal-preview p { margin:.35rem 0 0; font-size:.9rem; }
        .oso-card-filename { margin:.25rem 0; font-weight:700; }
        .oso-card-date { margin:0 0 1rem; }
        .oso-card-actions { display:grid; gap:.5rem; margin-top:auto; }
        .oso-card-actions .org-btn { width:100%; justify-content:center; box-sizing:border-box; text-align:center; }
        .oso-focused-heading { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin:0 0 1rem; }
        .oso-preview-panel { border:1px solid #eadde2; border-radius:14px; overflow:hidden; background:#fff; }
        .oso-preview-toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid #f1e4e8; }
        .oso-preview-toolbar p { margin:.2rem 0 0; }
        .oso-preview-toolbar strong { overflow-wrap:anywhere; }
        .oso-modal-document-full { box-sizing:border-box; width:100%; height:580px; min-height:480px; border:1px solid #e6dde0; border-radius:12px; background:#fff; }
        #osoFocusedPreview { overflow:auto; }
        iframe.oso-modal-document-full { display:block; border:0; }
        .oso-workbook { font-size:.8rem; }
        .oso-workbook h4, .oso-workbook table, .oso-workbook > p { margin:1.25rem; }
        .oso-workbook table { border-collapse:collapse; }
        .oso-workbook td { border:1px solid #ddd; padding:.35rem; white-space:pre-wrap; }
        .oso-modal-review { margin-top:1rem; padding:1.25rem; border:1px solid #eadde2; border-radius:14px; background:#fff; }
        .oso-review-heading { display:flex; align-items:center; gap:.75rem; }
        .oso-review-heading i { display:grid; place-items:center; width:2.5rem; height:2.5rem; border-radius:10px; background:#f6e9ed; color:#842b47; font-size:1.2rem; }
        .oso-review-heading h3 { margin:0; }
        .oso-review-instruction { margin:.75rem 0 1rem; color:#76686d; }
        .oso-review-status-card { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; padding:.85rem 1rem; border:1px solid #eadde2; border-radius:10px; background:#faf7f8; }
        .oso-modal-review label { display:block; font-weight:700; }
        .oso-modal-review textarea { display:block; width:100%; box-sizing:border-box; margin-top:.5rem; }
        .oso-modal-message { margin:.75rem 0; }
        .oso-modal-footer { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex:none; padding:1rem 1.5rem; border-top:1px solid #f1e4e8; background:#faf7f8; }
        .oso-modal-footer p { margin:0; color:#76686d; font-size:.85rem; }
        @media (max-width:760px) { .oso-modal-grid { grid-template-columns:1fr; } .oso-preview-toolbar, .oso-modal-footer { align-items:flex-start; flex-direction:column; } .oso-focused-heading { flex-wrap:wrap; } }
    </style>
    <dialog class="oso-reports-modal" id="osoReportsModal" aria-labelledby="osoModalTitle">
        <header class="oso-modal-header">
            <div><p class="oso-modal-label" id="osoModalLabel">SUBMITTED REPORTS</p><h2 id="osoModalTitle"></h2><p class="oso-modal-college" id="osoModalCollege"></p><span class="oso-modal-semester" id="osoModalSemesterBadge"></span></div>
            <button type="button" class="fin-icon-btn" id="osoModalClose" aria-label="Close reports"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </header>
        <div class="oso-modal-body">
            <div id="osoModalCardsView"><div class="oso-modal-grid" id="osoModalCards"></div></div>
            <div id="osoModalFocusedView" hidden>
                <div class="oso-focused-heading">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="osoModalBack"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to submitted reports</button>
                    <span class="oso-modal-semester" id="osoFocusedSemesterBadge"></span>
                </div>
                <section class="oso-preview-panel" aria-label="Report preview">
                    <div class="oso-preview-toolbar">
                        <div><strong id="osoFocusedFilename"></strong><p class="sr-muted" id="osoFocusedDate"></p></div>
                        <a id="osoFocusedDownload" class="org-btn org-btn-ghost" href=""><i class="bi bi-download" aria-hidden="true"></i> Download report</a>
                    </div>
                    <div id="osoFocusedPreview" class="oso-modal-document-full oso-workbook"></div>
                </section>
                <form class="sr-review oso-modal-review" id="osoReviewForm" data-confirm="manual">
                    @csrf
                    <div class="oso-review-heading"><i class="bi bi-clipboard-check" aria-hidden="true"></i><h3>OSO review</h3></div>
                    <p class="oso-review-instruction">Review the submitted document above and record your decision below.</p>
                    <div class="oso-review-status-card"><strong>Assessment status</strong><span class="sr-status" id="osoReviewStatus"></span></div>
                    <label for="osoReviewRemarks">OSO remarks<textarea id="osoReviewRemarks" name="notes" rows="5" maxlength="1000" aria-describedby="osoReviewEligibility"></textarea></label>
                    <p class="sr-muted" id="osoReviewEligibility"></p>
                    <p class="oso-modal-message sr-danger" id="osoReviewMessage" role="alert" hidden></p>
                    <div class="sr-actions">
                        <button type="submit" name="decision" value="return" class="org-btn org-btn-ghost" disabled>Return for revision</button>
                        <button type="submit" name="decision" value="accept" class="org-btn org-btn-primary">Approve &amp; archive</button>
                    </div>
                </form>
            </div>
        </div>
        <footer class="oso-modal-footer"><p id="osoModalFooterText">Review each submitted report carefully before approving or returning.</p><button type="button" class="org-btn org-btn-ghost" id="osoModalFooterClose">Close viewer</button></footer>
    </dialog>

    <dialog id="srLockDialog" class="sr-lock-dialog" aria-labelledby="srLockTitle" aria-describedby="srLockPeriod srLockDescription">
        <div class="sr-lock-box">
            <span class="sr-lock-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
            <h2 id="srLockTitle">Lock submissions?</h2>
            <p id="srLockPeriod" class="sr-lock-period"></p>
            <div id="srLockDescription">
                <p>This applies to <strong>ALL organizations</strong> in this reporting period and prevents new submissions of this report type.</p>
                <p>SO may still prepare, view and download reports. Existing submitted reports are unaffected and remain available for review.</p>
            </div>
            <div class="sr-lock-actions">
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="srLockCancel" autofocus>Cancel</button>
                <button type="button" class="org-btn org-btn-primary org-btn-sm" id="srLockConfirm"><i class="bi bi-lock" aria-hidden="true"></i> Yes, lock submissions</button>
            </div>
        </div>
    </dialog>

    <dialog id="finPreviewDialog" class="fin-dialog fin-preview-dialog" aria-labelledby="finPreviewTitle">
        <div class="fin-preview-box">
            <header class="fin-dialog-head">
                <div class="fin-dialog-heading"><span class="fin-dialog-icon"><i class="bi bi-file-earmark-text"></i></span><div><strong id="finPreviewTitle">Submitted report preview</strong><small id="finPreviewMeta"></small></div></div>
                <div class="fin-preview-actions"><a href="" class="org-btn org-btn-ghost org-btn-sm" id="finPreviewDownload"><i class="bi bi-download"></i> Download original</a><button type="button" class="fin-icon-btn" data-fin-close aria-label="Close preview"><i class="bi bi-x-lg"></i></button></div>
            </header>
            <div class="fin-preview-body">
                <p class="fin-preview-status" id="finPreviewStatus" role="status"></p>
                <div class="fin-preview-workbook" id="finPreviewWorkbook" hidden>
                    <div class="fin-preview-summary"><div class="is-in"><small>Cash inflow</small><strong id="finPreviewInflow"></strong></div><div class="is-out"><small>Cash outflow</small><strong id="finPreviewOutflow"></strong></div><div class="is-balance"><small>Balance</small><strong id="finPreviewBalance"></strong></div></div>
                    <div class="fin-sheet-tabs" role="tablist" aria-label="Worksheets" id="finPreviewTabs"></div>
                    <p class="fin-preview-limit" id="finPreviewLimit"></p>
                    <div class="fin-sheet-scroll" id="finPreviewPanel" role="tabpanel" tabindex="0"></div>
                </div>
                <div class="fin-preview-file" id="finPreviewFile" hidden></div>
            </div>
        </div>
    </dialog>
    @include('org.partials.document-preview')
    <script type="application/json" id="soFinancialData">@json($previewData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/so-financial.js') }}?v={{ filemtime(public_path('js/so-financial.js')) }}"></script>
    <script src="{{ asset('js/semester-reports.js') }}?v={{ filemtime(public_path('js/semester-reports.js')) }}" defer></script>
    <script>
        (() => {
            const osoModal = document.getElementById('osoReportsModal');
            const cardsView = document.getElementById('osoModalCardsView');
            const focusedView = document.getElementById('osoModalFocusedView');
            const cards = document.getElementById('osoModalCards');
            const form = document.getElementById('osoReviewForm');
            const remarks = document.getElementById('osoReviewRemarks');
            const message = document.getElementById('osoReviewMessage');
            const preview = document.getElementById('osoFocusedPreview');
            const returnButton = form.querySelector('button[value="return"]');
            const approveButton = form.querySelector('button[value="accept"]');
            function updateReviewButtons() {
                const eligible = !pending && !!org?.reports[type]?.can_review;
                returnButton.disabled = !eligible || !remarks.value.trim();
                approveButton.disabled = !eligible;
            }
            let org, row, type, pending = false, previewVersion = 0;
            const element = (tag, className = '', text) => {
                const result = document.createElement(tag);
                result.className = className;
                if (text !== undefined) result.textContent = text;
                return result;
            };
            function showCards() {
                previewVersion++;
                preview.replaceChildren();
                focusedView.hidden = true;
                cardsView.hidden = false;
                document.getElementById('osoModalLabel').textContent = 'SUBMITTED REPORTS';
                document.getElementById('osoModalCollege').textContent = org.college || '';
                document.getElementById('osoModalSemesterBadge').hidden = false;
                document.getElementById('osoModalFooterText').textContent = 'Review each submitted report carefully before approving or returning.';
                cards.replaceChildren();
                ['ar', 'fr'].forEach(reportType => {
                    const report = org.reports[reportType];
                    const card = element('section', 'oso-modal-card');
                    const heading = element('div', 'oso-card-heading');
                    heading.append(element('span', 'oso-card-abbr', reportType.toUpperCase()), element('h3', '', report.title), element('span', `sr-status is-${report.state}`, report.can_view || report.state === 'returned' ? report.state_label : 'Not submitted'));
                    card.append(heading);
                    const icon = element('div', 'oso-modal-preview');
                    const symbol = element('i', report.can_view ? 'bi bi-file-earmark-text' : 'bi bi-file-earmark-x');
                    symbol.setAttribute('aria-hidden', 'true');
                    icon.append(symbol);
                    if (!report.can_view) {
                        icon.append(element('strong', '', 'No report submitted'), element('p', '', 'There is no report available for review.'));
                        card.append(icon);
                    } else {
                        card.append(icon, element('p', 'oso-card-filename', report.filename || 'No readable document available'), element('p', 'sr-muted oso-card-date', `Submitted: ${report.submission_date}`));
                        if (report.has_document) {
                            const actions = element('div', 'oso-card-actions');
                            const button = element('button', 'org-btn org-btn-primary', 'View & review report');
                            button.type = 'button';
                            button.addEventListener('click', () => openFocusedReview(reportType));
                            const download = element('a', 'org-btn org-btn-ghost', 'Download report');
                            download.href = report.download_url;
                            actions.append(button, download);
                            card.append(actions);
                        } else card.append(element('p', 'sr-danger', 'The submitted file is missing or unreadable.'));
                    }
                    cards.append(card);
                });
            }
            function openOsoReportsModal(data, sourceRow) {
                org = data;
                row = sourceRow;
                document.getElementById('osoModalTitle').textContent = org.organization;
                document.getElementById('osoModalCollege').textContent = org.college || '';
                document.getElementById('osoModalSemesterBadge').textContent = [org.academic_year ? `AY ${org.academic_year}` : '', org.semester].filter(Boolean).join(' · ');
                showCards();
                osoModal.showModal();
            }
            async function openFocusedReview(reportType) {
                type = reportType;
                const report = org.reports[type];
                const version = ++previewVersion;
                cardsView.hidden = true;
                focusedView.hidden = false;
                const period = [org.academic_year ? `AY ${org.academic_year}` : '', org.semester].filter(Boolean).join(' · ');
                document.getElementById('osoModalLabel').textContent = 'REPORT REVIEW';
                document.getElementById('osoModalCollege').textContent = [org.college, 'Office of the Student Organizations (OSO)', report.title].filter(Boolean).join(' · ');
                document.getElementById('osoModalSemesterBadge').hidden = true;
                document.getElementById('osoFocusedSemesterBadge').textContent = period;
                document.getElementById('osoModalFooterText').textContent = [report.title, period].filter(Boolean).join(' · ');
                document.getElementById('osoFocusedFilename').textContent = report.filename || 'Report document';
                document.getElementById('osoFocusedDate').textContent = `Submitted on ${report.submission_date}`;
                document.getElementById('osoFocusedDownload').href = report.download_url;
                const badge = document.getElementById('osoReviewStatus');
                badge.className = `sr-status is-${report.state}`;
                badge.textContent = report.state_label;
                remarks.value = report.notes || '';
                remarks.disabled = !report.can_review;
                updateReviewButtons();
                document.getElementById('osoReviewEligibility').textContent = report.can_review ? 'Remarks are required when returning a report.' : 'This report is not currently eligible for a review decision.';
                message.hidden = true;
                preview.replaceChildren();
                if (!report.is_workbook) {
                    const frame = element('iframe', 'oso-modal-document-full');
                    frame.title = `${report.title} preview`;
                    frame.src = report.preview_url;
                    preview.append(frame);
                    return;
                }
                preview.append(element('p', '', 'Loading workbook preview…'));
                try {
                    const response = await fetch(report.workbook_url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const payload = await response.json();
                    if (!response.ok || !Array.isArray(payload.sheets)) throw new Error(payload.message || 'Unable to preview workbook.');
                    if (version !== previewVersion) return;
                    preview.replaceChildren();
                    if (!payload.sheets.length) preview.append(element('p', '', 'This workbook has no worksheets.'));
                    payload.sheets.forEach((sheet, index) => {
                        preview.append(element('h4', '', sheet.name || `Sheet ${index + 1}`));
                        const table = element('table');
                        (sheet.rows || []).forEach(cells => {
                            const tr = element('tr');
                            cells.forEach(cell => {
                                if (cell?.skip) return;
                                const td = element('td', '', cell?.text == null ? '' : String(cell.text));
                                if (cell?.colspan > 1) td.colSpan = cell.colspan;
                                if (cell?.rowspan > 1) td.rowSpan = cell.rowspan;
                                tr.append(td);
                            });
                            table.append(tr);
                        });
                        preview.append(table);
                    });
                } catch (error) {
                    if (version === previewVersion) preview.replaceChildren(element('p', 'sr-danger', error.message || 'Unable to preview workbook.'));
                }
            }
            document.querySelectorAll('[data-oso-view]').forEach(button => button.addEventListener('click', () => {
                const sourceRow = button.closest('[data-sr-directory-row]');
                openOsoReportsModal(JSON.parse(sourceRow.dataset.reports), sourceRow);
            }));
            document.getElementById('osoModalClose').addEventListener('click', () => { if (!pending) osoModal.close(); });
            document.getElementById('osoModalFooterClose').addEventListener('click', () => { if (!pending) osoModal.close(); });
            document.getElementById('osoModalBack').addEventListener('click', () => { if (!pending) showCards(); });
            osoModal.addEventListener('cancel', event => { if (pending) event.preventDefault(); });
            osoModal.addEventListener('close', () => { previewVersion++; preview.replaceChildren(); });
            form.addEventListener('submit', async event => {
                event.preventDefault();
                if (pending || !org || !type) return;
                const decision = event.submitter?.value;
                if (!['return', 'accept'].includes(decision)) return;
                if (decision === 'return' && !remarks.value.trim()) {
                    message.textContent = 'Enter remarks before returning this report.';
                    message.hidden = false;
                    remarks.focus();
                    return;
                }
                if (!window.confirm(decision === 'return' ? 'Return this report for revision?' : 'Approve and archive this report?')) return;
                const report = org.reports[type];
                const data = new FormData(form);
                data.set('decision', decision);
                data.set('organization_name', org.organization);
                data.set('academic_year', org.academic_year);
                data.set('semester', org.semester);
                pending = true;
                form.querySelectorAll('button[type=submit]').forEach(button => { button.disabled = true; });
                message.hidden = true;
                try {
                    const response = await fetch(report.review_url, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const result = await response.json();
                    if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to submit the review.');
                    report.state = decision === 'accept' ? 'archived' : 'returned';
                    report.state_label = result.status_label;
                    report.notes = remarks.value;
                    report.can_review = false;
                    if (decision === 'return') report.can_view = false;
                    row.dataset.reports = JSON.stringify(org);
                    const cell = row.querySelector(`td[data-label="${type.toUpperCase()} status"]`);
                    const badge = cell.querySelector('.sr-status');
                    badge.className = `sr-status is-${report.state}`;
                    badge.textContent = report.state_label;
                    let note = cell.querySelector('small');
                    if (!note) { note = element('small'); cell.append(note); }
                    note.textContent = report.notes;
                    showCards();
                } catch (error) {
                    message.textContent = error.message || 'Unable to submit the review.';
                    message.hidden = false;
                } finally {
                    pending = false;
                    updateReviewButtons();
                }
            });
            remarks.addEventListener('input', () => { message.hidden = true; updateReviewButtons(); });
            const lockDialog = document.getElementById('srLockDialog');
            const lockConfirm = document.getElementById('srLockConfirm');
            let pendingLock = null;
            let confirmedForm = null;
            let lockSubmitted = false;
            const lockFormState = form => JSON.stringify({
                action: form.action,
                method: form.method,
                reportType: form.dataset.srSubmissionForm,
                fields: Array.from(new FormData(form).entries()),
            });
            function closeLockDialog() {
                const opener = pendingLock?.submitter;
                pendingLock = null;
                confirmedForm = null;
                if (lockDialog.open) lockDialog.close();
                if (opener?.isConnected) opener.focus({ preventScroll: true });
            }
            document.querySelectorAll('[data-sr-submission-form]').forEach(form => {
                form.addEventListener('submit', event => {
                    if (lockSubmitted) {
                        event.preventDefault();
                        return;
                    }
                    if (form === confirmedForm) {
                        confirmedForm = null;
                        lockSubmitted = true;
                        pendingLock = null;
                        lockConfirm.disabled = true;
                        form.querySelector('[data-sr-submission-button]').disabled = true;
                        lockDialog.close();
                        return;
                    }
                    // Locking already has the dedicated period/scope confirmation below.
                    if (form.elements.namedItem('is_locked').value !== '1') {
                        window.OrgActionConfirmations.confirmSubmit(event, 'Reopen submissions for this reporting period?');
                        return;
                    }
                    event.preventDefault();
                    if (lockDialog.open) return;
                    const submitter = event.submitter || form.querySelector('[data-sr-submission-button]');
                    pendingLock = { form, submitter, state: lockFormState(form) };
                    document.getElementById('srLockTitle').textContent = `Lock ${form.dataset.srSubmissionForm.toUpperCase()} submissions?`;
                    document.getElementById('srLockPeriod').textContent = `AY ${form.elements.namedItem('academic_year').value} · ${form.elements.namedItem('semester').value}`;
                    lockConfirm.disabled = false;
                    lockDialog.showModal();
                });
            });
            document.getElementById('srLockCancel').addEventListener('click', closeLockDialog);
            lockDialog.addEventListener('cancel', event => {
                event.preventDefault();
                closeLockDialog();
            });
            lockDialog.addEventListener('click', event => {
                if (event.target !== lockDialog) return;
                const bounds = lockDialog.getBoundingClientRect();
                if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) closeLockDialog();
            });
            lockDialog.addEventListener('close', () => {
                if (!lockDialog.open && pendingLock) closeLockDialog();
            });
            lockConfirm.addEventListener('click', () => {
                if (!pendingLock || !lockDialog.open || lockSubmitted || lockConfirm.disabled) return;
                const { form, submitter, state } = pendingLock;
                if (!form.isConnected || submitter.form !== form || lockFormState(form) !== state) {
                    closeLockDialog();
                    return;
                }
                lockConfirm.disabled = true;
                confirmedForm = form;
                form.requestSubmit(submitter);
                if (!lockSubmitted) {
                    confirmedForm = null;
                    lockConfirm.disabled = false;
                }
            });

            const search = document.getElementById('srDirectorySearch');
            const rows = Array.from(document.querySelectorAll('[data-sr-directory-row]'));
            const pageSize = 5;
            const params = new URLSearchParams(location.search);
            if (search) search.value = params.get('directory_search') || '';
            let page = Math.max(1, Number.parseInt(params.get('directory_page'), 10) || 1);
            if (!params.has('directory_page')) {
                const selectedIndex = rows.findIndex(row => row.classList.contains('is-selected'));
                if (selectedIndex >= 0) page = Math.floor(selectedIndex / pageSize) + 1;
            }
            function renderDirectory() {
                const query = search ? search.value.trim().toLowerCase() : '';
                const matches = rows.filter(row => row.dataset.search.includes(query));
                const pages = Math.max(1, Math.ceil(matches.length / pageSize));
                page = Math.min(page, pages);
                rows.forEach(row => { row.hidden = true; });
                const start = (page - 1) * pageSize;
                matches.slice(start, start + pageSize).forEach(row => { row.hidden = false; });
                document.getElementById('srDirectoryCount').textContent = `${matches.length} of ${rows.length} organizations`;
                document.getElementById('srDirectoryNoMatch').hidden = matches.length !== 0 || rows.length === 0;
                document.getElementById('srDirectoryPagination').hidden = matches.length === 0;
                document.getElementById('srDirectoryPageInfo').textContent = `Showing ${matches.length ? start + 1 : 0}–${Math.min(start + pageSize, matches.length)} of ${matches.length} organizations`;
                document.getElementById('srDirectoryPageNumber').textContent = `Page ${page} of ${pages}`;
                document.getElementById('srDirectoryPrevious').disabled = page === 1;
                document.getElementById('srDirectoryNext').disabled = page === pages;
                const url = new URL(location.href);
                url.searchParams.set('directory_page', String(page));
                if (search?.value) url.searchParams.set('directory_search', search.value);
                else url.searchParams.delete('directory_search');
                history.replaceState(null, '', url);
                document.querySelectorAll('[data-sr-view-submitted], .sr-tabs a').forEach(link => {
                    const target = new URL(link.href);
                    target.searchParams.set('directory_page', String(page));
                    if (search?.value) target.searchParams.set('directory_search', search.value);
                    else target.searchParams.delete('directory_search');
                    link.href = target.href;
                });
            }
            search?.addEventListener('input', () => { page = 1; renderDirectory(); });
            function changePage(delta) {
                page += delta;
                renderDirectory();
                document.getElementById('srOrganizationDirectory').scrollIntoView({ block: 'start' });
            }
            document.getElementById('srDirectoryPrevious').addEventListener('click', () => changePage(-1));
            document.getElementById('srDirectoryNext').addEventListener('click', () => changePage(1));
            renderDirectory();
            document.querySelectorAll('[data-sr-review-form]').forEach((form) => {
                const reason = form.elements.namedItem('notes');
                const validate = () => reason.setCustomValidity(reason.value.trim() ? '' : 'Enter a reason for this decision.');
                reason.addEventListener('input', validate);
                validate();
            });
        })();
    </script>
@endpush
