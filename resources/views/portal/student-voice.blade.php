<section class="sp-section sv-section" aria-labelledby="studentVoiceHeading">
    <style>
        .sv-section { min-width:0; }
        .sv-heading h2 { margin:0; font-size:1.15rem; }
        .sv-heading p { color:#7d6b74; font-size:.84rem; margin:.35rem 0 1rem; }

        /* Two-column layout: Left column (Student Voice + My Submissions), Right column (Submission Details) */
        .sv-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 1fr);
            gap: 1.25rem;
            align-items: stretch;
        }
        .sv-col-left {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            min-width: 0;
            height: 100%;
        }
        .sv-col-right {
            display: flex;
            flex-direction: column;
            min-width: 0;
            height: 100%;
        }
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

        /* Details Card on Right Side */
        #svDetailsCard {
            height: 100%;
            min-height: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .sv-details-box {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding-right: .25rem;
        }
        .sv-details-box::-webkit-scrollbar { width: 5px; }
        .sv-details-box::-webkit-scrollbar-track { background: transparent; }
        .sv-details-box::-webkit-scrollbar-thumb { background: #ecdfe4; border-radius: 4px; }

        .sv-timeline { list-style:none; padding:0 0 0 .55rem; margin:.35rem 0 0; border-left:2px solid #ecd6df; }
        .sv-timeline li { position:relative; padding:0 0 .55rem .65rem; font-size:.78rem; }
        .sv-timeline li::before { content:''; position:absolute; width:7px; height:7px; border-radius:50%; background:#a44d69; left:-.83rem; top:.25rem; }
        .sv-timeline li:last-child { padding-bottom:0; margin-bottom:0; }
        .sv-timeline strong { display:block; color:#2d2429; font-size:.78rem; }
        .sv-timeline small { color:#8e7682; margin-top:.15rem; display:block; font-size:.72rem; }

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
            .sv-layout { grid-template-columns:1fr; }
            .sv-col-right { height:auto; }
            #svDetailsCard { height:auto; max-height:none; }
            .sv-details-box { max-height:500px; }
        }
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

        {{-- Right Column: Submission Details Panel (Top aligned with Student Voice, bottom matches My Submissions, internally scrollable) --}}
        <div class="sv-col-right">
            <div class="sv-card" id="svDetailsCard">
                @php
                    $initialFeedback = $myFeedback->first();
                    $initStudentUser = $initialFeedback?->student;
                    $initCollegeDept = $initialFeedback ? ($initialFeedback->college ?: ($initStudentUser?->college ?? 'College / Department')) : '';
                    if ($initialFeedback && ($initialFeedback->program ?: ($initStudentUser?->program ?? ''))) {
                        $initCollegeDept .= ' ('.($initialFeedback->program ?: $initStudentUser->program).')';
                    }
                    $initStatusLabel = $initialFeedback ? match($initialFeedback->status) {
                        'verified' => 'Acknowledged',
                        'resolved' => 'Resolved',
                        'dismissed' => 'Dismissed',
                        default => 'Pending',
                    } : 'Pending';
                @endphp

                {{-- Placeholder when no submissions exist yet --}}
                <div id="sdPlaceholder" style="{{ $myFeedback->isNotEmpty() ? 'display: none;' : 'display: flex;' }} flex-direction: column; align-items: center; justify-content: center; text-align: center; height: 100%; min-height: 220px; padding: 1.5rem 1rem; color: #8e7682; margin: auto;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #fdf0f2; color: #8b1828; display: grid; place-items: center; font-size: 1.25rem; margin-bottom: 0.65rem;">
                        <i class="bi bi-eye"></i>
                    </div>
                    <h4 style="margin: 0 0 0.25rem; font-size: 0.95rem; color: #1e293b; font-weight: 800;">Submission Details</h4>
                    <p style="margin: 0; font-size: 0.8rem; color: #64748b; max-width: 270px; line-height: 1.5;">Click the <strong>View</strong> button on any submission in the table to inspect its full details and OSO response.</p>
                </div>

                {{-- Content when a submission is loaded or available --}}
                <div id="sdContent" class="sv-details-box" style="{{ $myFeedback->isEmpty() ? 'display: none;' : 'display: flex;' }}">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: .6rem; margin-bottom: .25rem; flex-shrink: 0;">
                        <div style="display: flex; align-items: center; gap: .5rem;">
                            <div style="width: 30px; height: 30px; border-radius: 50%; background: #fdf0f2; color: #8b1828; display: grid; place-items: center; font-size: .95rem;">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: .95rem; font-weight: 800; color: #1e293b;">Submission Details</h3>
                                <small id="sdTopicHeader" style="color: #64748b; font-size: .72rem; font-weight: 600;">
                                    @if ($initialFeedback) Concern #{{ $initialFeedback->id }} @endif
                                </small>
                            </div>
                        </div>
                        <span id="sdStatusBadge" class="sv-status {{ $initialFeedback?->status ?? 'pending' }}">{{ $initStatusLabel }}</span>
                    </div>

                    {{-- Student Reporter info --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: .6rem .85rem; flex-shrink: 0;">
                        <span style="font-size: .67rem; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: .04em; display: block;">Student Reporter</span>
                        <strong id="sdName" style="display: block; font-size: .88rem; color: #0f172a;">
                            {{ $initialFeedback ? ($initialFeedback->is_anonymous ? 'Anonymous Student' : ($initialFeedback->author_name ?: ($initStudentUser?->full_name ?? 'Student'))) : '' }}
                        </strong>
                        <span id="sdCollegeDept" style="display: block; font-size: .76rem; color: #64748b; font-weight: 600; margin-top: 1px;">
                            {{ $initCollegeDept }}
                        </span>
                    </div>

                    {{-- Date Submitted --}}
                    <div style="display: flex; align-items: center; gap: .4rem; font-size: .78rem; color: #475569; flex-shrink: 0;">
                        <i class="bi bi-calendar3" style="color: #8b1828;"></i>
                        <span>Date Submitted: <strong id="sdDateSubmitted" style="color: #0f172a;">{{ $initialFeedback?->created_at?->format('M j, Y · g:i A') ?? 'Not recorded' }}</strong></span>
                    </div>

                    {{-- Report / Concern Text --}}
                    <div style="display: flex; flex-direction: column; flex: 1; min-height: 110px;">
                        <span style="font-size: .67rem; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: .04em; display: block; margin-bottom: 3px;">Report / Concern</span>
                        <p id="sdConcern" style="margin: 0; padding: .65rem .85rem; background: #fffafb; border: 1px solid #f1e4e8; border-radius: 8px; font-size: .82rem; color: #334155; line-height: 1.55; white-space: pre-wrap; word-break: break-word; flex: 1; min-height: 75px; overflow-y: auto;">{{ $initialFeedback?->body ?? '' }}</p>
                    </div>

                    {{-- OSO Remarks (Shown only when OSO remarks exist) --}}
                    <div id="sdOsoRemarksSection" style="flex-shrink: 0; {{ !empty($initialFeedback?->review_note) ? '' : 'display: none;' }}">
                        <span style="font-size: .67rem; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: .04em; display: block; margin-bottom: 2px;">OSO Remarks</span>
                        <div id="sdOsoRemarksBox" style="margin: 0; padding: .6rem .85rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: .82rem; color: #166534; line-height: 1.55; white-space: pre-wrap; word-break: break-word;">
                            <strong style="display: block; margin-bottom: 2px; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em;">Office of Student Organizations (OSO)</strong>
                            <span id="sdOsoRemarksText">{{ $initialFeedback?->review_note ?? '' }}</span>
                        </div>
                    </div>
                    {{-- Status Timeline --}}
                    <div style="flex-shrink: 0; margin-top: auto; padding-top: .4rem;">
                        <span style="font-size: .67rem; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: .04em; display: block; margin-bottom: 2px;">Status Timeline</span>
                        <ol id="sdTimeline" class="sv-timeline">
                            @if ($initialFeedback)
                                <li><strong>Submitted</strong><small>{{ $initialFeedback->created_at?->format('M j, Y · g:i A') ?? 'Submission recorded' }}</small></li>
                                @if ($initialFeedback->status === 'verified')
                                    <li><strong>Acknowledged by OSO</strong><small>{{ $initialFeedback->verified_at?->format('M j, Y · g:i A') ?? 'Acknowledged by office' }}</small></li>
                                @elseif ($initialFeedback->status === 'resolved')
                                    @if ($initialFeedback->verified_at)
                                        <li><strong>Acknowledged by OSO</strong><small>{{ $initialFeedback->verified_at->format('M j, Y · g:i A') }}</small></li>
                                    @endif
                                    <li><strong>Resolved by OSO</strong><small>Resolution confirmed</small></li>
                                @elseif ($initialFeedback->status === 'dismissed')
                                    <li><strong>Dismissed by OSO</strong><small>Decision recorded</small></li>
                                @else
                                    <li><strong>Awaiting OSO review</strong><small>Pending initial assessment</small></li>
                                @endif
                            @endif
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.viewSubmissionDetails = function (data) {
            const placeholder = document.getElementById('sdPlaceholder');
            const content = document.getElementById('sdContent');
            const detailsCard = document.getElementById('svDetailsCard');

            if (placeholder) placeholder.style.display = 'none';
            if (content) content.style.display = 'flex';

            document.getElementById('sdTopicHeader').textContent = 'Concern #' + data.id;
            document.getElementById('sdName').textContent = data.name || 'Student';
            document.getElementById('sdCollegeDept').textContent = data.college_department || '';
            document.getElementById('sdDateSubmitted').textContent = data.date_submitted || '';
            document.getElementById('sdConcern').textContent = data.concern || '';

            const badge = document.getElementById('sdStatusBadge');
            if (badge) {
                badge.className = 'sv-status ' + (data.status || 'pending');
                badge.textContent = data.status_label || 'Pending';
            }

            const remarksSection = document.getElementById('sdOsoRemarksSection');
            const remarksText = document.getElementById('sdOsoRemarksText');
            const remarks = (data.oso_remarks || '').trim();
            const hasRemarks = Boolean(remarks && remarks !== 'No OSO remarks recorded yet.');

            if (remarksSection) {
                remarksSection.style.display = hasRemarks ? 'block' : 'none';
            }
            if (remarksText) {
                remarksText.textContent = remarks;
            }

            const timeline = document.getElementById('sdTimeline');
            if (timeline) {
                let items = `<li><strong>Submitted</strong><small>${data.created_at || 'Submission recorded'}</small></li>`;
                if (data.status === 'verified') {
                    items += `<li><strong>Acknowledged by OSO</strong><small>${data.verified_at || 'Acknowledged by office'}</small></li>`;
                } else if (data.status === 'resolved') {
                    if (data.verified_at) {
                        items += `<li><strong>Acknowledged by OSO</strong><small>${data.verified_at}</small></li>`;
                    }
                    items += `<li><strong>Resolved by OSO</strong><small>Resolution confirmed</small></li>`;
                } else if (data.status === 'dismissed') {
                    items += `<li><strong>Dismissed by OSO</strong><small>Decision recorded</small></li>`;
                } else {
                    items += `<li><strong>Awaiting OSO review</strong><small>Pending initial assessment</small></li>`;
                }
                timeline.innerHTML = items;
            }

            if (detailsCard) {
                detailsCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                detailsCard.style.boxShadow = '0 0 0 3px rgba(139, 24, 40, 0.25)';
                setTimeout(() => detailsCard.style.boxShadow = '', 1500);
            }
        };

        // Backwards compatibility alias
        window.openSubmissionDetailsModal = window.viewSubmissionDetails;

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
