<?php

namespace App\Services\SEO;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Http;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapService
{
    public function generate(): Sitemap
    {
        $sitemap = Sitemap::create();

        $publishedPosts = Post::with('category', 'tags')
            ->published()
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->get();

        foreach ($publishedPosts as $post) {
            $sitemap->add(
                Url::create(route('blog.show', $post->slug))
                    ->setLastModificationDate($post->updated_at ?? $post->published_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.8)
            );
        }

        $categories = Category::published()->get();
        foreach ($categories as $category) {
            $sitemap->add(
                Url::create(route('category.show', $category->slug))
                    ->setLastModificationDate($category->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.7)
            );
        }

        $tags = Tag::active()->has('posts', '>', 2)->get();
        foreach ($tags as $tag) {
            $sitemap->add(
                Url::create(route('tag.show', $tag->slug))
                    ->setLastModificationDate($tag->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.5)
            );
        }

        $staticPages = [
            ['route' => 'about', 'priority' => 0.6, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['route' => 'contact', 'priority' => 0.4, 'freq' => Url::CHANGE_FREQUENCY_YEARLY],
        ];

        foreach ($staticPages as $page) {
            $sitemap->add(
                Url::create(route($page['route']))
                    ->setChangeFrequency($page['freq'])
                    ->setPriority($page['priority'])
            );
        }

        $sitemap->writeToDisk('public', 'sitemap.xml');

        return $sitemap;
    }

    public function pingSearchEngines(): void
    {
        $sitemapUrl = url('sitemap.xml');

        $googleUrl = 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);
        $bingUrl = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl);

        try {
            Http::timeout(5)->get($googleUrl);
        } catch (\Exception $e) {
        }

        try {
            Http::timeout(5)->get($bingUrl);
        } catch (\Exception $e) {
        }
    }
}
