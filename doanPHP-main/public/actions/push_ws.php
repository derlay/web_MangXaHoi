<?php
// Đây chỉ là ví dụ, production nên dùng queue/Redis.
// File này SẼ được gọi từ send_message.php mỗi khi có tin mới.

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../app/config/database.php';

// Ở đây đơn giản: lấy payload từ POST body và đẩy ra stdout để server WS đọc được
// Để làm đúng hơn, bạn nên dùng Redis pub/sub giữa send_message.php và websocket-server.php

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);