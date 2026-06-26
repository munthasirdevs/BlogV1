<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Search\SearchIndexerService;
use App\Services\Search\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService,
        protected SearchIndexerService $searchIndexer
    ) {}

    public function index(Request $request): View
    {
        $keyword = $request->get('q', '');
        $useAi = $request->boolean('ai');
        $categoryId = $request->get('category_id');
        $tagId = $request->get('tag_id');
        $sort = $request->get('sort');

        if ($keyword) {
            if ($categoryId || $tagId || $sort) {
                $results = $this->searchIndexer->searchWithFilters($keyword, [
                    'category_id' => $categoryId,
                    'tag_id' => $tagId,
                    'sort' => $sort,
                ]);
            } elseif ($useAi) {
                $results = $this->searchService->aiEnhancedSearch($keyword);
            } else {
                $results = $this->searchService->searchPosts($keyword);
            }
        } else {
            $results = collect();
        }

        $trending = $this->searchService->getTrending();
        $facets = $this->searchIndexer->getFacets();

        return view('pages.search.index', compact('results', 'keyword', 'trending', 'facets'));
    }
}
