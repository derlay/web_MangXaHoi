<?php
// BẬT COOKIE PARAMS TOÀN CỤC TRƯỚC KHI BẮT ĐẦU PHIÊN
session_set_cookie_params([
    'path'     => '/',     // BẮT BUỘC: dùng cho mọi đường dẫn
    'httponly' => true,
    'samesite' => 'Lax',   // tránh mất cookie khi redirect POST->GET
    // 'secure' => true,   // chỉ bật khi chạy HTTPS
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/controllers/AuthController.php';

$ctrl = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl->handleLogin();
} else {
    // Nếu đã đăng nhập và là admin, vào admin luôn
    if (!empty($_SESSION['user_id']) && strtolower($_SESSION['role'] ?? '') === 'admin') {
        header('Location: /public/admin/index.php');
        exit;
    }
    $ctrl->showLogin();
}
