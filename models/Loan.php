<?php
/**
 * Family Banking System - Loan Model (وام و تسهیلات خانوادگی)
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class Loan {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT l.*, 
                   u.full_name as borrower_name, u.mobile as borrower_mobile,
                   g.full_name as guarantor_name
            FROM loans l
            JOIN users u ON l.user_id = u.id
            LEFT JOIN users g ON l.guarantor_user_id = g.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll($filters = []) {
        $sql = "
            SELECT l.*, 
                   u.full_name as borrower_name,
                   g.full_name as guarantor_name
            FROM loans l
            JOIN users u ON l.user_id = u.id
            LEFT JOIN users g ON l.guarantor_user_id = g.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND l.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND l.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (l.loan_number LIKE ? OR u.full_name LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY l.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createRequest($data) {
        $loan_num = 'LON-' . rand(1000, 9999) . '-' . rand(1000, 9999);
        $amount = (float)$data['amount'];
        $rate = (float)($data['interest_rate'] ?? get_setting('default_loan_interest_rate', 4));
        $months = (int)$data['term_months'];

        // محاسبه قسط ماهانه و کل بازپرداخت
        $total_interest = $amount * ($rate / 100);
        $total_repayment = $amount + $total_interest;
        $monthly_inst = ceil($total_repayment / $months);

        $stmt = $this->db->prepare("
            INSERT INTO loans (user_id, loan_number, amount, interest_rate, term_months, monthly_installment, total_repayment, guarantor_user_id, status, request_date, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'],
            $loan_num,
            $amount,
            $rate,
            $months,
            $monthly_inst,
            $total_repayment,
            $data['guarantor_user_id'] ?? null,
            date('Y-m-d'),
            $data['notes'] ?? null,
            date('Y-m-d H:i:s')
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * تایید وام توسط مدیر و تولید جدول اقساط + واریز پول به حساب متقاضی
     */
    public function approveLoan($loan_id, $target_account_id) {
        $loan = $this->findById($loan_id);
        if (!$loan || $loan['status'] !== 'pending') {
            throw new Exception("درخواست وام یافت نشد یا قبلاً تعیین تکلیف شده است.");
        }

        $this->db->beginTransaction();
        try {
            // واریز اصل مبلغ وام به حساب متقاضی
            $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$loan['amount'], $target_account_id]);

            // تغییر وضعیت وام به فعال
            $stmt = $this->db->prepare("UPDATE loans SET status = 'active', approval_date = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d'), $loan_id]);

            // ایجاد جدول اقساط
            $months = (int)$loan['term_months'];
            $monthly_principal = $loan['amount'] / $months;
            $monthly_interest = ($loan['total_repayment'] - $loan['amount']) / $months;

            for ($i = 1; $i <= $months; $i++) {
                $due_date = date('Y-m-d', strtotime("+{$i} months"));
                $stmt = $this->db->prepare("
                    INSERT INTO loan_installments (loan_id, installment_number, due_date, principal_amount, interest_amount, penalty_amount, total_amount, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 0.00, ?, 'pending', ?)
                ");
                $stmt->execute([
                    $loan_id,
                    $i,
                    $due_date,
                    $monthly_principal,
                    $monthly_interest,
                    $loan['monthly_installment'],
                    date('Y-m-d H:i:s')
                ]);
            }

            // ثبت تراکنش پرداخت وام
            $transModel = new Transaction();
            $transModel->createTransaction([
                'user_id' => $loan['user_id'],
                'source_account_id' => null,
                'dest_account_id' => $target_account_id,
                'type' => 'loan_disbursement',
                'amount' => $loan['amount'],
                'category' => 'پرداخت وام',
                'description' => 'واریز مبلغ تسهیلات وام شماره ' . $loan['loan_number']
            ]);

            // ارسال اعلان به کاربر
            send_notification($loan['user_id'], 'تایید درخواست وام', 'درخواست وام شماره ' . $loan['loan_number'] . ' تایید و به حساب شما واریز شد.', 'success', 'loans.php');

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function rejectLoan($loan_id, $reason = '') {
        $loan = $this->findById($loan_id);
        if (!$loan) return false;

        $stmt = $this->db->prepare("UPDATE loans SET status = 'rejected', notes = ? WHERE id = ?");
        $res = $stmt->execute([$reason, $loan_id]);
        if ($res) {
            send_notification($loan['user_id'], 'رد درخواست وام', 'درخواست وام شماره ' . $loan['loan_number'] . ' متاسفانه تایید نشد.', 'danger', 'loans.php');
        }
        return $res;
    }

    /**
     * پرداخت قسط وام
     */
    public function payInstallment($installment_id, $from_account_id) {
        $stmt = $this->db->prepare("SELECT i.*, l.user_id, l.loan_number, l.id as loan_id FROM loan_installments i JOIN loans l ON i.loan_id = l.id WHERE i.id = ?");
        $stmt->execute([$installment_id]);
        $inst = $stmt->fetch();

        if (!$inst || $inst['status'] === 'paid') {
            throw new Exception("قسط یافت نشد یا قبلاً پرداخت شده است.");
        }

        $this->db->beginTransaction();
        try {
            // محاسبه جریمه دیرکرد در صورت تاخیر
            $penalty = 0;
            if (strtotime($inst['due_date']) < strtotime(date('Y-m-d'))) {
                $penalty_rate = (float)get_setting('late_penalty_rate', 1.5);
                $penalty = ($inst['total_amount'] * ($penalty_rate / 100));
            }

            $total_payable = $inst['total_amount'] + $penalty;

            // کسر از حساب متقاضی
            $stmt = $this->db->prepare("SELECT balance FROM bank_accounts WHERE id = ?");
            $stmt->execute([$from_account_id]);
            $acc = $stmt->fetch();
            if (!$acc || $acc['balance'] < $total_payable) {
                throw new Exception("موجودی حساب کافی نیست. مبلغ قابل پرداخت با جریمه: " . format_money($total_payable));
            }

            $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$total_payable, $from_account_id]);

            // ثبت تراکنش
            $transModel = new Transaction();
            $trans_id = $transModel->createTransaction([
                'user_id' => $inst['user_id'],
                'source_account_id' => $from_account_id,
                'dest_account_id' => null,
                'type' => 'loan_repayment',
                'amount' => $total_payable,
                'category' => 'بازپرداخت قسط',
                'description' => 'پرداخت قسط شماره ' . $inst['installment_number'] . ' وام ' . $inst['loan_number']
            ]);

            // بروزرسانی وضعیت قسط
            $stmt = $this->db->prepare("UPDATE loan_installments SET status = 'paid', payment_date = ?, penalty_amount = ?, transaction_id = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d'), $penalty, $trans_id, $installment_id]);

            // چک تسویه کامل وام
            $stmt = $this->db->prepare("SELECT COUNT(*) as remaining FROM loan_installments WHERE loan_id = ? AND status != 'paid'");
            $stmt->execute([$inst['loan_id']]);
            $rem = $stmt->fetch();
            if ($rem['remaining'] == 0) {
                $stmt = $this->db->prepare("UPDATE loans SET status = 'settled' WHERE id = ?");
                $stmt->execute([$inst['loan_id']]);
                send_notification($inst['user_id'], 'تسویه کامل وام', 'تبریک! تمام اقساط وام شماره ' . $inst['loan_number'] . ' تسویه شد.', 'success', 'loans.php');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getInstallments($loan_id) {
        $stmt = $this->db->prepare("SELECT * FROM loan_installments WHERE loan_id = ? ORDER BY installment_number ASC");
        $stmt->execute([$loan_id]);
        return $stmt->fetchAll();
    }

    public function getTotalActiveLoansAmount() {
        $stmt = $this->db->query("SELECT SUM(amount) as total FROM loans WHERE status = 'active'");
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }
}
