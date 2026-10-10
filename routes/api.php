<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Auth is Sanctum SPA cookie mode: the client first calls
| GET /sanctum/csrf-cookie, then echoes the XSRF-TOKEN cookie back as the
| X-XSRF-TOKEN header on every state-changing request.
|
| Routes added by the optional modules live in their own route files
| (routes/tenancy.php, routes/rabbitmq.php), loaded by the module's service
| provider.
|
*/

Route::get('ready', [HealthController::class, 'ready']);

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('logout', [AuthController::class, 'logout']);

Route::middleware('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);

    // Example of a role-gated route group:
    // Route::middleware('role:admin')->prefix('admin')->group(function () { ... });
});
