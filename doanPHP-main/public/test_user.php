<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $id = 11;
} // mặc định test id 11

echo "<pre>";
echo "Conn class: " . (is_object($conn) ? get_class($conn) : gettype($conn)) . PHP_EOL;

$row = null;
try {
    if ($conn instanceof mysqli) {
        $sql = "SELECT user_id, username, full_name FROM users WHERE user_id = " . (int)$id . " LIMIT 1";
        $res = $conn->query($sql);
        if ($res === false) {
            echo "MySQLi query error: " . $conn->error . PHP_EOL;
        } else {
            $row = $res->fetch_assoc();
        }
    } elseif ($conn instanceof PDO) {
        $stmt = $conn->query("SELECT user_id, username, full_name FROM users WHERE user_id = " . (int)$id . " LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    } else {
        echo "Unknown conn type" . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}

echo "Result row: " . json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo "</pre>";