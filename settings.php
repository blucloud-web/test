<?php
/**
 * Family Banking System - System Settings & Logs
 */
require_once __DIR__ . '/includes/auth_check.php';
check_role(['admin']); // فقط مدیر ارشد

$page_title = 'تنظیمات و لاگ‌های سیستم - ' . APP_NAME;

$logModel = new SystemLog();

// دانلود پشتیبان دیتابیس
if (isset($_GET['action']) && $_GET['action'] === 'backup_db') {
    if (DB_TYPE === 'sqlite' && file_exists(DB_PATH)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=family_bank_backup_' . date('Ymd_His') . '.sqlite');
        header('Content-Length: ' . filesize(DB_PATH));
        readfile(DB_PATH);
        exit();
    } else {
        flash('danger', 'فایل دیتابیس برای دانلود یافت نشد.');
        redirect('settings.php');
    }
}

// بروزرسانی تنظیمات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['save_settings'])) {
        set_setting('bank_name', trim($_POST['bank_name']), 'نام صندوق');
        set_setting('default_loan_interest_rate', (float)$_POST['default_loan_interest_rate'], 'نرخ سود وام');
        set_setting('default_deposit_interest_rate', (float)$_POST['default_deposit_interest_rate'], 'نرخ سود سپرده');
        set_setting('late_penalty_rate', (float)$_POST['late_penalty_rate'], 'نرخ جریمه دیرکرد');

        log_activity('تغییر تنظیمات', 'تنظیمات', 'بروزرسانی پارامترهای اصلی سیستم');
        flash('success', 'تنظیمات سیستم با موفقیت بروزرسانی شد.');
        redirect('settings.php');
    }
}

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'module' => trim($_GET['module'] ?? '')
];

$logs = $logModel->getAll($filters);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">تنظیمات و سوابق امینتی (Logs)</h3>
                <p class="text-muted fs-7 mb-0">پیکربندی درصد سودها، پشتیبان‌گیری دیتابیس و بررسی دقیق رخدادهای سیستم</p>
            </div>
            <a href="settings.php?action=backup_db" class="btn btn-dark rounded-3 py-2 px-3 fw-semibold mt-3 mt-md-0 shadow-sm">
                <i class="fa-solid fa-download me-1"></i>دانلود پشتیبان کامل دیتابیس (Backup)
            </a>
        </div>

        <div class="row g-4 mb-4">
            <!-- فرم تنظیمات عمومی -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-3 mb-3"><i class="fa-solid fa-sliders me-2 text-primary"></i>تنظیمات پایه سیستم</h5>
                    
                    <form method="POST" action="settings.php">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="save_settings" value="1">
                        
                        <div class="mb-3 fs-7">
                            <label class="form-label fw-semibold text-secondary">نام صندوق یا بانک خانوادگی</label>
                            <input type="text" name="bank_name" class="form-control" required value="<?php echo e(get_setting('bank_name', APP_NAME)); ?>">
                        </div>

                        <div class="mb-3 fs-7">
                            <label class="form-label fw-semibold text-secondary">نرخ سود/کارمزد پیش‌فرض وام‌ها (%)</label>
                            <input type="number" step="0.1" name="default_loan_interest_rate" class="form-control" required value="<?php echo e(get_setting('default_loan_interest_rate', 4)); ?>">
                        </div>

                        <div class="mb-3 fs-7">
                            <label class="form-label fw-semibold text-secondary">نرخ سود پیش‌فرض سپرده‌های سرمایه‌گذاری (%)</label>
                            <input type="number" step="0.1" name="default_deposit_interest_rate" class="form-control" required value="<?php echo e(get_setting('default_deposit_interest_rate', 18)); ?>">
                        </div>

                        <div class="mb-4 fs-7">
                            <label class="form-label fw-semibold text-secondary">نرخ جریمه دیرکرد اقساط معوق (%)</label>
                            <input type="number" step="0.1" name="late_penalty_rate" class="form-control" required value="<?php echo e(get_setting('late_penalty_rate', 1.5)); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold rounded-3">
                            <i class="fa-solid fa-floppy-disk me-1"></i>ذخیره تنظیمات
                        </button>
                    </form>
                </div>
            </div>

            <!-- جدول لاگ‌های رخ داده در سیستم -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                    <h5 class="fw-bold text-dark border-bottom pb-3 mb-3"><i class="fa-solid fa-list-check me-2 text-primary"></i>سوابق عملیات کاربران (Activity Logs)</h5>
                    
                    <form method="GET" action="settings.php" class="row g-2 mb-3 no-print">
                        <div class="col-8">
                            <input type="text" name="search" class="form-control form-control-sm bg-light" placeholder="جستجو در لاگ‌ها..." value="<?php echo e($filters['search']); ?>">
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-secondary btn-sm w-100">جستجو</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-8">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th>#</th>
                                    <th>کاربر</th>
                                    <th>عملیات</th>
                                    <th>ماژول</th>
                                    <th>جزئیات</th>
                                    <th>تاریخ و زمان</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">هیچ لاگی یافت نشد.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $l): ?>
                                        <tr>
                                            <td><?php echo $l['id']; ?></td>
                                            <td class="fw-bold"><?php echo e($l['user_name'] ?? 'مهمان/سیستم'); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?php echo e($l['action']); ?></span></td>
                                            <td><?php echo e($l['module']); ?></td>
                                            <td class="text-muted"><?php echo e($l['details']); ?></td>
                                            <td class="text-muted"><?php echo e($l['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
