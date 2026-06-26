<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Analytics\AnalyticsService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    public function index(): View
    {
        $data = $this->analyticsService->getDashboardData();
        return view('admin.analytics.index', compact('data'));
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->analyticsService->getDashboardData();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="analytics-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Views (30 days)', $data['totalViews'] ?? 0]);
            fputcsv($handle, ['Today Views', $data['todayViews'] ?? 0]);

            if (!empty($data['topPosts'])) {
                fputcsv($handle, []);
                fputcsv($handle, ['Top Posts']);
                fputcsv($handle, ['Title', 'Views', 'Shares']);
                foreach ($data['topPosts'] as $post) {
                    fputcsv($handle, [$post->title, $post->views_count, $post->shares_count ?? 0]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function postsCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="post-analytics-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Title', 'Slug', 'Status', 'Views', 'Shares', 'SEO Score', 'Published']);

            Post::with('category')->chunk(100, function ($posts) use ($handle) {
                foreach ($posts as $post) {
                    fputcsv($handle, [
                        $post->title, $post->slug, $post->status,
                        $post->views_count, $post->shares_count ?? 0,
                        $post->seo_score ?? 0,
                        $post->published_at?->toDateString() ?? '',
                    ]);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
