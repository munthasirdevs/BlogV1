<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ScheduledJob extends Model
{
    protected $fillable = [
        'uuid', 'post_id', 'job_type', 'scheduled_at', 'executed_at',
        'status', 'retry_count', 'error_message', 'queue_name',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'executed_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('job_type', $type);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (ScheduledJob $job) {
            if (empty($job->uuid)) $job->uuid = (string) Str::uuid();
        });
    }
}
