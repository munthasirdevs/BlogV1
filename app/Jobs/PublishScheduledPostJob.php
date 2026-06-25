<?php

namespace App\Jobs;

use App\Models\Post;
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

    public function __construct(
        public int $postId
    ) {}

    public function handle(): void
    {
        try {
            $post = Post::find($this->postId);

            if (!$post) {
                Log::error('PublishScheduledPostJob: Post not found', ['post_id' => $this->postId]);
                return;
            }

            if ($post->status !== 'scheduled') {
                Log::warning('PublishScheduledPostJob: Post is not scheduled', [
                    'post_id' => $this->postId,
                    'status' => $post->status,
                ]);
                return;
            }

            if ($post->scheduled_at && $post->scheduled_at->gt(now())) {
                $this->release($post->scheduled_at->diffInSeconds(now()));
                return;
            }

            $post->update([
                'status' => 'published',
                'published_at' => now(),
                'is_scheduled' => false,
            ]);

            Cache::tags(['posts'])->flush();

            try {
                \App\Notifications\SitemapNotification::dispatch();
            } catch (\Exception $e) {
                Log::warning('PublishScheduledPostJob: Failed to dispatch sitemap notification', [
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('PublishScheduledPostJob: Post published successfully', [
                'post_id' => $this->postId,
                'title' => $post->title,
            ]);
        } catch (\Exception $e) {
            Log::error('PublishScheduledPostJob: Failed to publish post', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release(60);
            }
        }
    }
}
