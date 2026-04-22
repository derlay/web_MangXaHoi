<?php
// public/actions/like_post.php
// Toggle like/unlike, trả { success, action (liked|unliked), likes }

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid post id']);
    exit;
}

$uid = (int)$_SESSION['user_id'];

// Kiểm tra đã like chưa (sử dụng prepare + store_result)
$chk = $conn->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ? LIMIT 1");
if (!$chk) {
    error_log("like_post chk prepare failed: " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}
$chk->bind_param('ii', $postId, $uid);
$chk->execute();
$chk->store_result();
$liked = $chk->num_rows > 0;
$chk->close();

$ok = false;
if ($liked) {
    $del = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
    if ($del) {
        $del->bind_param('ii', $postId, $uid);
        $ok = $del->execute();
        $del->close();
    } else {
        error_log("like_post delete prepare failed: " . $conn->error);
    }
    $action = 'unliked';
} else {
    $ins = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    if ($ins) {
        $ins->bind_param('ii', $postId, $uid);
        $ok = $ins->execute();
        $ins->close();
    } else {
        error_log("like_post insert prepare failed: " . $conn->error);
    }
    $action = 'liked';
}

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB operation failed']);
    exit;
}

// trả về số like hiện thời
$r = $conn->prepare("SELECT COUNT(*) AS c FROM post_likes WHERE post_id = ?");
$count = 0;
if ($r) {
    $r->bind_param('i', $postId);
    $r->execute();
    if (method_exists($r, 'get_result')) {
        $res = $r->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $count = (int)($row['c'] ?? 0);
    } else {
        $r->bind_result($cnt);
        $r->fetch();
        $count = (int)($cnt ?? 0);
    }
    $r->close();
} else {
    $rr = $conn->query("SELECT COUNT(*) AS c FROM post_likes WHERE post_id = " . (int)$postId);
    $row = $rr ? $rr->fetch_assoc() : null;
    $count = (int)($row['c'] ?? 0);
}

echo json_encode(['success' => true, 'action' => $action, 'likes' => $count]);
exit;