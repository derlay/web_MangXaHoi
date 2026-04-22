<?php
// app/Helpers/auth.php

function getCurrentUser(mysqli $conn, bool $allowSessionFallback = true, bool $debug = false): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    if ($debug) {
        error_log('[getCurrentUser] SESSION=' . json_encode($_SESSION));
    }

    if (empty($_SESSION['user_id'])) {
        if ($debug) error_log('[getCurrentUser] Không có user_id trong session');
        return null;
    }

    $uid = (int)$_SESSION['user_id'];

    // Lấy các cột chắc chắn có trong DB
    $sql = "SELECT user_id, username, full_name,
               profile_picture_public_id, cover_photo_public_id, bio
        FROM users WHERE user_id=? LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        if ($debug) error_log('[getCurrentUser] Prepare failed: ' . $conn->error);
        return $allowSessionFallback ? sessionUserFallback() : null;
    }

    $stmt->bind_param('i', $uid);
    if (!$stmt->execute()) {
        if ($debug) error_log('[getCurrentUser] Execute failed: ' . $stmt->error);
        $stmt->close();
        return $allowSessionFallback ? sessionUserFallback() : null;
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        if ($debug) error_log('[getCurrentUser] Không tìm thấy user_id=' . $uid . ' trong DB');
        return $allowSessionFallback ? sessionUserFallback() : null;
    }

    return $row;
}

function sessionUserFallback(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_id'])) return null;
    return [
        'user_id'   => (int)$_SESSION['user_id'],
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? ($_SESSION['user_name'] ?? ''),
        // profile_picture_public_id có thể không có trong session — bỏ qua
    ];
}
