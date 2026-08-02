<?php
/**
 * Family Banking System - Printable Receipt
 */
require_once __DIR__ . '/includes/auth_check.php';

$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    flash('danger', 'کد پیگیری تراکنش معتبر نمی‌باشد.');
    redirect('transactions.php');
}

$transModel = new Transaction();
$t = $transModel->findByTrackingCode($code);

if (!$t) {
    flash('danger', 'تراکنش مورد نظر یافت نشد.');
    redirect('transactions.php');
}

$page_title = 'رسید تراکنش ' . $t['tracking_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            
            <div class="text-end mb-3 no-print">
                <a href="transactions.php" class="btn btn-outline-secondary btn-sm me-2"><i class="fa-solid fa-arrow-right me-1"></i>بازگشت به لیست تراکنش‌ها</a>
                <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa-solid fa-print me-1"></i>چاپ رسید</button>
            </div>

            <!-- کارت رسید برای چاپ -->
            <div class="card border-0 shadow-lg rounded-4 bg-white p-4" id="printableArea">
                <!-- سربرگ رسید -->
                <div class="text-center border-bottom pb-3 mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-building-columns fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?php echo e(APP_NAME); ?></h5>
                    <div class="text-muted fs-7">رسید تاییدیه تراکنش مالی</div>
                </div>

                <!-- نشانگر موفقیت -->
                <div class="text-center bg-light p-3 rounded-3 mb-4 border">
                    <div class="text-success fw-bold fs-6 mb-1">
                        <i class="fa-solid fa-circle-check me-1"></i>تراکنش با موفقیت انجام شد
                    </div>
                    <div class="text-secondary fs-7">مبلغ تراکنش:</div>
                    <h2 class="fw-bold text-dark mt-1 mb-0"><?php echo format_money($t['amount']); ?></h2>
                </div>

                <!-- جدول مشخصات -->
                <div class="fs-7">
                    <div class="row py-2 border-bottom">
                        <div class="col-5 text-muted">کد پیگیری تراکنش:</div>
                        <div class="col-7 fw-mono text-primary fw-bold text-end"><?php echo e($t['tracking_code']); ?></div>
                    </div>
                    <div class="row py-2 border-bottom">
                        <div class="col-5 text-muted">نوع تراکنش:</div>
                        <div class="col-7 fw-bold text-end">
                            <?php 
                            $type_badge = [
                                'deposit' => 'واریز به حساب',
                                'withdrawal' => 'برداشت از حساب',
                                'transfer' => 'انتقال داخلی',
                                'member_transfer' => 'انتقال بین اعضا',
                                'interest_payment' => 'پرداخت سود سپرده',
                                'loan_disbursement' => 'پرداخت تسهیلات وام',
                                'loan_repayment' => 'بازپرداخت قسط وام'
                            ];
                            echo $type_badge[$t['type']] ?? $t['type'];
                            ?>
                        </div>
                    </div>
                    <div class="row py-2 border-bottom">
                        <div class="col-5 text-muted">حساب مبدا:</div>
                        <div class="col-7 text-end fw-mono"><?php echo e($t['source_acc_num'] ?? '--- (صندوق)'); ?> <?php echo !empty($t['source_owner']) ? '(' . e($t['source_owner']) . ')' : ''; ?></div>
                    </div>
                    <div class="row py-2 border-bottom">
                        <div class="col-5 text-muted">حساب مقصد:</div>
                        <div class="col-7 text-end fw-mono"><?php echo e($t['dest_acc_num'] ?? '--- (صندوق)'); ?> <?php echo !empty($t['dest_owner']) ? '(' . e($t['dest_owner']) . ')' : ''; ?></div>
                    </div>
                    <div class="row py-2 border-bottom">
                        <div class="col-5 text-muted">دسته‌بندی / بابت:</div>
                        <div class="col-7 text-end"><?php echo e($t['category']); ?></div>
                    </div>
                    <div class="row py-2 border-bottom">
                        <div class="col-5 text-muted">تاریخ و زمان ثبت:</div>
                        <div class="col-7 text-end fw-mono"><?php echo e($t['created_at']); ?></div>
                    </div>
                    <div class="row py-2 border-bottom">
                        <div class="col-5 text-muted">ثبت‌کننده / متصدی:</div>
                        <div class="col-7 text-end"><?php echo e($t['creator_name'] ?? 'سیستم'); ?></div>
                    </div>
                    <?php if (!empty($t['description'])): ?>
                        <div class="row py-2">
                            <div class="col-5 text-muted">توضیحات:</div>
                            <div class="col-7 text-end"><?php echo e($t['description']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- پاپرگ رسید -->
                <div class="mt-4 pt-3 border-top text-center text-muted fs-8">
                    <div>این رسید سند الکترونیکی معتبر سیستم بانکداری خانوادگی است.</div>
                    <div class="fw-mono mt-1">Ref ID: <?php echo md5($t['tracking_code']); ?></div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
