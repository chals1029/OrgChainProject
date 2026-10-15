@extends('org.layout')

@php
    $ad = $accomplishmentDashboard ?? [];
    $reports = $ad['reports'] ?? [];
    $eligible = $ad['eligible_activities'] ?? [];
    $canEdit = ($office->office_role ?? '') === 'so' && !empty($ad['can_edit']) && empty($ad['locked']);
    $year = (string) ($ad['academic_year'] ?? $selectedYear ?? '');
    $semester = (string) ($ad['semester'] ?? $selectedSemester ?? '');
    $organization = (string) ($ad['organization'] ?? $selectedOrganization ?? '');
    $money = fn ($value) => '₱'.number_format((float) $value, 2);
    $sdgNames = [1 => 'No Poverty', 2 => 'Zero Hunger', 3 => 'Good Health and Well-being', 4 => 'Quality Education', 5 => 'Gender Equality', 6 => 'Clean Water and Sanitation', 7 => 'Affordable and Clean Energy', 8 => 'Decent Work and Economic Growth', 9 => 'Industry, Innovation and Infrastructure', 10 => 'Reduced Inequalities', 11 => 'Sustainable Cities and Communities', 12 => 'Responsible Consumption and Production', 13 => 'Climate Action', 14 => 'Life Below Water', 15 => 'Life on Land', 16 => 'Peace, Justice and Strong Institutions', 17 => 'Partnerships for the Goals'];
    $sdgLabel = function ($goal) use ($sdgNames) {
        $text = trim((string) $goal);
        if (preg_match('/^(?:SDG\s*)?(\d{1,2})$/i', $text, $match) && isset($sdgNames[(int) $match[1]])) {
            return 'SDG '.(int) $match[1].': '.$sdgNames[(int) $match[1]];
        }
        return $text;
    };
    $signatoryRoles = ['secretary' => 'Secretary', 'auditor' => 'Auditor', 'president' => 'President', 'adviser' => 'Adviser', 'coordinator' => 'OSO Coordinator', 'head' => 'Head, Student Organization'];
    $editorErrors = $errors->any() && old('_form') === 'accomplishment-report';
    $uploadErrors = $errors->any() && old('_form') === 'accomplishment-upload';
    $uploadYear = $uploadErrors ? (string) old('academic_year', $year) : $year;
    $uploadSemester = $uploadErrors ? (string) old('semester', $semester) : $semester;
    $uploadYears = collect($ad['years'] ?? [])->push($year)->push($uploadYear)->filter()->unique()->sortDesc();
    $packetDocument = collect(data_get($reportBundle ?? [], 'reports.ar.documents', []))->first();
    $packetIsNative = $packetDocument && $packetDocument->accomplishment_summary !== null;
    $clientData = [
        'eligible' => array_values($eligible), 'reports' => array_values($reports),
        'storeUrl' => $ad['store_url'] ?? '', 'canEdit' => $canEdit,
        'sdgNames' => $sdgNames,
        'old' => $editorErrors ? session()->getOldInput() : null,
    ];
@endphp

@section('title', 'Accomplishment Report')
@section('header')
    <h1><strong>Accomplishment Report</strong></h1>
    <p class="org-welcome">{{ $organization ?: 'Select an organization to review its report' }} · {{ $semester }} · AY {{ $year }}</p>
