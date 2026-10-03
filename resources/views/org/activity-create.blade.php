@extends('org.layout')

@section('title', !empty($editActivity) ? 'Edit Activity - ' . $editActivity['title'] : ($submission->exists ? 'Edit Activity' : 'Create Activity Proposal'))

@section('header')
    <a href="{{ route('office.activities') }}" class="org-back-link">
        <i class="bi bi-arrow-left"></i> Back to activities
    </a>
    <h1 id="pageHeaderTitle">{{ !empty($editActivity) ? 'Edit Activity' : ($submission->exists ? 'Edit Activity' : 'Create an Activity') }}</h1>
    <p class="org-welcome" id="pageHeaderDesc">Choose the request type and fill in the core activity details.</p>
@endsection



@section('content')
    @php
        $activity = $submission->activity;
        $isEditing = $submission->exists || !empty($editActivity);
        $currentType = old('activity_type', $submission->activity_type ?: 'in_campus');

        $editTitle = old('title', $editActivity['title'] ?? $activity?->title ?? '');
        $editOrg = old('organization_name', $editActivity['organization'] ?? $submission->organization_name ?? 'Supreme Student Council');
        $editLocation = old('location', $editActivity['location'] ?? $activity?->location ?? '');
        $editRationale = old('rationale', $editActivity['rationale'] ?? $submission->rationale ?? '');
        
        $rawObjectives = $editActivity['objectives'] ?? null;
        if (is_array($rawObjectives)) {
            $editObjectives = old('objectives', implode("\n• ", $rawObjectives));
            if (!empty($editObjectives) && !str_starts_with($editObjectives, '• ')) {
                $editObjectives = '• ' . $editObjectives;
            }
        } else {
            $editObjectives = old('objectives', $submission->objectives ?? '');
        }

        $editSdgGoals = old('sdg_goals', $editActivity['sdg_goals'] ?? ($activity?->sdg_goals ?? []));
        $editSdgGoals = is_array($editSdgGoals) ? array_values(array_filter($editSdgGoals)) : (filled($editSdgGoals) ? [(string) $editSdgGoals] : []);

        $editStartsAt = old('starts_at', $activity?->starts_at?->format('Y-m-d\TH:i') ?? '');
        $editEndsAt = old('ends_at', $activity?->ends_at?->format('Y-m-d\TH:i') ?? '');
        $editBudget = old('approved_budget', $activity?->approved_budget ?? ($editActivity['budget'] ?? null));

        if (empty($editStartsAt) && !empty($editActivity['start_time'])) {
            try {
                $editStartsAt = \Carbon\Carbon::parse($editActivity['start_time'])->format('Y-m-d\TH:i');
            } catch (\Exception $e) {}
        }
        if (empty($editEndsAt) && !empty($editActivity['end_time'])) {
            try {
                $editEndsAt = \Carbon\Carbon::parse($editActivity['end_time'])->format('Y-m-d\TH:i');
            } catch (\Exception $e) {}
        }

        $activityDocs = $docRows ?? [];
        $activityDocsByType = $docRowsByType ?? [$currentType => $activityDocs];
        $requirementSets = [
            'in_campus' => $inCampusRequirements ?? [],
            'local_off_campus' => $offCampusRequirements ?? [],
        ];
        $storedAttachments = is_array($submission->attachments ?? null) ? $submission->attachments : [];
        $storedConditions = is_array($storedAttachments['conditions'] ?? null) ? $storedAttachments['conditions'] : [];
        $activeConditions = old('conditions', $storedConditions);
        $typeLabel = $currentType === 'local_off_campus' ? 'Local Off-Campus' : 'In-Campus';
    @endphp

    <style>
        .org-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: #8b1828;
            text-decoration: none;
            margin-bottom: 0.6rem;
            transition: color 0.15s ease;
        }

        .org-back-link:hover {
            color: #6a101e;
            text-decoration: underline;
        }

        .org-form-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #f0e6e8;
            padding: 2rem 2.25rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 6px 24px rgba(90, 15, 30, 0.03);
        }

        .org-form-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 1.25rem;
            margin-bottom: 1.75rem;
            border-bottom: 1px solid #f6eff0;
        }

        .org-form-card-head h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0 0 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .org-form-card-head p {
            font-size: 0.88rem;
            color: #635b5e;
            margin: 0;
        }

        .org-card-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fdf0f2;
            color: #961b2e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
        }

        .org-btn-outline-red-sm {
            padding: 0.45rem 1.15rem;
            border-radius: 9999px;
            border: 1.5px solid #8b1828;
            background: #ffffff;
            color: #8b1828;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .org-btn-outline-red-sm:hover {
            background: #8b1828;
            color: #ffffff;
        }

        .org-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.35rem 1.5rem;
        }

        .org-form-field-wide {
            grid-column: 1 / -1;
        }

        .org-form-field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .org-form-field span {
            font-size: 0.86rem;
            font-weight: 700;
            color: #2b2528;
        }

        .org-form-field span b {
            color: #dc2626;
        }

        .org-form-field input,
        .org-form-field select,
        .org-form-field textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1.5px solid #e8dedf;
            background: #ffffff;
            font-size: 0.92rem;
            font-family: inherit;
            color: #1a1618;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .org-form-field input:focus,
        .org-form-field select:focus,
        .org-form-field textarea:focus {
            border-color: #8b1828;
            box-shadow: 0 0 0 4px rgba(139, 24, 40, 0.08);
        }

        .org-form-field input::placeholder,
        .org-form-field textarea::placeholder {
            color: #a3989c;
        }

        .org-form-field small {
            font-size: 0.78rem;
            color: #7a7074;
            margin-top: 0.15rem;
            line-height: 1.4;
        }

        .org-form-field em {
            font-size: 0.78rem;
            color: #dc2626;
            font-style: normal;
            margin-top: 0.2rem;
        }

        .org-sdg-field {
            grid-column: 1 / -1;
            min-width: 0;
            margin: 0;
            padding: 0;
            border: 0;
        }

        .org-sdg-field legend {
            padding: 0;
            margin-bottom: 0.45rem;
            font-size: 0.86rem;
            font-weight: 700;
            color: #2b2528;
        }

        .org-sdg-field legend b {
            color: #dc2626;
        }

        .org-sdg-help {
            display: block;
            margin-bottom: 0.7rem;
            color: #7a7074;
            font-size: 0.78rem;
            line-height: 1.4;
        }

        .org-sdg-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr));
            gap: 0.5rem;
        }

        .org-sdg-option {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 2.35rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid #eadfe1;
            border-radius: 10px;
            background: #fff;
            color: #3e3538;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .org-sdg-option:hover {
            border-color: #d9b7be;
            background: #fffafb;
        }

        .org-sdg-option input {
            width: auto;
            margin: 0;
            accent-color: #8b1828;
        }

        .org-template-preview-link {
            border: 0;
            padding: 0;
            background: transparent;
            color: #8b1828;
            font: inherit;
            font-size: 0.76rem;
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .org-template-preview-link:hover {
            color: #65101d;
        }

        .org-doc-preview-dialog {
            width: min(1120px, calc(100vw - 2rem));
            max-width: none;
            padding: 0;
            border: 0;
            border-radius: 18px;
            background: transparent;
            box-shadow: 0 22px 70px rgba(38, 23, 27, 0.28);
        }

        .org-doc-preview-dialog::backdrop {
            background: rgba(36, 24, 28, 0.64);
            backdrop-filter: blur(3px);
        }

        .org-doc-preview-box {
            display: flex;
            max-height: min(92vh, 980px);
            flex-direction: column;
            overflow: hidden;
            border-radius: 18px;
            background: #fff;
        }

        .org-doc-preview-head,
        .org-doc-preview-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.95rem 1.15rem;
            background: #fff;
        }

        .org-doc-preview-head {
            border-bottom: 1px solid #eee2e5;
        }

        .org-doc-preview-head strong {
            color: #30272a;
            font-size: 0.96rem;
        }

        .org-doc-preview-head small {
            display: block;
            margin-top: 0.18rem;
            color: #8a7b80;
            font-size: 0.72rem;
        }

        .org-doc-preview-close {
            width: 32px;
            height: 32px;
            border: 1px solid #eadde0;
            border-radius: 50%;
            background: #fff;
            color: #6f6064;
            cursor: pointer;
            font-size: 1.15rem;
            line-height: 1;
        }

        .org-doc-preview-close:hover {
            color: #8b1828;
            border-color: #d9b7be;
            background: #fdf5f6;
        }

        .org-doc-preview-body {
            min-height: 360px;
            max-height: 72vh;
            overflow: auto;
            padding: 1.25rem;
            background: #e9e6e5;
            scrollbar-width: thin;
        }

        .org-doc-preview-body .docx-wrapper {
            padding: 0 !important;
            background: transparent !important;
        }

        .org-doc-preview-body .docx {
            margin: 0 auto 1.25rem !important;
            box-shadow: 0 7px 24px rgba(42, 27, 30, 0.16) !important;
        }

        .org-doc-preview-body iframe {
            display: block;
            width: 100%;
            min-height: 62vh;
            border: 0;
            border-radius: 10px;
            background: #fff;
        }

        .org-doc-preview-loading,
        .org-doc-preview-error {
            display: grid;
            min-height: 330px;
            place-items: center;
            padding: 2rem;
            color: #77696d;
            font-size: 0.86rem;
            line-height: 1.5;
            text-align: center;
        }

        .org-doc-preview-loading i,
        .org-doc-preview-error i {
            display: block;
            margin-bottom: 0.5rem;
            color: #8b1828;
            font-size: 1.5rem;
        }

        .org-doc-preview-foot {
            border-top: 1px solid #eee2e5;
            justify-content: flex-end;
        }

        .org-doc-preview-download {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 0.85rem;
            border-radius: 9px;
            background: #8b1828;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 800;
            text-decoration: none;
        }

        .org-doc-preview-download:hover {
            background: #6e101f;
        }

        .org-requirement-intro {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            padding: 0.95rem 1rem;
            margin-bottom: 1rem;
            border-radius: 14px;
            background: #fdf5f6;
            border: 1px solid #f4dfe3;
            color: #5e454b;
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .org-requirement-intro i {
            color: #961b2e;
            font-size: 1rem;
            margin-top: 0.12rem;
        }

        .org-requirement-panel[hidden] {
            display: none;
        }

        .org-requirement-list {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .org-requirement-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 0.46fr);
            gap: 1rem;
            align-items: center;
            padding: 0.95rem 1rem;
            border: 1px solid #eee2e5;
            border-radius: 14px;
            background: #fff;
            transition: border-color 0.18s ease, background 0.18s ease, opacity 0.18s ease;
        }

        .org-requirement-row.is-conditional {
            background: #fffdf8;
            border-color: #f1e6c5;
        }

        .org-requirement-row.is-inactive {
            opacity: 0.62;
        }

        .org-requirement-copy {
            min-width: 0;
        }

        .org-requirement-title-line {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-bottom: 0.2rem;
        }

        .org-requirement-title-line strong {
            color: #2b2528;
            font-size: 0.88rem;
        }

        .org-requirement-copy p {
            margin: 0;
            color: #786f73;
            font-size: 0.79rem;
            line-height: 1.4;
        }

        .org-requirement-meta {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.4rem;
            color: #9a858b;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .org-requirement-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            font-size: 0.67rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .org-requirement-badge.required {
            color: #8b1828;
            background: #fdf0f2;
            border: 1px solid #f2cbd2;
        }

        .org-requirement-badge.optional {
            color: #6b7280;
            background: #f5f6f7;
            border: 1px solid #e5e7eb;
        }

        .org-requirement-badge.conditional {
            color: #9a6500;
            background: #fff8df;
            border: 1px solid #f1dda0;
        }

        .org-requirement-upload {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            min-width: 0;
        }

        .org-requirement-upload input[type="file"] {
            width: 100%;
            font-size: 0.77rem;
            color: #675c60;
        }

        .org-requirement-upload input[type="file"]::file-selector-button {
            margin-right: 0.5rem;
            padding: 0.42rem 0.7rem;
            border: 1px solid #e7d5d9;
            border-radius: 8px;
            background: #fff7f8;
            color: #8b1828;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .org-requirement-upload small {
            color: #8d7e82;
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .org-requirement-upload small.current-file {
            color: #16803c;
            font-weight: 700;
        }

        .org-condition-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.35rem;
            color: #785e15;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .org-condition-toggle input {
            accent-color: #8b1828;
        }

        .org-requirements-count {
            color: #7a7074;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .org-submit-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.7rem;
            flex-wrap: wrap;
        }

        .org-btn-draft {
            padding: 0.75rem 1.5rem;
            background: #ffffff;
            color: #8b1828;
            border: 1.5px solid #d9b9c0;
            border-radius: 9999px;
            font-size: 0.92rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
        }

        .org-btn-draft:hover {
            background: #fdf2f4;
        }

        /* Documents Table Styles */
        .org-docs-table-wrap {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 1.25rem;
        }

        .org-docs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }

        .org-docs-table th {
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #7a7074;
            border-bottom: 1px solid #f2e9eb;
            background: #faf6f7;
        }

        .org-docs-table td {
            padding: 1.1rem 1rem;
            border-bottom: 1px solid #f6eff0;
            vertical-align: middle;
            color: #1a1618;
        }

        .org-doc-name-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
        }

        .doc-type-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        .doc-type-pdf { background: #dc2626; }
        .doc-type-xlsx { background: #16a34a; }
        .doc-type-docx { background: #2563eb; }

        .org-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: 0.01em;
        }

        .org-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .org-status-green {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .org-status-green .org-status-dot { background: #16a34a; }

        .org-status-blue {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
        }
        .org-status-blue .org-status-dot { background: #2563eb; }

        .org-status-yellow {
            background: #fefce8;
            color: #b45309;
            border: 1px solid #fef08a;
        }
        .org-status-yellow .org-status-dot { background: #d97706; }

        .org-status-red {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .org-status-red .org-status-dot { background: #dc2626; }

        .doc-actions-cell {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .doc-action-btn {
            background: transparent;
            border: none;
            color: #4b4548;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.4rem;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .doc-action-btn:hover {
            color: #8b1828;
            background: #fdf2f4;
        }

        .doc-action-btn.btn-delete:hover {
            color: #dc2626;
            background: #fef2f2;
        }

        /* Yellow Warning Box */
        .org-doc-guideline-box {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 14px;
            padding: 0.95rem 1.15rem;
            color: #92400e;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .org-doc-guideline-box i {
            font-size: 1.1rem;
            color: #d97706;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        .org-doc-guideline-box strong {
            color: #78350f;
        }

        .org-form-actions-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .org-btn-save-submit {
            padding: 0.75rem 2.25rem;
            background: #8b1828;
            color: #ffffff;
            border: none;
            border-radius: 9999px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(139, 24, 40, 0.25);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .org-btn-save-submit:hover {
            background: #71101e;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(139, 24, 40, 0.35);
        }

        .org-btn-cancel-link {
            padding: 0.75rem 1.75rem;
            background: #ffffff;
            color: #5e5457;
            border: 1.5px solid #e2d8da;
            border-radius: 9999px;
            font-size: 0.92rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .org-btn-cancel-link:hover {
            background: #fdf8f9;
            border-color: #c4b0b4;
            color: #1a1618;
        }

        @media (max-width: 768px) {
            .org-form-grid {
                grid-template-columns: 1fr;
            }
            .org-form-card {
                padding: 1.5rem 1.25rem;
            }
            .org-requirement-row {
                grid-template-columns: 1fr;
            }
            .org-doc-preview-dialog {
                width: calc(100vw - 1rem);
            }
            .org-doc-preview-body {
                padding: 0.65rem;
            }
            .org-doc-preview-head,
            .org-doc-preview-foot {
                align-items: flex-start;
                flex-direction: column;
            }
        }
        }
    </style>

    @if ($errors->any())
        <div class="org-alert" style="margin-bottom: 1.5rem;">
            <i class="bi bi-exclamation-triangle-fill"></i> Please correct the highlighted information before saving.
        </div>
    @endif

    <form method="post" action="{{ $submission->exists ? route('office.activities.update', $submission) : route('office.activities.store') }}" enctype="multipart/form-data" data-org-upload-form>
        @csrf
        @if ($submission->exists) @method('PUT') @endif

        {{-- Card 1: Activity Information --}}
        <div class="org-form-card">
            <div class="org-form-card-head">
                <div>
                    <h2>Activity information</h2>
                    <p>Choose the request type and fill in the core activity details.</p>
                </div>
            </div>

            <div class="org-form-grid">
                {{-- Activity type --}}
                <label class="org-form-field org-form-field-wide">
                    <span>Activity type <b>*</b></span>
                    <select name="activity_type" id="activityType" required>
                        <option value="in_campus" @selected($currentType === 'in_campus')>In-campus activity</option>
                        <option value="local_off_campus" @selected($currentType === 'local_off_campus')>Local Off-campus activity</option>
                    </select>
                    <small>Select the category to generate the relevant official document templates and checklist requirements.</small>
                </label>

                {{-- Organization name --}}
                <label class="org-form-field">
                    <span>Organization name</span>
                    <input type="text" name="organization_name" id="inputOrgName" value="{{ $editOrg }}" placeholder="e.g., Supreme Student Council" list="recognizedOrgsList">
                    <datalist id="recognizedOrgsList">
                        @foreach (($recognizedOrgs ?? []) as $org)
                            <option value="{{ is_array($org) ? $org['name'] : $org->name }}">{{ is_array($org) ? ($org['college'] ?? '') : ($org->college ?? '') }}</option>
                        @endforeach
                    </datalist>
                    @error('organization_name') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Activity title --}}
                <label class="org-form-field">
                    <span>Activity title <b>*</b></span>
                    <input type="text" name="title" id="inputTitle" value="{{ $editTitle }}" required placeholder="e.g., Leadership Summit 2026">
                    @error('title') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Reference in Approved Plan of Activities (Attachment I) --}}
                <label class="org-form-field org-form-field-wide" style="background: #fffcf8; border: 1.5px solid #fed7aa; border-radius: 12px; padding: 0.85rem 1rem;">
                    <span style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <span style="font-weight: 700; color: #9a3412;">
                            <i class="bi bi-calendar-check-fill" style="color: #ea580c; margin-right: 0.25rem;"></i> Reference in Approved Plan of Activities (Attachment I) <b>*</b>
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            <a href="/templates/renewal/Attachment I_ Plan of Activities.pdf" target="_blank" style="font-size: 0.76rem; font-weight: 700; color: #8b1828; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                                <i class="bi bi-eye"></i> View Attachment I Template
                            </a>
                            <span style="color: #cbd5e1;">·</span>
                            <a href="/templates/renewal/Attachment I_ Plan of Activities.docx" download style="font-size: 0.76rem; font-weight: 700; color: #1d4ed8; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                                <i class="bi bi-download"></i> Download .docx
                            </a>
                        </span>
                    </span>
                    <input type="text" name="plan_reference" id="inputPlanReference" value="{{ old('plan_reference', $submission->attachments['plan_reference'] ?? '') }}" required placeholder="e.g., Annual Plan Item #2: CodeSprint Hackathon 2026 (Semester 1 / Oct)">
                    <small style="color: #7c2d12; margin-top: 0.35rem; display: block; font-size: 0.78rem;">
                        <strong>OSO Policy:</strong> Organizations can only propose activities listed in their approved Renewal Plan of Activities (Attachment I). State the specific project number/title from your approved plan.
                    </small>
                    @error('plan_reference') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Start date and time --}}
                <label class="org-form-field">
                    <span>Start date and time <b>*</b></span>
                    <input type="datetime-local" name="starts_at" id="inputStartsAt" required value="{{ $editStartsAt }}">
                    @error('starts_at') <em>{{ $message }}</em> @enderror
                </label>

                {{-- End date and time --}}
                <label class="org-form-field">
                    <span>End date and time</span>
                    <input type="datetime-local" name="ends_at" id="inputEndsAt" value="{{ $editEndsAt }}">
                    @error('ends_at') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Allocated budget --}}
                <label class="org-form-field">
                    <span>Allocated budget (₱) <b>*</b></span>
                    <input type="number" name="approved_budget" id="inputApprovedBudget" min="1" step="1" inputmode="numeric" required value="{{ $editBudget }}" placeholder="e.g., 25000">
                    <small>Set the approved amount for this activity. Utilized remains ₱0 until expenses are recorded.</small>
                    @error('approved_budget') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Venue / Destination --}}
                <label class="org-form-field org-form-field-wide">
                    <span>Venue / destination location <b>*</b></span>
                    <input type="text" name="location" id="inputLocation" required value="{{ $editLocation }}" placeholder="e.g., Gymnasium / Tagaytay City, Cavite">
                    @error('location') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Rationale --}}
                <label class="org-form-field org-form-field-wide">
                    <span>Rationale</span>
                    <textarea name="rationale" rows="4" placeholder="Why is this activity needed?">{{ $editRationale }}</textarea>
                    @error('rationale') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Objectives --}}
                <label class="org-form-field org-form-field-wide">
                    <span>Objectives</span>
                    <textarea name="objectives" rows="4" placeholder="List the intended outcomes.">{{ $editObjectives }}</textarea>
                    @error('objectives') <em>{{ $message }}</em> @enderror
                </label>

                {{-- SDG selection belongs to the Student Organization, not SDO. --}}
                <fieldset class="org-sdg-field">
                    <legend>Relevant SDG goals</legend>
                    <small class="org-sdg-help">Select the goals this activity supports. SDO will review the submitted documents and Waste Policy Compliance Form; it will not re-enter these goals.</small>
                    <div class="org-sdg-grid">
                        @foreach (range(1, 17) as $number)
                            <label class="org-sdg-option">
                                <input type="checkbox" name="sdg_goals[]" value="SDG {{ $number }}" @checked(in_array('SDG '.$number, $editSdgGoals, true))>
                                <span>SDG {{ $number }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('sdg_goals') <em>{{ $message }}</em> @enderror
                </fieldset>

                {{-- Participants --}}
                <label class="org-form-field">
                    <span>Participants involved</span>
                    <textarea name="participants" rows="4" placeholder="Who will participate?">{{ old('participants', $submission->participants) }}</textarea>
                    @error('participants') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Safety Plan --}}
                <label class="org-form-field">
                    <span>Safety / emergency preparedness plan</span>
                    <textarea name="safety_plan" rows="4" placeholder="Describe safety measures and emergency procedures.">{{ old('safety_plan', $submission->safety_plan) }}</textarea>
                    @error('safety_plan') <em>{{ $message }}</em> @enderror
                </label>
            </div>
        </div>

        {{-- Card 2: Official checklist for the selected activity type --}}
        <div class="org-form-card" id="activityRequirementsCard">
            <div class="org-form-card-head">
                <div>
                    <h2>
                        <span class="org-card-icon"><i class="bi bi-list-check"></i></span>
                        <span id="requirementsHeading">{{ $typeLabel }} requirements</span>
                    </h2>
                    <p>Based on the official BatStateU activity checklist supplied in this project.</p>
                </div>
                <span class="org-requirements-count" id="requirementsCount">Core filing documents</span>
            </div>

            <div class="org-requirement-intro">
                <i class="bi bi-shield-check"></i>
                <div>
                    Select every condition that applies to the activity and upload the corresponding file. Save a draft while preparing documents; <strong>Submit for review</strong> checks the required pre-activity documents for the selected type.
                </div>
            </div>

            @foreach ($requirementSets as $typeKey => $requirements)
                @php
                    $isVisibleRequirementSet = $currentType === $typeKey;
                    $typeName = $typeKey === 'local_off_campus' ? 'Local Off-Campus' : 'In-Campus';
                @endphp
                <div class="org-requirement-panel" data-requirement-type="{{ $typeKey }}" @if (!$isVisibleRequirementSet) hidden @endif>
                    <div class="org-requirement-list">
                        @foreach ($requirements as $requirement)
                            @php
                                $condition = $requirement['condition'] ?? null;
                                $conditionIsActive = $condition
                                    ? (bool) data_get($activeConditions, $condition, false) || !empty($storedAttachments[$requirement['key']])
                                    : true;
                                $storedFile = $storedAttachments[$requirement['key']] ?? null;
                                $isRequired = !empty($requirement['required_on_submit']);
                            @endphp
                            <div class="org-requirement-row {{ $condition ? 'is-conditional' : '' }} {{ $condition && !$conditionIsActive ? 'is-inactive' : '' }}" data-requirement-row data-condition="{{ $condition ?? '' }}">
                                <div class="org-requirement-copy">
                                    <div class="org-requirement-title-line">
                                        <strong>{{ $requirement['title'] }}</strong>
                                        @if ($condition)
                                            <span class="org-requirement-badge conditional">If applicable</span>
                                        @elseif ($isRequired)
                                            <span class="org-requirement-badge required">Required to submit</span>
                                        @else
                                            <span class="org-requirement-badge optional">Later upload</span>
                                        @endif
                                    </div>
                                    <p>{{ $requirement['description'] }}</p>
                                    <div class="org-requirement-meta">
                                        <i class="bi bi-calendar3"></i> {{ $requirement['phase'] ?? 'Before activity' }}
                                        @if (!empty($requirement['source_file']))
                                            <span aria-hidden="true">·</span>
                                            <button type="button"
                                                    class="org-template-preview-link"
                                                    data-doc-preview
                                                    data-doc-preview-url="{{ route('office.activities.templates.download', ['type' => $typeKey, 'file' => $requirement['source_file']]) }}"
                                                    data-doc-preview-title="{{ $requirement['title'] }} — Official template"
                                                    data-doc-preview-download-url="{{ route('office.activities.templates.download', ['type' => $typeKey, 'file' => $requirement['source_file']]) }}">
                                                Official template
                                            </button>
                                        @endif
                                    </div>
                                    @if ($condition)
                                        <label class="org-condition-toggle">
                                            <input type="checkbox" name="conditions[{{ $condition }}]" value="1" data-condition-toggle="{{ $condition }}" @checked($conditionIsActive)>
                                            This condition applies to my activity
                                        </label>
                                    @endif
                                </div>
                                <div class="org-requirement-upload">
                                    <input type="file"
                                           name="attachments[{{ $requirement['key'] }}]"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"
                                           data-requirement-file
                                           data-condition-file="{{ $condition ?? '' }}"
                                           @if ($condition && !$conditionIsActive) disabled @endif>
                                    @if (is_array($storedFile) && !empty($storedFile['name']))
                                        <small class="current-file"><i class="bi bi-check-circle-fill"></i> Current file: {{ $storedFile['name'] }}</small>
                                    @elseif ($condition && !$conditionIsActive)
                                        <small>Enable the condition above if this document applies.</small>
                                    @elseif ($isRequired)
                                        <small>Upload before submitting for review.</small>
                                    @else
                                        <small>Upload when available; this is not required for the initial filing.</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Card 3: Official document pack and uploaded files --}}
        <div class="org-form-card">
            <div class="org-form-card-head">
                <h2>
                    <span class="org-card-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                    Documents
                </h2>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                    <a id="downloadTemplatesBtn"
                       href="{{ route('office.activities.templates.download', ['type' => $currentType]) }}"
                       class="org-btn-outline-red-sm"
                       style="text-decoration:none;">
                        <i class="bi bi-download"></i> Download Documents
                    </a>
                    <button type="button" class="org-btn-outline-red-sm" onclick="document.getElementById('bulkDocUpload')?.click()">
                        <i class="bi bi-plus-lg"></i> Upload / Import
                    </button>
                    <input type="file" id="bulkDocUpload" name="supporting_documents[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.ppt,.pptx,.zip" data-org-upload data-max-size="20480" data-upload-status-id="bulkDocUploadStatus" style="display:none;">
                    <span id="bulkDocUploadStatus" class="org-upload-status" aria-live="polite">No files selected.</span>
                </div>
            </div>
            <p style="margin:0 0 1rem; font-size:0.82rem; color:#7a7074;">
                Download the official BatStateU document pack for the selected activity type, fill them out, then upload / import your completed files.
            </p>

            @foreach ($activityDocsByType as $docsType => $docs)
                <div class="org-docs-table-wrap" data-document-type="{{ $docsType }}" @if ($docsType !== $currentType) hidden @endif>
                    <table class="org-docs-table">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Status</th>
                                <th>Uploaded On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($docs as $doc)
                                <tr>
                                    <td>
                                        <div class="org-doc-name-cell">
                                            <span class="doc-type-icon doc-type-{{ $doc['ext'] ?? 'pdf' }}">{{ $doc['ext'] ?? 'pdf' }}</span>
                                            <div>
                                                <span>{{ $doc['name'] }}</span>
                                                @if (($doc['kind'] ?? '') === 'template')
                                                    <small style="display: block; font-size: 0.76rem; color: #786f73; margin-top: 0.2rem;">Official {{ $docsType === 'local_off_campus' ? 'off-campus' : 'in-campus' }} template — download, fill out, then upload your completed file.</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="org-status-pill org-status-{{ ($doc['kind'] ?? '') === 'template' ? 'blue' : 'green' }}">
                                            <span class="org-status-dot"></span> {{ $doc['status'] }}
                                        </span>
                                    </td>
                                    <td>{{ $doc['date'] }}</td>
                                    <td>
                                        <div class="doc-actions-cell">
                                            <button type="button"
                                                    class="doc-action-btn"
                                                     data-doc-preview
                                                     data-doc-preview-url="{{ $doc['url'] }}"
                                                     data-doc-preview-title="{{ $doc['name'] }}"
                                                     data-doc-preview-download-url="{{ $doc['download_url'] ?? $doc['url'] }}"
                                                     title="Preview document">
                                                <i class="bi bi-eye"></i> Preview
                                            </button>
                                            @if (($doc['kind'] ?? '') === 'upload' && isset($submission->id))
                                                {{-- Do not nest a delete form inside the main edit form. Nested forms make
                                                     browsers submit the outer activity form with the inner DELETE method. --}}
                                                <button type="button"
                                                        class="doc-action-btn btn-delete"
                                                        data-attachment-delete
                                                        data-attachment-delete-url="{{ route('office.activities.attachments.destroy', [$submission->id, $doc['key']]) }}"
                                                        data-attachment-name="{{ $doc['name'] }}"
                                                        title="Delete document">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align:center;padding:1.25rem;color:#786f73;font-size:0.85rem;">No documents yet — download the official templates above and upload your completed files.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach

            <div class="org-doc-guideline-box">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    If your document is returned for revision, please upload the updated file via Upload / Import above (uploaded files can be removed with the trash icon).
                    <strong>Once all documents are complete and approved, your activity will be marked as completed.</strong>
                </div>
            </div>
        </div>

        {{-- Real DOCX/PDF/image preview modal. Preview never navigates directly to a download response. --}}
        <dialog class="org-doc-preview-dialog" id="activityDocPreviewModal" aria-labelledby="activityDocPreviewTitle">
            <div class="org-doc-preview-box">
                <div class="org-doc-preview-head">
                    <div>
                        <strong id="activityDocPreviewTitle"><i class="bi bi-file-earmark-richtext"></i> Document preview</strong>
                        <small id="activityDocPreviewStatus">Loading the original project document…</small>
                    </div>
                    <button type="button" class="org-doc-preview-close" data-doc-preview-close aria-label="Close document preview">&times;</button>
                </div>
                <div class="org-doc-preview-body" id="activityDocPreviewBody">
                    <div class="org-doc-preview-loading"><div><i class="bi bi-hourglass-split"></i>Loading document…</div></div>
                </div>
                <div class="org-doc-preview-foot">
                    <button type="button" class="org-btn-cancel-link" data-doc-preview-close>Close</button>
                    <a class="org-doc-preview-download" id="activityDocPreviewDownload" href="#">
                        <i class="bi bi-download"></i> Download document
                    </a>
                </div>
            </div>
        </dialog>

        {{-- Form Actions Footer --}}
        <div class="org-form-actions-footer">
            <a href="{{ route('office.activities') }}" class="org-btn-cancel-link">Cancel</a>
            <div class="org-submit-actions">
                <button type="submit" name="submission_action" value="draft" class="org-btn-draft">
                    <i class="bi bi-save2"></i> Save draft
                </button>
                <button type="submit" name="submission_action" value="submit" class="org-btn-save-submit">
                    <i class="bi bi-send-check"></i> Submit for review
                </button>
            </div>
        </div>
    </form>

    <script src="{{ asset('js/vendor/jszip.min.js') }}"></script>
    <script src="{{ asset('js/vendor/docx-preview.min.js') }}"></script>
    <script>
        (function () {
            const previewModal = document.getElementById('activityDocPreviewModal');
            const previewBody = document.getElementById('activityDocPreviewBody');
            const previewTitle = document.getElementById('activityDocPreviewTitle');
            const previewStatus = document.getElementById('activityDocPreviewStatus');
            const previewDownload = document.getElementById('activityDocPreviewDownload');
            const objectUrls = new WeakMap();

            const setPreviewObjectUrl = (container, url) => {
                const previous = objectUrls.get(container);
                if (previous) URL.revokeObjectURL(previous);
                if (url) objectUrls.set(container, url);
                else objectUrls.delete(container);
            };

            const previewRequestUrl = (url) => {
                try {
                    const parsed = new URL(url, window.location.href);
                    if (parsed.pathname.includes('/office-desk/activities/templates/download')) {
                        parsed.searchParams.set('preview', '1');
                    }
                    return parsed.toString();
                } catch (error) {
                    return url;
                }
            };

            const extensionFrom = (url) => {
                try {
                    return new URL(url, window.location.href).pathname.split('.').pop().toLowerCase();
                } catch (error) {
                    return '';
                }
            };

            const renderDocument = async (url, container) => {
                if (!url || !container) throw new Error('Document URL is missing.');
                setPreviewObjectUrl(container, null);
                container.innerHTML = '';

                const response = await fetch(previewRequestUrl(url), {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/octet-stream, application/pdf, image/*, application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
                });
                if (!response.ok) throw new Error(`Preview request failed (${response.status}).`);

                const blob = await response.blob();
                const contentType = (response.headers.get('content-type') || '').split(';')[0].toLowerCase();
                const extension = extensionFrom(url);
                const isPdf = contentType === 'application/pdf' || extension === 'pdf';
                const isImage = contentType.startsWith('image/');
                const isDocx = contentType.includes('wordprocessingml') || extension === 'docx';
                const isLegacyDoc = extension === 'doc' || contentType.includes('msword');

                if (isPdf) {
                    const objectUrl = URL.createObjectURL(blob);
                    setPreviewObjectUrl(container, objectUrl);
                    const frame = document.createElement('iframe');
                    frame.src = objectUrl;
                    frame.title = 'PDF document preview';
                    frame.loading = 'lazy';
                    container.appendChild(frame);
                    return { kind: 'pdf' };
                }

                if (isImage) {
                    const objectUrl = URL.createObjectURL(blob);
                    setPreviewObjectUrl(container, objectUrl);
                    const image = document.createElement('img');
                    image.src = objectUrl;
                    image.alt = 'Document preview';
                    image.style.display = 'block';
                    image.style.maxWidth = '100%';
                    image.style.margin = '0 auto';
                    container.appendChild(image);
                    return { kind: 'image' };
                }

                if (isLegacyDoc) {
                    throw new Error('Legacy .doc files are download-only in the browser preview.');
                }

                if (!isDocx || !window.docx || typeof window.docx.renderAsync !== 'function') {
                    throw new Error('The DOCX preview engine is unavailable for this file.');
                }

                await window.docx.renderAsync(blob, container, null, {
                    breakPages: true,
                    ignoreWidth: false,
                    ignoreHeight: false,
                    renderHeaders: true,
                    renderFooters: true,
                    renderFootnotes: true,
                    useBase64URL: true,
                });
                return { kind: 'docx' };
            };

            window.orgChainRenderDocument = renderDocument;

            const deleteAttachment = (button) => {
                const name = button.dataset.attachmentName || 'this document';
                if (!window.confirm(`Remove ${name}?`)) return;

                // Submit a standalone form so the activity edit form remains a
                // PUT request when the user saves or submits the activity.
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = button.dataset.attachmentDeleteUrl;
                form.style.display = 'none';

                const token = document.querySelector('input[name="_token"]')?.value;
                if (token) {
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = token;
                    form.appendChild(csrf);
                }

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            };

            const showPreviewMessage = (message, error = false) => {
                if (!previewBody) return;
                previewBody.innerHTML = `<div class="org-doc-preview-${error ? 'error' : 'loading'}"><div><i class="bi bi-${error ? 'exclamation-triangle' : 'hourglass-split'}"></i>${message}</div></div>`;
            };

            const openPreview = async (trigger) => {
                if (!previewModal || !previewBody) return;
                const url = trigger.dataset.docPreviewUrl;
                const title = trigger.dataset.docPreviewTitle || 'Document preview';
                const downloadUrl = trigger.dataset.docPreviewDownloadUrl || url;
                previewTitle.replaceChildren();
                const titleIcon = document.createElement('i');
                titleIcon.className = 'bi bi-file-earmark-richtext';
                previewTitle.append(titleIcon, document.createTextNode(` ${title}`));
                previewStatus.textContent = 'Loading the original project document…';
                previewDownload.href = downloadUrl || '#';
                showPreviewMessage('Loading document…');
                if (!previewModal.open) previewModal.showModal();

                try {
                    const result = await renderDocument(url, previewBody);
                    previewStatus.textContent = result.kind === 'docx'
                        ? 'Rendered from the original DOCX file.'
                        : 'Rendered from the original project file.';
                } catch (error) {
                    previewStatus.textContent = 'Preview is not available for this file type.';
                    showPreviewMessage(`${error.message} Use Download document below to open it in Word.`, true);
                }
            };

            document.addEventListener('click', (event) => {
                const deleteButton = event.target.closest('[data-attachment-delete]');
                if (deleteButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    deleteAttachment(deleteButton);
                    return;
                }

                const trigger = event.target.closest('[data-doc-preview]');
                if (trigger) {
                    event.preventDefault();
                    openPreview(trigger);
                    return;
                }

                if (event.target.closest('[data-doc-preview-close]')) {
                    setPreviewObjectUrl(previewBody, null);
                    previewModal?.close();
                }
            });

            previewModal?.addEventListener('close', () => {
                setPreviewObjectUrl(previewBody, null);
            });
        })();
    </script>

    <script>
        (function () {
            const typeSelect = document.getElementById('activityType');
            const downloadBtn = document.getElementById('downloadTemplatesBtn');
            const requirementPanels = Array.from(document.querySelectorAll('[data-requirement-type]'));
            const documentPanels = Array.from(document.querySelectorAll('[data-document-type]'));
            const requirementHeading = document.getElementById('requirementsHeading');
            const pageHeaderDesc = document.getElementById('pageHeaderDesc');
            if (!typeSelect) return;

            const baseUrl = @json(route('office.activities.templates.download'));

            const syncDownloadLink = () => {
                const type = typeSelect.value || 'in_campus';
                if (downloadBtn) {
                    downloadBtn.href = baseUrl + '?type=' + encodeURIComponent(type);
                }

                const isOffCampus = type === 'local_off_campus';
                const label = isOffCampus ? 'Local Off-Campus' : 'In-Campus';
                if (requirementHeading) requirementHeading.textContent = label + ' requirements';
                if (pageHeaderDesc) pageHeaderDesc.textContent = isOffCampus
                    ? 'Prepare a local off-campus filing under the CHED and BatStateU requirements.'
                    : 'Prepare an in-campus filing under the BatStateU activity checklist.';

                requirementPanels.forEach((panel) => {
                    const active = panel.dataset.requirementType === type;
                    panel.hidden = !active;
                    panel.querySelectorAll('input[type="file"]').forEach((input) => {
                        const toggle = input.dataset.conditionFile
                            ? panel.querySelector(`[data-condition-toggle="${input.dataset.conditionFile}"]`)
                            : null;
                        input.disabled = !active || (!!toggle && !toggle.checked);
                    });
                    panel.querySelectorAll('[data-requirement-row][data-condition]').forEach((row) => {
                        const condition = row.dataset.condition;
                        const toggle = panel.querySelector(`[data-condition-toggle="${condition}"]`);
                        row.classList.toggle('is-inactive', !!toggle && !toggle.checked);
                    });
                });

                documentPanels.forEach((panel) => {
                    panel.hidden = panel.dataset.documentType !== type;
                });
            };

            typeSelect.addEventListener('change', syncDownloadLink);
            document.querySelectorAll('[data-condition-toggle]').forEach((toggle) => {
                toggle.addEventListener('change', syncDownloadLink);
            });

            syncDownloadLink();
        })();
    </script>
@endsection
