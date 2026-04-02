<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class DigestEmail
 *
 * Mailable for digest emails (daily, weekly, monthly).
 */
class DigestEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subscription instance.
     */
    public Subscription $subscription;

    /**
     * The posts to include in the digest.
     */
    public array $posts;

    /**
     * The digest period.
     */
    public string $period;

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
     * The email subject.
     */
    public string $subject;

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
     * Create a new message instance.
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
        $this->period = $this->getPeriodLabel();
        $this->subject = $this->generateSubject();
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
            subject: $this->subject,
            tags: ['subscription', 'digest', $this->frequency],
            metadata: [
                'subscription_id' => $this->subscription->id,
                'email_type' => 'digest',
                'frequency' => $this->frequency,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.digest',
            with: [
                'subject' => $this->subject,
                'posts' => $this->posts,
                'period' => $this->period,
                'frequency' => $this->frequency,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'email' => $this->subscription->email,
                'preferencesUrl' => $this->preferencesUrl,
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
     * Get the period label.
     */
    protected function getPeriodLabel(): string
    {
        return match($this->frequency) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => 'Regular',
        };
    }

    /**
     * Generate the email subject.
     */
    protected function generateSubject(): string
    {
        $periodLabel = $this->getPeriodLabel();
        return "{$periodLabel} Digest from " . config('app.name') . " - {$this->startDate}";
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
}
