<?php

namespace App\Jobs;

use App\Models\MediaFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class OptimizeMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public MediaFile $media
    ) {}

    public function handle(ImageManager $imageManager): void
    {
        $path = $this->media->file_path;

        if (!Storage::disk('public')->exists($path)) {
            return;
        }

        $fullPath = Storage::disk('public')->path($path);
        $info = pathinfo($fullPath);
        $dir = $info['dirname'];
        $filename = $info['filename'];

        if (!str_starts_with($this->media->mime_type, 'image/')) {
            $this->media->update(['optimization_status' => 'completed']);
            return;
        }

        try {
            $image = $imageManager->read($fullPath);

            $webpPath = $dir . '/' . $filename . '.webp';
            $image->toWebp(80)->save($webpPath);

            $sizes = [
                'thumb' => 150,
                'small' => 300,
                'medium' => 768,
            ];

            foreach ($sizes as $variant => $size) {
                $variantPath = $dir . '/' . $filename . "_{$variant}.webp";
                $variantImage = $imageManager->read($fullPath);
                $variantImage->scale(width: $size, height: $size)->toWebp(80)->save($variantPath);
            }

            $this->media->update(['optimization_status' => 'completed']);
        } catch (\Exception $e) {
            $this->media->update(['optimization_status' => 'failed']);
        }
    }
}
