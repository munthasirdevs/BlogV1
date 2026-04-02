<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class ConfirmationEmail
 *
 * Mailable for subscription confirmation emails.
 */
class ConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subscription instance.
     */
    public Subscription $subscription;

    /**
     * The confirmation URL.
     */
    public string $confirmationUrl;

    /**
     * The unsubscribe URL.
     */
    public string $unsubscribeUrl;

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
        $this->confirmationUrl = $this->generateConfirmationUrl();
        $this->unsubscribeUrl = $this->generateUnsubscribeUrl();
        $this->trackingUrl = $this->generateTrackingUrl();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm Your Subscription to ' . config('app.name'),
            tags: ['subscription', 'confirmation'],
            metadata: [
                'subscription_id' => $this->subscription->id,
                'email_type' => 'confirmation',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.confirm',
            with: [
                'confirmationUrl' => $this->confirmationUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
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
     * Generate the confirmation URL.
     */
    protected function generateConfirmationUrl(): string
    {
        return route('api.v1.subscribe.confirm', ['token' => $this->subscription->token]);
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
        // This will be set after the email is sent and tracking is created
        return route('api.v1.track.open', [
            'subscriberId' => $this->subscription->id,
            'emailId' => '{tracking_id}', // Placeholder to be replaced
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
