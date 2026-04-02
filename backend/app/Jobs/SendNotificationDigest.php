<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Class SendNotificationDigest
 *
 * Send daily/weekly digest of notifications to users who have enabled it.
 */
class SendNotificationDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The digest type (daily or weekly).
     */
    public string $type;

    /**
     * The number of days to include in the digest.
     */
    public int $days;

    /**
     * Maximum notifications to include in digest.
     */
    public int $maxNotifications = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(string $type = 'daily', int $days = 1)
    {
        $this->type = $type;
        $this->days = $days;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startDate = now()->subDays($this->days);

        // Get users who have digest enabled
        $users = User::where('status', 'active')
            ->whereNotNull('email_verified_at')
            ->whereHas('notificationPreferences', function ($query) {
                $query->where('notification_type', NotificationPreference::TYPE_DIGEST)
                    ->where('enabled', true)
                    ->whereJsonContains('channels', 'email');
            })
            ->chunk(100, function ($users) use ($startDate) {
                foreach ($users as $user) {
                    $this->sendDigestToUser($user, $startDate);
                }
            });

        \Log::info('Notification digest job completed', [
            'type' => $this->type,
            'days' => $this->days,
        ]);
    }

    /**
     * Send digest email to a specific user.
     */
    protected function sendDigestToUser(User $user, \DateTimeInterface $startDate): void
    {
        // Get user's notifications for the period
        $notifications = $user->notifications()
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->limit($this->maxNotifications)
            ->get();

        if ($notifications->isEmpty()) {
            return;
        }

        // Group notifications by type
        $groupedNotifications = $notifications->groupBy(function ($notification) {
            return $notification->data['type'] ?? 'other';
        });

        // Prepare digest data
        $digestData = [
            'user' => $user,
            'type' => $this->type,
            'period' => $this->getPeriodLabel(),
            'notifications' => $notifications,
            'grouped_notifications' => $groupedNotifications,
            'total_count' => $notifications->count(),
            'unread_count' => $notifications->whereNull('read_at')->count(),
            'start_date' => $startDate,
            'end_date' => now(),
        ];

        // Send the digest email
        try {
            $user->notify(new \App\Notifications\DigestNotification($digestData));

            \Log::info('Digest email sent', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'notification_count' => $notifications->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send digest email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the period label for the digest.
     */
    protected function getPeriodLabel(): string
    {
        return match ($this->type) {
            'daily' => 'Today',
            'weekly' => 'This Week',
            default => 'Recent',
        };
    }

    /**
     * Get the subject line for the digest email.
     */
    public function getSubject(): string
    {
        $period = $this->getPeriodLabel();
        return "[{$period}] Your " . config('app.name') . " Notification Digest";
    }
}
