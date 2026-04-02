<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Class PostPublishedNotification
 *
 * Notification sent to subscribers when a new post is published.
 * This is queued and sent in batches to avoid overwhelming the mail server.
 */
class PostPublishedNotification extends BaseNotification
{
    /**
     * The published post.
     */
    public Post $post;

    /**
     * The author of the post.
     */
    public User $author;

    /**
     * Create a new notification instance.
     */
    public function __construct(Post $post, User $author)
    {
        // Pass null as fromUser since this is a system notification
        parent::__construct(null);

        $this->post = $post;
        $this->author = $author;
        $this->type = \App\Models\NotificationPreference::TYPE_POST_PUBLISHED;
        $this->actionUrl = $this->generateActionUrl();
        $this->title = 'New Post Published';
        $this->message = $this->generateMessage();
        $this->data = $this->generateData();
    }

    /**
     * Generate the action URL.
     */
    protected function generateActionUrl(): string
    {
        return config('app.url') . "/posts/{$this->post->slug}";
    }

    /**
     * Generate the notification message.
     */
    protected function generateMessage(): string
    {
        $excerpt = $this->post->excerpt ?? Str::limit(strip_tags($this->post->content), 200);
        return "New post published by {$this->author->name}: \"{$this->post->title}\". {$excerpt}";
    }

    /**
     * Generate additional data for the notification.
     */
    protected function generateData(): array
    {
        return [
            'post_id' => $this->post->id,
            'post_slug' => $this->post->slug,
            'post_title' => $this->post->title,
            'post_excerpt' => $this->post->excerpt ?? Str::limit(strip_tags($this->post->content), 200),
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'author_avatar' => $this->author->avatar,
            'category' => $this->post->category?->name,
            'tags' => $this->post->tags->pluck('name')->toArray(),
            'featured_image' => $this->post->featured_image,
            'reading_time' => $this->post->reading_time,
        ];
    }

    /**
     * Get the email subject.
     */
    protected function getEmailSubject(): string
    {
        return "New Post: {$this->post->title}";
    }

    /**
     * Get the email greeting.
     */
    protected function getEmailGreeting(): string
    {
        $notifiableName = $this->notifiable instanceof User ? $this->notifiable->name : 'there';
        return "Hi {$notifiableName}!";
    }

    /**
     * Get the email action text.
     */
    protected function getEmailActionText(): string
    {
        return 'Read Post';
    }

    /**
     * Get the email closing line.
     */
    protected function getEmailClosingLine(): string
    {
        return "You're receiving this because you're subscribed to " . config('app.name') . " newsletter.";
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting($this->getEmailGreeting())
            ->line("New post published by **{$this->author->name}**!")
            ->line("**{$this->post->title}**")
            ->line($this->post->excerpt ?? Str::limit(strip_tags($this->post->content), 200))
            ->action($this->getEmailActionText(), $this->actionUrl)
            ->when($this->post->category, function ($mail) {
                return $mail->line("Category: {$this->post->category->name}");
            })
            ->when($this->post->reading_time, function ($mail) {
                return $mail->line("Reading time: {$this->post->reading_time} min");
            })
            ->line($this->getEmailClosingLine())
            ->markdown('emails.notifications.post_published');
    }
}
