<?php

namespace Tests\Tenancy\Support;

use App\Models\User;
use App\Tenancy\Models\Note;
use App\Tenancy\Models\Tenant;

/**
 * Creates one row in every tenant-scoped table
 * (config('tenancy.tenant_scoped_tables')) for a tenant, so the
 * isolation suite covers every manifest table rather than a hand-picked
 * subset. When you add a table to the manifest, add it here too; the suite
 * fails loudly (a missing array key) if you forget.
 *
 * Must be called inside a TenantContext::run() block for $tenant's own id:
 * every tenant-scoped table's INSERT is subject to its RLS policy's WITH
 * CHECK clause, so an insert with no matching context is rejected exactly
 * like a read would be. That is deliberate, not a limitation.
 */
final class TenantFixture
{
    /** @return array<string, string> table name => created row id */
    public static function seedOneRowPerTable(Tenant $tenant): array
    {
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $note = Note::factory()->create(['tenant_id' => $tenant->id]);

        return [
            'users' => $user->id,
            'notes' => $note->id,
        ];
    }
}
