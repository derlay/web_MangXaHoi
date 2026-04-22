<?php
// app/views/chat/conversation.php
// Biến có sẵn: $currentUser, $otherUser

$cloudName = trim((string)($_ENV['CLOUDINARY_CLOUD_NAME'] ?? getenv('CLOUDINARY_CLOUD_NAME') ?: ''));

function avatar_url_for(?string $pid, ?string $absUrl, string $cloudName, int $w = 40, int $h = 40): string
{
    if (!empty($absUrl)) return $absUrl;
    if (!empty($pid) && $cloudName !== '') {
        $clean = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '', $pid);
        return "https://res.cloudinary.com/{$cloudName}/image/upload/w_{$w},h_{$h},c_fill,g_face,r_max/{$clean}.jpg";
    }
    return "/public/img/default_avatar.jpg";
}

$selfId   = (int)$currentUser['user_id'];
$otherId  = (int)$otherUser['user_id'];

$selfAvatar  = avatar_url_for($currentUser['profile_picture_public_id'] ?? null, $currentUser['profile_picture_url'] ?? null, $cloudName, 36, 36);
$otherAvatar = avatar_url_for($otherUser['profile_picture_public_id'] ?? null, $otherUser['profile_picture_url'] ?? null, $cloudName, 36, 36);
$otherName   = htmlspecialchars($otherUser['full_name'] ?? $otherUser['username'] ?? 'Người dùng');
?>

<div class="chat-page-container">
    <div class="chat-layout">
        <!-- SIDEBAR TRÁI (bạn đã có, giữ nguyên) -->
        <aside class="chat-sidebar">
            <div class="chat-sidebar-header">Tin nhắn</div>
            <div class="chat-sidebar-search">
                <input type="text" class="chat-sidebar-search-input friends-search-input" placeholder="Tìm bạn...">
            </div>
            <div class="chat-thread-list" id="chat-thread-list">
                <?php foreach ($friendsList as $f):
                    $displayName = $f['full_name'] ?: $f['username'] ?: ('User ' . $f['user_id']);
                    $avatarUrl = $f['profile_picture_url'] ?? '';
                ?>
                <div class="chat-thread-item <?= (int)$f['user_id'] === (int)$otherUser['user_id'] ? 'active' : '' ?>"
                    data-user-id="<?= (int)$f['user_id'] ?>">
                    <div class="chat-thread-avatar">
                        <?php if ($avatarUrl): ?>
                        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="">
                        <?php else: ?>
                        <div class="chat-thread-avatar-placeholder">
                            <?= strtoupper(substr($displayName, 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-thread-content">
                        <div class="chat-thread-name">
                            <?= htmlspecialchars($displayName) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- KHUNG CHAT GIỮA: BẮT BUỘC PHẢI CÓ -->
        <section class="chat-main">
            <div class="chat-panel">
                <div class="chat-header">
                    <div class="chat-header-left">
                        <div class="chat-header-avatar">
                            <?php $otherAvatar = $otherUser['avatar_url'] ?? ''; ?>
                            <?php if ($otherAvatar): ?>
                            <img src="<?= htmlspecialchars($otherAvatar) ?>" alt="">
                            <?php else: ?>
                            <div class="chat-header-avatar-placeholder">
                                <?= strtoupper(substr($otherUser['full_name'] ?? $otherUser['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="chat-header-text">
                            <div class="chat-header-name">
                                <?= htmlspecialchars($otherUser['full_name'] ?? $otherUser['username'] ?? 'Người dùng') ?>
                            </div>
                            <?php if (!empty($otherUser['username'])): ?>
                            <div class="chat-header-username">
                                @<?= htmlspecialchars($otherUser['username']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- PHẦN NÀY NẾU THIẾU THÌ KHÔNG THẤY LAYOUT NHẮN TIN -->
                <div class="chat-body">
                    <div class="chat-messages" id="chat-messages" data-other-id="<?= (int)$otherUser['user_id'] ?>">
                    </div>

                    <div class="chat-input-wrapper">
                        <div class="chat-input-inner">
                            <textarea class="chat-input-textarea" id="chat-input"
                                placeholder="Nhập tin nhắn..."></textarea>
                            <button class="chat-send-btn" id="chat-send-btn">Gửi</button>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>
</div>