<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\Subscription;
use App\Models\EmailTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendCampaignEmail
 *
 * Job to send individual campaign emails.
 */
class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The email campaign.
     */
    public EmailCampaign $campaign;

    /**
     * The subscriber.
     */
    public Subscription $subscriber;

    /**
     * The A/B test variant ('a' or 'b').
     */
    public ?string $variant;

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
        EmailCampaign $campaign,
        Subscription $subscriber,
        ?string $variant = null
    ) {
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;
        $this->variant = $variant;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Determine subject line based on variant
            $subject = $this->variant === 'b' && $this->campaign->subject_b
                ? $this->campaign->subject_b
                : $this->campaign->subject;

            // Create email tracking record
            $tracking = EmailTracking::create([
                'subscription_id' => $this->subscriber->id,
                'email_campaign_id' => $this->campaign->id,
                'email_type' => EmailTracking::TYPE_NEWSLETTER,
                'subject' => $subject,
                'sent_at' => now(),
                'metadata' => [
                    'variant' => $this->variant,
                    'campaign_name' => $this->campaign->name,
                ],
            ]);

            // Increment campaign sent count
            $this->campaign->incrementSent();

            // Send the email using the campaign template
            $this->sendCampaignEmail($tracking, $subject);

            Log::info('Campaign email sent', [
                'campaign_id' => $this->campaign->id,
                'subscription_id' => $this->subscriber->id,
                'email' => $this->subscriber->email,
                'variant' => $this->variant,
                'tracking_id' => $tracking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send campaign email', [
                'campaign_id' => $this->campaign->id,
                'subscription_id' => $this->subscriber->id,
                'email' => $this->subscriber->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send the campaign email.
     */
    protected function sendCampaignEmail(EmailTracking $tracking, string $subject): void
    {
        // Build email content from campaign template
        $content = $this->campaign->content ?? [];
        $html = $content['html'] ?? $this->buildDefaultHtml($content);
        
        // Send using raw HTML
        Mail::send(
            [],
            [],
            fn($message) => $message
                ->to($this->subscriber->email)
                ->subject($subject)
                ->html($html)
        );
    }

    /**
     * Build default HTML from content.
     */
    protected function buildDefaultHtml(array $content): string
    {
        $title = $content['title'] ?? $this->campaign->name;
        $body = $content['body'] ?? '';
        
        return view('emails.campaign.default', [
            'title' => $title,
            'body' => $body,
            'trackingUrl' => route('api.v1.track.open', [
                'subscriberId' => $this->subscriber->id,
                'emailId' => $tracking->id,
            ]),
        ])->render();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Campaign email job failed', [
            'campaign_id' => $this->campaign->id,
            'subscription_id' => $this->subscriber->id,
            'email' => $this->subscriber->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
