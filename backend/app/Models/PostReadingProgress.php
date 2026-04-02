<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PostReadingProgress
 *
 * Tracks a user's reading progress for a post.
 *
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property int $percentage - 0-100
 * @property int $time_spent - Time in seconds
 * @property \Illuminate\Support\Carbon|null $last_read_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostReadingProgress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_reading_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'percentage',
        'time_spent',
        'last_read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
            'time_spent' => 'integer',
            'last_read_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($progress) {
            if (empty($progress->last_read_at)) {
                $progress->last_read_at = now();
            }
        });

        static::updating(function ($progress) {
            $progress->last_read_at = now();
        });
    }

    /**
     * Get the post this progress belongs to.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get the user this progress belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if reading is complete.
     */
    public function isComplete(): bool
    {
        return $this->percentage >= 100;
    }

    /**
     * Get progress as a decimal (0-1).
     */
    public function getProgressDecimalAttribute(): float
    {
        return $this->percentage / 100;
    }

    /**
     * Get time spent formatted.
     */
    public function getTimeSpentFormattedAttribute(): string
    {
        if ($this->time_spent < 60) {
            return $this->time_spent . 's';
        }

        $minutes = floor($this->time_spent / 60);
        $seconds = $this->time_spent % 60;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Get time spent in minutes.
     */
    public function getTimeSpentMinutesAttribute(): float
    {
        return round($this->time_spent / 60, 1);
    }

    /**
     * Scope for progress by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for progress by post.
     */
    public function scopeByPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope for completed readings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('percentage', '>=', 100);
    }

    /**
     * Scope for in-progress readings.
     */
    public function scopeInProgress($query)
    {
        return $query->where('percentage', '>', 0)
            ->where('percentage', '<', 100);
    }

    /**
     * Scope for recently read.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('last_read_at', '>=', now()->subDays($days));
    }

    /**
     * Update progress.
     */
    public function updateProgress(int $percentage, ?int $timeSpent = null): self
    {
        $this->update([
            'percentage' => max(0, min(100, $percentage)),
            'time_spent' => $timeSpent !== null 
                ? max($this->time_spent, $timeSpent) 
                : $this->time_spent,
        ]);

        return $this;
    }

    /**
     * Get or create progress for user and post.
     */
    public static function getOrCreate(int $userId, int $postId): self
    {
        return static::firstOrCreate(
            ['post_id' => $postId, 'user_id' => $userId],
            ['percentage' => 0, 'time_spent' => 0]
        );
    }

    /**
     * Get user's progress for a post.
     */
    public static function getUserProgress(int $userId, int $postId): ?self
    {
        return static::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();
    }

    /**
     * Get user's reading statistics.
     */
    public static function getUserStats(int $userId): array
    {
        $totalPosts = static::where('user_id', $userId)->count();
        $completedPosts = static::where('user_id', $userId)->completed()->count();
        $inProgressPosts = static::where('user_id', $userId)->inProgress()->count();
        $totalTimeSpent = static::where('user_id', $userId)->sum('time_spent');

        return [
            'total_posts' => $totalPosts,
            'completed' => $completedPosts,
            'in_progress' => $inProgressPosts,
            'total_time_spent' => $totalTimeSpent,
            'total_time_spent_formatted' => gmdate('H\h i\m', $totalTimeSpent),
            'completion_rate' => $totalPosts > 0 ? round(($completedPosts / $totalPosts) * 100, 1) : 0,
        ];
    }
}
