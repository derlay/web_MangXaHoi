<?php
// Home center column view
// Mục tiêu: luôn lấy được CLOUDINARY_CLOUD_NAME từ biến view hoặc ENV và build avatar URL an toàn.

// Lấy cloud name: ưu tiên biến truyền vào view, nếu rỗng thì lấy từ ENV/$_ENV
$cloudName = isset($cloudName) && trim((string)$cloudName) !== ''
    ? trim((string)$cloudName)
    : (trim((string)($_ENV['CLOUDINARY_CLOUD_NAME'] ?? '')) ?: trim((string)getenv('CLOUDINARY_CLOUD_NAME') ?: ''));

// Public ID avatar mặc định từ Cloudinary (ENV)
$defaultAvatarPid = trim((string)(
    $_ENV['CLOUDINARY_DEFAULT_AVATAR_PUBLIC_ID'] ??
    getenv('CLOUDINARY_DEFAULT_AVATAR_PUBLIC_ID') ?: ''
));

function build_avatar_url(?string $cloudName, ?string $publicId, ?string $fallbackPid): string
{
    $cloudName = trim((string)$cloudName);
    $pid = trim((string)($publicId ?: $fallbackPid));
    $pid = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '', $pid);

    if ($cloudName === '' || $pid === '') {
        return '/public/img/default_avatar.jpg';
    }
    return "https://res.cloudinary.com/{$cloudName}/image/upload/w_40,h_40,c_fill,g_face,r_max/{$pid}.jpg";
}

function render_post_card(array $p, string $cloudName, string $defaultAvatarPid): string
{
    $postId = (int)($p['post_id'] ?? 0);

    // NEW: author id for "View profile"
    $authorId = (int)($p['user_id'] ?? 0);
    $profileHref = $authorId > 0 ? ('/public/profile.php?user_id=' . $authorId) : '/public/profile.php';

    if (!empty($p['profile_picture_public_id'])) {
        $authorAvatarUrl = build_avatar_url($cloudName, $p['profile_picture_public_id'], $defaultAvatarPid);
    } elseif (!empty($p['profile_picture_url'])) {
        $authorAvatarUrl = (string)$p['profile_picture_url'];
    } else {
        $authorAvatarUrl = build_avatar_url($cloudName, null, $defaultAvatarPid);
    }
    $authorAvatarUrl = htmlspecialchars($authorAvatarUrl);

    $fullName     = htmlspecialchars($p['full_name'] ?? $p['username'] ?? 'Người dùng');
    $createdAt    = !empty($p['created_at']) ? date('d/m/Y H:i', strtotime($p['created_at'])) : '';
    $privacy      = htmlspecialchars($p['privacy'] ?? 'public');
    $textHtml     = !empty($p['content_text']) ? nl2br(htmlspecialchars($p['content_text'])) : '';
    $likedClass   = !empty($p['liked']) ? 'is-liked' : '';
    $likeCount    = (int)($p['like_count'] ?? 0);
    $commentCount = (int)($p['comment_count'] ?? 0);
    $shareCount   = (int)($p['share_count'] ?? 0);

    $mediaHtml = '';
    if (!empty($p['media_url'])) {
        $mediaUrl = htmlspecialchars($p['media_url']);
        if (($p['media_type'] ?? '') === 'video') {
            $mediaHtml = '<div class="post__media"><video controls playsinline preload="none" data-src="' . $mediaUrl . '" style="max-height:70vh;object-fit:contain;display:block;max-width:100%;"></video></div>';
        } else {
            $mediaHtml = '<div class="post__media"><img loading="lazy" src="' . $mediaUrl . '" alt="" style="max-height:70vh;object-fit:contain;display:block;max-width:100%;"></div>';
        }
    }

    return '
    <article class="card fb-card post"
             data-post-id="' . $postId . '"
             data-author-id="' . $authorId . '"
             id="post-' . $postId . '">
      <div class="post__header">
        <div class="post__avatar" style="background-image:url(\'' . $authorAvatarUrl . '\')"></div>
        <div class="post__meta">
          <div class="post__author">' . $fullName . '</div>
          <div class="post__time">' . $createdAt . ' · ' . $privacy . '</div>
        </div>

        <button class="post__more post__more--home"
                type="button"
                aria-label="More"
                aria-haspopup="true"
                aria-expanded="false"
                data-author-id="' . $authorId . '"
                data-profile-href="' . htmlspecialchars($profileHref) . '">⋯</button>
      </div>
      ' . ($textHtml ? '<div class="post__text">' . $textHtml . '</div>' : '') . '
      ' . $mediaHtml . '
      <div class="post__actions">
        <button class="post-action like-btn ' . $likedClass . '" data-post-id="' . $postId . '" type="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-thumbs-up-icon lucide-thumbs-up"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>
        Like</button>
        <button class="post-action comment-toggle" data-post-id="' . $postId . '" type="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle-icon lucide-message-circle"><path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/></svg>
        Comment</button>
        <button class="post-action share-btn" data-post-id="' . $postId . '" type="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share-icon lucide-share"><path d="M12 2v13"/><path d="m16 6-4-4-4 4"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/></svg>
        Share</button>
      </div>
      <div class="post__stats">
        <span class="likes-count" data-post-id="' . $postId . '">' . $likeCount . ' likes</span>
        <span class="comments-count" data-post-id="' . $postId . '">' . $commentCount . ' comments</span>
        <span class="shares-count" data-post-id="' . $postId . '">' . $shareCount . ' shares</span>
      </div>
      <div class="post__comments" id="comments-' . $postId . '" style="display:none;"></div>
    </article>';
}

