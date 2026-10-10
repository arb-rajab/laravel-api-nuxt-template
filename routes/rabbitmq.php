<?php

use App\Queue\RabbitMq\Http\QueueHealthController;
use Illuminate\Support\Facades\Route;

// Routes of the optional RabbitMQ module (prefix /api is applied by
// RabbitMqServiceProvider).
Route::middleware(['auth', 'role:admin'])->get('queue-health', [QueueHealthController::class, 'show']);
