<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class NewPostNotification
 *
 * Mailable for new post notification emails.
 */
class NewPostNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subscription instance.
     */
    public Subscription $subscription;

    /**
     * The post instance.
     */
    public Post $post;

    /**
     * The post data for the template.
     */
    public array $postData;

    /**
     * Related posts.
     */
    public array $relatedPosts;

    /**
     * Post tags.
     */
    public array $tags;

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
        Post $post,
        array $relatedPosts = []
    ) {
        $this->subscription = $subscription;
        $this->post = $post;
        $this->relatedPosts = $relatedPosts;
        $this->postData = $this->formatPostData($post);
        $this->tags = $post->tags->pluck('name')->toArray();
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
            subject: 'New Post: ' . $this->post->title,
            tags: ['subscription', 'new_post'],
            metadata: [
                'subscription_id' => $this->subscription->id,
                'post_id' => $this->post->id,
                'email_type' => 'new_post',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.new_post',
            with: [
                'post' => $this->postData,
                'tags' => $this->tags,
                'relatedPosts' => $this->relatedPosts,
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
     * Format post data for the template.
     */
    protected function formatPostData(Post $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'url' => url('/blog/' . $post->slug),
            'excerpt' => $post->excerpt ?? strip_tags($post->content),
            'content' => strip_tags($post->content),
            'category' => $post->category?->name,
            'author' => $post->author?->name,
            'publishedAt' => $post->published_at?->format('F j, Y'),
        ];
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
     * Set related posts.
     */
    public function setRelatedPosts(array $posts): self
    {
        $this->relatedPosts = $posts;
        return $this;
    }
}
