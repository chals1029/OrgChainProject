{{-- =========================================================================
     OrgChain Executive Settings Hub Modal & Component (Front-End Only)
     Impeccable & Unslop Design System · Batangas State University
     ========================================================================= --}}
<div id="orgSettingsModal" class="org-settings-backdrop" style="display: none;" aria-hidden="true">
    <div class="org-settings-dialog">
        {{-- Settings Header --}}
        <div class="org-settings-header">
            <div class="org-settings-header-left">
                <div class="org-settings-icon-badge">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>
                <div class="org-settings-title-block">
                    <div class="org-settings-title-line">
                        <h2 class="org-settings-title">System &amp; Office Settings</h2>
                        <span class="org-settings-version-tag"><i class="bi bi-shield-check"></i> v2.4 Stable</span>
                    </div>
                    <p class="org-settings-subtitle">Configure institutional parameters, security credentials, user roles, notifications, and data governance policies.</p>
                </div>
            </div>
            <div class="org-settings-header-right">
                <div class="org-settings-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="settingsGlobalSearch" placeholder="Search settings (e.g. PIN, 2FA, TOSA)..." oninput="filterSettingsSearch(this.value)">
                </div>
                <button type="button" class="org-settings-close-btn" onclick="closeSettingsModal()" title="Close Settings (Esc)" aria-label="Close Settings">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        {{-- Settings Main Layout (Sidebar Navigation + Content Panels) --}}
        <div class="org-settings-body">
            {{-- Left Navigation Sidebar --}}
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
                        <small>Profile, Password, PIN</small>
                    </div>
                </button>

                <button type="button" class="org-settings-nav-item" id="setNavBtn-security" onclick="switchSettingsTab('security')">
                    <div class="org-settings-nav-icon"><i class="bi bi-shield-lock-fill"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>Security</strong>
                        <small>Login, Timeout, TOSA PIN</small>
                    </div>
                </button>

                <button type="button" class="org-settings-nav-item" id="setNavBtn-notifications" onclick="switchSettingsTab('notifications')">
                    <div class="org-settings-nav-icon"><i class="bi bi-bell-fill"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>Notifications</strong>
                        <small>Alerts, Email, Dispatches</small>
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

                <button type="button" class="org-settings-nav-item" id="setNavBtn-preferences" onclick="switchSettingsTab('preferences')">
                    <div class="org-settings-nav-icon"><i class="bi bi-palette-fill"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>System Preferences</strong>
                        <small>Date, Language, Appearance</small>
                    </div>
                </button>

                <button type="button" class="org-settings-nav-item" id="setNavBtn-records" onclick="switchSettingsTab('records')">
                    <div class="org-settings-nav-icon"><i class="bi bi-database-fill-gear"></i></div>
                    <div class="org-settings-nav-text">
                        <strong>Data &amp; Records</strong>
                        <small>Archive, Backup, Logs</small>
                    </div>
                </button>

                {{-- Sidebar Footer Meta --}}
                <div class="org-settings-nav-footer">
                    <div style="font-size: 0.72rem; color: #7a7074; font-weight: 600;">Signed in as:</div>
                    <div style="font-size: 0.8rem; font-weight: 800; color: #1a1618; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $office->name ?? 'OSO Review Officer' }}</div>
                    <div style="font-size: 0.7rem; color: #8b1828; font-weight: 700;">{{ $brand['role'] ?? 'Office Administrator' }}</div>
                </div>
            </aside>

            {{-- Right Content Panels --}}
            <main class="org-settings-content" id="settingsContentContainer">
                
                {{-- =============================================================
                     TAB 1: GENERAL SETTINGS
                     ============================================================= --}}
                <section class="org-settings-panel is-active" id="setPanel-general">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">General Settings</h3>
                            <p class="org-settings-panel-desc">Manage institutional system identifiers, office branding, university logos, and primary contact routing.</p>
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
                                <p class="org-settings-card-desc">Institutional branding displayed across navigation headers, official PDF exports, and blockchain seals.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-field">
                                    <label for="setSysName" class="org-settings-label">
                                        <span>System Name</span>
                                        <span class="org-settings-required">*</span>
                                    </label>
                                    <input type="text" id="setSysName" class="org-settings-input" value="OrgChain Student Organizations Portal" placeholder="e.g. OrgChain Student Portal">
                                    <small class="org-settings-help">Displayed in the top navigation title bar and system header.</small>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setOfficeName" class="org-settings-label">
                                        <span>Office Name</span>
                                        <span class="org-settings-required">*</span>
                                    </label>
                                    <input type="text" id="setOfficeName" class="org-settings-input" value="Office of Student Organizations (OSO)" placeholder="e.g. Office of Student Organizations">
                                    <small class="org-settings-help">Official office unit managing evaluation desks and proposal endorsements.</small>
                                </div>
                            </div>

                            <div class="org-settings-grid-2" style="margin-top: 1rem;">
                                <div class="org-settings-field">
                                    <label for="setUniversityName" class="org-settings-label">University / Institution</label>
                                    <input type="text" id="setUniversityName" class="org-settings-input" value="Batangas State University - The National Engineering University" readonly style="background: #f8fafc; color: #475569;">
                                </div>
                                <div class="org-settings-field">
                                    <label for="setCampusUnit" class="org-settings-label">Campus / Department Jurisdiction</label>
                                    <input type="text" id="setCampusUnit" class="org-settings-input" value="Gov. Pablo Borbon Main Campus I · Central Directorate">
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
                                <p class="org-settings-card-desc">Uploaded logos are automatically stamped on official certificates, endorsements, and PDF dossiers.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-logo-preview-box">
                                <div class="org-settings-logo-img-wrap">
                                    <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain Logo" id="settingsLogoPreview" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 24 24\' fill=\'%238b1828\'><path d=\'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5\'/></svg>'">
                                </div>
                                <div style="flex: 1;">
                                    <strong style="font-size: 0.95rem; color: #1a1618; display: block; margin-bottom: 0.2rem;">Official OrgChain Emblem &amp; Seal</strong>
                                    <p style="font-size: 0.78rem; color: #64748b; margin: 0 0 0.75rem;">PNG, SVG or WEBP transparent format recommended (Max 2MB, 512x512px).</p>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button type="button" class="org-settings-btn-outline" onclick="triggerLogoUpload()">
                                            <i class="bi bi-upload"></i> Upload New Logo
                                        </button>
                                        <input type="file" id="settingsLogoFileInput" style="display: none;" accept="image/png, image/jpeg, image/svg+xml" onchange="handleLogoChange(this)">
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
                                <p class="org-settings-card-desc">Public-facing communication channels for student organization inquiries and evaluation feedback.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-field">
                                    <label for="setContactEmail" class="org-settings-label">Official Office Email</label>
                                    <div class="org-settings-input-group">
                                        <span class="org-settings-input-addon"><i class="bi bi-envelope"></i></span>
                                        <input type="email" id="setContactEmail" class="org-settings-input" value="oso.main@g.batstate-u.edu.ph" placeholder="office@g.batstate-u.edu.ph">
                                    </div>
                                </div>
                                <div class="org-settings-field">
                                    <label for="setContactPhone" class="org-settings-label">Office Telephone / Extension</label>
                                    <div class="org-settings-input-group">
                                        <span class="org-settings-input-addon"><i class="bi bi-telephone"></i></span>
                                        <input type="text" id="setContactPhone" class="org-settings-input" value="(043) 980-0385 loc. 1144" placeholder="(043) 980-0385 loc. 1144">
                                    </div>
                                </div>
                            </div>
                            <div class="org-settings-field" style="margin-top: 1rem;">
                                <label for="setContactLocation" class="org-settings-label">Physical Office Location</label>
                                <div class="org-settings-input-group">
                                    <span class="org-settings-input-addon"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" id="setContactLocation" class="org-settings-input" value="3rd Floor, Student Services Center, Gov. Pablo Borbon Main Campus I, Batangas City" placeholder="Building, Room, Campus">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =============================================================
                     TAB 2: ACCOUNT SETTINGS
                     ============================================================= --}}
                <section class="org-settings-panel" id="setPanel-account">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">Account Settings</h3>
                            <p class="org-settings-panel-desc">Manage your authorized officer profile credentials, institutional password, and master security PIN.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="saveSettingsSection('Account')">
                            <i class="bi bi-floppy2-fill"></i> Save Changes
                        </button>
                    </div>

                    {{-- Card: Profile Information --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-person-bounding-box"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Profile Information</h4>
                                <p class="org-settings-card-desc">Officer identity and institutional designation displayed on audit logs and blockchain signatures.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-profile-head">
                                <div class="org-settings-avatar-big">
                                    <span>{{ $office->initials() ?? 'OSO' }}</span>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 0.15rem; font-size: 1.1rem; color: #1a1618;">{{ $office->name ?? 'Office Review Officer' }}</h4>
                                    <span style="font-size: 0.8rem; color: #8b1828; font-weight: 700; background: #fdf0f2; padding: 0.2rem 0.55rem; border-radius: 6px; display: inline-block;">
                                        <i class="bi bi-patch-check-fill"></i> Certified Directorate Officer
                                    </span>
                                    <div style="margin-top: 0.5rem; font-size: 0.76rem; color: #64748b;">
                                        User ID: <strong>BSU-OFFICE-8841</strong> • Authorized Role: <strong>{{ $brand['role'] ?? 'Head Administrator' }}</strong>
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
                                    <input type="text" id="setOfficerDesignation" class="org-settings-input" value="Head, Office of Student Organizations">
                                </div>
                                <div class="org-settings-field">
                                    <label for="setOfficerEmail" class="org-settings-label">Primary Account Email</label>
                                    <input type="email" id="setOfficerEmail" class="org-settings-input" value="{{ $office->email ?? 'oso.lead@g.batstate-u.edu.ph' }}">
                                </div>
                                <div class="org-settings-field">
                                    <label for="setOfficerEmployeeId" class="org-settings-label">Employee / Faculty ID</label>
                                    <input type="text" id="setOfficerEmployeeId" class="org-settings-input" value="BSU-EMP-2024-8891">
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

                    {{-- Card: Change Master PIN --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-123"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Change Master Security PIN</h4>
                                <p class="org-settings-card-desc">Master 4-digit PIN used for sensitive actions, executive evaluations, and gateway access.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <div style="font-size: 0.88rem; font-weight: 700; color: #1a1618;">Current Master PIN Status</div>
                                    <div style="font-size: 0.78rem; color: #16a34a; font-weight: 600; margin-top: 0.2rem;">
                                        <i class="bi bi-check-circle-fill"></i> Active &amp; Encrypted (Default: 1234 or 2026)
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="password" id="setNewPinInput" maxlength="4" class="org-settings-input" style="width: 110px; text-align: center; letter-spacing: 4px; font-weight: 800; font-size: 1.1rem;" placeholder="••••">
                                    <button type="button" class="org-settings-save-btn" onclick="updateMasterPin()">
                                        <i class="bi bi-check2"></i> Save New PIN
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =============================================================
                     TAB 3: SECURITY SETTINGS
                     ============================================================= --}}
                <section class="org-settings-panel" id="setPanel-security">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">Security &amp; Access Controls</h3>
                            <p class="org-settings-panel-desc">Configure login safeguards, session inactivity thresholds, and TOSA restricted desk parameters.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="saveSettingsSection('Security')">
                            <i class="bi bi-floppy2-fill"></i> Save Changes
                        </button>
                    </div>

                    {{-- Card: Login Security --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-shield-check"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Login Security &amp; Multi-Factor Authentication</h4>
                                <p class="org-settings-card-desc">Enforce high-assurance security policies across all officer login sessions.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Two-Factor Authentication (2FA)</strong>
                                    <p class="org-settings-toggle-desc">Require email OTP or Google Authenticator verification on every login.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" id="set2FaToggle" checked onchange="toggleSettingState('2FA', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Strict IP Whitelist &amp; Geofencing</strong>
                                    <p class="org-settings-toggle-desc">Restrict portal access exclusively to authorized BSU Campus subnetworks.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" id="setIpLockToggle" onchange="toggleSettingState('IP Whitelist', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Biometric / WebAuthn Hardware Keys</strong>
                                    <p class="org-settings-toggle-desc">Allow fingerprint or FIDO2 hardware tokens for executive signing.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" id="setBiometricToggle" checked onchange="toggleSettingState('Biometric WebAuthn', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Session Timeout & Auto-lock --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-hourglass-bottom"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Session Inactivity &amp; Auto-Lock</h4>
                                <p class="org-settings-card-desc">Automatically secure active dashboards when idle to prevent unauthorized campus access.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-field">
                                    <label for="setSessionTimeout" class="org-settings-label">Portal Session Inactivity Timeout</label>
                                    <select id="setSessionTimeout" class="org-settings-select">
                                        <option value="15">15 Minutes (Recommended)</option>
                                        <option value="30">30 Minutes</option>
                                        <option value="60">1 Hour</option>
                                        <option value="120">2 Hours</option>
                                        <option value="0">Never (Shift Mode)</option>
                                    </select>
                                    <small class="org-settings-help">Signs out officer session after prolonged inactivity.</small>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setAutoLockInterval" class="org-settings-label">Auto-Lock Timer Threshold</label>
                                    <select id="setAutoLockInterval" class="org-settings-select">
                                        <option value="5">5 Minutes</option>
                                        <option value="10">10 Minutes</option>
                                        <option value="15" selected>15 Minutes (Default)</option>
                                        <option value="30">30 Minutes</option>
                                    </select>
                                    <small class="org-settings-help">Locks executive desks requiring PIN re-entry.</small>
                                </div>
                            </div>
                        </div>
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
                                    <strong class="org-settings-toggle-title">TOSA Module PIN Gateway Clearance</strong>
                                    <p class="org-settings-toggle-desc">Require 4-digit security PIN unlock before displaying TOSA candidate dossiers.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" id="setTosaGateToggle" checked onchange="toggleSettingState('TOSA Gatekeeper', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-grid-2" style="margin-top: 1rem;">
                                <div class="org-settings-field">
                                    <label for="setTosaPinField" class="org-settings-label">Current TOSA Access PIN</label>
                                    <div class="org-settings-input-group">
                                        <input type="password" id="setTosaPinField" class="org-settings-input" value="1234" style="letter-spacing: 3px; font-weight: 800;">
                                        <button type="button" class="org-settings-pw-toggle" onclick="togglePwVisibility('setTosaPinField', this)"><i class="bi bi-eye"></i></button>
                                    </div>
                                    <small class="org-settings-help">Authorized TOSA PINs: <code>1234</code>, <code>2026</code></small>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setTosaEvaluationMode" class="org-settings-label">TOSA Evaluation Mode</label>
                                    <select id="setTosaEvaluationMode" class="org-settings-select">
                                        <option value="strict" selected>Strict Dual-Review Verification</option>
                                        <option value="standard">Standard Single Officer Review</option>
                                        <option value="committee">Full Committee Consensus Protocol</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =============================================================
                     TAB 4: NOTIFICATIONS
                     ============================================================= --}}
                <section class="org-settings-panel" id="setPanel-notifications">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">Notification Settings</h3>
                            <p class="org-settings-panel-desc">Configure real-time event dispatchers, proposal alerts, announcements, and automated email notifications.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="saveSettingsSection('Notifications')">
                            <i class="bi bi-floppy2-fill"></i> Save Changes
                        </button>
                    </div>

                    {{-- Application Notifications --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-bell-badge"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Application &amp; Submission Notifications</h4>
                                <p class="org-settings-card-desc">Receive immediate push alerts when new proposals or candidate submissions arrive.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">New Activity Proposal Submissions</strong>
                                    <p class="org-settings-toggle-desc">Trigger notification when student orgs submit new project proposals.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" checked onchange="toggleSettingState('New Proposal Alert', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">TOSA Applicant Dossier Alerts</strong>
                                    <p class="org-settings-toggle-desc">Notify review desk when candidate requirement files are uploaded.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" checked onchange="toggleSettingState('TOSA Applicant Alert', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Interactive Sound &amp; Audio Cues</strong>
                                    <p class="org-settings-toggle-desc">Play subtle institutional chime on high-priority incoming items.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" onchange="toggleSettingState('Sound Effects', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Approval & Revision Notifications --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-arrow-repeat"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Approval &amp; Revision Notifications</h4>
                                <p class="org-settings-card-desc">Track status transitions across OSO, SDO SDG Review, and OVCAA desks.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Proposal Endorsement Dispatches</strong>
                                    <p class="org-settings-toggle-desc">Receive confirmation receipt when proposals advance to the next approval tier.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" checked onchange="toggleSettingState('Approval Dispatches', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Document Revision &amp; Return Alerts</strong>
                                    <p class="org-settings-toggle-desc">Alert reviewers when resubmitted documents are re-uploaded by students.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" checked onchange="toggleSettingState('Revision Alerts', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- System Announcements & Email Notifications --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-megaphone-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">System Announcements &amp; Email Notifications</h4>
                                <p class="org-settings-card-desc">Manage broadcast banners and automated email summary digests.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Executive Directorate Broadcast Banner</strong>
                                    <p class="org-settings-toggle-desc">Display active university memos and deadline countdown banners on student portals.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" checked onchange="toggleSettingState('Broadcast Banner', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-grid-2" style="margin-top: 1rem;">
                                <div class="org-settings-field">
                                    <label for="setEmailDigestFreq" class="org-settings-label">Email Digest Frequency</label>
                                    <select id="setEmailDigestFreq" class="org-settings-select">
                                        <option value="instant">Instant on Critical Event</option>
                                        <option value="daily" selected>Daily Morning Summary (08:00 AM)</option>
                                        <option value="weekly">Weekly Executive Digest</option>
                                        <option value="disabled">Disabled (In-app only)</option>
                                    </select>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setDigestEmail" class="org-settings-label">Destination Digest Email</label>
                                    <input type="email" id="setDigestEmail" class="org-settings-input" value="oso.directorate@g.batstate-u.edu.ph">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =============================================================
                     TAB 5: USERS & ROLES
                     ============================================================= --}}
                <section class="org-settings-panel" id="setPanel-users">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">Users &amp; Role Permissions</h3>
                            <p class="org-settings-panel-desc">Manage authorized office personnel, permission boundaries, and certified TOSA evaluators.</p>
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
                                <p class="org-settings-card-desc">Active personnel with cryptographic signing access to the OrgChain office desk.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body" style="padding: 0;">
                            <div class="org-settings-table-wrap">
                                <table class="org-settings-table">
                                    <thead>
                                        <tr>
                                            <th>Officer Name</th>
                                            <th>Role / Office</th>
                                            <th>TOSA Clearance</th>
                                            <th>2FA Status</th>
                                            <th>Last Active</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="settingsUserListTbody">
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                                    <div class="org-settings-avatar-sm">RC</div>
                                                    <div>
                                                        <strong style="display: block; font-size: 0.85rem; color: #1a1618;">Dr. Rosalinda M. Comia</strong>
                                                        <small style="font-size: 0.72rem; color: #64748b;">rosalinda.comia@g.batstate-u.edu.ph</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="org-settings-role-badge is-admin">OSO Directorate Head</span></td>
                                            <td><span class="org-settings-pill-green"><i class="bi bi-shield-check"></i> Level 3 Master</span></td>
                                            <td><span class="org-settings-status-active"><i class="bi bi-check2"></i> Enabled</span></td>
                                            <td style="font-size: 0.78rem; color: #64748b;">Just now</td>
                                            <td style="text-align: center;">
                                                <button type="button" class="org-settings-icon-btn" onclick="editUserModal('Dr. Rosalinda M. Comia')" title="Edit Permissions"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                                    <div class="org-settings-avatar-sm" style="background: #e0f2fe; color: #0284c7;">FD</div>
                                                    <div>
                                                        <strong style="display: block; font-size: 0.85rem; color: #1a1618;">Atty. Francis G. De Silva</strong>
                                                        <small style="font-size: 0.72rem; color: #64748b;">francis.desilva@g.batstate-u.edu.ph</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="org-settings-role-badge is-eval">OSO Review Officer</span></td>
                                            <td><span class="org-settings-pill-green"><i class="bi bi-shield-check"></i> Level 2 Evaluator</span></td>
                                            <td><span class="org-settings-status-active"><i class="bi bi-check2"></i> Enabled</span></td>
                                            <td style="font-size: 0.78rem; color: #64748b;">2 hrs ago</td>
                                            <td style="text-align: center;">
                                                <button type="button" class="org-settings-icon-btn" onclick="editUserModal('Atty. Francis G. De Silva')" title="Edit Permissions"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                                    <div class="org-settings-avatar-sm" style="background: #fef3c7; color: #b45309;">MS</div>
                                                    <div>
                                                        <strong style="display: block; font-size: 0.85rem; color: #1a1618;">Engr. Maria Santos</strong>
                                                        <small style="font-size: 0.72rem; color: #64748b;">maria.santos@g.batstate-u.edu.ph</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="org-settings-role-badge is-sdo">SDO SDG Reviewer</span></td>
                                            <td><span class="org-settings-pill-gray">No Access</span></td>
                                            <td><span class="org-settings-status-active"><i class="bi bi-check2"></i> Enabled</span></td>
                                            <td style="font-size: 0.78rem; color: #64748b;">Yesterday</td>
                                            <td style="text-align: center;">
                                                <button type="button" class="org-settings-icon-btn" onclick="editUserModal('Engr. Maria Santos')" title="Edit Permissions"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                                    <div class="org-settings-avatar-sm" style="background: #f3e8ff; color: #7e22ce;">AR</div>
                                                    <div>
                                                        <strong style="display: block; font-size: 0.85rem; color: #1a1618;">Prof. Alvin Reyes</strong>
                                                        <small style="font-size: 0.72rem; color: #64748b;">alvin.reyes@g.batstate-u.edu.ph</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="org-settings-role-badge is-ovcaa">OVCAA Final Endorser</span></td>
                                            <td><span class="org-settings-pill-gray">No Access</span></td>
                                            <td><span class="org-settings-status-active"><i class="bi bi-check2"></i> Enabled</span></td>
                                            <td style="font-size: 0.78rem; color: #64748b;">May 19, 2025</td>
                                            <td style="text-align: center;">
                                                <button type="button" class="org-settings-icon-btn" onclick="editUserModal('Prof. Alvin Reyes')" title="Edit Permissions"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
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
                                <h4 class="org-settings-card-title">Roles &amp; Permissions Matrix</h4>
                                <p class="org-settings-card-desc">Enforce principle of least privilege across institutional modules and budget approvals.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-perm-grid">
                                <div class="org-settings-perm-card">
                                    <strong>OSO Lead Administrator</strong>
                                    <ul>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Full Proposal Approval &amp; Return</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Full TOSA Candidate Clearance</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Budget &amp; Financial Governance</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> System Configuration &amp; PIN Reset</li>
                                    </ul>
                                </div>
                                <div class="org-settings-perm-card">
                                    <strong>TOSA Authorized Evaluator</strong>
                                    <ul>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> TOSA Dossier Verification</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Criteria Scoring &amp; Triage</li>
                                        <li><i class="bi bi-dash-circle-fill text-muted"></i> Proposal Budget Override</li>
                                        <li><i class="bi bi-dash-circle-fill text-muted"></i> Master System Settings</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =============================================================
                     TAB 6: SYSTEM PREFERENCES
                     ============================================================= --}}
                <section class="org-settings-panel" id="setPanel-preferences">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">System Preferences</h3>
                            <p class="org-settings-panel-desc">Customize interface appearance, language localizations, date-time formats, and display densities.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="saveSettingsSection('Preferences')">
                            <i class="bi bi-floppy2-fill"></i> Save Changes
                        </button>
                    </div>

                    {{-- Date & Time Preferences --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-clock-history"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Date &amp; Time Localization</h4>
                                <p class="org-settings-card-desc">Standardizes timestamp formatting on ledger transactions and calendar schedules.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-3">
                                <div class="org-settings-field">
                                    <label for="setTimezoneSelect" class="org-settings-label">System Timezone</label>
                                    <select id="setTimezoneSelect" class="org-settings-select">
                                        <option value="Asia/Manila" selected>Philippine Standard Time (PHT, GMT+8)</option>
                                        <option value="UTC">Coordinated Universal Time (UTC)</option>
                                    </select>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setDateFormatSelect" class="org-settings-label">Display Date Format</label>
                                    <select id="setDateFormatSelect" class="org-settings-select">
                                        <option value="MMM D, YYYY" selected>May 20, 2025 (Standard)</option>
                                        <option value="MM/DD/YYYY">05/20/2025 (US)</option>
                                        <option value="DD/MM/YYYY">20/05/2025 (PH/UK)</option>
                                        <option value="YYYY-MM-DD">2025-05-20 (ISO)</option>
                                    </select>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setTimeFormatSelect" class="org-settings-label">Clock Display</label>
                                    <select id="setTimeFormatSelect" class="org-settings-select">
                                        <option value="12h" selected>12-Hour (02:45 PM)</option>
                                        <option value="24h">24-Hour (14:45)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Language & Appearance --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-palette2"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Language, Theme &amp; Visual Appearance</h4>
                                <p class="org-settings-card-desc">Select visual themes inspired by BatStateU Red Spartan aesthetics and liquid glassmorphism.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-field">
                                    <label for="setLangSelect" class="org-settings-label">Primary Portal Language</label>
                                    <select id="setLangSelect" class="org-settings-select">
                                        <option value="en" selected>English (Philippines / Institutional)</option>
                                        <option value="fil">Filipino / Tagalog</option>
                                    </select>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setThemeSelect" class="org-settings-label">Visual Theme &amp; Styling Palette</label>
                                    <select id="setThemeSelect" class="org-settings-select" onchange="applyLiveTheme(this.value)">
                                        <option value="red-spartan" selected>Red Spartan Crimson (Official BSU)</option>
                                        <option value="liquid-glass">Liquid Glassmorphism Ambient</option>
                                        <option value="modern-light">Crisp Editorial Minimal</option>
                                    </select>
                                </div>
                            </div>

                            <div class="org-settings-toggle-row" style="margin-top: 1rem;">
                                <div>
                                    <strong class="org-settings-toggle-title">High-Contrast &amp; Accessibility Mode</strong>
                                    <p class="org-settings-toggle-desc">Enhances text contrast ratios and outlines for WCAG AAA compliance.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" onchange="toggleSettingState('High Contrast', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Smooth Micro-Animations &amp; Transitions</strong>
                                    <p class="org-settings-toggle-desc">Enable dynamic fluid transitions and hover elevations.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" checked onchange="toggleSettingState('Micro Animations', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Default Display Settings --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-display"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Default Display Settings &amp; Table Density</h4>
                                <p class="org-settings-card-desc">Configure default pagination limits and landing module upon officer login.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-field">
                                    <label for="setDefaultLandingModule" class="org-settings-label">Default Module on Login</label>
                                    <select id="setDefaultLandingModule" class="org-settings-select">
                                        <option value="dashboard" selected>Dashboard Executive Hub</option>
                                        <option value="activities">Activities &amp; Proposals Review</option>
                                        <option value="analytics">Analytics &amp; Intelligence</option>
                                        <option value="tosa">TOSA Awards Module</option>
                                    </select>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setTablePageSize" class="org-settings-label">Default Table Rows per Page</label>
                                    <select id="setTablePageSize" class="org-settings-select">
                                        <option value="7" selected>7 Rows (Comfortable)</option>
                                        <option value="10">10 Rows</option>
                                        <option value="25">25 Rows (Compact)</option>
                                        <option value="50">50 Rows</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =============================================================
                     TAB 7: DATA & RECORDS
                     ============================================================= --}}
                <section class="org-settings-panel" id="setPanel-records">
                    <div class="org-settings-panel-header">
                        <div>
                            <h3 class="org-settings-panel-title">Data &amp; Records Governance</h3>
                            <p class="org-settings-panel-desc">Manage institutional archives, cryptographic backups, data export manifests, and activity ledger audits.</p>
                        </div>
                        <button type="button" class="org-settings-save-btn" onclick="saveSettingsSection('Data & Records')">
                            <i class="bi bi-floppy2-fill"></i> Save Changes
                        </button>
                    </div>

                    {{-- Archive Settings --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-archive-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Archive Settings &amp; Retention Policy</h4>
                                <p class="org-settings-card-desc">Configure automatic rollover for completed Academic Year cycles and permanent preservation.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-toggle-row">
                                <div>
                                    <strong class="org-settings-toggle-title">Auto-Archive Completed Academic Year Cycles</strong>
                                    <p class="org-settings-toggle-desc">Automatically move concluded AY activity dossiers and financial sheets to the secure vault.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" checked onchange="toggleSettingState('Auto Archive', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>

                            <div class="org-settings-grid-2" style="margin-top: 1rem;">
                                <div class="org-settings-field">
                                    <label for="setRetentionSchedule" class="org-settings-label">Document Retention Schedule</label>
                                    <select id="setRetentionSchedule" class="org-settings-select">
                                        <option value="1">1 Year Active</option>
                                        <option value="3">3 Years (Standard)</option>
                                        <option value="5" selected>5 Years Institutional Audit Requirement</option>
                                        <option value="permanent">Permanent / Immutable Blockchain Archive</option>
                                    </select>
                                </div>

                                <div class="org-settings-field">
                                    <label for="setArchiveStorageLocation" class="org-settings-label">Archive Storage Partition</label>
                                    <input type="text" id="setArchiveStorageLocation" class="org-settings-input" value="BSU-VAULT-AY2627-NODE01" readonly style="background: #f8fafc; color: #475569;">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Backup & Snapshot Management --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Institutional Backup &amp; Disaster Recovery</h4>
                                <p class="org-settings-card-desc">Generate point-in-time database snapshots and configure automated cloud replication.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; background: #fdfafb; border: 1.5px solid #f2dfe2; border-radius: 12px; padding: 1rem 1.25rem;">
                                <div>
                                    <strong style="font-size: 0.92rem; color: #1a1618; display: block;">Latest Verified Backup Snapshot</strong>
                                    <span style="font-size: 0.78rem; color: #64748b;">Created Today at 04:00 AM PHT • Size: <strong>14.8 MB</strong> • Cryptographic Hash: <code>f8a29b...41e0</code></span>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="org-settings-save-btn" onclick="triggerManualBackup()">
                                        <i class="bi bi-database-fill-up"></i> Create Snapshot Now
                                    </button>
                                </div>
                            </div>

                            <div class="org-settings-toggle-row" style="margin-top: 1rem;">
                                <div>
                                    <strong class="org-settings-toggle-title">Automated Daily Cloud Sync (00:00 PHT)</strong>
                                    <p class="org-settings-toggle-desc">Replicate encrypted ledger state to redundant institutional offsite storage.</p>
                                </div>
                                <label class="org-settings-switch">
                                    <input type="checkbox" checked onchange="toggleSettingState('Cloud Backup', this.checked)">
                                    <span class="org-settings-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Export Data & Activity Logs --}}
                    <div class="org-settings-card">
                        <div class="org-settings-card-header">
                            <div class="org-settings-card-icon"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
                            <div>
                                <h4 class="org-settings-card-title">Export Data &amp; Activity Ledger</h4>
                                <p class="org-settings-card-desc">Download comprehensive data packages and tamper-evident audit trails.</p>
                            </div>
                        </div>
                        <div class="org-settings-card-body">
                            <div class="org-settings-grid-2">
                                <div class="org-settings-export-box">
                                    <i class="bi bi-file-earmark-excel text-success" style="font-size: 1.75rem;"></i>
                                    <div>
                                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Master Organization Registry</strong>
                                        <span style="font-size: 0.74rem; color: #64748b;">Complete roster of 42 accredited student organizations (.XLSX)</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" onclick="exportDataPackage('Organization Roster')">Export</button>
                                </div>

                                <div class="org-settings-export-box">
                                    <i class="bi bi-file-earmark-pdf text-danger" style="font-size: 1.75rem;"></i>
                                    <div>
                                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Annual Accomplishment Dossier</strong>
                                        <span style="font-size: 0.74rem; color: #64748b;">Signed executive summary of all AY activity completions (.PDF)</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" onclick="exportDataPackage('Accomplishment Dossier')">Export</button>
                                </div>

                                <div class="org-settings-export-box">
                                    <i class="bi bi-award text-primary" style="font-size: 1.75rem;"></i>
                                    <div>
                                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">TOSA Matrix &amp; Criteria Manifest</strong>
                                        <span style="font-size: 0.74rem; color: #64748b;">Official 8 requirements &amp; applicant evaluation logs (.CSV)</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" onclick="exportDataPackage('TOSA Manifest')">Export</button>
                                </div>

                                <div class="org-settings-export-box">
                                    <i class="bi bi-journal-code" style="font-size: 1.75rem; color: #8b1828;"></i>
                                    <div>
                                        <strong style="font-size: 0.88rem; color: #1a1618; display: block;">Cryptographic Audit Trail (Activity Logs)</strong>
                                        <span style="font-size: 0.74rem; color: #64748b;">Immutable SHA-256 ledger of all officer actions (.JSON &amp; PDF)</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" onclick="exportDataPackage('Audit Logs')">Export</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </main>
        </div>

        {{-- Settings Footer Actions --}}
        <div class="org-settings-footer">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #64748b;">
                <i class="bi bi-info-circle"></i>
                <span>Changes made here take effect immediately across your active session.</span>
            </div>
            <div style="display: flex; gap: 0.6rem;">
                <button type="button" class="org-settings-btn-subtle" onclick="closeSettingsModal()">Close</button>
                <button type="button" class="org-settings-save-btn" onclick="saveAllSettings()">
                    <i class="bi bi-check-circle-fill"></i> Save All Settings
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Add User Sub-Modal --}}
<dialog id="settingsAddUserModal" class="org-settings-submodal" style="max-width: 480px;">
    <div style="padding: 1.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #fdf0f2; color: #8b1828; display: grid; place-items: center; font-size: 1.1rem;">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #1a1618;">Invite Officer Account</h4>
            </div>
            <button type="button" onclick="document.getElementById('settingsAddUserModal').close()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        <form onsubmit="handleAddUserSubmit(event)" style="display: grid; gap: 0.85rem;">
            <div class="org-settings-field">
                <label class="org-settings-label">Officer Full Name</label>
                <input type="text" id="newOfficerName" class="org-settings-input" required placeholder="e.g. Prof. Juan Dela Cruz">
            </div>
            <div class="org-settings-field">
                <label class="org-settings-label">Official G-Suite Email</label>
                <input type="email" id="newOfficerEmail" class="org-settings-input" required placeholder="e.g. juan.delacruz@g.batstate-u.edu.ph">
            </div>
            <div class="org-settings-field">
                <label class="org-settings-label">Assigned Desk / Role</label>
                <select id="newOfficerRole" class="org-settings-select">
                    <option value="OSO Review Officer">OSO Review Officer (Triage)</option>
                    <option value="SDO SDG Reviewer">SDO SDG Reviewer</option>
                    <option value="OVCAA Final Endorser">OVCAA Final Endorser</option>
                    <option value="OSO Directorate Head">OSO Directorate Head (Admin)</option>
                </select>
            </div>
            <div class="org-settings-field">
                <label class="org-settings-label">TOSA Module Clearance</label>
                <select id="newOfficerTosaClearance" class="org-settings-select">
                    <option value="No Access">No Access</option>
                    <option value="Level 1 Read-only">Level 1 Read-only</option>
                    <option value="Level 2 Evaluator">Level 2 Evaluator</option>
                    <option value="Level 3 Master">Level 3 Master</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem;">
                <button type="button" class="org-settings-btn-subtle" onclick="document.getElementById('settingsAddUserModal').close()">Cancel</button>
                <button type="submit" class="org-settings-save-btn">Create Officer Account</button>
            </div>
        </form>
    </div>
</dialog>

{{-- Settings Toast Notification Container --}}
<div id="settingsToastContainer" class="org-settings-toast-container"></div>

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
    height: 88vh;
    max-height: 820px;
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

.org-settings-version-tag {
    font-size: 0.72rem;
    font-weight: 700;
    color: #16a34a;
    background: #dcfce7;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
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
    padding: 1.75rem 2rem;
    overflow-y: auto;
    background: #ffffff;
}

.org-settings-panel {
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
}

.org-settings-submodal::backdrop {
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(4px);
}

@media (max-width: 768px) {
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
     Settings Interactive Engine (Front-End Only)
     ========================================================================= --}}
<script>
    // Tab Switching
    function switchSettingsTab(tabKey) {
        const tabs = ['general', 'account', 'security', 'notifications', 'users', 'preferences', 'records'];
        tabs.forEach(t => {
            const panel = document.getElementById(`setPanel-${t}`);
            const btn = document.getElementById(`setNavBtn-${t}`);
            if (panel) panel.classList.toggle('is-active', t === tabKey);
            if (btn) btn.classList.toggle('is-active', t === tabKey);
        });
    }

    // Open and Close Modal
    function openSettingsModal(defaultTab = 'general') {
        closeOrgUserDropdown();
        const modal = document.getElementById('orgSettingsModal');
        if (modal) {
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            switchSettingsTab(defaultTab);
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSettingsModal() {
        const modal = document.getElementById('orgSettingsModal');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    }

    // Global Search across settings
    function filterSettingsSearch(query) {
        const q = query.toLowerCase().trim();
        if (!q) {
            document.querySelectorAll('.org-settings-card').forEach(c => c.style.display = 'block');
            return;
        }

        const tabs = ['general', 'account', 'security', 'notifications', 'users', 'preferences', 'records'];
        let matchedTab = null;

        tabs.forEach(t => {
            const panel = document.getElementById(`setPanel-${t}`);
            if (!panel) return;
            const cards = panel.querySelectorAll('.org-settings-card');
            let panelHasMatch = false;

            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(q)) {
                    card.style.display = 'block';
                    panelHasMatch = true;
                } else {
                    card.style.display = 'none';
                }
            });

            if (panelHasMatch && !matchedTab) {
                matchedTab = t;
            }
        });

        if (matchedTab) {
            switchSettingsTab(matchedTab);
        }
    }

    // Save Handlers
    function saveSettingsSection(sectionName) {
        showSettingsToast(`⚙️ ${sectionName} settings successfully saved to session!`, 'success');
    }

    function saveAllSettings() {
        showSettingsToast('✅ All OrgChain system & office settings updated and synchronized!', 'success');
        setTimeout(() => closeSettingsModal(), 600);
    }

    function toggleSettingState(settingName, isChecked) {
        showSettingsToast(`${settingName} has been ${isChecked ? 'Enabled' : 'Disabled'}.`, 'info');
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
        if (input.type === 'password') {
            input.type = 'text';
            btnEl.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } else {
            input.type = 'password';
            btnEl.innerHTML = '<i class="bi bi-eye"></i>';
        }
    }

    function handlePasswordUpdate(e) {
        e.preventDefault();
        const cur = document.getElementById('setCurrentPassword').value;
        const nw = document.getElementById('setNewPassword').value;
        const cf = document.getElementById('setConfirmPassword').value;

        if (!nw || nw !== cf) {
            showSettingsToast('Passwords do not match or empty. Please check.', 'info');
            return;
        }
        showSettingsToast('Account password successfully updated with cryptographic salting!', 'success');
        document.getElementById('setCurrentPassword').value = '';
        document.getElementById('setNewPassword').value = '';
        document.getElementById('setConfirmPassword').value = '';
        checkPasswordStrength('');
    }

    function updateMasterPin() {
        const pin = document.getElementById('setNewPinInput').value;
        if (pin.length !== 4) {
            showSettingsToast('Please enter a valid 4-digit security PIN.', 'info');
            return;
        }
        showSettingsToast(`Master Security PIN successfully updated to: ${pin}`, 'success');
        document.getElementById('setNewPinInput').value = '';
    }

    // Logo Upload Trigger
    function triggerLogoUpload() {
        document.getElementById('settingsLogoFileInput')?.click();
    }

    function handleLogoChange(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('settingsLogoPreview');
                if (img) img.src = e.target.result;
                showSettingsToast('New institutional emblem uploaded successfully!', 'success');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function resetLogoToDefault() {
        const img = document.getElementById('settingsLogoPreview');
        if (img) img.src = "{{ asset('Orgchain logo.png') }}";
        showSettingsToast('Institutional logo reset to default emblem.', 'info');
    }

    // Sub-modals & Exports
    function openAddUserModal() {
        document.getElementById('settingsAddUserModal')?.showModal();
    }

    function editUserModal(userName) {
        showSettingsToast(`Editing permissions for: ${userName}`, 'info');
    }

    function handleAddUserSubmit(e) {
        e.preventDefault();
        const name = document.getElementById('newOfficerName').value;
        const email = document.getElementById('newOfficerEmail').value;
        const role = document.getElementById('newOfficerRole').value;
        const tosa = document.getElementById('newOfficerTosaClearance').value;

        const tbody = document.getElementById('settingsUserListTbody');
        if (tbody) {
            const initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const tosaPill = tosa === 'No Access' 
                ? '<span class="org-settings-pill-gray">No Access</span>'
                : `<span class="org-settings-pill-green"><i class="bi bi-shield-check"></i> ${tosa}</span>`;

            const row = `
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <div class="org-settings-avatar-sm" style="background: #fdf0f2; color: #8b1828;">${initials}</div>
                            <div>
                                <strong style="display: block; font-size: 0.85rem; color: #1a1618;">${name}</strong>
                                <small style="font-size: 0.72rem; color: #64748b;">${email}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="org-settings-role-badge is-eval">${role}</span></td>
                    <td>${tosaPill}</td>
                    <td><span class="org-settings-status-active"><i class="bi bi-check2"></i> Enabled</span></td>
                    <td style="font-size: 0.78rem; color: #64748b;">Just created</td>
                    <td style="text-align: center;">
                        <button type="button" class="org-settings-icon-btn" onclick="editUserModal('${name}')" title="Edit Permissions"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        }

        document.getElementById('settingsAddUserModal')?.close();
        showSettingsToast(`Officer account for ${name} successfully created!`, 'success');
    }

    function triggerManualBackup() {
        showSettingsToast('Creating encrypted institutional snapshot (AY2026_MASTER.sql.gz)...', 'info');
        setTimeout(() => {
            showSettingsToast('Snapshot created successfully & verified with SHA-256 root hash!', 'success');
        }, 1200);
    }

    function exportDataPackage(type) {
        showSettingsToast(`Generating and exporting "${type}" package (Validated on Chain)...`, 'success');
    }

    function applyLiveTheme(themeKey) {
        showSettingsToast(`Theme preference updated to: ${themeKey}`, 'info');
    }

    // Settings Toast helper
    function showSettingsToast(msg, type = 'info') {
        const container = document.getElementById('settingsToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `org-settings-toast is-${type}`;
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill';
        toast.innerHTML = `<i class="bi ${icon}"></i> <span>${msg}</span>`;

        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(12px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3200);
    }

    // Close settings on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSettingsModal();
        }
    });
</script>
