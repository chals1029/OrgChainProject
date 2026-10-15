@extends('org.layout')

@section('title', 'TOSA · Restricted Office Access')

@section('header')
    <h1><strong>TOSA</strong></h1>
    <p class="org-welcome">Submitted applications, qualification review, and official requirements.</p>
@endsection


@section('content')
    @php
        $requirements = collect(\App\Services\TosaRequirements::SUBMISSIONS)
            ->map(fn ($document, $key) => ['key' => $key] + $document)->values()->all();
        $references = collect(\App\Services\TosaRequirements::REFERENCES)
            ->map(fn ($document, $key) => ['key' => $key] + $document)->values()->all();
        $stageLabels = collect($tosaSubsections ?? [
            'all' => 'All', 'pending' => 'Pending', 'screening' => 'Screening',
            'interview' => 'Interview', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'returned' => 'Revision',
        ])->except('all')->all();
        $yearMap = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year'];
        $tosaApplicantsJs = collect($tosaApplicants ?? [])->map(function ($applicant) use ($requirements, $yearMap, $stageLabels) {
            $year = (string) ($applicant->year_level ?? '');
            $documents = collect($requirements)->mapWithKeys(function ($requirement) use ($applicant) {
                $key = $requirement['key'];
                $path = $applicant->requirementPath($key);
                $available = $applicant->requirementFile($key) !== null;
                return [$key => [
                    'key' => $key,
                    'title' => $requirement['code'].'. '.$requirement['title'],
                    'name' => $path !== null ? (data_get($applicant->requirements, $key.'.name') ?: basename($path)) : null,
                    'url' => $available ? route('office.tosa.documents.download', ['applicant' => $applicant->id, 'key' => $key]) : null,
                    'type' => $path !== null ? strtoupper(pathinfo($path, PATHINFO_EXTENSION)) : $requirement['format'],
                ]];
            })->all();
            return [
                'id' => $applicant->id,
                'name' => $applicant->full_name,
                'studentId' => $applicant->sr_code,
                'email' => $applicant->email,
                'program' => $applicant->program,
                'yearLevel' => $yearMap[$year] ?? $year,
                'college' => $applicant->college,
                'organization' => $applicant->organization_name,
                'academicYear' => $applicant->academic_year,
                'subsection' => $applicant->subsection,
                'stage' => $stageLabels[$applicant->subsection] ?? $applicant->subsection,
                'gwa' => $applicant->gwa,
                'gwaVerified' => (bool) $applicant->gwa_verified,
                'date' => optional($applicant->created_at)->format('M j, Y'),
                'remarks' => $applicant->remarks,
                'documents' => $documents,
                'reviewUrl' => route('office.tosa.subsection', $applicant),
                'evaluation' => app(\App\Services\TosaScoringService::class)->evaluation($applicant),
                'scoreUrl' => route('office.tosa.scoring.save', $applicant),
                'workbookUrl' => route('office.tosa.workbook.show', $applicant),
                'workbookDownloadUrl' => route('office.tosa.workbook.download', $applicant),
            ];
        })->values();
        $templateDocuments = collect($references)->map(function ($reference) use ($tosaTemplates) {
            $template = ($tosaTemplates ?? [])[$reference['title']] ?? null;
            if ($template === null) {
                return null;
            }
            return [
                'key' => 'template_'.$template['id'], 'title' => $reference['title'],
                'name' => $template['name'], 'url' => $template['url'],
                'previewUrl' => $template['preview_url'] ?? null,
                'type' => $template['format'],
                'size' => $template['size'] ?? null,
            ];
        })->filter()->values();
    @endphp

    <style>
        .tosa-workspace { --tosa-ink: #30262a; --tosa-muted: #71646b; --tosa-border: #e9dfe3; min-width: 0; color: var(--tosa-ink); }
        .tosa-tabs-nav { display: flex; gap: .35rem; border-bottom: 1px solid var(--tosa-border); margin-bottom: 1rem; overflow-x: auto; }
        .tosa-tab-btn { border: 0; border-bottom: 3px solid transparent; background: transparent; color: var(--tosa-muted); font: inherit; font-weight: 700; padding: .7rem 1rem; cursor: pointer; white-space: nowrap; }
        .tosa-tab-btn.is-active { color: #8b1828; border-bottom-color: #8b1828; }
        .tosa-tab-count { font-size: .75rem; margin-left: .3rem; background: #f3e9ed; border-radius: 999px; padding: .1rem .4rem; }
        .tosa-card { background: #fff; border: 1px solid var(--tosa-border); border-radius: 12px; padding: 1rem; }
        .tosa-toolbar { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .tosa-search { flex: 1 1 240px; }
        .tosa-input { padding: .55rem .7rem; border: 1px solid #d9cdd3; border-radius: 8px; color: #30262a; font: inherit; font-size: .84rem; background: #fff; min-width: 0; }
        .tosa-search .tosa-input { width: 100%; }
        .tosa-input:focus-visible, .tosa-tab-btn:focus-visible { outline: 2px solid #8b1828; outline-offset: 2px; }
        .tosa-table-wrap { overflow-x: auto; }
        .tosa-table { width: 100%; border-collapse: collapse; min-width: 680px; font-size: .84rem; }
        .tosa-table th { text-align: left; padding: .65rem .7rem; background: #faf7f8; color: #64575f; font-size: .74rem; font-weight: 700; }
        .tosa-table td { padding: .8rem .7rem; border-bottom: 1px solid #f0e8ec; vertical-align: middle; }
        .tosa-table tr:last-child td { border-bottom: 0; }
        .tosa-table tbody tr:hover { background: #fdfbfc; }
        .tosa-name { font-weight: 700; overflow-wrap: anywhere; }
        .tosa-secondary { display: block; color: #71646b; font-size: .76rem; margin-top: .2rem; overflow-wrap: anywhere; }
        .tosa-stage { display: inline-flex; padding: .25rem .6rem; border-radius: 999px; background: #f3eef0; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .tosa-stage[data-stage="accepted"] { background: #e8f6ec; color: #25683c; }
        .tosa-stage[data-stage="screening"], .tosa-stage[data-stage="interview"] { background: #eaf2fb; color: #275b91; }
        .tosa-stage[data-stage="returned"] { background: #fff3e2; color: #8a5112; }
        .tosa-stage[data-stage="rejected"] { background: #fdebec; color: #9d2938; }
        .tosa-pagination { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; padding-top: .85rem; margin-top: .5rem; border-top: 1px solid var(--tosa-border); font-size: .8rem; color: #71646b; }
        .tosa-page-nav { display: flex; align-items: center; gap: .5rem; }
        .tosa-empty { text-align: center; padding: 2rem !important; color: #71646b; }
        .tosa-heading { font-size: 1.05rem; margin: 0 0 .35rem; font-weight: 800; }
        .tosa-note { color: #71646b; font-size: .82rem; line-height: 1.5; margin: 0 0 1rem; }
        .tosa-actions { display: flex; gap: .4rem; align-items: center; flex-wrap: wrap; }
        .tosa-template-name { max-width: 240px; }
        .tosa-modal { border: 1px solid #e9dfe3; border-radius: 14px; padding: 0; margin: auto; width: min(1000px, 94vw); max-height: 90vh; color: #30262a; }
        .tosa-modal::backdrop { background: rgba(28, 16, 23, .55); }
        .tosa-modal-body { padding: 1.25rem; }
        .tosa-modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: 1rem; }
        .tosa-close { border: 0; background: transparent; font-size: 1.6rem; cursor: pointer; color: #71646b; }
        .tosa-review-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 1.25rem; }
        .tosa-dossier-meta { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; font-size: .84rem; margin-bottom: 1rem; }
        .tosa-dossier-meta dt { color: #71646b; font-size: .74rem; }
        .tosa-dossier-meta dd { margin: .15rem 0 0; overflow-wrap: anywhere; }
        .tosa-doc-item { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; padding: .7rem 0; border-bottom: 1px solid #e9dfe3; font-size: .82rem; }
        .tosa-doc-item > div:first-child { flex: 1 1 180px; min-width: 0; }
        .tosa-field { display: block; margin-bottom: .9rem; font-size: .82rem; font-weight: 700; }
        .tosa-field .tosa-input { display: block; width: 100%; margin-top: .35rem; }
        .tosa-check { display: flex; gap: .5rem; align-items: center; margin-bottom: 1rem; font-size: .82rem; }
        .tosa-error { color: #a12638; font-size: .82rem; line-height: 1.5; }
        .tosa-feedback { font-size: .82rem; margin-top: .75rem; }
        .tosa-viewer-body { display: flex; flex-direction: column; height: 83vh; }
        .tosa-preview-frame { width: 100%; flex: 1; min-height: 0; border: 1px solid #e9dfe3; border-radius: 8px; }
        .tosa-preview-message { padding: 1rem; background: #faf7f8; border-radius: 8px; font-size: .84rem; }
        .tosa-viewer-tabs { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .7rem; }
        .tosa-scoring-section { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--tosa-border); min-width: 0; }
        .tosa-scoring-section summary { cursor: pointer; font-size: .84rem; font-weight: 700; padding: .6rem 0; }
        .tosa-scoring-section fieldset { min-width: 0; margin: 1rem 0 0; padding: .8rem; border: 1px solid var(--tosa-border); border-radius: 8px; }
        .tosa-scoring-section fieldset:disabled { opacity: .8; }
        .tosa-score-inputs, .tosa-evidence-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
        .tosa-evidence-category { border-top: 1px solid var(--tosa-border); padding: .4rem 0 .8rem; }
        .tosa-evidence-row { background: #faf7f8; padding: .75rem; margin-bottom: .6rem; border-radius: 8px; }
        .tosa-evidence-row .tosa-field { margin-bottom: 0; text-transform: none; }
        .tosa-evidence-row button { justify-self: start; }
        .tosa-scoring-section [aria-invalid="true"] { border-color: #a12638; outline: 1px solid #a12638; }
        #tosaScoreSummary { font-size: .84rem; overflow-wrap: anywhere; }
        @media (max-width: 720px) {
            .tosa-review-grid { grid-template-columns: 1fr; }
            .tosa-score-inputs, .tosa-evidence-row { grid-template-columns: minmax(0, 1fr); }
            .tosa-toolbar > select { flex: 1 1 145px; width: 100%; }
            .tosa-modal-body { padding: 1rem; }
            .tosa-card { padding: .75rem; }
            .tosa-tab-btn { padding: .65rem .75rem; }
        }
    </style>

    <div class="tosa-workspace">
        @if (session('status'))
            <p class="tosa-note" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="tosa-error" role="alert">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <nav class="tosa-tabs-nav" role="tablist" aria-label="TOSA workspace">
            <button type="button" class="tosa-tab-btn is-active" id="tabBtnApplicants" role="tab" aria-selected="true" aria-controls="tosaTabSectionApplicants" onclick="switchTosaTab('applicants')">Applicants <span class="tosa-tab-count" id="badgeAppCount">{{ $tosaApplicantsJs->count() }}</span></button>
            <button type="button" class="tosa-tab-btn" id="tabBtnRequirements" role="tab" aria-selected="false" aria-controls="tosaTabSectionRequirements" tabindex="-1" onclick="switchTosaTab('requirements')">Requirements</button>
            <button type="button" class="tosa-tab-btn" id="tabBtnTop10" role="tab" aria-selected="false" aria-controls="tosaTabSectionTop10" tabindex="-1" onclick="switchTosaTab('top10')">Top10</button>
        </nav>

        <section id="tosaTabSectionApplicants" role="tabpanel" aria-labelledby="tabBtnApplicants" class="tosa-card">
            <div class="tosa-toolbar">
                <div class="tosa-search"><input type="search" class="tosa-input" id="tosaApplicantSearch" aria-label="Search applicants" placeholder="Search name, student ID, program, or organization…" oninput="filterApplicants()"></div>
                <select class="tosa-input" id="tosaStatusFilter" aria-label="Review stage" onchange="filterApplicants()"><option value="">All review stages</option></select>
                <select class="tosa-input" id="tosaProgramFilter" aria-label="Program" onchange="filterApplicants()"><option value="">All programs</option></select>
                <select class="tosa-input" id="tosaYearFilter" aria-label="Year level" onchange="filterApplicants()"><option value="">All year levels</option></select>
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="exportApplicantReport()"><i class="bi bi-download"></i> Export roster</button>
            </div>
            <div class="tosa-table-wrap">
                <table class="tosa-table">
                    <thead><tr><th scope="col">Applicant</th><th scope="col">Program / year</th><th scope="col">Review stage</th><th scope="col">Date submitted</th><th scope="col">Dossier</th></tr></thead>
                    <tbody id="tosaApplicantsTbody"></tbody>
                </table>
            </div>
            <div class="tosa-pagination" id="tosaPagination">
                <div id="tosaPaginationInfo" role="status" aria-live="polite"></div>
                <div class="tosa-page-nav" id="tosaPaginationNav"></div>
            </div>
            <noscript><p class="tosa-error">Enable JavaScript to browse applicant dossiers and use the roster filters.</p></noscript>
        </section>

        <section id="tosaTabSectionRequirements" role="tabpanel" aria-labelledby="tabBtnRequirements" class="tosa-card" hidden>
            <h2 class="tosa-heading">Applicant checklist · Supporting credentials</h2>
            <p class="tosa-note">Applicants submit their completed TOSA Application Form and supporting credentials in the guide’s sections A–G below. “Nomination Application Form” in the guide means the same TOSA Application Form 2025.docx, not an additional form. Supporting certificates and activity records substantiate the achievements claimed; guides and the workbook are references, not applicant uploads.</p>
            <div class="tosa-table-wrap">
                <table class="tosa-table">
                    <thead><tr><th scope="col">Section</th><th scope="col">Required supporting contents</th><th scope="col">Digital upload format</th></tr></thead>
                    <tbody>
                        @foreach ($requirements as $requirement)
                            <tr>
                                <th scope="row">{{ $requirement['code'] }}</th>
                                <td><span class="tosa-name">{{ $requirement['title'] }}</span><span class="tosa-secondary">{{ $requirement['description'] }}</span></td>
                                <td>{{ $requirement['format'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <h3 class="tosa-heading" style="margin-top: 1.25rem;">Official references / templates</h3>
            <p class="tosa-note">Download TOSA Application Form 2025.docx, complete it and submit the completed version in section B. Blank forms, guides and the reference workbook are not supporting credentials or applicant scores. The computation reference has a separate read-only workbook preview.</p>
            <div class="tosa-table-wrap">
                <table class="tosa-table" id="tosaReferenceTable">
                    <thead><tr><th scope="col">Reference</th><th scope="col">Native format</th><th scope="col">Current file</th><th scope="col">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($references as $reference)
                            @php($template = ($tosaTemplates ?? [])[$reference['title']] ?? null)
                            @php($template = !empty($template['url']) ? $template : null)
                            <tr data-tosa-reference="{{ $reference['key'] }}">
                                <td><span class="tosa-name">{{ $reference['title'] }}</span></td>
                                <td>{{ $reference['format'] }}</td>
                                <td class="tosa-template-name">
                                    @if ($template)
                                        {{ $template['name'] }}
                                        <span class="tosa-secondary">{{ $template['source'] === 'bundled' ? 'Bundled official file' : 'OSO-uploaded reference' }}@if (!empty($template['size'])) · {{ $template['size'] }}@endif</span>
                                    @else
                                        <span class="tosa-secondary">Official file unavailable.</span>
                                    @endif
                                </td>
                                <td><div class="tosa-actions">
                                    @if ($template)
                                        @if ($reference['key'] === 'computation')
                                            <a class="org-btn org-btn-ghost org-btn-sm" href="{{ route('office.tosa.workbook.reference') }}" target="_blank" rel="noopener">View reference workbook</a>
                                        @endif
                                        @if ($template['format'] === 'PDF')
                                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="openTemplatePreview('{{ $template['id'] }}')">Preview PDF</button>
                                        @endif
                                        <a class="org-btn org-btn-ghost org-btn-sm" href="{{ $template['url'] }}?download=1">Download {{ $template['format'] }}</a>
                                    @endif
                                    @if ($tosaCanManageTemplates ?? false)
                                        <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="openUploadTemplateModal({{ $reference['id'] }})">{{ $template ? 'Replace reference' : 'Upload reference' }}</button>
                                    @endif
                                </div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @unless ($tosaCanManageTemplates ?? false)
                <p class="tosa-note" style="margin-top: 1rem; margin-bottom: 0;">Official reference files are managed by authorized OSO administrators. Your access is read-only.</p>
            @endunless
        </section>

        <section id="tosaTabSectionTop10" role="tabpanel" aria-labelledby="tabBtnTop10" class="tosa-card" hidden>
            <h2 class="tosa-heading">Top10 · Verified score ranking</h2>
            <p class="tosa-note">Accepted applicants with finalized, verified eligible evaluations only. Scores are ranked by the server within each academic year. Equal scores share a rank; a tie across the tenth place requires committee resolution and is not silently limited to ten students.</p>
            <label class="tosa-field" for="tosaRankingYear">Academic year
                <select class="tosa-input" id="tosaRankingYear"></select>
            </label>
            <div class="tosa-table-wrap">
                <table class="tosa-table">
                    <thead><tr><th scope="col">Rank</th><th scope="col">Candidate</th><th scope="col">Score / 100</th><th scope="col">Tie / cutoff</th><th scope="col">Dossier</th></tr></thead>
                    <tbody id="tosaTop10TableTbody"></tbody>
                </table>
            </div>
        </section>

        <dialog class="tosa-modal" id="applicantReviewModal" aria-labelledby="reviewTitle">
            <div class="tosa-modal-body">
                <header class="tosa-modal-header">
                    <div><h2 class="tosa-heading" id="reviewTitle">Applicant dossier</h2><p class="tosa-note" id="revMeta" style="margin-bottom: 0;"></p></div>
                    <button type="button" class="tosa-close" aria-label="Close applicant dossier" onclick="closeReviewModal()">&times;</button>
                </header>
                <div class="tosa-review-grid">
                    <div>
                        <dl class="tosa-dossier-meta" id="revDetails"></dl>
                        <h3 class="tosa-heading">Application documents</h3>
                        <div id="revDocListContainer"></div>
                    </div>
                    <form id="tosaReviewForm" method="post" data-confirm-message="Save this applicant qualification review?" onsubmit="saveApplicantReview(event)">
                        @csrf
                        <h3 class="tosa-heading">Qualification review</h3>
                        @unless ($tosaCanReview ?? false)<p class="tosa-note">Your TOSA clearance allows viewing only.</p>@endunless
                        <label class="tosa-field" for="revStage">Review stage
                            <select class="tosa-input" id="revStage" name="subsection" required @disabled(!($tosaCanReview ?? false))>
                                @foreach ($stageLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label class="tosa-field" for="revGwa">GWA
                            <input class="tosa-input" id="revGwa" name="gwa" type="number" min="1" max="5" step="0.01" placeholder="Not provided" @readonly(!($tosaCanReview ?? false))>
                        </label>
                        <label class="tosa-check"><input type="checkbox" id="revGwaVerified" name="gwa_verified" value="1" @disabled(!($tosaCanReview ?? false))> GWA verified by reviewer</label>
                        <label class="tosa-field" for="revRemarksInput">Reviewer remarks
                            <textarea class="tosa-input" id="revRemarksInput" name="remarks" rows="5" maxlength="1000" @readonly(!($tosaCanReview ?? false))></textarea>
                        </label>
                        @if ($tosaCanReview ?? false)
                            <button type="submit" class="org-btn org-btn-primary org-btn-sm" id="saveReviewButton">Save review</button>
                        @endif
                        <p class="tosa-feedback" id="reviewFeedback" role="status" aria-live="polite"></p>
                    </form>
                </div>
                <section class="tosa-scoring-section" aria-labelledby="tosaScoringTitle">
                    <h3 class="tosa-heading" id="tosaScoringTitle">Evaluator-verified scoring</h3>
                    <p class="tosa-note">Enter only actual achievements checked against the original A–G packet. Qualification review remains separate. Saving provisional scoring removes rank eligibility; finalization does not accept or endorse an applicant.</p>
                    @unless ($tosaCanReview ?? false)<p class="tosa-note">Read-only clearance: inspect the evidence and computation below; only authorized evaluators may save or finalize.</p>@endunless
                    <p class="tosa-note" id="tosaScoringBusy" role="status" hidden>A review or scoring save is in progress. You may inspect dossiers; editing is temporarily disabled until the server responds.</p>
                    <div id="tosaScoreSummary" aria-live="polite"></div>
                    <div class="tosa-actions" id="tosaScoreWorkbookLinks" hidden>
                        <a class="org-btn org-btn-ghost org-btn-sm" id="tosaScoreWorkbookPreview" target="_blank" rel="noopener">View computed workbook</a>
                        <a class="org-btn org-btn-ghost org-btn-sm" id="tosaScoreWorkbookDownload">Download computed XLSX</a>
                    </div>
                    <form id="tosaScoringForm" method="post">
                        @csrf
                        <fieldset id="tosaScoringFields" @disabled(!($tosaCanReview ?? false))>
                            <legend class="tosa-heading">Verified inputs</legend>
                            <div class="tosa-score-inputs">
                                <label class="tosa-field" for="tosaScoreYear">Academic year
                                    <input class="tosa-input" id="tosaScoreYear" name="academic_year" maxlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="2025-2026">
                                </label>
                                <label class="tosa-field" for="tosaScoreGwa">GWA
                                    <input class="tosa-input" id="tosaScoreGwa" name="gwa" type="number" min="1" max="5" step="0.01" placeholder="Not provided">
                                </label>
                            </div>
                            <label class="tosa-check"><input id="tosaScoreGwaVerified" type="checkbox"> GWA checked against the transcript</label>
                            <label class="tosa-check"><input id="tosaScoreEligible" type="checkbox"> Eligibility checked against the guidelines and complete original A–G packet</label>
                            <p class="tosa-note">An empty category is explicitly saved as zero verified claims. Add a row only for a real verified claim; remove unwanted rows rather than saving blanks. Finalization requires GWA ≤ 3.00, both verifications, a consecutive academic year and a complete submitted packet not returned or rejected.</p>
                            <p class="tosa-note">The server applies the corrected 2025 rubric, not the original workbook's demonstration entries or erroneous local-leadership formulas. Seminar points use verified attended days (1–5), not training titles or assumed attendance.</p>
                            <div id="tosaEvidenceCategories"></div>
                            @if ($tosaCanReview ?? false)
                                <div class="tosa-actions">
                                    <button type="submit" class="org-btn org-btn-ghost org-btn-sm" value="draft" data-confirm-message="Save provisional scoring? This removes rank eligibility.">Save provisional</button>
                                    <button type="submit" class="org-btn org-btn-primary org-btn-sm" value="finalize" data-confirm-message="Finalize this verified evaluation?">Finalize verified</button>
                                </div>
                            @endif
                        </fieldset>
                        <p id="tosaScoringFeedback" class="tosa-feedback" role="status" aria-live="polite"></p>
                    </form>
                </section>
            </div>
        </dialog>

        @if ($tosaCanManageTemplates ?? false)
            <dialog class="tosa-modal" id="uploadTemplateModal" style="max-width: 520px;" aria-labelledby="uploadTitle">
                <div class="tosa-modal-body">
                    <header class="tosa-modal-header"><h2 class="tosa-heading" id="uploadTitle">Upload official reference</h2><button type="button" class="tosa-close" aria-label="Close reference upload" onclick="document.getElementById('uploadTemplateModal').close()">&times;</button></header>
                    <form method="post" action="{{ route('office.tosa.references.store') }}" enctype="multipart/form-data" data-org-upload-form>
                        @csrf
                        <input type="hidden" id="uploadReferenceKey" name="reference_key">
                        <p class="tosa-note" id="uploadReferenceNameLabel"></p>
                        <label class="tosa-field" for="templateFileInput">Reference file
                            <input class="tosa-input" type="file" id="templateFileInput" name="template_file" required data-org-upload data-max-size="20480" data-upload-status-id="tosaTemplateUploadStatus">
                        </label>
                        <p class="tosa-note" id="templateFileFormatNote"></p>
                        <div id="tosaTemplateUploadStatus" class="org-upload-status" aria-live="polite">No file selected.</div>
                        <button type="submit" class="org-btn org-btn-primary org-btn-sm" style="margin-top: 1rem;">Save reference</button>
                    </form>
                </div>
            </dialog>
        @endif

        <dialog class="tosa-modal" id="tosaDocViewerModal" aria-labelledby="tosaDocViewerTitle">
            <div class="tosa-modal-body tosa-viewer-body">
                <header class="tosa-modal-header">
                    <div><h2 class="tosa-heading" id="tosaDocViewerTitle">Document preview</h2><p class="tosa-note" id="tosaDocViewerSubtitle" style="margin-bottom: 0;"></p></div>
                    <div class="tosa-actions">
                        <a id="tosaDocViewerDownloadBtn" class="org-btn org-btn-ghost org-btn-sm">Download</a>
                        <a id="tosaDocViewerNewTabBtn" class="org-btn org-btn-ghost org-btn-sm" target="_blank" rel="noopener">Open in new tab</a>
                        <button type="button" class="tosa-close" aria-label="Close document preview" onclick="closeTosaDocViewer()">&times;</button>
                    </div>
                </header>
                <div class="tosa-viewer-tabs" id="tosaDocViewerTabs" aria-label="Available documents"></div>
                <p class="tosa-preview-message" id="tosaDocViewerMessage" role="status" aria-live="polite"></p>
                <iframe class="tosa-preview-frame" id="tosaDocViewerIframe" title="TOSA document preview" hidden></iframe>
            </div>
        </dialog>
    </div>

    <script src="{{ asset('js/tosa-scoring.js') }}?v={{ filemtime(public_path('js/tosa-scoring.js')) }}"></script>
    <script>
        const TOSA_CAN_REVIEW = @json($tosaCanReview ?? false);
        const TOSA_CAN_MANAGE_TEMPLATES = @json($tosaCanManageTemplates ?? false);
        const tosaApplicants = @json($tosaApplicantsJs);
        const tosaReferences = @json($references);
        const tosaTemplates = @json($templateDocuments);
        const tosaStageLabels = @json($stageLabels);
        const PAGE_SIZE = 10;
        let currentApplicantPage = 1;
        let activeReviewApplicant = null;
        let activeViewerDocList = [];
        let previewUrl = null;
        let previewRequest = 0;

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
        }

        function switchTosaTab(tab) {
            ['applicants', 'requirements', 'top10'].forEach(key => {
                const suffix = key.charAt(0).toUpperCase() + key.slice(1);
                const active = key === tab;
                document.getElementById('tosaTabSection' + suffix).hidden = !active;
                const button = document.getElementById('tabBtn' + suffix);
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', String(active));
                button.tabIndex = active ? 0 : -1;
            });
            if (tab === 'top10') renderTop10Table();
        }

        document.querySelector('.tosa-tabs-nav').addEventListener('keydown', event => {
            const tabs = ['applicants', 'requirements', 'top10'];
            const current = tabs.findIndex(tab => event.target.id === 'tabBtn' + tab.charAt(0).toUpperCase() + tab.slice(1));
            if (current < 0 || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const index = event.key === 'Home' ? 0 : event.key === 'End' ? 2 : (current + (event.key === 'ArrowRight' ? 1 : 2)) % 3;
            switchTosaTab(tabs[index]);
            document.querySelector('.tosa-tabs-nav [aria-selected="true"]').focus();
        });

        function populateApplicantFilters() {
            const stageSelect = document.getElementById('tosaStatusFilter');
            const selectedStage = stageSelect.value;
            const stages = [...new Set(tosaApplicants.map(applicant => applicant.subsection).filter(Boolean))];
            stageSelect.innerHTML = '<option value="">All review stages</option>' + stages.map(stage => `<option value="${escapeHtml(stage)}">${escapeHtml(tosaStageLabels[stage] || stage)}</option>`).join('');
            // Keep the selected stage available when the last applicant moves to another stage.
            if (selectedStage && !stages.includes(selectedStage)) {
                stageSelect.add(new Option(tosaStageLabels[selectedStage] || selectedStage, selectedStage));
            }
            stageSelect.value = selectedStage;
            [['tosaProgramFilter', 'program', 'All programs'], ['tosaYearFilter', 'yearLevel', 'All year levels']].forEach(([id, field, label]) => {
                const select = document.getElementById(id);
                const value = select.value;
                const options = [...new Set(tosaApplicants.map(applicant => applicant[field]).filter(Boolean))].sort((a, b) => a.localeCompare(b));
                select.innerHTML = `<option value="">${label}</option>` + options.map(option => `<option value="${escapeHtml(option)}">${escapeHtml(option)}</option>`).join('');
                select.value = value;
            });
        }

        function filteredApplicants() {
            const search = document.getElementById('tosaApplicantSearch').value.toLowerCase().trim();
            const stage = document.getElementById('tosaStatusFilter').value;
            const program = document.getElementById('tosaProgramFilter').value;
            const year = document.getElementById('tosaYearFilter').value;
            return tosaApplicants.filter(applicant =>
                (!search || [applicant.name, applicant.studentId, applicant.program, applicant.organization, applicant.college].some(value => String(value || '').toLowerCase().includes(search))) &&
                (!stage || applicant.subsection === stage) && (!program || applicant.program === program) && (!year || applicant.yearLevel === year)
            );
        }

        function filterApplicants() {
            currentApplicantPage = 1;
            renderApplicantsTable();
        }

        function dossierButton(applicant) {
            return `<button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="openReviewModal(${Number(applicant.id)})">${TOSA_CAN_REVIEW ? 'Review' : 'View dossier'}</button>`;
        }

        function renderApplicantsTable() {
            const applicants = filteredApplicants();
            const pages = Math.max(1, Math.ceil(applicants.length / PAGE_SIZE));
            currentApplicantPage = Math.min(currentApplicantPage, pages);
            const start = (currentApplicantPage - 1) * PAGE_SIZE;
            const rows = applicants.slice(start, start + PAGE_SIZE);
            document.getElementById('tosaApplicantsTbody').innerHTML = rows.length ? rows.map(applicant => `
                <tr>
                    <td><span class="tosa-name">${escapeHtml(applicant.name)}</span><span class="tosa-secondary">${escapeHtml(applicant.studentId || 'Student ID not provided')}</span></td>
                    <td>${escapeHtml(applicant.program || 'Not provided')}<span class="tosa-secondary">${escapeHtml(applicant.yearLevel || '')}</span></td>
                    <td><span class="tosa-stage" data-stage="${escapeHtml(applicant.subsection)}">${escapeHtml(applicant.stage)}</span></td>
                    <td>${escapeHtml(applicant.date || 'Not recorded')}</td>
                    <td>${dossierButton(applicant)}</td>
                </tr>`).join('') : `<tr><td colspan="5" class="tosa-empty">${tosaApplicants.length ? 'No applicants match these filters.' : 'No submitted applications yet.'}</td></tr>`;
            document.getElementById('tosaPaginationInfo').textContent = applicants.length ? `Showing ${start + 1}–${Math.min(start + PAGE_SIZE, applicants.length)} of ${applicants.length} submitted applicants` : '0 applicants';
            document.getElementById('tosaPaginationNav').innerHTML = applicants.length ? `
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="goToApplicantPage(${currentApplicantPage - 1})" ${currentApplicantPage === 1 ? 'disabled' : ''} aria-label="Previous applicant page">Previous</button>
                <span>Page ${currentApplicantPage} of ${pages}</span>
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="goToApplicantPage(${currentApplicantPage + 1})" ${currentApplicantPage === pages ? 'disabled' : ''} aria-label="Next applicant page">Next</button>` : '';
        }

        function goToApplicantPage(page) {
            currentApplicantPage = Math.max(1, page);
            renderApplicantsTable();
        }

        function exportApplicantReport() {
            const applicants = filteredApplicants();
            const rows = [['Name', 'Student ID', 'Program', 'Year', 'Organization', 'Review stage', 'GWA', 'GWA verified', 'Date submitted']];
            applicants.forEach(applicant => rows.push([applicant.name, applicant.studentId, applicant.program, applicant.yearLevel, applicant.organization, applicant.stage, applicant.gwa, applicant.gwaVerified ? 'Yes' : 'No', applicant.date]));
            const csv = rows.map(row => row.map(value => {
                const text = String(value ?? '');
                const safe = /^[=+\-@\t\r]/.test(text) ? "'" + text : text;
                return '"' + safe.replace(/"/g, '""') + '"';
            }).join(',')).join('\r\n');
            const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' }));
            const link = document.createElement('a');
            link.href = url;
            link.download = 'TOSA_Applicant_Roster.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }

        function renderTop10Table() {
            window.TosaScoring.renderRanking();
        }

        function documentItems(documents) {
            return documents.map(doc => `<div class="tosa-doc-item">
                <div><span class="tosa-name">${escapeHtml(doc.title)}</span><span class="tosa-secondary">${escapeHtml(doc.name || '')} · ${escapeHtml(doc.type)}</span></div>
                ${doc.url ? `<div class="tosa-actions">${['PDF', 'PNG', 'JPG', 'JPEG'].includes(doc.type) ? `<button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="viewTosaDocPreview('${escapeHtml(doc.key)}')">Preview</button>` : ''}<a class="org-btn org-btn-ghost org-btn-sm" href="${escapeHtml(downloadUrl(doc.url))}">Download</a></div>` : '<span class="tosa-error">Document unavailable.</span>'}
            </div>`).join('');
        }

        function openReviewModal(id) {
            const applicant = tosaApplicants.find(item => item.id === id);
            if (!applicant) return;
            activeReviewApplicant = applicant;
            const documents = Object.values(applicant.documents);
            activeViewerDocList = documents.filter(doc => doc.url);
            document.getElementById('reviewTitle').textContent = applicant.name;
            document.getElementById('revMeta').textContent = [applicant.studentId, applicant.program, applicant.yearLevel].filter(Boolean).join(' · ');
            const details = [['Email', applicant.email], ['College', applicant.college], ['Organization', applicant.organization], ['Academic year', applicant.academicYear], ['Date submitted', applicant.date], ['Review stage', applicant.stage]];
            document.getElementById('revDetails').innerHTML = details.map(([label, value]) => `<div><dt>${label}</dt><dd>${escapeHtml(value || 'Not provided')}</dd></div>`).join('');
            document.getElementById('revDocListContainer').innerHTML = documentItems(documents);
            document.getElementById('tosaReviewForm').action = applicant.reviewUrl;
            document.getElementById('revStage').value = applicant.subsection;
            document.getElementById('revGwa').value = applicant.gwa ?? '';
            document.getElementById('revGwaVerified').checked = applicant.gwaVerified;
            document.getElementById('revRemarksInput').value = applicant.remarks || '';
            document.getElementById('reviewFeedback').textContent = '';
            window.TosaScoring.open(applicant);
            document.getElementById('applicantReviewModal').showModal();
        }

        function closeReviewModal() {
            document.getElementById('applicantReviewModal').close();
            activeReviewApplicant = null;
            window.TosaScoring.close();
        }

        async function saveApplicantReview(event) {
            event.preventDefault();
            if (!TOSA_CAN_REVIEW || !activeReviewApplicant) return;
            if (!window.TosaScoring.beginReview(activeReviewApplicant)) return;
            const reviewSession = window.TosaScoring.session();
            const applicant = activeReviewApplicant;
            const button = document.getElementById('saveReviewButton');
            const feedback = document.getElementById('reviewFeedback');
            const payload = {
                subsection: document.getElementById('revStage').value,
                gwa: document.getElementById('revGwa').value === '' ? null : Number(document.getElementById('revGwa').value),
                gwa_verified: document.getElementById('revGwaVerified').checked,
                remarks: document.getElementById('revRemarksInput').value.trim(),
            };
            button.disabled = true;
            feedback.classList.remove('tosa-error');
            feedback.textContent = 'Saving review…';
            try {
                const response = await fetch(applicant.reviewUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('#tosaReviewForm input[name="_token"]').value },
                    body: JSON.stringify(payload),
                });
                const result = await response.json();
                if (!response.ok || !result.ok) {
                    const validation = Object.values(result.errors || {}).flat().join(' ');
                    throw new Error(validation || 'The review could not be saved. Your changes have not been applied.');
                }
                Object.assign(applicant, {
                    subsection: result.subsection, stage: tosaStageLabels[result.subsection] || result.subsection,
                    gwa: result.gwa, gwaVerified: result.gwaVerified, remarks: result.remarks,
                    academicYear: result.academicYear, evaluation: result.evaluation,
                });
                window.TosaScoring.applyReview(applicant, result);
                populateApplicantFilters();
                renderApplicantsTable();
                renderTop10Table();
                if (activeReviewApplicant === applicant && window.TosaScoring.session() === reviewSession) {
                    feedback.textContent = 'Review saved.';
                    document.querySelector('#revDetails div:last-child dd').textContent = applicant.stage;
                }
            } catch (error) {
                if (activeReviewApplicant === applicant && window.TosaScoring.session() === reviewSession) {
                    feedback.classList.add('tosa-error');
                    feedback.textContent = error instanceof SyntaxError || error instanceof TypeError ? 'The review could not be saved. Check your connection or session and try again.' : error.message;
                }
            } finally {
                window.TosaScoring.endReview(applicant);
            }
        }

        function openUploadTemplateModal(id) {
            if (!TOSA_CAN_MANAGE_TEMPLATES) return;
            const reference = tosaReferences.find(item => item.id === id);
            if (!reference) return;
            document.getElementById('uploadReferenceKey').value = reference.key;
            document.getElementById('uploadReferenceNameLabel').textContent = reference.title;
            document.getElementById('templateFileFormatNote').textContent = reference.format + ' only; maximum 20 MB.';
            const input = document.getElementById('templateFileInput');
            input.accept = reference.extensions.map(extension => '.' + extension).join(',');
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('uploadTemplateModal').showModal();
        }

        function openTemplatePreview(id) {
            activeViewerDocList = tosaTemplates;
            viewTosaDocPreview('template_' + id);
        }

        function downloadUrl(url) {
            const target = new URL(url, window.location.origin);
            target.searchParams.set('download', '1');
            return target.href;
        }

        async function viewTosaDocPreview(key) {
            const doc = activeViewerDocList.find(item => item.key === key);
            if (!doc || !doc.url) return;
            const request = ++previewRequest;
            const modal = document.getElementById('tosaDocViewerModal');
            const frame = document.getElementById('tosaDocViewerIframe');
            const message = document.getElementById('tosaDocViewerMessage');
            frame.hidden = true;
            frame.removeAttribute('src');
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
            document.getElementById('tosaDocViewerTitle').textContent = doc.title;
            document.getElementById('tosaDocViewerSubtitle').textContent = doc.name || '';
            document.getElementById('tosaDocViewerDownloadBtn').href = downloadUrl(doc.url);
            const newTabButton = document.getElementById('tosaDocViewerNewTabBtn');
            newTabButton.hidden = !['PDF', 'PNG', 'JPG', 'JPEG'].includes(doc.type);
            newTabButton.href = doc.previewUrl || doc.url;
            document.getElementById('tosaDocViewerTabs').innerHTML = activeViewerDocList.filter(item => ['PDF', 'PNG', 'JPG', 'JPEG'].includes(item.type)).map(item => `<button type="button" class="org-btn org-btn-ghost org-btn-sm" aria-pressed="${item.key === key}" onclick="viewTosaDocPreview('${escapeHtml(item.key)}')">${escapeHtml(item.title)}</button>`).join('');
            message.hidden = false;
            message.classList.remove('tosa-error');
            if (!modal.open) modal.showModal();
            if (!['PDF', 'PNG', 'JPG', 'JPEG'].includes(doc.type)) {
                message.textContent = 'This file format cannot be previewed here. Download the official file to open it in a compatible application.';
                return;
            }
            message.textContent = 'Loading document…';
            try {
                const response = await fetch(doc.previewUrl || doc.url, { headers: { 'Accept': doc.type === 'PDF' ? 'application/pdf' : 'image/*' } });
                if (!response.ok) throw new Error('Document unavailable (HTTP ' + response.status + ').');
                const blob = await response.blob();
                const expectedType = doc.type === 'PDF' ? blob.type === 'application/pdf' : blob.type.startsWith('image/');
                if (!blob.size || !expectedType) throw new Error('The server did not return a readable document. Your session may have expired.');
                if (request !== previewRequest || !modal.open) return;
                previewUrl = URL.createObjectURL(blob);
                frame.src = previewUrl;
                frame.hidden = false;
                message.hidden = true;
            } catch (error) {
                if (request !== previewRequest || !modal.open) return;
                message.classList.add('tosa-error');
                message.textContent = error instanceof TypeError ? 'Unable to load this document. Check your connection or session.' : error.message;
            }
        }

        function releaseDocumentPreview() {
            ++previewRequest;
            document.getElementById('tosaDocViewerIframe').removeAttribute('src');
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        function closeTosaDocViewer() {
            document.getElementById('tosaDocViewerModal').close();
        }

        document.getElementById('tosaDocViewerModal').addEventListener('close', releaseDocumentPreview);
        document.getElementById('applicantReviewModal').addEventListener('close', () => { activeReviewApplicant = null; });
        populateApplicantFilters();
        const initialStage = @json(($selectedSubsection ?? 'all') === 'all' ? '' : $selectedSubsection);
        if (initialStage && ![...document.getElementById('tosaStatusFilter').options].some(option => option.value === initialStage)) {
            document.getElementById('tosaStatusFilter').add(new Option(tosaStageLabels[initialStage] || initialStage, initialStage));
        }
        document.getElementById('tosaStatusFilter').value = initialStage;
        renderApplicantsTable();
        window.TosaScoring.init({
            applicants: tosaApplicants,
            ranking: @json($tosaScoringRanking ?? []),
            canReview: TOSA_CAN_REVIEW,
            escapeHtml, dossierButton,
            onSaved(applicant) {
                document.getElementById('revGwa').value = applicant.gwa ?? '';
                document.getElementById('revGwaVerified').checked = applicant.gwaVerified;
                document.getElementById('revStage').value = applicant.subsection;
                document.getElementById('revRemarksInput').value = applicant.remarks || '';
                document.querySelector('#revDetails div:last-child dd').textContent = applicant.stage;
                const academicYear = document.querySelector('#revDetails div:nth-child(4) dd');
                if (academicYear) academicYear.textContent = applicant.academicYear || 'Not provided';
                renderApplicantsTable();
            },
        });
        renderTop10Table();
    </script>
@endsection
