<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class UserResource
 *
 * Resource for User model.
 */
class UserResource extends JsonResource
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
            'email' => $this->when($request->user()?->can('view-email', $this->resource) || $request->user()?->id === $this->id, $this->email),
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'role' => $this->role,
            'status' => $this->status,
            'website' => $this->website,
            'location' => $this->location,
            'timezone' => $this->timezone,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Social links
            'social_links' => [
                'twitter' => $this->twitter,
                'github' => $this->github,
                'linkedin' => $this->linkedin,
                'facebook' => $this->facebook,
            ],
            
            // Computed attributes
            'posts_count' => $this->when($this->posts_count !== null, $this->posts_count),
            
            // Roles (Spatie)
            'roles' => when($this->whenLoaded('roles'), fn() => $this->roles->pluck('name')),
            
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
