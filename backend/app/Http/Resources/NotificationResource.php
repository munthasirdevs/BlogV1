<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class NotificationResource
 *
 * API resource for transforming notifications.
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];
        
        return [
            'id' => $this->id,
            'type' => $data['type'] ?? $this->getTypeFromClass(),
            'notification_class' => $this->type,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'action_url' => $data['action_url'] ?? null,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'from_user' => $data['from_user'] ?? null,
            'data' => $this->getNotificationData($data),
            'icon' => $this->getIconForType($data['type'] ?? ''),
            'color' => $this->getColorForType($data['type'] ?? ''),
        ];
    }

    /**
     * Extract notification type from the notification class name.
     */
    protected function getTypeFromClass(): string
    {
        $className = class_basename($this->type);
        
        return match ($className) {
            'NewCommentNotification' => 'new_comment',
            'NewLikeNotification' => 'new_like',
            'PostPublishedNotification' => 'post_published',
            'MentionNotification' => 'mention',
            default => 'other',
        };
    }

    /**
     * Get notification-specific data.
     */
    protected function getNotificationData(array $data): array
    {
        return [
            'comment_id' => $data['comment_id'] ?? null,
            'post_id' => $data['post_id'] ?? null,
            'post_slug' => $data['post_slug'] ?? null,
            'post_title' => $data['post_title'] ?? null,
            'likeable_type' => $data['likeable_type'] ?? null,
            'likeable_id' => $data['likeable_id'] ?? null,
            'mention_context' => $data['mention_context'] ?? null,
            'comment_preview' => $data['comment_preview'] ?? null,
            'author_name' => $data['author_name'] ?? null,
            'author_avatar' => $data['author_avatar'] ?? null,
        ];
    }

    /**
     * Get icon for notification type.
     */
    protected function getIconForType(string $type): string
    {
        return match ($type) {
            'new_comment' => 'comment',
            'new_reply' => 'reply',
            'new_like_post', 'new_like_comment' => 'heart',
            'mention' => 'at-symbol',
            'post_published' => 'document-text',
            default => 'bell',
        };
    }

    /**
     * Get color for notification type.
     */
    protected function getColorForType(string $type): string
    {
        return match ($type) {
            'new_comment', 'new_reply' => 'blue',
            'new_like_post', 'new_like_comment' => 'red',
            'mention' => 'purple',
            'post_published' => 'green',
            default => 'gray',
        };
    }
}
