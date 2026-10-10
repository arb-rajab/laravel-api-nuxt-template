<?php

namespace App\Http\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Small helpers so controllers and middleware build the API's JSON
 * responses the same way.
 *
 * Success bodies are plain JSON. Lists are `{"data": [...], "meta": {...}}`
 * (see paginated()). Error bodies follow the contract documented on
 * App\Exceptions\ApiExceptionRenderer: `{"error": "CODE"}`, plus `"fields"`
 * for validation failures.
 */
trait RespondsWithJson
{
    /** @param  array<string, mixed>  $data */
    protected function ok(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    /** @param  array<string, mixed>  $extra */
    protected function error(string $code, int $status, array $extra = []): JsonResponse
    {
        return response()->json(['error' => $code, ...$extra], $status);
    }

    /**
     * A paginated list: `{"data": [...], "meta": {current_page, per_page,
     * total, last_page}}`. Pass `$map` to shape each item (an API resource
     * transformer, or a closure returning an array); without it the items are
     * serialised as they are.
     *
     * @param  LengthAwarePaginator<int, mixed>  $page
     * @param  (callable(mixed): mixed)|null  $map
     */
    protected function paginated(LengthAwarePaginator $page, ?callable $map = null): JsonResponse
    {
        $items = array_values($page->items());

        return response()->json([
            'data' => $map === null ? $items : array_map($map, $items),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    /** The requested `per_page`, clamped to 1..$max so a client cannot ask for an unbounded page; non-numeric input falls back to the default. */
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        $requested = $request->input('per_page');
        $value = is_numeric($requested) ? (int) $requested : $default;

        return max(1, min($max, $value));
    }
}
