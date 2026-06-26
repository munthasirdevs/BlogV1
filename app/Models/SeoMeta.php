<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'seoable_type', 'seoable_id',
        'meta_title', 'meta_description', 'meta_keywords',
        'canonical_url', 'robots_directive',
        'og_title', 'og_description', 'og_image',
        'twitter_title', 'twitter_description',
        'schema_type',
        'focus_keyword', 'secondary_keywords',
        'readability_score', 'keyword_density',
        'internal_links_count', 'external_links_count',
        'last_optimized_at',
    ];

    protected function casts(): array
    {
        return [
            'secondary_keywords' => 'json',
            'readability_score' => 'decimal:2',
            'keyword_density' => 'decimal:2',
            'internal_links_count' => 'integer',
            'external_links_count' => 'integer',
            'last_optimized_at' => 'datetime',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('seoable_type', $type);
    }

    public function scopeWithKeyword($query, string $keyword)
    {
        return $query->where('focus_keyword', $keyword);
    }

    public function scopeOptimized($query)
    {
        return $query->whereNotNull('last_optimized_at');
    }

    public function scopeNeedsOptimization($query, int $minScore = 70)
    {
        return $query->where(function ($q) use ($minScore) {
            $q->whereNull('last_optimized_at')
              ->orWhere('readability_score', '<', $minScore)
              ->orWhereNull('focus_keyword');
        });
    }
}
