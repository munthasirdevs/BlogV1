<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\Site;
use Illuminate\Support\Collection;

class PluginService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function register(array $manifest): Plugin
    {
        $plugin = Plugin::updateOrCreate(
            ['slug' => $manifest['slug']],
            [
                'name' => $manifest['name'],
                'version' => $manifest['version'] ?? '1.0.0',
                'author' => $manifest['author'] ?? null,
                'description' => $manifest['description'] ?? null,
                'permissions_required' => $manifest['permissions'] ?? [],
                'event_subscriptions' => $manifest['events'] ?? [],
                'provider_class' => $manifest['provider'] ?? null,
                'is_tenant_aware' => $manifest['tenant_aware'] ?? false,
                'status' => 'installed',
            ]
        );

        PluginVersion::create([
            'plugin_id' => $plugin->id,
            'version' => $manifest['version'] ?? '1.0.0',
            'changelog' => $manifest['changelog'] ?? 'Initial installation',
        ]);

        $this->cacheService->forgetByPattern('plugins:*');
        return $plugin;
    }

    public function enable(int $pluginId): Plugin
    {
        $plugin = Plugin::findOrFail($pluginId);
        $plugin->update(['status' => 'enabled']);
        $this->cacheService->forgetByPattern('plugins:*');
        return $plugin;
    }

    public function disable(int $pluginId): Plugin
    {
        $plugin = Plugin::findOrFail($pluginId);
        $plugin->update(['status' => 'disabled']);
        $this->cacheService->forgetByPattern('plugins:*');
        return $plugin;
    }

    public function enableForTenant(int $pluginId, int $tenantId): void
    {
        \App\Models\TenantPlugin::updateOrCreate(
            ['tenant_id' => $tenantId, 'plugin_id' => $pluginId],
            ['is_enabled' => true]
        );
        $this->cacheService->forget("tenant:{$tenantId}:plugins");
    }

    public function disableForTenant(int $pluginId, int $tenantId): void
    {
        \App\Models\TenantPlugin::where('tenant_id', $tenantId)
            ->where('plugin_id', $pluginId)
            ->update(['is_enabled' => false]);
        $this->cacheService->forget("tenant:{$tenantId}:plugins");
    }

    public function getEnabledPlugins(?int $tenantId = null): Collection
    {
        $query = Plugin::enabled();

        if ($tenantId) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('is_tenant_aware', false)
                  ->orWhereHas('tenants', fn($t) => $t->where('tenant_id', $tenantId)->where('is_enabled', true));
            });
        }

        return $query->get();
    }

    public function getPlugin(string $slug): ?Plugin
    {
        return Plugin::where('slug', $slug)->first();
    }

    public function getAllPlugins(): Collection
    {
        return $this->cacheService->remember('plugins:all', 3600, function () {
            return Plugin::withCount('versions')->orderBy('name')->get();
        });
    }

    public function addSetting(int $pluginId, ?int $tenantId, string $key, $value): void
    {
        \App\Models\PluginSetting::updateOrCreate(
            ['plugin_id' => $pluginId, 'tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value]
        );
    }

    public function getSetting(int $pluginId, ?int $tenantId, string $key, $default = null)
    {
        $setting = \App\Models\PluginSetting::where('plugin_id', $pluginId)
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();
        return $setting ? $setting->value : $default;
    }
}
