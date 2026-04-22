<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Bạn chưa đăng nhập']);
    exit;
}

$userId        = (int)$_SESSION['user_id'];
$contentText   = trim($_POST['content_text'] ?? '');
$privacy       = $_POST['privacy'] ?? 'friends';
$mediaUrl      = trim($_POST['media_url'] ?? '');
$mediaType     = trim($_POST['media_type'] ?? '');
$mediaPublicId = trim($_POST['media_public_id'] ?? '');

// Cho phép post chữ thuần: nếu không có media thì content_text phải có
if ($mediaUrl === '' && $contentText === '') {
    echo json_encode(['error' => 'Hãy nhập nội dung hoặc chọn media.']);
    exit;
}

// Chuẩn hóa privacy
$allowedPrivacy = ['public', 'friends', 'private'];
if (!in_array($privacy, $allowedPrivacy, true)) $privacy = 'friends';

// Chuẩn hóa media_type (chỉ khi có media)
if ($mediaUrl === '') {
    $mediaType     = null;
    $mediaPublicId = null;
} else {
    $allowedTypes = ['image', 'video', 'gif'];
    if (!in_array($mediaType, $allowedTypes, true)) {
        // fallback an toàn
        $mediaType = 'image';
    }
}

// INSERT (có/không có media_public_id tùy schema)
$sql = "
    INSERT INTO posts (user_id, content_text, media_url, media_type, media_public_id, privacy, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Lỗi DB: ' . $conn->error]);
    exit;
}

// Đưa null khi không có media (mysqli bind null vẫn được)
$mediaUrlParam      = $mediaUrl !== '' ? $mediaUrl : null;
$mediaTypeParam     = $mediaType ?: null;
$mediaPublicIdParam = $mediaPublicId ?: null;

$stmt->bind_param(
    'isssss',
    $userId,
    $contentText,
    $mediaUrlParam,
    $mediaTypeParam,
    $mediaPublicIdParam,
    $privacy
);

$ok = $stmt->execute();
if (!$ok) {
    $err = $stmt->error;
    $stmt->close();
    echo json_encode(['error' => 'Không thể tạo bài viết: ' . $err]);
    exit;
}
$postId = $stmt->insert_id;
$stmt->close();

echo json_encode(['success' => true, 'post_id' => $postId]);
