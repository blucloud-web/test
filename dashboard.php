<?php
/**
 * Family Banking System - Executive Dashboard
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'داشبورد - ' . APP_NAME;

$reportModel = new Report();
$stats = $reportModel->getDashboardStats();
$chartData = $reportModel->getMonthlyTransactionChartData();

$transModel = new Transaction();
$recentTransactions = $transModel->getRecent(6);

$userModel = new User();
$allUsers = $userModel->getAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Content Area -->
<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <!-- به روزرسانی و خوش‌آمدگویی -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">داشبورد مدیریت و نظارت</h3>
                <p class="text-muted fs-7 mb-0">خلاصه وضعیت حساب‌ها، تراکنش‌ها، صندوق مرکزی، وام‌ها و سپرده‌ها</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="transactions.php?action=new" class="btn btn-primary btn-sm rounded-3 py-2 px-3 fw-semibold shadow-sm">
                    <i class="fa-solid fa-plus me-1"></i>ثبت تراکنش جدید
                </a>
                <a href="loans.php?action=request" class="btn btn-outline-danger btn-sm rounded-3 py-2 px-3 fw-semibold bg-white">
                    <i class="fa-solid fa-hand-holding-dollar me-1"></i>درخواست وام
                </a>
                <a href="deposits.php?action=new" class="btn btn-outline-warning btn-sm rounded-3 py-2 px-3 fw-semibold bg-white text-dark">
                    <i class="fa-solid fa-piggy-bank me-1"></i>افتتاح سپرده
                </a>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="row g-3 mb-4">
            <!-- کارت موجودی صندوق مرکزی -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card card-hover border-0 shadow-sm rounded-4 bg-primary text-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fs-7 opacity-75">موجودی صندوق مرکزی</span>
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-vault fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo format_money($stats['central_fund_balance']); ?></h3>
                    <div class="fs-7 opacity-75">سرمایه اصلی مشترک خانواده</div>
                </div>
            </div>

            <!-- کارت مجموع موجودی حساب‌های بانک -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card card-hover border-0 shadow-sm rounded-4 bg-success text-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fs-7 opacity-75">مجموع دارایی حساب‌ها</span>
                        <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-wallet fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo format_money($stats['total_bank_balance']); ?></h3>
                    <div class="fs-7 opacity-75"><?php echo $stats['active_accounts']; ?> حساب فعال اعضا</div>
                </div>
            </div>

            <!-- کارت مجموع وام‌های فعال -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card card-hover border-0 shadow-sm rounded-4 bg-danger text-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fs-7 opacity-75">تسهیلات و وام‌های جاری</span>
                        <div class="bg-white text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-hand-holding-dollar fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo format_money($stats['active_loans_total']); ?></h3>
                    <div class="fs-7 opacity-75">مبلغ کل وام‌های پرداختی فعلی</div>
                </div>
            </div>

            <!-- کارت مجموع سپرده‌ها -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card card-hover border-0 shadow-sm rounded-4 bg-warning text-dark p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fs-7 opacity-75">مجموع اصل سپرده‌ها</span>
                        <div class="bg-white text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-piggy-bank fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo format_money($stats['active_deposits_total']); ?></h3>
                    <div class="fs-7 opacity-75">سرمایه‌گذاری‌های فعال اعضا</div>
                </div>
            </div>

            <!-- کارت تعداد اعضای سیستم -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card card-hover border-0 shadow-sm rounded-4 bg-purple text-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fs-7 opacity-75">تعداد کل اعضا</span>
                        <div class="bg-white text-purple rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-users fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo $stats['total_users']; ?> نفر</h3>
                    <div class="fs-7 opacity-75">اعضای خصوصی ثبت شده در سیستم</div>
                </div>
            </div>

            <!-- کارت وضعیت امنیت -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card card-hover border-0 shadow-sm rounded-4 bg-dark text-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fs-7 opacity-75">امنیت و پایداری</span>
                        <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-shield-halved fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1"><span class="badge bg-success">فعال و امن</span></h3>
                    <div class="fs-7 opacity-75">PDO Prepared Statements | CSRF OK</div>
                </div>
            </div>
        </div>

        <!-- Chart & Activity Row -->
        <div class="row g-3 mb-4">
            <!-- نمودار گردش مالی -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-column me-2 text-primary"></i>نمودار مقایسه واریز و برداشت (۶ ماه گذشته)</h6>
                        <span class="badge bg-light text-secondary">برحسب تومان</span>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="cashflowChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- فهرست اعضا -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-user-group me-2 text-primary"></i>اعضای صندوق</h6>
                        <a href="users.php" class="btn btn-link btn-sm text-decoration-none p-0">مشاهده همه</a>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($allUsers, 0, 5) as $u): ?>
                            <div class="list-group-item border-0 px-0 py-2 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 36px; height: 36px;">
                                        <?php echo mb_substr(e($u['full_name']), 0, 1, 'UTF-8'); ?>

                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark fs-7"><?php echo e($u['full_name']); ?></div>
                                        <div class="text-muted fs-8"><?php echo e($u['username']); ?> | <?php echo e($u['mobile'] ?? 'بدون موبایل'); ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark border fs-8"><?php echo get_role_name($u['role']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- آخرین تراکنش‌ها -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>آخرین تراکنش‌های ثبت‌شده در سیستم</h6>
                <a href="transactions.php" class="btn btn-outline-primary btn-sm rounded-3">مشاهده همه تراکنش‌ها</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-7 text-secondary">
                        <tr>
                            <th>کد پیگیری</th>
                            <th>عضو / ثبت‌کننده</th>
                            <th>نوع تراکنش</th>
                            <th>مبلغ (تومان)</th>
                            <th>دسته / بابت</th>
                            <th>تاریخ ثبت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php if (empty($recentTransactions)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">هیچ تراکنشی ثبت نشده است.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $t): ?>
                                <tr>
                                    <td class="fw-mono text-primary fw-bold"><?php echo e($t['tracking_code']); ?></td>
                                    <td><?php echo e($t['user_name']); ?></td>
                                    <td>
                                        <?php 
                                        $type_badge = [
                                            'deposit' => '<span class="badge bg-success-subtle text-success">واریز</span>',
                                            'withdrawal' => '<span class="badge bg-danger-subtle text-danger">برداشت</span>',
                                            'transfer' => '<span class="badge bg-info-subtle text-info">انتقال داخلی</span>',
                                            'member_transfer' => '<span class="badge bg-primary-subtle text-primary">انتقال بین اعضا</span>',
                                            'interest_payment' => '<span class="badge bg-warning-subtle text-dark">پرداخت سود</span>',
                                            'loan_disbursement' => '<span class="badge bg-purple text-white">پرداخت وام</span>',
                                            'loan_repayment' => '<span class="badge bg-secondary">پرداخت قسط</span>'
                                        ];
                                        echo $type_badge[$t['type']] ?? $t['type'];
                                        ?>
                                    </td>
                                    <td class="fw-bold"><?php echo format_money($t['amount']); ?></td>
                                    <td><?php echo e($t['category']); ?></td>
                                    <td class="text-muted"><?php echo e($t['created_at']); ?></td>
                                    <td>
                                        <a href="receipt.php?code=<?php echo e($t['tracking_code']); ?>" class="btn btn-sm btn-outline-secondary rounded-2 py-1 px-2" title="چاپ رسید">
                                            <i class="fa-solid fa-receipt me-1"></i>رسید
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('cashflowChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartData['labels']); ?>,
            datasets: [
                {
                    label: 'واریزی‌ها (تومان)',
                    data: <?php echo json_encode($chartData['deposits']); ?>,
                    backgroundColor: 'rgba(25, 135, 84, 0.75)',
                    borderColor: 'rgb(25, 135, 84)',
                    borderWidth: 1,
                    borderRadius: 6
                },
                {
                    label: 'برداشت‌ها (تومان)',
                    data: <?php echo json_encode($chartData['withdrawals']); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.75)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
