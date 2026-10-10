<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA (stateful/cookie) mode. This is the only thing Sanctum
        // contributes: authentication itself is Laravel's ordinary `web`
        // session guard. EnsureFrontendRequestsAreStateful gives an API
        // request from a configured stateful origin (config/sanctum.php) the
        // session + CSRF middleware group, so CSRF verification really runs.
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // This app is API-only: there is no `login` web route to redirect an
        // unauthenticated request to. Without this, a request that does not
        // send `Accept: application/json` crashes with a 500
        // RouteNotFoundException instead of a clean 401.
        $middleware->redirectGuestsTo(fn () => null);

        // Behind a reverse proxy, $request->ip() would otherwise be the
        // proxy's address for every request, collapsing every IP-keyed
        // control (the login rate limiter) into one shared bucket. List only
        // the proxy address(es)/CIDR(s) you actually sit behind, never '*'.
        $trustedProxies = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRUSTED_PROXIES', ''))
        )));

        if ($trustedProxies !== []) {
            $middleware->trustProxies(
                at: $trustedProxies,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO,
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ApiExceptionRenderer::register($exceptions);
    })->create();
