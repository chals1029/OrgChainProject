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

        $editTitle = old('title', $editActivity['title'] ?? $activity?->title ?? '');
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
        $activityDocsByType = $docRowsByType ?? [$currentType => $activityDocs];
        $requirementSets = [
            'in_campus' => $inCampusRequirements ?? [],
            'local_off_campus' => $offCampusRequirements ?? [],
        ];
        $storedAttachments = is_array($submission->attachments ?? null) ? $submission->attachments : [];
        $storedConditions = is_array($storedAttachments['conditions'] ?? null) ? $storedAttachments['conditions'] : [];
        $activeConditions = old('conditions', $storedConditions);
        $typeLabel = $currentType === 'local_off_campus' ? 'Local Off-Campus' : 'In-Campus';
        $renewalLabel = match ($activityRenewalStatus) {
            'approved' => 'Renewal approved',
            'submitted' => 'Renewal under review',
            'returned' => 'Renewal for revision',
            'rejected' => 'Renewal rejected',
            'draft' => 'Renewal draft',
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
            9 => ['Industry, Innovation and Infrastructure', 'gear-fill', '#fd6925'],
            10 => ['Reduced Inequalities', 'arrows-expand', '#dd1367'],
            11 => ['Sustainable Cities and Communities', 'houses-fill', '#fd9d24'],
            12 => ['Responsible Consumption and Production', 'recycle', '#bf8b2e'],
            13 => ['Climate Action', 'globe-americas', '#3f7e44'],
            14 => ['Life Below Water', 'water', '#0a97d9'],
            15 => ['Life on Land', 'tree-fill', '#56c02b'],
            16 => ['Peace, Justice and Strong Institutions', 'bank2', '#00689d'],
            17 => ['Partnerships for the Goals', 'link-45deg', '#19486a'],
        ];
        $hasStoredFile = static fn ($file): bool => is_array($file)
            && !empty($file['path'])
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($file['path']);
    @endphp

    <link rel="stylesheet" href="{{ asset('css/activity-create.css') }}?v={{ filemtime(public_path('css/activity-create.css')) }}">

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

    <form id="activityProposalForm" class="ap-form" method="post"
          action="{{ $submission->exists ? route('office.activities.update', $submission) : route('office.activities.store') }}"
          enctype="multipart/form-data" data-template-url="{{ route('office.activities.templates.download') }}">
        @csrf
        @if ($submission->exists) @method('PUT') @endif
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
                    <label class="ap-field ap-span-3">
                        <span>Activity title <b>*</b></span>
                        <input name="title" id="inputTitle" value="{{ $editTitle }}" maxlength="255" required placeholder="Enter the activity title">
                        @error('title') <em>{{ $message }}</em> @enderror
                    </label>

                    <div class="ap-field ap-plan-reference ap-span-6">
                        <label for="inputPlanReference">Reference in approved Plan of Activities <b>*</b></label>
                        <input name="plan_reference" id="inputPlanReference"
                               value="{{ old('plan_reference', $storedAttachments['plan_reference'] ?? '') }}"
                               maxlength="500" required placeholder="Project number / title — semester and planned date">
                        <small>Enter the project listed in your approved Attachment I. OSO will verify the reference against your submitted plan.</small>
                        @error('plan_reference') <em>{{ $message }}</em> @enderror
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
                               min="0.01" step="0.01" inputmode="decimal" required value="{{ $editBudget }}" placeholder="0.00">
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
                                        <span class="ap-sdg-icon" style="--sdg-color: {{ $goalColor }}" aria-hidden="true"><i class="bi bi-{{ $goalIcon }}"></i></span>
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
                <div class="ap-rule">
                    <i class="bi bi-info-circle"></i>
                    <span><strong>Submission rule:</strong> Complete all required pre-activity documents. Enable each condition that applies; other documents remain available for later upload.</span>
                </div>
                <div id="activityUploadNotice" class="ap-upload-notice" role="status" aria-live="polite" hidden></div>
                @foreach ($requirementSets as $typeKey => $requirements)
                    <div class="ap-requirement-panel" data-requirement-type="{{ $typeKey }}" @if ($currentType !== $typeKey) hidden @endif>
                        @foreach ($requirements as $requirement)
                            @php
                                $condition = $requirement['condition'] ?? null;
                                $storedFile = $storedAttachments[$requirement['key']] ?? null;
                                $hasFile = $hasStoredFile($storedFile);
                                $conditionIsActive = !$condition || (bool) data_get($activeConditions, $condition, false) || $hasFile;
                                $isRequired = !empty($requirement['required_on_submit']);
                                $sourceFile = $requirement['source_file'] ?? null;
                                $fileId = 'activity-file-'.$typeKey.'-'.$requirement['key'];
                            @endphp
                            <div class="ap-requirement-row {{ $condition && !$conditionIsActive ? 'is-inactive' : '' }} {{ $hasFile ? 'has-file' : '' }}"
                                 data-requirement-row data-condition="{{ $condition ?? '' }}" data-required="{{ $isRequired ? '1' : '0' }}" data-existing-file="{{ $hasFile ? '1' : '0' }}">
                                <span class="ap-file-icon" aria-hidden="true"><i class="bi {{ $hasFile ? 'bi-check-lg' : 'bi-file-earmark-text' }}" data-file-icon></i></span>
                                <div class="ap-requirement-copy">
                                    <div class="ap-requirement-title">
                                        <span class="ap-requirement-kicker">Requirement {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}{{ $isRequired && !$condition ? ' *' : '' }}</span>
                                        <strong>{{ $requirement['title'] }}</strong>
                                    </div>
                                    <p>{{ $requirement['description'] }}</p>
                                    <div class="ap-document-links">
                                        @if ($sourceFile)
                                            <button type="button" data-doc-preview
                                                    data-doc-preview-url="{{ route('office.activities.templates.download', ['type' => $typeKey, 'file' => $sourceFile]) }}"
                                                    data-doc-preview-title="{{ $requirement['title'] }} — Official template"
                                                    data-doc-preview-download-url="{{ route('office.activities.templates.download', ['type' => $typeKey, 'file' => $sourceFile]) }}"><i class="bi bi-eye"></i> View</button>
                                            <a href="{{ route('office.activities.templates.download', ['type' => $typeKey, 'file' => $sourceFile]) }}"><i class="bi bi-download"></i> Download</a>
                                        @else
                                            <small>No official template supplied; attach your organization's completed document.</small>
                                        @endif
                                        @if (!$isRequired)<small>{{ $requirement['phase'] ?? 'Later upload' }} · Not required for initial filing</small>@endif
                                    </div>
                                    @if ($condition)
                                        <label class="ap-condition-toggle">
                                            <input type="checkbox" name="conditions[{{ $condition }}]" value="1" data-condition-toggle="{{ $condition }}"
                                                   @checked($conditionIsActive) @disabled($currentType !== $typeKey)>
                                            Applicable to this activity
                                        </label>
                                    @endif
                                    @error('attachments.'.$requirement['key']) <em>{{ $message }}</em> @enderror
                                    @if ($hasFile && $submission->exists)
                                        <div class="ap-document-links">
                                            <button type="button" data-doc-preview
                                                    data-doc-preview-url="{{ route('office.activities.attachments.file', ['submission' => $submission->id, 'key' => $requirement['key'], 'preview' => 1]) }}"
                                                    data-doc-preview-title="{{ $storedFile['name'] ?? $requirement['title'] }}"
                                                    data-doc-preview-download-url="{{ route('office.activities.attachments.file', ['submission' => $submission->id, 'key' => $requirement['key'], 'download' => 1]) }}">View current file</button>
                                            <button type="button" data-attachment-delete
                                                    data-attachment-delete-url="{{ route('office.activities.attachments.destroy', [$submission->id, $requirement['key']]) }}"
                                                    data-attachment-name="{{ $storedFile['name'] ?? $requirement['title'] }}">Remove current file</button>
                                        </div>
                                    @endif
                                </div>
                                <div class="ap-requirement-upload">
                                    <span class="ap-file-status" data-file-status data-current-name="{{ $hasFile ? ($storedFile['name'] ?? basename($storedFile['path'])) : '' }}">{{ $hasFile ? ($storedFile['name'] ?? basename($storedFile['path'])) : 'No file selected.' }}</span>
                                    <label class="ap-upload-button" for="{{ $fileId }}">
                                        <i class="bi bi-upload" aria-hidden="true"></i>
                                        <span data-upload-label>{{ $hasFile ? 'Replace file' : 'Upload' }}</span>
                                        <input id="{{ $fileId }}" type="file" name="attachments[{{ $requirement['key'] }}]"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"
                                               aria-label="Upload {{ $requirement['title'] }}" data-requirement-file data-condition-file="{{ $condition ?? '' }}"
                                               @disabled($currentType !== $typeKey || !$conditionIsActive)>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </section>

            <details class="ap-card ap-supporting">
                <summary>Official checklist and supporting documents <small>Optional extras</small></summary>
                <p>Download the full template pack or attach supporting files. Extra files do not replace the required checklist uploads.</p>
                <div class="ap-footer-actions">
                    <a id="downloadTemplatesBtn" href="{{ route('office.activities.templates.download', ['type' => $currentType]) }}" class="ap-button ap-button-outline"><i class="bi bi-download"></i> Download template pack</a>
                    <label class="ap-upload-button" for="bulkDocUpload">
                        <i class="bi bi-upload"></i> Add supporting files
                        <input id="bulkDocUpload" type="file" name="supporting_documents[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg">
                    </label>
                    <span id="bulkDocUploadStatus" class="ap-file-status" aria-live="polite">No supporting files selected.</span>
                </div>
                @foreach ($activityDocsByType as $docsType => $docs)
                    @php $checklistSources = array_column($requirementSets[$docsType] ?? [], 'source_file'); $checklistKeys = array_column($requirementSets[$docsType] ?? [], 'key'); @endphp
                    <div class="ap-extra-docs" data-document-type="{{ $docsType }}" @if ($docsType !== $currentType) hidden @endif>
                        @foreach ($docs as $doc)
                            @if ((($doc['kind'] ?? '') === 'template' && !in_array($doc['name'], $checklistSources, true)) || (($doc['kind'] ?? '') === 'upload' && !in_array($doc['key'], $checklistKeys, true)))
                                <div class="ap-requirement-row">
                                    <span class="ap-file-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                                    <div class="ap-requirement-copy">
                                        <strong>{{ $doc['name'] }}</strong>
                                        <p>{{ $doc['status'] }}{{ filled($doc['date'] ?? '') && $doc['date'] !== '—' ? ' · '.$doc['date'] : '' }}</p>
                                    </div>
                                    <div class="ap-document-links">
                                        <button type="button" data-doc-preview data-doc-preview-url="{{ $doc['url'] }}" data-doc-preview-title="{{ $doc['name'] }}" data-doc-preview-download-url="{{ $doc['download_url'] ?? $doc['url'] }}"><i class="bi bi-eye"></i> View</button>
                                        <a href="{{ $doc['download_url'] ?? $doc['url'] }}"><i class="bi bi-download"></i> Download</a>
                                        @if (($doc['kind'] ?? '') === 'upload' && $submission->exists)
                                            <button type="button" data-attachment-delete data-attachment-delete-url="{{ route('office.activities.attachments.destroy', [$submission->id, $doc['key']]) }}" data-attachment-name="{{ $doc['name'] }}">Remove</button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </details>
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

        <div class="ap-footer">
            <div class="ap-footer-copy" aria-live="polite">
                <strong id="activityRemainingCount">Preparing the required-document checklist…</strong>
                <small id="activityRemainingHelp">Files are uploaded when you save or submit. Maximum 20 MB per file.</small>
            </div>
            <div class="ap-footer-actions">
                <a href="{{ route('office.activities') }}" class="ap-button ap-button-quiet">Cancel</a>
                <button type="submit" name="submission_action" value="draft" class="ap-button ap-button-outline"><i class="bi bi-save2"></i> Save draft</button>
                <button type="submit" name="submission_action" value="submit" id="activitySubmitButton" class="ap-button ap-button-primary" disabled><i class="bi bi-arrow-right"></i> Submit for Review</button>
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

    <script src="{{ asset('js/activity-create.js') }}?v={{ filemtime(public_path('js/activity-create.js')) }}"></script>
@endsection
