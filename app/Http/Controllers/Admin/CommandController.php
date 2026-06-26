<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use App\Services\Search\SearchIndexerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CommandController extends Controller
{
    public function __construct(
        protected CacheService $cacheService,
        protected SearchIndexerService $searchIndexer
    ) {}

    public function flushCache(): RedirectResponse
    {
        Cache::flush();
        Log::info('Cache flushed by admin', ['user_id' => auth()->id()]);
        return back()->with('success', 'All caches cleared.');
    }

    public function warmCache(): RedirectResponse
    {
        Artisan::call('cache:warm');
        $output = Artisan::output();
        return back()->with('success', 'Cache warmed: ' . $output);
    }

    public function rebuildSearch(): RedirectResponse
    {
        $count = $this->searchIndexer->rebuildIndex();
        return back()->with('success', "Search index rebuilt: {$count} posts indexed.");
    }

    public function clearLogs(): RedirectResponse
    {
        Artisan::call('log:clear');
        return back()->with('success', 'Application logs cleared.');
    }

    public function optimize(): RedirectResponse
    {
        Artisan::call('optimize');
        return back()->with('success', 'Application optimized (cache, routes, config).');
    }

    public function systemInfo(): \Illuminate\View\View
    {
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $dbConnection = config('database.default');
        $cacheDriver = config('cache.default');
        $queueDriver = config('queue.default');
        $sessionDriver = config('session.driver');
        $environment = app()->environment();
        $debugMode = config('app.debug');
        $appUrl = config('app.url');

        return view('admin.system.info', compact(
            'phpVersion', 'laravelVersion', 'dbConnection',
            'cacheDriver', 'queueDriver', 'sessionDriver',
            'environment', 'debugMode', 'appUrl'
        ));
    }
}
