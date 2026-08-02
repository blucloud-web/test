<?php
/**
 * Family Banking System - Family Deposits (سپرده‌های خانوادگی)
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'سپرده‌های خانوادگی - ' . APP_NAME;

$depositModel = new Deposit();
$accountModel = new Account();
$userModel = new User();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'member';

// عملیات‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['form_action'] ?? '';
    try {
        if ($action === 'create') {
            $amount = (float)str_replace(',', '', $_POST['principal_amount'] ?? 0);
            if ($amount <= 0) {
                throw new Exception("مبلغ سپرده باید معتبر باشد.");
            }

            $deposit_data = [
                'user_id' => ($user_role === 'member') ? $user_id : (int)$_POST['user_id'],
                'account_id' => (int)$_POST['account_id'],
                'principal_amount' => $amount,
                'interest_rate' => (float)$_POST['interest_rate'],
                'term_months' => (int)$_POST['term_months'],
                'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0
            ];

            $depId = $depositModel->create($deposit_data);
            log_activity('افتتاح سپرده', 'سپرده‌ها', "افتتاح سپرده جدید به مبلغ: " . format_money($amount));
            flash('success', 'سپرده خانوادگی با موفقیت افتتاح شد.');
            redirect('deposits.php');
        } elseif ($action === 'pay_interest') {
            check_role(['admin', 'accountant']);
            $dep_id = (int)$_POST['deposit_id'];
            $amount = (float)str_replace(',', '', $_POST['interest_amount'] ?? 0);
            $depositModel->payMonthlyInterest($dep_id, $amount, $_POST['note'] ?? 'واریز سود سپرده');
            log_activity('پرداخت سود سپرده', 'سپرده‌ها', "واریز سود به مبلغ: " . format_money($amount));
            flash('success', 'سود سپرده با موفقیت واریز گردید.');
            redirect('deposits.php');
        } elseif ($action === 'cancel') {
            $dep_id = (int)$_POST['deposit_id'];
            $depositModel->cancelDeposit($dep_id);
            log_activity('فسخ/تسویه سپرده', 'سپرده‌ها', "تسویه و عودت اصل سپرده شناسه: {$dep_id}");
            flash('info', 'سپرده تسویه شد و اصل سرمایه به حساب کاربر عودت گردید.');
            redirect('deposits.php');
        }
    } catch (Exception $e) {
        flash('danger', 'خطا: ' . $e->getMessage());
        redirect('deposits.php');
    }
}

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'status' => trim($_GET['status'] ?? '')
];

if ($user_role === 'member') {
    $filters['user_id'] = $user_id;
    $userAccounts = $accountModel->getByUserId($user_id);
} else {
    $userAccounts = $accountModel->getAll('', '', 'active');
}

$deposits = $depositModel->getAll($filters);
$allUsers = $userModel->getAll('', '', 'active');
$default_rate = get_setting('default_deposit_interest_rate', 18);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">سپرده‌گذاری‌های سرمایه‌گذاری</h3>
                <p class="text-muted fs-7 mb-0">افتتاح سپرده سرمایه‌گذاری خانوادگی، محاسبه سود ماهانه و عودت اصل سرمایه</p>
            </div>
            <button class="btn btn-warning rounded-3 py-2 px-3 fw-semibold text-dark mt-3 mt-md-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#newDepositModal">
                <i class="fa-solid fa-piggy-bank me-1"></i>افتتاح سپرده جدید
            </button>
        </div>

        <!-- کارت‌های لیست سپرده‌ها -->
        <div class="row g-3">
            <?php if (empty($deposits)): ?>
                <div class="col-12 text-center py-5 text-muted">هیچ سپرده فعال یا ثبت شده‌ای یافت نشد.</div>
            <?php else: ?>
                <?php foreach ($deposits as $d): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2">
                                <span class="fw-mono text-primary fw-bold fs-7"><?php echo e($d['deposit_number']); ?></span>
                                <?php echo get_status_badge($d['status']); ?>

                            </div>

                            <div class="mb-3">
                                <div class="fs-7 text-muted">صاحب سپرده:</div>
                                <div class="fw-bold text-dark fs-6"><?php echo e($d['owner_name']); ?></div>
                                <div class="fs-8 text-secondary mt-1">حساب مرتبط: <code><?php echo e($d['account_number']); ?></code></div>
                            </div>

                            <div class="bg-light p-3 rounded-3 mb-3 border">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fs-7 text-muted">اصل سرمایه:</span>
                                    <span class="fw-bold text-dark fs-6"><?php echo format_money($d['principal_amount']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fs-7 text-muted">نرخ سود سالانه:</span>
                                    <span class="fw-bold text-warning"><?php echo $d['interest_rate']; ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="fs-7 text-muted">کل سود دریافتی تا کنون:</span>
                                    <span class="fw-bold text-success"><?php echo format_money($d['total_interest_paid']); ?></span>
                                </div>
                            </div>

                            <div class="fs-8 text-muted mb-3">
                                <div>تاریخ افتتاح: <?php echo e($d['open_date']); ?></div>
                                <div>تاریخ سررسید: <?php echo e($d['maturity_date']); ?> (<?php echo $d['term_months']; ?> ماهه)</div>
                            </div>

                            <div class="mt-auto d-flex gap-2 border-top pt-2">
                                <?php if ($d['status'] === 'active'): ?>
                                    <?php if (in_array($user_role, ['admin', 'accountant'])): ?>
                                        <button class="btn btn-sm btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#payInterestModal<?php echo $d['id']; ?>">
                                            <i class="fa-solid fa-coins me-1"></i>واریز سود
                                        </button>
                                    <?php endif; ?>
                                    <form method="POST" action="deposits.php" class="w-100" onsubmit="return confirm('آیا از فسخ و عودت اصل سپرده اطمینان دارید؟');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="form_action" value="cancel">
                                        <input type="hidden" name="deposit_id" value="<?php echo $d['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            <i class="fa-solid fa-ban me-1"></i>تسویه / فسخ
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal پرداخت سود -->
                    <?php if (in_array($user_role, ['admin', 'accountant'])): ?>
                        <div class="modal fade" id="payInterestModal<?php echo $d['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0">
                                    <div class="modal-header border-bottom">
                                        <h6 class="modal-title fw-bold">واریز سود ماهانه سپرده <?php echo e($d['deposit_number']); ?></h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="deposits.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="form_action" value="pay_interest">
                                        <input type="hidden" name="deposit_id" value="<?php echo $d['id']; ?>">
                                        <div class="modal-body fs-7">
                                            <?php 
                                            // محاسبه تخمینی سود ماهانه
                                            $est_monthly = ($d['principal_amount'] * ($d['interest_rate'] / 100)) / 12;
                                            ?>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary">مبلغ سود واریزی (تومان) *</label>
                                                <input type="text" name="interest_amount" class="form-control currency-input fw-bold fs-6" required value="<?php echo number_format($est_monthly); ?>">
                                                <div class="form-text">محاسبه تقریبی سود یک‌ماهه: <?php echo format_money($est_monthly); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary">بابت / توضیحات</label>
                                                <input type="text" name="note" class="form-control" value="واریز سود ماهانه سپرده">
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                                            <button type="submit" class="btn btn-success fw-bold">تایید و واریز سود</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal افتتاح سپرده جدید -->
<div class="modal fade" id="newDepositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-piggy-bank me-2 text-warning"></i>افتتاح سپرده سرمایه‌گذاری جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="deposits.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="form_action" value="create">
                <div class="modal-body fs-7">
                    <?php if ($user_role !== 'member'): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">صاحب سپرده *</label>
                            <select name="user_id" class="form-select" required>
                                <?php foreach ($allUsers as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo e($u['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">حساب بانکی جهت کسر اصل مبلغ *</label>
                        <select name="account_id" class="form-select" required>
                            <?php foreach ($userAccounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"><?php echo e($acc['account_number']); ?> (موجود: <?php echo format_money($acc['balance']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">مبلغ اصل سپرده (تومان) *</label>
                        <input type="text" name="principal_amount" class="form-control currency-input fw-bold fs-6" required placeholder="0">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary">نرخ سود سالانه (%)</label>
                            <input type="number" step="0.5" name="interest_rate" class="form-control" required value="<?php echo $default_rate; ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary">مدت سپرده (ماه)</label>
                            <select name="term_months" class="form-select" required>
                                <option value="3">۳ ماهه</option>
                                <option value="6">۶ ماهه</option>
                                <option value="12" selected>۱۲ ماهه (یک‌ساله)</option>
                                <option value="24">۲۴ ماهه (دوساله)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="auto_renew" value="1" id="autoRenewCheck" checked>
                        <label class="form-check-label text-secondary" for="autoRenewCheck">
                            تمدید خودکار در زمان سررسید
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold">افتتاح سپرده</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
