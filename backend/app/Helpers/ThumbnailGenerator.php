<?php

namespace App\Helpers;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Class ThumbnailGenerator
 *
 * Generates thumbnails for uploaded images at multiple sizes.
 * 
 * Supported sizes:
 * - thumbnail (150x150)
 * - small (300x300)
 * - medium (600x600)
 * - large (1200x1200)
 */
class ThumbnailGenerator
{
    /**
     * Image manager instance.
     */
    protected ImageManager $manager;

    /**
     * Thumbnail size configurations.
     */
    protected array $sizes;

    /**
     * Storage disk for thumbnails.
     */
    protected string $disk;

    /**
     * Output quality for thumbnails (1-100).
     */
    protected int $quality;

    /**
     * Whether to maintain aspect ratio.
     */
    protected bool $maintainAspectRatio;

    /**
     * Background color for padding (if maintaining aspect ratio).
     */
    protected string $backgroundColor;

    /**
     * Create a new ThumbnailGenerator instance.
     *
     * @param array|null $sizes Size configurations
     * @param string $disk Storage disk
     * @param int $quality Output quality
     * @param bool $maintainAspectRatio Maintain aspect ratio
     * @param string $backgroundColor Background color for padding
     */
    public function __construct(
        ?array $sizes = null,
        string $disk = 'public',
        int $quality = 85,
        bool $maintainAspectRatio = true,
        string $backgroundColor = 'ffffff'
    ) {
        $this->manager = new ImageManager(new Driver());
        $this->sizes = $sizes ?? config('blog.thumbnail_sizes', [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ]);
        $this->disk = $disk;
        $this->quality = $quality;
        $this->maintainAspectRatio = $maintainAspectRatio;
        $this->backgroundColor = $backgroundColor;
    }

    /**
     * Generate all thumbnails for an uploaded image.
     *
     * @param Media $media The media record
     * @param UploadedFile $file The uploaded file
     * @return array Array of generated thumbnail information
     */
    public function generateAll(Media $media, UploadedFile $file): array
    {
        if (!$this->isImage($media)) {
            return [];
        }

        $thumbnails = [];
        $image = $this->manager->read($file->getRealPath());

        foreach ($this->sizes as $name => $config) {
            $thumbnail = $this->generateSingle($image, $config, $media, $name);
            if ($thumbnail) {
                $thumbnails[$name] = $thumbnail;
            }
        }

        return $thumbnails;
    }

