<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    public function publishEvent(string $event, array $payload, string $queueName = 'notifications_queue'): void
    {
        try {
            $connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'message-broker'),
                (int) env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASS', 'guest')
            );
            $channel = $connection->channel();

            $channel->queue_declare($queueName, false, true, false, false);

            $body = json_encode(['pattern' => $event, 'data' => $payload], JSON_UNESCAPED_UNICODE);
            $message = new AMQPMessage($body, ['delivery_mode' => 2]);
            $channel->basic_publish($message, '', $queueName);

            $channel->close();
            $connection->close();
        } catch (\Throwable $e) {
            Log::error('RabbitMQ Publish Error: ' . $e->getMessage());
        }
    }
}
