<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = [
        'slug', 'name', 'version', 'author', 'description',
        'permissions_required', 'event_subscriptions', 'provider_class',
        'status', 'is_tenant_aware', 'min_core_version',
    ];

    protected function casts(): array
    {
        return [
            'permissions_required' => 'json',
            'event_subscriptions' => 'json',
            'is_tenant_aware' => 'boolean',
        ];
    }

    public function versions()
    {
        return $this->hasMany(PluginVersion::class);
    }

    public function settings()
    {
        return $this->hasMany(PluginSetting::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Site::class, 'tenant_plugins')->withPivot('is_enabled');
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enabled');
    }

    public function isEnabled(): bool
    {
        return $this->status === 'enabled';
    }
}
