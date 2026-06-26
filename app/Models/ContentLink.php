<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentLink extends Model
{
    protected $fillable = [
        'uuid', 'source_type', 'source_id', 'target_type', 'target_id',
        'link_type', 'anchor_text', 'weight_score', 'ai_generated', 'context_snippet',
    ];

    protected function casts(): array
    {
        return [
            'weight_score' => 'decimal:2',
            'ai_generated' => 'boolean',
        ];
    }

    public function source()
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function target()
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('link_type', $type);
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('ai_generated', true);
    }

    public function scopeHighWeight($query, float $min = 0.5)
    {
        return $query->where('weight_score', '>=', $min);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (ContentLink $link) {
            if (empty($link->uuid)) $link->uuid = (string) Str::uuid();
        });
    }
}
