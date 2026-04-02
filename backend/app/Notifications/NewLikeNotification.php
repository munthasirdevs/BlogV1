<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Class NewLikeNotification
 *
 * Notification sent when someone likes a user's post or comment.
 * Only sent via database channel (not email) to avoid notification fatigue.
 */
class NewLikeNotification extends BaseNotification
{
    /**
     * The likeable model (Post or Comment).
     */
    public Model $likeable;

    /**
     * The type of likeable (post or comment).
     */
    public string $likeableType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Model $likeable, User $fromUser)
    {
        parent::__construct($fromUser);

        $this->likeable = $likeable;
        $this->likeableType = $likeable instanceof Post ? 'post' : 'comment';
        $this->type = $likeable instanceof Post
            ? \App\Models\NotificationPreference::TYPE_NEW_LIKE_POST
            : \App\Models\NotificationPreference::TYPE_NEW_LIKE_COMMENT;
        $this->actionUrl = $this->generateActionUrl();
        $this->title = $this->generateTitle();
        $this->message = $this->generateMessage();
        $this->data = $this->generateData();

        // Override via method to only use database and broadcast channels
        // (no email for like notifications)
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        // Add broadcast channel for real-time updates
        if ($this->shouldUseBroadcast($notifiable)) {
            $channels[] = 'broadcast';
        }

        // Never send email for like notifications
        return $channels;
    }

    /**
     * Generate the action URL.
     */
    protected function generateActionUrl(): string
    {
        if ($this->likeable instanceof Post) {
            return config('app.url') . "/posts/{$this->likeable->slug}";
        }

        // For comment likes, link to the post with comment anchor
        $comment = $this->likeable;
        return config('app.url') . "/posts/{$comment->post->slug}#comment-{$comment->id}";
    }

    /**
     * Generate the notification title.
     */
    protected function generateTitle(): string
    {
        if ($this->likeable instanceof Post) {
            return 'New Like on Your Post';
        }

        return 'New Like on Your Comment';
    }

    /**
     * Generate the notification message.
     */
    protected function generateMessage(): string
    {
        $likerName = $this->fromUser->name;

        if ($this->likeable instanceof Post) {
            return "{$likerName} liked your post \"{$this->likeable->title}\"";
        }

        $commentPreview = \Illuminate\Support\Str::limit($this->likeable->content, 50);
        return "{$likerName} liked your comment: \"{$commentPreview}\"";
    }

    /**
     * Generate additional data for the notification.
     */
    protected function generateData(): array
    {
        if ($this->likeable instanceof Post) {
            return [
                'likeable_type' => 'post',
                'likeable_id' => $this->likeable->id,
                'post_id' => $this->likeable->id,
                'post_slug' => $this->likeable->slug,
                'post_title' => $this->likeable->title,
            ];
        }

        $comment = $this->likeable;
        return [
            'likeable_type' => 'comment',
            'likeable_id' => $comment->id,
            'post_id' => $comment->post_id,
            'post_slug' => $comment->post->slug,
            'comment_preview' => \Illuminate\Support\Str::limit($comment->content, 100),
        ];
    }

    /**
     * Get the email subject.
     * (Not used since we don't send emails for likes, but required by parent class)
     */
    protected function getEmailSubject(): string
    {
        if ($this->likeable instanceof Post) {
            return "[{$this->fromUser->name}] liked your post";
        }

        return "[{$this->fromUser->name}] liked your comment";
    }

    /**
     * Get the email action text.
     */
    protected function getEmailActionText(): string
    {
        return 'View';
    }
}
