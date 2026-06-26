<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AnalyticsEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'event_type', 'entity_type', 'entity_id', 'user_id',
        'session_id', 'metadata', 'ip_hash', 'user_agent', 'device_type',
        'url', 'referrer', 'country', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeRecent($query, int $minutes = 30)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (AnalyticsEvent $event) {
            if (empty($event->uuid)) $event->uuid = (string) Str::uuid();
        });
    }
}
