<?php
/**
 * Family Banking System - Authentication Middleware
 */
require_once __DIR__ . '/../config.php';

check_auth();

// بروزرسانی زمان آخرین فعالیت برای کاربر آنلاین
$_SESSION['last_activity'] = time();
