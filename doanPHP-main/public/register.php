<?php
// public/register.php
require_once __DIR__ . '/../app/controllers/AuthController.php';

$ctrl = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý đăng ký
    $ctrl->handleRegister();
} else {
    // Hiển thị form đăng ký
    $_GET['page'] = 'register';
    $ctrl->showRegister();
}
