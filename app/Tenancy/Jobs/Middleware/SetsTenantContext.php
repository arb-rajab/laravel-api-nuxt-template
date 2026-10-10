<?php

namespace App\Tenancy\Jobs\Middleware;

use App\Tenancy\TenantContext;
use Closure;

/** Queue job middleware: runs the job inside TenantContext for a fixed tenant id. */
class SetsTenantContext
{
    public function __construct(private readonly string $tenantId) {}

    public function handle(mixed $job, Closure $next): void
    {
        TenantContext::run($this->tenantId, fn () => $next($job));
    }
}
