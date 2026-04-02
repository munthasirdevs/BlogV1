<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Class ImageOptimizer
 *
 * Optimizes uploaded images by:
 * - Auto-orienting (fixing EXIF rotation)
 * - Stripping metadata (privacy)
 * - Optimizing compression
 * - Converting to WebP (optional)
 * - Stripping EXIF data (GPS, camera info, timestamps)
 */
class ImageOptimizer
{
    /**
     * Image manager instance.
     */
    protected ImageManager $manager;

    /**
     * Output quality (1-100).
     */
    protected int $quality;

    /**
     * Whether to strip metadata.
     */
    protected bool $stripMetadata;

    /**
     * Whether to auto-orient images.
     */
    protected bool $autoOrient;

    /**
     * Whether to convert to WebP.
     */
    protected bool $convertToWebP;

    /**
     * Whether to keep orientation EXIF data.
     */
    protected bool $keepOrientation;

    /**
     * Maximum width for optimization.
     */
    protected ?int $maxWidth;

    /**
     * Maximum height for optimization.
     */
    protected ?int $maxHeight;

    /**
     * Create a new ImageOptimizer instance.
     *
     * @param int $quality Output quality (1-100)
     * @param bool $stripMetadata Strip metadata
     * @param bool $autoOrient Auto-orient images
     * @param bool $convertToWebP Convert to WebP
     * @param bool $keepOrientation Keep orientation data
     * @param int|null $maxWidth Maximum width
     * @param int|null $maxHeight Maximum height
     */
    public function __construct(
        int $quality = 85,
        bool $stripMetadata = true,
        bool $autoOrient = true,
        bool $convertToWebP = false,
        bool $keepOrientation = false,
        ?int $maxWidth = null,
        ?int $maxHeight = null
    ) {
        $this->manager = new ImageManager(new Driver());
        $this->quality = $quality;
        $this->stripMetadata = $stripMetadata;
        $this->autoOrient = $autoOrient;
        $this->convertToWebP = $convertToWebP;
        $this->keepOrientation = $keepOrientation;
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
    }

    /**
     * Optimize an uploaded image file.
     *
     * @param UploadedFile $file The uploaded file
     * @param string|null $outputPath Optional output path (defaults to same location)
     * @return array Optimization result with paths and metadata
     */
    public function optimize(UploadedFile $file, ?string $outputPath = null): array
    {
        $mimeType = $file->getMimeType();

        // Only optimize images
        if (!str_starts_with($mimeType, 'image/')) {
            return [
                'success' => false,
                'message' => 'File is not an image',
                'original_path' => $file->getPathname(),
            ];
        }

        // Skip SVG optimization (vector format)
        if ($mimeType === 'image/svg+xml') {
            return [
                'success' => true,
                'message' => 'SVG file - no optimization needed',
                'original_path' => $file->getPathname(),
                'optimized_path' => $file->getPathname(),
                'original_size' => $file->getSize(),
                'optimized_size' => $file->getSize(),
                'savings' => 0,
                'savings_percentage' => 0,
            ];
        }

        try {
            // Read the image
            $image = $this->manager->read($file->getRealPath());

            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            $originalSize = $file->getSize();

            // Auto-orient (fix EXIF rotation)
            if ($this->autoOrient) {
                $image = $this->autoOrientImage($image);
            }

            // Resize if max dimensions are set
            if ($this->maxWidth || $this->maxHeight) {
                $image = $this->resizeIfNeeded($image);
            }

            // Determine output format and path
            $outputPath = $outputPath ?? $file->getPathname();
            $outputMimeType = $this->convertToWebP ? 'image/webp' : $mimeType;

            // Encode the image
            $encoded = $this->encodeImage($image, $outputMimeType);

            // Save the optimized image
            if ($outputPath !== $file->getPathname()) {
                Storage::disk('local')->put($outputPath, (string) $encoded);
            } else {
                file_put_contents($outputPath, (string) $encoded);
            }

            // Get optimized size
            $optimizedSize = $outputPath === $file->getPathname() 
                ? filesize($outputPath) 
                : Storage::disk('local')->size($outputPath);

            // Calculate savings
            $savings = $originalSize - $optimizedSize;
            $savingsPercentage = $originalSize > 0 ? ($savings / $originalSize) * 100 : 0;

            // Get new dimensions
            $newImage = $this->manager->read($outputPath);
            $newWidth = $newImage->width();
            $newHeight = $newImage->height();

            return [
                'success' => true,
                'message' => 'Image optimized successfully',
                'original_path' => $file->getPathname(),
                'optimized_path' => $outputPath,
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'savings' => $savings,
                'savings_percentage' => round($savingsPercentage, 2),
                'original_dimensions' => [
                    'width' => $originalWidth,
                    'height' => $originalHeight,
                ],
                'optimized_dimensions' => [
                    'width' => $newWidth,
                    'height' => $newHeight,
                ],
                'original_mime_type' => $mimeType,
                'optimized_mime_type' => $outputMimeType,
                'metadata_stripped' => $this->stripMetadata,
                'auto_oriented' => $this->autoOrient,
                'converted_to_webp' => $this->convertToWebP,
            ];
        } catch (\Exception $e) {
            report($e);
            return [
                'success' => false,
                'message' => 'Optimization failed: ' . $e->getMessage(),
                'original_path' => $file->getPathname(),
            ];
        }
    }

