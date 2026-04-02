<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Class MediaResource
 *
 * Resource for Media model.
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'original_name' => $this->original_name,
            'file_path' => $this->file_path,
            'url' => $this->url,
            'thumbnail_url' => $this->when($this->isImage(), $this->getThumbnailUrl()),
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->formatFileSize($this->file_size ?? 0),
            'file_hash' => $this->file_hash,
            'collection_name' => $this->collection_name,
            'alt_text' => $this->alt_text,
            'title' => $this->title,
            'caption' => $this->caption,
            'description' => $this->description,
            'width' => $this->width,
            'height' => $this->height,
            'thumbnail_size' => $this->thumbnail_size,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'uploader' => when($this->whenLoaded('uploader'), fn() => new UserResource($this->uploader)),
            'model' => when($this->whenLoaded('model'), fn() => [
                'type' => $this->model_type,
                'id' => $this->model_id,
            ]),
            
            // Meta
            'is_image' => $this->isImage(),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }

    /**
     * Get the media URL.
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }
        return null;
    }

    /**
     * Check if media is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Get thumbnail URL.
     *
     * @param string $size
     * @return string|null
     */
    public function getThumbnailUrl(string $size = 'thumbnail'): ?string
    {
        if (!$this->isImage()) {
            return null;
        }

        // Check metadata for thumbnail URLs
        $thumbnails = $this->metadata['thumbnails'] ?? [];
        if (isset($thumbnails[$size]['url'])) {
            return $thumbnails[$size]['url'];
        }

        return $this->url;
    }

    /**
     * Format file size.
     *
     * @param int|null $bytes
     * @param int $precision
     * @return string
     */
    protected function formatFileSize(?int $bytes, int $precision = 2): string
    {
        if ($bytes === null || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
