<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\InteractsWithMedia;

class MediaFile extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFileFactory> */
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'uuid',
        'user_id',
        'folder_id',
        'file_name',
        'original_name',
        'file_path',
        'file_url',
        'mime_type',
        'file_extension',
        'file_size',
        'width',
        'height',
        'duration',
        'alt_text',
        'caption',
        'title',
        'description',
        'is_featured',
        'optimization_status',
        'ai_tags',
        'hash_signature',
        'disk',
    ];

    protected function casts(): array
    {
        return [
            'ai_tags' => 'json',
            'is_featured' => 'boolean',
        ];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function folder()
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function scopeImages($query)
    {
        return $query->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/bmp']);
    }

    public function scopeVideos($query)
    {
        return $query->whereIn('mime_type', ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo']);
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    public function scopeAudio($query)
    {
        return $query->whereIn('mime_type', ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/aac']);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MediaFile $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }
}
