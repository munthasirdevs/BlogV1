<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'uuid', 'tenant_id', 'subscription_id', 'amount', 'currency',
        'status', 'due_date', 'paid_at', 'invoice_items',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'invoice_items' => 'json',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Invoice $i) {
            if (empty($i->uuid)) $i->uuid = (string) Str::uuid();
        });
    }
}