// Avatar người dùng hiện tại
$meAvatar = !empty($currentUser)
    ? build_avatar_url($cloudName, $currentUser['profile_picture_public_id'] ?? null, $defaultAvatarPid)
    : build_avatar_url($cloudName, null, $defaultAvatarPid);

// Offset ban đầu của feed
$initialOffset = isset($posts) && is_array($posts) ? count($posts) : 0;
?>
<section class="fb-center">
    <?php if (!empty($currentUser)): ?>
        <div class="card fb-card composer">
            <div class="composer__header">
                <div class="composer__avatar" style="background-image:url('<?= htmlspecialchars($meAvatar) ?>')"></div>
                <input class="composer__input" type="text" placeholder="Bạn đang nghĩ gì?" />
            </div>
            <form id="create-post-form" class="composer__form">
                <textarea name="content_text" rows="3" class="form-control"
                    placeholder="Viết gì đó cho bạn bè..."></textarea>

                <!-- Input file ẩn -->
                <input type="file" id="post-media-file" accept="image/*,video/*,.gif" class="d-none">
                <div id="media-preview" class="composer__preview" style="display:none;"></div>

                <div class="composer__toolbar">
                    <button type="button" class="composer__tool" id="btn-select-media">Ảnh/Video</button>
                    <button type="button" class="composer__tool" id="btn-clear-media" style="display:none;">Xóa
                        media</button>

                    <select class="form-select form-select-sm w-auto" name="privacy">
                        <option value="public">Công khai</option>
                        <option value="friends" selected>Bạn bè</option>
                        <option value="private">Riêng tư</option>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm ms-auto">Đăng</button>
                </div>

                <!-- Hidden -->
                <input type="hidden" name="media_url" id="media_url" />
                <input type="hidden" name="media_type" id="media_type" />
                <input type="hidden" name="media_public_id" id="media_public_id" />
            </form>
        </div>
    <?php endif; ?>

    <div id="posts-list" class="post-list" data-context="home" data-initial-offset="<?= (int)$initialOffset ?>"
        data-limit="10">
        <?php if (!empty($posts) && is_array($posts)): ?>
            <?php foreach ($posts as $p): ?>
                <?= render_post_card($p, $cloudName, $defaultAvatarPid) ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card fb-card" style="padding:16px;">Chưa có bài viết.</div>
        <?php endif; ?>
    </div>

    <div id="postsSentinel" class="sentinel" aria-hidden="true" style="height:1px; pointer-events:none;"></div>
</section>