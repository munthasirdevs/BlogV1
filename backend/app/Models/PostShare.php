<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PostShare
 *
 * Represents a share of a blog post with tracking information.
 *
 * @property int $id
 * @property int $post_id
 * @property int|null $user_id
 * @property string $provider - twitter, facebook, linkedin, email, copy, etc.
 * @property string|null $share_url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostShare extends Model
{
    use HasFactory;

    /**
     * Share providers.
     */
    const PROVIDER_TWITTER = 'twitter';
    const PROVIDER_FACEBOOK = 'facebook';
    const PROVIDER_LINKEDIN = 'linkedin';
    const PROVIDER_EMAIL = 'email';
    const PROVIDER_COPY = 'copy';
    const PROVIDER_WHATSAPP = 'whatsapp';
    const PROVIDER_REDDIT = 'reddit';
    const PROVIDER_OTHER = 'other';

    /**
     * Available providers.
     */
    const AVAILABLE_PROVIDERS = [
        self::PROVIDER_TWITTER,
        self::PROVIDER_FACEBOOK,
        self::PROVIDER_LINKEDIN,
        self::PROVIDER_EMAIL,
        self::PROVIDER_COPY,
        self::PROVIDER_WHATSAPP,
        self::PROVIDER_REDDIT,
        self::PROVIDER_OTHER,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'provider',
        'share_url',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'post_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($share) {
            // Capture IP and user agent if available
            if (request()) {
                $share->ip_address = request()->ip();
                $share->user_agent = request()->userAgent();
            }
        });

        static::created(function ($share) {
            // Update post's share count
            $share->post?->incrementShareCount($share->provider);
        });
    }

    /**
     * Get the post that was shared.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get the user who shared.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if share is from a logged-in user.
     */
    public function isFromLoggedInUser(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get provider display name.
     */
    public function getProviderDisplayNameAttribute(): string
    {
        return ucfirst($this->provider);
    }

    /**
     * Get provider icon class.
     */
    public function getProviderIconAttribute(): string
    {
        $icons = [
            self::PROVIDER_TWITTER => 'fa-twitter',
            self::PROVIDER_FACEBOOK => 'fa-facebook',
            self::PROVIDER_LINKEDIN => 'fa-linkedin',
            self::PROVIDER_EMAIL => 'fa-envelope',
            self::PROVIDER_COPY => 'fa-copy',
            self::PROVIDER_WHATSAPP => 'fa-whatsapp',
            self::PROVIDER_REDDIT => 'fa-reddit',
            self::PROVIDER_OTHER => 'fa-share',
        ];

        return $icons[$this->provider] ?? $icons[self::PROVIDER_OTHER];
    }

    /**
     * Scope for shares by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope for shares by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for shares by post.
     */
    public function scopeByPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope for shares in date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for today's shares.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Get share count by provider for a post.
     */
    public static function getCountByProvider(int $postId): array
    {
        return static::where('post_id', $postId)
            ->selectRaw('provider, COUNT(*) as count')
            ->groupBy('provider')
            ->get()
            ->pluck('count', 'provider')
            ->toArray();
    }

    /**
     * Get total share count for a post.
     */
    public static function getTotalCount(int $postId): int
    {
        return static::where('post_id', $postId)->count();
    }

    /**
     * Record a share.
     */
    public static function recordShare(
        int $postId,
        string $provider,
        ?int $userId = null,
        ?string $shareUrl = null
    ): static {
        return static::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'provider' => $provider,
            'share_url' => $shareUrl,
        ]);
    }
}
