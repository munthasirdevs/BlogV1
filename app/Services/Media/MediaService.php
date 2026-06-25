<?php

namespace App\Services\Media;

use App\Jobs\OptimizeMediaJob;
use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function upload(UploadedFile $file, ?int $folderId, ?int $userId): MediaFile
    {
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
        if (str_starts_with($mimeType, 'image/')) {
            $imageSize = @getimagesize($file->getRealPath());
            if ($imageSize) {
                [$width, $height] = $imageSize;
            }
        }

        $media = MediaFile::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'folder_id' => $folderId,
            'file_name' => $fileName,
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_url' => $fileUrl,
            'mime_type' => $mimeType,
            'file_extension' => $extension,
            'file_size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'optimization_status' => 'pending',
            'hash_signature' => md5_file($file->getRealPath()),
        ]);

        dispatch(new OptimizeMediaJob($media));

        return $media;
    }

    public function delete(MediaFile $media): bool
    {
        $media->delete();

        dispatch(function () use ($media) {
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
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        })->afterResponse();

        return true;
    }

    public function getUrl(MediaFile $media, string $variant = 'original'): string
    {
        if ($variant === 'original') {
            return $media->file_url;
        }

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
}
