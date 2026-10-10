<?php

namespace App\Tenancy;

/**
 * The application-layer half of two-layer tenant isolation (the other half
 * is the Postgres RLS policy on each tenant-scoped table). Holds the tenant
 * id for the duration of the active TenantContext::run() call so the
 * Eloquent global scope has something to filter on without threading a
 * tenant id through every query.
 *
 * Process-local, in-memory state with no durability of its own. The real,
 * fail-closed boundary is the database GUC TenantContext sets alongside it.
 */
final class CurrentTenant
{
    private static ?string $id = null;

    public static function id(): ?string
    {
        return self::$id;
    }

    public static function set(?string $tenantId): void
    {
        self::$id = $tenantId;
    }

    public static function clear(): void
    {
        self::$id = null;
    }
}
