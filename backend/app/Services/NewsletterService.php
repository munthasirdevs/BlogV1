<?php

namespace App\Services;

use App\Models\EmailCampaign;
use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Models\Post;
use App\Repositories\SubscriptionRepository;
use App\Jobs\SendCampaignEmail;
use App\Jobs\SendDigestEmail;
use App\Jobs\SendNewPostNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Class NewsletterService
 *
 * Service for managing newsletter campaigns, digest emails,
 * A/B testing, and email sending with rate limiting.
 */
class NewsletterService
{
    /**
     * Subscription repository instance.
     */
    protected SubscriptionRepository $subscriptionRepository;

    /**
     * Rate limit: emails per minute.
     */
    protected int $rateLimit = 100;

    /**
     * Batch size for sending emails.
     */
    protected int $batchSize = 50;

    /**
     * Delay between batches in seconds.
     */
    protected int $batchDelay = 60;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->subscriptionRepository = new SubscriptionRepository();
    }

    /**
     * Create a new email campaign.
     */
    public function createCampaign(
        string $name,
        string $subject,
        string $template = 'newsletter',
        array $content = [],
        ?array $segmentFilters = null
    ): EmailCampaign {
        return EmailCampaign::createCampaign($name, $subject, $template, $content, $segmentFilters);
    }

    /**
     * Create an A/B test campaign.
     */
    public function createAbTestCampaign(
        string $name,
        string $subjectA,
        string $subjectB,
        int $splitPercentage = 50,
        int $sampleSize = 10,
        string $template = 'newsletter',
        array $content = []
    ): EmailCampaign {
        return EmailCampaign::create([
            'name' => $name,
            'subject' => $subjectA,
            'subject_b' => $subjectB,
            'template' => $template,
            'content' => $content,
            'is_ab_test' => true,
            'ab_test_split' => $splitPercentage,
            'ab_test_sample_size' => $sampleSize,
        ]);
    }

    /**
     * Schedule a campaign for sending.
     */
    public function scheduleCampaign(int $campaignId, \DateTimeInterface $scheduledAt): bool
    {
        $campaign = EmailCampaign::find($campaignId);

        if (!$campaign || !$campaign->isDraft()) {
            return false;
        }

        $campaign->schedule($scheduledAt);

        Log::info('Campaign scheduled', [
            'campaign_id' => $campaignId,
            'scheduled_at' => $scheduledAt->toIso8601String(),
        ]);

        return true;
    }

    /**
     * Start sending a campaign immediately.
     */
    public function startCampaign(int $campaignId): bool
    {
        $campaign = EmailCampaign::find($campaignId);

        if (!$campaign || !$campaign->isDraft()) {
            return false;
        }

        return $this->sendCampaign($campaign);
    }

    /**
     * Send a campaign to subscribers.
     */
    public function sendCampaign(EmailCampaign $campaign): bool
    {
        return DB::transaction(function () use ($campaign) {
            // Get subscribers based on segment filters
            $subscribers = $this->getSegmentedSubscribers($campaign->getSegmentFilters());
            
            // Exclude bounced, complained, and unsubscribed emails
            $excluded = $this->subscriptionRepository->getExcludedEmails();
            $subscribers = $subscribers->filter(function ($s) use ($excluded) {
                return !in_array($s->email, $excluded);
            });

            if ($subscribers->isEmpty()) {
                Log::warning('No subscribers for campaign', ['campaign_id' => $campaign->id]);
                return false;
            }

            // Handle A/B test
            if ($campaign->is_ab_test && !$campaign->isAbTestComplete()) {
                return $this->sendAbTestCampaign($campaign, $subscribers);
            }

            // Update campaign stats
            $campaign->update([
                'total_recipients' => $subscribers->count(),
            ]);

            $campaign->start();

            // Queue emails in batches
            $this->queueCampaignEmails($campaign, $subscribers->values()->all());

            Log::info('Campaign sending started', [
                'campaign_id' => $campaign->id,
                'recipients' => $subscribers->count(),
            ]);

            return true;
        });
    }

    /**
     * Send A/B test campaign.
     */
    protected function sendAbTestCampaign(EmailCampaign $campaign, Collection $subscribers): bool
    {
        $sampleSize = (int) ceil($subscribers->count() * ($campaign->ab_test_sample_size / 100));
        $sampleSubscribers = $subscribers->random($sampleSize);
        $remainingSubscribers = $subscribers->diff($sampleSubscribers);

        // Split sample into A and B groups
        $splitPoint = (int) ceil($sampleSize * ($campaign->ab_test_split / 100));
        $groupA = $sampleSubscribers->take($splitPoint);
        $groupB = $sampleSubscribers->slice($splitPoint);

        // Queue A variant
        $this->queueCampaignEmails($campaign, $groupA->values()->all(), 'a');

        // Queue B variant
        $this->queueCampaignEmails($campaign, $groupB->values()->all(), 'b');

        // Store remaining for winner send
        $campaign->update([
            'metadata' => array_merge($campaign->metadata ?? [], [
                'ab_remaining' => $remainingSubscribers->pluck('id')->toArray(),
                'ab_group_a_count' => $groupA->count(),
                'ab_group_b_count' => $groupB->count(),
            ]),
            'total_recipients' => $subscribers->count(),
        ]);

        $campaign->start();

        Log::info('A/B test campaign started', [
            'campaign_id' => $campaign->id,
            'group_a' => $groupA->count(),
            'group_b' => $groupB->count(),
            'remaining' => $remainingSubscribers->count(),
        ]);

        return true;
    }

    /**
     * Queue campaign emails with rate limiting.
     */
    protected function queueCampaignEmails(
        EmailCampaign $campaign,
        array $subscribers,
        ?string $variant = null
    ): void {
        $batches = array_chunk($subscribers, $this->batchSize);

        foreach ($batches as $index => $batch) {
            $delay = $index * $this->batchDelay;

            foreach ($batch as $subscriber) {
                SendCampaignEmail::dispatch($campaign, $subscriber, $variant)
                    ->delay(now()->addSeconds($delay));
            }
        }
    }

    /**
     * Get segmented subscribers based on filters.
     */
    public function getSegmentedSubscribers(?array $filters = null): Collection
    {
        if (empty($filters)) {
            return $this->subscriptionRepository->getConfirmedActive();
        }

        $query = Subscription::query()
            ->confirmed()
            ->active();

        // Filter by frequency
        if (isset($filters['frequency'])) {
            $query->whereIn('frequency', (array) $filters['frequency']);
        }

        // Filter by categories
        if (isset($filters['categories']) && !empty($filters['categories'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['categories'] as $categoryId) {
                    $q->orWhereJsonContains('preferences->categories', $categoryId);
                }
            });
        }

        // Filter by engagement
        if (isset($filters['engagement'])) {
            $query->join('email_trackings', 'subscriptions.id', '=', 'email_trackings.subscription_id');
            
            switch ($filters['engagement']) {
                case 'high':
                    $query->whereRaw('email_trackings.open_count >= 5 OR email_trackings.click_count >= 2');
                    break;
                case 'medium':
                    $query->whereRaw('email_trackings.open_count >= 2 AND email_trackings.open_count < 5');
                    break;
                case 'low':
                    $query->whereRaw('email_trackings.open_count < 2');
                    break;
            }
            
            $query->select('subscriptions.*')->distinct();
        }

        return $query->get();
    }

    /**
     * Determine A/B test winner and send to remaining subscribers.
     */
    public function determineAbWinner(int $campaignId): bool
    {
        $campaign = EmailCampaign::find($campaignId);

        if (!$campaign || !$campaign->is_ab_test) {
            return false;
        }

        // Get A/B test results
        $aStats = $this->getAbVariantStats($campaign, 'a');
        $bStats = $this->getAbVariantStats($campaign, 'b');

        // Determine winner based on open rate (or click rate as tiebreaker)
        $winner = $this->calculateAbWinner($aStats, $bStats);

        $campaign->update([
            'ab_test_winner' => $winner,
            'ab_test_completed_at' => now(),
        ]);

        // Send winner to remaining subscribers
        $metadata = $campaign->metadata ?? [];
        $remainingIds = $metadata['ab_remaining'] ?? [];

        if (!empty($remainingIds)) {
            $remainingSubscribers = Subscription::whereIn('id', $remainingIds)->get();
            $this->queueCampaignEmails($campaign, $remainingSubscribers->all(), $winner);
        }

        Log::info('A/B test winner determined', [
            'campaign_id' => $campaignId,
            'winner' => $winner,
            'a_open_rate' => $aStats['open_rate'],
            'b_open_rate' => $bStats['open_rate'],
        ]);

        return true;
    }

    /**
     * Get A/B variant statistics.
     */
    protected function getAbVariantStats(EmailCampaign $campaign, string $variant): array
    {
        $trackings = EmailTracking::where('email_campaign_id', $campaign->id)
            ->where('metadata->variant', $variant)
            ->get();

        $sent = $trackings->count();
        $opened = $trackings->where('opened_at', '!=', null)->count();
        $clicked = $trackings->where('clicked_at', '!=', null)->count();

        return [
            'sent' => $sent,
            'opened' => $opened,
            'clicked' => $clicked,
            'open_rate' => $sent > 0 ? ($opened / $sent) * 100 : 0,
            'click_rate' => $sent > 0 ? ($clicked / $sent) * 100 : 0,
        ];
    }

    /**
     * Calculate A/B test winner.
     */
    protected function calculateAbWinner(array $aStats, array $bStats): string
    {
        // Compare open rates first
        if ($aStats['open_rate'] > $bStats['open_rate']) {
            return EmailCampaign::WINNER_A;
        }
        if ($bStats['open_rate'] > $aStats['open_rate']) {
            return EmailCampaign::WINNER_B;
        }

        // Tiebreaker: click rate
        if ($aStats['click_rate'] > $bStats['click_rate']) {
            return EmailCampaign::WINNER_A;
        }

        return EmailCampaign::WINNER_B;
    }

    /**
     * Send digest email to subscribers.
     */
    public function sendDigest(string $frequency, array $posts): int
    {
        $subscribers = $this->subscriptionRepository->getDigestSubscribers($frequency);
        
        // Exclude bounced and complained emails
        $excluded = $this->subscriptionRepository->getExcludedEmails();
        $subscribers = $subscribers->filter(function ($s) use ($excluded) {
            return !in_array($s->email, $excluded);
        });

        if ($subscribers->isEmpty()) {
            return 0;
        }

        $count = 0;
        $batches = $subscribers->chunk($this->batchSize);

        foreach ($batches as $index => $batch) {
            $delay = $index * $this->batchDelay;

            foreach ($batch as $subscriber) {
                SendDigestEmail::dispatch($subscriber, $posts, $frequency)
                    ->delay(now()->addSeconds($delay));
                $count++;
            }
        }

        Log::info('Digest emails queued', [
            'frequency' => $frequency,
            'count' => $count,
            'posts' => count($posts),
        ]);

        return $count;
    }

    /**
     * Send new post notification.
     */
    public function sendNewPostNotification(Post $post): int
    {
        $subscribers = $this->getNewPostSubscribers($post->category_id);

        if (empty($subscribers)) {
            return 0;
        }

        $count = 0;
        $batches = collect($subscribers)->chunk($this->batchSize);

        foreach ($batches as $index => $batch) {
            $delay = $index * $this->batchDelay;

            foreach ($batch as $subscriber) {
                SendNewPostNotification::dispatch($subscriber, $post)
                    ->delay(now()->addSeconds($delay));
                $count++;
            }
        }

        Log::info('New post notifications queued', [
            'post_id' => $post->id,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Get subscribers for new post notification.
     */
    protected function getNewPostSubscribers(?int $categoryId = null): array
    {
        $subscribers = $this->subscriptionRepository->getNewPostSubscribers($categoryId);
        
        // Exclude bounced and complained emails
        $excluded = $this->subscriptionRepository->getExcludedEmails();
        
        return collect($subscribers)->filter(function ($s) use ($excluded) {
            return !in_array($s->email, $excluded);
        })->values()->all();
    }

    /**
     * Record email open.
     */
    public function recordOpen(int $trackingId, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        $tracking = EmailTracking::find($trackingId);

        if (!$tracking) {
            return false;
        }

        $tracking->recordOpen($ipAddress, $userAgent);

        // Increment campaign open count if applicable
        if ($tracking->email_campaign_id) {
            $campaign = EmailCampaign::find($tracking->email_campaign_id);
            if ($campaign) {
                $campaign->incrementOpened();
            }
        }

        return true;
    }

    /**
     * Record link click.
     */
    public function recordClick(int $trackingId, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        $tracking = EmailTracking::find($trackingId);

        if (!$tracking) {
            return false;
        }

        $tracking->recordClick($ipAddress, $userAgent);

        // Increment campaign click count if applicable
        if ($tracking->email_campaign_id) {
            $campaign = EmailCampaign::find($tracking->email_campaign_id);
            if ($campaign) {
                $campaign->incrementClicked();
            }
        }

        return true;
    }

    /**
     * Record email bounce.
     */
    public function recordBounce(int $trackingId, string $type, ?string $reason = null): bool
    {
        $tracking = EmailTracking::find($trackingId);

        if (!$tracking) {
            return false;
        }

        $tracking->recordBounce($type, $reason);

        // Increment campaign bounce count if applicable
        if ($tracking->email_campaign_id) {
            $campaign = EmailCampaign::find($tracking->email_campaign_id);
            if ($campaign) {
                $campaign->incrementBounced();
            }
        }

        Log::info('Email bounce recorded', [
            'tracking_id' => $trackingId,
            'type' => $type,
            'email' => $tracking->subscription?->email,
        ]);

        return true;
    }

    /**
     * Record spam complaint.
     */
    public function recordComplaint(int $trackingId, string $type = EmailTracking::COMPLAINT_SPAM): bool
    {
        $tracking = EmailTracking::find($trackingId);

        if (!$tracking) {
            return false;
        }

        $tracking->recordComplaint($type);

        // Increment campaign complaint count if applicable
        if ($tracking->email_campaign_id) {
            $campaign = EmailCampaign::find($tracking->email_campaign_id);
            if ($campaign) {
                $campaign->incrementComplained();
            }
        }

        Log::warning('Spam complaint recorded', [
            'tracking_id' => $trackingId,
            'type' => $type,
            'email' => $tracking->subscription?->email,
        ]);

        return true;
    }

    /**
     * Get campaign statistics.
     */
    public function getCampaignStats(int $campaignId): array
    {
        $campaign = EmailCampaign::find($campaignId);

        if (!$campaign) {
            return [];
        }

        return [
            'name' => $campaign->name,
            'subject' => $campaign->subject,
            'status' => $campaign->status,
            'total_recipients' => $campaign->total_recipients,
            'sent' => $campaign->sent_count,
            'delivered' => $campaign->delivered_count,
            'opened' => $campaign->opened_count,
            'clicked' => $campaign->clicked_count,
            'bounced' => $campaign->bounced_count,
            'complained' => $campaign->complained_count,
            'unsubscribed' => $campaign->unsubscribed_count,
            'open_rate' => $campaign->getOpenRate(),
            'click_rate' => $campaign->getClickRate(),
            'bounce_rate' => $campaign->getBounceRate(),
            'complaint_rate' => $campaign->getComplaintRate(),
            'unsubscribe_rate' => $campaign->getUnsubscribeRate(),
        ];
    }

    /**
     * Get all campaigns.
     */
    public function getCampaigns(int $perPage = 15)
    {
        return EmailCampaign::orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get campaign by ID.
     */
    public function getCampaign(int $id): ?EmailCampaign
    {
        return EmailCampaign::find($id);
    }

    /**
     * Cancel a campaign.
     */
    public function cancelCampaign(int $campaignId): bool
    {
        $campaign = EmailCampaign::find($campaignId);

        if (!$campaign || $campaign->isSent() || $campaign->isCancelled()) {
            return false;
        }

        $campaign->cancel();

        Log::info('Campaign cancelled', ['campaign_id' => $campaignId]);

        return true;
    }

    /**
     * Set rate limit.
     */
    public function setRateLimit(int $emailsPerMinute): void
    {
        $this->rateLimit = $emailsPerMinute;
        $this->batchSize = (int) ceil($emailsPerMinute / 2);
        $this->batchDelay = 60;
    }

    /**
     * Set batch size.
     */
    public function setBatchSize(int $size, int $delaySeconds): void
    {
        $this->batchSize = $size;
        $this->batchDelay = $delaySeconds;
    }
}
