<?php

namespace Tests\Support\RabbitMq;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * A plain job used only by RabbitMqQueueIntegrationTest. It writes to a real
 * file rather than in-process static state because the consuming
 * `php artisan queue:work` process the test spawns is a genuinely separate OS
 * process.
 */
class WritesMarkerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $filePath, private readonly string $marker) {}

    public function handle(): void
    {
        file_put_contents($this->filePath, $this->marker);
    }
}
