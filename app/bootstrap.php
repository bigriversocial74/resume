<?php
declare(strict_types=1);

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    exit('Missing app/config.php. Copy app/config.example.php and configure it.');
}

$config = require $configPath;

if (!empty($config['app']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(self), microphone=(self), fullscreen=(self), display-capture=(self), geolocation=()');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) === '443');
session_name($config['security']['session_name'] ?? 'de_crm_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/projects.php';
require_once __DIR__ . '/chat.php';
require_once __DIR__ . '/knowledge.php';
require_once __DIR__ . '/ai.php';
require_once __DIR__ . '/tavus.php';

function app_config(?string $key = null): mixed
{
    global $config;
    if ($key === null) {
        return $config;
    }
    $parts = explode('.', $key);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return null;
        }
        $value = $value[$part];
    }
    return $value;
}

function normalize_app_base_path(?string $path): string
{
    $path = '/' . trim((string)$path, '/');
    return $path === '/' ? '' : $path;
}

function app_base_path(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $baseUrl = (string)(app_config('app.base_url') ?? '');
    $configuredPath = parse_url($baseUrl, PHP_URL_PATH);
    if (is_string($configuredPath) && trim($configuredPath, '/') !== '') {
        $basePath = normalize_app_base_path($configuredPath);
        return $basePath;
    }

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/admin/', '/customer/', '/api/'] as $marker) {
        $position = strpos($scriptName, $marker);
        if ($position !== false) {
            $basePath = normalize_app_base_path(substr($scriptName, 0, $position));
            return $basePath;
        }
    }

    $dir = dirname($scriptName);
    $basePath = ($dir && $dir !== '.' && $dir !== '/') ? normalize_app_base_path($dir) : '';
    return $basePath;
}

function app_url(string $path = ''): string
{
    if ($path !== '' && preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $base = app_base_path();
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

function redirect_to(string $path): never
{
    header('Location: ' . app_url($path), true, 302);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
