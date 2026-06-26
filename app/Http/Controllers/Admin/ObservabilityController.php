<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ObservabilityService;
use Illuminate\View\View;

class ObservabilityController extends Controller
{
    public function __construct(
        protected ObservabilityService $observabilityService
    ) {}

    public function index(): View
    {
        $metrics = $this->observabilityService->getDashboardMetrics();
        $recentErrors = $metrics['recent_errors'] ?? [];

        return view('admin.observability.index', compact('metrics', 'recentErrors'));
    }
}
