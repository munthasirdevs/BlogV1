<?php

namespace App\Services;

use App\Events\NotificationBroadcast;
use App\Models\Comment;
use App\Models\NotificationPreference;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Notifications\NewCommentNotification;
use App\Notifications\NewLikeNotification;
use App\Notifications\PostPublishedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class NotificationService
 *
 * Service class for handling all notification-related business logic.
 */
class NotificationService
{
    /**
     * Cache TTL in seconds.
     */
    const CACHE_TTL = 60;

    /**
     * Send a new comment notification to the post author.
     */
    public function notifyPostAuthor(Comment $comment): void
    {
        $post = $comment->post;
        $author = $post->author;

        // Don't notify if the author is the commenter
        if ($author->id === $comment->user_id) {
            return;
        }

        // Check if author has preferences enabled for this notification type
        if (!$this->shouldSendNotification($author, NotificationPreference::TYPE_NEW_COMMENT, 'database')) {
            return;
        }

        $notification = new NewCommentNotification($comment, $comment->author);
        $author->notify($notification);

        // Broadcast the notification
        $this->broadcastNotification($author, $notification);
    }

    /**
     * Send a new reply notification to the parent comment author.
     */
    public function notifyCommentAuthor(Comment $reply): void
    {
        if (!$reply->parent_id) {
            return;
        }

        $parentComment = Comment::find($reply->parent_id);
        if (!$parentComment) {
            return;
        }

        $parentAuthor = $parentComment->author;

        // Don't notify if the parent author is the replier
        if ($parentAuthor->id === $reply->user_id) {
            return;
        }

        // Check if parent author has preferences enabled
        if (!$this->shouldSendNotification($parentAuthor, NotificationPreference::TYPE_NEW_REPLY, 'database')) {
            return;
        }

        $notification = new NewCommentNotification($reply, $reply->author);
        $parentAuthor->notify($notification);

        // Broadcast the notification
        $this->broadcastNotification($parentAuthor, $notification);
    }

    /**
     * Send a new like notification for a post.
     */
    public function notifyPostLike(Post $post, User $liker): void
    {
        $author = $post->author;

        // Don't notify if the author is the liker
        if ($author->id === $liker->id) {
            return;
        }

        // Check if author has preferences enabled
        if (!$this->shouldSendNotification($author, NotificationPreference::TYPE_NEW_LIKE_POST, 'database')) {
            return;
        }

        $notification = new NewLikeNotification($post, $liker);
        $author->notify($notification);

        // Broadcast the notification
        $this->broadcastNotification($author, $notification);
    }

    /**
     * Send a new like notification for a comment.
     */
    public function notifyCommentLike(Comment $comment, User $liker): void
    {
        $author = $comment->author;

        // Don't notify if the author is the liker
        if ($author->id === $liker->id) {
            return;
        }

        // Check if author has preferences enabled
        if (!$this->shouldSendNotification($author, NotificationPreference::TYPE_NEW_LIKE_COMMENT, 'database')) {
            return;
        }

        $notification = new NewLikeNotification($comment, $liker);
        $author->notify($notification);

        // Broadcast the notification
        $this->broadcastNotification($author, $notification);
    }

    /**
     * Send mention notifications to all mentioned users.
     */
    public function notifyMentions(Comment $comment): void
    {
        $mentionedUsers = $comment->mentioned_users ?? [];

        foreach ($mentionedUsers as $mentionedUser) {
            // Don't notify if the mentioned user is the commenter
            if ($mentionedUser->id === $comment->user_id) {
                continue;
            }

            // Check if mentioned user has preferences enabled
            if (!$this->shouldSendNotification($mentionedUser, NotificationPreference::TYPE_MENTION, 'database')) {
                continue;
            }

            $notification = new MentionNotification($comment, $comment->author);
            $mentionedUser->notify($notification);

            // Broadcast the notification
            $this->broadcastNotification($mentionedUser, $notification);
        }
    }

