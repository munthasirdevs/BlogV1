<?php

namespace App\Http\Controllers;

use App\Services\SystemOrchestrator;
use Illuminate\View\View;

class SystemStatusController extends Controller
{
    public function __construct(
        protected SystemOrchestrator $orchestrator
    ) {}

    public function index(): View
    {
        $health = $this->orchestrator->healthCheck();
        $summary = $this->orchestrator->getSystemSummary();
        $modules = $this->orchestrator->getModuleStatus();

        $routeCount = \Illuminate\Support\Facades\Route::getRoutes()->count() - 4;
        $modelCount = count(glob(app_path('Models/*.php')));
        $serviceCount = count(glob(app_path('Services/**/*.php')));

        return view('system.status', compact('health', 'summary', 'modules', 'routeCount', 'modelCount', 'serviceCount'));
    }
}
