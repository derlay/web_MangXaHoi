<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

// Kết nối DB (chỉnh lại đường dẫn file cấu hình nếu dự án của bạn khác)
require_once __DIR__ . '/../../app/config/database.php';

// Lấy Cloudinary cloud_name từ ENV nếu có (để build avatar_url). Không có thì fallback default avatar.
$cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: '';

// Helpers
function json_error(string $message, int $httpCode = 400): void
{
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function avatar_url(?string $publicId): string
{
    global $cloudName;
    if ($publicId && $cloudName) {
        // Cắt bỏ đuôi .jpg nếu người dùng đã lưu kèm đuôi
        $pid = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '', $publicId);
        return "https://res.cloudinary.com/{$cloudName}/image/upload/w_36,h_36,c_fill,g_face,r_max/{$pid}.jpg";
    }
    return '/public/img/default_avatar.jpg';
}

// Đọc tham số
$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = max(1, (int)($_GET['limit'] ?? 12));
$limit  = ($limit > 50) ? 50 : $limit;

if ($postId <= 0) {
    json_error('Thiếu hoặc sai post_id', 422);
}

// Kiểm tra kết nối DB
if (!isset($conn) || !($conn instanceof mysqli)) {
    json_error('Không kết nối được cơ sở dữ liệu', 500);
}

// Truy vấn danh sách bình luận
// Lưu ý: bảng comments có cột "content" -> alias thành "content_text" cho thống nhất với frontend
$sql = "
  SELECT
    c.comment_id,
    c.post_id,
    c.user_id,
    c.parent_comment_id,
    c.content AS content_text,
    c.created_at,
    u.full_name,
    u.username,
    u.profile_picture_public_id
  FROM comments c
  JOIN users u ON u.user_id = c.user_id
  WHERE c.post_id = ?
  ORDER BY c.created_at DESC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_error('Prepare thất bại: ' . $conn->error, 500);
}

$stmt->bind_param('iii', $postId, $limit, $offset);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    json_error('Execute thất bại: ' . $err, 500);
}

$res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Đếm tổng số bình luận của post
$countSql = "SELECT COUNT(*) AS total FROM comments WHERE post_id = ?";
$cst = $conn->prepare($countSql);
if (!$cst) {
    json_error('Prepare count thất bại: ' . $conn->error, 500);
}
$cst->bind_param('i', $postId);
$cst->execute();
$totalRow = $cst->get_result()->fetch_assoc();
$cst->close();

$total = (int)($totalRow['total'] ?? 0);

// Bổ sung display_name và avatar_url cho tiện frontend
foreach ($items as &$it) {
    $it['display_name'] = $it['full_name'] ?: ($it['username'] ?: 'Người dùng');
    $it['avatar_url']   = avatar_url($it['profile_picture_public_id'] ?? null);
}
unset($it);

// Trả JSON
echo json_encode([
    'success'     => true,
    'items'       => $items,
    'has_more'    => ($offset + count($items)) < $total,
    'next_offset' => $offset + count($items),
    'total'       => $total,
], JSON_UNESCAPED_UNICODE);