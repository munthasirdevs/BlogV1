<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\ActiveSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class CleanupAnalytics
 *
 * Scheduled job to clean up old analytics data.
 * Runs monthly to delete raw events older than 12 months.
 * Aggregated data is kept indefinitely.
 * 
 * Features:
 * - Deletes raw events older than retention period
 * - Cleans up expired active sessions
 * - Logs cleanup statistics
 * - GDPR compliant data retention
 */
class CleanupAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retention period in months for raw events.
     */
    const RETENTION_MONTHS = 12;

    /**
     * Active session timeout in minutes.
     */
    const SESSION_TIMEOUT_MINUTES = 30;

    /**
     * Number of attempts before failing.
     */
    public int $tries = 1;

    /**
     * Timeout in seconds.
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('Starting analytics cleanup', [
            'started_at' => now()->toIso8601String(),
        ]);

        try {
            // Calculate cutoff date
            $cutoffDate = now()->subMonths(self::RETENTION_MONTHS);

            Log::info('Analytics cleanup cutoff date', [
                'cutoff_date' => $cutoffDate->toDateString(),
                'retention_months' => self::RETENTION_MONTHS,
            ]);

            // Delete old analytics events
            $deletedEvents = $this->cleanupOldEvents($cutoffDate);

            // Clean up expired active sessions
            $deletedSessions = $this->cleanupActiveSessions();

            // Log summary
            Log::info('Analytics cleanup completed', [
                'completed_at' => now()->toIso8601String(),
                'deleted_events' => $deletedEvents,
                'deleted_sessions' => $deletedSessions,
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Analytics cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Clean up old analytics events.
     *
     * @param Carbon $cutoffDate
     * @return int Number of deleted records
     */
    protected function cleanupOldEvents(Carbon $cutoffDate): int
    {
        Log::info('Cleaning up old analytics events', [
            'cutoff_date' => $cutoffDate->toDateString(),
        ]);

        // Count records to be deleted first
        $countToDelete = AnalyticsEvent::where('occurred_at', '<', $cutoffDate)->count();

        Log::info('Found records to delete', [
            'count' => $countToDelete,
        ]);

        if ($countToDelete === 0) {
            return 0;
        }

        // Delete in chunks to avoid locking issues
        $deletedCount = 0;
        $chunkSize = 10000;

        AnalyticsEvent::where('occurred_at', '<', $cutoffDate)
            ->chunk($chunkSize, function ($events) use (&$deletedCount) {
                $count = $events->count();
                $events->each->delete();
                $deletedCount += $count;

                Log::info('Deleted chunk of analytics events', [
                    'chunk_size' => $count,
                    'total_deleted' => $deletedCount,
                ]);
            });

        return $deletedCount;
    }

    /**
     * Clean up expired active sessions.
     *
     * @return int Number of deleted sessions
     */
    protected function cleanupActiveSessions(): int
    {
        Log::info('Cleaning up expired active sessions', [
            'timeout_minutes' => self::SESSION_TIMEOUT_MINUTES,
        ]);

        $deletedCount = ActiveSession::where('last_seen_at', '<', now()->subMinutes(self::SESSION_TIMEOUT_MINUTES))
            ->delete();

        Log::info('Deleted expired active sessions', [
            'count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Analytics cleanup job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optionally notify admins
        // Notification::send(new AnalyticsCleanupFailed($exception));
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['analytics', 'cleanup', 'maintenance'];
    }
}
