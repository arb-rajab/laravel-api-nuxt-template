<?php

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

test('the framework liveness endpoint responds', function () {
    get('/up')->assertOk();
});

test('the readiness endpoint reports the database as reachable', function () {
    getJson('/api/ready')->assertOk()->assertJson(['status' => 'ready']);
});
