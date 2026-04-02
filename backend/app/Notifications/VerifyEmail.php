<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification
{
    use Queueable;

    /**
     * The number of seconds the signed URL should be valid.
     */
    protected int $expirationSeconds = 86400; // 24 hours

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected ?int $expires = null,
        protected ?string $request = null
    ) {}

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
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Thank you for registering on our platform. Please click the button below to verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('This verification link will expire in 24 hours.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Regards, ' . config('app.name'));
    }

    /**
     * Get the verification URL for the notifiable.
     */
    protected function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addSeconds($this->expires ?? $this->expirationSeconds),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'email_verification',
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
        ];
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
