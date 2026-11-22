<?php
/**
 * POST /collect — analytics beacon from assets/js/track.js (page view, click,
 * time on page). Always answers 204 No Content; see app/lib/analytics.php.
 */
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$sameSite = function (): bool {
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '');
    if ($origin === '') {
        return true;
    }
    $host = strtolower(preg_replace('/:\d+$/', '', (string)parse_url($origin, PHP_URL_HOST)));
    $own = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    return $host === $own;
};

http_response_code(204);
if (!is_post() || !$sameSite() || !Analytics::trackable()) {
    exit;
}
$raw = (string)file_get_contents('php://input', false, null, 0, 8192);
$in = json_decode($raw, true);
if (is_array($in)) {
    try {
        Analytics::collect($in);
    } catch (Throwable $e) {
        error_log('Analytics collect failed: ' . $e->getMessage());
    }
}
Analytics::runDeferred();
