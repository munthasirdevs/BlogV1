<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Class NewCommentNotification
 *
 * Notification sent when someone comments on a user's post or replies to their comment.
 */
class NewCommentNotification extends BaseNotification
{
    /**
     * The comment that triggered the notification.
     */
    public Comment $comment;

    /**
     * The post the comment belongs to.
     */
    public Post $post;

    /**
     * Whether this is a reply to another comment.
     */
    public bool $isReply;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment, User $fromUser)
    {
        parent::__construct($fromUser);

        $this->comment = $comment;
        $this->post = $comment->post;
        $this->isReply = $comment->parent_id !== null;
        $this->type = $this->isReply 
            ? \App\Models\NotificationPreference::TYPE_NEW_REPLY 
            : \App\Models\NotificationPreference::TYPE_NEW_COMMENT;
        $this->actionUrl = $this->generateActionUrl();
        $this->title = $this->generateTitle();
        $this->message = $this->generateMessage();
        $this->data = $this->generateData();
    }

    /**
     * Generate the action URL.
     */
    protected function generateActionUrl(): string
    {
        // Link directly to the comment with anchor
        $fragment = "#comment-{$this->comment->id}";
        
        return config('app.url') . "/posts/{$this->post->slug}{$fragment}";
    }

    /**
     * Generate the notification title.
     */
    protected function generateTitle(): string
    {
        if ($this->isReply) {
            return 'New Reply to Your Comment';
        }

        return 'New Comment on Your Post';
    }

    /**
     * Generate the notification message.
     */
    protected function generateMessage(): string
    {
        $commenterName = $this->fromUser->name;
        $commentPreview = Str::limit($this->comment->content, 100);

        if ($this->isReply) {
            return "{$commenterName} replied to your comment: \"{$commentPreview}\"";
        }

        return "{$commenterName} commented on your post \"{$this->post->title}\": \"{$commentPreview}\"";
    }

    /**
     * Generate additional data for the notification.
     */
    protected function generateData(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'post_id' => $this->post->id,
            'post_slug' => $this->post->slug,
            'post_title' => $this->post->title,
            'comment_preview' => Str::limit($this->comment->content, 200),
            'is_reply' => $this->isReply,
            'parent_comment_id' => $this->comment->parent_id,
        ];
    }

    /**
     * Get the email subject.
     */
    protected function getEmailSubject(): string
    {
        if ($this->isReply) {
            return "[{$this->fromUser->name}] replied to your comment";
        }

        return "[{$this->fromUser->name}] commented on your post";
    }

    /**
     * Get the email action text.
     */
    protected function getEmailActionText(): string
    {
        return $this->isReply ? 'View Reply' : 'View Comment';
    }

    /**
     * Get the email closing line.
     */
    protected function getEmailClosingLine(): string
    {
        if ($this->isReply) {
            return "You're receiving this because someone replied to your comment on {$this->post->title}.";
        }

        return "You're receiving this because someone commented on your post {$this->post->title}.";
    }
}
