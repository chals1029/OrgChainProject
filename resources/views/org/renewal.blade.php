@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSo = $role === 'so';
    $window = $renewalWindow ?? null;
    $isOpen = (bool) ($renewalIsOpen ?? false);
    $docs = $requiredDocs ?? [];
    $defaultChecklist = array_column($docs, 'key') === array_column(\App\Models\OrgRenewalWindow::defaultRequiredDocs(), 'key');
    $my = $myRenewalSubmission ?? null;
    $uploadedDocs = $my ? $my->storedDocuments() : collect();
    $uploadedKeys = $uploadedDocs->keys()->all();
    $pct = count($docs) ? (int) round(100 * count(array_intersect(array_column($docs, 'key'), $uploadedKeys)) / count($docs)) : 0;
@endphp

@section('title', 'Organization Renewal')

@section('header')
    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
        <h1><strong>{{ $isOso ? 'Renewal Dashboard' : 'Organization Renewal' }}</strong></h1>
        @if ($isOso && $window)
            <span class="rn-pill" style="border:1px solid #f0e0e3;color:#8b1828;">AY {{ $window->academic_year }}</span>
        @elseif ($isOpen)
            <span style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.25rem 0.65rem;border-radius:9999px;font-size:0.72rem;font-weight:800;background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;">
                <i class="bi bi-unlock-fill"></i> OPEN
            </span>
        @else
            <span style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.25rem 0.65rem;border-radius:9999px;font-size:0.72rem;font-weight:800;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                <i class="bi bi-lock-fill"></i> LOCKED
            </span>
        @endif
    </div>
    <p class="org-welcome">
        @if ($isOso)
            Official organization standing and renewal applications for the current academic year.
        @elseif ($isSo)
            Complete the required organization details and {{ $defaultChecklist ? 'Attachments A–J' : 'official renewal requirements' }}.
        @else
            Submit your renewal packet when OSO opens the filing window. Adviser and Dean names are recorded for the approval chain.
        @endif
    </p>
@endsection

@section('actions')
    @if ($window && ! $isOso)
        <span style="font-size:0.8rem;font-weight:700;color:#7a7074;">
            AY {{ $window->academic_year }} · {{ $window->semester }}
        </span>
    @endif
@endsection

