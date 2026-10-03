@extends('org.layout')

@section('title', 'Student Reports')

@section('header')
    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
        <h1><strong>Student Reports</strong></h1>
        <span style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.25rem 0.65rem;border-radius:9999px;font-size:0.72rem;font-weight:800;background:#fefce8;color:#b45309;border:1px solid #fde68a;">
            <i class="bi bi-inbox-fill"></i> {{ $stats['pending'] ?? 0 }} PENDING REVIEW
        </span>
    </div>
    <p class="org-welcome">
        Student Voice submissions from the student portal. Verify legitimate reports or dismiss noise — students see the status of their own submissions.
    </p>
@endsection

@section('actions')
    <span style="font-size:0.8rem;font-weight:700;color:#7a7074;">
        {{ $stats['total'] ?? 0 }} total · {{ $stats['verified'] ?? 0 }} verified · {{ $stats['dismissed'] ?? 0 }} dismissed
    </span>
@endsection

@section('content')
    <style>
        .sr-card { background:#fff; border:1.5px solid #f0e6e8; border-radius:20px; padding:1.35rem 1.5rem; box-shadow:0 4px 16px rgba(90,15,30,.03); margin-bottom:1.1rem; }
        .sr-card h3 { margin:0 0 0.35rem; font-size:1.02rem; font-weight:800; color:#1a1618; display:flex; align-items:center; gap:0.45rem; }
        .sr-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:0.75rem; margin-bottom:1.1rem; }
        .sr-stat { background:#fff; border:1.5px solid #f0e6e8; border-radius:16px; padding:0.9rem 1.1rem; }
        .sr-stat strong { display:block; font-size:1.5rem; color:#1a1618; }
        .sr-stat span { font-size:0.75rem; font-weight:700; color:#7a7074; text-transform:uppercase; letter-spacing:0.04em; }
        .sr-pill { display:inline-flex; align-items:center; gap:0.3rem; padding:0.2rem 0.6rem; border-radius:9999px; font-size:0.7rem; font-weight:800; text-transform:uppercase; }
        .sr-pill.pending { background:#fefce8; color:#b45309; border:1px solid #fde68a; }
        .sr-pill.verified { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .sr-pill.dismissed { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
        .sr-filters { display:flex; flex-wrap:wrap; gap:0.6rem; align-items:end; }
        .sr-filters label { display:grid; gap:0.25rem; font-size:0.75rem; font-weight:700; color:#554d50; }
        .sr-filters select { padding:0.5rem 0.7rem; border-radius:10px; border:1px solid #e8dedf; font-size:0.82rem; }
        .sr-details-btn { white-space:nowrap; }
        .sr-table-wrap { width:100%; overflow-x:auto; }
        .sr-table { width:100%; min-width:760px; table-layout:fixed; border-collapse:separate; border-spacing:0; font-size:0.86rem; text-align:left; }
        .sr-table th { padding:0.72rem 0.8rem; background:#faf6f7; border-bottom:1px solid #f2e9eb; color:#7a7074; font-size:0.74rem; font-weight:800; letter-spacing:0.02em; }
        .sr-table td { padding:0.78rem 0.8rem; border-bottom:1px solid #f6eff0; vertical-align:middle; color:#1a1618; overflow-wrap:anywhere; word-break:break-word; line-height:1.35; }
        .sr-table th:nth-child(1), .sr-table td:nth-child(1) { width:25%; }
        .sr-table th:nth-child(2), .sr-table td:nth-child(2) { width:31%; }
        .sr-table th:nth-child(3), .sr-table td:nth-child(3) { width:15%; }
        .sr-table th:nth-child(4), .sr-table td:nth-child(4) { width:11%; }
        .sr-table th:nth-child(5), .sr-table td:nth-child(5) { width:18%; }
        .sr-table td:nth-child(3), .sr-table td:nth-child(4), .sr-table td:nth-child(5) { white-space:nowrap; }
        .sr-table td:nth-child(5) { text-align:right; }
        .sr-table td strong { display:block; line-height:1.25; }
        .sr-table td small { display:block; line-height:1.35; overflow-wrap:anywhere; word-break:break-word; }
        .sr-report-cell { color:#30282b; }
        .sr-detail-dialog { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; width:100%; height:100%; max-width:none; max-height:none; box-sizing:border-box; margin:0; padding:1rem; border:0; background:transparent; overflow:auto; }
        .sr-detail-dialog:not([open]) { display:none; }
        .sr-detail-dialog::backdrop { background:rgba(26,10,13,.52); backdrop-filter:blur(5px); }
        .sr-detail-card { width:min(680px, 100%); max-height:calc(100vh - 2rem); overflow:auto; box-sizing:border-box; padding:1.45rem 1.55rem 1.35rem; background:#fff; border:1px solid #f0e6e8; border-radius:22px; box-shadow:0 24px 80px rgba(26,10,13,.25); }
        .sr-detail-head { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; padding-bottom:1rem; border-bottom:1px solid #f3eaec; }
        .sr-detail-kicker { margin:0 0 0.35rem; font-size:0.68rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:#8b1828; }
        .sr-detail-title { margin:0; font-size:1.25rem; line-height:1.2; color:#1a1618; }
        .sr-detail-close { width:34px; height:34px; display:inline-grid; place-items:center; flex:0 0 auto; border:1px solid #eadcdf; border-radius:50%; background:#fff; color:#7a7074; cursor:pointer; }
        .sr-detail-close:hover { color:#8b1828; border-color:#d8b9bf; background:#fff8f9; }
        .sr-detail-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.7rem; margin:1rem 0; }
        .sr-detail-meta-item { padding:.7rem .8rem; border:1px solid #f0e6e8; border-radius:12px; background:#fffafb; }
        .sr-detail-meta-item span { display:block; margin-bottom:.18rem; font-size:.68rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#8b7b80; }
        .sr-detail-meta-item strong { display:block; font-size:.82rem; color:#30282b; overflow-wrap:anywhere; }
        .sr-detail-message { padding:1rem; border:1px solid #e9dfe1; border-radius:14px; background:#fcf8f9; color:#30282b; font-size:.92rem; line-height:1.65; white-space:pre-wrap; overflow-wrap:anywhere; }
        .sr-detail-note { margin-top:.8rem; padding:.75rem .9rem; border-left:3px solid #c98c98; border-radius:0 10px 10px 0; background:#fff7f8; color:#665c60; font-size:.8rem; line-height:1.5; }
        .sr-detail-review { margin-top:1rem; padding-top:1rem; border-top:1px solid #f3eaec; }
        .sr-detail-review label { display:block; margin-bottom:.35rem; font-size:.76rem; font-weight:800; color:#554d50; }
        .sr-detail-review textarea { width:100%; min-height:74px; resize:vertical; padding:.65rem .75rem; border:1px solid #e8dedf; border-radius:11px; font:inherit; font-size:.82rem; color:#30282b; box-sizing:border-box; }
        .sr-detail-review textarea:focus { outline:2px solid rgba(139,24,40,.14); border-color:#b86370; }
        .sr-detail-actions { display:flex; justify-content:flex-end; gap:.6rem; margin-top:.85rem; }
        .sr-detail-actions form { margin:0; }
        .sr-detail-actions .sr-dismiss-btn { background:#fff2f2; color:#b91c1c; border:1px solid #fecaca; }
        .sr-detail-reviewed { margin-top:1rem; padding:.8rem .9rem; border-radius:12px; background:#f8f5f6; color:#665c60; font-size:.82rem; }
        @media (max-width:640px) {
            .sr-card { padding:1rem; }
            .sr-detail-card { padding:1.15rem; }
            .sr-detail-meta { grid-template-columns:1fr; }
            .sr-detail-actions { flex-direction:column-reverse; }
            .sr-detail-actions form, .sr-detail-actions button { width:100%; }
        }
        @media (max-width:640px) { .sr-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    </style>

    <div class="sr-stats">
        <div class="sr-stat"><strong>{{ $stats['total'] ?? 0 }}</strong><span>Total reports</span></div>
        <div class="sr-stat"><strong>{{ $stats['pending'] ?? 0 }}</strong><span>Pending review</span></div>
        <div class="sr-stat"><strong>{{ $stats['verified'] ?? 0 }}</strong><span>Verified</span></div>
        <div class="sr-stat"><strong>{{ $stats['dismissed'] ?? 0 }}</strong><span>Dismissed</span></div>
    </div>

    <section class="sr-card">
        <h3><i class="bi bi-funnel-fill"></i> Filter queue</h3>
        <form method="GET" action="{{ route('office.student-reports') }}" class="sr-filters">
            <label>
                Status
                <select name="status">
                    <option value="">All</option>
                    @foreach (['pending', 'verified', 'dismissed'] as $opt)
                        <option value="{{ $opt }}" @selected(($filters['status'] ?? '') === $opt)>{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                College
                <select name="college">
                    <option value="">All</option>
                    @foreach (($colleges ?? []) as $college)
                        <option value="{{ $college }}" @selected(($filters['college'] ?? '') === $college)>{{ $college }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Topic
                <select name="topic">
                    <option value="">All</option>
                    @foreach (($topics ?? []) as $topic)
                        <option value="{{ $topic }}" @selected(($filters['topic'] ?? '') === $topic)>{{ ucfirst($topic) }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-search"></i> Apply</button>
            <a href="{{ route('office.student-reports') }}" class="org-btn org-btn-ghost org-btn-sm">Reset</a>
        </form>
    </section>

    <section class="sr-card">
        <h3><i class="bi bi-chat-square-text-fill"></i> Verification queue</h3>
        <div class="sr-table-wrap">
            <table class="sr-table">
                <thead>
                    <tr>
                        <th>Reporter</th>
                        <th>Report</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>
                                <strong>{{ $report->is_anonymous ? 'Anonymous' : ($report->author_name ?: 'Student') }}</strong><br>
                                <small style="color:#7a7074;">{{ $report->college ?: '—' }} · {{ ucfirst($report->topic) }}</small>
                            </td>
                            <td class="sr-report-cell">
                                <div>{{ \Illuminate\Support\Str::limit($report->body, 180) }}</div>
                                @if ($report->review_note)
                                    <small style="display:block;margin-top:0.25rem;color:#7a7074;">
                                        <i class="bi bi-chat-left-text"></i> OSO note: {{ $report->review_note }}
                                    </small>
                                @endif
                            </td>
                            <td style="white-space:nowrap;font-size:0.8rem;">{{ optional($report->created_at)->format('M j, Y g:i A') }}</td>
                            <td><span class="sr-pill {{ $report->status }}">{{ strtoupper($report->status) }}</span></td>
                            <td>
                                <button
                                    type="button"
                                    class="org-btn org-btn-ghost org-btn-sm sr-details-btn"
                                    data-report-name="{{ $report->is_anonymous ? 'Anonymous' : ($report->author_name ?: 'Student') }}"
                                    data-report-college="{{ $report->college ?: '—' }}"
                                    data-report-program="{{ $report->program ?: '—' }}"
                                    data-report-topic="{{ ucfirst($report->topic ?: 'General') }}"
                                    data-report-body="{{ $report->body }}"
                                    data-report-submitted="{{ optional($report->created_at)->format('M j, Y g:i A') }}"
                                    data-report-status="{{ $report->status }}"
                                    data-report-note="{{ $report->review_note ?: '' }}"
                                    data-report-reviewed="{{ $report->verified_at ? 'Reviewed '.$report->verified_at->format('M j, Y g:i A') : 'Not reviewed yet' }}"
                                    data-review-url="{{ route('office.student-reports.review', $report) }}"
                                    onclick="openStudentReportDetails(this)">
                                    <i class="bi bi-eye"></i> View details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:2rem 1rem;color:#786f73;">
                                <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:0.5rem;"></i>
                                <strong>No student reports found.</strong>
                                <p style="margin:0.25rem 0 0;font-size:0.8rem;">Try clearing the filters — or wait for students to submit via Student Voice.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($reports, 'links'))
            <div style="margin-top:1rem;">{{ $reports->links() }}</div>
        @endif
    </section>

    <dialog class="sr-detail-dialog" id="studentReportDetailModal" aria-labelledby="studentReportDetailTitle">
        <article class="sr-detail-card">
            <div class="sr-detail-head">
                <div>
                    <p class="sr-detail-kicker">Student Voice report</p>
                    <h2 class="sr-detail-title" id="studentReportDetailTitle">Report details</h2>
                </div>
                <button type="button" class="sr-detail-close" onclick="closeStudentReportDetails()" aria-label="Close report details">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="sr-detail-meta">
                <div class="sr-detail-meta-item"><span>Submitted by</span><strong id="srDetailReporter">—</strong></div>
                <div class="sr-detail-meta-item"><span>Submitted</span><strong id="srDetailSubmitted">—</strong></div>
                <div class="sr-detail-meta-item"><span>College / program</span><strong id="srDetailCollege">—</strong></div>
                <div class="sr-detail-meta-item"><span>Topic</span><strong id="srDetailTopic">—</strong></div>
            </div>

            <div class="sr-detail-message" id="srDetailMessage"></div>
            <div class="sr-detail-note" id="srDetailExistingNote" hidden></div>

            <div class="sr-detail-review" id="srDetailPendingActions" hidden>
                <label for="srDetailReviewNote">Review note <span style="font-weight:600;color:#8b7b80;">(optional for dismissal)</span></label>
                <textarea id="srDetailReviewNote" placeholder="Add context for dismissing this report…"></textarea>
                <div class="sr-detail-actions">
                    <form id="srDetailDismissForm" method="POST" onsubmit="syncStudentReportReviewNote(this);">
                        @csrf
                        <input type="hidden" name="decision" value="dismissed">
                        <input type="hidden" name="review_note" value="">
                        <button type="submit" class="org-btn org-btn-sm sr-dismiss-btn"><i class="bi bi-x-lg"></i> Dismiss report</button>
                    </form>
                    <form id="srDetailVerifyForm" method="POST">
                        @csrf
                        <input type="hidden" name="decision" value="verified">
                        <button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-check-lg"></i> Verify report</button>
                    </form>
                </div>
            </div>

            <div class="sr-detail-reviewed" id="srDetailReviewedState" hidden>
                <i class="bi bi-clock-history"></i> <span id="srDetailReviewedText"></span>
            </div>
        </article>
    </dialog>

    <script>
        function openStudentReportDetails(button) {
            const modal = document.getElementById('studentReportDetailModal');
            if (!modal || !button) return;

            const data = button.dataset;
            document.getElementById('srDetailReporter').textContent = data.reportName || 'Student';
            document.getElementById('srDetailSubmitted').textContent = data.reportSubmitted || '—';
            document.getElementById('srDetailCollege').textContent = (data.reportCollege || '—') + ' · ' + (data.reportProgram || '—');
            document.getElementById('srDetailTopic').textContent = data.reportTopic || 'General';
            document.getElementById('srDetailMessage').textContent = data.reportBody || 'No report text provided.';

            const note = document.getElementById('srDetailExistingNote');
            note.hidden = !data.reportNote;
            note.textContent = data.reportNote ? 'OSO note: ' + data.reportNote : '';

            const pending = data.reportStatus === 'pending';
            document.getElementById('srDetailPendingActions').hidden = !pending;
            document.getElementById('srDetailReviewedState').hidden = pending;
            document.getElementById('srDetailReviewedText').textContent = data.reportReviewed || 'Reviewed';
            document.getElementById('srDetailReviewNote').value = '';

            document.getElementById('srDetailVerifyForm').action = data.reviewUrl || '';
            document.getElementById('srDetailDismissForm').action = data.reviewUrl || '';

            if (!modal.open) modal.showModal();
        }

        function closeStudentReportDetails() {
            const modal = document.getElementById('studentReportDetailModal');
            if (modal?.open) modal.close();
        }

        function syncStudentReportReviewNote(form) {
            const note = document.getElementById('srDetailReviewNote');
            const target = form?.querySelector('input[name="review_note"]');
            if (target && note) target.value = note.value;
            return true;
        }

        document.getElementById('studentReportDetailModal')?.addEventListener('click', (event) => {
            if (event.target === event.currentTarget) closeStudentReportDetails();
        });
    </script>
@endsection
