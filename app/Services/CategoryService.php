<?php

namespace App\Services;

use App\Models\Category;
use App\Services\AI\AIService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function __construct(
        protected CacheService $cacheService,
        protected AIService $aiService
    ) {}

    public function getAll(): Collection
    {
        return $this->cacheService->getCategories();
    }

    public function getTree(): Collection
    {
        return $this->cacheService->getCategoryTree();
    }

    public function getBySlug(string $slug): ?Category
    {
        return $this->cacheService->getCategoryBySlug($slug);
    }

    public function getWithPosts(string $slug, string $sort = 'latest', int $perPage = 12)
    {
        $category = Category::published()->where('slug', $slug)->firstOrFail();

        $query = $category->posts()->published()->with('tags', 'author');

        match ($sort) {
            'popular' => $query->orderBy('views_count', 'desc'),
            'oldest' => $query->orderBy('published_at', 'asc'),
            default => $query->orderBy('published_at', 'desc'),
        };

        return [
            'category' => $category,
            'posts' => $query->paginate($perPage)->withQueryString(),
        ];
    }

    public function search(string $term): Collection
    {
        return Category::where('name', 'like', "%{$term}%")
            ->orWhere('slug', 'like', "%{$term}%")
            ->published()
            ->orderBy('sort_order')
            ->get();
    }

    public function suggestFromContent(string $content): array
    {
        $prompt = "Based on this article content, suggest the single most relevant category from: Technology, Design, Business, Lifestyle, Science, Health. Return ONLY the category name.\n\n{$content}";

        $response = $this->aiService->generateContent($prompt, 'category');
        $suggested = trim($response);

        $category = Category::where('name', 'like', "%{$suggested}%")->first();

        return [
            'suggested_category' => $category?->name ?? 'Technology',
            'category_id' => $category?->id,
            'confidence' => $category ? 0.85 : 0.3,
        ];
    }

    public function suggestSeo(Category $category): array
    {
        $prompt = "Generate JSON SEO metadata for a blog category named '{$category->name}'. Description: '{$category->short_description}'. Return {\"meta_title\": \"...\", \"meta_description\": \"...\", \"meta_keywords\": \"...\"}";

        $response = $this->aiService->generateContent($prompt, 'meta_description');
        $parsed = json_decode($response, true);

        return $parsed ?: [
            'meta_title' => $category->name . ' — ' . config('app.name'),
            'meta_description' => $category->short_description ?? "Explore articles about {$category->name}.",
            'meta_keywords' => $category->name,
        ];
    }

    public function generateDescription(Category $category): string
    {
        $prompt = "Write a 2-3 sentence description for a blog category called '{$category->name}' that covers {$category->name} topics, articles, and tutorials. Make it engaging and SEO-friendly.";

        $response = $this->aiService->generateContent($prompt, 'article');

        if (!empty($response)) {
            $clean = strip_tags($response);
            $clean = preg_replace('/\s+/', ' ', $clean);
            return trim(mb_substr($clean, 0, 500));
        }

        return $category->short_description ?? "Articles about {$category->name}.";
    }

    public function invalidateCache(): void
    {
        $this->cacheService->invalidateCategories();
    }
}
