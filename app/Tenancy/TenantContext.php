<?php

namespace App\Tenancy;

use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only mechanism that establishes tenant context. The tenant GUC is set
 * with a parameterized `set_config(guc, ?, true)` call (never a
 * string-interpolated SET LOCAL) as the first statement inside an explicitly
 * opened transaction. There is no session-level fallback.
 *
 * Pooling caveat: a "local" set_config value resets at the end of its
 * transaction, but NOT to NULL. Once a physical connection has ever set the
 * GUC, Postgres treats it as defined for that connection's lifetime and
 * current_setting(..., true) returns an EMPTY STRING when no context is
 * active. With connection pooling (PgBouncer transaction mode) that is the
 * normal state after a connection's first tenant-scoped transaction. The RLS
 * policies are hardened against it (NULLIF(..., '') before the ::uuid cast,
 * see the RLS migration); this class's job is to always set a real value
 * inside a real transaction.
 */
final class TenantContext
{
    /**
     * Run $callback inside a transaction with the tenant GUC set. Fails
     * closed: an empty tenant id is refused rather than silently running
     * with no context.
     */
    public static function run(string $tenantId, Closure $callback, ?string $connection = null): mixed
    {
        if ($tenantId === '') {
            throw new InvalidArgumentException('TenantContext::run() requires a non-empty tenant id.');
        }

        $db = DB::connection($connection);

        return $db->transaction(function () use ($db, $tenantId, $callback) {
            $db->statement('select set_config(?, ?, true)', [config('tenancy.guc'), $tenantId]);

            $previous = CurrentTenant::id();
            CurrentTenant::set($tenantId);

            try {
                return $callback();
            } finally {
                CurrentTenant::set($previous);
            }
        });
    }

    /**
     * Explicitly clears the tenant GUC without opening a new transaction.
     *
     * Test infrastructure, not for request or job code. A test that proves
     * fail-closed behaviour with real rows present must clear the GUC
     * explicitly: RefreshDatabase wraps a whole test in one outer
     * transaction (so a nested run() only opens a savepoint, and releasing a
     * savepoint does not reset a transaction-local setting), and even a real
     * COMMIT leaves the GUC as an empty string rather than NULL.
     */
    public static function clear(?string $connection = null): void
    {
        DB::connection($connection)->statement('select set_config(?, NULL, true)', [config('tenancy.guc')]);

        CurrentTenant::clear();
    }
}
