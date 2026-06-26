<?php

namespace App\Services;

use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function create(string $name, string $domain, array $options = []): Site
    {
        $tenant = Site::create([
            'name' => $name,
            'domain' => $domain,
            'theme' => $options['theme'] ?? 'default',
            'settings' => $options['settings'] ?? [],
            'is_active' => true,
        ]);

        $this->cacheService->forgetByPattern('tenants:*');

        return $tenant;
    }

    public function suspend(int $tenantId): void
    {
        $tenant = Site::findOrFail($tenantId);
        $tenant->update(['is_active' => false]);
        $this->cacheService->forgetByPattern('tenants:*');
    }

    public function activate(int $tenantId): void
    {
        $tenant = Site::findOrFail($tenantId);
        $tenant->update(['is_active' => true]);
        $this->cacheService->forgetByPattern('tenants:*');
    }

    public function getStats(int $tenantId): array
    {
        $tenant = Site::withCount('posts')->findOrFail($tenantId);

        return [
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'is_active' => $tenant->is_active,
            'post_count' => $tenant->posts_count,
            'user_count' => User::where('tenant_id', $tenantId)->count(),
        ];
    }

    public function getAllTenants(): \Illuminate\Support\Collection
    {
        return $this->cacheService->remember('tenants:all', 3600, function () {
            return Site::withCount('posts')->orderBy('name')->get();
        });
    }
}
