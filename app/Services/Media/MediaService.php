<?php

namespace App\Services\Media;

use App\Jobs\OptimizeMediaJob;
use App\Models\MediaFile;
use App\Services\AI\AIService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class MediaService
{
    public function __construct(
        protected AIService $aiService
    ) {}

    public function upload(UploadedFile $file, ?int $folderId, ?int $userId): MediaFile
    {
        $hash = md5_file($file->getRealPath());
        $existing = MediaFile::where('hash_signature', $hash)->first();
        if ($existing) {
            throw new \RuntimeException('Duplicate file detected: ' . $existing->original_name);
        }

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        $fileName = $this->generateFilename($file);
        $storagePath = $this->getStoragePath($mimeType);
        $filePath = $storagePath . '/' . $fileName;

        $file->storeAs($storagePath, $fileName, 'public');
        $fileUrl = Storage::url($filePath);

        $width = null;
        $height = null;
        $duration = null;

        if (str_starts_with($mimeType, 'image/')) {
            $imageSize = @getimagesize($file->getRealPath());
            if ($imageSize) {
                [$width, $height] = $imageSize;
            }
        }

        if (str_starts_with($mimeType, 'video/')) {
            $duration = $this->extractVideoDuration($file->getRealPath());
        }

        $altText = null;
        if (str_starts_with($mimeType, 'image/') && $this->aiService) {
            try {
                $altText = $this->aiService->generateContent(
                    "Generate a concise, SEO-friendly alt text (max 125 chars) for this image description based on its filename: {$originalName}. Return only the alt text.",
                    'meta_description'
                );
                $altText = trim(mb_substr($altText, 0, 125));
            } catch (\Exception $e) {
                Log::debug('AI alt-text generation failed', ['error' => $e->getMessage()]);
            }
        }

        $disk = config('filesystems.default', 'public');

        $media = MediaFile::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'folder_id' => $folderId,
            'disk' => $disk,
            'file_name' => $fileName,
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_url' => $fileUrl,
            'mime_type' => $mimeType,
            'file_extension' => $extension,
            'file_size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            'alt_text' => $altText,
            'optimization_status' => 'pending',
            'hash_signature' => $hash,
        ]);

        dispatch(new OptimizeMediaJob($media));

        return $media;
    }

    public function uploadZip(\Illuminate\Http\UploadedFile $zip, ?int $folderId, ?int $userId): array
    {
        $results = ['uploaded' => 0, 'skipped' => 0, 'errors' => []];
        $zipPath = $zip->storeAs('temp', Str::uuid() . '.zip', 'local');
        $fullPath = Storage::disk('local')->path($zipPath);

        $archive = new ZipArchive();
        if ($archive->open($fullPath) !== true) {
            throw new \RuntimeException('Cannot open ZIP file.');
        }

        for ($i = 0; $i < $archive->numFiles; $i++) {
            $filename = $archive->getNameIndex($i);
            if ($archive->isDir($i)) continue;

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg','mp4','pdf','docx','mp3','wav'])) continue;

            $tempPath = sys_get_temp_dir() . '/' . Str::uuid() . '.' . $ext;
            copy('zip://' . $fullPath . '#' . $filename, $tempPath);

            try {
                $uploaded = new \Illuminate\Http\UploadedFile(
                    $tempPath, basename($filename),
                    mime_content_type($tempPath), null, true
                );
                $this->upload($uploaded, $folderId, $userId);
                $results['uploaded']++;
            } catch (\RuntimeException $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $results['skipped']++;
                } else {
                    $results['errors'][] = $filename . ': ' . $e->getMessage();
                }
            } finally {
                @unlink($tempPath);
            }
        }

        $archive->close();
        Storage::disk('local')->delete($zipPath);

        return $results;
    }

    public function delete(MediaFile $media): bool
    {
        $media->delete();
        $this->deleteMediaFiles($media);
        return true;
    }

    public function generateAiMetadata(MediaFile $media): array
    {
        $altText = $this->aiService->generateContent(
            "Generate SEO alt text (max 125 chars) for this image: {$media->original_name}.", 'meta_description'
        );
        $caption = $this->aiService->generateContent(
            "Write a brief caption for this image: {$media->original_name}.", 'article'
        );
        $tags = $this->aiService->extractKeywords($media->original_name);

        $media->update([
            'alt_text' => $altText ?: $media->alt_text,
            'caption' => $caption ?: $media->caption,
            'ai_tags' => $tags,
        ]);

        return ['alt_text' => $media->alt_text, 'caption' => $media->caption, 'tags' => $tags];
    }

    private function deleteMediaFiles(MediaFile $media): void
    {
        try {
            $disk = Storage::disk('public');
            $pathsToDelete = [$media->file_path];

            $info = pathinfo($media->file_path);
            $dir = $info['dirname'];
            $filename = $info['filename'];

            foreach (['thumb', 'small', 'medium'] as $variant) {
                $pathsToDelete[] = $dir . '/' . $filename . "_{$variant}.webp";
            }
            $pathsToDelete[] = $dir . '/' . $filename . '.webp';

            foreach ($pathsToDelete as $path) {
                if ($disk->exists($path)) $disk->delete($path);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete media files', ['media_id' => $media->id, 'error' => $e->getMessage()]);
        }
    }

    public function getUrl(MediaFile $media, string $variant = 'original'): string
    {
        if ($variant === 'original') return $media->file_url;
        $info = pathinfo($media->file_path);
        $variantPath = $info['dirname'] . '/' . $info['filename'] . "_{$variant}.webp";
        if (Storage::disk('public')->exists($variantPath)) {
            return Storage::url($variantPath);
        }
        return $media->file_url;
    }

    private function generateFilename(UploadedFile $file): string
    {
        return Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
    }

    private function getStoragePath(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'images',
            str_starts_with($mimeType, 'video/') => 'videos',
            str_starts_with($mimeType, 'audio/') => 'audio',
            default => 'documents',
        };
    }

    private function extractVideoDuration(string $path): ?int
    {
        try {
            $ffprobe = \FFMpeg\FFProbe::create();
            return (int) $ffprobe->streams($path)->first()->get('duration');
        } catch (\Exception $e) {
            return null;
        }
    }
}