@section('content')
    <style>
        .rn-card { background:#fff; border:1.5px solid #f0e6e8; border-radius:20px; padding:1.35rem 1.5rem; box-shadow:0 4px 16px rgba(90,15,30,.03); margin-bottom:1.1rem; }
        .rn-card h3 { margin:0 0 0.35rem; font-size:1.02rem; font-weight:800; color:#1a1618; display:flex; align-items:center; gap:0.45rem; }
        .rn-card h3 i { color:#8b1828; }
        .rn-muted { margin:0 0 1rem; font-size:0.84rem; color:#7a7074; }
        .rn-locked { text-align:center; padding:3rem 1.5rem; }
        .rn-locked i { font-size:2.4rem; color:#8b1828; }
        .rn-pill { font-size:0.7rem; font-weight:800; padding:0.15rem 0.5rem; border-radius:999px; }
        .rn-pill.ok { background:#f0fdf4; color:#15803d; }
        .rn-pill.wait { background:#fff7ed; color:#c2410c; }
        .rn-alert { padding:0.75rem 0.95rem; border-radius:12px; font-size:0.84rem; font-weight:700; margin-bottom:1rem; }
        .rn-alert.ok { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .rn-alert.err { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
        .rn-progress { height:8px; background:#f3e8ea; border-radius:999px; overflow:hidden; margin:0.5rem 0 1rem; }
        .rn-progress > span { display:block; height:100%; background:#8b1828; border-radius:999px; }
        .rn-section-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:0.9rem; }
        .rn-section-head .rn-muted { margin:0; }
        .rn-link { display:inline-flex; align-items:center; gap:0.25rem; color:#8b1828; font-size:0.72rem; font-weight:800; text-decoration:none; }
        .rn-link:hover { text-decoration:underline; }
        .rn-status { display:inline-flex; align-items:center; padding:0.12rem 0.5rem; border-radius:999px; font-size:0.66rem; font-weight:800; border:1px solid transparent; }
        .rn-status.is-verified { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
        .rn-status.is-pending { background:#fffbeb; color:#b45309; border-color:#fde68a; }
        .rn-status.is-returned { background:#fff1f2; color:#be123c; border-color:#fecdd3; }
        .rn-status.is-rejected { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
        .rn-status.is-muted { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
        .so-renewal { color:#1a1618; }
        .org-content > .so-renewal { animation-name:soRenewalFade; }
        @keyframes soRenewalFade { from { opacity:0; } to { opacity:1; } }
        .so-renewal .rn-card { padding:0.9rem 1rem; border-radius:14px; margin-bottom:0.75rem; }
        .so-renewal-head, .so-renewal-banner, .so-renewal-footer { display:flex; align-items:center; justify-content:space-between; gap:0.8rem; }
        .so-renewal-head { margin-bottom:0.7rem; }
        .so-renewal-head h3 { margin:0; }
        .so-renewal-head .rn-muted { margin:0.2rem 0 0; }
        .so-renewal-info { display:grid; grid-template-columns:2fr 1fr 1fr; gap:0.65rem; }
        .so-renewal-info label { display:grid; gap:0.25rem; min-width:0; font-size:0.68rem; font-weight:800; color:#2b2427; }
        .so-renewal-info input { box-sizing:border-box; width:100%; min-width:0; padding:0.55rem 0.65rem; border:1px solid #ebe3e5; border-radius:9px; background:#f8f7f7; color:#342d30; font:inherit; font-size:0.72rem; }
        .so-renewal-info input:not([readonly]) { background:#fff; border-color:#c9a6ad; }
        .so-renewal-banner { border-left:4px solid #8b1828 !important; background:#fffcfd !important; }
        .so-renewal-banner-copy { display:flex; align-items:center; gap:0.7rem; min-width:0; }
        .so-renewal-banner-icon { display:grid; place-items:center; width:38px; height:38px; flex:0 0 auto; border-radius:9px; background:#fdf0f2; color:#8b1828; font-size:1.2rem; }
        .so-renewal-banner-label { color:#8b1828; font-size:0.62rem; font-weight:900; letter-spacing:.045em; text-transform:uppercase; }
        .so-renewal-banner-title { display:block; margin-top:0.1rem; font-size:0.78rem; line-height:1.3; }
        .so-renewal-banner-actions { display:flex; align-items:center; gap:0.4rem; flex:0 0 auto; }
        .so-renewal-section-head { display:flex; align-items:flex-start; justify-content:space-between; gap:0.8rem; }
        .so-renewal-section-head h3 { margin:0; }
        .so-renewal-section-head .rn-muted { margin:0.2rem 0 0; }
        .so-renewal-row { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:0.7rem; padding:0.6rem 0.65rem; margin-top:0.45rem; border:1px solid #f0e6e8; border-radius:11px; background:#fff; scroll-margin-bottom:6.5rem; }
        .so-renewal-row-main { display:flex; align-items:flex-start; gap:0.55rem; min-width:0; }
        .so-renewal-file-icon { display:grid; place-items:center; width:1.55rem; height:1.55rem; flex:0 0 auto; border-radius:7px; background:#fdf0f2; color:#8b1828; font-size:0.82rem; }
        .so-renewal-row-copy { min-width:0; }
        .so-renewal-row-title { display:flex; align-items:center; gap:0.35rem; flex-wrap:wrap; }
        .so-renewal-row-title strong { font-size:0.72rem; }
        .so-renewal-row-title .so-renewal-attachment { color:#8b1828; font-size:0.58rem; font-weight:900; text-transform:uppercase; white-space:nowrap; }
        .so-renewal-template-links { display:flex; align-items:center; gap:0.55rem; margin-top:0.12rem; }
        .so-renewal-template-links .rn-link { font-size:0.62rem; }
        .so-renewal-file-name { display:block; margin-top:0.12rem; overflow-wrap:anywhere; color:#15803d; font-size:0.62rem; font-weight:700; }
        .so-renewal-file-name.pending { color:#786f73; font-weight:600; }
        .so-renewal-file-name .rn-status { margin-left:0.3rem; font-size:0.58rem; padding:0.05rem 0.4rem; vertical-align:middle; }
        .so-renewal-review-remarks { display:block; margin-top:0.25rem; padding:0.3rem 0.5rem; border-radius:7px; font-size:0.62rem; line-height:1.4; overflow-wrap:anywhere; }
        .so-renewal-review-remarks.is-returned { background:#fff1f2; color:#9f1239; }
        .so-renewal-review-remarks.is-rejected { background:#fef2f2; color:#991b1b; }
        .so-renewal-row-actions { display:flex; align-items:center; justify-content:flex-end; gap:0.4rem; flex-wrap:wrap; }
        .so-renewal-file-form { display:flex; align-items:center; gap:0.35rem; margin:0; }
        .so-renewal-file-form input[type=file] { width:150px; max-width:100%; font-size:0.62rem; color:#5e565a; }
        .so-renewal-file-form .org-btn { white-space:nowrap; }
        .so-renewal-file-link { max-width:15rem; overflow-wrap:anywhere; text-decoration:underline; text-underline-offset:2px; }
        .so-renewal-footer { position:sticky; bottom:0; z-index:5; padding:0.7rem 0.85rem calc(0.7rem + env(safe-area-inset-bottom, 0px)); border:1px solid #f0e6e8; border-radius:12px; background:rgba(255,255,255,.97); box-shadow:0 4px 18px rgba(90,15,30,.1); }
        .so-renewal-footer strong { display:block; font-size:0.75rem; }
        .so-renewal-footer small { display:block; margin-top:0.12rem; color:#786f73; font-size:0.62rem; }
        .so-renewal-footer .org-btn { flex:0 0 auto; white-space:nowrap; }
        .so-renewal-footer .org-btn[disabled] { opacity:1; background:#efe4e6; border-color:#dcc6cb; color:#6b4a51; box-shadow:none; cursor:not-allowed; }
        .so-renewal-footer .org-btn[disabled]::after { display:none; }
        .so-renewal [hidden] { display:none !important; }
        @media (prefers-reduced-motion: reduce) { .org-content > .so-renewal { animation:none; } }
        .rn-preview-dialog { box-sizing:border-box; position:fixed; inset:0; margin:auto; border:0; border-radius:16px; padding:0; width:min(1040px,calc(100vw - 2rem)); height:min(88dvh,calc(100dvh - 2rem)); overflow:hidden; background:#fff; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); animation:none; transform:none; }
        .rn-preview-box { display:flex; flex-direction:column; height:100%; min-height:0; }
        .rn-preview-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.7rem; padding:0.85rem 1.25rem; border-bottom:1.5px solid #f0e6e8; background:#fffcfd; }
        .rn-preview-heading { display:flex; align-items:center; gap:0.75rem; min-width:0; flex:1 1 18rem; }
        .rn-preview-icon { display:grid; place-items:center; width:36px; height:36px; flex:0 0 auto; border-radius:9px; background:#fdf0f2; color:#8b1828; font-size:1.2rem; }
        .rn-preview-title { min-width:0; overflow-wrap:anywhere; }
        .rn-preview-title strong { font-size:0.95rem; color:#1a1618; display:block; }
        .rn-preview-title small { color:#786f73; font-size:0.75rem; }
        .rn-preview-actions { display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem; }
        .rn-preview-actions .org-btn { padding:0.35rem 0.75rem; font-size:0.76rem; }
        .rn-preview-body { flex:1 1 auto; min-height:0; overflow:auto; background:#e9e6e5; }
        .rn-preview-body > iframe { display:block; width:100%; height:100%; border:0; }
        .rn-preview-body:has(> img) { display:flex; align-items:center; justify-content:center; }
        .rn-preview-body > img { display:block; max-width:100%; max-height:100%; object-fit:contain; }
        .rn-preview-body .docx-wrapper { min-width:fit-content; padding:1rem !important; background:transparent !important; }
        .rn-preview-body section.docx { margin:0 auto 1rem !important; box-shadow:0 7px 24px rgba(42,27,30,.16); }
        .rn-preview-message { display:grid; place-items:center; height:100%; margin:0; padding:1.5rem; text-align:center; color:#675a5e; }
        @media (max-width:640px) {
            .rn-preview-dialog { width:calc(100vw - 1rem); height:calc(100dvh - 1rem); }
            .rn-preview-head { padding:0.7rem 0.8rem; }
            .rn-preview-body .docx-wrapper { padding:0.6rem !important; }
        }
        @media (min-width:901px) and (min-height:560px) {
            .org-main:has(.so-renewal-footer) { display:flex; flex-direction:column; overflow:hidden; }
            .org-main:has(.so-renewal-footer) > .org-topbar { flex:0 0 auto; }
            .org-content:has(> .so-renewal) { display:flex; flex-direction:column; flex:1 1 auto; min-height:0; }
            .org-content:has(> .so-renewal) > * { flex:0 0 auto; }
            .org-content > .so-renewal { display:flex; flex-direction:column; gap:0.6rem; flex:1 1 auto; min-height:0; }
            .so-renewal-scroll { flex:1 1 auto; min-height:0; overflow-y:auto; overscroll-behavior:contain; padding-right:0.25rem; }
            .so-renewal-scroll .rn-card:last-child { margin-bottom:0; }
            .so-renewal-scroll .so-renewal-row { scroll-margin-bottom:0; }
            .so-renewal-footer { position:static; flex:0 0 auto; }
        }
        @media (max-width:720px) {
            .so-renewal-banner { align-items:flex-start; }
            .so-renewal-banner-actions { flex-wrap:wrap; }
            .so-renewal-row { grid-template-columns:minmax(0,1fr); }
            .so-renewal-row-actions { justify-content:space-between; }
            .so-renewal-file-form { flex-wrap:wrap; }
        }
        @media (max-width:420px) {
            .so-renewal-info { grid-template-columns:1fr; }
            .so-renewal-row { scroll-margin-bottom:11rem; }
            .so-renewal-footer { align-items:stretch; flex-direction:column; }
            .so-renewal-footer .org-btn { justify-content:center; }
        }
    </style>

    @if ($errors->any())
        <div class="rn-alert err">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($isOso)
        @include('org.partials.oso-renewal-dashboard')
    @endif

    @if ($isSo)
        @if (! $isOpen)
            <section class="rn-card rn-locked">
                <i class="bi bi-lock-fill"></i>
                <h3 style="justify-content:center;margin-top:0.75rem;">Renewal is locked</h3>
                <p class="rn-muted" style="max-width:420px;margin:0.35rem auto 0;">
                    Only OSO can open the renewal filing window. Your organization cannot create a renewal request until OSO unlocks it.
                </p>
                @if ($window)
                    <p style="margin-top:1rem;font-size:0.8rem;font-weight:700;color:#7a7074;">
                        Next window target: AY {{ $window->academic_year }} · {{ $window->semester }}
                    </p>
                @endif
            </section>
        @else
            @php
                $isTargetOrgQualified = $targetOrgModel ? (bool) ($targetOrgModel->is_qualified_for_renewal ?? true) : true;
                $isTargetOrgActive = $targetOrgModel ? (bool) ($targetOrgModel->is_active ?? true) : true;
                $canSubmitPacket = $isTargetOrgQualified && $isTargetOrgActive;
            @endphp

            <div class="so-renewal">
                <div class="so-renewal-scroll">
            @if (! $canSubmitPacket)
                <div class="rn-alert err" style="margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: flex-start; gap: 0.85rem;">
                        <i class="bi bi-exclamation-octagon-fill" style="font-size: 1.6rem; color: #dc2626; flex-shrink: 0; margin-top: 0.15rem;"></i>
                        <div>
                            <strong style="font-size: 1rem; color: #991b1b; display: block;">Renewal Filing Restricted by OSO</strong>
                            <p style="margin: 0.25rem 0 0; color: #7f1d1d; font-size: 0.84rem; font-weight: normal; line-height: 1.45;">
                                Your organization is currently marked as 
                                <strong>{{ ! $isTargetOrgActive ? 'Inactive / Dormant' : 'Not Qualified to Renew' }}</strong>
                                by the Office of Student Organizations.
                            </p>
                            @if ($targetOrgModel && $targetOrgModel->disqualification_reason)
                                <div style="margin-top: 0.6rem; padding: 0.65rem 0.85rem; background: #fee2e2; border-left: 3.5px solid #dc2626; border-radius: 8px; font-size: 0.82rem; color: #991b1b; font-weight: 500;">
                                    <strong>Official OSO Reason / Deficiency:</strong> {{ $targetOrgModel->disqualification_reason }}
                                </div>
                            @endif
                            <small style="display: block; margin-top: 0.5rem; color: #991b1b; font-weight: 600;">
                                <i class="bi bi-info-circle-fill"></i> Please coordinate with the Office of Student Organizations (OSO) to settle financial liquidations or accomplishment reports before packet submission can be unlocked.
                            </small>
                        </div>
                    </div>
                </div>
            @endif

            @php
                $docKeys = collect($docs)->pluck('key')->all();
                $completedCount = count(array_intersect($docKeys, $uploadedKeys));
                $remainingCount = max(0, count($docs) - $completedCount);
                $adviserName = old('adviser_name', $my->adviser_name ?? '');
                $deanName = old('dean_name', $my->dean_name ?? '');
                $isTerminalPacket = $my && in_array($my->status, ['approved', 'rejected'], true);
                $isDetailsEditable = ! $isTerminalPacket && (! $my || $errors->any());
            @endphp

            @if ($isTerminalPacket)
                <div class="rn-alert {{ $my->status === 'approved' ? 'ok' : 'err' }}" role="status" style="margin-bottom:0.75rem;">
                    <i class="bi {{ $my->status === 'approved' ? 'bi-check-all' : 'bi-x-octagon' }}" aria-hidden="true"></i>
                    Renewal packet {{ $my->status === 'approved' ? 'approved' : 'rejected' }} by OSO{{ $my->reviewed_at ? ' on '.$my->reviewed_at->format('M j, Y g:i A') : '' }}. This packet is final and can no longer be edited or resubmitted.
                    @if ($my->review_remarks)
                        <div style="margin-top:0.35rem;font-weight:600;"><strong>OSO remarks:</strong> {{ $my->review_remarks }}</div>
                    @endif
                </div>
            @endif

                <section class="rn-card">
                    <div class="so-renewal-head">
                        <div>
                            <h3><i class="bi bi-building"></i> Organization Information</h3>
                            <p class="rn-muted">Fields marked with an asterisk (*) are required.</p>
                        </div>
                        <button type="button" id="soRenewalEditInfo" class="org-btn org-btn-ghost org-btn-sm" @disabled($isTerminalPacket || ! $canSubmitPacket)>
                            <i class="bi bi-pencil-square"></i> Edit information
                        </button>
                    </div>

                    <form id="soRenewalSubmissionForm" method="POST" enctype="multipart/form-data" action="{{ route('office.renewal.submit') }}" data-can-submit="{{ ! $isTerminalPacket && $canSubmitPacket ? '1' : '0' }}">
                        @csrf
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="window_id" value="{{ $window->id }}">
                        <input type="hidden" name="organization_name" value="{{ $targetOrg ?? '' }}">
                        <input type="hidden" name="college" value="{{ $targetCollege ?? '' }}">
                        <input type="hidden" name="notes" value="{{ old('notes', $my->notes ?? '') }}">
                        <div class="so-renewal-info">
                            <label>
                                Organization name *
                                <input type="text" value="{{ $targetOrg ?? 'Student Organization' }}" readonly aria-label="Organization name">
                            </label>
                            <label>
                                Organizational adviser *
                                <input type="text" name="adviser_name" value="{{ $adviserName }}" placeholder="Full name of adviser" @if (! $isDetailsEditable) readonly @endif required data-so-renewal-editable>
                            </label>
                            <label>
                                College dean *
                                <input type="text" name="dean_name" value="{{ $deanName }}" placeholder="Full name of dean" @if (! $isDetailsEditable) readonly @endif required data-so-renewal-editable>
                            </label>
                        </div>
                        @unless ($isTerminalPacket)
                            <p class="rn-muted">Details and selected files stay in this page until you click Submit for Review. Leaving or reloading the page discards unsubmitted changes.</p>
                        @endunless
                    </form>
                </section>

                <section class="rn-card so-renewal-banner">
                    <div class="so-renewal-banner-copy">
                        <span class="so-renewal-banner-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                        <div>
                            <span class="so-renewal-banner-label">Master University Form · BatStateU-FO-SOA-01</span>
                            <strong class="so-renewal-banner-title">Application for Recognition / Renewal of Student Organization (Rev. 03)</strong>
                        </div>
                    </div>
                    <div class="so-renewal-banner-actions">
                        <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-renewal-preview
                                data-renewal-preview-url="/templates/renewal/Copy of BatStateU-FO-SOA-01_Application for Recognition, Renewal of Student Organization_Rev. 03 (1) (1).pdf"
                                data-renewal-preview-title="BatStateU-FO-SOA-01 Master Application Form" data-renewal-preview-type="pdf"
                                data-renewal-preview-download="/templates/renewal/Copy of BatStateU-FO-SOA-01_Application for Recognition, Renewal of Student Organization_Rev. 03 (1) (1).docx">
                            <i class="bi bi-eye"></i> View
                        </button>
                        <a href="/templates/renewal/Copy of BatStateU-FO-SOA-01_Application for Recognition, Renewal of Student Organization_Rev. 03 (1) (1).docx" download class="org-btn org-btn-ghost org-btn-sm">
                            <i class="bi bi-download"></i> Download
                        </a>
                    </div>
                </section>

                <section class="rn-card">
                    <div class="so-renewal-section-head">
                        <div>
                            <h3><i class="bi bi-cloud-upload-fill"></i> Renewal Requirements{{ $defaultChecklist ? ' A–J' : '' }}</h3>
                            <p class="rn-muted">{{ $window->instructions ?: 'View or download each template, complete and sign it, then select the finished document.' }}</p>
                        </div>
                        <span id="soRenewalCount" class="rn-pill {{ $remainingCount === 0 ? 'ok' : 'wait' }}">{{ $completedCount }}/{{ count($docs) }} ready</span>
                    </div>
                    <div id="soRenewalProgress" class="rn-progress" role="progressbar" aria-label="Renewal requirements ready" aria-valuemin="0" aria-valuemax="{{ count($docs) }}" aria-valuenow="{{ $completedCount }}">
                        <span style="width:{{ $pct }}%"></span>
                    </div>

                    @foreach ($docs as $doc)
                        @php
                            $uploaded = $uploadedDocs->get($doc['key']);
                            $hasUploadedFile = in_array($doc['key'], $uploadedKeys, true);
                            $officialFile = $officialRequirementFiles[$doc['key']] ?? [];
                            $tmplDocx = ! empty($officialFile['file_available']);
                            $tmplPdf = $tmplDocx;
                            $tmplDocxUrl = $officialFile['download_url'] ?? null;
                            $tmplPdfUrl = $officialFile['preview_url'] ?? null;
                            $label = $officialFile['code'] ?? ('Attachment '.chr(64 + $loop->iteration));
                        @endphp
                        <article class="so-renewal-row">
                            <div class="so-renewal-row-main">
                                <span class="so-renewal-file-icon"><i class="bi bi-file-earmark-text"></i></span>
                                <div class="so-renewal-row-copy">
                                    <div class="so-renewal-row-title">
                                        <span class="so-renewal-attachment">{{ $label }} *</span>
                                        <strong>{{ $doc['title'] }}</strong>
                                    </div>
                                    <div class="so-renewal-template-links">
                                        @if ($tmplPdf)
                                            <a href="{{ $tmplPdfUrl }}" class="rn-link" data-renewal-preview
                                               data-renewal-preview-url="{{ $tmplPdfUrl }}" data-renewal-preview-download="{{ $tmplDocxUrl }}"
                                               data-renewal-preview-title="{{ $label }}: {{ $doc['title'] }}" data-renewal-preview-type="{{ $officialFile['preview_type'] ?? '' }}"><i class="bi bi-eye"></i> View</a>
                                        @endif
                                        @if ($tmplDocx)
                                            <a href="{{ $tmplDocxUrl }}" download class="rn-link"><i class="bi bi-download"></i> Download</a>
                                        @endif
                                    </div>
                                    @if ($uploaded)
                                        @php
                                            $uploadedReview = in_array($uploaded->review_status, ['verified', 'returned', 'rejected'], true) ? $uploaded->review_status : null;
                                            $uploadedReviewLabels = ['verified' => 'Verified by OSO', 'returned' => 'Returned for revision', 'rejected' => 'Rejected by OSO'];
                                        @endphp
                                        @if ($uploadedReview)
                                            <small class="so-renewal-file-name" data-so-saved-file>
                                                <span class="rn-status is-{{ $uploadedReview }}">{{ $uploadedReviewLabels[$uploadedReview] }}</span>
                                            </small>
                                        @endif
                                        @if (in_array($uploadedReview, ['returned', 'rejected'], true) && filled($uploaded->review_remarks))
                                            <small class="so-renewal-review-remarks is-{{ $uploadedReview }}">
                                                <strong>OSO remarks:</strong> {{ $uploaded->review_remarks }}
                                            </small>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="so-renewal-row-actions">
                                <span class="org-upload-status" data-so-file-status aria-live="polite" @if ($hasUploadedFile) hidden @endif>No file selected.</span>
                                <a class="org-upload-status is-selected so-renewal-file-link"
                                   data-so-file-link data-saved-name="{{ $hasUploadedFile ? ($uploaded->file_name ?: basename($uploaded->file_path)) : '' }}"
                                   data-saved-url="{{ $hasUploadedFile ? route('office.renewal.documents.file', ['document' => $uploaded, 'v' => $uploaded->fileVersion()]) : '' }}"
                                   data-saved-type="{{ $hasUploadedFile ? strtolower(pathinfo($uploaded->file_name ?: $uploaded->file_path, PATHINFO_EXTENSION)) : '' }}"
                                   data-saved-download="{{ $hasUploadedFile ? route('office.renewal.documents.file', ['document' => $uploaded, 'v' => $uploaded->fileVersion(), 'download' => 1]) : '' }}"
                                   data-renewal-preview-type="{{ $hasUploadedFile ? strtolower(pathinfo($uploaded->file_name ?: $uploaded->file_path, PATHINFO_EXTENSION)) : '' }}"
                                   data-renewal-preview-download="{{ $hasUploadedFile ? route('office.renewal.documents.file', ['document' => $uploaded, 'v' => $uploaded->fileVersion(), 'download' => 1]) : '' }}"
                                   @if ($hasUploadedFile) href="{{ route('office.renewal.documents.file', ['document' => $uploaded, 'v' => $uploaded->fileVersion()]) }}" @else hidden @endif
                                   >{{ $hasUploadedFile ? ($uploaded->file_name ?: basename($uploaded->file_path)) : '' }}</a>
                                @if ($isTerminalPacket)
                                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" disabled title="This packet is final">
                                        <i class="bi bi-lock"></i> Packet {{ $my->status === 'approved' ? 'approved' : 'rejected' }}
                                    </button>
                                @else
                                    <div class="so-renewal-file-form">
                                        <input id="renewalDocumentInput{{ $doc['key'] }}" type="file" name="documents[{{ $doc['key'] }}]" form="soRenewalSubmissionForm" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg" data-so-renewal-document data-uploaded="{{ $hasUploadedFile ? '1' : '0' }}" style="display:none;" @disabled(! $canSubmitPacket)>
                                        <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-so-renewal-upload-button @disabled(! $canSubmitPacket)>
                                            <i class="bi bi-upload"></i> <span data-so-upload-label>{{ $hasUploadedFile ? 'Replace File' : 'Upload' }}</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>
                </div>

                <div class="so-renewal-footer">
                    <div>
                        @if ($isTerminalPacket)
                            <strong>Renewal packet {{ $my->status === 'approved' ? 'approved' : 'rejected' }}</strong>
                            <small>OSO has recorded a final decision. Uploads, edits and resubmission are closed for this packet.</small>
                        @else
                            <strong id="soRenewalRemaining">{{ $remainingCount }} requirement{{ $remainingCount === 1 ? '' : 's' }} remaining</strong>
                            <small id="soRenewalHelp">Files are not uploaded and details are not saved until you submit for review.</small>
                        @endif
                    </div>
                    <button id="soRenewalSubmit" type="submit" form="soRenewalSubmissionForm" class="org-btn org-btn-primary" @disabled($isTerminalPacket || $remainingCount > 0 || ! $canSubmitPacket)>
                        <i class="bi {{ $isTerminalPacket ? 'bi-lock-fill' : 'bi-send-fill' }}"></i> {{ $isTerminalPacket ? 'Packet Final' : 'Submit for Review' }}
                    </button>
                </div>
            </div>
        @endif
    @endif
    @if ($isSo && $isOpen)
        <script src="{{ asset('js/so-renewal.js') }}?v={{ filemtime(public_path('js/so-renewal.js')) }}"></script>
    @endif
    <script>

        function openManageQualificationModal(org) {
            const modal = document.getElementById('manageOrgQualificationModal');
            const form = document.getElementById('manageOrgQualificationForm');
            if (!modal || !form) return;

            form.action = org.status_url;
            const nameEl = document.getElementById('modalOrgName');
            const collegeEl = document.getElementById('modalOrgCollege');
            if (nameEl) nameEl.textContent = org.name || '';
            if (collegeEl) collegeEl.textContent = org.college || 'Campus Wide';

            const isActive = org.is_active !== undefined ? !!org.is_active : true;
            const isQualified = org.is_qualified_for_renewal !== undefined ? !!org.is_qualified_for_renewal : true;

            const activeTrue = document.getElementById('modalActiveTrue');
            const activeFalse = document.getElementById('modalActiveFalse');
            const qualTrue = document.getElementById('modalQualifiedTrue');
            const qualFalse = document.getElementById('modalQualifiedFalse');

            if (activeTrue && activeFalse) {
                activeTrue.checked = isActive;
                activeFalse.checked = !isActive;
            }

            if (qualTrue && qualFalse) {
                qualTrue.checked = isQualified;
                qualFalse.checked = !isQualified;
            }

            const reasonEl = document.getElementById('modalDisqualificationReason');
            if (reasonEl) {
                reasonEl.value = org.disqualification_reason || '';
            }

            handleModalStatusChange();

            if (typeof modal.showModal === 'function') {
                modal.showModal();
            } else {
                modal.setAttribute('open', '');
            }
        }

        function closeManageQualificationModal() {
            const modal = document.getElementById('manageOrgQualificationModal');
            if (modal) {
                if (typeof modal.close === 'function') {
                    modal.close();
                } else {
                    modal.removeAttribute('open');
                }
            }
        }

        function handleModalStatusChange() {
            const activeTrue = document.getElementById('modalActiveTrue');
            const qualTrue = document.getElementById('modalQualifiedTrue');
            const qualFalse = document.getElementById('modalQualifiedFalse');
            const reasonWrapper = document.getElementById('modalReasonWrapper');
            const reasonEl = document.getElementById('modalDisqualificationReason');

            const isActive = activeTrue ? activeTrue.checked : true;
            let isQualified = qualTrue ? qualTrue.checked : true;

            if (!isActive && isQualified && qualFalse) {
                qualFalse.checked = true;
                isQualified = false;
            }

            if (reasonWrapper) {
                if (isQualified && isActive) {
                    reasonWrapper.style.opacity = '0.5';
                } else {
                    reasonWrapper.style.opacity = '1';
                    if (reasonEl && !reasonEl.value.trim() && !isActive) {
                        reasonEl.value = 'Inactive organization — no active executive council roster filed for current academic year.';
                    }
                }
            }
        }

        function setDisqualificationPreset(text) {
            const reasonEl = document.getElementById('modalDisqualificationReason');
            const qualFalse = document.getElementById('modalQualifiedFalse');
            if (reasonEl) {
                reasonEl.value = text;
            }
            if (qualFalse) {
                qualFalse.checked = true;
            }
            handleModalStatusChange();
        }

    </script>

    @if ($isSo)
    <dialog id="renewalDocViewerModal" class="rn-preview-dialog" aria-labelledby="renewalDocViewerTitle">
        <div class="rn-preview-box">
            <header class="rn-preview-head">
                <div class="rn-preview-heading">
                    <span class="rn-preview-icon" aria-hidden="true"><i class="bi bi-file-earmark-richtext"></i></span>
                    <div class="rn-preview-title">
                        <strong id="renewalDocViewerTitle">Document Preview</strong>
                        <small>{{ $targetOrg ?: 'Student Organization' }} · Renewal Packet</small>
                    </div>
                </div>
                <div class="rn-preview-actions">
                    <a id="renewalDocViewerDownloadBtn" href="#" download class="org-btn org-btn-ghost org-btn-sm">
                        <i class="bi bi-download"></i> Download
                    </a>
                    <a id="renewalDocViewerNewTabBtn" href="#" target="_blank" rel="noopener" class="org-btn org-btn-ghost org-btn-sm">
                        <i class="bi bi-box-arrow-up-right"></i> Open in Tab
                    </a>
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-renewal-preview-close aria-label="Close document preview">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </header>
            <div id="renewalDocViewerBody" class="rn-preview-body"></div>
        </div>
    </dialog>
    <script src="{{ asset('js/vendor/jszip.min.js') }}" defer></script>
    <script src="{{ asset('js/vendor/docx-preview.min.js') }}" defer></script>
    <script src="{{ asset('js/so-renewal-preview.js') }}?v={{ filemtime(public_path('js/so-renewal-preview.js')) }}" defer></script>
    @endif

    @if ($isOso)
        {{-- OSO Manage Organization Qualification & Status Modal --}}
        <dialog id="manageOrgQualificationModal" style="border: none; border-radius: 16px; margin:auto; padding: 0; width: 92vw; max-width: 580px; max-height:90dvh; overflow-y:auto; animation:none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); background: #ffffff;">
            <form id="manageOrgQualificationForm" method="POST" action="" style="display: flex; flex-direction: column; height: 100%; margin: 0;">
                @csrf
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.4rem; border-bottom: 1.5px solid #f0e6e8; background: #fffcfd;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: #fdf0f2; color: #8b1828; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="bi bi-sliders"></i>
                        </div>
                        <div>
                            <strong style="font-size: 1rem; color: #1a1618; display: block;">Manage Organization Status</strong>
                            <small style="color: #786f73; font-size: 0.76rem;">Office of Student Organizations (OSO) Clearance</small>
                            <small style="display:block;color:#786f73;font-size:0.72rem;">Administrative flags do not approve a renewal packet.</small>
                        </div>
                    </div>
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="closeManageQualificationModal()" style="padding: 0.35rem 0.65rem; font-size: 0.85rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div style="padding: 1.25rem 1.4rem; display: flex; flex-direction: column; gap: 1.1rem;">
                    {{-- Target Organization display box --}}
                    <div style="background: #faf4f5; border: 1px solid #ebd9dc; border-radius: 12px; padding: 0.85rem 1rem;">
                        <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #8b1828; letter-spacing: 0.05em;">Selected Organization</span>
                        <strong id="modalOrgName" style="display: block; font-size: 0.95rem; color: #1a1618; margin-top: 0.15rem;"></strong>
                        <small id="modalOrgCollege" style="color: #786f73; font-size: 0.76rem;"></small>
                    </div>

                    {{-- Operational Status (Active vs Inactive) --}}
                    <div>
                        <label style="font-weight: 800; font-size: 0.82rem; color: #1a1618; display: block; margin-bottom: 0.4rem;">
                            1. Operational Status
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem;">
                            <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; border: 1.5px solid #bbf7d0; background: #f0fdf4; border-radius: 10px; cursor: pointer;">
                                <input type="radio" name="is_active" id="modalActiveTrue" value="1" style="accent-color: #16a34a;" onchange="handleModalStatusChange()">
                                <div>
                                    <strong style="display: block; font-size: 0.82rem; color: #166534;">Active</strong>
                                    <small style="display: block; font-size: 0.68rem; color: #15803d;">Operating organization</small>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; border: 1.5px solid #cbd5e1; background: #f8fafc; border-radius: 10px; cursor: pointer;">
                                <input type="radio" name="is_active" id="modalActiveFalse" value="0" style="accent-color: #475569;" onchange="handleModalStatusChange()">
                                <div>
                                    <strong style="display: block; font-size: 0.82rem; color: #334155;">Inactive / Dormant</strong>
                                    <small style="display: block; font-size: 0.68rem; color: #64748b;">No active student council</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Renewal Qualification --}}
                    <div>
                        <label style="font-weight: 800; font-size: 0.82rem; color: #1a1618; display: block; margin-bottom: 0.4rem;">
                            2. Renewal Filing Qualification
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem;">
                            <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; border: 1.5px solid #bbf7d0; background: #f0fdf4; border-radius: 10px; cursor: pointer;">
                                <input type="radio" name="is_qualified_for_renewal" id="modalQualifiedTrue" value="1" style="accent-color: #16a34a;" onchange="handleModalStatusChange()">
                                <div>
                                    <strong style="display: block; font-size: 0.82rem; color: #166534;">Qualified to Renew</strong>
                                    <small style="display: block; font-size: 0.68rem; color: #15803d;">Cleared to submit packet</small>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; border: 1.5px solid #fecaca; background: #fef2f2; border-radius: 10px; cursor: pointer;">
                                <input type="radio" name="is_qualified_for_renewal" id="modalQualifiedFalse" value="0" style="accent-color: #dc2626;" onchange="handleModalStatusChange()">
                                <div>
                                    <strong style="display: block; font-size: 0.82rem; color: #991b1b;">Not Qualified</strong>
                                    <small style="display: block; font-size: 0.68rem; color: #b91c1c;">Submission blocked</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Disqualification Reason Box --}}
                    <div id="modalReasonWrapper" style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label style="font-weight: 800; font-size: 0.82rem; color: #991b1b; display: block;">
                            Disqualification / Clearance Reason
                        </label>
                        
                        {{-- Quick Presets --}}
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: #786f73; margin-bottom: 0.35rem;">Quick Presets:</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.7rem; padding: 0.25rem 0.55rem; background: #fff5f5; border-color: #fed7d7; color: #991b1b;" onclick="setDisqualificationPreset('Unliquidated Financial Report (FR) — pending year-end liquidation clearance.')">
                                    Unliquidated FR
                                </button>
                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.7rem; padding: 0.25rem 0.55rem; background: #fff5f5; border-color: #fed7d7; color: #991b1b;" onclick="setDisqualificationPreset('Incomplete Accomplishment Report (AR) — missing required activity documentation.')">
                                    Incomplete AR
                                </button>
                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.7rem; padding: 0.25rem 0.55rem; background: #fff5f5; border-color: #fed7d7; color: #991b1b;" onclick="setDisqualificationPreset('Inactive organization — no active executive council roster or general assembly filed.')">
                                    Inactive / No Council
                                </button>
                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.7rem; padding: 0.25rem 0.55rem; background: #fff5f5; border-color: #fed7d7; color: #991b1b;" onclick="setDisqualificationPreset('Dormant club — undergoing charter revision and faculty adviser re-assignment.')">
                                    Charter Revision
                                </button>
                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.7rem; padding: 0.25rem 0.55rem; background: #fff5f5; border-color: #fed7d7; color: #991b1b;" onclick="setDisqualificationPreset('Pending disciplinary clearance or administrative review.')">
                                    Administrative Review
                                </button>
                            </div>
                        </div>

                        <textarea name="disqualification_reason" id="modalDisqualificationReason" rows="3" placeholder="Specify why the organization is not qualified or what clearance requirements they must settle..." style="width: 100%; box-sizing: border-box; padding: 0.6rem 0.75rem; border: 1.5px solid #fed7d7; border-radius: 10px; font-size: 0.82rem; background: #fffcfc; color: #1a1618; outline: none;"></textarea>
                        <small style="color: #786f73; font-size: 0.7rem;">This reason will be shown to the student organization desk on their renewal portal.</small>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.65rem; padding: 0.85rem 1.4rem; border-top: 1.5px solid #f0e6e8; background: #fffcfd;">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="closeManageQualificationModal()">
                        Cancel
                    </button>
                    <button type="submit" class="org-btn org-btn-primary org-btn-sm">
                        <i class="bi bi-check-lg"></i> Save Organization Status
                    </button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
