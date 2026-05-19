<?php
require_once __DIR__ . '/app/functions.php';
if (is_logged_in() && ($_SESSION['user']['role_key'] ?? '') === 'admin_website') {
    redirect('sysadmin/dashboard.php');
}
redirect('login.php');
