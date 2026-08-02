<?php
/**
 * Family Banking System - Login Page
 */
require_once __DIR__ . '/config.php';

// اگر کاربر قبلا وارد شده باشد
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'لطفاً نام کاربری و رمز عبور را وارد کنید.';
    } else {
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);

        if ($user) {
            // تنظیم سشن‌های کاربر
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_fullname'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            log_activity('ورود به سیستم', 'احراز هویت', 'ورود موفقیت‌آمیز کاربر ' . $user['username']);

            flash('success', 'به سیستم بانکداری خانوادگی خوش آمدید، ' . e($user['full_name']) . '!');
            redirect('dashboard.php');
        } else {
            $error = 'نام کاربری یا رمز عبور اشتباه است یا حساب غیرفعال می‌باشد.';
            log_activity('ورود ناموفق', 'احراز هویت', 'تلاش ناموفق برای ورود با نام کاربری: ' . $username);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Vazirmatn Font -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light font-vazir d-flex align-items-center justify-content-center min-vh-100 py-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-5 col-xl-4">
            
            <div class="text-center mb-4">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 75px; height: 75px;">
                    <i class="fa-solid fa-building-columns fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark"><?php echo e(APP_NAME); ?></h3>
                <p class="text-muted fs-7">سامانه مدیریت خصوصی مالی و صندوق اعضای خانواده</p>
            </div>

            <div class="card shadow-lg border-0 rounded-4 p-4 bg-white">
                <h5 class="fw-bold mb-3 text-dark text-center border-bottom pb-3">ورود به حساب کاربری</h5>

                <?php render_flash_messages(); ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-3 fs-7 mb-3">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo e($error); ?>

                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" autocomplete="off">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label for="username" class="form-label fs-7 fw-semibold text-secondary">نام کاربری</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0 rounded-start-3"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-label form-control bg-light border-start-0 rounded-end-3 py-2 fs-7" id="username" name="username" placeholder="نام کاربری خود را وارد کنید" required value="admin">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fs-7 fw-semibold text-secondary">رمز عبور</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0 rounded-start-3"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control bg-light border-start-0 rounded-end-3 py-2 fs-7" id="password" name="password" placeholder="رمز عبور خود را وارد کنید" required value="123456">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-bold mb-3 shadow-sm">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>ورود به پنل کاربری
                    </button>
                </form>

                <div class="bg-light p-3 rounded-3 border mt-2">
                    <div class="fs-7 fw-bold text-secondary mb-2"><i class="fa-solid fa-key me-1 text-warning"></i>دسترسی سریع نمونه برای تست:</div>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary fs-8 py-1 px-2" onclick="setLogin('admin', '123456')">مدیر کل</button>
                        <button type="button" class="btn btn-sm btn-outline-success fs-8 py-1 px-2" onclick="setLogin('accountant', '123456')">حسابدار</button>
                        <button type="button" class="btn btn-sm btn-outline-info fs-8 py-1 px-2" onclick="setLogin('sara', '123456')">عضو خانواده</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary fs-8 py-1 px-2" onclick="setLogin('viewer', '123456')">بازرس</button>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3 text-muted fs-7">
                <span>تمام حقوق محفوظ است &copy; <?php echo date('Y'); ?></span>
                <span class="mx-2">|</span>
                <a href="install.php" class="text-muted text-decoration-none"><i class="fa-solid fa-gear me-1"></i>نصب دیتابیس</a>
            </div>

        </div>
    </div>
</div>

<script>
function setLogin(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
}
</script>

</body>
</html>
