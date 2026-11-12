@extends('org.layout')

@section('title', 'Users & Roles')

@section('header')
    <h1><strong>Users &amp; Roles</strong></h1>
    <p class="org-welcome">Manage office and student organization accounts, register new student organizations with emailed credentials, and handle secure officer turnover.</p>
@endsection

@section('content')
@php
    $isOso = ($office->office_role ?? '') === 'oso';
    $settingsUsers = $officeUsers ?? collect();
    $collegesList = $accountColleges ?? collect();
@endphp

<style>
    .org-settings-avatar-sm.has-logo {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 2px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .org-accounts-page {
        display: flex !important;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
        max-width: 100%;
    }
    .org-accounts-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        padding: 0.35rem;
        border: 1px solid #eadde0;
        border-radius: 14px;
        background: #fff;
        width: fit-content;
        max-width: 100%;
    }
    .org-accounts-tab {
        border: 0;
        border-radius: 10px;
        padding: 0.7rem 1rem;
        background: transparent;
        color: #5f5559;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }
    .org-accounts-tab[aria-selected="true"] {
        background: #fbecef;
        color: #8b1828;
    }
    .org-accounts-tab:focus-visible {
        outline: 2px solid #8b1828;
        outline-offset: 2px;
    }
    .org-accounts-page [hidden] {
        display: none !important;
    }
    #accountsPanel {
        display: grid;
        gap: 1.25rem;
        min-width: 0;
    }
    .org-settings-user-identity > div:last-child {
        min-width: 0;
        overflow-wrap: anywhere;
    }
    /* Modern, minimalist flat table styling */
    .org-settings-table {
        width: 100%;
        border-collapse: collapse;
    }
    .org-settings-table th {
        padding: 12px 14px;
        border-bottom: 1px solid #e2e8f0;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .org-settings-table td {
        padding: 14px 14px;
        border-bottom: 1px solid #f1f5f9;
        background: #ffffff;
        vertical-align: middle;
        transition: background-color 0.15s ease;
    }
    .org-settings-table tbody tr:hover td {
        background-color: #fafbfd;
    }
    .org-settings-table tbody tr:last-child td {
        background: #fff8f9 !important; /* Soft faint light-pink/off-white background tint on bottom row */
    }

    /* Pill-shaped toggle switches */
    .org-toggle-switch {
        position: relative;
        display: inline-block;
        width: 38px;
        height: 20px;
        margin: 0;
        cursor: pointer;
        user-select: none;
        flex-shrink: 0;
    }
    .org-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
        margin: 0;
    }
    .org-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1; /* Inactive: light gray track */
        transition: background-color 0.2s ease;
        border-radius: 999px; /* Pill-shaped */
    }
    .org-toggle-slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: #ffffff; /* White circular slider knob */
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .org-toggle-switch input:checked + .org-toggle-slider {
        background-color: #2563eb; /* Active: bright blue track */
    }
    .org-toggle-switch input:checked + .org-toggle-slider:before {
        transform: translateX(18px);
    }
    .org-toggle-switch input:focus-visible + .org-toggle-slider {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }
    .org-toggle-switch input:disabled + .org-toggle-slider {
        opacity: 0.55;
        cursor: not-allowed;
    }

    /* Status Badges / Chips directly to the right of each toggle switch */
    .org-status-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 68px;
        padding: 2.5px 8px;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.25;
        border-radius: 4px; /* Compact, rounded-corner rectangular labels */
        letter-spacing: 0.02em;
        text-align: center;
        user-select: none;
        box-sizing: border-box;
    }
    .org-status-chip.is-active {
        background-color: #16a34a; /* Solid green background */
        color: #ffffff;            /* Bold white text (Level 1 and Enable) */
    }
    .org-status-chip.is-inactive {
        background-color: #334155; /* Solid charcoal-gray background */
        color: #ffffff;            /* Bold white text (Disable and No Access) */
    }
    .org-accounts-pagination button:disabled {
        opacity: 0.45;
        cursor: not-allowed !important;
    }
    .org-accounts-pagination [hidden] {
        display: none !important;
    }

    .org-accounts-header-actions {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }
    .org-accounts-form-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding-top: 0.35rem;
        border-top: 1px solid #f1f5f9;
    }
    .org-accounts-form-footer .org-settings-help {
        flex: 1 1 320px;
        line-height: 1.45;
    }
    .org-account-feedback {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 0.9rem 1.1rem;
        border-radius: 12px;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        color: #1e293b;
        font-size: 0.84rem;
    }
    .org-account-feedback[hidden] {
        display: none !important;
    }
    .org-account-feedback.is-success {
        background: #f0fdf4;
        border-color: #bbf7d0;
        color: #166534;
    }
    .org-account-feedback.is-error {
        background: #fff1f3;
        border-color: #fecdd3;
        color: #8b1828;
    }
    .org-account-feedback-main {
        display: flex;
        align-items: flex-start;
        gap: 0.7rem;
        flex: 1 1 280px;
        min-width: 0;
    }
    .org-account-feedback-main i {
        font-size: 1.1rem;
        margin-top: 0.1rem;
        flex-shrink: 0;
    }
    .org-account-feedback-main strong {
        display: block;
        font-size: 0.86rem;
        margin-bottom: 0.15rem;
    }
    .org-account-feedback-main p {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }
    .org-howto-steps {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 0.75rem;
    }
    .org-howto-step {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: 12px;
        border: 1.5px solid #f0e6e8;
        background: #fdfafb;
    }
    .org-howto-step-num {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #fdf0f2;
        color: #8b1828;
        font-size: 0.8rem;
        font-weight: 800;
        display: grid;
        place-items: center;
        flex-shrink: 0;
    }
    .org-howto-step strong {
        display: block;
        font-size: 0.86rem;
        color: #1a1618;
        margin-bottom: 0.2rem;
    }
    .org-howto-step p {
        margin: 0;
        font-size: 0.79rem;
        color: #475569;
        line-height: 1.5;
    }
    .org-settings-input[aria-invalid="true"],
    .org-settings-select[aria-invalid="true"] {
        border-color: #dc2626;
        background: #fffafb;
    }
    .org-settings-user-actions { display: inline-flex; align-items: center; flex-wrap: nowrap; gap: .3rem; white-space: nowrap; }
    .org-settings-user-actions .org-action-icon-btn { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; padding: 0; font-size: .95rem; line-height: 1; border-radius: 6px; flex-shrink: 0; }
    .org-action-icon-btn[data-tooltip]::before, .org-action-icon-btn[data-tooltip]::after { position: absolute; left: 50%; z-index: 5; opacity: 0; pointer-events: none; transform: translateX(-50%); }
    .org-action-icon-btn[data-tooltip]::before { content: attr(data-tooltip); bottom: calc(100% + 7px); padding: .3rem .5rem; border-radius: 6px; background: #30262a; color: #fff; font-size: .72rem; font-weight: 600; line-height: 1; white-space: nowrap; }
    .org-action-icon-btn[data-tooltip]::after { content: ''; bottom: calc(100% + 2px); border: 5px solid transparent; border-top-color: #30262a; border-bottom: 0; }
    .org-action-icon-btn[data-tooltip]:hover::before, .org-action-icon-btn[data-tooltip]:hover::after, .org-action-icon-btn[data-tooltip]:focus-visible::before, .org-action-icon-btn[data-tooltip]:focus-visible::after { opacity: 1; }
    @media (max-width: 768px) {
        .org-accounts-header-actions,
        .org-accounts-header-actions button,
        .org-accounts-form-footer button,
        .org-account-feedback button {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<section class="org-settings-panel org-accounts-page is-active" id="setPanel-users" aria-label="Users and Roles">
    <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 0.25rem;">
        <div class="org-accounts-header-actions">
            <button type="button" class="org-settings-btn-outline" id="accountsHowToUseBtn" aria-haspopup="dialog" aria-controls="accountsHowToUseModal" onclick="openAccountsHowToUseModal()">
                <i class="bi bi-question-circle-fill" aria-hidden="true"></i> How to use
            </button>
        </div>
    </div>

    {{-- Register New Student Organization & Email Officer Credentials --}}
    <div class="org-settings-card" id="newOrganizationAccountCard">
        <div class="org-settings-card-header">
            <div class="org-settings-card-icon"><i class="bi bi-building-add" aria-hidden="true"></i></div>
            <div>
                <h3 class="org-settings-card-title" id="newOrganizationCardTitle">Register New Student Organization</h3>
                <p class="org-settings-card-desc">Add a new organization and its SO officer. A strong temporary password is automatically generated and emailed to the officer.</p>
            </div>
        </div>
        <div class="org-settings-card-body">
            <form id="newOrganizationAccountForm" method="post" action="{{ route('office.organizations.accounts.store') }}" data-confirm="manual" onsubmit="handleCreateOrganizationAccount(event)" class="org-settings-account-form" autocomplete="off">
                @csrf
                <p id="newOrganizationAccountError" class="org-settings-form-error" role="alert" tabindex="-1" hidden></p>

                <div id="newOrgFeedback" class="org-account-feedback" role="status" aria-live="polite" tabindex="-1" hidden>
                    <div class="org-account-feedback-main">
                        <i id="newOrgFeedbackIcon" class="bi bi-info-circle-fill" aria-hidden="true"></i>
                        <div>
                            <strong id="newOrgFeedbackTitle"></strong>
                            <p id="newOrgFeedbackMessage"></p>
                        </div>
                    </div>
                    <button type="button" id="newOrgRetryBtn" class="org-settings-btn-outline" onclick="retryNewOrganizationEmail()" hidden>
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Retry Credential Email
                    </button>
                </div>

                <div class="org-settings-grid-2">
                    <div class="org-settings-field">
                        <label for="newOrgName" class="org-settings-label">
                            <span>Organization Name</span>
                            <span class="org-settings-required">*</span>
                        </label>
                        <input type="text" id="newOrgName" name="organization_name" class="org-settings-input" required maxlength="255" placeholder="e.g. Junior Philippine Computer Society (JPCS)">
                    </div>
                    <div class="org-settings-field">
                        <label for="newOrgOfficerEmail" class="org-settings-label">
                            <span>Authorized Email Address</span>
                            <span class="org-settings-required">*</span>
                        </label>
                        <input type="email" id="newOrgOfficerEmail" name="email" class="org-settings-input" required maxlength="255" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="e.g. officer@g.batstate-u.edu.ph or org@gmail.com">
                        <small class="org-settings-help">Use an official BatStateU email (<code>@g.batstate-u.edu.ph</code> / <code>@batstate-u.edu.ph</code>) or the organization's Gmail address (<code>@gmail.com</code>).</small>
                    </div>
                    <div class="org-settings-field">
                        <label for="newOrgShortName" class="org-settings-label">Short Name / Acronym (optional)</label>
                        <input type="text" id="newOrgShortName" name="short_name" class="org-settings-input" maxlength="64" placeholder="e.g. JPCS">
                    </div>
                    <div class="org-settings-field">
                        <label for="newOrgCollege" class="org-settings-label">
                            <span>College / Department</span>
                            <span class="org-settings-required">*</span>
                        </label>
                        <input type="text" id="newOrgCollege" name="college" list="newOrgCollegeList" class="org-settings-input" required maxlength="255" placeholder="Select or enter college">
                        <datalist id="newOrgCollegeList">
                            @foreach ($collegesList as $collegeOption)
                                <option value="{{ $collegeOption }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div class="org-accounts-form-footer">
                    <p class="org-settings-help" style="margin: 0;">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        Temporary credentials are emailed only. New organizations require approved renewal for academic-year recognition.
                    </p>
                    <button type="submit" id="newOrgSubmitBtn" class="org-settings-save-btn">
                        <i class="bi bi-envelope-check-fill" aria-hidden="true"></i> Register Organization
                    </button>
                </div>
            </form>
        </div>
    </div>
    {{-- Account Type Tabs --}}
    <div class="org-accounts-tabs" role="tablist" aria-label="Account type" style="margin: 0.25rem 0;">
        <button type="button" id="organizationAccountsTab" class="org-accounts-tab" role="tab" aria-selected="true" aria-controls="accountsPanel" data-accounts-tab="organization">Organization Accounts</button>
        <button type="button" id="officeAccountsTab" class="org-accounts-tab" role="tab" aria-selected="false" aria-controls="accountsPanel" data-accounts-tab="office" tabindex="-1">Office Accounts</button>
    </div>

    <div id="accountsPanel" role="tabpanel" aria-labelledby="organizationAccountsTab">

    {{-- User Accounts Table --}}
    <div class="org-settings-card">
        <div class="org-settings-card-header">
            <div class="org-settings-card-icon"><i class="bi bi-person-lines-fill" aria-hidden="true"></i></div>
            <div>
                <h3 class="org-settings-card-title" id="accountsTableTitle">Authorized Accounts</h3>
                <p class="org-settings-card-desc" id="accountsTableDescription">Officer names and their assigned organizations. Disabled accounts remain visible.</p>
            </div>
        </div>
        <div class="org-settings-card-body" style="padding: 0;">
            <div style="padding: 1rem;">
                <label for="settingsAccountSearch" class="org-settings-label">Search accounts</label>
                <input type="search" id="settingsAccountSearch" class="org-settings-input" placeholder="Name, university email, organization, or role" autocomplete="off" oninput="accountsCurrentPage = 1; filterSettingsAccounts(this.value)">
                <p id="settingsAccountSearchEmpty" class="org-settings-help" role="status" hidden></p>
            </div>
            <div class="org-settings-table-wrap">
                <table class="org-settings-table">
                    <thead>
                        <tr>
                            <th id="colHeaderName">ORGANIZATION NAME</th>
                            <th id="colHeaderDept">COLLEGE / DEPARTMENT</th>
                            <th id="colHeaderStatus" style="text-align: center; color: #64748b; font-family: system-ui, -apple-system, sans-serif; font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">ACCOUNT STATUS</th>
                            <th>LAST UPDATED</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="settingsUserListTbody">
                        @foreach ($settingsUsers as $user)
                            @php
                                $orgName = $user->office_role === 'so'
                                    ? ($user->studentOrganization?->name ?: $user->name)
                                    : $user->name;
                                $displayInitials = $user->office_role === 'so' && $user->studentOrganization?->short_name
                                    ? mb_substr($user->studentOrganization->short_name, 0, 3)
                                    : $user->initials();
                                $college = $user->studentOrganization?->college;
                                $userData = [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                    'organization_name' => $user->studentOrganization?->name,
                                    'org_logo_url' => $user->office_role === 'so' ? $user->studentOrganization?->logoUrl() : null,
                                    'college' => $college,
                                    'email' => $user->email,
                                    'office_role' => $user->office_role,
                                    'role' => $user->roleLabel(),
                                    'office_title' => $user->office_title,
                                    'employee_id' => $user->employee_id,
                                    'student_organization_id' => $user->student_organization_id,
                                    'initials' => $displayInitials,
                                    'updated_at' => $user->updated_at?->toIso8601String(),
                                    'tosa_clearance' => $user->effectiveTosaClearance(),
                                    'is_active' => (bool) $user->is_active,
                                    'must_change_password' => (bool) $user->must_change_password,
                                ];
                                $isCurrentAccount = (int) $user->id === (int) $office->id;
                            @endphp
                            <tr data-settings-user-id="{{ $user->id }}" data-settings-user="{{ json_encode($userData) }}" @if ($user->office_role !== 'so') hidden @endif>
                                <td>
                                    <div class="org-settings-user-identity">
                                        <div class="org-settings-avatar-sm {{ $userData['org_logo_url'] ? 'has-logo' : '' }}" style="overflow: hidden; border-radius: 8px;">@if ($userData['org_logo_url'])<img src="{{ $userData['org_logo_url'] }}" alt="{{ $orgName }}" style="width: 100%; height: 100%; object-fit: contain; display: block; border-radius: inherit;">@else{{ $displayInitials }}@endif</div>
                                        <div>
                                            <strong data-user-name style="text-transform: uppercase;">{{ strtoupper($orgName) }}</strong>
                                            <small data-user-email>{{ $user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span data-user-college style="font-weight: 600; color: #2d3748;">{{ $college ?? ($user->office_role === 'so' ? '—' : $userData['role']) }}</span>
                                    @if ($user->office_role !== 'so' && $user->office_title)
                                        <small style="display: block; color: #718096; font-size: 0.75rem;">{{ $user->office_title }}</small>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                                        <label class="org-toggle-switch" title="{{ $user->is_active ? 'Account Enabled' : 'Account Disabled' }}">
                                            <input type="checkbox" data-status-toggle="{{ $user->id }}" @checked($user->is_active) @disabled($isCurrentAccount) onclick="handleStatusToggleClick(event, {{ $user->id }})">
                                            <span class="org-toggle-slider"></span>
                                        </label>
                                        <span class="org-status-chip {{ $user->is_active ? 'is-active' : 'is-inactive' }}" data-status-chip="{{ $user->id }}">
                                            {{ $user->is_active ? 'Enable' : 'Disable' }}
                                        </span>
                                    </div>
                                    <small data-user-password-state style="display: block; font-size: 0.7rem; color: #dc2626; margin-top: 0.2rem;">{{ $userData['must_change_password'] ? 'Password change required' : '' }}</small>
                                </td>
                                <td data-user-updated>{{ $user->updated_at?->diffForHumans() ?? 'Not recorded' }}</td>
                                <td style="white-space: nowrap;">
                                    <div class="org-settings-user-actions">
                                        @if ($isCurrentAccount)
                                            <button type="button" class="org-settings-btn-subtle org-action-icon-btn" data-account-action="own" data-tooltip="My Account" aria-label="My Account"><i class="bi bi-person-circle" aria-hidden="true"></i></button>
                                        @else
                                            <button type="button" class="org-settings-btn-subtle org-action-icon-btn" data-account-action="edit" data-tooltip="Edit" aria-label="Edit profile: {{ $user->name }}"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                                            <button type="button" class="org-settings-btn-subtle org-action-icon-btn" data-account-action="reset" data-tooltip="Password" aria-label="Reset temporary password: {{ $user->name }}"><i class="bi bi-key-fill" aria-hidden="true"></i></button>
                                            @if ($user->office_role === 'so')
                                                <button type="button" class="org-settings-btn-subtle org-action-icon-btn" data-account-action="turnover" data-tooltip="Turnover" aria-label="Turn over SO officer: {{ $user->name }}" @disabled(!$user->is_active || !$user->student_organization_id) title="{{ !$user->is_active || !$user->student_organization_id ? 'Turnover requires an enabled account with an assigned organization' : 'Turn over SO officer' }}"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></button>
                                            @endif
                                            @if ($user->office_role === 'so' && $user->is_active && $user->student_organization_id && $user->must_change_password)
                                                <button type="button" class="org-settings-btn-subtle org-action-icon-btn" data-account-action="retry-email" data-tooltip="Email Credentials" data-retry-url="{{ route('office.organizations.accounts.retry', $user) }}" aria-label="Email credentials: {{ $user->name }}" title="Generate a new temporary password, replace the previous temporary password, and email login credentials"><i class="bi bi-envelope-at" aria-hidden="true"></i></button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="org-accounts-pagination" id="accountsPagination" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; padding: 0.85rem 1.25rem; border-top: 1px solid #f1f5f9; background: #fff;" hidden>
                <span id="accountsPageInfo" style="font-size: 0.82rem; color: #64748b; font-weight: 600;"></span>
                <nav style="display: flex; align-items: center; gap: 0.4rem;" aria-label="Accounts table pages">
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="accountsPrevBtn" onclick="changeAccountsPage(-1)" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; color: #8b1828; font-weight: 700;">Previous</button>
                    <div id="accountsPageNumbers" style="display: inline-flex; align-items: center; gap: 0.35rem;"></div>
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="accountsNextBtn" onclick="changeAccountsPage(1)" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; color: #8b1828; font-weight: 700;">Next</button>
                </nav>
            </div>
        </div>
    </div>
    </div>
    {{-- Roles & Permissions Matrix --}}
    <div class="org-settings-card">
        <div class="org-settings-card-header">
            <div class="org-settings-card-icon"><i class="bi bi-shield-shaded" aria-hidden="true"></i></div>
            <div>
                <h3 class="org-settings-card-title">Role Permissions Guide</h3>
                <p class="org-settings-card-desc">Account roles determine office access.</p>
            </div>
        </div>
        <div class="org-settings-card-body">
            <div class="org-settings-perm-grid">
                <div class="org-settings-perm-card">
                    <strong>OSO Office Accounts</strong>
                    <ul>
                        <li><i class="bi bi-check-circle-fill text-success"></i> Office settings and account management</li>
                    </ul>
                </div>
                <div class="org-settings-perm-card">
                    <strong>Student Organization Accounts</strong>
                    <ul>
                        <li><i class="bi bi-info-circle"></i> SO officers access only their assigned student organization. Turnover preserves its records and the outgoing officer's identity.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- How to Use Guide Sub-Modal --}}
<dialog id="accountsHowToUseModal" class="org-settings-submodal" aria-labelledby="accountsHowToUseTitle" style="max-width: 560px;">
    <div style="padding: 1.5rem;">
        <div class="org-settings-submodal-header">
            <div style="display: flex; align-items: center; gap: 0.55rem;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #fdf0f2; color: #8b1828; display: grid; place-items: center; font-size: 1.1rem;">
                    <i class="bi bi-question-circle-fill" aria-hidden="true"></i>
                </div>
                <h4 id="accountsHowToUseTitle" style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #1a1618;">How to Use Users &amp; Roles</h4>
            </div>
            <button type="button" class="org-settings-btn-subtle" aria-label="Close guide" onclick="document.getElementById('accountsHowToUseModal').close()">&times;</button>
        </div>
        <p class="org-settings-help" style="margin: 0 0 1rem; font-size: 0.8rem; color: #475569; line-height: 1.5;">
            Use this page to onboard new student organizations, manage office desk accounts, and handle officer transitions securely.
        </p>
        <ol class="org-howto-steps">
            <li class="org-howto-step">
                <span class="org-howto-step-num" aria-hidden="true">1</span>
                <div>
                    <strong>Register a New Student Organization</strong>
                    <p>Enter the organization name, optional acronym, college, officer full name, and official BatStateU or organization Gmail address. OrgChain creates the organization and SO officer account together, then emails a server-generated temporary password directly to the officer. New organizations are operational for renewal filing and are not marked recognized for an academic year until renewal is approved.</p>
                </div>
            </li>
            <li class="org-howto-step">
                <span class="org-howto-step-num" aria-hidden="true">2</span>
                <div>
                    <strong>Retry Failed Credential Emails</strong>
                    <p>If mail delivery fails during registration—or after reloading the page—the organization and officer account remain saved. Click <strong>Email credentials</strong> on the officer's row in the accounts table (or <strong>Retry Credential Email</strong> in the status banner) to generate a fresh temporary password that replaces the previous temporary password and resend the login email.</p>
                </div>
            </li>
            <li class="org-howto-step">
                <span class="org-howto-step-num" aria-hidden="true">3</span>
                <div>
                    <strong>Manage Existing Accounts</strong>
                    <p>Use table actions to <strong>Edit</strong> profiles, reset a <strong>Password</strong>, <strong>Disable / Enable</strong> access, or open <strong>My Account</strong> for your own profile.</p>
                </div>
            </li>
            <li class="org-howto-step">
                <span class="org-howto-step-num" aria-hidden="true">4</span>
                <div>
                    <strong>Turn Over an SO Officer</strong>
                    <p>Click <strong>Turn over</strong> on an active SO account to create the incoming officer's account and automatically disable the outgoing account while preserving all organization activities, ledgers, reports, and archives. Every new or reset account must change its temporary password on first login.</p>
                </div>
            </li>
        </ol>
        <div class="org-settings-dialog-actions" style="margin-top: 1.15rem;">
            <button type="button" class="org-settings-save-btn" onclick="document.getElementById('accountsHowToUseModal').close()">Got it</button>
        </div>
    </div>
</dialog>


<dialog id="settingsAccountModal" class="org-settings-submodal" aria-labelledby="settingsAccountTitle" aria-describedby="settingsAccountDescription" style="max-width: 480px;">
    <div style="padding: 1.5rem;">
        <div class="org-settings-submodal-header">
            <h4 id="settingsAccountTitle">Manage Officer Account</h4>
            <button type="button" class="org-settings-btn-subtle" aria-label="Close account form" onclick="document.getElementById('settingsAccountModal').close()">&times;</button>
        </div>
        <p id="settingsAccountSubject" class="org-settings-account-subject"></p>
        <p id="settingsAccountDescription" class="org-settings-help"></p>
        <form id="settingsAccountForm" method="post" data-confirm="manual" class="org-settings-account-form" autocomplete="off" onsubmit="handleAccountManagementSubmit(event)">
            <p id="settingsAccountError" class="org-settings-form-error" role="alert" tabindex="-1" hidden></p>
            <div id="settingsAccountImmutable" class="org-settings-field">
                <strong class="org-settings-label">Assigned role and organization</strong>
                <p id="settingsAccountAssignment" class="org-settings-help"></p>
                <small class="org-settings-help">Role and organization cannot be changed. Turnover creates a separate incoming account linked to the same organization.</small>
            </div>
            <div class="org-settings-field" data-account-profile-field>
                <label for="accountOfficerName" class="org-settings-label">Officer Full Name</label>
                <input type="text" id="accountOfficerName" class="org-settings-input" maxlength="255" required autocomplete="name">
            </div>
            <div class="org-settings-field" data-account-profile-field>
                <label for="accountOfficerEmail" class="org-settings-label">Official University Email</label>
                <input type="email" id="accountOfficerEmail" class="org-settings-input" maxlength="255" required autocomplete="off" autocapitalize="none" spellcheck="false">
                <small id="settingsTurnoverEmailHelp" class="org-settings-help" hidden>Use the incoming officer's own institutional email. It must be distinct from the outgoing account and not already registered.</small>
            </div>
            <div class="org-settings-field" data-account-profile-field>
                <label for="accountOfficerTitle" class="org-settings-label">Office / Officer Title (optional)</label>
                <input type="text" id="accountOfficerTitle" class="org-settings-input" maxlength="255">
            </div>
            <div class="org-settings-field" data-account-profile-field>
                <label for="accountOfficerEmployeeId" class="org-settings-label">Employee ID (optional)</label>
                <input type="text" id="accountOfficerEmployeeId" class="org-settings-input" maxlength="255">
            </div>
            <div id="accountTosaClearanceField" class="org-settings-field" hidden>
                <label for="accountTosaClearance" class="org-settings-label">TOSA access</label>
                <select id="accountTosaClearance" class="org-settings-select" disabled>
                    <option value="No Access">No Access</option>
                    <option value="Level 1 Read-only">Level 1 Read-only</option>
                </select>
                <small class="org-settings-help">TOSA access allows viewing dossiers. Changing access signs this officer out.</small>
            </div>
            <div class="org-settings-field" data-account-password-field>
                <label for="accountOfficerPassword" class="org-settings-label">Temporary Password</label>
                <input type="password" id="accountOfficerPassword" class="org-settings-input" required minlength="8" autocomplete="new-password" autocapitalize="none" spellcheck="false">
            </div>
            <div class="org-settings-field" data-account-password-field>
                <label for="accountOfficerPasswordConfirmation" class="org-settings-label">Confirm Temporary Password</label>
                <input type="password" id="accountOfficerPasswordConfirmation" class="org-settings-input" required minlength="8" autocomplete="new-password" autocapitalize="none" spellcheck="false">
                <small class="org-settings-help">Share this temporary password securely. The officer must choose a different password at first login. No invitation email is sent.</small>
            </div>
            <div class="org-settings-dialog-actions">
                <button type="button" class="org-settings-btn-subtle" onclick="document.getElementById('settingsAccountModal').close()">Cancel</button>
                <button type="submit" id="settingsAccountSubmit" class="org-settings-save-btn">Save</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    const settingsAccounts = new Map();
    let settingsAccountAction = null;
    let settingsAccountTargetId = null;
    let settingsAccountReturnFocus = null;
    let activeAccountsTab = 'organization';

    const osoAccountRoutes = {
        userStatusBase: @json(url('/office-desk/settings/users')),
        organizationAccounts: @json(route('office.organizations.accounts.store')),
        organizationRetryBase: @json(url('/office-desk/organizations/accounts')),
    };

    const newOrgFieldMap = {
        organization_name: 'newOrgName',
        short_name: 'newOrgShortName',
        college: 'newOrgCollege',
        officer_name: 'newOrgName',
        email: 'newOrgOfficerEmail',
    };

    function clearNewOrgFieldErrors() {
        Object.values(newOrgFieldMap).forEach(id => {
            document.getElementById(id)?.removeAttribute('aria-invalid');
        });
        const error = document.getElementById('newOrganizationAccountError');
        if (error) {
            error.textContent = '';
            error.hidden = true;
        }
    }

    function showNewOrgValidationError(message, errors = {}) {
        Object.entries(newOrgFieldMap).forEach(([field, id]) => {
            if (errors && errors[field]) {
                document.getElementById(id)?.setAttribute('aria-invalid', 'true');
            }
        });
        const error = document.getElementById('newOrganizationAccountError');
        if (!error) return;
        error.textContent = message;
        error.hidden = !message;
        if (message) error.focus();
    }

    function showNewOrgFeedback({ ok = false, title = '', message = '', retryUrl = '', userId = '' } = {}) {
        const box = document.getElementById('newOrgFeedback');
        const icon = document.getElementById('newOrgFeedbackIcon');
        const titleEl = document.getElementById('newOrgFeedbackTitle');
        const msgEl = document.getElementById('newOrgFeedbackMessage');
        const retryBtn = document.getElementById('newOrgRetryBtn');
        if (!box || !titleEl || !msgEl || !retryBtn) return;

        if (!message) {
            box.hidden = true;
            retryBtn.hidden = true;
            return;
        }

        box.className = `org-account-feedback ${ok ? 'is-success' : 'is-error'}`;
        if (icon) {
            icon.className = `bi ${ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}`;
        }
        titleEl.textContent = title || (ok ? 'Organization & Officer Account Ready' : 'Credential Email Delivery Failed');
        msgEl.textContent = message;
        if (!ok && retryUrl) {
            retryBtn.dataset.retryUrl = retryUrl;
            retryBtn.dataset.userId = String(userId || '');
            retryBtn.hidden = false;
        } else {
            retryBtn.hidden = true;
            delete retryBtn.dataset.retryUrl;
            delete retryBtn.dataset.userId;
        }
        box.hidden = false;
    }


    function handleCreateOrganizationAccount(event) {
        event.preventDefault();
        clearNewOrgFieldErrors();

        const orgName = settingsInputValue('newOrgName').trim();
        const payload = {
            organization_name: orgName,
            short_name: settingsInputValue('newOrgShortName').trim(),
            college: settingsInputValue('newOrgCollege').trim(),
            officer_name: orgName,
            email: settingsInputValue('newOrgOfficerEmail').trim(),
        };

        return runSettingsMutation(async () => {
            const response = await fetch(osoAccountRoutes.organizationAccounts, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': osoSettingsCsrf(),
                },
                body: JSON.stringify(payload),
            });

            const isJson = (response.headers.get('content-type') || '').includes('application/json');
            if (!isJson || response.redirected) {
                throw new Error(await osoSettingsError(response));
            }

            const data = await response.json();

            if (!response.ok && !data.created) {
                const message = Object.values(data.errors || {}).flat().join(' ') || data.message || 'Please correct the highlighted organization account details.';
                showNewOrgValidationError(message, data.errors || {});
                showSettingsToast(message, 'error');
                return;
            }

            if (data.user) upsertOsoUser(data.user);

            document.getElementById('newOrganizationAccountForm')?.reset();

            const retryUrl = data.retry_url || (data.user?.id ? `${osoAccountRoutes.organizationRetryBase}/${data.user.id}/retry-email` : '');
            if (data.email_sent) {
                showNewOrgFeedback({
                    ok: true,
                    title: 'Organization Registered & Credentials Emailed',
                    message: data.message || 'Organization and officer account created. Temporary login credentials were emailed.',
                });
                showSettingsToast(data.message || 'Organization and officer account created.', 'success');
            } else {
                const warnMessage = data.message || 'Organization and officer account were created, but the credential email could not be delivered. Check mail settings and use Retry to send a new temporary password.';
                showNewOrgFeedback({
                    ok: false,
                    title: 'Organization Created — Credential Email Failed',
                    message: warnMessage,
                    retryUrl,
                    userId: data.user?.id,
                });
                showSettingsToast(warnMessage, 'error');
                document.getElementById('newOrgRetryBtn')?.focus();
            }
        }, 'Register this new student organization and email temporary login credentials to the officer?');
    }

    function retryNewOrganizationEmail(userId = null, explicitRetryUrl = '') {
        const retryBtn = document.getElementById('newOrgRetryBtn');
        const targetId = userId !== null && userId !== undefined && userId !== ''
            ? Number(userId)
            : (retryBtn?.dataset.userId ? Number(retryBtn.dataset.userId) : null);
        const retryUrl = explicitRetryUrl
            || (targetId ? `${osoAccountRoutes.organizationRetryBase}/${targetId}/retry-email` : (retryBtn?.dataset.retryUrl || ''));
        if (!retryUrl) return;
        clearNewOrgFieldErrors();

        const targetUser = targetId ? settingsAccounts.get(Number(targetId)) : null;
        const confirmMessage = targetUser?.name
            ? `Email new login credentials to ${targetUser.name} (${targetUser.email})? This generates a new temporary password and replaces the previous temporary password.`
            : 'Email new login credentials to this officer? This generates a new temporary password and replaces the previous temporary password.';

        return runSettingsMutation(async () => {
            const response = await fetch(retryUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': osoSettingsCsrf(),
                },
                body: JSON.stringify({}),
            });

            const isJson = (response.headers.get('content-type') || '').includes('application/json');
            if (!isJson || response.redirected) {
                throw new Error(await osoSettingsError(response));
            }

            const data = await response.json();
            if (data.user) upsertOsoUser(data.user);

            if (response.ok && data.ok === true && data.email_sent === true) {
                const successMessage = data.message || 'Temporary login credentials were emailed to the officer.';
                showNewOrgFeedback({
                    ok: true,
                    title: 'Credentials Emailed',
                    message: successMessage,
                });
                showSettingsToast(successMessage, 'success');
                return;
            }

            const errorMessage = Object.values(data.errors || {}).flat().join(' ')
                || data.message
                || (response.status === 409
                    ? 'A credential email retry is already in progress for this account. Please wait a moment and try again.'
                    : 'Credential email could not be delivered. Check mail settings and try again.');
            const canStillRetry = response.status !== 422;
            showNewOrgFeedback({
                ok: false,
                title: response.status === 409 ? 'Credential Email Retry In Progress' : 'Credential Email Delivery Failed',
                message: errorMessage,
                retryUrl: canStillRetry ? (data.retry_url || retryUrl) : '',
                userId: data.user?.id || targetId || '',
            });
            showSettingsToast(errorMessage, 'error');
        }, confirmMessage);
    }

    function openAccountsHowToUseModal() {
        const dialog = document.getElementById('accountsHowToUseModal');
        if (!dialog || settingsMutationPending) return;
        settingsAccountReturnFocus = document.activeElement;
        if (!dialog.open) dialog.showModal();
        dialog.querySelector('button')?.focus();
    }

    const ACCOUNTS_PER_PAGE = 10;
    let accountsCurrentPage = 1;

    // Account metadata is shared by server-rendered rows and confirmed API responses.
    function filterSettingsAccounts(query = settingsInputValue('settingsAccountSearch')) {
        const q = query.trim().toLowerCase();
        const allRows = [...document.querySelectorAll('[data-settings-user-id]')];
        const matchingRows = [];

        allRows.forEach(row => {
            const user = settingsAccounts.get(Number(row.dataset.settingsUserId));
            if (!user) return;
            const text = [user.name, user.email, user.organization_name, user.college, user.role, user.office_role, user.office_title].join(' ').toLowerCase();
            const inTab = activeAccountsTab === 'organization' ? user.office_role === 'so' : user.office_role !== 'so';
            const matchesQuery = !Boolean(q) || text.includes(q);

            if (inTab && matchesQuery) {
                matchingRows.push(row);
            } else {
                row.hidden = true;
            }
        });

        const totalMatches = matchingRows.length;
        const totalPages = Math.max(1, Math.ceil(totalMatches / ACCOUNTS_PER_PAGE));

        if (accountsCurrentPage > totalPages) accountsCurrentPage = totalPages;
        if (accountsCurrentPage < 1) accountsCurrentPage = 1;

        const startIndex = (accountsCurrentPage - 1) * ACCOUNTS_PER_PAGE;
        const endIndex = startIndex + ACCOUNTS_PER_PAGE;

        matchingRows.forEach((row, index) => {
            row.hidden = !(index >= startIndex && index < endIndex);
        });

        const empty = document.getElementById('settingsAccountSearchEmpty');
        if (empty) {
            empty.hidden = totalMatches > 0;
            empty.textContent = q ? 'No accounts match your search.' : `No ${activeAccountsTab === 'organization' ? 'organization' : 'office'} accounts yet.`;
        }

        const pagination = document.getElementById('accountsPagination');
        const pageInfo = document.getElementById('accountsPageInfo');
        const prevBtn = document.getElementById('accountsPrevBtn');
        const nextBtn = document.getElementById('accountsNextBtn');
        const pageNumbersContainer = document.getElementById('accountsPageNumbers');

        if (pagination && pageInfo && prevBtn && nextBtn) {
            if (totalMatches <= ACCOUNTS_PER_PAGE) {
                pagination.hidden = totalMatches === 0;
                if (totalMatches > 0) {
                    pageInfo.textContent = `Showing all ${totalMatches} ${totalMatches === 1 ? 'account' : 'accounts'}`;
                    prevBtn.style.display = 'none';
                    nextBtn.style.display = 'none';
                    if (pageNumbersContainer) pageNumbersContainer.innerHTML = '';
                }
            } else {
                pagination.hidden = false;
                const displayStart = startIndex + 1;
                const displayEnd = Math.min(endIndex, totalMatches);
                pageInfo.textContent = `Showing ${displayStart}–${displayEnd} of ${totalMatches} accounts`;

                prevBtn.style.display = '';
                prevBtn.disabled = accountsCurrentPage <= 1;
                nextBtn.style.display = '';
                nextBtn.disabled = accountsCurrentPage >= totalPages;

                if (pageNumbersContainer) {
                    pageNumbersContainer.innerHTML = '';
                    for (let p = 1; p <= totalPages; p++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.type = 'button';
                        pageBtn.textContent = p;
                        pageBtn.style.cssText = 'min-width: 28px; height: 28px; padding: 0 6px; font-size: 0.78rem; font-weight: 700; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;';
                        if (p === accountsCurrentPage) {
                            pageBtn.style.background = '#8b1828';
                            pageBtn.style.color = '#ffffff';
                            pageBtn.style.borderColor = '#8b1828';
                            pageBtn.setAttribute('aria-current', 'page');
                        } else {
                            pageBtn.style.background = '#ffffff';
                            pageBtn.style.color = '#334155';
                            pageBtn.addEventListener('click', () => {
                                accountsCurrentPage = p;
                                filterSettingsAccounts();
                            });
                        }
                        pageNumbersContainer.appendChild(pageBtn);
                    }
                }
            }
        }
    }

    function changeAccountsPage(delta) {
        accountsCurrentPage += delta;
        filterSettingsAccounts();
    }

    function selectAccountsTab(tab) {
        accountsCurrentPage = 1;
        activeAccountsTab = tab.dataset.accountsTab;
        document.querySelectorAll('[data-accounts-tab]').forEach(button => {
            const selected = button === tab;
            button.setAttribute('aria-selected', String(selected));
            button.tabIndex = selected ? 0 : -1;
        });
        document.getElementById('accountsPanel').setAttribute('aria-labelledby', tab.id);
        document.getElementById('newOrganizationAccountCard').hidden = activeAccountsTab !== 'organization';
        document.getElementById('accountsTableTitle').textContent = activeAccountsTab === 'organization' ? 'Authorized Accounts' : 'Office Accounts';
        document.getElementById('accountsTableDescription').textContent = activeAccountsTab === 'organization'
            ? 'Officer names and their assigned organizations. Disabled accounts remain visible.'
            : 'OSO, SDO, OVCAA, and OC accounts. Disabled accounts remain visible.';
        const colName = document.getElementById('colHeaderName');
        if (colName) colName.textContent = activeAccountsTab === 'organization' ? 'ORGANIZATION NAME' : 'OFFICE ACCOUNT / NAME';
        const colDept = document.getElementById('colHeaderDept');
        if (colDept) colDept.textContent = activeAccountsTab === 'organization' ? 'COLLEGE / DEPARTMENT' : 'OFFICE / ROLE';
        filterSettingsAccounts();
    }

    document.querySelectorAll('[data-accounts-tab]').forEach(button => {
        button.addEventListener('click', () => selectAccountsTab(button));
        button.addEventListener('keydown', event => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const tabs = [...document.querySelectorAll('[data-accounts-tab]')];
            const index = tabs.indexOf(button);
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 :
                (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            tabs[next].focus();
            selectAccountsTab(tabs[next]);
        });
    });


    function showSettingsAccountError(dialog, message = '') {
        const error = dialog?.querySelector('[role="alert"]');
        if (!error) return;
        error.textContent = message;
        error.hidden = !message;
        if (message) error.focus();
    }

    function openSettingsAccountDialog(dialog, focusId) {
        if (!settingsIsOso || settingsMutationPending || !dialog) return;
        settingsAccountReturnFocus = document.activeElement;
        showSettingsAccountError(dialog);
        const toastContainer = document.getElementById('settingsToastContainer');
        if (toastContainer) dialog.appendChild(toastContainer);
        if (!dialog.open) dialog.showModal();
        document.getElementById(focusId)?.focus();
    }


    function upsertOsoUser(user) {
        const tbody = document.getElementById('settingsUserListTbody');
        if (!tbody || !user) return;
        const id = Number(user.id);
        settingsAccounts.set(id, user);
        tbody.querySelector('td[colspan]')?.closest('tr').remove();
        let row = tbody.querySelector(`[data-settings-user-id="${id}"]`);
        if (!row) {
            row = document.createElement('tr');
            row.dataset.settingsUserId = id;
            tbody.prepend(row);
        }
        row.dataset.settingsUser = JSON.stringify(user);
        const isCurrent = id === Number(settingsOfficerId);
        const isActive = Boolean(user.is_active);

        // Only fixed markup is parsed; every account-provided value uses textContent.
        row.innerHTML = '<td><div class="org-settings-user-identity"><div class="org-settings-avatar-sm"></div><div><strong data-user-name style="text-transform: uppercase;"></strong><small data-user-email></small></div></div></td><td><span data-user-college style="font-weight: 600; color: #2d3748;"></span></td><td style="text-align: center;"><div style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;"><label class="org-toggle-switch" title="' + (isActive ? 'Account Enabled' : 'Account Disabled') + '"><input type="checkbox" data-status-toggle="' + id + '"' + (isActive ? ' checked' : '') + (isCurrent ? ' disabled' : '') + '><span class="org-toggle-slider"></span></label><span class="org-status-chip ' + (isActive ? 'is-active' : 'is-inactive') + '" data-status-chip="' + id + '">' + (isActive ? 'Enable' : 'Disable') + '</span></div><small data-user-password-state style="display: block; font-size: 0.7rem; color: #dc2626; margin-top: 0.2rem;"></small></td><td data-user-updated></td><td style="white-space: nowrap;"><div class="org-settings-user-actions"></div></td>';
        const orgName = user.office_role === 'so' ? (user.organization_name || user.name) : user.name;
        row.querySelector('[data-user-name]').textContent = (orgName || '').toUpperCase();
        row.querySelector('[data-user-email]').textContent = user.email;
        const avatar = row.querySelector('.org-settings-avatar-sm');
        if (user.office_role === 'so' && user.org_logo_url) {
            const image = document.createElement('img');
            image.src = user.org_logo_url;
            image.alt = orgName || '';
            image.style.cssText = 'width: 100%; height: 100%; object-fit: contain; display: block; border-radius: inherit;';
            avatar.classList.add('has-logo');
            avatar.style.overflow = 'hidden';
            avatar.style.borderRadius = '8px';
            avatar.appendChild(image);
        } else {
            avatar.textContent = user.initials;
        }
        const collegeEl = row.querySelector('[data-user-college]');
        if (collegeEl) collegeEl.textContent = user.office_role === 'so' ? (user.college || '—') : (user.role || '—');
        row.querySelector('[data-user-password-state]').textContent = user.must_change_password ? 'Password change required' : '';
        const updatedAt = user.updated_at ? new Date(user.updated_at) : null;
        row.querySelector('[data-user-updated]').textContent = updatedAt && !Number.isNaN(updatedAt.getTime()) ? updatedAt.toLocaleString() : 'Not recorded';
        // Bind toggle click listeners (only enable/disable upon user confirmation)
        const statusToggle = row.querySelector(`[data-status-toggle="${id}"]`);
        if (statusToggle) {
            statusToggle.addEventListener('click', (event) => {
                handleStatusToggleClick(event, id);
            });
        }
        const actions = row.querySelector('.org-settings-user-actions');
        const addAction = (action, label, accessibleLabel, icon, disabled = false) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'org-settings-btn-subtle org-action-icon-btn';
            button.dataset.accountAction = action;
            button.dataset.tooltip = label;
            const iconElement = document.createElement('i');
            iconElement.className = `bi ${icon}`;
            iconElement.setAttribute('aria-hidden', 'true');
            button.appendChild(iconElement);
            button.disabled = disabled;
            button.setAttribute('aria-label', action === 'own' ? accessibleLabel : `${accessibleLabel}: ${user.name}`);
            if (disabled) button.title = 'Turnover requires an enabled account with an assigned organization.';
            actions.appendChild(button);
            return button;
        };
        if (id === Number(settingsOfficerId)) {
            addAction('own', 'My Account', 'My Account', 'bi-person-circle');
        } else {
            addAction('edit', 'Edit', 'Edit profile', 'bi-pencil-square');
            addAction('reset', 'Password', 'Reset temporary password', 'bi-key-fill');
            if (user.office_role === 'so') addAction('turnover', 'Turnover', 'Turn over SO officer', 'bi-arrow-left-right', !user.is_active || !user.student_organization_id);
            if (user.office_role === 'so' && user.is_active && user.student_organization_id && user.must_change_password) {
                const retryButton = addAction('retry-email', 'Email Credentials', 'Email credentials', 'bi-envelope-at');
                retryButton.dataset.retryUrl = `${osoAccountRoutes.organizationRetryBase}/${id}/retry-email`;
                retryButton.title = 'Generate a new temporary password, replace the previous temporary password, and email login credentials';
            }
        }
        filterSettingsAccounts();
    }

    async function openAccountManagement(id, action) {
        const user = settingsAccounts.get(Number(id));
        if (!user || !settingsIsOso || settingsMutationPending) return;
        if (Number(id) === Number(settingsOfficerId)) {
            openSettingsModal('account');
            document.getElementById('setOfficerName')?.focus();
            return;
        }
        if (!['edit', 'reset', 'turnover'].includes(action)) return;
        if (action === 'turnover' && (user.office_role !== 'so' || !user.is_active || !user.student_organization_id)) {
            showSettingsToast('Turnover requires an enabled SO account with an assigned organization.', 'error');
            return;
        }
        const confirmConfig = {
            edit: {
                title: 'Edit Officer Profile?',
                message: `Are you sure you want to edit the profile for ${user.name}?`,
                accept: 'Yes, Edit Profile'
            },
            reset: {
                title: 'Reset Temporary Password?',
                message: `Are you sure you want to reset the temporary password for ${user.name}?`,
                accept: 'Yes, Reset Password'
            },
            turnover: {
                title: 'Turn Over SO Officer?',
                message: `Are you sure you want to turn over the account for ${user.name}? This will prepare the transition to an incoming officer.`,
                accept: 'Yes, Proceed'
            }
        }[action];

        if (confirmConfig) {
            if (window.OrgActionConfirmations?.confirm) {
                const confirmed = await window.OrgActionConfirmations.confirm(confirmConfig.message, {
                    title: confirmConfig.title,
                    accept: confirmConfig.accept
                });
                if (!confirmed) return;
            } else if (!window.confirm(confirmConfig.message)) {
                return;
            }
        }
        const dialog = document.getElementById('settingsAccountModal');
        dialog.querySelector('form').reset();
        settingsAccountAction = action;
        settingsAccountTargetId = Number(id);
        const isEdit = action === 'edit';
        const isReset = action === 'reset';
        const labels = { edit: 'Edit Officer Profile', reset: 'Reset Temporary Password', turnover: 'Turn Over SO Officer' };
        document.getElementById('settingsAccountTitle').textContent = labels[action];
        document.getElementById('settingsAccountSubmit').textContent = { edit: 'Save Profile', reset: 'Reset Password', turnover: 'Confirm Secure Turnover' }[action];
        document.getElementById('settingsAccountSubject').textContent = `${user.name} · ${user.organization_name || user.role}`;
        document.getElementById('settingsAccountDescription').textContent = isEdit
            ? 'Update this officer’s profile. Changing their email revokes their existing sessions and remembered login.'
            : isReset
                ? 'The old password, existing sessions, and remembered login will be revoked. A disabled account stays disabled.'
                : 'Create a separate incoming officer account for the same organization. The outgoing account will be disabled and all its sessions and remembered login revoked. Its identity, activities, ledger, AR/FR, and archive history are retained. This cannot be repeated for the disabled outgoing account.';
        document.getElementById('settingsAccountAssignment').textContent = `${user.role}${user.organization_name ? ' · ' + user.organization_name : ''}`;
        document.getElementById('settingsAccountImmutable').hidden = isReset;
        document.getElementById('settingsTurnoverEmailHelp').hidden = action !== 'turnover';
        const clearanceField = document.getElementById('accountTosaClearanceField');
        const clearanceInput = document.getElementById('accountTosaClearance');
        clearanceField.hidden = !isEdit || user.office_role !== 'ovcaa';
        clearanceInput.disabled = clearanceField.hidden;
        clearanceInput.value = user.tosa_clearance || 'No Access';
        dialog.querySelectorAll('[data-account-profile-field]').forEach(field => {
            field.hidden = isReset;
            field.querySelector('input').disabled = isReset;
        });
        dialog.querySelectorAll('[data-account-password-field]').forEach(field => {
            field.hidden = isEdit;
            field.querySelector('input').disabled = isEdit;
        });
        if (isEdit) {
            document.getElementById('accountOfficerName').value = user.name;
            document.getElementById('accountOfficerEmail').value = user.email;
            document.getElementById('accountOfficerTitle').value = user.office_title || '';
            document.getElementById('accountOfficerEmployeeId').value = user.employee_id || '';
        }
        openSettingsAccountDialog(dialog, isReset ? 'accountOfficerPassword' : 'accountOfficerName');
    }


    function handleAccountManagementSubmit(event) {
        event.preventDefault();
        const action = settingsAccountAction;
        const id = settingsAccountTargetId;
        if (!id || !['edit', 'reset', 'turnover'].includes(action)) return;
        const dialog = document.getElementById('settingsAccountModal');
        showSettingsAccountError(dialog);
        const values = {};
        if (action !== 'reset') {
            values.name = settingsInputValue('accountOfficerName');
            values.email = settingsInputValue('accountOfficerEmail');
            values.office_title = settingsInputValue('accountOfficerTitle');
            values.employee_id = settingsInputValue('accountOfficerEmployeeId');
            if (action === 'edit' && settingsAccounts.get(Number(id))?.office_role === 'ovcaa') {
                values.tosa_clearance = settingsInputValue('accountTosaClearance');
            }
        }
        if (action !== 'edit') {
            values.password = settingsInputValue('accountOfficerPassword');
            values.password_confirmation = settingsInputValue('accountOfficerPasswordConfirmation');
            if (values.password !== values.password_confirmation) {
                showSettingsAccountError(dialog, 'The temporary password and confirmation must match.');
                return;
            }
        }
        const suffix = { edit: '', reset: '/reset-password', turnover: '/turnover' }[action];
        return runSettingsMutation(async () => {
            const data = await postSettingsJson(`${osoAccountRoutes.userStatusBase}/${id}${suffix}`, values, action === 'edit' ? 'PATCH' : 'POST');
            if (action === 'turnover') upsertOsoUser(data.previous_user);
            upsertOsoUser(data.user);
            dialog.close();
            showSettingsToast(data.message || 'Officer account updated.', 'success');
        }, { edit: 'Save these officer account changes?', reset: 'Reset this officer password?', turnover: 'Turn over this officer account? Existing sessions will be revoked.' }[action]);
    }

    function handleStatusToggleClick(event, id) {
        if (event) {
            event.preventDefault(); // Stop toggle switch from changing before confirmation!
        }
        if (Number(id) === Number(settingsOfficerId)) {
            showSettingsToast('Use My Account to manage your own profile and password. You cannot change your own account status.', 'error');
            return;
        }
        const user = settingsAccounts.get(Number(id));
        if (!user || settingsMutationPending) return;

        const targetActive = !user.is_active;
        const confirmMessage = targetActive
            ? `Enable ${user.name}?`
            : `Disable ${user.name}? Existing sessions will be revoked.`;

        runSettingsMutation(async () => {
            const data = await postSettingsJson(`${osoAccountRoutes.userStatusBase}/${id}/status`, { is_active: targetActive }, 'PATCH');
            upsertOsoUser(data.user);
            showSettingsToast(data.message || 'Officer account status updated.', 'success');
        }, confirmMessage);
    }


    function toggleOsoUserStatus(id, isActive) {
        return handleStatusToggleClick(null, id);
    }


    document.querySelectorAll('[data-settings-user]').forEach(row => {
        upsertOsoUser(JSON.parse(row.dataset.settingsUser));
    });
    filterSettingsAccounts();

    document.getElementById('settingsUserListTbody')?.addEventListener('click', event => {
        const button = event.target.closest('[data-account-action]');
        if (!button || button.disabled || settingsMutationPending) return;
        const id = Number(button.closest('[data-settings-user-id]').dataset.settingsUserId);
        const action = button.dataset.accountAction;
        if (action === 'retry-email') {
            retryNewOrganizationEmail(id, button.dataset.retryUrl || '');
        } else {
            openAccountManagement(id, action);
        }
    });

    document.querySelectorAll('.org-settings-submodal').forEach(dialog => {
        dialog.addEventListener('cancel', event => {
            if (settingsMutationPending) {
                event.preventDefault();
                showSettingsToast('Please wait for the current account change to finish.', 'info');
            }
        });
        dialog.addEventListener('close', () => {
            const targetId = settingsAccountTargetId;
            dialog.querySelectorAll('input[type="password"]').forEach(input => input.value = '');
            const toastContainer = document.getElementById('settingsToastContainer');
            if (toastContainer) document.body.appendChild(toastContainer);
            showSettingsAccountError(dialog);
            settingsAccountAction = null;
            settingsAccountTargetId = null;
            const fallback = targetId ? document.querySelector(`[data-settings-user-id="${targetId}"] button:not(:disabled)`) : null;
            const focus = settingsAccountReturnFocus?.isConnected ? settingsAccountReturnFocus : fallback;
            if (focus && !focus.disabled) focus.focus();
            settingsAccountReturnFocus = null;
        });
    });
</script>
@endpush
