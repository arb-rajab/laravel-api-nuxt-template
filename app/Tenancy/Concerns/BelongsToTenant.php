<?php

namespace App\Tenancy\Concerns;

use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use App\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apply to every tenant-scoped model. Adds the application-layer global scope
 * and fills tenant_id from the active TenantContext on create, so call sites
 * are not each responsible for remembering to set it. (`users` is the one
 * exception: it uses UserTenantScope, registered in TenancyServiceProvider.)
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if ($model->tenant_id === null && CurrentTenant::id() !== null) {
                $model->tenant_id = CurrentTenant::id();
            }
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
