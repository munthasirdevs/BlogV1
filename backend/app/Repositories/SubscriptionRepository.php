<?php

namespace App\Repositories;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class SubscriptionRepository
 *
 * Repository for managing newsletter subscriptions with advanced
 * querying, segmentation, and filtering capabilities.
 */
class SubscriptionRepository extends BaseRepository
{
    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return Subscription::class;
    }

    /**
     * Get confirmed and active subscriptions.
     */
    public function getConfirmedActive(): Collection
    {
        return $this->model->query()
            ->confirmed()
            ->active()
            ->get();
    }

    /**
     * Get paginated confirmed and active subscriptions.
     */
    public function getConfirmedActivePaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()
            ->confirmed()
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get subscriptions by frequency preference.
     */
    public function getByFrequency(string $frequency): Collection
    {
        return $this->model->query()
            ->confirmed()
            ->active()
            ->frequency($frequency)
            ->get();
    }

    /**
     * Get subscriptions that need confirmation (pending).
     */
    public function getNeedsConfirmation(): Collection
    {
        return $this->model->query()
            ->needsConfirmation()
            ->get();
    }

    /**
     * Search subscriptions by email.
     */
    public function searchByEmail(string $email, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()
            ->search($email)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find subscription by email.
     */
    public function findByEmail(string $email): ?Subscription
    {
        return $this->model->query()
            ->where('email', $email)
            ->first();
    }

    /**
     * Find confirmed subscription by email.
     */
    public function findConfirmedByEmail(string $email): ?Subscription
    {
        return $this->model->query()
            ->confirmed()
            ->where('email', $email)
            ->first();
    }

    /**
     * Find subscription by token.
     */
    public function findByToken(string $token): ?Subscription
    {
        return $this->model->query()
            ->where('token', $token)
            ->first();
    }

    /**
     * Find unconfirmed subscription by token (for confirmation).
     */
    public function findUnconfirmedByToken(string $token): ?Subscription
    {
        return $this->model->query()
            ->unconfirmed()
            ->active()
            ->where('token', $token)
            ->first();
    }

    /**
     * Get subscriptions by category interest.
     */
    public function getByCategoryInterest(int $categoryId): Collection
    {
        return $this->model->query()
            ->confirmed()
            ->active()
            ->whereJsonContains('preferences->categories', $categoryId)
            ->get();
    }

    /**
     * Get subscriptions by multiple category interests.
     */
    public function getByCategoryInterests(array $categoryIds): Collection
    {
        return $this->model->query()
            ->confirmed()
            ->active()
            ->where(function (Builder $query) use ($categoryIds) {
                foreach ($categoryIds as $categoryId) {
                    $query->orWhereJsonContains('preferences->categories', $categoryId);
                }
            })
            ->get();
    }

    /**
     * Get subscriptions that want new post notifications.
     */
    public function getNewPostSubscribers(?int $categoryId = null): Collection
    {
        $query = $this->model->query()
            ->confirmed()
            ->active()
            ->where(function (Builder $q) {
                $q->whereJsonContains('preferences->new_posts', true)
                  ->orWhereNull('preferences->new_posts');
            });

        if ($categoryId !== null) {
            $query->whereJsonContains('preferences->categories', $categoryId);
        }

        return $query->get();
    }

    /**
     * Segment by engagement level.
     */
    public function getByEngagement(string $level = 'high'): Collection
    {
        $query = $this->model->query()
            ->confirmed()
            ->active()
            ->join('email_trackings', 'subscriptions.id', '=', 'email_trackings.subscription_id');

        switch ($level) {
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

        return $query->select('subscriptions.*')->distinct()->get();
    }

    /**
     * Get subscriber statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->model->query()->count();
        $confirmed = $this->model->query()->confirmed()->count();
        $active = $this->model->query()->active()->count();
        $pending = $this->model->query()->unconfirmed()->active()->count();
        $unsubscribed = $this->model->query()->whereNotNull('unsubscribed_at')->count();

        $byFrequency = $this->model->query()
            ->select('frequency', DB::raw('count(*) as count'))
            ->groupBy('frequency')
            ->get()
            ->pluck('count', 'frequency')
            ->toArray();

        $todaySubscribers = $this->model->query()
            ->whereDate('created_at', today())
            ->count();

        $thisWeekSubscribers = $this->model->query()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $thisMonthSubscribers = $this->model->query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'active' => $active,
            'pending' => $pending,
            'unsubscribed' => $unsubscribed,
            'by_frequency' => $byFrequency,
            'today' => $todaySubscribers,
            'this_week' => $thisWeekSubscribers,
            'this_month' => $thisMonthSubscribers,
            'confirmation_rate' => $total > 0 ? round(($confirmed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get segment counts for admin dashboard.
     */
    public function getSegmentCounts(): array
    {
        $confirmedActive = $this->model->query()->confirmed()->active()->count();
        
        $dailyDigest = $this->model->query()
            ->confirmed()
            ->active()
            ->frequency(Subscription::FREQUENCY_DAILY)
            ->count();

        $weeklyDigest = $this->model->query()
            ->confirmed()
            ->active()
            ->frequency(Subscription::FREQUENCY_WEEKLY)
            ->count();

        $monthlyDigest = $this->model->query()
            ->confirmed()
            ->active()
            ->frequency(Subscription::FREQUENCY_MONTHLY)
            ->count();

        $instantNotifications = $this->model->query()
            ->confirmed()
            ->active()
            ->frequency(Subscription::FREQUENCY_INSTANT)
            ->count();

        $wantsNewPosts = $this->model->query()
            ->confirmed()
            ->active()
            ->where(function (Builder $q) {
                $q->whereJsonContains('preferences->new_posts', true)
                  ->orWhereNull('preferences->new_posts');
            })
            ->count();

        // Engagement segments
        $highEngagement = $this->getByEngagement('high')->count();
        $mediumEngagement = $this->getByEngagement('medium')->count();
        $lowEngagement = $this->getByEngagement('low')->count();

        return [
            'confirmed_active' => $confirmedActive,
            'daily_digest' => $dailyDigest,
            'weekly_digest' => $weeklyDigest,
            'monthly_digest' => $monthlyDigest,
            'instant_notifications' => $instantNotifications,
            'wants_new_posts' => $wantsNewPosts,
            'high_engagement' => $highEngagement,
            'medium_engagement' => $mediumEngagement,
            'low_engagement' => $lowEngagement,
        ];
    }

    /**
     * Get subscriptions for digest email.
     */
    public function getDigestSubscribers(string $frequency): Collection
    {
        return $this->model->query()
            ->confirmed()
            ->active()
            ->frequency($frequency)
            ->where(function (Builder $q) {
                $q->whereJsonContains('preferences->weekly_digest', true)
                  ->orWhereJsonContains('preferences->daily_digest', true)
                  ->orWhereJsonContains('preferences->monthly_digest', true)
                  ->orWhereNull('preferences');
            })
            ->get();
    }

    /**
     * Check if email is already subscribed (confirmed or not).
     */
    public function isSubscribed(string $email): bool
    {
        return $this->model->query()
            ->where('email', $email)
            ->exists();
    }

    /**
     * Check if email is confirmed and active.
     */
    public function isConfirmedSubscriber(string $email): bool
    {
        return $this->model->query()
            ->confirmed()
            ->active()
            ->where('email', $email)
            ->exists();
    }

    /**
     * Get bounced emails (should not send to these).
     */
    public function getBouncedEmails(): array
    {
        return $this->model->query()
            ->join('email_trackings', 'subscriptions.id', '=', 'email_trackings.subscription_id')
            ->whereNotNull('email_trackings.bounced_at')
            ->where('email_trackings.bounce_type', 'hard')
            ->pluck('subscriptions.email')
            ->toArray();
    }

    /**
     * Get emails that have complained (should not send to these).
     */
    public function getComplainedEmails(): array
    {
        return $this->model->query()
            ->join('email_trackings', 'subscriptions.id', '=', 'email_trackings.subscription_id')
            ->whereNotNull('email_trackings.complained_at')
            ->pluck('subscriptions.email')
            ->toArray();
    }

    /**
     * Get emails to exclude from sending (bounced + complained + unsubscribed).
     */
    public function getExcludedEmails(): array
    {
        return array_unique(array_merge(
            $this->getBouncedEmails(),
            $this->getComplainedEmails(),
            $this->model->query()->whereNotNull('unsubscribed_at')->pluck('email')->toArray()
        ));
    }

    /**
     * Bulk update preferences for multiple subscriptions.
     */
    public function bulkUpdatePreferences(array $subscriptionIds, array $preferences): int
    {
        $count = 0;
        foreach ($subscriptionIds as $id) {
            $subscription = $this->find($id);
            if ($subscription) {
                $currentPrefs = $subscription->preferences ?? [];
                $subscription->update([
                    'preferences' => array_merge($currentPrefs, $preferences),
                ]);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Clean up old unconfirmed subscriptions.
     */
    public function cleanupOldUnconfirmed(int $days = 7): int
    {
        return $this->model->query()
            ->unconfirmed()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Export subscriber data for GDPR compliance.
     */
    public function exportData(string $email): ?array
    {
        $subscription = $this->findByEmail($email);
        
        if (!$subscription) {
            return null;
        }

        return [
            'email' => $subscription->email,
            'subscribed_at' => $subscription->subscribed_at?->toIso8601String(),
            'confirmed_at' => $subscription->confirmed_at?->toIso8601String(),
            'unsubscribed_at' => $subscription->unsubscribed_at?->toIso8601String(),
            'is_confirmed' => $subscription->is_confirmed,
            'is_active' => $subscription->is_active,
            'preferences' => $subscription->preferences,
            'frequency' => $subscription->frequency,
            'ip_address' => $subscription->ip_address,
            'email_history' => $subscription->trackings()->get()->map(function ($tracking) {
                return [
                    'type' => $tracking->email_type,
                    'subject' => $tracking->subject,
                    'sent_at' => $tracking->sent_at?->toIso8601String(),
                    'opened' => $tracking->wasOpened(),
                    'clicked' => $tracking->wasClicked(),
                ];
            }),
        ];
    }

    /**
     * Delete subscriber data for GDPR compliance.
     */
    public function deleteSubscriberData(string $email): bool
    {
        $subscription = $this->findByEmail($email);
        
        if ($subscription) {
            return $subscription->delete();
        }

        return false;
    }
}
