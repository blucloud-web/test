<?php
/**
 * Family Banking System - Account Model
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class Account {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT a.*, u.full_name as owner_name, u.username as owner_username FROM bank_accounts a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByAccountNumber($account_number) {
        $stmt = $this->db->prepare("SELECT a.*, u.full_name as owner_name FROM bank_accounts a JOIN users u ON a.user_id = u.id WHERE a.account_number = ?");
        $stmt->execute([$account_number]);
        return $stmt->fetch();
    }

    public function getByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function getAll($search = '', $type = '', $status = '') {
        $sql = "SELECT a.*, u.full_name as owner_name, u.username as owner_username FROM bank_accounts a JOIN users u ON a.user_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (a.account_number LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($type)) {
            $sql .= " AND a.account_type = ?";
            $params[] = $type;
        }

        if (!empty($status)) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $acc_num = 'ACC-' . rand(1000, 9999) . '-' . rand(1000, 9999);
        $stmt = $this->db->prepare("INSERT INTO bank_accounts (user_id, account_number, account_type, balance, status, open_date, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['user_id'],
            $acc_num,
            $data['account_type'],
            $data['balance'] ?? 0,
            $data['status'] ?? 'active',
            $data['open_date'] ?? date('Y-m-d'),
            $data['description'] ?? null,
            date('Y-m-d H:i:s')
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE bank_accounts SET account_type = ?, status = ?, description = ? WHERE id = ?");
        return $stmt->execute([
            $data['account_type'],
            $data['status'],
            $data['description'] ?? null,
            $id
        ]);
    }

    public function updateBalance($id, $amount, $operation = 'add') {
        if ($operation === 'add') {
            $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE id = ?");
        } else {
            $stmt = $this->db->prepare("UPDATE bank_accounts SET balance = balance - ? WHERE id = ?");
        }
        return $stmt->execute([$amount, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM bank_accounts WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getTotalBalance() {
        $stmt = $this->db->query("SELECT SUM(balance) as total FROM bank_accounts WHERE status = 'active'");
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }

    public function countActive() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM bank_accounts WHERE status = 'active'");
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }
}
