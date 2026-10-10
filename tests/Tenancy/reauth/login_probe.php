<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/bootstrap.php';

/** @var array<int, string> $arguments */
$arguments = $_SERVER['argv'];
[, $slug, $email, $password] = $arguments;

$app = bootReauthProbeApp();
$kernel = $app->make(Kernel::class);
$sessionCookieName = config('session.cookie');

// Step 1: GET /sanctum/csrf-cookie with no cookies yet, a fresh browser's
// first request, exactly as useApi.ts's ensureCsrfCookie() performs it.
$csrfRequest = Request::create('/sanctum/csrf-cookie', 'GET', server: [
    'HTTP_ORIGIN' => 'http://localhost',
    'HTTP_ACCEPT' => 'application/json',
]);
$csrfResponse = $kernel->handle($csrfRequest);
$kernel->terminate($csrfRequest, $csrfResponse);

[$preLoginSession, $preLoginXsrf] = extractReauthCookies($csrfResponse, $sessionCookieName);

if ($csrfResponse->getStatusCode() !== 204 && $csrfResponse->getStatusCode() !== 200) {
    fwrite(STDERR, "csrf-cookie request failed: {$csrfResponse->getStatusCode()}\n");
    exit(1);
}

// Step 2: POST login with the cookies step 1 just set (the real two-request
// CSRF dance). The controller regenerates the session on success, so the
// session id set back differs from the pre-login one; that regenerated id is
// what a returning browser (and the second process) actually holds.
$loginRequest = Request::create(
    "/api/tenants/{$slug}/login",
    'POST',
    [],
    [$sessionCookieName => $preLoginSession, 'XSRF-TOKEN' => $preLoginXsrf],
    [],
    [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_ORIGIN' => 'http://localhost',
        'HTTP_X_XSRF_TOKEN' => $preLoginXsrf,
    ],
    json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
);
$loginResponse = $kernel->handle($loginRequest);
$kernel->terminate($loginRequest, $loginResponse);

[$postLoginSession, $postLoginXsrf] = extractReauthCookies($loginResponse, $sessionCookieName);

echo json_encode([
    'login_status' => $loginResponse->getStatusCode(),
    'login_body' => $loginResponse->getContent(),
    'session_cookie_name' => $sessionCookieName,
    'session_value' => $postLoginSession,
    'xsrf_value' => $postLoginXsrf,
]);
