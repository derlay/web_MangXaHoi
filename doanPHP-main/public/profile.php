<?php
// public/profile.php
require_once __DIR__ . '/../app/controllers/ProfileController.php';

$controller = new ProfileController();

// AJAX delete post: POST /public/profile.php?action=delete_post
if (($_GET['action'] ?? '') === 'delete_post') {
    $controller->deletePostAjax();
    exit;
}

// normal render
$controller->show(isset($_GET['user_id']) ? (int)$_GET['user_id'] : null);