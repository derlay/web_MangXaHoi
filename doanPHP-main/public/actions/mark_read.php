<?php
// public/actions/mark_read.php
// POST { user_id } -> mark as read all messages FROM user_id TO current user

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
$other = (int)($_POST['user_id'] ?? 0);
if ($other <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid user']);
    exit;
}

$u = $conn->prepare("UPDATE messages SET read_at = NOW() WHERE sender_id = ? AND receiver_id = ? AND read_at IS NULL");
$u->bind_param('ii', $other, $me);
$ok = $u->execute();
$u->close();

echo json_encode(['success'=> (bool)$ok]);
exit;