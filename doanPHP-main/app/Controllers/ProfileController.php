<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cloudinary.php';
require_once __DIR__ . '/../helpers/cloudinary_helper.php';
session_start();

class ProfileController
{
    protected mysqli $conn;
    protected array $cloud;

    public function __construct()
    {
        global $conn;
        $this->conn  = $conn;
        $this->cloud = include __DIR__ . '/../config/cloudinary.php';
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
            session_start();
        }
    }

    private function fetchCurrentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) return null;
        $uid  = (int)$_SESSION['user_id'];
        $stmt = $this->conn->prepare("
            SELECT user_id, username, full_name, cover_photo_public_id, profile_picture_public_id, bio
            FROM users WHERE user_id = ? LIMIT 1
        ");
        if (!$stmt) return null;
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    private function json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

public function deletePostAjax(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->json(['ok' => false, 'error' => 'method_not_allowed'], 405);
    }

    $currentUser = $this->fetchCurrentUser();
    if (!$currentUser) {
        $this->json(['ok' => false, 'error' => 'unauthorized'], 401);
    }

    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId <= 0) {
        $this->json(['ok' => false, 'error' => 'invalid_post_id'], 422);
    }

    $viewerId = (int)$currentUser['user_id'];

    // Check ownership
    $st = $this->conn->prepare("SELECT user_id FROM posts WHERE post_id = ? LIMIT 1");
    if (!$st) {
        $this->json(['ok' => false, 'error' => 'db_prepare_failed'], 500);
    }
    $st->bind_param('i', $postId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc() ?: null;
    $st->close();

    if (!$row) {
        $this->json(['ok' => false, 'error' => 'post_not_found'], 404);
    }

    if ((int)$row['user_id'] !== $viewerId) {
        $this->json(['ok' => false, 'error' => 'forbidden'], 403);
    }

    // Delete dependent rows (if your DB doesn't use ON DELETE CASCADE)
    // 1) comment_likes -> depends on comments
    $this->conn->query("
        DELETE cl FROM comment_likes cl
        JOIN comments c ON c.comment_id = cl.comment_id
        WHERE c.post_id = {$postId}
    ");

    // 2) comments
    $stmt = $this->conn->prepare("DELETE FROM comments WHERE post_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $stmt->close();
    }

    // 3) post likes
    $stmt = $this->conn->prepare("DELETE FROM post_likes WHERE post_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $stmt->close();
    }



    // 5) delete post
    $stmt = $this->conn->prepare("DELETE FROM posts WHERE post_id = ? LIMIT 1");
    if (!$stmt) {
        $this->json(['ok' => false, 'error' => 'db_prepare_failed'], 500);
    }
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected <= 0) {
        $this->json(['ok' => false, 'error' => 'delete_failed'], 500);
    }

    $this->json(['ok' => true, 'post_id' => $postId]);
}
    public function show(?int $userId = null): void
    {
        $currentUser = $this->fetchCurrentUser();

        if (!$userId) {
            if (!$currentUser) {
                header('Location: /public/login.php');
                exit;
            }
            $userId = (int)$currentUser['user_id'];
        }

        // Lấy thông tin chủ profile
        $stmt = $this->conn->prepare("
            SELECT user_id, username, full_name, cover_photo_public_id, profile_picture_public_id, bio
            FROM users WHERE user_id = ? LIMIT 1
        ");
        if (!$stmt) {
            http_response_code(500);
            die('DB error: ' . $this->conn->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        // Cloud config
        $cloud = $this->cloud;
        $cloudName = $cloud['cloud_name'] ?? '';

        // Cover & avatar for profile owner
        $user['cover_url'] = cloudinary_cover(
            $cloudName,
            $user['cover_photo_public_id'] ?? null,
            [
                'default_public_id' => $cloud['default_cover_public_id'] ?? null,
                'version' => $cloud['default_cover_version'] ?? null,
                'w' => 1200,
                'h' => 380
            ]
        );
        $user['avatar_url'] = cloudinary_avatar(
            $cloudName,
            $user['profile_picture_public_id'] ?? null,
            [
                'default_public_id' => $cloud['default_avatar_public_id'] ?? null,
                'w' => 120,
                'h' => 120
            ]
        );

        // Posts của user (lấy tối đa 30)
        // NOTE: include u.user_id so partials can build the profile link.
        $pstmt = $this->conn->prepare("
            SELECT p.post_id, p.content_text, p.media_url, p.media_type, p.privacy, p.created_at,
                   u.user_id, u.full_name, u.profile_picture_public_id
            FROM posts p
            JOIN users u ON u.user_id = p.user_id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT 30
        ");
        $posts = [];
        if ($pstmt) {
            $pstmt->bind_param('i', $userId);
            $pstmt->execute();
            $posts = $pstmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $pstmt->close();
        }

        // Prepare statement để lấy comments (sẽ execute cho mỗi post)
        $commentsLimit = 5; // ban đầu hiển thị 5 bình luận
        $cstmt = $this->conn->prepare("
            SELECT c.comment_id, c.content, c.created_at,
                   u.full_name, u.username, u.profile_picture_public_id
            FROM comments c
            JOIN users u ON u.user_id = c.user_id
            WHERE c.post_id = ?
            ORDER BY c.created_at DESC
            LIMIT ?
        ");

        // Thống kê và avatar tác giả post, + tải 5 bình luận gần nhất cho mỗi post
        foreach ($posts as &$post) {
            $pid = (int)$post['post_id'];

            // counts (use prepared statements ideally; keep simple for now)
            $post['like_count']    = (int)($this->conn->query("SELECT COUNT(*) c FROM post_likes WHERE post_id=$pid")->fetch_assoc()['c'] ?? 0);
            $post['comment_count'] = (int)($this->conn->query("SELECT COUNT(*) c FROM comments WHERE post_id=$pid")->fetch_assoc()['c'] ?? 0);
            $post['share_count']   = (int)($this->conn->query("SELECT COUNT(*) c FROM shares WHERE post_id=$pid")->fetch_assoc()['c'] ?? 0);

            // viewer liked?
            $viewerLiked = false;
            if ($currentUser) {
                $viewerId  = (int)$currentUser['user_id'];
                $likedRes  = $this->conn->query("SELECT 1 FROM post_likes WHERE post_id=$pid AND user_id=$viewerId LIMIT 1");
                $viewerLiked = $likedRes && $likedRes->fetch_assoc();
            }
            $post['liked'] = (bool)$viewerLiked;

            // Make sure post has author user_id (we selected it in query)
            if (isset($post['user_id'])) {
                $post['user_id'] = (int)$post['user_id'];
            } else {
                // fallback: if not present, use profile owner id
                $post['user_id'] = $userId;
            }

            // author avatar for post (build url)
            $post['author_avatar_url'] = cloudinary_avatar(
                $cloudName,
                $post['profile_picture_public_id'] ?? null,
                ['default_public_id' => $cloud['default_avatar_public_id'] ?? null, 'w' => 40, 'h' => 40]
            );

            // fetch last N comments (most recent). We reverse to show oldest->newest among those N
            $postComments = [];
            if ($cstmt) {
                $cstmt->bind_param('ii', $pid, $commentsLimit);
                if ($cstmt->execute()) {
                    $cres = $cstmt->get_result();
                    $rows = $cres->fetch_all(MYSQLI_ASSOC);
                    // rows are ordered newest first (DESC), reverse to show older first
                    $rows = array_reverse($rows);
                    foreach ($rows as $r) {
                        $avatar = cloudinary_avatar(
                            $cloudName,
                            $r['profile_picture_public_id'] ?? null,
                            ['default_public_id' => $cloud['default_avatar_public_id'] ?? null, 'w' => 32, 'h' => 32]
                        );
                        $postComments[] = [
                            'id' => (int)$r['comment_id'],
                            'content' => $r['content'],
                            'created_at' => $r['created_at'],
                            'author_name' => $r['full_name'],
                            'username' => $r['username'],
                            'author_avatar_url' => $avatar
                        ];
                    }
                }
            }
            $post['comments'] = $postComments;
            $post['has_more_comments'] = ($post['comment_count'] > count($postComments));
        }
        unset($post);
        if ($cstmt) $cstmt->close();

        // Biến view
        $title               = "Profile - " . htmlspecialchars($user['username']);
        $cloudUnsignedPreset = $this->cloud['unsigned_preset'] ?? '';
        $cloudName           = $this->cloud['cloud_name'] ?? '';
        $ownProfile          = $currentUser && ((int)$currentUser['user_id'] === (int)$user['user_id']); // boolean
        $own                 = $ownProfile; // nếu view vẫn dùng $own

        // comments page size (JS sẽ dùng khi bấm "Xem thêm")
        $comments_page_size = $commentsLimit;

        // Gọi view
        // Ensure view path matches your project's case (you have used both Views / views earlier).
        // This controller expects the layout at app/views/profile/layout_profile.php
        $layoutPath = __DIR__ . '/../views/profile/layout_profile.php';
        if (!file_exists($layoutPath)) {
            // Try alternative capitalized path used elsewhere
            $alt = __DIR__ . '/../Views/profile/layout_profile.php';
            if (file_exists($alt)) {
                $layoutPath = $alt;
            } else {
                // helpful error if view missing
                http_response_code(500);
                echo "Profile view missing. Tried: " . htmlspecialchars($layoutPath) . " and " . htmlspecialchars($alt);
                error_log("[ProfileController] Missing layout_profile.php at expected locations.");
                return;
            }
        }

        include $layoutPath;
    }

    // edit profile
    public function edit(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location:/public/login.php');
            exit;
        }
        $uid = (int)$_SESSION['user_id'];
        $stmt = $this->conn->prepare("
          SELECT user_id, username, full_name, bio,
           profile_picture_public_id, cover_photo_public_id
          FROM users WHERE user_id=? LIMIT 1
        ");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $currentUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $photos = [];
        $res = $this->conn->query("
          SELECT media_url, media_type
          FROM posts
          WHERE user_id = {$uid} AND media_url IS NOT NULL AND media_url <> ''
          ORDER BY post_id DESC
          LIMIT 40
        ");
        if ($res) {
            $photos = $res->fetch_all(MYSQLI_ASSOC);
        }

        $cloud = $this->cloud;
        include __DIR__ . '/../Views/profile/edit_profile.php';
    }
}
