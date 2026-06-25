<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FeaturedImage extends Model
{
    protected $fillable = [
        'uuid',
        'media_id',
        'model_type',
        'model_id',
        'title',
        'alt_text',
        'caption',
        'original_path',
        'thumbnail_path',
        'medium_path',
        'large_path',
        'webp_path',
        'blur_placeholder',
        'dominant_color',
        'width',
        'height',
        'aspect_ratio',
        'ai_generated',
        'seo_score',
    ];

    protected function casts(): array
    {
        return [
            'ai_generated' => 'boolean',
            'seo_score' => 'decimal:2',
        ];
    }

    public function media()
    {
        return $this->belongsTo(MediaFile::class, 'media_id');
    }

    public function model()
    {
        return $this->morphTo();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (FeaturedImage $featuredImage) {
            if (empty($featuredImage->uuid)) {
                $featuredImage->uuid = (string) Str::uuid();
            }
        });
    }
}
