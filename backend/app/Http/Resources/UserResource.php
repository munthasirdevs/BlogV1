<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->when($request->user()?->id === $this->id || $request->user()?->isAdmin(), $this->email),
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'role' => $this->role,
            'status' => $this->when($request->user()?->isAdmin(), $this->status),
            'email_verified' => (bool) $this->email_verified_at,
            'posts_count' => $this->whenLoaded('posts', fn() => $this->posts->count()),
            'comments_count' => $this->whenLoaded('comments', fn() => $this->comments->count()),
            'created_at' => $this->created_at->toIso8601String(),
            'joined_at' => $this->created_at->diffForHumans(),
        ];
    }
}
