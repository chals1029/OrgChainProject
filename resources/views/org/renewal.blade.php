@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSo = $role === 'so';
    $window = $renewalWindow ?? null;
    $isOpen = (bool) ($renewalIsOpen ?? false);
    $docs = $requiredDocs ?? [];
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
        @media (max-width:720px) { .rn-grid { grid-template-columns:1fr; } .rn-span-2 { grid-column:1; } .rn-doc { flex-direction:column; align-items:flex-start; } }
    </style>

    @if (session('success'))
        <div class="rn-alert ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif
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
                        <textarea name="instructions" rows="3">{{ old('instructions', $window->instructions ?? 'Upload the complete 10-document renewal packet. Select your Organizational Adviser and College Dean for review.') }}</textarea>
                    </label>
                    <label class="rn-span-2" style="display:flex;align-items:center;gap:0.55rem;font-weight:700;">
                        <input type="hidden" name="is_open" value="0">
                        <input type="checkbox" name="is_open" value="1" @checked(old('is_open', $window?->is_open))>
                        Open renewal filing for Student Organizations
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
            <h3><i class="bi bi-inbox-fill"></i> Incoming Renewal Packets</h3>
            <p class="rn-muted">Submitted by SO desks for this window. Adviser → Dean → OSO recognition chain comes next.</p>
            @forelse ($renewalSubmissions as $row)
                <div class="rn-doc">
                    <div>
                        <strong>{{ $row->organization_name }}</strong>
                        <small>
                            {{ $row->college ?: '—' }} · Adviser: {{ $row->adviser_name ?: '—' }} · Dean: {{ $row->dean_name ?: '—' }}
                            · Docs {{ $row->documents->count() }}/{{ count($docs) }}
                        </small>
                    </div>
                    <span class="rn-pill {{ $row->status === 'submitted' ? 'ok' : 'wait' }}">{{ strtoupper($row->status) }}</span>
                </div>
            @empty
                <p class="rn-muted" style="margin:0;">No renewal submissions yet.</p>
            @endforelse
        </section>

        <section class="rn-card">
            <h3><i class="bi bi-list-check"></i> Required Documents ({{ count($docs) }})</h3>
            <ol style="margin:0;padding-left:1.2rem;font-size:0.86rem;color:#3f3538;display:grid;gap:0.35rem;">
                @foreach ($docs as $doc)
                    <li>{{ $doc['title'] }}</li>
                @endforeach
            </ol>
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
            <section class="rn-card">
                <h3><i class="bi bi-file-earmark-plus-fill"></i> Create Renewal Request</h3>
                <p class="rn-muted">{{ $window->instructions ?: 'Complete your org details, then upload all required documents.' }}</p>

                <form method="POST" action="{{ route('office.renewal.submit') }}">
                    @csrf
                    <div class="rn-grid">
                        <label>
                            Organization
                            @if (($orgChoices ?? collect())->isNotEmpty())
                                <select name="organization_name" required>
                                    @foreach ($orgChoices as $org)
                                        <option value="{{ $org }}" @selected(old('organization_name', $my->organization_name ?? $org) === $org)>{{ $org }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="organization_name" value="{{ old('organization_name', $my->organization_name ?? '') }}" required>
                            @endif
                        </label>
                        <label>
                            College
                            <input type="text" name="college" value="{{ old('college', $my->college ?? '') }}" placeholder="e.g. CICS">
                        </label>
                        <label>
                            Organizational Adviser
                            <input type="text" name="adviser_name" value="{{ old('adviser_name', $my->adviser_name ?? '') }}" required>
                        </label>
                        <label>
                            College Dean
                            <input type="text" name="dean_name" value="{{ old('dean_name', $my->dean_name ?? '') }}" required>
                        </label>
                        <label class="rn-span-2">
                            Notes (optional)
                            <textarea name="notes" rows="2">{{ old('notes', $my->notes ?? '') }}</textarea>
                        </label>
                    </div>
                    <div class="rn-actions">
                        <button type="submit" name="action" value="draft" class="org-btn org-btn-ghost org-btn-sm">Save Draft</button>
                        <button type="submit" name="action" value="submit" class="org-btn org-btn-primary org-btn-sm" @disabled(! $my)>
                            <i class="bi bi-send-fill"></i> Submit Packet
                        </button>
                    </div>
                    @unless ($my)
                        <p class="rn-muted" style="margin-top:0.65rem;">Save a draft first, then upload documents, then submit.</p>
                    @endunless
                </form>
            </section>

            @if ($my)
                <section class="rn-card">
                    <h3><i class="bi bi-cloud-upload-fill"></i> Required Documents</h3>
                    <p class="rn-muted">Completion: {{ $pct }}% · Status: <strong>{{ strtoupper($my->status) }}</strong></p>
                    <div class="rn-progress"><span style="width:{{ $pct }}%"></span></div>

                    @foreach ($docs as $doc)
                        @php $uploaded = collect($my->documents)->firstWhere('doc_key', $doc['key']); @endphp
                        <div class="rn-doc">
                            <div>
                                <strong>{{ $doc['title'] }}</strong>
                                <small>
                                    @if ($uploaded)
                                        {{ $uploaded->file_name }}
                                    @else
                                        Not uploaded yet
                                    @endif
                                </small>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                @if ($uploaded)
                                    <span class="rn-pill ok">UPLOADED</span>
                                    <a href="{{ asset('storage/'.$uploaded->file_path) }}" target="_blank" rel="noopener" class="org-btn org-btn-ghost org-btn-sm">View</a>
                                @else
                                    <span class="rn-pill wait">REQUIRED</span>
                                @endif
                                <form method="POST" action="{{ route('office.renewal.documents') }}" enctype="multipart/form-data" style="display:flex;gap:0.35rem;align-items:center;">
                                    @csrf
                                    <input type="hidden" name="submission_id" value="{{ $my->id }}">
                                    <input type="hidden" name="doc_key" value="{{ $doc['key'] }}">
                                    <input type="file" name="document" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required style="font-size:0.72rem;max-width:180px;">
                                    <button type="submit" class="org-btn org-btn-ghost org-btn-sm">Upload</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif
        @endif
    @endif
@endsection
