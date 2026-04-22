<?php

$title = $title ?? 'NETI';

$cloudName           = $cloudName           ?? ($cloud['cloud_name'] ?? '');
$cloudUnsignedPreset = $cloudUnsignedPreset ?? ($cloud['unsigned_preset'] ?? '');
$apiBase             = $apiBase             ?? '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/public/asset/bootstrap/css/bootstrap.min.css">

    <!-- CSS UI -->
    <link rel="stylesheet" href="/public/css/base.css">
    <link rel="stylesheet" href="/public/css/layout.css">
    <link rel="stylesheet" href="/public/css/components.css">
    <link rel="stylesheet" href="/public/css/home.css">
    <link rel="stylesheet" href="/public/css/profile.css">

    <link rel="stylesheet" href="/public/css/post_actions.css">
    <link rel="stylesheet" href="/public/css/comments_overlay.css">

    <script>
        window.CLOUDINARY_CLOUD_NAME = "<?= htmlspecialchars($cloudName) ?>";
        window.CLOUDINARY_UNSIGNED_PRESET = "<?= htmlspecialchars($cloudUnsignedPreset) ?>";
        window.API_BASE = "<?= htmlspecialchars($apiBase) ?>";
    </script>

</head>

<body>
    <div class="app-shell">
        <div class="app-frame">

            <?php include __DIR__ . '/header.php'; ?>
            <div id="notif-popup" class="notif-popup" aria-hidden="true">
                <div class="notif-popup__header">
                    <span>Thông báo</span>
                </div>
                <div class="notif-popup__body">
                    <?php if (!empty($pendingIncoming)): ?>
                        <?php foreach ($pendingIncoming as $u): ?>
                            <div class="notif-item">
                                <div class="notif-avatar"
                                    style="background-image:url('<?= htmlspecialchars($u['profile_picture_url'] ?? '/public/img/default_avatar.jpg') ?>');">
                                </div>
                                <div class="notif-main">
                                    <div class="notif-text">
                                        <strong><?= htmlspecialchars($u['full_name'] ?? $u['username'] ?? 'User') ?></strong>
                                        đã gửi cho bạn lời mời kết bạn.
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-empty">Không có lời mời kết bạn mới.</div>
                    <?php endif; ?>

                    <?php if (!empty($friendsList)): ?>
                        <hr class="notif-separator" />
                        <?php foreach (array_slice($friendsList, 0, 5) as $u): ?>
                            <div class="notif-item">
                                <div class="notif-avatar"
                                    style="background-image:url('<?= htmlspecialchars($u['profile_picture_url'] ?? '/public/img/default_avatar.jpg') ?>');">
                                </div>
                                <div class="notif-main">
                                    <div class="notif-text">
                                        Bạn và
                                        <strong><?= htmlspecialchars($u['full_name'] ?? $u['username'] ?? 'User') ?></strong>
                                        đã trở thành bạn bè.
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <main class="layout">
                <?php include __DIR__ . '/sidebar_left.php'; ?>

                <?php
                // phần cột giữa: feed/trang chủ
                include __DIR__ . '/index.php';
                ?>

                <?php include __DIR__ . '/sidebar_right.php'; ?>
            </main>
        </div>



        <main class="layout">
            <?php include __DIR__ . '/sidebar_left.php'; ?>

            <?php
            // phần cột giữa: feed/trang chủ
            include __DIR__ . '/index.php';
            ?>

            <?php include __DIR__ . '/sidebar_right.php'; ?>
        </main>
    </div>
    </div>

    <div id="cmt-ov" style="display:none;"></div>

    <!-- Scripts -->
    <script src="/public/asset/bootstrap/js/bootstrap.bundle.min.js"></script>
    </div>
    </div>

    <div id="cmt-ov" style="display:none;"></div>

    <!-- Scripts -->
    <script src="/public/asset/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Cloudinary widget  -->
    <script src="https://widget.cloudinary.com/v2.0/global/all.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="/public/js/cloudinary_upload.js"></script>

    <script src="/public/js/profile_actions.js"></script>

    <script src="/public/js/feed_infinite.js" defer></script>
    <script src="/public/js/comments_overlay.js" defer></script>
    <script src="/public/js/like_actions.js" defer></script>
    <!-- KHÔNG dùng feed_actions.js nếu đã dùng overlay bình luận để tránh xung đột -->
    <script src="/public/js/chat.js"></script>
    <script src="/public/js/friends.js"></script>
    <script src="/public/js/feed_actions.js"></script>
    <script src="/public/js/post_menu.js" defer></script>
    <script src="/public/js/notif_bell.js" defer></script>


    <script>
        // Khởi tạo icon Lucide lần đầu và khi feed thêm bài mới
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons({
                    attrs: {
                        class: 'lucide',
                        stroke: 'currentColor',
                        'stroke-width': 1.8
                    }
                });
            }
            const postsList = document.getElementById('posts-list');
            if (postsList && window.MutationObserver && window.lucide) {
                const mo = new MutationObserver(() => {
                    lucide.createIcons({
                        attrs: {
                            class: 'lucide',
                            stroke: 'currentColor',
                            'stroke-width': 1.8
                        }
                    });
                });
                mo.observe(postsList, {
                    childList: true,
                    subtree: true
                });
            }
        });
    </script>
</body>

</html>