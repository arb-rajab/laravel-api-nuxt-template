<?php

namespace App\Tenancy;

use App\Models\User;
use App\Tenancy\Http\Middleware\AuthenticateTenantUser;
use App\Tenancy\Http\Middleware\AuthenticateTenantUserWithoutTransactionWrap;
use App\Tenancy\Http\Middleware\ResolveTenantFromSignedToken;
use App\Tenancy\Http\Middleware\ResolveTenantFromSlug;
use App\Tenancy\Http\Middleware\SetTenantContext;
use App\Tenancy\Scopes\UserTenantScope;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * OPTIONAL MODULE: multi-tenancy with two enforcement layers (Eloquent
 * global scopes + Postgres row level security). To remove it, delete this
 * provider's line in bootstrap/providers.php and these paths:
 * app/Tenancy, config/tenancy.php, routes/tenancy.php,
 * database/migrations/tenancy, database/factories/Tenancy, tests/Tenancy,
 * and the tenant_id parts of tests/Support. Everything outside those paths
 * works without tenancy.
 *
 * Middleware aliases:
 *   resolve.tenant.slug      route {slug} -> tenant_id on the request
 *   resolve.tenant.token:p   signed {token} (purpose p) -> tenant_id
 *   tenant.context           wraps the rest of the request in TenantContext
 *   auth.tenant              session auth for tenant users (whole request in a transaction)
 *   auth.tenant.external     same, but no transaction held across the controller
 */
class TenancyServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(database_path('migrations/tenancy'));

        $router->aliasMiddleware('resolve.tenant.slug', ResolveTenantFromSlug::class);
        $router->aliasMiddleware('resolve.tenant.token', ResolveTenantFromSignedToken::class);
        $router->aliasMiddleware('tenant.context', SetTenantContext::class);
        $router->aliasMiddleware('auth.tenant', AuthenticateTenantUser::class);
        $router->aliasMiddleware('auth.tenant.external', AuthenticateTenantUserWithoutTransactionWrap::class);

        // `users` holds tenant users and global users (tenant_id NULL), so it
        // takes the widened scope rather than the BelongsToTenant trait.
        User::addGlobalScope(new UserTenantScope);

        User::creating(function (User $user) {
            if ($user->getAttribute('tenant_id') === null && CurrentTenant::id() !== null) {
                $user->setAttribute('tenant_id', CurrentTenant::id());
            }
        });

        Route::middleware('api')->prefix('api')->group(base_path('routes/tenancy.php'));
    }
}
