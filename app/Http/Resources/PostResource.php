<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->routeIs('api.v1.posts.show'), $this->content),
            'status' => $this->status,
            'visibility' => $this->visibility,
            'featured' => $this->is_featured,
            'reading_time' => $this->reading_time,
            'word_count' => $this->word_count,
            'views' => $this->views_count,
            'seo_score' => $this->seo_score,
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => new AuthorResource($this->whenLoaded('author')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
