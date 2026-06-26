<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\CacheService;
use App\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService,
        protected CacheService $cacheService
    ) {}

    public function show(Request $request, string $slug): View
    {
        $data = $this->tagService->getWithPosts($slug, $request->get('sort', 'latest'));
        $tag = $data['tag'];
        $posts = $data['posts'];
        $trendingTags = $data['trendingTags'];

        $relatedTags = $tag->related(6);
        $allTags = $this->tagService->getCloud();

        if (!$tag->seo()->exists()) {
            $tag->generateSeo();
        }

        return view('pages.tag.show', compact('tag', 'posts', 'trendingTags', 'relatedTags', 'allTags'));
    }
}
