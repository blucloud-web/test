<?php
/**
 * Family Banking System - Bank Accounts Management
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'حساب‌های بانکی - ' . APP_NAME;

$accountModel = new Account();
$userModel = new User();

// ایجاد حساب جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    check_role(['admin', 'accountant']); // فقط مدیر و حسابدار

    $action = $_POST['form_action'] ?? '';
    if ($action === 'create') {
        $accId = $accountModel->create($_POST);
        log_activity('افتتاح حساب', 'حساب‌های بانکی', "افتتاح حساب جدید برای کاربر شناسه: {$_POST['user_id']}");
        flash('success', 'حساب بانکی جدید با موفقیت افتتاح گردید.');
        redirect('accounts.php');
    } elseif ($action === 'update_status') {
        $id = (int)$_POST['account_id'];
        $status = $_POST['status'];
        $acc = $accountModel->findById($id);
        if ($acc) {
            $accountModel->update($id, [
                'account_type' => $acc['account_type'],
                'status' => $status,
                'description' => $acc['description']
            ]);
            log_activity('بروزرسانی وضعیت حساب', 'حساب‌های بانکی', "تغییر وضعیت حساب {$acc['account_number']} به {$status}");
            flash('info', 'وضعیت حساب با موفقیت بروزرسانی شد.');
        }
        redirect('accounts.php');
    }
}

$search = trim($_GET['search'] ?? '');
$type_filter = trim($_GET['type'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$role = $_SESSION['user_role'] ?? 'member';
$user_id = $_SESSION['user_id'];

// اگر عضو عادی است فقط حساب‌های خودش را ببیند، مگر اینکه مدیر یا حسابدار باشد
if ($role === 'member') {
    $accounts = $accountModel->getByUserId($user_id);
} else {
    $accounts = $accountModel->getAll($search, $type_filter, $status_filter);
}

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
                <h3 class="fw-bold text-dark mb-1">حساب‌های بانکی اعضا</h3>
                <p class="text-muted fs-7 mb-0">مدیریت حساب‌های جاری، قرض‌الحسنه، کوتاه مدت و بلند مدت خانوادگی</p>
            </div>
            <?php if (in_array($role, ['admin', 'accountant'])): ?>
                <button class="btn btn-primary rounded-3 py-2 px-3 fw-semibold mt-3 mt-md-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                    <i class="fa-solid fa-plus me-1"></i>افتتاح حساب جدید
                </button>
            <?php endif; ?>
        </div>

        <?php if ($role !== 'member'): ?>
            <!-- فیلتر و جستجو -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="accounts.php" class="row g-2">
                    <div class="col-12 col-md-5">
                        <input type="text" class="form-control form-control-sm bg-light" name="search" placeholder="جستجو شماره حساب یا نام صاحب حساب..." value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="type" class="form-select form-select-sm bg-light">
                            <option value="">همه انواع حساب</option>
                            <option value="current" <?php echo ($type_filter === 'current') ? 'selected' : ''; ?>>جاری</option>
                            <option value="qard" <?php echo ($type_filter === 'qard') ? 'selected' : ''; ?>>قرض‌الحسنه</option>
                            <option value="short_term" <?php echo ($type_filter === 'short_term') ? 'selected' : ''; ?>>کوتاه‌مدت</option>
                            <option value="long_term" <?php echo ($type_filter === 'long_term') ? 'selected' : ''; ?>>بلندمدت</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select form-select-sm bg-light">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>فعال</option>
                            <option value="blocked" <?php echo ($status_filter === 'blocked') ? 'selected' : ''; ?>>مسدود</option>
                            <option value="closed" <?php echo ($status_filter === 'closed') ? 'selected' : ''; ?>>بسته شده</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-secondary btn-sm w-100">فیلتر</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- لیست حساب‌ها -->
        <div class="row g-3">
            <?php if (empty($accounts)): ?>
                <div class="col-12 text-center py-5 text-muted">هیچ حسابی موجود نمی‌باشد.</div>
            <?php else: ?>
                <?php foreach ($accounts as $a): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary border me-1"><?php echo get_account_type_name($a['account_type']); ?></span>
                                    <?php echo get_status_badge($a['status']); ?>

                                </div>
                                <span class="fs-8 text-muted"><i class="fa-solid fa-calendar me-1"></i><?php echo e($a['open_date']); ?></span>
                            </div>

                            <div class="mb-3">
                                <div class="fs-7 text-muted mb-1">صاحب حساب:</div>
                                <div class="fw-bold text-dark fs-6"><?php echo e($a['owner_name'] ?? $_SESSION['user_fullname']); ?></div>
                                <div class="fw-mono text-primary mt-1 fs-7"><i class="fa-solid fa-credit-card me-1"></i><?php echo e($a['account_number']); ?></div>
                            </div>

                            <div class="bg-light p-3 rounded-3 mb-3 text-center border">
                                <div class="fs-8 text-muted">موجودی فعلی:</div>
                                <div class="fw-bold text-success fs-4 mt-1"><?php echo format_money($a['balance']); ?></div>
                            </div>

                            <?php if (!empty($a['description'])): ?>
                                <p class="fs-7 text-muted mb-3"><i class="fa-solid fa-info-circle me-1"></i><?php echo e($a['description']); ?></p>
                            <?php endif; ?>

                            <div class="mt-auto d-flex justify-content-between gap-2 border-top pt-2">
                                <a href="transactions.php?account_id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="fa-solid fa-list-check me-1"></i>گردش حساب
                                </a>
                                <?php if (in_array($role, ['admin', 'accountant'])): ?>
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAccountModal<?php echo $a['id']; ?>" title="تغییر وضعیت">
                                        <i class="fa-solid fa-gear"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal تغییر وضعیت حساب -->
                    <?php if (in_array($role, ['admin', 'accountant'])): ?>
                        <div class="modal fade" id="editAccountModal<?php echo $a['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0">
                                    <div class="modal-header border-bottom">
                                        <h6 class="modal-title fw-bold">تغییر وضعیت حساب <?php echo e($a['account_number']); ?></h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="accounts.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="form_action" value="update_status">
                                        <input type="hidden" name="account_id" value="<?php echo $a['id']; ?>">
                                        <div class="modal-body fs-7">
                                            <label class="form-label fw-semibold">وضعیت جدید حساب</label>
                                            <select name="status" class="form-select" required>
                                                <option value="active" <?php echo ($a['status'] === 'active') ? 'selected' : ''; ?>>فعال</option>
                                                <option value="blocked" <?php echo ($a['status'] === 'blocked') ? 'selected' : ''; ?>>مسدود شده</option>
                                                <option value="closed" <?php echo ($a['status'] === 'closed') ? 'selected' : ''; ?>>بسته شده</option>
                                            </select>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                                            <button type="submit" class="btn btn-primary fw-bold">ذخیره تغییرات</button>
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

<!-- Modal افتتاح حساب جدید -->
<?php if (in_array($role, ['admin', 'accountant'])): ?>
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-plus-circle me-2 text-primary"></i>افتتاح حساب جدید برای عضو</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="accounts.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="form_action" value="create">
                <div class="modal-body fs-7">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">صاحب حساب *</label>
                        <select name="user_id" class="form-select" required>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo e($u['full_name']); ?> (<?php echo e($u['username']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">نوع حساب *</label>
                        <select name="account_type" class="form-select" required>
                            <option value="current">حساب جاری</option>
                            <option value="qard">حساب قرض‌الحسنه</option>
                            <option value="short_term">حساب کوتاه‌مدت</option>
                            <option value="long_term">حساب بلندمدت</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">موجودی اولیه (تومان)</label>
                        <input type="text" name="balance" class="form-control currency-input" placeholder="0" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">توضیحات یا یادداشت</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="هدف از افتتاح حساب..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary fw-bold">افتتاح حساب</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
