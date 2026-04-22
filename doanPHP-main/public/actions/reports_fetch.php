<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
// if (($_SESSION['role']??'User')!=='Admin'){ echo json_encode(['error'=>'Forbidden']); exit; }

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = max(1, (int)($_GET['limit'] ?? 10));
$limit  = $limit > 50 ? 50 : $limit;
$q      = trim($_GET['q'] ?? '');    // tìm theo reporter/target_type/reason
$statusFilter = trim($_GET['status'] ?? ''); // optional: pending/resolved/dismissed

$needle = "%{$q}%";
$rows = [];

$where = [];
$params = [];
$types  = '';

if ($q !== '') {
    $where[] = "(ur.username LIKE ? OR r.target_type LIKE ? OR r.reason LIKE ?)";
    $params[] = $needle;
    $params[] = $needle;
    $params[] = $needle;
    $types .= 'sss';
}

if ($statusFilter !== '') {
    $where[] = "r.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

if (empty($params)) {
    $sql = "
    SELECT r.report_id, ur.username AS reporter, r.target_type, r.target_id,
           r.reason, r.status, r.created_at
    FROM reports r
    JOIN users ur ON ur.user_id = r.reporter_id
    $whereSql
    ORDER BY r.created_at DESC
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
    SELECT r.report_id, ur.username AS reporter, r.target_type, r.target_id,
           r.reason, r.status, r.created_at
    FROM reports r
    JOIN users ur ON ur.user_id = r.reporter_id
    $whereSql
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
  ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Prepare error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if (empty($params)) {
    $countSql = "
    SELECT COUNT(*) total
    FROM reports r
    JOIN users ur ON ur.user_id = r.reporter_id
    $whereSql
  ";
    $cres = $conn->query($countSql);
    $total = (int)($cres->fetch_assoc()['total'] ?? 0);
} else {
    $countSql = "
    SELECT COUNT(*) total
    FROM reports r
    JOIN users ur ON ur.user_id = r.reporter_id
    $whereSql
  ";
    $cstmt = $conn->prepare($countSql);
    if (!$cstmt) {
        echo json_encode(['error' => 'Count prepare error: ' . $conn->error]);
        exit;
    }
    $cstmt->bind_param($types, ...$params);
    $cstmt->execute();
    $total = (int)$cstmt->get_result()->fetch_assoc()['total'];
    $cstmt->close();
}

echo json_encode([
    'items' => $rows,
    'has_more' => ($offset + count($rows)) < $total,
    'next_offset' => $offset + count($rows),
    'total' => $total
]);
