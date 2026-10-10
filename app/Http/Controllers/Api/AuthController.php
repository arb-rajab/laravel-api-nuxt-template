<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sanctum SPA (cookie/session) auth with one shared `web` guard; role-based
 * authorization happens at the app layer (EnsureRole). No bearer tokens.
 *
 * The same failure body is returned for an unknown email and a wrong
 * password, so a caller cannot probe which emails exist.
 */
class AuthController extends Controller
{
    /** POST /api/login */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return $this->error('INVALID_CREDENTIALS', 401);
        }

        // Cookie auth only works for requests from a configured stateful
        // origin (config/sanctum.php), which are the ones that get a session.
        if (! $request->hasSession()) {
            Auth::logout();

            return $this->error('STATEFUL_ORIGIN_REQUIRED', 400);
        }

        // A fresh session id on every successful login defends against
        // session fixation.
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        return $this->ok(['user' => $user->toApiPayload()]);
    }

    /** GET /api/me */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->ok(['user' => $user->toApiPayload()]);
    }

    /** POST /api/logout: needs no authenticated user, so it works for every kind of session. */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->ok(['status' => 'logged_out']);
    }
}
