<?php

use App\Models\User;
use App\Tenancy\Concerns\BelongsToTenant;
use App\Tenancy\Scopes\UserTenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * RlsManifestTest guards the database side (a table shipped without RLS).
 * This is the application-side counterpart: a model backed by a
 * tenant-scoped table but missing BelongsToTenant (or, for `users`,
 * UserTenantScope) is invisible to that check, since RLS would still protect
 * it. The gap this closes is application-layer defense in depth and the
 * scope's auto-fill-on-create behaviour.
 *
 * Parameterized over the live model directories and the manifest, so a
 * tenant-scoped model added next month is covered without editing this test.
 */
test('every Eloquent model backed by a tenant-scoped table uses the tenant scope guard', function () {
    $tenantScopedTables = config('tenancy.tenant_scoped_tables');
    $modelFiles = array_merge(glob(app_path('Models/*.php')), glob(app_path('Tenancy/Models/*.php')));

    expect($modelFiles)->not->toBeEmpty();

    $matchedTables = [];

    foreach ($modelFiles as $file) {
        $class = str_replace(['/', '.php'], ['\\', ''], 'App'.substr($file, strlen(app_path())));
        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
            continue;
        }

        $table = (new $class)->getTable();

        if (! in_array($table, $tenantScopedTables, true)) {
            continue;
        }

        $matchedTables[] = $table;

        if ($class === User::class) {
            // `users` mixes tenant and global rows, so it registers the
            // widened UserTenantScope (in TenancyServiceProvider) instead of
            // using BelongsToTenant.
            expect(array_key_exists(UserTenantScope::class, (new User)->getGlobalScopes()))->toBeTrue(
                'App\\Models\\User is backed by the tenant-scoped users table but does not register UserTenantScope.'
            );

            continue;
        }

        expect(array_key_exists(BelongsToTenant::class, class_uses_recursive($class)))->toBeTrue(
            "{$class} is backed by tenant-scoped table [{$table}] but does not use ".BelongsToTenant::class.'.'
        );
    }

    // Every manifest table must be reachable from a model, otherwise this test
    // could pass trivially having checked nothing for a table with no model.
    foreach ($tenantScopedTables as $table) {
        expect(in_array($table, $matchedTables, true))->toBeTrue(
            "No model is backed by manifest table [{$table}]."
        );
    }
});
