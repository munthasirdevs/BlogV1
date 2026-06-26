<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Site extends Model
{
    /** @use HasFactory<\Database\Factories\SiteFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid', 'name', 'domain', 'theme', 'settings', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'json',
            'is_active' => 'boolean',
        ];
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'tenant_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function settings()
    {
        return $this->hasMany(TenantSetting::class, 'tenant_id');
    }

    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public function setSetting(string $key, $value): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Site $site) {
            if (empty($site->uuid)) {
                $site->uuid = (string) Str::uuid();
            }
        });
    }
}
