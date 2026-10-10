<?php

namespace App\Tenancy\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session authentication for tenant users, in a single middleware: resolves
 * the tenant from the session, opens TenantContext, then checks the guard.
 *
 * Why one middleware and not `auth` + a tenant-resolution middleware:
 * `users` carries an RLS policy, so with no tenant GUC set, the very query
 * SessionGuard uses to re-hydrate a returning session finds nothing, and
 * every request after login would fail to authenticate. The tenant must be
 * known BEFORE the user lookup, so it cannot be read from the user row. It
 * is read from the session, written by TenantAuthController::login() at the
 * one point the tenant is legitimately known. That is exactly as
 * trustworthy as Auth::id() already is.
 *
 * Splitting this into separate middleware ordered before `auth` does not
 * work: Laravel's global middleware priority list sorts
 * `Authenticate` (an AuthenticatesRequests implementation) ahead of any
 * middleware not itself in that list, regardless of route array order. One
 * consolidated handle() leaves nothing for that sort to get wrong.
 *
 * The whole request runs inside the TenantContext transaction. For routes
 * that call an external service, use `auth.tenant.external` instead.
 */
class AuthenticateTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // A request without a stateful origin never gets the session
        // middleware, so no session store exists: unauthenticated, not a 500.
        if (! $request->hasSession()) {
            return response()->json(['error' => 'UNAUTHENTICATED'], 401);
        }

        $tenantId = $request->session()->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            return response()->json(['error' => 'UNAUTHENTICATED'], 401);
        }

        return TenantContext::run($tenantId, function () use ($request, $next, $tenantId) {
            $request->attributes->set('tenant_id', $tenantId);

            if (! Auth::guard('web')->check()) {
                return response()->json(['error' => 'UNAUTHENTICATED'], 401);
            }

            return $next($request);
        });
    }
}
