<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Wraps Laravel's RefreshDatabase so its one-time migrate:fresh runs through
 * the `pgsql_migrator` connection instead of the application's runtime
 * connection. The runtime role deliberately does not own the tables and has
 * no CREATE/DROP privilege on them, so RefreshDatabase's default
 * migrateFreshUsing() would fail with "must be owner of table".
 *
 * Only the one-time schema setup uses the migrator; each test's own
 * transaction still runs on config('database.default'), the same
 * least-privilege connection production code uses.
 */
trait RefreshesDatabaseAsMigrator
{
    use RefreshDatabase {
        migrateFreshUsing as protected baseMigrateFreshUsing;
    }

    protected function migrateFreshUsing(): array
    {
        return array_merge($this->baseMigrateFreshUsing(), [
            '--database' => 'pgsql_migrator',
        ]);
    }
}
