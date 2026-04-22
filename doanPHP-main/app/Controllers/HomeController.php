<?php
//HomeController.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cloudinary.php';
require_once __DIR__ . '/../helpers/cloudinary_helper.php';
require_once __DIR__ . '/../helpers/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

class HomeController
{
    protected mysqli $conn;
    protected array $cloud;

    public function __construct()
    {
        global $conn;
        $this->conn  = $conn;
        $this->cloud = include __DIR__ . '/../config/cloudinary.php';
    }

    private function buildAvatar(?string $publicId, int $w = 48, int $h = 48): string
    {
        return cloudinary_avatar(
            $this->cloud['cloud_name'],
            $publicId,
            [
                'default_public_id' => $this->cloud['default_avatar_public_id'] ?? null,
                'w' => $w,
                'h' => $h
            ]
        );
    }

    private function buildCover(?string $publicId, int $w = 1200, int $h = 380): string
    {
        return cloudinary_cover(
            $this->cloud['cloud_name'],
            $publicId,
            [
                'default_public_id' => $this->cloud['default_cover_public_id'] ?? null,
                'version'           => $this->cloud['default_cover_version'] ?? null,
                'w'                 => $w,
                'h'                 => $h
            ]
        );
    }

    // ---------- Helpers for AJAX ----------
    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    private function requireAuthId(): int
    {
        $u = getCurrentUser($this->conn);
        if (!$u) {
            $this->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }
        return (int)$u['user_id'];
    }

    private function getPostLikeCount(int $postId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS c FROM post_likes WHERE post_id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($res['c'] ?? 0);
    }

    private function getCommentLikeCount(int $commentId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS c FROM comment_likes WHERE comment_id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $commentId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($res['c'] ?? 0);
    }