    /**
     * Generate a single thumbnail size.
     *
     * @param ImageInterface $image The source image
     * @param array $config Size configuration
     * @param Media $media The media record
     * @param string $sizeName The size name
     * @return array|null Thumbnail information or null on failure
     */
    public function generateSingle(
        ImageInterface $image,
        array $config,
        Media $media,
        string $sizeName
    ): ?array {
        try {
            $width = $config['width'];
            $height = $config['height'];

            // Create thumbnail
            $thumbnail = $this->resizeImage($image, $width, $height);

            // Generate filename
            $thumbnailPath = $this->getThumbnailPath($media, $sizeName);

            // Encode and save thumbnail
            $encoded = $thumbnail->encodeByMediaType($this->quality / 100);
            Storage::disk($this->disk)->put($thumbnailPath, (string) $encoded);

            // Get thumbnail dimensions
            $thumbnailInfo = $this->manager->read(Storage::disk($this->disk)->path($thumbnailPath));
            $actualWidth = $thumbnailInfo->width();
            $actualHeight = $thumbnailInfo->height();

            // Get file size
            $fileSize = Storage::disk($this->disk)->size($thumbnailPath);

            return [
                'size_name' => $sizeName,
                'path' => $thumbnailPath,
                'url' => Storage::disk($this->disk)->url($thumbnailPath),
                'width' => $actualWidth,
                'height' => $actualHeight,
                'file_size' => $fileSize,
                'mime_type' => $media->mime_type,
            ];
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Generate thumbnail for a specific media ID.
     *
     * @param int $mediaId The media ID
     * @param string $sizeName The size name
     * @return string|null The thumbnail URL or null
     */
    public function getUrl(int $mediaId, string $sizeName = 'thumbnail'): ?string
    {
        $media = Media::find($mediaId);
        if (!$media) {
            return null;
        }

        // Check if thumbnail exists in metadata
        $thumbnails = $media->metadata['thumbnails'] ?? [];
        if (isset($thumbnails[$sizeName]['url'])) {
            return $thumbnails[$sizeName]['url'];
        }

        // Check if thumbnail file exists
        $thumbnailPath = $this->getThumbnailPath($media, $sizeName);
        if (Storage::disk($this->disk)->exists($thumbnailPath)) {
            return Storage::disk($this->disk)->url($thumbnailPath);
        }

        // Return original URL if thumbnail doesn't exist
        return $media->url;
    }

    /**
     * Get all thumbnail URLs for a media.
     *
     * @param Media $media The media record
     * @return array Array of thumbnail URLs
     */
    public function getAllUrls(Media $media): array
    {
        if (!$this->isImage($media)) {
            return [];
        }

        $urls = [];
        foreach ($this->sizes as $name => $config) {
            $urls[$name] = $this->getUrl($media->id, $name);
        }

        return $urls;
    }

    /**
     * Delete all thumbnails for a media.
     *
     * @param Media $media The media record
     * @return int Number of deleted thumbnails
     */
    public function deleteAll(Media $media): int
    {
        $count = 0;
        foreach ($this->sizes as $name => $config) {
            $thumbnailPath = $this->getThumbnailPath($media, $name);
            if (Storage::disk($this->disk)->exists($thumbnailPath)) {
                Storage::disk($this->disk)->delete($thumbnailPath);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Delete a specific thumbnail.
     *
     * @param Media $media The media record
     * @param string $sizeName The size name
     * @return bool Whether the thumbnail was deleted
     */
    public function delete(Media $media, string $sizeName): bool
    {
        $thumbnailPath = $this->getThumbnailPath($media, $sizeName);
        if (Storage::disk($this->disk)->exists($thumbnailPath)) {
            return Storage::disk($this->disk)->delete($thumbnailPath);
        }

        return false;
    }

    /**
     * Check if media is an image.
     *
     * @param Media $media The media record
     * @return bool
     */
    protected function isImage(Media $media): bool
    {
        return str_starts_with($media->mime_type, 'image/') 
            && $media->mime_type !== 'image/svg+xml';
    }

    /**
     * Resize image to specified dimensions.
     *
     * @param ImageInterface $image The source image
     * @param int $width Target width
     * @param int $height Target height
     * @return ImageInterface The resized image
     */
    protected function resizeImage(ImageInterface $image, int $width, int $height): ImageInterface
    {
        if ($this->maintainAspectRatio) {
            // Use cover to maintain aspect ratio and fill the dimensions
            return $image->cover($width, $height);
        }

        // Resize to exact dimensions (may distort)
        return $image->scale($width, $height);
    }

    /**
     * Get the thumbnail file path.
     *
     * @param Media $media The media record
     * @param string $sizeName The size name
     * @return string The thumbnail path
     */
    protected function getThumbnailPath(Media $media, string $sizeName): string
    {
        $pathInfo = pathinfo($media->path);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? 'jpg';

        // Store thumbnails in a subdirectory
        return "{$directory}/thumbnails/{$filename}_{$sizeName}.{$extension}";
    }

    /**
     * Get the thumbnail folder path.
     *
     * @param Media $media The media record
     * @return string The thumbnail folder path
     */
    public function getThumbnailFolder(Media $media): string
    {
        $pathInfo = pathinfo($media->path);
        return "{$pathInfo['dirname']}/thumbnails";
    }

    /**
     * Set the quality for generated thumbnails.
     *
     * @param int $quality Quality value (1-100)
     * @return self
     */
    public function setQuality(int $quality): self
    {
        $this->quality = max(1, min(100, $quality));
        return $this;
    }

    /**
     * Set whether to maintain aspect ratio.
     *
     * @param bool $maintain Maintain aspect ratio
     * @return self
     */
    public function setMaintainAspectRatio(bool $maintain): self
    {
        $this->maintainAspectRatio = $maintain;
        return $this;
    }

    /**
     * Add a custom thumbnail size.
     *
     * @param string $name The size name
     * @param int $width The width
     * @param int $height The height
     * @return self
     */
    public function addSize(string $name, int $width, int $height): self
    {
        $this->sizes[$name] = ['width' => $width, 'height' => $height];
        return $this;
    }

    /**
     * Remove a thumbnail size.
     *
     * @param string $name The size name
     * @return self
     */
    public function removeSize(string $name): self
    {
        unset($this->sizes[$name]);
        return $this;
    }

    /**
     * Get all configured sizes.
     *
     * @return array
     */
    public function getSizes(): array
    {
        return $this->sizes;
    }
}
