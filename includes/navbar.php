<?php
if (!defined('APP_INIT')) die('Direct access not permitted');

$notifModel = new Notification();
$unreadCount = isset($_SESSION['user_id']) ? $notifModel->getUnreadCount($_SESSION['user_id']) : 0;
$user_role = $_SESSION['user_role'] ?? 'member';
$user_name = $_SESSION['user_fullname'] ?? 'کاربر مهمان';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm border-bottom py-2 px-3 sticky-top">
    <div class="container-fluid px-0">
        <!-- دکمه تافل ساپدبار در موبایل -->
        <button class="btn btn-outline-light d-md-none me-2" id="sidebarToggle">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center me-3" href="dashboard.php">
            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                <i class="fa-solid fa-building-columns fs-5"></i>
            </div>
            <span class="fw-bold fs-5"><?php echo e(APP_NAME); ?></span>
        </a>

        <!-- بخش راست و اعلان‌ها -->
        <div class="d-flex align-items-center ms-auto">
            <!-- نشانگر نقش -->
            <span class="badge bg-light text-primary me-3 d-none d-sm-inline-block px-3 py-2 rounded-pill shadow-sm">
                <i class="fa-solid fa-user-shield me-1"></i>
                <?php echo e(get_role_name($user_role)); ?>

            </span>

            <!-- کلید اعلان‌ها -->
            <a href="notifications.php" class="btn btn-primary position-relative me-3 shadow-none text-white border-0">
                <i class="fa-solid fa-bell fs-5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                        <?php echo e($unreadCount); ?>

                    </span>
                <?php endif; ?>
            </a>

            <!-- منوی کاربر -->
            <div class="dropdown">
                <a class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold text-uppercase me-2" style="width: 38px; height: 38px;">
                        <?php echo mb_substr(e($user_name), 0, 1, 'UTF-8'); ?>

                    </div>
                    <span class="d-none d-md-inline fw-semibold"><?php echo e($user_name); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 mt-2 border-0" aria-labelledby="userDropdown">
                    <li>
                        <div class="dropdown-header text-muted">
                            کاربر متصل: <strong class="text-dark"><?php echo e($_SESSION['username'] ?? ''); ?></strong>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2" href="user_profile.php">
                            <i class="fa-solid fa-user-pen text-primary me-2"></i>پروفایل کاربری
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="notifications.php">
                            <i class="fa-solid fa-bell text-warning me-2"></i>اعلان‌ها 
                            <?php if($unreadCount > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2 text-danger" href="logout.php">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>خروج از حساب
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
