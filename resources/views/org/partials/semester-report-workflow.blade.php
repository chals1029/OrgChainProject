@php
    $reportType = $reportType ?? 'ar';
    $reportTitle = $reportType === 'fr' ? 'Financial Report' : 'Accomplishment Report';
    $bundle = $reportBundle ?? [
        'organization' => '',
        'semester' => $selectedSemester ?? '1st Semester',
        'academic_year' => $selectedYear ?? '2025-2026',
        'state' => 'draft',
        'state_label' => 'Draft — not submitted',
        'documents' => ['ar' => collect(), 'fr' => collect()],
        'has_both_documents' => false,
    ];
    $role = $office->office_role ?? '';
    $selectedOrganization = $bundle['organization'] ?? ($selectedOrganization ?? '');
    $selectedSemester = $bundle['semester'] ?? ($selectedSemester ?? '1st Semester');
    $selectedYear = $bundle['academic_year'] ?? ($selectedYear ?? '2025-2026');
    $bundleStatusClass = match ($bundle['state'] ?? 'draft') {
        'oso_review' => 'is-review',
        'returned' => 'is-returned',
        'verified' => 'is-verified',
        'archived' => 'is-archived',
        default => 'is-draft',
    };
$documentGroups = $bundle['documents'] ?? [];
$currentDocuments = $documentGroups[$reportType] ?? collect();
$documentInputId = 'semester-report-document-'.$reportType;
@endphp

