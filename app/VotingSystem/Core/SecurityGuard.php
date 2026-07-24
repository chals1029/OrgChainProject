<?php

namespace App\VotingSystem\Core;

use App\VotingSystem\Models\SecurityEvent;

class SecurityGuard
{
    public static function enforce(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = voting_current_path();

        if (self::isStaticAsset($path)) {
            return;
        }

        $ip = self::clientIp();
        $isPublicVoterPath = self::isPublicVoterPath($path);
        $globalCount = 0;

        if (!$isPublicVoterPath) {
            $globalWindow = max(10, (int) voting_config('security.global_rate_window', 60));
            $globalLimit = max(20, (int) voting_config('security.global_rate_limit', 1200));
            $globalCount = RateLimiter::hitAndCount('global_request', $ip, $globalWindow);

            if ($globalCount > $globalLimit) {
                self::record('global_rate_blocked', 'critical', $ip, $method, $path, $globalCount, 'Global request rate exceeded.');
                self::block($globalWindow);
            }
        } else {
            $publicWindow = max(10, (int) voting_config('security.public_rate_window', 60));
            $publicLimit = max(30, (int) voting_config('security.public_rate_limit', 600));
            $publicCount = RateLimiter::hitAndCount('public_voter_request', $ip, $publicWindow);

            if ($publicCount > $publicLimit) {
                self::record('public_voter_rate_blocked', 'high', $ip, $method, $path, $publicCount, 'Public voter route request rate exceeded.');
                self::block($publicWindow);
            }
        }

        if (!$isPublicVoterPath && self::isStaffRateLimitedPath($path)) {
            $staffWindow = max(30, (int) voting_config('security.staff_rate_window', 300));
            $staffLimit = max(10, (int) voting_config('security.staff_rate_limit', 120));
            $staffCount = RateLimiter::hitAndCount('staff_request', $ip, $staffWindow);

            if ($staffCount > $staffLimit) {
                self::record('staff_rate_blocked', 'high', $ip, $method, $path, $staffCount, 'Staff route request rate exceeded.');
                self::block($staffWindow);
            }
        }

        if (self::isStaffSecurityPath($path)) {
            $sqlInjectionSignal = self::detectSqlInjectionAttempt();

            if ($sqlInjectionSignal !== null) {
                $attempts = RateLimiter::hitAndCount('sql_injection_attempt', $ip, 3600);
                self::record(
                    'sql_injection_attempt',
                    $attempts >= 5 ? 'critical' : 'high',
                    $ip,
                    $method,
                    $path,
                    $attempts,
                    $sqlInjectionSignal
                );

                if ($attempts >= 5) {
                    self::record('sql_injection_blocked', 'critical', $ip, $method, $path, $attempts, 'Repeated SQL injection signatures detected within one hour.');
                    self::block(3600);
                }

                self::warnStaffLogin($path);
            }
        }

        if (self::isAdminProbe($path)) {
            self::record('admin_path_probe', 'medium', $ip, $method, $path, $globalCount, 'Unauthenticated request to admin route.');
        }

        if (self::isCommonScannerPath($path)) {
            self::record('scanner_path_probe', 'high', $ip, $method, $path, $globalCount, 'Request matched a common scanner path.');
        }
    }

    public static function record(string $eventType, string $severity, string $ip, string $method, string $path, int $requestCount = 0, string $details = ''): void
    {
        $key = $eventType . '|' . $ip . '|' . $path;

        if (RateLimiter::hitAndCount('security_event_log', $key, 300) > 3) {
            return;
        }

        try {
            (new SecurityEvent())->record([
                'ip_address' => $ip,
                'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'method' => $method,
                'path' => $path,
                'event_type' => $eventType,
                'severity' => $severity,
                'request_count' => $requestCount,
                'details' => $details,
            ]);
        } catch (\Throwable $exception) {
            error_log('Security event log failed: ' . $exception->getMessage());
        }
    }

