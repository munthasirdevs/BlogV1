<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class NotificationBroadcast
 *
 * Broadcast event for real-time notification delivery via Laravel Echo.
 * This event is fired when a new notification is created for a user.
 */
class NotificationBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who receives the notification.
     */
    public User $user;

    /**
     * The notification data.
     */
    public array $notification;

    /**
     * The notification type.
     */
    public string $type;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $notification, string $type)
    {
        $this->user = $user;
        $this->notification = $notification;
        $this->type = $type;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Private channel for the specific user
            new PrivateChannel('user.' . $this->user->id),
            // Also broadcast on a general notifications channel for the user
            new PrivateChannel('notifications.' . $this->user->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification['id'] ?? null,
            'type' => $this->type,
            'notification_type' => $this->notification['type'] ?? null,
            'title' => $this->notification['title'] ?? null,
            'message' => $this->notification['message'] ?? null,
            'action_url' => $this->notification['action_url'] ?? null,
            'data' => $this->notification,
            'from_user' => $this->notification['from_user'] ?? null,
            'created_at' => $this->notification['created_at'] ?? now()->toISOString(),
            'user_id' => $this->user->id,
        ];
    }

    /**
     * Determine if the event should be broadcasted.
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast if user is active and not banned
        return $this->user->isActive();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOnPresence(): array
    {
        return [
            new PresenceChannel('presence.user.' . $this->user->id),
        ];
    }

    /**
     * Handle the event broadcast failure.
     */
    public function broadcastFailed(\Throwable $e): void
    {
        \Log::error('Notification broadcast failed', [
            'user_id' => $this->user->id,
            'notification_type' => $this->type,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
