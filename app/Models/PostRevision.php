<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PostRevision extends Model
{
    protected $fillable = [
        'uuid',
        'post_id',
        'editor_id',
        'revision_number',
        'title_snapshot',
        'excerpt_snapshot',
        'content_snapshot',
        'seo_snapshot',
        'ai_generated',
        'ai_tool_used',
        'change_summary',
    ];

    protected function casts(): array
    {
        return [
            'seo_snapshot' => 'json',
            'ai_generated' => 'boolean',
        ];
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PostRevision $revision) {
            if (empty($revision->uuid)) {
                $revision->uuid = (string) Str::uuid();
            }
        });
    }
}
