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

        $editStartsAt = old('starts_at', $activity?->starts_at?->format('Y-m-d\TH:i') ?? '');
        $editEndsAt = old('ends_at', $activity?->ends_at?->format('Y-m-d\TH:i') ?? '');

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

        $defaultDocs = [
            [
                'name' => 'Activity Proposal',
                'type' => 'pdf',
                'status' => 'Completed',
                'status_style' => 'green',
                'uploaded_on' => 'May 12, 2026 9:45 AM',
                'note' => null,
            ],
            [
                'name' => 'Budget Breakdown',
                'type' => 'xlsx',
                'status' => 'In Review',
                'status_style' => 'blue',
                'uploaded_on' => 'May 12, 2026 9:45 AM',
                'note' => null,
            ],
            [
                'name' => 'Risk Assessment',
                'type' => 'pdf',
                'status' => 'Pending',
                'status_style' => 'yellow',
                'uploaded_on' => 'May 12, 2026 9:45 AM',
                'note' => null,
            ],
            [
                'name' => 'Speaker Profiles & Program Flow',
                'type' => 'docx',
                'status' => 'Pending',
                'status_style' => 'yellow',
                'uploaded_on' => 'May 12, 2026 9:45 AM',
                'note' => null,
            ],
        ];

        $activityDocs = $editActivity['documents'] ?? $defaultDocs;
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
        }
    </style>

    @if (session('success'))
        <div class="org-alert org-alert-success" style="margin-bottom: 1.5rem;">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="org-alert" style="margin-bottom: 1.5rem;">
            <i class="bi bi-exclamation-triangle-fill"></i> Please correct the highlighted information before saving.
        </div>
    @endif

    <form method="post" action="{{ $submission->exists ? route('office.activities.update', $submission) : route('office.activities.store') }}" enctype="multipart/form-data">
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
                    <input type="text" name="organization_name" id="inputOrgName" value="{{ $editOrg }}" placeholder="e.g., Supreme Student Council">
                    @error('organization_name') <em>{{ $message }}</em> @enderror
                </label>

                {{-- Activity title --}}
                <label class="org-form-field">
                    <span>Activity title <b>*</b></span>
                    <input type="text" name="title" id="inputTitle" value="{{ $editTitle }}" required placeholder="e.g., Leadership Summit 2026">
                    @error('title') <em>{{ $message }}</em> @enderror
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

        {{-- Card 2: Documents (Matching Mockup) --}}
        <div class="org-form-card">
            <div class="org-form-card-head">
                <h2>
                    <span class="org-card-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                    Documents
                </h2>
                <button type="button" class="org-btn-outline-red-sm" onclick="alert('Upload / Import Document dialog')">
                    <i class="bi bi-plus-lg"></i> Upload / Import
                </button>
            </div>

            <div class="org-docs-table-wrap">
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
                        @foreach ($activityDocs as $doc)
                            <tr>
                                <td>
                                    <div class="org-doc-name-cell">
                                        <span class="doc-type-icon doc-type-{{ $doc['type'] }}">{{ $doc['type'] }}</span>
                                        <div>
                                            <span>{{ $doc['name'] }}</span>
                                            @if (!empty($doc['note']))
                                                <small style="display: block; font-size: 0.76rem; color: #786f73; margin-top: 0.2rem;">{{ $doc['note'] }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="org-status-pill org-status-{{ $doc['status_style'] }}">
                                        <span class="org-status-dot"></span> {{ $doc['status'] }}
                                    </span>
                                </td>
                                <td>{{ $doc['uploaded_on'] }}</td>
                                <td>
                                    <div class="doc-actions-cell">
                                        <button type="button" class="doc-action-btn" title="Preview document" onclick="alert('Previewing {{ $doc['name'] }}')">
                                            <i class="bi bi-eye"></i> Preview
                                        </button>
                                        <button type="button" class="doc-action-btn" title="Replace document" onclick="alert('Replace {{ $doc['name'] }}')">
                                            <i class="bi bi-arrow-repeat"></i> Replace
                                        </button>
                                        <button type="button" class="doc-action-btn btn-delete" title="Delete document" onclick="if(confirm('Are you sure you want to remove this document?')) alert('Removed {{ $doc['name'] }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="org-doc-guideline-box">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    If your document is returned for revision, please replace or resubmit the updated file.
                    <strong>Once all documents are complete and approved, your activity will be marked as completed.</strong>
                </div>
            </div>
        </div>

        {{-- Form Actions Footer --}}
        <div class="org-form-actions-footer">
            <a href="{{ route('office.activities') }}" class="org-btn-cancel-link">Cancel</a>
            <button type="submit" class="org-btn-save-submit">
                <i class="bi bi-check2-circle"></i> Save Changes
            </button>
        </div>
    </form>
@endsection
