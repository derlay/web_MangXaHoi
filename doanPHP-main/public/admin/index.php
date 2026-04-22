<?php
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['user_id'])) {
    header('Location:/public/login.php');
    exit;
}
if (($_SESSION['role'] ?? 'User') !== 'Admin') {
    header('Location:/public/index.php');
    exit;
}

require_once __DIR__ . '/../../app/Controllers/AdminController.php';
$tab = $_GET['tab'] ?? 'dashboard';
$ctrl = new AdminController();
$data = $ctrl->getDataForTab($tab);

$titleMap = [
    'dashboard' => 'Thống kê hàng ngày',
    'users'     => 'Quản lý người dùng',
    'reports'   => 'Kiểm duyệt',
    'logs'      => 'Nhật ký hành động quản trị',
    'posts'     => 'Quản lý bài viết',
    'settings'  => 'Cài đặt hệ thống',
];
$title = $titleMap[$tab] ?? 'Thống kê hàng ngày';

include __DIR__ . '/../../app/Views/admin/layout.php';
