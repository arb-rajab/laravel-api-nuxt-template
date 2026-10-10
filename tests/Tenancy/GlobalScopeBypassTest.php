<?php

use App\Tenancy\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Tenancy\Support\TenantFixture;

/**
 * The direct test of the two-layer claim: RLS holds even when the
 * application-layer scope is bypassed entirely (deliberately, or by a bug)
 * with a raw DB::select or a query builder call that never touches Eloquent.
 *
 * Both tenants are constructed inline in each test, never a shared "the test
 * tenant" fixture, which is exactly the kind of setup that could mask a
 * cross-tenant bug.
 */
test('RLS holds against raw queries and the query builder, bypassing Eloquent entirely', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $rowsA = TenantContext::run($tenantA->id, fn () => TenantFixture::seedOneRowPerTable($tenantA));
    $rowsB = TenantContext::run($tenantB->id, fn () => TenantFixture::seedOneRowPerTable($tenantB));

    TenantContext::run($tenantA->id, function () use ($rowsA, $rowsB) {
        foreach (config('tenancy.tenant_scoped_tables') as $table) {
            $viaRawSelect = collect(DB::select("select id from {$table}"))->pluck('id');
            expect($viaRawSelect->contains($rowsA[$table]))
                ->toBeTrue("Tenant A's own row is missing from [{$table}] via DB::select.");
            expect($viaRawSelect->contains($rowsB[$table]))
                ->toBeFalse("Tenant B's row leaked into [{$table}] via DB::select.");

            $viaQueryBuilder = DB::table($table)->pluck('id');
            expect($viaQueryBuilder->contains($rowsA[$table]))
                ->toBeTrue("Tenant A's own row is missing from [{$table}] via the query builder.");
            expect($viaQueryBuilder->contains($rowsB[$table]))
                ->toBeFalse("Tenant B's row leaked into [{$table}] via the query builder.");
        }
    });
});

test('RLS rejects a raw DB::statement write claiming a different tenant than the active context', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    TenantContext::run($tenantA->id, function () use ($tenantB) {
        // WITH CHECK defaults to the policy's USING expression, so an INSERT
        // is gated exactly like a read: a hand-written statement claiming
        // tenant B's id while context is A must be rejected by Postgres
        // itself, not merely by application code remembering to set
        // tenant_id correctly.
        $attempt = fn () => DB::statement(
            'insert into notes (id, tenant_id, title) values (gen_random_uuid(), ?, ?)',
            [$tenantB->id, 'Cross-tenant attempt']
        );

        expect($attempt)->toThrow(QueryException::class);
    });
});

test('RLS fails closed when no tenant context is set, even though matching rows exist', function () {
    $tenant = Tenant::factory()->create();

    $rows = TenantContext::run($tenant->id, fn () => TenantFixture::seedOneRowPerTable($tenant));

    // Clear explicitly rather than merely not calling run() again; see
    // TenantContext::clear() for why that is necessary inside a test.
    TenantContext::clear();

    foreach (config('tenancy.tenant_scoped_tables') as $table) {
        // `users` has a global-user carve-out, so only this tenant's own row
        // is asserted absent.
        expect(DB::table($table)->where('id', $rows[$table])->exists())->toBeFalse(
            "[{$table}] returned a row with no tenant context set; RLS did not fail closed."
        );
    }
});
