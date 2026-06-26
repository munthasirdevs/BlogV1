<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use App\Services\Media\MediaProcessingService;
use Illuminate\Console\Command;

class ProcessPendingImages extends Command
{
    protected $signature = 'images:process-pending';
    protected $description = 'Process pending image conversions (WebP, responsive, LQIP)';

    public function handle(MediaProcessingService $processingService): int
    {
        $pending = MediaFile::where('optimization_status', 'pending')
            ->where('mime_type', 'like', 'image/%')
            ->whereNotIn('mime_type', ['image/svg+xml', 'image/gif'])
            ->limit(10)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending images to process.');
            return Command::SUCCESS;
        }

        foreach ($pending as $media) {
            $processingService->process($media);
            $this->line("Processed media ID: {$media->id}");
        }

        $this->info("Processed {$pending->count()} image(s).");
        return Command::SUCCESS;
    }
}
