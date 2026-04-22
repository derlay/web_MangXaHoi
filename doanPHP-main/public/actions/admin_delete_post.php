<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'User') !== 'Admin') {
    http_response_code(403);
    exit('Forbidden');
}

$adminId = (int)$_SESSION['user_id'];
$postId = (int)($_POST['post_id'] ?? 0);
if ($postId <= 0) {
    header('Location:/public/admin/index.php?tab=posts');
    exit;
}

$d = $conn->prepare("DELETE FROM posts WHERE post_id=?");
$d->bind_param('i', $postId);
$d->execute();
$d->close();

$log = $conn->prepare("
  INSERT INTO admin_action_logs (admin_user_id, action_type, target_type, target_id, details, created_at)
  VALUES (?, 'delete_post', 'post', ?, 'Deleted post', NOW())
");
$log->bind_param('ii', $adminId, $postId);
$log->execute();
$log->close();

header('Location:/public/admin/index.php?tab=posts');
