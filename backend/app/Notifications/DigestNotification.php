<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Class DigestNotification
 *
 * Daily/Weekly digest notification containing multiple notifications.
 */
class DigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The digest data.
     */
    public array $digestData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $digestData)
    {
        $this->digestData = $digestData;
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $user = $notifiable instanceof User ? $notifiable : null;
        $userName = $user?->name ?? 'there';
        $period = $this->digestData['period'] ?? 'Recent';
        $totalCount = $this->digestData['total_count'] ?? 0;
        $unreadCount = $this->digestData['unread_count'] ?? 0;

        $mail = (new MailMessage)
            ->subject("[$period] Your " . config('app.name') . " Notification Digest")
            ->greeting("Hi {$userName}!")
            ->line("Here's your {$period} digest with {$totalCount} notification(s) (" . $unreadCount . " unread).")
            ->line("Here's what you missed:");

        // Add each notification as a line item
        foreach ($this->digestData['notifications'] as $notification) {
            $data = $notification->data ?? [];
            $title = $data['title'] ?? 'Notification';
            $message = $data['message'] ?? '';
            $actionUrl = $data['action_url'] ?? null;

            $mail->line("• {$title}");

            if ($message) {
                $mail->line("  " . \Illuminate\Support\Str::limit($message, 100));
            }
        }

        $mail->action('View All Notifications', config('app.url') . '/notifications')
            ->line("Don't want to receive these emails?")
            ->line("You can manage your notification preferences in your account settings.");

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'digest',
            'period' => $this->digestData['period'] ?? 'Recent',
            'total_count' => $this->digestData['total_count'] ?? 0,
            'unread_count' => $this->digestData['unread_count'] ?? 0,
            'start_date' => $this->digestData['start_date']?->toISOString(),
            'end_date' => $this->digestData['end_date']?->toISOString(),
        ];
    }
}
