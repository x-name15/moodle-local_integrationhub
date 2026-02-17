<?php
namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * AMQP Transport Driver.
 *
 * Handles publishing messages to RabbitMQ.
 * Expects 'base_url' to be a connection string (e.g. amqp://user:pass@host:5672).
 * Expects 'endpoint' to be the Routing Key or Queue name.
 */
class amqp implements contract {

    /**
     * @inheritDoc
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = ''): array {
        $starttime = microtime(true);
        $attempts = 1; // AMQP publish attempt.

        // Parse connection string: amqp://user:pass@host:port/vhost
        // For simplicity, we assume standard URL format.
        $parsed = parse_url($service->base_url);
        if (!$parsed || !isset($parsed['host'])) {
             return $this->error_result('Invalid AMQP connection string', $starttime);
        }

        $host = $parsed['host'];
        $port = $parsed['port'] ?? 5672;
        $user = $parsed['user'] ?? 'guest';
        $pass = $parsed['pass'] ?? 'guest';
        $vhost = isset($parsed['path']) && $parsed['path'] !== '/' ? substr($parsed['path'], 1) : '/';

        try {
            // 1. Connect.
            $connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost, false, 
                'AMQPLAIN', 
                null, 
                'en_US', 
                (int)$service->timeout, // Connection timeout
                (int)$service->timeout  // Read/Write timeout
            );
            $channel = $connection->channel();

            // 2. Publish.
            // We assume 'endpoint' is the routing key.
            // Exchange handling: For now, we publish to default exchange (direct to queue) or can be configured.
            // Let's assume we publish to a default exchange if provided, or default '' exchange.
            // Current MVP: Direct publish to queue named in endpoint.
            
            $routingkey = ltrim($endpoint, '/'); // Remove leading slash if present.
            
            // Declare queue loosely to ensure it exists? 
            // Better to assume infrastructure exists or use Passive declare.
            // For MVP: Just publish.
            
            $msgbody = json_encode($payload);
            $msg = new AMQPMessage($msgbody, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json'
            ]);

            $exchange = ''; // Default exchange.
            
            $channel->basic_publish($msg, $exchange, $routingkey);

            // 3. Close.
            $channel->close();
            $connection->close();

            return [
                'success'   => true,
                'response'  => 'Published to ' . $routingkey,
                'error'     => null,
                'latency'   => (int)((microtime(true) - $starttime) * 1000),
                'attempts'  => $attempts
            ];

        } catch (\Exception $e) {
            return $this->error_result('AMQP Error: ' . $e->getMessage(), $starttime);
        }
    }

    private function error_result(string $msg, float $starttime): array {
        return [
            'success'   => false,
            'response'  => null,
            'error'     => $msg,
            'latency'   => (int)((microtime(true) - $starttime) * 1000),
            'attempts'  => 1
        ];
    }
}
