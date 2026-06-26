<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'type', 'database', 'email', 'priority'];

    protected function casts(): array
    {
        return [
            'database' => 'boolean',
            'email' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEnabledFor($query, string $type, string $channel = 'database')
    {
        return $query->where('type', $type)->where($channel, true);
    }
}
