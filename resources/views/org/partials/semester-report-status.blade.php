@php
    $reportType = $reportType ?? 'ar';
    $reportTitle = $reportType === 'fr' ? 'Financial Report' : 'Accomplishment Report';
    $bundle = $reportBundle ?? [];
    $record = $bundle['reports'][$reportType] ?? [];
    $selectedOrganization = $bundle['organization'] ?? ($selectedOrganization ?? '');
    $selectedSemester = $bundle['semester'] ?? ($selectedSemester ?? '1st Semester');
    $selectedYear = $bundle['academic_year'] ?? ($selectedYear ?? '');
    $submissionLocked = (bool) ($bundle['submission_locks'][$reportType] ?? false);
    $currentDocuments = collect($record['documents'] ?? []);
    $latestDocument = $currentDocuments->first();
    $requiresFinancialView = $reportType === 'fr' && ($office->office_role ?? '') === 'so';
    $financialViewed = !$requiresFinancialView || ($latestDocument && app(\App\Services\FinancialReportPreviewGate::class)->wasViewed($office, $latestDocument));
    $reportDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->setTimezone(config('app.timezone'))->format('M j, Y g:i A') : 'Not yet';
    $yearStart = now()->month >= 8 ? now()->year : now()->year - 1;
    $periodYears = collect($reportingYears ?? [])->merge(collect(range($yearStart, $yearStart - 5))->map(fn ($year) => $year.'-'.($year + 1)))->push($selectedYear)->filter()->unique()->sortDesc();
@endphp

