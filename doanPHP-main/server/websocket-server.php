<?php
require __DIR__ . '/../vendor/autoload.php';

use App\WebSocketServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\SocketServer;

// ENV
$wsPort    = (int)(getenv('WS_PORT') ?: 8080);
$redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
$redisPort = (int)(getenv('REDIS_PORT') ?: 6379);
$queue     = getenv('REDIS_QUEUE') ?: 'chat_queue';

// Component
$app = new WebSocketServer($redisHost, $redisPort, $queue);

// React loop
$loop = LoopFactory::create();

// Poll Redis định kỳ
$loop->addPeriodicTimer(0.1, function () use ($app) {
    $app->pollRedisOnce();
});

// SocketServer đúng kiểu React\Socket\ServerInterface
$socket = new SocketServer("0.0.0.0:{$wsPort}", [], $loop);

// Khởi tạo IoServer với socket + loop
$server = new IoServer(
    new HttpServer(new WsServer($app)),
    $socket,
    $loop
);

echo '[' . date('H:i:s') . "] WS listening on ws://0.0.0.0:{$wsPort}\n";
$server->run();
