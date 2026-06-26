<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Str;

class ObservabilityService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function log(string $level, string $channel, string $message, array $context = []): SystemLog
    {
        return SystemLog::create([
            'tenant_id' => tenant_id(),
            'level' => $level,
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
            'request_id' => request()->header('X-Request-ID') ?? (string) Str::uuid(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function info(string $channel, string $message, array $context = []): SystemLog
    {
        return $this->log('info', $channel, $message, $context);
    }

    public function warning(string $channel, string $message, array $context = []): SystemLog
    {
        return $this->log('warn', $channel, $message, $context);
    }

    public function error(string $channel, string $message, array $context = []): SystemLog
    {
        return $this->log('error', $channel, $message, $context);
    }

    public function critical(string $channel, string $message, array $context = []): SystemLog
    {
        return $this->log('critical', $channel, $message, $context);
    }

    public function getDashboardMetrics(): array
    {
        $now = now();
        $oneHourAgo = $now->copy()->subHour();
        $oneDayAgo = $now->copy()->subDay();

        return [
            'errors_last_hour' => SystemLog::whereIn('level', ['error', 'critical'])
                ->where('created_at', '>=', $oneHourAgo)->count(),
            'total_logs_last_hour' => SystemLog::where('created_at', '>=', $oneHourAgo)->count(),
            'errors_last_24h' => SystemLog::errors()->where('created_at', '>=', $oneDayAgo)->count(),
            'logs_by_channel' => SystemLog::where('created_at', '>=', $oneDayAgo)
                ->selectRaw('channel, COUNT(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel')
                ->toArray(),
            'logs_by_level' => SystemLog::where('created_at', '>=', $oneDayAgo)
                ->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'recent_errors' => SystemLog::errors()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->toArray(),
        ];
    }
}
