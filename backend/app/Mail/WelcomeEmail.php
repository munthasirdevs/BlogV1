<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class WelcomeEmail
 *
 * Mailable for welcome emails sent after subscription confirmation.
 */
class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subscription instance.
     */
    public Subscription $subscription;

    /**
     * The preferences URL.
     */
    public string $preferencesUrl;

    /**
     * The unsubscribe URL.
     */
    public string $unsubscribeUrl;

    /**
     * The tracking URL.
     */
    public string $trackingUrl;

    /**
     * Popular posts to display.
     */
    public array $popularPosts;

    /**
     * The subscription frequency.
     */
    public string $frequency;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Subscription $subscription,
        array $popularPosts = []
    ) {
        $this->subscription = $subscription;
        $this->popularPosts = $popularPosts;
        $this->frequency = ucfirst($subscription->frequency ?? 'instant');
        $this->preferencesUrl = $this->generatePreferencesUrl();
        $this->unsubscribeUrl = $this->generateUnsubscribeUrl();
        $this->trackingUrl = $this->generateTrackingUrl();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . '! 🎉',
            tags: ['subscription', 'welcome'],
            metadata: [
                'subscription_id' => $this->subscription->id,
                'email_type' => 'welcome',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.welcome',
            with: [
                'preferencesUrl' => $this->preferencesUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'trackingUrl' => $this->trackingUrl,
                'frequency' => $this->frequency,
                'popularPosts' => $this->popularPosts,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Generate the preferences URL.
     */
    protected function generatePreferencesUrl(): string
    {
        return url('/preferences?token=' . $this->subscription->token);
    }

    /**
     * Generate the unsubscribe URL.
     */
    protected function generateUnsubscribeUrl(): string
    {
        return url('/unsubscribe?token=' . $this->subscription->token);
    }

    /**
     * Generate the tracking URL.
     */
    protected function generateTrackingUrl(): string
    {
        return route('api.v1.track.open', [
            'subscriberId' => $this->subscription->id,
            'emailId' => '{tracking_id}',
        ]);
    }

    /**
     * Set the tracking ID after email is sent.
     */
    public function setTrackingId(int $trackingId): self
    {
        $this->trackingUrl = route('api.v1.track.open', [
            'subscriberId' => $this->subscription->id,
            'emailId' => $trackingId,
        ]);
        
        return $this;
    }

    /**
     * Set popular posts.
     */
    public function setPopularPosts(array $posts): self
    {
        $this->popularPosts = $posts;
        return $this;
    }
}
