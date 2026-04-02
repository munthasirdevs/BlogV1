<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Mail\UnsubscribeConfirmationEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendUnsubscribeConfirmationEmail
 *
 * Job to send unsubscribe confirmation emails.
 */
class SendUnsubscribeConfirmationEmail implements ShouldQueue
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
            // Create email tracking record
            $tracking = EmailTracking::create([
                'subscription_id' => $this->subscription->id,
                'email_type' => EmailTracking::TYPE_UNSUBSCRIBE_CONFIRM,
                'subject' => 'Unsubscribe Confirmed',
                'sent_at' => now(),
            ]);

            // Send the email
            $mailable = new UnsubscribeConfirmationEmail($this->subscription);
            $mailable->setTrackingId($tracking->id);

            Mail::to($this->subscription->email)->send($mailable);

            Log::info('Unsubscribe confirmation email sent', [
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'tracking_id' => $tracking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send unsubscribe confirmation email', [
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
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
        Log::error('Unsubscribe confirmation email job failed', [
            'subscription_id' => $this->subscription->id,
            'email' => $this->subscription->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
