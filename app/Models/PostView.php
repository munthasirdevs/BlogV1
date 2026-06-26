<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostView extends Model
{
    protected $fillable = [
        'post_id', 'ip_hash', 'country', 'device_type', 'visited_at',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public function post(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
