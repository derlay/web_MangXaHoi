<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

// TODO: đổi đường dẫn require theo dự án của bạn
require_once __DIR__ . '/../../app/config/database.php';

// Xác thực
$currentUserId = $_SESSION['user_id'] ?? null;
if (!$currentUserId) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập']);
    exit;
}

// Input
$postId  = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$content = isset($_POST['content']) ? trim((string)$_POST['content']) : '';
if ($postId <= 0) {
    echo json_encode(['success' => false, 'error' => 'post_id không hợp lệ']);
    exit;
}
if ($content === '') {
    echo json_encode(['success' => false, 'error' => 'Nội dung trống']);
    exit;
}
if (mb_strlen($content) > 500) {
    echo json_encode(['success' => false, 'error' => 'Nội dung quá dài (tối đa 500 ký tự)']);
    exit;
}

// Kiểm tra bài viết tồn tại và quyền bình luận (nếu cần)
$st = $conn->prepare("SELECT post_id, privacy, user_id FROM posts WHERE post_id=? LIMIT 1");
$st->bind_param('i', $postId);
$st->execute();
$post = $st->get_result()->fetch_assoc();
$st->close();

if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Bài viết không tồn tại']);
    exit;
}

// Nếu có luật privacy phức tạp (friends/private), thêm kiểm tra ở đây.
// Ví dụ: nếu privacy='private' và currentUserId != owner -> chặn.
// if ($post['privacy'] === 'private' && (int)$post['user_id'] !== (int)$currentUserId) {
//   echo json_encode(['success' => false, 'error' => 'Không có quyền bình luận bài viết này']); exit;
// }

// Tạo bình luận
$now = date('Y-m-d H:i:s');

$st = $conn->prepare("INSERT INTO comments (post_id, user_id, content_text, created_at) VALUES (?, ?, ?, ?)");
$st->bind_param('iiss', $postId, $currentUserId, $content, $now);
$ok = $st->execute();
if (!$ok) {
    $err = $conn->error ?: 'Không thể tạo bình luận';
    $st->close();
    echo json_encode(['success' => false, 'error' => $err]);
    exit;
}
$commentId = (int)$st->insert_id;
$st->close();

// Lấy thông tin user để trả về cho client hiển thị
$st = $conn->prepare("SELECT full_name, profile_picture_public_id FROM users WHERE user_id=? LIMIT 1");
$st->bind_param('i', $currentUserId);
$st->execute();
$u = $st->get_result()->fetch_assoc();
$st->close();

$avatarUrl = '/public/img/default_avatar.jpg';
if (!empty($u['profile_picture_public_id'])) {
    // Tùy cấu hình Cloudinary của bạn
    $avatarUrl = "https://res.cloudinary.com/{$cloudName}/image/upload/w_48,h_48,c_fill,g_face,r_max/" . $u['profile_picture_public_id'] . ".jpg";
}

// Trả về object comment để client thêm vào đầu danh sách (overlay)
echo json_encode([
    'success' => true,
    'data' => [
        'comment_id' => $commentId,
        'post_id'    => $postId,
        'user_id'    => (int)$currentUserId,
        'full_name'  => $u['full_name'] ?? 'Bạn',
        'profile_picture_url' => $avatarUrl,
        'content_text' => $content,
        'created_at'   => $now
    ]
]);