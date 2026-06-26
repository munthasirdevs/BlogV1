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
        'ai_prompt',
        'change_summary',
        'diff_hash',
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

    public function isDifferentFrom(PostRevision $other): bool
    {
        return md5($this->title_snapshot . $this->content_snapshot . $this->excerpt_snapshot)
            !== md5($other->title_snapshot . $other->content_snapshot . $other->excerpt_snapshot);
    }

    public static function computeDiffHash(string $title, ?string $content, ?string $excerpt): string
    {
        return md5($title . ($content ?? '') . ($excerpt ?? ''));
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('ai_generated', true);
    }

    public function scopeByEditor($query, int $editorId)
    {
        return $query->where('editor_id', $editorId);
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
