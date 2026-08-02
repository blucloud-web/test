<?php
/**
 * Family Banking System - Transactions & Transfers
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'تراکنش‌ها و انتقال وجه - ' . APP_NAME;

$transModel = new Transaction();
$accountModel = new Account();
$userModel = new User();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'member';

// ثبت تراکنش جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $type = $_POST['type'] ?? '';
        $amount = (float)str_replace(',', '', $_POST['amount'] ?? 0);
        $source_account_id = !empty($_POST['source_account_id']) ? (int)$_POST['source_account_id'] : null;
        $dest_account_id = !empty($_POST['dest_account_id']) ? (int)$_POST['dest_account_id'] : null;
        $target_user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : $user_id;

        if ($amount <= 0) {
            throw new Exception("مبلغ تراکنش باید بزرگتر از صفر باشد.");
        }

        $trans_id = $transModel->createTransaction([
            'user_id' => $target_user_id,
            'source_account_id' => $source_account_id,
            'dest_account_id' => $dest_account_id,
            'type' => $type,
            'amount' => $amount,
            'category' => $_POST['category'] ?? 'عمومی',
            'description' => $_POST['description'] ?? null,
            'created_by' => $user_id
        ]);

        $trx = $transModel->findById($trans_id);

        log_activity('ثبت تراکنش', 'تراکنش‌ها', "ثبت تراکنش {$type} با کد پیگیری: {$trx['tracking_code']} به مبلغ: " . format_money($amount));
        flash('success', "تراکنش با موفقیت انجام شد. کد پیگیری: {$trx['tracking_code']}");
        redirect("receipt.php?code={$trx['tracking_code']}");
    } catch (Exception $e) {
        flash('danger', 'خطا در انجام تراکنش: ' . $e->getMessage());
        redirect('transactions.php');
    }
}

// پارامترهای فیلتر
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'type' => trim($_GET['type'] ?? ''),
    'account_id' => !empty($_GET['account_id']) ? (int)$_GET['account_id'] : null,
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
];

if ($user_role === 'member') {
    $filters['user_id'] = $user_id;
    $myAccounts = $accountModel->getByUserId($user_id);
} else {
    $myAccounts = $accountModel->getAll('', '', 'active');
}

$allAccounts = $accountModel->getAll('', '', 'active');
$transactions = $transModel->getAll($filters);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">تراکنش‌ها و انتقال وجه</h3>
                <p class="text-muted fs-7 mb-0">واریز، برداشت، انتقال بین حساب‌ها و انتقال داخلی بین اعضای خانواده</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <button class="btn btn-success rounded-3 py-2 px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
                    <i class="fa-solid fa-arrow-down-left me-1"></i>واریز به حساب
                </button>
                <button class="btn btn-danger rounded-3 py-2 px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                    <i class="fa-solid fa-arrow-up-right me-1"></i>برداشت از حساب
                </button>
                <button class="btn btn-primary rounded-3 py-2 px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#transferModal">
                    <i class="fa-solid fa-right-left me-1"></i>انتقال بین اعضا
                </button>
            </div>
        </div>

        <!-- فیلتر و جستجوی پیشرفته -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <form method="GET" action="transactions.php" class="row g-2">
                <div class="col-12 col-md-3">
                    <input type="text" class="form-control form-control-sm bg-light" name="search" placeholder="کد پیگیری، بابت یا نام..." value="<?php echo e($filters['search']); ?>">
                </div>
                <div class="col-6 col-md-2">
                    <select name="type" class="form-select form-select-sm bg-light">
                        <option value="">همه تراکنش‌ها</option>
                        <option value="deposit" <?php echo ($filters['type'] === 'deposit') ? 'selected' : ''; ?>>واریز</option>
                        <option value="withdrawal" <?php echo ($filters['type'] === 'withdrawal') ? 'selected' : ''; ?>>برداشت</option>
                        <option value="transfer" <?php echo ($filters['type'] === 'transfer') ? 'selected' : ''; ?>>انتقال داخلی</option>
                        <option value="member_transfer" <?php echo ($filters['type'] === 'member_transfer') ? 'selected' : ''; ?>>انتقال بین اعضا</option>
                        <option value="interest_payment" <?php echo ($filters['type'] === 'interest_payment') ? 'selected' : ''; ?>>سود سپرده</option>
                        <option value="loan_repayment" <?php echo ($filters['type'] === 'loan_repayment') ? 'selected' : ''; ?>>اقساط وام</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" class="form-control form-control-sm bg-light" name="date_from" value="<?php echo e($filters['date_from']); ?>" title="از تاریخ">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" class="form-control form-control-sm bg-light" name="date_to" value="<?php echo e($filters['date_to']); ?>" title="تا تاریخ">
                </div>
                <div class="col-12 col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm w-100">اعمال فیلتر</button>
                    <a href="transactions.php" class="btn btn-outline-secondary btn-sm">حذف</a>
                </div>
            </form>
        </div>

        <!-- جدول تراکنش‌ها -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-7 text-secondary">
                        <tr>
                            <th>کد پیگیری</th>
                            <th>حساب مبدا</th>
                            <th>حساب مقصد</th>
                            <th>نوع</th>
                            <th>مبلغ (تومان)</th>
                            <th>دسته / بابت</th>
                            <th>تاریخ و زمان</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">هیچ تراکنشی بر اساس فیلترهای انتخابی یافت نشد.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td class="fw-mono text-primary fw-bold"><?php echo e($t['tracking_code']); ?></td>
                                    <td><?php echo e($t['source_acc_num'] ?? '--- (صندوق)'); ?></td>
                                    <td><?php echo e($t['dest_acc_num'] ?? '--- (صندوق)'); ?></td>
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
                                        <a href="receipt.php?code=<?php echo e($t['tracking_code']); ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="رسید چاپ">
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

<!-- Modal واریز -->
<div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-arrow-down-left me-2"></i>ثبت واریز جدید به حساب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="transactions.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="type" value="deposit">
                <div class="modal-body fs-7">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">حساب مقصد *</label>
                        <select name="dest_account_id" class="form-select" required>
                            <?php foreach ($myAccounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"><?php echo e($acc['account_number']); ?> - <?php echo e($acc['owner_name'] ?? ''); ?> (موجود: <?php echo format_money($acc['balance']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">مبلغ واریزی (تومان) *</label>
                        <input type="text" name="amount" class="form-control currency-input fs-6 fw-bold" required placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">دسته‌بندی / بابت</label>
                        <select name="category" class="form-select">
                            <option value="پس‌انداز">پس‌انداز</option>
                            <option value="حق شارژ">حق شارژ ماهانه صندوق</option>
                            <option value="سرمایه‌گذاری">سرمایه‌گذاری</option>
                            <option value="سایر">سایر</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">توضیحات</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="توضیحات واریزی..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-success fw-bold">ثبت واریز</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal برداشت -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-arrow-up-right me-2"></i>ثبت برداشت از حساب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="transactions.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="type" value="withdrawal">
                <div class="modal-body fs-7">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">حساب مبدا *</label>
                        <select name="source_account_id" class="form-select" required>
                            <?php foreach ($myAccounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"><?php echo e($acc['account_number']); ?> - <?php echo e($acc['owner_name'] ?? ''); ?> (موجود: <?php echo format_money($acc['balance']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">مبلغ برداشت (تومان) *</label>
                        <input type="text" name="amount" class="form-control currency-input fs-6 fw-bold" required placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">دسته‌بندی / بابت</label>
                        <select name="category" class="form-select">
                            <option value="برداشت شخصی">برداشت شخصی</option>
                            <option value="هزینه خانوادگی">هزینه خانوادگی</option>
                            <option value="خرید">خرید تجهیزات</option>
                            <option value="سایر">سایر</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">توضیحات</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="دلیل برداشت..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-danger fw-bold">ثبت برداشت</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal انتقال بين اعضا -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-right-left me-2"></i>انتقال وجه بین اعضای خانواده</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="transactions.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="type" value="member_transfer">
                <div class="modal-body fs-7">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">حساب مبدا شما *</label>
                        <select name="source_account_id" class="form-select" required>
                            <?php foreach ($myAccounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"><?php echo e($acc['account_number']); ?> (موجود: <?php echo format_money($acc['balance']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">حساب مقصد (عضو خانواده) *</label>
                        <select name="dest_account_id" class="form-select" required>
                            <?php foreach ($allAccounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"><?php echo e($acc['account_number']); ?> - <?php echo e($acc['owner_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">مبلغ انتقال (تومان) *</label>
                        <input type="text" name="amount" class="form-control currency-input fs-6 fw-bold" required placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">بابت / توضیحات</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="علت انتقال پول..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary fw-bold">انجام انتقال</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
