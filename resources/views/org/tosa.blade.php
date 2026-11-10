@extends('org.layout')

@section('title', 'TOSA')

@section('header')
    <h1><strong>TOSA</strong></h1>
    <p class="org-welcome">Applications, documents and results.</p>
@endsection

@section('content')
    <style>
        .tosa-page { display:grid; gap:1rem; min-width:0; }
        .tosa-tabs { display:flex; gap:.4rem; overflow-x:auto; border-bottom:1px solid #eadfe2; }
        .tosa-tabs a { flex:none; padding:.75rem .9rem; border-bottom:3px solid transparent; color:#66565c; text-decoration:none; font-weight:700; }
        .tosa-tabs a:hover { color:#731422; }
        .tosa-tabs a:focus-visible { outline:2px solid #8b1828; outline-offset:-2px; }
        .tosa-tabs a[aria-current=page] { color:#8b1828; border-bottom-color:#8b1828; }
        .tosa-card { min-width:0; background:#fff; border:1px solid #eadfe2; border-radius:14px; padding:1.15rem; }
        .tosa-card h2 { margin:0 0 .35rem; font-size:1rem; color:#2b2225; }
        .tosa-card h3 { margin:0 0 .3rem; font-size:.92rem; color:#2b2225; }
        .tosa-note { color:#6b6064; font-size:.84rem; margin:.3rem 0; line-height:1.45; }
        .tosa-toolbar { display:flex; align-items:center; flex-wrap:wrap; gap:.75rem; min-width:0; padding:.75rem 1.15rem; border:1.5px solid #f0e6e8; border-radius:20px; background:#fff; box-shadow:0 4px 16px rgba(90,15,30,.03); }
        .tosa-toolbar form { display:flex; align-items:center; flex-wrap:wrap; gap:.75rem; flex:1; min-width:0; }
        .tosa-filter-title { display:inline-flex; align-items:center; gap:.35rem; color:#8b1828; font-size:.82rem; font-weight:800; letter-spacing:.02em; white-space:nowrap; }
        .tosa-filter-group { display:flex; align-items:center; gap:.35rem; min-width:0; }
        .tosa-filter-group.is-search { flex:1 1 16rem; }
        .tosa-filter-label { display:inline-flex; align-items:center; gap:.25rem; color:#6b5f63; font-size:.76rem; font-weight:700; white-space:nowrap; }
        .tosa-filter-control { min-width:0; max-width:100%; padding:.45rem .8rem; border:1.5px solid #f0e0e3; border-radius:9999px; background:#fdfafb; color:#2b2427; font:inherit; font-size:.78rem; font-weight:700; }
        .tosa-filter-control:focus-visible, .tosa-filter-reset:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-filter-group input { flex:1; }
        .tosa-filter-group select { cursor:pointer; }
        .tosa-filter-reset { display:inline-flex; justify-content:center; align-items:center; gap:.3rem; min-height:34px; padding:0 .75rem; border:1.5px solid #ebd5d8; border-radius:999px; background:#faf4f5; color:#7a2030; font-size:.76rem; font-weight:700; text-decoration:none; flex:none; }
        .tosa-filter-reset:hover { background:#f5e8eb; }
        .tosa-filter-count { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .75rem; border:1px solid #ead4d8; border-radius:9999px; background:#fdf6f7; color:#7a1c2b; font-size:.73rem; font-weight:700; white-space:nowrap; }
        .tosa-input { width:100%; box-sizing:border-box; border:1px solid #d9cdd1; border-radius:8px; padding:.55rem .65rem; font:inherit; color:#2b2225; background:#fff; min-width:0; max-width:100%; }
        .tosa-input:focus-visible { outline:2px solid #8b1828; outline-offset:1px; border-color:#8b1828; }
        .tosa-input[readonly], .tosa-input:disabled { background:#f8f5f6; color:#655a5e; cursor:not-allowed; }
        .tosa-input[aria-invalid=true] { border-color:#b42318; background:#fffbfa; }
        .tosa-table-wrap { max-height:min(60dvh, 36rem); overflow:auto; overscroll-behavior:contain; border:1px solid #f1e9eb; border-radius:12px; }
        .tosa-table-wrap:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-table { width:100%; border-collapse:collapse; font-size:.86rem; }
        .tosa-table th, .tosa-table td { text-align:left; vertical-align:middle; padding:.6rem .65rem; border-bottom:1px solid #f0e8ea; }
        .tosa-table th { position:sticky; top:0; z-index:1; background:#fcf9fa; color:#63575b; font-size:.75rem; font-weight:700; }
        .tosa-table th:last-child, .tosa-table td:last-child { width:1%; white-space:nowrap; text-align:right; }
        .tosa-table td[data-label="Actions"] .tosa-actions { justify-content:flex-end; gap:.35rem; flex-wrap:nowrap; }
        .tosa-table td[data-label="Actions"] .org-btn { white-space:nowrap; }
        .tosa-table td strong, .tosa-table td small { display:block; overflow-wrap:anywhere; }
        .tosa-table td small { color:#6b6064; margin-top:.15rem; }
        .tosa-status { display:inline-flex; align-items:center; gap:.32rem; border-radius:999px; padding:.22rem .65rem; border:1px solid #ead6da; background:#f8ecee; color:#8b1828; font-size:.75rem; font-weight:700; white-space:nowrap; }
        .tosa-status.is-verified, .tosa-status.is-qualified { border-color:#bfe3cb; background:#ebf8ef; color:#1f6837; }
        .tosa-status.is-reviewed { border-color:#cdddf3; background:#eef4fc; color:#2b548a; }
        .tosa-status.is-not-qualified { border-color:#ebcfd5; background:#f9ecee; color:#92263b; }
        .tosa-status.is-for-correction { border-color:#f1d3a7; background:#fff6e8; color:#8f5109; }
        .tosa-status.is-pending-review { border-color:#e2d8dc; background:#f6f2f4; color:#5e5256; }
        .tosa-announcement-card { width:100%; box-sizing:border-box; padding:1.15rem; background:#fff; border:1px solid #eadfe2; border-radius:14px; }
        .tosa-announcement-head { display:flex; align-items:center; gap:.7rem; margin-bottom:.9rem; }
        .tosa-announcement-head h2 { margin:0; font-size:1.05rem; color:#2b2225; }
        .tosa-announcement-form { width:100%; max-width:100%; display:flex; flex-direction:column; gap:.85rem; box-sizing:border-box; }
        .tosa-form-field { display:flex; flex-direction:column; gap:.35rem; }
        .tosa-field-header { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:.15rem; }
        .tosa-field-header label, .tosa-split-col > label { font-size:.76rem; font-weight:800; color:#5a4c52; letter-spacing:.02em; }
        .tosa-counter { font-size:.74rem; color:#75696d; font-variant-numeric:tabular-nums; }
        .tosa-announcement-grid { display:grid; grid-template-columns:minmax(0, 1.35fr) minmax(0, 1fr); gap:1.15rem; align-items:stretch; width:100%; box-sizing:border-box; }
        .tosa-announcement-col-left { display:flex; flex-direction:column; gap:.75rem; height:100%; min-width:0; }
        .tosa-message-col { display:flex; flex-direction:column; flex:1; min-width:0; }
        .tosa-message-col textarea { width:100%; flex:1; min-height:8rem; resize:vertical; box-sizing:border-box; }
        .tosa-deadline-field { width:100%; display:flex; flex-direction:column; gap:.35rem; margin-top:auto; }
        .tosa-deadline-wrap { position:relative; display:flex; align-items:center; width:100%; }
        .tosa-deadline-input { width:100%; color:#2b2427; font-size:.84rem; }
        .tosa-deadline-input::-webkit-calendar-picker-indicator { cursor:pointer; }
        .tosa-deadline-wrap.is-empty:not(:focus-within) .tosa-deadline-input::-webkit-datetime-edit { color:transparent; }
        .tosa-deadline-placeholder { position:absolute; left:.65rem; color:#757575; font-size:.84rem; font-weight:400; pointer-events:none; }
        .tosa-deadline-wrap:not(.is-empty) .tosa-deadline-placeholder, .tosa-deadline-wrap:focus-within .tosa-deadline-placeholder { display:none; }
        .tosa-announcement-col-right { display:flex; flex-direction:column; height:100%; min-width:0; }
        .tosa-attachment-col { display:flex; flex-direction:column; height:100%; flex:1; min-width:0; }
        .tosa-attachment-col .tosa-dropzone { flex:1; width:100%; height:100%; min-height:13.5rem; display:flex; align-items:center; justify-content:center; box-sizing:border-box; }
        .tosa-dropzone { border:2px dashed #d9cdd1; border-radius:10px; background:#faf6f7; position:relative; padding:.75rem 1rem; display:flex; align-items:center; justify-content:center; text-align:center; cursor:pointer; transition:border-color .15s ease, background-color .15s ease; box-sizing:border-box; }
        .tosa-dropzone:hover { border-color:#8b1828; background:#fbf1f3; }
        .tosa-dropzone-input { position:absolute; inset:0; opacity:0; width:100%; height:100%; cursor:pointer; z-index:2; }
        .tosa-dropzone-content { display:flex; flex-direction:column; align-items:center; gap:.25rem; pointer-events:none; }
        .tosa-dropzone-icon { font-size:1.6rem; color:#8b1828; }
        .tosa-dropzone-text { font-size:.84rem; font-weight:700; color:#4a3e43; }
        .tosa-dropzone-sub { font-size:.74rem; color:#7a6e73; }
        .tosa-announcement-actions { display:flex; justify-content:flex-end; margin-top:.5rem; }
        .btn-publish-announcement { background:#8b1828 !important; color:#fff !important; font-weight:700; border-radius:8px; padding:.55rem 1.15rem; display:inline-flex; align-items:center; gap:.45rem; border:none; cursor:pointer; text-decoration:none; font-size:.84rem; }
        .btn-publish-announcement:hover { background:#731422 !important; }
        .tosa-broadcast-head { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom:.85rem; }
        .tosa-broadcast-head h2 { margin:0 0 .25rem; font-size:1.05rem; color:#2b2225; }
        .tosa-broadcast-head p { margin:0; color:#6b6064; font-size:.83rem; }
        .tosa-broadcast-badge { display:inline-flex; align-items:center; padding:.25rem .7rem; border:1px solid #ead4d8; border-radius:999px; background:#fdf6f7; color:#7a1c2b; font-size:.75rem; font-weight:700; white-space:nowrap; }
        .tosa-broadcast-table th { background:#f9fafb; color:#6b7280; font-size:.73rem; font-weight:700; letter-spacing:.03em; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
        .tosa-broadcast-table td { padding:.85rem 1rem; border-bottom:1px solid #f0e8ea; vertical-align:middle; }
        .tosa-table-sub { display:block; color:#6b6064; font-size:.77rem; margin-top:.2rem; line-height:1.35; }
        .tosa-btn-end { display:inline-flex; align-items:center; justify-content:center; min-height:32px; padding:0 .75rem; border:1px solid #ead4d8; border-radius:8px; background:#faedf0; color:#8b1828; font-size:.78rem; font-weight:700; cursor:pointer; text-decoration:none; }
        .tosa-btn-end:hover:not(:disabled) { background:#f8e1e6; border-color:#dcb8bf; }
        .tosa-btn-end:disabled, .tosa-btn-end.is-disabled { opacity:.45; cursor:not-allowed; }
        .tosa-broadcast-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-top:.85rem; padding-top:.5rem; }
        .tosa-pagination-wrap { display:flex; align-items:center; gap:.75rem; }
        .tosa-actions { display:inline-flex; align-items:center; gap:.35rem; flex-wrap:nowrap; }
        .tosa-footer { display:flex; flex-wrap:wrap; gap:.55rem; align-items:center; margin-top:.85rem; }
        .tosa-pagination { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-top:.85rem; }
        .tosa-pagination .tosa-actions { margin-left:auto; display:inline-flex; align-items:center; gap:.4rem; flex-wrap:nowrap; }
        .tosa-feedback { border-radius:10px; padding:.75rem .9rem; border:1px solid #ebd7dc; background:#f8f1f3; color:#73303a; font-size:.85rem; overflow-wrap:anywhere; }
        .tosa-inline-error { margin:.25rem 0 0; padding:.5rem .7rem; border-radius:8px; border:1px solid #f1c6cc; background:#fdf2f4; color:#922032; font-size:.79rem; font-weight:600; }
        .tosa-inline-error[hidden] { display:none !important; }
        .tosa-modal { box-sizing:border-box; position:fixed; inset:0; margin:auto; border:1px solid #eadfe2; border-radius:14px; padding:0; width:min(760px, calc(100vw - 2rem)); max-width:none; max-height:calc(100dvh - 2rem); overflow:auto; color:#30262a; background:#fff; animation:none; transform:none; }
        .tosa-modal::backdrop { background:rgba(28,16,23,.55); }
        .tosa-modal-body { padding:1.15rem; }
        .tosa-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; margin-bottom:.35rem; }
        .tosa-close { display:inline-flex; align-items:center; justify-content:center; width:2.2rem; height:2.2rem; border:1px solid transparent; border-radius:8px; background:transparent; color:#5a4d52; font-size:1.35rem; line-height:1; cursor:pointer; }
        .tosa-close:hover { background:#f8eef0; color:#8b1828; }
        .tosa-close:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
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
        .tosa-icon-button:hover:not(:disabled) { background:#fbf1f3; border-color:#d7b9c0; }
        .tosa-icon-button:disabled { opacity:.4; cursor:not-allowed; }
        .tosa-icon-button:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-template-row [data-tooltip] { position:relative; }
        .tosa-template-row [data-tooltip]::before, .tosa-template-row [data-tooltip]::after { position:absolute; left:50%; z-index:5; opacity:0; pointer-events:none; transform:translateX(-50%); }
        .tosa-template-row [data-tooltip]::before { content:attr(data-tooltip); bottom:calc(100% + 7px); padding:.3rem .5rem; border-radius:6px; background:#30262a; color:#fff; font-size:.72rem; font-weight:600; line-height:1; white-space:nowrap; }
        .tosa-template-row [data-tooltip]::after { content:''; bottom:calc(100% + 2px); border:5px solid transparent; border-top-color:#30262a; border-bottom:0; }
        .tosa-template-row [data-tooltip]:hover::before, .tosa-template-row [data-tooltip]:hover::after, .tosa-template-row [data-tooltip]:focus-visible::before, .tosa-template-row [data-tooltip]:focus-visible::after { opacity:1; }
        .tosa-template-replace summary[data-tooltip] { position:absolute; }
        .tosa-template-row, .tosa-call, .tosa-candidate { padding:.75rem 0; border-top:1px solid #f1e9eb; }
        .tosa-template-row { position:relative; }
        .tosa-template-row .tosa-file-row { padding:0; border:0; }
        .tosa-template-row.has-replace .tosa-file-actions { padding-right:2.8rem; }
        .tosa-template-replace summary { position:absolute; top:.75rem; right:0; list-style:none; }
        .tosa-template-replace summary::-webkit-details-marker { display:none; }
        .tosa-template-replace form { display:grid; gap:.5rem; margin-top:.75rem; padding:.75rem; border:1px solid #f1e9eb; border-radius:10px; }
        .tosa-replace-title { color:#30262a; font-size:.85rem; }
        .tosa-template-replace input[type=file] { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
        .tosa-replace-file-label { justify-self:start; cursor:pointer; }
        .tosa-template-replace input[type=file]:focus-visible + label { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-template-replace .tosa-note:not(.is-selected):not(.is-error) { display:none; }
        .tosa-template-replace button { justify-self:start; }
        .tosa-footer button:disabled, .org-btn:disabled { opacity:.5; cursor:not-allowed; }
        .tosa-call-form { display:grid; gap:.75rem; width:100%; max-width:100%; margin:.75rem 0 0; box-sizing:border-box; }
        .tosa-call-form label { display:grid; gap:.3rem; font-size:.83rem; font-weight:700; color:#3d3135; }
        .tosa-call-form textarea { width:100%; min-height:6.5rem; resize:vertical; font-weight:400; box-sizing:border-box; }
        .tosa-correction { margin-top:.9rem; padding-top:.85rem; border-top:1px solid #f1e9eb; }
        .tosa-correction summary { cursor:pointer; font-weight:700; color:#842335; padding:.2rem 0; }
        .tosa-correction summary:focus-visible { outline:2px solid #8b1828; outline-offset:2px; border-radius:6px; }
        .tosa-correction form { display:grid; gap:.75rem; margin-top:.75rem; }
        .tosa-correction-fieldset { margin:0; padding:.75rem .85rem; border:1px solid #eadfe2; border-radius:10px; background:#fcf9fa; min-width:0; }
        .tosa-correction-fieldset legend { padding:0 .35rem; font-size:.8rem; font-weight:700; color:#3d3135; }
        .tosa-correction-checks { display:grid; grid-template-columns:repeat(auto-fill, minmax(15rem, 1fr)); gap:.45rem .75rem; margin-top:.35rem; }
        .tosa-check-option { display:flex; align-items:flex-start; gap:.5rem; padding:.4rem .5rem; border:1px solid #efe6e9; border-radius:8px; background:#fff; font-size:.8rem; font-weight:600; color:#30262a; cursor:pointer; }
        .tosa-check-option:hover { border-color:#d9bec4; background:#fdf8f9; }
        .tosa-check-option input[type=checkbox] { margin-top:.18rem; accent-color:#8b1828; width:1rem; height:1rem; flex:none; cursor:pointer; }
        .tosa-check-option input[type=checkbox]:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-correction label.tosa-reason-label { display:grid; gap:.3rem; font-size:.82rem; font-weight:700; color:#3d3135; }
        .tosa-correction textarea { min-height:5rem; resize:vertical; font-weight:400; }
        .tosa-correction button { justify-self:start; }

        /* Top 10 workspace */
        .tosa-top-shell { display:grid; gap:1rem; min-width:0; }
        .tosa-top-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
        .tosa-top-heading { display:flex; align-items:center; gap:.85rem; min-width:0; }
        .tosa-top-heading .tosa-file-icon { width:3rem; height:3rem; font-size:1.35rem; }
        .tosa-top-heading h2 { margin:0; font-size:1.2rem; color:#30262a; }
        .tosa-top-heading p { margin:.2rem 0 0; color:#6b6064; font-size:.84rem; }
        .tosa-top-actions { display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:.65rem; }
        .tosa-top-actions form { margin:0; }
        .tosa-subtabs { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.25rem; padding:.3rem; background:#f7eef0; border:1px solid #ead9dd; border-radius:999px; }
        .tosa-subtabs a { display:flex; align-items:center; justify-content:center; padding:.65rem .95rem; border-radius:999px; color:#7a3541; font-size:.83rem; font-weight:700; text-decoration:none; text-align:center; }
        .tosa-subtabs a:hover { color:#731422; }
        .tosa-subtabs a:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-subtabs a[aria-current=page] { background:#8b1828; color:#fff; }
        .tosa-unranked-notice { display:flex; align-items:flex-start; gap:.55rem; margin:.1rem 0 1rem; color:#6b6064; font-size:.82rem; line-height:1.45; }
        .tosa-unranked-notice i { color:#8b1828; flex:none; }
        .tosa-selection-status { display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem; border:1px solid #d8e6da; border-radius:10px; background:#f3f8f3; color:#315f3c; }
        .tosa-selection-status.is-complete { border-color:#c5e6ce; background:#eef9f0; color:#216b39; }
        .tosa-selection-status.is-attention { border-color:#efd8b2; background:#fff9ef; color:#80530f; }
        .tosa-selection-status.is-attention .tosa-selection-status-icon { background:#fff0d7; color:#80530f; }
        .tosa-selection-status-icon { display:grid; place-items:center; flex:none; width:2.2rem; height:2.2rem; border-radius:50%; background:#d9efde; color:#216b39; font-size:1.1rem; }
        .tosa-selection-status-copy { flex:1; min-width:0; }
        .tosa-selection-status-copy strong, .tosa-selection-status-copy small { display:block; }
        .tosa-selection-status-copy strong { font-size:.88rem; }
        .tosa-selection-status-copy small { margin-top:.2rem; font-size:.78rem; }
        .tosa-selection-progress { display:flex; align-items:center; gap:.55rem; font-size:.8rem; font-weight:700; font-variant-numeric:tabular-nums; }
        .tosa-selection-progress progress { width:7rem; height:.5rem; accent-color:#27834b; }
        .tosa-selection-list-heading { display:flex; align-items:center; gap:.55rem; margin:1.2rem 0 .5rem; }
        .tosa-selection-list-heading h3 { margin:0; }
        .tosa-selection-count { padding:.22rem .55rem; border-radius:999px; background:#faedf0; color:#8b1828; font-size:.73rem; font-weight:700; }
        .tosa-candidate-list { list-style:none; margin:0; padding:0; }
        .tosa-candidate { display:flex; align-items:center; gap:.8rem; min-width:0; padding:.85rem 0; border-top:1px solid #f1e9eb; overflow-wrap:anywhere; }
        .tosa-candidate-copy { flex:1; min-width:0; }
        .tosa-candidate-copy strong { display:block; font-size:.86rem; color:#2b2225; }
        .tosa-candidate-copy small { display:block; margin-top:.15rem; color:#6b6064; font-size:.76rem; }
        .tosa-candidate-status { display:inline-flex; align-items:center; gap:.3rem; color:#216b39; font-size:.8rem; font-weight:700; white-space:nowrap; }
        .tosa-candidate-status.is-attention { color:#80530f; }
        .tosa-candidate form { flex:none; }
        .tosa-remove { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; min-height:2.4rem; padding:.4rem .75rem; border:1px solid #a32236; border-radius:8px; background:#a32236; color:#fff; font:inherit; font-size:.8rem; font-weight:700; cursor:pointer; }
        .tosa-remove:hover:not(:disabled) { background:#821a2a; }
        .tosa-remove:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-remove:disabled { opacity:.5; cursor:not-allowed; }
        .tosa-caption-box { margin-top:1rem; padding-top:1rem; border-top:1px solid #f1e9eb; }
        .tosa-caption-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.9rem; margin-top:.65rem; }
        .tosa-caption-field { display:flex; flex-direction:column; gap:.35rem; min-width:0; }
        .tosa-caption-label-row { display:flex; align-items:baseline; justify-content:space-between; gap:.5rem; }
        .tosa-caption-label-row label { font-size:.82rem; font-weight:700; color:#382c30; }
        .tosa-caption-counter { font-size:.74rem; font-weight:700; color:#6f6367; font-variant-numeric:tabular-nums; }
        .tosa-caption-counter.is-invalid { color:#b42318; }
        .tosa-caption-field textarea { min-height:5.25rem; resize:vertical; }
        .tosa-empty-state { display:grid; justify-items:center; gap:.45rem; padding:2.2rem 1rem; border:1px dashed #e5d6da; border-radius:12px; background:#fcf9fa; text-align:center; color:#6b6064; }
        .tosa-empty-state strong { color:#35292d; font-size:.9rem; }
        .tosa-empty-state p { margin:0; font-size:.82rem; max-width:32rem; }
        .tosa-published-card { padding:0; overflow:hidden; }
        .tosa-published-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; padding:1rem 1.15rem; border-bottom:1px solid #f1e9eb; }
        .tosa-published-head-title { display:flex; align-items:center; gap:.65rem; }
        .tosa-published-head h2 { margin:0; }
        .tosa-published-notice-wrap { padding:0 1.15rem; }
        .tosa-published-table { width:100%; border-collapse:collapse; font-size:.85rem; }
        .tosa-published-table th, .tosa-published-table td { padding:.8rem 1.15rem; text-align:left; border-top:1px solid #f1e9eb; overflow-wrap:anywhere; }
        .tosa-published-table th { background:#fcf8f8; color:#6b6064; font-size:.75rem; font-weight:700; }
        .tosa-published-table td:first-child { width:45%; font-weight:600; color:#382d31; }
        .tosa-published-footer { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.75rem; padding:.9rem 1.15rem; border-top:1px solid #f1e9eb; background:#fdfafb; }
        .tosa-published-privacy { display:flex; align-items:center; gap:.45rem; color:#576b61; font-size:.76rem; font-weight:600; }
        .tosa-published-privacy::before { content:''; width:.5rem; height:.5rem; border-radius:50%; background:#38a87a; flex:none; }
        .tosa-unpublish-dialog { box-sizing:border-box; position:fixed; inset:0; margin:auto; width:min(420px, calc(100% - 2rem)); max-width:none; max-height:calc(100dvh - 2rem); overflow:auto; transform:none; border:1px solid #eadfe2; border-radius:18px; padding:1.6rem; box-shadow:0 18px 55px rgba(0,0,0,.2); text-align:center; color:#30262a; background:#fff; }
        .tosa-unpublish-dialog::backdrop { background:rgba(32,35,45,.52); }
        .tosa-unpublish-icon { display:grid; place-items:center; width:3.2rem; height:3.2rem; margin:0 auto .9rem; border-radius:50%; background:#fae9ed; color:#9a263a; font-size:1.4rem; }
        .tosa-unpublish-dialog h2 { margin:0 0 .45rem; font-size:1.05rem; }
        .tosa-unpublish-dialog p { margin:0; color:#6b6064; font-size:.85rem; line-height:1.5; }
        .tosa-unpublish-actions { display:flex; gap:.6rem; margin-top:1.25rem; }
        .tosa-unpublish-actions > *, .tosa-unpublish-actions button { flex:1; min-width:0; }
        .tosa-unpublish-actions form { display:flex; }
        .tosa-unpublish-actions .org-btn-ghost { background:#eef1f5; border-color:#dde2e9; color:#27303f; }

        /* History tab: TOSA maroon & pink palette */
        .tosa-history-shell { display:grid; gap:1rem; min-width:0; color:#30262a; }
        .tosa-history-toolbar { display:flex; align-items:center; flex-wrap:wrap; gap:.75rem; min-width:0; padding:.75rem 1.15rem; border:1.5px solid #f0e6e8; border-radius:20px; background:#fff; box-shadow:0 4px 16px rgba(90,15,30,.03); }
        .tosa-history-toolbar form { display:flex; align-items:center; flex-wrap:wrap; gap:.75rem; flex:1; min-width:0; }
        .tosa-history-filter-title { display:inline-flex; align-items:center; gap:.35rem; color:#8b1828; font-size:.82rem; font-weight:800; letter-spacing:.02em; white-space:nowrap; }
        .tosa-history-toolbar .tosa-filter-label { color:#6b5f63; }
        .tosa-history-control { min-width:0; max-width:100%; padding:.45rem .8rem; border:1.5px solid #f0e0e3; border-radius:9999px; background:#fdfafb; color:#2b2427; font:inherit; font-size:.78rem; font-weight:700; }
        .tosa-history-control:focus-visible { outline:2px solid #8b1828; outline-offset:2px; }
        .tosa-history-count { display:inline-flex; align-items:center; gap:.35rem; margin-left:auto; padding:.35rem .75rem; border:1px solid #ead4d8; border-radius:9999px; background:#fdf6f7; color:#7a1c2b; font-size:.73rem; font-weight:700; white-space:nowrap; }
        .tosa-history { padding:0; overflow:hidden; background:#fff; border:1px solid #eadfe2; border-radius:14px; color:#30262a; }
        .tosa-history-badge-icon { display:grid; place-items:center; width:2.2rem; height:2.2rem; border-radius:10px; background:#f8edf0; color:#8b1828; font-size:1rem; flex:none; }
        .tosa-history-table-wrap { max-height:min(60dvh, 36rem); overflow:auto; overscroll-behavior:contain; border-radius:0 0 14px 14px; }
        .tosa-history-table-wrap:focus-visible { outline:2px solid #8b1828; outline-offset:-2px; }
        .tosa-history-table { width:100%; border-collapse:collapse; font-size:.84rem; color:#30262a; }
        .tosa-history-table th, .tosa-history-table td { padding:.85rem 1.1rem; text-align:left; vertical-align:top; border-bottom:1px solid #f0e8ea; overflow-wrap:anywhere; }
        .tosa-history-table th { position:sticky; top:0; z-index:2; background:#fcf9fa; color:#63575b; font-size:.75rem; font-weight:700; white-space:nowrap; }
        .tosa-history-table tbody tr:hover { background:#fdf8f9; }
        .tosa-history-time { color:#75696d; font-size:.8rem; font-weight:600; white-space:nowrap; }
        .tosa-history-event-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .65rem; border:1px solid #ead6da; border-radius:999px; background:#f8ecee; color:#8b1828; font-size:.75rem; font-weight:700; }
        .tosa-history-event-badge.is-review { border-color:#bfe3cb; background:#ebf8ef; color:#1f6837; }
        .tosa-history-event-badge.is-returned { border-color:#f1d3a7; background:#fff6e8; color:#8f5109; }
        .tosa-history-event-badge.is-submission { border-color:#cdddf3; background:#eef4fc; color:#2b548a; }
        .tosa-history-detail-main strong { color:#2b2225; font-size:.86rem; }
        .tosa-history-detail-sub { margin-top:.2rem; color:#6b6064; font-size:.78rem; }
        .tosa-history-reason { margin:.5rem 0 0; padding:.65rem .8rem; border-left:3px solid #d79a45; border-radius:0 8px 8px 0; background:#fff9ef; color:#564534; font-size:.8rem; }
        .tosa-history-reason strong { display:block; margin-bottom:.2rem; color:#2b2225; }
        .tosa-history-reason ul { margin:.2rem 0 .4rem; padding-left:1.15rem; color:#564534; }
        .tosa-history-reason p { margin:0; white-space:pre-wrap; color:#564534; }
        .tosa-history-details { margin-top:.45rem; font-size:.79rem; }
        .tosa-history-details summary { width:fit-content; cursor:pointer; color:#8b1828; font-weight:700; }
        .tosa-history-details ul { margin:.4rem 0 0; padding-left:1.15rem; color:#51454a; }
        .tosa-history-actor { display:inline-flex; align-items:center; gap:.35rem; color:#5a4c52; font-size:.8rem; font-weight:600; }
        .tosa-history-empty { display:grid; justify-items:center; gap:.55rem; padding:2.75rem 1.25rem; color:#6b6064; text-align:center; font-size:.85rem; }
        .tosa-history-empty strong { color:#2b2225; font-size:.92rem; }
        .tosa-history-empty p { margin:0; max-width:28rem; color:#6b6064; }
        .tosa-history-pagination { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; margin:0; padding:.85rem 1.15rem; border-top:1px solid #f1e9eb; background:#fdfafb; }
        .tosa-history-pagination .tosa-note { color:#6b6064; margin:0; }
        .tosa-history-pagination .tosa-actions { margin-left:auto; display:inline-flex; align-items:center; gap:.4rem; flex-wrap:nowrap; }
        .tosa-history-reset { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .75rem; border:1px solid #ead6da; border-radius:999px; background:#fff; color:#8b1828; font-size:.78rem; font-weight:700; text-decoration:none; }
        .tosa-history-reset:hover { background:#fbf2f4; border-color:#8b1828; color:#8b1828; }

        @media (max-width:700px) {
            .tosa-toolbar, .tosa-history-toolbar { padding:.8rem; }
            .tosa-toolbar form, .tosa-history-toolbar form { flex:1 1 100%; width:100%; gap:.6rem; }
            .tosa-filter-group.is-search { flex:1 1 100%; }
            .tosa-filter-count, .tosa-history-count { margin-left:0; }
            .tosa-tabs { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); overflow:visible; }
            .tosa-tabs a { display:flex; align-items:center; justify-content:center; min-width:0; min-height:2.75rem; padding:.55rem .6rem; text-align:center; line-height:1.2; font-size:.82rem; overflow-wrap:anywhere; }
            .tosa-subtabs a { padding:.55rem .5rem; }
            .tosa-top-bar { align-items:flex-start; }
            .tosa-top-actions { width:100%; justify-content:flex-end; }
            .tosa-selection-status { flex-wrap:wrap; }
            .tosa-selection-progress { width:100%; justify-content:flex-end; }
            .tosa-candidate { flex-wrap:wrap; }
            .tosa-candidate-copy { flex-basis:calc(100% - 3.5rem); }
            .tosa-candidate .tosa-status { margin-left:3.2rem; }
            .tosa-candidate form { margin-left:auto; }
            .tosa-caption-grid { grid-template-columns:1fr; }
            .tosa-table, .tosa-table tbody, .tosa-table tr, .tosa-table td,
            .tosa-history-table, .tosa-history-table tbody, .tosa-history-table tr, .tosa-history-table td { display:block; width:100%; box-sizing:border-box; }
            .tosa-table thead, .tosa-history-table thead { display:none; }
            .tosa-table tr { border:1px solid #eadfe2; border-radius:12px; padding:.55rem; margin:.55rem 0; }
            .tosa-table td { display:flex; justify-content:space-between; gap:.75rem; border:0; padding:.35rem; }
            .tosa-table td::before { content:attr(data-label); color:#6b6064; font-size:.76rem; font-weight:700; flex:none; }
            .tosa-table td > div { min-width:0; text-align:right; }
            .tosa-history-table tr { border:1px solid #eadfe2; border-radius:10px; padding:.6rem; margin:.6rem .75rem; background:#fff; }
            .tosa-history-table td { display:grid; gap:.2rem; border:0; padding:.35rem .25rem; }
            .tosa-history-table td::before { content:attr(data-label); color:#6b6064; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.02em; }
            .tosa-history-time { white-space:normal; }
            .tosa-file-row { gap:.45rem; }
            .tosa-file-info { gap:.5rem; }
            .tosa-file-actions { gap:.2rem; }
            .tosa-icon-button { width:2.35rem; height:2.5rem; }
            .tosa-template-row.has-replace .tosa-file-actions { padding-right:2.55rem; }
            .tosa-published-table th, .tosa-published-table td { padding:.7rem .85rem; }
            .tosa-history-pagination { padding:.85rem 1rem; }
            .tosa-announcement-grid, .tosa-correction-checks { grid-template-columns:1fr; }
            .tosa-broadcast-footer { flex-direction:column; align-items:flex-start; }
            .tosa-pagination-wrap { width:100%; justify-content:space-between; }
            .tosa-published-footer { align-items:flex-start; }
        }
    </style>

    @php
        $shortlistModel = $tosaShortlist ?? \App\Models\TosaShortlist::query()->find(1);
        $globalDraftIds = array_values(array_unique(array_map('intval', $shortlistModel?->draft_ids ?? [])));
        $globalDraftCount = min(10, count($globalDraftIds));
        $globalPublished = $shortlistModel?->published_at !== null;
        $canEditShortlist = (bool) ($tosaEditing ?? !$globalPublished);
    @endphp

    <div class="tosa-page">
        <nav class="tosa-tabs" aria-label="TOSA sections">
            @foreach ([
                'submissions' => 'Submissions',
                'documents' => 'Documents & Announcements',
                'top10' => 'Top '.$globalDraftCount.'/10',
                'history' => 'History',
            ] as $key => $label)
                <a href="{{ route('office.tosa', ['tab' => $key]) }}" data-tosa-main-tab="{{ $key }}" @if ($tosaTab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        @if (session('success')) <p class="tosa-feedback" role="status">{{ session('success') }}</p> @endif
        @if (session('warning')) <p class="tosa-feedback" role="alert">{{ session('warning') }}</p> @endif
        @if ($errors->any()) <div class="tosa-feedback" role="alert">{{ $errors->first() }}</div> @endif

        @if ($tosaTab === 'submissions')
            <div class="tosa-toolbar" role="search" aria-label="Filter TOSA applications">
                <form id="tosa-submissions-filter-form" method="get" action="{{ route('office.tosa') }}">
                    <input type="hidden" name="tab" value="submissions">
                    <span class="tosa-filter-title"><i class="bi bi-funnel-fill" aria-hidden="true"></i> Filters:</span>
                    <div class="tosa-filter-group is-search">
                        <label class="tosa-filter-label" for="tosa-search"><i class="bi bi-search" aria-hidden="true"></i> Search</label>
                        <input id="tosa-search" class="tosa-filter-control" type="search" name="search" value="{{ $tosaSearch }}" maxlength="100" placeholder="Name, student ID or program">
                    </div>
                    <div class="tosa-filter-group">
                        <label class="tosa-filter-label" for="tosa-result"><i class="bi bi-check2-circle" aria-hidden="true"></i> Status</label>
                        <select id="tosa-result" class="tosa-filter-control" name="result">
                            <option value="all" @selected($tosaResultFilter === 'all')>All statuses</option>
                            <option value="reviewed" @selected($tosaResultFilter === 'reviewed')>Reviewed</option>
                            <option value="qualified" @selected($tosaResultFilter === 'qualified')>Qualified</option>
                            <option value="not_qualified" @selected($tosaResultFilter === 'not_qualified')>Not qualified</option>
                            <option value="pending_review" @selected($tosaResultFilter === 'pending_review')>Pending review</option>
                            <option value="for_correction" @selected($tosaResultFilter === 'for_correction')>For correction</option>
                        </select>
                    </div>
                </form>
                <span class="tosa-filter-count"><i class="bi bi-people-fill" aria-hidden="true"></i> {{ $tosaApplicants->total() }} {{ \Illuminate\Support\Str::plural('applicant', $tosaApplicants->total()) }}</span>
            </div>
            <section class="tosa-card" aria-labelledby="tosaSubmissionsHeading">
                <h2 id="tosaSubmissionsHeading">Applications</h2>
                <div class="tosa-table-wrap" role="region" aria-label="Submitted applications" tabindex="0">
                    <table class="tosa-table">
                        <thead><tr><th>Applicant</th><th>Program / year</th><th>Review / result</th><th>Date sent</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse ($tosaApplicants as $applicant)
                                @php
                                    $returned = $applicant->subsection === 'returned';
                                    $verified = app(\App\Services\TosaWorkflowService::class)->isVerified($applicant);
                                    $inDraft = in_array((int) $applicant->id, $globalDraftIds, true);
                                    $publishedDecision = $shortlistModel?->decisionFor($applicant, $verified);
                                    if ($returned) {
                                        $statusKey = 'for_correction';
                                        $statusLabel = 'For correction';
                                        $statusClass = 'is-for-correction';
                                        $statusIcon = 'bi-arrow-return-left';
                                    } elseif ($globalPublished) {
                                        if ($publishedDecision === 'qualified') {
                                            $statusKey = 'qualified';
                                            $statusLabel = 'Qualified';
                                            $statusClass = 'is-qualified';
                                            $statusIcon = 'bi-check-circle-fill';
                                        } elseif ($publishedDecision === 'not_qualified') {
                                            $statusKey = 'not_qualified';
                                            $statusLabel = 'Not qualified';
                                            $statusClass = 'is-not-qualified';
                                            $statusIcon = 'bi-x-circle';
                                        } elseif ($verified) {
                                            $statusKey = 'reviewed';
                                            $statusLabel = 'Reviewed';
                                            $statusClass = 'is-reviewed';
                                            $statusIcon = 'bi-check-circle';
                                        } else {
                                            $statusKey = 'pending_review';
                                            $statusLabel = 'Pending review';
                                            $statusClass = 'is-pending-review';
                                            $statusIcon = 'bi-hourglass-split';
                                        }
                                    } else {
                                        if ($verified && $inDraft) {
                                            $statusKey = 'qualified';
                                            $statusLabel = 'Qualified';
                                            $statusClass = 'is-qualified';
                                            $statusIcon = 'bi-check-circle-fill';
                                        } elseif ($verified) {
                                            $statusKey = 'reviewed';
                                            $statusLabel = 'Reviewed';
                                            $statusClass = 'is-reviewed';
                                            $statusIcon = 'bi-check-circle';
                                        } else {
                                            $statusKey = 'pending_review';
                                            $statusLabel = 'Pending review';
                                            $statusClass = 'is-pending-review';
                                            $statusIcon = 'bi-hourglass-split';
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td data-label="Applicant"><div><strong>{{ $applicant->full_name }}</strong><small>{{ $applicant->sr_code }}</small></div></td>
                                    <td data-label="Program / year"><div>{{ $applicant->program ?: '—' }}<small>{{ $applicant->year_level ?: '—' }}</small></div></td>
                                    <td data-label="Review / result"><div><span class="tosa-status {{ $statusClass }}" data-status="{{ $statusKey }}"><i class="bi {{ $statusIcon }}" aria-hidden="true"></i> {{ $statusLabel }}</span></div></td>
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
                    $inDraft = in_array((int) $applicant->id, $globalDraftIds, true);
                    $alreadyReviewed = app(\App\Services\TosaWorkflowService::class)->isVerified($applicant);
                @endphp
                <dialog class="tosa-modal" id="tosa-review-{{ $applicant->id }}" aria-labelledby="tosa-review-title-{{ $applicant->id }}">
                    <div class="tosa-modal-body">
                        <header class="tosa-modal-head">
                            <div><h2 id="tosa-review-title-{{ $applicant->id }}">{{ $applicant->full_name }}</h2><p class="tosa-note">{{ $applicant->sr_code }} · {{ $applicant->program }}</p></div>
                            <button type="button" class="tosa-close" data-tosa-close aria-label="Close document review">&times;</button>
                        </header>
                        @if ($returned)
                            <p class="tosa-note"><span class="tosa-status is-for-correction"><i class="bi bi-arrow-return-left" aria-hidden="true"></i> For correction</span> Waiting for the student to resubmit the requested documents.</p>
                        @elseif ($tosaCanManageWorkflow)
                            <p class="tosa-note">Review all seven files, then mark the application as reviewed. Only reviewed applications can be qualified and added to the Top 10 selection.</p>
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
                                @if (!$alreadyReviewed)
                                    <form method="post" action="{{ route('office.tosa.review', $applicant) }}" data-confirm-message="Have you reviewed all seven documents? Mark this application as reviewed?">
                                        @csrf
                                        <button type="submit" class="org-btn org-btn-primary org-btn-sm" @disabled($hasUnavailable)><i class="bi bi-check-circle" aria-hidden="true"></i> Mark as Reviewed</button>
                                    </form>
                                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" disabled title="Review all seven files before qualifying this applicant"><i class="bi bi-star" aria-hidden="true"></i> Qualify and add to Top selection</button>
                                @else
                                    <span class="tosa-status {{ $inDraft && !$globalPublished ? 'is-qualified' : 'is-reviewed' }}">
                                        <i class="bi {{ $inDraft && !$globalPublished ? 'bi-star-fill' : 'bi-check-circle-fill' }}" aria-hidden="true"></i>
                                        {{ $inDraft && !$globalPublished ? 'Qualified (In Top 10)' : 'Reviewed' }}
                                    </span>
                                    @if ($inDraft)
                                        <button type="button" class="org-btn org-btn-primary org-btn-sm" disabled><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Added to Top 10</button>
                                    @else
                                        <form method="post" action="{{ route('office.tosa.top10.add', $applicant) }}" data-tosa-add data-confirm-message="Qualify and add this reviewed applicant to the Top 10 selection?">
                                            @csrf
                                            <button type="submit" class="org-btn org-btn-primary org-btn-sm" @disabled($globalDraftCount >= 10 || !$canEditShortlist)><i class="bi bi-star-fill" aria-hidden="true"></i> Qualify and add to Top selection</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                            @if (!$inDraft && $globalDraftCount >= 10 && $canEditShortlist)<p class="tosa-note">The Top 10 selection is full (10/10). Remove an applicant in the Top 10 tab first.</p>@endif
                            @if ($globalPublished && !$canEditShortlist)<p class="tosa-note">Results are currently published. Unpublish in the Top 10 tab to change the selection.</p>@endif
                        @endif
                        @if ($tosaCanManageWorkflow && !$returned)
                            <details class="tosa-correction">
                                <summary>Return for correction (Document correction)</summary>
                                <form method="post" action="{{ route('office.tosa.subsection', $applicant) }}" data-tosa-correction-form data-confirm="manual" data-confirm-title="Return for Correction?" data-confirm-accept="Yes, Return" data-confirm-message="Return the selected document(s) for correction and email the student? If this applicant is in the Top 10 selection, they will be removed automatically.">
                                    @csrf
                                    <input type="hidden" name="subsection" value="returned">
                                    <fieldset class="tosa-correction-fieldset" aria-describedby="tosa-correction-error-{{ $applicant->id }}">
                                        <legend>Documents requiring correction (select at least one)</legend>
                                        <div class="tosa-correction-checks">
                                            @foreach (\App\Services\TosaRequirements::SUBMISSIONS as $key => $requirement)
                                                <label class="tosa-check-option">
                                                    <input type="checkbox" name="document_keys[]" value="{{ $key }}" data-tosa-correction-check>
                                                    <span>{{ $requirement['code'] }}. {{ $requirement['title'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                    <p id="tosa-correction-error-{{ $applicant->id }}" class="tosa-inline-error" role="alert" data-tosa-correction-error hidden>Select at least one document to return for correction.</p>
                                    <label class="tosa-reason-label" for="tosa-correction-reason-{{ $applicant->id }}">
                                        <span>Reason for correction</span>
                                        <textarea id="tosa-correction-reason-{{ $applicant->id }}" class="tosa-input" name="reason" maxlength="1000" required placeholder="Explain what needs to be corrected or re-uploaded..."></textarea>
                                    </label>
                                    <button type="submit" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-arrow-return-left" aria-hidden="true"></i> Return</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </dialog>
            @endforeach
        @elseif ($tosaTab === 'documents')
            <section class="tosa-card" aria-labelledby="tosaDocumentsHeading">
                <h2 id="tosaDocumentsHeading"><strong>Official Documents</strong></h2>
                <p class="tosa-note">Download the application form and guides.</p>
                @foreach (\App\Services\TosaRequirements::REFERENCES as $key => $reference)
                    @php
                        $template = $tosaTemplates[$reference['title']] ?? null;
                    @endphp
                    <div class="tosa-template-row {{ $tosaCanManageTemplates ? 'has-replace' : '' }}">
                        <div class="tosa-file-row">
                            <div class="tosa-file-info">
                                <span class="tosa-file-icon {{ $reference['format'] === 'XLSX' ? 'is-sheet' : (in_array($reference['format'], ['DOC', 'DOCX'], true) ? 'is-word' : '') }}" aria-hidden="true"><i class="bi {{ $reference['format'] === 'XLSX' ? 'bi-file-earmark-spreadsheet' : (in_array($reference['format'], ['DOC', 'DOCX'], true) ? 'bi-file-earmark-word' : 'bi-file-earmark-pdf') }}"></i></span>
                                <span class="tosa-file-copy"><strong>{{ $reference['title'] }}</strong><small>{{ !empty($template['url']) ? $reference['format'] : 'Unavailable' }}</small></span>
                            </div>
                            <div class="tosa-file-actions">
                                @if (!empty($template['url']))
                                    @if ($reference['format'] === 'XLSX')
                                        <button type="button" class="tosa-icon-button" data-tooltip="View" aria-label="View {{ $reference['title'] }}" data-tosa-workbook-open data-workbook-url="{{ route('office.tosa.workbook.preview') }}" data-workbook-download="{{ $template['url'] }}?download=1"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                    @else
                                        <button type="button" class="tosa-icon-button" data-tooltip="View" aria-label="View {{ $reference['title'] }}" data-document-preview data-document-preview-url="{{ $template['preview_url'] ?: $template['url'] }}" data-document-preview-title="{{ $reference['title'] }}" data-document-original-name="{{ $template['name'] }}" data-document-preview-type="{{ strtolower($reference['format']) }}" data-document-conversion-url="{{ route('office.documents.preview.convert') }}" data-document-preview-download="{{ $template['url'] }}?download=1"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                    @endif
                                    <a class="tosa-icon-button" href="{{ $template['url'] }}?download=1" data-tooltip="Download" aria-label="Download {{ $reference['title'] }}"><i class="bi bi-download" aria-hidden="true"></i></a>
                                @endif
                            </div>
                        </div>
                        @if ($tosaCanManageTemplates)
                            <details class="tosa-template-replace">
                                <summary class="tosa-icon-button" data-tooltip="Replace" aria-label="Replace {{ $reference['title'] }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></summary>
                                <form method="post" action="{{ route('office.tosa.references.store') }}" enctype="multipart/form-data" data-org-upload-form data-confirm-message="Replace this reference file?">
                                    @csrf <input type="hidden" name="reference_key" value="{{ $key }}">
                                    <strong class="tosa-replace-title">Replace {{ $reference['title'] }}</strong>
                                    <input id="tosa-template-file-{{ $key }}" type="file" name="template_file" aria-label="Replacement file for {{ $reference['title'] }}" accept="{{ implode(',', array_map(fn ($extension) => '.'.$extension, $reference['extensions'])) }}" required data-org-upload data-max-size="20480" data-upload-status-id="tosa-template-status-{{ $key }}">
                                    <label class="org-btn org-btn-ghost org-btn-sm tosa-replace-file-label" for="tosa-template-file-{{ $key }}">Choose file</label>
                                    <p id="tosa-template-status-{{ $key }}" class="tosa-note" aria-live="polite"></p>
                                    <button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Upload Replacement</button>
                                </form>
                            </details>
                        @endif
                    </div>
                @endforeach
            </section>
            <section class="tosa-card" aria-labelledby="tosaLetterTemplatesHeading">
                <h2 id="tosaLetterTemplatesHeading"><strong>Decision Letter Templates</strong></h2>
                <p class="tosa-note">Customize the Recognition Day ceremony date, venue, committee signatories, or congratulatory text in Microsoft Word. Ensure your uploaded DOCX retains the @{{NAME}} and @{{PROGRAM}} placeholders.</p>
                @foreach ($tosaLetterTemplates ?? [] as $letter)
                    <div class="tosa-template-row {{ $tosaCanManageTemplates ? 'has-replace' : '' }}">
                        <div class="tosa-file-row">
                            <div class="tosa-file-info">
                                <span class="tosa-file-icon is-word" aria-hidden="true"><i class="bi bi-file-earmark-word"></i></span>
                                <span class="tosa-file-copy"><strong>{{ $letter['title'] }}</strong><small>{{ $letter['is_custom'] ? 'Custom DOCX' : 'DOCX' }}</small></span>
                            </div>
                            <div class="tosa-file-actions">
                                <button type="button" class="tosa-icon-button" data-tooltip="View" aria-label="View {{ $letter['title'] }}" data-document-preview data-document-preview-url="{{ $letter['preview_url'] }}" data-document-preview-title="{{ $letter['title'] }}" data-document-original-name="{{ $letter['filename'] }}" data-document-preview-type="docx" data-document-conversion-url="{{ route('office.documents.preview.convert') }}" data-document-preview-download="{{ $letter['download_url'] }}?download=1"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                <a class="tosa-icon-button" href="{{ $letter['download_url'] }}?download=1" data-tooltip="Download" aria-label="Download {{ $letter['title'] }}"><i class="bi bi-download" aria-hidden="true"></i></a>
                            </div>
                        </div>
                        @if ($tosaCanManageTemplates)
                            <details class="tosa-template-replace">
                                <summary class="tosa-icon-button" data-tooltip="Replace" aria-label="Replace {{ $letter['title'] }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></summary>
                                <form method="post" action="{{ route('office.tosa.letters.template.store') }}" enctype="multipart/form-data" data-org-upload-form data-confirm-message="Replace this decision letter template?">
                                    @csrf <input type="hidden" name="decision" value="{{ $letter['key'] }}">
                                    <strong class="tosa-replace-title">Replace {{ $letter['title'] }}</strong>
                                    <input id="tosa-letter-file-{{ $letter['key'] }}" type="file" name="template_file" aria-label="Replacement file for {{ $letter['title'] }}" accept=".docx" required data-org-upload data-max-size="20480" data-upload-status-id="tosa-letter-status-{{ $letter['key'] }}">
                                    <label class="org-btn org-btn-ghost org-btn-sm tosa-replace-file-label" for="tosa-letter-file-{{ $letter['key'] }}">Choose file</label>
                                    <p id="tosa-letter-status-{{ $letter['key'] }}" class="tosa-note" aria-live="polite"></p>
                                    <button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Upload Replacement</button>
                                </form>
                            </details>
                        @endif
                    </div>
                @endforeach
            </section>
            @if ($tosaCanManageWorkflow)
                <section class="tosa-card tosa-announcement-card" aria-labelledby="tosaComposeHeading">
                    <div class="tosa-announcement-head">
                        <span class="tosa-file-icon" aria-hidden="true"><i class="bi bi-megaphone"></i></span>
                        <h2 id="tosaComposeHeading">Post New Announcement</h2>
                    </div>
                    <form class="tosa-call-form tosa-announcement-form" method="post" action="{{ route('office.updates.announcements.store') }}" enctype="multipart/form-data" data-org-upload-form data-confirm-message="Publish this Call for TOSA?">
                        @csrf
                        <input type="hidden" name="type" value="Call for TOSA">
                        <input type="hidden" name="priority" value="normal">
                        <div class="tosa-announcement-grid">
                            <div class="tosa-announcement-col-left">
                                <div class="tosa-form-field tosa-message-col">
                                    <div class="tosa-field-header">
                                        <label for="tosa-announcement-body">ANNOUNCEMENT MESSAGE *</label>
                                        <span id="tosa-announcement-counter" class="tosa-counter">0/500 characters</span>
                                    </div>
                                    <textarea id="tosa-announcement-body" class="tosa-input" name="body" maxlength="500" required placeholder="Type your announcement or application instructions here...">{{ old('body') }}</textarea>
                                </div>
                                <div class="tosa-form-field tosa-deadline-field">
                                    <label for="tosa-expires-at">APPLICATION DEADLINE (CLOSES ON) *</label>
                                    <div class="tosa-deadline-wrap {{ old('expires_at') ? '' : 'is-empty' }}">
                                        <input id="tosa-expires-at" class="tosa-input tosa-deadline-input" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" required>
                                        <span class="tosa-deadline-placeholder" aria-hidden="true">mm/dd/yyyy --:-- --</span>
                                    </div>
                                </div>
                            </div>
                            <div class="tosa-announcement-col-right">
                                <div class="tosa-form-field tosa-attachment-col">
                                    <div class="tosa-field-header">
                                        <label for="tosa-attachment-input">ATTACHMENT (OPTIONAL)</label>
                                    </div>
                                    <div class="tosa-dropzone" id="tosaDropzone">
                                        <input id="tosa-attachment-input" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" data-org-upload data-max-size="15360" data-upload-status-id="tosa-call-attachment-status" class="tosa-dropzone-input">
                                        <div class="tosa-dropzone-content">
                                            <i class="bi bi-cloud-arrow-up tosa-dropzone-icon" aria-hidden="true"></i>
                                            <span class="tosa-dropzone-text" id="tosaDropzoneText">Click or drag file here</span>
                                            <small class="tosa-dropzone-sub">Max 15MB</small>
                                            <small id="tosa-call-attachment-status" class="tosa-note" aria-live="polite"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tosa-announcement-actions">
                            <button type="submit" class="btn-publish-announcement">
                                <i class="bi bi-send" aria-hidden="true"></i> Publish Announcement
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="tosa-card tosa-announcement-card" aria-labelledby="tosaCallsHeading">
                <header class="tosa-broadcast-head">
                    <div>
                        <h2 id="tosaCallsHeading">Announcement History &amp; Live Feeds</h2>
                        <p class="tosa-note">Active broadcasts currently displayed on student portals.</p>
                    </div>
                    <span class="tosa-broadcast-badge">{{ $tosaCalls->total() }} {{ \Illuminate\Support\Str::plural('Broadcast', $tosaCalls->total()) }}</span>
                </header>

                <div class="tosa-table-wrap" role="region" aria-label="Announcement history and live feeds" tabindex="0">
                    <table class="tosa-table tosa-broadcast-table">
                        <thead>
                            <tr>
                                <th scope="col">STATUS</th>
                                <th scope="col">TITLE &amp; ANNOUNCEMENT DETAILS</th>
                                <th scope="col">DATE POSTED</th>
                                <th scope="col">DEADLINE</th>
                                <th scope="col">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tosaCalls as $call)
                                @php
                                    $callActive = !$call->ended_at && (!$call->expires_at || $call->expires_at->isFuture());
                                    $words = preg_split('/\s+/', trim((string) $call->body)) ?: [];
                                    $previewText = implode(' ', array_slice($words, 0, 18));
                                    if (count($words) > 18) $previewText .= '…';
                                @endphp
                                <tr>
                                    <td data-label="STATUS">
                                        <span class="tosa-status {{ $callActive ? 'is-qualified' : ($call->ended_at ? 'is-for-correction' : 'is-pending-review') }}">
                                            <i class="bi {{ $callActive ? 'bi-check-circle-fill' : ($call->ended_at ? 'bi-archive-fill' : 'bi-clock') }}" aria-hidden="true"></i>
                                            {{ $callActive ? 'Active' : ($call->ended_at ? 'Archived' : 'Expired') }}
                                        </span>
                                    </td>
                                    <td data-label="TITLE &amp; ANNOUNCEMENT DETAILS">
                                        <div>
                                            <strong>{{ $call->title }}</strong>
                                            <small class="tosa-table-sub">{{ $previewText }}</small>
                                        </div>
                                    </td>
                                    <td data-label="DATE POSTED">
                                        <div>{{ $call->created_at?->format('M j, Y') ?: '—' }}</div>
                                    </td>
                                    <td data-label="DEADLINE">
                                        <div>{{ $call->expires_at ? $call->expires_at->format('M j, Y · g:i A') : 'No deadline set' }}</div>
                                    </td>
                                    <td data-label="ACTIONS">
                                        <div class="tosa-actions">
                                            @if ($call->attachment_path)
                                                <a class="org-btn org-btn-ghost org-btn-sm" href="{{ route('office.updates.announcements.attachment', $call) }}" title="Download attachment" aria-label="Download attachment for {{ $call->title }}"><i class="bi bi-paperclip" aria-hidden="true"></i></a>
                                            @endif
                                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-tosa-announcement-edit="{{ $call->id }}" data-announcement-body="{{ e($call->body) }}" data-announcement-expires="{{ $call->expires_at?->format('Y-m-d\TH:i') ?? '' }}" aria-label="Edit announcement">Edit</button>
                                            @if ($tosaCanManageWorkflow && $callActive)
                                                <form method="post" action="{{ route('office.tosa.announcements.end', $call) }}" data-confirm-title="End Announcement?" data-confirm-accept="Yes, End" data-confirm-message="This announcement will be removed from active student notices and kept in history.">
                                                    @csrf
                                                    <button type="submit" class="tosa-btn-end" aria-label="End announcement">End</button>
                                                </form>
                                            @else
                                                <button type="button" class="tosa-btn-end is-disabled" disabled>End</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="tosa-empty-cell">No announcements published yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="tosa-broadcast-footer">
                    <span class="tosa-note">
                        Showing {{ $tosaCalls->firstItem() ?? ($tosaCalls->count() > 0 ? 1 : 0) }} of {{ $tosaCalls->total() }} {{ \Illuminate\Support\Str::plural('announcement', $tosaCalls->total()) }}
                    </span>
                    <div class="tosa-pagination-wrap">
                        <span class="tosa-note">Page {{ $tosaCalls->currentPage() }} of {{ $tosaCalls->lastPage() }}</span>
                        @if ($tosaCalls->hasPages())
                            <div class="tosa-actions">
                                @if ($tosaCalls->previousPageUrl())
                                    <a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaCalls->previousPageUrl() }}">Previous</a>
                                @endif
                                @if ($tosaCalls->nextPageUrl())
                                    <a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaCalls->nextPageUrl() }}">Next</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </section>

        @elseif ($tosaTab === 'top10')
            @php
                $draftIds = $globalDraftIds;
                $allReviewed = $tosaCandidates->count() === 10 && $tosaCandidates->every(fn ($candidate) => $candidate['verified']);
                $resultsPublished = $globalPublished;
                $publishedDecisions = collect($shortlistModel?->published_decisions ?? []);
                $publishedEntries = is_array($shortlistModel?->published_entries) ? $shortlistModel->published_entries : [];
                $defaultQualifiedCaption = 'Congratulations! You have been selected as one of the Top 10 TOSA applicants.';
                $defaultNotQualifiedCaption = 'Thank you for applying to TOSA. You were not selected for the Top 10.';
                $storedQualifiedCaption = $shortlistModel?->caption_qualified
                    ?? data_get($publishedDecisions->first(fn ($decision) => ($decision['status'] ?? null) === 'qualified'), 'caption')
                    ?? $defaultQualifiedCaption;
                $storedNotQualifiedCaption = $shortlistModel?->caption_not_qualified
                    ?? data_get($publishedDecisions->first(fn ($decision) => ($decision['status'] ?? null) === 'not_qualified'), 'caption')
                    ?? $defaultNotQualifiedCaption;
                $qualifiedCaption = (string) old('caption_qualified', $storedQualifiedCaption);
                $notQualifiedCaption = (string) old('caption_not_qualified', $storedNotQualifiedCaption);
                $qualifiedLength = mb_strlen(trim($qualifiedCaption));
                $notQualifiedLength = mb_strlen(trim($notQualifiedCaption));
                $captionsValid = $qualifiedLength >= 1 && mb_strlen($qualifiedCaption) <= 300
                    && $notQualifiedLength >= 1 && mb_strlen($notQualifiedCaption) <= 300;
                $canPublish = count($draftIds) === 10 && $allReviewed && $captionsValid && $canEditShortlist;
                $requestedSection = $tosaSection ?? request('section');
                $activeSection = in_array($requestedSection, ['selection', 'published'], true)
                    ? $requestedSection
                    : ($resultsPublished ? 'published' : 'selection');
            @endphp

            <div class="tosa-top-shell" data-tosa-top-workspace data-can-publish-candidates="{{ count($draftIds) === 10 && $allReviewed && $canEditShortlist ? '1' : '0' }}" data-editing="{{ $canEditShortlist ? '1' : '0' }}">
                <div class="tosa-top-bar">
                    <div class="tosa-top-heading">
                        <span class="tosa-file-icon" aria-hidden="true"><i class="bi bi-trophy"></i></span>
                        <div><h2>Top 10</h2><p>Manage the Top 10 applicants for this application.</p></div>
                    </div>
                    @if ($tosaCanManageWorkflow)
                        <div class="tosa-top-actions" role="region" aria-label="Top 10 actions">
                            @if (count($draftIds) >= 10 || !$canEditShortlist)
                                <button type="button" class="org-btn org-btn-ghost org-btn-sm" disabled><i class="bi bi-person-plus" aria-hidden="true"></i> Add applicants</button>
                            @else
                                <a class="org-btn org-btn-ghost org-btn-sm" href="{{ route('office.tosa', ['tab' => 'submissions', 'result' => 'reviewed']) }}"><i class="bi bi-person-plus" aria-hidden="true"></i> Add applicants</a>
                            @endif
                            <form id="tosa-publish-form" method="post" action="{{ route('office.tosa.top10.save') }}" data-tosa-publish-form data-confirm="manual" data-confirm-title="Publish Top 10 Results?" data-confirm-accept="Yes, Publish" data-confirm-message="Publish private result notices for reviewed applicants? Selected applicants will be marked Qualified and other reviewed applicants Not qualified.">
                                @csrf
                                <input type="hidden" name="intent" value="publish">
                                @foreach ($draftIds as $id)<input type="hidden" name="applicant_ids[]" value="{{ $id }}">@endforeach
                                <button type="submit" class="org-btn org-btn-primary org-btn-sm" data-tosa-publish @disabled(!$canPublish)>
                                    <i class="bi bi-send-check" aria-hidden="true"></i> Publish results
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <nav class="tosa-subtabs" aria-label="Top 10 views">
                    <a href="{{ route('office.tosa', ['tab' => 'top10', 'section' => 'selection']) }}" data-tosa-section-tab="selection" @if ($activeSection === 'selection') aria-current="page" @endif>Top Selection</a>
                    <a href="{{ route('office.tosa', ['tab' => 'top10', 'section' => 'published']) }}" data-tosa-section-tab="published" @if ($activeSection === 'published') aria-current="page" @endif>Published Top 10</a>
                </nav>

                <section class="tosa-card" id="tosa-section-selection" data-tosa-section-panel="selection" aria-labelledby="tosaTopHeading" @if ($activeSection !== 'selection') hidden @endif>
                    <p class="tosa-unranked-notice"><i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>The top ten applicants are presented without ranking or indicating their order of selection.</span></p>
                    <div class="tosa-selection-status {{ $allReviewed ? 'is-complete' : (count($draftIds) === 10 ? 'is-attention' : '') }}">
                        <span class="tosa-selection-status-icon" aria-hidden="true"><i class="bi {{ $allReviewed ? 'bi-check-circle-fill' : 'bi-info-circle' }}"></i></span>
                        <div class="tosa-selection-status-copy">
                            @if ($allReviewed)
                                <strong>Your Top 10 is complete</strong>
                                <small data-tosa-selection-guidance>@if ($resultsPublished) Results are published. Unpublish to make a change. @elseif (!$tosaCanManageWorkflow) The Top 10 selection is ready for OSO review. @elseif (!$captionsValid) Complete both captions before publishing. @else You can publish the results or remove an applicant to make a change. @endif</small>
                            @elseif (count($draftIds) === 10)
                                <strong>Review needed</strong>
                                <small>Review every selected applicant before publishing.</small>
                            @else
                                <strong>Top selection in progress</strong>
                                <small>Add reviewed applicants to complete your selection.</small>
                            @endif
                        </div>
                        <div class="tosa-selection-progress"><progress value="{{ $globalDraftCount }}" max="10" aria-label="Selected applicants"></progress><span>{{ $globalDraftCount }}/10</span></div>
                    </div>
                    @if ($resultsPublished)
                        <p class="tosa-note">Results are currently published and read-only. Unpublish in the Published Top 10 tab to modify the selection or captions.</p>
                    @endif

                    <div class="tosa-selection-list-heading"><h3 id="tosaTopHeading">Selected applicants</h3><span class="tosa-selection-count">{{ $globalDraftCount }} of 10</span></div>
                    @if ($tosaCandidates->isNotEmpty())
                        <ul class="tosa-candidate-list" aria-label="Selected applicants">
                            @foreach ($tosaCandidates as $candidate)
                                <li class="tosa-candidate">
                                    <span class="tosa-file-icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                                    <div class="tosa-candidate-copy">
                                        <strong>{{ $candidate['applicant']?->full_name ?? 'Applicant unavailable' }}</strong>
                                        <small>{{ $candidate['applicant']?->sr_code ?: '—' }} · {{ $candidate['applicant']?->program ?: '—' }}</small>
                                    </div>
                                    @if ($candidate['verified'])
                                        <span class="tosa-status is-qualified"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Qualified</span>
                                    @else
                                        <span class="tosa-status is-pending-review"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Review needed</span>
                                    @endif
                                    @if ($tosaCanManageWorkflow)
                                        <form method="post" action="{{ route('office.tosa.top10.remove', $candidate['id']) }}" data-confirm-title="Remove from Top Selection?" data-confirm-accept="Yes, Remove" data-confirm-message="Remove this applicant from the Top 10 selection? Their status will return to Reviewed and their application will not be deleted.">
                                            @csrf
                                            <button type="submit" class="tosa-remove" aria-label="Remove {{ $candidate['applicant']?->full_name ?? 'unavailable applicant' }} from Top 10 selection" @disabled(!$canEditShortlist)><i class="bi bi-trash" aria-hidden="true"></i> Remove</button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="tosa-empty-state">
                            <span class="tosa-file-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                            <strong>No applicants in Top Selection yet</strong>
                            <p>Review submitted applications first, then add up to 10 reviewed applicants to prepare the Top 10 selection.</p>
                        </div>
                    @endif


                    @if ($tosaCanManageWorkflow)
                        <div class="tosa-caption-box" hidden style="display:none !important;">
                            <h3>Student announcement captions</h3>
                            <div class="tosa-caption-grid">
                                <div class="tosa-caption-field">
                                    <div class="tosa-caption-label-row">
                                        <label for="tosa-caption-qualified">Qualified caption</label>
                                        <span id="tosa-caption-qualified-count" class="tosa-caption-counter {{ $qualifiedLength < 1 || mb_strlen($qualifiedCaption) > 300 ? 'is-invalid' : '' }}" data-tosa-caption-count="caption_qualified">{{ mb_strlen($qualifiedCaption) }}/300</span>
                                    </div>
                                    <textarea id="tosa-caption-qualified" class="tosa-input" name="caption_qualified" form="tosa-publish-form" maxlength="300" required aria-describedby="tosa-caption-qualified-count tosa-caption-qualified-error" data-tosa-caption="caption_qualified" @readonly(!$canEditShortlist)>{{ $qualifiedCaption }}</textarea>
                                    <p id="tosa-caption-qualified-error" class="tosa-inline-error" role="alert" data-tosa-caption-error="caption_qualified" hidden>Enter a Qualified caption (1 to 300 characters).</p>
                                </div>
                                <div class="tosa-caption-field">
                                    <div class="tosa-caption-label-row">
                                        <label for="tosa-caption-not-qualified">Not qualified caption</label>
                                        <span id="tosa-caption-not-qualified-count" class="tosa-caption-counter {{ $notQualifiedLength < 1 || mb_strlen($notQualifiedCaption) > 300 ? 'is-invalid' : '' }}" data-tosa-caption-count="caption_not_qualified">{{ mb_strlen($notQualifiedCaption) }}/300</span>
                                    </div>
                                    <textarea id="tosa-caption-not-qualified" class="tosa-input" name="caption_not_qualified" form="tosa-publish-form" maxlength="300" required aria-describedby="tosa-caption-not-qualified-count tosa-caption-not-qualified-error" data-tosa-caption="caption_not_qualified" @readonly(!$canEditShortlist)>{{ $notQualifiedCaption }}</textarea>
                                    <p id="tosa-caption-not-qualified-error" class="tosa-inline-error" role="alert" data-tosa-caption-error="caption_not_qualified" hidden>Enter a Not qualified caption (1 to 300 characters).</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </section>

                <section class="tosa-card tosa-published-card" id="tosa-section-published" data-tosa-section-panel="published" aria-labelledby="tosaPublishedHeading" @if ($activeSection !== 'published') hidden @endif>
                    <header class="tosa-published-head">
                        <div class="tosa-published-head-title">
                            <span class="tosa-file-icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                            <div>
                                <h2 id="tosaPublishedHeading">Published Top 10</h2>
                                @if ($resultsPublished)
                                    <p class="tosa-note">Published {{ $shortlistModel->published_at->format('M j, Y · g:i A') }}</p>
                                @endif
                            </div>
                        </div>
                        @if ($resultsPublished && $tosaCanManageWorkflow)
                            <button type="button" class="org-btn org-btn-primary org-btn-sm" data-tosa-unpublish-open><i class="bi bi-eye-slash" aria-hidden="true"></i> Unpublish results</button>
                        @endif
                    </header>

                    <div class="tosa-published-notice-wrap">
                        <p class="tosa-unranked-notice"><i class="bi bi-info-circle-fill" aria-hidden="true"></i><span>The top ten applicants are presented without ranking or indicating their order of selection.</span></p>
                    </div>

                    @if ($resultsPublished && count($publishedEntries) > 0)
                        <table class="tosa-published-table" aria-labelledby="tosaPublishedHeading">
                            <thead><tr><th scope="col">Student name</th><th scope="col">Program</th><th scope="col">Status</th></tr></thead>
                            <tbody>
                                @foreach ($publishedEntries as $entry)
                                    <tr>
                                        <td>{{ $entry['name'] }}</td>
                                        <td>{{ ($entry['program'] ?? null) ?: '—' }}</td>
                                        <td><span class="tosa-status is-qualified"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Qualified</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="tosa-published-footer">
                            <span class="tosa-published-privacy">Office only · Students see only their own result.</span>
                        </div>
                    @else
                        <div class="tosa-modal-body">
                            <div class="tosa-empty-state">
                                <span class="tosa-file-icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                                <strong>No published Top 10 results</strong>
                                <p>Complete the 10-applicant selection and captions in Top Selection, then publish when ready.</p>
                            </div>
                        </div>
                    @endif
                </section>

                @if ($resultsPublished && $tosaCanManageWorkflow)
                    <dialog class="tosa-unpublish-dialog" id="tosaUnpublishDialog" aria-labelledby="tosaUnpublishTitle" aria-describedby="tosaUnpublishDescription">
                        <span class="tosa-unpublish-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span>
                        <h2 id="tosaUnpublishTitle">Unpublish TOSA results?</h2>
                        <p id="tosaUnpublishDescription">This hides all published result notices from students and unlocks the Top 10 selection and captions for editing.</p>
                        <div class="tosa-unpublish-actions">
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-tosa-unpublish-close>Cancel</button>
                            <form method="post" action="{{ route('office.tosa.top10.save') }}" data-confirm="off" data-tosa-unpublish-form>
                                @csrf <input type="hidden" name="intent" value="unpublish">
                                <button type="submit" class="org-btn org-btn-primary org-btn-sm">Yes, unpublish</button>
                            </form>
                        </div>
                    </dialog>
                @endif
            </div>
        @else
            @php
                $historySearch = (string) ($tosaHistorySearch ?? request('history_search', ''));
                $historyActivity = (string) ($tosaHistoryActivity ?? request('activity', 'all'));
                $historyActivity = in_array($historyActivity, ['all', 'publishing', 'applicant_changes'], true) ? $historyActivity : 'all';
                $historyFiltered = $historySearch !== '' || $historyActivity !== 'all';
                $eventTypes = [
                    'submitted' => ['Application submitted', 'bi-file-earmark-arrow-up', 'is-submission'],
                    'resubmitted' => ['Application resubmitted', 'bi-arrow-repeat', 'is-submission'],
                    'reviewed' => ['Documents reviewed', 'bi-check-circle', 'is-review'],
                    'returned' => ['Returned for correction', 'bi-arrow-return-left', 'is-returned'],
                    'call_published' => ['TOSA call published', 'bi-megaphone', ''],
                    'call_ended' => ['TOSA call archived', 'bi-archive', ''],
                    'filing_opened' => ['TOSA applications enabled', 'bi-unlock', 'is-review'],
                    'filing_closed' => ['TOSA applications closed', 'bi-lock', 'is-returned'],
                    'top10_saved' => ['Added to Top 10 selection', 'bi-star-fill', 'is-review'],
                    'top10_removed' => ['Removed from Top 10 selection', 'bi-person-dash', 'is-returned'],
                    'selection_updated' => ['Top 10 selection updated', 'bi-sliders', ''],
                    'top10_published' => ['Top 10 published', 'bi-award', 'is-review'],
                    'top10_unpublished' => ['Top 10 unpublished', 'bi-eye-slash', 'is-returned'],
                    'result_email_sent' => ['Result email sent', 'bi-envelope-check', 'is-review'],
                    'result_email_failed' => ['Result email failed', 'bi-envelope-exclamation', 'is-returned'],
                    'result_email_skipped' => ['Result email skipped (dummy)', 'bi-envelope-slash', ''],
                ];
            @endphp
            <div class="tosa-history-shell">
                <div class="tosa-history-toolbar" role="search" aria-label="Filter TOSA history">
                    <form id="tosa-history-filter-form" method="get" action="{{ route('office.tosa') }}">
                        <input type="hidden" name="tab" value="history">
                        <span class="tosa-history-filter-title"><i class="bi bi-funnel" aria-hidden="true"></i> History filters:</span>
                        <div class="tosa-filter-group is-search">
                            <label class="tosa-filter-label" for="tosa-history-search"><i class="bi bi-search" aria-hidden="true"></i> Search</label>
                            <input id="tosa-history-search" class="tosa-history-control" type="search" name="history_search" value="{{ $historySearch }}" maxlength="100" placeholder="Applicant, staff, document or reason">
                        </div>
                        <div class="tosa-filter-group">
                            <label class="tosa-filter-label" for="tosa-history-activity"><i class="bi bi-activity" aria-hidden="true"></i> Activity</label>
                            <select id="tosa-history-activity" class="tosa-history-control" name="activity">
                                <option value="all" @selected($historyActivity === 'all')>All activities</option>
                                <option value="publishing" @selected($historyActivity === 'publishing')>Publishing</option>
                                <option value="applicant_changes" @selected($historyActivity === 'applicant_changes')>Applicant changes</option>
                            </select>
                        </div>
                    </form>
                    <span class="tosa-history-count">{{ $tosaEvents->total() }} {{ \Illuminate\Support\Str::plural('record', $tosaEvents->total()) }}</span>
                </div>

                <section class="tosa-history" aria-label="TOSA history">

                    @if ($tosaEvents->isNotEmpty())
                        <div class="tosa-history-table-wrap" role="region" aria-label="TOSA activity log" tabindex="0">
                            <table class="tosa-history-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Date &amp; time</th>
                                        <th scope="col">Event</th>
                                        <th scope="col">Details &amp; description</th>
                                        <th scope="col">Updated by</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tosaEvents as $event)
                                        @php
                                            $eventType = $eventTypes[$event->action] ?? ['TOSA activity', 'bi-clock-history'];
                                            $details = is_array($event->details) ? $event->details : [];
                                            $applicantName = $event->applicant?->full_name ?? ($details['applicant_name'] ?? null);
                                            $docKeys = [];
                                            if (!empty($details['document_keys']) && is_array($details['document_keys'])) {
                                                $docKeys = $details['document_keys'];
                                            } elseif (!empty($details['document_key']) && is_string($details['document_key'])) {
                                                $docKeys = [$details['document_key']];
                                            }
                                            $docTitles = [];
                                            foreach ($docKeys as $docKey) {
                                                $req = \App\Services\TosaRequirements::SUBMISSIONS[$docKey] ?? null;
                                                $docTitles[] = $req ? ($req['code'].'. '.$req['title']) : (string) $docKey;
                                            }
                                        @endphp
                                        <tr>
                                            <td data-label="Date &amp; time">
                                                <time class="tosa-history-time" datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('M j, Y · g:i A') }}</time>
                                            </td>
                                            <td data-label="Event">
                                                <span class="tosa-history-event-badge {{ $eventType[2] ?? '' }}"><i class="bi {{ $eventType[1] }}" aria-hidden="true"></i> {{ $eventType[0] }}</span>
                                            </td>
                                            <td data-label="Details &amp; description">
                                                <div class="tosa-history-detail-main">
                                                    @if ($applicantName)
                                                        <strong>{{ $applicantName }}</strong>
                                                        @if ($event->action === 'top10_removed' && ($details['cause'] ?? null) === 'correction')
                                                            <span class="tosa-history-detail-sub">— Automatically removed from Top 10 due to document correction request.</span>
                                                        @endif
                                                    @elseif (!empty($details['summary']) || !empty($details['description']))
                                                        <span>{{ $details['summary'] ?? $details['description'] }}</span>
                                                    @else
                                                        <span>{{ $eventType[0] }}</span>
                                                    @endif
                                                </div>
                                                @if ($event->action === 'returned')
                                                    <div class="tosa-history-reason">
                                                        @if (count($docTitles) > 1)
                                                            <strong>Documents returned ({{ count($docTitles) }}):</strong>
                                                            <ul>
                                                                @foreach ($docTitles as $docTitle)
                                                                    <li>{{ $docTitle }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @elseif (count($docTitles) === 1)
                                                            <strong>{{ $docTitles[0] }}</strong>
                                                        @else
                                                            <strong>TOSA document</strong>
                                                        @endif
                                                        @if (!empty($details['reason']))
                                                            <p>{{ $details['reason'] }}</p>
                                                        @endif
                                                    </div>
                                                @endif
                                                @if (!empty($details['names']) && is_array($details['names']))
                                                    <details class="tosa-history-details">
                                                        <summary>View published names ({{ count($details['names']) }})</summary>
                                                        <ul>
                                                            @foreach ($details['names'] as $name)
                                                                <li>{{ $name }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </details>
                                                @endif
                                            </td>
                                            <td data-label="Updated by">
                                                <span class="tosa-history-actor"><i class="bi bi-person" aria-hidden="true"></i> <span>{{ $event->actor_name }}</span></span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="tosa-history-empty">
                            <span class="tosa-history-badge-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                            @if ($historyFiltered)
                                <strong>No matching history records</strong>
                                <p>No events matched your search or activity filter. Reset the filters to view all recorded activity.</p>
                                <a href="{{ route('office.tosa', ['tab' => 'history']) }}" class="tosa-history-reset"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset filters</a>
                            @else
                                <strong>No recorded activity yet</strong>
                                <p>Application reviews, correction requests, Top 10 changes, and publication events will appear here.</p>
                            @endif
                        </div>
                    @endif

                    @if ($tosaEvents->isNotEmpty())
                        <nav class="tosa-history-pagination" aria-label="History pages">
                            <span class="tosa-note">Page {{ $tosaEvents->currentPage() }} of {{ $tosaEvents->lastPage() }}</span>
                            <div class="tosa-actions">
                                @if ($tosaEvents->previousPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaEvents->previousPageUrl() }}">Previous</a>@endif
                                @if ($tosaEvents->nextPageUrl())<a class="org-btn org-btn-ghost org-btn-sm" href="{{ $tosaEvents->nextPageUrl() }}">Next</a>@endif
                            </div>
                        </nav>
                    @endif
                </section>
            </div>
        @endif
    </div>

    @include('org.partials.document-preview')
    @include('org.partials.tosa-workbook-preview')
    @push('scripts')
        <script>
            (function () {
                document.querySelectorAll('[data-tosa-open]').forEach((button) => {
                    button.addEventListener('click', () => {
                        document.getElementById('tosa-review-' + button.dataset.tosaOpen)?.showModal();
                    });
                });
                document.querySelectorAll('[data-tosa-close]').forEach((button) => {
                    button.addEventListener('click', () => button.closest('dialog')?.close());
                });
                document.querySelector('[data-tosa-unpublish-open]')?.addEventListener('click', () => {
                    document.getElementById('tosaUnpublishDialog')?.showModal();
                });
                document.querySelector('[data-tosa-unpublish-close]')?.addEventListener('click', () => {
                    document.getElementById('tosaUnpublishDialog')?.close();
                });
                document.querySelector('[data-tosa-unpublish-form]')?.addEventListener('submit', (event) => {
                    if (event.submitter) event.submitter.disabled = true;
                });
                document.querySelectorAll('[data-tosa-add]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        if (event.defaultPrevented || !event.submitter) return;
                        event.submitter.disabled = true;
                        event.submitter.textContent = 'Adding…';
                    });
                });

                // Multi-document correction checkbox validation
                document.querySelectorAll('[data-tosa-correction-form]').forEach((form) => {
                    const checks = Array.from(form.querySelectorAll('[data-tosa-correction-check]'));
                    const errorEl = form.querySelector('[data-tosa-correction-error]');
                    const validateChecks = (showError) => {
                        const checkedCount = checks.filter((cb) => cb.checked).length;
                        const valid = checkedCount > 0;
                        if (errorEl) errorEl.hidden = !showError || valid;
                        checks.forEach((cb) => cb.setCustomValidity(''));
                        return valid;
                    };
                    checks.forEach((cb) => {
                        cb.addEventListener('change', () => validateChecks(false));
                    });
                    form.addEventListener('submit', (event) => {
                        if (!validateChecks(true)) {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            if (checks[0]) {
                                checks[0].setCustomValidity('Select at least one document to return for correction.');
                                checks[0].reportValidity();
                                checks[0].setCustomValidity('');
                                checks[0].focus();
                            }
                            return;
                        }
                        if (!form.reportValidity()) {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            return;
                        }
                        if (window.OrgActionConfirmations?.confirmSubmit) {
                            window.OrgActionConfirmations.confirmSubmit(event);
                        }
                    });
                });

                // Top 10 workspace: internal tabs, live caption counters, validation, and unsaved caption guard
                const workspace = document.querySelector('[data-tosa-top-workspace]');
                if (workspace) {
                    const canPublishCandidates = workspace.dataset.canPublishCandidates === '1';
                    const isEditing = workspace.dataset.editing === '1';
                    const publishForm = document.querySelector('[data-tosa-publish-form]');
                    const publishBtn = document.querySelector('[data-tosa-publish]');
                    const captionInputs = Array.from(workspace.querySelectorAll('[data-tosa-caption]'));
                    const sectionTabs = Array.from(workspace.querySelectorAll('[data-tosa-section-tab]'));
                    const sectionPanels = Array.from(workspace.querySelectorAll('[data-tosa-section-panel]'));
                    const initialValues = new Map(captionInputs.map((el) => [el.name, el.value]));
                    let isSubmittingPublish = false;

                    const activateSection = (targetSection, updateHistory = true) => {
                        let targetHref = null;
                        sectionTabs.forEach((link) => {
                            if (link.dataset.tosaSectionTab === targetSection) {
                                link.setAttribute('aria-current', 'page');
                                targetHref = link.href;
                            } else {
                                link.removeAttribute('aria-current');
                            }
                        });
                        sectionPanels.forEach((panel) => {
                            panel.hidden = panel.dataset.tosaSectionPanel !== targetSection;
                        });
                        if (updateHistory && targetHref && window.history?.replaceState) {
                            window.history.replaceState(null, '', targetHref);
                        }
                    };

                    const hasUnsavedCaptionChanges = () => {
                        if (!isEditing || isSubmittingPublish) return false;
                        return captionInputs.some((el) => el.value !== initialValues.get(el.name));
                    };

                    const validateCaptions = (showInlineErrors) => {
                        let allValid = true;
                        captionInputs.forEach((textarea) => {
                            const key = textarea.dataset.tosaCaption;
                            const rawLength = Array.from(textarea.value).length;
                            const trimmedLength = Array.from(textarea.value.trim()).length;
                            const valid = trimmedLength >= 1 && rawLength <= 300;
                            const countEl = workspace.querySelector(`[data-tosa-caption-count="${key}"]`);
                            const errEl = workspace.querySelector(`[data-tosa-caption-error="${key}"]`);
                            if (countEl) {
                                countEl.textContent = `${rawLength}/300`;
                                countEl.classList.toggle('is-invalid', !valid);
                            }
                            if (!valid) {
                                allValid = false;
                                textarea.setAttribute('aria-invalid', 'true');
                                textarea.setCustomValidity(trimmedLength < 1 ? 'Enter a caption before publishing.' : 'Keep the caption within 300 characters.');
                                if (showInlineErrors && errEl) errEl.hidden = false;
                            } else {
                                textarea.removeAttribute('aria-invalid');
                                textarea.setCustomValidity('');
                                if (errEl) errEl.hidden = true;
                            }
                        });
                        if (publishBtn) {
                            publishBtn.disabled = !(canPublishCandidates && allValid && isEditing);
                            const guidance = workspace.querySelector('[data-tosa-selection-guidance]');
                            if (guidance && canPublishCandidates && isEditing) guidance.textContent = allValid
                                ? 'You can publish the results or remove an applicant to make a change.'
                                : 'Complete both captions before publishing.';
                        }
                        return allValid;
                    };

                    captionInputs.forEach((textarea) => {
                        textarea.addEventListener('input', () => validateCaptions(false));
                        textarea.addEventListener('blur', () => validateCaptions(true));
                    });
                    validateCaptions(false);

                    if (publishForm) {
                        publishBtn?.addEventListener('click', () => {
                            const selectionPanel = workspace.querySelector('[data-tosa-section-panel="selection"]');
                            if (selectionPanel?.hidden) {
                                activateSection('selection');
                            }
                        });

                        publishForm.addEventListener('submit', (event) => {
                            const selectionPanel = workspace.querySelector('[data-tosa-section-panel="selection"]');
                            if (selectionPanel?.hidden) {
                                activateSection('selection');
                            }
                            if (!validateCaptions(true) || !canPublishCandidates || !isEditing) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                const firstInvalid = captionInputs.find((el) => el.getAttribute('aria-invalid') === 'true');
                                firstInvalid?.reportValidity();
                                firstInvalid?.focus();
                                return;
                            }
                            if (window.OrgActionConfirmations?.confirmSubmit) {
                                const approved = window.OrgActionConfirmations.confirmSubmit(event);
                                if (approved) {
                                    isSubmittingPublish = true;
                                }
                            } else {
                                isSubmittingPublish = true;
                            }
                        });
                    }

                    // Switch internal Top Selection / Published Top 10 panels without losing caption state
                    sectionTabs.forEach((tabLink) => {
                        tabLink.addEventListener('click', (event) => {
                            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                            const targetSection = tabLink.dataset.tosaSectionTab;
                            if (!targetSection) return;
                            event.preventDefault();
                            activateSection(targetSection);
                        });
                    });

                    window.addEventListener('beforeunload', (event) => {
                        if (hasUnsavedCaptionChanges()) {
                            event.preventDefault();
                            event.returnValue = '';
                        }
                    });
                }

                // Automatic filter navigation for Submissions and History
                const setupAutoFilter = (formId, searchInputId, selectId) => {
                    const form = document.getElementById(formId);
                    if (!form) return;
                    const searchInput = document.getElementById(searchInputId);
                    const select = document.getElementById(selectId);
                    let debounceTimer;

                    if (select) {
                        select.addEventListener('change', () => {
                            form.submit();
                        });
                    }

                    if (searchInput) {
                        searchInput.addEventListener('input', () => {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(() => {
                                sessionStorage.setItem('tosa_focus_search', searchInputId);
                                form.submit();
                            }, 380);
                        });
                        searchInput.addEventListener('search', () => {
                            clearTimeout(debounceTimer);
                            sessionStorage.setItem('tosa_focus_search', searchInputId);
                            form.submit();
                        });
                        searchInput.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                clearTimeout(debounceTimer);
                                sessionStorage.setItem('tosa_focus_search', searchInputId);
                                form.submit();
                            }
                        });
                    }
                };

                setupAutoFilter('tosa-submissions-filter-form', 'tosa-search', 'tosa-result');
                setupAutoFilter('tosa-history-filter-form', 'tosa-history-search', 'tosa-history-activity');

                const focusInputId = sessionStorage.getItem('tosa_focus_search');
                if (focusInputId) {
                    sessionStorage.removeItem('tosa_focus_search');
                    const inputToFocus = document.getElementById(focusInputId);
                    if (inputToFocus) {
                        inputToFocus.focus();
                        const valLen = inputToFocus.value.length;
                        inputToFocus.setSelectionRange(valLen, valLen);
                    }
                }
                const msgInput = document.getElementById('tosa-announcement-body');
                const msgCounter = document.getElementById('tosa-announcement-counter');
                if (msgInput && msgCounter) {
                    const updateMsgCount = () => {
                        const len = Array.from(msgInput.value).length;
                        msgCounter.textContent = `${len}/500 characters`;
                    };
                    msgInput.addEventListener('input', updateMsgCount);
                    updateMsgCount();
                }
                const deadlineInput = document.getElementById('tosa-expires-at');
                if (deadlineInput) {
                    const updateDeadlinePlaceholder = () => deadlineInput.closest('.tosa-deadline-wrap').classList.toggle('is-empty', !deadlineInput.value);
                    deadlineInput.addEventListener('input', updateDeadlinePlaceholder);
                    deadlineInput.addEventListener('click', (event) => {
                        if (typeof deadlineInput.showPicker !== 'function') return;
                        try {
                            deadlineInput.showPicker();
                            event.preventDefault();
                        } catch (_) {
                            // Leave the browser's native input behavior available.
                        }
                    });
                    deadlineInput.addEventListener('change', updateDeadlinePlaceholder);
                    updateDeadlinePlaceholder();
                }


                const fileInput = document.getElementById('tosa-attachment-input');
                const dropText = document.getElementById('tosaDropzoneText');
                if (fileInput && dropText) {
                    fileInput.addEventListener('change', () => {
                        if (fileInput.files && fileInput.files[0]) {
                            dropText.textContent = fileInput.files[0].name;
                        } else {
                            dropText.textContent = 'Click or drag file here';
                        }
                    });
                }

                document.querySelectorAll('[data-tosa-announcement-edit]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        if (msgInput) {
                            msgInput.value = btn.dataset.announcementBody || '';
                            msgInput.dispatchEvent(new Event('input'));
                        }
                        const expInput = document.getElementById('tosa-expires-at');
                        if (expInput && btn.dataset.announcementExpires) {
                            expInput.value = btn.dataset.announcementExpires;
                            expInput.dispatchEvent(new Event('input'));
                        }
                        document.getElementById('tosaComposeHeading')?.scrollIntoView({ behavior: 'smooth' });
                        msgInput?.focus();
                    });
                });
                @if (session()->has('tosa_open_applicant'))
                    document.getElementById('tosa-review-{{ (int) session('tosa_open_applicant') }}')?.showModal();
                @endif
            })();
        </script>
    @endpush
@endsection
