<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | `rabbitmq` is provided by the optional RabbitMQ module
    | (app/Queue/RabbitMq). If you delete that module, set QUEUE_CONNECTION to
    | `redis` or `database`.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        // Optional module: app/Queue/RabbitMq (direct php-amqplib
        // integration with TTL/dead-letter delayed delivery and retry
        // tracking). All jobs share the single configured queue.
        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => (int) env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'starter_app'),
            'password' => env('RABBITMQ_PASSWORD', ''),
            'vhost' => env('RABBITMQ_VHOST', '/'),
            'queue' => env('RABBITMQ_QUEUE', 'starter_app'),
            'retry_after' => (int) env('RABBITMQ_RETRY_AFTER', 90),
        ],

    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table' => 'job_batches',
    ],

    // Failed jobs are not persisted by default. Switch to `database-uuids`
    // and add a failed_jobs migration if you want them stored.
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'null'),
    ],

];
