<?php

namespace App\Services\Media;

use App\Models\FeaturedImage;
use App\Models\MediaFile;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class FeaturedImageService
{
    public function __construct(
        protected ImageManager $imageManager
    ) {}

    public function generateVariants(MediaFile $media, string $modelType, int $modelId): FeaturedImage
    {
        $fullPath = Storage::disk('public')->path($media->file_path);
        $info = pathinfo($fullPath);
        $dir = $info['dirname'];
        $filename = $info['filename'];
        $uuid = (string) Str::uuid();

        $image = $this->imageManager->read($fullPath);

        $sizes = [
            'thumbnail' => [150, 150],
            'small' => [400, 300],
            'medium' => [800, 600],
            'large' => [1200, 628],
        ];

        $paths = [
            'original_path' => $media->file_path,
            'thumbnail_path' => "featured/{$uuid}_thumbnail.webp",
            'medium_path' => "featured/{$uuid}_medium.webp",
            'large_path' => "featured/{$uuid}_large.webp",
            'webp_path' => "featured/{$uuid}.webp",
        ];

        if (!Storage::disk('public')->exists('featured')) {
            Storage::disk('public')->makeDirectory('featured');
        }

        foreach ($sizes as $variant => [$width, $height]) {
            $variantImage = $this->imageManager->read($fullPath);
            $variantImage->cover($width, $height)->toWebp(80)->save(
                Storage::disk('public')->path($paths["{$variant}_path"])
            );
        }

        $convertedImage = $this->imageManager->read($fullPath);
        $convertedImage->toWebp(80)->save(
            Storage::disk('public')->path($paths['webp_path'])
        );

        $blurImage = $this->imageManager->read($fullPath);
        $blurImage->scale(width: 20)->blur(10);
        $blurPath = Storage::disk('public')->path("featured/{$uuid}_blur.webp");
        $blurImage->toWebp(60)->save($blurPath);
        $blurPlaceholder = 'data:image/webp;base64,' . base64_encode(file_get_contents($blurPath));
        @unlink($blurPath);

        $dominantColor = $this->extractDominantColor($fullPath);

        $aspectRatio = $image->width() / max($image->height(), 1);

        return FeaturedImage::create([
            'uuid' => $uuid,
            'media_id' => $media->id,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'title' => $media->title ?? $media->original_name,
            'alt_text' => $media->alt_text,
            'caption' => $media->caption,
            'original_path' => $paths['original_path'],
            'thumbnail_path' => $paths['thumbnail_path'],
            'medium_path' => $paths['medium_path'],
            'large_path' => $paths['large_path'],
            'webp_path' => $paths['webp_path'],
            'blur_placeholder' => $blurPlaceholder,
            'dominant_color' => $dominantColor,
            'width' => $image->width(),
            'height' => $image->height(),
            'aspect_ratio' => round($aspectRatio, 4),
        ]);
    }

    public function getImageHtml(FeaturedImage $image, string $size = 'medium'): string
    {
        $sizePaths = [
            'thumbnail' => $image->thumbnail_path,
            'small' => $image->medium_path,
            'medium' => $image->medium_path,
            'large' => $image->large_path,
        ];

        $fallbackPath = $sizePaths[$size] ?? $image->medium_path;

        $srcset = '';
        foreach (['thumbnail', 'small', 'medium', 'large'] as $s) {
            $path = $sizePaths[$s] ?? null;
            if ($path && Storage::disk('public')->exists($path)) {
                $dimensions = [
                    'thumbnail' => '150w',
                    'small' => '400w',
                    'medium' => '800w',
                    'large' => '1200w',
                ];
                $srcset .= Storage::url($path) . ' ' . $dimensions[$s] . ', ';
            }
        }

        $srcset = rtrim($srcset, ', ');

        $webpUrl = $image->webp_path && Storage::disk('public')->exists($image->webp_path)
            ? Storage::url($image->webp_path)
            : null;

        $fallbackUrl = $fallbackPath && Storage::disk('public')->exists($fallbackPath)
            ? Storage::url($fallbackPath)
            : Storage::url($image->original_path);

        $blurStyle = $image->blur_placeholder ? 'background-image: url(\'' . e($image->blur_placeholder) . '\'); background-size: cover;' : '';
        $alt = e($image->alt_text ?: $image->title ?: 'Featured image');
        $escapedWebpUrl = e($webpUrl ?? '');
        $escapedFallbackUrl = e($fallbackUrl);
        $escapedSrcset = e($srcset);

        $html = '<picture>';
        if ($webpUrl) {
            $html .= '<source srcset="' . $escapedWebpUrl . '" type="image/webp">';
        }
        if ($srcset) {
            $html .= '<source srcset="' . $escapedSrcset . '">';
        }
        $html .= '<img src="' . $escapedFallbackUrl . '" alt="' . $alt . '" loading="lazy" style="' . $blurStyle . '">';
        $html .= '</picture>';

        return $html;
    }

    public function generateOgImage(Post $post): string
    {
        $featuredImage = FeaturedImage::where('model_type', Post::class)
            ->where('model_id', $post->id)
            ->latest()
            ->first();

        if (!$featuredImage || !$featuredImage->large_path || !Storage::disk('public')->exists($featuredImage->large_path)) {
            return '';
        }

        return Storage::url($featuredImage->large_path);
    }

    public function delete(FeaturedImage $featuredImage): void
    {
        $paths = [
            $featuredImage->thumbnail_path,
            $featuredImage->medium_path,
            $featuredImage->large_path,
            $featuredImage->webp_path,
        ];

        $disk = Storage::disk('public');

        foreach ($paths as $path) {
            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $featuredImage->delete();
    }

    protected function extractDominantColor(string $imagePath): string
    {
        try {
            $image = $this->imageManager->read($imagePath);
            $image->resize(1, 1);
            $pixel = $image->pickColor(0, 0);
            return sprintf('#%02x%02x%02x', $pixel[0], $pixel[1], $pixel[2]);
        } catch (\Exception $e) {
            return '#808080';
        }
    }
}
