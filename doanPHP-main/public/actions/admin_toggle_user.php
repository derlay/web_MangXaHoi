<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'User') !== 'Admin') {
    http_response_code(403);
    exit('Forbidden');
}
$targetUserId = (int)($_POST['user_id'] ?? 0);
$adminId = (int)$_SESSION['user_id'];

if ($targetUserId <= 0) {
    header('Location:/public/admin/index.php?tab=users');
    exit;
}
$exists = $conn->prepare("SELECT 1 FROM blocked_users WHERE blocker_id=? AND blocked_id=? LIMIT 1");
$exists->bind_param('ii', $adminId, $targetUserId);
$exists->execute();
$isBanned = (bool)$exists->get_result()->fetch_assoc();
$exists->close();

if ($isBanned) {
    // Unban
    $stmt = $conn->prepare("DELETE FROM blocked_users WHERE blocker_id=? AND blocked_id=?");
    $stmt->bind_param('ii', $adminId, $targetUserId);
    $stmt->execute();
    $stmt->close();

    $log = $conn->prepare("
    INSERT INTO admin_action_logs (admin_user_id, action_type, target_type, target_id, details, created_at)
    VALUES (?, 'unban_user', 'user', ?, 'Unban user', NOW())
  ");
    $log->bind_param('ii', $adminId, $targetUserId);
    $log->execute();
    $log->close();
} else {
    // Ban
    $stmt = $conn->prepare("INSERT INTO blocked_users (blocker_id, blocked_id, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param('ii', $adminId, $targetUserId);
    $stmt->execute();
    $stmt->close();

    $log = $conn->prepare("
    INSERT INTO admin_action_logs (admin_user_id, action_type, target_type, target_id, details, created_at)
    VALUES (?, 'ban_user', 'user', ?, 'Ban user', NOW())
  ");
    $log->bind_param('ii', $adminId, $targetUserId);
    $log->execute();
    $log->close();
}



header('Location:/public/admin/index.php?tab=users');
