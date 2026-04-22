<?php

declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Predis\Client as Predis;

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

try {
    if (!isset($conn)) throw new RuntimeException('DB conn not set');

    $currentUser = getCurrentUser($conn);
    if (!$currentUser) {
        echo json_encode(['success' => false, 'error' => 'auth']);
        exit;
    }

    $otherId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT)
        ?: filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT)
        ?: 0;

    $content = trim((string)($_POST['content_text'] ?? ''));
    $media   = trim((string)($_POST['media_url'] ?? ''));

    if ($otherId <= 0 || ($content === '' && $media === '')) {
        echo json_encode(['success' => false, 'error' => 'bad_input']);
        exit;
    }

    $sid = (int)$currentUser['user_id'];

    /* Tìm conversation 1-1 an toàn (không phụ thuộc COUNT(*)) */
    $sqlFind = "
    SELECT c.conversation_id
    FROM conversations c
    JOIN conversation_participants cp1
      ON cp1.conversation_id = c.conversation_id AND cp1.user_id = ?
    JOIN conversation_participants cp2
      ON cp2.conversation_id = c.conversation_id AND cp2.user_id = ?
    ORDER BY c.conversation_id DESC
    LIMIT 1
    ";
    $stf = $conn->prepare($sqlFind);
    if (!$stf) throw new RuntimeException('prepare_find_fail: ' . $conn->error);
    $stf->bind_param('ii', $sid, $otherId);
    $stf->execute();
    $rf    = $stf->get_result();
    $found = $rf ? $rf->fetch_assoc() : null;
    $stf->close();

    if ($found) {
        $conversationId = (int)$found['conversation_id'];
    } else {
        $conn->begin_transaction();

        $stCreate = $conn->prepare("INSERT INTO conversations(name, creator_id, created_at) VALUES (NULL, ?, NOW())");
        if (!$stCreate) {
            $conn->rollback();
            throw new RuntimeException('prepare_conv_fail: ' . $conn->error);
        }
        $stCreate->bind_param('i', $sid);
        if (!$stCreate->execute()) {
            $err = $stCreate->error;
            $stCreate->close();
            $conn->rollback();
            throw new RuntimeException('execute_conv_fail: ' . $err);
        }
        $conversationId = (int)$stCreate->insert_id;
        $stCreate->close();

        $stP = $conn->prepare("INSERT INTO conversation_participants(conversation_id, user_id, joined_at) VALUES (?, ?, NOW())");
        if (!$stP) {
            $conn->rollback();
            throw new RuntimeException('prepare_participants_fail: ' . $conn->error);
        }

        $stP->bind_param('ii', $conversationId, $sid);
        if (!$stP->execute()) {
            $err = $stP->error;
            $stP->close();
            $conn->rollback();
            throw new RuntimeException('execute_participant_me_fail: ' . $err);
        }

        $stP->bind_param('ii', $conversationId, $otherId);
        if (!$stP->execute()) {
            $err = $stP->error;
            $stP->close();
            $conn->rollback();
            throw new RuntimeException('execute_participant_other_fail: ' . $err);
        }
        $stP->close();

        $conn->commit();
    }

    /* Insert message */
    $conn->begin_transaction();
    $sqlMsg = "INSERT INTO messages (conversation_id, sender_id, content_text, media_url, sent_at)
               VALUES (?, ?, ?, ?, NOW())";
    $st = $conn->prepare($sqlMsg);
    if (!$st) {
        $conn->rollback();
        throw new RuntimeException('prepare_msg_fail: ' . $conn->error);
    }
    if (!$st->bind_param('iiss', $conversationId, $sid, $content, $media)) {
        $err = $st->error;
        $st->close();
        $conn->rollback();
        throw new RuntimeException('bind_fail: ' . $err);
    }
    if (!$st->execute()) {
        $err = $st->error;
        $st->close();
        $conn->rollback();
        throw new RuntimeException('execute_fail: ' . $err);
    }
    if ($st->affected_rows !== 1) {
        $st->close();
        $conn->rollback();
        throw new RuntimeException('no_rows_inserted');
    }
    $id = (int)$st->insert_id;
    $st->close();
    $conn->commit();

    /* Cloudinary avatar URLs */
    $cloudCfgPath = __DIR__ . '/../../app/config/cloudinary.php';
    $helperPath   = __DIR__ . '/../../app/helpers/cloudinary_helper.php';
    $useCloud     = is_file($cloudCfgPath) && is_file($helperPath);
    $senderAvatarUrl = null;
    $otherAvatarUrl  = null;

    if ($useCloud) {
        $cloud = include $cloudCfgPath;
        require_once $helperPath;

        $senderPpid = (string)($currentUser['profile_picture_public_id'] ?? '');
        $otherPpid  = '';
        $stPpid = $conn->prepare("SELECT profile_picture_public_id FROM users WHERE user_id = ?");
        if ($stPpid) {
            $stPpid->bind_param('i', $otherId);
            if ($stPpid->execute()) {
                $resP = $stPpid->get_result();
                if ($rowP = $resP->fetch_assoc()) {
                    $otherPpid = (string)($rowP['profile_picture_public_id'] ?? '');
                }
            }
            $stPpid->close();
        }

        $senderAvatarUrl = cloudinary_avatar(
            $cloud['cloud_name'] ?? '',
            $senderPpid !== '' ? $senderPpid : ($cloud['default_avatar_public_id'] ?? null),
            ['default_public_id' => $cloud['default_avatar_public_id'] ?? null, 'w' => 28, 'h' => 28, 'format' => 'jpg']
        );
        $otherAvatarUrl = cloudinary_avatar(
            $cloud['cloud_name'] ?? '',
            $otherPpid !== '' ? $otherPpid : ($cloud['default_avatar_public_id'] ?? null),
            ['default_public_id' => $cloud['default_avatar_public_id'] ?? null, 'w' => 28, 'h' => 28, 'format' => 'jpg']
        );
    }

    /* Response cho máy gửi */
    $message = [
        'message_id'        => $id,
        'conversation_id'   => $conversationId,
        'sender_id'         => $sid,
        'content_text'      => $content,
        'media_url'         => $media,
        'sent_at'           => date('Y-m-d H:i:s'),
        'sender_avatar_url' => $senderAvatarUrl ?: null,
    ];

    /* Push Redis để WS broadcast */
    $redisHost  = getenv('REDIS_HOST') ?: '127.0.0.1';
    $redisPort  = (int)(getenv('REDIS_PORT') ?: 6379);
    $redisQueue = getenv('REDIS_QUEUE') ?: 'chat_queue';

    try {
        $redis = new Predis(['scheme' => 'tcp', 'host' => $redisHost, 'port' => $redisPort]);
        $payload = [
            'type' => 'message',
            'message' => [
                'id' => $id,
                'conversation_id' => $conversationId,
                'sender_id' => $sid,
                'other_id'  => (int)$otherId,
                'content_text' => $content,
                'media_url' => $media,
                'sent_at' => date('c'),
                'sender_avatar_url' => $senderAvatarUrl,
                'other_avatar_url'  => $otherAvatarUrl,
            ],
            'recipients' => [(int)$sid, (int)$otherId],
            'origin_sender_id' => (int)$sid,
        ];
        $redis->rpush($redisQueue, json_encode($payload));
    } catch (Throwable $e) {
        error_log('[send_message][redis] ' . $e->getMessage());
    }

    echo json_encode(['success' => true, 'message' => $message]);
    exit;
} catch (Throwable $e) {
    error_log('[send_message] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'db', 'detail' => $e->getMessage()]);
    exit;
}
