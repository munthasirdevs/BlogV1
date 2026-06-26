<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    public function subscription(): JsonResponse
    {
        $tenantId = tenant_id();
        if (!$tenantId) return ApiResponse::error('Tenant context required', 400);

        $subscription = Subscription::where('tenant_id', $tenantId)
            ->with('plan')
            ->latest()
            ->first();

        return ApiResponse::success($subscription, 'Subscription retrieved');
    }

    public function invoices(): JsonResponse
    {
        $tenantId = tenant_id();
        if (!$tenantId) return ApiResponse::error('Tenant context required', 400);

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ApiResponse::paginated($invoices, 'Invoices retrieved');
    }
}
