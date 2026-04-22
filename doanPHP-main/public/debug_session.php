<?php
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();
header('Content-Type: text/plain; charset=utf-8');

echo "session_id: " . session_id() . PHP_EOL;
echo "COOKIE PHPSESSID: " . ($_COOKIE['PHPSESSID'] ?? '(none)') . PHP_EOL;
echo "SESSION:\n";
print_r($_SESSION);
