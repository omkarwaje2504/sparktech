<?php
require __DIR__ . '/_init.php';
if (is_post() && csrf_valid()) {
    logout();
}
redirect('/admin/login.php');
