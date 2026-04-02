<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class NotificationPreference
 *
 * Stores user preferences for different notification types and channels.
 *
 * @property int $id
 * @property int $user_id
 * @property string $notification_type
 * @property array $channels
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class NotificationPreference extends Model
{
    use HasFactory;

    /**
     * Notification type constants.
     */
    const TYPE_NEW_COMMENT = 'new_comment';
    const TYPE_NEW_REPLY = 'new_reply';
    const TYPE_NEW_LIKE_POST = 'new_like_post';
    const TYPE_NEW_LIKE_COMMENT = 'new_like_comment';
    const TYPE_MENTION = 'mention';
    const TYPE_POST_PUBLISHED = 'post_published';
    const TYPE_FOLLOW = 'follow';
    const TYPE_DIGEST = 'digest';

    /**
     * Channel constants.
     */
    const CHANNEL_DATABASE = 'database';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_BROADCAST = 'broadcast';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'notification_type',
        'channels',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'enabled' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if a channel is enabled.
     */
    public function hasChannel(string $channel): bool
    {
        return $this->enabled && in_array($channel, $this->channels);
    }

    /**
     * Add a channel to the preference.
     */
    public function addChannel(string $channel): void
    {
        $channels = $this->channels;
        if (!in_array($channel, $channels)) {
            $channels[] = $channel;
            $this->update(['channels' => $channels]);
        }
    }

    /**
     * Remove a channel from the preference.
     */
    public function removeChannel(string $channel): void
    {
        $channels = array_diff($this->channels, [$channel]);
        $this->update(['channels' => array_values($channels)]);
    }

    /**
     * Enable the preference.
     */
    public function enable(): void
    {
        $this->update(['enabled' => true]);
    }

    /**
     * Disable the preference.
     */
    public function disable(): void
    {
        $this->update(['enabled' => false]);
    }

    /**
     * Scope for enabled preferences.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific notification type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope for preferences that include a specific channel.
     */
    public function scopeWithChannel($query, string $channel)
    {
        return $query->whereJsonContains('channels', $channel);
    }

    /**
     * Get default preferences for a notification type.
     */
    public static function getDefaults(string $type): array
    {
        return match ($type) {
            self::TYPE_NEW_COMMENT => [
                'channels' => [self::CHANNEL_DATABASE, self::CHANNEL_EMAIL],
                'enabled' => true,
            ],
            self::TYPE_NEW_REPLY => [
                'channels' => [self::CHANNEL_DATABASE, self::CHANNEL_EMAIL],
                'enabled' => true,
            ],
            self::TYPE_NEW_LIKE_POST => [
                'channels' => [self::CHANNEL_DATABASE],
                'enabled' => true,
            ],
            self::TYPE_NEW_LIKE_COMMENT => [
                'channels' => [self::CHANNEL_DATABASE],
                'enabled' => true,
            ],
            self::TYPE_MENTION => [
                'channels' => [self::CHANNEL_DATABASE, self::CHANNEL_EMAIL],
                'enabled' => true,
            ],
            self::TYPE_POST_PUBLISHED => [
                'channels' => [self::CHANNEL_DATABASE, self::CHANNEL_EMAIL],
                'enabled' => true,
            ],
            default => [
                'channels' => [self::CHANNEL_DATABASE],
                'enabled' => true,
            ],
        };
    }

    /**
     * Get all available notification types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_NEW_COMMENT => 'New Comment on Your Post',
            self::TYPE_NEW_REPLY => 'New Reply to Your Comment',
            self::TYPE_NEW_LIKE_POST => 'New Like on Your Post',
            self::TYPE_NEW_LIKE_COMMENT => 'New Like on Your Comment',
            self::TYPE_MENTION => 'Mention in a Comment',
            self::TYPE_POST_PUBLISHED => 'New Post Published (Newsletter)',
            self::TYPE_DIGEST => 'Daily/Weekly Digest',
        ];
    }

    /**
     * Get all available channels.
     */
    public static function getAvailableChannels(): array
    {
        return [
            self::CHANNEL_DATABASE => 'In-App Notifications',
            self::CHANNEL_EMAIL => 'Email Notifications',
            self::CHANNEL_BROADCAST => 'Real-time (WebSocket)',
        ];
    }
}
