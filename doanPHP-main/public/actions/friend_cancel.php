<?php
// public/actions/friend_cancel.php
// POST { user_id } -> huỷ lời mời (pending) hoặc unfriend (accepted)

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

$me    = (int)$_SESSION['user_id'];
$other = (int)($_POST['user_id'] ?? 0);

if ($other <= 0 || $other === $me) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid user']);
    exit;
}

$del = $conn->prepare("
    DELETE FROM friendships
    WHERE (user_one_id = ? AND user_two_id = ?)
       OR (user_one_id = ? AND user_two_id = ?)
    LIMIT 1
");
if (!$del) {
    error_log("friend_cancel prepare failed: ".$conn->error);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'DB prepare failed']);
    exit;
}
$del->bind_param('iiii', $me, $other, $other, $me);
$del->execute();
$affected = $del->affected_rows;
$del->close();

echo json_encode(['success'=>($affected > 0)]);
exit;