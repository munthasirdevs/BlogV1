<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Post;
use App\Services\SEO\SEOService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function __construct(
        protected SEOService $seoService
    ) {}

    public function analyze(Request $request): JsonResponse
    {
        $request->validate(['post_id' => ['required', 'exists:posts,id']]);

        $post = Post::with('seo')->findOrFail($request->post_id);
        $analysis = $this->seoService->analyzePost($post);

        return ApiResponse::success($analysis, 'SEO analysis complete');
    }
}
