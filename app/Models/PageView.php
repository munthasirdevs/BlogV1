<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $fillable = [
        'page_url', 'ip_hash', 'country', 'device_type', 'browser', 'visited_at',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }
}
