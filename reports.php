<?php
/**
 * Family Banking System - Financial Reports & Statistics
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'گزارش‌های مالی و صورت‌حساب‌ها - ' . APP_NAME;

$userModel = new User();
$accountModel = new Account();
$transModel = new Transaction();
$loanModel = new Loan();
$depositModel = new Deposit();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'member';

$selected_user = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : ($user_role === 'member' ? $user_id : null);
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

// دریافت خروجی CSV تراکنش‌ها در صورت درخواست
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $list = $transModel->getAll([
        'user_id' => $selected_user,
        'date_from' => $date_from,
        'date_to' => $date_to
    ]);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=transactions_report_' . date('Ymd') . '.csv');
    
    $output = fopen('php://output', 'w');
    // افزودن BOM برای نمایش صحیح حروف فارسی در اکسل
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['کد پیگیری', 'نام عضو', 'نوع تراکنش', 'مبلغ (تومان)', 'دسته‌بندی', 'تاریخ ثبت']);
    foreach ($list as $r) {
        fputcsv($output, [$r['tracking_code'], $r['user_name'], $r['type'], $r['amount'], $r['category'], $r['created_at']]);
    }
    fclose($output);
    exit();
}

$transactions = $transModel->getAll([
    'user_id' => $selected_user,
    'date_from' => $date_from,
    'date_to' => $date_to
]);

$allUsers = $userModel->getAll('', '', 'active');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">گزارش‌ها و صورت‌حساب‌های جامع</h3>
                <p class="text-muted fs-7 mb-0">گزارش‌گیری تخصصی، خروجی اکسل (CSV) و چاپ صورت‌حساب‌های مالی اعضا</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="reports.php?export=csv&user_id=<?php echo $selected_user; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-outline-success btn-sm rounded-3 py-2 px-3 fw-semibold bg-white">
                    <i class="fa-solid fa-file-excel me-1"></i>دانلود فایل Excel (CSV)
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-3 py-2 px-3 fw-semibold bg-white">
                    <i class="fa-solid fa-print me-1"></i>چاپ صورت‌حساب
                </button>
            </div>
        </div>

        <!-- فیلتر گزارش -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white no-print">
            <form method="GET" action="reports.php" class="row g-2">
                <?php if ($user_role !== 'member'): ?>
                    <div class="col-12 col-md-4">
                        <select name="user_id" class="form-select form-select-sm bg-light">
                            <option value="">همه اعضای خانواده</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo ($selected_user == $u['id']) ? 'selected' : ''; ?>><?php echo e($u['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-6 col-md-3">
                    <input type="date" name="date_from" class="form-control form-control-sm bg-light" value="<?php echo e($date_from); ?>" placeholder="از تاریخ">
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" name="date_to" class="form-control form-control-sm bg-light" value="<?php echo e($date_to); ?>" placeholder="تا تاریخ">
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">دریافت گزارش</button>
                </div>
            </form>
        </div>

        <!-- جدول صورت‌حساب -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3" id="printableArea">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold text-dark mb-1">صورت‌حساب گردش مالی تراکنش‌ها</h6>
                    <div class="fs-7 text-muted">تعداد کل تراکنش‌های یافت شده: <?php echo count($transactions); ?> مورد</div>
                </div>
                <div class="text-end fs-8 text-muted">تاریخ گزارش: <?php echo date('Y/m/d'); ?></div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-7">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>#</th>
                            <th>کد پیگیری</th>
                            <th>نام عضو</th>
                            <th>نوع تراکنش</th>
                            <th>مبلغ (تومان)</th>
                            <th>بابت / دسته</th>
                            <th>تاریخ ثبت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">هیچ رکوردی برای نمایش وجود ندارد.</td></tr>
                        <?php else: ?>
                            <?php $idx = 1; foreach ($transactions as $t): ?>
                                <tr>
                                    <td><?php echo $idx++; ?></td>
                                    <td class="fw-mono text-primary fw-bold"><?php echo e($t['tracking_code']); ?></td>
                                    <td class="fw-bold"><?php echo e($t['user_name']); ?></td>
                                    <td>
                                        <?php 
                                        $type_badge = [
                                            'deposit' => '<span class="badge bg-success-subtle text-success">واریز</span>',
                                            'withdrawal' => '<span class="badge bg-danger-subtle text-danger">برداشت</span>',
                                            'transfer' => '<span class="badge bg-info-subtle text-info">انتقال داخلی</span>',
                                            'member_transfer' => '<span class="badge bg-primary-subtle text-primary">انتقال بین اعضا</span>',
                                            'interest_payment' => '<span class="badge bg-warning-subtle text-dark">سود سپرده</span>',
                                            'loan_disbursement' => '<span class="badge bg-purple text-white">پرداخت وام</span>',
                                            'loan_repayment' => '<span class="badge bg-secondary">قسط وام</span>'
                                        ];
                                        echo $type_badge[$t['type']] ?? $t['type'];
                                        ?>
                                    </td>
                                    <td class="fw-bold"><?php echo format_money($t['amount']); ?></td>
                                    <td><?php echo e($t['category']); ?></td>
                                    <td class="text-muted"><?php echo e($t['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
