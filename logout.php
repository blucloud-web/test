<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
    log_activity('خروج از سیستم', 'احراز هویت', 'خروج کاربر ' . ($_SESSION['username'] ?? ''));
}

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

flash('info', 'شما با موفقیت از سیستم خارج شدید.');
redirect('login.php');
