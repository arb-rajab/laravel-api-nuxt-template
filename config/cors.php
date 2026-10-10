<?php

return [

    // Sanctum SPA cookie auth requires the browser to receive and send the
    // session/XSRF cookies cross-origin (the Nuxt frontend is a different
    // origin from this API in dev and production). That only works with an
    // explicit origin allowlist and supports_credentials = true. A wildcard
    // origin is rejected by browsers when credentials are involved.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('FRONTEND_URLS', 'http://localhost:3000'))),

    'allowed_origins_patterns' => [],

    // Must be an explicit list, not ['*']: browsers treat '*' literally for
    // Access-Control-Allow-Headers once credentials are involved, and block
    // the request at preflight with no server-side log. List every header
    // the frontend actually sends.
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
