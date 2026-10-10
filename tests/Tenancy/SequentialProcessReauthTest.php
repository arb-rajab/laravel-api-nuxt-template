<?php

/**
 * Regression guard for the bug that AuthenticateTenantUser exists to prevent:
 * a returning tenant-user session failing to re-authenticate because the auth
 * guard's user lookup ran before any tenant context existed (RLS hid the row).
 *
 * Every other "log in, then make an authenticated request" test runs inside
 * one Pest test method: one in-process HTTP client, one database
 * connection/transaction. In that setup any earlier TenantContext::run() call
 * (even the test's own fixture setup) leaves the DB-level tenant GUC set for
 * the rest of the outer transaction, so the guard's lookup never runs against
 * a genuinely GUC-less connection and the bug is invisible.
 *
 * This test reproduces the real condition with three separate `php` OS
 * processes, each with its own app instance and database connection:
 *
 *   1. reauth/setup.php:                creates and commits a tenant + admin.
 *   2. reauth/login_probe.php:          the real csrf-cookie -> login round
 *                                       trip; captures the post-login cookies.
 *   3. reauth/second_request_probe.php: a NEW process presenting only those
 *                                       cookies, as a returning browser would.
 *
 * If the middleware ordering ever regresses, step 3 returns 401 and this test
 * fails, where every in-process test would stay green.
 */
function runReauthProbe(string $script, array $args): array
{
    $process = proc_open(
        [PHP_BINARY, __DIR__.'/reauth/'.$script, ...$args],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname(__DIR__, 2),
    );

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return [$stdout, $stderr];
}

test('a returning tenant session, presented to a genuinely separate process and connection, re-authenticates', function () {
    [$setupOut, $setupErr] = runReauthProbe('setup.php', []);
    expect($setupErr)->toBe('', "setup probe stderr: {$setupErr}\nstdout: {$setupOut}");

    $setup = json_decode($setupOut, true);
    expect($setup['slug'] ?? null)->toBeString();

    [$loginOut, $loginErr] = runReauthProbe('login_probe.php', [
        $setup['slug'], 'admin@example.test', 'correct-password',
    ]);
    expect($loginErr)->toBe('', "login probe stderr: {$loginErr}\nstdout: {$loginOut}");

    $login = json_decode($loginOut, true);
    expect($login['login_status'] ?? null)->toBe(200, "the login probe's own login did not succeed: {$loginOut}");
    expect($login['session_value'] ?? null)->toBeString();
    expect($login['xsrf_value'] ?? null)->toBeString();

    [$secondOut, $secondErr] = runReauthProbe('second_request_probe.php', [
        $login['session_cookie_name'], $login['session_value'], $login['xsrf_value'],
    ]);
    expect($secondErr)->toBe('', "second-request probe stderr: {$secondErr}\nstdout: {$secondOut}");

    [$status] = explode('|', $secondOut, 2) + [null];

    expect((int) $status)->toBe(
        200,
        "a separate process/connection failed to re-authenticate a returning tenant session: {$secondOut}",
    );
});
