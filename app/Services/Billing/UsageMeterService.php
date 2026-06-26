<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageRecord;
use App\Services\CacheService;

class UsageMeterService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function increment(int $tenantId, string $metric, float $quantity = 1, float $cost = 0): void
    {
        UsageRecord::create([
            'tenant_id' => $tenantId,
            'type' => $metric,
            'quantity' => $quantity,
            'cost_estimate' => $cost,
            'created_at' => now(),
        ]);

        $this->cacheService->forget("tenant:{$tenantId}:usage:{$metric}:monthly");
    }

    public function getMonthlyUsage(int $tenantId, string $metric): float
    {
        $key = "tenant:{$tenantId}:usage:{$metric}:monthly";
        return $this->cacheService->remember($key, 300, function () use ($tenantId, $metric) {
            return UsageRecord::where('tenant_id', $tenantId)
                ->where('type', $metric)
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('quantity');
        });
    }

    public function checkQuota(int $tenantId, string $metric, float $limit): array
    {
        $usage = $this->getMonthlyUsage($tenantId, $metric);
        $remaining = max(0, $limit - $usage);
        $percentage = $limit > 0 ? round(($usage / $limit) * 100, 1) : 0;

        return [
            'metric' => $metric,
            'usage' => $usage,
            'limit' => $limit,
            'remaining' => $remaining,
            'percentage' => $percentage,
            'exceeded' => $usage >= $limit,
        ];
    }

    public function getTenantQuotas(int $tenantId): array
    {
        $subscription = Subscription::where('tenant_id', $tenantId)
            ->with('plan')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->plan) {
            return ['error' => 'No active subscription'];
        }

        $plan = $subscription->plan;

        return [
            'ai_credits' => $this->checkQuota($tenantId, 'ai_tokens', $plan->ai_credits_limit),
            'storage' => $this->checkQuota($tenantId, 'storage', $plan->storage_limit),
            'api_requests' => $this->checkQuota($tenantId, 'api_requests', 10000),
            'plan' => $plan->name,
        ];
    }

    public function recordAiUsage(int $tenantId, float $tokens, float $cost = 0): void
    {
        $this->increment($tenantId, 'ai_tokens', $tokens, $cost);
    }

    public function recordStorageUsage(int $tenantId, float $bytes): void
    {
        $this->increment($tenantId, 'storage', $bytes);
    }

    public function recordApiRequest(int $tenantId): void
    {
        $this->increment($tenantId, 'api_requests', 1);
    }

    public function getDetailedAnalytics(int $tenantId): array
    {
        return [
            'ai_tokens' => $this->getMonthlyUsage($tenantId, 'ai_tokens'),
            'storage' => $this->getMonthlyUsage($tenantId, 'storage'),
            'api_requests' => $this->getMonthlyUsage($tenantId, 'api_requests'),
            'quotas' => $this->getTenantQuotas($tenantId),
        ];
    }
}
