<?php

namespace App\Models;

use App\Jobs\PublishScheduledPostJob;
use App\Traits\HasCacheKeys;
use App\Traits\HasPublishingWorkflow;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    public const CACHE_PREFIX = 'posts';

    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory, SoftDeletes, HasPublishingWorkflow, HasCacheKeys;

    protected $fillable = [
        'uuid',
        'author_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'content_format',
        'status',
        'visibility',
        'is_featured',
        'is_scheduled',
        'published_at',
        'scheduled_at',
        'reading_time',
        'word_count',
        'views_count',
        'likes_count',
        'shares_count',
        'seo_score',
        'ai_score',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_scheduled' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)
            ->withPivot('relevance_score', 'created_at');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function revisions()
    {
        return $this->hasMany(PostRevision::class);
    }

    public function scheduleForPublication(Carbon $dateTime): bool
    {
        $this->scheduled_at = $dateTime;
        $this->is_scheduled = true;
        $this->status = 'scheduled';

        $saved = $this->save();

        if ($saved) {
            PublishScheduledPostJob::dispatch($this->id)->delay($dateTime);
        }

        return $saved;
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function metrics()
    {
        return $this->hasOne(ContentMetric::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeVisibleByUser($query, ?\App\Models\User $user = null)
    {
        if ($user) {
            return $query->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhere('author_id', $user->id);
            });
        }
        return $query->where('visibility', 'public');
    }

    public function related(int $limit = 4): \Illuminate\Support\Collection
    {
        try {
            $graphLinks = \App\Models\ContentLink::where('source_type', 'post')
                ->where('source_id', $this->id)
                ->where('target_type', 'post')
                ->orderBy('weight_score', 'desc')
                ->take($limit)
                ->pluck('target_id');

            if ($graphLinks->isNotEmpty()) {
                $posts = self::published()->whereIn('id', $graphLinks)->with('category', 'author')->get();
                if ($posts->isNotEmpty()) return $posts;
            }
        } catch (\Exception $e) {
        }

        $tagIds = $this->tags()->pluck('tags.id')->toArray();

        $related = self::published()
            ->where('id', '!=', $this->id)
            ->where(function ($q) use ($tagIds) {
                if (!empty($tagIds)) {
                    $q->whereHas('tags', fn($t) => $t->whereIn('tags.id', $tagIds));
                }
                if ($this->category_id) {
                    $q->orWhere('category_id', $this->category_id);
                }
            })
            ->with('category', 'author')
            ->orderBy('published_at', 'desc')
            ->take($limit)
            ->get();

        if ($related->count() < $limit) {
            $extra = self::published()
                ->where('id', '!=', $this->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->with('category', 'author')
                ->orderBy('views_count', 'desc')
                ->take($limit - $related->count())
                ->get();
            $related = $related->concat($extra);
        }

        return $related;
    }

    public function getTrendingScore(): float
    {
        $daysSincePublished = max(1, now()->diffInDays($this->published_at ?? $this->created_at));
        return ($this->views_count / $daysSincePublished) + ($this->shares_count * 2) + ($this->comments()->count() * 3);
    }

    public function generateExcerpt(int $length = 160): string
    {
        if (!empty($this->excerpt)) return $this->excerpt;
        $text = strip_tags($this->content ?? '');
        return mb_strlen($text) > $length
            ? mb_substr($text, 0, $length) . '...'
            : $text;
    }

    public static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $base = $slug;
        $counter = 1;
        while (self::where('slug', $slug)->when($excludeId, fn($q, $id) => $q->where('id', '!=', $id))->exists()) {
            $slug = $base . '-' . $counter++;
        }
        return $slug;
    }

    public function duplicate(int $newAuthorId): self
    {
        $copy = $this->replicate();
        $copy->title = $this->title . ' (Copy)';
        $copy->slug = self::generateUniqueSlug($copy->title);
        $copy->status = 'draft';
        $copy->author_id = $newAuthorId;
        $copy->published_at = null;
        $copy->scheduled_at = null;
        $copy->is_scheduled = false;
        $copy->views_count = 0;
        $copy->likes_count = 0;
        $copy->shares_count = 0;
        $copy->save();

        foreach ($this->tags as $tag) {
            $copy->tags()->attach($tag->id, [
                'relevance_score' => $tag->pivot->relevance_score,
                'created_at' => now(),
            ]);
        }

        return $copy;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Post $post) {
            if (empty($post->uuid)) {
                $post->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (Post $post) {
            if (!empty($post->content)) {
                $post->word_count = str_word_count(strip_tags($post->content));
                $post->reading_time = max(1, (int) ceil($post->word_count / 200));
            }
        });
    }
}
