<?php

namespace App\Tenancy\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Wraps the rest of the pipeline in TenantContext::run() for the tenant id an
 * earlier middleware (resolve.tenant.*) placed on the request. Fails closed:
 * throws, never proceeds unscoped, if nothing did.
 *
 * Note that this holds one database transaction open for the whole request.
 * Routes that call an external service mid-request must not use it (see
 * AuthenticateTenantUserWithoutTransactionWrap).
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenantId = $request->attributes->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            throw new RuntimeException(
                'SetTenantContext middleware requires tenant_id to already be resolved onto the request.'
            );
        }

        return TenantContext::run($tenantId, fn () => $next($request));
    }
}
