<?php

use App\Tenancy\Http\Controllers\NoteController;
use App\Tenancy\Http\Controllers\TenantAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenancy module routes (prefix /api is applied by TenancyServiceProvider)
|--------------------------------------------------------------------------
|
| Public tenant routes: slug -> tenant id -> TenantContext.
| Tenant user routes:   auth.tenant (session -> tenant id -> TenantContext -> auth).
|
*/

Route::prefix('tenants/{slug}')
    ->middleware(['resolve.tenant.slug', 'tenant.context'])
    ->group(function () {
        Route::post('login', [TenantAuthController::class, 'login'])->middleware('throttle:login');

        // Add public, tenant-scoped reads here.
    });

Route::prefix('tenant')
    ->middleware('auth.tenant')
    ->group(function () {
        Route::get('me', [TenantAuthController::class, 'me']);

        Route::get('notes', [NoteController::class, 'index']);

        Route::middleware('role:admin')->group(function () {
            Route::post('notes', [NoteController::class, 'store']);
            Route::delete('notes/{id}', [NoteController::class, 'destroy']);
        });
    });

// A route whose controller calls an external service mid-request must not
// hold the auth.tenant transaction open. Use auth.tenant.external, place the
// route outside the group above, and open short TenantContext::run() calls in
// the controller around its database work:
//
// Route::middleware(['auth.tenant.external', 'role:admin'])
//     ->post('tenant/some-external-action', [SomeController::class, 'store']);
