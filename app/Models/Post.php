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
