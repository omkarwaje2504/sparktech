<?php
require __DIR__ . '/_init.php';
$me = require_login();
$full = DB::row('SELECT * FROM {users} WHERE id = ?', [(int)$me['id']]);

if (is_post()) {
    csrf_require();
    $name = trim(clean_text($_POST['name'] ?? '', 120));
    $email = trim((string)($_POST['email'] ?? ''));
    $current = (string)($_POST['current'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    $new2 = (string)($_POST['new2'] ?? '');

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('E-mail address is not valid.', 'error');
    } else {
        DB::update('users', ['name' => $name, 'email' => $email], 'id = ?', [(int)$me['id']]);
        if ($new !== '' || $current !== '') {
            if (!password_verify($current, $full['password_hash'])) {
                flash('Current password is wrong — password not changed.', 'error');
                redirect('/admin/account.php');
            }
            if (strlen($new) < 10) {
                flash('New password must be at least 10 characters.', 'error');
                redirect('/admin/account.php');
            }
            if ($new !== $new2) {
                flash('The new passwords do not match.', 'error');
                redirect('/admin/account.php');
            }
            DB::update('users', ['password_hash' => password_hash($new, PASSWORD_DEFAULT)], 'id = ?', [(int)$me['id']]);
            session_regenerate_id(true);
            flash('Profile and password updated.');
        } else {
            flash('Profile updated.');
        }
    }
    redirect('/admin/account.php');
}

admin_header('My account', 'account');
?>
<div class="adm-card" style="max-width:640px">
  <form method="post" class="adm-form">
    <?= csrf_field() ?>
    <label>Username <input value="<?= e($full['username']) ?>" disabled></label>
    <label>Name <input name="name" value="<?= e($full['name']) ?>"></label>
    <label>E-mail <input type="email" name="email" value="<?= e($full['email']) ?>"></label>
    <hr>
    <h3>Change password</h3>
    <label>Current password <input type="password" name="current" autocomplete="current-password"></label>
    <label>New password (min. 10 characters) <input type="password" name="new" minlength="10" autocomplete="new-password"></label>
    <label>Repeat new password <input type="password" name="new2" minlength="10" autocomplete="new-password"></label>
    <button class="adm-btn adm-btn-primary" type="submit">Save</button>
  </form>
</div>
<?php admin_footer();
