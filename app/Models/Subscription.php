<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscription extends Model
{
    protected $fillable = [
        'uuid', 'tenant_id', 'plan_id', 'status',
        'starts_at', 'ends_at', 'trial_ends_at', 'auto_renew',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Subscription $s) {
            if (empty($s->uuid)) $s->uuid = (string) Str::uuid();
        });
    }
}
