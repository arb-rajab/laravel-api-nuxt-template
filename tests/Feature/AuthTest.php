<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/*
 * Sanctum SPA (stateful/cookie) auth, one shared `web` guard, role-based
 * authorization at the app layer. See tests/Support/AuthTestHelpers.php for
 * why disableConsoleCsrfBypass() exists (Laravel skips CSRF verification
 * during any Pest/PHPUnit run unless told otherwise).
 */

const FRONTEND_ORIGIN = ['Origin' => 'http://localhost'];

test('a login request from the frontend origin without a CSRF token is rejected with 419', function () {
    disableConsoleCsrfBypass();
    User::factory()->withPassword('correct-password')->create(['email' => 'user@example.test']);

    postJson('/api/login', ['email' => 'user@example.test', 'password' => 'correct-password'], FRONTEND_ORIGIN)
        ->assertStatus(419)
        ->assertJsonPath('error', 'CSRF_TOKEN_MISMATCH');
});

test('a login with a valid CSRF token succeeds and the session authenticates later requests', function () {
    disableConsoleCsrfBypass();
    User::factory()->withPassword('correct-password')->create(['email' => 'user@example.test', 'name' => 'Una User']);

    [$xsrf, $login] = loginAndCaptureXsrf('/api/login', ['email' => 'user@example.test', 'password' => 'correct-password']);

    $login->assertOk()->assertJsonPath('user.email', 'user@example.test');
    $login->assertJsonMissingPath('user.password');

    getJson('/api/me', FRONTEND_ORIGIN + ['X-XSRF-TOKEN' => $xsrf])
        ->assertOk()
        ->assertJsonPath('user.name', 'Una User')
        ->assertJsonPath('user.role', 'member');
});

test('a wrong password and an unknown email produce the identical response', function () {
    User::factory()->withPassword('correct-password')->create(['email' => 'user@example.test']);

    $wrongPassword = postJson('/api/login', ['email' => 'user@example.test', 'password' => 'nope'])
        ->assertStatus(401)->json();
    $unknownEmail = postJson('/api/login', ['email' => 'nobody@example.test', 'password' => 'nope'])
        ->assertStatus(401)->json();

    expect($wrongPassword)->toBe(['error' => 'INVALID_CREDENTIALS'])->and($unknownEmail)->toBe($wrongPassword);
});

test('a login from a non-stateful origin is refused with a clear error instead of a server error', function () {
    User::factory()->withPassword('correct-password')->create(['email' => 'user@example.test']);

    postJson('/api/login', ['email' => 'user@example.test', 'password' => 'correct-password'])
        ->assertStatus(400)
        ->assertExactJson(['error' => 'STATEFUL_ORIGIN_REQUIRED']);
});

test('logout ends the session', function () {
    disableConsoleCsrfBypass();
    User::factory()->withPassword('correct-password')->create(['email' => 'user@example.test']);

    [$xsrf] = loginAndCaptureXsrf('/api/login', ['email' => 'user@example.test', 'password' => 'correct-password']);

    postJson('/api/logout', [], FRONTEND_ORIGIN + ['X-XSRF-TOKEN' => $xsrf])->assertOk();

    getJson('/api/me', FRONTEND_ORIGIN)->assertStatus(401);
});

test('role middleware lets a matching role through and rejects the rest with 403', function () {
    Route::middleware(['api', 'auth', 'role:admin'])->get('/api/test-admin-only', fn () => ['ok' => true]);

    $this->actingAs(User::factory()->create())->getJson('/api/test-admin-only')
        ->assertStatus(403)
        ->assertExactJson(['error' => 'FORBIDDEN']);

    $this->actingAs(User::factory()->admin()->create())->getJson('/api/test-admin-only')
        ->assertOk()
        ->assertJson(['ok' => true]);
});
