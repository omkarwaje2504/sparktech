<?php
/**
 * Router for PHP's built-in server (local development only — Apache uses .htaccess):
 *   php -S 127.0.0.1:8090 -t php-site php-site/app/cli/dev-router.php
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = dirname(__DIR__, 2);
if (preg_match('~^/(app|storage)(/|$)~', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
$file = $root . $path;
if ($path !== '/' && is_file($file)) {
    return false; // static file or a real PHP script (admin/*.php, install.php)
}
if (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {
    if (substr($path, -1) !== '/') {
        header('Location: ' . $path . '/', true, 301);
        exit;
    }
    $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '/index.php';
    require rtrim($file, '/') . '/index.php';
    return true;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
