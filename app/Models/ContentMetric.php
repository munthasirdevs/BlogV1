<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentMetric extends Model
{
    protected $fillable = [
        'post_id',
        'daily_views',
        'weekly_views',
        'monthly_views',
        'engagement_score',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
