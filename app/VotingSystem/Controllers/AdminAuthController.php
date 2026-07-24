<?php

namespace App\VotingSystem\Controllers;

use App\VotingSystem\Core\Auth;
use App\VotingSystem\Core\Controller;
use App\VotingSystem\Core\RateLimiter;
use App\VotingSystem\Core\SecurityGuard;
use App\VotingSystem\Models\AuditLog;
use App\VotingSystem\Models\AdminUser;

class AdminAuthController extends Controller
{
    private const OTP_EXPIRES_SECONDS = 600;
    private const OTP_RESEND_SECONDS = 60;

    public function login(): void
    {
        $this->sendNoStoreHeaders();
        $currentUser = Auth::user();

        if ($currentUser !== null && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            (new AuditLog())->record('admin_session_reset', 'Existing staff session was cleared from the private sign-in path.');
            Auth::logout();
            voting_flash('info', 'For security, please sign in again.');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requirePost();
            $this->rememberOldInput();
            $step = $_POST['auth_step'] ?? 'credentials';

            if ($step === 'cancel_code') {
                unset($_SESSION['pending_admin_otp']);
                voting_flash('info', 'Code verification was cancelled. Please sign in again.');
                $this->redirect(admin_login_path());
            }

            if ($step === 'verify_code') {
                $this->verifyCode();
                return;
            }

            if ($step === 'resend_code') {
                $this->resendCode();
                return;
            }

            $email = strtolower(trim($_POST['email'] ?? ''));
            $identifier = $this->rateLimitIdentifier($email);

            if (RateLimiter::tooManyAttempts('admin_login', $identifier, 5, 900)) {
                SecurityGuard::record('admin_login_rate_blocked', 'high', SecurityGuard::clientIp(), 'POST', voting_current_path(), 5, 'Admin credential rate limit exceeded.');
                voting_flash('error', 'Too many failed sign-in attempts. Please wait 15 minutes and try again.');
                $this->redirect(admin_login_path());
            }

            $user = Auth::validateCredentials($email, $_POST['password'] ?? '');

            if ($user !== null) {
                if (!$this->requiresEmailCode($user)) {
                    unset($_SESSION['pending_admin_otp'], $_SESSION['_old']);
                    RateLimiter::clear('admin_login', $identifier);
                    Auth::loginUser($user);
                    (new AuditLog())->record('admin_login', 'Canvassing officer signed in with email and password.');
                    $this->redirect($this->postLoginPath($user));
                }

                $this->issueCode($user);

                voting_flash('success', 'A 6-digit admin sign-in number was generated. Type the displayed number to continue.');
                $this->redirect(admin_login_path());
            }

            RateLimiter::hit('admin_login', $identifier, 900);
            SecurityGuard::record('admin_login_failed', 'medium', SecurityGuard::clientIp(), 'POST', voting_current_path(), 0, 'Invalid admin credentials submitted.');
            voting_flash('error', 'Invalid email or password.');
            $this->redirect(admin_login_path());
        }

        $this->view('admin/login', ['title' => 'Admin Login'], 'public');
    }

    public function logout(): void
    {
        $this->sendNoStoreHeaders();
        $this->requirePost();
        (new AuditLog())->record('admin_logout', 'Admin signed out.');
        Auth::logout();
        voting_flash('success', 'You have signed out.');
        $this->redirect(admin_login_path());
    }

