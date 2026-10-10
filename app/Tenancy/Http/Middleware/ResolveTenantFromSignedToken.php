<?php

namespace App\Tenancy\Http\Middleware;

use App\Tenancy\InvalidTenantTokenException;
use App\Tenancy\SignedTenantToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public-path tenant resolution from a signed capability token in the
 * {token} route parameter. Usage: `resolve.tenant.token:some_purpose`.
 * Puts tenant_id and token_payload on the request; must run before
 * `tenant.context`. Every failure mode returns the identical response.
 */
class ResolveTenantFromSignedToken
{
    public function handle(Request $request, Closure $next, string $purpose): Response
    {
        try {
            $payload = SignedTenantToken::verify((string) $request->route('token'), $purpose);
        } catch (InvalidTenantTokenException) {
            return response()->json(['error' => 'INVALID_OR_EXPIRED_TOKEN'], 404);
        }

        $request->attributes->set('tenant_id', $payload['tenant_id']);
        $request->attributes->set('token_payload', $payload);

        return $next($request);
    }
}
