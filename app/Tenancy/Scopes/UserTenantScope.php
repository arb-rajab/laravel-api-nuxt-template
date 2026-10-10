<?php

namespace App\Tenancy\Scopes;

use App\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * `users` mixes tenant users with global users (tenant_id NULL), which must
 * stay visible whatever the current tenant context. Mirrors the widened RLS
 * policy on `users` in the RLS migration. Every other model uses the plain
 * TenantScope via the BelongsToTenant trait.
 *
 * @implements Scope<Model>
 */
class UserTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = CurrentTenant::id();

        if ($tenantId === null) {
            $builder->whereNull($model->qualifyColumn('tenant_id'));

            return;
        }

        $builder->where(function (Builder $query) use ($model, $tenantId) {
            $query->whereNull($model->qualifyColumn('tenant_id'))
                ->orWhere($model->qualifyColumn('tenant_id'), '=', $tenantId);
        });
    }
}
