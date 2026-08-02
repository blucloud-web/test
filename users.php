<?php
/**
 * Family Banking System - User Management CRUD
 */
require_once __DIR__ . '/includes/auth_check.php';
check_role(['admin']); // فقط مدیر کل

$page_title = 'مدیریت کاربران - ' . APP_NAME;
$userModel = new User();

// ثبت یا ویرایش کاربر
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['form_action'] ?? '';
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        if ($userModel->findByUsername($username)) {
            flash('danger', 'این نام کاربری قبلاً ثبت شده است.');
        } else {
            $userId = $userModel->create($_POST);
            log_activity('افزودن کاربر', 'کاربران', "افزودن کاربر جدید: {$username} (شناسه: {$userId})");
            flash('success', 'عضو جدید با موفقیت اضافه شد.');
        }
        redirect('users.php');
    } elseif ($action === 'edit') {
        $id = (int)$_POST['user_id'];
        $userModel->update($id, $_POST);
        log_activity('ویرایش کاربر', 'کاربران', "بروزرسانی مشخصات کاربر شناسه: {$id}");
        flash('success', 'اطلاعات کاربر با موفقیت بروزرسانی شد.');
        redirect('users.php');
    } elseif ($action === 'toggle_status') {
        $id = (int)$_POST['user_id'];
        $user = $userModel->findById($id);
        if ($user) {
            $new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
            $userModel->update($id, [
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'status' => $new_status,
                'mobile' => $user['mobile'],
                'national_id' => $user['national_id'],
                'address' => $user['address'],
                'notes' => $user['notes']
            ]);
            flash('info', "وضعیت کاربر به " . ($new_status === 'active' ? 'فعال' : 'غیرفعال') . " تغییر یافت.");
        }
        redirect('users.php');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['user_id'];
        if ($id === (int)$_SESSION['user_id']) {
            flash('danger', 'شما نمی‌توانید حساب کاربری جاری خودتان را حذف کنید.');
        } else {
            $userModel->delete($id);
            log_activity('حذف کاربر', 'کاربران', "حذف کاربر شناسه: {$id}");
            flash('success', 'کاربر با موفقیت حذف گردید.');
        }
        redirect('users.php');
    }
}

$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$users = $userModel->getAll($search, $role_filter, $status_filter);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">مدیریت اعضا و کاربران</h3>
                <p class="text-muted fs-7 mb-0">تعریف عضو جدید، ویرایش نقش‌ها، فعال/غیرفعال‌سازی و تنظیم سطوح دسترسی</p>
            </div>
            <button class="btn btn-primary rounded-3 py-2 px-3 fw-semibold mt-3 mt-md-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-user-plus me-1"></i>افزودن عضو جدید
            </button>
        </div>

        <!-- فیلتر و جستجو -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <form method="GET" action="users.php" class="row g-2">
                <div class="col-12 col-md-5">
                    <input type="text" class="form-control form-control-sm bg-light" name="search" placeholder="جستجو نام، نام کاربری، کد ملی یا موبایل..." value="<?php echo e($search); ?>">
                </div>
                <div class="col-6 col-md-3">
                    <select name="role" class="form-select form-select-sm bg-light">
                        <option value="">همه نقش‌ها</option>
                        <option value="admin" <?php echo ($role_filter === 'admin') ? 'selected' : ''; ?>>مدیر کل</option>
                        <option value="accountant" <?php echo ($role_filter === 'accountant') ? 'selected' : ''; ?>>حسابدار</option>
                        <option value="member" <?php echo ($role_filter === 'member') ? 'selected' : ''; ?>>عضو خانواده</option>
                        <option value="readonly" <?php echo ($role_filter === 'readonly') ? 'selected' : ''; ?>>فقط خواندنی</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm bg-light">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>فعال</option>
                        <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>غیرفعال</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-secondary btn-sm w-100">فیلتر</button>
                </div>
            </form>
        </div>

        <!-- جدول کاربران -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-7 text-secondary">
                        <tr>
                            <th>#</th>
                            <th>نام و نام خانوادگی</th>
                            <th>نام کاربری</th>
                            <th>شماره موبایل</th>
                            <th>کد ملی</th>
                            <th>سطح دسترسی (نقش)</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">هیچ کاربری یافت نشد.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td class="fw-bold">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 34px; height: 34px;">
                                                <?php echo mb_substr(e($u['full_name']), 0, 1, 'UTF-8'); ?>

                                            </div>
                                            <?php echo e($u['full_name']); ?>

                                        </div>
                                    </td>
                                    <td><code><?php echo e($u['username']); ?></code></td>
                                    <td><?php echo e($u['mobile'] ?? '---'); ?></td>
                                    <td><?php echo e($u['national_id'] ?? '---'); ?></td>
                                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo get_role_name($u['role']); ?></span></td>
                                    <td><?php echo get_status_badge($u['status']); ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- دکمه تغییر وضعیت -->
                                            <form method="POST" action="users.php" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="form_action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo ($u['status'] === 'active') ? 'btn-outline-warning' : 'btn-outline-success'; ?> py-1 px-2" title="تغییر وضعیت">
                                                    <i class="fa-solid <?php echo ($u['status'] === 'active') ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                                </button>
                                            </form>

                                            <!-- دکمه حذف -->
                                            <form method="POST" action="users.php" class="d-inline" onsubmit="return confirm('آیا از حذف این کاربر اطمینان دارید؟');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="حذف کاربر">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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

<!-- Modal افزودن کاربر جدید -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-plus me-2 text-primary"></i>افزودن عضو جدید به سیستم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="users.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="form_action" value="create">
                <div class="modal-body fs-7">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">نام و نام خانوادگی *</label>
                            <input type="text" name="full_name" class="form-control" required placeholder="مثال: احمد حسینی">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">نام کاربری *</label>
                            <input type="text" name="username" class="form-control" required placeholder="مثال: ahmed">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">رمز عبور اولیه *</label>
                            <input type="password" name="password" class="form-control" required placeholder="حداقل ۶ کاراکتر">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">نقش و سطح دسترسی *</label>
                            <select name="role" class="form-select" required>
                                <option value="member">عضو خانواده</option>
                                <option value="accountant">حسابدار</option>
                                <option value="admin">مدیر کل</option>
                                <option value="readonly">فقط خواندنی</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">شماره موبایل</label>
                            <input type="text" name="mobile" class="form-control" placeholder="0912...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">کد ملی (اختیاری)</label>
                            <input type="text" name="national_id" class="form-control" placeholder="0012345678">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">آدرس سکونت</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="آدرس کامل..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">یادداشت / توضیحات</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="توضیحات اضافی..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary fw-bold">ذخیره عضو جدید</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
