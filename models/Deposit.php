<?php
/**
 * Family Banking System - Deposit Model (سپرده‌های خانوادگی)
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class Deposit {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name as owner_name, a.account_number, a.account_type
            FROM deposits d
            JOIN users u ON d.user_id = u.id
            JOIN bank_accounts a ON d.account_id = a.id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll($filters = []) {
        $sql = "
            SELECT d.*, u.full_name as owner_name, a.account_number
            FROM deposits d
            JOIN users u ON d.user_id = u.id
            JOIN bank_accounts a ON d.account_id = a.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND d.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (d.deposit_number LIKE ? OR u.full_name LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY d.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $this->db->beginTransaction();
        try {
            $account_id = $data['account_id'];
            $amount = (float)$data['principal_amount'];

            // چک موجودی حساب برای کسر مبلغ سپرده
            $stmt = $this->db->prepare("SELECT balance FROM bank_accounts WHERE id = ?");
            $stmt->execute([$account_id]);
            $acc = $stmt->fetch();
            if (!$acc || $acc['balance'] < $amount) {
                throw new Exception("موجودی حساب انتخابی برای افتتاح سپرده کافی نیست.");
            }

            // کسر مبلغ سپرده از حساب اصلی
            $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $account_id]);

            $deposit_num = 'DEP-' . rand(1000, 9999) . '-' . rand(1000, 9999);
            $open_date = date('Y-m-d');
            $months = (int)$data['term_months'];
            $maturity_date = date('Y-m-d', strtotime("+{$months} months"));

            $stmt = $this->db->prepare("
                INSERT INTO deposits (user_id, account_id, deposit_number, principal_amount, interest_rate, term_months, auto_renew, status, open_date, maturity_date, total_interest_paid, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, 0.00, ?)
            ");
            $stmt->execute([
                $data['user_id'],
                $account_id,
                $deposit_num,
                $amount,
                $data['interest_rate'],
                $months,
                $data['auto_renew'] ?? 1,
                $open_date,
                $maturity_date,
                date('Y-m-d H:i:s')
            ]);
            $deposit_id = $this->db->lastInsertId();

            // ثبت تراکنش افتتاح سپرده
            $transModel = new Transaction();
            $transModel->createTransaction([
                'user_id' => $data['user_id'],
                'source_account_id' => $account_id,
                'dest_account_id' => null,
                'type' => 'withdrawal',
                'amount' => $amount,
                'category' => 'افتتاح سپرده',
                'description' => 'انتقال مبلغ جهت افتتاح سپرده شماره ' . $deposit_num
            ]);

            $this->db->commit();
            return $deposit_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * پرداخت سود ماهانه سپرده
     */
    public function payMonthlyInterest($deposit_id, $amount, $note = 'پرداخت سود ماهانه سپرده') {
        $deposit = $this->findById($deposit_id);
        if (!$deposit || $deposit['status'] !== 'active') {
            throw new Exception("سپرده یافت نشد یا غیرفعال است.");
        }

        $this->db->beginTransaction();
        try {
            // واریز به حساب صاحب سپرده
            $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $deposit['account_id']]);

            // بروزرسانی مجموع سود پرداختی
            $stmt = $this->db->prepare("UPDATE deposits SET total_interest_paid = total_interest_paid + ? WHERE id = ?");
            $stmt->execute([$amount, $deposit_id]);

            // ثبت لاگ سود
            $stmt = $this->db->prepare("INSERT INTO deposit_interest_logs (deposit_id, amount, payment_date, description, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$deposit_id, $amount, date('Y-m-d'), $note, date('Y-m-d H:i:s')]);

            // ثبت تراکنش
            $transModel = new Transaction();
            $transModel->createTransaction([
                'user_id' => $deposit['user_id'],
                'source_account_id' => null,
                'dest_account_id' => $deposit['account_id'],
                'type' => 'interest_payment',
                'amount' => $amount,
                'category' => 'سود سپرده',
                'description' => $note . ' شماره ' . $deposit['deposit_number']
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * فسخ پیش از موعد یا تسویه سپرده و عودت اصل سرمایه
     */
    public function cancelDeposit($deposit_id) {
        $deposit = $this->findById($deposit_id);
        if (!$deposit || $deposit['status'] !== 'active') {
            throw new Exception("سپرده غیرفعال است.");
        }

        $this->db->beginTransaction();
        try {
            $principal = (float)$deposit['principal_amount'];

            // واریز اصل پول به حساب کاربر
            $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$principal, $deposit['account_id']]);

            // تغییر وضعیت سپرده
            $stmt = $this->db->prepare("UPDATE deposits SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$deposit_id]);

            // تراکنش عودت اصل سپرده
            $transModel = new Transaction();
            $transModel->createTransaction([
                'user_id' => $deposit['user_id'],
                'source_account_id' => null,
                'dest_account_id' => $deposit['account_id'],
                'type' => 'deposit',
                'amount' => $principal,
                'category' => 'فسخ سپرده',
                'description' => 'عودت اصل سرمایه پس از فسخ/تسویه سپرده شماره ' . $deposit['deposit_number']
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getInterestLogs($deposit_id) {
        $stmt = $this->db->prepare("SELECT * FROM deposit_interest_logs WHERE deposit_id = ? ORDER BY id DESC");
        $stmt->execute([$deposit_id]);
        return $stmt->fetchAll();
    }

    public function getTotalPrincipal() {
        $stmt = $this->db->query("SELECT SUM(principal_amount) as total FROM deposits WHERE status = 'active'");
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }
}
