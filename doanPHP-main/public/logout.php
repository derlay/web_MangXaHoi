<?php
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../app/Controllers/AuthController.php';
$ctrl = new AuthController();
$ctrl->logout();
