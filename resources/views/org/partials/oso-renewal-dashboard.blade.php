@php
    $filingEnabled = (bool) old('is_open', $window?->is_open ?? false);
    $windowState = $isOpen ? 'Renewal open' : (! $window?->is_open ? 'Renewal locked' : ($window->opens_at?->isFuture() ? 'Scheduled opening' : 'Schedule ended'));
    $formContext = old('form_context');
    $editingFile = $officialRequirementFiles[old('doc_key', '')] ?? null;
    $templateAccept = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg';
    $statusLabels = ['submitted' => 'Pending Review', 'returned' => 'For Revision', 'rejected' => 'Rejected', 'approved' => 'Renewed', 'draft' => 'Draft', 'none' => 'Pending Filing'];
    $statusColors = ['submitted' => 'amber', 'returned' => 'red', 'rejected' => 'red', 'approved' => 'green', 'draft' => 'muted', 'none' => 'amber'];
@endphp
<link rel="stylesheet" href="{{ asset('css/oso-renewal.css') }}?v={{ filemtime(public_path('css/oso-renewal.css')) }}">
<div id="osoRenewalDashboard" class="or-dashboard">
    <section class="or-metrics" aria-label="Organization renewal totals">
        @foreach ([
            ['label' => 'Total organizations', 'value' => $orgStats['total'], 'hint' => 'Official OSO records', 'icon' => 'people', 'color' => 'red'],
            ['label' => 'Qualified to renew', 'value' => $orgStats['qualified'], 'hint' => 'OSO filing eligibility', 'icon' => 'check-lg', 'color' => 'green'],
            ['label' => 'Not qualified', 'value' => $orgStats['not_qualified'], 'hint' => 'Unresolved filing eligibility', 'icon' => 'x-lg', 'color' => 'red'],
            ['label' => 'Inactive organizations', 'value' => $orgStats['inactive'], 'hint' => 'Inactive or not renewed', 'icon' => 'archive', 'color' => 'muted'],
        ] as $metric)
            <article class="or-metric">
                <span class="or-metric-icon is-{{ $metric['color'] }}"><i class="bi bi-{{ $metric['icon'] }}" aria-hidden="true"></i></span>
                <div class="or-metric-copy"><span class="or-metric-label">{{ $metric['label'] }}</span><strong class="or-metric-value">{{ $metric['value'] }}</strong><small class="or-metric-hint">{{ $metric['hint'] }}</small></div>
            </article>
        @endforeach
    </section>

    <section class="or-card" aria-labelledby="osoRenewalWindowHeading">
        <header class="or-card-head">
            <div class="or-title"><span class="or-title-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span><div><h2 id="osoRenewalWindowHeading">OSO Renewal Window</h2><p class="or-subtitle">Configure when eligible organizations can access and submit their renewal applications.</p></div></div>
            <div class="or-head-actions"><span id="osoRenewalWindowState" class="or-pill is-{{ $isOpen ? 'green' : 'red' }}">{{ $windowState }}</span><button id="osoRenewalToggle" type="button" class="or-button is-small" aria-pressed="{{ $filingEnabled ? 'true' : 'false' }}" aria-controls="osoRenewalOpenValue">{{ $filingEnabled ? 'Lock renewal' : 'Enable renewal' }}</button></div>
        </header>
        <form id="osoRenewalWindowForm" class="or-window-form" method="POST" action="{{ route('office.renewal.window') }}">
            @csrf
            <input type="hidden" name="window_id" value="{{ $window?->id }}">
            <input type="hidden" name="form_context" value="window">
            <input id="osoRenewalOpenValue" type="hidden" name="is_open" value="{{ $filingEnabled ? '1' : '0' }}" data-saved-value="{{ $window?->is_open ? '1' : '0' }}">
            <div class="or-fields">
                <label class="or-field">Academic Year<input name="academic_year" value="{{ old('academic_year', $window?->academic_year ?? '2026-2027') }}" maxlength="32" required></label>
                <label class="or-field">Semester<select name="semester" required>@foreach (array_unique(['Annual', '1st Semester', '2nd Semester', 'Summer', old('semester', $window?->semester ?? 'Annual')]) as $semester)<option value="{{ $semester }}" @selected(old('semester', $window?->semester ?? 'Annual') === $semester)>{{ $semester }}</option>@endforeach</select></label>
                <label class="or-field">Opens at<input id="osoRenewalOpensAt" type="datetime-local" name="opens_at" value="{{ old('opens_at', $window?->opens_at?->format('Y-m-d\TH:i')) }}"></label>
                <label class="or-field">Closes at<input id="osoRenewalClosesAt" type="datetime-local" name="closes_at" value="{{ old('closes_at', $window?->closes_at?->format('Y-m-d\TH:i')) }}"></label>
                <label class="or-field or-full">Instructions<textarea name="instructions" rows="3" maxlength="5000" placeholder="Submit all required official attachments, with complete details and signatures.">{{ old('instructions', $window?->instructions) }}</textarea></label>
            </div>
            <footer class="or-form-footer"><p class="or-helper"><i class="bi bi-info-circle" aria-hidden="true"></i> Changes take effect after saving. A different academic year or semester starts a new filing period.</p><button type="submit" class="or-button is-primary">Save Settings</button></footer>
        </form>
    </section>

    <section class="or-card" aria-labelledby="osoRenewalApplicationsHeading">
        <header class="or-card-head"><div><h2 id="osoRenewalApplicationsHeading">Renewal Applications</h2><p class="or-subtitle">Organization standing is based on the final OSO renewal decision, not account existence or uploads alone.</p></div><button id="osoRenewalExport" type="button" class="or-button is-small"><i class="bi bi-download" aria-hidden="true"></i> Export list</button></header>
        <div class="or-note"><i class="bi bi-award" aria-hidden="true"></i><div><strong>Official OSO status basis</strong><p><strong>Active · Renewed</strong> means the current filing period has final OSO approval and the organization is operationally enabled. Otherwise its standing remains inactive or not renewed. Filing eligibility and operational flags are managed separately.</p></div></div>
        <div class="or-filters"><label class="or-search"><i class="bi bi-search" aria-hidden="true"></i><input id="osoRenewalSearch" type="search" placeholder="Search organization or college…" aria-label="Search organization or college"></label><select id="osoRenewalStatusFilter" aria-label="Filter renewal applications"><option value="all">All statuses</option><option value="submitted">Pending Review</option><option value="returned">For Revision</option><option value="approved">Renewed</option><option value="rejected">Rejected</option><option value="draft">Draft</option><option value="none">Pending Filing</option><option value="active">Active · Renewed</option><option value="inactive">Inactive organizations</option><option value="qualified">Qualified to renew</option><option value="not_qualified">Not qualified</option></select></div>
        <div class="or-table-scroll">
            <table id="osoRenewalApplicationsTable" class="or-table"><thead><tr><th scope="col">Organization</th><th scope="col">Official Status</th><th scope="col">Submitted</th><th scope="col">Renewal Status</th><th scope="col">Requirements</th><th scope="col">Action</th></tr></thead><tbody>
                @foreach ($allOrganizations as $organization)
                    @php
                        $status = $organization['submission_status'];
                        $standing = $organization['officially_active'] ? 'Active · Renewed' : ($status === 'approved' ? 'Inactive · Renewed' : 'Inactive · Not Renewed');
                        $initials = mb_substr($organization['short_name'], 0, 4);
                    @endphp
                    <tr data-renewal-row data-search="{{ $organization['name'].' '.$organization['short_name'].' '.$organization['college'] }}" data-status="{{ $status }}" data-official-active="{{ $organization['officially_active'] ? '1' : '0' }}" data-qualified="{{ $organization['can_file_renewal'] ? '1' : '0' }}">
                        <td><div class="or-org"><span class="or-org-mark" title="{{ $organization['short_name'] }}" aria-hidden="true">{{ $initials }}</span><div class="or-org-copy"><strong class="or-name">{{ $organization['name'] }}</strong><small class="or-college">{{ $organization['college'] }}</small></div></div></td>
                        <td><span class="or-pill or-official-status is-{{ $organization['officially_active'] ? 'green' : 'red' }}">{{ $standing }}</span>@if (! $organization['can_file_renewal'])<small class="or-helper" title="{{ $organization['disqualification_reason'] ?? 'Operationally inactive organizations cannot file.' }}">Not qualified to file</small>@endif</td>
                        <td class="or-submitted-date">{{ $organization['submitted_at']?->format('M j, Y') ?? 'Not yet submitted' }}</td>
                        <td><span class="or-pill or-renewal-status is-{{ $statusColors[$status] ?? 'muted' }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span></td>
                        <td><span class="or-pill or-requirements-status is-{{ $organization['requirements_pending'] === 0 ? 'green' : 'red' }}" title="{{ $organization['requirements_verified'] }}/{{ $organization['requirements_required'] }} current requirements verified; {{ $organization['requirements_missing'] }} not uploaded">{{ $organization['requirements_pending'] === 0 ? 'Complete' : $organization['requirements_pending'].' pending' }}</span></td>
                        <td><div class="or-row-actions">@if ($organization['submission_id'])<a class="or-button is-small" href="{{ route('office.renewal.submissions.show', $organization['submission_id']) }}">View Application</a>@else<span class="or-button is-small" aria-disabled="true">No Submission</span>@endif<button type="button" class="or-button is-small" data-or-eligibility data-organization='@json($organization)' aria-label="Manage eligibility for {{ $organization['name'] }}" title="Manage filing eligibility"><i class="bi bi-sliders" aria-hidden="true"></i></button></div></td>
                    </tr>
                @endforeach
            </tbody></table>
        </div>
        <p id="osoRenewalEmpty" class="or-empty" @if($allOrganizations->isNotEmpty()) hidden @endif>No organizations match these filters.</p>
        <footer class="or-table-footer"><small id="osoRenewalResultCount" role="status" aria-live="polite">{{ $allOrganizations->count() }} organizations</small><nav id="osoRenewalPagination" class="or-pagination" aria-label="Renewal application pages"></nav></footer>
        @if ($otherRenewalSubmissions->isNotEmpty())
            <details class="or-note"><summary>Other packets and unlisted organizations ({{ $otherRenewalSubmissions->count() }})</summary>@foreach ($otherRenewalSubmissions as $submission)<p><a href="{{ route('office.renewal.submissions.show', $submission) }}">{{ $submission->organization_name }} — {{ $statusLabels[$submission->status] ?? ucfirst($submission->status) }}</a></p>@endforeach</details>
        @endif
    </section>

    <section class="or-card" aria-labelledby="osoRenewalRequirementsHeading">
        <header class="or-card-head"><div><h2 id="osoRenewalRequirementsHeading">Official Renewal Requirement Files</h2><p class="or-subtitle">Manage the downloadable OSO templates used by organizations for renewal.</p></div><div class="or-head-actions"><span class="or-pill is-red">{{ count($docs) }} requirements</span><button type="button" class="or-button is-primary is-small" data-or-add @disabled(! $window || count($docs) >= 30)><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Official Requirement</button></div></header>
        <div class="or-note"><i class="bi bi-file-earmark-text" aria-hidden="true"></i><p>Replacing a file updates the blank official template available to organizations. Existing signed submissions and final renewal decisions are not changed.</p></div>
        @if (! $window)<p class="or-helper">Save a renewal window first to manage official requirements.</p>@endif
        <div class="or-requirements-grid">
            @foreach ($officialRequirementFiles as $file)
                <article class="or-requirement">
                    <span class="or-requirement-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                    <div class="or-requirement-copy"><span class="or-requirement-code">{{ $file['code'] }} *</span><strong class="or-requirement-name" title="{{ $file['title'] }}">{{ $file['title'] }}</strong><small class="or-requirement-description" title="{{ $file['description'] ?? '' }}">{{ $file['description'] ?? 'Official OSO renewal requirement template' }}</small></div>
                    <div class="or-requirement-actions">
                        <button type="button" class="or-button is-small" data-or-view data-url="{{ $file['preview_url'] }}" data-download-url="{{ $file['download_url'] }}" data-title="{{ $file['title'] }}" data-filename="{{ $file['file_name'] }}" data-type="{{ $file['preview_type'] }}" @disabled(! $file['file_available'])>View File</button>
                        <form method="POST" action="{{ route('office.renewal.requirements.template') }}" enctype="multipart/form-data" data-or-replace-form>
                            @csrf<input type="hidden" name="window_id" value="{{ $window?->id }}"><input type="hidden" name="doc_key" value="{{ $file['key'] }}"><input type="hidden" name="form_context" value="replace_template">
                            <input type="file" name="document" accept="{{ $templateAccept }}" required data-or-file data-status-target="#osoRenewalReplaceStatus{{ $loop->iteration }}" aria-label="Replace official file for {{ $file['title'] }}" hidden>
                            <button type="button" class="or-button is-small" data-or-replace @disabled(! $window)>{{ $file['file_available'] ? 'Replace File' : 'Upload File' }}</button>
                        </form>
                        <button type="button" class="or-button is-blue is-small" data-or-edit data-key="{{ $file['key'] }}" data-code="{{ $file['code'] }}" data-title="{{ $file['title'] }}" data-description="{{ $file['description'] ?? '' }}" data-template-name="{{ $file['file_name'] ?? 'No file attached' }}" data-update-url="{{ route('office.renewal.requirements.update', $file['key']) }}" @disabled(! $window)>Edit</button>
                        <button type="button" class="or-button is-danger is-small" data-or-delete data-key="{{ $file['key'] }}" data-title="{{ $file['title'] }}" data-delete-url="{{ route('office.renewal.requirements.destroy', $file['key']) }}" @disabled(! $window || count($docs) <= 1)>Delete</button>
                    </div>
                    <span id="osoRenewalReplaceStatus{{ $loop->iteration }}" class="or-file-status" aria-live="polite"></span>
                </article>
            @endforeach
        </div>
    </section>

    <dialog id="osoRenewalAddDialog" class="or-dialog" aria-labelledby="osoRenewalAddHeading" data-reopen="{{ $errors->any() && $formContext === 'add_requirement' ? 'true' : 'false' }}">
        <form id="osoRenewalAddForm" method="POST" action="{{ route('office.renewal.requirements.store') }}" enctype="multipart/form-data">
            @csrf<input type="hidden" name="window_id" value="{{ $window?->id }}"><input type="hidden" name="form_context" value="add_requirement">
            <header class="or-dialog-head"><div class="or-title"><span class="or-title-icon"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i></span><div><h2 id="osoRenewalAddHeading">Add Official Requirement</h2><p class="or-subtitle">Create a new renewal requirement and upload its official OSO template.</p></div></div><button type="button" class="or-dialog-close" data-or-close aria-label="Close add requirement"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <div class="or-dialog-body">
                <label class="or-field"><span class="or-field-label">Requirement Code <small>Required</small></span><input id="osoRenewalAddCode" name="code" placeholder="e.g. Attachment K" value="{{ $formContext === 'add_requirement' ? old('code') : '' }}" maxlength="80" required></label>
                <label class="or-field"><span class="or-field-label">Requirement Name <small>Required</small></span><input id="osoRenewalAddTitle" name="title" placeholder="Enter the official requirement name" value="{{ $formContext === 'add_requirement' ? old('title') : '' }}" maxlength="255" required></label>
                <label class="or-field">Description<textarea id="osoRenewalAddDescription" name="description" rows="3" maxlength="5000" placeholder="Brief instructions or purpose of this requirement.">{{ $formContext === 'add_requirement' ? old('description') : '' }}</textarea></label>
                <label class="or-field"><span class="or-field-label">Official Template File <small>Required</small></span><span class="or-file-picker"><input id="osoRenewalAddFile" type="file" name="document" accept="{{ $templateAccept }}" required data-or-file data-status-target="#osoRenewalAddFileStatus"></span><small class="or-helper">PDF, Office documents, PNG or JPG · maximum 20 MB</small><span id="osoRenewalAddFileStatus" class="or-file-status" aria-live="polite"></span></label>
                @if ($errors->any() && $formContext === 'add_requirement')<p class="or-file-status">{{ $errors->first() }} Select the official file again before submitting.</p>@endif
            </div>
            <footer class="or-dialog-footer"><button type="button" class="or-button" data-or-close>Cancel</button><button id="osoRenewalAddSubmit" type="submit" class="or-button is-primary" disabled>Add Requirement</button></footer>
        </form>
    </dialog>

    <dialog id="osoRenewalEditDialog" class="or-dialog" aria-labelledby="osoRenewalEditHeading" data-reopen="{{ $errors->any() && $formContext === 'edit_requirement' && $editingFile ? 'true' : 'false' }}">
        <form id="osoRenewalEditForm" method="POST" action="{{ $editingFile ? route('office.renewal.requirements.update', $editingFile['key']) : '' }}">
            @csrf @method('PATCH')<input type="hidden" name="window_id" value="{{ $window?->id }}"><input type="hidden" name="form_context" value="edit_requirement"><input id="osoRenewalEditKey" type="hidden" name="doc_key" value="{{ old('doc_key') }}">
            <header class="or-dialog-head"><div class="or-title"><span class="or-title-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span><div><h2 id="osoRenewalEditHeading">Edit Official Requirement</h2><p class="or-subtitle">Update the requirement name and instructions shown to organizations.</p></div></div><button type="button" class="or-dialog-close" data-or-close aria-label="Close edit requirement"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <div class="or-dialog-body">
                <label class="or-field">Requirement Code<input id="osoRenewalEditCode" value="{{ $editingFile['code'] ?? '' }}" readonly></label>
                <label class="or-field"><span class="or-field-label">Requirement Name <small>Required</small></span><input id="osoRenewalEditTitle" name="title" value="{{ $formContext === 'edit_requirement' ? old('title') : '' }}" maxlength="255" required></label>
                <label class="or-field">Description<textarea id="osoRenewalEditDescription" name="description" rows="3" maxlength="5000">{{ $formContext === 'edit_requirement' ? old('description') : '' }}</textarea></label>
                <div class="or-current-file"><i class="bi bi-file-earmark-text" aria-hidden="true"></i><div><small class="or-helper">Current template</small><strong id="osoRenewalEditCurrentFile">{{ $editingFile['file_name'] ?? 'No file attached' }}</strong></div></div>
                @if ($errors->any() && $formContext === 'edit_requirement')<p class="or-file-status">{{ $errors->first() }}</p>@endif
            </div>
            <footer class="or-dialog-footer"><button type="button" class="or-button" data-or-close>Cancel</button><button id="osoRenewalEditSubmit" type="submit" class="or-button is-primary">Save Changes</button></footer>
        </form>
    </dialog>

    <dialog id="osoRenewalDeleteDialog" class="or-dialog" aria-labelledby="osoRenewalDeleteHeading">
        <form id="osoRenewalDeleteForm" method="POST" action="">
            @csrf @method('DELETE')<input type="hidden" name="window_id" value="{{ $window?->id }}"><input id="osoRenewalDeleteKey" type="hidden" name="doc_key">
            <header class="or-dialog-head"><h2 id="osoRenewalDeleteHeading">Remove Official Requirement</h2><button type="button" class="or-dialog-close" data-or-close aria-label="Close remove requirement"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <div class="or-dialog-body"><strong id="osoRenewalDeleteTitle"></strong><p class="or-subtitle">Remove this requirement from the current filing checklist? Existing signed uploads and historical template files will remain unchanged.</p></div>
            <footer class="or-dialog-footer"><button type="button" class="or-button" data-or-close>Cancel</button><button type="submit" class="or-button is-primary">Remove Requirement</button></footer>
        </form>
    </dialog>

    <dialog id="osoRenewalPreviewDialog" class="or-dialog or-preview-dialog" aria-labelledby="osoRenewalPreviewTitle">
        <header class="or-dialog-head"><h2 id="osoRenewalPreviewTitle">Official Template</h2><div class="or-head-actions"><a id="osoRenewalPreviewDownload" class="or-button is-small" href="#">Download</a><a id="osoRenewalPreviewExternal" class="or-button is-small" href="#" target="_blank" rel="noopener">Open File</a><button type="button" class="or-dialog-close" data-or-close aria-label="Close official template preview"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div></header>
        <div class="or-preview-body"><p id="osoRenewalPreviewMessage" class="or-preview-message" role="status" hidden></p><iframe id="osoRenewalPreviewFrame" class="or-preview-frame" title="Official renewal template preview" hidden></iframe><div id="osoRenewalPreviewDocx" class="or-preview-docx" hidden></div></div>
    </dialog>
</div>
<script src="{{ asset('js/vendor/jszip.min.js') }}" defer></script>
<script src="{{ asset('js/vendor/docx-preview.min.js') }}" defer></script>
<script src="{{ asset('js/oso-renewal.js') }}?v={{ filemtime(public_path('js/oso-renewal.js')) }}" defer></script>
