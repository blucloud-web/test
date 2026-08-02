<?php
/**
 * Family Banking System - Configuration & DB Connector
 * سیستم بانکداری خانوادگی - تنظیمات و ارتباط با دیتابیس
 */

// جلوگیری از دسترسی مستقیم
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// تنظیمات منطقه زمانی و گزارش خطاها
date_default_timezone_set('Asia/Tehran');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '1');

// ثابت‌های سیستم
define('APP_NAME', 'سیستم بانکداری خانوادگی');
define('APP_VERSION', '1.0.0');
define('CURRENCY', 'تومان');
define('BASE_DIR', __DIR__);
define('UPLOAD_DIR', BASE_DIR . '/uploads');
define('AVATAR_DIR', UPLOAD_DIR . '/avatars');

// ایجاد پوشه‌های ضروری در صورت عدم وجود
if (!file_exists(BASE_DIR . '/data')) {
    @mkdir(BASE_DIR . '/data', 0777, true);
}
if (!file_exists(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0777, true);
}
if (!file_exists(AVATAR_DIR)) {
    @mkdir(AVATAR_DIR, 0777, true);
}

// تنظیمات دیتابیس
define('DB_TYPE', 'sqlite'); // 'sqlite' یا 'mysql'
define('DB_SQLITE_PATH', BASE_DIR . '/data/family_bank.sqlite');

define('DB_HOST', 'localhost');
define('DB_NAME', 'family_bank');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// راه‌اندازی سشن امن
if (session_status() === PHP_SESSION_NONE) {
    $session_dir = BASE_DIR . '/data/sessions';
    if (!file_exists($session_dir)) {
        @mkdir($session_dir, 0777, true);
    }
    ini_set('session.save_path', $session_dir);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * دریافت نمونه اتصال PDO به دیتابیس
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            if (DB_TYPE === 'sqlite') {
                $dsn = "sqlite:" . DB_SQLITE_PATH;
                $pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                // فعال‌سازی کلیدهای خارجی در SQLite
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
        } catch (PDOException $e) {
            die("خطا در اتصال به دیتابیس: " . $e->getMessage());
        }
    }
    return $pdo;
}

// لود کردن توابع کمکی
require_once BASE_DIR . '/includes/functions.php';

// ثبت اتولودر برای کلاس‌های مدل
spl_autoload_register(function ($class_name) {
    $file = BASE_DIR . '/models/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
