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
        'uuid',
        'name',
        'domain',
        'theme',
        'settings',
        'is_active',
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
        return $this->hasMany(Post::class);
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
