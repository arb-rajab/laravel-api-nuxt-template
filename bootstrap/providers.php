<?php

use App\Providers\AppServiceProvider;
use App\Queue\RabbitMq\RabbitMqServiceProvider;
use App\Tenancy\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    // Optional modules. Delete a line together with its folder to remove it
    // (see the "Optional modules" section of README.md).
    TenancyServiceProvider::class,
    RabbitMqServiceProvider::class,
];
