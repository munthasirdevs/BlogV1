<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class Tag
 * 
 * Represents a tag that can be associated with blog posts
 * for categorization and discovery.
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $color
 * @property int $posts_count
 * @property bool $is_featured
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Tag extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'posts_count',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posts_count' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            // Update slug if name changes and slug wasn't manually set
            if ($tag->isDirty('name') && $tag->getOriginal('slug') === Str::slug($tag->getOriginal('name'))) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Get the posts with this tag.
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag')
            ->withTimestamps();
    }

    /**
     * Get only published posts with this tag.
     */
    public function publishedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_tag')
            ->where('posts.status', Post::STATUS_PUBLISHED)
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', now())
            ->withTimestamps();
    }

    /**
     * Get recent posts with this tag.
     */
    public function recentPosts(int $limit = 5)
    {
        return $this->publishedPosts()
            ->latest('posts.published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get featured posts with this tag.
     */
    public function featuredPosts(int $limit = 3)
    {
        return $this->publishedPosts()
            ->where('posts.is_featured', true)
            ->latest('posts.published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Update the posts count.
     */
    public function updatePostsCount(): void
    {
        $this->update([
            'posts_count' => $this->publishedPosts()->count(),
        ]);
    }

    /**
     * Get published posts count.
     */
    public function getPostsCountAttribute(): int
    {
        return $this->publishedPosts()->count();
    }

    /**
     * Check if tag is featured.
     */
    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Get tag color with fallback.
     */
    public function getColorWithFallbackAttribute(): string
    {
        return $this->color ?? '#6B7280';
    }

    /**
     * Scope for featured tags.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by posts count.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('posts_count', 'desc');
    }

    /**
     * Scope to order alphabetically.
     */
    public function scopeAlphabetical($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope to search tags by name.
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('description', 'LIKE', "%{$searchTerm}%");
    }

    /**
     * Scope for tags with minimum posts count.
     */
    public function scopeWithMinPosts($query, int $minPosts)
    {
        return $query->where('posts_count', '>=', $minPosts);
    }

    /**
     * Get popular tags.
     */
    public static function getPopular(int $limit = 10)
    {
        return static::withMinPosts(1)
            ->popular()
            ->limit($limit)
            ->get();
    }

    /**
     * Get all tags as a cloud with weights.
     */
    public static function getCloud(int $limit = 20): \Illuminate\Support\Collection
    {
        $tags = static::withMinPosts(1)
            ->orderBy('posts_count', 'desc')
            ->limit($limit)
            ->get();

        if ($tags->isEmpty()) {
            return $tags;
        }

        $maxPosts = $tags->max('posts_count');
        $minPosts = $tags->min('posts_count');
        $range = max(1, $maxPosts - $minPosts);

        return $tags->map(function ($tag) use ($minPosts, $range) {
            // Calculate weight from 1 to 5 based on posts count
            $tag->weight = (int) ceil((($tag->posts_count - $minPosts) / $range) * 4) + 1;
            return $tag;
        });
    }
}
