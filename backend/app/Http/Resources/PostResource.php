<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
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
            'status' => $this->when($request->user()?->id === $this->user_id || $request->user()?->isAdmin(), $this->status),
            'views_count' => $this->views_count,
            'reading_time' => $this->reading_time,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'author' => new UserResource($this->whenLoaded('author')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'comments_count' => $this->comments_count,
            'likes_count' => $this->likes_count,
            'is_liked' => $this->when($request->user(), function () use ($request) {
                return $this->likes()->where('user_id', $request->user()->id)->exists();
            }),
            'is_bookmarked' => $this->when($request->user(), function () use ($request) {
                return $this->bookmarks()->where('user_id', $request->user()->id)->exists();
            }),
            'related_posts' => PostResource::collection($this->when($request->routeIs('posts.show'), function () {
                return $this->getRelatedPosts(4);
            })),
        ];
    }
}
