<?php

namespace App\Queue\RabbitMq\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GET /api/queue-health: broker-level queue depth, read from the RabbitMQ
 * management plugin's HTTP API (config/services.php `rabbitmq_management`).
 * Best-effort: a management-plugin outage degrades to `available: false`
 * rather than a 500.
 */
class QueueHealthController extends Controller
{
    public function show(): JsonResponse
    {
        return $this->ok(['broker' => $this->brokerHealth()]);
    }

    /** @return array<string, mixed> */
    private function brokerHealth(): array
    {
        $config = config('services.rabbitmq_management');

        try {
            $response = Http::withBasicAuth($config['user'], $config['password'])
                ->timeout(2)
                ->get(sprintf(
                    '%s/api/queues/%s/%s',
                    rtrim($config['url'], '/'),
                    rawurlencode($config['vhost']),
                    rawurlencode($config['queue']),
                ));

            if (! $response->successful()) {
                return ['available' => false];
            }

            $body = $response->json();

            return [
                'available' => true,
                'messages_ready' => $body['messages_ready'] ?? null,
                'messages_unacknowledged' => $body['messages_unacknowledged'] ?? null,
                'consumers' => $body['consumers'] ?? null,
            ];
        } catch (Throwable $e) {
            Log::info('Queue health check could not reach the RabbitMQ management API.', ['error' => $e->getMessage()]);

            return ['available' => false];
        }
    }
}
