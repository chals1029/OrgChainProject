<?php

namespace App\VotingSystem\Core;

use App\VotingSystem\Models\AdminUser;

class Auth
{
    private static ?array $cachedUser = null;
    private static bool $cachedUserLoaded = false;

    public static function attempt(string $email, string $password): bool
    {
        $user = self::validateCredentials($email, $password);

        if ($user === null) {
            return false;
        }

        self::loginUser($user);

        return true;
    }

    public static function validateCredentials(string $email, string $password): ?array
    {
        $user = (new AdminUser())->findByEmail($email);

        if ($user === null || (int) $user['is_active'] !== 1) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public static function loginUser(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'login_at' => time(),
            'last_seen' => time(),
            'ua_hash' => self::userAgentHash(),
        ];
    }

    public static function user(): ?array
    {
        if (self::$cachedUserLoaded) {
            return self::$cachedUser;
        }

        $user = $_SESSION['admin_user'] ?? null;

        if ($user === null || empty($user['id'])) {
            self::$cachedUserLoaded = true;
            self::$cachedUser = null;
            return null;
        }

        if (!self::sessionIsFresh($user)) {
            self::logout();
            self::$cachedUserLoaded = true;
            self::$cachedUser = null;
            return null;
        }

        $fresh = (new AdminUser())->find((int) $user['id']);

        if ($fresh === null || (int) $fresh['is_active'] !== 1) {
            self::logout();
            self::$cachedUserLoaded = true;
            self::$cachedUser = null;
            return null;
        }

        $_SESSION['admin_user'] = [
            'id' => $fresh['id'],
            'name' => $fresh['name'],
            'email' => $fresh['email'],
            'role' => $fresh['role'],
            'login_at' => (int) ($user['login_at'] ?? time()),
            'last_seen' => time(),
            'ua_hash' => self::userAgentHash(),
        ];

        self::$cachedUserLoaded = true;
        self::$cachedUser = $_SESSION['admin_user'];

        return self::$cachedUser;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_user']);
        unset($_SESSION['pending_admin_otp']);
        self::$cachedUser = null;
        self::$cachedUserLoaded = false;
        session_regenerate_id(true);
    }

    private static function sessionIsFresh(array $user): bool
    {
        $now = time();
        $idleLimit = max(300, (int) voting_config('security.session_idle_seconds', 1800));
        $absoluteLimit = max($idleLimit, (int) voting_config('security.session_absolute_seconds', 28800));
        $loginAt = (int) ($user['login_at'] ?? $now);
        $lastSeen = (int) ($user['last_seen'] ?? $now);
        $storedUserAgentHash = (string) ($user['ua_hash'] ?? '');

        if ($now - $lastSeen > $idleLimit || $now - $loginAt > $absoluteLimit) {
            return false;
        }

        return $storedUserAgentHash === '' || hash_equals($storedUserAgentHash, self::userAgentHash());
    }

    private static function userAgentHash(): string
    {
        return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
}
