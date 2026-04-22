<?php
require_once __DIR__ . '/../../app/config/database.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
if (!isset($_POST['post_id'])) {
    echo json_encode(['error' => 'Missing post_id']);
    exit;
}

$postId = (int)$_POST['post_id'];
$userId = (int)$_SESSION['user_id'];
global $conn;

// Tạo record share
$stmt = $conn->prepare("INSERT INTO shares (post_id, user_id, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param('ii', $postId, $userId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['error' => 'Không thể share']);
    exit;
}

$countRes = $conn->query("SELECT COUNT(*) AS c FROM shares WHERE post_id = $postId");
$shareCount = $countRes ? (int)$countRes->fetch_assoc()['c'] : 0;

echo json_encode(['success' => true, 'share_count' => $shareCount]);
