<?php
    $pendingAdminOtp = $_SESSION['pending_admin_otp'] ?? null;
    $pendingEmail = (string) ($pendingAdminOtp['email'] ?? '');
    $pendingEmailLabel = $pendingEmail;
    $pendingDisplayCode = preg_replace('/\D+/', '', (string) ($pendingAdminOtp['display_code'] ?? '')) ?: '------';

    if ($pendingEmail !== '' && str_contains($pendingEmail, '@')) {
        [$pendingName, $pendingDomain] = explode('@', $pendingEmail, 2);
        $pendingEmailLabel = substr($pendingName, 0, 2) . str_repeat('*', max(2, strlen($pendingName) - 2)) . '@' . $pendingDomain;
    }
?>

<div class="login-portal-wrapper">
    <header class="login-topbar">
        <div class="topbar-left">
            <div class="topbar-logo-box">
                <img src="<?= e(voting_asset('img/orgchain-logo.png')) ?>" alt="OrgChain" class="ssc-logo-img" onerror="this.src='<?= e(voting_asset('img/orgchain-logo.png')) ?>'">
            </div>
            <div class="topbar-titles">
                <span class="sys-title">OrgChain Official Voting</span>
                <span class="sys-subtitle">Admin Control Center</span>
            </div>
        </div>
        <div class="topbar-right" hidden></div>
    </header>

    <main class="login-split-layout">
        <section class="login-info-panel">
            <div class="info-content animate-login-entry">
                <div class="login-kicker"><i class="bi bi-shield-lock"></i> Staff Access Portal</div>

                <h1>Secure Election Management</h1>

                <p class="lead">Welcome to the OrgChain Official Voting Control Center. Manage candidates, monitor real-time participation, and ensure the integrity of the student voice.</p>

                <ul class="login-feature-list">
                    <li>
                        <div class="feat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <span>Real-time Analytics</span>
                        <p>Monitor college participation and vote tallies as they happen.</p>
                    </li>
                    <li>
                        <div class="feat-icon"><i class="bi bi-people"></i></div>
                        <span>Voter Control</span>
                        <p>Manage the official registry and SR-Code verifications securely.</p>
                    </li>
                    <li>
                        <div class="feat-icon"><i class="bi bi-shield-check"></i></div>
                        <span>Security First</span>
                        <p>End-to-end encryption for every ballot cast in the system.</p>
                    </li>
                    <li>
                        <div class="feat-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
                        <span>Instant Reports</span>
                        <p>Generate official OrgChain canvassing reports with one click.</p>
                    </li>
                </ul>
            </div>
        </section>

        <section class="login-form-panel">
            <div class="form-container animate-login-entry delay-200">
                <div class="login-glass-card">
                    <?php require base_path('resources/views/voting-system/partials/flash.php'); ?>

                    <div class="login-card-header">
                        <p class="eyebrow">Administrator Access</p>
                        <h2><?= $pendingAdminOtp ? 'Verify Number' : 'Sign In' ?></h2>
                        <p><?= $pendingAdminOtp ? 'Type the displayed 6-digit number to continue.' : 'Enter your official credentials to continue.' ?></p>
                    </div>

                    <?php if ($pendingAdminOtp): ?>
                        <form id="loginForm" method="post" action="<?= e(voting_url(admin_login_path())) ?>">
                            <?= voting_csrf_field() ?>
                            <input type="hidden" name="auth_step" value="verify_code">

                            <div class="login-otp-note">
                                <i class="bi bi-shield-check"></i>
                                Type the displayed number for <?= e($pendingEmailLabel) ?>. It expires in 10 minutes.
                            </div>

                            <div class="mb-4">
                                <div class="admin-displayed-code" aria-live="polite"><?= e($pendingDisplayCode) ?></div>
                                <label for="code" class="login-field-label">Enter Verification Number</label>
                                <div class="login-input-wrap">
                                    <span class="login-input-icon"><i class="bi bi-shield-lock"></i></span>
                                    <input id="code" class="form-control" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus
                                           placeholder="000000" autocomplete="one-time-code">
                                </div>
                            </div>

                            <button id="submitBtn" class="btn login-auth-btn" type="submit" disabled>
                                <span id="btnText"><i class="bi bi-check2-circle"></i> Verify Number</span>
                                <span id="btnLoader" style="display: none;"><i class="bi bi-arrow-repeat spin"></i> Checking...</span>
                            </button>
                        </form>

                        <div class="login-secondary-actions">
                            <form method="post" action="<?= e(voting_url(admin_login_path())) ?>" class="flex-fill">
                                <?= voting_csrf_field() ?>
                                <input type="hidden" name="auth_step" value="resend_code">
                                <button type="submit" class="btn w-100">Generate New Number</button>
                            </form>
                            <form method="post" action="<?= e(voting_url(admin_login_path())) ?>" class="flex-fill">
                                <?= voting_csrf_field() ?>
                                <input type="hidden" name="auth_step" value="cancel_code">
                                <button type="submit" class="btn w-100">Use another account</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form id="loginForm" method="post" action="<?= e(voting_url(admin_login_path())) ?>">
                            <?= voting_csrf_field() ?>
                            <input type="hidden" name="auth_step" value="credentials">

                            <div class="mb-4">
                                <label for="email" class="login-field-label">Admin Email</label>
                                <div class="login-input-wrap">
                                    <span class="login-input-icon"><i class="bi bi-envelope"></i></span>
                                    <input id="email" class="form-control" type="email" name="email" value="<?= e(voting_old('email')) ?>" required autofocus
                                           placeholder="admin@bsu.edu.ph" autocomplete="email">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="login-field-label">Security Password</label>
                                <div class="login-input-wrap">
                                    <span class="login-input-icon"><i class="bi bi-key"></i></span>
                                    <input id="password" class="form-control" type="password" name="password" required
                                           placeholder="••••••••" autocomplete="current-password">
                                    <button type="button" class="login-toggle-pw" onclick="togglePasswordVisibility()">
                                        <i class="bi bi-eye" id="toggleIcon"></i> <span id="toggleText">Show</span>
                                    </button>
                                </div>
                            </div>

                            <button id="submitBtn" class="btn login-auth-btn" type="submit" disabled>
                                <span id="btnText"><i class="bi bi-shield-lock-fill"></i> Authenticate</span>
                                <span id="btnLoader" style="display: none;"><i class="bi bi-arrow-repeat spin"></i> Verifying...</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="login-secure-meta">
                        <p class="secure-label">
                            <i class="bi bi-lock-fill" style="color: #22c55e;"></i> Secure Environment
                        </p>
                        <a href="#" onclick="alert('Please contact the OrgChain IT Helpdesk for credential recovery.'); return false;">
                            <i class="bi bi-question-circle"></i> Need help accessing?
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="login-footer-note">
        &copy; <?= date('Y') ?> OrgChain Elections
        <span>· Batangas State University</span>
    </footer>
</div>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        const toggleText = document.getElementById('toggleText');

        if (!passwordInput || !toggleIcon || !toggleText) {
            return;
        }

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            toggleText.innerText = 'Hide';
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            toggleText.innerText = 'Show';
        }
    }

    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const codeInput = document.getElementById('code');
    const submitBtn = document.getElementById('submitBtn');

    function validateForm() {
        if (!submitBtn) {
            return;
        }

        const credentialsReady = emailInput && passwordInput && emailInput.value.trim() !== '' && passwordInput.value.trim() !== '';
        const codeReady = codeInput && codeInput.value.replace(/\D/g, '').length === 6;

        if (credentialsReady || codeReady) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    if (emailInput) {
        emailInput.addEventListener('input', validateForm);
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', validateForm);
    }

    if (codeInput) {
        codeInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            validateForm();
        });
    }

    window.addEventListener('load', validateForm);

    if (loginForm) {
        loginForm.onsubmit = function () {
            const text = document.getElementById('btnText');
            const loader = document.getElementById('btnLoader');

            submitBtn.disabled = true;
            if (text) {
                text.style.display = 'none';
            }
            if (loader) {
                loader.style.display = 'inline-block';
            }
        };
    }
</script>
