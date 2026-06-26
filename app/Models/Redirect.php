<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'old_url', 'new_url', 'redirect_type', 'hit_count',
    ];

    protected function casts(): array
    {
        return [
            'redirect_type' => 'string',
            'hit_count' => 'integer',
        ];
    }
}
