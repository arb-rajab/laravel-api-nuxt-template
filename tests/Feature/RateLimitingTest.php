<?php

use App\Models\User;

use function Pest\Laravel\postJson;

/*
 * CACHE_STORE=array (.env.testing) backs Laravel's cache-based RateLimiter,
 * and each Pest test boots a fresh application (a fresh, empty array cache),
 * so asserting the Nth request in a tight loop exercises the real limiter
 * without cross-test bleed.
 */

test('the login route 429s after the per-credential limit, even with correct credentials', function () {
    User::factory()->withPassword('correct-password')->create(['email' => 'user@example.test']);

    $limit = config('rate_limiting.login.per_credential_per_minute');

    for ($i = 0; $i < $limit; $i++) {
        postJson('/api/login', ['email' => 'user@example.test', 'password' => 'wrong'])->assertStatus(401);
    }

    $response = postJson('/api/login', ['email' => 'user@example.test', 'password' => 'correct-password']);

    $response->assertStatus(429)->assertJson(['error' => 'TOO_MANY_REQUESTS']);
    expect($response->headers->has('Retry-After'))->toBeTrue();
});

test('exhausting one credential does not block a different credential from the same IP', function () {
    User::factory()->withPassword('correct-password')->create(['email' => 'user-b@example.test']);

    $limit = config('rate_limiting.login.per_credential_per_minute');

    for ($i = 0; $i < $limit; $i++) {
        postJson('/api/login', ['email' => 'user-a@example.test', 'password' => 'wrong'])->assertStatus(401);
    }

    postJson('/api/login', ['email' => 'user-a@example.test', 'password' => 'wrong'])->assertStatus(429);
    postJson('/api/login', ['email' => 'user-b@example.test', 'password' => 'correct-password'], ['Origin' => 'http://localhost'])->assertOk();
});

test('the per-IP limit stops one source spraying many different emails', function () {
    $perCredential = config('rate_limiting.login.per_credential_per_minute');
    $perIp = config('rate_limiting.login.per_ip_per_minute');

    expect($perIp)->toBeGreaterThan($perCredential);

    for ($i = 0; $i < $perIp; $i++) {
        postJson('/api/login', ['email' => "spray-{$i}@example.test", 'password' => 'wrong'])->assertStatus(401);
    }

    postJson('/api/login', ['email' => 'one-more@example.test', 'password' => 'wrong'])
        ->assertStatus(429)
        ->assertJson(['error' => 'TOO_MANY_REQUESTS']);
});
