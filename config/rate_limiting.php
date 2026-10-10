<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login rate limiting
    |--------------------------------------------------------------------------
    |
    | Both login endpoints share the `login` named limiter registered in
    | AppServiceProvider::boot(). Two limits apply together:
    |
    | - per_credential_per_minute: keyed on (lowercased email + IP). Stops a
    |   targeted brute-force against one account while leaving room for a
    |   real user mistyping a password a few times.
    | - per_ip_per_minute: keyed on IP alone. Stops one source spraying many
    |   different emails. Set higher than the per-credential limit.
    |
    | If your end-to-end suite logs in with the same seeded credential many
    | times from one IP, keep these above that suite's real usage.
    |
    */

    'login' => [
        'per_credential_per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_CREDENTIAL_PER_MINUTE', 10),
        'per_ip_per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_IP_PER_MINUTE', 30),
    ],

];
