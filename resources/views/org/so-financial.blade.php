@extends('org.layout')

@php
    $fd = $financialDashboard ?? [];
    $money = fn ($value) => $value === null ? null : '₱'.number_format((float) $value, 2);
    $formatDate = fn (?string $iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->setTimezone(config('app.timezone'))->format('M j, Y') : null;
    $yearFilter = (string) ($fd['year_filter'] ?? 'all');
    $semesterFilter = (string) ($fd['semester_filter'] ?? 'all');
    $years = $fd['years'] ?? [];
    $semesters = $fd['semesters'] ?? ['1st Semester', '2nd Semester', 'Midyear'];
    $activities = $fd['activities'] ?? [];
    $currentReports = $fd['current_reports'] ?? [];
    $historyReports = collect($fd['history_reports'] ?? [])
        ->sortByDesc(fn (array $report) => $report['submitted_at'] ?? $report['created_at'])
        ->values()
        ->all();
    $historyYears = collect($historyReports)->pluck('academic_year')->filter()->unique()->sortDesc()->values();
    $historySemesters = collect($historyReports)->pluck('semester')->filter()->unique()->values();

    $ayStart = now()->month >= 8 ? now()->year : now()->year - 1;
    $academicYearOptions = collect(range($ayStart + 1, $ayStart - 5))
        ->map(fn (int $year) => $year.'-'.($year + 1))
        ->merge(collect($years)->filter(fn ($year) => preg_match('/^(\d{4})-(\d{4})$/', (string) $year, $m) && (int) $m[2] === (int) $m[1] + 1))
        ->unique()
        ->sortDesc()
        ->values();
    $oldForm = old('_form');
    $createHasErrors = $errors->any() && $oldForm === 'financial-create';
    $openingHasErrors = $errors->any() && $oldForm === 'financial-opening';
    $incomeHasErrors = $errors->any() && $oldForm === 'financial-income';
    $dialogHasErrors = $createHasErrors || $openingHasErrors || $incomeHasErrors;
    $createYear = $oldForm === 'financial-create' ? old('academic_year') : ($yearFilter !== 'all' ? $yearFilter : $ayStart.'-'.($ayStart + 1));
    $createSemester = $oldForm === 'financial-create' ? old('semester') : ($semesterFilter !== 'all' ? $semesterFilter : ($semesters[0] ?? ''));

    $managementYear = (string) ($fd['management_year'] ?? '');
    $funding = $fd['funding'] ?? [];
    $hasAccount = (bool) ($funding['has_account'] ?? false);
    $incomeActivities = $fd['income_activities'] ?? [];
    $openingYear = $managementYear;
    $openingAmount = $openingHasErrors
        ? old('opening_balance')
        : ($hasAccount ? number_format((float) ($funding['opening_balance'] ?? 0), 2, '.', '') : '');
    $fiscalStart = (string) ($fd['fiscal_start'] ?? '');
    $fiscalEnd = (string) ($fd['fiscal_end'] ?? '');
    $incomeMaxDate = min($fiscalEnd, now()->toDateString());
    $incomeRequestKey = $incomeHasErrors && old('request_key') ? old('request_key') : (string) \Illuminate\Support\Str::uuid();

    $reportPayload = collect($currentReports)->merge($historyReports)
        ->mapWithKeys(fn (array $report) => [(string) $report['id'] => [
            'id' => $report['id'],
            'title' => $report['title'],
            'original_name' => $report['original_name'],
            'academic_year' => $report['academic_year'],
            'semester' => $report['semester'],
            'preview_url' => $report['preview_url'],
            'download_url' => $report['download_url'],
            'has_file' => (bool) $report['has_file'],
            'is_workbook' => (bool) $report['is_workbook'],
        ]]);
    $clientData = [
        'activities' => $activities,
        'reports' => $reportPayload,
    ];
@endphp

@section('title', 'Financial Report')

@section('header')
    <h1><strong>Financial Report</strong></h1>
    <p class="org-welcome">{{ $fd['organization'] ?? '' }} · {{ $fd['period_label'] ?? '' }}</p>
@endsection

@section('actions')
    <div class="fin-header-actions">
        <a href="{{ $fd['export_url'] ?? '#' }}" class="org-btn org-btn-ghost">
            <i class="bi bi-file-earmark-arrow-down"></i> Export Report
        </a>
        <button type="button" class="org-btn org-btn-primary" data-fin-open-create>
            <i class="bi bi-plus-lg"></i> Create Report
        </button>
    </div>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/so-financial.css') }}?v={{ filemtime(public_path('css/so-financial.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/semester-reports.css') }}?v={{ filemtime(public_path('css/semester-reports.css')) }}">

    <div class="so-fin">
        @if ($errors->any() && ! $dialogHasErrors)
            <div class="fin-alert is-error" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (!empty($submissionLocks['fr']))
            <div class="fin-alert" role="status" data-report-submission-locked="fr">
                <i class="bi bi-lock"></i>
                <span>OSO has locked FR submissions for AY {{ $yearFilter }} · {{ $semesterFilter }}. You can still create, preview and download reports. Wait for OSO to reopen submissions.</span>
            </div>
        @endif

        <section class="fin-card" aria-labelledby="finCashFlowTitle">
            <div class="fin-card-head">
                <div>
                    <h2 id="finCashFlowTitle"><i class="bi bi-cash-stack"></i> Cash Flow</h2>
                    <p class="fin-muted">{{ $fd['period_label'] ?? '' }}</p>
                </div>
                <div class="fin-header-actions">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-fin-open="finOpeningDialog">
                        <i class="bi bi-wallet2"></i> {{ $hasAccount ? 'Edit AY '.$managementYear.' opening' : 'Save AY '.$managementYear.' opening' }}
                    </button>
                    <button type="button" class="org-btn org-btn-primary org-btn-sm" data-fin-open="finIncomeDialog">
                        <i class="bi bi-plus-circle"></i> Record cash inflow
                    </button>
                </div>
            </div>
            <form method="get" action="{{ route('office.financial') }}" class="fin-filters fin-overview-filters" id="finFilterForm">
                <label>
                    <span>Academic year</span>
                    <select name="academic_year" data-fin-autosubmit>
                        <option value="all" @selected($yearFilter === 'all')>All years (combined)</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected($yearFilter === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Semester</span>
                    <select name="semester" data-fin-autosubmit>
                        <option value="all" @selected($semesterFilter === 'all')>All semesters</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester }}" @selected($semesterFilter === $semester)>{{ $semester }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="org-btn org-btn-ghost org-btn-sm" data-fin-filter-apply>Apply</button>
            </form>

            <div class="fin-stats">
                <article class="fin-stat is-beginning">
                    <span class="fin-stat-icon"><i class="bi bi-wallet2"></i></span>
                    <div>
                        <small>Beginning balance</small>
                        <strong>{{ $money($fd['beginning_balance'] ?? 0) }}</strong>
                        <em>{{ match (true) {
                            $yearFilter === 'all' => 'Sum of separate annual openings',
                            $semesterFilter === 'all' => 'Saved annual opening',
                            default => 'Annual opening + earlier semester postings',
                        } }}</em>
                    </div>
                </article>
                <article class="fin-stat is-in">
                    <span class="fin-stat-icon"><i class="bi bi-arrow-down-left"></i></span>
                    <div>
                        <small>Incoming cash</small>
                        <strong>{{ $money($fd['cash_inflow'] ?? 0) }}</strong>
                        <em>Recorded cash inflows</em>
                    </div>
                </article>
                <article class="fin-stat is-out">
                    <span class="fin-stat-icon"><i class="bi bi-arrow-up-right"></i></span>
                    <div>
                        <small>Outgoing cash</small>
                        <strong>{{ $money($fd['cash_outflow'] ?? 0) }}</strong>
                        <em>Posted receipt expenses</em>
                    </div>
                </article>
                <article class="fin-stat is-ending">
                    <span class="fin-stat-icon"><i class="bi bi-piggy-bank"></i></span>
                    <div>
                        <small>Ending balance</small>
                        <strong>{{ $money($fd['ending_balance'] ?? 0) }}</strong>
                        <em>Beginning + incoming − outgoing</em>
                    </div>
                </article>
            </div>
            @if ($yearFilter === 'all')
                <p class="fin-muted fin-stats-note">All years adds up each saved annual account. Each one is a separate yearly allocation, nothing is carried between academic years, and the total is not a live bank balance.</p>
            @endif

            <div class="fin-chart-block">
                <div class="fin-chart-head">
                    <h3>Cash flow by event</h3>
                    @if (count($activities))
                        <div class="fin-legend" aria-hidden="true">
                            <span><i class="is-in"></i> Incoming</span>
                            <span><i class="is-out"></i> Outgoing</span>
                        </div>
                    @endif
                </div>
                @if (count($activities))
                    <div class="fin-chart-canvas" id="finChartWrap">
                        <canvas id="finEventChart" role="img" aria-label="Grouped bar chart of incoming and outgoing cash for {{ count($activities) }} recorded events. Figures are listed in the table that follows."></canvas>
                    </div>
                    <p class="fin-muted fin-chart-fallback-note" id="finChartFallbackNote" hidden>The chart library could not be loaded; event figures are shown as a table.</p>
                    <table class="fin-chart-table fin-sr-only" id="finChartTable">
                        <caption>Incoming and outgoing cash per event · {{ $fd['period_label'] ?? '' }}</caption>
                        <thead>
                            <tr><th scope="col">Event</th><th scope="col">Incoming</th><th scope="col">Outgoing</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($activities as $activity)
                                <tr>
                                    <th scope="row">{{ $activity['name'] }}</th>
                                    <td>{{ $money($activity['inflow']) }}</td>
                                    <td>{{ $money($activity['outflow']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="fin-empty">
                        <i class="bi bi-bar-chart"></i>
                        <strong>No event cash flows recorded</strong>
                        <span>No cash inflows or receipt expenses are recorded for {{ $fd['period_label'] ?? 'this period' }}.</span>
                    </div>
                @endif
            </div>
        </section>

        <section class="fin-card" aria-labelledby="finCurrentTitle">
            <div class="fin-card-head">
                <div>
                    <h2 id="finCurrentTitle"><i class="bi bi-file-earmark-spreadsheet"></i> Current Reports</h2>
                    <p class="fin-muted">Open the latest Financial Report with View before submitting FR to OSO.</p>
                </div>
            </div>

            @if (count($currentReports))
                <div class="fin-table-wrap">
                    <table class="fin-table">
                        <thead>
                            <tr>
                                <th scope="col">Report</th>
                                <th scope="col">Period</th>
                                <th scope="col">Date</th>
                                <th scope="col">State</th>
                                <th scope="col" class="is-num">Balance</th>
                                <th scope="col" class="is-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($currentReports as $report)
                                <tr>
                                    <td data-label="Report">
                                        <strong class="fin-report-title">{{ $report['title'] }}</strong>
                                        <small class="fin-report-file">{{ $report['original_name'] }}</small>
                                    </td>
                                    <td data-label="Period">{{ $report['academic_year'] }} · {{ $report['semester'] }}</td>
                                    <td data-label="Date">
                                        @if ($report['submitted_at'])
                                            <span class="fin-date">Submitted {{ $formatDate($report['submitted_at']) }}</span>
                                        @else
                                            <span class="fin-date is-created">Created {{ $formatDate($report['created_at']) }}</span>
                                        @endif
                                    </td>
                                    <td data-label="State">
                                        <span class="fin-state is-{{ \Illuminate\Support\Str::slug((string) $report['status']) }}">{{ $report['status_label'] }}</span>
                                        @if (!empty($report['notes']))<div class="sr-remarks"><strong>OSO remarks</strong><p>{{ $report['notes'] }}</p></div>@endif
                                        @if (!empty($report['opened_at']))<small class="fin-report-file">Opened by OSO {{ $formatDate($report['opened_at']) }}</small>@endif
                                        @if (!empty($report['reviewed_at']))<small class="fin-report-file">Reviewed {{ $formatDate($report['reviewed_at']) }}@if (!empty($report['reviewed_by'])) · {{ $report['reviewed_by'] }}@endif</small>@endif
                                        @if ($report['status'] === 'rejected')<small class="fin-report-file">Rejected — final decision. FR cannot be edited or resubmitted.</small>@endif
                                    </td>
                                    <td data-label="Balance" class="is-num">
                                        @if ($report['balance'] === null)
                                            <span class="fin-muted">Not recorded</span>
                                        @else
                                            {{ $money($report['balance']) }}
                                        @endif
                                    </td>
                                    <td data-label="Actions" class="is-actions">
                                        <div class="fin-row-actions">
                                            @if ($report['has_file'])
                                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-fin-view="{{ $report['id'] }}"><i class="bi bi-eye"></i> View</button>
                                                <a href="{{ $report['download_url'] }}" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-download"></i> Download</a>
                                            @else
                                                <span class="fin-missing"><i class="bi bi-exclamation-circle"></i> File missing</span>
                                            @endif
                                            @if ($report['can_submit'] && $report['has_file'])
                                                <form method="post" action="{{ $report['submit_url'] }}" class="fin-submit-form" data-fin-submit-form="{{ $report['id'] }}">
                                                    @csrf
                                                    <input type="hidden" name="reportType" value="fr">
                                                    <button type="submit" class="org-btn org-btn-primary org-btn-sm" data-fin-submit="{{ $report['id'] }}" disabled aria-describedby="finSubmitHelp{{ $report['id'] }}">
                                                        <i class="bi bi-send"></i> Submit FR to OSO
                                                    </button>
                                                </form>
                                            @elseif (!empty($report['submission_locked']) && in_array($report['status'], ['draft', 'returned'], true))
                                                <button type="button" class="org-btn org-btn-primary org-btn-sm" disabled data-fin-submission-locked="{{ $report['id'] }}"><i class="bi bi-lock"></i> FR submission locked</button>
                                            @endif
                                        </div>
                                        @if ($report['can_submit'] && $report['has_file'])
                                            <small class="fin-row-help" id="finSubmitHelp{{ $report['id'] }}" data-fin-submit-help="{{ $report['id'] }}">Open View to review this file before submitting.</small>
                                        @endif
                                        @if (!empty($report['submit_help']))
                                            <small class="fin-row-help">{{ $report['submit_help'] }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="fin-empty">
                    <i class="bi bi-file-earmark-plus"></i>
                    <strong>No current financial reports</strong>
                    <span>Use Create Report to upload a completed Financial Report workbook (.xlsx).</span>
                </div>
            @endif
        </section>

        <section class="fin-card" aria-labelledby="finHistoryTitle">
            <div class="fin-card-head">
                <div>
                    <h2 id="finHistoryTitle"><i class="bi bi-clock-history"></i> Report History</h2>
                    <p class="fin-muted">Reports submitted more than a year ago, most recent first.</p>
                </div>
                @if (count($historyReports))
                    <div class="fin-filters fin-history-filters">
                        <label class="fin-search">
                            <span class="fin-sr-only">Search history</span>
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search" id="finHistorySearch" placeholder="Search reports" autocomplete="off">
                        </label>
                        <label>
                            <span class="fin-sr-only">History academic year</span>
                            <select id="finHistoryYear">
                                <option value="">All years</option>
                                @foreach ($historyYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="fin-sr-only">History semester</span>
                            <select id="finHistorySemester">
                                <option value="">All semesters</option>
                                @foreach ($historySemesters as $semester)
                                    <option value="{{ $semester }}">{{ $semester }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                @endif
            </div>

            @if (count($historyReports))
                <div class="fin-table-wrap">
                    <table class="fin-table">
                        <thead>
                            <tr>
                                <th scope="col">Report</th>
                                <th scope="col">Period</th>
                                <th scope="col">Date</th>
                                <th scope="col">State</th>
                                <th scope="col" class="is-num">Balance</th>
                                <th scope="col" class="is-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="finHistoryRows">
                            @foreach ($historyReports as $report)
                                <tr data-fin-history-row
                                    data-search="{{ \Illuminate\Support\Str::lower($report['title'].' '.$report['original_name'].' '.$report['status_label'].' '.$report['academic_year'].' '.$report['semester']) }}"
                                    data-year="{{ $report['academic_year'] }}"
                                    data-semester="{{ $report['semester'] }}">
                                    <td data-label="Report">
                                        <strong class="fin-report-title">{{ $report['title'] }}</strong>
                                        <small class="fin-report-file">{{ $report['original_name'] }}</small>
                                    </td>
                                    <td data-label="Period">{{ $report['academic_year'] }} · {{ $report['semester'] }}</td>
                                    <td data-label="Date">
                                        @if ($report['submitted_at'])
                                            <span class="fin-date">Submitted {{ $formatDate($report['submitted_at']) }}</span>
                                        @else
                                            <span class="fin-date is-created">Created {{ $formatDate($report['created_at']) }}</span>
                                        @endif
                                    </td>
                                    <td data-label="State">
                                        <span class="fin-state is-{{ \Illuminate\Support\Str::slug((string) $report['status']) }}">{{ $report['status_label'] }}</span>
                                        @if (!empty($report['notes']))<div class="sr-remarks"><strong>OSO remarks</strong><p>{{ $report['notes'] }}</p></div>@endif
                                        @if (!empty($report['opened_at']))<small class="fin-report-file">Opened by OSO {{ $formatDate($report['opened_at']) }}</small>@endif
                                        @if (!empty($report['reviewed_at']))<small class="fin-report-file">Reviewed {{ $formatDate($report['reviewed_at']) }}@if (!empty($report['reviewed_by'])) · {{ $report['reviewed_by'] }}@endif</small>@endif
                                        @if ($report['status'] === 'rejected')<small class="fin-report-file">Rejected — final decision. FR cannot be edited or resubmitted.</small>@endif
                                    </td>
                                    <td data-label="Balance" class="is-num">
                                        @if ($report['balance'] === null)
                                            <span class="fin-muted">Not recorded</span>
                                        @else
                                            {{ $money($report['balance']) }}
                                        @endif
                                    </td>
                                    <td data-label="Actions" class="is-actions">
                                        <div class="fin-row-actions">
                                            @if ($report['has_file'])
                                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-fin-view="{{ $report['id'] }}"><i class="bi bi-eye"></i> View</button>
                                                <a href="{{ $report['download_url'] }}" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-download"></i> Download</a>
                                            @else
                                                <span class="fin-missing"><i class="bi bi-exclamation-circle"></i> File missing</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="fin-empty" id="finHistoryNoMatch" hidden>
                    <i class="bi bi-search"></i>
                    <strong>No matching reports</strong>
                    <span>No history report matches the current search and filters.</span>
                </div>
                <p class="fin-sr-only" id="finHistoryCount" aria-live="polite"></p>
            @else
                <div class="fin-empty">
                    <i class="bi bi-archive"></i>
                    <strong>No report history yet</strong>
                    <span>Reports move here one year after they are submitted.</span>
                </div>
            @endif
        </section>
    </div>

    <dialog id="finCreateDialog" class="fin-dialog fin-create-dialog" aria-labelledby="finCreateTitle" @if ($createHasErrors) data-open-on-load @endif>
        <form method="post" action="{{ $fd['create_url'] ?? '' }}" enctype="multipart/form-data" class="fin-create-form" id="finCreateForm" data-org-upload-form>
            @csrf
            <input type="hidden" name="_form" value="financial-create">
            <header class="fin-dialog-head">
                <div class="fin-dialog-heading">
                    <span class="fin-dialog-icon"><i class="bi bi-file-earmark-plus"></i></span>
                    <div>
                        <strong id="finCreateTitle">Create Report</strong>
                        <small>Upload a completed Financial Report workbook</small>
                    </div>
                </div>
                <button type="button" class="fin-icon-btn" data-fin-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </header>

            <div class="fin-dialog-body">
                @if ($createHasErrors)
                    <div class="fin-alert is-error" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <label class="fin-field">
                    <span>Report title</span>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="255" required placeholder="e.g. First Semester Financial Report">
                </label>
                <div class="fin-field-row">
                    <label class="fin-field">
                        <span>Academic year</span>
                        <select name="academic_year" required>
                            @foreach ($academicYearOptions as $option)
                                <option value="{{ $option }}" @selected($createYear === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="fin-field">
                        <span>Semester</span>
                        <select name="semester" required>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester }}" @selected($createSemester === $semester)>{{ $semester }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="fin-field">
                    <label for="finWorkbookInput">Completed workbook (.xlsx)</label>
                    <input type="file" id="finWorkbookInput" name="document" required class="fin-file-input"
                           accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           aria-describedby="finWorkbookStatus finWorkbookError"
                           data-org-upload data-upload-status-id="finWorkbookStatus">
                    <span class="org-upload-status" id="finWorkbookStatus" aria-live="polite">No file selected.</span>
                    <small class="fin-field-error" id="finWorkbookError" role="alert" hidden></small>
                </div>
                <div class="fin-template-card">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <div>
                        <strong>Financial Report template</strong>
                        <small>Fill in all seven forms of the official workbook, then upload it here.</small>
                    </div>
                    <a href="{{ $fd['template_url'] ?? '#' }}" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-download"></i> Template</a>
                </div>
            </div>

            <footer class="fin-dialog-foot">
                <button type="button" class="org-btn org-btn-ghost" data-fin-close>Cancel</button>
                <button type="submit" class="org-btn org-btn-primary"><i class="bi bi-cloud-arrow-up"></i> Create Report</button>
            </footer>
        </form>
    </dialog>

    <dialog id="finOpeningDialog" class="fin-dialog fin-create-dialog" aria-labelledby="finOpeningTitle" @if ($openingHasErrors) data-open-on-load @endif>
        <form method="post" action="{{ $fd['opening_url'] ?? '' }}" class="fin-create-form" id="finOpeningForm" data-fin-cash-form>
            @csrf
            <input type="hidden" name="_form" value="financial-opening">
            <header class="fin-dialog-head">
                <div class="fin-dialog-heading">
                    <span class="fin-dialog-icon"><i class="bi bi-wallet2"></i></span>
                    <div>
                        <strong id="finOpeningTitle">Annual opening cash balance</strong>
                        <small>{{ $fd['organization'] ?? '' }}</small>
                    </div>
                </div>
                <button type="button" class="fin-icon-btn" data-fin-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </header>

            <div class="fin-dialog-body">
                @if ($openingHasErrors)
                    <div class="fin-alert is-error" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="fin-muted fin-dialog-note">Enter the actual cash the organization held on August 1, the start of AY {{ $managementYear }}. This annual opening, plus recorded cash inflows, sets how much activity budget can be approved and spent. It is not carried into other academic years and is not filled in from uploaded workbooks. It cannot be lowered below what is already spent or reserved. To edit another year, choose that year in the Cash Flow filter first.</p>
                <div class="fin-field-row">
                    <div class="fin-field">
                        <span>Academic year</span>
                        <input type="hidden" name="academic_year" value="{{ $openingYear }}">
                        <input type="text" value="AY {{ $openingYear }}" readonly aria-label="Academic year">
                    </div>
                    <label class="fin-field">
                        <span>Opening cash balance (₱)</span>
                        <input type="number" name="opening_balance" value="{{ $openingAmount }}" min="0" step="0.01" inputmode="decimal" required placeholder="0.00">
                    </label>
                </div>
                @if ($hasAccount)
                    <p class="fin-muted">AY {{ $managementYear }} currently has {{ $money($funding['reserved'] ?? 0) }} reserved and {{ $money($funding['spent'] ?? 0) }} spent.</p>
                @endif
            </div>

            <footer class="fin-dialog-foot">
                <button type="button" class="org-btn org-btn-ghost" data-fin-close>Cancel</button>
                <button type="submit" class="org-btn org-btn-primary"><i class="bi bi-check-lg"></i> Save opening balance</button>
            </footer>
        </form>
    </dialog>

    <dialog id="finIncomeDialog" class="fin-dialog fin-create-dialog" aria-labelledby="finIncomeTitle" @if ($incomeHasErrors) data-open-on-load @endif>
        <form method="post" action="{{ $fd['income_url'] ?? '' }}" class="fin-create-form" id="finIncomeForm" data-fin-cash-form>
            @csrf
            <input type="hidden" name="_form" value="financial-income">
            <input type="hidden" name="academic_year" value="{{ $managementYear }}">
            <input type="hidden" name="request_key" value="{{ $incomeRequestKey }}">
            <header class="fin-dialog-head">
                <div class="fin-dialog-heading">
                    <span class="fin-dialog-icon"><i class="bi bi-plus-circle"></i></span>
                    <div>
                        <strong id="finIncomeTitle">Record cash inflow</strong>
                        <small>AY {{ $managementYear }} · {{ $fd['organization'] ?? '' }}</small>
                    </div>
                </div>
                <button type="button" class="fin-icon-btn" data-fin-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </header>

            <div class="fin-dialog-body">
                @if ($incomeHasErrors)
                    <div class="fin-alert is-error" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($hasAccount)
                    <p class="fin-muted fin-dialog-note">Record cash actually received (collections, contributions, sponsorships). Each official receipt or reference number can be recorded once. To record for another academic year, choose that year in the Cash Flow filter first.</p>
                    <div class="fin-field-row">
                        <label class="fin-field">
                            <span>Date received</span>
                            <input type="date" name="transaction_date" value="{{ $incomeHasErrors ? old('transaction_date') : '' }}" min="{{ $fiscalStart }}" max="{{ $incomeMaxDate }}" required>
                        </label>
                        <label class="fin-field">
                            <span>Amount (₱)</span>
                            <input type="number" name="amount" value="{{ $incomeHasErrors ? old('amount') : '' }}" min="0.01" step="0.01" inputmode="decimal" required placeholder="0.00">
                        </label>
                    </div>
                    <label class="fin-field">
                        <span>Purpose</span>
                        <input type="text" name="purpose" value="{{ $incomeHasErrors ? old('purpose') : '' }}" maxlength="255" required placeholder="e.g. Membership fees">
                    </label>
                    <div class="fin-field-row">
                        <label class="fin-field">
                            <span>Received from</span>
                            <input type="text" name="received_from" value="{{ $incomeHasErrors ? old('received_from') : '' }}" maxlength="255" required placeholder="Payer or source">
                        </label>
                        <label class="fin-field">
                            <span>Receipt / reference no.</span>
                            <input type="text" name="reference" value="{{ $incomeHasErrors ? old('reference') : '' }}" maxlength="120" required placeholder="e.g. OR-0001">
                        </label>
                    </div>
                    <label class="fin-field">
                        <span>Activity (optional)</span>
                        <select name="org_activity_id">
                            <option value="">General organization income</option>
                            @foreach ($incomeActivities as $activity)
                                <option value="{{ $activity['id'] }}" @selected($incomeHasErrors && (string) old('org_activity_id') === (string) $activity['id'])>{{ $activity['title'] }}{{ $activity['date'] ? ' · '.$activity['date'] : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                @else
                    <div class="fin-empty">
                        <i class="bi bi-wallet2"></i>
                        <strong>Save the opening balance first</strong>
                        <span>Cash inflows for AY {{ $managementYear }} can be recorded after its annual opening cash balance is saved.</span>
                    </div>
                @endif
            </div>

            <footer class="fin-dialog-foot">
                <button type="button" class="org-btn org-btn-ghost" data-fin-close>Cancel</button>
                @if ($hasAccount)
                    <button type="submit" class="org-btn org-btn-primary"><i class="bi bi-check-lg"></i> Record inflow</button>
                @else
                    <button type="button" class="org-btn org-btn-primary" data-fin-open="finOpeningDialog"><i class="bi bi-wallet2"></i> Save opening balance</button>
                @endif
            </footer>
        </form>
    </dialog>

    <dialog id="finPreviewDialog" class="fin-dialog fin-preview-dialog" aria-labelledby="finPreviewTitle">
        <div class="fin-preview-box">
            <header class="fin-dialog-head">
                <div class="fin-dialog-heading">
                    <span class="fin-dialog-icon"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                    <div>
                        <strong id="finPreviewTitle">Report preview</strong>
                        <small id="finPreviewMeta"></small>
                    </div>
                </div>
                <div class="fin-preview-actions">
                    <a href="#" class="org-btn org-btn-ghost org-btn-sm" id="finPreviewDownload"><i class="bi bi-download"></i> Download original</a>
                    <button type="button" class="fin-icon-btn" data-fin-close aria-label="Close preview"><i class="bi bi-x-lg"></i></button>
                </div>
            </header>
            <div class="fin-preview-body">
                <p class="fin-preview-status" id="finPreviewStatus" role="status"></p>
                <div class="fin-preview-workbook" id="finPreviewWorkbook" hidden>
                    <div class="fin-preview-summary">
                        <div class="is-in"><small>Cash inflow</small><strong id="finPreviewInflow"></strong></div>
                        <div class="is-out"><small>Cash outflow</small><strong id="finPreviewOutflow"></strong></div>
                        <div class="is-balance"><small>Balance</small><strong id="finPreviewBalance"></strong></div>
                    </div>
                    <div class="fin-sheet-tabs" role="tablist" aria-label="Worksheets" id="finPreviewTabs"></div>
                    <p class="fin-preview-limit" id="finPreviewLimit"></p>
                    <div class="fin-sheet-scroll" id="finPreviewPanel" role="tabpanel" tabindex="0"></div>
                </div>
                <div class="fin-preview-file" id="finPreviewFile" hidden></div>
            </div>
        </div>
    </dialog>

    <script type="application/json" id="soFinancialData">@json($clientData)</script>
@endsection

@push('scripts')
    @if (count($activities))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif
    <script src="{{ asset('js/so-financial.js') }}?v={{ filemtime(public_path('js/so-financial.js')) }}"></script>
@endpush
