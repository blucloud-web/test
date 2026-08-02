<?php
if (!defined('APP_INIT')) die('Direct access not permitted');

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? 'member';
?>
<!-- Sidebar Navigation -->
<div class="bg-white border-end shadow-sm" id="sidebar-wrapper" style="width: 260px; min-height: 100vh;">
    <div class="sidebar-heading px-4 py-3 border-bottom text-secondary fw-bold text-uppercase fs-7 tracking-wider">
        منوی اصلی سیستم
    </div>
    <div class="list-group list-group-flush p-2">
        <!-- داشبورد -->
        <a href="dashboard.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'dashboard.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
            <i class="fa-solid fa-chart-line me-2 <?php echo ($current_page == 'dashboard.php') ? '' : 'text-primary'; ?>"></i>
            داشبورد مدیریت
        </a>

        <!-- حساب‌های بانکی -->
        <a href="accounts.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'accounts.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
            <i class="fa-solid fa-wallet me-2 <?php echo ($current_page == 'accounts.php') ? '' : 'text-success'; ?>"></i>
            حساب‌های بانکی
        </a>

        <!-- تراکنش‌ها -->
        <a href="transactions.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'transactions.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
            <i class="fa-solid fa-right-left me-2 <?php echo ($current_page == 'transactions.php') ? '' : 'text-info'; ?>"></i>
            تراکنش‌ها و انتقال
        </a>

        <!-- سپرده‌ها -->
        <a href="deposits.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'deposits.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
            <i class="fa-solid fa-piggy-bank me-2 <?php echo ($current_page == 'deposits.php') ? '' : 'text-warning'; ?>"></i>
            سپرده‌گذاری خانوادگی
        </a>

        <!-- وام‌ها -->
        <a href="loans.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'loans.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
            <i class="fa-solid fa-hand-holding-dollar me-2 <?php echo ($current_page == 'loans.php') ? '' : 'text-danger'; ?>"></i>
            وام‌ها و اقساط
        </a>

        <!-- صندوق مرکزی -->
        <a href="central_fund.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'central_fund.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
            <i class="fa-solid fa-vault me-2 <?php echo ($current_page == 'central_fund.php') ? '' : 'text-purple'; ?>"></i>
            صندوق مرکزی
        </a>

        <!-- گزارش‌ها -->
        <a href="reports.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'reports.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
            <i class="fa-solid fa-file-invoice-dollar me-2 <?php echo ($current_page == 'reports.php') ? '' : 'text-secondary'; ?>"></i>
            گزارش‌ها و نمودارها
        </a>

        <?php if ($role === 'admin'): ?>
            <div class="sidebar-heading px-3 py-2 mt-3 text-secondary fw-bold text-uppercase fs-7">
                مدیریت ارشد سیستم
            </div>

            <!-- کاربران -->
            <a href="users.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'users.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
                <i class="fa-solid fa-users me-2 <?php echo ($current_page == 'users.php') ? '' : 'text-primary'; ?>"></i>
                مدیریت کاربران
            </a>

            <!-- تنظیمات و لاگ‌ها -->
            <a href="settings.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 <?php echo ($current_page == 'settings.php') ? 'active bg-primary text-white' : 'text-dark hover-bg-light'; ?>">
                <i class="fa-solid fa-gears me-2 <?php echo ($current_page == 'settings.php') ? '' : 'text-dark'; ?>"></i>
                تنظیمات و لاگ‌ها
            </a>
        <?php endif; ?>

        <div class="sidebar-heading px-3 py-2 mt-3 text-secondary fw-bold text-uppercase fs-7">
            سایر گزینه‌ها
        </div>
        <a href="install.php" target="_blank" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 text-muted hover-bg-light">
            <i class="fa-solid fa-database me-2 text-info"></i>
            نصب اولیه / بازنشانی DB
        </a>
        <a href="logout.php" class="list-group-item list-group-item-action rounded-3 mb-1 border-0 py-2.5 px-3 text-danger hover-bg-light">
            <i class="fa-solid fa-right-from-bracket me-2"></i>
            خروج از حساب
        </a>
    </div>
</div>
