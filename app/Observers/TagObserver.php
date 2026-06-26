<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

class TagObserver
{
    public function created(Tag $tag): void
    {
        $this->clearCache();
    }

    public function updated(Tag $tag): void
    {
        $this->clearCache();
    }

    public function deleted(Tag $tag): void
    {
        $this->clearCache();
    }

    public function restored(Tag $tag): void
    {
        $this->clearCache();
    }

    public function postTagAttached(Post $post, array $tagIds): void
    {
        Tag::whereIn('id', $tagIds)->increment('usage_count');
        $this->clearCache();
    }

    public function postTagDetached(Post $post, array $tagIds): void
    {
        Tag::whereIn('id', $tagIds)->where('usage_count', '>', 0)->decrement('usage_count');
        $this->clearCache();
    }

    public function postTagsSynced(Post $post, array $tagIds): void
    {
        $currentIds = $post->tags()->pluck('tags.id')->toArray();
        $attached = array_diff($tagIds, $currentIds);
        $detached = array_diff($currentIds, $tagIds);

        if (!empty($attached)) {
            Tag::whereIn('id', $attached)->increment('usage_count');
        }
        if (!empty($detached)) {
            Tag::whereIn('id', $detached)->where('usage_count', '>', 0)->decrement('usage_count');
        }

        $this->clearCache();
    }

    private function clearCache(): void
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags(['tags'])->flush();
            }
        } catch (\Exception $e) {
        }
    }
}
