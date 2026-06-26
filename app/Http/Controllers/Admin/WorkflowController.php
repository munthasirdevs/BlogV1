<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostRevision;
use App\Services\SEO\SEOService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function __construct(
        protected SEOService $seoService
    ) {}

    public function index(): View
    {
        $drafts = Post::where('status', 'draft')->with('author', 'category')
            ->orderBy('updated_at', 'desc')->paginate(10, ['*'], 'drafts_page');

        $inReview = Post::whereIn('status', ['review', 'seo_review'])->with('author', 'category')
            ->orderBy('updated_at', 'desc')->paginate(10, ['*'], 'review_page');

        $approved = Post::where('status', 'approved')->with('author', 'category')
            ->orderBy('updated_at', 'desc')->paginate(10, ['*'], 'approved_page');

        $scheduled = Post::where('status', 'scheduled')->with('author', 'category')
            ->orderBy('scheduled_at')->paginate(10, ['*'], 'scheduled_page');

        return view('admin.workflow.index', compact('drafts', 'inReview', 'approved', 'scheduled'));
    }

    public function autosave(Request $request): JsonResponse
    {
        $postId = $request->input('post_id');
        $post = $postId ? Post::find($postId) : null;

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        if ($post->author_id !== auth()->id() && !auth()->user()->can('edit_posts')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $post->update($fields);

        return response()->json([
            'success' => true,
            'updated_at' => $post->updated_at->toIso8601String(),
            'word_count' => str_word_count(strip_tags($post->content ?? '')),
        ]);
    }

    public function score(Request $request): JsonResponse
    {
        $request->validate(['content' => ['required', 'string']]);

        $content = $request->content;
        $text = strip_tags($content);
        $wordCount = str_word_count($text);
        $readingTime = max(1, (int) ceil($wordCount / 200));

        preg_match_all('/<h[1-6][^>]*>/i', $content, $headings);
        $headingCount = count($headings[0]);

        preg_match_all('/<img[^>]+>/i', $content, $images);
        $imageCount = count($images[0]);

        preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>/i', $content, $links);
        $linkCount = count($links[0]);

        $score = 0;
        $score += min($wordCount / 800 * 25, 25);       // Content length (25%)
        $score += min($headingCount / 3 * 20, 20);       // Heading structure (20%)
        $score += min($imageCount / 2 * 15, 15);          // Images (15%)
        $score += min($linkCount / 3 * 15, 15);           // Links (15%)
        $score += $readingTime >= 3 ? 15 : $readingTime * 5; // Readability (15%)
        $score += $wordCount > 100 ? 10 : 5;              // Minimum content (10%)

        return response()->json([
            'score' => (int) round($score),
            'word_count' => $wordCount,
            'reading_time' => $readingTime,
            'headings' => $headingCount,
            'images' => $imageCount,
            'links' => $linkCount,
            'breakdown' => [
                'content_length' => min($wordCount / 800 * 25, 25),
                'headings' => min($headingCount / 3 * 20, 20),
                'images' => min($imageCount / 2 * 15, 15),
                'links' => min($linkCount / 3 * 15, 15),
                'readability' => $readingTime >= 3 ? 15 : $readingTime * 5,
                'minimum' => $wordCount > 100 ? 10 : 5,
            ],
        ]);
    }
}
