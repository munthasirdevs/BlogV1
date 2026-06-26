<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use Illuminate\View\View;

class BillingDashboardController extends Controller
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    public function index(): View
    {
        $revenue = $this->billingService->getRevenueAnalytics();
        return view('admin.billing.dashboard', compact('revenue'));
    }
}
