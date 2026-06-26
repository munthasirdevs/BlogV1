<?php

namespace App\Services;

use App\Models\ContentLink;
use App\Models\Post;
use App\Services\AI\AIService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContentGraphService
{
    public function __construct(
        protected CacheService $cacheService,
        protected AIService $aiService
    ) {}

    public function getRelatedPosts(Post $post, int $limit = 6): Collection
    {
        $cached = $this->cacheService->remember("post:{$post->id}:related", 3600, function () use ($post, $limit) {
            $linkIds = ContentLink::where('source_type', 'post')
                ->where('source_id', $post->id)
                ->whereIn('target_type', ['post'])
                ->orderBy('weight_score', 'desc')
                ->take($limit)
                ->pluck('target_id');

            if ($linkIds->isNotEmpty()) {
                return Post::published()->whereIn('id', $linkIds)->with('category', 'author')->get();
            }

            return $post->related($limit);
        });

        return $cached;
    }

    public function findOrphans(): Collection
    {
        $linkedPostIds = ContentLink::where('source_type', 'post')
            ->where('target_type', 'post')
            ->distinct()
            ->pluck('source_id')
            ->merge(
                ContentLink::where('target_type', 'post')
                    ->where('source_type', 'post')
                    ->distinct()
                    ->pluck('target_id')
            )->unique();

        return Post::published()
            ->whereNotIn('id', $linkedPostIds)
            ->with('category')
            ->orderBy('published_at', 'desc')
            ->get();
    }

    public function aiSuggestLinks(Post $post, int $limit = 5): array
    {
        $candidates = Post::published()
            ->where('id', '!=', $post->id)
            ->where('category_id', $post->category_id)
            ->orWhereHas('tags', fn($q) => $q->whereIn('tags.id', $post->tags->pluck('id')))
            ->where('id', '!=', $post->id)
            ->with('category')
            ->orderBy('views_count', 'desc')
            ->take($limit * 2)
            ->get();

        if ($candidates->isEmpty()) return [];

        $prompt = "Given this blog post title '{$post->title}', rank the following articles by their relevance as internal links. Return the indices (0-based) of the top {$limit} most relevant articles, comma-separated, ordered by relevance.\n\nCandidate articles:\n";
        foreach ($candidates as $i => $c) {
            $prompt .= "{$i}. {$c->title} [Category: {$c->category?->name}]\n";
        }

        try {
            $response = $this->aiService->generateContent($prompt, 'audit');
            preg_match_all('/\d+/', $response, $matches);
            $indices = array_unique(array_map('intval', $matches[0] ?? []));

            $suggestions = [];
            foreach ($indices as $idx) {
                if (isset($candidates[$idx]) && count($suggestions) < $limit) {
                    $c = $candidates[$idx];
                    $anchor = $this->generateAnchorText($post, $c);
                    $suggestions[] = [
                        'post_id' => $c->id,
                        'title' => $c->title,
                        'slug' => $c->slug,
                        'anchor_text' => $anchor,
                        'url' => route('blog.show', $c->slug),
                        'score' => round(1.0 - ($idx / count($candidates)), 2),
                    ];
                }
            }

            return $suggestions;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function buildGraphForPost(Post $post): int
    {
        ContentLink::where('source_type', 'post')->where('source_id', $post->id)->delete();

        $related = $post->related(5);
        $count = 0;

        foreach ($related as $target) {
            $weight = $this->calculateWeight($post, $target);
            ContentLink::create([
                'source_type' => 'post',
                'source_id' => $post->id,
                'target_type' => 'post',
                'target_id' => $target->id,
                'link_type' => 'related',
                'anchor_text' => $target->title,
                'weight_score' => $weight,
                'ai_generated' => false,
            ]);
            $count++;
        }

        $this->cacheService->forget("post:{$post->id}:related");
        return $count;
    }

    public function rebuildAllGraphs(): int
    {
        $total = 0;
        Post::published()->chunk(50, function ($posts) use (&$total) {
            foreach ($posts as $post) {
                $total += $this->buildGraphForPost($post);
            }
        });
        return $total;
    }

    protected function calculateWeight(Post $source, Post $target): float
    {
        $weight = 0.5;

        if ($source->category_id && $source->category_id === $target->category_id) {
            $weight += 0.2;
        }

        $sharedTags = $source->tags->pluck('id')->intersect($target->tags->pluck('id'))->count();
        $weight += min($sharedTags * 0.1, 0.2);

        $recency = now()->diffInDays($target->published_at ?? $target->created_at);
        $weight += max(0, 0.1 - ($recency / 365) * 0.1);

        return round(min($weight, 1.0), 2);
    }

    protected function generateAnchorText(Post $source, Post $target): string
    {
        $sharedTags = $source->tags->pluck('name')->intersect($target->tags->pluck('name'));
        if ($sharedTags->isNotEmpty()) {
            return $sharedTags->first();
        }
        return $target->title;
    }

    public function invalidateCache(): void
    {
        $this->cacheService->forgetByPattern('post:*:related');
    }
}
