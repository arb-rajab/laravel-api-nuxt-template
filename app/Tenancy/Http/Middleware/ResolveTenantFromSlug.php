<?php

namespace App\Tenancy\Http\Middleware;

use App\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public-path tenant resolution: the client supplies only a {slug} (a
 * deliberately public, shareable identifier). Resolves slug -> tenant_id with
 * a parameterized lookup against `tenants` (outside the RLS boundary), and
 * only that server-resolved value ever reaches the GUC: never anything
 * client-supplied. Must run before `tenant.context`.
 */
class ResolveTenantFromSlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::query()->where('slug', $request->route('slug'))->first();

        if ($tenant === null) {
            // The same generic response whether the slug never existed or the
            // tenant was soft-deleted: a caller cannot tell the two apart.
            return response()->json(['error' => 'NOT_FOUND'], 404);
        }

        $request->attributes->set('tenant_id', $tenant->id);

        return $next($request);
    }
}
