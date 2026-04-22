<?php

/** @var array  $currentUser */
/** @var array  $otherUser */
/** @var string $title */
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="/public/asset/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/css/base.css">
    <link rel="stylesheet" href="/public/css/layout.css">
    <link rel="stylesheet" href="/public/css/components.css">
    <link rel="stylesheet" href="/public/css/home.css">
    <link rel="stylesheet" href="/public/css/profile.css">

    <link rel="stylesheet" href="/public/css/chat.css">
</head>

<body>
    <div class="app-shell">
        <div class="app-frame">
            <?php
            // Giống Home: include header
            $currentUserGlobal = $currentUser;
            include __DIR__ . '/../home/header.php';
            ?>

            <main class="layout">
                <div class="container-fluid py-3" style="flex:1;">
                    <?php
                    // Đây là view cũ: conversation.php
                    // Biến dùng bên trong: $currentUser, $otherUser, ...
                    include __DIR__ . '/conversation.php';
                    ?>
                </div>
            </main>
        </div>
    </div>

    <script src="/public/asset/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // API chạy theo cách B, server PHP ở gốc
        window.API_BASE = 'http://localhost:8000';
        window.CHAT_WS_URL = 'ws://localhost:8080'; // Ratchet
        window.CURRENT_USER_ID = <?= (int)$currentUser['user_id'] ?>;
    </script>
    <script src="/public/js/chat.js"></script>

</body>

</html>