<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'parent_id',
        'name',
        'slug',
        'short_description',
        'full_description',
        'image',
        'icon',
        'color',
        'sort_order',
        'featured',
        'status',
        'template',
        'lang',
        'access_level',
        'article_count',
        'posts_count',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'posts_count' => 'integer',
        ];
    }

    public function scopeTemplate($query, string $template)
    {
        return $query->where('template', $template);
    }

    public function scopeLang($query, string $lang)
    {
        return $query->where('lang', $lang);
    }

    public static function rebuildClosureTable(): void
    {
        \Illuminate\Support\Facades\DB::table('category_closure')->truncate();

        $categories = self::all()->keyBy('id');

        foreach ($categories as $category) {
            // Self-reference (depth 0)
            \Illuminate\Support\Facades\DB::table('category_closure')->insert([
                'ancestor_id' => $category->id,
                'descendant_id' => $category->id,
                'depth' => 0,
            ]);

            // Walk ancestors
            $depth = 1;
            $parent = $category->parent;
            while ($parent) {
                \Illuminate\Support\Facades\DB::table('category_closure')->insert([
                    'ancestor_id' => $parent->id,
                    'descendant_id' => $category->id,
                    'depth' => $depth,
                ]);
                $depth++;
                $parent = $parent->parent;
            }
        }
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function ancestors(): array
    {
        $ancestors = [];
        $parent = $this->parent;
        while ($parent) {
            $ancestors[] = $parent;
            $parent = $parent->parent;
        }
        return array_reverse($ancestors);
    }

    public function getBreadcrumbs(): array
    {
        $crumbs = [__('Home') => route('blog.index')];
        foreach ($this->ancestors() as $a) {
            $crumbs[$a->name] = route('category.show', $a->slug);
        }
        $crumbs[$this->name] = '';
        return $crumbs;
    }

    public function descendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }
        return $descendants;
    }

    public static function tree(): \Illuminate\Support\Collection
    {
        return self::root()
            ->with(['children' => fn($q) => $q->withCount('posts')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function generateSeo(): void
    {
        $this->seo()->updateOrCreate(
            ['seoable_id' => $this->id, 'seoable_type' => self::class],
            [
                'meta_title' => $this->seo?->meta_title ?? $this->name . ' — ' . config('app.name'),
                'meta_description' => $this->seo?->meta_description
                    ?? $this->short_description
                    ?? 'Explore articles about ' . $this->name . '.',
                'canonical_url' => route('category.show', $this->slug),
                'og_title' => $this->seo?->og_title ?? $this->name,
                'og_description' => $this->seo?->og_description ?? $this->short_description,
                'og_image' => $this->seo?->og_image ?? $this->image,
                'schema_type' => 'CollectionPage',
            ]
        );
    }

    public function related(int $limit = 5): \Illuminate\Support\Collection
    {
        $relatedIds = \Illuminate\Support\Facades\DB::table('posts')
            ->whereIn('posts.id', function ($q) {
                $q->select('p2.id')
                  ->from('posts as p2')
                  ->where('p2.category_id', $this->id);
            })
            ->where('posts.category_id', '!=', $this->id)
            ->whereNotNull('posts.category_id')
            ->groupBy('posts.category_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('posts.category_id');

        return self::whereIn('id', $relatedIds)->get();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Category $category) {
            if (empty($category->uuid)) {
                $category->uuid = (string) Str::uuid();
            }
        });
    }
}
