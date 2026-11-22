<?php
require __DIR__ . '/_init.php';
$me = require_login();

if (is_post()) {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'create') {
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $name = trim(clean_text($_POST['name'] ?? '', 120));
        $pass = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.@-]{3,64}$/', $username)) {
            flash('Username: 3–64 letters, numbers, dot, dash, underscore or @.', 'error');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('E-mail address is not valid.', 'error');
        } elseif (strlen($pass) < 10) {
            flash('Password must be at least 10 characters.', 'error');
        } elseif (DB::value('SELECT COUNT(*) FROM {users} WHERE username = ?', [$username])) {
            flash('That username is taken.', 'error');
        } else {
            DB::insert('users', ['username' => $username, 'email' => $email, 'name' => $name,
                'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'admin', 'created_at' => now()]);
            flash("User “{$username}” created. Share the password with them securely.");
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$me['id']) {
            flash('You cannot delete your own account.', 'error');
        } elseif ((int)DB::value('SELECT COUNT(*) FROM {users}') <= 1) {
            flash('At least one user must remain.', 'error');
        } else {
            DB::exec('DELETE FROM {users} WHERE id = ?', [$id]);
            flash('User deleted.', 'info');
        }
    } elseif ($action === 'reset') {
        $id = (int)($_POST['id'] ?? 0);
        $pass = (string)($_POST['password'] ?? '');
        if (strlen($pass) < 10) {
            flash('Password must be at least 10 characters.', 'error');
        } else {
            DB::update('users', ['password_hash' => password_hash($pass, PASSWORD_DEFAULT)], 'id = ?', [$id]);
            flash('Password changed.');
        }
    }
    redirect('/admin/users.php');
}

$users = DB::all('SELECT id, username, email, name, created_at, last_login_at FROM {users} ORDER BY id');
admin_header('Users', 'users');
?>
<div class="adm-card adm-card-flush">
  <table class="adm-table">
    <thead><tr><th>User</th><th>E-mail</th><th>Last login</th><th></th></tr></thead>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><b><?= e($u['name'] ?: $u['username']) ?></b><br><small class="adm-muted">@<?= e($u['username']) ?><?= (int)$u['id'] === (int)$me['id'] ? ' · you' : '' ?></small></td>
      <td><?= e($u['email'] ?: '—') ?></td>
      <td><small><?= e(time_ago($u['last_login_at'])) ?></small></td>
      <td class="adm-right">
        <details class="adm-details">
          <summary class="adm-btn adm-btn-sm adm-btn-ghost">Set password</summary>
          <form method="post" class="adm-inline"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <input type="password" name="password" minlength="10" placeholder="New password" required autocomplete="new-password"><button class="adm-btn adm-btn-sm">Save</button></form>
        </details>
        <?php if ((int)$u['id'] !== (int)$me['id']): ?>
        <form method="post" class="adm-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button class="adm-btn adm-btn-sm adm-btn-danger" data-confirm="Delete this user?">Delete</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<div class="adm-card">
  <h3>Add a user</h3>
  <p class="adm-muted adm-small">Every user can edit and publish the website, see enquiries and manage users.</p>
  <form method="post" class="adm-form adm-grid2" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="create">
    <label>Username <input name="username" required></label>
    <label>Name <input name="name"></label>
    <label>E-mail <input type="email" name="email"></label>
    <label>Password (min. 10 characters) <input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
    <div><button class="adm-btn adm-btn-primary" type="submit">Create user</button></div>
  </form>
</div>
<?php admin_footer();
