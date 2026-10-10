<?php

use App\Models\User;
use App\Tenancy\Models\Note;
use App\Tenancy\Models\Tenant;
use App\Tenancy\TenantContext;
use Tests\Tenancy\Support\TenantFixture;

/*
 * "Cross-tenant ID guessing" at the model layer: given tenant A's valid
 * context, fetch a real row belonging to tenant B by its actual id, exactly
 * as an authorization bug or a forgotten scope would.
 */

test('every manifest table refuses to resolve another tenant\'s row by its real id, via Eloquent', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    TenantContext::run($tenantA->id, fn () => TenantFixture::seedOneRowPerTable($tenantA));
    $rowsB = TenantContext::run($tenantB->id, fn () => TenantFixture::seedOneRowPerTable($tenantB));

    $modelsByTable = [
        'users' => User::class,
        'notes' => Note::class,
    ];

    expect(array_keys($modelsByTable))->toEqualCanonicalizing(config('tenancy.tenant_scoped_tables'));

    TenantContext::run($tenantA->id, function () use ($modelsByTable, $rowsB) {
        foreach ($modelsByTable as $table => $modelClass) {
            expect($modelClass::find($rowsB[$table]))
                ->toBeNull("Tenant A resolved tenant B's real [{$table}] row by id via {$modelClass}::find().");
        }
    });
});

test('a model created inside a tenant context is stamped with that tenant automatically', function () {
    $tenantA = Tenant::factory()->create();

    $note = TenantContext::run($tenantA->id, fn () => Note::query()->create(['title' => 'Auto-stamped']));

    expect($note->tenant_id)->toBe($tenantA->id);
});

test('a global user (no tenant) stays visible from inside any tenant context', function () {
    $tenant = Tenant::factory()->create();
    $global = User::factory()->create(['tenant_id' => null, 'email' => 'global@example.test']);

    $found = TenantContext::run($tenant->id, fn () => User::query()->where('email', 'global@example.test')->first());

    expect($found?->id)->toBe($global->id);
});
