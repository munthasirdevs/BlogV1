<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'post_id', 'parent_id', 'user_id', 'guest_name', 'guest_email',
        'body', 'status', 'approved_by', 'ip_address', 'user_agent',
        'is_edited', 'edited_at', 'ai_moderation_score',
    ];

    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
            'edited_at' => 'datetime',
            'ai_moderation_score' => 'decimal:2',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSpam($query)
    {
        return $query->where('status', 'spam');
    }

    public function scopeWithReactionCounts($query)
    {
        return $query->withCount(['reactions as likes_count' => fn($q) => $q->where('reaction_type', 'like')]);
    }

    public function getReactionSummary(): array
    {
        return $this->reactions()
            ->selectRaw('reaction_type, COUNT(*) as count')
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();
    }

    public function getAuthorName(): string
    {
        return $this->user?->name ?? $this->guest_name ?? __('Anonymous');
    }

    public function getAuthorEmail(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Comment $comment) {
            if (empty($comment->user_agent)) {
                $comment->user_agent = request()->userAgent();
            }
        });
    }
}
