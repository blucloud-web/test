<?php
/**
 * Family Banking System - Transaction Model
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class Transaction {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT t.*, 
                   u.full_name as user_name, u.username as user_username,
                   cb.full_name as creator_name,
                   sa.account_number as source_acc_num, sa.account_type as source_acc_type,
                   da.account_number as dest_acc_num, da.account_type as dest_acc_type,
                   su.full_name as source_owner, du.full_name as dest_owner
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN users cb ON t.created_by = cb.id
            LEFT JOIN bank_accounts sa ON t.source_account_id = sa.id
            LEFT JOIN bank_accounts da ON t.dest_account_id = da.id
            LEFT JOIN users su ON sa.user_id = su.id
            LEFT JOIN users du ON da.user_id = du.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByTrackingCode($code) {
        $stmt = $this->db->prepare("SELECT id FROM transactions WHERE tracking_code = ?");
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ? $this->findById($row['id']) : null;
    }

    public function getAll($filters = []) {
        $sql = "
            SELECT t.*, 
                   u.full_name as user_name,
                   sa.account_number as source_acc_num,
                   da.account_number as dest_acc_num
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN bank_accounts sa ON t.source_account_id = sa.id
            LEFT JOIN bank_accounts da ON t.dest_account_id = da.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND (t.user_id = ? OR sa.user_id = ? OR da.user_id = ?)";
            $params[] = $filters['user_id'];
            $params[] = $filters['user_id'];
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['account_id'])) {
            $sql .= " AND (t.source_account_id = ? OR t.dest_account_id = ?)";
            $params[] = $filters['account_id'];
            $params[] = $filters['account_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND t.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (t.tracking_code LIKE ? OR t.description LIKE ? OR u.full_name LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY t.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * ایجاد تراکنش با مدیریت خودکار توازن موجودی دیتابیس (ACID Transaction)
     */
    public function createTransaction($data) {
        $this->db->beginTransaction();
        try {
            $tracking_code = generate_code('TRX');
            $type = $data['type'];
            $amount = (float)$data['amount'];
            $source_id = $data['source_account_id'] ?? null;
            $dest_id = $data['dest_account_id'] ?? null;
            $user_id = $data['user_id'];
            $created_by = $data['created_by'] ?? $user_id;

            // اعتبارسنجی موجودی در صورت برداشت یا انتقال
            if (in_array($type, ['withdrawal', 'transfer', 'member_transfer']) && $source_id) {
                $stmt = $this->db->prepare("SELECT balance, status FROM bank_accounts WHERE id = ?");
                $stmt->execute([$source_id]);
                $source_acc = $stmt->fetch();
                if (!$source_acc || $source_acc['status'] !== 'active') {
                    throw new Exception("حساب مبدا فعال نیست.");
                }
                if ($source_acc['balance'] < $amount) {
                    throw new Exception("موجودی حساب مبدا کافی نمی‌باشد.");
                }
            }

            // ثبت تراکنش
            $stmt = $this->db->prepare("
                INSERT INTO transactions (tracking_code, source_account_id, dest_account_id, user_id, type, amount, category, status, description, attachment, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $tracking_code,
                $source_id,
                $dest_id,
                $user_id,
                $type,
                $amount,
                $data['category'] ?? 'عمومی',
                $data['description'] ?? null,
                $data['attachment'] ?? null,
                $created_by,
                date('Y-m-d H:i:s')
            ]);
            $trans_id = $this->db->lastInsertId();

            // بروزرسانی موجودی حساب‌ها
            if ($source_id) {
                $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$amount, $source_id]);
            }

            if ($dest_id) {
                $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$amount, $dest_id]);
            }

            $this->db->commit();
            return $trans_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getRecent($limit = 5) {
        return $this->getAll(['limit' => $limit]);
    }

    public function getTotalStats() {
        $stmt = $this->db->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND type IN ('deposit', 'interest_payment')");
        $dep = $stmt->fetch();

        $stmt = $this->db->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND type = 'withdrawal'");
        $wth = $stmt->fetch();

        return [
            'total_deposit' => $dep['total'] ?? 0,
            'total_withdrawal' => $wth['total'] ?? 0
        ];
    }
}
