<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventStore extends Model
{
    protected $table = 'event_store';

    protected $fillable = [
        'event_id', 'tenant_id', 'event_type', 'payload',
        'source', 'correlation_id', 'status', 'retry_count', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'json',
            'retry_count' => 'integer',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (EventStore $event) {
            if (empty($event->event_id)) $event->event_id = (string) Str::uuid();
            if (empty($event->correlation_id) && request()->hasHeader('X-Correlation-ID')) {
                $event->correlation_id = request()->header('X-Correlation-ID');
            }
        });
    }
}