    private function isPostLiked(int $userId, int $postId): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('ii', $userId, $postId);
        $stmt->execute();
        $liked = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $liked;
    }

    private function isCommentLiked(int $userId, int $commentId): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM comment_likes WHERE user_id = ? AND comment_id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('ii', $userId, $commentId);
        $stmt->execute();
        $liked = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $liked;
    }

    private function togglePostLikeAjax(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'method_not_allowed'], 405);
        }
        $userId = $this->requireAuthId();
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            $this->json(['ok' => false, 'error' => 'invalid_post_id'], 422);
        }

        $liked = $this->isPostLiked($userId, $postId);
        if ($liked) {
            $stmt = $this->conn->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?");
            if ($stmt) {
                $stmt->bind_param('ii', $userId, $postId);
                $stmt->execute();
                $stmt->close();
            }
            $liked = false;
        } else {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO post_likes(user_id, post_id, created_at) VALUES (?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('ii', $userId, $postId);
                $stmt->execute();
                $stmt->close();
            }
            $liked = true;
        }

        $count = $this->getPostLikeCount($postId);
        $this->json(['ok' => true, 'liked' => $liked, 'like_count' => $count]);
    }

    private function toggleCommentLikeAjax(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'method_not_allowed'], 405);
        }
        $userId = $this->requireAuthId();
        $commentId = (int)($_POST['comment_id'] ?? 0);
        if ($commentId <= 0) {
            $this->json(['ok' => false, 'error' => 'invalid_comment_id'], 422);
        }

        $liked = $this->isCommentLiked($userId, $commentId);
        if ($liked) {
            $stmt = $this->conn->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
            if ($stmt) {
                $stmt->bind_param('ii', $userId, $commentId);
                $stmt->execute();
                $stmt->close();
            }
            $liked = false;
        } else {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO comment_likes(user_id, comment_id, created_at) VALUES (?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('ii', $userId, $commentId);
                $stmt->execute();
                $stmt->close();
            }
            $liked = true;
        }

        $count = $this->getCommentLikeCount($commentId);
        $this->json(['ok' => true, 'liked' => $liked, 'like_count' => $count]);
    }

    // ---------- Controller entry ----------
    public function index(?int $userId = null): void
    {
        // AJAX actions (giống trang in4): ?action=toggle_post_like / ?action=toggle_comment_like
        $action = $_GET['action'] ?? '';
        if ($action === 'toggle_post_like') {
            $this->togglePostLikeAjax();
        } elseif ($action === 'toggle_comment_like') {
            $this->toggleCommentLikeAjax();
        }

        // Lấy current user
        $currentUser = getCurrentUser($this->conn);
        if (!$currentUser) {
            header('Location: /public/login.php');
            exit;
        }

        // Avatar cho current user (dùng public_id nếu có, fallback Cloudinary default)
        $currentUser['avatar_url'] = $this->buildAvatar($currentUser['profile_picture_public_id'] ?? null);

        // Nếu muốn hiển thị trang của user khác trên Home (ví dụ preview)
        $viewUser = null;
        if ($userId !== null && $userId !== (int)$currentUser['user_id']) {
            $stmt = $this->conn->prepare("
                SELECT user_id, username, full_name, cover_photo_public_id, profile_picture_public_id, bio
                FROM users
                WHERE user_id = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $viewUser = $stmt->get_result()->fetch_assoc() ?: null;
                $stmt->close();
                if ($viewUser) {
                    $viewUser['cover_url']  = $this->buildCover($viewUser['cover_photo_public_id'] ?? null);
                    $viewUser['avatar_url'] = $this->buildAvatar($viewUser['profile_picture_public_id'] ?? null, 120, 120);
                }
            }
        }

        // Cover chung trang home (tuỳ bạn dùng trong layout)
        $homeCoverUrl = $this->buildCover(null, 1200, 300);

        // Lấy posts feed kèm like/liked và comment_count (giống trang in4)
        $posts = [];
        $sql = "
    SELECT
        p.post_id,
        p.user_id,
        p.content_text,
        p.media_url,
        p.media_type,
        p.privacy,
        p.created_at,

        u.user_id AS user_id,
        u.full_name,
        u.username,
        u.profile_picture_public_id,

        EXISTS(SELECT 1 FROM post_likes pl WHERE pl.post_id = p.post_id AND pl.user_id = ?) AS liked,
        (SELECT COUNT(*) FROM post_likes pl2 WHERE pl2.post_id = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.post_id) AS comment_count,
        (SELECT COUNT(*) FROM shares s WHERE s.post_id = p.post_id) AS share_count
    FROM posts p
    JOIN users u ON u.user_id = p.user_id
    ORDER BY p.created_at DESC
    LIMIT 10
";

        if ($stmt = $this->conn->prepare($sql)) {
            $uid = (int)$currentUser['user_id'];
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            $posts = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();

            foreach ($posts as &$p) {
                // ensure integer user_id
                $p['user_id'] = (int)($p['user_id'] ?? 0);

                // avatar url used by your view
                $p['author_avatar_url'] = $this->buildAvatar($p['profile_picture_public_id'] ?? null, 40, 40);

                // nếu DB chưa có bảng shares thì query share_count sẽ lỗi;
                // trong trường hợp đó bạn có thể bỏ subquery share_count phía trên, và dùng fallback:
                $p['share_count'] = (int)($p['share_count'] ?? 0);
            }
            unset($p);
        }

        // Truyền xuống view
        // $currentUser, $posts, $homeCoverUrl, $viewUser (nếu cần)

        // ====== Bổ sung: danh sách bạn bè (accepted) và người lạ (suggestions) ======
        $friendsList = [];
        $friendSuggestions = [];
        $me = (int)$currentUser['user_id'];

        // Danh sách bạn bè (status = 'accepted')
        $st = $this->conn->prepare("
            SELECT u.user_id, u.username, u.full_name, u.profile_picture_public_id
            FROM friendships f
            JOIN users u
              ON (u.user_id = f.user_one_id AND f.user_two_id = ?)
              OR (u.user_id = f.user_two_id AND f.user_one_id = ?)
            WHERE f.status = 'accepted'
            ORDER BY u.full_name ASC
            LIMIT 30
        ");
        if ($st) {
            $st->bind_param('ii', $me, $me);
            $st->execute();
            $r = $st->get_result();
            $friendsList = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
            $st->close();

            foreach ($friendsList as &$fu) {
                $fu['profile_picture_url'] = $this->buildAvatar($fu['profile_picture_public_id'] ?? null, 32, 32);
            }
            unset($fu);
        }
        $pendingIncoming = [];
        $pendingOutgoing = [];

        // Pending incoming: người khác gửi lời mời cho mình (me = user_two_id)
        $st3 = $this->conn->prepare("
    SELECT
        f.friendship_id,
        u.user_id, u.username, u.full_name, u.profile_picture_public_id,
        f.created_at
    FROM friendships f
    JOIN users u ON u.user_id = f.user_one_id
    WHERE f.user_two_id = ?
      AND f.status = 'pending'
    ORDER BY f.created_at DESC
    LIMIT 10
");
        if ($st3) {
            $st3->bind_param('i', $me);
            $st3->execute();
            $r3 = $st3->get_result();
            $pendingIncoming = $r3 ? $r3->fetch_all(MYSQLI_ASSOC) : [];
            $st3->close();

            foreach ($pendingIncoming as &$pu) {
                $pu['profile_picture_url'] = $this->buildAvatar($pu['profile_picture_public_id'] ?? null, 32, 32);
            }
            unset($pu);
        }

        // Pending outgoing: mình gửi lời mời (me = user_one_id)
        $st4 = $this->conn->prepare("
    SELECT
        f.friendship_id,
        u.user_id, u.username, u.full_name, u.profile_picture_public_id,
        f.created_at
    FROM friendships f
    JOIN users u ON u.user_id = f.user_two_id
    WHERE f.user_one_id = ?
      AND f.status = 'pending'
    ORDER BY f.created_at DESC
    LIMIT 10
");
        if ($st4) {
            $st4->bind_param('i', $me);
            $st4->execute();
            $r4 = $st4->get_result();
            $pendingOutgoing = $r4 ? $r4->fetch_all(MYSQLI_ASSOC) : [];
            $st4->close();

            foreach ($pendingOutgoing as &$pu) {
                $pu['profile_picture_url'] = $this->buildAvatar($pu['profile_picture_public_id'] ?? null, 32, 32);
            }
            unset($pu);
        }
        // Người lạ (không có quan hệ friend/pending/blocked với current user)
        $st2 = $this->conn->prepare("
            SELECT u.user_id, u.username, u.full_name, u.profile_picture_public_id
            FROM users u
            WHERE u.user_id <> ?
              AND NOT EXISTS (
                SELECT 1 FROM friendships f
                WHERE (f.user_one_id = ? AND f.user_two_id = u.user_id)
                   OR (f.user_one_id = u.user_id AND f.user_two_id = ?)
              )
            ORDER BY u.full_name ASC
            LIMIT 5
        ");
        if ($st2) {
            $st2->bind_param('iii', $me, $me, $me);
            $st2->execute();
            $r2 = $st2->get_result();
            $friendSuggestions = $r2 ? $r2->fetch_all(MYSQLI_ASSOC) : [];
            $st2->close();

            foreach ($friendSuggestions as &$su) {
                $su['profile_picture_url'] = $this->buildAvatar($su['profile_picture_public_id'] ?? null, 32, 32);
            }
            unset($su);
        }

        // Truyền xuống view:
        // $currentUser, $posts, $homeCoverUrl, $viewUser, $friendsList, $friendSuggestions
        include __DIR__ . '/../views/home/layout.php';
    }
}