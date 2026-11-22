<?php
require __DIR__ . '/_init.php';

if (is_admin()) {
    redirect('/admin/');
}

$error = '';
$username = '';
if (is_post()) {
    $username = trim((string)($_POST['username'] ?? ''));
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $result = attempt_login($username, (string)($_POST['password'] ?? ''));
        if ($result === true) {
            $next = (string)($_GET['next'] ?? '');
            $base = base_path();
            if ($next !== '' && $next[0] === '/' && !str_starts_with($next, '//')) {
                if ($base !== '' && str_starts_with($next, $base)) {
                    $next = substr($next, strlen($base));
                }
                redirect($next ?: '/admin/');
            }
            redirect('/admin/');
        }
        $error = $result;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Log in — Website admin</title>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>">
<link rel="stylesheet" href="<?= e(asset('admin/assets/admin.css')) ?>">
</head>
<body class="adm-auth">
  <form method="post" class="adm-auth-card adm-form" autocomplete="on">
    <div class="adm-auth-brand"><img src="<?= e(url('/images/sparktech-logo.webp')) ?>" alt="SPARKTECH" height="44"></div>
    <h1 class="adm-h1">Website admin</h1>
    <p class="adm-muted">Log in to edit your website.</p>
    <?php if ($error): ?><div class="adm-alert adm-alert-error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>Username or e-mail <input name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus></label>
    <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
    <button class="adm-btn adm-btn-primary adm-btn-lg adm-btn-block" type="submit">Log in</button>
    <p class="adm-muted adm-small" style="text-align:center;margin-top:18px"><a href="<?= e(url('/')) ?>">← Back to website</a></p>
  </form>
</body>
</html>
