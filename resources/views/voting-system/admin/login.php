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
<style>
    .site-navbar, .site-footer { display: none !important; }
    body, main { padding: 0 !important; margin: 0 !important; }

    body {
        background:
            radial-gradient(900px 480px at 8% -8%, rgba(196, 59, 82, 0.14), transparent 55%),
            radial-gradient(720px 420px at 100% 18%, rgba(155, 27, 48, 0.1), transparent 50%),
            radial-gradient(600px 380px at 50% 100%, rgba(111, 16, 32, 0.06), transparent 55%),
            linear-gradient(180deg, #ffffff 0%, #faf6f7 48%, #f7f1f2 100%) !important;
        min-height: 100vh;
    }

    .login-portal-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background: transparent;
    }

    .login-portal-wrapper .ballot-topbar {
        position: sticky !important;
        top: 0.7rem !important;
        z-index: 50;
        margin: 0.7rem auto 0 !important;
        width: calc(100% - 1.6rem) !important;
        max-width: 1240px;
        height: auto !important;
        min-height: 58px !important;
        padding: 0.45rem 0.85rem !important;
        border-radius: 999px !important;
        border: 1px solid rgba(255, 255, 255, 0.85) !important;
        background: rgba(255, 255, 255, 0.55) !important;
        backdrop-filter: blur(18px) saturate(1.6) !important;
        -webkit-backdrop-filter: blur(18px) saturate(1.6) !important;
        box-shadow:
            0 12px 32px rgba(74, 10, 21, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
        color: #1f1817 !important;
    }

    .login-portal-wrapper .ballot-topbar .sys-title,
    .login-portal-wrapper .ballot-topbar .sys-subtitle {
        color: #1f1817 !important;
    }

    .login-portal-wrapper .ballot-topbar .sys-subtitle {
        opacity: 0.65;
    }

    .login-split-layout {
        flex-grow: 1;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(340px, 420px);
        gap: clamp(2rem, 5vw, 4.5rem);
        align-items: center;
        width: calc(100% - 1.6rem);
        max-width: 1240px;
        margin: 0 auto;
        padding: clamp(1.25rem, 3vw, 2.5rem) clamp(0.75rem, 2vw, 1.5rem) clamp(2rem, 4vw, 3rem);
        min-height: calc(100vh - 90px);
    }

    .login-info-panel {
        padding: clamp(0.5rem, 2vw, 1rem);
        display: flex;
        align-items: center;
        justify-content: flex-start;
        position: relative;
        overflow: visible;
        background: transparent;
    }

    .info-content {
        max-width: 640px;
        position: relative;
        z-index: 5;
        width: 100%;
    }

    .login-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 1.1rem;
        padding: 0.4rem 0.85rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.55);
        backdrop-filter: blur(12px) saturate(1.4);
        -webkit-backdrop-filter: blur(12px) saturate(1.4);
        color: #6f1020;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        box-shadow: 0 8px 20px rgba(74, 10, 21, 0.08);
    }

    .info-content h1 {
        font-family: 'Outfit', sans-serif;
        font-weight: 900;
        font-size: clamp(2rem, 3.2vw, 2.75rem);
        line-height: 1.06;
        margin: 0;
        color: #6f1020;
        letter-spacing: -0.02em;
    }

    .info-content .lead {
        font-size: 1.02rem;
        color: rgba(31, 24, 23, 0.72);
        font-weight: 500;
        line-height: 1.6;
        margin: 1.25rem 0 1.5rem;
        max-width: 500px;
    }

    .login-feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
    }

    .login-feature-list li {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        padding: 1rem 1.05rem;
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.85);
        background: rgba(255, 255, 255, 0.48);
        backdrop-filter: blur(14px) saturate(1.45);
        -webkit-backdrop-filter: blur(14px) saturate(1.45);
        box-shadow:
            0 10px 28px rgba(74, 10, 21, 0.07),
            inset 0 1px 0 rgba(255, 255, 255, 0.85);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .login-feature-list li:hover {
        transform: translateY(-2px);
        box-shadow:
            0 14px 32px rgba(74, 10, 21, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
    }

    .login-feature-list .feat-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(145deg, rgba(155, 27, 48, 0.12), rgba(111, 16, 32, 0.08));
        color: #9b1b30;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.15rem;
        border: 1px solid rgba(155, 27, 48, 0.12);
    }

    .login-feature-list span {
        font-size: 0.82rem;
        font-weight: 800;
        color: #1f1817;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .login-feature-list p {
        font-size: 0.82rem;
        color: rgba(31, 24, 23, 0.62);
        margin: 0;
        line-height: 1.45;
        font-weight: 500;
    }

    .info-content h1 {
        max-width: 520px;
    }

    .login-form-panel {
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 0;
        box-shadow: none;
    }

    .form-container {
        width: 100%;
        max-width: 420px;
        margin-left: auto;
    }

    .login-glass-card {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.62);
        backdrop-filter: blur(22px) saturate(1.55);
        -webkit-backdrop-filter: blur(22px) saturate(1.55);
        box-shadow:
            0 24px 60px rgba(74, 10, 21, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
        padding: 1.5rem 1.4rem 1.25rem;
    }

    .login-glass-card::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.95), transparent);
        pointer-events: none;
    }

    .login-card-header {
        margin-bottom: 1.15rem;
        position: relative;
        z-index: 1;
    }

    .login-card-header .eyebrow {
        margin: 0 0 0.45rem;
        color: #9b1b30;
        font-weight: 850;
        font-size: 0.72rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .login-card-header h2 {
        font-family: 'Outfit', sans-serif;
        font-weight: 900;
        color: #1f1817;
        font-size: 1.5rem;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .login-card-header p {
        color: rgba(31, 24, 23, 0.62);
        font-size: 0.86rem;
        margin: 0.4rem 0 0;
        font-weight: 500;
        line-height: 1.45;
    }

    .login-otp-note {
        background: rgba(155, 27, 48, 0.08);
        border: 1px solid rgba(155, 27, 48, 0.16);
        border-radius: 14px;
        padding: 0.85rem 1rem;
        margin-bottom: 1.2rem;
        color: #6f1020;
        font-weight: 700;
        font-size: 0.86rem;
        line-height: 1.4;
    }

    .login-field-label {
        font-weight: 800;
        color: #1f1817;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.5rem;
        display: block;
    }

    .login-input-wrap {
        position: relative;
    }

    .login-input-wrap .login-input-icon {
        position: absolute;
        left: 1.05rem;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(31, 24, 23, 0.38);
        z-index: 10;
        pointer-events: none;
    }

    .login-glass-card .form-control {
        padding: 0.72rem 0.9rem 0.72rem 2.6rem !important;
        border-radius: 14px !important;
        border: 1px solid rgba(255, 255, 255, 0.95) !important;
        background: rgba(255, 255, 255, 0.72) !important;
        backdrop-filter: blur(8px);
        font-weight: 600 !important;
        color: #1f1817 !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 4px 14px rgba(74, 10, 21, 0.04) !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
    }

    .login-glass-card .form-control:focus {
        border-color: rgba(155, 27, 48, 0.28) !important;
        box-shadow:
            0 0 0 4px rgba(155, 27, 48, 0.07),
            inset 0 1px 0 rgba(255, 255, 255, 0.95) !important;
        outline: none !important;
    }

    .login-glass-card #password.form-control {
        padding-right: 4.4rem !important;
    }

    .login-glass-card #code.form-control {
        font-size: 1.2rem !important;
        letter-spacing: 0.18em !important;
        font-weight: 800 !important;
    }

    .login-toggle-pw {
        position: absolute;
        right: 0.65rem;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(155, 27, 48, 0.08);
        border: 1px solid rgba(155, 27, 48, 0.12);
        border-radius: 999px;
        color: #9b1b30;
        cursor: pointer;
        z-index: 10;
        padding: 0.35rem 0.7rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        transition: background 0.15s ease;
    }

    .login-toggle-pw:hover {
        background: rgba(155, 27, 48, 0.14);
    }

    .login-auth-btn {
        width: 100%;
        padding: 0.85rem 1rem !important;
        font-size: 0.95rem !important;
        border-radius: 999px !important;
        font-weight: 850 !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        background: linear-gradient(135deg, #9b1b30, #6f1020) !important;
        color: #fff !important;
        box-shadow:
            0 14px 32px rgba(74, 10, 21, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.28) !important;
        transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease !important;
    }

    .login-auth-btn:not(:disabled):hover {
        transform: translateY(-1px);
        box-shadow:
            0 18px 38px rgba(74, 10, 21, 0.34),
            inset 0 1px 0 rgba(255, 255, 255, 0.35) !important;
    }

    .login-auth-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .login-secondary-actions {
        display: flex;
        gap: 0.65rem;
        margin-top: 0.85rem;
    }

    .login-secondary-actions .btn {
        border-radius: 999px !important;
        font-weight: 800 !important;
        font-size: 0.82rem !important;
        padding: 0.7rem 0.85rem !important;
        border: 1px solid rgba(155, 27, 48, 0.22) !important;
        background: rgba(255, 255, 255, 0.55) !important;
        color: #6f1020 !important;
        backdrop-filter: blur(8px);
    }

    .login-secondary-actions .btn:hover {
        background: rgba(255, 255, 255, 0.85) !important;
        color: #4a0a15 !important;
    }

    .login-secure-meta {
        text-align: center;
        margin-top: 1.35rem;
        padding-top: 1.1rem;
        border-top: 1px solid rgba(155, 27, 48, 0.1);
    }

    .login-secure-meta .secure-label {
        font-size: 0.72rem;
        color: rgba(31, 24, 23, 0.5);
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.45rem;
    }

    .login-secure-meta a {
        color: rgba(31, 24, 23, 0.55);
        font-size: 0.82rem;
        text-decoration: none;
        font-weight: 650;
    }

    .login-secure-meta a:hover {
        color: #9b1b30;
    }

    .login-footer-note {
        margin-top: 1.35rem;
        text-align: center;
        color: rgba(31, 24, 23, 0.45);
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.5;
    }

    .animate-login-entry {
        animation: loginSlideUp 0.75s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .delay-200 { animation-delay: 0.15s; }

    @keyframes loginSlideUp {
        from { opacity: 0; transform: translateY(22px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .spin {
        animation: rotate 1s linear infinite;
        display: inline-block;
    }

    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    input::-ms-reveal,
    input::-ms-clear {
        display: none !important;
    }

    @media (max-height: 760px) and (min-width: 1101px) {
        .login-feature-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .login-feature-list li {
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.9rem 0.45rem 0.5rem;
            border-radius: 999px;
        }

        .login-feature-list li:hover {
            transform: none;
        }

        .login-feature-list .feat-icon {
            width: 30px;
            height: 30px;
            font-size: 0.92rem;
            border-radius: 50%;
        }

        .login-feature-list p {
            display: none;
        }

        .info-content .lead {
            margin-bottom: 1.35rem;
        }
    }

    @media (max-width: 1100px) {
        .login-split-layout {
            grid-template-columns: 1fr;
            min-height: auto;
            padding-top: 1rem;
        }
        .login-info-panel {
            padding: 0.25rem 0 0;
        }
        .info-content h1 {
            max-width: 620px;
        }
        .login-feature-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }
        .login-feature-list li {
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.9rem 0.45rem 0.5rem;
            border-radius: 999px;
        }
        .login-feature-list li:hover {
            transform: none;
        }
        .login-feature-list .feat-icon {
            width: 30px;
            height: 30px;
            font-size: 0.92rem;
            border-radius: 50%;
        }
        .login-feature-list p {
            display: none;
        }
        .info-content .lead {
            margin-bottom: 1.25rem;
            max-width: 720px;
        }
        .login-form-panel {
            justify-content: center;
        }
        .form-container {
            max-width: 520px;
            margin: 0 auto;
        }
    }

    @media (max-width: 640px) {
        .login-portal-wrapper {
            min-width: 0;
            overflow-x: hidden;
        }

        .login-portal-wrapper .ballot-topbar {
            top: 0.5rem !important;
            margin: 0.5rem auto 0 !important;
            width: calc(100% - 1rem) !important;
            border-radius: 22px !important;
            padding: 0.4rem 0.65rem !important;
        }

        .login-portal-wrapper .topbar-titles .sys-subtitle {
            display: none;
        }

        .login-split-layout {
            width: calc(100% - 1.6rem);
            gap: 1rem;
            padding-bottom: 1.5rem;
        }

        .info-content h1 {
            font-size: clamp(1.9rem, 9vw, 2.45rem);
        }

        .info-content .lead {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .login-glass-card {
            padding: 1.4rem 1.15rem 1.25rem;
            border-radius: 24px;
        }

        .login-secondary-actions {
            flex-direction: column;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .animate-login-entry {
            animation: none;
        }
    }
</style>

<div class="login-portal-wrapper">
    <header class="ballot-topbar">
        <div class="topbar-left">
            <div class="topbar-logo-box">
                <img src="<?= e(voting_asset('img/orgchain-logo.png')) ?>" alt="OrgChain" class="ssc-logo-img" onerror="this.src='<?= e(voting_asset('img/orgchain-logo.png')) ?>'">
            </div>
            <div class="topbar-titles">
                <span class="sys-title">OrgChain Official Voting</span>
                <span class="sys-subtitle">Admin Control Center</span>
            </div>
        </div>
        <div class="topbar-right"></div>
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
                <div style="margin-bottom: 1rem;">
                    <?php require base_path('resources/views/voting-system/partials/flash.php'); ?>
                </div>

                <div class="login-glass-card">
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

                <div class="login-footer-note">
                    &copy; <?= date('Y') ?> OrgChain Elections<br>
                    <span style="opacity: 0.75;">Batangas State University</span>
                </div>
            </div>
        </section>
    </main>
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
