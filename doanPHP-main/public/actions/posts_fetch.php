<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
// if (($_SESSION['role']??'User')!=='Admin') { echo json_encode(['error'=>'Forbidden']); exit; }

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = max(1, (int)($_GET['limit'] ?? 7));
$limit  = $limit > 50 ? 50 : $limit;
$q      = trim($_GET['q'] ?? '');

$needle = "%{$q}%";

$flagSql = "
  SELECT target_id, COUNT(*) cnt
  FROM reports
  WHERE target_type='post' AND status='pending'
  GROUP BY target_id
";
$flagsMap = [];
$fr = $conn->query($flagSql);
if ($fr) {
    foreach ($fr->fetch_all(MYSQLI_ASSOC) as $r) {
        $flagsMap[(int)$r['target_id']] = (int)$r['cnt'];
    }
}

$rows = [];
if ($q === '') {
    $sql = "
    SELECT p.post_id, u.username, p.content_text, p.media_type, p.media_url,
           p.privacy, p.created_at
    FROM posts p
    JOIN users u ON u.user_id = p.user_id
    ORDER BY p.created_at DESC
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
    SELECT p.post_id, u.username, p.content_text, p.media_type, p.media_url,
           p.privacy, p.created_at
    FROM posts p
    JOIN users u ON u.user_id = p.user_id
    WHERE (u.username LIKE ? OR p.content_text LIKE ?)
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
  ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Prepare error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('ss', $needle, $needle);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($q === '') {
    $countSql = "SELECT COUNT(*) total FROM posts";
    $cres = $conn->query($countSql);
    $total = (int)($cres->fetch_assoc()['total'] ?? 0);
} else {
    $countSql = "
    SELECT COUNT(*) total
    FROM posts p
    JOIN users u ON u.user_id = p.user_id
    WHERE (u.username LIKE ? OR p.content_text LIKE ?)
  ";
    $cstmt = $conn->prepare($countSql);
    if (!$cstmt) {
        echo json_encode(['error' => 'Count prepare error: ' . $conn->error]);
        exit;
    }
    $cstmt->bind_param('ss', $needle, $needle);
    $cstmt->execute();
    $total = (int)$cstmt->get_result()->fetch_assoc()['total'];
    $cstmt->close();
}

foreach ($rows as &$r) {
    $pid = (int)$r['post_id'];
    $r['status'] = isset($flagsMap[$pid]) && $flagsMap[$pid] > 0 ? 'Flagged' : 'Published';
}
unset($r);

echo json_encode([
    'items' => $rows,
    'has_more' => ($offset + count($rows)) < $total,
    'next_offset' => $offset + count($rows),
    'total' => $total
]);
