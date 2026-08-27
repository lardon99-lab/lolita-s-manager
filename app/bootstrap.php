<?php
declare(strict_types=1);

use App\Support\Env;
use App\Support\Logger;

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';
Env::load($root . '/.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Tegucigalpa'));

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_name('lolitas_session');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Lax']);
if (PHP_SAPI === 'cli') {
    $sessionPath = $root . '/storage/sessions';
    if (!is_dir($sessionPath)) @mkdir($sessionPath, 0770, true);
    session_save_path($sessionPath);
}
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}

set_exception_handler(static function (Throwable $error): void {
    Logger::error($error);
    http_response_code(500);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => 'Ocurrio un error interno.'], JSON_UNESCAPED_UNICODE);
        return;
    }
    echo 'Ocurrio un error interno.';
});

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
