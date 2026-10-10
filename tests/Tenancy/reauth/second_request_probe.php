<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/bootstrap.php';

/** @var array<int, string> $arguments */
$arguments = $_SERVER['argv'];
[, $sessionCookieName, $sessionValue, $xsrfValue] = $arguments;

// A brand new app instance in a brand new OS process: its own fresh database
// connection that no TenantContext::run() call has ever touched. The auth
// guard's own user-lookup query is the FIRST query this connection runs, with
// no tenant GUC set by anything earlier on it. Pest's shared in-process
// connection can never reproduce that.
$app = bootReauthProbeApp();
$kernel = $app->make(Kernel::class);

$request = Request::create(
    '/api/tenant/me',
    'GET',
    [],
    [$sessionCookieName => $sessionValue, 'XSRF-TOKEN' => $xsrfValue],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_ORIGIN' => 'http://localhost',
    ],
);

$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo $response->getStatusCode().'|'.$response->getContent();
