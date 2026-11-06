@php
    $isOso = ($office->office_role ?? '') === 'oso';
    $settings = $officeSettings ?? [];
    $general = $settings['general'] ?? [];
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
                    <p class="org-settings-subtitle">{{ $isOso ? 'Manage institutional details, office profile, and data exports.' : 'Manage your office profile and account password.' }}</p>
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

                <div class="org-settings-nav-group-label" style="margin-top: 0.85rem;">MANAGEMENT</div>
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
                                    <p style="font-size: 0.78rem; color: #64748b; margin: 0 0 0.75rem;">PNG or JPEG, up to 2 MB. Choose a file, then upload to save the logo.</p>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button type="button" class="org-settings-btn-outline" onclick="triggerLogoUpload()">
                                            <i class="bi bi-folder2-open"></i> Choose Logo
                                        </button>
                                        <input type="file" id="settingsLogoFileInput" style="display: none;" accept="image/png,image/jpeg,.png,.jpg,.jpeg" onchange="handleLogoChange(this)">
                                        <button type="button" id="settingsLogoUploadButton" class="org-settings-btn-outline" onclick="uploadSettingsLogo()" hidden disabled>Upload selected logo</button>
                                        <span id="settingsLogoSelectedName" class="org-settings-help" aria-live="polite"></span>
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
                            <form method="post" data-confirm="manual" onsubmit="handlePasswordUpdate(event)" style="display: grid; gap: 1rem;">
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
                                        <span style="font-size: 0.74rem; color: #64748b;">Applicants, stage, and requirement counts (.CSV). Available to authorized TOSA accounts.</span>
                                    </div>
                                    <button type="button" class="org-settings-btn-subtle" title="Requires TOSA clearance" onclick="exportDataPackage('TOSA Manifest')">Export</button>
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
                <span>{{ $isOso ? 'Save All saves General and Profile only. Passwords and logos save separately.' : 'Profile changes and passwords use their own save buttons.' }}</span>
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
.org-settings-dialog-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: flex-end; margin-top: 0.5rem; }
.org-settings-user-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.4rem; width: 160px; }
.org-settings-user-actions button { justify-content: center; min-height: 36px; padding: 0.4rem 0.35rem; border-radius: 8px; font-size: 0.72rem; text-align: center; line-height: 1.2; }
.org-settings-user-actions button[data-account-action="status"] { color: #8b1828; border-color: #e7c6cc; }
.org-settings-user-actions button:disabled { cursor: not-allowed; opacity: 0.5; }
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
    #setPanel-users .org-settings-table,
    #setPanel-users .org-settings-table tbody { display: block; width: 100%; }
    #setPanel-users .org-settings-table thead { display: none; }
    #setPanel-users .org-settings-table tr[data-settings-user-id] {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem 1rem;
        padding: 1rem;
        border-bottom: 1px solid #f0e6e8;
    }
    #setPanel-users .org-settings-table tr[hidden] { display: none !important; }
    #setPanel-users .org-settings-table tr[data-settings-user-id] td {
        display: block;
        min-width: 0;
        padding: 0;
        border: 0;
    }
    #setPanel-users .org-settings-table tr[data-settings-user-id] td:first-child,
    #setPanel-users .org-settings-table tr[data-settings-user-id] td:last-child { grid-column: 1 / -1; }
    #setPanel-users .org-settings-table tr[data-settings-user-id] td:not(:first-child)::before {
        display: block;
        margin-bottom: 0.3rem;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 700;
    }
    #setPanel-users .org-settings-table td:nth-child(2)::before { content: 'Role / Office'; }
    #setPanel-users .org-settings-table td:nth-child(3)::before { content: 'TOSA Clearance'; }
    #setPanel-users .org-settings-table td:nth-child(4)::before { content: 'Account Status'; }
    #setPanel-users .org-settings-table td:nth-child(5)::before { content: 'Last Updated'; }
    #setPanel-users .org-settings-table td:nth-child(6)::before { content: 'Actions'; }
    #setPanel-users .org-settings-user-actions { width: 100%; }
}
</style>

{{-- =========================================================================
     Settings Interactive Engine (OSO backend-connected)
     ========================================================================= --}}