@endsection
@section('actions')
    @if ($canEdit)
        <button type="button" class="org-btn org-btn-primary" data-ar-upload><i class="bi bi-plus-lg"></i> Create activity report</button>
    @endif
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/org-accomplishment.css') }}?v={{ filemtime(public_path('css/org-accomplishment.css')) }}">
    <div class="ar-desk">
        @if ($errors->any() && !$uploadErrors && (!$editorErrors || !$canEdit))
            <div class="ar-alert is-error" role="alert"><strong>The report could not be updated.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <section class="ar-card" aria-labelledby="arPeriodTitle">
            <div class="ar-section-head">
                <div><span class="ar-eyebrow">Official semester reporting</span><h2 id="arPeriodTitle">Your accomplishment desk</h2><p class="ar-muted">Activities, attendance and accomplishment reports.</p></div>
                <span class="ar-status {{ !empty($ad['locked']) ? 'is-locked' : '' }}">{{ !empty($ad['locked']) ? 'AR locked' : (($office->office_role ?? '') === 'oso' ? 'Review only' : 'Editable · not submitted') }}</span>
            </div>
            <form method="get" action="{{ route('office.accomplishment') }}" class="ar-period-form" data-report-period-form>
                @if (($office->office_role ?? '') === 'oso')
                    <label class="ar-field"><span>Organization</span><select name="organization"><option value="">Select organization</option>@foreach (($organizations ?? []) as $name)<option value="{{ $name }}" @selected($organization === $name)>{{ $name }}</option>@endforeach</select></label>
                @endif
                <label class="ar-field"><span>Academic year</span><select name="academic_year">@foreach (($ad['years'] ?? []) as $option)<option value="{{ $option }}" @selected($year === (string) $option)>{{ $option }}</option>@endforeach</select></label>
                <label class="ar-field"><span>Semester</span><select name="semester">@foreach (($ad['semesters'] ?? []) as $option)<option value="{{ $option }}" @selected($semester === $option)>{{ $option }}</option>@endforeach</select></label>
            </form>
            <div class="ar-stats" aria-label="Actual saved accomplishment statistics">
                <article><span class="ar-stat-icon"><i class="bi bi-journal-check"></i></span><div><small>Reported activities</small><strong>{{ number_format((int) data_get($ad, 'stats.activities', 0)) }}</strong><span>This reporting period</span></div></article>
                <article><span class="ar-stat-icon"><i class="bi bi-people"></i></span><div><small>Actual participants</small><strong>{{ number_format((int) data_get($ad, 'stats.participants', 0)) }}</strong><span>Saved attendance</span></div></article>
                <article><span class="ar-stat-icon"><i class="bi bi-images"></i></span><div><small>Supporting images</small><strong>{{ number_format((int) data_get($ad, 'stats.evidence', 0)) }}</strong><span>Captioned documentation</span></div></article>
                <article><span class="ar-stat-icon"><i class="bi bi-receipt"></i></span><div><small>Recorded expenses</small><strong>{{ $money(data_get($ad, 'stats.expenses', 0)) }}</strong><span>Activity spending</span></div></article>
            </div>
        </section>

        <section class="ar-card" aria-labelledby="arPacketTitle">
            <div class="ar-section-head"><div><span class="ar-eyebrow">Semester accomplishment report</span><h2 id="arPacketTitle">{{ $packetDocument && !$packetIsNative ? 'Uploaded AR report' : 'Compiled AR packet' }}</h2><p class="ar-muted">{{ $semester }} · AY {{ $year }}</p></div><span class="ar-status">{{ data_get($reportBundle ?? [], 'reports.ar.state', 'draft') === 'draft' ? 'Not submitted' : data_get($reportBundle ?? [], 'reports.ar.state_label', 'Not submitted') }}</span></div>
            @if (!empty($ad['locked']))<p class="ar-alert"><i class="bi bi-lock"></i> This AR is locked for editing.</p>@endif
            <div class="ar-actions">
                @if ($packetDocument && !$packetIsNative && $packetDocument->hasStoredFile())
                    <button class="org-btn org-btn-primary" type="button" data-document-preview data-document-preview-url="{{ route('office.reports.documents.view', $packetDocument) }}" data-document-preview-title="{{ $packetDocument->name ?: $packetDocument->original_name }}" data-document-original-name="{{ $packetDocument->original_name }}" data-document-preview-type="{{ strtolower(pathinfo($packetDocument->original_name, PATHINFO_EXTENSION)) }}" data-document-preview-download="{{ route('office.reports.documents.view', ['document' => $packetDocument, 'download' => 1]) }}" data-document-conversion-url="{{ route('office.documents.preview.convert') }}"><i class="bi bi-eye"></i> Preview report</button>
                    <a class="org-btn org-btn-ghost" href="{{ route('office.reports.documents.view', ['document' => $packetDocument, 'download' => 1]) }}"><i class="bi bi-download"></i> Download original</a>
                @elseif ((!$packetDocument || $packetIsNative) && !empty($ad['preview_url']))
                    <button class="org-btn org-btn-primary" type="button" data-ar-preview="{{ $ad['preview_url'] }}" data-ar-title="Semester accomplishment packet" data-ar-download="{{ $ad['export_url'] ?? '' }}"><i class="bi bi-eye"></i> Preview semester packet</button>
                    <button class="org-btn org-btn-ghost" type="button" data-ar-preview="{{ $ad['preview_url'] }}" data-ar-title="Semester accomplishment packet" data-ar-print><i class="bi bi-printer"></i> Print / Save as PDF</button>
                @endif
                @if ((!$packetDocument || $packetIsNative) && !empty($ad['export_url']))<a class="org-btn org-btn-ghost" href="{{ $ad['export_url'] }}"><i class="bi bi-file-earmark-word"></i> Download Word</a>@endif
                @if (!empty($ad['template_url']))<a class="org-btn org-btn-ghost" href="{{ $ad['template_url'] }}"><i class="bi bi-download"></i> Narrative template</a>@endif
                @if (!empty($ad['particulars_template_url']))<a class="org-btn org-btn-ghost" href="{{ $ad['particulars_template_url'] }}"><i class="bi bi-download"></i> Particulars template</a>@endif
            </div>
            @if (!$packetDocument)<p class="ar-muted">No saved report for this period.</p>@elseif (!$packetDocument->hasStoredFile())<p class="ar-alert is-error">The saved report file is missing or unreadable.</p>@endif
            @include('org.partials.semester-report-status', ['reportType' => 'ar', 'reportBundle' => $reportBundle ?? [], 'selectedOrganization' => $organization, 'selectedSemester' => $semester, 'selectedYear' => $year, 'hideReportPeriodControls' => true, 'embedReportStatus' => true])
        </section>

        <section aria-labelledby="arReportsTitle">
            <div class="ar-section-head ar-list-head"><div><h2 id="arReportsTitle">Activity reports <span class="ar-count">{{ count($reports) }}</span></h2><p class="ar-muted">Completed activities with attendance, documentation and expenses.</p></div></div>
            @if (empty($reports))
                <div class="ar-card ar-empty"><i class="bi bi-journal-text" aria-hidden="true"></i><h3>No activity reports for this period</h3><p>{{ $canEdit ? 'Upload a completed report using Create activity report.' : 'Saved activity reports will appear here.' }}</p></div>
            @else
                <div class="ar-report-grid">
                    @foreach ($reports as $report)
                        <article class="ar-card ar-report-card">
                            <div class="ar-section-head"><span class="ar-eyebrow">Saved activity report</span>@if (!empty($report['classification']))<span class="ar-tag">{{ $report['classification'] }}</span>@endif</div>
                            <h3>{{ $report['title'] }}</h3><p class="ar-muted">{{ $report['date_label'] ?? '' }}@if (!empty($report['time_label'])) · {{ $report['time_label'] }}@endif<br>{{ $report['venue'] ?? '' }}</p>
                            <p class="ar-report-summary">{{ $report['brief_description'] ?? '' }}</p>
                            <div class="ar-tags">@foreach (($report['sdg_goals'] ?? []) as $goal)<span class="ar-tag">{{ $sdgLabel($goal) }}</span>@endforeach</div>
                            <dl class="ar-report-metrics"><div><dt>Actual attendance</dt><dd>{{ number_format((int) ($report['participants'] ?? 0)) }}<small>M {{ $report['male_participants'] ?? 0 }} / F {{ $report['female_participants'] ?? 0 }}</small></dd></div><div><dt>Evidence</dt><dd>{{ count($report['evidence'] ?? []) }}<small>Captioned images</small></dd></div><div><dt>Expenses</dt><dd>{{ $money(data_get($report, 'financial.total_expenses', 0)) }}<small>Recorded activity spending</small></dd></div></dl>
                            <div class="ar-actions">@if (!empty($report['preview_url']))<button class="org-btn org-btn-ghost" type="button" data-ar-preview="{{ $report['preview_url'] }}" data-ar-title="{{ $report['title'] }}"><i class="bi bi-eye"></i> Preview report</button>@endif @if ($canEdit)<button class="org-btn org-btn-ghost" type="button" data-ar-edit="{{ $report['id'] }}"><i class="bi bi-pencil-square"></i> Edit report</button>@endif</div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

    </div>

    @if ($canEdit || $uploadErrors)
    <dialog class="ar-dialog ar-upload-dialog" id="arUploadDialog" aria-labelledby="arUploadTitle" @if ($uploadErrors) data-open-on-load @endif>
        <form method="post" action="{{ route('office.reports.documents.store', ['reportType' => 'ar']) }}" enctype="multipart/form-data" id="arUploadForm">
            @csrf
            <input type="hidden" name="_form" value="accomplishment-upload">
            <input type="hidden" name="reportType" value="ar">
            <input type="hidden" name="organization_name" value="{{ $organization }}">
            <header class="ar-dialog-head"><div><span class="ar-eyebrow">Create activity report</span><h2 id="arUploadTitle">Upload accomplished report file</h2></div><button type="button" class="ar-icon-button" data-ar-close aria-label="Close upload dialog"><i class="bi bi-x-lg"></i></button></header>
            <div class="ar-dialog-body">
                <p class="ar-muted ar-upload-note">Upload the completed report. Submit it to OSO separately when ready.</p>
                @if ($uploadErrors)<div class="ar-alert is-error" role="alert" tabindex="-1" id="arUploadErrors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p>Choose the report file again.</p></div>@endif
                <div class="ar-field-row">
                    <label class="ar-field"><span>Academic year *</span><select name="academic_year" required @disabled(!$canEdit)>@foreach ($uploadYears as $option)<option value="{{ $option }}" @selected($uploadYear === (string) $option)>AY {{ $option }}</option>@endforeach</select></label>
                    <label class="ar-field"><span>Semester *</span><select name="semester" required @disabled(!$canEdit)>@foreach (($ad['semesters'] ?? ['1st Semester', '2nd Semester', 'Midyear']) as $option)<option value="{{ $option }}" @selected($uploadSemester === $option)>{{ $option }}</option>@endforeach</select></label>
                </div>
                <label class="ar-upload-zone" for="arUploadFile">
                    <span class="ar-upload-icon"><i class="bi bi-upload" aria-hidden="true"></i></span>
                    <strong>Choose accomplishment report file</strong>
                    <small>DOC, DOCX or PDF · Up to 20 MB</small>
                    <input type="file" name="document" id="arUploadFile" required @disabled(!$canEdit) accept=".doc,.docx,.pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf" aria-describedby="arUploadFilename">
                    <span id="arUploadFilename" class="ar-upload-filename" role="status">No file selected</span>
                </label>
            </div>
            <footer class="ar-dialog-foot"><button type="button" class="org-btn org-btn-ghost" data-ar-close>Cancel</button><button type="submit" class="org-btn org-btn-primary" id="arUploadSave" @disabled(!$canEdit)><i class="bi bi-upload"></i> Upload report</button></footer>
        </form>
    </dialog>
    @endif
    @if ($canEdit)
    <dialog class="ar-dialog ar-editor-dialog" id="arEditorDialog" aria-labelledby="arEditorTitle" @if ($editorErrors) data-open-on-load @endif>
        <form method="post" action="{{ $ad['store_url'] ?? '' }}" enctype="multipart/form-data" id="arEditorForm">
            @csrf
            <input type="hidden" name="_form" value="accomplishment-report"><input type="hidden" name="_report_id" value=""><input type="hidden" name="academic_year" value="{{ $year }}"><input type="hidden" name="semester" value="{{ $semester }}">
            <header class="ar-dialog-head"><div><span class="ar-eyebrow">{{ $semester }} · AY {{ $year }}</span><h2 id="arEditorTitle">Edit activity report</h2></div><button type="button" class="ar-icon-button" data-ar-close aria-label="Close report editor"><i class="bi bi-x-lg"></i></button></header>
            <div class="ar-dialog-body">
                @if ($editorErrors)<div class="ar-alert is-error" role="alert" tabindex="-1" id="arEditorErrors"><strong>Please correct the report.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p>Re-select new images before saving.</p></div>@endif
                <p class="ar-muted">Save your changes, then submit the semester AR to OSO separately.</p>
                <section class="ar-form-section"><h3>1. Approved activity context</h3><label class="ar-field"><span>Approved, ended activity <b aria-hidden="true">*</b></span><select name="org_activity_id" id="arActivity" required><option value="">Choose an eligible activity</option>@foreach ($eligible as $activity)<option value="{{ $activity['id'] }}">{{ $activity['title'] }}</option>@endforeach</select></label>
                    <div class="ar-context" id="arActivityContext" hidden><h4 id="arContextTitle"></h4><dl><div><dt>Date / day</dt><dd id="arContextDate"></dd></div><div><dt>Time</dt><dd id="arContextTime"></dd></div><div><dt>Venue</dt><dd id="arContextVenue"></dd></div></dl><div class="ar-tags" id="arContextSdgs"></div></div>
                    <div class="ar-field-row"><label class="ar-field"><span>Sponsor of the activity *</span><input type="text" name="sponsor" required maxlength="255"></label><label class="ar-field"><span>Organization classification <small>(optional)</small></span><input type="text" name="classification" maxlength="255" placeholder="e.g. College-Based / Academic / Campus-Wide"></label></div>
                    <label class="ar-field"><span>Objectives *</span><textarea name="objectives" required rows="4"></textarea></label>
                    <label class="ar-field"><span>Persons involved / participants *</span><textarea name="people_involved" required rows="3" placeholder="Describe who actually participated"></textarea></label>
                    <div class="ar-attendance"><label class="ar-field"><span>Actual male participants *</span><input type="number" name="male_participants" min="0" step="1" required inputmode="numeric"></label><label class="ar-field"><span>Actual female participants *</span><input type="number" name="female_participants" min="0" step="1" required inputmode="numeric"></label><div class="ar-total"><span>Total actual participants</span><output id="arParticipantTotal">—</output></div></div>
                </section>
                <section class="ar-form-section"><h3>2. Activity particulars & narrative</h3><p class="ar-muted">Write the actual outcome. For problems or recommendations that do not apply, enter “None” explicitly.</p>
                    <label class="ar-field"><span>Brief description / overview *</span><textarea name="brief_description" required rows="4"></textarea></label>
                    <label class="ar-field"><span>Highlights of the activity / narrative *</span><textarea name="narrative" required rows="7" placeholder="Describe the implementation, highlights and actual outcomes"></textarea></label>
                    <div class="ar-field-row"><label class="ar-field"><span>Problems encountered *</span><textarea name="problems_encountered" required rows="4"></textarea></label><label class="ar-field"><span>Recommendations *</span><textarea name="recommendations" required rows="4"></textarea></label></div>
                </section>
                <section class="ar-form-section"><h3>3. Captioned documentation *</h3><p class="ar-muted">At least one JPEG or PNG image. Up to 20 images, 10 MB each.</p><div class="ar-evidence-grid" id="arEvidenceList"></div><button class="org-btn org-btn-ghost" type="button" id="arAddPhoto"><i class="bi bi-image"></i> Add photo / supporting scan</button><p class="ar-alert is-error" id="arEvidenceError" role="alert" hidden></p></section>
                <section class="ar-form-section"><h3>4. Financial report</h3><p class="ar-muted">Uses saved collections, expenses and receipt references.</p><div class="ar-financial-context" id="arFinancialContext" hidden></div></section>
                <section class="ar-form-section"><h3>5. Signatories <small>(optional names)</small></h3><div class="ar-field-row">@foreach ($signatoryRoles as $key => $label)<label class="ar-field"><span>{{ $label }}</span><input type="text" name="signatories[{{ $key }}]" maxlength="255"></label>@endforeach</div></section>
            </div>
            <footer class="ar-dialog-foot"><button type="button" class="org-btn org-btn-ghost" data-ar-close>Cancel</button><button type="submit" class="org-btn org-btn-primary" id="arSave"><i class="bi bi-check-lg"></i> Save changes</button></footer>
        </form>
    </dialog>
    @endif

    <dialog class="ar-dialog ar-preview-dialog" id="arPreviewDialog" aria-labelledby="arPreviewTitle">
        <div class="ar-preview-box"><header class="ar-dialog-head"><div><span class="ar-eyebrow">Saved report</span><h2 id="arPreviewTitle">Accomplishment preview</h2></div><div class="ar-actions"><a href="" class="org-btn org-btn-ghost" id="arPreviewDownload" hidden><i class="bi bi-file-earmark-word"></i> Word</a><button type="button" class="org-btn org-btn-ghost" id="arPreviewPrint"><i class="bi bi-printer"></i> Print / PDF</button><button type="button" class="ar-icon-button" data-ar-close aria-label="Close report preview"><i class="bi bi-x-lg"></i></button></div></header><p class="ar-preview-status" id="arPreviewStatus" role="status">Loading saved report…</p><iframe id="arPreviewFrame" title="Saved accomplishment report" src="about:blank"></iframe></div>
    </dialog>
    <script type="application/json" id="orgAccomplishmentData">@json($clientData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/org-accomplishment.js') }}?v={{ filemtime(public_path('js/org-accomplishment.js')) }}" defer></script>
@endpush
