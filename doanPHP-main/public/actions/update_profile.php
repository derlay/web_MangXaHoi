<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Bạn chưa đăng nhập']);
    exit;
}
$uid = (int)$_SESSION['user_id'];

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$bio      = trim($_POST['bio'] ?? '');
$avatarPid = trim($_POST['profile_picture_public_id'] ?? '');
$coverPid = trim($_POST['cover_photo_public_id'] ?? '');

if ($fullName === '' || $username === '') {
    echo json_encode(['error' => 'Full name & Username bắt buộc']);
    exit;
}

// Check username duplicate
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username=? AND user_id<>? LIMIT 1");
$stmt->bind_param('si', $username, $uid);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    echo json_encode(['error' => 'Username đã tồn tại']);
    exit;
}
$stmt->close();

$stmt = $conn->prepare("
  UPDATE users
  SET full_name=?,
      username=?,
      bio=?,
      profile_picture_public_id = NULLIF(?, ''),
      cover_photo_public_id     = NULLIF(?, '')
  WHERE user_id=?
");
if (!$stmt) {
    echo json_encode(['error' => 'DB error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('sssssi', $fullName, $username, $bio, $avatarPid, $coverPid, $uid);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    echo json_encode(['error' => 'Không thể lưu: ' . $err]);
    exit;
}
$stmt->close();

echo json_encode(['success' => true]);
