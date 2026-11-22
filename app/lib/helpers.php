<?php
/**
 * Small global helpers: config access, escaping, URLs, requests, flashes.
 */

function cfg(string $key, $default = null)
{
    $cfg = $GLOBALS['ST_CONFIG'] ?? null;
    if (!is_array($cfg)) {
        return $default;
    }
    foreach (explode('.', $key) as $seg) {
        if (!is_array($cfg) || !array_key_exists($seg, $cfg)) {
            return $default;
        }
        $cfg = $cfg[$seg];
    }
    return $cfg;
}

/** HTML-escape any scalar. */
function e($value): string
{
    if ($value === null || is_array($value)) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL prefix when the site lives in a sub-folder (e.g. "/new"). Empty string
 * for a domain root install. Entry scripts inside sub-folders define ST_DEPTH.
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (defined('ST_BASE_PATH')) {
        return $base = rtrim((string)ST_BASE_PATH, '/');
    }
    $configured = cfg('base_path');
    if (is_string($configured) && $configured !== '') {
        return $base = rtrim($configured, '/');
    }
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $depth = defined('ST_DEPTH') ? (int)ST_DEPTH : 0;
    for ($i = 0; $i < $depth; $i++) {
        $dir = rtrim(str_replace('\\', '/', dirname($dir)), '/');
    }
    if ($dir === '.' || $dir === '/') {
        $dir = '';
    }
    return $base = $dir;
}

/** Resolve a stored URL: root-relative paths get the base path prepended. */
function url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '#';
    }
    if ($path[0] === '/' && !str_starts_with($path, '//')) {
        return base_path() . $path;
    }
    return $path;
}

/** Absolute URL (scheme + host) for canonical / OG / sitemap. */
function abs_url(string $path = '/'): string
{
    $configured = cfg('site_url');
    if (is_string($configured) && $configured !== '') {
        $origin = rtrim($configured, '/');
        return $origin . $path;
    }
    $scheme = is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        return true;
    }
    return (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/** Versioned asset URL for cache busting. */
function asset(string $path): string
{
    $file = ST_ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? (string)filemtime($file) : ST_VERSION;
    return url('/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $to, int $code = 302): void
{
    if ($to !== '' && $to[0] === '/' && !str_starts_with($to, '//')) {
        $to = base_path() . $to;
    }
    header('Location: ' . $to, true, $code);
    exit;
}

function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    if (isset($_POST[$key])) {
        return is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
    }
    if (isset($_GET[$key])) {
        return is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key];
    }
    return $default;
}

function request_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 64);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('~[^a-z0-9]+~', '-', $text);
    return trim((string)$text, '-');
}

/** "card_title" → "Card title" (used for auto field labels). */
function humanize(string $key): string
{
    $key = str_replace(['_', '-'], ' ', $key);
    return ucfirst(trim($key));
}

function flash(string $message, string $type = 'success'): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['_flash'][] = ['m' => $message, 't' => $type];
    }
}

function flashes(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function is_list_array($value): bool
{
    if (!is_array($value)) {
        return false;
    }
    if ($value === []) {
        return true;
    }
    return array_keys($value) === range(0, count($value) - 1);
}

/** Human-readable byte size. */
function human_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float)$bytes;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return ($i === 0 ? (string)$bytes : number_format($n, 1)) . ' ' . $units[$i];
}

/** Random hex token. */
function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/** Signed, time-stamped token for public forms (spam protection without a session). */
function form_token(): string
{
    $ts = (string)time();
    return $ts . '.' . hash_hmac('sha256', 'form|' . $ts, (string)cfg('app_key', 'st'));
}

/** Valid if signed by us, at least 3 s old (bots are instant) and under 2 days old. */
function form_token_valid(string $token): bool
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2 || !ctype_digit($parts[0])) {
        return false;
    }
    $expected = hash_hmac('sha256', 'form|' . $parts[0], (string)cfg('app_key', 'st'));
    if (!hash_equals($expected, $parts[1])) {
        return false;
    }
    $age = time() - (int)$parts[0];
    return $age >= 3 && $age <= 172800;
}

/** Two-digit index label: 0 → "01". */
function pad2(int $n): string
{
    return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
}
