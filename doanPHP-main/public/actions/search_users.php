<?php
// public/actions/search_users.php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$currentId = (int)$_SESSION['user_id'];
$q = trim((string)($_GET['q'] ?? ''));

if ($q === '') {
    echo json_encode(['success'=>true,'results'=>[]]);
    exit;
}

// limit đơn giản
$limit = 10;

// tìm theo username / full_name gần đúng
$stmt = $conn->prepare("
    SELECT user_id, username, full_name, profile_picture_public_id
    FROM users
    WHERE user_id <> ?
      AND (username LIKE CONCAT('%', ?, '%') OR full_name LIKE CONCAT('%', ?, '%'))
    ORDER BY full_name ASC
    LIMIT ?
");
if (!$stmt) {
    error_log("search_users prepare failed: ".$conn->error);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'DB prepare failed']);
    exit;
}
$stmt->bind_param('issi', $currentId, $q, $q, $limit);
$stmt->execute();
$res = $stmt->get_result();
$users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// helper: xác định friend_status
function get_friend_status(mysqli $conn, int $me, int $other): string {
    $sql = "
        SELECT user_one_id, user_two_id, status
        FROM friendships
        WHERE (user_one_id = ? AND user_two_id = ?)
           OR (user_one_id = ? AND user_two_id = ?)
        LIMIT 1
    ";
    $st = $conn->prepare($sql);
    if (!$st) return 'none';
    $st->bind_param('iiii', $me, $other, $other, $me);
    $st->execute();
    $r = $st->get_result();
    $row = $r ? $r->fetch_assoc() : null;
    $st->close();
    if (!$row) return 'none';
    if ($row['status'] === 'blocked') return 'blocked';
    if ($row['status'] === 'accepted') return 'accepted';
    // pending: phân biệt gửi/nhận
    if ($row['status'] === 'pending') {
        if ((int)$row['user_one_id'] === $me) return 'pending_sent';
        return 'pending_received';
    }
    return 'none';
}

// build kết quả
$results = [];
foreach ($users as $u) {
    $otherId = (int)$u['user_id'];
    $status  = get_friend_status($conn, $currentId, $otherId);

    // avatar: bạn đang dùng Cloudinary; tạm thời trả về public_id, hoặc đường dẫn cũ nếu có
    $results[] = [
        'user_id'    => $otherId,
        'username'   => $u['username'],
        'full_name'  => $u['full_name'],
        'avatar'     => $u['profile_picture_public_id'], // bạn có thể đổi thành URL thực nếu muốn
        'friend_status' => $status,
    ];
}

echo json_encode(['success'=>true,'results'=>$results]);
exit;