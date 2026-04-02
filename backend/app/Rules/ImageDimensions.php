<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Class ImageDimensions
 *
 * Custom validation rule to validate image dimensions and file size.
 *
 * Usage:
 *   new ImageDimensions(minWidth: 800, minHeight: 600)
 *   new ImageDimensions(maxWidth: 4096, maxHeight: 4096, maxSize: 5120) // 5MB
 */
class ImageDimensions implements ValidationRule
{
    /**
     * Minimum width in pixels.
     */
    protected ?int $minWidth;

    /**
     * Minimum height in pixels.
     */
    protected ?int $minHeight;

    /**
     * Maximum width in pixels.
     */
    protected ?int $maxWidth;

    /**
     * Maximum height in pixels.
     */
    protected ?int $maxHeight;

    /**
     * Maximum file size in KB.
     */
    protected ?int $maxSize;

    /**
     * Required aspect ratio (width/height).
     */
    protected ?float $aspectRatio;

    /**
     * Aspect ratio tolerance.
     */
    protected float $aspectRatioTolerance = 0.1;

    /**
     * Custom error messages.
     */
    protected array $messages = [];

    /**
     * Create a new rule instance.
     *
     * @param int|null $minWidth Minimum width in pixels
     * @param int|null $minHeight Minimum height in pixels
     * @param int|null $maxWidth Maximum width in pixels
     * @param int|null $maxHeight Maximum height in pixels
     * @param int|null $maxSize Maximum file size in KB
     */
    public function __construct(
        ?int $minWidth = null,
        ?int $minHeight = null,
        ?int $maxWidth = null,
        ?int $maxHeight = null,
        ?int $maxSize = null
    ) {
        $this->minWidth = $minWidth;
        $this->minHeight = $minHeight;
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->maxSize = $maxSize;
    }

    /**
     * Set required aspect ratio.
     *
     * @param float $ratio Aspect ratio (width/height)
     * @param float $tolerance Tolerance for aspect ratio match
     * @return self
     */
    public function aspectRatio(float $ratio, float $tolerance = 0.1): self
    {
        $this->aspectRatio = $ratio;
        $this->aspectRatioTolerance = $tolerance;
        return $this;
    }

    /**
     * Set custom error messages.
     *
     * @param array $messages
     * @return self
     */
    public function withMessages(array $messages): self
    {
        $this->messages = array_merge($this->messages, $messages);
        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        // Verify it's an image
        if (!str_starts_with($value->getMimeType(), 'image/')) {
            $fail($this->messages['image'] ?? 'The file must be an image.');
            return;
        }

        // Get image dimensions
        $dimensions = getimagesize($value->getRealPath());

        if ($dimensions === false) {
            $fail($this->messages['dimensions'] ?? 'Could not determine image dimensions.');
            return;
        }

        $width = $dimensions[0];
        $height = $dimensions[1];

        // Check minimum width
        if ($this->minWidth !== null && $width < $this->minWidth) {
            $fail($this->messages['min_width'] ?? "The image width must be at least {$this->minWidth} pixels.");
            return;
        }

        // Check minimum height
        if ($this->minHeight !== null && $height < $this->minHeight) {
            $fail($this->messages['min_height'] ?? "The image height must be at least {$this->minHeight} pixels.");
            return;
        }

        // Check maximum width
        if ($this->maxWidth !== null && $width > $this->maxWidth) {
            $fail($this->messages['max_width'] ?? "The image width must not exceed {$this->maxWidth} pixels.");
            return;
        }

        // Check maximum height
        if ($this->maxHeight !== null && $height > $this->maxHeight) {
            $fail($this->messages['max_height'] ?? "The image height must not exceed {$this->maxHeight} pixels.");
            return;
        }

        // Check file size
        if ($this->maxSize !== null) {
            $fileSizeKB = $value->getSize() / 1024;
            if ($fileSizeKB > $this->maxSize) {
                $fail($this->messages['max_size'] ?? "The image size must not exceed {$this->maxSize} KB.");
                return;
            }
        }

        // Check aspect ratio
        if ($this->aspectRatio !== null) {
            $actualRatio = $width / $height;
            $ratioDiff = abs($actualRatio - $this->aspectRatio);

            if ($ratioDiff > $this->aspectRatioTolerance) {
                $fail($this->messages['aspect_ratio'] ?? 
                    "The image aspect ratio must be approximately {$this->aspectRatio}.");
            }
        }
    }
}
