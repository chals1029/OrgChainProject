<section class="sp-section sv-section" aria-labelledby="studentVoiceHeading">
    <style>
        .sv-section { min-width:0; }
        .sv-heading h2 { margin:0; font-size:1.15rem; }
        .sv-heading p { color:#7d6b74; font-size:.84rem; margin:.35rem 0 1rem; }

        .sv-layout { display:grid; grid-template-columns:1fr; gap:1.25rem; width:100%; transition:grid-template-columns .3s ease; }
        .sv-layout.has-details { grid-template-columns:minmax(0, 1fr) minmax(320px, 32%); align-items:start; }
        .sv-col-left { display:flex; flex-direction:column; gap:1.25rem; min-width:0; width:100%; }
        .sv-col-right { display:none; }
        .sv-layout.has-details .sv-col-right { display:flex; flex-direction:column; min-width:0; position:sticky; top:1rem; }
        #mySubmissionsCard {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .sv-card {
            border: 1px solid #eadde2;
            border-radius: 18px;
            background: #fff;
            padding: 1.25rem;
            min-width: 0;
            box-shadow: 0 5px 18px rgba(95,25,45,.03);
            display: flex;
            flex-direction: column;
            transition: box-shadow .3s ease;
        }
        .sv-card h3 { margin:0 0 .75rem; font-size:.96rem; font-weight:800; color:#1a1618; }
        .sv-form { display:grid; gap:.85rem; flex:1; }
        .sv-form label { display:grid; gap:.35rem; color:#6c5762; font-size:.78rem; font-weight:750; }
        .sv-form select,.sv-form textarea { width:100%; box-sizing:border-box; border:1px solid #e3d2d9; background:#fff; border-radius:10px; padding:.65rem .75rem; color:#3b2b34; font:inherit; font-size:.84rem; }
        .sv-form textarea { resize:vertical; min-height:100px; line-height:1.6; }
        .sv-form :focus-visible { outline:2px solid #8b1828; outline-offset:3px; }
        .sv-form small { font-weight:400; color:#8b7881; }
        .sv-submit { justify-self:start; padding:.65rem 1.15rem; border:0; border-radius:10px; background:#8b1828; color:#fff; font:inherit; font-size:.82rem; font-weight:750; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; }
        .sv-submit:hover { background:#731421; }

        .sv-details-card { position:relative; box-sizing:border-box; background:#fff; border:1px solid #eadde2; border-radius:18px; box-shadow:0 4px 20px rgba(95,25,45,.04); padding:1.25rem; }
        .sv-details-header { display:flex; align-items:flex-start; gap:.7rem; padding-right:2rem; }
        .sv-details-icon { flex:none; width:36px; height:36px; display:grid; place-items:center; border-radius:50%; background:#fdf0f2; color:#8b1828; }
        .sv-details-header h3 { margin:0; }
        .sv-details-header small { color:#7d6b74; font-size:.73rem; }
        #svCloseDetailsBtn { position:absolute; top:1rem; right:1rem; width:30px; height:30px; display:grid; place-items:center; border:1px solid #eadde2; border-radius:50%; background:#fff; color:#6c5762; cursor:pointer; }
        #svCloseDetailsBtn:hover { background:#fbf2f4; color:#8b1828; }
        #svCloseDetailsBtn:focus-visible, .sv-action-btn:focus-visible { outline:2px solid #8b1828; outline-offset:3px; }
        .sv-student-banner { margin-top:.85rem; padding:.7rem .85rem; background:#fdf5f7; border-radius:10px; color:#6c5762; font-size:.76rem; line-height:1.5; }
        .sv-student-banner strong, .sv-student-banner span { display:block; }
        .sv-student-banner strong { color:#382630; font-size:.86rem; }
        .sv-details-status { margin-top:.65rem; }
        .sv-detail-section { border:1px solid #f0e2e7; border-radius:12px; padding:.75rem .85rem; margin-top:.75rem; }
        .sv-detail-section.has-remarks { background:#f0fdf4; border-color:#bbf7d0; }
        .sv-section-title { display:block; margin:0 0 .35rem; color:#7d6b74; font-size:.67rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
        .sv-detail-text { margin:0; color:#3b2b34; font-size:.8rem; line-height:1.5; white-space:pre-wrap; overflow-wrap:anywhere; }
        .sv-remarks-date { display:block; margin-top:.35rem; color:#65796a; font-size:.7rem; }
        .sv-timeline-steps { list-style:none; margin:.45rem 0 0; padding:0; }
        .sv-timeline-steps li { position:relative; display:flex; gap:.6rem; padding:0 0 .8rem; color:#8e7682; font-size:.76rem; }
        .sv-timeline-steps li:not(:last-child)::before { content:''; position:absolute; left:9px; top:20px; bottom:0; width:2px; background:#eadde2; }
        .sv-timeline-steps li.is-complete:not(:last-child)::before { background:#86cba5; }
        .sv-timeline-steps li:last-child { padding-bottom:0; }
        .sv-timeline-dot { position:relative; z-index:1; flex:none; width:20px; height:20px; border:1px solid #decdd4; border-radius:50%; display:grid; place-items:center; background:#fff; color:#b8a5ae; font-size:.65rem; }
        .is-complete .sv-timeline-dot { border-color:#86cba5; background:#eaf8ef; color:#16713c; }
        .is-current .sv-timeline-dot { border-color:#8b1828; background:#8b1828; color:#fff; }
        .sv-timeline-steps strong, .sv-timeline-steps small { display:block; }
        .sv-timeline-steps strong { color:#3b2b34; font-size:.77rem; }
        .sv-timeline-steps small { margin-top:.1rem; line-height:1.4; font-size:.7rem; }

        /* Submissions Table */
        .sv-table-wrap { overflow-x:auto; margin-top:.5rem; border:1px solid #f0e6e9; border-radius:12px; }
        .sv-table { width:100%; border-collapse:collapse; font-size:.82rem; text-align:left; }
        .sv-table th { padding:.75rem .9rem; background:#fdf8f9; color:#6c5762; font-weight:800; font-size:.73rem; letter-spacing:.04em; text-transform:uppercase; border-bottom:1.5px solid #ebdbe0; white-space:nowrap; }
        .sv-table td { padding:.85rem .9rem; border-bottom:1px solid #f6edf0; vertical-align:middle; color:#3b2b34; }
        .sv-table tbody tr:last-child td { border-bottom:0; }
        .sv-table tbody tr:hover td { background:#fffafb; }

        .sv-status { display:inline-block; border:1px solid #fde68a; background:#fffbeb; color:#92400e; padding:.2rem .6rem; font-size:.68rem; font-weight:800; border-radius:999px; white-space:nowrap; }
        .sv-status.verified { border-color:#a7f3d0; background:#ecfdf5; color:#065f46; }
        .sv-status.resolved { border-color:#bfdbfe; background:#eff6ff; color:#1d4ed8; }
        .sv-status.dismissed { border-color:#fecaca; background:#fef2f2; color:#991b1b; }

        .sv-action-btn { display:inline-flex; align-items:center; gap:.25rem; padding:.3rem .65rem; border-radius:7px; font-size:.75rem; font-weight:750; font-family:inherit; cursor:pointer; text-decoration:none; }
        .sv-btn-view { border:1px solid #ebd4db; background:#fff; color:#8b1828; }
        .sv-btn-view:hover { background:#fbf2f4; border-color:#8b1828; }
        .sv-btn-delete { border:1px solid #fed7d7; background:#fff; color:#dc2626; }
        .sv-btn-delete:hover { background:#fef2f2; border-color:#dc2626; }

        @media(max-width:992px) {
            .sv-layout.has-details { grid-template-columns:1fr; }
            .sv-layout.has-details .sv-col-right { position:static; width:100%; }
        }
        @media (prefers-reduced-motion:reduce) { .sv-layout { transition:none; } }
    </style>

    <div class="sv-layout">
        {{-- Left Column: Student Voice (top) and My Submissions (bottom) --}}
        <div class="sv-col-left">
            {{-- 1. Student Voice Card (top of left column) --}}
            <div class="sv-card" id="svSubmitCard">
                <div style="margin-bottom: .85rem;">
                    <h2 id="studentVoiceHeading" style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #1a1618; display: flex; align-items: center; gap: .45rem;">
                        <i class="bi bi-chat-heart" style="color: #8b1828;" aria-hidden="true"></i> Student Voice
                    </h2>
                    <p style="margin: .35rem 0 0; color: #7d6b74; font-size: .82rem; line-height: 1.5;">Share your concerns, feedback, or suggestions. OSO reviews and addresses them accordingly.</p>
                </div>
                <form method="POST" action="{{ route('portal.feedback.store') }}" class="sv-form" data-confirm-message="Send this concern to OSO?">
                    @csrf
                    <label>Your concern
                        <textarea name="message" required maxlength="2000" rows="4" placeholder="Describe your concern so OSO can respond…">{{ old('message') }}</textarea>
                        <small>Up to 2,000 characters. Only your own submission history appears here.</small>
                    </label>
                    @error('message')<p role="alert" style="color:#b91c1c;font-size:.78rem;margin:0;">{{ $message }}</p>@enderror
                    <button type="submit" class="sv-submit"><i class="bi bi-send" aria-hidden="true"></i> Send concern</button>
                </form>
            </div>

            {{-- 2. My Submissions Card (directly below Student Voice, same width, vertically aligned) --}}
            <div class="sv-card" id="mySubmissionsCard">
                <h3><i class="bi bi-collection-fill" style="color:#8b1828;"></i> My Submissions <small style="color:#8b7881;font-weight:600;">({{ $myFeedback->count() }})</small></h3>
                <div class="sv-table-wrap">
                    <table class="sv-table" aria-label="My concern submissions table">
                        <thead>
                            <tr>
                                <th style="width: 17%;">DATE SUBMITTED</th>
                                <th style="width: 33%;">CONCERN</th>
                                <th style="width: 25%;">OSO REMARKS</th>
                                <th style="width: 11%;">STATUS</th>
                                <th style="width: 14%; text-align: center;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($myFeedback as $feedback)
                                @php
                                    $studentUser = $feedback->student;
                                    $collegeDept = $feedback->college ?: ($studentUser?->college ?? 'College / Department');
                                    if ($feedback->program ?: ($studentUser?->program ?? '')) {
                                        $collegeDept .= ' ('.($feedback->program ?: $studentUser->program).')';
                                    }
                                    $statusLabel = match($feedback->status) {
                                        'verified' => 'Acknowledged',
                                        'resolved' => 'Resolved',
                                        'dismissed' => 'Dismissed',
                                        default => 'Pending',
                                    };
                                    $feedbackData = [
                                        'id' => $feedback->id,
                                        'name' => $feedback->is_anonymous ? 'Anonymous Student' : ($feedback->author_name ?: ($studentUser?->full_name ?? 'Student')),
                                        'college_department' => $collegeDept,
                                        'date_submitted' => $feedback->created_at?->format('M j, Y · g:i A') ?? 'Not recorded',
                                        'concern' => $feedback->body,
                                        'topic' => ucfirst($feedback->topic ?: 'General'),
                                        'oso_remarks' => $feedback->review_note ?: '',
                                        'status' => $feedback->status,
                                        'status_label' => $statusLabel,
                                        'created_at' => $feedback->created_at?->format('M j, Y · g:i A') ?? 'Not recorded',
                                        'verified_at' => $feedback->verified_at?->format('M j, Y · g:i A'),
                                        'updated_at' => $feedback->updated_at?->format('M j, Y · g:i A'),
                                    ];
                                @endphp
                                <tr id="feedback-row-{{ $feedback->id }}">
                                    <td style="white-space: nowrap; font-size: .8rem; color: #52424b;">
                                        <strong>{{ $feedback->created_at?->format('M j, Y') }}</strong>
                                        <small style="display: block; font-size: .72rem; color: #8e7682;">{{ $feedback->created_at?->format('g:i A') }}</small>
                                    </td>
                                    <td>
                                        <strong style="display: block; font-size: .82rem; color: #1a1618; margin-bottom: 2px;">Concern #{{ $feedback->id }}</strong>
                                        <span style="font-size: .8rem; color: #5f4d56; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $feedback->body }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($feedback->review_note)
                                            <span style="font-size: .8rem; color: #166534; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                {{ $feedback->review_note }}
                                            </span>
                                        @else
                                            <span style="font-size: .76rem; color: #94808a; font-style: italic;">No OSO remarks yet</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="sv-status {{ $feedback->status }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; justify-content: center; gap: .35rem;">
                                            <button type="button" class="sv-action-btn sv-btn-view" onclick="viewSubmissionDetails({{ json_encode($feedbackData) }})" title="View submission details">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <form method="POST" action="{{ route('portal.feedback.destroy', $feedback) }}" data-confirm-title="Delete submission?" data-confirm-message="Are you sure you want to delete this submission?" data-confirm-accept="Yes, delete" style="margin: 0; display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sv-action-btn sv-btn-delete" title="Delete submission">
                                                    <i class="bi bi-trash3"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2.25rem 1rem; color: #8e7682;">
                                        <i class="bi bi-inbox" style="font-size: 1.6rem; display: block; margin-bottom: .4rem; color: #d8c4cd;"></i>
                                        No submissions yet. Your concerns and any recorded OSO responses will appear here after you send them.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sv-col-right">
            <aside class="sv-details-card" id="svDetailsCard" aria-labelledby="svDetailsHeading">
                <button type="button" id="svCloseDetailsBtn" aria-label="Close submission details" onclick="closeSubmissionDetails()"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                <div class="sv-details-header">
                    <span class="sv-details-icon"><i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i></span>
                    <div>
                        <h3 id="svDetailsHeading">Submission Details</h3>
                        <small id="sdTopicHeader"></small>
                    </div>
                </div>
                <div class="sv-student-banner">
                    <strong id="sdName"></strong>
                    <span id="sdCollegeDept"></span>
                    <span><i class="bi bi-calendar3" aria-hidden="true"></i> Submitted <span id="sdDateSubmitted" style="display:inline;"></span></span>
                </div>
                <div class="sv-details-status"><span id="sdStatusBadge" class="sv-status"></span></div>
                <div class="sv-detail-section">
                    <span class="sv-section-title">Report</span>
                    <p id="sdConcern" class="sv-detail-text"></p>
                </div>
                <div id="sdOsoRemarksSection" class="sv-detail-section">
                    <span class="sv-section-title">OSO Remarks</span>
                    <p id="sdOsoRemarksText" class="sv-detail-text"></p>
                    <small id="sdOsoRemarksDate" class="sv-remarks-date"></small>
                </div>
                <div class="sv-detail-section">
                    <span class="sv-section-title">Status Timeline</span>
                    <ol id="sdTimeline" class="sv-timeline-steps"></ol>
                </div>
            </aside>
        </div>
    </div>

    <script>
        window.viewSubmissionDetails = function (data) {
            const layout = document.querySelector('.sv-layout');
            const detailsCard = document.getElementById('svDetailsCard');
            document.getElementById('sdTopicHeader').textContent = 'Concern #' + data.id + ' · Student Voice';
            document.getElementById('sdName').textContent = data.name || 'Student';
            document.getElementById('sdCollegeDept').textContent = data.college_department || '';
            document.getElementById('sdDateSubmitted').textContent = data.date_submitted || 'Not recorded';
            document.getElementById('sdConcern').textContent = data.concern || '';

            const badge = document.getElementById('sdStatusBadge');
            badge.className = 'sv-status ' + (data.status || 'pending');
            badge.textContent = data.status_label || 'Pending';

            const remarks = (data.oso_remarks || '').trim();
            document.getElementById('sdOsoRemarksSection').classList.toggle('has-remarks', Boolean(remarks));
            document.getElementById('sdOsoRemarksText').textContent = remarks || 'No OSO remarks yet.';
            document.getElementById('sdOsoRemarksDate').textContent = remarks && data.updated_at ? 'Submission last updated: ' + data.updated_at : '';

            const isVerified = data.status === 'verified';
            const isResolved = data.status === 'resolved';
            const isDismissed = data.status === 'dismissed';
            const timeline = document.getElementById('sdTimeline');
            timeline.replaceChildren();
            const steps = [
                { label: 'Submitted', state: 'is-complete', detail: (data.created_at || 'Date not recorded') + ' · Concern received.' },
                { label: 'Acknowledged', state: isVerified ? 'is-current' : '', detail: isVerified ? (data.verified_at || 'Date not recorded') + ' · Reviewed by OSO.' : (isResolved || isDismissed ? 'Acknowledgement history not recorded.' : 'Awaiting OSO review.') },
                { label: isDismissed ? 'Dismissed' : 'Resolved', state: isResolved || isDismissed ? 'is-current' : '', detail: isResolved ? (data.verified_at || 'Date not recorded') + ' · Resolution recorded.' : (isDismissed ? 'Decision recorded; date not recorded.' : 'Awaiting a final decision.') },
            ];
            steps.forEach(function (step) {
                const item = document.createElement('li');
                if (step.state) item.className = step.state;
                const dot = document.createElement('span');
                dot.className = 'sv-timeline-dot';
                dot.setAttribute('aria-hidden', 'true');
                dot.innerHTML = step.state ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-circle-fill"></i>';
                const text = document.createElement('div');
                const label = document.createElement('strong');
                const detail = document.createElement('small');
                label.textContent = step.label;
                detail.textContent = step.detail;
                text.append(label, detail);
                item.append(dot, text);
                timeline.append(item);
            });

            layout.classList.add('has-details');
            if (window.matchMedia('(max-width: 992px)').matches) {
                detailsCard.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            }
        };

        window.closeSubmissionDetails = function () {
            document.querySelector('.sv-layout').classList.remove('has-details');
        };

        if (window.location.hash.startsWith('#feedback-')) {
            const id = window.location.hash.replace('#feedback-', '');
            const row = document.getElementById('feedback-row-' + id);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.style.backgroundColor = '#fdf2f4';
                setTimeout(() => row.style.backgroundColor = '', 2500);
            }
        }
    </script>
</section>
