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

// Serve real static files (CSS/JS/images, including /voting-assets/*).
if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}

// Everything else goes through Laravel (including /voting-system/* routes).
require $publicPath.'/index.php';
