<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(): JsonResponse
    {
        $webhooks = Webhook::where('tenant_id', tenant_id())->get();
        return ApiResponse::success($webhooks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array'],
            'events.*' => ['string'],
        ]);

        $validated['tenant_id'] = tenant_id();
        $webhook = Webhook::create($validated);

        return ApiResponse::created($webhook, 'Webhook created');
    }

    public function destroy(Webhook $webhook): JsonResponse
    {
        $webhook->delete();
        return ApiResponse::success(null, 'Webhook deleted');
    }
}
