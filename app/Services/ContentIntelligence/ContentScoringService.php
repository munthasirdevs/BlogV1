<?php

namespace App\Services\ContentIntelligence;

use App\Models\Post;
use App\Services\SEO\SEOService;

class ContentScoringService
{
    public function __construct(
        protected SEOService $seoService
    ) {}

    public function scorePost(Post $post): array
    {
        $seoAnalysis = $this->seoService->analyzePost($post);
        $readability = $this->seoService->calculateReadability($post->content ?? '');
        $engagementScore = $this->calculateEngagementScore($post);
        $freshnessScore = $this->calculateFreshnessScore($post);
        $completenessScore = $this->calculateCompletenessScore($post);

        $overallScore = (int) round(
            $seoAnalysis['overall_score'] * 0.35 +
            $readability['score'] * 0.20 +
            $engagementScore * 0.20 +
            $freshnessScore * 0.10 +
            $completenessScore * 0.15
        );

        return [
            'overall' => min(100, $overallScore),
            'seo' => $seoAnalysis['overall_score'],
            'readability' => min(100, (int) $readability['score']),
            'engagement' => $engagementScore,
            'freshness' => $freshnessScore,
            'completeness' => $completenessScore,
            'breakdown' => [
                'title' => $seoAnalysis['title_score'],
                'description' => $seoAnalysis['description_score'],
                'headings' => $seoAnalysis['headings_score'],
                'content' => $seoAnalysis['content_score'],
                'images' => $seoAnalysis['images_score'],
                'links' => $seoAnalysis['links_score'],
            ],
            'readability_level' => $readability['level'],
            'suggestions' => $seoAnalysis['recommendations'],
        ];
    }

    protected function calculateEngagementScore(Post $post): int
    {
        $views = $post->views_count ?? 0;
        $shares = $post->shares_count ?? 0;
        $comments = $post->comments()->count();
        $daysSincePublished = max(1, now()->diffInDays($post->published_at ?? $post->created_at));

        $dailyViews = $views / $daysSincePublished;
        $score = 0;
        $score += min($dailyViews * 2, 40);
        $score += min($shares * 5, 30);
        $score += min($comments * 10, 30);

        return min(100, (int) $score);
    }

    protected function calculateFreshnessScore(Post $post): int
    {
        $publishedAt = $post->published_at ?? $post->created_at;
        $daysSincePublished = now()->diffInDays($publishedAt);

        return match (true) {
            $daysSincePublished <= 7 => 100,
            $daysSincePublished <= 30 => 80,
            $daysSincePublished <= 90 => 60,
            $daysSincePublished <= 180 => 40,
            $daysSincePublished <= 365 => 20,
            default => 10,
        };
    }

    protected function calculateCompletenessScore(Post $post): int
    {
        $score = 0;
        $score += !empty($post->title) ? 15 : 0;
        $score += !empty($post->excerpt) ? 15 : 0;
        $score += !empty($post->content) && strlen($post->content) > 500 ? 20 : 5;
        $score += !empty($post->featured_image) ? 15 : 0;
        $score += $post->category_id ? 15 : 0;
        $score += $post->tags()->count() > 0 ? 10 : 0;
        $score += $post->seo()->exists() ? 10 : 0;

        return min(100, $score);
    }

    public function getContentGaps(int $tenantId): array
    {
        $lowSeoPosts = Post::where('tenant_id', $tenantId)
            ->where('seo_score', '<', 50)
            ->orWhereNull('seo_score')
            ->count();

        $postsWithoutExcerpt = Post::where('tenant_id', $tenantId)
            ->whereNull('excerpt')
            ->count();

        $postsWithoutCategory = Post::where('tenant_id', $tenantId)
            ->whereNull('category_id')
            ->count();

        $postsWithoutFeaturedImage = Post::where('tenant_id', $tenantId)
            ->whereNull('featured_image')
            ->count();

        return [
            'low_seo_score' => $lowSeoPosts,
            'missing_excerpt' => $postsWithoutExcerpt,
            'missing_category' => $postsWithoutCategory,
            'missing_featured_image' => $postsWithoutFeaturedImage,
        ];
    }
}
