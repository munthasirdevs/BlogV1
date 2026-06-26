<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Post;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $this->clearCache();
    }

    public function updated(Category $category): void
    {
        $this->clearCache();
    }

    public function deleted(Category $category): void
    {
        $this->clearCache();
    }

    public function restored(Category $category): void
    {
        $this->clearCache();
    }

    public function postCreated(Post $post): void
    {
        if ($post->category_id) {
            Category::where('id', $post->category_id)->increment('article_count');
        }
    }

    public function postUpdated(Post $post): void
    {
        if ($post->isDirty('category_id')) {
            $oldId = $post->getOriginal('category_id');
            if ($oldId) {
                Category::where('id', $oldId)->decrement('article_count');
            }
            if ($post->category_id) {
                Category::where('id', $post->category_id)->increment('article_count');
            }
        }
    }

    public function postDeleted(Post $post): void
    {
        if ($post->category_id) {
            Category::where('id', $post->category_id)->decrement('article_count');
        }
    }

    private function clearCache(): void
    {
        if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
            try {
                Cache::tags(['categories'])->flush();
            } catch (\Exception $e) {
                Cache::flush();
            }
        }
    }
}
