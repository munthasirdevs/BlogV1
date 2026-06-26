<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'color',
        'usage_count',
        'trending_score',
        'seo_title',
        'seo_description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'trending_score' => 'decimal:2',
        ];
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class)
            ->withPivot('relevance_score', 'created_at');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrending($query)
    {
        return $query->where('trending_score', '>', 0)->orderBy('trending_score', 'desc');
    }

    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getWeightClass(): string
    {
        $maxUsage = Tag::active()->max('usage_count') ?: 1;
        $ratio = ($this->usage_count ?? 0) / $maxUsage;

        return match (true) {
            $ratio > 0.8 => 'tag-5xl',
            $ratio > 0.6 => 'tag-4xl',
            $ratio > 0.4 => 'tag-3xl',
            $ratio > 0.2 => 'tag-2xl',
            $ratio > 0.1 => 'tag-xl',
            default => 'tag-lg',
        };
    }

    public function related(int $limit = 6): \Illuminate\Support\Collection
    {
        $tagIds = DB::table('post_tag')
            ->whereIn('post_id', function ($q) {
                $q->select('post_id')->from('post_tag')->where('tag_id', $this->id);
            })
            ->where('tag_id', '!=', $this->id)
            ->select('tag_id', DB::raw('COUNT(*) as co_occurrence'))
            ->groupBy('tag_id')
            ->orderByDesc('co_occurrence')
            ->limit($limit)
            ->pluck('tag_id');

        return self::active()->whereIn('id', $tagIds)->get();
    }

    public function generateSeo(): void
    {
        $this->seo()->updateOrCreate(
            ['seoable_id' => $this->id, 'seoable_type' => self::class],
            [
                'meta_title' => $this->seo_title ?? "{$this->name} — " . config('app.name'),
                'meta_description' => $this->seo_description ?? "Browse all articles tagged '{$this->name}'. " . max($this->usage_count, 1) . " posts available.",
                'canonical_url' => route('tag.show', $this->slug),
                'og_title' => $this->seo_title ?? $this->name,
                'og_description' => $this->seo_description ?? "Articles tagged with {$this->name}",
                'schema_type' => 'CollectionPage',
            ]
        );
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            if (empty($tag->uuid)) {
                $tag->uuid = (string) Str::uuid();
            }
        });
    }
}
