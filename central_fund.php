<?php
/**
 * Family Banking System - Central Fund (صندوق مرکزی خانواده)
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'صندوق مرکزی خانواده - ' . APP_NAME;

$fundModel = new CentralFund();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'member';

// ثبت ورود/خروج پول به صندوق مرکزی
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    check_role(['admin', 'accountant']);

    try {
        $type = $_POST['type'] ?? '';
        $amount = (float)str_replace(',', '', $_POST['amount'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($amount <= 0 || empty($title)) {
            throw new Exception("مبلغ و عنوان الزامی می‌باشند.");
        }

        $fundModel->addLog($type, $amount, $title, $description, $user_id);

        log_activity('گردش صندوق مرکزی', 'صندوق مرکزی', "ثبت {$type} به عنوان {$title} و مبلغ " . format_money($amount));
        flash('success', 'تغییرات در صندوق مرکزی با موفقیت ثبت شد.');
        redirect('central_fund.php');
    } catch (Exception $e) {
        flash('danger', 'خطا: ' . $e->getMessage());
        redirect('central_fund.php');
    }
}

$summary = $fundModel->getSummary();
$logs = $fundModel->getAllLogs(50);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">صندوق مرکزی و خزانه خانواده</h3>
                <p class="text-muted fs-7 mb-0">نظارت بر سرمایه کل، افزایش سرمایه اعضا، هزینه‌ها و درآمدهای مشترک</p>
            </div>
            <?php if (in_array($user_role, ['admin', 'accountant'])): ?>
                <button class="btn btn-purple text-white rounded-3 py-2 px-3 fw-semibold mt-3 mt-md-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#fundLogModal">
                    <i class="fa-solid fa-plus-circle me-1"></i>ثبت ورود/خروج به صندوق
                </button>
            <?php endif; ?>
        </div>

        <!-- KPI های صندوق مرکزی -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-purple text-white">
                    <div class="fs-7 opacity-75">موجودی فعلی صندوق مرکزی</div>
                    <h3 class="fw-bold mt-2 mb-0"><?php echo format_money($summary['balance']); ?></h3>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-success text-white">
                    <div class="fs-7 opacity-75">مجموع تزریق سرمایه اولیه</div>
                    <h3 class="fw-bold mt-2 mb-0"><?php echo format_money($summary['total_capital']); ?></h3>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-info text-white">
                    <div class="fs-7 opacity-75">کل درآمدهای متفرقه</div>
                    <h3 class="fw-bold mt-2 mb-0"><?php echo format_money($summary['total_income']); ?></h3>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-secondary text-white">
                    <div class="fs-7 opacity-75">کل هزینه‌های صندوق</div>
                    <h3 class="fw-bold mt-2 mb-0"><?php echo format_money($summary['total_expense']); ?></h3>
                </div>
            </div>
        </div>

        <!-- تاریخچه گردش صندوق -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="fa-solid fa-clock-rotate-left me-2 text-purple"></i>سوابق تراکنش‌ها و گردش مالی صندوق مرکزی</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-7">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>#</th>
                            <th>نوع عملیات</th>
                            <th>عنوان تراکنش</th>
                            <th>مبلغ (تومان)</th>
                            <th>ثبت‌کننده</th>
                            <th>تاریخ ثبت</th>
                            <th>توضیحات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">هیچ رکوردی برای صندوق مرکزی ثبت نشده است.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td>
                                        <?php if ($log['type'] === 'capital_injection'): ?>
                                            <span class="badge bg-success">تزریق سرمایه</span>
                                        <?php elseif ($log['type'] === 'income'): ?>
                                            <span class="badge bg-info">درآمد</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">هزینه</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?php echo e($log['title']); ?></td>
                                    <td class="fw-bold"><?php echo format_money($log['amount']); ?></td>
                                    <td><?php echo e($log['creator_name']); ?></td>
                                    <td class="text-muted"><?php echo e($log['created_at']); ?></td>
                                    <td class="text-muted"><?php echo e($log['description'] ?? '---'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal ثبت گردش صندوق -->
<?php if (in_array($user_role, ['admin', 'accountant'])): ?>
<div class="modal fade" id="fundLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-vault me-2 text-purple"></i>ثبت گردش در صندوق مرکزی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="central_fund.php">
                <?php echo csrf_field(); ?>
                <div class="modal-body fs-7">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">نوع تراکنش *</label>
                        <select name="type" class="form-select" required>
                            <option value="capital_injection">تزریق سرمایه / شارژ اولیه</option>
                            <option value="income">درآمد متفرقه صندوق</option>
                            <option value="expense">هزینه متفرقه صندوق</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">عنوان تراکنش *</label>
                        <input type="text" name="title" class="form-control" required placeholder="مثال: شارژ ماهانه صندوق توسط اعضا">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">مبلغ (تومان) *</label>
                        <input type="text" name="amount" class="form-control currency-input fw-bold fs-6" required placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">توضیحات تکمیلی</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="جزئیات بیشتر..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn bg-purple text-white fw-bold">ثبت در صندوق</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
