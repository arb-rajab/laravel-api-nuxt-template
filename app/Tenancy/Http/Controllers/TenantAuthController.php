<?php

namespace App\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantAuthController extends Controller
{
    /**
     * POST /api/tenants/{slug}/login. Runs behind resolve.tenant.slug +
     * tenant.context, so the credential lookup is already scoped to the
     * resolved tenant by both enforcement layers. Tenant users' emails are
     * only unique per tenant, which is why login needs the tenant first.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return $this->error('INVALID_CREDENTIALS', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Global users (no tenant) are visible inside any tenant context;
        // they sign in through POST /api/login, not here.
        if ($user->getAttribute('tenant_id') === null) {
            Auth::logout();

            return $this->error('INVALID_CREDENTIALS', 401);
        }

        if (! $request->hasSession()) {
            Auth::logout();

            return $this->error('STATEFUL_ORIGIN_REQUIRED', 400);
        }

        $request->session()->regenerate();

        // Read back by AuthenticateTenantUser on every later request, since
        // the tenant must be known before the user row can be looked up.
        $request->session()->put('tenant_id', $user->getAttribute('tenant_id'));

        return $this->ok(['user' => $user->toApiPayload()]);
    }

    /** GET /api/tenant/me */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->ok(['user' => $user->toApiPayload()]);
    }
}
