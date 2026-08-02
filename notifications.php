<?php
/**
 * Family Banking System - User Notifications
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'اعلان‌ها و یادآوری‌ها - ' . APP_NAME;

$notifModel = new Notification();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    verify_csrf();
    $notifModel->markAllAsRead($user_id);
    flash('success', 'تمام اعلان‌ها به عنوان خوانده شده علامت‌گذاری شدند.');
    redirect('notifications.php');
}

$notifications = $notifModel->getByUserId($user_id, 50);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">اعلان‌ها و هشدارها</h3>
                <p class="text-muted fs-7 mb-0">پیام‌های سیستم، تایید وام‌ها، یادآوری سررسید اقساط و سود سپرده‌ها</p>
            </div>
            <form method="POST" action="notifications.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="mark_all_read" value="1">
                <button type="submit" class="btn btn-outline-primary rounded-3 py-2 px-3 fs-7 fw-semibold bg-white mt-3 mt-md-0 shadow-sm">
                    <i class="fa-solid fa-check-double me-1"></i>علامت‌گذاری همه به عنوان خوانده شده
                </button>
            </form>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                    <div class="list-group list-group-flush">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center py-5 text-muted fs-7">هیچ اعلانی یافت نشد.</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="list-group-item border-0 border-bottom py-3 px-2 d-flex align-items-start justify-content-between <?php echo ($n['is_read'] == 0) ? 'bg-light rounded-3 mb-1' : ''; ?>">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3 mt-1">
                                            <?php if ($n['type'] === 'success'): ?>
                                                <i class="fa-solid fa-circle-check text-success fs-4"></i>
                                            <?php elseif ($n['type'] === 'danger'): ?>
                                                <i class="fa-solid fa-circle-xmark text-danger fs-4"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-circle-info text-primary fs-4"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1 fs-7"><?php echo e($n['title']); ?></h6>
                                            <p class="text-secondary fs-7 mb-1"><?php echo e($n['message']); ?></p>
                                            <div class="text-muted fs-8"><?php echo e($n['created_at']); ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($n['link'])): ?>
                                        <a href="<?php echo e($n['link']); ?>" class="btn btn-sm btn-outline-secondary fs-8 py-1 px-2">مشاهده</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
