<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Repositories\SubscriptionRepository;
use App\Jobs\SendConfirmationEmail;
use App\Jobs\SendWelcomeEmail;
use App\Jobs\SendUnsubscribeConfirmationEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class SubscriptionService
 *
 * Service for managing newsletter subscriptions with double opt-in,
 * preference management, and email tracking.
 */
class SubscriptionService extends BaseService
{
    /**
     * The subscription repository instance.
     *
     * @var SubscriptionRepository
     */
    protected $repository;

    /**
     * Token expiration time in hours.
     */
    protected int $tokenExpirationHours = 24;

    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        $this->repository = new SubscriptionRepository();
    }

    /**
     * Subscribe a new email address (public signup).
     * Creates subscription and sends confirmation email.
     */
    public function subscribe(string $email, array $preferences = [], ?int $userId = null): Subscription
    {
        return DB::transaction(function () use ($email, $preferences, $userId) {
            // Check if already subscribed
            $existing = $this->repository->findByEmail($email);

            if ($existing) {
                // If already confirmed and active, return existing
                if ($existing->isConfirmed() && $existing->isActive()) {
                    return $existing;
                }

                // Resubscribe if was unsubscribed
                if (!$existing->isActive()) {
                    $existing->resubscribe();
                }

                // Regenerate token for confirmation
                $existing->regenerateToken();
                
                // Update preferences if provided
                if (!empty($preferences)) {
                    $currentPrefs = $existing->preferences ?? [];
                    $existing->update([
                        'preferences' => array_merge($currentPrefs, $preferences),
                    ]);
                }

                $subscription = $existing;
            } else {
                // Create new subscription
                $subscription = Subscription::subscribe($email, $userId, $preferences);
            }

            // Queue confirmation email
            $this->queueConfirmationEmail($subscription);

            Log::info('Subscription created', [
                'email' => $email,
                'subscription_id' => $subscription->id,
                'is_new' => !$existing,
            ]);

            return $subscription;
        });
    }

    /**
     * Confirm a subscription using token.
     */
    public function confirmSubscription(string $token): ?Subscription
    {
        $subscription = $this->repository->findUnconfirmedByToken($token);

        if (!$subscription) {
            // Check if token is expired or already confirmed
            $confirmedSubscription = $this->repository->findByToken($token);
            if ($confirmedSubscription && $confirmedSubscription->isConfirmed()) {
                Log::info('Already confirmed subscription', ['email' => $confirmedSubscription->email]);
                return $confirmedSubscription;
            }
            
            Log::warning('Invalid confirmation token', ['token' => Str::limit($token, 8)]);
            return null;
        }

        // Check token expiration
        if ($subscription->created_at < now()->subHours($this->tokenExpirationHours)) {
            // Regenerate token and send new confirmation email
            $subscription->regenerateToken();
            $this->queueConfirmationEmail($subscription);
            
            Log::info('Expired token, new email sent', ['email' => $subscription->email]);
            return null;
        }

        // Confirm the subscription
        $subscription->confirm();

        // Queue welcome email
        $this->queueWelcomeEmail($subscription);

        // Track confirmation
        EmailTracking::create([
            'subscription_id' => $subscription->id,
            'email_type' => EmailTracking::TYPE_CONFIRMATION,
            'subject' => 'Subscription Confirmed',
            'sent_at' => now(),
            'delivered_at' => now(),
            'metadata' => ['action' => 'confirmed'],
        ]);

        Log::info('Subscription confirmed', [
            'email' => $subscription->email,
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    /**
     * Unsubscribe an email address.
     */
    public function unsubscribe(string $email): bool
    {
        return DB::transaction(function () use ($email) {
            $subscription = $this->repository->findByEmail($email);

            if (!$subscription) {
                Log::info('Unsubscribe requested for non-existent email', ['email' => $email]);
                return false;
            }

            $wasActive = $subscription->isActive();
            $subscription->unsubscribe();

            // Track unsubscription
            EmailTracking::create([
                'subscription_id' => $subscription->id,
                'email_type' => EmailTracking::TYPE_UNSUBSCRIBE_CONFIRM,
                'subject' => 'Unsubscribed',
                'sent_at' => now(),
                'metadata' => ['action' => 'unsubscribed'],
            ]);

            // Queue unsubscribe confirmation email
            if ($wasActive) {
                $this->queueUnsubscribeConfirmationEmail($subscription);
            }

            Log::info('Subscription unsubscribed', [
                'email' => $subscription->email,
                'subscription_id' => $subscription->id,
            ]);

            return true;
        });
    }

    /**
     * Unsubscribe using token (no auth required).
     */
    public function unsubscribeByToken(string $token): bool
    {
        $subscription = $this->repository->findByToken($token);

        if (!$subscription) {
            return false;
        }

        return $this->unsubscribe($subscription->email);
    }

    /**
     * Update subscription preferences.
     */
    public function updatePreferences(int $subscriptionId, array $preferences): ?Subscription
    {
        $subscription = $this->repository->find($subscriptionId);

        if (!$subscription) {
            return null;
        }

        $currentPrefs = $subscription->preferences ?? [];
        $updatedPrefs = array_merge($currentPrefs, $preferences);

        // Validate preferences
        $validPreferences = $this->validatePreferences($updatedPrefs);

        $subscription->update([
            'preferences' => $validPreferences,
        ]);

        Log::info('Preferences updated', [
            'subscription_id' => $subscriptionId,
            'preferences' => $validPreferences,
        ]);

        return $subscription;
    }

    /**
     * Update preferences by email.
     */
    public function updatePreferencesByEmail(string $email, array $preferences): ?Subscription
    {
        $subscription = $this->repository->findByEmail($email);

        if (!$subscription) {
            return null;
        }

        return $this->updatePreferences($subscription->id, $preferences);
    }

    /**
     * Validate and sanitize preferences.
     */
    protected function validatePreferences(array $preferences): array
    {
        $valid = [];

        // Boolean preferences
        $booleanPrefs = ['new_posts', 'weekly_digest', 'daily_digest', 'monthly_digest'];
        foreach ($booleanPrefs as $pref) {
            if (isset($preferences[$pref])) {
                $valid[$pref] = filter_var($preferences[$pref], FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Frequency preference
        if (isset($preferences['frequency'])) {
            $validFrequencies = [
                Subscription::FREQUENCY_INSTANT,
                Subscription::FREQUENCY_DAILY,
                Subscription::FREQUENCY_WEEKLY,
                Subscription::FREQUENCY_MONTHLY,
            ];
            if (in_array($preferences['frequency'], $validFrequencies)) {
                $valid['frequency'] = $preferences['frequency'];
            }
        }

        // Categories (array of integers)
        if (isset($preferences['categories']) && is_array($preferences['categories'])) {
            $valid['categories'] = array_map('intval', $preferences['categories']);
        }

        // Content types (array of strings)
        if (isset($preferences['content_types']) && is_array($preferences['content_types'])) {
            $validContentTypes = ['articles', 'tutorials', 'news', 'updates'];
            $valid['content_types'] = array_filter(
                $preferences['content_types'],
                fn($type) => in_array($type, $validContentTypes)
            );
        }

        return $valid;
    }

    /**
     * Get subscription by ID.
     */
    public function getSubscription(int $id): ?Subscription
    {
        return $this->repository->find($id);
    }

    /**
     * Get subscription by email.
     */
    public function getSubscriptionByEmail(string $email): ?Subscription
    {
        return $this->repository->findByEmail($email);
    }

    /**
     * Get all subscriptions (admin).
     */
    public function getSubscriptions(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Get confirmed active subscriptions.
     */
    public function getConfirmedActiveSubscriptions(int $perPage = 15)
    {
        return $this->repository->getConfirmedActivePaginated($perPage);
    }

    /**
     * Get subscriber statistics.
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Get segment counts.
     */
    public function getSegmentCounts(): array
    {
        return $this->repository->getSegmentCounts();
    }

    /**
     * Search subscriptions.
     */
    public function searchSubscriptions(string $query, int $perPage = 15)
    {
        return $this->repository->searchByEmail($query, $perPage);
    }

    /**
     * Delete a subscription.
     */
    public function deleteSubscription(int $id): bool
    {
        $subscription = $this->repository->find($id);

        if (!$subscription) {
            return false;
        }

        Log::info('Subscription deleted', [
            'subscription_id' => $id,
            'email' => $subscription->email,
        ]);

        return $subscription->delete();
    }

    /**
     * Queue confirmation email.
     */
    protected function queueConfirmationEmail(Subscription $subscription): void
    {
        SendConfirmationEmail::dispatch($subscription);
    }

    /**
     * Queue welcome email.
     */
    protected function queueWelcomeEmail(Subscription $subscription): void
    {
        SendWelcomeEmail::dispatch($subscription);
    }

    /**
     * Queue unsubscribe confirmation email.
     */
    protected function queueUnsubscribeConfirmationEmail(Subscription $subscription): void
    {
        SendUnsubscribeConfirmationEmail::dispatch($subscription);
    }

    /**
     * Get subscriptions for new post notification.
     */
    public function getNewPostSubscribers(?int $categoryId = null): array
    {
        $subscribers = $this->repository->getNewPostSubscribers($categoryId);
        
        // Exclude bounced and complained emails
        $excluded = $this->repository->getExcludedEmails();
        
        return $subscribers->filter(function ($s) use ($excluded) {
            return !in_array($s->email, $excluded);
        })->values()->all();
    }

    /**
     * Get subscriptions for digest.
     */
    public function getDigestSubscribers(string $frequency): array
    {
        $subscribers = $this->repository->getDigestSubscribers($frequency);
        
        // Exclude bounced and complained emails
        $excluded = $this->repository->getExcludedEmails();
        
        return $subscribers->filter(function ($s) use ($excluded) {
            return !in_array($s->email, $excluded);
        })->values()->all();
    }

    /**
     * Export subscriber data (GDPR).
     */
    public function exportSubscriberData(string $email): ?array
    {
        return $this->repository->exportData($email);
    }

    /**
     * Delete subscriber data (GDPR).
     */
    public function deleteSubscriberData(string $email): bool
    {
        return $this->repository->deleteSubscriberData($email);
    }

    /**
     * Clean up old unconfirmed subscriptions.
     */
    public function cleanupOldUnconfirmed(): int
    {
        return $this->repository->cleanupOldUnconfirmed(7);
    }

    /**
     * Resend confirmation email.
     */
    public function resendConfirmation(string $email): bool
    {
        $subscription = $this->repository->findByEmail($email);

        if (!$subscription || $subscription->isConfirmed()) {
            return false;
        }

        // Regenerate token
        $subscription->regenerateToken();
        
        // Queue new confirmation email
        $this->queueConfirmationEmail($subscription);

        return true;
    }

    /**
     * Check if email can subscribe (not bounced/complained).
     */
    public function canSubscribe(string $email): bool
    {
        // Check if email has hard bounced
        $hasHardBounce = EmailTracking::whereHas('subscription', function ($q) use ($email) {
            $q->where('email', $email);
        })
        ->where('bounce_type', EmailTracking::BOUNCE_HARD)
        ->exists();

        if ($hasHardBounce) {
            return false;
        }

        // Check if email has complained
        $hasComplaint = EmailTracking::whereHas('subscription', function ($q) use ($email) {
            $q->where('email', $email);
        })
        ->whereNotNull('complained_at')
        ->exists();

        if ($hasComplaint) {
            return false;
        }

        return true;
    }
}
