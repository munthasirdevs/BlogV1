<?php

namespace App\Services;

use App\Models\Media;
use App\Repositories\MediaRepository;
use App\Helpers\ThumbnailGenerator;
use App\Helpers\ImageOptimizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Class MediaService
 *
 * Service class for media-related operations.
 * Handles file uploads, optimization, thumbnail generation, and cleanup.
 */
class MediaService extends BaseService
{
    /**
     * Thumbnail generator instance.
     */
    protected ?ThumbnailGenerator $thumbnailGenerator = null;

    /**
     * Image optimizer instance.
     */
    protected ?ImageOptimizer $imageOptimizer = null;

    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        $this->repository = new MediaRepository();
    }

    /**
     * Get the repository instance.
     *
     * @return MediaRepository
     */
    public function getRepository(): MediaRepository
    {
        if (!$this->repository instanceof MediaRepository) {
            $this->initializeRepository();
        }
        return $this->repository;
    }

    /**
     * Get thumbnail generator instance.
     *
     * @return ThumbnailGenerator
     */
    public function getThumbnailGenerator(): ThumbnailGenerator
    {
        if (!$this->thumbnailGenerator) {
            $this->thumbnailGenerator = new ThumbnailGenerator();
        }
        return $this->thumbnailGenerator;
    }

    /**
     * Get image optimizer instance.
     *
     * @return ImageOptimizer
     */
    public function getImageOptimizer(): ImageOptimizer
    {
        if (!$this->imageOptimizer) {
            $this->imageOptimizer = new ImageOptimizer(
                quality: config('blog.image_quality', 85),
                stripMetadata: config('blog.strip_metadata', true),
                autoOrient: config('blog.auto_orient', true),
                convertToWebP: config('blog.convert_to_webp', false),
            );
        }
        return $this->imageOptimizer;
    }

    /**
     * Upload a single file.
     *
     * @param UploadedFile $file
     * @param array $data Additional metadata
     * @return Media
     * @throws RuntimeException
     */
    public function uploadFile(UploadedFile $file, array $data = []): Media
    {
        return DB::transaction(function () use ($file, $data) {
            // Validate file is not empty
            if ($file->getError() !== UPLOAD_ERR_OK) {
                throw new RuntimeException('File upload error: ' . $this->getUploadErrorMessage($file->getError()));
            }

            // Generate unique filename
            $originalFilename = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = $this->generateSecureFilename($extension);

            // Determine storage path (organized by date)
            $collectionName = $data['collection_name'] ?? 'default';
            $storagePath = $this->getStoragePath($collectionName);

            // Get the disk to use
            $disk = $data['disk'] ?? config('filesystems.default', 'public');

            // Store the file
            $path = $file->storeAs($storagePath, $filename, $disk);

            if (!$path) {
                throw new RuntimeException('Failed to store file');
            }

            // Get file information
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $fileHash = $this->generateFileHash($file);

            // Check for duplicate uploads
            if (config('blog.prevent_duplicate_uploads', true)) {
                $existing = $this->getRepository()->findByHash($fileHash);
                if ($existing) {
                    // Delete the uploaded file since we have a duplicate
                    Storage::disk($disk)->delete($path);
                    
                    // Return existing media record
                    return $existing;
                }
            }

            // Get image dimensions if it's an image
            $dimensions = null;
            if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
                $dimensions = $this->getImageDimensions($file);
            }

            // Prepare media data
            $mediaData = [
                'uploader_id' => $data['uploader_id'] ?? auth()->id(),
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'path' => $path,
                'disk' => $disk,
                'mime_type' => $mimeType,
                'size' => $fileSize,
                'file_hash' => $fileHash,
                'alt_text' => $data['alt_text'] ?? null,
                'title' => $data['title'] ?? null,
                'caption' => $data['caption'] ?? null,
                'description' => $data['description'] ?? null,
                'dimensions' => $dimensions,
                'collection_name' => $collectionName,
                'is_public' => $data['is_public'] ?? true,
                'metadata' => [],
            ];

            // Create media record
            $media = $this->getRepository()->create($mediaData);

            // Optimize image and generate thumbnails
            if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
                $this->processImage($media, $file, $disk);
            }

            // Log the upload
            Log::info('Media uploaded', [
                'media_id' => $media->id,
                'filename' => $filename,
                'size' => $fileSize,
                'mime_type' => $mimeType,
            ]);

            return $media;
        });
    }

    /**
     * Upload multiple files.
     *
     * @param array $files Array of UploadedFile instances
     * @param array $data Additional metadata
     * @return array ['successful' => Collection, 'failed' => array]
     */
    public function uploadMultiple(array $files, array $data = []): array
    {
        $results = [
            'successful' => collect(),
            'failed' => [],
        ];

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                $results['failed'][] = [
                    'index' => $index,
                    'error' => 'Invalid file object',
                ];
                continue;
            }

            try {
                $media = $this->uploadFile($file, $data);
                $results['successful']->push($media);
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Process an uploaded image (optimize and generate thumbnails).
     *
     * @param Media $media
     * @param UploadedFile $file
     * @param string $disk
     * @return void
     */
    protected function processImage(Media $media, UploadedFile $file, string $disk): void
    {
        $metadata = $media->metadata ?? [];

        // Optimize the image
        $optimizer = $this->getImageOptimizer();
        $optimizationResult = $optimizer->optimize($file);

        if ($optimizationResult['success']) {
            $metadata['optimization'] = [
                'original_size' => $optimizationResult['original_size'],
                'optimized_size' => $optimizationResult['optimized_size'],
                'savings_percentage' => $optimizationResult['savings_percentage'],
                'dimensions' => $optimizationResult['optimized_dimensions'],
            ];
        }

        // Generate thumbnails
        $thumbnailGenerator = $this->getThumbnailGenerator();
        $thumbnails = $thumbnailGenerator->generateAll($media, $file);

        if (!empty($thumbnails)) {
            $metadata['thumbnails'] = $thumbnails;
        }

        // Store EXIF data (stripped of sensitive info)
        if (config('blog.store_exif', false)) {
            $exifData = $optimizer->getExifData($file->getRealPath());
            if (isset($exifData['error'])) {
                unset($exifData['error']);
            }
            // Only keep non-sensitive data
            $metadata['exif'] = array_filter($exifData, function ($key) {
                return in_array($key, ['orientation', 'exposure_time', 'f_number', 'focal_length']);
            }, ARRAY_FILTER_USE_KEY);
        }

        // Update media with metadata
        $media->metadata = $metadata;
        $media->save();
    }

    /**
     * Get paginated media library.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getMediaLibrary(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->getRepository()->getPaginated($filters, $perPage);
    }

    /**
     * Get media by ID.
     *
     * @param int $id
     * @return Media|null
     */
    public function getMedia(int $id): ?Media
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Get media URL.
     *
     * @param int $id
     * @return string|null
     */
    public function getMediaUrl(int $id): ?string
    {
        $media = $this->getMedia($id);
        if (!$media) {
            return null;
        }

        return Storage::disk($media->disk)->url($media->path);
    }

    /**
     * Get thumbnail URL.
     *
     * @param int $id
     * @param string $size
     * @return string|null
     */
    public function getThumbnailUrl(int $id, string $size = 'thumbnail'): ?string
    {
        $media = $this->getMedia($id);
        if (!$media) {
            return null;
        }

        return $this->getThumbnailGenerator()->getUrl($id, $size);
    }

    /**
     * Get all thumbnail URLs.
     *
     * @param Media $media
     * @return array
     */
    public function getAllThumbnailUrls(Media $media): array
    {
        return $this->getThumbnailGenerator()->getAllUrls($media);
    }

    /**
     * Update media metadata.
     *
     * @param int $id
     * @param array $data
     * @return Media
     */
    public function updateMetadata(int $id, array $data): Media
    {
        $allowedFields = ['alt_text', 'title', 'caption', 'description', 'collection_name', 'is_public'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        return $this->getRepository()->update($id, $updateData);
    }

    /**
     * Soft delete media.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteMedia(int $id): ?bool
    {
        $media = $this->getRepository()->find($id);
        if (!$media) {
            return null;
        }

        // Check if media is in use
        if (config('blog.prevent_deletion_if_in_use', false) && $this->getRepository()->isInUse($id)) {
            throw new RuntimeException('Cannot delete media that is currently in use');
        }

        // Soft delete the record (file will be cleaned up by scheduled job)
        return $this->getRepository()->delete($id);
    }

    /**
     * Permanently delete media and file.
     *
     * @param int $id
     * @return bool
     */
    public function forceDeleteMedia(int $id): bool
    {
        $fileInfo = $this->getRepository()->getFilePathBeforeDelete($id);
        if (!$fileInfo) {
            return false;
        }

        // Delete the physical file
        if (Storage::disk($fileInfo['disk'])->exists($fileInfo['path'])) {
            Storage::disk($fileInfo['disk'])->delete($fileInfo['path']);
        }

        // Delete thumbnails
        $media = Media::withTrashed()->find($id);
        if ($media && str_starts_with($media->mime_type, 'image/')) {
            $this->getThumbnailGenerator()->deleteAll($media);
        }

        // Force delete the record
        return Media::withTrashed()->find($id)?->forceDelete() ?? false;
    }

    /**
     * Restore a soft-deleted media.
     *
     * @param int $id
     * @return bool
     */
    public function restoreMedia(int $id): bool
    {
        return $this->getRepository()->restore($id);
    }

    /**
     * Search media.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchMedia(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getRepository()->searchPaginated($query, $perPage);
    }

    /**
     * Get media usage information.
     *
     * @param int $id
     * @return array
     */
    public function getMediaUsage(int $id): array
    {
        $media = $this->getMedia($id);
        if (!$media) {
            return ['in_use' => false, 'usages' => []];
        }

        $usages = $this->getRepository()->findUsage($id);

        return [
            'in_use' => !empty($usages),
            'usages' => $usages,
        ];
    }

    /**
     * Get media statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->getRepository()->getStatistics();
    }

    /**
     * Get user's storage usage.
     *
     * @param int $userId
     * @return array
     */
    public function getUserStorageUsage(int $userId): array
    {
        $bytes = $this->getRepository()->getUserStorageUsage($userId);
        $limit = config('blog.max_user_storage_mb', 100) * 1024 * 1024;

        return [
            'used' => $bytes,
            'used_formatted' => $this->formatFileSize($bytes),
            'limit' => $limit,
            'limit_formatted' => $this->formatFileSize($limit),
            'percentage' => $limit > 0 ? round(($bytes / $limit) * 100, 2) : 0,
            'remaining' => max(0, $limit - $bytes),
            'remaining_formatted' => $this->formatFileSize(max(0, $limit - $bytes)),
        ];
    }

    /**
     * Get total storage usage.
     *
     * @return array
     */
    public function getTotalStorageUsage(): array
    {
        $bytes = $this->getRepository()->getTotalStorageUsage();

        return [
            'total' => $bytes,
            'total_formatted' => $this->formatFileSize($bytes),
            'count' => Media::count(),
        ];
    }

    /**
     * Delete orphaned media (scheduled job).
     *
     * @param int $olderThanHours
     * @return int Number of deleted media
     */
    public function deleteOrphanedMedia(int $olderThanHours = 168): int
    {
        $orphaned = $this->getRepository()->findOrphaned();
        $count = 0;

        foreach ($orphaned as $media) {
            if ($media->deleted_at && $media->deleted_at->diffInHours() >= $olderThanHours) {
                $this->forceDeleteMedia($media->id);
                $count++;
            }
        }

        Log::info("Orphaned media cleanup: deleted {$count} files");

        return $count;
    }

    /**
     * Generate a secure unique filename.
     *
     * @param string $extension
     * @return string
     */
    protected function generateSecureFilename(string $extension): string
    {
        $timestamp = now()->format('YmdHis');
        $random = Str::random(16);
        $sanitizedExtension = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($extension));

        return "{$timestamp}_{$random}.{$sanitizedExtension}";
    }

    /**
     * Get storage path for collection.
     *
     * @param string $collectionName
     * @return string
     */
    protected function getStoragePath(string $collectionName): string
    {
        $datePath = now()->format('Y/m/d');
        return "media/{$collectionName}/{$datePath}";
    }

    /**
     * Generate file hash for deduplication.
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFileHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * Get image dimensions.
     *
     * @param UploadedFile $file
     * @return array|null
     */
    protected function getImageDimensions(UploadedFile $file): ?array
    {
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            return null;
        }

        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
        ];
    }

    /**
     * Get upload error message.
     *
     * @param int $errorCode
     * @return string
     */
    protected function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error',
        };
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

    /**
     * Attach media to a model.
     *
     * @param int $mediaId
     * @param string $modelType
     * @param int $modelId
     * @param string|null $collectionName
     * @return Media
     */
    public function attachToModel(int $mediaId, string $modelType, int $modelId, ?string $collectionName = null): Media
    {
        $updateData = [
            'model_type' => $modelType,
            'model_id' => $modelId,
        ];

        if ($collectionName) {
            $updateData['collection_name'] = $collectionName;
        }

        return $this->getRepository()->update($mediaId, $updateData);
    }

    /**
     * Get recent uploads.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentUploads(int $limit = 10): Collection
    {
        return $this->getRepository()->findRecent($limit);
    }

    /**
     * Get images only.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getImages(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getRepository()->getImagesPaginated($perPage);
    }

    /**
     * Get documents only.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDocuments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getRepository()->getDocumentsPaginated($perPage);
    }

    /**
     * Regenerate thumbnails for a media.
     *
     * @param int $mediaId
     * @return array
     */
    public function regenerateThumbnails(int $mediaId): array
    {
        $media = $this->getMedia($mediaId);
        if (!$media) {
            return ['success' => false, 'message' => 'Media not found'];
        }

        if (!str_starts_with($media->mime_type, 'image/') || $media->mime_type === 'image/svg+xml') {
            return ['success' => false, 'message' => 'Thumbnails can only be generated for raster images'];
        }

        // Get file from storage
        $filePath = Storage::disk($media->disk)->path($media->path);
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Source file not found'];
        }

        // Create temporary uploaded file
        $file = new UploadedFile(
            $filePath,
            $media->filename,
            $media->mime_type,
            null,
            true
        );

        // Delete existing thumbnails
        $this->getThumbnailGenerator()->deleteAll($media);

        // Generate new thumbnails
        $thumbnails = $this->getThumbnailGenerator()->generateAll($media, $file);

        // Update metadata
        $metadata = $media->metadata ?? [];
        $metadata['thumbnails'] = $thumbnails;
        $media->metadata = $metadata;
        $media->save();

        return [
            'success' => true,
            'message' => 'Thumbnails regenerated successfully',
            'thumbnails' => $thumbnails,
        ];
    }
}
