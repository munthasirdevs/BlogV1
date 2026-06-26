<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\ScheduledJob;
use App\Services\AI\AIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PublishScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public ?int $scheduledJobId = null;

    public function __construct(
        public int $postId,
        ?int $scheduledJobId = null
    ) {
        $this->scheduledJobId = $scheduledJobId;
    }

    public function handle(AIService $aiService): void
    {
        $scheduledJob = null;
        if ($this->scheduledJobId) {
            $scheduledJob = ScheduledJob::find($this->scheduledJobId);
        }

        try {
            $post = Post::find($this->postId);

            if (!$post) {
                Log::error('PublishScheduledPostJob: Post not found', ['post_id' => $this->postId]);
                $scheduledJob?->update(['status' => 'failed', 'error_message' => 'Post not found']);
                return;
            }

            if ($post->status !== 'scheduled') {
                Log::warning('PublishScheduledPostJob: Post is not scheduled', ['post_id' => $this->postId, 'status' => $post->status]);
                $scheduledJob?->update(['status' => 'skipped', 'error_message' => "Status is {$post->status}, not scheduled"]);
                return;
            }

            if ($post->scheduled_at && $post->scheduled_at->gt(now())) {
                $seconds = $post->scheduled_at->diffInSeconds(now());
                $this->release($seconds > 0 ? $seconds : 5);
                return;
            }

            // AI pre-publish check (non-blocking, logs warnings)
            try {
                $content = $post->content ?? '';
                $seoCheck = $aiService->generateContent(
                    "Analyze this blog post for publishing readiness. Check for: grammar issues, missing headings, SEO optimization. Return 'READY' or specific issues found.\n\nTitle: {$post->title}\nContent: {$content}",
                    'audit'
                );
                if ($seoCheck && !str_contains(strtoupper($seoCheck), 'READY')) {
                    Log::info('AI pre-publish suggestions for post', [
                        'post_id' => $post->id,
                        'suggestions' => mb_substr($seoCheck, 0, 500),
                    ]);
                }
            } catch (\Exception $e) {
                Log::debug('AI pre-publish check skipped', ['error' => $e->getMessage()]);
            }

            $post->update([
                'status' => 'published',
                'published_at' => now(),
                'is_scheduled' => false,
            ]);

            if ($scheduledJob) {
                $scheduledJob->update([
                    'status' => 'completed',
                    'executed_at' => now(),
                    'queue_name' => $this->queue,
                ]);
            }

            try {
                Cache::forget('posts:recent');
                Cache::forget('homepage');
            } catch (\Exception $e) {
            }

            Log::info('PublishScheduledPostJob: Post published successfully', [
                'post_id' => $this->postId,
                'title' => $post->title,
            ]);

        } catch (\Exception $e) {
            Log::error('PublishScheduledPostJob: Failed', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);

            $scheduledJob?->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $scheduledJob->retry_count + 1,
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff * $this->attempts());
            }
        }
    }
}
