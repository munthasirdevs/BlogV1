<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Site;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemOrchestrator
{
    public function __construct(
        protected SecurityService $securityService,
        protected ObservabilityService $observabilityService,
        protected CacheService $cacheService,
        protected BillingService $billingService
    ) {}

    public function healthCheck(): array
    {
        $checks = [];

        // Database
        try {
            DB::select('SELECT 1');
            $checks['database'] = ['status' => 'healthy', 'latency_ms' => $this->measureLatency(fn() => DB::select('SELECT 1'))];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }

        // Cache (Redis/File)
        try {
            $key = 'health:' . now()->timestamp;
            Cache::put($key, 'ok', 10);
            $cached = Cache::get($key);
            $checks['cache'] = ['status' => $cached === 'ok' ? 'healthy' : 'degraded'];
            Cache::forget($key);
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }

        // Queue
        try {
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $checks['queue'] = [
                'status' => $failedJobs > 10 ? 'degraded' : 'healthy',
                'pending_jobs' => $queueSize,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }

        // Storage
        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $disk->exists('health_check.txt');
            $checks['storage'] = ['status' => 'healthy'];
        } catch (\Exception $e) {
            $checks['storage'] = ['status' => 'degraded', 'error' => $e->getMessage()];
        }

        $allHealthy = collect($checks)->every(fn($c) => $c['status'] === 'healthy');

        return [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services' => $checks,
        ];
    }

    public function getSystemSummary(): array
    {
        $totalTenants = Site::count();
        $activeTenants = Site::active()->count();
        $totalPosts = Post::count();
        $publishedPosts = Post::published()->count();
        $totalJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $errorRate = SystemLog::where('level', 'error')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $totalLogs = SystemLog::where('created_at', '>=', now()->subDay())->count();

        $securityScore = $this->securityService->getSecurityDashboard()['security_score'] ?? 100;
        $revenueData = $this->billingService->getRevenueAnalytics();

        return [
            'tenants' => ['total' => $totalTenants, 'active' => $activeTenants],
            'content' => ['total_posts' => $totalPosts, 'published_posts' => $publishedPosts],
            'queue' => ['pending' => $totalJobs, 'failed' => $failedJobs],
            'observability' => ['errors_24h' => $errorRate, 'total_logs_24h' => $totalLogs, 'error_rate_pct' => $totalLogs > 0 ? round($errorRate / $totalLogs * 100, 2) : 0],
            'security' => ['score' => $securityScore],
            'revenue' => $revenueData,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function getModuleStatus(): array
    {
        return [
            'core' => ['posts' => true, 'categories' => true, 'tags' => true, 'media' => true, 'pages' => true],
            'ai' => ['content_generation' => true, 'seo_optimization' => true, 'tagging' => true],
            'saas' => ['multi_tenant' => true, 'tenant_isolation' => true, 'billing' => true],
            'search' => ['fulltext' => true, 'ai_enhanced' => true, 'autocomplete' => true],
            'security' => ['rbac' => true, 'rate_limiting' => true, 'csp' => true, 'audit' => true],
            'observability' => ['logging' => true, 'monitoring' => true, 'health_checks' => true],
            'analytics' => ['tracking' => true, 'dashboard' => true, 'reports' => true],
            'workflow' => ['approval' => true, 'scheduling' => true, 'revisions' => true],
        ];
    }

    protected function measureLatency(callable $fn): float
    {
        $start = microtime(true);
        $fn();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
