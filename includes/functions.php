<?php
/**
 * Family Banking System - Helper Functions
 * توابع عمومی، امنیتی، قالب‌بندی و اعتبارسنجی
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

/**
 * پاکسازی ورودی‌ها برای جلوگیری از XSS
 */
function e($value) {
    if ($value === null) return '';
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * ایجاد توکن CSRF
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * فیلد مخفی CSRF برای فرم‌ها
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * تایید توکن CSRF
 */
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        flash('danger', 'خطای امنیتی: توکن CSRF معتبر نمی‌باشد.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
        exit;
    }
}

/**
 * ارسال پیام‌های Flash بین صفحات
 */
function flash($type = null, $message = null) {
    if ($type !== null && $message !== null) {
        $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
    } else {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
}

/**
 * نمایش پیام‌های Flash به زبان فارسی با Bootstrap Alert
 */
function render_flash_messages() {
    $messages = flash();
    if (empty($messages)) return;
    
    foreach ($messages as $msg) {
        $type = e($msg['type']);
        $text = e($msg['message']);
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
        echo "<i class='fa-solid fa-circle-info me-2'></i>" . $text;
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
    }
}

/**
 * هدایت به صفحه دیگر
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * چک کردن لاگین بودن کاربر
 */
function check_auth() {
    if (empty($_SESSION['user_id'])) {
        flash('warning', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        redirect('login.php');
    }
}

/**
 * چک کردن سطح دسترسی نقش کاربر
 * نقش‌ها: admin > accountant > member > readonly
 */
function check_role($allowed_roles = []) {
    check_auth();
    $current_role = $_SESSION['user_role'] ?? '';
    if (!in_array($current_role, (array)$allowed_roles)) {
        flash('danger', 'شما مجوز دسترسی به این بخش را ندارید.');
        redirect('dashboard.php');
    }
}

/**
 * فرمت مبلغ به صورت سه رقم سه رقم به همراه واحد پول
 */
function format_money($amount, $show_currency = true) {
    $num = number_format((float)$amount, 0, '.', ',');
    return $num . ($show_currency ? ' ' . CURRENCY : '');
}

/**
 * تبدیل اعداد انگلیسی به فارسی برای نمایش شکیل‌تر
 */
function fa_number($number) {
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return str_replace($en, $fa, (string)$number);
}

/**
 * تولید کد پیگیری یکتا
 */
function generate_code($prefix = 'TRX') {
    return $prefix . '-' . date('Ymd') . '-' . rand(1000, 9999);
}

/**
 * ثبت لاگ فعالیت‌های سیستم
 */
function log_activity($action, $module, $details = '') {
    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

        $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, module, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $module, $details, $ip, $ua, date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        // نادیده گرفتن خطا در لاگ‌گیری برای عدم توقف سیستم
    }
}

/**
 * ارسال اعلان جدید به کاربر
 */
function send_notification($user_id, $title, $message, $type = 'info', $link = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, link, created_at) VALUES (?, ?, ?, ?, 0, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type, $link, date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        // نادیده گرفتن خطا
    }
}

/**
 * دریافت تنظیم سیستم از دیتابیس
 */
function get_setting($key, $default = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * بروزرسانی یا درج تنظیم در دیتابیس
 */
function set_setting($key, $value, $description = '') {
    $db = getDB();
    $now = date('Y-m-d H:i:s');
    if (DB_TYPE === 'sqlite') {
        $stmt = $db->prepare("INSERT OR REPLACE INTO system_settings (setting_key, setting_value, description, updated_at) VALUES (?, ?, ?, ?)");
    } else {
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, description, updated_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description), updated_at = VALUES(updated_at)");
    }
    $stmt->execute([$key, $value, $description, $now]);
}

/**
 * تبدیل نوع حساب به عنوان فارسی
 */
function get_account_type_name($type) {
    $types = [
        'current' => 'جاری',
        'qard' => 'قرض‌الحسنه',
        'short_term' => 'کوتاه‌مدت',
        'long_term' => 'بلندمدت'
    ];
    return $types[$type] ?? $type;
}

/**
 * تبدیل نقش به عنوان فارسی
 */
function get_role_name($role) {
    $roles = [
        'admin' => 'مدیر کل',
        'accountant' => 'حسابدار',
        'member' => 'عضو خانواده',
        'readonly' => 'فقط خواندنی'
    ];
    return $roles[$role] ?? $role;
}

/**
 * نشانگر وضعیت با کلاس Bootstrap
 */
function get_status_badge($status) {
    switch ($status) {
        case 'active':
        case 'completed':
        case 'approved':
        case 'paid':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i>فعال / تایید شده</span>';
        case 'pending':
            return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-solid fa-clock me-1"></i>در انتظار</span>';
        case 'inactive':
        case 'blocked':
        case 'closed':
        case 'cancelled':
        case 'rejected':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-xmark me-1"></i>غیرفعال / لغو شده</span>';
        case 'overdue':
            return '<span class="badge bg-danger text-white"><i class="fa-solid fa-exclamation-triangle me-1"></i>معوقه</span>';
        case 'matured':
        case 'settled':
            return '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fa-solid fa-flag-checkered me-1"></i>سررسید / تسویه</span>';
        default:
            return '<span class="badge bg-secondary">' . e($status) . '</span>';
    }
}
