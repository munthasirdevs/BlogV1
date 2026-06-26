<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\ContentGraphService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentGraphController extends Controller
{
    public function __construct(
        protected ContentGraphService $contentGraphService
    ) {}

    public function rebuild(): RedirectResponse
    {
        $count = $this->contentGraphService->rebuildAllGraphs();
        return redirect()->route('admin.dashboard')->with('success', "Content graph rebuilt: {$count} links created.");
    }

    public function suggest(Post $post): JsonResponse
    {
        $suggestions = $this->contentGraphService->aiSuggestLinks($post);
        return response()->json(['success' => true, 'suggestions' => $suggestions]);
    }

    public function orphans(): View
    {
        $orphans = $this->contentGraphService->findOrphans();
        $totalPosts = Post::published()->count();
        return view('admin.seo.index', compact('orphans', 'totalPosts'));
    }
}
