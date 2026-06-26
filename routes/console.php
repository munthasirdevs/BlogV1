<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cache:warm', function () {
    $this->call(\App\Console\Commands\CacheWarmCommand::class);
})->purpose('Preload all application caches');

Artisan::command('search:rebuild {--tenant= : Tenant ID to rebuild for}', function (\App\Services\Search\SearchIndexerService $indexer) {
    $tenantId = $this->option('tenant');
    $count = $indexer->rebuildIndex($tenantId);
    $this->info("Search index rebuilt: {$count} posts indexed.");
})->purpose('Rebuild the search index');
