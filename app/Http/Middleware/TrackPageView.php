<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Models\Post;
use App\Models\PostView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    private const BOT_PATTERNS = [
        'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python-requests',
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot',
        'facebookexternalhit', 'facebot', 'twitterbot', 'rogerbot', 'linkedinbot',
        'embedly', 'quora link preview', 'showyoubot', 'outbrain', 'pinterest',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            // Skip admin routes
            if (str_starts_with($request->path(), 'admin/') || $request->path() === 'admin') {
                return;
            }

            // Exclude bots
            $userAgent = $request->userAgent() ?? '';
            if ($this->isBot($userAgent)) {
                return;
            }

            $ipHash = hash('sha256', $request->ip() ?? '0.0.0.0');
            $deviceType = $this->detectDevice($userAgent);

            // Record page view
            PageView::create([
                'page_url' => $request->path(),
                'ip_hash' => $ipHash,
                'device_type' => $deviceType,
                'visited_at' => now(),
            ]);

            // If post page, also record post view and increment counter
            if (preg_match('#^blog/(.+)$#', $request->path(), $matches)) {
                $slug = $matches[1];
                $post = Post::where('slug', $slug)->first();

                if ($post) {
                    PostView::create([
                        'post_id' => $post->id,
                        'ip_hash' => $ipHash,
                        'device_type' => $deviceType,
                        'visited_at' => now(),
                    ]);

                    $post->increment('views_count');
                }
            }
        } catch (\Throwable $e) {
            Log::error('TrackPageView error: ' . $e->getMessage());
        }
    }

    private function isBot(string $userAgent): bool
    {
        $lower = strtolower($userAgent);
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private function detectDevice(string $userAgent): string
    {
        $lower = strtolower($userAgent);

        $mobileKeywords = ['mobile', 'iphone', 'ipod', 'android', 'blackberry', 'windows phone', 'opera mini', 'iemobile'];

        $tabletKeywords = ['tablet', 'ipad', 'playbook', 'silk', 'kindle'];

        foreach ($tabletKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'tablet';
            }
        }

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'mobile';
            }
        }

        return 'desktop';
    }
}
