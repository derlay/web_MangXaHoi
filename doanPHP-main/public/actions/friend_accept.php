<?php
// public/actions/friend_accept.php
// POST { user_id } (người đã gửi lời mời) -> set accepted

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method Not Allowed']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$me    = (int)$_SESSION['user_id']; // người nhận request
$other = (int)($_POST['user_id'] ?? 0);

if ($other <= 0 || $other === $me) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid user']);
    exit;
}

// chỉ cho phép accept nếu có pending từ other -> me
$upd = $conn->prepare("
    UPDATE friendships
    SET status = 'accepted', accepted_at = NOW()
    WHERE user_one_id = ? AND user_two_id = ? AND status = 'pending'
    LIMIT 1
");
if (!$upd) {
    error_log("friend_accept prepare failed: ".$conn->error);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'DB prepare failed']);
    exit;
}
$upd->bind_param('ii', $other, $me);
$upd->execute();
$affected = $upd->affected_rows;
$upd->close();

if ($affected <= 0) {
    echo json_encode(['success'=>false,'error'=>'No pending request']);
    exit;
}

echo json_encode(['success'=>true,'status'=>'accepted']);
exit;