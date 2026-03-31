<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'content' => $this->content,
            'is_edited' => $this->is_edited,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'status' => $this->when($request->user()?->isAdmin(), $this->status),
            'author' => new UserResource($this->whenLoaded('author')),
            'replies' => CommentResource::collection($this->whenLoaded('approvedReplies')),
            'replies_count' => $this->when($request->routeIs('posts.comments.*'), function () {
                return $this->replies()->approved()->count();
            }),
        ];
    }
}
