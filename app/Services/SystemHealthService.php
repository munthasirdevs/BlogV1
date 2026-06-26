<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthService
{
    public function getGlobalHealth(): array
    {
        $checks = [];
        $allHealthy = true;

        // Database
        try {
            DB::select('SELECT 1');
            $checks['database'] = ['status' => 'healthy'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $allHealthy = false;
        }

        // Cache
        try {
            Cache::put('health:ping', true, 1);
            Cache::get('health:ping');
            $checks['cache'] = ['status' => 'healthy'];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'degraded'];
        }

        // Queue
        try {
            $failed = DB::table('failed_jobs')->count();
            $checks['queue'] = ['status' => $failed > 10 ? 'degraded' : 'healthy', 'failed_jobs' => $failed];
            if ($failed > 10) $allHealthy = false;
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'unhealthy'];
            $allHealthy = false;
        }

        // AI API Key
        $checks['ai'] = ['status' => config('services.nvidia.key') ? 'configured' : 'not_configured'];

        // Tenant count
        $checks['tenants'] = ['count' => Site::count(), 'active' => Site::active()->count()];

        // Content stats
        $checks['content'] = [
            'total_posts' => Post::count(),
            'published' => Post::published()->count(),
        ];

        $state = $allHealthy ? 'healthy' : 'degraded';

        return [
            'status' => $state,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0.0',
        ];
    }

    public function getSystemModules(): array
    {
        return [
            'content' => ['posts' => true, 'categories' => true, 'tags' => true, 'media' => true, 'pages' => true],
            'ai' => ['generation' => true, 'seo' => true, 'tagging' => true, 'safety' => true],
            'billing' => ['plans' => true, 'subscriptions' => true, 'invoices' => true, 'usage' => true],
            'saas' => ['tenants' => true, 'isolation' => true, 'provisioning' => true],
            'security' => ['rbac' => true, 'csp' => true, 'audit' => true, 'suspicious_detection' => true],
            'observability' => ['logs' => true, 'metrics' => true, 'health' => true],
            'search' => ['fulltext' => true, 'ai_enhanced' => true, 'autocomplete' => true],
            'workflow' => ['approval' => true, 'scheduling' => true, 'revisions' => true],
            'plugins' => ['registry' => true, 'hooks' => true, 'lifecycle' => true],
            'performance' => ['cache' => true, 'queue' => true, 'optimization' => true],
        ];
    }
}
