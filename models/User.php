<?php
/**
 * Family Banking System - User Model
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class User {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function getAll($search = '', $role = '', $status = '') {
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR username LIKE ? OR mobile LIKE ? OR national_id LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO users (full_name, username, password, email, role, status, mobile, national_id, address, avatar, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt->execute([
            $data['full_name'],
            $data['username'],
            $password_hash,
            $data['email'] ?? null,
            $data['role'] ?? 'member',
            $data['status'] ?? 'active',
            $data['mobile'] ?? null,
            $data['national_id'] ?? null,
            $data['address'] ?? null,
            $data['avatar'] ?? null,
            $data['notes'] ?? null,
            date('Y-m-d H:i:s')
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, status = ?, mobile = ?, national_id = ?, address = ?, notes = ?";
        $params = [
            $data['full_name'],
            $data['email'] ?? null,
            $data['role'],
            $data['status'],
            $data['mobile'] ?? null,
            $data['national_id'] ?? null,
            $data['address'] ?? null,
            $data['notes'] ?? null
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (isset($data['avatar'])) {
            $sql .= ", avatar = ?";
            $params[] = $data['avatar'];
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM users");
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }

    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
