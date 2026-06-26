<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SystemLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'tenant_id', 'level', 'channel', 'message',
        'context', 'request_id', 'user_id', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'json',
            'created_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['error', 'critical']);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (SystemLog $log) {
            if (empty($log->uuid)) $log->uuid = (string) Str::uuid();
        });
    }
}
