<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PostResource
 *
 * Resource for Post model.
 *
 * @OA\Schema(
 *     schema="PostResource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Getting Started with Laravel"),
 *     @OA\Property(property="slug", type="string", example="getting-started-with-laravel"),
 *     @OA\Property(property="excerpt", type="string", example="A brief introduction..."),
 *     @OA\Property(property="content", type="string", example="Full content..."),
 *     @OA\Property(property="featured_image", type="string", format="url", example="https://example.com/image.jpg"),
 *     @OA\Property(property="is_featured", type="boolean", example=false),
 *     @OA\Property(property="reading_time", type="integer", example=5),
 *     @OA\Property(property="status", type="string", enum={"draft", "published", "scheduled", "archived"}, example="published"),
 *     @OA\Property(property="views_count", type="integer", example=150),
 *     @OA\Property(property="likes_count", type="integer", example=25),
 *     @OA\Property(property="comments_count", type="integer", example=10),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="author", ref="#/components/schemas/UserResource"),
 *     @OA\Property(property="category", ref="#/components/schemas/CategoryResource"),
 *     @OA\Property(property="tags", type="array", @OA\Items(ref="#/components/schemas/TagResource"))
 * )
 */
class PostResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->routeIs('posts.show'), $this->content),
            'featured_image' => $this->featured_image,
            'is_featured' => $this->is_featured,
            'reading_time' => $this->reading_time,
            'reading_time_formatted' => $this->reading_time . ' min read',
            'status' => $this->status,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'author' => when($this->whenLoaded('author'), fn() => new UserResource($this->author)),
            'category' => when($this->whenLoaded('category'), fn() => new CategoryResource($this->category)),
            'tags' => when($this->whenLoaded('tags'), fn() => TagResource::collection($this->tags)),
            
            // Meta
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     */
    public function with($request): array
    {
        return [
            'version' => 'v1',
        ];
    }
}
