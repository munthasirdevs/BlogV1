<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Models\Post;
use App\Mail\WelcomeEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendWelcomeEmail
 *
 * Job to send welcome emails after subscription confirmation.
 */
class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The subscription instance.
     */
    public Subscription $subscription;

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
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get popular posts to include
            $popularPosts = $this->getPopularPosts();

            // Create email tracking record
            $tracking = EmailTracking::create([
                'subscription_id' => $this->subscription->id,
                'email_type' => EmailTracking::TYPE_WELCOME,
                'subject' => 'Welcome to ' . config('app.name'),
                'sent_at' => now(),
            ]);

            // Send the email
            $mailable = new WelcomeEmail($this->subscription, $popularPosts);
            $mailable->setTrackingId($tracking->id);

            Mail::to($this->subscription->email)->send($mailable);

            Log::info('Welcome email sent', [
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'tracking_id' => $tracking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get popular posts to include in the welcome email.
     */
    protected function getPopularPosts(): array
    {
        try {
            return Post::published()
                ->with(['category', 'author'])
                ->orderByDesc('views_count')
                ->limit(3)
                ->get()
                ->map(function ($post) {
                    return [
                        'title' => $post->title,
                        'url' => url('/blog/' . $post->slug),
                        'excerpt' => $post->excerpt ?? strip_tags($post->content),
                        'category' => $post->category?->name,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('Failed to get popular posts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Welcome email job failed', [
            'subscription_id' => $this->subscription->id,
            'email' => $this->subscription->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
