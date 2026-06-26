<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'uuid', 'title', 'slug', 'content', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seo(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
