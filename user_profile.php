<?php
/**
 * Family Banking System - User Profile
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'پروفایل کاربری - ' . APP_NAME;
$userModel = new User();
$user_id = $_SESSION['user_id'];
$user = $userModel->findById($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    $data = [
        'full_name' => $full_name,
        'email' => $email,
        'role' => $user['role'],
        'status' => $user['status'],
        'mobile' => $mobile,
        'national_id' => $user['national_id'],
        'address' => $address,
        'notes' => $user['notes']
    ];

    if (!empty($new_password)) {
        $data['password'] = $new_password;
    }

    $userModel->update($user_id, $data);
    $_SESSION['user_fullname'] = $full_name;

    log_activity('بروزرسانی پروفایل', 'پروفایل', "بروزرسانی اطلاعات شخصی توسط کاربر {$user['username']}");
    flash('success', 'اطلاعات پروفایل شما با موفقیت به روز گردید.');
    redirect('user_profile.php');
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                    <div class="d-flex align-items-center border-bottom pb-3 mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold fs-3" style="width: 60px; height: 60px;">
                            <?php echo mb_substr(e($user['full_name']), 0, 1, 'UTF-8'); ?>

                        </div>
                        <div>
                            <h4 class="fw-bold text-dark mb-1"><?php echo e($user['full_name']); ?></h4>
                            <p class="text-muted fs-7 mb-0">
                                نام کاربری: <code><?php echo e($user['username']); ?></code> | 
                                نقش: <span class="badge bg-primary-subtle text-primary"><?php echo get_role_name($user['role']); ?></span>
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="user_profile.php">
                        <?php echo csrf_field(); ?>

                        <div class="row g-3 fs-7">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">نام و نام خانوادگی</label>
                                <input type="text" name="full_name" class="form-control" required value="<?php echo e($user['full_name']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">ایمیل</label>
                                <input type="email" name="email" class="form-control" value="<?php echo e($user['email']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">شماره موبایل</label>
                                <input type="text" name="mobile" class="form-control" value="<?php echo e($user['mobile']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">کد ملی</label>
                                <input type="text" class="form-control bg-light" value="<?php echo e($user['national_id']); ?>" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-secondary">آدرس سکونت</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo e($user['address']); ?></textarea>
                            </div>

                            <hr class="my-3">

                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-secondary">رمز عبور جدید (در صورت نیاز به تغییر)</label>
                                <input type="password" name="new_password" class="form-control" placeholder="برای عدم تغییر خالی بگذارید">
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary fw-bold px-4 py-2">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>ذخیره تغییرات پروفایل
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
