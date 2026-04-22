<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/config/cloudinary.php';
require_once __DIR__ . '/../../app/helpers/cloudinary_helper.php';

$postId = (int)($_GET['post_id'] ?? 0);
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = min(20, max(1, (int)($_GET['limit'] ?? 5)));

if ($postId <= 0) {
    echo json_encode(['error' => 'post_id không hợp lệ']);
    exit;
}

$cloud = include __DIR__ . '/../../app/config/cloudinary.php';

$stmt = $conn->prepare("
  SELECT c.comment_id, c.content, c.created_at,
         u.full_name, u.username, u.profile_picture_public_id
  FROM comments c
  JOIN users u ON u.user_id = c.user_id
  WHERE c.post_id = ?
  ORDER BY c.created_at DESC
  LIMIT ? OFFSET ?
");
$stmt->bind_param('iii', $postId, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($row = $res->fetch_assoc()) {
    $avatarUrl = cloudinary_avatar(
        $cloud['cloud_name'],
        $row['profile_picture_public_id'] ?? null,
        ['default_public_id' => $cloud['default_avatar_public_id'], 'w' => 32, 'h' => 32]
    );
    $items[] = [
        'id' => (int)$row['comment_id'],
        'content' => $row['content'],
        'created_at' => $row['created_at'],
        'author_name' => $row['full_name'],
        'username' => $row['username'],
        'author_avatar_url' => $avatarUrl,
    ];
}
$stmt->close();

// Kiểm tra còn nữa không
$stmt2 = $conn->prepare("SELECT COUNT(*) AS total FROM comments WHERE post_id=?");
$stmt2->bind_param('i', $postId);
$stmt2->execute();
$total = (int)$stmt2->get_result()->fetch_assoc()['total'];
$stmt2->close();

echo json_encode([
    'items' => $items,
    'has_more' => ($offset + count($items)) < $total
]);
