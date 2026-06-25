<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Services\SEO\SEOService;
use App\Services\SEO\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SEOController extends Controller
{
    public function __construct(
        protected SEOService $seoService,
        protected SitemapService $sitemapService
    ) {}

    public function index(): View
    {
        $totalPosts = Post::count();

        $indexedPosts = Post::published()->count();

        $avgSeoScore = Post::published()->avg('seo_score') ?? 0;

        $lowScorePosts = Post::with('author', 'category')
            ->published()
            ->where('seo_score', '<', 50)
            ->orWhereNull('seo_score')
            ->where('status', 'published')
            ->orderBy('seo_score')
            ->limit(10)
            ->get();

        $sitemapExists = file_exists(public_path('sitemap.xml'));

        $stats = [
            'total_posts' => $totalPosts,
            'indexed_posts' => $indexedPosts,
            'average_seo_score' => round($avgSeoScore, 1),
            'low_score_posts' => $lowScorePosts,
            'sitemap_exists' => $sitemapExists,
            'sitemap_url' => $sitemapExists ? url('sitemap.xml') : null,
        ];

        return view('admin.seo.index', compact('stats'));
    }

    public function edit($id): View
    {
        $post = Post::with('seo')->findOrFail($id);

        return view('admin.seo.edit', compact('post'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'robots_directive' => ['nullable', 'string', 'max:100'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
            'schema_type' => ['nullable', 'string', 'max:100'],
        ]);

        $post->seo()->updateOrCreate(
            ['seoable_id' => $post->id, 'seoable_type' => Post::class],
            $validated
        );

        return redirect()->back()->with('success', 'SEO meta updated successfully.');
    }

    public function generateSitemap(): RedirectResponse
    {
        $this->sitemapService->generate();
        $this->sitemapService->pingSearchEngines();

        return redirect()->route('admin.seo.index')->with('success', 'Sitemap generated and search engines notified.');
    }

    public function analyze($postId): JsonResponse
    {
        $post = Post::with('seo')->findOrFail($postId);

        $analysis = $this->seoService->analyzePost($post);

        $post->seo_score = $analysis['overall_score'];
        $post->save();

        return response()->json($analysis);
    }
}
