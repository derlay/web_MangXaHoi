<?php
// Views/home/header.php
$topbarAvatar = !empty($currentUser['avatar_url'])
    ? $currentUser['avatar_url']
    : '';
?>
<header class="top-bar">
    <div class="top-left">
        <?php if (!empty($currentUser)): ?>
            <a class="fb-me" href="/public/profile.php">
                <span class="fb-me__avatar" style="background-image:url('<?= htmlspecialchars($topbarAvatar) ?>')"></span>
                <span class="fb-me__name"><?= htmlspecialchars($currentUser['full_name']) ?></span>
            </a>
        <?php else: ?>
            <a href="/public/login.php" class="btn-ghost" style="font-size:14px;">Đăng nhập</a>
        <?php endif; ?>
    </div>

    <div class="search-wrapper">
        <input class="search-input" type="text" placeholder="Search for friends, groups, pages" aria-label="Search">
        <!-- Kết quả search (friends.js sẽ fill vào đây) -->
        <div class="search-results"></div>
    </div>

    <div class="top-right">
        <!-- Chuông thông báo -->
        <button id="notif-bell" type="button" class="notif-bell" aria-haspopup="true" aria-expanded="false"
            title="Thông báo">
            🔔
            <?php if (!empty($pendingIncoming)): ?>
                <span class="notif-badge"><?= count($pendingIncoming) ?></span>
            <?php endif; ?>
        </button>

    </div>
</header>