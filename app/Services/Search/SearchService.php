<?php

namespace App\Services\Search;

use App\Models\Post;
use App\Models\SearchLog;
use App\Services\AI\AIService;
use App\Services\CacheService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function __construct(
        protected CacheService $cacheService,
        protected AIService $aiService
    ) {}

    public function searchPosts(string $keyword, int $perPage = 12): LengthAwarePaginator
    {
        $results = Post::published()
            ->where(function ($q) use ($keyword) {
                $escaped = str_replace(['%', '_'], ['\%', '\_'], $keyword);
                $q->whereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$keyword])
                  ->orWhere('title', 'like', '%' . $escaped . '%')
                  ->orWhere('content', 'like', '%' . $escaped . '%');
            })
            ->with('category', 'author')
            ->orderBy('views_count', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        SearchLog::create([
            'keyword' => $keyword,
            'results_count' => $results->total(),
            'user_id' => auth()->id(),
            'searched_at' => now(),
        ]);

        return $results;
    }

    public function aiEnhancedSearch(string $query, int $perPage = 12): LengthAwarePaginator
    {
        $enhancedQuery = $query;
        try {
            $suggestion = $this->aiService->generateContent(
                "Given this search query: '{$query}', suggest a better, more specific search query that would find relevant blog content. Return ONLY the improved query, no explanation.",
                'meta_description'
            );
            if (!empty($suggestion) && strlen($suggestion) > 3) {
                $enhancedQuery = trim($suggestion);
            }
        } catch (\Exception $e) {
        }

        $results = Post::published()
            ->where(function ($q) use ($query, $enhancedQuery) {
                $q->whereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$enhancedQuery])
                  ->orWhereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$query])
                  ->orWhere('title', 'like', '%' . $query . '%')
                  ->orWhere('content', 'like', '%' . $query . '%');
            })
            ->with('category', 'author')
            ->orderByRaw("CASE WHEN title LIKE ? THEN 3 WHEN title LIKE ? THEN 2 ELSE 1 END", ["{$query}%", "%{$query}%"])
            ->orderBy('views_count', 'desc')
            ->paginate($perPage);

        SearchLog::create([
            'keyword' => $query,
            'results_count' => $results->total(),
            'user_id' => auth()->id(),
            'searched_at' => now(),
        ]);

        return $results;
    }

    public function autocomplete(string $prefix): Collection
    {
        $cacheKey = 'search:autocomplete:' . md5($prefix);
        return $this->cacheService->remember($cacheKey, 300, function () use ($prefix) {
            return Post::published()
                ->where('title', 'like', $prefix . '%')
                ->orderBy('views_count', 'desc')
                ->take(5)
                ->get(['id', 'title', 'slug']);
        });
    }

    public function getTrending(int $limit = 5): Collection
    {
        return $this->cacheService->remember('search:trending', 1800, function () use ($limit) {
            return SearchLog::select('keyword', DB::raw('COUNT(*) as count'))
                ->where('searched_at', '>=', now()->subDays(7))
                ->groupBy('keyword')
                ->orderByDesc('count')
                ->take($limit)
                ->get();
        });
    }

    public function getPopularSearches(int $limit = 10): Collection
    {
        return SearchLog::selectRaw('keyword, COUNT(*) as count')
            ->groupBy('keyword')
            ->orderByDesc('count')
            ->take($limit)
            ->get();
    }

    public function getSearchSuggestions(string $prefix): Collection
    {
        return SearchLog::where('keyword', 'like', $prefix . '%')
            ->selectRaw('keyword, COUNT(*) as count')
            ->groupBy('keyword')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->pluck('keyword');
    }

    public function getZeroResultQueries(): Collection
    {
        return SearchLog::where('results_count', 0)
            ->select('keyword', DB::raw('COUNT(*) as count'))
            ->groupBy('keyword')
            ->orderByDesc('count')
            ->take(20)
            ->get();
    }
}
