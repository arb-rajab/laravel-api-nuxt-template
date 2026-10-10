<?php

namespace App\Queue\RabbitMq;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * OPTIONAL MODULE. Registers the 'rabbitmq' queue driver. Laravel resolves a
 * connector by the connection's own `driver` key (config/queue.php), never by
 * the connection name, so this extend() call is what makes
 * QUEUE_CONNECTION=rabbitmq resolvable at all.
 */
class RabbitMqServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Queue::extend('rabbitmq', fn () => new RabbitMqConnector);

        Route::middleware('api')->prefix('api')->group(base_path('routes/rabbitmq.php'));
    }
}
