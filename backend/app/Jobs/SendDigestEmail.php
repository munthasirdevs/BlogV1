<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Mail\DigestEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendDigestEmail
 *
 * Job to send digest emails (daily, weekly, monthly).
 */
class SendDigestEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The subscription instance.
     */
    public Subscription $subscription;

    /**
     * The posts to include in the digest.
     */
    public array $posts;

    /**
     * The digest frequency.
     */
    public string $frequency;

    /**
     * The start date of the digest period.
     */
    public string $startDate;

    /**
     * The end date of the digest period.
     */
    public string $endDate;

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
        array $posts,
        string $frequency,
        string $startDate,
        string $endDate
    ) {
        $this->subscription = $subscription;
        $this->posts = $posts;
        $this->frequency = $frequency;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
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
                'email_type' => EmailTracking::TYPE_DIGEST,
                'subject' => ucfirst($this->frequency) . ' Digest',
                'sent_at' => now(),
                'metadata' => [
                    'frequency' => $this->frequency,
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                    'post_count' => count($this->posts),
                ],
            ]);

            // Send the email
            $mailable = new DigestEmail(
                $this->subscription,
                $this->posts,
                $this->frequency,
                $this->startDate,
                $this->endDate
            );
            $mailable->setTrackingId($tracking->id);

            Mail::to($this->subscription->email)->send($mailable);

            Log::info('Digest email sent', [
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'frequency' => $this->frequency,
                'tracking_id' => $tracking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send digest email', [
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'frequency' => $this->frequency,
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
        Log::error('Digest email job failed', [
            'subscription_id' => $this->subscription->id,
            'email' => $this->subscription->email,
            'frequency' => $this->frequency,
            'error' => $exception->getMessage(),
        ]);
    }
}
