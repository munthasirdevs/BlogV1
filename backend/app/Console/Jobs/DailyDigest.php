<?php

namespace App\Console\Jobs;

use App\Models\Post;
use App\Services\NewsletterService;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class DailyDigest
 *
 * Scheduled job to send daily digest emails.
 * Runs at 8:00 AM every day.
 */
class DailyDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The newsletter service instance.
     */
    protected NewsletterService $newsletterService;

    /**
     * The subscription service instance.
     */
    protected SubscriptionService $subscriptionService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        NewsletterService $newsletterService,
        SubscriptionService $subscriptionService
    ) {
        $this->newsletterService = $newsletterService;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Daily digest job started');

        try {
            // Get posts from yesterday
            $startDate = now()->subDay()->startOfDay();
            $endDate = now()->subDay()->endOfDay();

            $posts = $this->getPostsForDateRange($startDate, $endDate);

            if (empty($posts)) {
                Log::info('No posts for daily digest', ['date' => $startDate->toDateString()]);
                return;
            }

            // Get daily digest subscribers
            $subscribers = $this->subscriptionService->getDigestSubscribers('daily');

            if (empty($subscribers)) {
                Log::info('No daily digest subscribers');
                return;
            }

            // Format posts for email
            $formattedPosts = $this->formatPosts($posts);

            // Send digest to each subscriber
            $count = 0;
            foreach ($subscribers as $subscriber) {
                \App\Jobs\SendDigestEmail::dispatch(
                    $subscriber,
                    $formattedPosts,
                    'daily',
                    $startDate->format('M j, Y'),
                    $endDate->format('M j, Y')
                );
                $count++;
            }

            Log::info('Daily digest emails queued', [
                'count' => $count,
                'posts' => count($posts),
            ]);
        } catch (\Exception $e) {
            Log::error('Daily digest job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get posts for the specified date range.
     */
    protected function getPostsForDateRange($startDate, $endDate): array
    {
        return Post::published()
            ->whereBetween('published_at', [$startDate, $endDate])
            ->with(['category', 'author', 'tags'])
            ->orderByDesc('published_at')
            ->get()
            ->toArray();
    }

    /**
     * Format posts for email template.
     */
    protected function formatPosts(array $posts): array
    {
        return collect($posts)->map(function ($post) {
            return [
                'title' => $post['title'],
                'slug' => $post['slug'],
                'url' => url('/blog/' . $post['slug']),
                'excerpt' => $post['excerpt'] ?? strip_tags($post['content']),
                'content' => strip_tags($post['content']),
                'category' => $post['category']['name'] ?? null,
                'author' => $post['author']['name'] ?? null,
                'published_at' => $post['published_at'],
            ];
        })->toArray();
    }
}
