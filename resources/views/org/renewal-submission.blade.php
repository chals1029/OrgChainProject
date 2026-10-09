@extends('org.layout')

@php
    $window = $renewalWindow ?? null;
    $docs = $requiredDocs ?? [];
    $org = $organization ?? null;
    $totalRequired = count($docs);
    $docsByKey = $submission->storedDocuments();
    $isReviewable = (bool) ($canReviewDocuments ?? false);
    $canApprove = (bool) ($canApproveRenewal ?? false);

    $shortName = trim((string) ($org?->short_name ?? ''));
    $fullName = $org?->name ?: $submission->organization_name;
    $college = $org?->college ?: ($submission->college ?: 'Campus Wide');

    $initials = '';
    $shortHead = $shortName !== '' ? (preg_split('/[^A-Za-z0-9]+/', $shortName, -1, PREG_SPLIT_NO_EMPTY)[0] ?? '') : '';
    if ($shortHead !== '' && strlen($shortHead) <= 4) {
        $initials = strtoupper($shortHead);
    } else {
        foreach (preg_split('/[^A-Za-z0-9]+/', (string) $fullName, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (ctype_upper($word[0]) || ctype_digit($word[0])) {
                $initials .= $word[0];
            }
            if (strlen($initials) === 3) {
                break;
            }
        }
    }
    $initials = $initials !== '' ? strtoupper($initials) : 'ORG';

    $packetStates = [
        'submitted' => ['Submitted · Under Review', 'is-pending', 'bi-send-check'],
        'approved' => ['Approved', 'is-verified', 'bi-check-all'],
        'returned' => ['Returned for Revision', 'is-returned', 'bi-arrow-return-left'],
        'rejected' => ['Rejected', 'is-rejected', 'bi-x-octagon'],
    ];
    [$packetLabel, $packetClass, $packetIcon] = $packetStates[$submission->status] ?? [ucfirst((string) $submission->status), 'is-muted', 'bi-circle'];

    $docStates = [
        'pending' => ['Pending Review', 'is-pending', 'bi-hourglass-split'],
        'verified' => ['Verified', 'is-verified', 'bi-check-circle-fill'],
        'returned' => ['For Revision', 'is-returned', 'bi-arrow-return-left'],
        'rejected' => ['Rejected', 'is-rejected', 'bi-x-circle-fill'],
    ];
    $previewable = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp'];

    $rows = [];
    $pendingCount = 0;
    $returnedCount = 0;
    $rejectedCount = 0;
    foreach (array_values($docs) as $index => $req) {
        $label = $req['attachment_label'] ?? ($index < 26 ? 'Attachment '.chr(65 + $index) : 'Requirement '.($index + 1));
        preg_match('/([A-Za-z0-9]+)\s*$/', $label, $letterMatch);
        $uploaded = $docsByKey->get($req['key'] ?? '');
        $status = null;
        if ($uploaded) {
            $status = in_array($uploaded->review_status, ['verified', 'returned', 'rejected'], true) ? $uploaded->review_status : 'pending';
            match ($status) {
                'pending' => $pendingCount++,
                'returned' => $returnedCount++,
                'rejected' => $rejectedCount++,
                default => null,
            };
        }
        $rows[] = [
            'key' => $req['key'] ?? 'doc'.$index,
            'title' => $req['title'] ?? $label,
            'label' => $label,
            'letter' => strtoupper($letterMatch[1] ?? (string) ($index + 1)),
            'doc' => $uploaded,
            'status' => $status,
        ];
    }

    $verified = (int) ($verifiedCount ?? 0);
    $missing = (int) ($missingCount ?? 0);
    $verifiedPct = $totalRequired > 0 ? (int) round(($verified / $totalRequired) * 100) : 0;
    $outstanding = array_filter([
        $missing > 0 ? $missing.' missing' : null,
        $pendingCount > 0 ? $pendingCount.' awaiting verification' : null,
        $returnedCount > 0 ? $returnedCount.' for revision' : null,
        $rejectedCount > 0 ? $rejectedCount.' rejected' : null,
    ]);
@endphp

@section('title', 'Submitted Documents')

@section('header')
    <a href="{{ route('office.renewal') }}" class="rs-back">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Renewal Applications
    </a>
    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
        <h1><strong>Submitted Documents</strong></h1>
        @if ($window)
            <span class="rs-chip"><i class="bi bi-calendar3" aria-hidden="true"></i> AY {{ $window->academic_year }} · {{ $window->semester }}</span>
        @endif
    </div>
    <p class="org-welcome">Review each renewal requirement, then record the final decision for this packet.</p>
@endsection

@section('content')
    <style>
        .rs-back { display:inline-flex; align-items:center; gap:0.35rem; margin-bottom:0.45rem; color:#8b1828; font-size:0.78rem; font-weight:800; text-decoration:none; }
        .rs-back:hover { text-decoration:underline; }
        .rs-back:focus-visible, .rs-page :focus-visible, .rs-dialog :focus-visible { outline:2px solid #a92b3e; outline-offset:2px; }
        .rs-chip { display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.65rem; border-radius:999px; background:#fdf0f2; color:#8b1828; border:1px solid #f2dfe2; font-size:0.72rem; font-weight:800; white-space:nowrap; }
        .rs-page { color:#1a1618; }
        .org-content > .rs-page { animation-name:rsFade; }
        @keyframes rsFade { from { opacity:0; } to { opacity:1; } }
        .rs-card { background:#fff; border:1.5px solid #f0e6e8; border-radius:14px; padding:0.9rem 1rem; box-shadow:0 4px 16px rgba(90,15,30,.03); margin-bottom:0.75rem; }
        .rs-alert { padding:0.7rem 0.9rem; border-radius:12px; font-size:0.8rem; font-weight:700; margin-bottom:0.75rem; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
        .rs-summary { display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:0.85rem; background:linear-gradient(135deg,#fff 0%,#fffafb 100%); }
        .rs-avatar { display:grid; place-items:center; width:52px; height:52px; border-radius:14px; background:#fdf0f2; color:#8b1828; border:1px solid #f2dfe2; font-size:0.95rem; font-weight:900; letter-spacing:.02em; }
        .rs-summary-copy { min-width:0; }
        .rs-summary-copy h2 { margin:0; font-size:1.05rem; font-weight:900; line-height:1.2; overflow-wrap:anywhere; }
        .rs-summary-copy p { margin:0.15rem 0 0; color:#786f73; font-size:0.74rem; font-weight:600; overflow-wrap:anywhere; }
        .rs-badges { display:flex; flex-wrap:wrap; gap:0.35rem; margin-top:0.45rem; }
        .rs-kicker { display:block; margin-bottom:0.25rem; color:#8b1828; font-size:0.58rem; font-weight:900; letter-spacing:0.06em; text-transform:uppercase; }
        .rs-summary-meta { display:grid; gap:0.2rem; justify-items:end; text-align:right; font-size:0.7rem; color:#786f73; font-weight:600; }
        .rs-summary-meta strong { color:#2b2427; font-weight:800; }
        .rs-summary-notes { grid-column:1 / -1; display:grid; gap:0.4rem; }
        .rs-note { margin:0; padding:0.45rem 0.65rem; border-radius:9px; font-size:0.74rem; line-height:1.4; background:#faf5f6; border-left:3px solid #8b1828; color:#4a3e42; overflow-wrap:anywhere; }
        .rs-note.is-warn { background:#fff7ed; border-left-color:#ea580c; color:#9a3412; }
        .rs-note.is-danger { background:#fef2f2; border-left-color:#dc2626; color:#991b1b; }
        .rs-pill { display:inline-flex; align-items:center; gap:0.3rem; padding:0.18rem 0.55rem; border-radius:999px; font-size:0.68rem; font-weight:800; white-space:nowrap; border:1px solid transparent; }
        .rs-pill.is-verified { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
        .rs-pill.is-pending { background:#fffbeb; color:#b45309; border-color:#fde68a; }
        .rs-pill.is-returned { background:#fff1f2; color:#be123c; border-color:#fecdd3; }
        .rs-pill.is-rejected { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
        .rs-pill.is-muted { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
        .rs-section-head { display:flex; align-items:flex-start; justify-content:space-between; gap:0.8rem; margin-bottom:0.35rem; }
        .rs-section-head h3 { margin:0; font-size:0.95rem; font-weight:800; display:flex; align-items:center; gap:0.45rem; }
        .rs-section-head h3 i { color:#8b1828; }
        .rs-section-head p { margin:0.2rem 0 0; color:#7a7074; font-size:0.74rem; }
        .rs-row { display:grid; grid-template-columns:auto minmax(0,1fr) auto auto; align-items:center; gap:0.7rem; padding:0.6rem 0.7rem; margin-top:0.45rem; border:1px solid #f0e6e8; border-radius:11px; background:#fff; scroll-margin-bottom:7rem; }
        .rs-row.is-missing { background:#fcfafa; border-style:dashed; }
        .rs-letter { display:grid; place-items:center; width:1.9rem; height:1.9rem; border-radius:9px; background:#f9e9ed; color:#8b1828; font-size:0.78rem; font-weight:900; }
        .rs-row.is-missing .rs-letter { background:#f3f1f2; color:#8a8084; }
        .rs-row-copy { min-width:0; }
        .rs-attachment { display:block; color:#8b1828; font-size:0.58rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; }
        .rs-row-copy strong { display:block; font-size:0.78rem; line-height:1.3; }
        .rs-row-copy small { display:block; margin-top:0.12rem; color:#786f73; font-size:0.66rem; font-weight:600; overflow-wrap:anywhere; }
        .rs-remarks { margin:0.35rem 0 0; padding:0.35rem 0.55rem; border-radius:8px; font-size:0.68rem; line-height:1.4; overflow-wrap:anywhere; }
        .rs-remarks.is-returned { background:#fff1f2; color:#9f1239; }
        .rs-remarks.is-rejected { background:#fef2f2; color:#991b1b; }
        .rs-remarks.is-verified { background:#f0fdf4; color:#166534; }
        .rs-row-actions { display:flex; align-items:center; justify-content:flex-end; gap:0.3rem; flex-wrap:wrap; }
        .rs-row-actions form { display:inline-flex; margin:0; }
        .rs-act { display:inline-flex; align-items:center; gap:0.25rem; padding:0.32rem 0.6rem; border-radius:8px; border:1px solid #eadde0; background:#fff; color:#5e2630; font:inherit; font-size:0.68rem; font-weight:800; text-decoration:none; cursor:pointer; white-space:nowrap; transition:background .15s ease, border-color .15s ease; }
        .rs-act:hover { background:#fdf0f2; border-color:#e5c7cd; }
        .rs-act.is-view { background:#f5faff; color:#365f86; border-color:#d6e5f0; }
        .rs-act.is-view:hover { background:#eaf4fd; }
        .rs-act.is-verify { color:#15803d; border-color:#bbf7d0; }
        .rs-act.is-verify:hover { background:#f0fdf4; }
        .rs-act.is-return { color:#b45309; border-color:#fde68a; }
        .rs-act.is-return:hover { background:#fffbeb; }
        .rs-act.is-reject { color:#b91c1c; border-color:#fecaca; }
        .rs-act.is-reject:hover { background:#fef2f2; }
        .rs-act[disabled] { opacity:0.45; cursor:not-allowed; background:#fff; }
        .rs-missing-note { color:#8a8084; font-size:0.66rem; font-weight:700; white-space:nowrap; }
        .rs-empty { margin:0.5rem 0 0; color:#786f73; font-size:0.78rem; }
        .rs-footer { position:sticky; bottom:max(0.5rem, env(safe-area-inset-bottom)); z-index:5; display:flex; align-items:center; justify-content:space-between; gap:0.9rem; margin-bottom:0.5rem; padding:0.75rem 0.9rem calc(0.75rem + env(safe-area-inset-bottom, 0px)); border:1px solid #f0e6e8; border-radius:12px; background:rgba(255,255,255,.97); box-shadow:0 4px 18px rgba(90,15,30,.1); }
        .rs-footer-copy { min-width:0; flex:1 1 auto; }
        .rs-footer-copy strong { display:block; font-size:0.8rem; }
        .rs-footer-copy small { display:block; margin-top:0.15rem; color:#786f73; font-size:0.66rem; line-height:1.35; }
        .rs-footer-copy small.is-warn { color:#b45309; font-weight:700; }
        .rs-progress { height:6px; max-width:320px; margin-top:0.35rem; background:#f3e8ea; border-radius:999px; overflow:hidden; }
        .rs-progress > span { display:block; height:100%; background:#16a34a; border-radius:999px; }
        .rs-footer-actions { display:flex; align-items:center; gap:0.45rem; flex:0 0 auto; flex-wrap:wrap; justify-content:flex-end; }
        .rs-footer-actions form { display:inline-flex; margin:0; }
        .rs-footer .org-btn { white-space:nowrap; }
        .rs-btn-return { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
        .rs-btn-return:hover { background:#fef3c7; color:#92400e; }
        .rs-btn-reject { background:#fff; color:#b91c1c; border:1px solid #fecaca; }
        .rs-btn-reject:hover { background:#fef2f2; color:#991b1b; }
        .rs-footer .org-btn[disabled] { opacity:1; background:#efe4e6; border-color:#dcc6cb; color:#6b4a51; box-shadow:none; cursor:not-allowed; transform:none; }
        .rs-footer .org-btn[disabled]::after { display:none; }
        .rs-dialog { width:min(100% - 2rem, 480px); max-height:calc(100dvh - 2rem); margin:auto; padding:0; border:0; border-radius:16px; color:#1a1618; background:#fff; box-shadow:0 24px 70px rgba(26,10,13,.25); overflow:auto; }
        .rs-dialog::backdrop { background:rgba(26,10,13,.45); }
        .org-content > .rs-dialog { animation:none; }
        .rs-dialog form { display:grid; gap:0.85rem; margin:0; padding:1.1rem 1.2rem; }
        .rs-dialog-head { display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem; }
        .rs-dialog-head h2 { margin:0; font-size:1rem; font-weight:900; }
        .rs-dialog-head p { margin:0.2rem 0 0; color:#786f73; font-size:0.76rem; overflow-wrap:anywhere; }
        .rs-dialog-close { display:grid; place-items:center; flex:0 0 auto; width:32px; height:32px; border:0; border-radius:9px; background:transparent; color:#786f73; cursor:pointer; }
        .rs-dialog-close:hover { background:#fdf0f2; color:#8b1828; }
        .rs-dialog label { display:grid; gap:0.35rem; font-size:0.78rem; font-weight:800; }
        .rs-dialog textarea { box-sizing:border-box; width:100%; min-height:110px; padding:0.6rem 0.75rem; border:1.5px solid #f0e0e3; border-radius:10px; background:#fdfafb; color:#1a1618; font:inherit; font-size:0.82rem; resize:vertical; }
        .rs-dialog textarea:focus { outline:2px solid rgba(139,24,40,.18); border-color:#a92b3e; }
        .rs-dialog-actions { display:flex; justify-content:flex-end; gap:0.5rem; flex-wrap:wrap; }
        .rs-preview { width:min(100% - 2rem, 1040px); height:min(88dvh, 900px); max-height:calc(100dvh - 2rem); overflow:hidden; }
        .rs-preview-shell { display:flex; flex-direction:column; height:100%; }
        .rs-preview-head { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.75rem 1rem; border-bottom:1.5px solid #f0e6e8; background:#fffcfd; flex-wrap:wrap; }
        .rs-preview-head strong { display:block; font-size:0.9rem; overflow-wrap:anywhere; }
        .rs-preview-head small { color:#786f73; font-size:0.72rem; overflow-wrap:anywhere; }
        .rs-preview-tools { display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap; }
        .rs-preview-body { flex:1 1 auto; min-height:0; background:#525659; }
        .rs-preview-body iframe { display:block; width:100%; height:100%; border:0; background:#fff; }
        .rs-page [hidden] { display:none !important; }
        @media (prefers-reduced-motion: reduce) { .org-content > .rs-page { animation:none; } }
        @media (min-width:901px) and (min-height:560px) {
            .org-main:has(.rs-footer) { display:flex; flex-direction:column; overflow:hidden; }
            .org-main:has(.rs-footer) > .org-topbar { flex:0 0 auto; }
            .org-content:has(> .rs-page) { display:flex; flex-direction:column; flex:1 1 auto; min-height:0; }
            .org-content:has(> .rs-page) > * { flex:0 0 auto; }
            .org-content > .rs-page { display:flex; flex-direction:column; gap:0.6rem; flex:1 1 auto; min-height:0; }
            .rs-scroll { flex:1 1 auto; min-height:0; overflow-y:auto; overscroll-behavior:contain; padding-right:0.25rem; }
            .rs-scroll .rs-card:last-child { margin-bottom:0; }
            .rs-scroll .rs-row { scroll-margin-bottom:0; }
            .rs-footer { position:static; flex:0 0 auto; }
        }
        @media (max-width:900px) {
            .rs-summary { grid-template-columns:auto minmax(0,1fr); }
            .rs-summary-meta { grid-column:1 / -1; justify-items:start; text-align:left; }
        }
        @media (max-width:720px) {
            .rs-row { grid-template-columns:auto minmax(0,1fr); }
            .rs-row > .rs-pill { grid-column:2; justify-self:start; }
            .rs-row-actions { grid-column:1 / -1; justify-content:flex-start; }
            .rs-footer { flex-direction:column; align-items:stretch; }
            .rs-footer-actions { justify-content:stretch; }
            .rs-footer-actions > *, .rs-footer-actions form .org-btn { flex:1 1 auto; justify-content:center; }
            .rs-row { scroll-margin-bottom:12rem; }
        }
        @media (max-width:420px) {
            .rs-summary { grid-template-columns:1fr; }
            .rs-footer-actions { flex-direction:column; }
            .rs-footer-actions form, .rs-footer-actions form .org-btn { width:100%; }
            .rs-row { scroll-margin-bottom:18rem; }
        }
    </style>

    <div class="rs-page">
        <div class="rs-scroll">
            @if ($errors->any())
                <div class="rs-alert" role="alert">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <section class="rs-card rs-summary" aria-labelledby="rsOrgName">
                <span class="rs-avatar" aria-hidden="true">{{ $initials }}</span>
                <div class="rs-summary-copy">
                    <span class="rs-kicker">Renewal Application</span>
                    <h2 id="rsOrgName">{{ $fullName }}</h2>
                    <p><i class="bi bi-building" aria-hidden="true"></i> {{ $college }}</p>
                    <div class="rs-badges">
                        <span class="rs-pill {{ $packetClass }}"><i class="bi {{ $packetIcon }}" aria-hidden="true"></i> {{ $packetLabel }}</span>
                        @if ($org)
                            @if ($org->is_active)
                                <span class="rs-pill is-verified"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Active</span>
                            @else
                                <span class="rs-pill is-muted"><i class="bi bi-moon-fill" aria-hidden="true"></i> Inactive / Dormant</span>
                            @endif
                            @if ($org->is_qualified_for_renewal ?? true)
                                <span class="rs-pill is-verified"><i class="bi bi-shield-check" aria-hidden="true"></i> Qualified to Renew</span>
                            @else
                                <span class="rs-pill is-rejected"><i class="bi bi-slash-circle-fill" aria-hidden="true"></i> Not Qualified</span>
                            @endif
                        @else
                            <span class="rs-pill is-muted"><i class="bi bi-question-circle" aria-hidden="true"></i> No linked organization record</span>
                        @endif
                    </div>
                </div>
                <div class="rs-summary-meta">
                    @if ($submission->submitted_at)
                        <span>Submitted <strong>{{ $submission->submitted_at->format('M j, Y g:i A') }}</strong></span>
                    @endif
                    <span>Adviser: <strong>{{ $submission->adviser_name ?: '—' }}</strong></span>
                    <span>Dean: <strong>{{ $submission->dean_name ?: '—' }}</strong></span>
                    @if ($submission->reviewed_at)
                        <span>Last packet decision: <strong>{{ $submission->reviewed_at->format('M j, Y g:i A') }}</strong></span>
                    @endif
                </div>
                @if (($org && (! $org->is_active || ! ($org->is_qualified_for_renewal ?? true))) || $submission->notes || $submission->review_remarks)
                    <div class="rs-summary-notes">
                        @if ($org && (! $org->is_active || ! ($org->is_qualified_for_renewal ?? true)))
                            <p class="rs-note is-danger" role="note">
                                <strong><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i> Renewal restriction:</strong>
                                OSO currently marks this organization as {{ ! $org->is_active ? 'Inactive / Dormant' : 'Not Qualified to Renew' }}.
                                @if ($org->disqualification_reason)
                                    Reason: {{ $org->disqualification_reason }}
                                @endif
                                Packet decisions here do not change this status; update it from the eligibility monitor.
                            </p>
                        @endif
                        @if ($submission->notes)
                            <p class="rs-note"><strong>SO notes:</strong> {{ $submission->notes }}</p>
                        @endif
                        @if ($submission->review_remarks)
                            <p class="rs-note is-warn"><strong>Packet remarks:</strong> {{ $submission->review_remarks }}</p>
                        @endif
                    </div>
                @endif
            </section>

            <section class="rs-card" aria-labelledby="rsRequirementsTitle">
                <div class="rs-section-head">
                    <div>
                        <h3 id="rsRequirementsTitle"><i class="bi bi-folder2-open" aria-hidden="true"></i> Submitted Renewal Documents</h3>
                        <p>
                            @if ($isReviewable)
                                Open each file, then verify it or return/reject it with remarks for the SO desk.
                            @else
                                Document review is available only while the packet is submitted.
                            @endif
                        </p>
                    </div>
                    <span class="rs-pill {{ $totalRequired > 0 && $verified === $totalRequired ? 'is-verified' : 'is-pending' }}">{{ $verified }}/{{ $totalRequired }} verified</span>
                </div>

                @forelse ($rows as $row)
                    @php
                        $doc = $row['doc'];
                        $state = $row['status'] ? $docStates[$row['status']] : null;
                        $ext = $doc ? strtolower(pathinfo($doc->file_name ?: $doc->file_path, PATHINFO_EXTENSION)) : '';
                        $firstUploadedAt = $doc?->created_at;
                        $titleId = 'rsDocTitle'.$loop->index;
                        $fileVersion = $doc?->fileVersion();
                    @endphp
                    <article class="rs-row {{ $doc ? '' : 'is-missing' }}" aria-labelledby="{{ $titleId }}">
                        <span class="rs-letter" aria-hidden="true">{{ $row['letter'] }}</span>
                        <div class="rs-row-copy">
                            <span class="rs-attachment">{{ $row['label'] }}</span>
                            <strong id="{{ $titleId }}">{{ $row['title'] }}</strong>
                            @if ($doc)
                                <small>
                                    <i class="bi bi-paperclip" aria-hidden="true"></i> {{ $ext !== '' ? strtoupper($ext).' document' : 'Document' }}
                                    @if ($firstUploadedAt)
                                        · First uploaded {{ $firstUploadedAt->format('M j, Y g:i A') }}
                                    @endif
                                </small>
                                @if ($doc->review_remarks && in_array($row['status'], ['returned', 'rejected', 'verified'], true))
                                    <p class="rs-remarks is-{{ $row['status'] }}">
                                        <strong>{{ $row['status'] === 'verified' ? 'OSO note' : 'OSO remarks' }}:</strong> {{ $doc->review_remarks }}
                                        @if ($doc->reviewed_at)
                                            <span>· {{ $doc->reviewed_at->format('M j, Y g:i A') }}</span>
                                        @endif
                                    </p>
                                @endif
                            @else
                                <small>Not uploaded by the SO desk.</small>
                            @endif
                        </div>
                        @if ($state)
                            <span class="rs-pill {{ $state[1] }}"><i class="bi {{ $state[2] }}" aria-hidden="true"></i> {{ $state[0] }}</span>
                        @else
                            <span class="rs-pill is-muted"><i class="bi bi-dash-circle" aria-hidden="true"></i> Missing</span>
                        @endif
                        <div class="rs-row-actions">
                            @if ($doc)
                                @if (in_array($ext, $previewable, true))
                                    <button type="button" class="rs-act is-view"
                                        data-rs-preview="{{ route('office.renewal.documents.file', $doc) }}"
                                        data-rs-download="{{ route('office.renewal.documents.file', [$doc, 'download' => 1]) }}"
                                        data-rs-title="{{ $row['label'] }} · {{ $row['title'] }}"
                                        data-rs-file="{{ $doc->file_name }}"
                                        aria-label="View {{ $row['title'] }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i> View
                                    </button>
                                @else
                                    <a class="rs-act is-view" href="{{ route('office.renewal.documents.file', [$doc, 'download' => 1]) }}" aria-label="Download {{ $row['title'] }} ({{ strtoupper($ext) ?: 'file' }})">
                                        <i class="bi bi-download" aria-hidden="true"></i> Download
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('office.renewal.documents.review', $doc) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="verified">
                                    <input type="hidden" name="file_version" value="{{ $fileVersion }}">
                                    <button type="submit" class="rs-act is-verify" aria-label="Verify {{ $row['title'] }}" @disabled(! $isReviewable || $row['status'] === 'verified')>
                                        <i class="bi bi-check2" aria-hidden="true"></i> Verify
                                    </button>
                                </form>
                                <button type="button" class="rs-act is-return" aria-label="Return {{ $row['title'] }} for revision" @disabled(! $isReviewable)
                                    data-rs-remarks="{{ route('office.renewal.documents.review', $doc) }}"
                                    data-rs-decision="returned"
                                    data-rs-file-version="{{ $fileVersion }}"
                                    data-rs-heading="Return for revision"
                                    data-rs-subject="{{ $row['label'] }} · {{ $row['title'] }}"
                                    data-rs-submit="Return for Revision">
                                    <i class="bi bi-arrow-return-left" aria-hidden="true"></i> Return
                                </button>
                                <button type="button" class="rs-act is-reject" aria-label="Reject {{ $row['title'] }}" @disabled(! $isReviewable)
                                    data-rs-remarks="{{ route('office.renewal.documents.review', $doc) }}"
                                    data-rs-decision="rejected"
                                    data-rs-file-version="{{ $fileVersion }}"
                                    data-rs-heading="Reject document"
                                    data-rs-subject="{{ $row['label'] }} · {{ $row['title'] }}"
                                    data-rs-submit="Reject Document">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i> Reject
                                </button>
                            @else
                                <span class="rs-missing-note">No file to review</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="rs-empty">No required documents are configured for this renewal window.</p>
                @endforelse
            </section>
        </div>

        <div class="rs-footer" role="region" aria-label="Final renewal decision">
            <div class="rs-footer-copy" id="rsDecisionSummary">
                <span class="rs-kicker">Final Renewal Decision</span>
                <strong>{{ ! $isReviewable ? $packetLabel : ($canApprove ? 'Ready for renewal approval' : 'Document verification incomplete') }}</strong>
                <div class="rs-progress" role="progressbar" aria-label="Required documents verified" aria-valuemin="0" aria-valuemax="{{ $totalRequired }}" aria-valuenow="{{ $verified }}">
                    <span style="width:{{ $verifiedPct }}%"></span>
                </div>
                @if (! $isReviewable)
                    <small>This packet is {{ strtolower($packetLabel) }}. Final decisions are available only while a packet is submitted.</small>
                @elseif ($totalRequired === 0)
                    <small class="is-warn">No required documents are configured, so this packet cannot be approved.</small>
                @elseif ($outstanding !== [])
                    <small class="is-warn">Approval needs every required document verified: {{ implode(', ', $outstanding) }}.</small>
                @else
                    <small>All required documents verified. Approval records the packet decision only; organization status stays managed by OSO.</small>
                @endif
            </div>
            <div class="rs-footer-actions">
                <button type="button" class="org-btn org-btn-sm rs-btn-return" @disabled(! $isReviewable)
                    data-rs-remarks="{{ route('office.renewal.review', $submission) }}"
                    data-rs-decision="returned"
                    data-rs-heading="Return packet for revisions"
                    data-rs-subject="{{ $fullName }}"
                    data-rs-submit="Return Packet">
                    <i class="bi bi-arrow-return-left" aria-hidden="true"></i> Return for Revisions
                </button>
                <button type="button" class="org-btn org-btn-sm rs-btn-reject" @disabled(! $isReviewable)
                    data-rs-remarks="{{ route('office.renewal.review', $submission) }}"
                    data-rs-decision="rejected"
                    data-rs-heading="Reject renewal"
                    data-rs-subject="{{ $fullName }}"
                    data-rs-submit="Reject Renewal">
                    <i class="bi bi-x-octagon" aria-hidden="true"></i> Reject Renewal
                </button>
                <form method="POST" action="{{ route('office.renewal.review', $submission) }}">
                    @csrf
                    <input type="hidden" name="decision" value="approved">
                    <button type="submit" class="org-btn org-btn-primary org-btn-sm" aria-describedby="rsDecisionSummary" @disabled(! $canApprove)>
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Approve Renewal
                    </button>
                </form>
            </div>
        </div>
    </div>

    <dialog class="rs-dialog" id="rsRemarksDialog" aria-labelledby="rsRemarksHeading" aria-describedby="rsRemarksSubject">
        <form method="POST" action="" id="rsRemarksForm">
            @csrf
            <input type="hidden" name="decision" value="" id="rsRemarksDecision">
            <input type="hidden" name="file_version" value="" id="rsRemarksFileVersion" disabled>
            <div class="rs-dialog-head">
                <div>
                    <h2 id="rsRemarksHeading">Add remarks</h2>
                    <p id="rsRemarksSubject"></p>
                </div>
                <button type="button" class="rs-dialog-close" data-rs-close aria-label="Close dialog"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <label for="rsRemarksInput">
                Remarks for the SO desk *
                <textarea name="remarks" id="rsRemarksInput" rows="4" maxlength="2000" required placeholder="Explain what must be corrected or why this is rejected."></textarea>
            </label>
            <div class="rs-dialog-actions">
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-rs-close>Cancel</button>
                <button type="submit" class="org-btn org-btn-primary org-btn-sm" id="rsRemarksSubmit">Submit</button>
            </div>
        </form>
    </dialog>

    <dialog class="rs-dialog rs-preview" id="rsPreviewDialog" aria-labelledby="rsPreviewTitle">
        <div class="rs-preview-shell">
            <div class="rs-preview-head">
                <div style="min-width:0;">
                    <strong id="rsPreviewTitle">Document preview</strong>
                    <small id="rsPreviewFile"></small>
                </div>
                <div class="rs-preview-tools">
                    <a id="rsPreviewDownload" href="#" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-download" aria-hidden="true"></i> Download</a>
                    <a id="rsPreviewOpen" href="#" target="_blank" rel="noopener" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Open in Tab</a>
                    <button type="button" class="rs-dialog-close" data-rs-close aria-label="Close preview"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="rs-preview-body">
                <iframe id="rsPreviewFrame" title="Document preview" src="about:blank"></iframe>
            </div>
        </div>
    </dialog>

    <script>
        (() => {
            const remarksDialog = document.getElementById('rsRemarksDialog');
            const remarksForm = document.getElementById('rsRemarksForm');
            const remarksInput = document.getElementById('rsRemarksInput');
            const previewDialog = document.getElementById('rsPreviewDialog');
            const previewFrame = document.getElementById('rsPreviewFrame');
            let opener = null;

            const openDialog = (dialog, trigger) => {
                opener = trigger;
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    dialog.setAttribute('open', '');
                }
            };
            const closeDialog = (dialog) => {
                if (typeof dialog.close === 'function') {
                    dialog.close();
                } else {
                    dialog.removeAttribute('open');
                    dialog.dispatchEvent(new Event('close'));
                }
            };

            document.addEventListener('click', (event) => {
                const remarksTrigger = event.target.closest('[data-rs-remarks]');
                if (remarksTrigger && !remarksTrigger.disabled) {
                    remarksForm.action = remarksTrigger.dataset.rsRemarks;
                    document.getElementById('rsRemarksDecision').value = remarksTrigger.dataset.rsDecision;
                    const fileVersion = document.getElementById('rsRemarksFileVersion');
                    const hasFileVersion = 'rsFileVersion' in remarksTrigger.dataset;
                    fileVersion.value = hasFileVersion ? remarksTrigger.dataset.rsFileVersion : '';
                    fileVersion.disabled = !hasFileVersion;
                    document.getElementById('rsRemarksHeading').textContent = remarksTrigger.dataset.rsHeading;
                    document.getElementById('rsRemarksSubject').textContent = remarksTrigger.dataset.rsSubject;
                    document.getElementById('rsRemarksSubmit').textContent = remarksTrigger.dataset.rsSubmit;
                    remarksInput.value = '';
                    remarksInput.setCustomValidity('');
                    openDialog(remarksDialog, remarksTrigger);
                    remarksInput.focus();
                    return;
                }

                const previewTrigger = event.target.closest('[data-rs-preview]');
                if (previewTrigger) {
                    previewFrame.src = previewTrigger.dataset.rsPreview;
                    document.getElementById('rsPreviewTitle').textContent = previewTrigger.dataset.rsTitle;
                    document.getElementById('rsPreviewFile').textContent = previewTrigger.dataset.rsFile;
                    document.getElementById('rsPreviewDownload').href = previewTrigger.dataset.rsDownload;
                    document.getElementById('rsPreviewOpen').href = previewTrigger.dataset.rsPreview;
                    openDialog(previewDialog, previewTrigger);
                    return;
                }

                const closeTrigger = event.target.closest('[data-rs-close]');
                if (closeTrigger) {
                    closeDialog(closeTrigger.closest('dialog'));
                }
            });

            [remarksDialog, previewDialog].forEach((dialog) => {
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog) closeDialog(dialog);
                });
                dialog.addEventListener('close', () => {
                    if (dialog === previewDialog) previewFrame.src = 'about:blank';
                    opener?.focus();
                    opener = null;
                });
            });

            remarksInput.addEventListener('input', () => remarksInput.setCustomValidity(''));
            remarksForm.addEventListener('submit', (event) => {
                if (!remarksInput.value.trim()) {
                    event.preventDefault();
                    remarksInput.setCustomValidity('Enter remarks for the SO desk.');
                    remarksInput.reportValidity();
                }
            });
        })();
    </script>
@endsection
