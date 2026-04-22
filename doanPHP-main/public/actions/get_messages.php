<?php

declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$currentUser = getCurrentUser($conn);
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

if ($userId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'invalid_user_id']);
    exit;
}

$me = (int)$currentUser['user_id'];

/* Tìm conversation 1-1 an toàn kể cả khi bảng participants có dòng trùng */
$sqlConv = "
SELECT c.conversation_id
FROM conversations c
JOIN conversation_participants cp1
  ON cp1.conversation_id = c.conversation_id AND cp1.user_id = ?
JOIN conversation_participants cp2
  ON cp2.conversation_id = c.conversation_id AND cp2.user_id = ?
ORDER BY c.conversation_id DESC
LIMIT 1
";
$stc = $conn->prepare($sqlConv);
if (!$stc) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'db_prepare_failed_conv', 'detail' => $conn->error]);
    exit;
}
$stc->bind_param('ii', $me, $userId);
$stc->execute();
$rc   = $stc->get_result();
$conv = $rc ? $rc->fetch_assoc() : null;
$stc->close();

if (!$conv) {
    echo json_encode([
        'success'         => true,
        'messages'        => [],
        'total'           => 0,
        'conversation_id' => null,
    ]);
    exit;
}

$conversationId = (int)$conv['conversation_id'];

/* Lấy messages + public_id ảnh người gửi */
$sql = "
SELECT
  m.message_id,
  m.conversation_id,
  m.sender_id,
  m.content_text,
  m.media_url,
  m.sent_at,
  u.full_name AS sender_full_name,
  u.profile_picture_public_id AS sender_ppid
FROM messages m
JOIN users u ON u.user_id = m.sender_id
WHERE m.conversation_id = ?
ORDER BY m.sent_at ASC
LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'db_prepare_failed', 'detail' => $conn->error]);
    exit;
}
$stmt->bind_param('iii', $conversationId, $limit, $offset);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'db_execute_failed', 'detail' => $err]);
    exit;
}
$res = $stmt->get_result();
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}
$stmt->close();

/* Cloudinary avatar tuyệt đối */
$cloudCfgPath = __DIR__ . '/../../app/config/cloudinary.php';
$helperPath   = __DIR__ . '/../../app/helpers/cloudinary_helper.php';
$useCloud     = is_file($cloudCfgPath) && is_file($helperPath);
$cloudCfg     = $useCloud ? include $cloudCfgPath : [];
if ($useCloud) {
    require_once $helperPath;
}

foreach ($rows as &$r) {
    $r['sender_avatar_url'] = $useCloud
        ? cloudinary_avatar(
            $cloudCfg['cloud_name'] ?? '',
            !empty($r['sender_ppid']) ? $r['sender_ppid'] : ($cloudCfg['default_avatar_public_id'] ?? null),
            ['default_public_id' => $cloudCfg['default_avatar_public_id'] ?? null, 'w' => 28, 'h' => 28, 'format' => 'jpg']
        )
        : '';
}
unset($r);

echo json_encode([
    'success'         => true,
    'messages'        => $rows,
    'total'           => count($rows),
    'conversation_id' => $conversationId,
]);
