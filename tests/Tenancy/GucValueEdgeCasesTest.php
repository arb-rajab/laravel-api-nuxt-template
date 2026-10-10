<?php

use App\Tenancy\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Tenancy\Support\TenantFixture;

/*
 * The cases the application handles differently: a malformed GUC value, a
 * well-formed but non-existent tenant, and a well-formed, real, but
 * different tenant.
 */

test('a non-uuid GUC value throws a QueryException rather than returning zero rows', function () {
    $tenant = Tenant::factory()->create();
    TenantContext::run($tenant->id, fn () => TenantFixture::seedOneRowPerTable($tenant));

    DB::statement('select set_config(?, ?, true)', [config('tenancy.guc'), 'not-a-uuid-at-all']);

    $attempt = fn () => DB::table('notes')->get();

    expect($attempt)->toThrow(QueryException::class);
});

test('a well-formed GUC value for a tenant that does not exist returns zero rows, not an error', function () {
    $tenant = Tenant::factory()->create();
    $rows = TenantContext::run($tenant->id, fn () => TenantFixture::seedOneRowPerTable($tenant));

    DB::statement('select set_config(?, ?, true)', [config('tenancy.guc'), (string) Str::uuid()]);

    foreach (config('tenancy.tenant_scoped_tables') as $table) {
        expect(DB::table($table)->where('id', $rows[$table])->exists())->toBeFalse(
            "[{$table}] returned a row under a well-formed but non-existent tenant id."
        );
    }
});

test('a well-formed GUC value for a different, real tenant returns none of the querying tenant\'s rows', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $rowsA = TenantContext::run($tenantA->id, fn () => TenantFixture::seedOneRowPerTable($tenantA));
    TenantContext::run($tenantB->id, fn () => TenantFixture::seedOneRowPerTable($tenantB));

    DB::statement('select set_config(?, ?, true)', [config('tenancy.guc'), $tenantB->id]);

    foreach (config('tenancy.tenant_scoped_tables') as $table) {
        expect(DB::table($table)->where('id', $rowsA[$table])->exists())->toBeFalse(
            "[{$table}] leaked tenant A's row while context was set to real tenant B."
        );
    }
});
