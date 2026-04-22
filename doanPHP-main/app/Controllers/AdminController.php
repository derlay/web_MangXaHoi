<?php
require_once __DIR__ . '/../config/database.php';

class AdminController
{
    private mysqli $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
        if (!$this->conn) {
            throw new RuntimeException('DB connection missing');
        }
    }

    public function getDataForTab(string $tab): array
    {
        switch ($tab) {
            case 'users':
                return [
                    'users' => $this->fetchUsers(),
                    'pendingReports' => $this->countPendingReports(),
                ];
            case 'reports':
                return [
                    'reports' => $this->fetchReports(),
                    'pendingReports' => $this->countPendingReports(),
                ];
            case 'logs':
                return [
                    'logs' => $this->fetchAdminLogs(),
                    'pendingReports' => $this->countPendingReports(),
                ];
            case 'posts':
                return [
                    'posts' => $this->fetchPosts(),
                    'pendingReports' => $this->countPendingReports(),
                ];
            case 'settings':
                return [
                    'pendingReports' => $this->countPendingReports(),
                ];
            case 'dashboard':
            default:
                return [
                    'dailyStats' => $this->fetchDailyStats(),
                    'pendingReports' => $this->countPendingReports(),
                    'totals' => $this->fetchTotals(),
                ];
        }
    }

    private function fetchUsers(): array
    {
        $sql = "
          SELECT u.user_id, u.username, u.full_name, u.email, u.created_at,
                 GROUP_CONCAT(r.role_name ORDER BY r.role_name) AS roles
          FROM users u
          LEFT JOIN user_roles ur ON ur.user_id = u.user_id
          LEFT JOIN roles r ON r.role_id = ur.role_id
          GROUP BY u.user_id
          ORDER BY u.created_at DESC
          LIMIT 500
        ";
        $res = $this->conn->query($sql);
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

        // Tập hợp các admin user_id
        $admins = [];
        $ares = $this->conn->query("
          SELECT ur.user_id
          FROM user_roles ur
          JOIN roles r ON r.role_id = ur.role_id
          WHERE r.role_name = 'Admin'
        ");
        if ($ares) $admins = array_map(fn($r) => (int)$r['user_id'], $ares->fetch_all(MYSQLI_ASSOC));

        foreach ($rows as &$row) {
            $uid = (int)$row['user_id'];
            $roleList = array_filter(array_map('trim', explode(',', (string)$row['roles'])));
            $role = 'User';
            if (in_array('Admin', $roleList, true)) $role = 'Admin';
            elseif (in_array('Moderator', $roleList, true)) $role = 'Moderator';
            $row['role'] = $role;

            // Đánh dấu Banned nếu bị một Admin block (blocked_users)
            $banned = false;
            if (!empty($admins)) {
                $ids = implode(',', array_map('intval', $admins));
                $bres = $this->conn->query("SELECT 1 FROM blocked_users WHERE blocked_id={$uid} AND blocker_id IN ({$ids}) LIMIT 1");
                $banned = $bres && $bres->fetch_assoc();
            }
            $row['status'] = $banned ? 'Banned' : 'Active';
        }
        unset($row);
        return $rows;
    }

    private function fetchDailyStats(): array
    {
        $sql = "SELECT stat_date, new_users_count, total_users_count, total_posts_count, total_comments_count, active_users_count
                FROM daily_statistics
                ORDER BY stat_date ASC
                LIMIT 365";
        $res = $this->conn->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function fetchReports(): array
    {
        // Thêm username người báo cáo
        $sql = "SELECT r.report_id, u.username AS reporter, r.target_type, r.target_id, r.reason, r.status, r.created_at
                FROM reports r
                JOIN users u ON u.user_id = r.reporter_id
                ORDER BY r.created_at DESC
                LIMIT 500";
        $res = $this->conn->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function fetchAdminLogs(): array
    {
        // Lấy admin username từ admin_user_id
        $sql = "SELECT l.log_id, l.admin_user_id, u.username AS admin,
                       l.action_type, l.target_type, l.target_id, l.details, l.created_at
                FROM admin_action_logs l
                JOIN users u ON u.user_id = l.admin_user_id
                ORDER BY l.created_at DESC
                LIMIT 500";
        $res = $this->conn->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function fetchPosts(): array
    {
        // KHÔNG dùng flagged_posts (không tồn tại). Suy ra Flagged nếu có report pending cho post đó.
        $sql = "
          SELECT
            p.post_id,
            u.username,
            p.content_text,
            p.media_type,
            p.media_url,
            p.privacy,
            p.created_at,
            IF(COALESCE(pr.cnt,0) > 0, 'Flagged', 'Published') AS status
          FROM posts p
          JOIN users u ON u.user_id = p.user_id
          LEFT JOIN (
            SELECT target_id, COUNT(*) AS cnt
            FROM reports
            WHERE target_type = 'post' AND status = 'pending'
            GROUP BY target_id
          ) pr ON pr.target_id = p.post_id
          ORDER BY p.created_at DESC
          LIMIT 500
        ";
        $res = $this->conn->query($sql);
        if (!$res) {
            error_log('fetchPosts SQL error: ' . $this->conn->error);
        }
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function countPendingReports(): int
    {
        $sql = "SELECT COUNT(*) AS c FROM reports WHERE status = 'pending'";
        $res = $this->conn->query($sql);
        $row = $res ? $res->fetch_assoc() : ['c' => 0];
        return (int)$row['c'];
    }

    private function fetchTotals(): array
    {
        $totUsers = (int)($this->conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0);
        $totPosts = (int)($this->conn->query("SELECT COUNT(*) AS c FROM posts")->fetch_assoc()['c'] ?? 0);
        $totComments = (int)($this->conn->query("SELECT COUNT(*) AS c FROM comments")->fetch_assoc()['c'] ?? 0);
        // Xấp xỉ active users 30 ngày: có post trong 30 ngày
        $totActive = (int)($this->conn->query("
            SELECT COUNT(*) AS c
            FROM users u
            WHERE EXISTS (SELECT 1 FROM posts p WHERE p.user_id = u.user_id AND p.created_at >= NOW() - INTERVAL 30 DAY)
        ")->fetch_assoc()['c'] ?? 0);

        return [
            'users' => $totUsers,
            'posts' => $totPosts,
            'comments' => $totComments,
            'activeUsers' => $totActive
        ];
    }
}
