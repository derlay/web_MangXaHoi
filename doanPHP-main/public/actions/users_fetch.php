<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../app/config/database.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
// if (($_SESSION['role'] ?? 'User') !== 'Admin') { echo json_encode(['error'=>'Forbidden']); exit; }

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = max(1, (int)($_GET['limit'] ?? 10));
$limit  = $limit > 50 ? 50 : $limit;
$q      = trim($_GET['q'] ?? '');

$needle = "%{$q}%";

/**
 * Base FROM cho cả 2 câu
 */
$sqlFrom = "
  FROM users u
  LEFT JOIN user_roles ur ON ur.user_id = u.user_id
  LEFT JOIN roles r ON r.role_id = ur.role_id
";

/**
 * 1. Lấy dữ liệu batch
 */
$rows = [];
if ($q === '') {
    // Không search: câu lệnh không có placeholder
    $sql = "
      SELECT u.user_id, u.username, u.full_name, u.email, u.created_at,
             GROUP_CONCAT(r.role_name ORDER BY r.role_name) AS roles
      $sqlFrom
      GROUP BY u.user_id
      ORDER BY u.created_at DESC
      LIMIT $limit OFFSET $offset
    ";
    $res = $conn->query($sql);
    if (!$res) {
        echo json_encode(['error' => 'DB error: ' . $conn->error]);
        exit;
    }
    $rows = $res->fetch_all(MYSQLI_ASSOC);
} else {
    // Có search: dùng prepared (3 LIKE)
    $sql = "
      SELECT u.user_id, u.username, u.full_name, u.email, u.created_at,
             GROUP_CONCAT(r.role_name ORDER BY r.role_name) AS roles
      $sqlFrom
      WHERE (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)
      GROUP BY u.user_id
      ORDER BY u.created_at DESC
      LIMIT $limit OFFSET $offset
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Prepare error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('sss', $needle, $needle, $needle);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/**
 * 2. Lấy tổng để xác định has_more
 */
if ($q === '') {
    $countSql = "
      SELECT COUNT(DISTINCT u.user_id) AS total
      $sqlFrom
    ";
    $cres = $conn->query($countSql);
    if (!$cres) {
        echo json_encode(['error' => 'Count error: ' . $conn->error]);
        exit;
    }
    $total = (int)$cres->fetch_assoc()['total'];
} else {
    $countSql = "
      SELECT COUNT(DISTINCT u.user_id) AS total
      $sqlFrom
      WHERE (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)
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

/**
 * 3. Tập hợp Admin IDs
 */
$admins = [];
$ares = $conn->query("
  SELECT ur.user_id
  FROM user_roles ur
  JOIN roles r ON r.role_id = ur.role_id
  WHERE r.role_name='Admin'
");
if ($ares) {
    $admins = array_map(fn($r) => (int)$r['user_id'], $ares->fetch_all(MYSQLI_ASSOC));
}

/**
 * 4. Xây role hiệu lực + status
 */
foreach ($rows as &$row) {
    $roleList = array_filter(array_map('trim', explode(',', (string)$row['roles'])));
    $effective = 'User';
    if (in_array('Admin', $roleList, true)) $effective = 'Admin';
    elseif (in_array('Moderator', $roleList, true)) $effective = 'Moderator';
    $row['role'] = $effective;

    $uid = (int)$row['user_id'];
    $banned = false;
    if (!empty($admins)) {
        $ids = implode(',', array_map('intval', $admins));
        $bres = $conn->query("SELECT 1 FROM blocked_users WHERE blocked_id={$uid} AND blocker_id IN ({$ids}) LIMIT 1");
        $banned = $bres && $bres->fetch_assoc();
    }
    $row['status'] = $banned ? 'Banned' : 'Active';
}
unset($row);

echo json_encode([
    'items'      => $rows,
    'has_more'   => ($offset + count($rows)) < $total,
    'next_offset' => $offset + count($rows),
    'total'      => $total
]);
