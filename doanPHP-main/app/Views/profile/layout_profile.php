<?php
// Fallback avatar cho thanh topbar (current user)
$topbarAvatar = $currentUser
    ? cloudinary_avatar($cloudName, $currentUser['profile_picture_public_id'] ?? null)
    : '/public/img/default_avatar.jpg';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="stylesheet" href="/public/asset/bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/public/css/base.css" />
    <link rel="stylesheet" href="/public/css/components.css" />
    <link rel="stylesheet" href="/public/css/profile.css" />
    <link rel="stylesheet" href="/public/css/post_actions.css">


    <link rel="stylesheet" href="/public/css/comments.css">
    <link rel="stylesheet" href="/public/css/comments_overlay.css">

    <script>
    window.CLOUDINARY_CLOUD_NAME = "<?= htmlspecialchars($cloud['cloud_name']) ?>";
    window.CLOUDINARY_UNSIGNED_PRESET = "<?= htmlspecialchars($cloud['unsigned_preset']) ?>";
    </script>
    <script src="/public/js/profile_post_delete.js?v=3" defer></script>
</head>

<body class="bg-app">

    <!-- Topbar -->
    <header class="fb-topbar">
        <div class="fb-topbar__inner">
            <a href="/public/index.php" class="fb-brand">NETI</a>
            <div class="fb-search">
                <input type="text" class="fb-search__input" placeholder="Search posts, friends..." />
            </div>
            <div class="fb-actions">
                <?php if ($currentUser): ?>
                <a class="fb-me" href="/public/profile.php">
                    <span class="fb-me__avatar"
                        style="background-image:url('<?= htmlspecialchars($topbarAvatar) ?>')"></span>
                    <span class="fb-me__name"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                </a>
                <?php else: ?>
                <a class="btn btn-sm btn-outline-primary" href="/public/login.php">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Cover/Profile head -->
    <section class="profile-cover has-cover">
        <div class="profile-cover__img" style="background-image:url('<?= htmlspecialchars($user['cover_url']) ?>')">
        </div>
        <div class="profile-head container">
            <div class="profile-head__left">
                <div class="profile-head__avatar"
                    style="background-image:url('<?= htmlspecialchars($user['avatar_url']) ?>')"></div>
                <div class="profile-head__meta">
                    <h1 class="profile-name"><?= htmlspecialchars($user['full_name']) ?></h1>
                    <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
                </div>
            </div>
            <div class="profile-head__actions">
                <?php if ($own): ?>
                <a href="/public/profile_edit.php" class="btn btn-light btn-sm">Edit profile</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <main class="container fb-main">
        <!-- Left column -->
        <aside class="fb-left sticky">
            <div class="card fb-card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Intro</h6>
                    <?php if (!empty($user['bio'])): ?>
                    <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                    <?php else: ?>
                    <p class="text-muted mb-2">Chưa có giới thiệu.</p>
                    <?php endif; ?>
                    <ul class="list-unstyled small text-muted mb-0">
                        <!-- <li>User ID: <?= (int)$user['user_id'] ?></li>
            <li>Username: @<?= htmlspecialchars($user['username']) ?></li> -->
                    </ul>
                </div>
            </div>

            <div class="card fb-card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Photos</h6>
                    <div class="photos-grid">
                        <div class="photos-grid__empty text-muted">Chưa có ảnh</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Center column -->
        <section class="fb-center">
            <!-- Composer (chỉ hiện cho chủ profile) -->
            <?php if ($currentUser && $ownProfile): ?>
            <div class="card fb-card composer">
                <div class="composer__header">
                    <div class="composer__avatar"
                        style="background-image:url('<?= htmlspecialchars($topbarAvatar) ?>')"></div>
                    <input class="composer__input" type="text" placeholder="Bạn đang nghĩ gì?"
                        data-toggle="composer-open" />
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

            <!-- Danh sách bài viết (Lazy Loading) -->
            <!-- data-context="profile" để JS biết đây là feed của trang in4 -->
            <div id="posts-list" class="post-list" data-context="profile" data-user-id="<?= (int)$user['user_id'] ?>"
                data-initial-offset="<?= isset($posts) ? count($posts) : 0 ?>" data-limit="10">

                <!-- Nếu server đã render sẵn một số bài (SSR), vẫn giữ nguyên dưới đây.
             JS sẽ tiếp tục load tiếp từ data-initial-offset. -->
                <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $p): ?>
                <article class="card fb-card post" data-post-id="<?= (int)$p['post_id'] ?>">
                    <div class="post__header">
                        <div class="post__avatar"
                            style="background-image:url('<?= htmlspecialchars($p['author_avatar_url']) ?>')"></div>
                        <div class="post__meta">
                            <div class="post__author"><?= htmlspecialchars($p['full_name']) ?></div>
                            <div class="post__time">
                                <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?> ·
                                <?= htmlspecialchars($p['privacy']) ?>
                            </div>
                        </div>
                        <button  class="post__more post__more--profile" type="button"
  aria-haspopup="true"
  aria-expanded="false"
  data-post-id="<?= (int)$p['post_id'] ?>"
  data-can-delete="<?= $ownProfile ? '1' : '0' ?>">⋯</button>
                    </div>

                    <?php if (!empty($p['content_text'])): ?>
                    <div class="post__text"><?= nl2br(htmlspecialchars($p['content_text'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($p['media_url'])): ?>
                    <div class="post__media">
                        <?php if ($p['media_type'] === 'video'): ?>
                        <!-- Video lazy: dùng data-src, src gán khi vào viewport -->
                        <video class="lazy-video" controls playsinline preload="none"
                            data-src="<?= htmlspecialchars($p['media_url']) ?>"></video>
                        <?php else: ?>
                        <!-- Ảnh lazy: dùng loading="lazy" -->
                        <img class="lazy-img" loading="lazy" src="<?= htmlspecialchars($p['media_url']) ?>" alt="" />
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="post__actions">
                        <button class="post-action like-btn <?= $p['liked'] ? 'is-liked' : '' ?>" data-action="like"
                            data-post-id="<?= (int)$p['post_id'] ?>"><i data-lucide="thumbs-up" class="nav-icon"></i>
                            Like</button>
                        <button class="post-action comment-toggle" data-action="comment"
                            data-post-id="<?= (int)$p['post_id'] ?>"><i data-lucide="message-circle"
                                class="nav-icon"></i>
                            Comment</button>
                        <button class="post-action share-btn" data-action="share"
                            data-post-id="<?= (int)$p['post_id'] ?>"><i data-lucide="share" class="nav-icon"></i>
                            Share</button>
                    </div>

                    <div class="post__stats">
                        <span class="likes-count" data-post-id="<?= (int)$p['post_id'] ?>"><?= (int)$p['like_count'] ?>
                            likes</span>
                        <span class="comments-count"
                            data-post-id="<?= (int)$p['post_id'] ?>"><?= (int)$p['comment_count'] ?> comments</span>
                        <span class="shares-count"
                            data-post-id="<?= (int)$p['post_id'] ?>"><?= (int)$p['share_count'] ?> shares</span>
                    </div>

                    <!-- Ẩn khối comment cũ để dùng overlay (tránh trùng) -->
                    <div class="post__comments" id="comments-<?= (int)$p['post_id'] ?>" style="display:none;"></div>
                </article>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="card fb-card" style="padding:16px;">Chưa có bài viết.</div>
                <?php endif; ?>
            </div>

            <!-- Sentinel cho lazy (nếu chưa có, feed_infinite.js sẽ tự tạo) -->
            <div id="postsSentinel" class="sentinel" aria-hidden="true"></div>
        </section>
    </main>

    <!-- Overlay bình luận dùng chung cho in4 -->
    <div id="in4-cmt-ov" class="in4-cmt-ov" aria-hidden="true" style="display:none;">
        <div class="in4-cmt__backdrop"></div>
        <div class="in4-cmt__panel" role="dialog" aria-modal="true" aria-labelledby="in4-cmt-title">
            <div class="in4-cmt__head">
                <div id="in4-cmt-header" class="in4-cmt__posthead">
                    <h3 id="in4-cmt-title" class="in4-cmt__title">Bình luận</h3>
                </div>
                <button id="in4-cmt-close" class="in4-cmt__close" type="button" aria-label="Đóng">✕</button>
            </div>
            <div id="in4-cmt-body" class="in4-cmt__body">
                <div id="in4-cmt-list" class="in4-cmt__list"></div>
                <div id="in4-cmt-sentinel" class="in4-cmt__sentinel"></div>
            </div>
            <form id="in4-cmt-form" class="in4-cmt__form">
                <input id="in4-cmt-input" class="in4-cmt__input" placeholder="Viết bình luận..." maxlength="500" />
                <button class="in4-cmt__send" type="submit">Gửi</button>
            </form>
        </div>
    </div>

    <!-- Cloudinary & scripts -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons({
            attrs: {
                class: 'nav-icon',
                stroke: 'currentColor',
                'stroke-width': 1.8
            }
        });
    });
    </script>
    <script>
    window.CLOUDINARY_CLOUD_NAME = "<?= htmlspecialchars($cloudName) ?>";
    window.CLOUDINARY_UNSIGNED_PRESET = "<?= htmlspecialchars($cloudUnsignedPreset) ?>";
    </script>
    <script src="https://widget.cloudinary.com/v2.0/global/all.js"></script>
    <script src="/public/js/cloudinary_upload.js"></script>
    <script src="/public/js/profile_actions.js"></script>

    <script src="/public/js/feed_infinite.js" defer></script>
    <script src="/public/js/comments_overlay.js" defer></script>
</body>

</html>