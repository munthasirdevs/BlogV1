<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Bookmark
 * 
 * Represents a bookmark saved by a user for a post,
 * with support for collections.
 * 
 * @property int $id
 * @property int $user_id
 * @property int $post_id
 * @property string $collection_name
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Bookmark extends Model
{
    use HasFactory;

    /**
     * Default collection name.
     */
    const DEFAULT_COLLECTION = 'default';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'post_id',
        'collection_name',
        'notes',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bookmark) {
            if (empty($bookmark->collection_name)) {
                $bookmark->collection_name = self::DEFAULT_COLLECTION;
            }
        });
    }

    /**
     * Get the user who bookmarked.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the bookmarked post.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Check if bookmark is in default collection.
     */
    public function isDefaultCollection(): bool
    {
        return $this->collection_name === self::DEFAULT_COLLECTION;
    }

    /**
     * Get collection display name.
     */
    public function getCollectionDisplayNameAttribute(): string
    {
        return ucfirst($this->collection_name);
    }

    /**
     * Scope for bookmarks in a specific collection.
     */
    public function scopeInCollection($query, string $collectionName)
    {
        return $query->where('collection_name', $collectionName);
    }

    /**
     * Scope for default collection bookmarks.
     */
    public function scopeDefaultCollection($query)
    {
        return $query->where('collection_name', self::DEFAULT_COLLECTION);
    }

    /**
     * Scope for bookmarks by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for bookmarks of a specific post.
     */
    public function scopeForPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope to order by latest.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get user's bookmark collections with counts.
     */
    public static function getUserCollections(int $userId): \Illuminate\Support\Collection
    {
        return static::byUser($userId)
            ->selectRaw('collection_name, COUNT(*) as count')
            ->groupBy('collection_name')
            ->get()
            ->map(function ($item) {
                $item->display_name = ucfirst($item->collection_name);
                return $item;
            });
    }

    /**
     * Check if user has bookmarked a post.
     */
    public static function hasBookmarked(int $userId, int $postId, ?string $collection = null): bool
    {
        $query = static::where('user_id', $userId)
            ->where('post_id', $postId);

        if ($collection) {
            $query->where('collection_name', $collection);
        }

        return $query->exists();
    }

    /**
     * Toggle bookmark for a user on a post.
     * Returns true if created, false if deleted.
     */
    public static function toggle(int $userId, int $postId, ?string $collection = null, ?string $notes = null): bool
    {
        $collection = $collection ?? self::DEFAULT_COLLECTION;
        
        $existing = static::where('user_id', $userId)
            ->where('post_id', $postId)
            ->where('collection_name', $collection)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        static::create([
            'user_id' => $userId,
            'post_id' => $postId,
            'collection_name' => $collection,
            'notes' => $notes,
        ]);

        return true;
    }

    /**
     * Get user's bookmarks with post data.
     */
    public static function getUserBookmarks(int $userId, ?string $collection = null)
    {
        $query = static::byUser($userId)
            ->with(['post' => function ($q) {
                $q->with(['author', 'category']);
            }])
            ->latest();

        if ($collection) {
            $query->inCollection($collection);
        }

        return $query->get();
    }
}