    public static function clientIp(): string
    {
        $candidates = [
            (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
            (string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''),
            (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            foreach (explode(',', $candidate) as $ip) {
                $ip = trim($ip);

                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return substr($ip, 0, 45);
                }
            }
        }

        return 'unknown';
    }

    private static function block(int $retryAfterSeconds): never
    {
        http_response_code(429);
        header('Retry-After: ' . min(300, max(30, $retryAfterSeconds)));
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Too many requests. Please wait and try again.');
    }

    private static function isPublicVoterPath(string $path): bool
    {
        return str_starts_with($path, '/auth/google')
            || str_starts_with($path, '/vote');
    }

    private static function isStaffRateLimitedPath(string $path): bool
    {
        return $path === admin_login_path()
            || is_staff_protected_path($path)
            || $path === admin_logout_path();
    }

    private static function isStaffSecurityPath(string $path): bool
    {
        return self::isStaffRateLimitedPath($path);
    }

    private static function isAdminProbe(string $path): bool
    {
        if (!empty($_SESSION['admin_user'])) {
            return false;
        }

        return $path === '/admin/login'
            || is_staff_protected_path($path);
    }

    private static function isCommonScannerPath(string $path): bool
    {
        $path = strtolower($path);
        $needles = [
            '/wp-admin',
            '/wp-login.php',
            '/xmlrpc.php',
            '/phpmyadmin',
            '/pma',
            '/.env',
            '/vendor/',
            '/config/',
            '/database/',
            '/storage/',
            '/app/',
        ];

        foreach ($needles as $needle) {
            if ($path === $needle || str_starts_with($path, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function isStaticAsset(string $path): bool
    {
        return preg_match('/\.(?:css|js|png|jpg|jpeg|gif|svg|ico|webp|woff2?|ttf|map)$/i', $path) === 1;
    }

    private static function detectSqlInjectionAttempt(): ?string
    {
        $sources = [
            'query' => $_GET,
            'form' => $_POST,
            'cookie' => $_COOKIE,
        ];

        foreach ($sources as $source => $values) {
            foreach (self::flattenValues($values) as $field => $value) {
                $match = self::matchSqlInjectionSignature($value);

                if ($match !== null) {
                    return 'Possible SQL injection signature "' . $match . '" in ' . $source . ' field "' . substr((string) $field, 0, 80) . '".';
                }
            }
        }

        $rawQuery = (string) ($_SERVER['QUERY_STRING'] ?? '');

        if ($rawQuery !== '') {
            $match = self::matchSqlInjectionSignature($rawQuery);

            if ($match !== null) {
                return 'Possible SQL injection signature "' . $match . '" in raw query string.';
            }
        }

        return null;
    }

    private static function flattenValues(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            $field = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;

            if (is_array($value)) {
                $flat += self::flattenValues($value, $field);
                continue;
            }

            $flat[$field] = (string) $value;
        }

        return $flat;
    }

    private static function matchSqlInjectionSignature(string $value): ?string
    {
        $decoded = html_entity_decode(rawurldecode($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = strtolower(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $decoded) ?? '');
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        if (trim($normalized) === '') {
            return null;
        }

        $patterns = [
            'union select' => '/\bunion\b\s+(?:all\s+)?\bselect\b/i',
            'stacked query' => '/;\s*(?:select|insert|update|delete|drop|alter|truncate)\b/i',
            'boolean tautology' => '/(?:\bor\b|\band\b)\s+[\'"`]?(?:\d+|[a-z_][a-z0-9_]*)[\'"`]?\s*=\s*[\'"`]?(?:\d+|[a-z_][a-z0-9_]*)[\'"`]?/i',
            'sql comment' => '/(?:--|#|\/\*|\*\/)\s*(?:$|\b(?:select|union|or|and|drop|insert|update|delete)\b)/i',
            'time delay' => '/\b(?:sleep|benchmark|pg_sleep)\s*\(|\bwaitfor\s+delay\b/i',
            'schema probing' => '/\b(?:information_schema|mysql\.user|sqlite_master|pg_catalog|sysobjects)\b/i',
            'file access' => '/\b(?:load_file|into\s+outfile|into\s+dumpfile)\b/i',
            'destructive sql' => '/\b(?:drop|alter|truncate)\s+(?:table|database|schema)\b/i',
            'credential table targeting' => '/\b(?:select|insert|update|delete)\b.+\b(?:admin_users|users|voters|votes|password_hash)\b/i',
            'sql procedure abuse' => '/\b(?:xp_cmdshell|sp_executesql|exec\s*\()\b/i',
        ];

        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return $label;
            }
        }

        return null;
    }

    private static function warnStaffLogin(string $path): void
    {
        if ($path === admin_login_path()) {
            voting_flash('error', 'Security warning: suspicious injection-style input was detected. This attempt was recorded with your IP address, browser, and request path for review.');
            voting_redirect(admin_login_path());
        }
    }
}
