<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\UsageRecord;
use Carbon\Carbon;

class BillingService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function subscribe(Site $tenant, Plan $plan, ?Carbon $trialEndsAt = null): Subscription
    {
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $trialEndsAt ? 'trial' : 'active',
            'starts_at' => now(),
            'trial_ends_at' => $trialEndsAt,
            'auto_renew' => true,
        ]);

        $this->cacheService->forget("tenant:{$tenant->id}:subscription");
        return $subscription;
    }

    public function recordUsage(int $tenantId, string $type, float $quantity, float $cost = 0, array $metadata = []): UsageRecord
    {
        return UsageRecord::create([
            'tenant_id' => $tenantId,
            'type' => $type,
            'quantity' => $quantity,
            'cost_estimate' => $cost,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public function getMonthlyUsage(int $tenantId, string $type): float
    {
        return UsageRecord::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('quantity');
    }

    public function generateInvoice(int $tenantId, ?int $subscriptionId = null): Invoice
    {
        $subscription = $subscriptionId ? Subscription::findOrFail($subscriptionId)
            : Subscription::where('tenant_id', $tenantId)->active()->first();

        $plan = $subscription?->plan;

        $aiUsage = $this->getMonthlyUsage($tenantId, 'ai_tokens');
        $storageUsage = $this->getMonthlyUsage($tenantId, 'storage');

        $items = [];
        $totalAmount = 0;

        if ($plan && $plan->price_monthly > 0) {
            $items[] = ['type' => 'plan', 'description' => $plan->name . ' (Monthly)', 'amount' => $plan->price_monthly];
            $totalAmount += $plan->price_monthly;
        }

        if ($aiUsage > ($plan?->ai_credits_limit ?? 0)) {
            $overage = $aiUsage - ($plan?->ai_credits_limit ?? 0);
            $aiCost = round($overage * 0.002, 2);
            $items[] = ['type' => 'ai_overage', 'description' => "AI overage ({$overage} tokens)", 'amount' => $aiCost];
            $totalAmount += $aiCost;
        }

        $invoice = Invoice::create([
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription?->id,
            'amount' => $totalAmount,
            'currency' => 'USD',
            'status' => 'draft',
            'due_date' => now()->addDays(15),
            'invoice_items' => $items,
        ]);

        $this->cacheService->forget("tenant:{$tenantId}:invoices");
        return $invoice;
    }

    public function recordPayment(int $invoiceId, string $provider, float $amount, string $transactionId = null): Payment
    {
        $payment = Payment::create([
            'invoice_id' => $invoiceId,
            'provider' => $provider,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        Invoice::where('id', $invoiceId)->update(['status' => 'paid', 'paid_at' => now()]);

        return $payment;
    }

    public function getRevenueAnalytics(): array
    {
        $monthStart = now()->startOfMonth();

        return [
            'mrr' => Invoice::where('status', 'paid')
                ->where('paid_at', '>=', $monthStart)
                ->sum('amount'),
            'total_invoices' => Invoice::count(),
            'paid_invoices' => Invoice::where('status', 'paid')->count(),
            'pending_invoices' => Invoice::where('status', 'draft')->count(),
            'failed_payments' => Invoice::where('status', 'failed')->count(),
            'total_ai_usage' => UsageRecord::where('type', 'ai_tokens')->sum('quantity'),
        ];
    }
}
