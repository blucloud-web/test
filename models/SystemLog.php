<?php
/**
 * Family Banking System - SystemLog Model
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class SystemLog {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($filters = []) {
        $sql = "SELECT l.*, u.full_name as user_name, u.username FROM system_logs l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (l.action LIKE ? OR l.module LIKE ? OR l.details LIKE ? OR u.full_name LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['module'])) {
            $sql .= " AND l.module = ?";
            $params[] = $filters['module'];
        }

        $sql .= " ORDER BY l.id DESC LIMIT " . (int)($filters['limit'] ?? 100);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
