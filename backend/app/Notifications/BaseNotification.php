<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Class BaseNotification
 *
 * Base notification class providing common functionality for all notifications.
 * All notification classes should extend this class.
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the queued notification may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the notification.
     *
     * @var array<int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * The user who triggered the notification.
     */
    protected ?User $fromUser = null;

    /**
     * The notification type identifier.
     */
    protected string $type;

    /**
     * The action URL for the notification.
     */
    protected string $actionUrl;

    /**
     * The notification title.
     */
    protected string $title;

    /**
     * The notification message preview.
     */
    protected string $message;

    /**
     * Additional data for the notification.
     */
    protected array $data = [];

    /**
     * Create a new notification instance.
     */
    public function __construct(?User $fromUser = null)
    {
        $this->fromUser = $fromUser;
        $this->onQueue('notifications');
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

        // Add mail channel if email is enabled for this notification type
        if ($this->shouldUseMail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Determine if the broadcast channel should be used.
     */
    protected function shouldUseBroadcast($notifiable): bool
    {
        if (!$notifiable instanceof User) {
            return false;
        }

        // Check user preferences
        $preference = $notifiable->notificationPreferences()
            ->where('notification_type', $this->type)
            ->first();

        if ($preference) {
            return $preference->hasChannel('broadcast');
        }

        // Default: use broadcast if user has email verified
        return $notifiable->email_verified_at !== null;
    }

    /**
     * Determine if the mail channel should be used.
     */
    protected function shouldUseMail($notifiable): bool
    {
        if (!$notifiable instanceof User) {
            return false;
        }

        // Check user preferences
        $preference = $notifiable->notificationPreferences()
            ->where('notification_type', $this->type)
            ->first();

        if ($preference) {
            return $preference->hasChannel('email');
        }

        // Default: don't use mail unless explicitly enabled
        return false;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return array_merge([
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'from_user' => $this->fromUser ? [
                'id' => $this->fromUser->id,
                'name' => $this->fromUser->name,
                'avatar' => $this->fromUser->avatar,
            ] : null,
            'created_at' => now()->toISOString(),
        ], $this->data);
    }

    /**
     * Get the broadcast representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toBroadcast($notifiable): array
    {
        return array_merge($this->toArray($notifiable), [
            'id' => $this->id ?? null,
            'broadcast_type' => 'notification',
            'notification_type' => $this->type,
        ]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting($this->getEmailGreeting())
            ->line($this->message)
            ->action($this->getEmailActionText(), $this->actionUrl)
            ->line($this->getEmailClosingLine())
            ->when($this->fromUser, function ($mail) {
                return $mail->line("From: {$this->fromUser->name}");
            })
            ->markdown('emails.notifications.base');
    }

    /**
     * Get the email subject.
     */
    abstract protected function getEmailSubject(): string;

    /**
     * Get the email greeting.
     */
    protected function getEmailGreeting(): string
    {
        $notifiableName = $this->notifiable instanceof User ? $this->notifiable->name : 'there';
        return "Hello {$notifiableName}!";
    }

    /**
     * Get the email action text.
     */
    protected function getEmailActionText(): string
    {
        return 'View Notification';
    }

    /**
     * Get the email closing line.
     */
    protected function getEmailClosingLine(): string
    {
        return 'Thank you for using ' . config('app.name') . '!';
    }

    /**
     * Set additional data for the notification.
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the notification type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the action URL.
     */
    public function getActionUrl(): string
    {
        return $this->actionUrl;
    }

    /**
     * Get the from user.
     */
    public function getFromUser(): ?User
    {
        return $this->fromUser;
    }

    /**
     * Handle the notification failure.
     */
    public function failed(\Throwable $e): void
    {
        \Log::error('Notification failed', [
            'notification' => static::class,
            'type' => $this->type,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
