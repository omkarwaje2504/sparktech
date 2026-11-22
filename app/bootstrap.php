<?php
/**
 * SPARKTECH CMS — bootstrap.
 *
 * Every entry point (index.php, admin/*.php, install.php) requires this file.
 * It loads config, the helper libraries, and opens the session only when the
 * visitor already carries a session cookie (public visitors stay cookie-free).
 */

if (!defined('ST_ROOT')) {
    define('ST_ROOT', dirname(__DIR__));
}
define('ST_APP', __DIR__);
define('ST_VERSION', '1.0.0');

// ---- PHP 7.4 polyfills ------------------------------------------------------
if (!function_exists('str_starts_with')) {
    function str_starts_with($h, $n) { return $n === '' || strncmp((string)$h, (string)$n, strlen((string)$n)) === 0; }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($h, $n) { return $n === '' || substr((string)$h, -strlen((string)$n)) === (string)$n; }
}
if (!function_exists('str_contains')) {
    function str_contains($h, $n) { return $n === '' || strpos((string)$h, (string)$n) !== false; }
}

require ST_APP . '/lib/helpers.php';
require ST_APP . '/lib/db.php';
require ST_APP . '/lib/auth.php';
require ST_APP . '/lib/sanitize.php';
require ST_APP . '/lib/content.php';
require ST_APP . '/lib/doc.php';
require ST_APP . '/lib/media.php';
require ST_APP . '/lib/mailer.php';
require ST_APP . '/lib/view.php';
require ST_APP . '/lib/seo.php';
require ST_APP . '/lib/validate.php';
require ST_APP . '/lib/analytics.php';
require ST_APP . '/views/icons.php';
require ST_APP . '/views/components.php';
require ST_APP . '/views/blocks.php';

$configFile = ST_APP . '/config.php';
if (is_file($configFile)) {
    $GLOBALS['ST_CONFIG'] = require $configFile;
} else {
    $GLOBALS['ST_CONFIG'] = null;
    if (!defined('ST_INSTALLER')) {
        // Not installed yet — send the visitor to the installer.
        header('Location: ' . base_path() . '/install.php');
        exit;
    }
}

date_default_timezone_set((string)cfg('timezone', 'Asia/Kolkata'));

if (cfg('debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    if (is_dir(ST_ROOT . '/storage') && is_writable(ST_ROOT . '/storage')) {
        ini_set('error_log', ST_ROOT . '/storage/php-errors.log');
    }
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
}

if ($GLOBALS['ST_CONFIG'] !== null) {
    session_boot(defined('ST_FORCE_SESSION'));
    if (!defined('ST_INSTALLER')) {
        DB::upgrade();
    }
}
