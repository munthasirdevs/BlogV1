<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CategoryResource
 *
 * Resource for Category model.
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'parent' => when($this->whenLoaded('parent'), fn() => new self($this->parent)),
            'children' => when($this->whenLoaded('children'), fn() => self::collection($this->children)),
            
            // Computed attributes
            'full_name' => $this->when($request->routeIs('categories.show'), $this->full_name),
            'path' => $this->when($request->routeIs('categories.show'), $this->path),
            'posts_count' => $this->when($this->published_posts_count !== null, $this->published_posts_count),
            
            // Meta
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
