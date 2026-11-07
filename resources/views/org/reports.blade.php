@extends('org.layout')

@section('title', 'Student Reports')
@section('header')
    <h1><strong>Student Reports</strong></h1>
    <p class="org-welcome">Review Student Voice concerns, read the complete submission, and send an OSO acknowledgment, resolution, or dismissal with remarks.</p>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/office-review-kpis.css') }}?v={{ filemtime(public_path('css/office-review-kpis.css')) }}">
    <style>
        .sr-card { min-width:0; background:#fff; border:1px solid #eadfe2; border-radius:18px; padding:1.25rem; box-shadow:0 4px 16px rgba(90,15,30,.03); }
        .sr-workspace { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(360px,.95fr); gap:1.25rem; align-items:start; }
        .sr-heading { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
        .sr-heading h2 { margin:0; font-size:1.1rem; font-weight:800; color:#1a1618; }
        .sr-count { color:#786c72; font-size:.78rem; font-weight:600; }
        .sr-filters { display:flex; gap:.6rem; flex-wrap:wrap; align-items:end; margin-bottom:1rem; }
        .sr-filters label { display:grid; gap:.3rem; flex:1 1 130px; min-width:0; color:#6d5d64; font-size:.72rem; font-weight:750; }
        .sr-filters label:first-child { flex-basis:260px; }
        .sr-filters input,.sr-filters select { width:100%; min-width:0; min-height:38px; box-sizing:border-box; padding:.5rem .65rem; border:1px solid #e8dedf; border-radius:9px; background:#fff; color:#30272a; font:inherit; font-size:.82rem; }
        
        /* Queue Table & Columnar Card Layout */
        .sr-queue-table-wrap { width:100%; min-width:0; }
        .sr-queue-header { display:grid; grid-template-columns:minmax(130px,1.2fr) minmax(115px,1fr) minmax(170px,2fr) minmax(110px,auto); gap:.75rem 1rem; padding:.65rem 1rem; background:#faf4f6; border:1px solid #ebdbe0; border-radius:11px; margin-bottom:.5rem; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#78666f; }
        .sr-queue { display:grid; gap:.65rem; max-height:38rem; overflow:auto; padding:.2rem; }
        .sr-queue-item { display:grid; grid-template-columns:minmax(130px,1.2fr) minmax(115px,1fr) minmax(170px,2fr) minmax(110px,auto); gap:.75rem 1rem; align-items:center; width:100%; padding:.9rem 1rem; text-align:left; border:1px solid #eee3e6; border-radius:13px; background:#fff; color:#30272a; font:inherit; cursor:pointer; transition:all .15s ease; }
        .sr-queue-item:hover, .sr-queue-item[aria-pressed="true"] { border-color:#ba7d89; background:#fff7f9; box-shadow:0 3px 12px rgba(139,24,40,.06); }
        .sr-queue-item:focus-visible { outline:2px solid #8b1828; outline-offset:3px; }
        
        .sr-col-reporter { min-width:0; }
        .sr-reporter-name { display:block; font-size:.88rem; font-weight:750; color:#1a1618; overflow-wrap:anywhere; }
        .sr-report-badge { display:inline-flex; align-items:center; gap:.25rem; margin-top:.25rem; font-size:.7rem; font-weight:700; color:#8b1828; background:#fdf2f4; border:1px solid #f7d7dc; border-radius:6px; padding:1px 6px; }
        
        .sr-col-date { min-width:0; }
        .sr-date-val { display:block; font-size:.82rem; font-weight:600; color:#33262b; }
        .sr-time-val { display:block; font-size:.72rem; color:#827079; margin-top:.15rem; }
        
        .sr-col-concern { min-width:0; }
        .sr-queue-snippet { margin:0; color:#5c4d54; font-size:.82rem; line-height:1.5; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
        
        .sr-col-status { text-align:right; }
        
        /* Pills */
        .sr-pill { display:inline-flex; align-items:center; justify-content:center; gap:.3rem; padding:.26rem .7rem; border-radius:999px; font-size:.72rem; font-weight:800; border:1px solid #e2d8dc; background:#f8f5f6; color:#66565e; }
        .sr-pill.pending { background:#fffbeb; color:#92400e; border-color:#fde68a; }
        .sr-pill.verified { background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
        .sr-pill.resolved { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
        .sr-pill.dismissed { background:#fef2f2; color:#991b1b; border-color:#fecaca; }

        /* Concern Details Section */
        .sr-detail { position:sticky; top:1rem; }
        .sr-detail-header-top { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.35rem; }
        .sr-detail-kicker { margin:0; color:#8b1828; text-transform:uppercase; letter-spacing:.08em; font-size:.7rem; font-weight:800; display:flex; align-items:center; gap:.35rem; }
        .sr-report-indicator-badge { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:6px; font-size:.72rem; font-weight:800; color:#8b1828; background:#fff2f5; border:1px solid #f6ccd4; }

        /* Reporter Block: Name bigger font, bold, uppercase + College/Program at bottom, then Date */
        .sr-detail-reporter-block { padding:1.15rem; border:1px solid #eddfe3; border-radius:13px; background:#fffafb; margin:1rem 0; }
        .sr-detail-reporter-profile { margin-bottom:.75rem; }
        .sr-detail-reporter-name { margin:0; font-size:1.25rem; font-weight:800; text-transform:uppercase; letter-spacing:.02em; color:#1a1618; line-height:1.3; overflow-wrap:anywhere; }
        .sr-detail-reporter-program { margin:.35rem 0 0; font-size:.88rem; font-weight:600; color:#63535a; line-height:1.45; overflow-wrap:anywhere; }
        .sr-detail-reporter-date { padding-top:.75rem; border-top:1px dashed #ebd9de; display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap; }
        .sr-date-label { font-size:.72rem; font-weight:750; text-transform:uppercase; letter-spacing:.05em; color:#88757e; }
        .sr-date-value { font-size:.85rem; font-weight:700; color:#2b2326; }

        /* Report body container */
        .sr-detail-report-container { border:1px solid #eddfe3; border-radius:13px; background:#fff; overflow:hidden; margin-bottom:1rem; }
        .sr-detail-report-label { padding:.6rem 1rem; background:#faf4f6; border-bottom:1px solid #eddfe3; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#8b1828; display:flex; align-items:center; gap:.4rem; }
        .sr-detail-message { padding:1rem; white-space:pre-wrap; overflow-wrap:anywhere; font-size:.9rem; line-height:1.7; color:#2b2326; }

        .sr-detail-note { margin-top:1rem; padding:.8rem 1rem; border-left:3px solid #b66c7d; background:#fff5f8; font-size:.82rem; line-height:1.6; white-space:pre-wrap; overflow-wrap:anywhere; }
        .sr-review { border-top:1px solid #efe5e8; margin-top:1rem; padding-top:1rem; }
        .sr-review label { display:block; margin-bottom:.4rem; font-size:.78rem; font-weight:750; }
        .sr-review textarea { width:100%; box-sizing:border-box; min-height:110px; resize:vertical; padding:.7rem; border:1px solid #dfccd3; border-radius:10px; font:inherit; font-size:.82rem; }
        .sr-review textarea:focus { outline:2px solid #f3dce3; border-color:#a35b6d; }
        .sr-actions { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:.6rem; margin-top:.8rem; }
        .sr-actions form { margin:0; }
        .sr-reviewed { margin:1rem 0 0; color:#7d6b74; font-size:.8rem; }
        .sr-empty { margin:0; padding:1.5rem .5rem; color:#83717a; font-size:.85rem; line-height:1.6; }
        .sr-workspace [hidden] { display:none !important; }
        
        @media(max-width:1100px) {
            .sr-workspace { grid-template-columns:1fr; }
            .sr-detail { position:static; }
        }
        @media(max-width:768px) {
            .sr-queue-header { display:none; }
            .sr-queue-item { grid-template-columns:1fr; gap:.5rem; }
            .sr-col-status { text-align:left; }
            .sr-card { padding:1rem; }
            .sr-actions form, .sr-actions button { width:100%; }
        }
    </style>

    <div class="office-review-kpis" aria-label="Student feedback totals">
        @foreach ([['total', 'Total reports', 'chat-square-text', 'Matching submitted feedback'], ['pending', 'Pending review', 'hourglass-split', 'Awaiting an OSO decision'], ['verified', 'Acknowledged', 'check2-circle', 'Acknowledged by OSO'], ['resolved', 'Resolved', 'check-all', 'Resolved by OSO'], ['dismissed', 'Dismissed', 'archive', 'Dismissed by OSO']] as [$key, $label, $icon, $hint])
            <article class="office-review-kpi"><span class="office-review-kpi-icon"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span><strong class="office-review-kpi-value">{{ $stats[$key] ?? 0 }}</strong><span class="office-review-kpi-label">{{ $label }}</span><p class="office-review-kpi-hint">{{ $hint }}</p></article>
        @endforeach
    </div>

    <form method="GET" action="{{ route('office.student-reports') }}" class="sr-card sr-filters" aria-label="Filter student concerns">
        <label>Search concerns<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Student or concern details…"></label>
        <label>Status<select name="status"><option value="">All statuses</option>@foreach (['pending', 'verified', 'resolved', 'dismissed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status === 'verified' ? 'Acknowledged' : ucfirst($status) }}</option>@endforeach</select></label>
        <label>College<select name="college"><option value="">All colleges</option>@foreach ($colleges as $college)<option value="{{ $college }}" @selected(($filters['college'] ?? '') === $college)>{{ $college }}</option>@endforeach</select></label>
        <button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
        <a href="{{ route('office.student-reports') }}" class="org-btn org-btn-ghost org-btn-sm">Reset</a>
    </form>

    <div class="sr-workspace">
        <section class="sr-card" aria-labelledby="srQueueTitle">
            <div class="sr-heading">
                <h2 id="srQueueTitle" style="font-weight: 800;"><i class="bi bi-inbox" aria-hidden="true"></i> <strong>Concern queue</strong></h2>
                <span class="sr-count">{{ $reports->total() }} matching</span>
            </div>

            <div class="sr-queue-table-wrap">
                <div class="sr-queue-header" aria-hidden="true">
                    <div class="sr-col-reporter">Reporter (Name)</div>
                    <div class="sr-col-date">Date Submitted</div>
                    <div class="sr-col-concern">Concern / Feedback</div>
                    <div class="sr-col-status">Status</div>
                </div>

                <div class="sr-queue">
                    @forelse ($reports as $report)
                        @php
                            $statusLabel = match ($report->status) {
                                'verified' => 'Acknowledged',
                                'resolved' => 'Resolved',
                                'dismissed' => 'Dismissed',
                                default => 'Pending',
                            };
                            $reporterName = $report->is_anonymous ? 'Anonymous' : ($report->author_name ?: 'Student');
                        @endphp
                        <button type="button" class="sr-queue-item" aria-pressed="false" aria-controls="studentReportDetailPanel" aria-label="View Student Report #{{ $report->id }}"
                            data-report-id="{{ $report->id }}"
                            data-report-name="{{ $reporterName }}"
                            data-report-college="{{ $report->college ?: '—' }}"
                            data-report-program="{{ $report->program ?: '—' }}"
                            data-report-body="{{ $report->body }}"
                            data-report-submitted="{{ $report->created_at?->format('M j, Y · g:i A') ?? 'Not recorded' }}"
                            data-report-status="{{ $report->status }}"
                            data-report-status-label="{{ $statusLabel }}"
                            data-report-note="{{ $report->review_note ?: '' }}"
                            data-report-reviewed="{{ $report->status === 'verified' ? 'Acknowledged'.($report->verified_at ? ' · '.$report->verified_at->format('M j, Y · g:i A') : '') : ($report->status === 'resolved' ? 'Resolved'.($report->verified_at ? ' · '.$report->verified_at->format('M j, Y · g:i A') : '') : ($report->status === 'dismissed' ? 'Dismissed' : 'Awaiting an OSO decision')) }}"
                            data-review-url="{{ route('office.student-reports.review', $report) }}"
                            onclick="openStudentReportDetails(this)">
                            
                            <div class="sr-col-reporter">
                                <strong class="sr-reporter-name">{{ $reporterName }}</strong>
                                <span class="sr-report-badge"><i class="bi bi-file-earmark-text"></i> Report #{{ $report->id }}</span>
                            </div>
                            
                            <div class="sr-col-date">
                                <span class="sr-date-val">{{ $report->created_at?->format('M j, Y') }}</span>
                                <small class="sr-time-val">{{ $report->created_at?->format('g:i A') }}</small>
                            </div>
                            
                            <div class="sr-col-concern">
                                <p class="sr-queue-snippet">{{ \Illuminate\Support\Str::limit($report->body, 120) }}</p>
                            </div>
                            
                            <div class="sr-col-status">
                                <span class="sr-pill {{ $report->status }}">{{ $statusLabel }}</span>
                            </div>
                        </button>
                    @empty
                        <p class="sr-empty">No concerns match these filters. Change the search or reset the filters.</p>
                    @endforelse
                </div>
            </div>
            <div style="margin-top:1rem;">{{ $reports->links('components.pagination') }}</div>
        </section>

        <section class="sr-card sr-detail" id="studentReportDetailPanel" aria-labelledby="studentReportDetailTitle">
            <p class="sr-empty" id="srDetailEmpty">Choose a concern from the queue to read its details.</p>
            <div id="srDetailContent" hidden>
                <div class="sr-detail-header-top">
                    <p class="sr-detail-kicker"><i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i> Student Voice Report</p>
                    <span class="sr-pill" id="srDetailStatus"></span>
                </div>

                <div class="sr-heading" style="margin-bottom:0.85rem;">
                    <h2 id="studentReportDetailTitle">Student Report Details</h2>
                    <span id="srDetailReportBadge" class="sr-report-indicator-badge">Report</span>
                </div>

                {{-- Reporter Name (Capitalized, Bold, Bigger Font) + College/Program at bottom, then Date Submitted --}}
                <div class="sr-detail-reporter-block">
                    <div class="sr-detail-reporter-profile">
                        <h3 id="srDetailReporter" class="sr-detail-reporter-name"></h3>
                        <p id="srDetailCollege" class="sr-detail-reporter-program"></p>
                    </div>
                    <div class="sr-detail-reporter-date">
                        <span class="sr-date-label">Date Submitted</span>
                        <strong id="srDetailSubmitted" class="sr-date-value"></strong>
                    </div>
                </div>

                {{-- Student Report Message Body with Report Indicator --}}
                <div class="sr-detail-report-container">
                    <div class="sr-detail-report-label">
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                        <span>Student Report</span>
                    </div>
                    <div class="sr-detail-message" id="srDetailMessage"></div>
                </div>

                <div class="sr-detail-note" id="srDetailExistingNote" hidden></div>

                {{-- OSO Remarks and Actions with Dismiss, Acknowledge, and Resolved --}}
                <div class="sr-review" id="srDetailPendingActions" hidden>
                    <label for="srDetailReviewNote">OSO remarks <span style="font-weight:400;">(optional)</span></label>
                    <textarea id="srDetailReviewNote" maxlength="2000" placeholder="Add a response or context for your decision…"></textarea>
                    <div class="sr-actions">
                        <form id="srDetailDismissForm" method="POST" onsubmit="syncStudentReportReviewNote(this)" data-confirm-message="Dismiss this student report?">
                            @csrf
                            <input type="hidden" name="decision" value="dismissed">
                            <input type="hidden" name="review_note">
                            <button type="submit" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-x-lg" aria-hidden="true"></i> Dismiss</button>
                        </form>
                        <form id="srDetailAcknowledgeForm" method="POST" onsubmit="syncStudentReportReviewNote(this)" data-confirm-message="Acknowledge receipt of this student report?">
                            @csrf
                            <input type="hidden" name="decision" value="acknowledged">
                            <input type="hidden" name="review_note">
                            <button type="submit" class="org-btn org-btn-outline org-btn-sm" style="color:#065f46; border-color:#a7f3d0;"><i class="bi bi-check-lg" aria-hidden="true"></i> Acknowledge</button>
                        </form>
                        <form id="srDetailResolveForm" method="POST" onsubmit="syncStudentReportReviewNote(this)" data-confirm-message="Mark this student report as resolved?">
                            @csrf
                            <input type="hidden" name="decision" value="resolved">
                            <input type="hidden" name="review_note">
                            <button type="submit" class="org-btn org-btn-primary org-btn-sm" style="background:#1d4ed8; border-color:#1d4ed8;"><i class="bi bi-check-all" aria-hidden="true"></i> Resolved</button>
                        </form>
                    </div>
                </div>
                <p class="sr-reviewed" id="srDetailReviewedText"></p>
            </div>
        </section>
    </div>
    <script>
        function openStudentReportDetails(button, focusDetails = true) {
            const data = button.dataset;
            document.querySelectorAll('.sr-queue-item').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
            document.getElementById('srDetailEmpty').hidden = true;
            document.getElementById('srDetailContent').hidden = false;
            
            // Name: Capitalized & bolded via CSS, content injected
            document.getElementById('srDetailReporter').textContent = data.reportName;
            document.getElementById('srDetailCollege').textContent = data.reportCollege + (data.reportProgram && data.reportProgram !== '—' ? ' · ' + data.reportProgram : '');
            document.getElementById('srDetailSubmitted').textContent = data.reportSubmitted;
            document.getElementById('srDetailMessage').textContent = data.reportBody;
            
            if (document.getElementById('srDetailReportBadge')) {
                document.getElementById('srDetailReportBadge').textContent = 'Report #' + (data.reportId || '');
            }

            const status = document.getElementById('srDetailStatus');
            status.className = 'sr-pill ' + data.reportStatus;
            status.textContent = data.reportStatusLabel || (data.reportStatus === 'verified'
                ? 'Acknowledged'
                : (data.reportStatus === 'resolved'
                    ? 'Resolved'
                    : (data.reportStatus === 'dismissed' ? 'Dismissed' : 'Pending')));

            const note = document.getElementById('srDetailExistingNote');
            note.hidden = !data.reportNote;
            note.textContent = data.reportNote ? 'OSO remarks\n' + data.reportNote : '';

            // Allow review actions if pending or acknowledged (can resolve)
            document.getElementById('srDetailPendingActions').hidden = data.reportStatus === 'dismissed' || data.reportStatus === 'resolved';
            document.getElementById('srDetailReviewedText').textContent = data.reportReviewed;
            document.getElementById('srDetailReviewNote').value = '';

            document.getElementById('srDetailAcknowledgeForm').action = data.reviewUrl;
            document.getElementById('srDetailDismissForm').action = data.reviewUrl;
            document.getElementById('srDetailResolveForm').action = data.reviewUrl;

            // If already acknowledged, hide Acknowledge button and keep Resolved & Dismiss
            const ackForm = document.getElementById('srDetailAcknowledgeForm');
            if (ackForm) {
                ackForm.style.display = data.reportStatus === 'verified' ? 'none' : '';
            }

            if (focusDetails && window.matchMedia('(max-width:1100px)').matches) {
                document.getElementById('studentReportDetailPanel').scrollIntoView({ block: 'start' });
            }
        }
        function syncStudentReportReviewNote(form) {
            form.querySelector('input[name="review_note"]').value = document.getElementById('srDetailReviewNote').value;
        }
        const firstConcern = document.querySelector('.sr-queue-item');
        if (firstConcern) openStudentReportDetails(firstConcern, false);
    </script>
@endsection
