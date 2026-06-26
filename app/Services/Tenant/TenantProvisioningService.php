<?php

namespace App\Services\Tenant;

use App\Models\Plan;
use App\Models\Site;
use App\Models\User;
use App\Services\BillingService;
use App\Services\CacheService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    public function __construct(
        protected CacheService $cacheService,
        protected BillingService $billingService
    ) {}

    public function provision(string $name, string $domain, array $options = []): array
    {
        $tenant = Site::create([
            'name' => $name,
            'domain' => $domain,
            'theme' => $options['theme'] ?? 'default',
            'settings' => [
                'timezone' => $options['timezone'] ?? 'UTC',
                'locale' => $options['locale'] ?? 'en',
            ],
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => $options['admin_name'] ?? 'Administrator',
            'email' => $options['admin_email'] ?? "admin@{$domain}",
            'password' => Hash::make($options['admin_password'] ?? Str::random(16)),
            'tenant_id' => $tenant->id,
        ]);

        $admin->assignRole('super-admin');

        if (isset($options['plan_id'])) {
            $plan = Plan::find($options['plan_id']);
            if ($plan) {
                $this->billingService->subscribe($tenant, $plan, now()->addDays(14));
            }
        }

        $this->cacheService->forgetByPattern('tenants:*');

        return [
            'tenant' => $tenant,
            'admin' => $admin,
            'default_password' => $options['admin_password'] ?? null,
        ];
    }

    public function suspend(Site $tenant): void
    {
        $tenant->update(['is_active' => false]);
        $this->cacheService->forgetByPattern("tenant:{$tenant->id}:*");
    }

    public function activate(Site $tenant): void
    {
        $tenant->update(['is_active' => true]);
    }

    public function deleteTenant(Site $tenant): void
    {
        $tid = $tenant->id;
        $tenant->delete();
        $this->cacheService->forgetByPattern("tenant:{$tid}:*");
        $this->cacheService->forgetByPattern('tenants:*');
    }

    public function getUsageSummary(int $tenantId): array
    {
        $tenant = Site::withCount(['posts', 'users'])->findOrFail($tenantId);

        return [
            'posts' => $tenant->posts_count,
            'users' => $tenant->users_count,
            'domain' => $tenant->domain,
            'active' => $tenant->is_active,
            'created' => $tenant->created_at->toIso8601String(),
        ];
    }
}
