<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class EmailTracking
 *
 * Tracks email delivery, opens, clicks, bounces, and complaints
 * for newsletter subscriptions and campaigns.
 *
 * @property int $id
 * @property int $subscription_id
 * @property int|null $email_campaign_id
 * @property string $email_type
 * @property string|null $subject
 * @property string|null $message_id
 * @property \Illuminate\Support\Carbon $sent_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property int $open_count
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property int $click_count
 * @property \Illuminate\Support\Carbon|null $bounced_at
 * @property string|null $bounce_type
 * @property string|null $bounce_reason
 * @property \Illuminate\Support\Carbon|null $complained_at
 * @property string|null $complaint_type
 * @property bool $is_unsubscribed
 * @property \Illuminate\Support\Carbon|null $unsubscribed_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Subscription $subscription
 * @property-read EmailCampaign|null $campaign
 */
class EmailTracking extends Model
{
    use HasFactory;

    /**
     * Email type constants.
     */
    const TYPE_CONFIRMATION = 'confirmation';
    const TYPE_DIGEST = 'digest';
    const TYPE_NEWSLETTER = 'newsletter';
    const TYPE_NEW_POST = 'new_post';
    const TYPE_WELCOME = 'welcome';
    const TYPE_UNSUBSCRIBE_CONFIRM = 'unsubscribe_confirm';

    /**
     * Bounce type constants.
     */
    const BOUNCE_HARD = 'hard';
    const BOUNCE_SOFT = 'soft';

    /**
     * Complaint type constants.
     */
    const COMPLAINT_ABUSE = 'abuse';
    const COMPLAINT_SPAM = 'spam';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subscription_id',
        'email_campaign_id',
        'email_type',
        'subject',
        'message_id',
        'sent_at',
        'delivered_at',
        'opened_at',
        'open_count',
        'clicked_at',
        'click_count',
        'bounced_at',
        'bounce_type',
        'bounce_reason',
        'complained_at',
        'complaint_type',
        'is_unsubscribed',
        'unsubscribed_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'open_count' => 'integer',
            'clicked_at' => 'datetime',
            'click_count' => 'integer',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
            'is_unsubscribed' => 'boolean',
            'unsubscribed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tracking) {
            if (empty($tracking->sent_at)) {
                $tracking->sent_at = now();
            }
            if (!isset($tracking->open_count)) {
                $tracking->open_count = 0;
            }
            if (!isset($tracking->click_count)) {
                $tracking->click_count = 0;
            }
        });
    }

    /**
     * Get the subscription that owns this tracking.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the campaign this tracking belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class);
    }

    /**
     * Record an email open.
     */
    public function recordOpen(?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->increment('open_count');
        
        if (!$this->opened_at) {
            $this->update([
                'opened_at' => now(),
                'ip_address' => $ipAddress ?? request()->ip(),
                'user_agent' => $userAgent ?? request()->userAgent(),
            ]);
        } else {
            $this->update([
                'ip_address' => $ipAddress ?? request()->ip(),
                'user_agent' => $userAgent ?? request()->userAgent(),
            ]);
        }
    }

    /**
     * Record a link click.
     */
    public function recordClick(?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->increment('click_count');
        
        if (!$this->clicked_at) {
            $this->update([
                'clicked_at' => now(),
                'ip_address' => $ipAddress ?? request()->ip(),
                'user_agent' => $userAgent ?? request()->userAgent(),
            ]);
        }
    }

    /**
     * Record email delivery.
     */
    public function recordDelivery(): void
    {
        $this->update(['delivered_at' => now()]);
    }

    /**
     * Record email bounce.
     */
    public function recordBounce(string $type, ?string $reason = null): void
    {
        $this->update([
            'bounced_at' => now(),
            'bounce_type' => $type,
            'bounce_reason' => $reason,
        ]);

        // Mark subscription as inactive for hard bounces
        if ($type === self::BOUNCE_HARD && $this->subscription) {
            $this->subscription->update([
                'is_active' => false,
                'unsubscribed_at' => now(),
            ]);
        }
    }

    /**
     * Record spam complaint.
     */
    public function recordComplaint(string $type = self::COMPLAINT_SPAM): void
    {
        $this->update([
            'complained_at' => now(),
            'complaint_type' => $type,
        ]);

        // Immediately unsubscribe on complaint
        if ($this->subscription) {
            $this->subscription->update([
                'is_active' => false,
                'unsubscribed_at' => now(),
            ]);
        }
    }

    /**
     * Check if email was opened.
     */
    public function wasOpened(): bool
    {
        return $this->open_count > 0;
    }

    /**
     * Check if any link was clicked.
     */
    public function wasClicked(): bool
    {
        return $this->click_count > 0;
    }

    /**
     * Check if email bounced.
     */
    public function wasBounced(): bool
    {
        return $this->bounced_at !== null;
    }

    /**
     * Check if it was a hard bounce.
     */
    public function wasHardBounced(): bool
    {
        return $this->bounce_type === self::BOUNCE_HARD;
    }

    /**
     * Check if complaint was received.
     */
    public function wasComplained(): bool
    {
        return $this->complained_at !== null;
    }

    /**
     * Get engagement score (0-100).
     */
    public function getEngagementScore(): int
    {
        $score = 0;
        
        if ($this->wasOpened()) {
            $score += 30;
            // Add more for multiple opens
            $score += min($this->open_count * 5, 20);
        }
        
        if ($this->wasClicked()) {
            $score += 40;
            // Add more for multiple clicks
            $score += min($this->click_count * 5, 10);
        }
        
        return min($score, 100);
    }

    /**
     * Scope for opened emails.
     */
    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    /**
     * Scope for clicked emails.
     */
    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    /**
     * Scope for bounced emails.
     */
    public function scopeBounced($query)
    {
        return $query->whereNotNull('bounced_at');
    }

    /**
     * Scope for hard bounced emails.
     */
    public function scopeHardBounced($query)
    {
        return $query->where('bounce_type', self::BOUNCE_HARD);
    }

    /**
     * Scope for soft bounced emails.
     */
    public function scopeSoftBounced($query)
    {
        return $query->where('bounce_type', self::BOUNCE_SOFT);
    }

    /**
     * Scope for complained emails.
     */
    public function scopeComplained($query)
    {
        return $query->whereNotNull('complained_at');
    }

    /**
     * Scope for specific email type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('email_type', $type);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sent_at', [$startDate, $endDate]);
    }

    /**
     * Get tracking by subscription and type.
     */
    public static function findBySubscriptionAndType(int $subscriptionId, string $type): ?static
    {
        return static::where('subscription_id', $subscriptionId)
            ->where('email_type', $type)
            ->latest('sent_at')
            ->first();
    }

    /**
     * Create tracking record.
     */
    public static function createTracking(
        int $subscriptionId,
        string $type,
        ?string $subject = null,
        ?int $campaignId = null,
        ?string $messageId = null
    ): static {
        return static::create([
            'subscription_id' => $subscriptionId,
            'email_type' => $type,
            'subject' => $subject,
            'email_campaign_id' => $campaignId,
            'message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }
}
