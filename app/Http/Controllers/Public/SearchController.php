<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = $request->get('q', '');

        $results = collect();
        $resultsCount = 0;

        if ($keyword) {
            $results = Post::published()
                ->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('content', 'like', '%' . $keyword . '%');
                })
                ->with('category', 'author')
                ->orderBy('published_at', 'desc')
                ->paginate(12);

            $resultsCount = $results->total();

            // Log search
            SearchLog::create([
                'keyword' => $keyword,
                'results_count' => $resultsCount,
                'user_id' => auth()->id(),
                'searched_at' => now(),
            ]);
        }

        return view('pages.search.index', compact('results', 'keyword', 'resultsCount'));
    }
}
