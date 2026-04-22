<?php
// app/Views/profile/show.php
// View for profile page. Safe-include the post partial if available.

$viewUser = $viewUser ?? [];
$posts = $posts ?? [];
$me = $me ?? null;
$friendStatus = $friendStatus ?? 'none';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($viewUser['full_name'] ?? $viewUser['username'] ?? 'Profile') ?></title>
  <link rel="stylesheet" href="/public/css/base.css">
  <link rel="stylesheet" href="/public/css/home.css">
  <link rel="stylesheet" href="/public/css/profile.css">
</head>
<body>
  <div class="profile-page" style="max-width:900px;margin:16px auto;">
    <div class="cover" style="height:200px;background-image:url('<?= htmlspecialchars($viewUser['cover_url'] ?? '/public/img/default_cover.jpg') ?>');background-size:cover;border-radius:8px;"></div>

    <div class="profile-card" style="background:#fff;padding:16px;border-radius:8px;margin-top:-40px;">
      <div style="display:flex;gap:12px;align-items:center">
        <div class="profile-avatar" style="width:96px;height:96px;border-radius:999px;background-image:url('<?= htmlspecialchars($viewUser['avatar_url'] ?? '/public/img/default_avatar.jpg') ?>');background-size:cover;border:4px solid #fff;"></div>
        <div style="flex:1">
          <h2 style="margin:0"><?= htmlspecialchars($viewUser['full_name'] ?? '') ?></h2>
          <div style="color:var(--text-muted)">@<?= htmlspecialchars($viewUser['username'] ?? '') ?></div>
          <div style="margin-top:8px;color:var(--text-muted)"><?= nl2br(htmlspecialchars($viewUser['bio'] ?? '')) ?></div>
        </div>

        <div>
          <?php if (($me['user_id'] ?? null) === ($viewUser['user_id'] ?? null)): ?>
            <button class="btn btn-outline" disabled>Đây là bạn</button>
          <?php else: ?>
            <?php if ($friendStatus === 'accepted'): ?>
              <button class="btn btn-primary friend-msg-btn" data-user-id="<?= (int)$viewUser['user_id'] ?>">Message</button>
            <?php elseif ($friendStatus === 'pending_received'): ?>
              <button class="btn btn-success friend-accept-btn" data-user-id="<?= (int)$viewUser['user_id'] ?>">Chấp nhận</button>
            <?php elseif ($friendStatus === 'pending_sent'): ?>
              <button class="btn friend-pending sent-check" disabled aria-label="Đã gửi"></button>
            <?php else: ?>
              <button class="btn btn-outline suggest-add-btn" data-user-id="<?= (int)$viewUser['user_id'] ?>">Kết bạn</button>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <section style="margin-top:16px;">
      <h3>Bài viết</h3>

      <?php
      // path to partial post item in Views/home
      $postPartial = __DIR__ . '/../home/post_item.php';
      if (empty($posts)) {
          echo '<div class="empty-state">Người này chưa có bài viết.</div>';
      } else {
          foreach ($posts as $post) {
              // make $post available for partial
              if (file_exists($postPartial)) {
                  // ensure variables the partial expects are available
                  // partial will use $post and $me/$currentUser if needed
                  include $postPartial;
              } else {
                  // fallback rendering if partial missing
                  ?>
                  <article class="post" id="post-fallback-<?= (int)($post['post_id'] ?? 0) ?>">
                      <header class="post-header">
                          <div class="post-avatar" style="background-image:url('<?= htmlspecialchars($post['author_avatar_url'] ?? '/public/img/default_avatar.jpg') ?>');"></div>
                          <div class="post-meta">
                              <div class="post-author"><?= htmlspecialchars($post['full_name'] ?? '') ?></div>
                              <div class="post-time"><?= !empty($post['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($post['created_at']))) : '' ?></div>
                          </div>
                      </header>
                      <div class="post-body"><?= nl2br(htmlspecialchars($post['content_text'] ?? '')) ?></div>
                  </article>
                  <?php
              }
          }
      }
      ?>
    </section>
  </div>
</body>
</html>