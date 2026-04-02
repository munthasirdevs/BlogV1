<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PostView
 * 
 * Represents a view of a blog post with detailed visitor
 * information for analytics.
 * 
 * @property int $id
 * @property int $post_id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $session_id
 * @property string|null $referrer
 * @property int|null $time_on_page
 * @property bool $is_unique
 * @property \Illuminate\Support\Carbon $viewed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostView extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'ip_address',
        'user_agent',
        'session_id',
        'referrer',
        'time_on_page',
        'is_unique',
        'viewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'time_on_page' => 'integer',
            'is_unique' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($view) {
            if (empty($view->viewed_at)) {
                $view->viewed_at = now();
            }
        });
    }

    /**
     * Get the post that was viewed.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get the user who viewed the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if view is from a logged-in user.
     */
    public function isFromLoggedInUser(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if view is unique.
     */
    public function isUnique(): bool
    {
        return $this->is_unique;
    }

    /**
     * Get time on page formatted.
     */
    public function getTimeOnPageFormattedAttribute(): ?string
    {
        if (!$this->time_on_page) {
            return null;
        }

        if ($this->time_on_page < 60) {
            return $this->time_on_page . 's';
        }

        $minutes = floor($this->time_on_page / 60);
        $seconds = $this->time_on_page % 60;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Get referrer domain.
     */
    public function getReferrerDomainAttribute(): ?string
    {
        if (!$this->referrer) {
            return null;
        }

        $parsed = parse_url($this->referrer);
        return $parsed['host'] ?? null;
    }

    /**
     * Check if view is from search engine.
     */
    public function isFromSearchEngine(): bool
    {
        $searchEngines = ['google', 'bing', 'yahoo', 'duckduckgo', 'baidu'];
        $domain = strtolower($this->referrer_domain ?? '');

        foreach ($searchEngines as $engine) {
            if (str_contains($domain, $engine)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if view is from social media.
     */
    public function isFromSocialMedia(): bool
    {
        $socialMedia = ['facebook', 'twitter', 'linkedin', 'reddit', 'pinterest', 'instagram'];
        $domain = strtolower($this->referrer_domain ?? '');

        foreach ($socialMedia as $platform) {
            if (str_contains($domain, $platform)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get browser name from user agent.
     */
    public function getBrowserAttribute(): ?string
    {
        if (!$this->user_agent) {
            return null;
        }

        $ua = $this->user_agent;

        if (str_contains($ua, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($ua, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) {
            return 'Safari';
        } elseif (str_contains($ua, 'Edge')) {
            return 'Edge';
        } elseif (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) {
            return 'Internet Explorer';
        }

        return 'Other';
    }

    /**
     * Get OS name from user agent.
     */
    public function getOsAttribute(): ?string
    {
        if (!$this->user_agent) {
            return null;
        }

        $ua = $this->user_agent;

        if (str_contains($ua, 'Windows')) {
            return 'Windows';
        } elseif (str_contains($ua, 'Mac')) {
            return 'macOS';
        } elseif (str_contains($ua, 'Linux')) {
            return 'Linux';
        } elseif (str_contains($ua, 'Android')) {
            return 'Android';
        } elseif (str_contains($ua, 'iOS') || str_contains($ua, 'iPhone')) {
            return 'iOS';
        }

        return 'Other';
    }

    /**
     * Check if view is from mobile device.
     */
    public function isFromMobile(): bool
    {
        if (!$this->user_agent) {
            return false;
        }

        return (bool) preg_match('/(android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini)/i', $this->user_agent);
    }

    /**
     * Scope for views on a specific post.
     */
    public function scopeForPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope for views by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for views in a session.
     */
    public function scopeInSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for unique views only.
     */
    public function scopeUnique($query)
    {
        return $query->where('is_unique', true);
    }

    /**
     * Scope for views in date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * Scope for views today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('viewed_at', today());
    }

    /**
     * Scope for views this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('viewed_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope for views this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('viewed_at', now()->month)
            ->whereYear('viewed_at', now()->year);
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('viewed_at', 'desc');
    }

    /**
     * Scope for views from search engines.
     */
    public function scopeFromSearchEngines($query)
    {
        return $query->where('referrer', 'LIKE', '%google%')
            ->orWhere('referrer', 'LIKE', '%bing%')
            ->orWhere('referrer', 'LIKE', '%yahoo%')
            ->orWhere('referrer', 'LIKE', '%duckduckgo%');
    }

    /**
     * Scope for views from social media.
     */
    public function scopeFromSocialMedia($query)
    {
        return $query->where('referrer', 'LIKE', '%facebook%')
            ->orWhere('referrer', 'LIKE', '%twitter%')
            ->orWhere('referrer', 'LIKE', '%linkedin%')
            ->orWhere('referrer', 'LIKE', '%reddit%');
    }

    /**
     * Get unique views count for a post in date range.
     */
    public static function getUniqueViewsCount(int $postId, $startDate, $endDate): int
    {
        return static::forPost($postId)
            ->unique()
            ->dateRange($startDate, $endDate)
            ->count();
    }

    /**
     * Get total views count for a post in date range.
     */
    public static function getTotalViewsCount(int $postId, $startDate, $endDate): int
    {
        return static::forPost($postId)
            ->dateRange($startDate, $endDate)
            ->count();
    }

    /**
     * Record a post view.
     */
    public static function record(
        Post $post,
        ?string $sessionId = null,
        ?int $userId = null,
        ?string $referrer = null,
        ?string $userAgent = null,
        ?string $ipAddress = null
    ): static {
        return static::create([
            'post_id' => $post->id,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'referrer' => $referrer,
            'user_agent' => $userAgent ?? request()->userAgent(),
            'ip_address' => $ipAddress ?? request()->ip(),
            'is_unique' => true, // Will be updated if duplicate found
        ]);
    }

    /**
     * Record view and check for uniqueness.
     */
    public static function recordWithUniqueness(
        Post $post,
        string $sessionId,
        ?int $userId = null,
        ?string $referrer = null
    ): static {
        // Check if this session already viewed this post recently
        $existingView = static::forPost($post->id)
            ->inSession($sessionId)
            ->where('viewed_at', '>=', now()->subHours(24))
            ->first();

        if ($existingView) {
            // Not unique, return existing view
            return $existingView;
        }

        return static::record($post, $sessionId, $userId, $referrer);
    }
}
