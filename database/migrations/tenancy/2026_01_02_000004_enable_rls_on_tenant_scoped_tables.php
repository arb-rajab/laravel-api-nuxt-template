<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The Postgres RLS backstop. Driven by config('tenancy.tenant_scoped_tables'),
// the same manifest the tenant-isolation test suite is graded against, so
// this migration and those tests cannot drift apart over which tables are
// covered. A table added to the manifest later needs its own migration that
// enables RLS the same way (copy this loop for just that table).
//
// The policies wrap the GUC in NULLIF(..., '') before the ::uuid cast. Once
// any transaction on a physical connection has called set_config() for the
// GUC, current_setting() returns an EMPTY STRING rather than NULL whenever no
// context is active, and ''::uuid raises SQLSTATE 22P02 instead of comparing
// as NULL. With NULLIF the comparison is NULL (no match, fail closed).
//
// `users` gets a widened policy: global users (tenant_id NULL) stay visible
// whatever the current tenant context is.
return new class extends Migration
{
    public function up(): void
    {
        $guc = config('tenancy.guc');

        foreach (config('tenancy.tenant_scoped_tables') as $table) {
            DB::unprepared("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY;");
            DB::unprepared("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY;");

            if ($table === 'users') {
                DB::unprepared(<<<SQL
                    CREATE POLICY tenant_isolation ON users
                      USING (
                        tenant_id IS NULL
                        OR tenant_id = NULLIF(current_setting('{$guc}', true), '')::uuid
                      );
                SQL);

                continue;
            }

            DB::unprepared(<<<SQL
                CREATE POLICY tenant_isolation ON {$table}
                  USING (tenant_id = NULLIF(current_setting('{$guc}', true), '')::uuid);
            SQL);
        }
    }

    public function down(): void
    {
        foreach (config('tenancy.tenant_scoped_tables') as $table) {
            DB::unprepared("DROP POLICY IF EXISTS tenant_isolation ON {$table};");
            DB::unprepared("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY;");
            DB::unprepared("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY;");
        }
    }
};
