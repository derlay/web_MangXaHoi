<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/config/cloudinary.php';
require_once __DIR__ . '/../../app/helpers/cloudinary_helper.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Bạn chưa đăng nhập']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$postId = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($postId <= 0 || $content === '') {
    echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param('iis', $postId, $userId, $content);
if (!$stmt->execute()) {
    echo json_encode(['error' => 'DB error: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$commentId = (int)$stmt->insert_id;
$stmt->close();

// Lấy lại dữ liệu với user để trả về item hoàn chỉnh
$stmt2 = $conn->prepare("
  SELECT c.comment_id, c.content, c.created_at,
         u.full_name, u.username, u.profile_picture_public_id
  FROM comments c
  JOIN users u ON u.user_id = c.user_id
  WHERE c.comment_id = ?
  LIMIT 1
");
$stmt2->bind_param('i', $commentId);
$stmt2->execute();
$row = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

$cloud = include __DIR__ . '/../../app/config/cloudinary.php';
$avatarUrl = cloudinary_avatar(
    $cloud['cloud_name'],
    $row['profile_picture_public_id'] ?? null,
    ['default_public_id' => $cloud['default_avatar_public_id'], 'w' => 32, 'h' => 32]
);

echo json_encode([
    'item' => [
        'id' => (int)$row['comment_id'],
        'content' => $row['content'],
        'created_at' => $row['created_at'],
        'author_name' => $row['full_name'],
        'username' => $row['username'],
        'author_avatar_url' => $avatarUrl,
    ]
]);
