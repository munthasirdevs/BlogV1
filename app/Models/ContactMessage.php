<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }
}
