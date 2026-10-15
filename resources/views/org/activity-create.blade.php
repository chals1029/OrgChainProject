@extends('org.layout')

@section('title', !empty($editActivity) ? 'Edit Activity - ' . $editActivity['title'] : ($submission->exists ? 'Edit Activity' : 'Create Activity Proposal'))

@section('header')
    <a href="{{ route('office.activities') }}" class="org-back-link">
        <i class="bi bi-arrow-left"></i> Back to activities
    </a>
    <h1 id="pageHeaderTitle">{{ !empty($editActivity) ? 'Edit Activity' : ($submission->exists ? 'Edit Activity' : 'Create an Activity') }}</h1>
    <p class="org-welcome" id="pageHeaderDesc">Complete the activity details and upload the official proposal requirements.</p>
@endsection



@section('content')
    @php
        $activity = $submission->activity;
        $currentType = old('activity_type', $submission->activity_type ?: 'in_campus');

        $editTitle = $editActivity['title'] ?? $activity?->title ?? '';
        $editOrg = $activityOrganization?->name ?? old('organization_name', $editActivity['organization'] ?? $submission->organization_name ?? '');
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
        $requirementSets = [
            'in_campus' => $inCampusRequirements ?? [],
            'local_off_campus' => $offCampusRequirements ?? [],
        ];
        $storedAttachments = is_array($submission->attachments ?? null) ? $submission->attachments : [];
        $approvedPlanActivities = $approvedPlanActivities ?? [];
        $selectedPlanKey = old('plan_activity_key', $storedAttachments['plan_activity_key'] ?? '');
        if (!session()->hasOldInput('plan_activity_key') && !in_array($selectedPlanKey, array_column($approvedPlanActivities, 'key'), true)) {
            $selectedPlanKey = '';
            foreach ($approvedPlanActivities as $planActivity) {
                if ($planActivity['title'] === $editTitle && $planActivity['reference'] === ($storedAttachments['plan_reference'] ?? '')) {
                    $selectedPlanKey = $planActivity['key'];
                    break;
                }
            }
        }
        $checklistKeys = array_column($requirementSets[$currentType] ?? [], 'key');
        $previousUploads = array_filter($activityDocs, fn ($doc) => ($doc['kind'] ?? '') === 'upload' && !in_array($doc['key'], $checklistKeys, true));
        $typeLabel = $currentType === 'local_off_campus' ? 'Local Off-Campus' : 'In-Campus';
        $renewalLabel = match ($activityRenewalStatus) {
            'approved' => 'Renewal approved',
            'submitted' => 'Renewal under review',
            'returned' => 'Renewal for revision',
            'rejected' => 'Renewal rejected',
            default => 'Renewal not submitted',
        };
        $sdgDefinitions = [
            1 => ['No Poverty', 'people-fill', '#e5243b'],
            2 => ['Zero Hunger', 'cup-hot-fill', '#dda63a'],
            3 => ['Good Health and Well-being', 'heart-pulse-fill', '#4c9f38'],
            4 => ['Quality Education', 'book-fill', '#c5192d'],
            5 => ['Gender Equality', 'gender-ambiguous', '#ff3a21'],
            6 => ['Clean Water and Sanitation', 'droplet-fill', '#26bde2'],
            7 => ['Affordable and Clean Energy', 'sun-fill', '#fcc30b'],
            8 => ['Decent Work and Economic Growth', 'bar-chart-fill', '#a21942'],
            9 => ['Industry, Innovation and Infrastructure', 'boxes', '#fd6925'],
            10 => ['Reduced Inequalities', 'arrows-expand', '#dd1367'],
            11 => ['Sustainable Cities and Communities', 'houses-fill', '#fd9d24'],
            12 => ['Responsible Consumption and Production', 'recycle', '#bf8b2e'],
            13 => ['Climate Action', 'globe-americas', '#3f7e44'],
            14 => ['Life Below Water', 'water', '#0a97d9'],
            15 => ['Life on Land', 'tree-fill', '#56c02b'],
            16 => ['Peace, Justice and Strong Institutions', null, '#00689d'],
            17 => ['Partnerships for the Goals', 'link-45deg', '#19486a'],
        ];
        $documentDisk = \Illuminate\Support\Facades\Storage::disk('local');
        $hasStoredFile = static fn ($file): bool => is_array($file)
            && !empty($file['path'])
            && $documentDisk->fileExists($file['path'])
            && $documentDisk->size($file['path']) > 0;
    @endphp

    <link rel="stylesheet" href="{{ asset('css/activity-create.css') }}?v={{ filemtime(public_path('css/activity-create.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/document-preview.css') }}?v={{ filemtime(public_path('css/document-preview.css')) }}">

    @if ($errors->any())
        <div class="ap-rule" role="alert">
            <i class="bi bi-exclamation-circle"></i>
            <div>
                <strong>Please correct the activity proposal.</strong>
                <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                <small>Files selected before a failed submission must be selected again.</small>
            </div>
        </div>
    @endif

    <form id="activityProposalForm" class="ap-form" method="post" data-confirm="manual"
          action="{{ $submission->exists ? route('office.activities.update', $submission) : route('office.activities.store') }}"
          enctype="multipart/form-data" data-template-url="{{ route('office.activities.templates.download') }}"
          data-document-conversion-url="{{ route('office.documents.preview.convert') }}">
        @csrf
        @if ($submission->exists) @method('PUT') @endif
        <input type="hidden" name="submission_action" value="submit">
        <div class="ap-scroll">
            <section class="ap-card" aria-labelledby="activityInformationHeading">
                <div class="ap-card-head">
                    <div class="ap-section-title">
                        <span class="ap-section-number" aria-hidden="true">01</span>
                        <div>
                            <h2 id="activityInformationHeading">Activity information</h2>
                            <p>Reference your approved plan and complete the core proposal details.</p>
                        </div>
                    </div>
                    @if ($activityOrganization)
                        <span class="ap-status {{ $activityRenewalStatus === 'approved' && $activityOrganization->is_active ? 'is-active' : 'is-warning' }}">
                            <i class="bi {{ $activityRenewalStatus === 'approved' && $activityOrganization->is_active ? 'bi-check-lg' : 'bi-info-circle' }}"></i>
                            {{ $renewalLabel }} · {{ $activityOrganization->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    @endif
                </div>

                <div class="ap-grid">
                    <fieldset class="ap-type-field ap-span-6">
                        <legend>Activity type <b>*</b></legend>
                        <div class="ap-type-choices">
                            <label class="ap-type-choice">
                                <input type="radio" name="activity_type" value="in_campus" @checked($currentType === 'in_campus') required>
                                <span><strong>In-campus</strong><small>Within any BatStateU campus</small></span>
                            </label>
                            <label class="ap-type-choice">
                                <input type="radio" name="activity_type" value="local_off_campus" @checked($currentType === 'local_off_campus') required>
                                <span><strong>Off-campus</strong><small>Outside university premises</small></span>
                            </label>
                        </div>
                        @error('activity_type') <em>{{ $message }}</em> @enderror
                    </fieldset>

                    <label class="ap-field ap-span-3">
                        <span>Organization</span>
                        <input name="organization_name" value="{{ $editOrg }}" maxlength="255"
                               @readonly($activityOrganization !== null) @if (!$activityOrganization) list="recognizedOrgsList" @endif
                               placeholder="Organization responsible for this activity">
                        @error('organization_name') <em>{{ $message }}</em> @enderror
                    </label>
                    @if (!$activityOrganization)
                        <datalist id="recognizedOrgsList">
                            @foreach (($recognizedOrgs ?? []) as $org)
                                <option value="{{ is_array($org) ? $org['name'] : $org->name }}"></option>
                            @endforeach
                        </datalist>
                    @endif
                    <div class="ap-field ap-plan-reference ap-span-3">
                        <label for="inputPlanActivity">Activity from approved Attachment I <b>*</b></label>
                        <select name="plan_activity_key" id="inputPlanActivity" required @if ($approvedPlanError) aria-describedby="approvedPlanHelp" @endif>
                            <option value="">Choose an approved activity</option>
                            @foreach ($approvedPlanActivities as $planActivity)
                                <option value="{{ $planActivity['key'] }}" title="{{ $planActivity['reference'] }}" @selected($selectedPlanKey === $planActivity['key'])>{{ $loop->iteration }}. {{ $planActivity['title'] }}</option>
                            @endforeach
                        </select>
                        @if ($approvedPlanError)
                            <small id="approvedPlanHelp">{{ $approvedPlanError }}</small>
                        @endif
                        @if ($submission->exists && $selectedPlanKey === '' && $editTitle !== '')
                            <small>Your previous activity ({{ $editTitle }}) does not match an entry in the current approved plan. Choose an actual approved entry before resubmitting.</small>
                        @endif
                        @error('plan_activity_key') <em>{{ $message }}</em> @enderror
                    </div>

                    <label class="ap-field ap-span-2">
                        <span>Start date and time <b>*</b></span>
                        <input type="datetime-local" name="starts_at" id="inputStartsAt" required value="{{ $editStartsAt }}">
                        @error('starts_at') <em>{{ $message }}</em> @enderror
                    </label>
                    <label class="ap-field ap-span-2">
                        <span>End date and time</span>
                        <input type="datetime-local" name="ends_at" id="inputEndsAt" value="{{ $editEndsAt }}">
                        @error('ends_at') <em>{{ $message }}</em> @enderror
                    </label>
                    <label class="ap-field ap-span-2">
                        <span>Allocated budget (₱) <b>*</b></span>
                        <input type="number" name="approved_budget" id="inputApprovedBudget"
                               min="0" step="0.01" inputmode="decimal" required value="{{ $editBudget }}" placeholder="0.00">
                        @error('approved_budget') <em>{{ $message }}</em> @enderror
                    </label>
                    <label class="ap-field ap-span-3">
                        <span>Venue / destination <b>*</b></span>
                        <input name="location" id="inputLocation" required maxlength="255" value="{{ $editLocation }}" placeholder="Enter the venue or destination">
                        @error('location') <em>{{ $message }}</em> @enderror
                    </label>
                    <label class="ap-field ap-span-6">
                        <span>Activity objectives</span>
                        <textarea name="objectives" rows="3" maxlength="10000" placeholder="Describe the intended outcomes of this activity.">{{ $editObjectives }}</textarea>
                        @error('objectives') <em>{{ $message }}</em> @enderror
                    </label>
                    <label class="ap-field ap-span-3">
                        <span>Participants involved</span>
                        <textarea name="participants" rows="3" maxlength="10000" placeholder="Students, faculty, guests, and other participants.">{{ old('participants', $submission->participants) }}</textarea>
                        @error('participants') <em>{{ $message }}</em> @enderror
                    </label>
                    <label class="ap-field ap-span-3">
                        <span>Safety / emergency preparedness plan</span>
                        <textarea name="safety_plan" rows="3" maxlength="10000" placeholder="Safety measures, emergency contacts, and response procedures.">{{ old('safety_plan', $submission->safety_plan) }}</textarea>
                        @error('safety_plan') <em>{{ $message }}</em> @enderror
                    </label>
                    <details class="ap-rationale ap-span-6" @if(filled($editRationale)) open @endif>
                        <summary>Additional rationale <small>Optional</small></summary>
                        <label class="ap-field">
                            <span>Why is this activity needed?</span>
                            <textarea name="rationale" rows="3" maxlength="10000">{{ $editRationale }}</textarea>
                            @error('rationale') <em>{{ $message }}</em> @enderror
                        </label>
                    </details>
                    <fieldset class="ap-sdg-field ap-span-6">
                        <legend>Sustainable Development Goals</legend>
                        <small>Select all applicable goals. Hover or focus a tile to see its full description.</small>
                        <div class="ap-sdg-grid">
                            @foreach ($sdgDefinitions as $number => [$goalName, $goalIcon, $goalColor])
                                <label class="ap-sdg-option" title="SDG {{ $number }}: {{ $goalName }}">
                                    <input type="checkbox" name="sdg_goals[]" value="SDG {{ $number }}"
                                           aria-label="SDG {{ $number }}: {{ $goalName }}" @checked(in_array('SDG '.$number, $editSdgGoals, true))>
                                    <span class="ap-sdg-tile">
                                        <span class="ap-sdg-icon" style="--sdg-color: {{ $goalColor }}" aria-hidden="true">
                                            @if ($number === 16)
                                                <svg width="20" height="20" viewBox="0 0 72 72" fill="currentColor" focusable="false">
                                                    <path d="M8 17C24 13 29 35 43 23C48 18 51 18 54 24L59 28L54 29C46 29 47 36 46 43C44 53 33 58 20 56C13 56 9 51 6 49C16 53 18 48 24 45C12 43 16 23 8 17Z"/>
                                                    <path d="M54 34C51 27 53 23 62 13M26 54V61M31 54V61" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                                    <path d="M62 13C59 9 60 6 64 4C65 9 65 12 62 13ZM58 18C54 16 53 13 54 10C58 11 60 14 58 18ZM61 18C63 14 66 14 68 15C66 19 64 20 61 18ZM55 23C51 21 50 18 51 15C55 17 57 20 55 23ZM57 24C60 21 63 21 65 24C61 27 59 27 57 24Z"/>
                                                    <path d="M4 60L42 61V64L4 65Z"/>
                                                    <rect x="0" y="59" width="3" height="7" rx="1.5"/>
                                                    <rect x="42" y="57" width="14" height="11" rx="1"/>
                                                    <rect x="41" y="53" width="16" height="3" rx="1.5"/>
                                                    <rect x="41" y="69" width="16" height="3" rx="1.5"/>
                                                </svg>
                                            @else
                                                <i class="bi bi-{{ $goalIcon }}"></i>
                                            @endif
                                        </span>
                                        <span class="ap-sdg-number">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('sdg_goals') <em>{{ $message }}</em> @enderror
                    </fieldset>
                </div>
            </section>

            <section class="ap-card" aria-labelledby="requirementsHeading">
                <div class="ap-card-head">
                    <div class="ap-section-title">
                        <span class="ap-section-number" aria-hidden="true">02</span>
                        <div>
                            <h2 id="requirementsHeading">{{ $typeLabel }} requirements</h2>
                            <p>View or download the official templates, then attach your completed documents.</p>
                        </div>
                    </div>
                    <span class="ap-status" id="requirementsCount">Checking requirements…</span>
                </div>
                <progress class="ap-progress" id="requirementsProgress" value="0" max="1" aria-label="Required documents complete"></progress>
                <div class="ap-footer-actions">
                    <a id="downloadTemplatesBtn" href="{{ route('office.activities.templates.download', ['type' => $currentType]) }}" class="ap-button ap-button-outline"><i class="bi bi-download"></i> Download template pack</a>
                </div>
                <div class="ap-rule">
                    <i class="bi bi-info-circle"></i>
                    <span><strong>Submission rule:</strong> Upload all documents in the selected checklist before submitting. No additional documents are required.</span>
                </div>
                <div id="activityUploadNotice" class="ap-upload-notice" role="status" aria-live="polite" hidden></div>
                @foreach ($requirementSets as $typeKey => $requirements)
                    <div class="ap-requirement-panel" data-requirement-type="{{ $typeKey }}" @if ($currentType !== $typeKey) hidden @endif>
                        @foreach ($requirements as $requirement)
                            @php
                                $storedFile = $storedAttachments[$requirement['key']] ?? null;
                                $hasFile = $hasStoredFile($storedFile);
                                $isRequired = !empty($requirement['required_on_submit']);
                                $sourceFile = $requirement['source_file'];
                                $fileId = 'activity-file-'.$typeKey.'-'.$requirement['key'];
                            @endphp
                            <div class="ap-requirement-row {{ $hasFile ? 'has-file' : '' }}"
                                 data-requirement-row data-required="{{ $isRequired ? '1' : '0' }}" data-existing-file="{{ $hasFile ? '1' : '0' }}">
                                <span class="ap-file-icon" aria-hidden="true"><i class="bi {{ $hasFile ? 'bi-check-lg' : 'bi-file-earmark-text' }}" data-file-icon></i></span>
                                <div class="ap-requirement-copy">
                                    <div class="ap-requirement-title">
                                        <span class="ap-requirement-kicker">Requirement {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}{{ $isRequired ? ' *' : '' }}</span>
                                        <strong>{{ $requirement['title'] }}</strong>
                                    </div>
                                    <p>{{ $requirement['description'] }}</p>
                                    <div class="ap-document-links">
                                        <button type="button" data-document-preview
                                                data-document-preview-url="{{ route('office.activities.templates.download', ['type' => $typeKey, 'file' => $sourceFile, 'preview' => 1]) }}"
                                                data-document-preview-title="{{ $requirement['title'] }} — Official template"
                                                data-document-original-name="{{ $sourceFile }}" data-document-preview-type="{{ strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION)) }}"
                                                data-document-conversion-url="{{ route('office.documents.preview.convert') }}"
                                                data-document-preview-download="{{ route('office.activities.templates.download', ['type' => $typeKey, 'file' => $sourceFile]) }}"><i class="bi bi-eye"></i> View</button>
                                        <a href="{{ route('office.activities.templates.download', ['type' => $typeKey, 'file' => $sourceFile]) }}"><i class="bi bi-download"></i> Download</a>
                                    </div>
                                    @error('attachments.'.$requirement['key']) <em>{{ $message }}</em> @enderror
                                    @if ($hasFile && $submission->exists)
                                        <div class="ap-document-links">
                                            <button type="button" data-document-preview
                                                    data-document-preview-url="{{ route('office.activities.attachments.file', ['submission' => $submission->id, 'key' => $requirement['key'], 'preview' => 1]) }}"
                                                    data-document-preview-title="{{ $storedFile['name'] ?? $requirement['title'] }}"
                                                    data-document-original-name="{{ $storedFile['name'] ?? basename($storedFile['path']) }}"
                                                    data-document-preview-type="{{ strtolower(pathinfo($storedFile['name'] ?? $storedFile['path'], PATHINFO_EXTENSION)) }}"
                                                    data-document-conversion-url="{{ route('office.documents.preview.convert') }}"
                                                    data-document-preview-download="{{ route('office.activities.attachments.file', ['submission' => $submission->id, 'key' => $requirement['key'], 'download' => 1]) }}">View current file</button>
                                            <button type="button" data-attachment-delete
                                                    data-attachment-delete-url="{{ route('office.activities.attachments.destroy', [$submission->id, $requirement['key']]) }}"
                                                    data-attachment-name="{{ $storedFile['name'] ?? $requirement['title'] }}">Remove current file</button>
                                        </div>
                                    @endif
                                </div>
                                <div class="ap-requirement-upload">
                                    <span class="ap-file-status" data-file-status data-current-name="{{ $hasFile ? ($storedFile['name'] ?? basename($storedFile['path'])) : '' }}">
                                        @if ($hasFile && $submission->exists)
                                            <a data-document-file data-current-file href="{{ route('office.activities.attachments.file', ['submission' => $submission->id, 'key' => $requirement['key'], 'preview' => 1]) }}"
                                               data-document-preview-download="{{ route('office.activities.attachments.file', ['submission' => $submission->id, 'key' => $requirement['key'], 'download' => 1]) }}"
                                               data-document-preview-type="{{ strtolower(pathinfo($storedFile['name'] ?? $storedFile['path'], PATHINFO_EXTENSION)) }}"
                                               data-document-conversion-url="{{ route('office.documents.preview.convert') }}">{{ $storedFile['name'] ?? basename($storedFile['path']) }}</a>
                                        @else
                                            No file selected.
                                        @endif
                                    </span>
                                    <label class="ap-upload-button" for="{{ $fileId }}">
                                        <i class="bi bi-upload" aria-hidden="true"></i>
                                        <span data-upload-label>{{ $hasFile ? 'Replace file' : 'Upload' }}</span>
                                        <input id="{{ $fileId }}" type="file" name="attachments[{{ $requirement['key'] }}]"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"
                                               aria-label="Upload {{ $requirement['title'] }}" data-requirement-file
                                               @disabled($currentType !== $typeKey)>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </section>

            @if ($previousUploads)
                <details class="ap-card ap-supporting">
                    <summary>Previous uploads <small>Not part of this checklist</small></summary>
                    <p>Files saved under an earlier checklist are retained for reference. They are not required for this application.</p>
                    <div class="ap-extra-docs">
                        @foreach ($previousUploads as $doc)
                            <div class="ap-requirement-row">
                                <span class="ap-file-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                                <div class="ap-requirement-copy">
                                    <strong>{{ $doc['name'] }}</strong>
                                    <p>{{ $doc['status'] }}{{ filled($doc['date'] ?? '') && $doc['date'] !== '—' ? ' · '.$doc['date'] : '' }}</p>
                                </div>
                                <div class="ap-document-links">
                                    <button type="button" data-document-preview data-document-preview-url="{{ $doc['url'] }}" data-document-preview-title="{{ $doc['name'] }}" data-document-original-name="{{ $doc['name'] }}" data-document-preview-type="{{ strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION)) }}" data-document-conversion-url="{{ route('office.documents.preview.convert') }}" data-document-preview-download="{{ $doc['download_url'] ?? $doc['url'] }}"><i class="bi bi-eye"></i> View</button>
                                    <a href="{{ $doc['download_url'] ?? $doc['url'] }}"><i class="bi bi-download"></i> Download</a>
                                    <button type="button" data-attachment-delete data-attachment-delete-url="{{ route('office.activities.attachments.destroy', [$submission->id, $doc['key']]) }}" data-attachment-name="{{ $doc['name'] }}">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>

        {{-- Real DOCX/PDF/image preview modal. Preview never navigates directly to a download response. --}}
        <dialog class="org-doc-preview-dialog" id="activityDocPreviewModal" data-document-preview-dialog aria-labelledby="activityDocPreviewTitle">
            <div class="org-doc-preview-box">
                <div class="org-doc-preview-head">
                    <div>
                        <strong id="activityDocPreviewTitle" data-document-preview-title>Document preview</strong>
                        <small>Preview of the original selected or stored document.</small>
                    </div>
                    <button type="button" class="org-doc-preview-close" data-document-preview-close aria-label="Close document preview">&times;</button>
                </div>
                <div class="org-doc-preview-body document-preview-body" id="activityDocPreviewBody" data-document-preview-body>
                    <div class="org-doc-preview-loading"><div><i class="bi bi-hourglass-split"></i>Loading document…</div></div>
                </div>
                <div class="org-doc-preview-foot">
                    <a data-document-preview-external target="_blank" rel="noopener" hidden>Open preview in new tab</a>
                    <button type="button" class="org-btn-cancel-link" data-document-preview-close>Close</button>
                    <a class="org-doc-preview-download" id="activityDocPreviewDownload" data-document-preview-download href="#">
                        <i class="bi bi-download"></i> Download original
                    </a>
                </div>
            </div>
        </dialog>

        <div class="ap-footer">
            <div class="ap-footer-copy" aria-live="polite">
                <strong id="activityRemainingCount">Preparing the required-document checklist…</strong>
                <small id="activityRemainingHelp">Files upload when you submit for review. Maximum 20 MB per file.</small>
            </div>
            <div class="ap-footer-actions">
                <a href="{{ route('office.activities') }}" class="ap-button ap-button-quiet">Cancel</a>
                <button type="submit" id="activitySubmitButton" class="ap-button ap-button-primary" disabled><i class="bi bi-arrow-right"></i> Submit for Review</button>
            </div>
        </div>
    </form>

    <script src="{{ asset('js/vendor/jszip.min.js') }}"></script>
    <script src="{{ asset('js/vendor/docx-preview.min.js') }}"></script>
    <script src="{{ asset('js/document-preview.js') }}?v={{ filemtime(public_path('js/document-preview.js')) }}"></script>
    <script>
        (function () {
            const deleteAttachment = (button) => {
                const name = button.dataset.attachmentName || 'this document';

                // Submit a standalone form so the activity edit form remains a
                // PUT request when the user submits the activity.
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = button.dataset.attachmentDeleteUrl;
                form.style.display = 'none';
                form.dataset.confirmMessage = `Remove ${name}?`;

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
                form.requestSubmit();
            };


            document.addEventListener('click', (event) => {
                const deleteButton = event.target.closest('[data-attachment-delete]');
                if (deleteButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    deleteAttachment(deleteButton);
                    return;
                }
            });
        })();
    </script>

    <script src="{{ asset('js/activity-create.js') }}?v={{ filemtime(public_path('js/activity-create.js')) }}"></script>
@endsection
