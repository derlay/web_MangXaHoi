<?php
// app/views/home/post_item.php
$post = $post ?? [];

// author info
$authorId = (int)($post['user_id'] ?? 0);
$authorName = $post['full_name'] ?? ($post['username'] ?? 'User');
$authorAvatar = $post['profile_picture_public_id'] ?? $post['author_avatar_url'] ?? ($post['profile_picture_url'] ?? '/public/img/default_avatar.jpg');

// controller thường đã set author_avatar_url là URL đầy đủ
if (strpos((string)$authorAvatar, 'http') !== 0) {
    $authorAvatar = $post['author_avatar_url'] ?? '/public/img/default_avatar.jpg';
}

// post fields
$postId = (int)($post['post_id'] ?? 0);
$contentText = $post['content_text'] ?? '';
$createdAt = $post['created_at'] ?? null;
$mediaSrc = $post['media_url'] ?? null;
$mediaType = $post['media_type'] ?? null;
$likeCount = (int)($post['like_count'] ?? 0);
$commentCount = (int)($post['comment_count'] ?? 0);
$liked = !empty($post['liked']);
?>
<article class="post" id="post-<?= $postId ?>" data-author-id="<?= $authorId ?>">
    <header class="post-header">
        <!-- Avatar + Tên: chỉ hiển thị, KHÔNG còn là link -->
        <div class="post-avatar"
            style="background-image:url('<?= htmlspecialchars($authorAvatar) ?>'); width:40px; height:40px; border-radius:999px;">
        </div>

        <div class="post-meta">
            <div class="post-author"><?= htmlspecialchars($authorName) ?></div>
            <div class="post-time"><?= $createdAt ? htmlspecialchars(date('d/m/Y H:i', strtotime($createdAt))) : '' ?>
            </div>
        </div>

        <!-- Nút 3 chấm: JS sẽ mở menu -->
        <button class="post-more" type="button" aria-haspopup="true" aria-expanded="false"
            aria-label="Tùy chọn bài viết" title="Tùy chọn">⋯</button>
    </header>

    <div class="post-body">
        <?= nl2br(htmlspecialchars($contentText)) ?>
    </div>

    <?php if (!empty($mediaSrc)): ?>
    <div class="post-image">
        <img src="<?= htmlspecialchars($mediaSrc) ?>" alt="">
    </div>
    <?php endif; ?>

    <!-- Actions & Stats -->
    <div class="post__actions">
        <button class="post-action like-btn <?= $liked ? 'is-liked' : '' ?>" data-post-id="<?= $postId ?>">👍
            Like</button>
        <button class="post-action comment-toggle" data-post-id="<?= $postId ?>"><svg xmlns="http://www.w3.org/2000/svg"
                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-message-circle-icon lucide-message-circle">
                <path
                    d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719" />
            </svg>
            Comment</button>
        <button class="post-action share-btn" data-post-id="<?= $postId ?>"> <svg xmlns="http://www.w3.org/2000/svg"
                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share-icon lucide-share">
                <path d="M12 2v13" />
                <path d="m16 6-4-4-4 4" />
                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
            </svg>
            Share</button>
    </div>

    <div class="post__stats">
        <span class="likes-count" data-post-id="<?= $postId ?>"><?= $likeCount ?> likes</span>
        <span class="comments-count" data-post-id="<?= $postId ?>"><?= $commentCount ?> comments</span>
    </div>

    <div class="comment-box always-visible" style="margin-top:12px;">
        <input class="comment-input" data-post-id="<?= $postId ?>" type="text" placeholder="Write your comment...">
        <div class="comment-actions"><span>😊</span><span>📎</span></div>
    </div>

    <div class="post__comments" id="comments-<?= $postId ?>" style="display:none; margin-top:8px;">
        <div class="existing-comments" data-post-id="<?= $postId ?>"></div>
        <div class="comments-controls" style="margin-top:8px;">
            <button class="show-more-comments-btn" data-post-id="<?= $postId ?>" style="display:none;">Xem thêm</button>
        </div>
    </div>
</article>