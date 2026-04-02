<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Like
 * 
 * Represents a like on any likeable model (posts, comments, etc.)
 * using polymorphic relationships.
 * 
 * @property int $id
 * @property int $user_id
 * @property int $likeable_id
 * @property string $likeable_type
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Like extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'likeable_id',
        'likeable_type',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Note: Count updates are handled by the LikeService to avoid double-counting
        // The service uses atomic operations for race condition safety
    }

    /**
     * Get the parent likeable model.
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who liked.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for likes on posts.
     */
    public function scopeForPosts($query)
    {
        return $query->where('likeable_type', Post::class);
    }

    /**
     * Scope for likes on comments.
     */
    public function scopeForComments($query)
    {
        return $query->where('likeable_type', Comment::class);
    }

    /**
     * Scope for likes by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for likes on a specific model.
     */
    public function scopeForModel($query, $model)
    {
        return $query->where('likeable_type', get_class($model))
            ->where('likeable_id', $model->id);
    }

    /**
     * Check if user has liked a model.
     */
    public static function hasLiked(int $userId, $model): bool
    {
        return static::where('user_id', $userId)
            ->where('likeable_type', get_class($model))
            ->where('likeable_id', $model->id)
            ->exists();
    }

    /**
     * Get like count for a model.
     */
    public static function countForModel($model): int
    {
        return static::where('likeable_type', get_class($model))
            ->where('likeable_id', $model->id)
            ->count();
    }

    /**
     * Toggle like for a user on a model.
     * Returns true if created, false if deleted.
     */
    public static function toggle(int $userId, $model): bool
    {
        $existing = static::where('user_id', $userId)
            ->where('likeable_type', get_class($model))
            ->where('likeable_id', $model->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        static::create([
            'user_id' => $userId,
            'likeable_id' => $model->id,
            'likeable_type' => get_class($model),
        ]);

        return true;
    }
}
