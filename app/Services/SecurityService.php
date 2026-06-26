<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\DB;

class SecurityService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function getSecurityDashboard(): array
    {
        $recentSuspicious = AnalyticsEvent::where('event_type', 'suspicious_activity')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $failedLogins = AnalyticsEvent::where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $totalApiRequests = AnalyticsEvent::where('event_type', 'api_request')
            ->where('created_at', '>=', now()->subDays(1))
            ->count();

        $uniqueIps = AnalyticsEvent::where('created_at', '>=', now()->subDays(1))
            ->whereNotNull('ip_hash')
            ->distinct('ip_hash')
            ->count('ip_hash');

        return [
            'suspicious_events_7d' => $recentSuspicious,
            'failed_logins_7d' => $failedLogins,
            'api_requests_24h' => $totalApiRequests,
            'unique_ips_24h' => $uniqueIps,
            'security_score' => $this->calculateSecurityScore($recentSuspicious, $failedLogins),
        ];
    }

    public function getSuspiciousLogs(int $limit = 20): array
    {
        return AnalyticsEvent::where('event_type', 'suspicious_activity')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    protected function calculateSecurityScore(int $suspicious, int $failedLogins): int
    {
        $score = 100;
        $score -= min($suspicious * 5, 50);
        $score -= min($failedLogins * 2, 30);
        return max(0, $score);
    }
}
