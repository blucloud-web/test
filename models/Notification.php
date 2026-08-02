<?php
/**
 * Family Banking System - Notification Model
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class Notification {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByUserId($user_id, $limit = 20) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT " . (int)$limit);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function getUnreadCount($user_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }

    public function markAllAsRead($user_id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
