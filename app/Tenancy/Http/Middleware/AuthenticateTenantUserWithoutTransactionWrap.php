<?php

namespace App\Tenancy\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Same session-based tenant-user authentication as AuthenticateTenantUser,
 * but the tenant context is opened only around the auth check; the rest of
 * the pipeline runs OUTSIDE any open transaction, with tenant_id left on the
 * request. Use it (alias `auth.tenant.external`) for any route whose
 * controller calls an external service (payment provider, third-party API)
 * mid-request, so a database transaction is never held open across a
 * network call. The controller then opens its own short
 * TenantContext::run() calls around its database work.
 */
class AuthenticateTenantUserWithoutTransactionWrap
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return response()->json(['error' => 'UNAUTHENTICATED'], 401);
        }

        $tenantId = $request->session()->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            return response()->json(['error' => 'UNAUTHENTICATED'], 401);
        }

        $authenticated = TenantContext::run($tenantId, fn () => Auth::guard('web')->check());

        if (! $authenticated) {
            return response()->json(['error' => 'UNAUTHENTICATED'], 401);
        }

        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }
}
