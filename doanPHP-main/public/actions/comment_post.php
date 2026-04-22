<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../app/config/database.php';

function json_error(string $msg, int $http = 400)
{
    http_response_code($http);
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
function json_ok(array $data = [])
{
    echo json_encode(['success' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra đăng nhập
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) json_error('Chưa đăng nhập', 401);

// Đọc input
$postId = (int)($_POST['post_id'] ?? 0);
// Chấp nhận cả content_text và content
$contentText = trim((string)($_POST['content_text'] ?? ($_POST['content'] ?? '')));

if ($postId <= 0) json_error('Thiếu post_id', 422);
if ($contentText === '') json_error('Nội dung trống', 422);
if (mb_strlen($contentText) > 1000) json_error('Nội dung quá dài (tối đa 1000 ký tự)', 422);

// Thêm bình luận
$sql = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) json_error('Prepare thất bại: ' . $conn->error, 500);
$stmt->bind_param('iis', $postId, $userId, $contentText);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    json_error('Execute thất bại: ' . $err, 500);
}
$commentId = $stmt->insert_id;
$stmt->close();

// Lấy lại comment vừa tạo (kèm user)
$q = "
  SELECT c.comment_id, c.post_id, c.user_id,
         c.content AS content_text, c.created_at,
         u.full_name, u.username, u.profile_picture_public_id
  FROM comments c
  JOIN users u ON u.user_id = c.user_id
  WHERE c.comment_id = ?
";
$s2 = $conn->prepare($q);
if (!$s2) json_error('Prepare select thất bại: ' . $conn->error, 500);
$s2->bind_param('i', $commentId);
$s2->execute();
$comment = $s2->get_result()->fetch_assoc() ?: [];
$s2->close();

// Bổ sung display_name và avatar_url (tùy chọn, nếu bạn muốn client nhẹ hơn)
$cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: '';
$comment['display_name'] = $comment['full_name'] ?: ($comment['username'] ?? 'Người dùng');
if (!empty($comment['profile_picture_public_id']) && $cloudName) {
    $pid = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '', $comment['profile_picture_public_id']);
    $comment['avatar_url'] = "https://res.cloudinary.com/{$cloudName}/image/upload/w_36,h_36,c_fill,g_face,r_max/{$pid}.jpg";
} else {
    $comment['avatar_url'] = '/public/img/default_avatar.jpg';
}

json_ok(['comment' => $comment]);