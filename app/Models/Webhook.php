<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = ['tenant_id', 'name', 'url', 'events', 'secret', 'is_active', 'timeout', 'retry_count'];

    protected function casts(): array
    {
        return [
            'events' => 'json',
            'is_active' => 'boolean',
            'timeout' => 'integer',
            'retry_count' => 'integer',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }
}
