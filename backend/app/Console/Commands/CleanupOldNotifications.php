<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Class CleanupOldNotifications
 *
 * Scheduled command to clean up old read notifications.
 * Deletes read notifications older than 30 days.
 */
class CleanupOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup-old
                            {--days=30 : Delete notifications older than this many days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old read notifications to reduce database size';

    /**
     * The notification service instance.
     */
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Starting notification cleanup...");
        $this->info("Will clean up read notifications older than {$days} days.");

        if ($dryRun) {
            $this->warn("DRY RUN - No notifications will be deleted.");
        }

        $cutoffDate = now()->subDays($days);

        // Count notifications that would be deleted
        $countQuery = \DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', $cutoffDate);

        $count = $countQuery->count();

        $this->info("Found {$count} notifications to clean up.");

        if ($count === 0) {
            $this->info("No notifications to clean up.");
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Would delete {$count} notifications.");
            return Command::SUCCESS;
        }

        // Show progress bar
        $this->output->progressStart($count);

        // Delete in batches to avoid memory issues
        $batchSize = 1000;
        $deletedTotal = 0;

        do {
            $deleted = \DB::table('notifications')
                ->whereNotNull('read_at')
                ->where('created_at', '<', $cutoffDate)
                ->limit($batchSize)
                ->delete();

            $deletedTotal += $deleted;
            $this->output->progressAdvance($deleted);
        } while ($deleted > 0);

        $this->output->progressFinish();

        $this->newLine();
        $this->info("Successfully deleted {$deletedTotal} notifications.");

        // Log the cleanup
        \Log::info('Notification cleanup completed', [
            'deleted_count' => $deletedTotal,
            'older_than_days' => $days,
            'cutoff_date' => $cutoffDate->toISOString(),
        ]);

        return Command::SUCCESS;
    }
}
