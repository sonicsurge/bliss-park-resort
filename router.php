<?php
/**
 * router.php - development-server router for `php -S localhost:8000 router.php`.
 *
 * Apache uses .htaccess to redirect old public links. PHP's development server
 * deliberately ignores .htaccess, so this tiny router makes local testing act
 * like the deployed site. It is not used by Apache or PHP-FPM in production.
 */
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? ltrim(rawurldecode($path), '/') : '';

/* Reject traversal and control characters before the path is used as a file. */
if (str_contains($path, "\0") || str_contains($path, '..')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not found.';
    exit;
}

/* Only known legacy names redirect; a request cannot choose its target. */
$legacyPages = [
    '' => 'homepage.php',
    'homepage.html' => 'homepage.php',
    'about.html' => 'about.php',
    'room.html' => 'room.php',
    'services.html' => 'services.php',
    'header.html' => 'homepage.php',
    'adminheader.html' => 'homepage.php',
    'login.html' => 'login.php',
    'register.html' => 'register.php',
    'contact.html' => 'contact.php',
    'bookingpage.html' => 'booking.php',
    'payment details.html' => 'booking.php',
];

if (array_key_exists($path, $legacyPages)) {
    header('Location: ' . $legacyPages[$path], true, 302);
    exit;
}

/*
 * The built-in server ignores .htaccess. Permit only assets that a browser
 * needs, block internal folders and sensitive extensions, and let public PHP
 * entry points execute. This prevents a local development server from exposing
 * .env, schema.sql, audit logs, or source-only helper files.
 */
$blockedFiles = ['.env', '.user.ini', 'php.ini', 'router.php', 'schema.sql'];
$blockedPrefixes = ['includes/', 'storage/', 'tools/', '.git/', '.idea/', '.vscode/'];
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$assetExtensions = ['css', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'];
$blocked = in_array($path, $blockedFiles, true)
    || str_starts_with(basename($path), '.')
    || in_array($extension, ['ini', 'sql', 'log', 'md', 'py'], true);

foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($path, $prefix)) {
        $blocked = true;
        break;
    }
}

if ($blocked) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not found.';
    exit;
}

$file = __DIR__ . '/' . $path;
if ($path !== '' && is_file($file) && in_array($extension, $assetExtensions, true)) {
    return false;
}

/* Public PHP pages execute through the normal bootstrap instead of being read. */
if ($path !== '' && is_file($file) && $extension === 'php') {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Not found.';