    /**
     * Send post published notification to all subscribers.
     * This should be queued and sent in batches.
     */
    public function notifySubscribers(Post $post): void
    {
        $author = $post->author;

        // Get all active subscribers who have email notifications enabled
        $subscribers = User::whereHas('subscription', function ($query) {
            $query->where('status', 'subscribed')
                ->where('confirmed', true);
        })
        ->where('status', 'active')
        ->get();

        foreach ($subscribers as $subscriber) {
            // Don't notify the author about their own post
            if ($subscriber->id === $author->id) {
                continue;
            }

            // Check if subscriber has preferences enabled for post published
            if (!$this->shouldSendNotification($subscriber, NotificationPreference::TYPE_POST_PUBLISHED, 'email')) {
                continue;
            }

            // Queue the notification
            PostPublishedNotification::dispatch($post, $author)->delay(now()->addSeconds(5));
        }
    }

    /**
     * Check if a notification should be sent based on user preferences.
     */
    public function shouldSendNotification(User $user, string $type, string $channel): bool
    {
        $preference = $user->notificationPreferences()
            ->where('notification_type', $type)
            ->first();

        // If no preference exists, use defaults
        if (!$preference) {
            $defaults = NotificationPreference::getDefaults($type);
            return $defaults['enabled'] && in_array($channel, $defaults['channels']);
        }

        return $preference->hasChannel($channel);
    }

    /**
     * Broadcast a notification via WebSocket.
     */
    public function broadcastNotification(User $user, $notification): void
    {
        if (!$this->shouldSendNotification($user, $notification->getType(), 'broadcast')) {
            return;
        }

        // Get the database notification that was just created
        $dbNotification = $user->notifications()
            ->where('type', get_class($notification))
            ->latest('created_at')
            ->first();

        if ($dbNotification) {
            event(new NotificationBroadcast(
                $user,
                $notification->toArray($user),
                $notification->getType()
            ));
        }
    }

    /**
     * Get user's notifications with pagination and filters.
     */
    public function getUserNotifications(
        User $user,
        int $perPage = 50,
        ?string $readStatus = null,
        ?string $type = null
    ): LengthAwarePaginator {
        $query = $user->notifications();

        // Filter by read status
        if ($readStatus === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($readStatus === 'unread') {
            $query->whereNull('read_at');
        }

        // Filter by type
        if ($type) {
            $query->whereJsonExtract('data', '$.type')
                ->orWhere('type', 'like', "%{$type}%");
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        $cacheKey = "notifications:unread_count:{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $user->unreadNotifications()->count();
        });
    }

    /**
     * Clear unread count cache.
     */
    public function clearUnreadCountCache(User $user): void
    {
        Cache::forget("notifications:unread_count:{$user->id}");
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        $this->clearUnreadCountCache($user);

        return true;
    }

    /**
     * Mark a notification as unread.
     */
    public function markAsUnread(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsUnread();
        $this->clearUnreadCountCache($user);

        return true;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user, ?string $type = null): int
    {
        $query = $user->unreadNotifications();

        if ($type) {
            $query->whereJsonExtract('data', '$.type')
                ->orWhere('type', 'like', "%{$type}%");
        }

        $count = $query->update(['read_at' => now()]);
        $this->clearUnreadCountCache($user);

        return $count;
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->delete();
        $this->clearUnreadCountCache($user);

        return true;
    }

