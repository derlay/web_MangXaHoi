<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../app/config/database.php';

// Lấy user hiện tại
$currentUserId = $_SESSION['user_id'] ?? null;
if (!$currentUserId) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$context = $_GET['context'] ?? 'home'; // home | profile
$profileUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = max(1, (int)($_GET['limit'] ?? 10));
$limit  = $limit > 30 ? 30 : $limit;

// Hàm kiểm tra bạn bè - thay theo schema thật (ví dụ bảng friendships)
function is_friend(mysqli $conn, int $a, int $b): bool
{
    // Ví dụ: friendships(user_id, friend_id, status='accepted')
    $sql = "SELECT 1 FROM friendships 
          WHERE ((user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?))
            AND status='accepted' LIMIT 1";
    $st = $conn->prepare($sql);
    if (!$st) return false;
    $st->bind_param('iiii', $a, $b, $b, $a);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    return (bool)$r;
}

// WHERE theo context + privacy
$where = "1=1";
$params = [];
$types = '';

if ($context === 'profile') {
    if (!$profileUserId) {
        echo json_encode(['error' => 'Missing profile user_id']);
        exit;
    }

    if ($profileUserId === (int)$currentUserId) {
        // Chủ trang → thấy tất cả post của mình
        $where = "p.user_id = ?";
        $params[] = $profileUserId;
        $types .= 'i';
    } else {
        // Người khác xem profile: public + (friends nếu là bạn)
        $friend = is_friend($conn, (int)$currentUserId, (int)$profileUserId);
        if ($friend) {
            $where = "p.user_id = ? AND p.privacy IN ('public','friends')";
            $params[] = $profileUserId;
            $types .= 'i';
        } else {
            $where = "p.user_id = ? AND p.privacy = 'public'";
            $params[] = $profileUserId;
            $types .= 'i';
        }
    }
} else {
    // Home feed cơ bản: public + của chính mình + bạn bè (nếu có bảng friendships)
    // Đơn giản: public OR của chính mình
    // Mở rộng: JOIN friendships để lấy bạn bè accepted
    $where = "(p.privacy='public' OR p.user_id=?)";
    $params[] = (int)$currentUserId;
    $types .= 'i';
}

// Lấy batch posts
$sql = "
  SELECT p.post_id, p.user_id, p.content_text, p.media_type, p.media_url, p.privacy, p.created_at,
         u.full_name, u.username,
         u.profile_picture_public_id,
         -- Có thể bạn đã có cột avatar_url sẵn, thì thay cho tiện:
         '' AS author_avatar_url
  FROM posts p
  JOIN users u ON u.user_id = p.user_id
  WHERE $where
  ORDER BY p.created_at DESC
  LIMIT $limit OFFSET $offset
";

$items = [];
if ($types) {
    $st = $conn->prepare($sql);
    if (!$st) {
        echo json_encode(['error' => 'Prepare error: ' . $conn->error]);
        exit;
    }
    $st->bind_param($types, ...$params);
    $st->execute();
    $rs = $st->get_result();
    $items = $rs->fetch_all(MYSQLI_ASSOC);
    $st->close();
} else {
    $rs = $conn->query($sql);
    if (!$rs) {
        echo json_encode(['error' => 'DB error: ' . $conn->error]);
        exit;
    }
    $items = $rs->fetch_all(MYSQLI_ASSOC);
}

// Ghép dữ liệu đếm và liked
$postIds = array_map(fn($r) => (int)$r['post_id'], $items);
$likesMap = [];
$commentsMap = [];
$sharesMap = [];
$likedMap = [];
if ($postIds) {
    $in = implode(',', array_map('intval', $postIds));
    // Đếm like
    $lr = $conn->query("SELECT post_id, COUNT(*) cnt FROM likes WHERE post_id IN ($in) GROUP BY post_id");
    if ($lr) foreach ($lr->fetch_all(MYSQLI_ASSOC) as $r) $likesMap[(int)$r['post_id']] = (int)$r['cnt'];

    // Đếm comment
    $cr = $conn->query("SELECT post_id, COUNT(*) cnt FROM comments WHERE post_id IN ($in) GROUP BY post_id");
    if ($cr) foreach ($cr->fetch_all(MYSQLI_ASSOC) as $r) $commentsMap[(int)$r['post_id']] = (int)$r['cnt'];

    // Đếm share (nếu có bảng shares). Không có thì để 0
    $sr = $conn->query("SELECT post_id, COUNT(*) cnt FROM shares WHERE post_id IN ($in) GROUP BY post_id");
    if ($sr) foreach ($sr->fetch_all(MYSQLI_ASSOC) as $r) $sharesMap[(int)$r['post_id']] = (int)$r['cnt'];

    // Liked by current user
    $cur = (int)$currentUserId;
    $qr = $conn->query("SELECT post_id FROM likes WHERE user_id=$cur AND post_id IN ($in)");
    if ($qr) foreach ($qr->fetch_all(MYSQLI_ASSOC) as $r) $likedMap[(int)$r['post_id']] = true;
}

// Chuyển đổi và avatar URL (Cloudinary nếu bạn có hàm)
function cloudinary_avatar_url(?string $publicId): string
{
    if (!$publicId) return '/public/img/default_avatar.jpg';
    // Nếu bạn có helper Cloudinary, thay vào đây.
    return "https://res.cloudinary.com/demo/image/upload/w_64,h_64,c_fill,g_face,r_max/{$publicId}.jpg";
}

foreach ($items as &$it) {
    $pid = (int)$it['post_id'];
    $it['like_count']    = $likesMap[$pid]    ?? 0;
    $it['comment_count'] = $commentsMap[$pid] ?? 0;
    $it['share_count']   = $sharesMap[$pid]   ?? 0;
    $it['liked']         = isset($likedMap[$pid]);
    $it['author_avatar_url'] = cloudinary_avatar_url($it['profile_picture_public_id'] ?? null);
}
unset($it);

// Count tổng
if ($types) {
    $countSql = "SELECT COUNT(*) total FROM posts p WHERE $where";
    $cst = $conn->prepare($countSql);
    $cst->bind_param($types, ...$params);
    $cst->execute();
    $total = (int)$cst->get_result()->fetch_assoc()['total'];
    $cst->close();
} else {
    $countSql = "SELECT COUNT(*) total FROM posts p WHERE $where";
    $cres = $conn->query($countSql);
    $total = (int)($cres->fetch_assoc()['total'] ?? 0);
}

echo json_encode([
    'items'       => $items,
    'has_more'    => ($offset + count($items)) < $total,
    'next_offset' => $offset + count($items),
    'total'       => $total
]);