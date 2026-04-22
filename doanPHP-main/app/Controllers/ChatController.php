<?php
// ChatController.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/cloudinary_helper.php';
require_once __DIR__ . '/../config/cloudinary.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

class ChatController
{
    protected mysqli $conn;
    protected array $cloud;

    public function __construct()
    {
        global $conn;
        $this->conn  = $conn;
        $this->cloud = include __DIR__ . '/../config/cloudinary.php';
    }

    private function buildAvatar(?string $publicId, int $w = 32, int $h = 32): string
    {
        return cloudinary_avatar(
            $this->cloud['cloud_name'],
            $publicId,
            [
                'default_public_id' => $this->cloud['default_avatar_public_id'] ?? null,
                'w' => $w,
                'h' => $h,
            ]
        );
    }

    public function index(): void
    {
        $currentUser = getCurrentUser($this->conn);
        if (!$currentUser) {
            header('Location: /public/login.php');
            exit;
        }

        $me = (int)$currentUser['user_id'];

        // ========= LẤY LIST BẠN BÈ (accepted) =========
        $friendsList = [];
        $st = $this->conn->prepare("
            SELECT u.user_id, u.username, u.full_name, u.profile_picture_public_id
            FROM friendships f
            JOIN users u
              ON (u.user_id = f.user_one_id AND f.user_two_id = ?)
              OR (u.user_id = f.user_two_id AND f.user_one_id = ?)
            WHERE f.status = 'accepted'
            ORDER BY u.full_name ASC
        ");
        if ($st) {
            $st->bind_param('ii', $me, $me);
            $st->execute();
            $r = $st->get_result();
            $friendsList = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
            $st->close();

            foreach ($friendsList as &$fu) {
                $fu['profile_picture_url'] = $this->buildAvatar($fu['profile_picture_public_id'] ?? null, 40, 40);
            }
            unset($fu);
        }

        // ========= XÁC ĐỊNH NGƯỜI ĐANG CHAT =========
        $otherId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($otherId <= 0 && !empty($friendsList)) {
            // nếu không truyền user_id thì auto chọn bạn bè đầu tiên
            $otherId = (int)$friendsList[0]['user_id'];
        }

        // lấy thông tin user đang chat
        $otherUser = null;
        if ($otherId > 0 && $otherId !== $me) {
            $stmt = $this->conn->prepare("
                SELECT user_id, username, full_name, profile_picture_public_id
                FROM users
                WHERE user_id = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('i', $otherId);
                $stmt->execute();
                $res = $stmt->get_result();
                $otherUser = $res ? $res->fetch_assoc() : null;
                $stmt->close();
            }
        }

        if (!$otherUser) {
            // không có bạn để chat, có thể render view riêng báo “Chưa có cuộc trò chuyện”
            $title = 'Tin nhắn';
            $friendsList; // vẫn truyền được list friend
            include __DIR__ . '/../views/chat/empty.php';
            return;
        }

        $otherUser['avatar_url'] = $this->buildAvatar($otherUser['profile_picture_public_id'] ?? null, 40, 40);
        $currentUser['avatar_url'] = $this->buildAvatar($currentUser['profile_picture_public_id'] ?? null, 32, 32);

        $title = 'Chat với ' . ($otherUser['full_name'] ?: $otherUser['username'] ?: 'User');

        // biến truyền xuống view:
        // $currentUser, $otherUser, $friendsList, $title
        include __DIR__ . '/../views/chat/chat.php';
    }
}