<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class Category
 * 
 * Represents a blog post category with hierarchical support
 * (parent-child relationships).
 * 
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $color
 * @property string|null $icon
 * @property int $sort_order
 * @property bool $is_featured
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_featured',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            // Update slug if name changes and slug wasn't manually set
            if ($category->isDirty('name') && $category->getOriginal('slug') === Str::slug($category->getOriginal('name'))) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants()
    {
        return $this->children()->with('descendants')->get()->flatten();
    }

    /**
     * Get the posts in the category.
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    /**
     * Get only published posts in the category.
     */
    public function publishedPosts()
    {
        return $this->hasMany(Post::class, 'category_id')->published();
    }

    /**
     * Get featured posts in the category.
     */
    public function featuredPosts(int $limit = 3)
    {
        return $this->publishedPosts()
            ->featured()
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent posts in the category.
     */
    public function recentPosts(int $limit = 5)
    {
        return $this->publishedPosts()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get published posts count.
     */
    public function getPostsCountAttribute(): int
    {
        return $this->publishedPosts()->count();
    }

    /**
     * Get total posts count (including drafts).
     */
    public function getTotalPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }

    /**
     * Check if category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if category is a parent (top-level).
     */
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if category is featured.
     */
    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Check if category is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the full category path (ancestors).
     */
    public function getPathAttribute(): array
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }
        
        return $path;
    }

    /**
     * Get the full category name with ancestors.
     */
    public function getFullNameAttribute(): string
    {
        $names = [];
        $current = $this;
        
        while ($current) {
            array_unshift($names, $current->name);
            $current = $current->parent;
        }
        
        return implode(' > ', $names);
    }

    /**
     * Get breadcrumb data.
     */
    public function getBreadcrumbAttribute(): array
    {
        return array_map(function ($item, $index) {
            return [
                'label' => $item['name'],
                'url' => route('category.show', $item['slug']),
                'active' => $index === count($this->path) - 1,
            ];
        }, $this->path, array_keys($this->path));
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive categories.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for top-level categories (no parent).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for featured categories.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope to search categories by name.
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('description', 'LIKE', "%{$searchTerm}%");
    }

    /**
     * Get categories with published posts count.
     */
    public function scopeWithPostsCount($query)
    {
        return $query->withCount(['posts as published_posts_count' => function ($q) {
            $q->published();
        }]);
    }

    /**
     * Get all categories as a tree structure.
     */
    public static function getTree(): \Illuminate\Support\Collection
    {
        return static::topLevel()
            ->ordered()
            ->with(['children' => function ($q) {
                $q->ordered();
            }])
            ->get();
    }

    /**
     * Get all categories as a flat list with depth.
     */
    public static function getFlatList(): \Illuminate\Support\Collection
    {
        $categories = static::ordered()->get();
        
        return $categories->map(function ($category) use ($categories) {
            $depth = 0;
            $parent = $category->parent;
            
            while ($parent) {
                $depth++;
                $parent = $categories->find($parent->id)?->parent;
            }
            
            $category->depth = $depth;
            return $category;
        });
    }
}
