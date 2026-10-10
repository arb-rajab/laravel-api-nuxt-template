<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Two limits apply together (see config/rate_limiting.php): one keyed
        // on email + IP against targeted brute force, one keyed on IP alone
        // against spraying many emails. Backed by the default cache store.
        RateLimiter::for('login', function (Request $request) {
            $credentialKey = mb_strtolower((string) $request->input('email')).'|'.$request->ip();

            return [
                Limit::perMinute(config('rate_limiting.login.per_credential_per_minute'))->by($credentialKey),
                Limit::perMinute(config('rate_limiting.login.per_ip_per_minute'))->by((string) $request->ip()),
            ];
        });
    }
}
