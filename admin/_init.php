<?php
/**
 * Shared admin bootstrap + layout helpers. Every admin page requires this.
 */
define('ST_DEPTH', 1);
define('ST_FORCE_SESSION', true);
require dirname(__DIR__) . '/app/bootstrap.php';

header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');

function admin_header(string $title, string $active = ''): void
{
    $user = current_user();
    $unread = 0;
    $pending = 0;
    try {
        $unread = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE is_read = 0 AND spam = 0');
        $pending = count(Content::pending());
    } catch (Throwable $e) {
    }
    $nav = [
        ['index.php', 'dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['edit.php?on=1&return=%2F&_csrf=' . csrf_token(), 'live', 'Edit website', 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
        ['pages.php', 'pages', 'Pages', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['popups.php', 'popups', 'Popups', 'M4 6a2 2 0 012-2h12a2 2 0 012 2v9a2 2 0 01-2 2H9l-5 4V6z'],
        ['media.php', 'media', 'Media library', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['analytics.php', 'analytics', 'Analytics', 'M4 19h16M7 16V10m5 6V5m5 11v-4'],
        ['leads.php', 'leads', 'Enquiries', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        ['history.php', 'history', 'History', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['users.php', 'users', 'Users', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['settings.php', 'settings', 'Settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Website admin</title>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>">
<link rel="stylesheet" href="<?= e(asset('admin/assets/admin.css')) ?>">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
</head>
<body class="adm">
<aside class="adm-side" id="adm-side">
  <a class="adm-logo" href="<?= e(url('/admin/')) ?>"><img src="<?= e(url('/images/sparktech-logo.webp')) ?>" alt="SPARKTECH" height="36"></a>
  <nav>
    <?php foreach ($nav as [$href, $key, $label, $icon]): ?>
    <a href="<?= e(url('/admin/' . $href)) ?>" class="<?= $active === $key ? 'is-active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $icon ?>"/></svg>
      <span><?= e($label) ?></span>
      <?php if ($key === 'leads' && $unread): ?><b class="adm-badge"><?= $unread ?></b><?php endif; ?>
      <?php if ($key === 'dashboard' && $pending): ?><b class="adm-badge adm-badge-amber" title="Unpublished changes"><?= $pending ?></b><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="adm-side-foot">
    <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View website ↗</a>
    <a href="<?= e(url('/admin/account.php')) ?>" class="<?= $active === 'account' ? 'is-active' : '' ?>"><?= e($user['name'] ?: $user['username']) ?> · My account</a>
    <form method="post" action="<?= e(url('/admin/logout.php')) ?>"><?= csrf_field() ?><button type="submit">Log out</button></form>
  </div>
</aside>
<div class="adm-main">
  <header class="adm-top">
    <button class="adm-menu" type="button" onclick="document.getElementById('adm-side').classList.toggle('is-open')" aria-label="Menu">☰</button>
    <h1><?= e($title) ?></h1>
  </header>
  <div class="adm-content">
    <?php foreach (flashes() as $f): ?>
    <div class="adm-alert adm-alert-<?= e($f['t']) ?>"><?= e($f['m']) ?></div>
    <?php endforeach; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </div>
</div>
<script src="<?= e(asset('admin/assets/admin.js')) ?>"></script>
</body>
</html>
<?php
}

/** Friendly relative time. */
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $t = strtotime($datetime);
    $d = time() - $t;
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . ' min ago';
    if ($d < 86400) return floor($d / 3600) . ' h ago';
    if ($d < 604800) return floor($d / 86400) . ' d ago';
    return date('d M Y', $t);
}

/** Label for a doc name. */
function doc_label(string $doc): string
{
    if (isset(Content::BUILTIN[$doc])) {
        return Content::BUILTIN[$doc];
    }
    if (str_starts_with($doc, 'page:')) {
        return 'Page: ' . substr($doc, 5);
    }
    return $doc;
}

/** Public URL for a doc (where to edit it live). */
function doc_url(string $doc): string
{
    $map = ['site' => '/', 'home' => '/', 'about' => '/about/', 'services' => '/services/', 'technologies' => '/technologies/',
        'capabilities' => '/capabilities/', 'gallery' => '/gallery/', 'contact' => '/contact/'];
    if (isset($map[$doc])) {
        return $map[$doc];
    }
    if (str_starts_with($doc, 'page:')) {
        return '/' . substr($doc, 5) . '/';
    }
    return '/';
}

function live_edit_url(string $path): string
{
    return url('/admin/edit.php?on=1&return=' . rawurlencode($path) . '&_csrf=' . csrf_token());
}
