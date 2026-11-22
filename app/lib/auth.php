<?php
/**
 * Sessions, login, CSRF and edit-mode state.
 */

const ST_SESSION_NAME = 'stcms_session';

function session_boot(bool $force = false): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Public visitors never get a cookie; only admins (or forms that need one).
    if (!$force && empty($_COOKIE[ST_SESSION_NAME])) {
        return;
    }
    if (headers_sent()) {
        return;
    }
    session_name(ST_SESSION_NAME);
    $params = [
        'lifetime' => 0,
        'path' => (base_path() ?: '') . '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($params);
    } else {
        session_set_cookie_params(0, $params['path'] . '; samesite=Lax', '', $params['secure'], true);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '28800');
    session_start();

    // Idle timeout: 8 hours.
    if (isset($_SESSION['uid'], $_SESSION['seen']) && time() - (int)$_SESSION['seen'] > 28800) {
        $_SESSION = [];
    }
    $_SESSION['seen'] = time();
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['uid'])) {
        try {
            $user = DB::row('SELECT id, username, email, name, role FROM {users} WHERE id = ?', [(int)$_SESSION['uid']]);
        } catch (Throwable $e) {
            $user = null;
        }
    }
    return $user;
}

function is_admin(): bool
{
    return current_user() !== null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $back = $_SERVER['REQUEST_URI'] ?? '';
        redirect('/admin/login.php' . ($back ? '?next=' . rawurlencode($back) : ''));
    }
    return $user;
}

function login_throttled(string $ip): bool
{
    $since = date('Y-m-d H:i:s', time() - 900);
    $count = (int)DB::value('SELECT COUNT(*) FROM {login_attempts} WHERE ip = ? AND created_at > ?', [$ip, $since]);
    return $count >= 8;
}

/** @return true|string true on success, otherwise an error message */
function attempt_login(string $username, string $password)
{
    $ip = request_ip();
    if (login_throttled($ip)) {
        return 'Too many failed attempts. Please wait 15 minutes and try again.';
    }
    $user = DB::row('SELECT * FROM {users} WHERE username = ? OR email = ?', [$username, $username]);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        DB::insert('login_attempts', ['ip' => $ip, 'username' => substr($username, 0, 64), 'created_at' => now()]);
        return 'Invalid username or password.';
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        DB::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
    }
    session_boot(true);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['seen'] = time();
    DB::update('users', ['last_login_at' => now()], 'id = ?', [$user['id']]);
    DB::exec('DELETE FROM {login_attempts} WHERE ip = ?', [$ip]);
    return true;
}

function logout(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_boot(true);
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = random_token(16);
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token = null): bool
{
    if ($token === null) {
        $token = (string)($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    }
    return session_status() === PHP_SESSION_ACTIVE
        && !empty($_SESSION['csrf'])
        && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

function csrf_require(): void
{
    if (!csrf_valid()) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

/** Live editing is on when an admin switched it on for this session. */
function edit_mode(): bool
{
    static $mode = null;
    if ($mode === null) {
        $mode = is_admin() && !empty($_SESSION['edit_mode']);
    }
    return $mode;
}
