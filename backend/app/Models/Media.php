<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Class Media
 * 
 * Represents an uploaded media file (image, document, etc.)
 * with support for collections and polymorphic relationships.
 * 
 * @property int $id
 * @property int $uploader_id
 * @property string $filename
 * @property string|null $original_filename
 * @property string $path
 * @property string $disk
 * @property string $mime_type
 * @property int $size
 * @property string|null $alt_text
 * @property string|null $caption
 * @property string|null $title
 * @property array|null $dimensions
 * @property array|null $metadata
 * @property string $collection_name
 * @property int $sort_order
 * @property bool $is_public
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Media extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uploader_id',
        'filename',
        'original_filename',
        'path',
        'disk',
        'mime_type',
        'size',
        'alt_text',
        'caption',
        'title',
        'dimensions',
        'metadata',
        'collection_name',
        'sort_order',
        'is_public',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'dimensions' => 'array',
            'metadata' => 'array',
            'sort_order' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($media) {
            if (empty($media->disk)) {
                $media->disk = 'public';
            }
            if (empty($media->collection_name)) {
                $media->collection_name = 'default';
            }
            if (empty($media->is_public)) {
                $media->is_public = true;
            }
        });

        // Delete file from storage when model is deleted
        static::deleting(function ($media) {
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
        });
    }

    /**
     * Get the user who uploaded the media.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Get the parent model.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL to the media.
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_public) {
            return Storage::disk($this->disk)->url($this->path);
        }
        
        // For private files, you might want to use a signed URL
        return Storage::disk($this->disk)->temporaryUrl(
            $this->path,
            now()->addMinutes(5)
        );
    }

    /**
     * Get the file size in human readable format.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if media is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if media is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if media is a document.
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Get image width.
     */
    public function getWidthAttribute(): ?int
    {
        return $this->dimensions['width'] ?? null;
    }

    /**
     * Get image height.
     */
    public function getHeightAttribute(): ?int
    {
        return $this->dimensions['height'] ?? null;
    }

    /**
     * Get aspect ratio.
     */
    public function getAspectRatioAttribute(): ?float
    {
        if ($this->width && $this->height) {
            return $this->width / $this->height;
        }
        return null;
    }

    /**
     * Scope for images.
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'LIKE', 'image/%');
    }

    /**
     * Scope for videos.
     */
    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'LIKE', 'video/%');
    }

    /**
     * Scope for documents.
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Scope for public media.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for private media.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope for media in a collection.
     */
    public function scopeInCollection($query, string $collectionName)
    {
        return $query->where('collection_name', $collectionName);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Scope for media uploaded by a user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('uploader_id', $userId);
    }

    /**
     * Scope to filter by mime type.
     */
    public function scopeMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
