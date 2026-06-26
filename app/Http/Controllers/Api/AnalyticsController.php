<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    public function dashboard(): JsonResponse
    {
        $data = $this->analyticsService->getDashboardData();
        return ApiResponse::success($data, 'Analytics dashboard data');
    }
}
