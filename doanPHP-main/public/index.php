<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Hiển thị lỗi khi DEV (tắt khi lên PROD)
if (!isset($_SERVER['APP_ENV']) || $_SERVER['APP_ENV'] !== 'prod') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// PHP built-in server: phục vụ file tĩnh trực tiếp để giảm tải
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path && $path !== '/' && is_file($file)) {
        // Trả cho built-in server xử lý (css/js/img)
        return false;
    }
}

// Bảo vệ session cookie
$secure = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);
// Tùy chọn: đổi tên session để tránh đụng các app khác
// session_name('NETI_SESSID');

session_set_cookie_params([
    'lifetime' => 0,                 // session cookie
    'path'     => '/',
    'domain'   => '',                // để trống = mặc định host hiện tại
    'secure'   => $secure,           // true nếu chạy https
    'httponly' => true,
    'samesite' => 'Lax',             // tránh lỗi cookie khi điều hướng
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
    // Nếu bạn muốn bảo mật hơn sau khi đăng nhập, hãy gọi session_regenerate_id(true) ở chỗ login
}

// Tùy chọn: Header giúp chặn cache trang động (bật nếu cần debug không cache)
// header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
// header('Pragma: no-cache');

// Autoload (nếu dùng Composer)
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

// Định nghĩa đường dẫn app (nếu cần dùng ở nhiều nơi)
define('APP_PATH', dirname(__DIR__) . '/app');
define('VIEW_PATH', dirname(__DIR__) . '/app/Views');

// Chạy Controller chính
try {
    require_once APP_PATH . '/Controllers/HomeController.php';
    $home = new HomeController();
    $home->index();
} catch (Throwable $e) {
    http_response_code(500);

    // Trang lỗi gọn cho DEV; thay bằng view riêng ở PROD
    if (!isset($_SERVER['APP_ENV']) || $_SERVER['APP_ENV'] !== 'prod') {
        $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line = (int)$e->getLine();
        echo "<h1>Lỗi máy chủ</h1><p>$msg</p><pre>$file:$line</pre>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
    } else {
        echo '<h1>Đã xảy ra lỗi</h1><p>Vui lòng thử lại sau.</p>';
    }
}
