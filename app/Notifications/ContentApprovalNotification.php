<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContentApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Post $post,
        protected string $action
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->action) {
            'submitted' => 'Content Submitted for Review: ' . $this->post->title,
            'approved' => 'Content Approved: ' . $this->post->title,
            'rejected' => 'Content Rejected: ' . $this->post->title,
            'published' => 'Content Published: ' . $this->post->title,
            default => 'Content Update: ' . $this->post->title,
        };

        $message = match ($this->action) {
            'submitted' => "The post \"{$this->post->title}\" has been submitted for review.",
            'approved' => "The post \"{$this->post->title}\" has been approved.",
            'rejected' => "The post \"{$this->post->title}\" has been rejected.",
            'published' => "The post \"{$this->post->title}\" has been published.",
            default => "The post \"{$this->post->title}\" has been updated.",
        };

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->action('View Post', route('admin.posts.edit', $this->post));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'title' => $this->post->title,
            'action' => $this->action,
            'message' => match ($this->action) {
                'submitted' => "Post \"{$this->post->title}\" submitted for review.",
                'approved' => "Post \"{$this->post->title}\" approved.",
                'rejected' => "Post \"{$this->post->title}\" rejected.",
                'published' => "Post \"{$this->post->title}\" published.",
                default => "Post \"{$this->post->title}\" updated.",
            },
        ];
    }
}
