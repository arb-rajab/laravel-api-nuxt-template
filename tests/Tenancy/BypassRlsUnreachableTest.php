<?php

use Illuminate\Support\Facades\DB;

/**
 * The migrator role (BYPASSRLS) is used only for migrations, seeders and
 * backfills, never by a running request or queue worker. Checks both halves
 * of that claim: the runtime credentials are not the migrator's, and a live
 * connection using them confirms BYPASSRLS is false for the role in use.
 */
test('the runtime database credentials are not the migrator\'s', function () {
    $runtimeUsername = config('database.connections.'.config('database.default').'.username');
    $migratorUsername = config('database.connections.pgsql_migrator.username');

    expect($runtimeUsername)->not->toBeNull();
    expect($runtimeUsername)->not->toBe($migratorUsername);
});

test('the runtime connection\'s actual database role does not have BYPASSRLS', function () {
    $row = DB::selectOne('select rolbypassrls from pg_roles where rolname = current_user');

    expect((bool) $row->rolbypassrls)->toBeFalse(
        'The application runtime connection is using a role with BYPASSRLS; this must never be true outside the migrator\'s offline use.'
    );
});

test('the migrator connection is a different role that does hold BYPASSRLS', function () {
    // Confirms the test above actually discriminates between the two roles,
    // rather than passing because neither has BYPASSRLS (a misconfigured
    // local Postgres, for example).
    $row = DB::connection('pgsql_migrator')->selectOne('select current_user as role, rolbypassrls from pg_roles where rolname = current_user');

    expect($row->role)->toBe(config('database.connections.pgsql_migrator.username'));
    expect((bool) $row->rolbypassrls)->toBeTrue();
});
