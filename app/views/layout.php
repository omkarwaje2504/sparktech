<?php
/**
 * Site layout. Variables: $content (page HTML), $meta, $site (Doc).
 * @var string $content
 * @var array $meta
 * @var Doc $site
 */
$gaId = trim((string)Settings::get('ga_id', ''));
$editing = edit_mode();
$track = !$editing && Analytics::trackable();
?><!doctype html>
<html lang="en" class="scroll-smooth<?= $editing ? ' cms-editing' : '' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<?= seo_head($meta, $site) ?>
  <meta name="apple-mobile-web-app-title" content="SPARKTECH">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="any">
  <link rel="icon" type="image/png" href="<?= e(url('/icon1.png')) ?>">
  <link rel="apple-touch-icon" href="<?= e(url('/apple-icon.png')) ?>">
  <link rel="manifest" href="<?= e(url('/manifest.json')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap">
  <link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
  <?php if ($editing): ?><link rel="stylesheet" href="<?= e(asset('assets/css/editor.css')) ?>"><?php endif; ?>
  <script>document.documentElement.classList.add('js');</script>
  <?php if ($gaId !== '' && !is_admin() && preg_match('/^[A-Z0-9-]+$/i', $gaId)): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
  <?php endif; ?>
</head>
<body class="antialiased min-h-screen flex flex-col">
<a href="#main" class="st-skip">Skip to main content</a>
<?php require ST_APP . '/views/partials/header.php'; ?>
<main id="main" class="flex-1" tabindex="-1">
<?= $content ?>
</main>
<?php require ST_APP . '/views/partials/whatsapp.php'; ?>
<?php require ST_APP . '/views/partials/footer.php'; ?>
<?= render_popups($site) ?>
<?php if (is_admin()) require ST_APP . '/views/partials/adminbar.php'; ?>
<script>window.ST = <?= json_encode(['base' => base_path(), 'editing' => $editing, 'track' => $track,
    'dnt' => Settings::get('an_respect_dnt', '0') === '1'], JSON_UNESCAPED_SLASHES) ?>;</script>
<?php if ($track): ?><script src="<?= e(asset('assets/js/track.js')) ?>" defer></script><?php endif; ?>
<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
<?php if ($editing): ?>
<script type="application/json" id="cms-data"><?= cms_editor_payload($meta) ?></script>
<script src="<?= e(asset('assets/js/editor.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
