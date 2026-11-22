<?php
/** Switch live edit mode on/off, then return to the page. */
require __DIR__ . '/_init.php';
require_login();
if (!csrf_valid((string)($_GET['_csrf'] ?? ''))) {
    redirect('/admin/');
}
$_SESSION['edit_mode'] = isset($_GET['on']);
$return = (string)($_GET['return'] ?? '/');
// Only allow returning to a local path.
if ($return === '' || $return[0] !== '/' || str_starts_with($return, '//')) {
    $return = '/';
}
redirect($return);
