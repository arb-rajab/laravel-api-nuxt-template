<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The API error contract: every error under `api/*` (or any request that
 * expects JSON) is `{"error": "CODE"}`, plus `"fields": {...}` for
 * validation failures. Clients branch on the machine-readable code, never on
 * prose. Codes: VALIDATION_FAILED (422), UNAUTHENTICATED (401), FORBIDDEN
 * (403), NOT_FOUND (404), CSRF_TOKEN_MISMATCH (419), TOO_MANY_REQUESTS (429),
 * HTTP_ERROR (any other HTTP exception).
 *
 * One rendering rule here replaces every controller mapping exceptions by
 * hand. Order matters: Laravel uses the first renderer that returns a
 * response, so the generic HTTP fallback is registered last.
 */
final class ApiExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(self::wantsJson(...));

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (self::wantsJson($request)) {
                return self::json('VALIDATION_FAILED', $e->status, ['fields' => $e->errors()]);
            }
        });

        // NotFoundHttpException, not ModelNotFoundException: the framework
        // converts every ModelNotFoundException into a NotFoundHttpException
        // before any render() callback runs, so a callback typed against
        // ModelNotFoundException is never invoked.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (self::wantsJson($request)) {
                return self::json('NOT_FOUND', 404);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (self::wantsJson($request)) {
                return self::json('UNAUTHENTICATED', 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (self::wantsJson($request)) {
                return self::json('FORBIDDEN', 403);
            }
        });

        // Carries the Retry-After header the framework already computed.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (self::wantsJson($request)) {
                return self::json('TOO_MANY_REQUESTS', 429, [], $e->getHeaders());
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! self::wantsJson($request)) {
                return null;
            }

            $code = match ($e->getStatusCode()) {
                403 => 'FORBIDDEN',
                419 => 'CSRF_TOKEN_MISMATCH',
                default => 'HTTP_ERROR',
            };

            return self::json($code, $e->getStatusCode(), [], $e->getHeaders());
        });
    }

    private static function wantsJson(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * @param  array<string, mixed>  $extra
     * @param  array<string, string|list<string>>  $headers
     */
    private static function json(string $code, int $status, array $extra = [], array $headers = []): JsonResponse
    {
        return response()->json(['error' => $code, ...$extra], $status, $headers);
    }
}
