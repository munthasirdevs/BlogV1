<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class Post
 * 
 * Represents a blog post with content, metadata, and relationships
 * to categories, tags, comments, and engagement metrics.
 * 
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $featured_image
 * @property bool $is_featured
 * @property int $reading_time
 * @property string $status - draft, published, scheduled, archived
 * @property int $views_count
 * @property int $likes_count
 * @property int $comments_count
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $scheduled_for
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property array|null $meta_keywords
 * @property array|null $custom_fields
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Post extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ARCHIVED = 'archived';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'is_featured',
        'reading_time',
        'status',
        'views_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'published_at',
        'scheduled_for',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'custom_fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'reading_time' => 'integer',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'shares_count' => 'integer',
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'meta_keywords' => 'array',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
            
            // Auto-calculate reading time if content is provided
            if (empty($post->reading_time) && !empty($post->content)) {
                $post->reading_time = $post->calculateReadingTime();
            }
        });

        static::updating(function ($post) {
            // Update slug if title changes and slug wasn't manually set
            if ($post->isDirty('title') && $post->getOriginal('slug') === Str::slug($post->getOriginal('title'))) {
                $post->slug = Str::slug($post->title);
            }
            
            // Recalculate reading time if content changes
            if ($post->isDirty('content')) {
                $post->reading_time = $post->calculateReadingTime();
            }
        });
    }

    /**
     * Calculate reading time in minutes.
     */
    public function calculateReadingTime(): int
    {
        $words = str_word_count(strip_tags($this->content ?? ''));
        return max(1, (int) ceil($words / 200));
    }

    /**
     * Get the author of the post.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the category of the post.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the tags associated with the post.
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag')
            ->withTimestamps();
    }

    /**
     * Get the comments for the post.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    /**
     * Get only approved comments.
     */
    public function approvedComments()
    {
        return $this->hasMany(Comment::class, 'post_id')->approved();
    }

    /**
     * Get only top-level comments (no replies).
     */
    public function topLevelComments()
    {
        return $this->hasMany(Comment::class, 'post_id')->topLevel();
    }

    /**
     * Get the likes for the post.
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Get the bookmarks for the post.
     */
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'post_id');
    }

    /**
     * Get the shares for the post.
     */
    public function shares()
    {
        return $this->hasMany(PostShare::class, 'post_id');
    }

    /**
     * Get media attachments for the post.
     */
    public function media()
    {
        return $this->morphMany(Media::class, 'model')
            ->orderBy('sort_order');
    }

    /**
     * Get the featured image media.
     */
    public function featuredImage()
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection_name', 'featured');
    }

    /**
     * Get analytics events for the post.
     */
    public function analyticsEvents()
    {
        return $this->hasMany(AnalyticsEvent::class, 'post_id');
    }

    /**
     * Get post views.
     */
    public function views()
    {
        return $this->hasMany(PostView::class, 'post_id');
    }

    /**
     * Get reading progress records.
     */
    public function readingProgress()
    {
        return $this->hasMany(PostReadingProgress::class, 'post_id');
    }

    /**
     * Check if post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if post is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if post is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if post is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Check if post is featured.
     */
    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Check if post can be viewed (published and not in future).
     */
    public function canBeViewedByPublic(): bool
    {
        return $this->isPublished() 
            && $this->published_at 
            && $this->published_at->isPast();
    }

    /**
     * Get reading time formatted.
     */
    public function getReadingTimeFormattedAttribute(): string
    {
        return $this->reading_time . ' min read';
    }

    /**
     * Get word count.
     */
    public function getWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->content ?? ''));
    }

    /**
     * Get content as HTML with paragraphs.
     */
    public function getContentAsHtmlAttribute(): string
    {
        return nl2br(e($this->content ?? ''));
    }

    /**
     * Get excerpt or generate from content.
     */
    public function getExcerptOrGenerateAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }
        
        return Str::limit(strip_tags($this->content ?? ''), 200);
    }

    /**
     * Scope for published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope for draft posts.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for scheduled posts.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope for archived posts.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope for featured posts.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by latest.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Scope to order by most viewed.
     */
    public function scopeMostViewed($query)
    {
        return $query->orderBy('views_count', 'desc');
    }

    /**
     * Scope to order by most liked.
     */
    public function scopeMostLiked($query)
    {
        return $query->orderBy('likes_count', 'desc');
    }

    /**
     * Scope to order by most commented.
     */
    public function scopeMostCommented($query)
    {
        return $query->orderBy('comments_count', 'desc');
    }

    /**
     * Scope for trending posts (high engagement in recent period).
     */
    public function scopeTrending($query, $days = 7)
    {
        return $query->published()
            ->where('published_at', '>=', now()->subDays($days))
            ->orderByRaw('(views_count + likes_count * 2 + comments_count * 3) DESC');
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, $categorySlug)
    {
        return $query->whereHas('category', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    /**
     * Scope to filter by category ID.
     */
    public function scopeCategoryId($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by tag.
     */
    public function scopeTag($query, $tagSlug)
    {
        return $query->whereHas('tags', function ($q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }

    /**
     * Scope to filter by author.
     */
    public function scopeAuthor($query, $authorId)
    {
        return $query->where('user_id', $authorId);
    }

    /**
     * Scope to filter by author slug/name.
     */
    public function scopeAuthorSlug($query, $authorSlug)
    {
        return $query->whereHas('author', function ($q) use ($authorSlug) {
            $q->where('name', 'LIKE', "%{$authorSlug}%");
        });
    }

    /**
     * Scope for search.
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('content', 'LIKE', "%{$searchTerm}%")
                ->orWhere('excerpt', 'LIKE', "%{$searchTerm}%")
                ->orWhere('meta_title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('meta_description', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Scope for posts published in date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('published_at', [$startDate, $endDate]);
    }

    /**
     * Scope for posts published in a specific year.
     */
    public function scopeYear($query, $year)
    {
        return $query->whereYear('published_at', $year);
    }

    /**
     * Scope for posts published in a specific month.
     */
    public function scopeMonth($query, $year, $month)
    {
        return $query->whereYear('published_at', $year)
            ->whereMonth('published_at', $month);
    }

    /**
     * Increment view count.
     */
    public function incrementViewsCount(int $amount = 1): void
    {
        $this->increment('views_count', $amount);
    }

    /**
     * Decrement view count.
     */
    public function decrementViewsCount(int $amount = 1): void
    {
        $this->decrement('views_count', $amount);
    }

    /**
     * Increment share count.
     */
    public function incrementShareCount(string $provider): void
    {
        $this->increment('shares_count');
    }

    /**
     * Refresh engagement counts.
     */
    public function refreshCounts(): void
    {
        $this->update([
            'likes_count' => $this->likes()->count(),
            'comments_count' => $this->approvedComments()->count(),
            'shares_count' => $this->shares()->count(),
        ]);
    }

    /**
     * Get related posts.
     */
    public function getRelatedPosts(int $limit = 4)
    {
        return static::published()
            ->where('id', '!=', $this->id)
            ->where('category_id', $this->category_id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get previous post in same category.
     */
    public function getPreviousPost()
    {
        return static::published()
            ->where('category_id', $this->category_id)
            ->where('published_at', '<', $this->published_at)
            ->latest('published_at')
            ->first();
    }

    /**
     * Get next post in same category.
     */
    public function getNextPost()
    {
        return static::published()
            ->where('category_id', $this->category_id)
            ->where('published_at', '>', $this->published_at)
            ->oldest('published_at')
            ->first();
    }
}
