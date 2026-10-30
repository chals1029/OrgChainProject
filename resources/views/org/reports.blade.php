@extends('org.layout')

@section('title', 'Student Reports')
@section('header')
    <h1><strong>Student Reports</strong></h1>
    <p class="org-welcome">Review Student Voice concerns, read the complete submission, and send an OSO acknowledgment or dismissal with remarks.</p>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/office-review-kpis.css') }}?v={{ filemtime(public_path('css/office-review-kpis.css')) }}">
    <style>
        .sr-card { min-width:0; background:#fff; border:1px solid #eadfe2; border-radius:18px; padding:1.25rem; box-shadow:0 4px 16px rgba(90,15,30,.03); }
        .sr-workspace { display:grid; grid-template-columns:minmax(0,1fr) minmax(340px,.85fr); gap:1rem; align-items:start; }
        .sr-heading { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
        .sr-heading h2 { margin:0; font-size:1.05rem; }
        .sr-count { color:#786c72; font-size:.78rem; }
        .sr-filters { display:flex; gap:.6rem; flex-wrap:wrap; align-items:end; margin-bottom:1rem; }
        .sr-filters label { display:grid; gap:.3rem; flex:1 1 130px; min-width:0; color:#6d5d64; font-size:.72rem; font-weight:750; }
        .sr-filters label:first-child { flex-basis:260px; }
        .sr-filters input,.sr-filters select { width:100%; min-width:0; min-height:38px; box-sizing:border-box; padding:.5rem .65rem; border:1px solid #e8dedf; border-radius:9px; background:#fff; color:#30272a; font:inherit; font-size:.82rem; }
        .sr-queue { display:grid; gap:.65rem; max-height:38rem; overflow:auto; padding:.2rem; }
        .sr-queue-item { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.45rem .75rem; width:100%; padding:1rem; text-align:left; border:1px solid #eee3e6; border-radius:13px; background:#fff; color:#30272a; font:inherit; cursor:pointer; }
        .sr-queue-item:hover,.sr-queue-item[aria-pressed="true"] { border-color:#ba7d89; background:#fff7f9; }
        .sr-queue-item:focus-visible { outline:2px solid #8b1828; outline-offset:3px; }
        .sr-queue-item strong { display:block; font-size:.86rem; overflow-wrap:anywhere; }
        .sr-queue-item small { display:block; margin-top:.25rem; color:#827079; font-size:.71rem; }
        .sr-queue-snippet { grid-column:1/-1; margin:0; color:#66585f; font-size:.8rem; line-height:1.5; overflow-wrap:anywhere; }
        .sr-queue-tail { display:flex; justify-content:space-between; align-items:center; gap:.6rem; grid-column:1/-1; }
        .sr-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.22rem .6rem; border-radius:999px; font-size:.69rem; font-weight:800; border:1px solid #e2d8dc; background:#f8f5f6; color:#66565e; }
        .sr-pill.pending { background:#fffbeb; color:#92400e; border-color:#fde68a; }
        .sr-pill.verified { background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
        .sr-pill.dismissed { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
        .sr-detail { position:sticky; top:1rem; }
        .sr-detail-kicker { margin:0 0 .35rem; color:#8b1828; text-transform:uppercase; letter-spacing:.08em; font-size:.68rem; font-weight:800; }
        .sr-detail h2 { margin:0; font-size:1.2rem; overflow-wrap:anywhere; }
        .sr-detail-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.7rem; margin:1rem 0; }
        .sr-detail-meta div { padding:.7rem; border:1px solid #f0e6e8; border-radius:10px; background:#fffafb; }
        .sr-detail-meta span { display:block; color:#88757e; font-size:.67rem; font-weight:750; text-transform:uppercase; margin-bottom:.25rem; }
        .sr-detail-meta strong { display:block; font-size:.8rem; overflow-wrap:anywhere; }
        .sr-detail-message { padding:1rem; border:1px solid #ede1e5; border-radius:12px; background:#fcf9fa; white-space:pre-wrap; overflow-wrap:anywhere; font-size:.9rem; line-height:1.7; }
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
        @media(max-width:1100px) { .sr-workspace { grid-template-columns:1fr; } .sr-detail { position:static; } }
        @media(max-width:520px) { .sr-card { padding:1rem; } .sr-detail-meta { grid-template-columns:1fr; } .sr-actions form,.sr-actions button { width:100%; } }
    </style>

    <div class="office-review-kpis" aria-label="Student feedback totals">
        @foreach ([['total', 'Total reports', 'chat-square-text', 'Matching submitted feedback'], ['pending', 'Pending review', 'hourglass-split', 'Awaiting an OSO decision'], ['verified', 'Acknowledged', 'check2-circle', 'Acknowledged by OSO'], ['dismissed', 'Dismissed', 'archive', 'Dismissed by OSO']] as [$key, $label, $icon, $hint])
            <article class="office-review-kpi"><span class="office-review-kpi-icon"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span><strong class="office-review-kpi-value">{{ $stats[$key] ?? 0 }}</strong><span class="office-review-kpi-label">{{ $label }}</span><p class="office-review-kpi-hint">{{ $hint }}</p></article>
        @endforeach
    </div>

    <form method="GET" action="{{ route('office.student-reports') }}" class="sr-card sr-filters" aria-label="Filter student concerns">
        <label>Search concerns<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Student, concern or topic…"></label>
        <label>Status<select name="status"><option value="">All statuses</option>@foreach (['pending', 'verified', 'dismissed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status === 'verified' ? 'Acknowledged' : ucfirst($status) }}</option>@endforeach</select></label>
        <label>College<select name="college"><option value="">All colleges</option>@foreach ($colleges as $college)<option value="{{ $college }}" @selected(($filters['college'] ?? '') === $college)>{{ $college }}</option>@endforeach</select></label>
        <label>Topic<select name="topic"><option value="">All topics</option>@foreach ($topics as $topic)<option value="{{ $topic }}" @selected(($filters['topic'] ?? '') === $topic)>{{ ucfirst($topic) }}</option>@endforeach</select></label>
        <button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
        <a href="{{ route('office.student-reports') }}" class="org-btn org-btn-ghost org-btn-sm">Reset</a>
    </form>

    <div class="sr-workspace">
        <section class="sr-card" aria-labelledby="srQueueTitle">
            <div class="sr-heading"><h2 id="srQueueTitle"><i class="bi bi-inbox" aria-hidden="true"></i> Concern queue</h2><span class="sr-count">{{ $reports->total() }} matching</span></div>
            <div class="sr-queue">
                @forelse ($reports as $report)
                    <button type="button" class="sr-queue-item" aria-pressed="false" aria-controls="studentReportDetailPanel" aria-label="View concern {{ $report->id }}"
                        data-report-name="{{ $report->is_anonymous ? 'Anonymous' : ($report->author_name ?: 'Student') }}"
                        data-report-college="{{ $report->college ?: '—' }}" data-report-program="{{ $report->program ?: '—' }}"
                        data-report-topic="{{ ucfirst($report->topic ?: 'General') }}" data-report-body="{{ $report->body }}"
                        data-report-submitted="{{ $report->created_at?->format('M j, Y · g:i A') ?? 'Not recorded' }}"
                        data-report-status="{{ $report->status }}" data-report-note="{{ $report->review_note ?: '' }}"
                        data-report-reviewed="{{ $report->status === 'verified' ? 'Acknowledged'.($report->verified_at ? ' · '.$report->verified_at->format('M j, Y · g:i A') : ' · Review time not recorded') : ($report->status === 'dismissed' ? 'Dismissed' : 'Awaiting an OSO decision') }}"
                        data-review-url="{{ route('office.student-reports.review', $report) }}" onclick="openStudentReportDetails(this)">
                        <div><strong>{{ $report->is_anonymous ? 'Anonymous' : ($report->author_name ?: 'Student') }}</strong><small>Report #{{ $report->id }} · {{ $report->college ?: 'Unassigned college' }} · {{ ucfirst($report->topic) }}</small></div>
                        <span class="sr-pill {{ $report->status }}">{{ $report->status === 'verified' ? 'Acknowledged' : ucfirst($report->status) }}</span>
                        <p class="sr-queue-snippet">{{ \Illuminate\Support\Str::limit($report->body, 130) }}</p>
                        <div class="sr-queue-tail"><small>{{ $report->created_at?->format('M j, Y · g:i A') }}</small><span class="app-view-action"><i class="bi bi-eye" aria-hidden="true"></i> View</span></div>
                    </button>
                @empty
                    <p class="sr-empty">No concerns match these filters. Change the search or reset the filters.</p>
                @endforelse
            </div>
            <div style="margin-top:1rem;">{{ $reports->links('components.pagination') }}</div>
        </section>

        <section class="sr-card sr-detail" id="studentReportDetailPanel" aria-labelledby="studentReportDetailTitle">
            <p class="sr-empty" id="srDetailEmpty">Choose a concern from the queue to read its details.</p>
            <div id="srDetailContent" hidden>
                <p class="sr-detail-kicker">Student Voice concern</p>
                <div class="sr-heading"><h2 id="studentReportDetailTitle">Concern details</h2><span class="sr-pill" id="srDetailStatus"></span></div>
                <div class="sr-detail-meta">
                    <div><span>Submitted by</span><strong id="srDetailReporter"></strong></div>
                    <div><span>Submitted</span><strong id="srDetailSubmitted"></strong></div>
                    <div><span>College / program</span><strong id="srDetailCollege"></strong></div>
                    <div><span>Topic</span><strong id="srDetailTopic"></strong></div>
                </div>
                <div class="sr-detail-message" id="srDetailMessage"></div>
                <div class="sr-detail-note" id="srDetailExistingNote" hidden></div>
                <div class="sr-review" id="srDetailPendingActions" hidden>
                    <label for="srDetailReviewNote">OSO remarks <span style="font-weight:400;">(optional)</span></label>
                    <textarea id="srDetailReviewNote" maxlength="2000" placeholder="Add a response or context for your decision…"></textarea>
                    <div class="sr-actions">
                        <form id="srDetailDismissForm" method="POST" onsubmit="syncStudentReportReviewNote(this)" data-confirm-message="Dismiss this student concern?">@csrf<input type="hidden" name="decision" value="dismissed"><input type="hidden" name="review_note"><button type="submit" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-x-lg" aria-hidden="true"></i> Dismiss</button></form>
                        <form id="srDetailAcknowledgeForm" method="POST" onsubmit="syncStudentReportReviewNote(this)" data-confirm-message="Acknowledge receipt of this student concern?">@csrf<input type="hidden" name="decision" value="acknowledged"><input type="hidden" name="review_note"><button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-check-lg" aria-hidden="true"></i> Acknowledge</button></form>
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
            document.getElementById('srDetailReporter').textContent = data.reportName;
            document.getElementById('srDetailSubmitted').textContent = data.reportSubmitted;
            document.getElementById('srDetailCollege').textContent = data.reportCollege + ' · ' + data.reportProgram;
            document.getElementById('srDetailTopic').textContent = data.reportTopic;
            document.getElementById('srDetailMessage').textContent = data.reportBody;
            const status = document.getElementById('srDetailStatus');
            status.className = 'sr-pill ' + data.reportStatus;
            status.textContent = data.reportStatus === 'verified' ? 'Acknowledged' : data.reportStatus === 'dismissed' ? 'Dismissed' : 'Pending review';
            const note = document.getElementById('srDetailExistingNote');
            note.hidden = !data.reportNote;
            note.textContent = data.reportNote ? 'OSO remarks\n' + data.reportNote : '';
            document.getElementById('srDetailPendingActions').hidden = data.reportStatus !== 'pending';
            document.getElementById('srDetailReviewedText').textContent = data.reportReviewed;
            document.getElementById('srDetailReviewNote').value = '';
            document.getElementById('srDetailAcknowledgeForm').action = data.reviewUrl;
            document.getElementById('srDetailDismissForm').action = data.reviewUrl;
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
