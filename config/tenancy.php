<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant-scoped tables
    |--------------------------------------------------------------------------
    |
    | The manifest the tenant-isolation suite (tests/Tenancy) is graded
    | against. Every table listed here must carry a `tenant_id` column and an
    | enforced Postgres RLS policy named `tenant_isolation` (see the RLS
    | migration in database/migrations/tenancy). The manifest-completeness
    | test fails if a table has a `tenant_id` column but is not listed; the
    | RLS test fails if a listed table has no policy. Add every new
    | tenant-scoped table here AND to the RLS migration (or a new one).
    |
    | `tenants` is deliberately absent: it is the tenancy boundary itself.
    | `users` is listed, but its policy also lets rows with a NULL tenant_id
    | (global users) through.
    |
    */

    'tenant_scoped_tables' => [
        'users',
        'notes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant context GUC
    |--------------------------------------------------------------------------
    |
    | The Postgres session variable every RLS policy is keyed on. Set only
    | via a parameterized set_config(..., true) call as the first statement
    | inside an explicit transaction (App\Tenancy\TenantContext). Never a
    | session-level SET.
    |
    */

    'guc' => 'app.current_tenant_id',

];
