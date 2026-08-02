<?php
/**
 * Family Banking System - Database Installer
 * نصب و بازنشانی اولیه ساختار دیتابیس به همراه داده‌های اولیه نمونه
 */

require_once __DIR__ . '/config.php';

$message = '';
$status_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install'])) {
    try {
        $db = getDB();
        
        if (DB_TYPE === 'sqlite') {
            // ساختار SQLite
            $db->exec("
                DROP TABLE IF EXISTS system_logs;
                DROP TABLE IF EXISTS system_settings;
                DROP TABLE IF EXISTS notifications;
                DROP TABLE IF EXISTS central_fund_logs;
                DROP TABLE IF EXISTS loan_installments;
                DROP TABLE IF EXISTS loans;
                DROP TABLE IF EXISTS deposit_interest_logs;
                DROP TABLE IF EXISTS deposits;
                DROP TABLE IF EXISTS transactions;
                DROP TABLE IF EXISTS bank_accounts;
                DROP TABLE IF EXISTS users;

                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    full_name TEXT NOT NULL,
                    username TEXT NOT NULL UNIQUE,
                    password TEXT NOT NULL,
                    email TEXT NULL,
                    role TEXT NOT NULL DEFAULT 'member',
                    status TEXT NOT NULL DEFAULT 'active',
                    mobile TEXT NULL,
                    national_id TEXT NULL,
                    address TEXT NULL,
                    avatar TEXT NULL,
                    notes TEXT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                );

                CREATE TABLE bank_accounts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    account_number TEXT NOT NULL UNIQUE,
                    account_type TEXT NOT NULL DEFAULT 'current',
                    balance REAL NOT NULL DEFAULT 0.00,
                    status TEXT NOT NULL DEFAULT 'active',
                    open_date TEXT NOT NULL,
                    description TEXT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                );

                CREATE TABLE transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tracking_code TEXT NOT NULL UNIQUE,
                    source_account_id INTEGER NULL,
                    dest_account_id INTEGER NULL,
                    user_id INTEGER NOT NULL,
                    type TEXT NOT NULL,
                    amount REAL NOT NULL,
                    category TEXT NULL DEFAULT 'عمومی',
                    status TEXT NOT NULL DEFAULT 'completed',
                    description TEXT NULL,
                    attachment TEXT NULL,
                    created_by INTEGER NOT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    FOREIGN KEY (source_account_id) REFERENCES bank_accounts (id) ON DELETE SET NULL,
                    FOREIGN KEY (dest_account_id) REFERENCES bank_accounts (id) ON DELETE SET NULL,
                    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
                );

                CREATE TABLE deposits (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    account_id INTEGER NOT NULL,
                    deposit_number TEXT NOT NULL UNIQUE,
                    principal_amount REAL NOT NULL,
                    interest_rate REAL NOT NULL DEFAULT 0.00,
                    term_months INTEGER NOT NULL DEFAULT 12,
                    auto_renew INTEGER NOT NULL DEFAULT 1,
                    status TEXT NOT NULL DEFAULT 'active',
                    open_date TEXT NOT NULL,
                    maturity_date TEXT NOT NULL,
                    total_interest_paid REAL NOT NULL DEFAULT 0.00,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    FOREIGN KEY (account_id) REFERENCES bank_accounts (id) ON DELETE CASCADE
                );

                CREATE TABLE deposit_interest_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    deposit_id INTEGER NOT NULL,
                    amount REAL NOT NULL,
                    payment_date TEXT NOT NULL,
                    description TEXT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (deposit_id) REFERENCES deposits (id) ON DELETE CASCADE
                );

                CREATE TABLE loans (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    loan_number TEXT NOT NULL UNIQUE,
                    amount REAL NOT NULL,
                    interest_rate REAL NOT NULL DEFAULT 0.00,
                    term_months INTEGER NOT NULL DEFAULT 12,
                    monthly_installment REAL NOT NULL,
                    total_repayment REAL NOT NULL,
                    guarantor_user_id INTEGER NULL,
                    status TEXT NOT NULL DEFAULT 'pending',
                    request_date TEXT NOT NULL,
                    approval_date TEXT NULL,
                    notes TEXT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    FOREIGN KEY (guarantor_user_id) REFERENCES users (id) ON DELETE SET NULL
                );

                CREATE TABLE loan_installments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    loan_id INTEGER NOT NULL,
                    installment_number INTEGER NOT NULL,
                    due_date TEXT NOT NULL,
                    principal_amount REAL NOT NULL,
                    interest_amount REAL NOT NULL DEFAULT 0.00,
                    penalty_amount REAL NOT NULL DEFAULT 0.00,
                    total_amount REAL NOT NULL,
                    status TEXT NOT NULL DEFAULT 'pending',
                    payment_date TEXT NULL,
                    transaction_id INTEGER NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (loan_id) REFERENCES loans (id) ON DELETE CASCADE,
                    FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE SET NULL
                );

                CREATE TABLE central_fund_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL,
                    amount REAL NOT NULL,
                    title TEXT NOT NULL,
                    description TEXT NULL,
                    created_by INTEGER NOT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
                );

                CREATE TABLE notifications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    title TEXT NOT NULL,
                    message TEXT NOT NULL,
                    type TEXT NOT NULL DEFAULT 'info',
                    is_read INTEGER NOT NULL DEFAULT 0,
                    link TEXT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                );

                CREATE TABLE system_settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    setting_key TEXT NOT NULL UNIQUE,
                    setting_value TEXT NULL,
                    description TEXT NULL,
                    updated_at TEXT NOT NULL
                );

                CREATE TABLE system_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NULL,
                    action TEXT NOT NULL,
                    module TEXT NOT NULL,
                    details TEXT NULL,
                    ip_address TEXT NULL,
                    user_agent TEXT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
                );
            ");
        } else {
            // ساختار MySQL
            $sql = file_get_contents(__DIR__ . '/database.sql');
            $db->exec($sql);
        }

        // افزودن اعضای اولیه
        $pass = password_hash('123456', PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');

        // پاکسازی احتمالی
        $db->exec("DELETE FROM users;");
        $stmt = $db->prepare("INSERT INTO users (id, full_name, username, password, email, role, status, mobile, national_id, address, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 'محمد حسینی (مدیر کل)', 'admin', $pass, 'admin@familybank.local', 'admin', 'active', '09121111111', '0012345678', 'تهران، آزادی', 'مدیر ارشد سیستم', $now, $now]);
        $stmt->execute([2, 'رضا حسینی (حسابدار)', 'accountant', $pass, 'acc@familybank.local', 'accountant', 'active', '09122222222', '0023456789', 'تهران، ولیعصر', 'حسابدار رسمی', $now, $now]);
        $stmt->execute([3, 'سارا حسینی (عضو خانواده)', 'sara', $pass, 'sara@familybank.local', 'member', 'active', '09123333333', '0034567890', 'اصفهان', 'عضو فعال خانواده', $now, $now]);
        $stmt->execute([4, 'علی حسینی (عضو خانواده)', 'ali', $pass, 'ali@familybank.local', 'member', 'active', '09124444444', '0045678901', 'مشهد', 'عضو خانواده', $now, $now]);
        $stmt->execute([5, 'مریم حسینی (بازرس)', 'viewer', $pass, 'viewer@familybank.local', 'readonly', 'active', '09125555555', '0056789012', 'شیراز', 'دسترسی فقط خواندنی', $now, $now]);

        // افزودن تنظیمات اولیه
        set_setting('default_loan_interest_rate', '4.00', 'نرخ کارمزد/سود وام‌ها');
        set_setting('default_deposit_interest_rate', '18.00', 'نرخ سود سپرده‌های سرمایه‌گذاری');
        set_setting('late_penalty_rate', '1.50', 'نرخ جریمه دیرکرد اقساط');
        set_setting('central_fund_balance', '500000000.00', 'موجودی کل صندوق مرکزی خانواده');
        set_setting('bank_name', 'صندوق بانکداری خانوادگی حسینی', 'نام صندوق');
        set_setting('currency', 'تومان', 'واحد پول');

        // حساب‌های نمونه
        $db->exec("DELETE FROM bank_accounts;");
        $stmt = $db->prepare("INSERT INTO bank_accounts (id, user_id, account_number, account_type, balance, status, open_date, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 1, 'ACC-1001-8842', 'current', 125000000.00, 'active', '2025-01-01', 'حساب جاری اصلی مدیر', $now, $now]);
        $stmt->execute([2, 2, 'ACC-1002-3109', 'current', 45000000.00, 'active', '2025-01-05', 'حساب جاری حسابدار', $now, $now]);
        $stmt->execute([3, 3, 'ACC-1003-9214', 'short_term', 80000000.00, 'active', '2025-01-10', 'حساب کوتاه‌مدت پس‌انداز سارا', $now, $now]);
        $stmt->execute([4, 3, 'ACC-1003-4410', 'current', 15000000.00, 'active', '2025-01-12', 'حساب جاری روزمره سارا', $now, $now]);
        $stmt->execute([5, 4, 'ACC-1004-7712', 'long_term', 60000000.00, 'active', '2025-02-01', 'حساب سرمایه‌گذاری بلندمدت علی', $now, $now]);

        // تراکنش‌های نمونه
        $db->exec("DELETE FROM transactions;");
        $stmt = $db->prepare("INSERT INTO transactions (id, tracking_code, source_account_id, dest_account_id, user_id, type, amount, category, status, description, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 'TRX-20260101-9981', null, 1, 1, 'deposit', 100000000.00, 'سرمایه اولیه', 'completed', 'واریز اولیه سرمایه به حساب مدیر', 1, $now]);
        $stmt->execute([2, 'TRX-20260105-4412', 1, 2, 2, 'transfer', 45000000.00, 'انتقال داخلی', 'completed', 'انتقال بودجه جاری به حسابدار', 1, $now]);
        $stmt->execute([3, 'TRX-20260110-1049', 1, 3, 3, 'member_transfer', 80000000.00, 'پس‌انداز خانوادگی', 'completed', 'انتقال اعتبار از مدیر به سارا', 1, $now]);

        // سپرده نمونه
        $db->exec("DELETE FROM deposits;");
        $stmt = $db->prepare("INSERT INTO deposits (id, user_id, account_id, deposit_number, principal_amount, interest_rate, term_months, auto_renew, status, open_date, maturity_date, total_interest_paid, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 3, 3, 'DEP-9001-3312', 50000000.00, 18.00, 12, 1, 'active', '2025-01-15', '2026-01-15', 4500000.00, $now]);

        // وام و اقساط نمونه
        $db->exec("DELETE FROM loans;");
        $stmt = $db->prepare("INSERT INTO loans (id, user_id, loan_number, amount, interest_rate, term_months, monthly_installment, total_repayment, guarantor_user_id, status, request_date, approval_date, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 4, 'LON-7001-5521', 30000000.00, 4.00, 10, 3100000.00, 31000000.00, 1, 'active', '2025-02-05', '2025-02-06', 'وام خرید تجهیزات با ضمانت مدیر', $now]);

        $db->exec("DELETE FROM loan_installments;");
        $stmt = $db->prepare("INSERT INTO loan_installments (id, loan_id, installment_number, due_date, principal_amount, interest_amount, penalty_amount, total_amount, status, payment_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 1, 1, '2025-03-05', 3000000.00, 100000.00, 0.00, 3100000.00, 'paid', '2025-03-04', $now]);
        $stmt->execute([2, 1, 2, '2025-04-05', 3000000.00, 100000.00, 0.00, 3100000.00, 'paid', '2025-04-05', $now]);
        $stmt->execute([3, 1, 3, '2025-05-05', 3000000.00, 100000.00, 0.00, 3100000.00, 'pending', null, $now]);

        // صندوق مرکزی نمونه
        $db->exec("DELETE FROM central_fund_logs;");
        $stmt = $db->prepare("INSERT INTO central_fund_logs (id, type, amount, title, description, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 'capital_injection', 500000000.00, 'سرمایه اولیه صندوق مرکزی', 'تامین اولیه سرمایه صندوق', 1, $now]);

        // اعلانات نمونه
        $db->exec("DELETE FROM notifications;");
        $stmt = $db->prepare("INSERT INTO notifications (id, user_id, title, message, type, is_read, link, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 4, 'خوش‌آمدگویی', 'به سیستم بانکداری خانوادگی خوش آمدید.', 'info', 0, 'dashboard.php', $now]);

        log_activity('نصب سیستم', 'سیستم', 'دیتابیس سیستم به همراه داده‌های نمونه اولیه راه‌اندازی گردید.');

        $message = 'دیتابیس و داده‌های اولیه با موفقیت راه‌اندازی و نصب گردیدند!';
        $status_class = 'alert-success';
    } catch (Exception $e) {
        $message = 'خطا در اجرای فرآیند نصب: ' . $e->getMessage();
        $status_class = 'alert-danger';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نصب دیتابیس - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.min.css">
</head>
<body class="bg-light font-vazir d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-lg border-0 rounded-4 p-4" style="max-width: 550px; width: 100%;">
        <div class="text-center mb-4">
            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                <i class="fa-solid fa-database fs-2"></i>
            </div>
            <h4 class="fw-bold text-dark">نصب اولیه سیستم بانکداری خانوادگی</h4>
            <p class="text-muted fs-7">راه‌اندازی جداول دیتابیس و نمونه داده‌های اولیه</p>
        </div>

        <?php if ($message): ?>
            <div class="alert <?php echo $status_class; ?> rounded-3 fs-7 mb-4">
                <i class="fa-solid fa-circle-info me-2"></i><?php echo e($message); ?>

            </div>
        <?php endif; ?>

        <div class="card bg-white border p-3 rounded-3 mb-4 fs-7">
            <h6 class="fw-bold mb-2 text-primary"><i class="fa-solid fa-users me-2"></i>حساب‌های پیش‌فرض تست:</h6>
            <ul class="mb-0 ps-3">
                <li><strong>مدیر کل:</strong> نام‌کاربری: <code>admin</code> | رمز: <code>123456</code></li>
                <li><strong>حسابدار:</strong> نام‌کاربری: <code>accountant</code> | رمز: <code>123456</code></li>
                <li><strong>عضو خانواده:</strong> نام‌کاربری: <code>sara</code> | رمز: <code>123456</code></li>
                <li><strong>عضو خانواده:</strong> نام‌کاربری: <code>ali</code> | رمز: <code>123456</code></li>
                <li><strong>فقط خواندنی:</strong> نام‌کاربری: <code>viewer</code> | رمز: <code>123456</code></li>
            </ul>
        </div>

        <form method="POST">
            <input type="hidden" name="run_install" value="1">
            <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-bold">
                <i class="fa-solid fa-rocket me-2"></i>ایجاد جداول و درج داده‌های نمونه
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none text-muted fs-7">ورود به صفحه ورود سیستم <i class="fa-solid fa-arrow-left ms-1"></i></a>
        </div>
    </div>
</body>
</html>
