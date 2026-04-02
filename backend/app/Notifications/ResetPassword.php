<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPassword extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     */
    public string $token;

    /**
     * The callback that should be used to create the reset password URL.
     */
    public static $createUrlCallback;

    /**
     * The callback that should be used to build the mail message.
     */
    public static $toMailCallback;

    /**
     * Create a new notification instance.
     *
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Password Reset Request')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation('Regards, ' . config('app.name'));
    }

    /**
     * Get the reset password URL for the notifiable.
     */
    protected function resetUrl(User $notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        $frontendUrl = config('app.frontend_url', config('app.url'));
        
        return $frontendUrl . '/reset-password?token=' . urlencode($this->token) . 
               '&email=' . urlencode($notifiable->getEmailForPasswordReset());
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'password_reset',
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'token' => $this->token,
        ];
    }

    /**
     * Set a callback that should be used to create the reset password button URL.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function createUrlUsing($callback)
    {
        static::$createUrlCallback = $callback;
    }

    /**
     * Set a callback that should be used to build the mail message.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }

    /**
     * Determine if the notification should be queued.
     *
     * @param User $notifiable
     * @return bool
     */
    public function shouldQueue(User $notifiable): bool
    {
        // Don't queue in testing environment
        return app()->environment('production', 'local');
    }
}