<style>
    .org-semester-report-card { margin: 0 0 1.25rem; padding: 1.1rem 1.25rem; border: 1.5px solid #f0e6e8; border-radius: 18px; background: #fff; box-shadow: 0 4px 16px rgba(90,15,30,.03); }
    .org-semester-report-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; }
    .org-semester-report-head h3 { margin:0; color:#1a1618; font-size:1rem; display:flex; align-items:center; gap:.45rem; }
    .org-semester-report-head p { margin:.3rem 0 0; color:#7a7074; font-size:.78rem; line-height:1.45; }
    .org-semester-report-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #eadcdf; border-radius:999px; padding:.3rem .68rem; font-size:.7rem; font-weight:800; white-space:nowrap; }
    .org-semester-report-pill.is-draft { background:#f8f5f6; color:#665c60; }
    .org-semester-report-pill.is-review { background:#fff7ed; color:#b45309; border-color:#fed7aa; }
    .org-semester-report-pill.is-returned { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
    .org-semester-report-pill.is-verified, .org-semester-report-pill.is-archived { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
    .org-semester-report-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.7rem; margin-top:.9rem; }
    .org-semester-report-grid label { display:grid; gap:.25rem; color:#554d50; font-size:.72rem; font-weight:800; }
    .org-semester-report-grid select, .org-semester-report-grid input, .org-semester-report-note { width:100%; box-sizing:border-box; padding:.5rem .65rem; border:1px solid #e8dedf; border-radius:9px; background:#fff; color:#30282b; font:inherit; font-size:.8rem; }
    .org-semester-report-grid select:focus, .org-semester-report-grid input:focus, .org-semester-report-note:focus { outline:2px solid rgba(139,24,40,.13); border-color:#b86370; }
    .org-semester-report-actions { display:flex; align-items:center; flex-wrap:wrap; gap:.55rem; margin-top:.85rem; }
    .org-semester-report-actions form { margin:0; }
    .org-semester-report-docs { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.85rem; }
    .org-semester-report-doc { display:inline-flex; align-items:center; gap:.35rem; max-width:100%; padding:.35rem .55rem; border:1px solid #f0e6e8; border-radius:9px; background:#fffafb; font-size:.72rem; color:#554d50; }
    .org-semester-report-doc a { color:#8b1828; font-weight:800; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:230px; }
    .org-semester-report-help { margin:.65rem 0 0; color:#7a7074; font-size:.74rem; line-height:1.45; }
    .org-semester-report-file-picker { display:flex; align-items:center; gap:.65rem; flex:1 1 310px; min-width:min(100%, 290px); min-height:42px; box-sizing:border-box; padding:.3rem .45rem; border:1px solid #eadcdf; border-radius:11px; background:#fffafb; }
    .org-semester-report-file-picker:focus-within { border-color:#a51e36; box-shadow:0 0 0 3px rgba(165,30,54,.12); }
    .org-semester-report-file-trigger { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; flex:0 0 auto; min-height:34px; box-sizing:border-box; padding:.45rem .72rem; border-radius:8px; background:#8b1828; color:#fff; cursor:pointer; font-size:.74rem; font-weight:850; transition:background .15s ease, transform .15s ease; }
    .org-semester-report-file-trigger:hover { background:#701321; transform:translateY(-1px); }
    .org-semester-report-file-trigger:active { transform:translateY(0); }
    .org-semester-report-file-input { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; clip-path:inset(50%); }
    .org-semester-report-file-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#7a7074; font-size:.73rem; font-weight:650; }
    .org-semester-report-file-picker.is-selected { border-color:#86c99b; background:#f6fff8; }
    .org-semester-report-file-picker.is-selected .org-semester-report-file-name { color:#166534; font-weight:800; }
    .org-semester-report-queue { display:grid; gap:.65rem; margin-top:1rem; padding-top:1rem; border-top:1px solid #f3eaec; }
    .org-semester-report-queue h4 { margin:0; font-size:.84rem; color:#30282b; }
    .org-semester-report-queue-item { padding:.75rem; border:1px solid #f0e6e8; border-radius:12px; background:#fffafb; }
    .org-semester-report-queue-top { display:flex; justify-content:space-between; align-items:flex-start; gap:.7rem; flex-wrap:wrap; }
    .org-semester-report-queue-top strong { color:#30282b; font-size:.82rem; }
    .org-semester-report-queue-top small { display:block; margin-top:.15rem; color:#7a7074; font-size:.7rem; }
    .org-semester-report-queue-actions { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.6rem; }
    .org-semester-report-queue-actions form { display:flex; gap:.4rem; flex:1 1 240px; margin:0; }
    .org-semester-report-queue-actions textarea { flex:1; min-height:34px; resize:vertical; padding:.4rem .5rem; border:1px solid #e8dedf; border-radius:8px; font:inherit; font-size:.74rem; }
    .org-semester-report-error { margin-top:.7rem; padding:.55rem .7rem; border-radius:9px; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; font-size:.76rem; }
    .org-semester-report-lock { margin-top:.7rem; padding:.65rem .75rem; border:1px solid #fed7aa; border-radius:9px; background:#fff7ed; color:#9a3412; font-size:.76rem; line-height:1.45; }
    @media (max-width:760px) { .org-semester-report-grid { grid-template-columns:1fr; } .org-semester-report-queue-actions form { flex-basis:100%; } }
</style>

<section class="org-semester-report-card" aria-label="AR and FR semester workflow">
    <div class="org-semester-report-head">
        <div>
            <h3><i class="bi bi-arrow-left-right" style="color:#8b1828;"></i> Semester AR + FR submission</h3>
            <p>SO stages both reports, submits them together to OSO, and OSO accepts or returns the complete package. SDO and OVCAA do not review AR/FR.</p>
        </div>
        <span class="org-semester-report-pill {{ $bundleStatusClass }}"><i class="bi bi-circle-fill" style="font-size:.42rem;"></i> {{ $bundle['state_label'] ?? 'Draft — not submitted' }}</span>
    </div>

    <form method="GET" action="{{ $reportType === 'fr' ? route('office.financial') : route('office.accomplishment') }}" class="org-semester-report-grid" style="align-items:end;">
        <label>
            Organization
            <select name="organization" aria-label="Report organization">
                <option value="">Select organization</option>
                @foreach (($organizations ?? collect()) as $organizationName)
                    <option value="{{ $organizationName }}" @selected($selectedOrganization === $organizationName)>{{ $organizationName }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Semester
            <select name="semester" aria-label="Report semester">
                @foreach (['1st Semester', '2nd Semester', 'Midyear'] as $periodSemester)
                    <option value="{{ $periodSemester }}" @selected($selectedSemester === $periodSemester)>{{ $periodSemester }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Academic year
            <select name="academic_year" aria-label="Report academic year">
                @foreach (['2024-2025', '2025-2026', '2026-2027'] as $periodYear)
                    <option value="{{ $periodYear }}" @selected($selectedYear === $periodYear)>{{ $periodYear }}</option>
                @endforeach
            </select>
        </label>
        <div class="org-semester-report-actions" style="grid-column:1 / -1; margin-top:0;">
            <button type="submit" class="org-btn org-btn-outline"><i class="bi bi-funnel"></i> Load package</button>
            @if ($selectedOrganization !== '')
                <span style="font-size:.74rem;color:#7a7074;">{{ $selectedOrganization }} · {{ $selectedSemester }} · {{ $selectedYear }}</span>
            @endif
        </div>
    </form>

    @if ($selectedOrganization !== '')
        <div class="org-semester-report-docs" aria-label="Staged report documents">
            <strong style="font-size:.74rem;color:#554d50;align-self:center;">{{ strtoupper($reportType) }} documents:</strong>
            @forelse ($currentDocuments as $document)
                <span class="org-semester-report-doc">
                    <i class="bi bi-file-earmark-text" style="color:#8b1828;"></i>
                    <a href="{{ route('office.reports.documents.view', $document) }}" target="_blank" rel="noopener">{{ $document->name ?: $document->original_name }}</a>
                    <a href="{{ route('office.reports.documents.view', ['document' => $document, 'download' => 1]) }}" title="Download" aria-label="Download {{ $document->original_name }}"><i class="bi bi-download"></i></a>
                </span>
            @empty
                <span style="font-size:.74rem;color:#7a7074;">No staged {{ strtoupper($reportType) }} document yet.</span>
            @endforelse
        </div>

        @if ($role === 'so')
            @if (($bundle['state'] ?? 'draft') === 'returned' && !empty($bundle['notes']))
                <div class="org-semester-report-error"><strong>OSO revision note:</strong> {{ $bundle['notes'] }}</div>
            @endif
            @if (($bundle['state'] ?? 'draft') === 'oso_review')
                <div class="org-semester-report-lock">
                    <i class="bi bi-lock-fill"></i>
                    <strong>Package locked while waiting for OSO.</strong>
                    @if (!empty($bundle['oso_opened_at']))
                        OSO opened the submitted AR + FR files on {{ optional($bundle['oso_opened_at'])->format('M j, Y g:i A') }}. The package remains locked until OSO returns or accepts it.
                    @else
                        OSO has not opened the submitted AR + FR files yet. You cannot replace or resubmit them while they are in review.
                    @endif
                </div>
            @elseif (in_array(($bundle['state'] ?? 'draft'), ['verified', 'archived'], true))
                <div class="org-semester-report-help"><i class="bi bi-lock-fill"></i> This package is complete and locked.</div>
            @else
                <form method="POST" action="{{ route('office.reports.documents.store', $reportType) }}" enctype="multipart/form-data" class="org-semester-report-actions">
                    @csrf
                    <input type="hidden" name="organization_name" value="{{ $selectedOrganization }}">
                    <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                    <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                    <div class="org-semester-report-file-picker" data-report-file-picker>
                        <label class="org-semester-report-file-trigger" for="{{ $documentInputId }}">
                            <i class="bi bi-folder2-open" aria-hidden="true"></i>
                            <span>Choose {{ strtoupper($reportType) }} file</span>
                        </label>
                        <input id="{{ $documentInputId }}" class="org-semester-report-file-input" type="file" name="document" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg" aria-describedby="{{ $documentInputId }}-name">
                        <span class="org-semester-report-file-name" id="{{ $documentInputId }}-name" data-file-name>No file selected</span>
                    </div>
                    <input type="text" name="name" maxlength="255" placeholder="Document title (optional)" style="min-width:180px;padding:.5rem .65rem;border:1px solid #e8dedf;border-radius:9px;font:inherit;font-size:.78rem;">
                    <button type="submit" class="org-btn org-btn-primary"><i class="bi bi-cloud-arrow-up-fill"></i> Stage {{ strtoupper($reportType) }} document</button>
                </form>

                <form method="POST" action="{{ route('office.reports.semester.submit') }}" class="org-semester-report-actions">
                    @csrf
                    <input type="hidden" name="organization_name" value="{{ $selectedOrganization }}">
                    <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                    <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
                    <input type="hidden" name="return_type" value="{{ $reportType }}">
                    <button type="submit" class="org-btn org-btn-primary" @disabled(!($bundle['has_both_documents'] ?? false))><i class="bi bi-send-fill"></i> Submit AR + FR to OSO</button>
                    @if (!($bundle['has_both_documents'] ?? false))
                        <span class="org-semester-report-help">Stage at least one AR document and one FR document before submitting the package.</span>
                    @endif
                </form>
            @endif
        @elseif ($role === 'oso' && ($bundle['state'] ?? '') === 'oso_review')
            <div class="org-semester-report-help"><i class="bi bi-shield-check"></i> This package is in the OSO queue. Open either submitted file above to mark the package opened, then use the review actions below to return it to SO or accept and archive it.</div>
        @endif
    @else
        <p class="org-semester-report-help"><i class="bi bi-info-circle"></i> Select an organization and reporting period to stage or review the paired AR + FR package.</p>
    @endif

    @if ($role === 'oso' && !empty($reportQueue))
        <div class="org-semester-report-queue" aria-label="OSO semester report review queue">
            <h4><i class="bi bi-inbox-fill" style="color:#8b1828;"></i> OSO review queue ({{ count($reportQueue) }})</h4>
            @foreach ($reportQueue as $queueBundle)
                <div class="org-semester-report-queue-item">
                    <div class="org-semester-report-queue-top">
                        <div>
                            <strong>{{ $queueBundle['organization'] }}</strong>
                            <small>{{ $queueBundle['semester'] }} · {{ $queueBundle['academic_year'] }} · AR {{ count($queueBundle['documents']['ar'] ?? []) }} doc(s) · FR {{ count($queueBundle['documents']['fr'] ?? []) }} doc(s)</small>
                        </div>
                        <span class="org-semester-report-pill is-review">OSO Review</span>
                    </div>
                    <div class="org-semester-report-docs">
                        @foreach (['ar' => 'AR', 'fr' => 'FR'] as $queueType => $queueLabel)
                            @foreach (($queueBundle['documents'][$queueType] ?? []) as $queueDocument)
                                <span class="org-semester-report-doc">
                                    <strong style="font-size:.66rem;color:#8b1828;">{{ $queueLabel }}</strong>
                                    <a href="{{ route('office.reports.documents.view', $queueDocument) }}" target="_blank" rel="noopener">{{ $queueDocument->name ?: $queueDocument->original_name }}</a>
                                    <a href="{{ route('office.reports.documents.view', ['document' => $queueDocument, 'download' => 1]) }}" title="Download" aria-label="Download {{ $queueDocument->original_name }}"><i class="bi bi-download"></i></a>
                                </span>
                            @endforeach
                        @endforeach
                    </div>
                    <div class="org-semester-report-queue-actions">
                        <form method="POST" action="{{ route('office.reports.semester.review') }}">
                            @csrf
                            <input type="hidden" name="organization_name" value="{{ $queueBundle['organization'] }}">
                            <input type="hidden" name="semester" value="{{ $queueBundle['semester'] }}">
                            <input type="hidden" name="academic_year" value="{{ $queueBundle['academic_year'] }}">
                            <input type="hidden" name="decision" value="return">
                            <textarea name="notes" placeholder="Revision note for SO (optional)"></textarea>
                            <button type="submit" class="org-btn org-btn-outline" style="color:#b91c1c;border-color:#fecaca;"><i class="bi bi-arrow-return-left"></i> Return to SO</button>
                        </form>
                        <form method="POST" action="{{ route('office.reports.semester.review') }}">
                            @csrf
                            <input type="hidden" name="organization_name" value="{{ $queueBundle['organization'] }}">
                            <input type="hidden" name="semester" value="{{ $queueBundle['semester'] }}">
                            <input type="hidden" name="academic_year" value="{{ $queueBundle['academic_year'] }}">
                            <input type="hidden" name="decision" value="accept">
                            <input type="hidden" name="notes" value="Accepted by OSO.">
                            <button type="submit" class="org-btn org-btn-primary"><i class="bi bi-check2-circle"></i> Accept &amp; archive</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($errors->has('report'))
        <div class="org-semester-report-error">{{ $errors->first('report') }}</div>
    @endif
</section>

<script>
    document.querySelectorAll('[data-report-file-picker]').forEach((picker) => {
        const input = picker.querySelector('input[type="file"]');
        const filename = picker.querySelector('[data-file-name]');
        if (!input || !filename || input.dataset.bound === 'true') return;

        input.dataset.bound = 'true';
        input.addEventListener('change', () => {
            const selectedFile = input.files && input.files[0];
            picker.classList.toggle('is-selected', Boolean(selectedFile));
            filename.textContent = selectedFile ? selectedFile.name : 'No file selected';
        });
    });
</script>
