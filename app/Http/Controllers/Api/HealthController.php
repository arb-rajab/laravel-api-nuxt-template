<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /** GET /api/ready: readiness probe (database reachable). Liveness is the framework's /up. */
    public function ready(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (Throwable) {
            return $this->error('NOT_READY', 503);
        }

        return $this->ok(['status' => 'ready']);
    }
}
