@php
    $isOso = ($office->office_role ?? '') === 'oso';
    $settings = $officeSettings ?? [];
    $general = $settings['general'] ?? [];
    $security = $settings['security'] ?? [];
    $settingsUsers = $officeUsers ?? collect();
    $settingsLogoUrl = !empty($general['logo_path'])
        ? asset('storage/'.$general['logo_path'])
        : asset('Orgchain logo.png');
@endphp
{{-- =========================================================================
     OrgChain Office-User Settings Hub
     Impeccable & Unslop Design System · Batangas State University
     ========================================================================= --}}
<div id="orgSettingsModal" class="org-settings-backdrop" style="display: none;" aria-hidden="true">
    <div class="org-settings-dialog" role="dialog" aria-modal="true" aria-labelledby="settingsModalTitle" tabindex="-1">
        {{-- Settings Header --}}
        <div class="org-settings-header">
            <div class="org-settings-header-left">
                <div class="org-settings-icon-badge">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>
                <div class="org-settings-title-block">
                    <div class="org-settings-title-line">
                        <h2 class="org-settings-title" id="settingsModalTitle">{{ $isOso ? 'System & Office Settings' : 'My Office Settings' }}</h2>
                    </div>
                    <p class="org-settings-subtitle">{{ $isOso ? 'Manage office accounts, institutional details, TOSA access, and data exports.' : 'Manage your office profile and account password.' }}</p>
                </div>
            </div>
            <div class="org-settings-header-right">
                <div class="org-settings-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="search" id="settingsGlobalSearch" aria-label="Search settings" placeholder="Search settings (e.g. profile, password)..." autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" oninput="filterSettingsSearch(this.value)">
                </div>
                <button type="button" class="org-settings-close-btn" onclick="closeSettingsModal()" title="Close Settings (Esc)" aria-label="Close Settings">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        {{-- Settings Main Layout (Sidebar Navigation + Content Panels) --}}
        <div class="org-settings-body">
            {{-- Left Navigation Sidebar --}}
            @if ($isOso)
            <aside class="org-settings-nav">
                <div class="org-settings-nav-group-label">CONFIGURATION</div>
                <button type="button" class="org-settings-nav-item is-active" id="setNavBtn-general" onclick="switchSettingsTab('general')">
                    <div class="org-settings-nav-icon"><i class="bi bi-sliders"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>General</strong>
                        <small>System, Office, Logo, Contact</small>
                    </div>
                </button>

                <button type="button" class="org-settings-nav-item" id="setNavBtn-account" onclick="switchSettingsTab('account')">
                    <div class="org-settings-nav-icon"><i class="bi bi-person-badge-fill"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>Account</strong>
                        <small>Profile, Password</small>
                    </div>
                </button>

                <button type="button" class="org-settings-nav-item" id="setNavBtn-security" onclick="switchSettingsTab('security')">
                    <div class="org-settings-nav-icon"><i class="bi bi-shield-lock-fill"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>Security</strong>
                        <small>TOSA PIN access</small>
                    </div>
                </button>

                <div class="org-settings-nav-group-label" style="margin-top: 0.85rem;">MANAGEMENT</div>
                <button type="button" class="org-settings-nav-item" id="setNavBtn-users" onclick="switchSettingsTab('users')">
                    <div class="org-settings-nav-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>Users &amp; Roles</strong>
                        <small>Accounts, Permissions, TOSA</small>
                    </div>
                </button>

                <button type="button" class="org-settings-nav-item" id="setNavBtn-records" onclick="switchSettingsTab('records')">
                    <div class="org-settings-nav-icon"><i class="bi bi-database-fill-gear"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>Data &amp; Records</strong>
                        <small>Snapshots, Record Exports</small>
                    </div>
                </button>

                {{-- Sidebar Footer Meta --}}
                <div class="org-settings-nav-footer">
                    <div style="font-size: 0.72rem; color: #7a7074; font-weight: 600;">Signed in as:</div>
                    <div style="font-size: 0.8rem; font-weight: 800; color: #1a1618; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $office->name ?? 'OSO Review Officer' }}</div>
                    <div style="font-size: 0.7rem; color: #8b1828; font-weight: 700;">{{ $brand['role'] ?? 'Office User' }}</div>
                </div>
            </aside>
            @endif

            {{-- Right Content Panels --}}
            <main class="org-settings-content" id="settingsContentContainer">
                <p id="settingsSearchEmpty" role="status" hidden>No settings match your search.</p>
                
                @if ($isOso)
                {{-- =============================================================
                     TAB 1: GENERAL SETTINGS
                     ============================================================= --}}
                <section class="org-settings-panel is-active" id="setPanel-general">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">General Settings</h3>
                            <p class="org-settings-panel-desc">Manage saved institutional identifiers, office branding, and contact information.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="saveSettingsSection('General')">
                            <i class="bi bi-floppy2-fill"></i> Save Changes
                        </button>
                    </div>

                    {{-- Card: System Name & Office Name --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-building-gear"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">System &amp; Office Identification</h4>
                                <p class="org-settings-card-desc">Office identification and institutional details included in configuration snapshots.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-field">
                                    <label for="setSysName" class="org-settings-label">
                                        <span>System Name</span>
                                        <span class="org-settings-required">*</span>
                                    </label>
                                    <input type="text" id="setSysName" class="org-settings-input" value="{{ $general['system_name'] ?? 'OrgChain Student Organizations Portal' }}" placeholder="e.g. OrgChain Student Portal">
                                    <small class="org-settings-help">Saved as the system identifier in configuration snapshots.</small>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setOfficeName" class="org-settings-label">
                                        <span>Office Name</span>
                                        <span class="org-settings-required">*</span>
                                    </label>
                                    <input type="text" id="setOfficeName" class="org-settings-input" value="{{ $general['office_name'] ?? 'Office of Student Organizations (OSO)' }}" placeholder="e.g. Office of Student Organizations">
                                    <small class="org-settings-help">Official office unit managing evaluation desks and proposal endorsements.</small>
                                </div>
                            </div>

                            <div class="org-settings-grid-2" style="margin-top: 1rem;">
                                <div class="org-settings-field">
                                    <label for="setUniversityName" class="org-settings-label">University / Institution</label>
                                    <input type="text" id="setUniversityName" class="org-settings-input" value="{{ $general['university_name'] ?? 'Batangas State University - The National Engineering University' }}">
                                </div>
                                <div class="org-settings-field">
                                    <label for="setCampusUnit" class="org-settings-label">Campus / Department Jurisdiction</label>
                                    <input type="text" id="setCampusUnit" class="org-settings-input" value="{{ $general['campus_unit'] ?? 'Gov. Pablo Borbon Main Campus I · Central Directorate' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Logo & Visual Assets --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-image-alt"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Institutional Logo &amp; Insignia</h4>
                                <p class="org-settings-card-desc">The saved logo is used as the office portal favicon.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-logo-preview-box">
                                <div class="org-settings-logo-img-wrap">
                                    <img src="{{ $settingsLogoUrl }}" alt="OrgChain Logo" id="settingsLogoPreview" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 24 24\' fill=\'%238b1828\'><path d=\'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5\'/></svg>'">
                                </div>
                                <div style="flex: 1;">
                                    <strong style="font-size: 0.95rem; color: #1a1618; display: block; margin-bottom: 0.2rem;">Official OrgChain Emblem &amp; Seal</strong>
                                    <p style="font-size: 0.78rem; color: #64748b; margin: 0 0 0.75rem;">PNG or JPEG, up to 2 MB. Uploads and resets are saved immediately.</p>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button type="button" class="org-settings-btn-outline" onclick="triggerLogoUpload()">
                                            <i class="bi bi-upload"></i> Upload New Logo
                                        </button>
                                        <input type="file" id="settingsLogoFileInput" style="display: none;" accept="image/png,image/jpeg,.png,.jpg,.jpeg" onchange="handleLogoChange(this)">
                                        <button type="button" class="org-settings-btn-subtle" onclick="resetLogoToDefault()">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset to Default
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Contact Information --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-envelope-at"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Contact Information &amp; Support Channels</h4>
                                <p class="org-settings-card-desc">Office contact details saved in institutional configuration snapshots.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-field">
                                    <label for="setContactEmail" class="org-settings-label">Official Office Email</label>
                                    <div class="org-settings-input-group">
                                        <span class="org-settings-input-addon"><i class="bi bi-envelope"></i></span>
                                        <input type="email" id="setContactEmail" class="org-settings-input" value="{{ $general['contact_email'] ?? 'oso.main@g.batstate-u.edu.ph' }}" placeholder="office@g.batstate-u.edu.ph">
                                    </div>
                                </div>
                                <div class="org-settings-field">
                                    <label for="setContactPhone" class="org-settings-label">Office Telephone / Extension</label>
                                    <div class="org-settings-input-group">
                                        <span class="org-settings-input-addon"><i class="bi bi-telephone"></i></span>
                                        <input type="text" id="setContactPhone" class="org-settings-input" value="{{ $general['contact_phone'] ?? '(043) 980-0385 loc. 1144' }}" placeholder="(043) 980-0385 loc. 1144">
                                    </div>
                                </div>
                            </div>
                            <div class="org-settings-field" style="margin-top: 1rem;">
                                <label for="setContactLocation" class="org-settings-label">Physical Office Location</label>
                                <div class="org-settings-input-group">
                                    <span class="org-settings-input-addon"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" id="setContactLocation" class="org-settings-input" value="{{ $general['contact_location'] ?? '3rd Floor, Student Services Center, Gov. Pablo Borbon Main Campus I, Batangas City' }}" placeholder="Building, Room, Campus">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @endif

                {{-- =============================================================
                     TAB 2: ACCOUNT SETTINGS
                     ============================================================= --}}
                <section class="org-settings-panel" id="setPanel-account">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">Account Settings</h3>
                            <p class="org-settings-panel-desc">Manage your authorized office profile, password, and personal account credentials.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="saveOsoAccount()">
                            <i class="bi bi-floppy2-fill"></i> Save Profile
                        </button>
                    </div>

                    {{-- Card: Profile Information --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-person-bounding-box"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Profile Information</h4>
                                <p class="org-settings-card-desc">Your office account identity and institutional designation.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-profile-head">
                                <div class="org-settings-avatar-big">
                                    <span id="settingsProfileInitials">{{ $office->initials() ?? 'OSO' }}</span>
                                </div>
                                <div>
                                    <h4 id="settingsProfileName" style="margin: 0 0 0.15rem; font-size: 1.1rem; color: #1a1618;">{{ $office->name ?? 'Office Review Officer' }}</h4>
                                    <span style="font-size: 0.8rem; color: #8b1828; font-weight: 700; background: #fdf0f2; padding: 0.2rem 0.55rem; border-radius: 6px; display: inline-block;">
                                        <i class="bi bi-patch-check-fill"></i> Office Account
                                    </span>
                                    <div style="margin-top: 0.5rem; font-size: 0.76rem; color: #64748b;">
                                        User ID: <strong>BSU-OFFICE-{{ $office->id ?? '—' }}</strong> • Authorized Role: <strong>{{ $brand['role'] ?? 'Head Administrator' }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="org-settings-grid-2" style="margin-top: 1.25rem;">
                                <div class="org-settings-field">
                                    <label for="setOfficerName" class="org-settings-label">Full Name &amp; Title</label>
                                    <input type="text" id="setOfficerName" class="org-settings-input" value="{{ $office->name ?? 'Dr. Rosalinda M. Comia' }}">
                                </div>
                                <div class="org-settings-field">
                                    <label for="setOfficerDesignation" class="org-settings-label">Official Designation</label>
                                    <input type="text" id="setOfficerDesignation" class="org-settings-input" value="{{ $office->office_title ?? 'Head, Office of Student Organizations' }}">
                                </div>
                                <div class="org-settings-field">
                                    <label for="setOfficerEmail" class="org-settings-label">Official Account Email</label>
                                    <input type="email" id="setOfficerEmail" class="org-settings-input" value="{{ $office->email ?? 'oso.lead@g.batstate-u.edu.ph' }}">
                                </div>
                                <div class="org-settings-field">
                                    <label for="setOfficerEmployeeId" class="org-settings-label">Employee / Faculty ID</label>
                                    <input type="text" id="setOfficerEmployeeId" class="org-settings-input" value="{{ $office->employee_id ?? '' }}" placeholder="e.g. BSU-EMP-2024-8891">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Change Password --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-key-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Change Password</h4>
                                <p class="org-settings-card-desc">Ensure your account uses a strong password with letters, numbers, and symbols.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <form onsubmit="handlePasswordUpdate(event)" style="display: grid; gap: 1rem;">
                                <div class="org-settings-grid-3">
                                    <div class="org-settings-field">
                                        <label for="setCurrentPassword" class="org-settings-label">Current Password</label>
                                        <div class="org-settings-input-group">
                                            <input type="password" id="setCurrentPassword" class="org-settings-input" placeholder="••••••••••••">
                                            <button type="button" class="org-settings-pw-toggle" onclick="togglePwVisibility('setCurrentPassword', this)"><i class="bi bi-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="org-settings-field">
                                        <label for="setNewPassword" class="org-settings-label">New Password</label>
                                        <div class="org-settings-input-group">
                                            <input type="password" id="setNewPassword" class="org-settings-input" placeholder="••••••••••••" oninput="checkPasswordStrength(this.value)">
                                            <button type="button" class="org-settings-pw-toggle" onclick="togglePwVisibility('setNewPassword', this)"><i class="bi bi-eye"></i></button>
                                        </div>
                                        <div class="org-settings-pw-meter" id="pwStrengthMeter">
                                            <div class="org-settings-pw-bar" id="pwStrengthBar"></div>
                                        </div>
                                    </div>
                                    <div class="org-settings-field">
                                        <label for="setConfirmPassword" class="org-settings-label">Confirm New Password</label>
                                        <div class="org-settings-input-group">
                                            <input type="password" id="setConfirmPassword" class="org-settings-input" placeholder="••••••••••••">
                                            <button type="button" class="org-settings-pw-toggle" onclick="togglePwVisibility('setConfirmPassword', this)"><i class="bi bi-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <button type="submit" class="org-settings-btn-outline">
                                        <i class="bi bi-shield-lock"></i> Update Account Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </section>

                @if ($isOso)
                {{-- TOSA access controls --}}
                <section class="org-settings-panel" id="setPanel-security">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">TOSA Access Controls</h3>
                            <p class="org-settings-panel-desc">Manage the TOSA PIN gateway and its timed re-lock.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="saveSettingsSection('Security')">
                            <i class="bi bi-floppy2-fill"></i> Save Changes
                        </button>
                    </div>

                    {{-- Card: TOSA Module Access & TOSA PIN --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon" style="background: #fdf0f2; color: #8b1828;"><i class="bi bi-award-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">TOSA Module Access &amp; TOSA PIN</h4>
                                <p class="org-settings-card-desc">Restricted governance controls for the Ten Outstanding Students Awards executive module.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Require a TOSA PIN</strong>
                                    <p class="org-settings-toggle-desc">Require 4-digit security PIN unlock before displaying TOSA candidate dossiers.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" id="setTosaGateToggle" aria-label="Require a TOSA PIN" @checked($security['tosa_gate'] ?? true) onchange="toggleSettingState('TOSA PIN requirement', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-grid-2" style="margin-top: 1rem;">
                                <div class="org-settings-field">
                                    <label for="setTosaPinField" class="org-settings-label">New TOSA Access PIN</label>
                                    <div class="org-settings-input-group">
                                        <input type="password" id="setTosaPinField" class="org-settings-input" value="" inputmode="numeric" maxlength="4" placeholder="Enter new PIN" style="letter-spacing: 3px; font-weight: 800;">
                                        <button type="button" class="org-settings-pw-toggle" onclick="togglePwVisibility('setTosaPinField', this)"><i class="bi bi-eye"></i></button>
                                    </div>
                                    <small class="org-settings-help" id="settingsTosaPinStatus">
                                        @if (!empty($security['tosa_pin_configured']))
                                            Current PIN is configured and stored as a one-way hash. Enter a new 4-digit PIN to replace it.
                                        @else
                                            No PIN is configured yet. Configure one before enabling PIN-protected TOSA access.
                                        @endif
                                    </small>
                                    <button type="button" class="org-settings-btn-outline" style="margin-top: 0.6rem;" onclick="saveOsoPin('tosa')"><i class="bi bi-shield-lock"></i> Save TOSA PIN</button>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setSessionTimeout" class="org-settings-label">TOSA PIN Unlock Duration</label>
                                    <select id="setSessionTimeout" class="org-settings-select">
                                        <option value="15" @selected((int) ($security['session_timeout'] ?? 15) === 15)>15 Minutes</option>
                                        <option value="30" @selected((int) ($security['session_timeout'] ?? 15) === 30)>30 Minutes</option>
                                        <option value="60" @selected((int) ($security['session_timeout'] ?? 15) === 60)>1 Hour</option>
                                        <option value="120" @selected((int) ($security['session_timeout'] ?? 15) === 120)>2 Hours</option>
                                        <option value="0" @selected((int) ($security['session_timeout'] ?? 15) === 0)>No timed re-lock</option>
                                    </select>
                                    <small class="org-settings-help">Re-locks the TOSA view when the duration expires. This does not sign you out.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @endif

                {{-- =============================================================
                     TAB 5: USERS & ROLES
                     ============================================================= --}}
                @if ($isOso)
                <section class="org-settings-panel" id="setPanel-users">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">Users &amp; Role Permissions</h3>
                            <p class="org-settings-panel-desc">Manage office and student organization accounts, credentials, and secure officer turnover.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="openAddUserModal()">
                            <i class="bi bi-person-plus-fill"></i> Add Officer Account
                        </button>
                    </div>

                    {{-- User Accounts Table --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-person-lines-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Authorized Office Accounts</h4>
                                <p class="org-settings-card-desc">Disabled accounts remain visible. Use My Account for your own profile and password.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body" style="padding: 0;">
                            <div style="padding: 1rem;">
                                <label for="settingsAccountSearch" class="org-settings-label">Search accounts</label>
                                <input type="search" id="settingsAccountSearch" class="org-settings-input" placeholder="Name, university email, organization, or role" autocomplete="off" oninput="filterSettingsAccounts(this.value)">
                                <p id="settingsAccountSearchEmpty" class="org-settings-help" role="status" hidden>No accounts match your search.</p>
                            </div>
                            <div class="org-settings-table-wrap">
                                <table class="org-settings-table">
                                    <thead>
                                        <tr>
                                            <th>Officer Name</th>
                                            <th>Role / Office</th>
                                            <th>TOSA Clearance</th>
                                            <th>Account Status</th>
                                            <th>Last Updated</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="settingsUserListTbody">
                                        @forelse ($settingsUsers as $user)
                                            @php
                                                $userData = [
                                                    'id' => $user->id,
                                                    'name' => $user->name,
                                                    'email' => $user->email,
                                                    'office_role' => $user->office_role,
                                                    'role' => $user->roleLabel(),
                                                    'office_title' => $user->office_title,
                                                    'employee_id' => $user->employee_id,
                                                    'student_organization_id' => $user->student_organization_id,
                                                    'organization_name' => $user->studentOrganization?->name,
                                                    'initials' => $user->initials(),
                                                    'updated_at' => $user->updated_at?->toIso8601String(),
                                                    'tosa_clearance' => $user->effectiveTosaClearance(),
                                                    'is_active' => (bool) $user->is_active,
                                                    'must_change_password' => (bool) $user->must_change_password,
                                                ];
                                                $isCurrentAccount = (int) $user->id === (int) $office->id;
                                            @endphp
                                            <tr data-settings-user-id="{{ $user->id }}" data-settings-user="{{ json_encode($userData) }}">
                                                <td>
                                                    <div class="org-settings-user-identity">
                                                        <div class="org-settings-avatar-sm">{{ $userData['initials'] }}</div>
                                                        <div>
                                                            <strong data-user-name>{{ $user->name }}</strong>
                                                            <small data-user-email>{{ $user->email }}</small>
                                                            <small data-user-title>{{ $user->office_title }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="org-settings-role-badge">{{ $userData['role'] }}</span>
                                                    <small data-user-organization>{{ $userData['organization_name'] ?? ($user->office_role === 'so' ? 'No organization assigned' : '') }}</small>
                                                </td>
                                                <td><span data-user-clearance class="{{ $userData['tosa_clearance'] === 'No Access' ? 'org-settings-pill-gray' : 'org-settings-pill-green' }}">{{ $userData['tosa_clearance'] }}</span></td>
                                                <td>
                                                    <span data-user-status class="{{ $user->is_active ? 'org-settings-status-active' : 'org-settings-pill-gray' }}">{{ $user->is_active ? 'Enabled' : 'Disabled' }}</span>
                                                    <small data-user-password-state>{{ $userData['must_change_password'] ? 'Password change required' : '' }}</small>
                                                </td>
                                                <td data-user-updated>{{ $user->updated_at?->diffForHumans() ?? 'Not recorded' }}</td>
                                                <td>
                                                    <div class="org-settings-user-actions">
                                                        @if ($isCurrentAccount)
                                                            <button type="button" class="org-settings-btn-subtle" data-account-action="own">My Account</button>
                                                        @else
                                                            <button type="button" class="org-settings-btn-subtle" data-account-action="edit">Edit profile</button>
                                                            <button type="button" class="org-settings-btn-subtle" data-account-action="reset">Reset temporary password</button>
                                                            @if ($user->office_role === 'so')
                                                                <button type="button" class="org-settings-btn-subtle" data-account-action="turnover" @disabled(!$user->is_active || !$user->student_organization_id) title="{{ !$user->is_active || !$user->student_organization_id ? 'Turnover requires an enabled account with an assigned organization' : 'Replace this officer with a separate account' }}">Turn over SO officer</button>
                                                            @endif
                                                            <button type="button" class="org-settings-btn-subtle" data-account-action="status">{{ $user->is_active ? 'Disable' : 'Enable' }}</button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" style="padding: 1.5rem; text-align: center; color: #64748b;">No office accounts have been configured yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Roles & Permissions Matrix & TOSA Authorized Users --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-shield-shaded"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Role &amp; TOSA Access Guide</h4>
                                <p class="org-settings-card-desc">Account roles determine office access. TOSA clearance does not grant additional proposal or budget permissions.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-perm-grid">
                                <div class="org-settings-perm-card">
                                    <strong>OSO Office Accounts</strong>
                                    <ul>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Office settings and account management</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> TOSA access depends on assigned clearance</li>
                                        <li><i class="bi bi-info-circle"></i> No Access blocks TOSA access</li>
                                    </ul>
                                </div>
                                <div class="org-settings-perm-card">
                                    <strong>TOSA Clearance</strong>
                                    <ul>
                                        <li><i class="bi bi-eye"></i> Level 1: read-only TOSA access</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Level 2: applicant evaluation</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Level 3: OSO evaluation and template management</li>
                                        <li><i class="bi bi-info-circle"></i> OVCAA supports levels 1–2; SO, SDO, and OC have no TOSA access</li>
                                        <li><i class="bi bi-info-circle"></i> SO officers access only their assigned student organization. Turnover preserves its records and the outgoing officer's identity.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @endif

                {{-- =============================================================
                     TAB 7: DATA & RECORDS
                     ============================================================= --}}
                @if ($isOso)
                <section class="org-settings-panel" id="setPanel-records">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">Data &amp; Records</h3>
                            <p class="org-settings-panel-desc">Download configuration snapshots, organization records, and activity logs.</p>
                        </div>
                    </div>

                    {{-- Backup & Snapshot Management --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Configuration Snapshot</h4>
                                <p class="org-settings-card-desc">Download a JSON snapshot with a SHA-256 checksum of OSO configuration, office accounts, and record counts. It is not a digital signature or audit event log.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; background: #fdfafb; border: 1.5px solid #f2dfe2; border-radius: 12px; padding: 1rem 1.25rem;">
                                <div>
                                    <strong style="font-size: 0.92rem; color: #1a1618; display: block;">Create a Current Snapshot</strong>
                                    <span style="font-size: 0.78rem; color: #64748b;">Generated on request; this is not a full database or file backup.</span>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="org-settings-save-btn" onclick="triggerManualBackup()">
                                        <i class="bi bi-database-fill-up"></i> Create Snapshot Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Export Data & Activity Logs --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Export Records</h4>
                                <p class="org-settings-card-desc">Download the available organization, activity, and TOSA records.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-export-box">
                                    <i class="bi bi-file-earmark-excel text-success" style="font-size: 1.75rem;"></i>
                                    <div>
                                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Organization Registry</strong>
                                        <span style="font-size: 0.74rem; color: #64748b;">Student organization roster and active status (.CSV)</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" onclick="exportDataPackage('Organization Roster')">Export</button>
                                </div>

                                <div class="org-settings-export-box">
                                    <i class="bi bi-file-earmark-spreadsheet text-danger" style="font-size: 1.75rem;"></i>
                                    <div>
                                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Activity &amp; Budget Summary</strong>
                                        <span style="font-size: 0.74rem; color: #64748b;">Activity dates, budgets, and workflow status (.CSV)</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" onclick="exportDataPackage('Accomplishment Dossier')">Export</button>
                                </div>

                                <div class="org-settings-export-box">
                                    <i class="bi bi-award text-primary" style="font-size: 1.75rem;"></i>
                                    <div>
                                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">TOSA Applicant Manifest</strong>
                                        <span style="font-size: 0.74rem; color: #64748b;">Applicants, stage, and requirement counts (.CSV). Requires TOSA clearance; unlock TOSA first when PIN protection is enabled.</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" title="Requires TOSA clearance and an unlocked TOSA session when PIN protection is enabled" onclick="exportDataPackage('TOSA Manifest')">Export</button>
                                </div>

                                <div class="org-settings-export-box">
                                    <i class="bi bi-journal-code" style="font-size: 1.75rem; color: #8b1828;"></i>
                                    <div>
                                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Office Account &amp; Configuration Snapshot</strong>
                                        <span style="font-size: 0.74rem; color: #64748b;">Same configuration snapshot with checksum (.JSON), not event logs</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" onclick="exportDataPackage('Office Snapshot')">Export</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @endif

            </main>
        </div>

        {{-- Settings Footer Actions --}}
        <div class="org-settings-footer">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #64748b;">
                <i class="bi bi-info-circle"></i>
                <span>{{ $isOso ? 'Save All saves General, Profile, and TOSA controls only. Passwords, PINs, logos, and user actions save separately.' : 'Profile changes and passwords use their own save buttons.' }}</span>
            </div>
            <div style="display: flex; gap: 0.6rem;">
                <button type="button" class="org-settings-btn-subtle" onclick="closeSettingsModal()">Close</button>
                @if ($isOso)
                <button type="button" class="org-settings-save-btn" onclick="saveAllSettings()">
                    <i class="bi bi-check-circle-fill"></i> Save All Settings
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Add User Sub-Modal --}}
<dialog id="settingsAddUserModal" class="org-settings-submodal" aria-labelledby="settingsAddUserTitle" style="max-width: 480px;">
    <div style="padding: 1.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #fdf0f2; color: #8b1828; display: grid; place-items: center; font-size: 1.1rem;">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h4 id="settingsAddUserTitle" style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #1a1618;">Create Officer Account</h4>
            </div>
            <button type="button" aria-label="Close account form" onclick="document.getElementById('settingsAddUserModal').close()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        <form id="settingsAddUserForm" onsubmit="handleAddUserSubmit(event)" class="org-settings-account-form" autocomplete="off">
            <p id="settingsAddUserError" class="org-settings-form-error" role="alert" tabindex="-1" hidden></p>
            <p class="org-settings-help">Choose a temporary password and share it securely with the officer. No invitation email is sent. The officer must change it on first login before accessing any system actions.</p>
            <div class="org-settings-field">
                <label for="newOfficerName" class="org-settings-label">Officer Full Name</label>
                <input type="text" id="newOfficerName" class="org-settings-input" required maxlength="255" autocomplete="name" placeholder="Officer's full name">
            </div>
            <div class="org-settings-field">
                <label for="newOfficerEmail" class="org-settings-label">Official University Email</label>
                <input type="email" id="newOfficerEmail" class="org-settings-input" required maxlength="255" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="e.g. juan.delacruz@g.batstate-u.edu.ph">
            </div>
            <div class="org-settings-field">
                <label for="newOfficerPassword" class="org-settings-label">Temporary Password</label>
                <input type="password" id="newOfficerPassword" class="org-settings-input" required minlength="8" autocomplete="new-password" autocapitalize="none" spellcheck="false" placeholder="At least 8 characters">
            </div>
            <div class="org-settings-field">
                <label for="newOfficerPasswordConfirmation" class="org-settings-label">Confirm Temporary Password</label>
                <input type="password" id="newOfficerPasswordConfirmation" class="org-settings-input" required minlength="8" autocomplete="new-password" autocapitalize="none" spellcheck="false" placeholder="Repeat the temporary password">
            </div>
            <div class="org-settings-field">
                <label for="newOfficerRole" class="org-settings-label">Assigned Desk / Role</label>
                <select id="newOfficerRole" class="org-settings-select" onchange="updateNewOfficerClearance()">
                    <option value="so">SO Student Organization Officer</option>
                    <option value="oso">OSO Review Officer (Triage)</option>
                    <option value="sdo">SDO Document Reviewer</option>
                    <option value="ovcaa">OVCAA Final Endorser</option>
                    <option value="oc">OC Final Approval Officer</option>
                </select>
            </div>
            <div id="newOfficerOrganizationField" class="org-settings-field" hidden>
                <label for="newOfficerOrganization" class="org-settings-label">Registered Student Organization</label>
                <select id="newOfficerOrganization" class="org-settings-select" disabled>
                    <option value="">Select an organization</option>
                    @foreach (($accountOrganizations ?? collect()) as $organization)
                        <option value="{{ $organization->id }}">{{ $organization->name }}{{ $organization->short_name ? ' ('.$organization->short_name.')' : '' }}</option>
                    @endforeach
                </select>
                <small class="org-settings-help">Required for SO officers. This account receives access only to this organization, with no TOSA access.</small>
            </div>
            <div class="org-settings-field">
                <label for="newOfficerTitle" class="org-settings-label">Office / Officer Title (optional)</label>
                <input type="text" id="newOfficerTitle" class="org-settings-input" maxlength="255">
            </div>
            <div class="org-settings-field">
                <label for="newOfficerEmployeeId" class="org-settings-label">Employee ID (optional)</label>
                <input type="text" id="newOfficerEmployeeId" class="org-settings-input" maxlength="255">
            </div>
            <div id="newOfficerClearanceField" class="org-settings-field">
                <label for="newOfficerTosaClearance" class="org-settings-label">TOSA Module Clearance</label>
                <select id="newOfficerTosaClearance" class="org-settings-select">
                    <option value="No Access">No Access</option>
                    <option value="Level 1 Read-only">Level 1 Read-only</option>
                    <option value="Level 2 Evaluator">Level 2 Evaluator</option>
                    <option value="Level 3 Master">Level 3 Master</option>
                </select>
                <small class="org-settings-help">TOSA access is limited to OSO and OVCAA accounts. No invitation email is sent.</small>
            </div>
            <div class="org-settings-dialog-actions">
                <button type="button" class="org-settings-btn-subtle" onclick="document.getElementById('settingsAddUserModal').close()">Cancel</button>
                <button type="submit" class="org-settings-save-btn">Create Officer Account</button>
            </div>
        </form>
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
        <form id="settingsAccountForm" class="org-settings-account-form" autocomplete="off" onsubmit="handleAccountManagementSubmit(event)">
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

{{-- Settings Toast Notification Container --}}
<div id="settingsToastContainer" class="org-settings-toast-container" role="status" aria-live="polite" aria-atomic="false"></div>

{{-- =========================================================================
     CSS Styling for Settings Component (Impeccable Design System)
     ========================================================================= --}}
<style>
/* Settings Overlay & Backdrop */
.org-settings-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 12, 14, 0.65);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    box-sizing: border-box;
    animation: settingsBackdropFade 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes settingsBackdropFade {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Dialog Shell */
.org-settings-dialog {
    background: #ffffff;
    border-radius: 24px;
    border: 1.5px solid #f0e6e8;
    box-shadow: 0 24px 64px rgba(90, 15, 30, 0.18), 0 4px 16px rgba(0, 0, 0, 0.04);
    width: 100%;
    max-width: 1080px;
    min-width: 0;
    box-sizing: border-box;
    height: 88vh;
    max-height: 820px;
    max-height: min(820px, calc(100dvh - 3rem));
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: settingsDialogSlide 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes settingsDialogSlide {
    from { opacity: 0; transform: translateY(16px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Header */
.org-settings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.75rem;
    border-bottom: 1.5px solid #f0e6e8;
    background: #ffffff;
    gap: 1.25rem;
    flex-wrap: nowrap;
}

.org-settings-header-left {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex: 1;
    min-width: 0;
}

.org-settings-title-block {
    flex: 1;
    min-width: 0;
}

.org-settings-title-line {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.org-settings-icon-badge {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #8b1828, #62101c);
    color: #ffffff;
    display: grid;
    place-items: center;
    font-size: 1.35rem;
    box-shadow: 0 4px 12px rgba(139, 24, 40, 0.25);
    flex-shrink: 0;
}

.org-settings-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    color: #1a1618;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.org-settings-subtitle {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: #64748b;
    line-height: 1.35;
}

.org-settings-header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.org-settings-search-wrap {
    position: relative;
    width: 260px;
    min-width: 0;
}

.org-settings-search-wrap i {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.85rem;
}

.org-settings-search-wrap input {
    width: 100%;
    padding: 0.5rem 0.85rem 0.5rem 2.2rem;
    border-radius: 9999px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    font-size: 0.82rem;
    color: #1e293b;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.org-settings-search-wrap input:focus {
    background: #ffffff;
    border-color: #8b1828;
    box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.1);
}

.org-settings-close-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1.5px solid #f0e6e8;
    background: #ffffff;
    color: #64748b;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.org-settings-close-btn:hover {
    background: #fdf0f2;
    color: #8b1828;
    border-color: #f2dfe2;
    transform: scale(1.05);
}

/* Settings Body (Sidebar + Content) */
.org-settings-body {
    display: flex;
    flex: 1;
    min-height: 0;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
}

/* Nav Sidebar */
.org-settings-nav {
    width: 240px;
    background: #fdfafb;
    border-right: 1.5px solid #f0e6e8;
    padding: 1.25rem 0.85rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    flex-shrink: 0;
}

.org-settings-nav-group-label {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #94a3b8;
    padding: 0.4rem 0.65rem 0.2rem;
}

.org-settings-nav-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.75rem;
    border-radius: 12px;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
    box-sizing: border-box;
}

.org-settings-nav-item:hover {
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.org-settings-nav-item.is-active {
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(139, 24, 40, 0.08);
    border: 1px solid #f2dfe2;
}

.org-settings-nav-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f1f5f9;
    color: #64748b;
    display: grid;
    place-items: center;
    font-size: 0.95rem;
    flex-shrink: 0;
    transition: all 0.15s ease;
}

.org-settings-nav-item.is-active .org-settings-nav-icon {
    background: #fdf0f2;
    color: #8b1828;
}

.org-settings-nav-text strong {
    display: block;
    font-size: 0.84rem;
    color: #1e293b;
    font-weight: 700;
    line-height: 1.25;
}

.org-settings-nav-item.is-active .org-settings-nav-text strong {
    color: #8b1828;
}

.org-settings-nav-text small {
    display: block;
    font-size: 0.72rem;
    color: #94a3b8;
    margin-top: 0.1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 135px;
}

.org-settings-nav-footer {
    margin-top: auto;
    padding: 0.85rem 0.65rem 0.25rem;
    border-top: 1px solid #f0e6e8;
}

/* Content Area */
.org-settings-content {
    flex: 1;
    min-width: 0;
    min-height: 0;
    max-width: 100%;
    box-sizing: border-box;
    padding: 1.75rem 2rem;
    overflow-y: auto;
    background: #ffffff;
}

.org-settings-panel {
    min-width: 0;
    max-width: 100%;
    display: none;
    flex-direction: column;
    gap: 1.25rem;
    animation: settingsPanelFade 0.2s ease;
}

.org-settings-panel.is-active {
    display: flex;
}

@keyframes settingsPanelFade {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.org-settings-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 0.75rem;
    border-bottom: 1.5px solid #f1f5f9;
    gap: 1rem;
    flex-wrap: wrap;
}

.org-settings-panel-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 800;
    color: #1a1618;
}

.org-settings-panel-desc {
    margin: 0.2rem 0 0;
    font-size: 0.84rem;
    color: #64748b;
}

/* Card */
.org-settings-card {
    min-width: 0;
    max-width: 100%;
    box-sizing: border-box;
    background: #ffffff;
    border-radius: 16px;
    border: 1.5px solid #f0e6e8;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(90, 15, 30, 0.02);
}

.org-settings-card-header {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 1rem 1.25rem;
    background: #fdfafb;
    border-bottom: 1.5px solid #f0e6e8;
}

.org-settings-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #fdf0f2;
    color: #8b1828;
    display: grid;
    place-items: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.org-settings-card-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 800;
    color: #1a1618;
}

.org-settings-card-desc {
    margin: 0.15rem 0 0;
    font-size: 0.78rem;
    color: #64748b;
}

.org-settings-card-body {
    min-width: 0;
    max-width: 100%;
    box-sizing: border-box;
    padding: 1.25rem;
}

/* Form Controls */
.org-settings-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.org-settings-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.org-settings-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.org-settings-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.org-settings-required {
    color: #dc2626;
}

.org-settings-input, .org-settings-select {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    font-size: 0.85rem;
    color: #1e293b;
    font-family: inherit;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.org-settings-input:focus, .org-settings-select:focus {
    border-color: #8b1828;
    box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.1);
}

.org-settings-help {
    font-size: 0.72rem;
    color: #94a3b8;
}

.org-settings-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.org-settings-input-addon {
    position: absolute;
    left: 0.75rem;
    color: #94a3b8;
    font-size: 0.85rem;
    pointer-events: none;
}

.org-settings-input-group .org-settings-input {
    padding-left: 2.2rem;
}

.org-settings-pw-toggle {
    position: absolute;
    right: 0.75rem;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.9rem;
}

.org-settings-pw-meter {
    height: 4px;
    background: #e2e8f0;
    border-radius: 9999px;
    overflow: hidden;
    margin-top: 0.35rem;
}

.org-settings-pw-bar {
    height: 100%;
    width: 0%;
    background: #ef4444;
    transition: width 0.3s ease, background 0.3s ease;
}

/* Switch Toggle Row */
.org-settings-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 0;
    border-bottom: 1px solid #f1f5f9;
    gap: 1rem;
}

.org-settings-toggle-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.org-settings-toggle-title {
    font-size: 0.86rem;
    color: #1a1618;
    font-weight: 700;
    display: block;
}

.org-settings-toggle-desc {
    margin: 0.15rem 0 0;
    font-size: 0.76rem;
    color: #64748b;
}

/* Switch Toggle */
.org-settings-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.org-settings-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.org-settings-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: #cbd5e1;
    transition: .3s;
    border-radius: 9999px;
}

.org-settings-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

.org-settings-switch input:checked + .org-settings-slider {
    background-color: #8b1828;
}

.org-settings-switch input:checked + .org-settings-slider:before {
    transform: translateX(20px);
}

/* Buttons */
.org-settings-save-btn {
    padding: 0.6rem 1.25rem;
    border-radius: 9999px;
    border: none;
    background: #8b1828;
    color: #ffffff;
    font-size: 0.84rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    box-shadow: 0 4px 14px rgba(139, 24, 40, 0.25);
    transition: all 0.15s ease;
    font-family: inherit;
}

.org-settings-save-btn:hover {
    background: #62101c;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(139, 24, 40, 0.35);
}

.org-settings-btn-outline {
    padding: 0.55rem 1rem;
    border-radius: 9999px;
    border: 1.5px solid #8b1828;
    background: #ffffff;
    color: #8b1828;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.15s ease;
    font-family: inherit;
}

.org-settings-btn-outline:hover {
    background: #fdf0f2;
}

.org-settings-btn-subtle {
    padding: 0.55rem 1rem;
    border-radius: 9999px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.15s ease;
    font-family: inherit;
}

.org-settings-btn-subtle:hover {
    background: #f8fafc;
    color: #1e293b;
    border-color: #cbd5e1;
}

/* Logo Preview */
.org-settings-logo-preview-box {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex-wrap: wrap;
}

.org-settings-logo-img-wrap {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    border: 1.5px solid #f0e6e8;
    background: #fdfafb;
    display: grid;
    place-items: center;
    padding: 0.5rem;
    box-shadow: 0 4px 12px rgba(90, 15, 30, 0.04);
}

.org-settings-logo-img-wrap img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Profile Head */
.org-settings-profile-head {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.org-settings-avatar-big {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8b1828, #62101c);
    color: #ffffff;
    display: grid;
    place-items: center;
    font-size: 1.4rem;
    font-weight: 800;
    box-shadow: 0 4px 16px rgba(139, 24, 40, 0.3);
}

.org-settings-avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #fdf0f2;
    color: #8b1828;
    display: grid;
    place-items: center;
    font-size: 0.78rem;
    font-weight: 800;
}

/* Table */
.org-settings-table-wrap {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    contain: inline-size;
    overflow-x: auto;
}

.org-settings-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
}

.org-settings-table th {
    background: #f8fafc;
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 700;
    color: #64748b;
    border-bottom: 1.5px solid #f0e6e8;
}

.org-settings-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.org-settings-table tr:hover td {
    background: #fdfafb;
}

.org-settings-role-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    display: inline-block;
}

.org-settings-role-badge.is-admin { background: #fdf0f2; color: #8b1828; }
.org-settings-role-badge.is-eval { background: #e0f2fe; color: #0284c7; }
.org-settings-role-badge.is-sdo { background: #fef3c7; color: #b45309; }
.org-settings-role-badge.is-ovcaa { background: #f3e8ff; color: #7e22ce; }
.org-settings-role-badge.is-oc { background: #ede9fe; color: #6d28d9; }
.org-settings-role-badge.is-so { background: #e0f2fe; color: #075985; }

.org-settings-pill-green {
    font-size: 0.72rem;
    font-weight: 700;
    color: #16a34a;
    background: #dcfce7;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.org-settings-pill-gray {
    font-size: 0.72rem;
    font-weight: 600;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    display: inline-block;
}

.org-settings-status-active {
    color: #16a34a;
    font-weight: 700;
    font-size: 0.76rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.org-settings-icon-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    display: inline-grid;
    place-items: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.org-settings-icon-btn:hover {
    background: #fdf0f2;
    color: #8b1828;
    border-color: #f2dfe2;
}

.org-settings-icon-btn:disabled,
.org-settings-dialog button:disabled,
.org-settings-submodal button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.org-settings-card[hidden], #settingsSearchEmpty[hidden] {
    display: none !important;
}

/* Perm grid */
.org-settings-perm-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.org-settings-perm-card {
    background: #fdfafb;
    border: 1.5px solid #f0e6e8;
    border-radius: 12px;
    padding: 1rem;
}

.org-settings-perm-card strong {
    font-size: 0.88rem;
    color: #1a1618;
    display: block;
    margin-bottom: 0.5rem;
}

.org-settings-perm-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 0.4rem;
    font-size: 0.78rem;
    color: #475569;
}

.org-settings-perm-card ul li {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

/* Export boxes */
.org-settings-export-box {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    background: #fdfafb;
    border: 1.5px solid #f0e6e8;
    border-radius: 12px;
    padding: 0.85rem 1rem;
}

.org-settings-export-box > div {
    flex: 1;
}

/* Footer */
.org-settings-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.75rem;
    border-top: 1.5px solid #f0e6e8;
    background: #ffffff;
    flex-wrap: wrap;
    gap: 0.75rem;
}

/* Toast */
.org-settings-toast-container {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 1000000;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.org-settings-toast {
    background: #1e293b;
    color: #ffffff;
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    font-size: 0.84rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    animation: toastSlideUp 0.25s ease;
}

.org-settings-toast.is-success {
    background: #15803d;
}

.org-settings-toast.is-info {
    background: #8b1828;
}

.org-settings-toast.is-error {
    background: #b91c1c;
}

@keyframes toastSlideUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Sub-modal */
.org-settings-submodal {
    border-radius: 18px;
    border: 1.5px solid #f0e6e8;
    background: #ffffff;
    box-shadow: 0 20px 48px rgba(90, 15, 30, 0.2);
    padding: 0;
    margin: auto;
    overflow-y: auto;
    box-sizing: border-box;
    width: calc(100% - 2rem);
    max-height: calc(100dvh - 2rem);
}

.org-settings-submodal::backdrop {
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(4px);
}

.org-settings-account-form { display: grid; gap: 0.85rem; }
.org-settings-account-form [hidden] { display: none !important; }
.org-settings-submodal-header { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1rem; }
.org-settings-submodal-header h4 { margin: 0; font-size: 1.05rem; color: #1a1618; }
.org-settings-account-subject { font-weight: 700; overflow-wrap: anywhere; }
.org-settings-dialog-actions, .org-settings-user-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.org-settings-dialog-actions { justify-content: flex-end; margin-top: 0.5rem; }
.org-settings-user-actions { min-width: 190px; max-width: 260px; }
.org-settings-user-actions button { font-size: 0.72rem; padding: 0.45rem 0.6rem; }
.org-settings-user-identity { display: flex; align-items: center; gap: 0.6rem; }
.org-settings-user-identity .org-settings-avatar-sm { flex-shrink: 0; }
.org-settings-user-identity strong { display: block; font-size: 0.85rem; color: #1a1618; }
.org-settings-user-identity small, [data-user-organization], [data-user-password-state], [data-user-updated] { font-size: 0.72rem; color: #64748b; overflow-wrap: anywhere; }
.org-settings-user-identity small, [data-user-organization], [data-user-password-state] { display: block; margin-top: 0.2rem; }
.org-settings-form-error { margin: 0; padding: 0.75rem; border: 1px solid #e7a8b2; border-radius: 8px; background: #fff1f3; color: #8b1828; font-size: 0.82rem; overflow-wrap: anywhere; }
.org-settings-submodal :focus-visible, .org-settings-user-actions button:focus-visible { outline: 3px solid #8b1828; outline-offset: 3px; }

@media (max-width: 768px) {
    .org-settings-backdrop {
        padding: 0.5rem;
    }
    .org-settings-dialog {
        height: calc(100dvh - 1rem);
        max-height: calc(100dvh - 1rem);
        border-radius: 16px;
    }
    .org-settings-header, .org-settings-footer {
        padding: 0.85rem;
        flex-shrink: 0;
    }
    .org-settings-content {
        padding: 1rem;
    }
    .org-settings-header {
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .org-settings-header-right {
        width: 100%;
        justify-content: space-between;
    }
    .org-settings-body {
        flex-direction: column;
    }
    .org-settings-nav {
        width: 100%;
        flex-direction: row;
        overflow-x: auto;
        border-right: none;
        border-bottom: 1.5px solid #f0e6e8;
        box-sizing: border-box;
        padding: 0.5rem;
        flex-shrink: 0;
    }
    .org-settings-nav-item {
        width: auto;
        flex-shrink: 0;
        white-space: nowrap;
    }
    .org-settings-nav-group-label, .org-settings-nav-footer, .org-settings-nav-text small {
        display: none;
    }
    .org-settings-toast-container {
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
    }
    .org-settings-toast {
        padding: 0.75rem;
        overflow-wrap: anywhere;
    }
    .org-settings-grid-2, .org-settings-grid-3, .org-settings-perm-grid {
        grid-template-columns: 1fr;
    }
    .org-settings-search-wrap {
        flex: 1;
    }
}
</style>

{{-- =========================================================================
     Settings Interactive Engine (OSO backend-connected)
     ========================================================================= --}}
<script>
    const settingsIsOso = @json($isOso);
    const settingsOfficerId = @json($office->id);
    const settingsAccounts = new Map();
    let settingsAccountAction = null;
    let settingsAccountTargetId = null;
    let settingsAccountReturnFocus = null;
    let settingsReturnFocus = null;
    let settingsBodyOverflow = '';
    let settingsMutationPending = false;

    function availableSettingsTabs() {
        return settingsIsOso ? ['general', 'account', 'security', 'users', 'records'] : ['account'];
    }

    function switchSettingsTab(tabKey, keepSearch = false) {
        if (!keepSearch) {
            document.getElementById('settingsGlobalSearch').value = '';
            document.querySelectorAll('#orgSettingsModal .org-settings-card').forEach(card => card.hidden = false);
            document.getElementById('settingsSearchEmpty').hidden = true;
        }
        const tabs = availableSettingsTabs();
        const activeTab = tabs.includes(tabKey) ? tabKey : tabs[0];
        tabs.forEach(tab => {
            const active = tab === activeTab;
            document.getElementById(`setPanel-${tab}`)?.classList.toggle('is-active', active);
            const button = document.getElementById(`setNavBtn-${tab}`);
            button?.classList.toggle('is-active', active);
            button?.setAttribute('aria-current', active ? 'page' : 'false');
        });
        document.getElementById('settingsContentContainer').scrollTop = 0;
    }

    function openSettingsModal(defaultTab = 'general') {
        const modal = document.getElementById('orgSettingsModal');
        if (modal.getAttribute('aria-hidden') === 'false') return;
        settingsReturnFocus = document.activeElement;
        if (typeof closeOrgUserDropdown === 'function') closeOrgUserDropdown();
        settingsBodyOverflow = document.body.style.overflow;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        switchSettingsTab(defaultTab);
        document.body.style.overflow = 'hidden';
        modal.querySelector('.org-settings-dialog').focus();
    }

    function clearSettingsCredential(id) {
        const input = document.getElementById(id);
        if (!input) return;
        input.value = '';
        input.type = 'password';
        const button = input.parentElement.querySelector('.org-settings-pw-toggle');
        if (button) {
            button.innerHTML = '<i class="bi bi-eye"></i>';
            button.setAttribute('aria-label', 'Show password or PIN');
            button.setAttribute('aria-pressed', 'false');
        }
    }

    function closeSettingsModal() {
        const modal = document.getElementById('orgSettingsModal');
        if (modal.getAttribute('aria-hidden') === 'true') return;
        document.querySelectorAll('.org-settings-submodal[open]').forEach(dialog => dialog.close());
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = settingsBodyOverflow;
        ['setCurrentPassword', 'setNewPassword', 'setConfirmPassword', 'setTosaPinField'].forEach(clearSettingsCredential);
        checkPasswordStrength('');
        if (settingsReturnFocus?.isConnected) settingsReturnFocus.focus();
        window.dispatchEvent(new CustomEvent('oso-settings-closed'));
    }

    function filterSettingsSearch(query) {
        const q = query.toLowerCase().trim();
        const matches = [];
        availableSettingsTabs().forEach(tab => {
            const panel = document.getElementById(`setPanel-${tab}`);
            let matched = false;
            panel.querySelectorAll('.org-settings-card').forEach(card => {
                const text = `${panel.querySelector('.org-settings-panel-title').textContent} ${card.textContent}`.toLowerCase();
                card.hidden = Boolean(q) && !text.includes(q);
                if (!card.hidden) matched = true;
            });
            if (matched) matches.push(tab);
        });
        document.getElementById('settingsSearchEmpty').hidden = !q || matches.length > 0;
        if (q && matches.length) {
            const active = document.querySelector('#orgSettingsModal .org-settings-panel.is-active')?.id.replace('setPanel-', '');
            switchSettingsTab(matches.includes(active) ? active : matches[0], true);
        }
    }

    // Profile and password changes belong to the signed-in user.
    // Institutional settings and management actions remain OSO-only.
    const osoSettingsRoutes = {
        update: @json(route('office.settings.update')),
        account: @json(route('office.settings.account')),
        password: @json(route('office.settings.password')),
        pin: @json(url('/office-desk/settings/pin')),
        logo: @json(route('office.settings.logo')),
        logoReset: @json(route('office.settings.logo.reset')),
        users: @json(route('office.settings.users.store')),
        userStatusBase: @json(url('/office-desk/settings/users')),
        snapshot: @json(route('office.settings.snapshot')),
        exportsBase: @json(url('/office-desk/settings/exports')),
    };

    function osoSettingsCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async function osoSettingsError(response) {
        if ((response.headers.get('content-type') || '').includes('application/json')) {
            const data = await response.json();
            return Object.values(data.errors || {}).flat().join(' ') || data.message || 'The settings request could not be completed.';
        }
        if (response.status === 419) return 'Your session expired. Reload the page and sign in again.';
        if (response.status === 401 || response.redirected) return 'Please sign in again before changing settings.';
        if (response.status === 403) return 'You do not have permission to perform this action.';
        return `The settings request could not be completed (HTTP ${response.status}).`;
    }

    async function osoSettingsRequest(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': osoSettingsCsrf(),
                ...(options.headers || {}),
            },
        });
        if (!response.ok) throw new Error(await osoSettingsError(response));
        if (response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) {
            throw new Error('The server did not confirm the change. Your session may have expired; reload and sign in again.');
        }
        const data = await response.json();
        if (data.ok !== true) throw new Error(data.message || 'The server did not confirm the change.');
        return data;
    }

    async function runSettingsMutation(action) {
        if (settingsMutationPending) {
            showSettingsToast('Please wait for the current settings change to finish.', 'info');
            return;
        }
        settingsMutationPending = true;
        const controls = [...document.querySelectorAll('#orgSettingsModal button[onclick*="save"], #orgSettingsModal button[onclick*="resetLogo"], #orgSettingsModal button[onclick*="triggerLogo"], #orgSettingsModal [data-account-action], #orgSettingsModal button[type="submit"], .org-settings-submodal button')]
            .filter(button => !button.disabled);
        controls.forEach(button => button.disabled = true);
        try {
            return await action();
        } catch (error) {
            const dialog = document.querySelector('.org-settings-submodal[open]');
            if (dialog) showSettingsAccountError(dialog, error.message || 'The settings request could not be completed.');
            showSettingsToast(error.message || 'The settings request could not be completed.', 'error');
        } finally {
            settingsMutationPending = false;
            controls.forEach(button => button.disabled = false);
        }
    }

    function postSettingsJson(url, values, method = 'POST') {
        return osoSettingsRequest(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(values),
        });
    }

    function settingsInputValue(id) {
        return document.getElementById(id)?.value ?? '';
    }

    function settingsChecked(id) {
        return Boolean(document.getElementById(id)?.checked);
    }

    function collectOsoSection(section) {
        const values = {
            general: {
                system_name: settingsInputValue('setSysName'),
                office_name: settingsInputValue('setOfficeName'),
                university_name: settingsInputValue('setUniversityName'),
                campus_unit: settingsInputValue('setCampusUnit'),
                contact_email: settingsInputValue('setContactEmail'),
                contact_phone: settingsInputValue('setContactPhone'),
                contact_location: settingsInputValue('setContactLocation'),
            },
            security: {
                session_timeout: Number(settingsInputValue('setSessionTimeout')),
                tosa_gate: settingsChecked('setTosaGateToggle'),
            },
        };

        return values[section] || null;
    }

    async function persistSettingsSection(section) {
        const data = await postSettingsJson(osoSettingsRoutes.update, { section, values: collectOsoSection(section) });
        if (section === 'general' && data.settings?.general) {
            const name = data.settings.general.office_name;
            const kicker = document.querySelector('.org-topbar .org-module-kicker');
            if (kicker) kicker.textContent = `${name} Review Desk`;
            const separator = document.title.lastIndexOf(' | ');
            if (separator >= 0) document.title = `${document.title.slice(0, separator)} | ${name}`;
        }
        window.dispatchEvent(new CustomEvent('oso-settings-saved', { detail: { section, settings: data.settings } }));
        return data;
    }

    function saveSettingsSection(sectionName) {
        const section = sectionName.toLowerCase();
        if (section === 'account') return saveOsoAccount();
        return runSettingsMutation(async () => {
            const data = await persistSettingsSection(section);
            showSettingsToast(data.message || `${sectionName} settings saved.`, 'success');
        });
    }

    async function persistSettingsProfile() {
        const data = await postSettingsJson(osoSettingsRoutes.account, {
            name: settingsInputValue('setOfficerName'),
            office_title: settingsInputValue('setOfficerDesignation'),
            email: settingsInputValue('setOfficerEmail'),
            employee_id: settingsInputValue('setOfficerEmployeeId'),
        });
        const user = data.user;
        if (user) {
            const emailInput = document.getElementById('setOfficerEmail');
            if (emailInput.value.trim().toLowerCase() === user.email) emailInput.value = user.email;
            document.querySelectorAll('#settingsProfileName, .org-settings-nav-footer > div:nth-child(2), .org-user-pill-name, .org-dropdown-name').forEach(element => element.textContent = user.name);
            document.querySelectorAll('#settingsProfileInitials, .org-user-avatar span, .org-dropdown-avatar span').forEach(element => element.textContent = user.initials);
            document.querySelectorAll('.org-dropdown-email').forEach(element => element.textContent = user.email);
            if (settingsAccounts.has(Number(settingsOfficerId))) {
                upsertOsoUser({ ...settingsAccounts.get(Number(settingsOfficerId)), ...user });
            }
        }
        return data;
    }

    function saveOsoAccount() {
        return runSettingsMutation(async () => {
            const data = await persistSettingsProfile();
            showSettingsToast(data.message || 'Account profile saved.', 'success');
        });
    }

    function saveAllSettings() {
        return runSettingsMutation(async () => {
            const saved = [];
            const sections = settingsIsOso ? ['general', 'account', 'security'] : ['account'];
            for (const section of sections) {
                try {
                    if (section === 'account') await persistSettingsProfile();
                    else await persistSettingsSection(section);
                    saved.push(section === 'account' ? 'Profile' : section === 'general' ? 'General' : 'TOSA controls');
                } catch (error) {
                    const prefix = saved.length ? `Partially saved: ${saved.join(', ')}. ` : 'No settings were saved. ';
                    throw new Error(`${prefix}${section === 'account' ? 'Profile' : section} was not saved: ${error.message} Remaining sections were not saved.`);
                }
            }
            showSettingsToast('General settings, profile, and TOSA controls saved. Passwords and PINs were not changed.', 'success');
        });
    }

    function toggleSettingState(settingName, isChecked) {
        showSettingsToast(`${settingName} will be ${isChecked ? 'enabled' : 'disabled'} after saving TOSA access controls.`, 'info');
    }

    // Password Strengths
    function checkPasswordStrength(pw) {
        const bar = document.getElementById('pwStrengthBar');
        if (!bar) return;
        if (!pw) {
            bar.style.width = '0%';
            return;
        }
        let score = 0;
        if (pw.length >= 8) score += 25;
        if (/[A-Z]/.test(pw)) score += 25;
        if (/[0-9]/.test(pw)) score += 25;
        if (/[^A-Za-z0-9]/.test(pw)) score += 25;

        bar.style.width = `${score}%`;
        if (score <= 25) bar.style.background = '#ef4444';
        else if (score <= 50) bar.style.background = '#f97316';
        else if (score <= 75) bar.style.background = '#eab308';
        else bar.style.background = '#22c55e';
    }

    function togglePwVisibility(inputId, btnEl) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        btnEl.innerHTML = `<i class="bi ${reveal ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
        btnEl.setAttribute('aria-label', reveal ? 'Hide password or PIN' : 'Show password or PIN');
        btnEl.setAttribute('aria-pressed', String(reveal));
    }

    function handlePasswordUpdate(e) {
        e.preventDefault();
        if (!settingsInputValue('setNewPassword') || settingsInputValue('setNewPassword') !== settingsInputValue('setConfirmPassword')) {
            showSettingsToast('Enter a new password and matching confirmation.', 'error');
            return;
        }
        return runSettingsMutation(async () => {
            const data = await postSettingsJson(osoSettingsRoutes.password, {
                current_password: settingsInputValue('setCurrentPassword'),
                new_password: settingsInputValue('setNewPassword'),
                new_password_confirmation: settingsInputValue('setConfirmPassword'),
            });
            ['setCurrentPassword', 'setNewPassword', 'setConfirmPassword'].forEach(clearSettingsCredential);
            checkPasswordStrength('');
            showSettingsToast(data.message || 'Account password updated.', 'success');
        });
    }

    function saveOsoPin(type = 'tosa') {
        if (type !== 'tosa') return;
        const pin = settingsInputValue('setTosaPinField');
        if (!/^\d{4}$/.test(pin)) {
            showSettingsToast('Please enter a valid 4-digit TOSA PIN.', 'error');
            return;
        }
        return runSettingsMutation(async () => {
            const data = await postSettingsJson(`${osoSettingsRoutes.pin}/tosa`, { pin });
            clearSettingsCredential('setTosaPinField');
            document.getElementById('settingsTosaPinStatus').textContent = 'Current PIN is configured and stored as a one-way hash. Enter a new 4-digit PIN to replace it.';
            window.dispatchEvent(new CustomEvent('oso-settings-saved', { detail: { section: 'pin' } }));
            showSettingsToast(data.message || 'TOSA PIN updated.', 'success');
        });
    }

    // Logo Upload Trigger
    function triggerLogoUpload() {
        document.getElementById('settingsLogoFileInput')?.click();
    }

    function applySettingsLogo(url) {
        const image = document.getElementById('settingsLogoPreview');
        if (image) image.src = url;
        const favicon = document.querySelector('link[rel="icon"]');
        if (favicon) favicon.href = url;
    }

    function handleLogoChange(input) {
        const file = input.files?.[0];
        if (!file) return;
        if (!/\.(png|jpe?g)$/i.test(file.name) || file.size > 2048 * 1024) {
            input.value = '';
            showSettingsToast('Choose a PNG or JPEG logo no larger than 2 MB.', 'error');
            return;
        }
        return runSettingsMutation(async () => {
            try {
                const formData = new FormData();
                formData.append('logo', file);
                const data = await osoSettingsRequest(osoSettingsRoutes.logo, { method: 'POST', body: formData });
                if (data.url) applySettingsLogo(`${data.url}?v=${Date.now()}`);
                showSettingsToast(data.message || 'Institutional logo uploaded.', 'success');
            } finally {
                input.value = '';
            }
        });
    }

    function resetLogoToDefault() {
        if (!window.confirm('Reset the OSO logo to the default OrgChain emblem?')) return;
        return runSettingsMutation(async () => {
            const data = await osoSettingsRequest(osoSettingsRoutes.logoReset, { method: 'DELETE' });
            applySettingsLogo(@json(asset('Orgchain logo.png')));
            showSettingsToast(data.message || 'Institutional logo reset.', 'success');
        });
    }

    // Account metadata is shared by server-rendered rows and confirmed API responses.
    function filterSettingsAccounts(query = settingsInputValue('settingsAccountSearch')) {
        const q = query.trim().toLowerCase();
        let matches = 0;
        document.querySelectorAll('[data-settings-user-id]').forEach(row => {
            const user = settingsAccounts.get(Number(row.dataset.settingsUserId));
            if (!user) return;
            const text = [user.name, user.email, user.organization_name, user.role, user.office_role, user.office_title].join(' ').toLowerCase();
            row.hidden = Boolean(q) && !text.includes(q);
            if (!row.hidden) matches++;
        });
        const empty = document.getElementById('settingsAccountSearchEmpty');
        if (empty) empty.hidden = !q || matches > 0;
    }

    function updateNewOfficerClearance() {
        const role = settingsInputValue('newOfficerRole');
        const isSo = role === 'so';
        const organization = document.getElementById('newOfficerOrganization');
        organization.required = isSo;
        organization.disabled = !isSo;
        document.getElementById('newOfficerOrganizationField').hidden = !isSo;
        const select = document.getElementById('newOfficerTosaClearance');
        [...select.options].forEach((option, index) => {
            option.disabled = index > (role === 'oso' ? 3 : role === 'ovcaa' ? 2 : 0);
            option.hidden = option.disabled;
        });
        if (select.selectedOptions[0]?.disabled || isSo) select.value = 'No Access';
        select.disabled = isSo;
        document.getElementById('newOfficerClearanceField').hidden = isSo;
    }

    function showSettingsAccountError(dialog, message = '') {
        const error = dialog.querySelector('[role="alert"]');
        error.textContent = message;
        error.hidden = !message;
        if (message) error.focus();
    }

    function openSettingsAccountDialog(dialog, focusId) {
        if (!settingsIsOso || settingsMutationPending) return;
        settingsAccountReturnFocus = document.activeElement;
        showSettingsAccountError(dialog);
        dialog.appendChild(document.getElementById('settingsToastContainer'));
        if (!dialog.open) dialog.showModal();
        document.getElementById(focusId).focus();
    }

    function openAddUserModal() {
        updateNewOfficerClearance();
        openSettingsAccountDialog(document.getElementById('settingsAddUserModal'), 'newOfficerName');
    }

    function upsertOsoUser(user) {
        const tbody = document.getElementById('settingsUserListTbody');
        if (!tbody) return;
        const id = Number(user.id);
        settingsAccounts.set(id, user);
        tbody.querySelector('td[colspan]')?.closest('tr').remove();
        let row = tbody.querySelector(`[data-settings-user-id="${id}"]`);
        if (!row) {
            row = document.createElement('tr');
            row.dataset.settingsUserId = id;
            tbody.appendChild(row);
        }
        row.dataset.settingsUser = JSON.stringify(user);
        // Only fixed markup is parsed; every account-provided value uses textContent.
        row.innerHTML = '<td><div class="org-settings-user-identity"><div class="org-settings-avatar-sm"></div><div><strong data-user-name></strong><small data-user-email></small><small data-user-title></small></div></div></td><td><span class="org-settings-role-badge"></span><small data-user-organization></small></td><td><span data-user-clearance></span></td><td><span data-user-status></span><small data-user-password-state></small></td><td data-user-updated></td><td><div class="org-settings-user-actions"></div></td>';
        row.querySelector('[data-user-name]').textContent = user.name;
        row.querySelector('[data-user-email]').textContent = user.email;
        row.querySelector('[data-user-title]').textContent = user.office_title || '';
        row.querySelector('.org-settings-avatar-sm').textContent = user.initials;
        const role = row.querySelector('.org-settings-role-badge');
        role.textContent = user.role;
        const roleClass = { so: 'is-so', oso: 'is-admin', sdo: 'is-sdo', oc: 'is-oc', ovcaa: 'is-ovcaa' }[user.office_role];
        if (roleClass) role.classList.add(roleClass);
        row.querySelector('[data-user-organization]').textContent = user.organization_name || (user.office_role === 'so' ? 'No organization assigned' : '');
        const clearance = row.querySelector('[data-user-clearance]');
        clearance.textContent = user.tosa_clearance;
        clearance.className = user.tosa_clearance === 'No Access' ? 'org-settings-pill-gray' : 'org-settings-pill-green';
        const status = row.querySelector('[data-user-status]');
        status.textContent = user.is_active ? 'Enabled' : 'Disabled';
        status.className = user.is_active ? 'org-settings-status-active' : 'org-settings-pill-gray';
        row.querySelector('[data-user-password-state]').textContent = user.must_change_password ? 'Password change required' : '';
        const updatedAt = user.updated_at ? new Date(user.updated_at) : null;
        row.querySelector('[data-user-updated]').textContent = updatedAt && !Number.isNaN(updatedAt.getTime()) ? updatedAt.toLocaleString() : 'Not recorded';
        const actions = row.querySelector('.org-settings-user-actions');
        const addAction = (action, label, disabled = false) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'org-settings-btn-subtle';
            button.dataset.accountAction = action;
            button.textContent = label;
            button.disabled = disabled;
            button.setAttribute('aria-label', `${label}: ${user.name}`);
            if (disabled) button.title = 'Turnover requires an enabled account with an assigned organization.';
            actions.appendChild(button);
        };
        if (id === Number(settingsOfficerId)) {
            addAction('own', 'My Account');
        } else {
            addAction('edit', 'Edit profile');
            addAction('reset', 'Reset temporary password');
            if (user.office_role === 'so') addAction('turnover', 'Turn over SO officer', !user.is_active || !user.student_organization_id);
            addAction('status', user.is_active ? 'Disable' : 'Enable');
        }
        filterSettingsAccounts();
    }

    function openAccountManagement(id, action) {
        const user = settingsAccounts.get(Number(id));
        if (!user || !settingsIsOso || settingsMutationPending) return;
        if (Number(id) === Number(settingsOfficerId)) {
            switchSettingsTab('account');
            document.getElementById('setOfficerName')?.focus();
            return;
        }
        if (!['edit', 'reset', 'turnover'].includes(action)) return;
        if (action === 'turnover' && (user.office_role !== 'so' || !user.is_active || !user.student_organization_id)) {
            showSettingsToast('Turnover requires an enabled SO account with an assigned organization.', 'error');
            return;
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

    function handleAddUserSubmit(event) {
        event.preventDefault();
        const dialog = document.getElementById('settingsAddUserModal');
        showSettingsAccountError(dialog);
        if (settingsInputValue('newOfficerPassword') !== settingsInputValue('newOfficerPasswordConfirmation')) {
            showSettingsAccountError(dialog, 'The temporary password and confirmation must match.');
            return;
        }
        const role = settingsInputValue('newOfficerRole');
        const values = {
            name: settingsInputValue('newOfficerName'),
            email: settingsInputValue('newOfficerEmail'),
            office_role: role,
            office_title: settingsInputValue('newOfficerTitle'),
            employee_id: settingsInputValue('newOfficerEmployeeId'),
            password: settingsInputValue('newOfficerPassword'),
            password_confirmation: settingsInputValue('newOfficerPasswordConfirmation'),
            tosa_clearance: role === 'so' ? 'No Access' : settingsInputValue('newOfficerTosaClearance'),
        };
        if (role === 'so') values.student_organization_id = settingsInputValue('newOfficerOrganization');
        return runSettingsMutation(async () => {
            const data = await postSettingsJson(osoSettingsRoutes.users, values);
            upsertOsoUser(data.user);
            dialog.querySelector('form').reset();
            updateNewOfficerClearance();
            dialog.close();
            showSettingsToast(data.message || 'Officer account created.', 'success');
            filterSettingsSearch(settingsInputValue('settingsGlobalSearch'));
        });
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
            const data = await postSettingsJson(`${osoSettingsRoutes.userStatusBase}/${id}${suffix}`, values, action === 'edit' ? 'PATCH' : 'POST');
            if (action === 'turnover') upsertOsoUser(data.previous_user);
            upsertOsoUser(data.user);
            dialog.close();
            showSettingsToast(data.message || 'Officer account updated.', 'success');
            filterSettingsSearch(settingsInputValue('settingsGlobalSearch'));
        });
    }

    function toggleOsoUserStatus(id, isActive) {
        if (Number(id) === Number(settingsOfficerId)) {
            showSettingsToast('Use My Account to manage your own profile and password. You cannot change your own account status.', 'error');
            return;
        }
        const user = settingsAccounts.get(Number(id));
        if (!user || settingsMutationPending) return;
        if (!window.confirm(isActive ? `Enable ${user.name}? Previously revoked sessions remain invalid.` : `Disable ${user.name}? All existing sessions and remembered login will be revoked. Their identity and records are retained.`)) return;
        return runSettingsMutation(async () => {
            const data = await postSettingsJson(`${osoSettingsRoutes.userStatusBase}/${id}/status`, { is_active: isActive }, 'PATCH');
            upsertOsoUser(data.user);
            showSettingsToast(data.message || 'Officer account status updated.', 'success');
        });
    }

    async function downloadSettingsFile(url, label) {
        showSettingsToast(`Preparing ${label} for download…`, 'info');
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json, text/csv', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error(await osoSettingsError(response));
            const disposition = response.headers.get('content-disposition') || '';
            if (response.redirected || !disposition.toLowerCase().includes('attachment')) {
                throw new Error('The server did not return a download. Your session may have expired; reload and sign in again.');
            }
            const filename = disposition.match(/filename="([^"]+)"/i)?.[1] || disposition.match(/filename=([^;]+)/i)?.[1] || 'orgchain-export';
            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
            showSettingsToast(`${label} download started.`, 'success');
        } catch (error) {
            showSettingsToast(error.message || 'The download could not be completed.', 'error');
        }
    }

    function triggerManualBackup() {
        return downloadSettingsFile(osoSettingsRoutes.snapshot, 'Configuration snapshot');
    }

    function exportDataPackage(type) {
        if (type === 'Office Snapshot') return downloadSettingsFile(osoSettingsRoutes.snapshot, 'Office account snapshot');
        const keys = {
            'Organization Roster': 'organization-roster',
            'Accomplishment Dossier': 'accomplishment-dossier',
            'TOSA Manifest': 'tosa-manifest',
        };
        const key = keys[type];
        if (!key) {
            showSettingsToast('That export package is not available.', 'error');
            return;
        }
        return downloadSettingsFile(`${osoSettingsRoutes.exportsBase}/${key}`, type);
    }

    // Settings Toast helper
    function showSettingsToast(msg, type = 'info') {
        const container = document.getElementById('settingsToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `org-settings-toast is-${type}`;
        const icon = type === 'success'
            ? 'bi-check-circle-fill'
            : (type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
        const iconElement = document.createElement('i');
        iconElement.className = `bi ${icon}`;
        iconElement.setAttribute('aria-hidden', 'true');
        const message = document.createElement('span');
        message.textContent = msg;
        toast.append(iconElement, message);

        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(12px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, type === 'error' ? 10000 : 4500);
    }

    document.querySelectorAll('#orgSettingsModal .org-settings-pw-toggle').forEach(button => {
        button.setAttribute('aria-label', 'Show password or PIN');
        button.setAttribute('aria-pressed', 'false');
    });

    document.querySelectorAll('[data-settings-user]').forEach(row => {
        upsertOsoUser(JSON.parse(row.dataset.settingsUser));
    });
    updateNewOfficerClearance();

    document.getElementById('settingsUserListTbody')?.addEventListener('click', event => {
        const button = event.target.closest('[data-account-action]');
        if (!button || button.disabled || settingsMutationPending) return;
        const id = Number(button.closest('[data-settings-user-id]').dataset.settingsUserId);
        const action = button.dataset.accountAction;
        if (action === 'status') {
            toggleOsoUserStatus(id, !settingsAccounts.get(id).is_active);
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
            document.body.appendChild(document.getElementById('settingsToastContainer'));
            showSettingsAccountError(dialog);
            settingsAccountAction = null;
            settingsAccountTargetId = null;
            if (document.getElementById('orgSettingsModal').getAttribute('aria-hidden') === 'false') {
                const fallback = targetId ? document.querySelector(`[data-settings-user-id="${targetId}"] button:not(:disabled)`) : null;
                const focus = settingsAccountReturnFocus?.isConnected ? settingsAccountReturnFocus : fallback;
                if (focus && !focus.disabled) focus.focus();
                else document.querySelector('#orgSettingsModal .org-settings-dialog').focus();
            }
            settingsAccountReturnFocus = null;
        });
    });

    document.getElementById('orgSettingsModal').addEventListener('click', event => {
        if (event.target.id === 'orgSettingsModal') closeSettingsModal();
    });

    document.addEventListener('keydown', event => {
        const modal = document.getElementById('orgSettingsModal');
        if (modal.getAttribute('aria-hidden') === 'true' || document.querySelector('.org-settings-submodal[open]')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeSettingsModal();
        } else if (event.key === 'Tab') {
            const elements = [...modal.querySelectorAll('button, input, select, a[href], [tabindex="0"]')]
                .filter(element => !element.disabled && element.getClientRects().length);
            const first = elements[0];
            const last = elements[elements.length - 1];
            if (event.shiftKey && (document.activeElement === first || !elements.includes(document.activeElement))) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !elements.includes(document.activeElement))) {
                event.preventDefault();
                first?.focus();
            }
        }
    });
</script>
