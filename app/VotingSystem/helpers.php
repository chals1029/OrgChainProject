<?php

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function env_value(string $key, mixed $default = null): mixed
{
    $value = getenv($key);

    return $value === false ? $default : $value;
}

function project_path(string $path = ''): string
{
    $path = trim($path);

    // Voting storage lives under Laravel storage/app/voting
    $root = storage_path('app/voting');

    if ($path === '') {
        return $root;
    }

    $normalized = str_replace('\\', '/', $path);

    if (str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized)) {
        return $path;
    }

    return $root.'/'.ltrim($path, '/\\');
}

/**
 * Absolute path helpers for the integrated voting module.
 */
function voting_storage_path(string $path = ''): string
{
    return project_path($path);
}

function voting_public_assets_path(string $path = ''): string
{
    $root = public_path('voting-assets');

    return $path === '' ? $root : $root.'/'.ltrim($path, '/\\');
}

function voting_config(string $key, mixed $default = null): mixed
{
    // Prefer Laravel config/voting.php
    if (function_exists('config')) {
        $laravelValue = config('voting.'.$key, null);
        if ($laravelValue !== null) {
            return $laravelValue;
        }
    }

    $config = $GLOBALS['voting_config'] ?? $GLOBALS['config'] ?? [];
    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (! is_array($value) || ! array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

// Use Laravel's e() when available; otherwise provide a local escape helper.
if (! function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function display_position_title(mixed $title): string
{
    $title = trim((string) $title);

    if (strcasecmp($title, 'Vice for External Affairs') === 0) {
        return 'Vice President for External Affairs';
    }

    return $title;
}

function request_is_secure(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function normalized_host(string $host): string
{
    $host = trim(strtolower($host));

    if ($host === '') {
        return '';
    }

    $host = explode(',', $host, 2)[0];
    $host = trim($host);
    $parsed = parse_url('//' . $host, PHP_URL_HOST);

    return trim((string) ($parsed ?: $host), '[]');
}

function trusted_hosts(): array
{
    $hosts = [
        'sscnasugbuvotinghub.com',
        'www.sscnasugbuvotinghub.com',
        'localhost',
        '127.0.0.1',
        '::1',
    ];

    $appUrlHost = normalized_host((string) parse_url((string) voting_config('app_url', ''), PHP_URL_HOST));
    if ($appUrlHost !== '') {
        $hosts[] = $appUrlHost;
    }

    foreach (explode(',', (string) voting_config('trusted_hosts', '')) as $host) {
        $host = normalized_host($host);
        if ($host !== '') {
            $hosts[] = $host;
        }
    }

    return array_values(array_unique(array_filter($hosts)));
}

function request_host(): string
{
    return normalized_host((string) ($_SERVER['HTTP_HOST'] ?? ''));
}

function request_host_is_trusted(?string $host = null): bool
{
    $host = normalized_host((string) ($host ?? request_host()));

    if ($host === '') {
        return true;
    }

    if (in_array($host, trusted_hosts(), true)) {
        return true;
    }

    return str_ends_with($host, '.test')
        || str_ends_with($host, '.local')
        || str_ends_with($host, '.localhost');
}

function trusted_request_host(): string
{
    $host = request_host();

    if (request_host_is_trusted($host)) {
        return $host !== '' ? $host : 'sscnasugbuvotinghub.com';
    }

    $appUrlHost = normalized_host((string) parse_url((string) voting_config('app_url', ''), PHP_URL_HOST));

    return $appUrlHost !== '' ? $appUrlHost : 'sscnasugbuvotinghub.com';
}

function college_abbreviation(mixed $college): string
{
    $name = trim((string) $college);
    $normalized = preg_replace('/[^a-z0-9]+/', ' ', strtolower($name)) ?? '';
    $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? '');

    $map = [
        'college of accountancy business economics and international hospitality management' => 'CABEIHM',
        'college of arts and sciences' => 'CAS',
        'college of criminal justice education' => 'CCJE',
        'college of nursing and allied health sciences' => 'CHS',
        'college of health sciences' => 'CHS',
        'college of informatics and computing sciences' => 'CICS',
        'college of teacher education' => 'CTE',
        'laboratory school' => 'LAB SCHOOL',
        'lab school' => 'LAB SCHOOL',
    ];

    return $map[$normalized] ?? $name;
}

function college_sort_rank(mixed $college): int
{
    $order = [
        'CABEIHM' => 10,
        'CAS' => 20,
        'CCJE' => 30,
        'CHS' => 40,
        'CICS' => 50,
        'CTE' => 60,
        'LAB SCHOOL' => 70,
    ];

    return $order[college_abbreviation($college)] ?? 999;
}

/**
 * URL prefix when this app is nested (e.g. /voting-system under OrgChain).
 */
function voting_app_mount_path(): string
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    // Always mounted under /voting-system inside OrgChain Laravel.
    $configured = rtrim((string) voting_config('url_prefix', '/voting-system'), '/');
    if ($configured === '' || $configured === '/') {
        return $cached = '/voting-system';
    }

    return $cached = (str_starts_with($configured, '/') ? $configured : '/'.$configured);
}

function voting_base_url(string $path = ''): string
{
    $suffix = ltrim($path, '/');
    $mount = voting_app_mount_path(); // /voting-system
    $configured = rtrim((string) voting_config('app_url', ''), '/');

    // Absolute app URL (http://127.0.0.1:8000) + mount prefix
    if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
        $base = rtrim($configured, '/').$mount;

        return $suffix === '' ? $base : $base.'/'.$suffix;
    }

    // Relative mount only
    if ($mount === '') {
        return '/'.$suffix;
    }

    return $suffix === '' ? $mount : $mount.'/'.$suffix;
}

function voting_asset(string $path): string
{
    $cleanPath = ltrim($path, '/');
    // Strip legacy prefixes if present
    $cleanPath = preg_replace('#^(public/)?assets/#', '', $cleanPath) ?: $cleanPath;

    $assetFile = public_path('voting-assets/'.$cleanPath);
    $version = is_file($assetFile) ? ('?v='.filemtime($assetFile)) : '';

    // Absolute URL path from web root (not under /voting-system prefix)
    return '/voting-assets/'.$cleanPath.$version;
}

function media_url(?string $path, string $fallback): string
{
    $path = trim((string) $path);

    if ($path === '') {
        return voting_asset($fallback);
    }

    if (preg_match('/^https:\/\//i', $path)) {
        return $path;
    }

    if (str_starts_with($path, '/') && !str_starts_with($path, '//')) {
        return $path;
    }

    if (str_contains($path, '..') || str_contains($path, '\\')) {
        return voting_asset($fallback);
    }

    return voting_asset($path);
}

function candidate_image_url(array $candidate): string
{
    $candidateId = (int) ($candidate['id'] ?? 0);

    if ($candidateId <= 0) {
        return voting_asset('img/candidate-placeholder.svg');
    }

    return voting_url('/media/candidate?id=' . $candidateId);
}

function voting_url(string $path = ''): string
{
    return voting_base_url($path);
}

function admin_login_path(): string
{
    $path = trim((string) voting_config('security.admin_login_path', '/ssc-comelec-access-7f4d29'));
    $path = '/' . trim($path, '/');

    if ($path === '/'
        || strtolower($path) === '/admin'
        || str_starts_with(strtolower($path), '/admin/')
        || str_contains($path, '..')
        || str_contains($path, '\\')
    ) {
        return '/ssc-comelec-access-7f4d29';
    }

    return $path;
}

function admin_logout_path(): string
{
    return rtrim(admin_login_path(), '/') . '/logout';
}

function private_staff_path(string $configKey, string $fallback, array $reservedPaths = []): string
{
    $reservedPaths = array_map(
        static fn (string $reservedPath): string => strtolower('/' . trim($reservedPath, '/')),
        $reservedPaths
    );

    $path = trim((string) voting_config($configKey, $fallback));
    $path = '/' . trim($path, '/');
    $lowerPath = strtolower($path);

    if ($path === '/'
        || in_array($lowerPath, $reservedPaths, true)
        || $lowerPath === '/admin'
        || str_starts_with($lowerPath, '/admin/')
        || str_starts_with($lowerPath, '/vote')
        || str_starts_with($lowerPath, '/auth/')
        || str_starts_with($lowerPath, '/assets/')
        || str_starts_with($lowerPath, '/public/')
        || str_contains($path, '..')
        || str_contains($path, '\\')
    ) {
        return $fallback;
    }

    return $path;
}

function canvassing_dashboard_path(): string
{
    return private_staff_path(
        'security.canvassing_dashboard_path',
        '/ssc-canvassing-dashboard-21b6e4',
        [admin_login_path(), admin_logout_path()]
    );
}

function canvassing_tally_path(): string
{
    return private_staff_path(
        'security.canvassing_tally_path',
        '/ssc-canvassing-tally-8a3d92',
        [admin_login_path(), admin_logout_path(), canvassing_dashboard_path()]
    );
}

function canvassing_path(): string
{
    return canvassing_tally_path();
}

function canvassing_reports_path(): string
{
    return private_staff_path(
        'security.canvassing_reports_path',
        '/ssc-canvassing-reports-5c7f10',
        [admin_login_path(), admin_logout_path(), canvassing_dashboard_path(), canvassing_tally_path()]
    );
}

function staff_private_paths(): array
{
    return [
        canvassing_dashboard_path(),
        canvassing_tally_path(),
        canvassing_reports_path(),
    ];
}

function is_staff_protected_path(string $path): bool
{
    $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: '/', '/');

    return $path === '/admin'
        || str_starts_with($path, '/admin/')
        || in_array($path, staff_private_paths(), true);
}

function staff_dashboard_path_for(?array $user): string
{
    return (($user['role'] ?? '') === 'admin') ? '/admin/dashboard' : canvassing_dashboard_path();
}

function staff_reports_path_for(?array $user): string
{
    return (($user['role'] ?? '') === 'admin') ? '/admin/reports' : canvassing_reports_path();
}

function voting_current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $mount = voting_app_mount_path();

    if ($mount !== '' && str_starts_with($uri, $mount)) {
        $uri = substr($uri, strlen($mount)) ?: '/';
    }

    // Safety for nested installs when mount detection fails
    if (str_starts_with($uri, '/voting-system')) {
        $uri = substr($uri, strlen('/voting-system')) ?: '/';
    }

    $uri = '/' . trim($uri, '/');

    return $uri === '/' ? '/' : rtrim($uri, '/');
}

function voting_old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function voting_flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;

        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function voting_redirect(string $path): never
{
    header('Location: ' . voting_url($path));
    exit;
}

function voting_csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function voting_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(voting_csrf_token()) . '">';
}
