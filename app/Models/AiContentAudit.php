<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiContentAudit extends Model
{
    protected $fillable = [
        'post_id', 'readability_score', 'seo_score',
        'keyword_density', 'recommendations',
    ];

    protected function casts(): array
    {
        return [
            'readability_score' => 'decimal:2',
            'seo_score' => 'decimal:2',
            'keyword_density' => 'decimal:2',
            'recommendations' => 'json',
        ];
    }

    public function post(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
