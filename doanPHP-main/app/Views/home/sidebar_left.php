<?php
// Khởi tạo biến để tránh lỗi undefined
$friendsList = $friendsList ?? [];
$pendingIncoming = $pendingIncoming ?? [];
$pendingOutgoing = $pendingOutgoing ?? [];
$friendSuggestions = $friendSuggestions ?? [];
$events = $events ?? [];
$currentUser = $currentUser ?? null;

// Build avatar hiện tại an toàn
$meAvatar = '/public/img/default_avatar.jpg';
if (is_array($currentUser)) {
    if (!empty($currentUser['avatar_url'])) {
        $meAvatar = (string)$currentUser['avatar_url'];
    } elseif (!empty($currentUser['profile_picture_url'])) {
        $meAvatar = (string)$currentUser['profile_picture_url'];
    } elseif (!empty($currentUser['profile_picture_public_id']) && !empty($cloudName) && function_exists('cloudinary_avatar')) {
        // fallback: tự build từ public_id nếu view có $cloudName + helper được include
        $meAvatar = cloudinary_avatar($cloudName, $currentUser['profile_picture_public_id']);
    }
}
?>

<aside class="left-sidebar">


    <div>
        <div class="menu-section-title" style="display:flex; justify-content:space-between; align-items:center;">
            <span>Gợi ý kết bạn</span>
            <a href="#" style="font-size:12px; text-decoration:none;">Xem tất cả</a>
        </div>

        <?php if (!empty($friendSuggestions)): ?>
            <ul class="nav-list" style="gap:8px;">
                <?php foreach ((array)$friendSuggestions as $u): ?>
                    <li class="nav-item" style="align-items:center; justify-content: space-between;">
                        <div style="display:flex; gap:10px; align-items:center; flex:1; overflow: hidden;">
                            <div class="avatar-xs"
                                style="background-image:url('<?= htmlspecialchars($u['profile_picture_url'] ?? '/public/img/default_avatar.jpg') ?>'); flex-shrink:0;">
                            </div>
                            <div class="nav-label" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($u['full_name'] ?? $u['username'] ?? 'User') ?>
                            </div>
                        </div>
                        <button class="suggest-add-btn btn btn-sm btn-outline-primary"
                            style="padding: 0px 6px; line-height: 1.2;" type="button"
                            data-user-id="<?= (int)($u['user_id'] ?? 0) ?>">+</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="small text-muted" style="padding-left: 10px;">Không có gợi ý.</div>
        <?php endif; ?>
    </div>

    <hr style="border-top: 1px solid #eee; margin: 15px 0;">

    <div>
        <div class="menu-section-title">Bạn bè</div>
        <?php if (!empty($friendsList)): ?>
            <ul class="nav-list" style="gap:8px;">
                <?php foreach ($friendsList as $u): ?>
                    <li class="nav-item" style="align-items:center; justify-content: space-between;">
                        <a href="/public/profile.php?user_id=<?= (int)$u['user_id'] ?>"
                            style="display:flex; gap:10px; align-items:center; flex:1; text-decoration:none; color:inherit; overflow: hidden;">
                            <div class="avatar-xs"
                                style="background-image:url('<?= htmlspecialchars($u['profile_picture_url'] ?? '/public/img/default_avatar.jpg') ?>'); flex-shrink:0;">
                            </div>
                            <div class="nav-label" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($u['full_name'] ?? $u['username'] ?? 'User') ?>
                            </div>
                        </a>
                        <button class="friend-msg-btn btn btn-sm btn-light" style="font-size: 10px; padding: 2px 5px;"
                            type="button" data-user-id="<?= (int)($u['user_id'] ?? 0) ?>">
                            Msg
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="small text-muted" style="padding-left: 10px;">Chưa có bạn bè.</div>
        <?php endif; ?>
    </div>

    <hr style="border-top: 1px solid #eee; margin: 15px 0;">



    <div class="left-profile" style="margin-top:auto; padding-top: 20px;">
        <a href="/public/profile.php"
            style="display:flex; align-items:center; gap: 10px; margin-bottom: 10px; text-decoration:none; color:inherit;">
            <div class="avatar-xs"
                style="background-image:url('<?= htmlspecialchars($meAvatar) ?>'); width: 40px; height: 40px;"></div>
            <div class="profile-info" style="overflow: hidden;">
                <div class="profile-name"
                    style="font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username'] ?? 'Your Name') ?>
                </div>
                <div class="profile-role" style="font-size: 12px; color: #666;">Basic Member</div>
            </div>
        </a>

        <div class="logout-box">
            <a class="btn btn-red" href="/public/logout.php" style="width:100%; display:block; text-align:center;">Đăng
                xuất</a>
        </div>
    </div>

</aside>