    /**
     * Get notification statistics for a user.
     */
    public function getStats(User $user): array
    {
        $total = $user->notifications()->count();
        $unread = $user->unreadNotifications()->count();
        $read = $total - $unread;

        // Get counts by type
        $byType = $user->notifications()
            ->selectRaw("JSON_EXTRACT(data, '$.type') as type, COUNT(*) as count")
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        // Get counts for today
        $today = $user->notifications()
            ->whereDate('created_at', today())
            ->count();

        // Get unread for today
        $unreadToday = $user->unreadNotifications()
            ->whereDate('created_at', today())
            ->count();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $read,
            'today' => $today,
            'unread_today' => $unreadToday,
            'by_type' => $byType,
        ];
    }

    /**
     * Get user's notification preferences.
     */
    public function getPreferences(User $user): array
    {
        $preferences = [];
        $availableTypes = NotificationPreference::getAvailableTypes();
        $availableChannels = NotificationPreference::getAvailableChannels();

        foreach ($availableTypes as $type => $label) {
            $preference = $user->notificationPreferences()
                ->where('notification_type', $type)
                ->first();

            if ($preference) {
                $preferences[$type] = [
                    'label' => $label,
                    'enabled' => $preference->enabled,
                    'channels' => $preference->channels,
                ];
            } else {
                $defaults = NotificationPreference::getDefaults($type);
                $preferences[$type] = [
                    'label' => $label,
                    'enabled' => $defaults['enabled'],
                    'channels' => $defaults['channels'],
                ];
            }
        }

        return [
            'preferences' => $preferences,
            'available_channels' => $availableChannels,
        ];
    }

    /**
     * Update user's notification preferences.
     */
    public function updatePreferences(User $user, array $preferences): void
    {
        DB::transaction(function () use ($user, $preferences) {
            foreach ($preferences as $type => $data) {
                // Validate type
                if (!array_key_exists($type, NotificationPreference::getAvailableTypes())) {
                    continue;
                }

                $preference = $user->notificationPreferences()
                    ->firstOrCreate(
                        ['notification_type' => $type],
                        ['channels' => [], 'enabled' => true]
                    );

                if (isset($data['enabled'])) {
                    $preference->enabled = $data['enabled'];
                }

                if (isset($data['channels']) && is_array($data['channels'])) {
                    // Filter to only valid channels
                    $validChannels = array_intersect(
                        $data['channels'],
                        array_keys(NotificationPreference::getAvailableChannels())
                    );
                    $preference->channels = array_values($validChannels);
                }

                $preference->save();
            }
        });

        // Clear any cached preferences
        Cache::forget("notifications:preferences:{$user->id}");
    }

    /**
     * Send a test notification to a user.
     */
    public function sendTestNotification(User $user): void
    {
        $testComment = new Comment([
            'content' => 'This is a test notification!',
            'post_id' => 1,
        ]);

        $testPost = new Post([
            'title' => 'Test Post',
            'slug' => 'test-post',
        ]);

        $testComment->setRelation('post', $testPost);
        $testComment->setRelation('author', $user);

        $notification = new NewCommentNotification($testComment, $user);
        $user->notify($notification);
    }

    /**
     * Clean up old read notifications.
     * Delete read notifications older than specified days.
     */
    public function cleanupOldNotifications(int $olderThanDays = 30): int
    {
        $cutoffDate = now()->subDays($olderThanDays);

        $deleted = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        \Log::info('Notification cleanup completed', [
            'deleted_count' => $deleted,
            'older_than_days' => $olderThanDays,
            'cutoff_date' => $cutoffDate->toISOString(),
        ]);

        return $deleted;
    }

    /**
     * Get notifications for export (GDPR compliance).
     */
    public function getExportData(User $user): array
    {
        $notifications = $user->notifications()
            ->orderBy('created_at')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at->toISOString(),
                    'updated_at' => $notification->updated_at->toISOString(),
                ];
            })
            ->toArray();

        return [
            'notifications' => $notifications,
            'total_count' => count($notifications),
            'exported_at' => now()->toISOString(),
        ];
    }

    /**
     * Delete all notifications for a user (GDPR compliance).
     */
    public function deleteAllNotifications(User $user): int
    {
        $count = $user->notifications()->delete();
        $this->clearUnreadCountCache($user);

        \Log::info('All notifications deleted for user', [
            'user_id' => $user->id,
            'deleted_count' => $count,
        ]);

        return $count;
    }
}