    /**
     * Optimize image from a file path.
     *
     * @param string $filePath Path to the image file
     * @param string|null $outputPath Optional output path
     * @return array Optimization result
     */
    public function optimizeFromFile(string $filePath, ?string $outputPath = null): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'File not found: ' . $filePath,
            ];
        }

        $file = new UploadedFile(
            $filePath,
            basename($filePath),
            mime_content_type($filePath),
            null,
            true
        );

        return $this->optimize($file, $outputPath);
    }

    /**
     * Optimize image from storage.
     *
     * @param string $path Path in storage disk
     * @param string $disk Storage disk name
     * @return array Optimization result
     */
    public function optimizeFromStorage(string $path, string $disk = 'public'): array
    {
        if (!Storage::disk($disk)->exists($path)) {
            return [
                'success' => false,
                'message' => 'File not found in storage: ' . $path,
            ];
        }

        $filePath = Storage::disk($disk)->path($path);
        return $this->optimizeFromFile($filePath);
    }

    /**
     * Auto-orient image based on EXIF data.
     *
     * @param ImageInterface $image The image
     * @return ImageInterface The oriented image
     */
    protected function autoOrientImage(ImageInterface $image): ImageInterface
    {
        // Intervention Image v3 automatically handles EXIF orientation
        // when loading the image, but we can explicitly orient it
        return $image->orientate();
    }

    /**
     * Resize image if it exceeds maximum dimensions.
     *
     * @param ImageInterface $image The image
     * @return ImageInterface The resized image
     */
    protected function resizeIfNeeded(ImageInterface $image): ImageInterface
    {
        $width = $image->width();
        $height = $image->height();

        $shouldResize = false;
        $newWidth = $width;
        $newHeight = $height;

        if ($this->maxWidth && $width > $this->maxWidth) {
            $newWidth = $this->maxWidth;
            $newHeight = (int) ($height * ($this->maxWidth / $width));
            $shouldResize = true;
        }

        if ($this->maxHeight && $newHeight > $this->maxHeight) {
            $newHeight = $this->maxHeight;
            $newWidth = (int) ($newWidth * ($this->maxHeight / $newHeight));
            $shouldResize = true;
        }

        if ($shouldResize) {
            return $image->scale($newWidth, $newHeight);
        }

        return $image;
    }

    /**
     * Encode image based on MIME type.
     *
     * @param ImageInterface $image The image
     * @param string $mimeType Output MIME type
     * @return \Intervention\Image\Interfaces\EncodedImageInterface The encoded image
     */
    protected function encodeImage(ImageInterface $image, string $mimeType): \Intervention\Image\Interfaces\EncodedImageInterface
    {
        return match ($mimeType) {
            'image/jpeg' => $image->toJpeg($this->quality),
            'image/png' => $image->toPng(),
            'image/webp' => $image->toWebp($this->quality),
            'image/gif' => $image->toGif(),
            default => $image->encodeByMediaType($this->quality / 100),
        };
    }

    /**
     * Strip EXIF data from image.
     * Note: Intervention Image v3 strips metadata by default when encoding.
     *
     * @param ImageInterface $image The image
     * @return ImageInterface The image with stripped metadata
     */
    protected function stripExifData(ImageInterface $image): ImageInterface
    {
        // In Intervention Image v3, metadata is stripped by default
        // when encoding. This method is kept for compatibility.
        return $image;
    }

    /**
     * Get EXIF data from an image file.
     *
     * @param string $filePath Path to the image file
     * @return array EXIF data
     */
    public function getExifData(string $filePath): array
    {
        if (!function_exists('exif_read_data')) {
            return ['error' => 'EXIF extension not available'];
        }

        try {
            $exif = @exif_read_data($filePath);
            if ($exif === false) {
                return ['error' => 'No EXIF data found'];
            }

            // Extract relevant data
            return [
                'make' => $exif['Make'] ?? null,
                'model' => $exif['Model'] ?? null,
                'orientation' => $exif['Orientation'] ?? null,
                'date_time' => $exif['DateTime'] ?? null,
                'date_time_original' => $exif['DateTimeOriginal'] ?? null,
                'gps' => $this->extractGpsData($exif),
                'software' => $exif['Software'] ?? null,
                'artist' => $exif['Artist'] ?? null,
                'copyright' => $exif['Copyright'] ?? null,
                'exposure_time' => $exif['ExposureTime'] ?? null,
                'f_number' => $exif['FNumber'] ?? null,
                'iso' => $exif['ISOSpeedRatingsos'] ?? null,
                'focal_length' => $exif['FocalLength'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Extract GPS data from EXIF.
     *
     * @param array $exif EXIF data
     * @return array|null GPS coordinates or null
     */
    protected function extractGpsData(array $exif): ?array
    {
        if (!isset($exif['GPSLatitude']) || !isset($exif['GPSLongitude'])) {
            return null;
        }

        $lat = $this->convertGpsCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
        $lon = $this->convertGpsCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'W');

        return [
            'latitude' => $lat,
            'longitude' => $lon,
        ];
    }

    /**
     * Convert GPS coordinate from EXIF format to decimal.
     *
     * @param array $coordinate GPS coordinate array
     * @param string $ref Reference (N/S/E/W)
     * @return float Decimal coordinate
     */
    protected function convertGpsCoordinate(array $coordinate, string $ref): float
    {
        $degrees = count($coordinate) > 0 ? $this->gpsFractionToDecimal($coordinate[0]) : 0;
        $minutes = count($coordinate) > 1 ? $this->gpsFractionToDecimal($coordinate[1]) : 0;
        $seconds = count($coordinate) > 2 ? $this->gpsFractionToDecimal($coordinate[2]) : 0;

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if ($ref === 'S' || $ref === 'W') {
            $decimal = -$decimal;
        }

        return $decimal;
    }

    /**
     * Convert GPS fraction to decimal.
     *
     * @param array $fraction GPS fraction array
     * @return float Decimal value
     */
    protected function gpsFractionToDecimal(array $fraction): float
    {
        if (count($fraction) < 2) {
            return 0;
        }
        return $fraction[0] / $fraction[1];
    }

    /**
     * Set the output quality.
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
     * Set whether to strip metadata.
     *
     * @param bool $stripMetadata Strip metadata
     * @return self
     */
    public function setStripMetadata(bool $stripMetadata): self
    {
        $this->stripMetadata = $stripMetadata;
        return $this;
    }

    /**
     * Set whether to auto-orient images.
     *
     * @param bool $autoOrient Auto-orient images
     * @return self
     */
    public function setAutoOrient(bool $autoOrient): self
    {
        $this->autoOrient = $autoOrient;
        return $this;
    }

    /**
     * Set whether to convert to WebP.
     *
     * @param bool $convertToWebP Convert to WebP
     * @return self
     */
    public function setConvertToWebP(bool $convertToWebP): self
    {
        $this->convertToWebP = $convertToWebP;
        return $this;
    }

    /**
     * Set maximum dimensions.
     *
     * @param int|null $maxWidth Maximum width
     * @param int|null $maxHeight Maximum height
     * @return self
     */
    public function setMaxDimensions(?int $maxWidth, ?int $maxHeight): self
    {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        return $this;
    }

    /**
     * Get the current quality setting.
     *
     * @return int
     */
    public function getQuality(): int
    {
        return $this->quality;
    }

    /**
     * Check if a file is a valid image.
     *
     * @param string $filePath Path to the file
     * @return bool
     */
    public function isValidImage(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $mimeType = mime_content_type($filePath);
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Get image dimensions.
     *
     * @param string $filePath Path to the image file
     * @return array|null Dimensions or null
     */
    public function getDimensions(string $filePath): ?array
    {
        if (!$this->isValidImage($filePath)) {
            return null;
        }

        try {
            $image = $this->manager->read($filePath);
            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
