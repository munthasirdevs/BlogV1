<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 *
 * Represents a user in the blog platform with roles, profile information,
 * and social links.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $avatar
 * @property string|null $bio
 * @property string $role - user, admin, editor, moderator
 * @property string $status - active, banned, suspended, deleted
 * @property string|null $website
 * @property string|null $twitter
 * @property string|null $github
 * @property string|null $linkedin
 * @property string|null $facebook
 * @property string|null $location
 * @property string $timezone
 * @property array|null $preferences
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'role',
        'status',
        'website',
        'twitter',
        'github',
        'linkedin',
        'facebook',
        'location',
        'timezone',
        'preferences',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->timezone)) {
                $user->timezone = 'UTC';
            }
        });

        // Note: Spatie role syncing is disabled as we use the role column directly
        // for authorization checks. The Spatie package is kept for backward compatibility.
    }

    /**
     * Sync the legacy role column with Spatie Permission roles.
     * This ensures backward compatibility during the transition period.
     * @deprecated Use role column directly instead
     */
    public function syncLegacyRoleWithSpatie(): void
    {
        // Disabled - using role column directly
        // if (!empty($this->role)) {
        //     $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $this->role, 'guard_name' => 'sanctum']);
        //     $this->syncRoles([$role]);
        // }
    }

    /**
     * Get the posts authored by the user.
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    /**
     * Get only published posts by the user.
     */
    public function publishedPosts()
    {
        return $this->hasMany(Post::class, 'user_id')->published();
    }

    /**
     * Get the comments made by the user.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'user_id');
    }

    /**
     * Get only approved comments by the user.
     */
    public function approvedComments()
    {
        return $this->hasMany(Comment::class, 'user_id')->approved();
    }

    /**
     * Get the likes given by the user.
     */
    public function likes()
    {
        return $this->hasMany(Like::class, 'user_id');
    }

    /**
     * Get the bookmarked posts.
     */
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'user_id');
    }

    /**
     * Get bookmarks grouped by collection.
     */
    public function bookmarksByCollection()
    {
        return $this->hasMany(Bookmark::class, 'user_id')
            ->selectRaw('collection_name, COUNT(*) as count')
            ->groupBy('collection_name');
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable');
    }

    /**
     * Get unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Get read notifications.
     */
    public function readNotifications()
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    /**
     * Get the user's notification preferences.
     */
    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class, 'user_id');
    }

    /**
     * Get a specific notification preference.
     */
    public function getNotificationPreference(string $type): ?NotificationPreference
    {
        return $this->notificationPreferences()
            ->where('notification_type', $type)
            ->first();
    }

    /**
     * Check if user wants to receive a specific notification type via a channel.
     */
    public function wantsNotification(string $type, string $channel): bool
    {
        $preference = $this->getNotificationPreference($type);

        if (!$preference) {
            $defaults = NotificationPreference::getDefaults($type);
            return $defaults['enabled'] && in_array($channel, $defaults['channels']);
        }

        return $preference->hasChannel($channel);
    }

    /**
     * Get the user's subscription.
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'user_id');
    }

    /**
     * Get media uploaded by the user.
     */
    public function uploadedMedia()
    {
        return $this->hasMany(Media::class, 'uploader_id');
    }

    /**
     * Get analytics events for the user.
     */
    public function analyticsEvents()
    {
        return $this->hasMany(AnalyticsEvent::class, 'user_id');
    }

    /**
     * Get post views by the user.
     */
    public function postViews()
    {
        return $this->hasMany(PostView::class, 'user_id');
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is editor.
     */
    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    /**
     * Check if user is moderator.
     */
    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    /**
     * Check if user has any admin role.
     */
    public function hasAdminRole(): bool
    {
        return in_array($this->role, ['admin', 'editor', 'moderator']);
    }

    /**
     * Check if user is banned.
     */
    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user can publish posts.
     */
    public function canPublish(): bool
    {
        return $this->hasAdminRole() && $this->isActive();
    }

    /**
     * Get social links as an array.
     */
    public function getSocialLinksAttribute(): array
    {
        return [
            'website' => $this->website,
            'twitter' => $this->twitter,
            'github' => $this->github,
            'linkedin' => $this->linkedin,
            'facebook' => $this->facebook,
        ];
    }

    /**
     * Get the count of posts by the user.
     */
    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }

    /**
     * Get the count of published posts by the user.
     */
    public function getPublishedPostsCountAttribute(): int
    {
        return $this->publishedPosts()->count();
    }

    /**
     * Scope for active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for banned users.
     */
    public function scopeBanned($query)
    {
        return $query->where('status', 'banned');
    }

    /**
     * Scope for admin users.
     */
    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope for editor users.
     */
    public function scopeEditor($query)
    {
        return $query->where('role', 'editor');
    }

    /**
     * Scope for users with admin roles.
     */
    public function scopeWithAdminRole($query)
    {
        return $query->whereIn('role', ['admin', 'editor', 'moderator']);
    }

    /**
     * Scope for authors (users who have published posts).
     */
    public function scopeAuthors($query)
    {
        return $query->whereHas('posts', function ($q) {
            $q->published();
        });
    }

    /**
     * Scope to search users by name or email.
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('email', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Scope to order by latest registered.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by most posts.
     */
    public function scopeMostPosts($query)
    {
        return $query->withCount('posts')->orderBy('posts_count', 'desc');
    }
}
