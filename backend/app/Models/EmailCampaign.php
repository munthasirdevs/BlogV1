<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EmailCampaign
 *
 * Represents a newsletter/email campaign with support for A/B testing,
 * scheduling, and detailed tracking statistics.
 *
 * @property int $id
 * @property string $name
 * @property string $subject
 * @property string|null $subject_b
 * @property string|null $preview_text
 * @property int|null $from_user_id
 * @property string|null $from_name
 * @property string|null $from_email
 * @property string|null $reply_to
 * @property string $template
 * @property array|null $content
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int $total_recipients
 * @property int $sent_count
 * @property int $delivered_count
 * @property int $opened_count
 * @property int $clicked_count
 * @property int $bounced_count
 * @property int $complained_count
 * @property int $unsubscribed_count
 * @property bool $is_ab_test
 * @property int $ab_test_split
 * @property int $ab_test_sample_size
 * @property string|null $ab_test_winner
 * @property \Illuminate\Support\Carbon|null $ab_test_completed_at
 * @property array|null $segment_filters
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class EmailCampaign extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * A/B test winner constants.
     */
    const WINNER_A = 'a';
    const WINNER_B = 'b';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'subject',
        'subject_b',
        'preview_text',
        'from_user_id',
        'from_name',
        'from_email',
        'reply_to',
        'template',
        'content',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'complained_count',
        'unsubscribed_count',
        'is_ab_test',
        'ab_test_split',
        'ab_test_sample_size',
        'ab_test_winner',
        'ab_test_completed_at',
        'segment_filters',
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
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'delivered_count' => 'integer',
            'opened_count' => 'integer',
            'clicked_count' => 'integer',
            'bounced_count' => 'integer',
            'complained_count' => 'integer',
            'unsubscribed_count' => 'integer',
            'is_ab_test' => 'boolean',
            'ab_test_split' => 'integer',
            'ab_test_sample_size' => 'integer',
            'ab_test_completed_at' => 'datetime',
            'segment_filters' => 'array',
            'metadata' => 'array',
            'content' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (empty($campaign->status)) {
                $campaign->status = self::STATUS_DRAFT;
            }
            if (!isset($campaign->is_ab_test)) {
                $campaign->is_ab_test = false;
            }
            if (!isset($campaign->ab_test_split)) {
                $campaign->ab_test_split = 50;
            }
            if (!isset($campaign->ab_test_sample_size)) {
                $campaign->ab_test_sample_size = 10;
            }
        });
    }

    /**
     * Get the user who created this campaign.
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the email trackings for this campaign.
     */
    public function trackings(): HasMany
    {
        return $this->hasMany(EmailTracking::class);
    }

    /**
     * Start the campaign.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_SENDING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark campaign as completed.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancel the campaign.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Schedule the campaign.
     */
    public function schedule(\DateTimeInterface $scheduledAt): void
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Increment sent count.
     */
    public function incrementSent(): void
    {
        $this->increment('sent_count');
    }

    /**
     * Increment delivered count.
     */
    public function incrementDelivered(): void
    {
        $this->increment('delivered_count');
    }

    /**
     * Increment opened count.
     */
    public function incrementOpened(): void
    {
        $this->increment('opened_count');
    }

    /**
     * Increment clicked count.
     */
    public function incrementClicked(): void
    {
        $this->increment('clicked_count');
    }

    /**
     * Increment bounced count.
     */
    public function incrementBounced(): void
    {
        $this->increment('bounced_count');
    }

    /**
     * Increment complained count.
     */
    public function incrementComplained(): void
    {
        $this->increment('complained_count');
    }

    /**
     * Increment unsubscribed count.
     */
    public function incrementUnsubscribed(): void
    {
        $this->increment('unsubscribed_count');
    }

    /**
     * Calculate open rate percentage.
     */
    public function getOpenRate(): float
    {
        if ($this->delivered_count === 0) {
            return 0.0;
        }
        return round(($this->opened_count / $this->delivered_count) * 100, 2);
    }

    /**
     * Calculate click rate percentage.
     */
    public function getClickRate(): float
    {
        if ($this->delivered_count === 0) {
            return 0.0;
        }
        return round(($this->clicked_count / $this->delivered_count) * 100, 2);
    }

    /**
     * Calculate bounce rate percentage.
     */
    public function getBounceRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }
        return round(($this->bounced_count / $this->sent_count) * 100, 2);
    }

    /**
     * Calculate complaint rate percentage.
     */
    public function getComplaintRate(): float
    {
        if ($this->delivered_count === 0) {
            return 0.0;
        }
        return round(($this->complained_count / $this->delivered_count) * 100, 4);
    }

    /**
     * Calculate unsubscribe rate percentage.
     */
    public function getUnsubscribeRate(): float
    {
        if ($this->delivered_count === 0) {
            return 0.0;
        }
        return round(($this->unsubscribed_count / $this->delivered_count) * 100, 2);
    }

    /**
     * Check if campaign is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if campaign is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if campaign is sending.
     */
    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    /**
     * Check if campaign is sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if campaign is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if A/B test is complete.
     */
    public function isAbTestComplete(): bool
    {
        return $this->is_ab_test && $this->ab_test_winner !== null;
    }

    /**
     * Get segment filters.
     */
    public function getSegmentFilters(): array
    {
        return $this->segment_filters ?? [];
    }

    /**
     * Scope for active campaigns.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_SENDING]);
    }

    /**
     * Scope for completed campaigns.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope for scheduled campaigns due to send.
     */
    public function scopeDue($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope for A/B test campaigns.
     */
    public function scopeAbTest($query)
    {
        return $query->where('is_ab_test', true);
    }

    /**
     * Create a new campaign.
     */
    public static function createCampaign(
        string $name,
        string $subject,
        string $template = 'newsletter',
        array $content = [],
        ?array $segmentFilters = null
    ): static {
        return static::create([
            'name' => $name,
            'subject' => $subject,
            'template' => $template,
            'content' => $content,
            'segment_filters' => $segmentFilters,
        ]);
    }
}