<link rel="stylesheet" href="{{ asset('css/semester-reports.css') }}?v={{ filemtime(public_path('css/semester-reports.css')) }}">
<section class="sr-card {{ ($embedReportStatus ?? false) ? 'sr-card-embedded' : '' }}" aria-label="{{ $reportTitle }} submission status">
    @if (!($embedReportStatus ?? false))
        <div class="sr-head">
            <div><h2>{{ $reportTitle }} submission</h2><p class="sr-muted">Submit {{ strtoupper($reportType) }} to OSO for this reporting period.</p></div>
            <span class="sr-status is-{{ $record['state'] ?? 'draft' }}">{{ ($record['state'] ?? 'draft') === 'draft' ? 'Not submitted' : ($record['state_label'] ?? 'Not submitted') }}</span>
        </div>
    @endif
    @if ($submissionLocked && ($office->office_role ?? '') === 'so')
        <p class="sr-notice" role="status" data-report-submission-locked="{{ $reportType }}"><i class="bi bi-lock"></i> {{ strtoupper($reportType) }} submissions are locked for AY {{ $selectedYear }} · {{ $selectedSemester }}. Preparation and preview remain available.</p>
    @endif
    @if (!($hideReportPeriodControls ?? false))
        <form method="get" action="{{ $reportType === 'fr' ? route('office.financial') : route('office.accomplishment') }}" class="sr-filters" data-report-period-form>
            <label>Organization<select name="organization"><option value="">Select organization</option>@foreach (($organizations ?? []) as $organizationName)<option value="{{ $organizationName }}" @selected($selectedOrganization === $organizationName)>{{ $organizationName }}</option>@endforeach</select></label>
            <label>Academic year<select name="academic_year">@foreach ($periodYears as $periodYear)<option value="{{ $periodYear }}" @selected($selectedYear === $periodYear)>{{ $periodYear }}</option>@endforeach</select></label>
            <label>Semester<select name="semester">@foreach (['1st Semester', '2nd Semester', 'Midyear'] as $periodSemester)<option value="{{ $periodSemester }}" @selected($selectedSemester === $periodSemester)>{{ $periodSemester }}</option>@endforeach</select></label>
        </form>
    @endif
    @if ($selectedOrganization !== '')
        @if (!empty($record['submitted_at']) || !empty($record['opened_at']) || !empty($record['reviewed_at']))
            <dl class="sr-dates">
                @if (!empty($record['submitted_at']))<div><dt>Submitted to OSO</dt><dd>{{ $reportDate($record['submitted_at']) }}</dd></div>@endif
                @if (!empty($record['opened_at']))<div><dt>Opened by OSO</dt><dd>{{ $reportDate($record['opened_at']) }}</dd></div>@endif
                @if (!empty($record['reviewed_at']))<div><dt>Reviewed by OSO</dt><dd>{{ $reportDate($record['reviewed_at']) }}</dd></div>@endif
            </dl>
        @endif
        @if (!empty($record['notes']))<div class="sr-remarks"><strong>OSO remarks</strong><p>{{ $record['notes'] }}</p></div>@endif
        <div class="sr-documents">
            @forelse ($currentDocuments as $document)
                <div class="sr-document">
                    <div><strong>{{ $document->name ?: $document->original_name }}</strong><small>{{ $document->original_name }}</small></div>
                    <div class="sr-actions">
                        @if ($document->hasStoredFile())
                        <a class="app-view-action" aria-label="View {{ strtoupper($reportType) }} report" href="{{ route('office.reports.documents.view', $document) }}" target="_blank" rel="noopener"
                            @if ($reportType === 'ar' && $document->accomplishment_summary !== null)
                                data-acc-preview="{{ route('office.reports.documents.view', ['document' => $document, 'embedded' => 1]) }}" data-acc-preview-title="{{ $document->name ?: $document->original_name }}" data-acc-preview-download="{{ route('office.reports.documents.view', ['document' => $document, 'download' => 1]) }}"
                            @elseif ($reportType === 'ar')
                                data-document-preview data-document-preview-url="{{ route('office.reports.documents.view', $document) }}" data-document-preview-title="{{ $document->name ?: $document->original_name }}" data-document-original-name="{{ $document->original_name }}" data-document-preview-type="{{ strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION)) }}" data-document-preview-download="{{ route('office.reports.documents.view', ['document' => $document, 'download' => 1]) }}" data-document-conversion-url="{{ route('office.documents.preview.convert') }}"
                            @endif><i class="bi bi-eye" aria-hidden="true"></i> View</a>
                        <a class="org-btn org-btn-ghost org-btn-sm" href="{{ route('office.reports.documents.view', ['document' => $document, 'download' => 1]) }}">Download</a>
                        @else
                            <span class="sr-danger">The saved file is missing or unreadable.</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="sr-muted">No saved {{ strtoupper($reportType) }} document for this period.</p>
            @endforelse
        </div>
        @if (($office->office_role ?? '') === 'so')
            @if (!empty($record['is_locked']))
                <p class="sr-notice"><i class="bi bi-lock"></i> This {{ strtoupper($reportType) }} is locked. @if (($record['state'] ?? '') === 'rejected')Rejection is final.@elseif (($record['state'] ?? '') === 'oso_review')Awaiting OSO review.@else Verified or archived.@endif</p>
            @else
                @if ($reportType === 'fr')
                    <form method="post" action="{{ route('office.reports.documents.store', ['reportType' => $reportType]) }}" enctype="multipart/form-data" class="sr-upload">
                        @csrf
                        <input type="hidden" name="reportType" value="{{ $reportType }}">
                        <input type="hidden" name="organization_name" value="{{ $selectedOrganization }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                        <label>Financial document<input type="file" name="document" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"></label>
                        <label>Document title (optional)<input type="text" name="name" maxlength="255"></label>
                        <button type="submit" class="org-btn org-btn-ghost">Save FR document</button>
                    </form>
                @endif
                <form method="post" action="{{ route('office.reports.submit', ['reportType' => $reportType]) }}" class="sr-actions">
                    @csrf
                    <input type="hidden" name="reportType" value="{{ $reportType }}">
                    <input type="hidden" name="organization_name" value="{{ $selectedOrganization }}">
                    <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                    <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                    <button type="submit" class="org-btn org-btn-primary" @disabled($submissionLocked || empty($record['has_document']) || !$financialViewed)>Submit {{ strtoupper($reportType) }} to OSO</button>
                    @if (empty($record['has_document']))<span class="sr-muted">Save the {{ strtoupper($reportType) }} document before submitting.</span>@endif
                    @if (!$financialViewed)<span class="sr-muted">View the latest Financial Report file before submitting FR.</span><a class="org-btn org-btn-ghost" href="{{ route('office.financial', ['academic_year' => $selectedYear, 'semester' => $selectedSemester]) }}">Open Financial Report</a>@endif
                </form>
            @endif
        @endif
    @else
        <p class="sr-muted">Select an organization and reporting period to see its {{ strtoupper($reportType) }} status.</p>
    @endif
    @if ($errors->has('report'))<p class="sr-notice is-error" role="alert">{{ $errors->first('report') }}</p>@endif
</section>

@include('org.partials.document-preview')
@once
    @push('scripts')
        <script src="{{ asset('js/semester-reports.js') }}?v={{ filemtime(public_path('js/semester-reports.js')) }}" defer></script>
    @endpush
@endonce
