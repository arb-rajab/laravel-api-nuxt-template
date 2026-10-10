<?php

use App\Models\User;
use App\Tenancy\Models\Note;
use App\Tenancy\Models\Tenant;
use App\Tenancy\TenantContext;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function tenantUser(Tenant $tenant, string $email, string $password, bool $admin = false): User
{
    return TenantContext::run($tenant->id, fn () => User::factory()->withPassword($password)->create([
        'tenant_id' => $tenant->id,
        'email' => $email,
        'role' => $admin ? User::ROLE_ADMIN : User::ROLE_MEMBER,
    ]));
}

const TENANT_ORIGIN = ['Origin' => 'http://localhost'];

test('a tenant login needs a valid CSRF token and then authenticates tenant routes', function () {
    disableConsoleCsrfBypass();

    $tenant = Tenant::factory()->create();
    tenantUser($tenant, 'admin@example.test', 'correct-password', admin: true);

    postJson("/api/tenants/{$tenant->slug}/login", [
        'email' => 'admin@example.test', 'password' => 'correct-password',
    ], TENANT_ORIGIN)->assertStatus(419);

    [$xsrf, $login] = loginAndCaptureXsrf("/api/tenants/{$tenant->slug}/login", [
        'email' => 'admin@example.test', 'password' => 'correct-password',
    ]);

    $login->assertOk()->assertJsonPath('user.tenant_id', $tenant->id);

    getJson('/api/tenant/me', TENANT_ORIGIN + ['X-XSRF-TOKEN' => $xsrf])
        ->assertOk()
        ->assertJsonPath('user.email', 'admin@example.test');
});

test('an unauthenticated request to a tenant route is rejected with 401', function () {
    getJson('/api/tenant/notes', TENANT_ORIGIN)->assertStatus(401);
});

test('an unknown tenant slug is a NOT_FOUND, not a distinguishable error', function () {
    postJson('/api/tenants/no-such-tenant/login', ['email' => 'a@example.test', 'password' => 'x'])
        ->assertStatus(404)
        ->assertExactJson(['error' => 'NOT_FOUND']);
});

test('a user cannot log in through another tenant\'s login route', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    tenantUser($tenantA, 'admin@example.test', 'correct-password');

    postJson("/api/tenants/{$tenantB->slug}/login", ['email' => 'admin@example.test', 'password' => 'correct-password'])
        ->assertStatus(401)
        ->assertExactJson(['error' => 'INVALID_CREDENTIALS']);
});

test('a global user cannot sign in through a tenant login route', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->withPassword('correct-password')->create(['tenant_id' => null, 'email' => 'global@example.test']);

    postJson("/api/tenants/{$tenant->slug}/login", ['email' => 'global@example.test', 'password' => 'correct-password'])
        ->assertStatus(401);
});

test('tenant notes are isolated per tenant over HTTP, and writes need the admin role', function () {
    disableConsoleCsrfBypass();

    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    tenantUser($tenantA, 'admin-a@example.test', 'correct-password', admin: true);
    TenantContext::run($tenantB->id, fn () => Note::factory()->create(['tenant_id' => $tenantB->id, 'title' => 'Tenant B secret']));

    [$xsrf] = loginAndCaptureXsrf("/api/tenants/{$tenantA->slug}/login", [
        'email' => 'admin-a@example.test', 'password' => 'correct-password',
    ]);
    $headers = TENANT_ORIGIN + ['X-XSRF-TOKEN' => $xsrf];

    postJson('/api/tenant/notes', ['title' => 'Tenant A note', 'tenant_id' => $tenantB->id], $headers)
        ->assertCreated()
        ->assertJsonPath('tenant_id', $tenantA->id);

    $titles = collect(getJson('/api/tenant/notes', $headers)->assertOk()->json('notes'))->pluck('title');

    expect($titles->all())->toBe(['Tenant A note']);
});

test('a member cannot create notes', function () {
    disableConsoleCsrfBypass();

    $tenant = Tenant::factory()->create();
    tenantUser($tenant, 'member@example.test', 'correct-password');

    [$xsrf] = loginAndCaptureXsrf("/api/tenants/{$tenant->slug}/login", [
        'email' => 'member@example.test', 'password' => 'correct-password',
    ]);

    postJson('/api/tenant/notes', ['title' => 'Nope'], TENANT_ORIGIN + ['X-XSRF-TOKEN' => $xsrf])
        ->assertStatus(403)
        ->assertExactJson(['error' => 'FORBIDDEN']);
});
