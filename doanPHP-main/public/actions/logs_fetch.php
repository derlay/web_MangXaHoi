<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../app/config/database.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
// Optional: chỉ cho Admin
// if (($_SESSION['role'] ?? 'User') !== 'Admin') {
//     echo json_encode(['error' => 'Forbidden']); exit;
// }

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = max(1, (int)($_GET['limit'] ?? 7));
$limit  = $limit > 50 ? 50 : $limit;
$q      = trim($_GET['q'] ?? '');

$needle = "%{$q}%";
$rows   = [];

if ($q === '') {
    $sql = "
        SELECT l.log_id,
               u.username AS admin,
               l.action_type,
               l.target_type,
               l.target_id,
               l.details,
               l.created_at
        FROM admin_action_logs l
        JOIN users u ON u.user_id = l.admin_user_id
        ORDER BY l.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $res = $conn->query($sql);
    if (!$res) {
        echo json_encode(['error' => 'DB error: ' . $conn->error]);
        exit;
    }
    $rows = $res->fetch_all(MYSQLI_ASSOC);
} else {
    $sql = "
        SELECT l.log_id,
               u.username AS admin,
               l.action_type,
               l.target_type,
               l.target_id,
               l.details,
               l.created_at
        FROM admin_action_logs l
        JOIN users u ON u.user_id = l.admin_user_id
        WHERE (u.username LIKE ? OR l.action_type LIKE ? OR l.details LIKE ?)
        ORDER BY l.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Prepare error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('sss', $needle, $needle, $needle);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($q === '') {
    $countSql = "SELECT COUNT(*) AS total FROM admin_action_logs";
    $cres = $conn->query($countSql);
    if (!$cres) {
        echo json_encode(['error' => 'Count error: ' . $conn->error]);
        exit;
    }
    $total = (int)($cres->fetch_assoc()['total'] ?? 0);
} else {
    $countSql = "
        SELECT COUNT(*) AS total
        FROM admin_action_logs l
        JOIN users u ON u.user_id = l.admin_user_id
        WHERE (u.username LIKE ? OR l.action_type LIKE ? OR l.details LIKE ?)
    ";
    $cstmt = $conn->prepare($countSql);
    if (!$cstmt) {
        echo json_encode(['error' => 'Count prepare error: ' . $conn->error]);
        exit;
    }
    $cstmt->bind_param('sss', $needle, $needle, $needle);
    $cstmt->execute();
    $total = (int)$cstmt->get_result()->fetch_assoc()['total'];
    $cstmt->close();
}

echo json_encode([
    'items'       => $rows,
    'has_more'    => ($offset + count($rows)) < $total,
    'next_offset' => $offset + count($rows),
    'total'       => $total
]);
