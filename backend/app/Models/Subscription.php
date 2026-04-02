<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class Subscription
 * 
 * Represents a newsletter/email subscription with preferences
 * and confirmation tracking.
 * 
 * @property int $id
 * @property string $email
 * @property int|null $user_id
 * @property string $token
 * @property \Illuminate\Support\Carbon $subscribed_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $unsubscribed_at
 * @property bool $is_confirmed
 * @property bool $is_active
 * @property array|null $preferences
 * @property string $frequency
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Frequency constants.
     */
    const FREQUENCY_INSTANT = 'instant';
    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'user_id',
        'token',
        'subscribed_at',
        'confirmed_at',
        'unsubscribed_at',
        'is_confirmed',
        'is_active',
        'preferences',
        'frequency',
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
            'subscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'is_confirmed' => 'boolean',
            'is_active' => 'boolean',
            'preferences' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (empty($subscription->token)) {
                $subscription->token = Str::random(64);
            }
            if (empty($subscription->subscribed_at)) {
                $subscription->subscribed_at = now();
            }
            if (empty($subscription->frequency)) {
                $subscription->frequency = self::FREQUENCY_INSTANT;
            }
            if (!isset($subscription->is_confirmed)) {
                $subscription->is_confirmed = false;
            }
            if (!isset($subscription->is_active)) {
                $subscription->is_active = true;
            }
        });
    }

    /**
     * Get the user associated with the subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Confirm the subscription.
     */
    public function confirm(): bool
    {
        if ($this->is_confirmed) {
            return false;
        }

        $this->update([
            'is_confirmed' => true,
            'confirmed_at' => now(),
        ]);

        return true;
    }

    /**
     * Unsubscribe the user.
     */
    public function unsubscribe(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $this->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        return true;
    }

    /**
     * Resubscribe the user.
     */
    public function resubscribe(): bool
    {
        $this->update([
            'is_active' => true,
            'unsubscribed_at' => null,
            'subscribed_at' => now(),
        ]);

        return true;
    }

    /**
     * Regenerate confirmation token.
     */
    public function regenerateToken(): string
    {
        $this->update(['token' => Str::random(64)]);
        return $this->token;
    }

    /**
     * Check if subscription is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->is_confirmed;
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->unsubscribed_at;
    }

    /**
     * Check if subscription is pending confirmation.
     */
    public function isPending(): bool
    {
        return !$this->is_confirmed && $this->is_active;
    }

    /**
     * Get preference value.
     */
    public function getPreference(string $key, $default = null)
    {
        return $this->preferences[$key] ?? $default;
    }

    /**
     * Set preference value.
     */
    public function setPreference(string $key, $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->update(['preferences' => $preferences]);
    }

    /**
     * Check if user wants new post notifications.
     */
    public function wantsNewPostNotifications(): bool
    {
        return $this->getPreference('new_posts', true);
    }

    /**
     * Check if user wants weekly digest.
     */
    public function wantsWeeklyDigest(): bool
    {
        return $this->getPreference('weekly_digest', false);
    }

    /**
     * Scope for confirmed subscriptions.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }

    /**
     * Scope for unconfirmed subscriptions.
     */
    public function scopeUnconfirmed($query)
    {
        return $query->where('is_confirmed', false);
    }

    /**
     * Scope for active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('unsubscribed_at');
    }

    /**
     * Scope for inactive subscriptions.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for subscriptions with specific frequency.
     */
    public function scopeFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Scope to search by email.
     */
    public function scopeSearch($query, $email)
    {
        return $query->where('email', 'LIKE', "%{$email}%");
    }

    /**
     * Scope for subscriptions that need confirmation.
     */
    public function scopeNeedsConfirmation($query)
    {
        return $query->unconfirmed()
            ->active()
            ->where('created_at', '>=', now()->subDays(7));
    }

    /**
     * Get confirmed subscriber count.
     */
    public static function getConfirmedCount(): int
    {
        return static::confirmed()->active()->count();
    }

    /**
     * Find subscription by token.
     */
    public static function findByToken(string $token): ?static
    {
        return static::where('token', $token)->first();
    }

    /**
     * Subscribe an email.
     */
    public static function subscribe(string $email, ?int $userId = null, array $preferences = []): static
    {
        $subscription = static::firstOrCreate(
            ['email' => $email],
            [
                'user_id' => $userId,
                'preferences' => $preferences,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        );

        // If was previously unsubscribed, resubscribe
        if (!$subscription->is_active) {
            $subscription->resubscribe();
        }

        return $subscription;
    }

    /**
     * Unsubscribe an email.
     */
    public static function unsubscribeByEmail(string $email): bool
    {
        $subscription = static::where('email', $email)->first();
        
        if ($subscription) {
            return $subscription->unsubscribe();
        }

        return false;
    }
}
