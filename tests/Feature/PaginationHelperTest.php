<?php

use App\Http\Concerns\RespondsWithJson;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/*
 * Exercises the RespondsWithJson pagination helpers through a throwaway
 * class, so the contract is pinned independently of any real endpoint.
 */

function paginationHarness(): object
{
    return new class
    {
        use RespondsWithJson {
            paginated as public;
            perPage as public;
        }
    };
}

test('paginated() wraps items in data and describes the page in meta', function () {
    $page = new LengthAwarePaginator([['id' => 1], ['id' => 2]], total: 5, perPage: 2, currentPage: 2);

    $response = paginationHarness()->paginated($page);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true))->toBe([
        'data' => [['id' => 1], ['id' => 2]],
        'meta' => ['current_page' => 2, 'per_page' => 2, 'total' => 5, 'last_page' => 3],
    ]);
});

test('paginated() maps every item through the optional callback', function () {
    $page = new LengthAwarePaginator([1, 2, 3], total: 3, perPage: 15, currentPage: 1);

    $response = paginationHarness()->paginated($page, fn (int $n): array => ['n' => $n * 10]);

    expect($response->getData(true)['data'])->toBe([['n' => 10], ['n' => 20], ['n' => 30]]);
});

test('paginated() renders an empty page with a single last page', function () {
    $page = new LengthAwarePaginator([], total: 0, perPage: 15, currentPage: 1);

    $body = paginationHarness()->paginated($page)->getData(true);

    expect($body['data'])->toBe([]);
    expect($body['meta'])->toMatchArray(['total' => 0, 'last_page' => 1]);
});

test('perPage() defaults, honours a valid value and clamps out-of-range ones', function () {
    $harness = paginationHarness();

    expect($harness->perPage(Request::create('/x')))->toBe(15);
    expect($harness->perPage(Request::create('/x', 'GET', ['per_page' => 40])))->toBe(40);
    expect($harness->perPage(Request::create('/x', 'GET', ['per_page' => 5000])))->toBe(100);
    expect($harness->perPage(Request::create('/x', 'GET', ['per_page' => 0])))->toBe(1);
    expect($harness->perPage(Request::create('/x', 'GET', ['per_page' => -3])))->toBe(1);
    expect($harness->perPage(Request::create('/x', 'GET', ['per_page' => 'abc'])))->toBe(15);
});
