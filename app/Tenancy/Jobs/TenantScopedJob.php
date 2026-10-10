<?php

namespace App\Tenancy\Jobs;

use App\Tenancy\Jobs\Middleware\SetsTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

/**
 * Base class for every queued job that reads or writes tenant-scoped data.
 * Carries the tenant id in its own serialized payload and runs handle()
 * inside TenantContext via job middleware, so the tenant GUC is re-set on
 * every attempt (including retries on a reused worker connection) and never
 * inherited from whatever job ran on the worker before.
 *
 * Jobs that touch no tenant-scoped data (cross-tenant maintenance, for
 * example) should not extend this class.
 */
abstract class TenantScopedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Not `readonly`: SerializesModels restores properties from the concrete job
    // subclass's scope on unserialize, which PHP 8.3 rejects for readonly.
    protected string $tenantId;

    public function __construct(string $tenantId)
    {
        if ($tenantId === '') {
            throw new InvalidArgumentException(
                static::class.' requires a non-empty tenant_id: a tenant-scoped job may never be dispatched without one.'
            );
        }

        $this->tenantId = $tenantId;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [new SetsTenantContext($this->tenantId)];
    }
}
