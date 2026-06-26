<?php

namespace App\Http\Controllers;

use App\Services\SystemOrchestrator;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        protected SystemOrchestrator $orchestrator
    ) {}

    public function __invoke(): JsonResponse
    {
        $health = $this->orchestrator->healthCheck();
        $statusCode = $health['status'] === 'healthy' ? 200 : 503;
        return response()->json($health, $statusCode);
    }
}
