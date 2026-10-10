<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Optional RabbitMQ module: the management plugin's HTTP API, read by
    // GET /api/queue-health. Best-effort only; the endpoint degrades to
    // "unavailable" if it cannot be reached.
    'rabbitmq_management' => [
        'url' => env('RABBITMQ_MANAGEMENT_URL', 'http://127.0.0.1:15672'),
        'user' => env('RABBITMQ_USER', 'starter_app'),
        'password' => env('RABBITMQ_PASSWORD', ''),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'queue' => env('RABBITMQ_QUEUE', 'starter_app'),
    ],

];
