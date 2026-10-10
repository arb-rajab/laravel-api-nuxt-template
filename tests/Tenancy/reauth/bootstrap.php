<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Boots the app inside a standalone, real OS process (the probes in this
 * folder are run with proc_open, not inside Pest). SESSION_DRIVER is forced
 * to 'file', overriding .env.testing's 'array' default: 'array' is
 * process-local memory that dies with the process, which is right for every
 * other test and wrong here, since a session written by one real process
 * (login) must be readable by a second, genuinely separate one, the way it
 * would be against a real running `php artisan serve`.
 */
function bootReauthProbeApp(): Application
{
    // putenv() alone is not enough: this script is a child of Pest's own PHP
    // process, which already loaded .env.testing's SESSION_DRIVER=array into
    // its $_SERVER/$_ENV, and those are inherited by the child before this
    // file runs. Dotenv's immutable repository checks $_SERVER/$_ENV, not
    // just getenv(), so all three must be set for the override to stick.
    foreach ([['APP_ENV', 'testing'], ['SESSION_DRIVER', 'file']] as [$key, $value]) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    require __DIR__.'/../../../vendor/autoload.php';

    /** @var Application $app */
    $app = require __DIR__.'/../../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    return $app;
}

/**
 * @return array{0: ?string, 1: ?string} [session cookie value, XSRF-TOKEN
 *                                       cookie value — both still in their raw, encrypted-as-sent-over-the-wire
 *                                       form, exactly as a browser would hold and replay them]
 */
function extractReauthCookies(Response $response, string $sessionCookieName): array
{
    $session = null;
    $xsrf = null;

    foreach ($response->headers->getCookies() as $cookie) {
        if ($cookie->getName() === $sessionCookieName) {
            $session = $cookie->getValue();
        } elseif ($cookie->getName() === 'XSRF-TOKEN') {
            $xsrf = $cookie->getValue();
        }
    }

    return [$session, $xsrf];
}
