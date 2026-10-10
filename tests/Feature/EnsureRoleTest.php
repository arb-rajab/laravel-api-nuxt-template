<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Http\Request;

test('a request with no authenticated user is rejected as UNAUTHENTICATED, not a crash', function () {
    $request = Request::create('/anything');
    $request->setUserResolver(fn () => null);

    $response = (new EnsureRole)->handle($request, fn () => response('next'), 'admin');

    expect($response->getStatusCode())->toBe(401);
});
