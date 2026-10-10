<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A NULL tenant_id marks a global user (for example a platform administrator).
// Emails are unique per tenant, and unique among global users.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->dropUnique('users_email_unique');
        });

        DB::unprepared(<<<'SQL'
            CREATE UNIQUE INDEX users_tenant_email_unique ON users (tenant_id, email);
            CREATE UNIQUE INDEX users_global_email_unique ON users (email) WHERE tenant_id IS NULL;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP INDEX IF EXISTS users_tenant_email_unique; DROP INDEX IF EXISTS users_global_email_unique;');

        Schema::table('users', function ($table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
            $table->unique('email');
        });
    }
};
