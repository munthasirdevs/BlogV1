<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageRecord extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'type', 'quantity', 'cost_estimate', 'metadata', 'created_at'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost_estimate' => 'decimal:4',
            'metadata' => 'json',
            'created_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForPeriod($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
