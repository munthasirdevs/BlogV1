<?php

namespace App\Services\Plugin;

use App\Models\Plugin;
use App\Services\CacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PluginHookService
{
    protected ?Collection $hooks = null;

    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function registerHook(string $hook, string $pluginSlug, callable $handler): void
    {
        $this->hooks ??= collect();
        $key = "hook:{$hook}";
        $existing = $this->hooks->get($key, []);
        $existing[] = ['plugin' => $pluginSlug, 'handler' => $handler];
        $this->hooks->put($key, $existing);
    }

    public function execute(string $hook, array $payload = []): array
    {
        $results = [];
        $this->loadPluginHooks();

        $key = "hook:{$hook}";
        $handlers = $this->hooks?->get($key, []) ?? [];

        foreach ($handlers as $handler) {
            try {
                $result = call_user_func($handler['handler'], $payload);
                $results[] = [
                    'plugin' => $handler['plugin'],
                    'status' => 'completed',
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                Log::error("Plugin hook '{$hook}' failed for plugin '{$handler['plugin']}'", [
                    'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'plugin' => $handler['plugin'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function hasHooks(string $hook): bool
    {
        $this->loadPluginHooks();
        return $this->hooks?->has("hook:{$hook}") ?? false;
    }

    public function getRegisteredHooks(): array
    {
        $this->loadPluginHooks();
        return $this->hooks?->keys()->map(fn($k) => str_replace('hook:', '', $k))->values()->toArray() ?? [];
    }

    protected function loadPluginHooks(): void
    {
        if ($this->hooks !== null) return;
        $this->hooks = collect();

        $enabledPlugins = Plugin::enabled()->get();

        foreach ($enabledPlugins as $plugin) {
            $subscriptions = $plugin->event_subscriptions ?? [];
            foreach ($subscriptions as $event) {
                $key = "hook:{$event}";
                $existing = $this->hooks->get($key, []);
                $existing[] = [
                    'plugin' => $plugin->slug,
                    'handler' => function ($payload) use ($plugin, $event) {
                        return $this->dispatchPluginEvent($plugin, $event, $payload);
                    },
                ];
                $this->hooks->put($key, $existing);
            }
        }
    }

    protected function dispatchPluginEvent(Plugin $plugin, string $event, array $payload): array
    {
        Log::debug("Plugin event dispatched", [
            'plugin' => $plugin->slug,
            'event' => $event,
            'payload' => $payload,
        ]);

        return ['dispatched' => true, 'plugin' => $plugin->slug, 'event' => $event];
    }

    public function clearHooks(): void
    {
        $this->hooks = null;
    }
}
