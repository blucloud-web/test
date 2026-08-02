<?php
/**
 * Family Banking System - Central Fund Model (صندوق مرکزی خانواده)
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class CentralFund {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getBalance() {
        return (float)get_setting('central_fund_balance', 500000000);
    }

    public function getAllLogs($limit = null) {
        $sql = "SELECT c.*, u.full_name as creator_name FROM central_fund_logs c JOIN users u ON c.created_by = u.id ORDER BY c.id DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function addLog($type, $amount, $title, $description, $created_by) {
        $amount = (float)$amount;
        $this->db->beginTransaction();
        try {
            // ثبت رکورد گردش صندوق
            $stmt = $this->db->prepare("INSERT INTO central_fund_logs (type, amount, title, description, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$type, $amount, $title, $description, $created_by, date('Y-m-d H:i:s')]);

            // بروزرسانی موجودی کل صندوق
            $current_balance = $this->getBalance();
            if ($type === 'income' || $type === 'capital_injection') {
                $new_balance = $current_balance + $amount;
            } else {
                $new_balance = $current_balance - $amount;
            }

            set_setting('central_fund_balance', $new_balance, 'موجودی کل صندوق مرکزی خانواده');

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getSummary() {
        $stmt = $this->db->query("SELECT SUM(amount) as total FROM central_fund_logs WHERE type = 'income'");
        $inc = $stmt->fetch();

        $stmt = $this->db->query("SELECT SUM(amount) as total FROM central_fund_logs WHERE type = 'expense'");
        $exp = $stmt->fetch();

        $stmt = $this->db->query("SELECT SUM(amount) as total FROM central_fund_logs WHERE type = 'capital_injection'");
        $cap = $stmt->fetch();

        return [
            'balance' => $this->getBalance(),
            'total_income' => $inc['total'] ?? 0,
            'total_expense' => $exp['total'] ?? 0,
            'total_capital' => $cap['total'] ?? 0
        ];
    }
}
