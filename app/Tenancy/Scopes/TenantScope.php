<?php

namespace App\Tenancy\Scopes;

use App\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * First enforcement layer (Eloquent global scope); the second, independent
 * layer is the Postgres RLS policy on the same table. Mirrors RLS's
 * fail-closed behaviour: with no tenant context set it excludes every row
 * rather than returning everything. Neither layer may fail open.
 *
 * @implements Scope<Model>
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = CurrentTenant::id();

        if ($tenantId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), '=', $tenantId);
    }
}
