<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Class MentionNotification
 *
 * Notification sent when a user is mentioned (@username) in a comment.
 */
class MentionNotification extends BaseNotification
{
    /**
     * The comment containing the mention.
     */
    public Comment $comment;

    /**
     * The post the comment belongs to.
     */
    public Post $post;

    /**
     * The context around the mention.
     */
    public string $mentionContext;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment, User $fromUser, string $mentionContext = '')
    {
        parent::__construct($fromUser);

        $this->comment = $comment;
        $this->post = $comment->post;
        $this->mentionContext = $mentionContext ?: $this->extractMentionContext();
        $this->type = \App\Models\NotificationPreference::TYPE_MENTION;
        $this->actionUrl = $this->generateActionUrl();
        $this->title = 'You Were Mentioned';
        $this->message = $this->generateMessage();
        $this->data = $this->generateData();
    }

    /**
     * Extract context around the mention from the comment content.
     */
    protected function extractMentionContext(): string
    {
        $content = $this->comment->content;
        $mentionPattern = '/@' . preg_quote($this->notifiable->username ?? $this->notifiable->name, '/') . '/i';
        
        // Find the position of the mention
        if (preg_match($mentionPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $position = $matches[0][1];
            // Get 50 characters before and after the mention
            $start = max(0, $position - 50);
            $length = min(strlen($content) - $start, 150);
            $context = substr($content, $start, $length);
            
            // Add ellipsis if truncated
            if ($start > 0) {
                $context = '...' . $context;
            }
            if ($start + $length < strlen($content)) {
                $context = $context . '...';
            }
            
            return $context;
        }
        
        return Str::limit($content, 100);
    }

    /**
     * Generate the action URL.
     */
    protected function generateActionUrl(): string
    {
        return config('app.url') . "/posts/{$this->post->slug}#comment-{$this->comment->id}";
    }

    /**
     * Generate the notification message.
     */
    protected function generateMessage(): string
    {
        $mentionerName = $this->fromUser->name;
        return "{$mentionerName} mentioned you in a comment on \"{$this->post->title}\": \"{$this->mentionContext}\"";
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
            'mention_context' => $this->mentionContext,
            'comment_content' => $this->comment->content,
            'comment_preview' => Str::limit($this->comment->content, 200),
        ];
    }

    /**
     * Get the email subject.
     */
    protected function getEmailSubject(): string
    {
        return "[{$this->fromUser->name}] mentioned you in a comment";
    }

    /**
     * Get the email greeting.
     */
    protected function getEmailGreeting(): string
    {
        $notifiableName = $this->notifiable instanceof User ? $this->notifiable->name : 'there';
        return "Hey {$notifiableName}!";
    }

    /**
     * Get the email action text.
     */
    protected function getEmailActionText(): string
    {
        return 'View Mention';
    }

    /**
     * Get the email closing line.
     */
    protected function getEmailClosingLine(): string
    {
        return "You're receiving this because {$this->fromUser->name} mentioned you in a comment.";
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
            ->line("**{$this->fromUser->name}** mentioned you in a comment on **{$this->post->title}**.")
            ->line("Here's what they said:")
            ->line("\"{$this->mentionContext}\"")
            ->action($this->getEmailActionText(), $this->actionUrl)
            ->line($this->getEmailClosingLine())
            ->markdown('emails.notifications.mention');
    }
}
