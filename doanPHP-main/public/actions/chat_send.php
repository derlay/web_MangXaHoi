<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user = getCurrentUser($conn);
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'auth']);
    exit;
}

// Nhận cả 2 key để tương thích frontend
$otherId = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
if (!$otherId) {
    $otherId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
}
$otherId = $otherId ?: 0;

$content = trim((string)($_POST['content_text'] ?? ''));
$media   = trim((string)($_POST['media_url'] ?? ''));

// validate input
if ($otherId <= 0 || ($content === '' && $media === '')) {
    echo json_encode(['success' => false, 'error' => 'bad_input']);
    exit;
}

$sql = "INSERT INTO messages (sender_id, receiver_id, content_text, media_url, created_at)
        VALUES (?, ?, ?, ?, NOW())";

try {
    $sid = (int)$user['user_id'];
    $rid = $otherId;

    // Nhánh mysqli
    if ($conn instanceof mysqli) {
        $st = $conn->prepare($sql);
        if (!$st) {
            echo json_encode(['success' => false, 'error' => 'prepare_fail']);
            exit;
        }

        // content/media có thể rỗng -> truyền chuỗi rỗng OK
        $st->bind_param('iiss', $sid, $rid, $content, $media);

        if (!$st->execute()) {
            $err = $st->error;
            $st->close();
            error_log('[chat_send] execute fail: ' . $err);
            echo json_encode(['success' => false, 'error' => 'execute_fail']);
            exit;
        }

        $id = $st->insert_id; // insert_id của statement trong mysqli
        $st->close();

        // Trả về message đầy đủ cho client
        $message = [
            'message_id'        => (int)$id,
            'sender_id'         => $sid,
            'receiver_id'       => $rid,
            'content_text'      => $content,
            'media_url'         => $media,
            'created_at'        => date('Y-m-d H:i:s'),
            // tuỳ bạn bổ sung nếu cần avatar
            'sender_avatar_url' => $user['avatar_url'] ?? null,
        ];

        echo json_encode(['success' => true, 'message' => $message]);
        exit;
    } else {
        // Nhánh PDO (nếu $conn là PDO)
        $st = $conn->prepare($sql);
        if (!$st) {
            echo json_encode(['success' => false, 'error' => 'prepare_fail']);
            exit;
        }
        $ok = $st->execute([$sid, $rid, $content, $media]);
        if (!$ok) {
            error_log('[chat_send] PDO execute fail');
            echo json_encode(['success' => false, 'error' => 'execute_fail']);
            exit;
        }
        // Lấy last insert id từ chính $conn (PDO)
        $id = (int)$conn->lastInsertId();

        $message = [
            'message_id'        => $id,
            'sender_id'         => $sid,
            'receiver_id'       => $rid,
            'content_text'      => $content,
            'media_url'         => $media,
            'created_at'        => date('Y-m-d H:i:s'),
            'sender_avatar_url' => $user['avatar_url'] ?? null,
        ];

        echo json_encode(['success' => true, 'message' => $message]);
        exit;
    }
} catch (Throwable $e) {
    error_log('[chat_send] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'db']);
    exit;
}