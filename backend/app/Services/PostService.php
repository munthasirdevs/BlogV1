<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    /**
     * Get paginated posts with filters.
     */
    public function getPaginatedPosts(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Post::published()
            ->with(['author', 'category', 'tags']);

        // Filter by category
        if (isset($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        // Filter by tag
        if (isset($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('slug', $filters['tag']);
            });
        }

        // Filter by author
        if (isset($filters['author'])) {
            $query->where('user_id', $filters['author']);
        }

        // Search
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('excerpt', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'published_at';
        $sortOrder = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        
        if (in_array($sortField, ['published_at', 'title', 'views_count', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    /**
     * Get post by slug.
     */
    public function getPostBySlug(string $slug): ?Post
    {
        return Post::published()
            ->with(['author', 'category', 'tags'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Create a new post.
     */
    public function createPost(array $data): Post
    {
        $postData = [
            'title' => $data['title'],
            'slug' => $data['slug'] ?? Str::slug($data['title']),
            'excerpt' => $data['excerpt'] ?? $this->generateExcerpt($data['content']),
            'content' => $data['content'],
            'featured_image' => $data['featured_image'] ?? null,
            'category_id' => $data['category_id'],
            'user_id' => $data['user_id'],
            'status' => $data['status'] ?? 'draft',
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ];

        if (isset($data['published_at'])) {
            $postData['published_at'] = $data['published_at'];
        }

        $post = Post::create($postData);

        // Attach tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $post->tags()->attach($data['tags']);
        }

        return $post->fresh(['author', 'category', 'tags']);
    }

    /**
     * Update a post.
     */
    public function updatePost(Post $post, array $data): Post
    {
        $updateData = [];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
            $updateData['slug'] = $data['slug'] ?? Str::slug($data['title']);
        }

        if (isset($data['excerpt'])) {
            $updateData['excerpt'] = $data['excerpt'];
        }

        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }

        if (isset($data['featured_image'])) {
            $updateData['featured_image'] = $data['featured_image'];
        }

        if (isset($data['category_id'])) {
            $updateData['category_id'] = $data['category_id'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
            
            if ($data['status'] === 'published' && !$post->published_at) {
                $updateData['published_at'] = now();
            }
        }

        if (isset($data['published_at'])) {
            $updateData['published_at'] = $data['published_at'];
        }

        if (isset($data['meta_title'])) {
            $updateData['meta_title'] = $data['meta_title'];
        }

        if (isset($data['meta_description'])) {
            $updateData['meta_description'] = $data['meta_description'];
        }

        $post->update($updateData);

        // Update tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return $post->fresh(['author', 'category', 'tags']);
    }

    /**
     * Delete a post.
     */
    public function deletePost(Post $post): void
    {
        $post->delete();
    }

    /**
     * Get user's posts.
     */
    public function getUserPosts(int $userId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Post::where('user_id', $userId)
            ->with(['author', 'category', 'tags']);

        // Include drafts if user is viewing their own posts
        if (request()?->user()?->id === $userId) {
            // Show all statuses
        } else {
            $query->published();
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Increment view count.
     */
    public function incrementViews(Post $post): void
    {
        $post->increment('views_count');
    }

    /**
     * Generate excerpt from content.
     */
    private function generateExcerpt(string $content, int $length = 150): string
    {
        $excerpt = strip_tags($content);
        
        if (strlen($excerpt) <= $length) {
            return $excerpt;
        }

        return Str::limit($excerpt, $length);
    }
}
