@extends('org.layout')

@section('title', 'TOSA')

@section('header')
    <h1><strong>TOSA</strong></h1>
    <p class="org-welcome">Applications, documents and results.</p>
@endsection

@section('content')
    <style>
        .tosa-page { display:grid; gap:1rem; min-width:0; }
        .tosa-tabs { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.2rem; }
        .tosa-tabs a { flex:none; padding:.65rem .9rem; border-radius:10px; color:#66565c; text-decoration:none; font-weight:700; }
        .tosa-tabs a[aria-current=page] { color:#8b1828; background:#f8eaed; }
        .tosa-card { min-width:0; background:#fff; border:1px solid #eadfe2; border-radius:14px; padding:1rem; }
        .tosa-card h2 { margin:0 0 .35rem; font-size:1rem; }
        .tosa-note { color:#6b6064; font-size:.85rem; margin:.3rem 0; }
        .tosa-toolbar { display:flex; flex-wrap:wrap; gap:.55rem; align-items:end; margin:.85rem 0; }
        .tosa-toolbar form { display:flex; flex-wrap:wrap; gap:.5rem; flex:1; }
        .tosa-input { border:1px solid #d9cdd1; border-radius:8px; padding:.55rem .65rem; font:inherit; min-width:0; max-width:100%; }
        .tosa-toolbar input { flex:1 1 14rem; }
        .tosa-table-wrap { max-height:min(60dvh, 36rem); overflow:auto; overscroll-behavior:contain; border:1px solid #f1e9eb; border-radius:12px; }
        .tosa-table-wrap:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-table { width:100%; border-collapse:collapse; font-size:.86rem; }
        .tosa-table th, .tosa-table td { text-align:left; vertical-align:middle; padding:.75rem .6rem; border-bottom:1px solid #f0e8ea; }
        .tosa-table th { position:sticky; top:0; z-index:1; background:#fff; color:#6b6064; font-size:.75rem; }
        .tosa-table td strong, .tosa-table td small { display:block; overflow-wrap:anywhere; }
        .tosa-table td small { color:#6b6064; margin-top:.15rem; }
        .tosa-status { display:inline-block; border-radius:999px; padding:.2rem .6rem; background:#f8ecee; color:#8b1828; font-size:.75rem; font-weight:700; white-space:nowrap; }
        .tosa-status.is-verified { background:#e8f6ec; color:#216b39; }
        .tosa-actions, .tosa-footer, .tosa-pagination { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
        .tosa-footer, .tosa-pagination { margin-top:.85rem; }
        .tosa-pagination { justify-content:space-between; }
        .tosa-feedback { border-radius:10px; padding:.7rem; background:#f8f1f3; color:#73303a; overflow-wrap:anywhere; }
        .tosa-modal { box-sizing:border-box; position:fixed; inset:0; margin:auto; border:1px solid #eadfe2; border-radius:14px; padding:0; width:min(760px, calc(100vw - 2rem)); max-width:none; max-height:calc(100dvh - 2rem); overflow:auto; color:#30262a; animation:none; transform:none; }
        .tosa-modal::backdrop { background:rgba(28,16,23,.55); }
        .tosa-modal-body { padding:1rem; }
        .tosa-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
        .tosa-close { border:0; background:transparent; font-size:1.4rem; cursor:pointer; }
        .tosa-file-row { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.65rem 0; border-bottom:1px solid #f1e9eb; }
        .tosa-file-info { display:flex; align-items:center; gap:.7rem; min-width:0; flex:1; }
        .tosa-file-icon { display:grid; place-items:center; width:2.4rem; height:2.4rem; flex:none; border-radius:10px; background:#f8edf0; color:#9a263a; font-size:1.15rem; }
        .tosa-file-icon.is-word { background:#edf2fb; color:#315a9c; }
        .tosa-file-icon.is-sheet { background:#eaf6ee; color:#267446; }
        .tosa-file-copy { min-width:0; overflow-wrap:anywhere; }
        .tosa-file-copy strong { display:block; color:#30262a; font-size:.85rem; line-height:1.35; }
        .tosa-file-copy small { display:block; margin-top:.15rem; color:#786c70; font-size:.72rem; }
        .tosa-file-actions { display:flex; align-items:center; gap:.3rem; flex:none; }
        .tosa-icon-button { display:inline-flex; align-items:center; justify-content:center; box-sizing:border-box; width:2.5rem; height:2.5rem; padding:0; border:1px solid #eadfe2; border-radius:10px; background:#fff; color:#842335; font-size:1rem; line-height:1; text-decoration:none; cursor:pointer; }
        .tosa-icon-button:hover { background:#fbf1f3; border-color:#d7b9c0; }
        .tosa-icon-button:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-template-row, .tosa-call, .tosa-candidate, .tosa-event { padding:.75rem 0; border-top:1px solid #f1e9eb; }
        .tosa-template-row { position:relative; }
        .tosa-template-row .tosa-file-row { padding:0; border:0; }
        .tosa-template-row.has-replace .tosa-file-actions { padding-right:2.8rem; }
        .tosa-template-replace summary { position:absolute; top:.75rem; right:0; list-style:none; }
        .tosa-template-replace summary::-webkit-details-marker { display:none; }
        .tosa-template-replace form { display:grid; gap:.5rem; margin-top:.75rem; padding:.75rem; border:1px solid #f1e9eb; border-radius:10px; }
        .tosa-template-replace label { display:grid; gap:.35rem; font-size:.82rem; }
        .tosa-template-replace input[type=file] { max-width:100%; }
        .tosa-template-replace button { justify-self:start; }
        .tosa-candidate { display:flex; align-items:flex-start; gap:.55rem; min-width:0; overflow-wrap:anywhere; }
        .tosa-candidate input { margin-top:.2rem; flex:none; }
        .tosa-call-form { display:grid; gap:.6rem; max-width:44rem; margin:.8rem 0 1rem; }
        .tosa-call-form label { display:grid; gap:.3rem; }
        .tosa-call-form textarea { min-height:5rem; resize:vertical; }
        .tosa-correction { margin-top:.85rem; padding-top:.8rem; border-top:1px solid #f1e9eb; }
        .tosa-correction summary { cursor:pointer; font-weight:700; }
        .tosa-correction form { display:grid; gap:.6rem; margin-top:.75rem; }
        .tosa-correction label { display:grid; gap:.25rem; }
        .tosa-correction textarea { min-height:5rem; resize:vertical; }
        .tosa-correction button { justify-self:start; }
        .tosa-candidate span { flex:1; }
        .tosa-candidate small { display:block; color:#6b6064; }
        .tosa-event strong, .tosa-event small { display:block; }
        .tosa-event small { color:#6b6064; margin-top:.2rem; }
        @media (max-width:700px) {
            .tosa-tabs { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); overflow:visible; }
            .tosa-tabs a { display:flex; align-items:center; justify-content:center; min-width:0; min-height:2.75rem; padding:.55rem .6rem; text-align:center; line-height:1.2; font-size:.82rem; overflow-wrap:anywhere; }
            .tosa-table, .tosa-table tbody, .tosa-table tr, .tosa-table td { display:block; width:100%; }
            .tosa-table thead { display:none; }
            .tosa-table tr { border:1px solid #eadfe2; border-radius:12px; padding:.55rem; margin:.55rem 0; }
            .tosa-table td { display:flex; justify-content:space-between; gap:.75rem; border:0; padding:.35rem; }
            .tosa-table td::before { content:attr(data-label); color:#6b6064; font-size:.76rem; flex:none; }
            .tosa-table td > div { min-width:0; text-align:right; }
            .tosa-file-row { gap:.45rem; }
            .tosa-file-info { gap:.5rem; }
            .tosa-file-actions { gap:.2rem; }
            .tosa-icon-button { width:2.35rem; height:2.5rem; }
            .tosa-template-row.has-replace .tosa-file-actions { padding-right:2.55rem; }
        }
    </style>

    <div class="tosa-page">
        <nav class="tosa-tabs" aria-label="TOSA sections">
            @foreach (['submissions' => 'Submissions', 'documents' => 'Documents & Announcements', 'top10' => 'Top 10', 'history' => 'History'] as $key => $label)
                <a href="{{ route('office.tosa', ['tab' => $key]) }}" @if ($tosaTab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        @if (session('success')) <p class="tosa-feedback" role="status">{{ session('success') }}</p> @endif
        @if (session('warning')) <p class="tosa-feedback" role="alert">{{ session('warning') }}</p> @endif
        @if ($errors->any()) <div class="tosa-feedback" role="alert">{{ $errors->first() }}</div> @endif

        @if ($tosaTab === 'submissions')
            <section class="tosa-card" aria-labelledby="tosaSubmissionsHeading">
                <h2 id="tosaSubmissionsHeading">Applications</h2>
                <p class="tosa-note">{{ $tosaApplicants->total() }} applicants</p>
                <div class="tosa-toolbar">
                    <form method="get" action="{{ route('office.tosa') }}">
                        <input type="hidden" name="tab" value="submissions">
                        <input class="tosa-input" type="search" name="search" value="{{ $tosaSearch }}" maxlength="100" placeholder="Search name, student ID or program" aria-label="Search applicants">
                        <button class="org-btn org-btn-ghost org-btn-sm" type="submit">Search</button>
                    </form>
                </div>
                <div class="tosa-table-wrap" role="region" aria-label="Submitted applications" tabindex="0">
                    <table class="tosa-table">
                        <thead><tr><th>Applicant</th><th>Program / year</th><th>Review</th><th>Date sent</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse ($tosaApplicants as $applicant)
                                @php
                                    $returned = $applicant->subsection === 'returned';
                                    $verified = app(\App\Services\TosaWorkflowService::class)->isVerified($applicant);
                                @endphp
                                <tr>
                                    <td data-label="Applicant"><div><strong>{{ $applicant->full_name }}</strong><small>{{ $applicant->sr_code }}</small></div></td>
                                    <td data-label="Program / year"><div>{{ $applicant->program ?: '—' }}<small>{{ $applicant->year_level ?: '—' }}</small></div></td>
                                    <td data-label="Review"><div><span class="tosa-status {{ $verified ? 'is-verified' : '' }}">{{ $returned ? 'For correction' : ($verified ? 'Reviewed' : 'For review') }}</span></div></td>
                                    <td data-label="Date sent"><div>{{ $applicant->latest_submission_at ? \Carbon\Carbon::parse($applicant->latest_submission_at)->format('M j, Y') : '—' }}</div></td>
                                    <td data-label="Actions"><div class="tosa-actions">
                                        <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-tosa-open="{{ $applicant->id }}">View files</button>
                                        @if ($tosaCanManageWorkflow)<a class="org-btn org-btn-ghost org-btn-sm" href="{{ route('office.tosa.package.download', $applicant) }}">Download ZIP</a>@endif
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No applications found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="tosa-pagination">
                    <span class="tosa-note">Page {{ $tosaApplicants->currentPage() }} of {{ $tosaApplicants->lastPage() }}</span>
                    <div class="tosa-actions">
                        @if ($tosaApplicants->previousPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaApplicants->previousPageUrl() }}">Previous</a>@endif
                        @if ($tosaApplicants->nextPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaApplicants->nextPageUrl() }}">Next</a>@endif
                    </div>
                </div>
                <noscript><p class="tosa-note">Enable JavaScript to open document review.</p></noscript>
            </section>

            @foreach ($tosaReviewApplicants as $applicant)
                @php
                    $returned = $applicant->subsection === 'returned';
                    $hasUnavailable = false;
                    $draftIds = array_map('intval', $tosaShortlist->draft_ids ?? []);
                    $inDraft = in_array((int) $applicant->id, $draftIds, true);
                    $alreadyReviewed = $inDraft && app(\App\Services\TosaWorkflowService::class)->isVerified($applicant);
                @endphp
                <dialog class="tosa-modal" id="tosa-review-{{ $applicant->id }}" aria-labelledby="tosa-review-title-{{ $applicant->id }}">
                    <div class="tosa-modal-body">
                        <header class="tosa-modal-head">
                            <div><h2 id="tosa-review-title-{{ $applicant->id }}">{{ $applicant->full_name }}</h2><p class="tosa-note">{{ $applicant->sr_code }} · {{ $applicant->program }}</p></div>
                            <button type="button" class="tosa-close" data-tosa-close aria-label="Close document review">&times;</button>
                        </header>
                        @if ($returned)
                            <p class="tosa-note">Waiting for the student to resubmit.</p>
                        @elseif ($tosaCanManageWorkflow)
                            <p class="tosa-note">Review all files before adding this applicant.</p>
                        @else
                            <p class="tosa-note">Open a file to review it.</p>
                        @endif
                            @foreach (\App\Services\TosaRequirements::SUBMISSIONS as $key => $requirement)
                                @php
                                    $path = $applicant->requirementPath($key);
                                    $available = $path !== null && $applicant->requirementFile($key) !== null;
                                    $hasUnavailable = $hasUnavailable || !$available;
                                    $name = data_get($applicant->requirements, $key.'.name') ?: ($path ? basename($path) : $requirement['title']);
                                    $url = $available ? route('office.tosa.documents.download', ['applicant' => $applicant->id, 'key' => $key]) : null;
                                @endphp
                                <div class="tosa-document tosa-file-row">
                                    <div class="tosa-file-info">
                                        <span class="tosa-file-icon {{ $available && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'docx' ? 'is-word' : '' }}" aria-hidden="true">
                                            <i class="bi {{ $available ? (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'docx' ? 'bi-file-earmark-word' : 'bi-file-earmark-pdf') : 'bi-file-earmark-x' }}"></i>
                                        </span>
                                        <span class="tosa-file-copy"><strong>{{ $requirement['code'] }}. {{ $requirement['title'] }}</strong><small>{{ $available ? strtoupper(pathinfo($path, PATHINFO_EXTENSION)) : 'Unavailable' }}</small></span>
                                    </div>
                                    <div class="tosa-file-actions">
                                        @if ($available)
                                            <button type="button" class="tosa-icon-button" title="View {{ $requirement['title'] }}" aria-label="View {{ $requirement['title'] }}" data-document-preview data-document-preview-url="{{ $url }}" data-document-preview-title="{{ $requirement['title'] }}" data-document-original-name="{{ $name }}" data-document-preview-type="{{ strtolower(pathinfo($path, PATHINFO_EXTENSION)) }}" data-document-preview-download="{{ $url }}?download=1"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                            <a class="tosa-icon-button" href="{{ $url }}?download=1" title="Download {{ $requirement['title'] }}" aria-label="Download {{ $requirement['title'] }}"><i class="bi bi-download" aria-hidden="true"></i></a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @if ($hasUnavailable)<p class="tosa-note">Unavailable files cannot be viewed. Ask the student to upload the actual documents.</p>@endif
                        @if ($tosaCanManageWorkflow && !$returned)
                            <div class="tosa-footer">
                                @if ($alreadyReviewed)
                                    <span class="tosa-status is-verified"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> In Top 10 draft</span>
                                @else
                                    <form method="post" action="{{ route('office.tosa.top10.add', $applicant) }}" data-confirm-message="Have you reviewed all seven documents? Add this applicant to the Top 10 draft?">
                                        @csrf
                                        <button type="submit" class="org-btn org-btn-primary org-btn-sm" @disabled($hasUnavailable || (!$inDraft && count($draftIds) >= 10))><i class="bi bi-star-fill" aria-hidden="true"></i> Add to Top 10</button>
                                    </form>
                                @endif
                            </div>
                            @if (!$inDraft && count($draftIds) >= 10)<p class="tosa-note">The draft is full. Remove someone in the Top 10 tab first.</p>@endif
                        @endif
                        @if ($tosaCanManageWorkflow && !$returned)
                            <details class="tosa-correction">
                                <summary>Return for correction</summary>
                                <form method="post" action="{{ route('office.tosa.subsection', $applicant) }}" data-confirm-message="Return this document and email the student?">
                                    @csrf <input type="hidden" name="subsection" value="returned">
                                    <label>Document
                                        <select class="tosa-input" name="document_key" required>
                                            <option value="">Select a document</option>
                                            @foreach (\App\Services\TosaRequirements::SUBMISSIONS as $key => $requirement)
                                                <option value="{{ $key }}">{{ $requirement['code'] }}. {{ $requirement['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>Reason
                                        <textarea class="tosa-input" name="reason" maxlength="1000" required></textarea>
                                    </label>
                                    <button type="submit" class="org-btn org-btn-ghost org-btn-sm">Return and email student</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </dialog>
            @endforeach
        @elseif ($tosaTab === 'documents')
            <section class="tosa-card" aria-labelledby="tosaDocumentsHeading">
                <h2 id="tosaDocumentsHeading">Official documents</h2>
                <p class="tosa-note">Download the application form and guides.</p>
                @foreach (\App\Services\TosaRequirements::REFERENCES as $key => $reference)
                    @php($template = $tosaTemplates[$reference['title']] ?? null)
                    <div class="tosa-template-row {{ $tosaCanManageTemplates ? 'has-replace' : '' }}">
                        <div class="tosa-file-row">
                            <div class="tosa-file-info">
                                <span class="tosa-file-icon {{ $reference['format'] === 'XLSX' ? 'is-sheet' : (in_array($reference['format'], ['DOC', 'DOCX'], true) ? 'is-word' : '') }}" aria-hidden="true"><i class="bi {{ $reference['format'] === 'XLSX' ? 'bi-file-earmark-spreadsheet' : (in_array($reference['format'], ['DOC', 'DOCX'], true) ? 'bi-file-earmark-word' : 'bi-file-earmark-pdf') }}"></i></span>
                                <span class="tosa-file-copy"><strong>{{ $reference['title'] }}</strong><small>{{ !empty($template['url']) ? $reference['format'] : 'Unavailable' }}</small></span>
                            </div>
                            <div class="tosa-file-actions">
                                @if (!empty($template['url']))
                                    @if ($reference['format'] !== 'XLSX')<button type="button" class="tosa-icon-button" title="View {{ $reference['title'] }}" aria-label="View {{ $reference['title'] }}" data-document-preview data-document-preview-url="{{ $template['preview_url'] ?: $template['url'] }}" data-document-preview-title="{{ $reference['title'] }}" data-document-original-name="{{ $template['name'] }}" data-document-preview-type="{{ strtolower($reference['format']) }}" data-document-conversion-url="{{ route('office.documents.preview.convert') }}" data-document-preview-download="{{ $template['url'] }}?download=1"><i class="bi bi-eye" aria-hidden="true"></i></button>@endif
                                    <a class="tosa-icon-button" href="{{ $template['url'] }}?download=1" title="Download {{ $reference['title'] }}" aria-label="Download {{ $reference['title'] }}"><i class="bi bi-download" aria-hidden="true"></i></a>
                                @endif
                            </div>
                        </div>
                        @if ($tosaCanManageTemplates)
                            <details class="tosa-template-replace">
                                <summary class="tosa-icon-button" title="Replace {{ $reference['title'] }}" aria-label="Replace {{ $reference['title'] }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></summary>
                                <form method="post" action="{{ route('office.tosa.references.store') }}" enctype="multipart/form-data" data-org-upload-form data-confirm-message="Replace this reference file?">
                                    @csrf <input type="hidden" name="reference_key" value="{{ $key }}">
                                    <label>Choose {{ $reference['format'] }} file <input type="file" name="template_file" accept="{{ implode(',', array_map(fn ($extension) => '.'.$extension, $reference['extensions'])) }}" required data-org-upload data-max-size="20480" data-upload-status-id="tosa-template-status-{{ $key }}"></label>
                                    <p id="tosa-template-status-{{ $key }}" class="tosa-note" aria-live="polite"></p>
                                    <button type="submit" class="org-btn org-btn-primary org-btn-sm">Save reference</button>
                                </form>
                            </details>
                        @endif
                    </div>
                @endforeach
            </section>
            <section class="tosa-card" aria-labelledby="tosaCallsHeading">
                <h2 id="tosaCallsHeading">TOSA announcements</h2>
                @if ($tosaCanManageWorkflow)
                    <form class="tosa-call-form" method="post" action="{{ route('office.updates.announcements.store') }}" enctype="multipart/form-data" data-org-upload-form data-confirm-message="Publish this Call for TOSA?">
                        @csrf
                        <input type="hidden" name="type" value="Call for TOSA"><input type="hidden" name="priority" value="normal"><input type="hidden" name="return_to" value="tosa">
                        <label>Announcement <textarea class="tosa-input" name="body" maxlength="10000" required>{{ old('body') }}</textarea></label>
                        <label>Closes on <input class="tosa-input" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" required></label>
                        <label>Attachment (optional) <input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" data-org-upload data-max-size="15360" data-upload-status-id="tosa-call-attachment-status"></label>
                        <small id="tosa-call-attachment-status" class="tosa-note" aria-live="polite"></small>
                        <div><button type="submit" class="org-btn org-btn-primary org-btn-sm">Publish Call for TOSA</button></div>
                    </form>
                @endif
                @forelse ($tosaCalls as $call)
                    <article class="tosa-call">
                        <strong>{{ $call->title }}</strong><p class="tosa-note">{{ $call->expires_at && $call->expires_at->isPast() ? 'Closed' : 'Open' }}</p>
                        <p>{{ $call->body }}</p>
                        @if ($call->attachment_path)<a href="{{ route('office.updates.announcements.attachment', $call) }}">Download attachment</a>@endif
                    </article>
                @empty<p class="tosa-note">No TOSA announcements yet.</p>@endforelse
                <div class="tosa-pagination">
                    <span class="tosa-note">Page {{ $tosaCalls->currentPage() }} of {{ $tosaCalls->lastPage() }}</span><div class="tosa-actions">
                        @if ($tosaCalls->previousPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaCalls->previousPageUrl() }}">Previous</a>@endif
                        @if ($tosaCalls->nextPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaCalls->nextPageUrl() }}">Next</a>@endif
                    </div>
                </div>
            </section>
        @elseif ($tosaTab === 'top10')
            @php($draftIds = $tosaShortlist->draft_ids ?? [])
            <section class="tosa-card" aria-labelledby="tosaTopHeading">
                <h2 id="tosaTopHeading">Top 10</h2>
                <p class="tosa-note">Add applicants from Submissions. Uncheck and save to remove someone; publish when ten are ready. This list is not ranked.</p>
                <form method="post" action="{{ route('office.tosa.top10.save') }}" data-confirm-message="Save this Top 10 selection?" id="tosaTop10Form">
                    @csrf
                    <p class="tosa-note" id="tosaTopCount" aria-live="polite">{{ count($draftIds) }} of 10 selected</p>
                    @forelse ($tosaCandidates as $candidate)
                        @php($applicant = $candidate['applicant'])
                        @php($selected = in_array((int) $applicant->id, $draftIds, true))
                        <label class="tosa-candidate"><input type="checkbox" name="applicant_ids[]" value="{{ $applicant->id }}" @checked($selected) @disabled(!$tosaCanManageWorkflow || (!$candidate['verified'] && !$selected))><span><strong>{{ $applicant->full_name }}</strong><small>{{ $applicant->program ?: '—' }} · {{ $candidate['verified'] ? 'Reviewed' : 'Review documents first' }}</small></span></label>
                    @empty<p class="tosa-note">No submitted applications yet.</p>@endforelse
                    @if ($tosaCanManageWorkflow)
                        <div class="tosa-footer">
                            <button type="submit" name="intent" value="save" class="org-btn org-btn-ghost org-btn-sm">Save draft</button>
                            <button type="submit" name="intent" value="publish" class="org-btn org-btn-primary org-btn-sm" data-confirm-message="Publish these ten names to students?">Publish Top 10</button>
                        </div>
                    @endif
                </form>
            </section>
            @if ($tosaShortlist->published_at && is_array($tosaShortlist->published_entries))
                <section class="tosa-card" aria-labelledby="tosaPublishedHeading">
                    <h2 id="tosaPublishedHeading">Published to students</h2>
                    <ul>@foreach ($tosaShortlist->published_entries as $entry)<li>{{ $entry['name'] }}@if (!empty($entry['program'])) · {{ $entry['program'] }}@endif</li>@endforeach</ul>
                    @if ($tosaCanManageWorkflow)<form method="post" action="{{ route('office.tosa.top10.save') }}" data-confirm-message="Hide the published Top 10 from students?">@csrf <input type="hidden" name="intent" value="unpublish"><button type="submit" class="org-btn org-btn-ghost org-btn-sm">Unpublish</button></form>@endif
                </section>
            @endif
        @else
            @php($eventLabels = ['submitted' => 'Application submitted', 'resubmitted' => 'Application resubmitted', 'reviewed' => 'Documents reviewed', 'returned' => 'Returned for correction', 'call_published' => 'TOSA call published', 'top10_saved' => 'Top 10 draft saved', 'top10_published' => 'Top 10 published', 'top10_unpublished' => 'Top 10 unpublished'])
            <section class="tosa-card" aria-labelledby="tosaHistoryHeading">
                <h2 id="tosaHistoryHeading">History</h2>
                <p class="tosa-note">Activity is recorded from this update onward.</p>
                @forelse ($tosaEvents as $event)
                    <article class="tosa-event">
                        <strong>{{ $eventLabels[$event->action] ?? 'TOSA activity' }}@if ($event->applicant) · {{ $event->applicant->full_name }}@endif</strong>
                        <small>{{ $event->actor_name }} · {{ $event->created_at->format('M j, Y, g:i A') }}</small>
                        @if ($event->action === 'returned' && !empty($event->details['reason']))
                            <small>Document: {{ \App\Services\TosaRequirements::SUBMISSIONS[$event->details['document_key'] ?? '']['title'] ?? 'TOSA document' }}</small>
                            <p>{{ $event->details['reason'] }}</p>
                        @endif
                        @if (!empty($event->details['names']))<details><summary>View published names</summary><ul>@foreach ($event->details['names'] as $name)<li>{{ $name }}</li>@endforeach</ul></details>@endif
                    </article>
                @empty<p class="tosa-note">No recorded activity yet.</p>@endforelse
                <div class="tosa-pagination"><span class="tosa-note">Page {{ $tosaEvents->currentPage() }} of {{ $tosaEvents->lastPage() }}</span><div class="tosa-actions">
                    @if ($tosaEvents->previousPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaEvents->previousPageUrl() }}">Previous</a>@endif
                    @if ($tosaEvents->nextPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaEvents->nextPageUrl() }}">Next</a>@endif
                </div></div>
            </section>
        @endif
    </div>

    @include('org.partials.document-preview')
    @push('scripts')
        <script>
            document.querySelectorAll('[data-tosa-open]').forEach(button => button.addEventListener('click', () => {
                document.getElementById('tosa-review-' + button.dataset.tosaOpen)?.showModal();
            }));
            document.querySelectorAll('[data-tosa-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
            const topForm = document.getElementById('tosaTop10Form');
            if (topForm) topForm.addEventListener('change', event => {
                const boxes = [...topForm.querySelectorAll('input[name="applicant_ids[]"]:checked')];
                if (boxes.length > 10 && event.target.matches('input[name="applicant_ids[]"]')) {
                    event.target.checked = false;
                }
                document.getElementById('tosaTopCount').textContent = topForm.querySelectorAll('input[name="applicant_ids[]"]:checked').length + ' of 10 selected';
            });
        </script>
    @endpush
@endsection
