<?php
// public/actions/get_comments.php
// GET ?post_id=...  => trả { success:true, comments: [...] }

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
header('Content-Type: application/json; charset=utf-8');

$postId = (int)($_GET['post_id'] ?? 0);
if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid post id']);
    exit;
}

$stmt = $conn->prepare("
    SELECT c.comment_id, c.content_text, c.created_at, u.user_id, u.full_name, u.profile_picture_url
    FROM comments c
    JOIN users u ON u.user_id = c.user_id
    WHERE c.post_id = ?
    ORDER BY c.created_at DESC
");
if (!$stmt) {
    error_log("get_comments prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'error' => 'DB prepare failed']);
    exit;
}
$stmt->bind_param('i', $postId);
if (!$stmt->execute()) {
    error_log("get_comments execute failed: " . $stmt->error);
    echo json_encode(['success' => false, 'error' => 'DB execute failed']);
    $stmt->close();
    exit;
}

$comments = [];
if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $comments = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $stmt->bind_result($c_id, $c_text, $c_created_at, $u_id, $u_full_name, $u_profile_url);
    while ($stmt->fetch()) {
        $comments[] = [
            'comment_id' => $c_id,
            'content_text' => $c_text,
            'created_at' => $c_created_at,
            'user_id' => $u_id,
            'full_name' => $u_full_name,
            'profile_picture_url' => $u_profile_url,
        ];
    }
}
$stmt->close();

echo json_encode(['success' => true, 'comments' => $comments]);
exit;