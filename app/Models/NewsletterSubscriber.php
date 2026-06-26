<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email', 'verification_token', 'verified_at', 'subscribed_at', 'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
