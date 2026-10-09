@extends('org.layout')

@php
    $selectedTab = $selectedTab ?? 'ar';
    $selectedOrganization = $selectedOrganization ?? '';
    $selectedYear = $selectedYear ?? '';
    $selectedSemester = $selectedSemester ?? '';
    $bundle = $reportBundle ?? [];
    $record = $bundle['reports'][$selectedTab] ?? [];
    $reportTitle = $selectedTab === 'fr' ? 'Financial Report' : 'Accomplishment Report';
    $documents = !empty($record['can_view']) ? collect($record['documents'] ?? []) : collect();
    $reportDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->setTimezone(config('app.timezone'))->format('M j, Y g:i A') : 'Not yet';
    $periodQuery = ['organization' => $selectedOrganization, 'academic_year' => $selectedYear, 'semester' => $selectedSemester];
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
    $previewData = ['reports' => $previewReports, 'reviewer_mode' => true];
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
        <section class="sr-card" aria-labelledby="srPeriodTitle">
            <div class="sr-head"><div><h2 id="srPeriodTitle">Reporting period</h2><p class="sr-muted">Choose an academic year and semester. Both report statuses are shown for that period.</p></div></div>
            <form method="get" action="{{ route('office.reports.index') }}" class="sr-filters">
                <input type="hidden" name="tab" value="{{ $selectedTab }}">
                <label>Organization<select name="organization"><option value="">All organizations</option>@foreach (($organizations ?? []) as $name)<option value="{{ $name }}" @selected($selectedOrganization === $name)>{{ $name }}</option>@endforeach</select></label>
                <label>Academic year<select name="academic_year">@foreach (($reportingYears ?? []) as $year)<option value="{{ $year }}" @selected($selectedYear === (string) $year)>{{ $year }}</option>@endforeach</select></label>
                <label>Semester<select name="semester">@foreach (($reportingSemesters ?? []) as $semester)<option value="{{ $semester }}" @selected($selectedSemester === $semester)>{{ $semester }}</option>@endforeach</select></label>
                <button type="submit" class="org-btn org-btn-primary"><i class="bi bi-funnel"></i> View period</button>
            </form>
        </section>

        <section class="sr-card" id="reportSubmissionControls" aria-labelledby="reportSubmissionControlsTitle">
            <div class="sr-head">
                <div>
                    <h2 id="reportSubmissionControlsTitle"><i class="bi bi-lock"></i> Submission controls</h2>
                    <p class="sr-muted">AY {{ $selectedYear }} · {{ $selectedSemester }} · Applies to all organizations in this period.</p>
                </div>
            </div>
            <p class="sr-muted">Lock AR and FR independently to prevent accidental submissions. SO can still prepare, preview and download reports. Reports already submitted remain available for review.</p>
            <div class="sr-documents">
                @foreach (['ar' => 'Accomplishment Report (AR)', 'fr' => 'Financial Report (FR)'] as $type => $title)
                    @php($submissionLocked = (bool) ($submissionLocks[$type] ?? false))
                    <div class="sr-document" data-submission-control="{{ $type }}" data-submission-locked="{{ $submissionLocked ? 'true' : 'false' }}">
                        <div>
                            <strong>{{ $title }}</strong>
                            <small>{{ $submissionLocked ? 'Locked — SO cannot submit' : 'Open — SO can submit completed reports' }}</small>
                        </div>
                        <form method="post" action="{{ route('office.reports.submission-lock', ['reportType' => $type]) }}" class="sr-actions">
                            @csrf
                            <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                            <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                            <input type="hidden" name="organization" value="{{ $selectedOrganization }}">
                            <input type="hidden" name="tab" value="{{ $selectedTab }}">
                            <input type="hidden" name="is_locked" value="{{ $submissionLocked ? '0' : '1' }}">
                            <button type="submit" class="org-btn {{ $submissionLocked ? 'org-btn-outline' : 'org-btn-primary' }} org-btn-sm">
                                <i class="bi {{ $submissionLocked ? 'bi-unlock' : 'bi-lock' }}"></i> {{ $submissionLocked ? 'Reopen' : 'Lock' }} {{ strtoupper($type) }} submissions
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sr-card" id="srOrganizationDirectory" aria-labelledby="srDirectoryTitle">
            <div class="sr-head"><div><h2 id="srDirectoryTitle">Organization report directory</h2><p class="sr-muted">AY {{ $selectedYear }} · {{ $selectedSemester }}</p></div><span class="sr-muted" id="srDirectoryCount" role="status">{{ count($reportDirectory ?? []) }} organizations</span></div>
            <label class="sr-search" for="srDirectorySearch">Search organizations<input id="srDirectorySearch" type="search" placeholder="Organization, abbreviation or college" autocomplete="off"></label>
            <div class="sr-directory-wrap">
                <table class="sr-directory">
                    <thead><tr><th scope="col">Organization</th><th scope="col">Accomplishment Report (AR)</th><th scope="col">Financial Report (FR)</th><th scope="col">Reports</th></tr></thead>
                    <tbody>
                        @forelse (($reportDirectory ?? []) as $entry)
                            <tr data-sr-directory-row data-search="{{ \Illuminate\Support\Str::lower($entry['organization'].' '.($entry['short_name'] ?? '').' '.($entry['college'] ?? '')) }}" @class(['is-selected' => $selectedOrganization === $entry['organization']]) @if ($loop->index >= 5) hidden @endif>
                                <td data-label="Organization"><strong>{{ $entry['organization'] }}</strong><small>{{ $entry['short_name'] ?? '' }}@if (!empty($entry['short_name']) && !empty($entry['college'])) · @endif{{ $entry['college'] ?? '' }}</small></td>
                                @foreach (['ar', 'fr'] as $type)
                                    @php($directoryReport = $entry['reports'][$type] ?? [])
                                    <td data-label="{{ strtoupper($type) }} status">
                                        <span class="sr-status is-{{ $directoryReport['state'] ?? 'draft' }}">{{ $directoryReport['state_label'] ?? 'Draft — not submitted' }}</span>
                                        <small>Submitted: {{ $reportDate($directoryReport['submitted_at'] ?? null) }}</small>
                                        @if (!empty($directoryReport['notes']))<small>{{ $directoryReport['notes'] }}</small>@endif
                                    </td>
                                @endforeach
                                <td data-label="Reports">
                                    @if ($entry['can_view_submitted'])
                                        <a class="org-btn org-btn-ghost org-btn-sm" href="{{ $entry['view_url'] }}#srSelectedReport" data-sr-view-submitted>View Submitted Reports</a>
                                    @else
                                        <button type="button" class="org-btn org-btn-ghost org-btn-sm" disabled title="No submitted AR or FR file is available for review.">View Submitted Reports</button>
                                    @endif
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

        <section class="sr-card" id="srSelectedReport" aria-labelledby="srSelectedTitle">
            @if ($selectedOrganization === '')
                <div class="sr-empty"><h3 id="srSelectedTitle">Choose an organization</h3><p>Use View Submitted Reports to open its reports for the selected semester.</p></div>
            @else
                <div class="sr-head"><div><h2 id="srSelectedTitle">{{ $selectedOrganization }}</h2><p class="sr-muted">{{ $bundle['college'] ?? '' }} · AY {{ $selectedYear }} · {{ $selectedSemester }}</p></div></div>
                <nav class="sr-tabs" aria-label="Report type">
                    @foreach (['ar' => 'Accomplishment Report (AR)', 'fr' => 'Financial Report (FR)'] as $type => $title)
                        @php($tabReport = $bundle['reports'][$type] ?? [])
                        <a class="sr-tab" href="{{ route('office.reports.index', array_merge($periodQuery, ['tab' => $type])) }}#srSelectedReport" @if ($selectedTab === $type) aria-current="page" @endif>{{ $title }}<span class="sr-status is-{{ $tabReport['state'] ?? 'draft' }}">{{ $tabReport['state_label'] ?? 'Draft — not submitted' }}</span></a>
                    @endforeach
                </nav>
                <div class="sr-head"><h3>{{ $reportTitle }}</h3><span class="sr-status is-{{ $record['state'] ?? 'draft' }}">{{ $record['state_label'] ?? 'Draft — not submitted' }}</span></div>
                <dl class="sr-dates">
                    <div><dt>Submitted to OSO</dt><dd>{{ $reportDate($record['submitted_at'] ?? null) }}</dd></div>
                    <div><dt>Opened by OSO</dt><dd>{{ $reportDate($record['opened_at'] ?? null) }}</dd></div>
                    <div><dt>Reviewed by OSO</dt><dd>{{ $reportDate($record['reviewed_at'] ?? null) }}</dd></div>
                </dl>
                <div class="sr-remarks"><strong>OSO remarks</strong><p>{{ ($record['notes'] ?? null) ?: 'No reviewer remarks recorded.' }}</p></div>
                @if (!empty($record['can_view']))
                    <div class="sr-documents" aria-label="Submitted {{ strtoupper($selectedTab) }} documents">
                        @forelse ($documents as $document)
                            <div class="sr-document">
                                <div><strong>{{ $document->name ?: $document->original_name }}</strong><small>{{ $document->original_name }} · Saved {{ $reportDate($document->created_at) }}</small></div>
                                <div class="sr-actions">
                                    @if ($previewReports[(string) $document->id]['has_file'])
                                        <button type="button" class="org-btn org-btn-primary org-btn-sm" data-fin-view="{{ $document->id }}"><i class="bi bi-eye"></i> Preview {{ strtoupper($selectedTab) }}</button>
                                        <a class="org-btn org-btn-ghost org-btn-sm" href="{{ $previewReports[(string) $document->id]['download_url'] }}"><i class="bi bi-download"></i> Download original</a>
                                    @else
                                        <span class="sr-danger">The submitted file is missing or unreadable.</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="sr-notice">No submitted {{ strtoupper($selectedTab) }} document is available for this period.</p>
                        @endforelse
                    </div>
                @else
                    <p class="sr-notice">@if (($record['state'] ?? '') === 'returned')This {{ strtoupper($selectedTab) }} was returned for revision. Its documents will be available after SO resubmits.@else This {{ strtoupper($selectedTab) }} has not been submitted. Draft documents are not available to OSO.@endif</p>
                @endif
                @if (!empty($record['can_review']))
                    <h3>Review {{ strtoupper($selectedTab) }}</h3>
                    <p class="sr-muted">These actions apply only to the selected {{ $reportTitle }}. Return for fixable issues; rejection is final.</p>
                    <div class="sr-review-grid">
                        @foreach (['return' => 'Return for Revision', 'reject' => 'Reject'] as $decision => $label)
                            <form method="post" action="{{ route('office.reports.review', ['reportType' => $selectedTab]) }}" class="sr-review" data-sr-review-form>
                                @csrf
                                <input type="hidden" name="reportType" value="{{ $selectedTab }}">
                                <input type="hidden" name="organization_name" value="{{ $selectedOrganization }}">
                                <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                                <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                                <input type="hidden" name="decision" value="{{ $decision }}">
                                <label>{{ $decision === 'return' ? 'Revision' : 'Rejection' }} reason (required)<textarea name="notes" rows="3" required>{{ old('decision') === $decision && old('reportType') === $selectedTab ? old('notes') : '' }}</textarea></label>
                                <button type="submit" class="org-btn org-btn-ghost">{{ $label }} — {{ strtoupper($selectedTab) }}</button>
                            </form>
                        @endforeach
                    </div>
                    <form method="post" action="{{ route('office.reports.review', ['reportType' => $selectedTab]) }}" class="sr-actions sr-verify">
                        @csrf
                        <input type="hidden" name="reportType" value="{{ $selectedTab }}">
                        <input type="hidden" name="organization_name" value="{{ $selectedOrganization }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                        <input type="hidden" name="decision" value="accept">
                        <button type="submit" class="org-btn org-btn-primary" @disabled(empty($record['has_document']))><i class="bi bi-check2-circle"></i> Verify &amp; archive {{ strtoupper($selectedTab) }}</button>
                    </form>
                    @if (empty($record['has_document']))<p class="sr-notice is-error">Verification and archiving require a readable submitted file. Return this {{ strtoupper($selectedTab) }} for revision or reject it with a reason.</p>@endif
                @elseif (($record['state'] ?? '') === 'oso_review')
                    <p class="sr-notice is-error">This report is not currently eligible for a review decision.</p>
                @endif
            @endif
        </section>
    </div>

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
    <script type="application/json" id="soFinancialData">@json($previewData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/so-financial.js') }}?v={{ filemtime(public_path('js/so-financial.js')) }}"></script>
    <script>
        (() => {
            const search = document.getElementById('srDirectorySearch');
            const rows = Array.from(document.querySelectorAll('[data-sr-directory-row]'));
            const pageSize = 5;
            const params = new URLSearchParams(location.search);
            search.value = params.get('directory_search') || '';
            let page = Math.max(1, Number.parseInt(params.get('directory_page'), 10) || 1);
            if (!params.has('directory_page')) {
                const selectedIndex = rows.findIndex(row => row.classList.contains('is-selected'));
                if (selectedIndex >= 0) page = Math.floor(selectedIndex / pageSize) + 1;
            }
            function renderDirectory() {
                const query = search.value.trim().toLowerCase();
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
                if (search.value) url.searchParams.set('directory_search', search.value);
                else url.searchParams.delete('directory_search');
                history.replaceState(null, '', url);
                document.querySelectorAll('[data-sr-view-submitted], .sr-tabs a').forEach(link => {
                    const target = new URL(link.href);
                    target.searchParams.set('directory_page', String(page));
                    if (search.value) target.searchParams.set('directory_search', search.value);
                    else target.searchParams.delete('directory_search');
                    link.href = target.href;
                });
            }
            search.addEventListener('input', () => { page = 1; renderDirectory(); });
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
