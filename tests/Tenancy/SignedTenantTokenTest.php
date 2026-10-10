<?php

use App\Tenancy\InvalidTenantTokenException;
use App\Tenancy\Models\Tenant;
use App\Tenancy\SignedTenantToken;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\getJson;

test('a token round-trips its tenant and subject for the right purpose', function () {
    $token = SignedTenantToken::issue('manage_item', 'tenant-1', 'item-9');

    $payload = SignedTenantToken::verify($token, 'manage_item');

    expect($payload['tenant_id'])->toBe('tenant-1')->and($payload['subject'])->toBe('item-9');
    expect($token)->toMatch('/^[A-Za-z0-9_-]+$/');
});

test('a wrong purpose, a tampered token and an expired token all fail with the same exception', function () {
    $token = SignedTenantToken::issue('manage_item', 'tenant-1', 'item-9');
    $expired = SignedTenantToken::issue('manage_item', 'tenant-1', 'item-9', Carbon::now()->subMinute());

    expect(fn () => SignedTenantToken::verify($token, 'other_purpose'))->toThrow(InvalidTenantTokenException::class);
    expect(fn () => SignedTenantToken::verify($token.'x', 'manage_item'))->toThrow(InvalidTenantTokenException::class);
    expect(fn () => SignedTenantToken::verify('garbage', 'manage_item'))->toThrow(InvalidTenantTokenException::class);
    expect(fn () => SignedTenantToken::verify($expired, 'manage_item'))->toThrow(InvalidTenantTokenException::class);
});

test('the token middleware resolves the tenant from a valid token and 404s identically on any failure', function () {
    Route::middleware(['api', 'resolve.tenant.token:manage_item', 'tenant.context'])
        ->get('/api/test-token/{token}', fn (Request $r) => ['tenant_id' => $r->attributes->get('tenant_id')]);

    $tenant = Tenant::factory()->create();

    getJson('/api/test-token/'.SignedTenantToken::issue('manage_item', $tenant->id, 'item-9'))
        ->assertOk()
        ->assertJson(['tenant_id' => $tenant->id]);

    getJson('/api/test-token/'.SignedTenantToken::issue('wrong_purpose', $tenant->id, 'item-9'))
        ->assertStatus(404)
        ->assertJson(['error' => 'INVALID_OR_EXPIRED_TOKEN']);

    getJson('/api/test-token/garbage')->assertStatus(404)->assertJson(['error' => 'INVALID_OR_EXPIRED_TOKEN']);
});
