<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use App\Services\AI\AIService;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class MediaProcessingService
{
    public function __construct(
        protected AIService $aiService,
        protected CacheService $cacheService,
        protected ImageManager $imageManager
    ) {}

    public function process(MediaFile $media): void
    {
        try {
            $this->optimizeImage($media);
            $this->generateAiMetadata($media);
            $this->updateSearchIndex($media);

            $media->update(['optimization_status' => 'completed']);
        } catch (\Exception $e) {
            $media->update(['optimization_status' => 'failed']);
            Log::error('Media processing failed', ['media_id' => $media->id, 'error' => $e->getMessage()]);
        }
    }

    public function optimizeImage(MediaFile $media): void
    {
        if (!str_starts_with($media->mime_type, 'image/')) return;

        $fullPath = Storage::disk('public')->path($media->file_path);
        if (!file_exists($fullPath)) return;

        $info = pathinfo($fullPath);
        $dir = $info['dirname'];
        $filename = $info['filename'];

        $webpPath = $dir . '/' . $filename . '.webp';
        try {
            $image = $this->imageManager->read($fullPath);
            $image->toWebp(80)->save($webpPath);

            $sizes = ['thumb' => 150, 'small' => 300, 'medium' => 768];
            foreach ($sizes as $variant => $size) {
                $variantPath = $dir . '/' . $filename . "_{$variant}.webp";
                $variantImage = $this->imageManager->read($fullPath);
                $variantImage->scale(width: $size)->toWebp(80)->save($variantPath);
            }

            $media->update([
                'variants' => [
                    'thumbnail' => Storage::url($dir . '/' . $filename . '_thumb.webp'),
                    'small' => Storage::url($dir . '/' . $filename . '_small.webp'),
                    'medium' => Storage::url($dir . '/' . $filename . '_medium.webp'),
                ],
                'placeholder_blur' => $this->generateLqip($fullPath),
            ]);
        } catch (\Exception $e) {
            Log::warning('Image optimization failed', ['media_id' => $media->id, 'error' => $e->getMessage()]);
        }
    }

    private function generateLqip(string $fullPath): string
    {
        try {
            $image = $this->imageManager->read($fullPath);
            $image->scale(width: 20);
            $encoded = (string) $image->toWebp(30);
            return 'data:image/webp;base64,' . base64_encode($encoded);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function generateAiMetadata(MediaFile $media): void
    {
        try {
            $altText = $this->aiService->generateContent(
                "Generate brief SEO alt text (max 120 chars) for this image: {$media->original_name}. Return only the alt text.",
                'meta_description'
            );
            $tags = $this->aiService->extractKeywords($media->original_name);

            $media->update([
                'alt_text' => $altText ?: $media->alt_text,
                'ai_tags' => $tags,
            ]);
        } catch (\Exception $e) {
            Log::debug('AI metadata generation skipped', ['media_id' => $media->id]);
        }
    }

    public function updateSearchIndex(MediaFile $media): void
    {
        $cacheKey = "media:{$media->id}";
        $this->cacheService->put($cacheKey, [
            'id' => $media->id,
            'name' => $media->original_name,
            'alt_text' => $media->alt_text,
            'caption' => $media->caption,
            'tags' => $media->ai_tags,
            'mime_type' => $media->mime_type,
            'file_size' => $media->file_size,
            'url' => $media->file_url,
        ], 3600);
    }

    public function getStorageAnalytics(): array
    {
        $totalFiles = MediaFile::count();
        $totalSize = MediaFile::sum('file_size');
        $images = MediaFile::where('mime_type', 'like', 'image/%')->count();
        $videos = MediaFile::where('mime_type', 'like', 'video/%')->count();
        $documents = MediaFile::where('mime_type', 'like', 'application/%')->count();
        $audio = MediaFile::where('mime_type', 'like', 'audio/%')->count();
        $optimized = MediaFile::where('optimization_status', 'completed')->count();

        return [
            'total_files' => $totalFiles,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => $totalSize > 0 ? round($totalSize / 1048576, 2) : 0,
            'by_type' => compact('images', 'videos', 'documents', 'audio'),
            'optimized' => $optimized,
            'optimization_rate' => $totalFiles > 0 ? round($optimized / $totalFiles * 100, 1) : 0,
        ];
    }

    public function cleanupOrphaned(): int
    {
        $count = 0;
        $disk = Storage::disk('public');

        MediaFile::onlyTrashed()->chunk(50, function ($mediaFiles) use ($disk, &$count) {
            foreach ($mediaFiles as $media) {
                if ($disk->exists($media->file_path)) {
                    $disk->delete($media->file_path);
                    $count++;
                }
                $media->forceDelete();
            }
        });

        return $count;
    }
}
