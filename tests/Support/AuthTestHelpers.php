<?php

use Illuminate\Testing\TestResponse;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withUnencryptedCookie;

/**
 * Laravel's CSRF middleware skips verification unconditionally while the app
 * is running in console (every Pest/PHPUnit run), so a test that wants to
 * prove CSRF is really enforced must switch that shortcut off first. Shared
 * by every Feature test that needs a real, cookie/CSRF-authenticated session.
 */
function disableConsoleCsrfBypass(): void
{
    $property = new ReflectionProperty(app(), 'isRunningInConsole');
    $property->setAccessible(true);
    $property->setValue(app(), false);
}

/**
 * Performs the real GET /sanctum/csrf-cookie -> POST {login path} flow a
 * genuine SPA would perform, then re-captures the fresh session/XSRF
 * cookies login regenerates so the caller can make further authenticated,
 * CSRF-valid requests immediately.
 *
 * @param  array<string, string>  $credentials
 * @return array{0: string, 1: TestResponse} [the fresh XSRF token value, the login response]
 */
function loginAndCaptureXsrf(string $loginPath, array $credentials): array
{
    $csrf = getJson('/sanctum/csrf-cookie', ['Origin' => 'http://localhost']);

    $sessionCookieName = config('session.cookie');
    withUnencryptedCookie($sessionCookieName, $csrf->getCookie($sessionCookieName, false)->getValue());
    withUnencryptedCookie('XSRF-TOKEN', $csrf->getCookie('XSRF-TOKEN', false)->getValue());

    $login = postJson($loginPath, $credentials, [
        'Origin' => 'http://localhost',
        'X-XSRF-TOKEN' => $csrf->getCookie('XSRF-TOKEN', false)->getValue(),
    ]);

    $newXsrf = $login->getCookie('XSRF-TOKEN', false)->getValue();
    withUnencryptedCookie($sessionCookieName, $login->getCookie($sessionCookieName, false)->getValue());
    withUnencryptedCookie('XSRF-TOKEN', $newXsrf);

    return [$newXsrf, $login];
}
