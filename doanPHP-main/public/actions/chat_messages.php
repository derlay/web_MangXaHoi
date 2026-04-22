<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user = getCurrentUser($conn);
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'auth']);
    exit;
}

$otherId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?: 0;
$since   = filter_input(INPUT_GET, 'since_id', FILTER_VALIDATE_INT) ?: 0;
if ($otherId <= 0) {
    echo json_encode(['success' => true, 'items' => []]);
    exit;
}

// Đổi tên bảng/cột nếu cần
$sql = "SELECT message_id, sender_id, receiver_id, content_text, media_url, created_at
        FROM messages
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
          AND (? = 0 OR message_id > ?)
        ORDER BY message_id ASC
        LIMIT 100";

try {
    if ($conn instanceof mysqli) {
        $st = $conn->prepare($sql);
        $sid = (int)$user['user_id'];
        $rid = $otherId;
        $st->bind_param('iiiiii', $sid, $rid, $rid, $sid, $since, $since);
        $st->execute();
        $res = $st->get_result();
        $items = [];
        while ($row = $res->fetch_assoc()) {
            $items[] = $row + ['sender_avatar_url' => null];
        }
        $st->close();
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    } else {
        $st = $conn->prepare($sql);
        $sid = (int)$user['user_id'];
        $rid = $otherId;
        $st->execute([$sid, $rid, $rid, $sid, $since, $since]);
        $items = $st->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }
} catch (Throwable $e) {
    error_log('[chat_messages] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'db']);
    exit;
}