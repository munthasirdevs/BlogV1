<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Cache\FullPageCacheService;
use App\Services\CacheService;
use Illuminate\Support\Facades\App;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $category->updateQuietly(['posts_count' => 0]);

        $this->invalidateCache($category);
    }

    public function saved(Category $category): void
    {
        $this->invalidateCache($category);
    }

    public function deleted(Category $category): void
    {
        $this->invalidateCache($category);
    }

    private function invalidateCache(Category $category): void
    {
        $cache = App::make(FullPageCacheService::class);
        $cache->invalidateByPrefix('global', $category->tenant_id);

        $cacheService = App::make(CacheService::class);
        $cacheService->forget('categories');
    }
}
