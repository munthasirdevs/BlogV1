<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ActiveSession
 *
 * Represents a currently active user session for real-time analytics.
 * Sessions are considered active if last_seen_at is within the last 5 minutes.
 *
 * @property int $id
 * @property string $session_id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $current_url
 * @property string|null $current_page_title
 * @property string|null $referrer
 * @property \Illuminate\Support\Carbon $first_seen_at
 * @property \Illuminate\Support\Carbon $last_seen_at
 * @property int $page_views
 * @property string|null $visitor_fingerprint
 * @property bool $is_new_visitor
 * @property string|null $country
 * @property string|null $city
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $os
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ActiveSession extends Model
{
    use HasFactory;

    /**
     * Number of minutes a session is considered active after last activity.
     */
    const ACTIVE_THRESHOLD_MINUTES = 5;

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
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'current_url',
        'current_page_title',
        'referrer',
        'first_seen_at',
        'last_seen_at',
        'page_views',
        'visitor_fingerprint',
        'is_new_visitor',
        'country',
        'city',
        'device_type',
        'browser',
        'os',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'page_views' => 'integer',
            'is_new_visitor' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->first_seen_at)) {
                $session->first_seen_at = now();
            }
            if (empty($session->last_seen_at)) {
                $session->last_seen_at = now();
            }
        });
    }

    /**
     * Get the user associated with the session.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for active sessions (seen within threshold).
     */
    public function scopeActive($query, int $thresholdMinutes = null)
    {
        $threshold = $thresholdMinutes ?? self::ACTIVE_THRESHOLD_MINUTES;
        return $query->where('last_seen_at', '>=', now()->subMinutes($threshold));
    }

    /**
     * Scope for sessions by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for sessions by fingerprint.
     */
    public function scopeByFingerprint($query, string $fingerprint)
    {
        return $query->where('visitor_fingerprint', $fingerprint);
    }

    /**
     * Scope for new visitor sessions.
     */
    public function scopeNewVisitors($query)
    {
        return $query->where('is_new_visitor', true);
    }

    /**
     * Scope for returning visitor sessions.
     */
    public function scopeReturningVisitors($query)
    {
        return $query->where('is_new_visitor', false);
    }

    /**
     * Scope for sessions by device type.
     */
    public function scopeDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope for sessions by country.
     */
    public function scopeCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope to order by last seen descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('last_seen_at');
    }

    /**
     * Check if session is still active.
     */
    public function isActive(int $thresholdMinutes = null): bool
    {
        $threshold = $thresholdMinutes ?? self::ACTIVE_THRESHOLD_MINUTES;
        return $this->last_seen_at->gte(now()->subMinutes($threshold));
    }

    /**
     * Update session activity.
     */
    public function touchActivity(
        ?string $url = null,
        ?string $pageTitle = null
    ): void {
        $this->update([
            'last_seen_at' => now(),
            'current_url' => $url ?? $this->current_url,
            'current_page_title' => $pageTitle ?? $this->current_page_title,
            'page_views' => ($this->page_views ?? 0) + 1,
        ]);
    }

    /**
     * Get session duration in seconds.
     */
    public function getDurationAttribute(): int
    {
        return $this->first_seen_at->diffInSeconds($this->last_seen_at);
    }

    /**
     * Get formatted session duration.
     */
    public function getDurationFormattedAttribute(): string
    {
        $seconds = $this->duration;

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return "{$hours}h {$remainingMinutes}m";
        }

        return "{$minutes}m {$remainingSeconds}s";
    }

    /**
     * Get active sessions count.
     */
    public static function getActiveCount(int $thresholdMinutes = null): int
    {
        return static::active($thresholdMinutes)->count();
    }

    /**
     * Get active sessions with current pages.
     */
    public static function getActiveSessionsWithPages(int $thresholdMinutes = null): \Illuminate\Support\Collection
    {
        return static::active($thresholdMinutes)
            ->latest()
            ->get(['session_id', 'user_id', 'current_url', 'current_page_title', 'last_seen_at']);
    }

    /**
     * Get active users by country.
     */
    public static function getActiveUsersByCountry(int $thresholdMinutes = null): \Illuminate\Support\Collection
    {
        return static::active($thresholdMinutes)
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get active users by device type.
     */
    public static function getActiveUsersByDevice(int $thresholdMinutes = null): \Illuminate\Support\Collection
    {
        return static::active($thresholdMinutes)
            ->whereNotNull('device_type')
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->get();
    }

    /**
     * Clean up expired sessions.
     */
    public static function cleanupExpired(int $thresholdMinutes = null): int
    {
        $threshold = $thresholdMinutes ?? self::ACTIVE_THRESHOLD_MINUTES;
        return static::where('last_seen_at', '<', now()->subMinutes($threshold))->delete();
    }

    /**
     * Find or create active session.
     */
    public static function findOrCreate(
        string $sessionId,
        ?int $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $fingerprint = null
    ): static {
        return static::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'visitor_fingerprint' => $fingerprint,
                'last_seen_at' => now(),
            ]
        );
    }
}
