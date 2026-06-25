<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaFolder extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'parent_id',
        'created_by',
        'updated_by',
    ];

    public function parent()
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(MediaFile::class, 'folder_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MediaFolder $folder) {
            if (empty($folder->uuid)) {
                $folder->uuid = (string) Str::uuid();
            }
        });
    }
}
