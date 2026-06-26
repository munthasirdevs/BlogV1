<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    public function autocomplete(Request $request): JsonResponse
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $results = $this->searchService->autocomplete($q);
        return response()->json($results);
    }

    public function trending(): JsonResponse
    {
        $trending = $this->searchService->getTrending();
        return response()->json($trending);
    }

    public function popular(): JsonResponse
    {
        $popular = $this->searchService->getPopularSearches();
        return response()->json($popular);
    }

    public function zeroResults(): JsonResponse
    {
        $zero = $this->searchService->getZeroResultQueries();
        return response()->json($zero);
    }
}
