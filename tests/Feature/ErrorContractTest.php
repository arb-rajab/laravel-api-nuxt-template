<?php

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/*
 * The API error contract documented on App\Exceptions\ApiExceptionRenderer:
 * every error is {"error": "CODE"} (plus "fields" for validation), never
 * Laravel's default {"message": ...} shapes.
 */

test('a validation failure renders VALIDATION_FAILED with the offending fields', function () {
    postJson('/api/login', [])
        ->assertStatus(422)
        ->assertJsonPath('error', 'VALIDATION_FAILED')
        ->assertJsonStructure(['fields' => ['email', 'password']]);
});

test('an unmatched api route renders NOT_FOUND', function () {
    getJson('/api/this-route-does-not-exist')
        ->assertStatus(404)
        ->assertExactJson(['error' => 'NOT_FOUND']);
});

test('an unauthenticated request to an auth-gated route renders UNAUTHENTICATED, even without an Accept header', function () {
    getJson('/api/me')->assertStatus(401)->assertExactJson(['error' => 'UNAUTHENTICATED']);

    // A bare client that does not send Accept: application/json must get a
    // clean 401 too, not a 500 from a missing `login` route.
    $this->get('/api/me')->assertStatus(401)->assertJson(['error' => 'UNAUTHENTICATED']);
});

test('a disallowed HTTP method renders the generic HTTP_ERROR code', function () {
    $this->deleteJson('/api/ready')
        ->assertStatus(405)
        ->assertExactJson(['error' => 'HTTP_ERROR']);
});
