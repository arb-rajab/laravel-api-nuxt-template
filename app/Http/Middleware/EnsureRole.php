<?php

namespace App\Http\Middleware;

use App\Http\Concerns\RespondsWithJson;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-based authorization at the app layer: one shared `web` auth guard,
 * gated per route by the authenticated user's own `role` column. Must run
 * after authentication; it reads $request->user() and never re-authenticates.
 *
 * Usage: `role:admin` or `role:admin,member` (comma-separated allowed roles).
 */
class EnsureRole
{
    use RespondsWithJson;

    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $allowed = explode(',', $roles);
        $user = $request->user();

        if ($user === null) {
            return $this->error('UNAUTHENTICATED', 401);
        }

        if (! in_array($user->role, $allowed, true)) {
            return $this->error('FORBIDDEN', 403);
        }

        return $next($request);
    }
}
