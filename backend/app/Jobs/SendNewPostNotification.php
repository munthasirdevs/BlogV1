<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\Post;
use App\Models\EmailTracking;
use App\Mail\NewPostNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendNewPostNotification
 *
 * Job to send new post notification emails.
 */
class SendNewPostNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The subscription instance.
     */
    public Subscription $subscription;

    /**
     * The post instance.
     */
    public Post $post;

    /**
     * Related posts.
     */
    public array $relatedPosts;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Subscription $subscription,
        Post $post,
        array $relatedPosts = []
    ) {
        $this->subscription = $subscription;
        $this->post = $post;
        $this->relatedPosts = $relatedPosts;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Create email tracking record
            $tracking = EmailTracking::create([
                'subscription_id' => $this->subscription->id,
                'email_type' => EmailTracking::TYPE_NEW_POST,
                'subject' => 'New Post: ' . $this->post->title,
                'sent_at' => now(),
                'metadata' => [
                    'post_id' => $this->post->id,
                    'post_title' => $this->post->title,
                ],
            ]);

            // Send the email
            $mailable = new NewPostNotification(
                $this->subscription,
                $this->post,
                $this->relatedPosts
            );
            $mailable->setTrackingId($tracking->id);

            Mail::to($this->subscription->email)->send($mailable);

            Log::info('New post notification sent', [
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'post_id' => $this->post->id,
                'tracking_id' => $tracking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send new post notification', [
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('New post notification job failed', [
            'subscription_id' => $this->subscription->id,
            'email' => $this->subscription->email,
            'post_id' => $this->post->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
