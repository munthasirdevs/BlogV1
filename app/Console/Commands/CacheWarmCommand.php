<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheWarmCommand extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Preload caches for optimal performance';

    public function handle(CacheService $cacheService): int
    {
        $this->info('Warming caches...');

        $this->warmCategories($cacheService);
        $this->warmTags($cacheService);
        $this->warmHomepage($cacheService);
        $this->warmSettings($cacheService);

        $this->info('All caches warmed successfully.');
        return Command::SUCCESS;
    }

    protected function warmCategories(CacheService $cache): void
    {
        $this->line('Warming categories...');
        $cache->getCategories();
        $cache->getCategoryTree();
        $this->info('  Categories cached.');
    }

    protected function warmTags(CacheService $cache): void
    {
        $this->line('Warming tags...');
        $cache->remember('tags:all', 3600, fn() => Tag::active()->orderBy('usage_count', 'desc')->get());
        $this->info('  Tags cached.');
    }

    protected function warmHomepage(CacheService $cache): void
    {
        $this->line('Warming homepage...');
        $cache->remember('homepage', 3600, function () {
            return [
                'featured' => Post::published()->where('is_featured', true)->with('author', 'category')
                    ->orderBy('published_at', 'desc')->take(3)->get(),
                'latest' => Post::published()->with('author', 'category')
                    ->orderBy('published_at', 'desc')->paginate(12),
            ];
        });
        $this->info('  Homepage cached.');
    }

    protected function warmSettings(CacheService $cache): void
    {
        $this->line('Warming settings...');
        $cache->remember('settings:all', 86400, fn() => \App\Models\Setting::all()->keyBy('key'));
        $this->info('  Settings cached.');
    }
}
