<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PostPreviewToken
 *
 * Represents a preview token for unpublished posts.
 * Allows sharing preview links with reviewers before publishing.
 *
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property string $token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property bool $used
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostPreviewToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'token',
        'expires_at',
        'used',
        'used_at',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'used' => 'boolean',
        ];
    }

    /**
     * Get the post that owns the preview token.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who created the preview token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token is valid.
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->used;
    }

    /**
     * Mark the token as used.
     */
    public function markAsUsed(string $ipAddress = null): void
    {
        $this->update([
            'used' => true,
            'used_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }
}
