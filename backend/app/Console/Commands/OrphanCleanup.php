<?php

namespace App\Console\Commands;

use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class OrphanCleanup
 *
 * Console command to clean up orphaned media files.
 * 
 * This command:
 * - Finds soft-deleted media older than specified hours
 * - Deletes the physical files from storage
 * - Permanently removes the database records
 * - Cleans up associated thumbnails
 * 
 * Should be scheduled to run weekly.
 */
class OrphanCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanup-orphans 
                            {--hours=168 : Delete orphans older than this many hours (default: 168 hours = 1 week)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned media files that have been soft-deleted';

    /**
     * Media service instance.
     */
    protected MediaService $mediaService;

    /**
     * Create a new command instance.
     *
     * @param MediaService $mediaService
     */
    public function __construct(MediaService $mediaService)
    {
        parent::__construct();
        $this->mediaService = $mediaService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Starting orphan cleanup...");
        $this->info("Looking for orphans older than {$hours} hours (" . round($hours / 24, 1) . " days)");

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No files will be deleted");
        }

        // Get orphaned media
        $orphaned = $this->getOrphanedMedia($hours);
        $count = $orphaned->count();

        if ($count === 0) {
            $this->info("No orphaned media found.");
            return Command::SUCCESS;
        }

        $this->info("Found {$count} orphaned media file(s).");

        // Show summary
        $this->displaySummary($orphaned);

        // Confirm deletion
        if (!$dryRun && !$force) {
            if (!$this->confirm("Do you want to proceed with deleting these {$count} orphaned files?")) {
                $this->info("Cleanup cancelled.");
                return Command::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->info("Dry run complete. {$count} files would be deleted.");
            return Command::SUCCESS;
        }

        // Process each orphan
        $deleted = 0;
        $failed = 0;
        $freedSpace = 0;

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($orphaned as $media) {
            try {
                // Track space to be freed
                $freedSpace += $media->size;

                // Delete the physical file
                if (Storage::disk($media->disk)->exists($media->path)) {
                    Storage::disk($media->disk)->delete($media->path);
                }

                // Delete thumbnails
                if (str_starts_with($media->mime_type, 'image/')) {
                    $this->deleteThumbnails($media);
                }

                // Force delete the record
                $media->forceDelete();
                $deleted++;
            } catch (\Exception $e) {
                $failed++;
                Log::error("Failed to delete orphaned media {$media->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Display results
        $this->info("Cleanup complete!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total orphans found', $count],
                ['Successfully deleted', $deleted],
                ['Failed to delete', $failed],
                ['Space freed', $this->formatFileSize($freedSpace)],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Get orphaned media older than specified hours.
     *
     * @param int $hours
     * @return \Illuminate\Support\Collection
     */
    protected function getOrphanedMedia(int $hours): \Illuminate\Support\Collection
    {
        return \App\Models\Media::onlyTrashed()
            ->where('deleted_at', '<', now()->subHours($hours))
            ->get();
    }

    /**
     * Delete thumbnails for a media file.
     *
     * @param \App\Models\Media $media
     * @return int Number of thumbnails deleted
     */
    protected function deleteThumbnails(\App\Models\Media $media): int
    {
        $deleted = 0;
        $thumbnails = \App\Models\Media::where('model_type', \App\Models\Media::class)
            ->where('model_id', $media->id)
            ->orWhere('filename', 'LIKE', '%' . pathinfo($media->filename, PATHINFO_FILENAME) . '_%')
            ->get();

        foreach ($thumbnails as $thumbnail) {
            if (Storage::disk($thumbnail->disk)->exists($thumbnail->path)) {
                Storage::disk($thumbnail->disk)->delete($thumbnail->path);
            }
            $thumbnail->forceDelete();
            $deleted++;
        }

        // Also check for thumbnails in metadata
        $metadata = $media->metadata ?? [];
        if (isset($metadata['thumbnails'])) {
            foreach ($metadata['thumbnails'] as $thumbnailData) {
                if (isset($thumbnailData['path'])) {
                    if (Storage::disk($media->disk)->exists($thumbnailData['path'])) {
                        Storage::disk($media->disk)->delete($thumbnailData['path']);
                        $deleted++;
                    }
                }
            }
        }

        return $deleted;
    }

    /**
     * Display summary of orphaned media.
     *
     * @param \Illuminate\Support\Collection $orphaned
     * @return void
     */
    protected function displaySummary(\Illuminate\Support\Collection $orphaned): void
    {
        $totalSize = $orphaned->sum('size');
        $byType = $orphaned->groupBy('mime_type')->map(function ($items) {
            return [
                'count' => $items->count(),
                'size' => $items->sum('size'),
            ];
        });

        $this->table(
            ['Type', 'Count', 'Size'],
            $byType->map(function ($data, $type) {
                return [
                    $this->truncateMimeType($type),
                    $data['count'],
                    $this->formatFileSize($data['size']),
                ];
            })->toArray()
        );

        $this->info("Total size to be freed: " . $this->formatFileSize($totalSize));
    }

    /**
     * Truncate MIME type for display.
     *
     * @param string $mimeType
     * @return string
     */
    protected function truncateMimeType(string $mimeType): string
    {
        $parts = explode('/', $mimeType);
        if (count($parts) >= 2) {
            return $parts[1];
        }
        return $mimeType;
    }

    /**
     * Format file size.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
