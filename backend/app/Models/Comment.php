<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Class Comment
 *
 * Represents a comment on a blog post with support for nested replies.
 *
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $content
 * @property string $status - pending, approved, rejected, spam
 * @property int $depth
 * @property bool $is_edited
 * @property int $likes_count
 * @property int $reply_count
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $moderated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SPAM = 'spam';

    /**
     * Maximum nesting depth.
     */
    const MAX_DEPTH = 5;

    /**
     * Maximum number of edits allowed.
     */
    const MAX_EDITS = 5;

    /**
     * Edit window in minutes (30 minutes).
     */
    const EDIT_WINDOW_MINUTES = 30;

    /**
     * Cache key prefix for comment trees.
     */
    const CACHE_PREFIX = 'comments:tree:';

    /**
     * Cache TTL in minutes.
     */
    const CACHE_TTL = 30;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
        'status',
        'depth',
        'is_edited',
        'likes_count',
        'reply_count',
        'ip_address',
        'user_agent',
        'moderated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'is_edited' => 'boolean',
            'likes_count' => 'integer',
            'reply_count' => 'integer',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @return array<string>
     */
    protected function hidden(): array
    {
        return ['ip_address', 'user_agent'];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            // Set depth based on parent
            if ($comment->parent_id) {
                $parent = static::find($comment->parent_id);
                if ($parent) {
                    $comment->depth = min($parent->depth + 1, self::MAX_DEPTH);
                }
            } else {
                $comment->depth = 0;
            }

            // Capture IP and user agent
            if (request()) {
                $comment->ip_address = request()->ip();
                $comment->user_agent = request()->userAgent();
            }
        });

        static::created(function ($comment) {
            // Update post's comment count
            $comment->post?->refreshCounts();

            // Clear comment tree cache
            self::clearTreeCache($comment->post_id);
        });

        static::updated(function ($comment) {
            // Clear comment tree cache on status change
            if ($comment->isDirty('status')) {
                self::clearTreeCache($comment->post_id);
                $comment->post?->refreshCounts();
            }
        });

        static::deleted(function ($comment) {
            // Update post's comment count
            $comment->post?->refreshCounts();

            // Clear comment tree cache
            self::clearTreeCache($comment->post_id);
        });
    }

    /**
     * Clear the comment tree cache for a post.
     */
    public static function clearTreeCache(int $postId): void
    {
        Cache::forget(self::CACHE_PREFIX . $postId);
    }

    /**
     * Get the post this comment belongs to.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get the user who made the comment.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the parent comment.
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get approved replies.
     */
    public function approvedReplies()
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->approved()
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get all descendants (nested replies) - recursive.
     */
    public function descendants()
    {
        return $this->replies()->with('descendants')->get()->flatten();
    }

    /**
     * Get edit history for this comment.
     */
    public function edits()
    {
        return $this->hasMany(CommentEdit::class, 'comment_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the likes for the comment.
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Check if comment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if comment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if comment is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if comment is spam.
     */
    public function isSpam(): bool
    {
        return $this->status === self::STATUS_SPAM;
    }

    /**
     * Check if comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if comment can have replies.
     */
    public function canHaveReplies(): bool
    {
        return $this->depth < self::MAX_DEPTH;
    }

    /**
     * Check if comment can be edited.
     */
    public function canBeEdited(): bool
    {
        // Can be edited within edit window
        if ($this->created_at->diffInMinutes(now()) > self::EDIT_WINDOW_MINUTES) {
            return false;
        }

        // Check edit count
        if ($this->edits()->count() >= self::MAX_EDITS) {
            return false;
        }

        return true;
    }

    /**
     * Check if comment can receive more edits.
     */
    public function canEditMore(): bool
    {
        return $this->edits()->count() < self::MAX_EDITS;
    }

    /**
     * Get author name with fallback.
     */
    public function getAuthorNameAttribute(): string
    {
        return $this->author?->name ?? 'Anonymous';
    }

    /**
     * Get author avatar with fallback.
     */
    public function getAuthorAvatarAttribute(): ?string
    {
        return $this->author?->avatar;
    }

    /**
     * Get content excerpt.
     */
    public function getExcerptAttribute(): string
    {
        return Str::limit($this->content, 100);
    }

    /**
     * Get time ago for display.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get content with linked mentions.
     */
    public function getContentWithMentionsAttribute(): string
    {
        return \App\Helpers\MentionParser::linkify($this->content);
    }

    /**
     * Get mentioned users from content.
     */
    public function getMentionedUsersAttribute()
    {
        return \App\Helpers\MentionParser::parse($this->content);
    }

    /**
     * Scope for approved comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for pending comments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for rejected comments.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for spam comments.
     */
    public function scopeSpam($query)
    {
        return $query->where('status', self::STATUS_SPAM);
    }

    /**
     * Scope for top-level comments (no parent).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for replies (has parent).
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope to order by latest (oldest first for threaded display).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope to order by newest first.
     */
    public function scopeNewest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to filter by depth.
     */
    public function scopeDepth($query, int $depth)
    {
        return $query->where('depth', $depth);
    }

    /**
     * Scope to filter by author.
     */
    public function scopeAuthor($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for comments with replies.
     */
    public function scopeWithReplies($query)
    {
        return $query->has('replies');
    }

    /**
     * Scope to search comments by content.
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('content', 'LIKE', "%{$searchTerm}%");
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by post.
     */
    public function scopePost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Get comment tree for a post (cached).
     */
    public static function getCommentTree(int $postId, bool $approvedOnly = true, int $maxDepth = 5)
    {
        $cacheKey = self::CACHE_PREFIX . $postId . ':' . ($approvedOnly ? 'approved' : 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($postId, $approvedOnly, $maxDepth) {
            $query = static::where('post_id', $postId)
                ->topLevel();

            if ($approvedOnly) {
                $query->approved();
            }

            return $query->with(['author', 'approvedReplies' => function ($q) use ($approvedOnly, $maxDepth) {
                if ($approvedOnly) {
                    $q->with(['author', 'approvedReplies' => function ($q2) use ($maxDepth) {
                        $q2->with('author');
                    }]);
                }
            }])
            ->latest()
            ->get();
        });
    }

    /**
     * Get flat list of comments for a post.
     */
    public static function getFlatComments(int $postId, bool $approvedOnly = true)
    {
        $query = static::where('post_id', $postId);

        if ($approvedOnly) {
            $query->approved();
        }

        return $query->with('author')
            ->latest()
            ->get();
    }

    /**
     * Mark comment as edited.
     */
    public function markAsEdited(): void
    {
        $this->update(['is_edited' => true]);
    }

    /**
     * Record an edit to this comment.
     */
    public function recordEdit(string $oldContent, string $newContent, ?string $reason = null): CommentEdit
    {
        return $this->edits()->create([
            'user_id' => auth()->id(),
            'old_content' => $oldContent,
            'new_content' => $newContent,
            'edit_reason' => $reason,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Increment likes count.
     */
    public function incrementLikesCount(): void
    {
        $this->increment('likes_count');
    }

    /**
     * Decrement likes count.
     */
    public function decrementLikesCount(): void
    {
        $this->decrement('likes_count');
    }

    /**
     * Increment reply count.
     */
    public function incrementReplyCount(): void
    {
        $this->increment('reply_count');

        // Also increment parent's reply count recursively
        if ($this->parent) {
            $this->parent->incrementReplyCount();
        }
    }

    /**
     * Decrement reply count.
     */
    public function decrementReplyCount(): void
    {
        $this->decrement('reply_count');

        // Also decrement parent's reply count recursively
        if ($this->parent) {
            $this->parent->decrementReplyCount();
        }
    }

    /**
     * Get the root comment (top-level ancestor).
     */
    public function getRootComment(): Comment
    {
        $comment = $this;

        while ($comment->parent) {
            $comment = $comment->parent;
        }

        return $comment;
    }

    /**
     * Check if this comment has any approved replies.
     */
    public function hasApprovedReplies(): bool
    {
        return $this->approvedReplies()->exists();
    }

    /**
     * Get the count of all descendants (nested replies).
     */
    public function getDescendantsCount(): int
    {
        return $this->descendants()->count();
    }

    /**
     * Approve this comment.
     */
    public function approve(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Reject this comment.
     */
    public function reject(): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Mark as spam.
     */
    public function markAsSpam(): void
    {
        $this->update([
            'status' => self::STATUS_SPAM,
            'moderated_at' => now(),
        ]);
    }
}
