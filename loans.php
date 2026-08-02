<?php
/**
 * Family Banking System - Family Loans & Facilities (وام‌ها و اقساط)
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'وام‌ها و تسهیلات - ' . APP_NAME;

$loanModel = new Loan();
$accountModel = new Account();
$userModel = new User();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'member';

// عملیات وام
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['form_action'] ?? '';
    try {
        if ($action === 'request') {
            $amount = (float)str_replace(',', '', $_POST['amount'] ?? 0);
            if ($amount <= 0) {
                throw new Exception("مبلغ درخواست وام باید معتبر باشد.");
            }

            $loan_data = [
                'user_id' => $user_id,
                'amount' => $amount,
                'interest_rate' => (float)($_POST['interest_rate'] ?? get_setting('default_loan_interest_rate', 4)),
                'term_months' => (int)$_POST['term_months'],
                'guarantor_user_id' => !empty($_POST['guarantor_user_id']) ? (int)$_POST['guarantor_user_id'] : null,
                'notes' => $_POST['notes'] ?? null
            ];

            $loanId = $loanModel->createRequest($loan_data);
            log_activity('درخواست وام', 'وام‌ها', "ثبت درخواست وام جدید به مبلغ: " . format_money($amount));
            flash('success', 'درخواست وام شما با موفقیت ثبت شد و جهت تایید برای مدیریت ارسال گردید.');
            redirect('loans.php');
        } elseif ($action === 'approve') {
            check_role(['admin', 'accountant']);
            $loan_id = (int)$_POST['loan_id'];
            $target_acc = (int)$_POST['target_account_id'];
            $loanModel->approveLoan($loan_id, $target_acc);
            log_activity('تایید وام', 'وام‌ها', "تایید و واریز وام شناسه: {$loan_id}");
            flash('success', 'وام تایید و مبلغ به حساب متقاضی واریز گردید.');
            redirect('loans.php');
        } elseif ($action === 'reject') {
            check_role(['admin', 'accountant']);
            $loan_id = (int)$_POST['loan_id'];
            $reason = $_POST['reason'] ?? 'تایید نشد.';
            $loanModel->rejectLoan($loan_id, $reason);
            log_activity('رد وام', 'وام‌ها', "رد درخواست وام شناسه: {$loan_id}");
            flash('info', 'درخواست وام رد شد.');
            redirect('loans.php');
        } elseif ($action === 'pay_installment') {
            $inst_id = (int)$_POST['installment_id'];
            $account_id = (int)$_POST['from_account_id'];
            $loanModel->payInstallment($inst_id, $account_id);
            log_activity('پرداخت قسط', 'وام‌ها', "پرداخت قسط وام شناسه قسط: {$inst_id}");
            flash('success', 'قسط وام با موفقیت پرداخت گردید.');
            redirect('loans.php');
        }
    } catch (Exception $e) {
        flash('danger', 'خطا: ' . $e->getMessage());
        redirect('loans.php');
    }
}

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'status' => trim($_GET['status'] ?? '')
];

if ($user_role === 'member') {
    $filters['user_id'] = $user_id;
    $myAccounts = $accountModel->getByUserId($user_id);
} else {
    $myAccounts = $accountModel->getAll('', '', 'active');
}

$loans = $loanModel->getAll($filters);
$allUsers = $userModel->getAll('', '', 'active');
$default_interest = get_setting('default_loan_interest_rate', 4);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="page-content-wrapper" class="w-100 bg-light">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        
        <?php render_flash_messages(); ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">وام‌ها و تسهیلات اعضا</h3>
                <p class="text-muted fs-7 mb-0">درخواست وام، بررسی و تایید مدیر، جدول بازپرداخت اقساط و محاسبه جریمه دیرکرد</p>
            </div>
            <button class="btn btn-danger rounded-3 py-2 px-3 fw-semibold mt-3 mt-md-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestLoanModal">
                <i class="fa-solid fa-hand-holding-dollar me-1"></i>درخواست وام جدید
            </button>
        </div>

        <!-- لیست وام‌ها -->
        <div class="row g-3">
            <?php if (empty($loans)): ?>
                <div class="col-12 text-center py-5 text-muted">هیچ درخواست وام یا تسهیلاتی یافت نشد.</div>
            <?php else: ?>
                <?php foreach ($loans as $l): ?>
                    <?php $installments = $loanModel->getInstallments($l['id']); ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-3">
                                <div>
                                    <span class="fw-mono text-primary fw-bold fs-6 me-2"><?php echo e($l['loan_number']); ?></span>
                                    <?php echo get_status_badge($l['status']); ?>

                                    <div class="mt-1 fs-7">
                                        وام‌گیرنده: <strong class="text-dark"><?php echo e($l['borrower_name']); ?></strong>
                                        <?php if (!empty($l['guarantor_name'])): ?>
                                            | ضامن: <span class="text-secondary"><?php echo e($l['guarantor_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-md-end mt-2 mt-md-0 fs-7">
                                    <div>مبلغ اصل: <strong class="text-dark fs-6"><?php echo format_money($l['amount']); ?></strong></div>
                                    <div class="text-muted">بازپرداخت کل: <?php echo format_money($l['total_repayment']); ?> (کارمزد: <?php echo $l['interest_rate']; ?>%)</div>
                                </div>
                            </div>

                            <!-- بخش مدیریت برای وام‌های در انتظار -->
                            <?php if ($l['status'] === 'pending' && in_array($user_role, ['admin', 'accountant'])): ?>
                                <div class="bg-light p-3 rounded-3 mb-3 border d-flex align-items-center justify-content-between">
                                    <span class="fs-7 text-dark fw-bold"><i class="fa-solid fa-clock text-warning me-1"></i>این درخواست نیازمند تعیین تکلیف مدیریت است:</span>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-success px-3" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $l['id']; ?>">تایید و واریز وام</button>
                                        <button class="btn btn-sm btn-outline-danger px-3" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $l['id']; ?>">رد درخواست</button>
                                    </div>
                                </div>

                                <!-- Modal تایید وام -->
                                <div class="modal fade" id="approveModal<?php echo $l['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0">
                                            <div class="modal-header border-bottom">
                                                <h6 class="modal-title fw-bold">تایید و واریز وام <?php echo e($l['loan_number']); ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="loans.php">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="form_action" value="approve">
                                                <input type="hidden" name="loan_id" value="<?php echo $l['id']; ?>">
                                                <div class="modal-body fs-7">
                                                    <?php $userAccs = $accountModel->getByUserId($l['user_id']); ?>
                                                    <label class="form-label fw-semibold text-secondary">حساب مقصد جهت واریز مبلغ وام *</label>
                                                    <select name="target_account_id" class="form-select" required>
                                                        <?php foreach ($userAccs as $ua): ?>
                                                            <option value="<?php echo $ua['id']; ?>"><?php echo e($ua['account_number']); ?> (موجودی فعلی: <?php echo format_money($ua['balance']); ?>)</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="modal-footer border-top">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                                                    <button type="submit" class="btn btn-success fw-bold">تایید و واریز مبلغ وام</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal رد وام -->
                                <div class="modal fade" id="rejectModal<?php echo $l['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0">
                                            <div class="modal-header border-bottom">
                                                <h6 class="modal-title fw-bold text-danger">رد درخواست وام <?php echo e($l['loan_number']); ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="loans.php">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="form_action" value="reject">
                                                <input type="hidden" name="loan_id" value="<?php echo $l['id']; ?>">
                                                <div class="modal-body fs-7">
                                                    <label class="form-label fw-semibold text-secondary">دلیل عدم تایید</label>
                                                    <textarea name="reason" class="form-control" rows="2" placeholder="دلیل رد درخواست..."></textarea>
                                                </div>
                                                <div class="modal-footer border-top">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                                                    <button type="submit" class="btn btn-danger fw-bold">ثبت رد درخواست</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- جدول اقساط -->
                            <?php if (!empty($installments)): ?>
                                <h6 class="fw-bold text-dark fs-7 mb-2"><i class="fa-solid fa-calendar-check me-1 text-primary"></i>جدول بازپرداخت اقساط (<?php echo count($installments); ?> قسط)</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center fs-8">
                                        <thead class="table-light text-secondary">
                                            <tr>
                                                <th>قسط #</th>
                                                <th>تاریخ سررسید</th>
                                                <th>مبلغ قسط</th>
                                                <th>جریمه دیرکرد</th>
                                                <th>وضعیت</th>
                                                <th>تاریخ پرداخت</th>
                                                <th>عملیات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($installments as $inst): ?>
                                                <?php 
                                                $is_overdue = ($inst['status'] === 'pending' && strtotime($inst['due_date']) < strtotime(date('Y-m-d')));
                                                ?>
                                                <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                                                    <td class="fw-bold"><?php echo $inst['installment_number']; ?></td>
                                                    <td><?php echo e($inst['due_date']); ?></td>
                                                    <td class="fw-bold"><?php echo format_money($inst['total_amount']); ?></td>
                                                    <td class="text-danger"><?php echo format_money($inst['penalty_amount']); ?></td>
                                                    <td>
                                                        <?php if ($inst['status'] === 'paid'): ?>
                                                            <span class="badge bg-success-subtle text-success">پرداخت شده</span>
                                                        <?php elseif ($is_overdue): ?>
                                                            <span class="badge bg-danger">معوق (دارای تاخیر)</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning-subtle text-dark">در انتظار پرداخت</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-muted"><?php echo e($inst['payment_date'] ?? '---'); ?></td>
                                                    <td>
                                                        <?php if ($inst['status'] === 'pending'): ?>
                                                            <button class="btn btn-sm btn-primary py-0 px-2 fs-8" data-bs-toggle="modal" data-bs-target="#payInstModal<?php echo $inst['id']; ?>">
                                                                پرداخت قسط
                                                            </button>

                                                            <!-- Modal پرداخت قسط -->
                                                            <div class="modal fade text-start" id="payInstModal<?php echo $inst['id']; ?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content rounded-4 border-0">
                                                                        <div class="modal-header border-bottom">
                                                                            <h6 class="modal-title fw-bold">پرداخت قسط شماره <?php echo $inst['installment_number']; ?></h6>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <form method="POST" action="loans.php">
                                                                            <?php echo csrf_field(); ?>
                                                                            <input type="hidden" name="form_action" value="pay_installment">
                                                                            <input type="hidden" name="installment_id" value="<?php echo $inst['id']; ?>">
                                                                            <div class="modal-body fs-7">
                                                                                <div class="alert alert-info py-2 fs-7 mb-3">
                                                                                    مبلغ اصل قسط: <strong><?php echo format_money($inst['total_amount']); ?></strong>
                                                                                    <?php if ($is_overdue): ?>
                                                                                        <br><span class="text-danger">به دلیل تاخیر در سررسید، جریمه دیرکرد محاسبه می‌گردد.</span>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <label class="form-label fw-semibold text-secondary">انتخاب حساب بانکی جهت برداشت *</label>
                                                                                <select name="from_account_id" class="form-select" required>
                                                                                    <?php foreach ($myAccounts as $acc): ?>
                                                                                        <option value="<?php echo $acc['id']; ?>"><?php echo e($acc['account_number']); ?> (موجود: <?php echo format_money($acc['balance']); ?>)</option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                            <div class="modal-footer border-top">
                                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                                                                                <button type="submit" class="btn btn-primary fw-bold">پرداخت و تسویه قسط</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        <?php else: ?>
                                                            <i class="fa-solid fa-check text-success fs-6"></i>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal درخواست وام جدید -->
<div class="modal fade" id="requestLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-hand-holding-dollar me-2 text-danger"></i>درخواست تسهیلات و وام جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="loans.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="form_action" value="request">
                <div class="modal-body fs-7">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">مبلغ وام درخواستی (تومان) *</label>
                        <input type="text" name="amount" class="form-control currency-input fw-bold fs-6" required placeholder="مثال: 50,000,000">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary">تعداد اقساط (ماه) *</label>
                            <select name="term_months" class="form-select" required>
                                <option value="6">۶ ماهه</option>
                                <option value="10" selected>۱۰ ماهه</option>
                                <option value="12">۱۲ ماهه</option>
                                <option value="24">۲۴ ماهه</option>
                                <option value="36">۳۶ ماهه</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary">کارمزد (%)</label>
                            <input type="number" step="0.5" name="interest_rate" class="form-control" value="<?php echo $default_interest; ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">ضامن (از بین اعضای خانواده)</label>
                        <select name="guarantor_user_id" class="form-select">
                            <option value="">بدون ضامن / اختیاری</option>
                            <?php foreach ($allUsers as $u): ?>
                                <?php if ($u['id'] !== $user_id): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo e($u['full_name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">هدف یا توضیحات درخواست</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="علت نیاز به تسهیلات..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-danger fw-bold">ثبت و ارسال درخواست وام</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