    private function verifyCode(): void
    {
        $pending = $_SESSION['pending_admin_otp'] ?? null;

        if (!$pending) {
            voting_flash('warning', 'Please sign in first so we can generate your admin sign-in number.');
            $this->redirect(admin_login_path());
        }

        $identifier = $this->rateLimitIdentifier($pending['email'] ?? '');

        if (RateLimiter::tooManyAttempts('admin_otp', $identifier, 6, self::OTP_EXPIRES_SECONDS)) {
            unset($_SESSION['pending_admin_otp']);
            SecurityGuard::record('admin_code_rate_blocked', 'high', SecurityGuard::clientIp(), 'POST', voting_current_path(), 6, 'Admin sign-in number attempt rate limit exceeded.');
            voting_flash('error', 'Too many incorrect codes. Please sign in again.');
            $this->redirect(admin_login_path());
        }

        if ((int) ($pending['expires_at'] ?? 0) < time()) {
            unset($_SESSION['pending_admin_otp']);
            voting_flash('error', 'Your sign-in code expired. Please sign in again.');
            $this->redirect(admin_login_path());
        }

        $code = preg_replace('/\D+/', '', (string) ($_POST['code'] ?? ''));

        if ($code !== '' && password_verify($code, (string) ($pending['code_hash'] ?? ''))) {
            $user = (new AdminUser())->find((int) $pending['user_id']);

            if ($user === null || (int) $user['is_active'] !== 1) {
                unset($_SESSION['pending_admin_otp']);
                voting_flash('error', 'This admin account is no longer active.');
                $this->redirect(admin_login_path());
            }

            unset($_SESSION['pending_admin_otp'], $_SESSION['_old']);
            RateLimiter::clear('admin_login', $identifier);
            RateLimiter::clear('admin_otp', $identifier);
            Auth::loginUser($user);
            (new AuditLog())->record('admin_login', 'Admin signed in with displayed verification number.');
            $this->redirect($this->postLoginPath($user));
        }

        RateLimiter::hit('admin_otp', $identifier, self::OTP_EXPIRES_SECONDS);
        $_SESSION['pending_admin_otp']['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
        SecurityGuard::record('admin_code_failed', 'medium', SecurityGuard::clientIp(), 'POST', voting_current_path(), (int) $_SESSION['pending_admin_otp']['attempts'], 'Invalid admin displayed verification number submitted.');
        voting_flash('error', 'Invalid sign-in number. Please type the displayed number and try again.');
        $this->redirect(admin_login_path());
    }

    private function resendCode(): void
    {
        $pending = $_SESSION['pending_admin_otp'] ?? null;

        if (!$pending) {
            voting_flash('warning', 'Please sign in first so we can generate your admin sign-in number.');
            $this->redirect(admin_login_path());
        }

        if ((int) ($pending['sent_at'] ?? 0) > time() - self::OTP_RESEND_SECONDS) {
            voting_flash('warning', 'Please wait a minute before generating another sign-in number.');
            $this->redirect(admin_login_path());
        }

        $user = (new AdminUser())->find((int) $pending['user_id']);

        if ($user === null || (int) $user['is_active'] !== 1) {
            unset($_SESSION['pending_admin_otp']);
            voting_flash('error', 'This admin account is no longer active.');
            $this->redirect(admin_login_path());
        }

        $this->issueCode($user);

        voting_flash('success', 'A new sign-in number was generated. Type the displayed number to continue.');
        $this->redirect(admin_login_path());
    }

    private function issueCode(array $user): void
    {
        $code = (string) random_int(100000, 999999);

        session_regenerate_id(true);
        $_SESSION['pending_admin_otp'] = [
            'user_id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'display_code' => $code,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + self::OTP_EXPIRES_SECONDS,
            'sent_at' => time(),
            'attempts' => 0,
        ];
    }

    private function requiresEmailCode(array $user): bool
    {
        return ($user['role'] ?? '') !== 'canvassing';
    }

    private function postLoginPath(array $user): string
    {
        return ($user['role'] ?? '') === 'canvassing' ? canvassing_dashboard_path() : '/admin/dashboard';
    }

    private function rateLimitIdentifier(string $email): string
    {
        return strtolower(trim($email)) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = substr($name, 0, 2);

        return $visible . str_repeat('*', max(2, strlen($name) - 2)) . ($domain !== '' ? '@' . $domain : '');
    }

    private function sendNoStoreHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
}
