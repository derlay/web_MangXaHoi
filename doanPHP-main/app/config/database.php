<?php

require_once dirname(__DIR__, 2) . '/app/config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env'); // nạp .env

$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'social_media';
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';


$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die('Kết nối thất bại: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
