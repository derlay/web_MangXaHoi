<?php
// public/actions/friend_request.php
// POST { to_user_id } -> tạo lời mời kết bạn (pending)

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

$me = (int)$_SESSION['user_id'];
$to = (int)($_POST['to_user_id'] ?? 0);

if ($to <= 0 || $to === $me) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid user']);
    exit;
}

// Kiểm tra đã có quan hệ chưa
$sql = "
    SELECT friendship_id, user_one_id, user_two_id, status
    FROM friendships
    WHERE (user_one_id = ? AND user_two_id = ?)
       OR (user_one_id = ? AND user_two_id = ?)
    LIMIT 1
";
$st = $conn->prepare($sql);
$st->bind_param('iiii', $me, $to, $to, $me);
$st->execute();
$res = $st->get_result();
$existing = $res ? $res->fetch_assoc() : null;
$st->close();

if ($existing) {
    if ($existing['status'] === 'accepted') {
        echo json_encode(['success'=>true,'status'=>'accepted']);
        exit;
    }
    if ($existing['status'] === 'pending') {
        // nếu mình đã gửi trước
        if ((int)$existing['user_one_id'] === $me) {
            echo json_encode(['success'=>true,'status'=>'pending_sent']);
            exit;
        }
        // nếu đối phương gửi, mình có thể sau này hiển thị nút Accept
        echo json_encode(['success'=>true,'status'=>'pending_received']);
        exit;
    }
    if ($existing['status'] === 'blocked') {
        echo json_encode(['success'=>false,'error'=>'Blocked']);
        exit;
    }
}

// Tạo mới pending (me -> to)
$ins = $conn->prepare("
    INSERT INTO friendships (user_one_id, user_two_id, status)
    VALUES (?, ?, 'pending')
");
if (!$ins) {
    error_log("friend_request prepare failed: ".$conn->error);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'DB prepare failed']);
    exit;
}
$ins->bind_param('ii', $me, $to);
if (!$ins->execute()) {
    error_log("friend_request execute failed: ".$ins->error);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'DB execute failed']);
    $ins->close();
    exit;
}
$ins->close();

echo json_encode(['success'=>true,'status'=>'pending_sent']);
exit;