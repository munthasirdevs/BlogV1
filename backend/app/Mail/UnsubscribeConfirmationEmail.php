<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class UnsubscribeConfirmationEmail
 *
 * Mailable for unsubscribe confirmation emails.
 */
class UnsubscribeConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subscription instance.
     */
    public Subscription $subscription;

    /**
     * The resubscribe URL.
     */
    public string $resubscribeUrl;

    /**
     * The tracking URL.
     */
    public string $trackingUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
        $this->resubscribeUrl = $this->generateResubscribeUrl();
        $this->trackingUrl = $this->generateTrackingUrl();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Unsubscribe Confirmed - ' . config('app.name'),
            tags: ['subscription', 'unsubscribe'],
            metadata: [
                'subscription_id' => $this->subscription->id,
                'email_type' => 'unsubscribe_confirm',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.unsubscribe_confirm',
            with: [
                'email' => $this->subscription->email,
                'resubscribeUrl' => $this->resubscribeUrl,
                'trackingUrl' => $this->trackingUrl,
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
     * Generate the resubscribe URL.
     */
    protected function generateResubscribeUrl(): string
    {
        return url('/subscribe?email=' . urlencode($this->subscription->email));
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
}
