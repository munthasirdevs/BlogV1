<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PostRevision
 *
 * Represents a revision of a post.
 * Tracks changes to post content over time.
 *
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property string|null $excerpt
 * @property string $slug
 * @property array|null $changes
 * @property string $revision_type
 * @property int $version
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostRevision extends Model
{
    use HasFactory;

    /**
     * Revision types.
     */
    const TYPE_MANUAL = 'manual';
    const TYPE_AUTOSAVE = 'autosave';
    const TYPE_PUBLISHED = 'published';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'title',
        'content',
        'excerpt',
        'slug',
        'changes',
        'revision_type',
        'version',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'version' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($revision) {
            // Auto-increment version
            if (empty($revision->version)) {
                $lastVersion = static::where('post_id', $revision->post_id)
                    ->max('version') ?? 0;
                $revision->version = $lastVersion + 1;
            }
        });
    }

    /**
     * Get the post that this revision belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who created this revision.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the previous revision.
     */
    public function getPreviousRevision(): ?PostRevision
    {
        return static::where('post_id', $this->post_id)
            ->where('version', '<', $this->version)
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Get the next revision.
     */
    public function getNextRevision(): ?PostRevision
    {
        return static::where('post_id', $this->post_id)
            ->where('version', '>', $this->version)
            ->orderBy('version', 'asc')
            ->first();
    }

    /**
     * Check if this is an autosave revision.
     */
    public function isAutosave(): bool
    {
        return $this->revision_type === self::TYPE_AUTOSAVE;
    }

    /**
     * Check if this is a manual revision.
     */
    public function isManual(): bool
    {
        return $this->revision_type === self::TYPE_MANUAL;
    }

    /**
     * Check if this is a published revision.
     */
    public function isPublished(): bool
    {
        return $this->revision_type === self::TYPE_PUBLISHED;
    }

    /**
     * Scope for manual revisions.
     */
    public function scopeManual($query)
    {
        return $query->where('revision_type', self::TYPE_MANUAL);
    }

    /**
     * Scope for autosave revisions.
     */
    public function scopeAutosave($query)
    {
        return $query->where('revision_type', self::TYPE_AUTOSAVE);
    }

    /**
     * Scope to get revisions by version.
     */
    public function scopeVersion($query, int $version)
    {
        return $query->where('version', $version);
    }

    /**
     * Scope to get latest revisions.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('version', 'desc');
    }
}
