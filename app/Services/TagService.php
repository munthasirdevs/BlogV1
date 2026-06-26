<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Tag;
use App\Services\AI\AIService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagService
{
    public function __construct(
        protected CacheService $cacheService,
        protected AIService $aiService
    ) {}

    public function getAll(): Collection
    {
        return $this->cacheService->remember('tags:all', 3600, function () {
            return Tag::active()->orderBy('usage_count', 'desc')->get();
        });
    }

    public function getTrending(int $limit = 10): Collection
    {
        return $this->cacheService->remember('tags:trending:' . $limit, 1800, function () use ($limit) {
            return Tag::active()
                ->orderBy('trending_score', 'desc')
                ->orderBy('usage_count', 'desc')
                ->take($limit)
                ->get();
        });
    }

    public function getCloud(): Collection
    {
        return $this->cacheService->remember('tags:cloud', 3600, function () {
            return Tag::active()->where('usage_count', '>', 0)
                ->orderBy('usage_count', 'desc')
                ->get();
        });
    }

    public function getWithPosts(string $slug, string $sort = 'latest', int $perPage = 12): array
    {
        $tag = Tag::active()->where('slug', $slug)->firstOrFail();
        $query = $tag->posts()->published()->with('category', 'author');

        match ($sort) {
            'popular' => $query->orderBy('views_count', 'desc'),
            'oldest' => $query->orderBy('published_at', 'asc'),
            default => $query->orderBy('published_at', 'desc'),
        };

        return [
            'tag' => $tag,
            'posts' => $query->paginate($perPage)->withQueryString(),
            'trendingTags' => $this->getTrending(8),
        ];
    }

    public function getRelated(Tag $tag, int $limit = 6): Collection
    {
        $tagIds = DB::table('post_tag')
            ->whereIn('post_id', function ($q) use ($tag) {
                $q->select('post_id')->from('post_tag')->where('tag_id', $tag->id);
            })
            ->where('tag_id', '!=', $tag->id)
            ->select('tag_id', DB::raw('COUNT(*) as co_occurrence'))
            ->groupBy('tag_id')
            ->orderByDesc('co_occurrence')
            ->limit($limit)
            ->pluck('tag_id');

        return Tag::active()->whereIn('id', $tagIds)->get();
    }

    public function merge(int $sourceId, int $targetId): Tag
    {
        $source = Tag::findOrFail($sourceId);
        $target = Tag::findOrFail($targetId);

        DB::transaction(function () use ($source, $target) {
            $existingPairs = DB::table('post_tag')
                ->where('tag_id', $target->id)
                ->pluck('post_id')
                ->toArray();

            DB::table('post_tag')
                ->where('tag_id', $source->id)
                ->whereNotIn('post_id', $existingPairs)
                ->update(['tag_id' => $target->id]);

            DB::table('post_tag')
                ->where('tag_id', $source->id)
                ->delete();

            $source->delete();

            $target->usage_count = $target->posts()->count();
            $target->save();
        });

        $this->cacheService->forgetByPattern('tags:*');

        return $target->fresh();
    }

    public function suggestFromContent(string $content, int $limit = 5): array
    {
        $prompt = "Extract the {$limit} most relevant tags/keywords from this content. Return as a comma-separated list. Use single words or short phrases (1-3 words).\n\nContent:\n{$content}";

        $response = $this->aiService->generateContent($prompt, 'keywords');

        if (empty($response)) {
            return [];
        }

        $suggestions = array_map('trim', explode(',', $response));
        $suggestions = array_slice($suggestions, 0, $limit);

        $existing = Tag::active()->pluck('id', 'name')->toArray();
        $result = [];

        foreach ($suggestions as $suggestion) {
            $normalized = mb_strtolower(trim($suggestion));
            if (empty($normalized)) continue;
            $result[] = [
                'name' => $suggestion,
                'exists' => isset($existing[$suggestion]) || isset($existing[ucfirst($suggestion)]),
                'tag_id' => $existing[$suggestion] ?? $existing[ucfirst($suggestion)] ?? null,
            ];
        }

        return $result;
    }

    public function generateSeo(Tag $tag): void
    {
        $tag->seo()->updateOrCreate(
            ['seoable_id' => $tag->id, 'seoable_type' => Tag::class],
            [
                'meta_title' => $tag->seo_title ?? "{$tag->name} — Articles & Resources — " . config('app.name'),
                'meta_description' => $tag->seo_description ?? "Explore all articles tagged with '{$tag->name}'. Browse our collection of " . max($tag->usage_count, 1) . " posts about {$tag->name}.",
                'canonical_url' => route('tag.show', $tag->slug),
                'og_title' => $tag->seo_title ?? $tag->name,
                'og_description' => $tag->seo_description ?? "Articles tagged with {$tag->name}",
                'schema_type' => 'CollectionPage',
            ]
        );
    }

    public function recalculateTrending(): void
    {
        $recentDays = 7;
        $cutoff = now()->subDays($recentDays);

        $scores = DB::table('post_tag')
            ->join('posts', 'post_tag.post_id', '=', 'posts.id')
            ->where('posts.published_at', '>=', $cutoff)
            ->where('posts.status', 'published')
            ->select('post_tag.tag_id', DB::raw('COUNT(*) as recent_count'), DB::raw('AVG(COALESCE(posts.views_count, 0)) as avg_views'))
            ->groupBy('post_tag.tag_id')
            ->get();

        foreach ($scores as $score) {
            $tag = Tag::find($score->tag_id);
            if ($tag) {
                $trending = ($score->recent_count * 10) + min($score->avg_views / 100, 50);
                $tag->update(['trending_score' => min($trending, 99.99)]);
            }
        }

        $this->cacheService->forgetByPattern('tags:*');
    }

    public function search(string $term): Collection
    {
        return Tag::active()
            ->where('name', 'like', "%{$term}%")
            ->orWhere('slug', 'like', "%{$term}%")
            ->orderBy('usage_count', 'desc')
            ->take(20)
            ->get();
    }

    public function invalidateCache(): void
    {
        $this->cacheService->forgetByPattern('tags:*');
    }
}
