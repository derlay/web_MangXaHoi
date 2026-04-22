<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Predis\Client as Predis;

class WebSocketServer implements MessageComponentInterface
{
    /** @var array<int, array<int, ConnectionInterface>> */
    private array $clientsByUserId = [];

    private Predis $redis;
    private string $queue;

    public function __construct(
        string $redisHost = '127.0.0.1',
        int $redisPort = 6379,
        string $queue = 'chat_queue'
    ) {
        $this->redis = new Predis([
            'scheme' => 'tcp',
            'host'   => $redisHost,
            'port'   => $redisPort,
        ]);
        $this->queue = $queue;
    }

    private function log(string $msg): void
    {
        echo '[' . date('H:i:s') . "] {$msg}\n";
        flush();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $queryString = $conn->httpRequest->getUri()->getQuery() ?? '';
        parse_str($queryString, $params);
        $userId = isset($params['user_id']) ? (int)$params['user_id'] : 0;

        if ($userId <= 0) {
            $conn->send(json_encode(['type' => 'error', 'message' => 'Missing user_id']));
            $conn->close();
            return;
        }

        $conn->user_id = $userId;
        $uid = (int)$userId;

        if (!isset($this->clientsByUserId[$uid])) {
            $this->clientsByUserId[$uid] = [];
        }
        $this->clientsByUserId[$uid][$conn->resourceId] = $conn;

        $this->log("New connection user_id={$uid}, rid={$conn->resourceId}");
    }

    public function onClose(ConnectionInterface $conn)
    {
        $uid = (int)($conn->user_id ?? 0);
        if ($uid > 0 && isset($this->clientsByUserId[$uid][$conn->resourceId])) {
            unset($this->clientsByUserId[$uid][$conn->resourceId]);
            if (empty($this->clientsByUserId[$uid])) {
                unset($this->clientsByUserId[$uid]);
            }
        }
        $this->log("Closed connection user_id={$uid}, rid={$conn->resourceId}");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->log('WS error: ' . $e->getMessage());
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Không cần xử lý message từ client
    }

    private function broadcastTo(array $userIds, array $payload): void
    {
        $json = json_encode($payload);
        foreach ($userIds as $uid) {
            $uid = (int)$uid;
            if (!empty($this->clientsByUserId[$uid])) {
                foreach ($this->clientsByUserId[$uid] as $conn) {
                    try {
                        $conn->send($json);
                    } catch (\Throwable $e) {
                        $this->log("Send error to {$uid}: " . $e->getMessage());
                    }
                }
            }
        }
        $this->log('Broadcast to ' . implode(',', $userIds));
    }

    // Gọi hàm này định kỳ từ loop bên ngoài (ReactPHP) để tiêu thụ Redis
    public function pollRedisOnce(): void
    {
        try {
            // BLPOP timeout 1s để không block hẳn loop
            $res = $this->redis->blpop([$this->queue], 1);
            if (!$res || count($res) < 2) {
                return;
            }
            [$queue, $json] = $res;
            $payload = json_decode($json, true);
            if (!is_array($payload)) {
                $this->log('Invalid payload JSON');
                return;
            }

            $recipients = [];
            if (!empty($payload['recipients']) && is_array($payload['recipients'])) {
                $recipients = array_map('intval', $payload['recipients']);
            } else {
                $msg = $payload['message'] ?? [];
                $sender = (int)($msg['sender_id'] ?? 0);
                $other  = (int)($msg['other_id'] ?? 0);
                if ($sender && $other) {
                    $recipients = [$sender, $other];
                }
            }

            if (!empty($recipients)) {
                $this->broadcastTo($recipients, $payload);
            } else {
                $this->log('No recipients; skip');
            }
        } catch (\Throwable $e) {
            $this->log('Redis poll error: ' . $e->getMessage());
            try {
                $this->redis->disconnect();
            } catch (\Throwable $e2) {
            }
            try {
                $this->redis->connect();
                $this->log('Redis reconnected');
            } catch (\Throwable $e3) {
            }
        }
    }
}
