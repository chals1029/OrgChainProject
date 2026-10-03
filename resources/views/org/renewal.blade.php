@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSo = $role === 'so';
    $window = $renewalWindow ?? null;
    $isOpen = (bool) ($renewalIsOpen ?? false);
    $docs = $requiredDocs ?? [];
    $editorDocs = old('required_docs', $docs);
    if (! is_array($editorDocs)) {
        $editorDocs = $docs;
    }
    $my = $myRenewalSubmission ?? null;
    $uploadedKeys = $my ? $my->uploadedKeys() : [];
    $pct = $my ? $my->completionPercent($docs) : 0;
@endphp

@section('title', 'Organization Renewal')

@section('header')
    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
        <h1><strong>Organization Renewal</strong></h1>
        @if ($isOpen)
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
            OSO controls the renewal filing window. Open it when orgs may submit; lock it anytime.
        @else
            Submit your renewal packet when OSO opens the filing window. Adviser and Dean names are recorded for the approval chain.
        @endif
    </p>
@endsection

@section('actions')
    @if ($window)
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
        .rn-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0.8rem; }
        .rn-grid label { display:grid; gap:0.35rem; font-size:0.78rem; font-weight:800; color:#2b2427; }
        .rn-grid input, .rn-grid select, .rn-grid textarea { width:100%; box-sizing:border-box; padding:0.65rem 0.8rem; font:inherit; border:1.5px solid #f0e0e3; border-radius:12px; background:#fdfafb; }
        .rn-span-2 { grid-column:span 2; }
        .rn-actions { display:flex; justify-content:flex-end; gap:0.55rem; margin-top:1rem; flex-wrap:wrap; }
        .rn-locked { text-align:center; padding:3rem 1.5rem; }
        .rn-locked i { font-size:2.4rem; color:#8b1828; }
        .rn-doc { display:flex; justify-content:space-between; align-items:center; gap:0.75rem; padding:0.7rem 0.85rem; border:1px solid #f0e6e8; border-radius:12px; background:#fdfafb; margin-bottom:0.45rem; }
        .rn-doc strong { font-size:0.84rem; color:#1a1618; }
        .rn-doc small { display:block; color:#786f73; font-size:0.74rem; }
        .rn-pill { font-size:0.7rem; font-weight:800; padding:0.15rem 0.5rem; border-radius:999px; }
        .rn-pill.ok { background:#f0fdf4; color:#15803d; }
        .rn-pill.wait { background:#fff7ed; color:#c2410c; }
        .rn-alert { padding:0.75rem 0.95rem; border-radius:12px; font-size:0.84rem; font-weight:700; margin-bottom:1rem; }
        .rn-alert.ok { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .rn-alert.err { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
        .rn-progress { height:8px; background:#f3e8ea; border-radius:999px; overflow:hidden; margin:0.5rem 0 1rem; }
        .rn-progress > span { display:block; height:100%; background:#8b1828; border-radius:999px; }
        .rn-check { grid-column:1 / -1; display:flex !important; align-items:center; justify-content:flex-start; gap:0.7rem; padding:0.8rem 0.9rem; border:1px solid #f0dfe3; border-radius:12px; background:#fff8f9; cursor:pointer; }
        .rn-check input { width:1.05rem; height:1.05rem; margin:0; flex:0 0 auto; accent-color:#8b1828; }
        .rn-check span { display:grid; gap:0.15rem; line-height:1.3; }
        .rn-check small { color:#7a7074; font-size:0.72rem; font-weight:600; }
        .rn-section-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:0.9rem; }
        .rn-section-head .rn-muted { margin:0; }
        .rn-requirements-scroll { max-height:430px; overflow-y:auto; padding-right:0.3rem; }
        .rn-requirement { display:flex; align-items:center; justify-content:space-between; gap:0.8rem; padding:0.75rem 0.8rem; border:1px solid #f0e6e8; border-radius:14px; background:#fdfafb; margin-bottom:0.5rem; }
        .rn-requirement:last-child { margin-bottom:0; }
        .rn-requirement-info { display:flex; align-items:flex-start; gap:0.7rem; min-width:0; }
        .rn-requirement-index { display:grid; place-items:center; width:1.7rem; height:1.7rem; flex:0 0 auto; border-radius:9px; background:#f9e9ed; color:#8b1828; font-size:0.72rem; font-weight:900; }
        .rn-requirement-info strong { display:block; font-size:0.82rem; color:#1a1618; }
        .rn-requirement-info small { display:block; margin-top:0.15rem; color:#786f73; font-size:0.7rem; }
        .rn-requirement-actions { display:flex; align-items:center; justify-content:flex-end; gap:0.45rem; flex-wrap:wrap; flex:0 0 auto; }
        .rn-template-form { display:flex; align-items:center; gap:0.35rem; margin:0; }
        .rn-template-form input[type=file] { width:170px; max-width:100%; padding:0.35rem; border:1px solid #eadde0; border-radius:8px; background:#fff; font-size:0.7rem; color:#5e565a; }
        .rn-link { display:inline-flex; align-items:center; gap:0.25rem; color:#8b1828; font-size:0.72rem; font-weight:800; text-decoration:none; }
        .rn-link:hover { text-decoration:underline; }
        .rn-editor-shell { grid-column:1 / -1; padding:0.9rem; border:1px solid #f0dfe3; border-radius:14px; background:#fff8f9; }
        .rn-editor-shell h4 { margin:0; font-size:0.9rem; color:#1a1618; }
        .rn-editor-shell .rn-muted { margin:0.2rem 0 0; font-size:0.76rem; }
        .rn-editor-list { display:grid; gap:0.5rem; max-height:300px; overflow-y:auto; padding:0.15rem 0.3rem 0.15rem 0; margin-top:0.8rem; }
        .rn-editor-row { display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:start; gap:0.55rem; padding:0.6rem; border:1px solid #f0e6e8; border-radius:11px; background:#fff; }
        .rn-editor-row input[type=text] { width:100%; box-sizing:border-box; padding:0.55rem 0.65rem; border:1px solid #eadde0; border-radius:9px; background:#fff; color:#2b2427; font:inherit; font-size:0.8rem; }
        .rn-editor-row input[type=text]:focus { outline:2px solid rgba(139,24,40,.18); border-color:#a92b3e; }
        .rn-editor-row .rn-helper { display:block; margin-top:0.22rem; }
        .rn-editor-remove { padding:0.45rem 0.55rem; border:1px solid #fecaca; border-radius:8px; background:#fff; color:#b91c1c; font-size:0.72rem; font-weight:800; cursor:pointer; white-space:nowrap; }
        .rn-editor-remove:hover { background:#fef2f2; }
        .rn-editor-remove:disabled { cursor:not-allowed; opacity:0.45; }
        .rn-helper { color:#786f73; font-size:0.7rem; font-weight:600; }
        .rn-filter-pill { border: 1.5px solid #e2e8f0; border-radius: 999px; background: #fff; color: #475569; font-weight: 700; transition: all .15s ease; cursor: pointer; }
        .rn-filter-pill:hover { background: #f8fafc; border-color: #cbd5e1; }
        .rn-filter-pill.is-active { background: #8b1828 !important; color: #fff !important; border-color: #8b1828 !important; box-shadow: 0 4px 12px rgba(139,24,40,.2); }
        .org-roster-row:hover { background: #fffcfd !important; }
        @media (max-width:720px) { .rn-grid { grid-template-columns:1fr; } .rn-span-2 { grid-column:1; } .rn-doc, .rn-requirement { flex-direction:column; align-items:stretch; } .rn-requirement-actions, .rn-template-form { justify-content:flex-start; } .rn-template-form { flex-wrap:wrap; } .rn-editor-shell .rn-section-head { flex-direction:column; align-items:stretch !important; } .rn-editor-shell .rn-section-head > button { align-self:flex-start; } .rn-editor-row { grid-template-columns:auto minmax(0,1fr); } .rn-editor-remove { grid-column:2; justify-self:start; } }
    </style>

    @if ($errors->any())
        <div class="rn-alert err">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($isOso)
        <section class="rn-card">
            <h3><i class="bi bi-sliders"></i> OSO Renewal Setup</h3>
            <p class="rn-muted">Only OSO can open or lock the SO Renewal tab. Closed by default until you open filing.</p>

            <form method="POST" action="{{ route('office.renewal.window') }}">
                @csrf
                <div class="rn-grid">
                    <label>
                        Academic Year
                        <input type="text" name="academic_year" value="{{ old('academic_year', $window->academic_year ?? '2026-2027') }}" required>
                    </label>
                    <label>
                        Semester
                        <select name="semester" required>
                            @foreach (['1st Semester','2nd Semester','Midyear','Annual'] as $sem)
                                <option value="{{ $sem }}" @selected(old('semester', $window->semester ?? 'Annual') === $sem)>{{ $sem }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Opens at (optional)
                        <input type="datetime-local" name="opens_at" value="{{ old('opens_at', optional($window?->opens_at)->format('Y-m-d\\TH:i')) }}">
                    </label>
                    <label>
                        Closes at (optional)
                        <input type="datetime-local" name="closes_at" value="{{ old('closes_at', optional($window?->closes_at)->format('Y-m-d\\TH:i')) }}">
                    </label>
                    <label class="rn-span-2">
                        Instructions for SO desks
                        <textarea name="instructions" rows="3">{{ old('instructions', $window->instructions ?? 'Upload the complete renewal packet. Select your Organizational Adviser and College Dean for review.') }}</textarea>
                    </label>
                    <div class="rn-editor-shell">
                        <div class="rn-section-head" style="align-items:center;margin-bottom:0;">
                            <div>
                                <h4>Required renewal documents</h4>
                                <p class="rn-muted">Edit the checklist for this filing window. Add only the documents SO desks must submit.</p>
                            </div>
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="addRenewalRequirement">
                                <i class="bi bi-plus-lg"></i> Add requirement
                            </button>
                        </div>
                        <div class="rn-editor-list" id="renewalRequirementsEditor">
                            @foreach ($editorDocs as $index => $doc)
                                @php
                                    $editorKey = is_array($doc) ? ($doc['key'] ?? '') : '';
                                    $editorTitle = is_array($doc) ? ($doc['title'] ?? '') : '';
                                @endphp
                                <div class="rn-editor-row" data-requirement-row>
                                    <span class="rn-requirement-index" data-requirement-number>{{ $index + 1 }}</span>
                                    <div style="min-width:0;">
                                        <input type="hidden" name="required_docs[{{ $index }}][key]" value="{{ $editorKey }}">
                                        <input type="text" name="required_docs[{{ $index }}][title]" value="{{ $editorTitle }}" placeholder="e.g. Board Resolution" required aria-label="Requirement {{ $index + 1 }} title">
                                        <small class="rn-helper">The title shown to the organization.</small>
                                    </div>
                                    <button type="button" class="rn-editor-remove" data-remove-requirement aria-label="Remove requirement {{ $index + 1 }}">
                                        <i class="bi bi-trash3"></i> Remove
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <small class="rn-helper">At least one requirement is required. Existing requirement keys stay stable so uploaded templates and submitted files remain connected.</small>
                    </div>
                    <label class="rn-check">
                        <input type="hidden" name="is_open" value="0">
                        <input type="checkbox" name="is_open" value="1" @checked(old('is_open', $window?->is_open))>
                        <span>
                            <strong>Open renewal filing for Student Organizations</strong>
                            <small>Opening this window publishes a renewal notice in the student portal.</small>
                        </span>
                    </label>
                </div>
                <div class="rn-actions">
                    <button type="submit" class="org-btn org-btn-primary org-btn-sm">
                        <i class="bi bi-save2-fill"></i> Save &amp; Apply Window
                    </button>
                </div>
            </form>
        </section>

        <section class="rn-card">
            <div class="rn-section-head" style="align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h3 style="margin: 0;"><i class="bi bi-buildings"></i> Organization Renewal Eligibility &amp; Status Monitor</h3>
                    <p class="rn-muted" style="margin: 0.2rem 0 0;">Monitor active vs. inactive organizations and qualify or disqualify organizations for AY {{ $window->academic_year ?? '2026-2027' }} renewal.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span class="rn-pill ok" style="font-size: 0.76rem; padding: 0.3rem 0.75rem;">
                        <i class="bi bi-shield-check"></i> {{ $orgStats['qualified'] ?? 0 }} Qualified
                    </span>
                    @if (($orgStats['not_qualified'] ?? 0) > 0)
                        <span class="rn-pill wait" style="font-size: 0.76rem; padding: 0.3rem 0.75rem; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ $orgStats['not_qualified'] }} Not Qualified
                        </span>
                    @endif
                    @if (($orgStats['inactive'] ?? 0) > 0)
                        <span class="rn-pill wait" style="font-size: 0.76rem; padding: 0.3rem 0.75rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                            <i class="bi bi-moon-fill"></i> {{ $orgStats['inactive'] }} Inactive
                        </span>
                    @endif
                </div>
            </div>

            {{-- 4 Metric Cards --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin: 1rem 0;">
                <div style="background: #fffcfd; border: 1.5px solid #f0dfe3; border-radius: 12px; padding: 0.85rem 1rem;">
                    <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #786f73; letter-spacing: 0.04em;">Total Organizations</div>
                    <div style="font-size: 1.6rem; font-weight: 900; color: #1a1618; margin-top: 0.15rem;">{{ $orgStats['total'] ?? 0 }}</div>
                    <small style="color: #8b1828; font-weight: 700; font-size: 0.7rem;">Official ARASOF-Nasugbu</small>
                </div>
                <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 0.85rem 1rem;">
                    <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #15803d; letter-spacing: 0.04em;">Qualified to Renew</div>
                    <div style="font-size: 1.6rem; font-weight: 900; color: #166534; margin-top: 0.15rem;">{{ $orgStats['qualified'] ?? 0 }}</div>
                    <small style="color: #15803d; font-weight: 700; font-size: 0.7rem;">Cleared for packet filing</small>
                </div>
                <div style="background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 0.85rem 1rem;">
                    <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #b91c1c; letter-spacing: 0.04em;">Not Qualified</div>
                    <div style="font-size: 1.6rem; font-weight: 900; color: #991b1b; margin-top: 0.15rem;">{{ $orgStats['not_qualified'] ?? 0 }}</div>
                    <small style="color: #dc2626; font-weight: 700; font-size: 0.7rem;">Deficient / Pending Clearance</small>
                </div>
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 0.85rem 1rem;">
                    <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.04em;">Inactive / Dormant</div>
                    <div style="font-size: 1.6rem; font-weight: 900; color: #1e293b; margin-top: 0.15rem;">{{ $orgStats['inactive'] ?? 0 }}</div>
                    <small style="color: #64748b; font-weight: 700; font-size: 0.7rem;">No current active council</small>
                </div>
            </div>

            {{-- Filter Pills & Live Search --}}
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.85rem; padding: 0.6rem 0.75rem; background: #fff8f9; border: 1px solid #f0dfe3; border-radius: 12px;">
                <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;" id="orgRosterFilterGroup">
                    <button type="button" class="rn-filter-pill is-active" data-filter="all" style="padding: 0.3rem 0.75rem; font-size: 0.74rem;">
                        All ({{ $orgStats['total'] ?? 0 }})
                    </button>
                    <button type="button" class="rn-filter-pill" data-filter="qualified" style="padding: 0.3rem 0.75rem; font-size: 0.74rem;">
                        <i class="bi bi-check-circle-fill" style="color: #16a34a;"></i> Qualified ({{ $orgStats['qualified'] ?? 0 }})
                    </button>
                    <button type="button" class="rn-filter-pill" data-filter="not_qualified" style="padding: 0.3rem 0.75rem; font-size: 0.74rem;">
                        <i class="bi bi-x-circle-fill" style="color: #dc2626;"></i> Not Qualified ({{ $orgStats['not_qualified'] ?? 0 }})
                    </button>
                    <button type="button" class="rn-filter-pill" data-filter="inactive" style="padding: 0.3rem 0.75rem; font-size: 0.74rem;">
                        <i class="bi bi-moon-fill" style="color: #64748b;"></i> Inactive ({{ $orgStats['inactive'] ?? 0 }})
                    </button>
                </div>
                <div style="position: relative; min-width: 240px; flex: 1; max-width: 360px;">
                    <i class="bi bi-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                    <input type="text" id="orgRosterSearchInput" placeholder="Filter by organization or college..." style="width: 100%; box-sizing: border-box; padding: 0.4rem 0.75rem 0.4rem 2.1rem; border: 1.5px solid #e2e8f0; border-radius: 999px; font-size: 0.78rem; background: #fff; color: #1e293b; outline: none;">
                </div>
            </div>

            {{-- Table of Organizations --}}
            <div style="overflow-x: auto; border: 1px solid #f0e6e8; border-radius: 12px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem; text-align: left;">
                    <thead>
                        <tr style="background: #faf4f5; border-bottom: 1.5px solid #ebd9dc; color: #5a4f54; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 0.75rem 0.9rem;">Organization</th>
                            <th style="padding: 0.75rem 0.9rem;">Status</th>
                            <th style="padding: 0.75rem 0.9rem;">Renewal Qualification</th>
                            <th style="padding: 0.75rem 0.9rem;">Disqualification / Clearance Reason</th>
                            <th style="padding: 0.75rem 0.9rem;">Packet Progress</th>
                            <th style="padding: 0.75rem 0.9rem; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="orgRosterTableBody">
                        @forelse ($allOrganizations as $org)
                            @php
                                $isQualified = (bool) ($org['is_qualified_for_renewal'] ?? true);
                                $isActive = (bool) ($org['is_active'] ?? true);
                                $reason = $org['disqualification_reason'] ?? '';
                                $subStatus = $org['submission_status'] ?? 'none';
                                $filterType = ! $isActive ? 'inactive' : (! $isQualified ? 'not_qualified' : 'qualified');
                            @endphp
                            <tr class="org-roster-row" 
                                data-filter-type="{{ $filterType }}"
                                data-is-qualified="{{ $isQualified ? '1' : '0' }}"
                                data-is-active="{{ $isActive ? '1' : '0' }}"
                                data-org-id="{{ $org['id'] }}"
                                data-org-name="{{ e($org['name']) }}"
                                data-org-college="{{ e($org['college']) }}"
                                data-reason="{{ e($reason) }}"
                                style="border-bottom: 1px solid #f3e9eb; background: #fff; transition: background .15s ease;">
                                <td style="padding: 0.75rem 0.9rem; vertical-align: middle;">
                                    <strong style="color: #1a1618; display: block; font-size: 0.84rem;">{{ $org['name'] }}</strong>
                                    <small style="color: #786f73; font-size: 0.74rem;">{{ $org['college'] }}</small>
                                </td>
                                <td style="padding: 0.75rem 0.9rem; vertical-align: middle;">
                                    @if ($isActive)
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">
                                            <i class="bi bi-check-circle-fill" style="font-size: 0.68rem;"></i> Active
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                                            <i class="bi bi-moon-fill" style="font-size: 0.68rem;"></i> Inactive / Dormant
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 0.9rem; vertical-align: middle;">
                                    @if ($isQualified)
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;">
                                            <i class="bi bi-shield-check" style="font-size: 0.72rem;"></i> Qualified
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">
                                            <i class="bi bi-slash-circle-fill" style="font-size: 0.72rem;"></i> Not Qualified
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 0.9rem; vertical-align: middle; max-width: 280px;">
                                    @if (! $isQualified || ! $isActive)
                                        <div style="display: inline-flex; align-items: flex-start; gap: 0.35rem; color: #991b1b; font-size: 0.76rem; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 8px; padding: 0.35rem 0.6rem;">
                                            <i class="bi bi-info-circle-fill" style="margin-top: 0.1rem; flex-shrink: 0; color: #dc2626;"></i>
                                            <span>{{ $reason ?: 'Not qualified by OSO.' }}</span>
                                        </div>
                                    @else
                                        <span style="color: #94a3b8; font-size: 0.76rem;">— Cleared for Renewal —</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 0.9rem; vertical-align: middle;">
                                    @if ($subStatus === 'approved')
                                        <span class="rn-pill ok" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                            <i class="bi bi-check-all"></i> Approved
                                        </span>
                                    @elseif ($subStatus === 'submitted')
                                        <span class="rn-pill wait" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                            <i class="bi bi-send-check"></i> Submitted ({{ $org['docs_count'] ?? 0 }}/{{ count($docs) }})
                                        </span>
                                    @elseif ($subStatus === 'returned')
                                        <span class="rn-pill wait" style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;">
                                            <i class="bi bi-arrow-return-left"></i> Returned
                                        </span>
                                    @elseif ($subStatus === 'draft')
                                        <span class="rn-pill wait" style="background: #fdfafb; color: #786f73; border: 1px solid #f0e6e8;">
                                            <i class="bi bi-pencil-square"></i> Draft
                                        </span>
                                    @else
                                        <span style="color: #94a3b8; font-size: 0.75rem;">Not Started</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 0.9rem; vertical-align: middle; text-align: right; white-space: nowrap;">
                                    <button type="button" 
                                            class="org-btn org-btn-ghost org-btn-sm" 
                                            style="font-size: 0.74rem; padding: 0.3rem 0.65rem;"
                                            onclick='openManageQualificationModal(@json($org))'>
                                        <i class="bi bi-sliders"></i> Set Status
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 2rem; text-align: center; color: #786f73;">No organizations registered in database.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="rn-muted" style="margin-top: 0.75rem; font-size: 0.75rem;">
                <i class="bi bi-shield-lock-fill" style="color: #8b1828;"></i>
                Only OSO coordinators can update organization active and renewal qualification statuses. Changes immediately enforce submission blocks on student desks.
            </p>
        </section>

        <section class="rn-card">
            <h3><i class="bi bi-inbox-fill"></i> Incoming Renewal Packets</h3>
            <p class="rn-muted">Submitted by SO desks for this window. OSO coordinators inspect attached files, verify compliance, then approve or return with remarks.</p>
            @forelse ($renewalSubmissions as $row)
                <div style="border: 1px solid #f0e6e8; border-radius: 14px; background: #fffcfd; padding: 0.95rem 1.1rem; margin-bottom: 0.85rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.85rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 240px;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <strong style="font-size: 0.98rem; color: #1a1618;">{{ $row->organization_name }}</strong>
                                <span class="rn-pill {{ $row->status === 'submitted' ? 'wait' : ($row->status === 'approved' ? 'ok' : 'wait') }}" style="text-transform: uppercase;">
                                    {{ $row->status }}
                                </span>
                            </div>
                            <small style="display: block; margin-top: 0.25rem; color: #786f73; font-size: 0.76rem;">
                                {{ $row->college ?: 'Campus Wide' }} · <strong>Adviser:</strong> {{ $row->adviser_name ?: '—' }} · <strong>Dean:</strong> {{ $row->dean_name ?: '—' }}
                                · <strong style="color: #8b1828;">Docs: {{ $row->documents->count() }}/{{ count($docs) }}</strong>
                                @if ($row->submitted_at)
                                    · Filed {{ $row->submitted_at->format('M j, Y g:i A') }}
                                @endif
                            </small>
                            @if ($row->notes)
                                <div style="margin-top: 0.45rem; padding: 0.45rem 0.65rem; background: #faf5f6; border-left: 2.5px solid #8b1828; border-radius: 6px; font-size: 0.76rem; color: #4a3e42;">
                                    <strong>SO Notes:</strong> {{ $row->notes }}
                                </div>
                            @endif
                            @if ($row->review_remarks)
                                <div style="margin-top: 0.35rem; color: #c2410c; font-size: 0.76rem;">
                                    <i class="bi bi-chat-left-text-fill"></i> <strong>OSO Review Remarks:</strong> {{ $row->review_remarks }}
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                            @if ($row->status === 'submitted')
                                <form method="POST" action="{{ route('office.renewal.review', $row) }}" style="display: inline-flex; margin: 0;">
                                    @csrf
                                    <button type="submit" name="decision" value="approved" class="org-btn org-btn-primary org-btn-sm" title="Approve packet and certify recognition">
                                        <i class="bi bi-check-lg"></i> Approve Renewal
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('office.renewal.review', $row) }}" style="display: inline-flex; margin: 0;" onsubmit="return osoRenewalReturnRemarks(this);">
                                    @csrf
                                    <input type="hidden" name="decision" value="returned">
                                    <input type="hidden" name="remarks" value="">
                                    <button type="submit" class="org-btn org-btn-sm" style="background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa;" title="Return to organization for corrections">
                                        <i class="bi bi-arrow-return-left"></i> Return for Revisions
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Attached Documents Inspection Drawer --}}
                    <details style="margin-top: 0.75rem; border-top: 1px dashed #ebd9dc; padding-top: 0.65rem;">
                        <summary style="cursor: pointer; font-size: 0.78rem; font-weight: 800; color: #8b1828; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i class="bi bi-folder2-open"></i> Inspect Submitted Documents ({{ $row->documents->count() }})
                        </summary>
                        <div style="display: grid; gap: 0.45rem; margin-top: 0.6rem;">
                            @forelse ($row->documents as $doc)
                                @php
                                    $fileUrl = asset('storage/' . $doc->file_path);
                                    $isPdf = str_ends_with(strtolower($doc->file_name ?? ''), '.pdf');
                                    $isImg = in_array(strtolower(pathinfo($doc->file_name ?? '', PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg']);
                                @endphp
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.6rem; padding: 0.55rem 0.75rem; background: #fff; border: 1px solid #f0e6e8; border-radius: 9px;">
                                    <div style="min-width: 0; flex: 1;">
                                        <strong style="display: block; font-size: 0.82rem; color: #1a1618;">{{ $doc->title }}</strong>
                                        <small style="color: #786f73; font-size: 0.72rem;">
                                            <i class="bi bi-paperclip"></i> {{ $doc->file_name }} · Uploaded {{ $doc->created_at->format('M j, Y g:i A') }}
                                        </small>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                                        @if ($isPdf || $isImg)
                                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.72rem; padding: 0.25rem 0.55rem;" onclick="previewRenewalDoc('{{ $fileUrl }}', '{{ addslashes($doc->title) }}')">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        @endif
                                        <a href="{{ $fileUrl }}" download="{{ $doc->file_name }}" class="org-btn org-btn-ghost org-btn-sm" style="font-size: 0.72rem; padding: 0.25rem 0.55rem; color: #1d4ed8; border-color: #bfdbfe;">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <p style="margin: 0; color: #786f73; font-size: 0.75rem;">No documents attached yet.</p>
                            @endforelse
                        </div>
                    </details>
                </div>
            @empty
                <p class="rn-muted" style="margin:0;">No renewal submissions yet.</p>
            @endforelse
        </section>

        <section class="rn-card">
            <div class="rn-section-head">
                <div>
                    <h3><i class="bi bi-paperclip"></i> Official Requirement Files ({{ count($docs) }})</h3>
                    <p class="rn-muted">Attach a blank form or reference file to any requirement. The checklist above controls which requirements SO desks must submit.</p>
                </div>
                <span class="rn-pill ok"><i class="bi bi-shield-check"></i> OSO managed</span>
            </div>
            @if (! $window)
                <div class="rn-alert err" style="margin-bottom:0;">Save a renewal window first, then you can attach official requirement files.</div>
            @else
                <div class="rn-requirements-scroll">
                    @foreach ($docs as $doc)
                        @php
                            $templatePath = $doc['template_path'] ?? null;
                            $templateName = $doc['template_name'] ?? null;
                        @endphp
                        <div class="rn-requirement">
                            <div class="rn-requirement-info">
                                <span class="rn-requirement-index">{{ $loop->iteration }}</span>
                                <div>
                                    <strong>{{ $doc['title'] }}</strong>
                                    <small>
                                        @if ($templateName)
                                            <i class="bi bi-paperclip"></i> {{ $templateName }}
                                        @else
                                            No official file attached yet
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <div class="rn-requirement-actions">
                                @if (! empty($doc['pdf_path']))
                                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="previewRenewalDoc('{{ asset('storage/'.$doc['pdf_path']) }}', '{{ addslashes($doc['title']) }}')" style="padding: 0.3rem 0.6rem; font-size: 0.74rem;">
                                        <i class="bi bi-eye"></i> View PDF
                                    </button>
                                @endif
                                @if ($templatePath)
                                    <a href="{{ asset('storage/'.$templatePath) }}" download class="org-btn org-btn-ghost org-btn-sm" style="padding: 0.3rem 0.6rem; font-size: 0.74rem; color: #1d4ed8;">
                                        <i class="bi bi-file-earmark-word"></i> DOCX
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('office.renewal.requirements.template') }}" enctype="multipart/form-data" class="rn-template-form" data-org-upload-form>
                                    @csrf
                                    <input type="hidden" name="doc_key" value="{{ $doc['key'] }}">
                                    <input type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg" required data-org-upload data-max-size="20480" data-upload-status-id="renewalTemplateStatus{{ $doc['key'] }}" aria-label="Official file for {{ $doc['title'] }}">
                                    <span id="renewalTemplateStatus{{ $doc['key'] }}" class="org-upload-status" aria-live="polite">No file selected.</span>
                                    <button type="submit" class="org-btn org-btn-ghost org-btn-sm">
                                        <i class="bi bi-cloud-arrow-up"></i> {{ $templatePath ? 'Replace' : 'Upload' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
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

            <section class="rn-card">
                <h3><i class="bi bi-file-earmark-plus-fill"></i> Create Renewal Request</h3>
                <p class="rn-muted">{{ $window->instructions ?: 'Complete your org details, then upload all required documents.' }}</p>

                @php
                    $step1Done = (bool) $my;
                    $step2Done = $my && $pct >= 100;
                    $step3Done = $my && ($my->status === 'submitted');
                @endphp
                <ol style="list-style:none;margin:0 0 1rem;padding:0;display:flex;flex-wrap:wrap;gap:0.5rem;">
                    <li style="flex:1;min-width:150px;padding:0.55rem 0.75rem;border-radius:10px;font-size:0.78rem;font-weight:800;{{ $step1Done ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;' : 'background:#8b1828;color:#fff;border:1px solid #8b1828;' }}">
                        1. Save draft {{ $step1Done ? '✓' : '← you are here' }}
                    </li>
                    <li style="flex:1;min-width:150px;padding:0.55rem 0.75rem;border-radius:10px;font-size:0.78rem;font-weight:800;{{ $step2Done ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;' : 'background:#fdfafb;color:#7a7074;border:1px solid #f0e6e8;' }}">
                        2. Upload documents ({{ $pct }}%) {{ $step2Done ? '✓' : '' }}
                    </li>
                    <li style="flex:1;min-width:150px;padding:0.55rem 0.75rem;border-radius:10px;font-size:0.78rem;font-weight:800;{{ $step3Done ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;' : 'background:#fdfafb;color:#7a7074;border:1px solid #f0e6e8;' }}">
                        3. Submit packet {{ $step3Done ? '✓' : '' }}
                    </li>
                </ol>

                <form method="POST" action="{{ route('office.renewal.submit') }}">
                    @csrf
                    <div class="rn-grid">
                        <div class="rn-span-2" style="background: #fff8f9; border: 1.5px solid #f0dfe3; border-radius: 14px; padding: 1.1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: #8b1828; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(139, 24, 40, 0.2);">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #8b1828; display: flex; align-items: center; gap: 0.35rem;">
                                        <span>Recognized Student Organization</span>
                                        <span style="display: inline-block; width: 4px; height: 4px; border-radius: 50%; background: #8b1828;"></span>
                                        <span>ARASOF-Nasugbu</span>
                                    </div>
                                    <strong style="font-size: 1.05rem; color: #1a1618; display: block; margin-top: 0.15rem;">College of Informatics and Computing Sciences Student Council (CICS-SC)</strong>
                                    <small style="color: #786f73; font-size: 0.78rem;">College of Informatics and Computing Sciences · AY {{ $window->academic_year ?? '2026-2027' }}</small>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                                @if ($canSubmitPacket)
                                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.75rem; font-weight: 800; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;">
                                        <i class="bi bi-patch-check-fill"></i> Qualified to Renew
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.75rem; font-weight: 800; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">
                                        <i class="bi bi-x-circle-fill"></i> {{ ! $isTargetOrgActive ? 'Inactive Organization' : 'Renewal Disqualified' }}
                                    </span>
                                @endif
                                <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.75rem; font-weight: 800; background: #fdfafb; color: #786f73; border: 1px solid #f0dfe3;">
                                    <i class="bi bi-shield-lock"></i> CICS-SC
                                </span>
                            </div>
                        </div>

                        <input type="hidden" name="organization_name" value="{{ $targetOrg ?? 'College of Informatics and Computing Sciences Student Council (CICS-SC)' }}">
                        <input type="hidden" name="college" value="{{ $targetCollege ?? 'College of Informatics and Computing Sciences' }}">

                        <label>
                            Organizational Adviser
                            <input type="text" name="adviser_name" value="{{ old('adviser_name', $my->adviser_name ?? 'Dr. Ronel M. Sapungan') }}" placeholder="Full Name of Organization Adviser" required>
                            <small class="rn-helper">Faculty adviser overseeing the organization.</small>
                        </label>
                        <label>
                            College Dean
                            <input type="text" name="dean_name" value="{{ old('dean_name', $my->dean_name ?? 'Dr. Meldrick A. Magsino') }}" placeholder="Full Name of College Dean" required>
                            <small class="rn-helper">Dean of College of Informatics and Computing Sciences.</small>
                        </label>
                        <label class="rn-span-2">
                            Renewal Notes &amp; Highlights (optional)
                            <textarea name="notes" rows="2" placeholder="Brief summary of council milestones, planned initiatives, or notes for OSO evaluation...">{{ old('notes', $my->notes ?? '') }}</textarea>
                        </label>
                    </div>
                    <div class="rn-actions">
                        <button type="submit" name="action" value="draft" class="org-btn org-btn-ghost org-btn-sm">
                            <i class="bi bi-save2"></i> Save CICS-SC Draft
                        </button>
                        <button type="submit" name="action" value="submit" class="org-btn org-btn-primary org-btn-sm" @disabled(! $my || ! $canSubmitPacket)>
                            <i class="bi bi-send-fill"></i> Submit Packet to OSO
                        </button>
                    </div>
                    @if (! $canSubmitPacket)
                        <p class="rn-muted" style="margin-top:0.65rem; color:#dc2626; font-weight:700;">
                            <i class="bi bi-shield-slash"></i> Packet submission is locked because this organization is flagged as not qualified to renew or inactive by OSO.
                        </p>
                    @elseif (! $my)
                        <p class="rn-muted" style="margin-top:0.65rem;">Save a draft first to initialize the CICS-SC renewal dossier, then upload the 10 required recognition documents below.</p>
                    @endif
                </form>
            </section>

            @if ($my)
                {{-- Master Institutional Application Banner --}}
                <section class="rn-card" style="border-left: 4.5px solid #8b1828; background: #fffcfd;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
                        <div style="display: flex; align-items: center; gap: 0.85rem;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #fdf0f2; color: #8b1828; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                            <div>
                                <span style="font-size: 0.72rem; font-weight: 800; color: #8b1828; text-transform: uppercase; letter-spacing: 0.05em;">Master University Form · BatStateU-FO-SOA-01</span>
                                <strong style="display: block; font-size: 0.96rem; color: #1a1618;">Application for Recognition / Renewal of Student Organization (Rev. 03)</strong>
                                <small style="color: #786f73; font-size: 0.76rem;">Official institutional template covering General Information, Objectives, and Certification.</small>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="previewRenewalDoc('/templates/renewal/Copy of BatStateU-FO-SOA-01_Application for Recognition, Renewal of Student Organization_Rev. 03 (1) (1).pdf', 'BatStateU-FO-SOA-01 Master Application Form')">
                                <i class="bi bi-eye"></i> View Master Form
                            </button>
                            <a href="/templates/renewal/Copy of BatStateU-FO-SOA-01_Application for Recognition, Renewal of Student Organization_Rev. 03 (1) (1).docx" download class="org-btn org-btn-ghost org-btn-sm" style="color: #1d4ed8; border-color: #bfdbfe;">
                                <i class="bi bi-download"></i> Download .DOCX
                            </a>
                        </div>
                    </div>
                </section>

                <section class="rn-card">
                    <div class="rn-section-head">
                        <div>
                            <h3><i class="bi bi-cloud-upload-fill"></i> 10 Official Recognition Requirements</h3>
                            <p class="rn-muted">Attach signed copies for each official requirement below. You can view or download each official template directly.</p>
                        </div>
                        <div style="text-align: right;">
                            <span class="rn-pill {{ $pct >= 100 ? 'ok' : 'wait' }}" style="font-size: 0.78rem; padding: 0.25rem 0.75rem;">
                                {{ $pct }}% Complete ({{ collect($my->documents)->count() }}/{{ count($docs) }})
                            </span>
                        </div>
                    </div>
                    <div class="rn-progress"><span style="width:{{ $pct }}%"></span></div>

                    {{-- Physical Signature & Scan Upload Instructions (Data Privacy Compliance) --}}
                    <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 0.85rem 1.1rem; margin-bottom: 1.1rem; display: flex; align-items: flex-start; gap: 0.75rem;">
                        <i class="bi bi-printer-fill" style="color: #0f172a; font-size: 1.35rem; margin-top: 0.1rem; flex-shrink: 0;"></i>
                        <div>
                            <strong style="color: #0f172a; font-size: 0.86rem; display: block;">Physical Wet-Ink Signing &amp; Scan Upload Workflow (Data Privacy Compliant)</strong>
                            <p style="margin: 0.25rem 0 0; color: #475569; font-size: 0.78rem; line-height: 1.45;">
                                In compliance with <strong>Republic Act 10173 (Data Privacy Act)</strong> and university governance policy, signatures are signed physically:<br>
                                <strong>1. Download Template (.docx)</strong> &rarr; Fill in your council officers, member rosters, and activity plans in Word.<br>
                                <strong>2. Print Manually</strong> &rarr; Present the hardcopy to your Faculty Adviser, College Dean, and Council Officers for authentic physical signatures.<br>
                                <strong>3. Scan &amp; Upload</strong> &rarr; Scan or capture the signed hardcopy (PDF or clean scan) and upload it to the corresponding requirement slot below.
                            </p>
                        </div>
                    </div>

                    @foreach ($docs as $doc)
                        @php
                            $uploaded = collect($my->documents)->firstWhere('doc_key', $doc['key']);
                            $tmplDocx = $doc['template_path'] ?? null;
                            $tmplPdf = $doc['pdf_path'] ?? null;
                            $label = $doc['attachment_label'] ?? ('Attachment '.chr(65 + $loop->index));
                        @endphp
                        <div class="rn-doc" style="align-items: center; padding: 0.85rem 1rem; border-radius: 14px; border: 1.5px solid #f0e6e8; background: #ffffff; margin-bottom: 0.65rem;">
                            <div style="display: flex; align-items: center; gap: 0.85rem; min-width: 0; flex: 1;">
                                <div style="width: 38px; height: 38px; border-radius: 9px; background: {{ $uploaded ? '#f0fdf4' : '#fff8f9' }}; color: {{ $uploaded ? '#15803d' : '#8b1828' }}; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; border: 1px solid {{ $uploaded ? '#bbf7d0' : '#f0dfe3' }};">
                                    <i class="bi {{ $uploaded ? 'bi-check-circle-fill' : 'bi-file-earmark-arrow-up' }}"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                                        <span style="font-size: 0.7rem; font-weight: 800; background: #fdf0f2; color: #8b1828; padding: 0.12rem 0.45rem; border-radius: 6px;">{{ $label }}</span>
                                        <strong style="font-size: 0.88rem; color: #1a1618;">{{ $doc['title'] }}</strong>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.65rem; margin-top: 0.25rem; flex-wrap: wrap;">
                                        @if ($tmplPdf)
                                            <button type="button" class="rn-link" style="background: none; border: none; padding: 0; cursor: pointer;" onclick="previewRenewalDoc('{{ asset('storage/'.$tmplPdf) }}', '{{ addslashes($label.': '.$doc['title']) }}')">
                                                <i class="bi bi-eye"></i> View Template (.pdf)
                                            </button>
                                        @endif
                                        @if ($tmplDocx)
                                            <a href="{{ asset('storage/'.$tmplDocx) }}" download class="rn-link" style="color: #1d4ed8;">
                                                <i class="bi bi-file-earmark-word"></i> Download Template (.docx)
                                            </a>
                                        @endif
                                        <small style="color: #786f73; font-size: 0.72rem;">
                                            @if ($uploaded)
                                                <span style="color: #15803d; font-weight: 700;">✓ {{ $uploaded->file_name }}</span>
                                            @else
                                                <span style="color: #d97706;">Pending Upload</span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; flex-wrap: wrap;">
                                @if ($uploaded)
                                    <span class="rn-pill ok" style="padding: 0.25rem 0.65rem;"><i class="bi bi-check2"></i> UPLOADED</span>
                                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="previewRenewalDoc('{{ asset('storage/'.$uploaded->file_path) }}', 'Uploaded: {{ addslashes($doc['title']) }}')" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                        <i class="bi bi-eye"></i> View File
                                    </button>
                                @else
                                    <span class="rn-pill wait" style="padding: 0.25rem 0.65rem;"><i class="bi bi-exclamation-circle"></i> REQUIRED</span>
                                @endif
                                <form method="POST" action="{{ route('office.renewal.documents') }}" enctype="multipart/form-data" data-org-upload-form style="display: flex; gap: 0.35rem; align-items: center; margin: 0;">
                                    @csrf
                                    <input type="hidden" name="submission_id" value="{{ $my->id }}">
                                    <input type="hidden" name="doc_key" value="{{ $doc['key'] }}">
                                    <input type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg" required data-org-upload data-max-size="20480" data-upload-status-id="renewalDocumentStatus{{ $doc['key'] }}" style="font-size: 0.72rem; max-width: 170px;">
                                    <span id="renewalDocumentStatus{{ $doc['key'] }}" class="org-upload-status" aria-live="polite">No file selected.</span>
                                    <button type="submit" class="org-btn org-btn-ghost org-btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                        <i class="bi bi-upload"></i> {{ $uploaded ? 'Replace' : 'Upload' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif
        @endif
    @endif
    <script>
        (() => {
            const editor = document.getElementById('renewalRequirementsEditor');
            const addButton = document.getElementById('addRenewalRequirement');
            if (!editor || !addButton) return;

            const rows = () => Array.from(editor.querySelectorAll('[data-requirement-row]'));

            const refreshRows = () => {
                const currentRows = rows();
                currentRows.forEach((row, index) => {
                    const number = row.querySelector('[data-requirement-number]');
                    const key = row.querySelector('input[type="hidden"]');
                    const title = row.querySelector('input[type="text"]');
                    const remove = row.querySelector('[data-remove-requirement]');

                    if (number) number.textContent = String(index + 1);
                    if (key) key.name = `required_docs[${index}][key]`;
                    if (title) {
                        title.name = `required_docs[${index}][title]`;
                        title.setAttribute('aria-label', `Requirement ${index + 1} title`);
                    }
                    if (remove) {
                        remove.disabled = currentRows.length === 1;
                        remove.setAttribute('aria-label', `Remove requirement ${index + 1}`);
                    }
                });
            };

            addButton.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'rn-editor-row';
                row.setAttribute('data-requirement-row', '');
                row.innerHTML = `
                    <span class="rn-requirement-index" data-requirement-number></span>
                    <div style="min-width:0;">
                        <input type="hidden" value="">
                        <input type="text" placeholder="e.g. Board Resolution" required>
                        <small class="rn-helper">The title shown to the organization.</small>
                    </div>
                    <button type="button" class="rn-editor-remove" data-remove-requirement>
                        <i class="bi bi-trash3"></i> Remove
                    </button>
                `;
                editor.appendChild(row);
                refreshRows();
                row.querySelector('input[type="text"]')?.focus();
            });

            editor.addEventListener('click', (event) => {
                const remove = event.target.closest('[data-remove-requirement]');
                if (!remove || remove.disabled) return;
                remove.closest('[data-requirement-row]')?.remove();
                refreshRows();
            });

            refreshRows();
        })();

        function osoRenewalReturnRemarks(form) {
            const note = window.prompt('Return remarks for the SO desk (what must they fix?):', '');
            if (note === null) return false;
            form.querySelector('input[name="remarks"]').value = note;
            return true;
        }

        function previewRenewalDoc(url, title) {
            const modal = document.getElementById('renewalDocViewerModal');
            const iframe = document.getElementById('renewalDocViewerIframe');
            const titleEl = document.getElementById('renewalDocViewerTitle');
            const dlBtn = document.getElementById('renewalDocViewerDownloadBtn');
            const newTabBtn = document.getElementById('renewalDocViewerNewTabBtn');

            if (!modal || !iframe) return;

            iframe.src = url;
            if (titleEl) titleEl.textContent = title || 'Document Preview';
            if (dlBtn) dlBtn.href = url;
            if (newTabBtn) newTabBtn.href = url;

            if (typeof modal.showModal === 'function') {
                modal.showModal();
            } else {
                modal.setAttribute('open', '');
            }
        }

        function closeRenewalDocViewer() {
            const modal = document.getElementById('renewalDocViewerModal');
            const iframe = document.getElementById('renewalDocViewerIframe');
            if (iframe) iframe.src = '';
            if (modal) {
                if (typeof modal.close === 'function') {
                    modal.close();
                } else {
                    modal.removeAttribute('open');
                }
            }
        }

        function openManageQualificationModal(org) {
            const modal = document.getElementById('manageOrgQualificationModal');
            const form = document.getElementById('manageOrgQualificationForm');
            if (!modal || !form) return;

            form.action = `/renewal/organizations/${org.id}/status`;
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

        (function() {
            const filterGroup = document.getElementById('orgRosterFilterGroup');
            const searchInput = document.getElementById('orgRosterSearchInput');
            const rows = document.querySelectorAll('.org-roster-row');

            if (!filterGroup || !rows.length) return;

            let currentFilter = 'all';

            function applyFilter() {
                const query = (searchInput ? searchInput.value : '').toLowerCase().trim();

                rows.forEach(row => {
                    const type = row.getAttribute('data-filter-type');
                    const text = (row.textContent || '').toLowerCase();

                    const matchesFilter = (currentFilter === 'all') ||
                        (currentFilter === 'qualified' && type === 'qualified') ||
                        (currentFilter === 'not_qualified' && (type === 'not_qualified' || row.getAttribute('data-is-qualified') === '0')) ||
                        (currentFilter === 'inactive' && (type === 'inactive' || row.getAttribute('data-is-active') === '0'));

                    const matchesSearch = !query || text.includes(query);

                    if (matchesFilter && matchesSearch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            filterGroup.addEventListener('click', (e) => {
                const btn = e.target.closest('.rn-filter-pill');
                if (!btn) return;

                filterGroup.querySelectorAll('.rn-filter-pill').forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');

                currentFilter = btn.getAttribute('data-filter') || 'all';
                applyFilter();
            });

            if (searchInput) {
                searchInput.addEventListener('input', applyFilter);
            }
        })();
    </script>

    {{-- In-Browser Document Previewer Modal --}}
    <dialog id="renewalDocViewerModal" style="border: none; border-radius: 16px; padding: 0; width: 92vw; max-width: 1040px; height: 88vh; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); background: #ffffff;">
        <div style="display: flex; flex-direction: column; height: 100%;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1.25rem; border-bottom: 1.5px solid #f0e6e8; background: #fffcfd;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 36px; height: 36px; border-radius: 9px; background: #fdf0f2; color: #8b1828; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                    </div>
                    <div>
                        <strong id="renewalDocViewerTitle" style="font-size: 0.95rem; color: #1a1618; display: block;">Document Preview</strong>
                        <small style="color: #786f73; font-size: 0.75rem;">College of Informatics and Computing Sciences Student Council (CICS-SC) · Renewal Packet</small>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a id="renewalDocViewerDownloadBtn" href="#" download class="org-btn org-btn-ghost org-btn-sm" style="padding: 0.35rem 0.75rem; font-size: 0.76rem;">
                        <i class="bi bi-download"></i> Download
                    </a>
                    <a id="renewalDocViewerNewTabBtn" href="#" target="_blank" rel="noopener" class="org-btn org-btn-ghost org-btn-sm" style="padding: 0.35rem 0.75rem; font-size: 0.76rem;">
                        <i class="bi bi-box-arrow-up-right"></i> Open in Tab
                    </a>
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="closeRenewalDocViewer()" style="padding: 0.35rem 0.65rem; font-size: 0.85rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <div style="flex: 1; min-height: 0; background: #525659; position: relative;">
                <iframe id="renewalDocViewerIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </dialog>

    @if ($isOso)
        {{-- OSO Manage Organization Qualification & Status Modal --}}
        <dialog id="manageOrgQualificationModal" style="border: none; border-radius: 16px; padding: 0; width: 92vw; max-width: 580px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); background: #ffffff;">
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
