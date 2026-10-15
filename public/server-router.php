<?php

/**
 * PHP built-in server router for OrgChain (Laravel + integrated voting module).
 *
 * From project root:
 *   php -S 127.0.0.1:8000 -t public public/server-router.php
 *   composer serve
 */

$publicPath = __DIR__;
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

// Public storage contains public assets only; private records must never bypass Laravel.
if (str_contains($uri, "\0") || str_contains($uri, '\\') || in_array('..', explode('/', $uri), true)) {
    http_response_code(404);
    exit;
}
if (str_starts_with($uri, '/storage/')) {
    $category = explode('/', substr($uri, strlen('/storage/')))[0];
    if (! in_array($category, ['avatars', 'office-settings', 'templates', 'tosa-templates'], true)) {
        http_response_code(404);
        exit;
    }
}

// Serve real static files (CSS/JS/images, including /voting-assets/*).
if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}

// Everything else goes through Laravel (including /voting-system/* routes).
require $publicPath.'/index.php';