<script>
    const settingsIsOso = @json($isOso);
    const settingsOfficerId = @json($office->id);
    let settingsReturnFocus = null;
    let settingsBodyOverflow = '';
    let settingsMutationPending = false;

    function availableSettingsTabs() {
        return settingsIsOso ? ['general', 'account', 'records'] : ['account'];
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
            button.setAttribute('aria-label', 'Show password');
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
        ['setCurrentPassword', 'setNewPassword', 'setConfirmPassword'].forEach(clearSettingsCredential);
        checkPasswordStrength('');
        if (settingsReturnFocus?.isConnected) settingsReturnFocus.focus();
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
        logo: @json(route('office.settings.logo')),
        logoReset: @json(route('office.settings.logo.reset')),
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

    async function runSettingsMutation(action, confirmationMessage = 'Save these settings changes?') {
        if (settingsMutationPending) {
            showSettingsToast('Please wait for the current settings change to finish.', 'info');
            return;
        }
        if (!await window.OrgActionConfirmations.confirm(confirmationMessage)) return;
        if (settingsMutationPending) return;
        settingsMutationPending = true;
        const controls = [...document.querySelectorAll('#orgSettingsModal button[onclick*="save"], #orgSettingsModal button[onclick*="resetLogo"], #orgSettingsModal button[onclick*="triggerLogo"], #settingsLogoUploadButton, [data-account-action], #setPanel-users button[type="submit"], #newOrgRetryBtn, #orgSettingsModal button[type="submit"], .org-settings-submodal button')]
            .filter(button => !button.disabled);
        controls.forEach(button => button.disabled = true);
        try {
            return await action();
        } catch (error) {
            const dialog = document.querySelector('.org-settings-submodal[open]');
            if (dialog && typeof showSettingsAccountError === 'function') showSettingsAccountError(dialog, error.message || 'The settings request could not be completed.');
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
        return data;
    }

    function saveSettingsSection(sectionName) {
        const section = sectionName.toLowerCase();
        if (section === 'account') return saveOsoAccount();
        return runSettingsMutation(async () => {
            const data = await persistSettingsSection(section);
            showSettingsToast(data.message || `${sectionName} settings saved.`, 'success');
        }, `Save ${sectionName.toLowerCase()} settings?`);
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
            if (typeof upsertOsoUser === 'function' && typeof settingsAccounts !== 'undefined' && settingsAccounts.has(Number(settingsOfficerId))) {
                upsertOsoUser({ ...settingsAccounts.get(Number(settingsOfficerId)), ...user });
            }
        }
        return data;
    }

    function saveOsoAccount() {
        return runSettingsMutation(async () => {
            const data = await persistSettingsProfile();
            showSettingsToast(data.message || 'Account profile saved.', 'success');
        }, 'Save your account profile changes?');
    }

    function saveAllSettings() {
        return runSettingsMutation(async () => {
            const saved = [];
            const sections = settingsIsOso ? ['general', 'account'] : ['account'];
            for (const section of sections) {
                try {
                    if (section === 'account') await persistSettingsProfile();
                    else await persistSettingsSection(section);
                    saved.push(section === 'account' ? 'Profile' : 'General');
                } catch (error) {
                    const prefix = saved.length ? `Partially saved: ${saved.join(', ')}. ` : 'No settings were saved. ';
                    throw new Error(`${prefix}${section === 'account' ? 'Profile' : section} was not saved: ${error.message} Remaining sections were not saved.`);
                }
            }
            showSettingsToast(`${saved.join(' and ')} saved. Passwords were not changed.`, 'success');
        }, 'Save all profile and general settings changes?');
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
        btnEl.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
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
        }, 'Update your account password?');
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
        const button = document.getElementById('settingsLogoUploadButton');
        const name = document.getElementById('settingsLogoSelectedName');
        button.hidden = true;
        button.disabled = true;
        name.textContent = '';
        if (!file) return;
        if (!/\.(png|jpe?g)$/i.test(file.name) || file.size > 2048 * 1024) {
            input.value = '';
            showSettingsToast('Choose a PNG or JPEG logo no larger than 2 MB.', 'error');
            return;
        }
        name.textContent = file.name;
        button.hidden = false;
        button.disabled = false;
    }

    function uploadSettingsLogo() {
        const input = document.getElementById('settingsLogoFileInput');
        const file = input.files?.[0];
        if (!file) return;
        return runSettingsMutation(async () => {
            try {
                const formData = new FormData();
                formData.append('logo', file);
                const data = await osoSettingsRequest(osoSettingsRoutes.logo, { method: 'POST', body: formData });
                if (data.url) applySettingsLogo(`${data.url}?v=${Date.now()}`);
                showSettingsToast(data.message || 'Institutional logo uploaded.', 'success');
            } finally {
                input.value = '';
                handleLogoChange(input);
            }
        }, 'Upload this logo as the official OrgChain emblem?');
    }

    function resetLogoToDefault() {
        return runSettingsMutation(async () => {
            const data = await osoSettingsRequest(osoSettingsRoutes.logoReset, { method: 'DELETE' });
            applySettingsLogo(@json(asset('Orgchain logo.png')));
            showSettingsToast(data.message || 'Institutional logo reset.', 'success');
        }, 'Reset the OSO logo to the default OrgChain emblem?');
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
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
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
