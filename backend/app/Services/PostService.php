<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;
use App\Repositories\PostRepository;
use App\Services\NotificationService;
use App\Events\PostCreated;
use App\Events\PostUpdated;
use App\Events\PostPublished;
use App\Events\PostDeleted;
use App\Events\PostRestored;
use App\Events\PostViewed;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class PostService
 *
 * Service layer for post business logic.
 * Handles all post-related operations with proper validation and events.
 *
 * @package App\Services
 */
class PostService
{
    /**
     * The notification service instance.
     */
    protected NotificationService $notificationService;

    /**
     * Create a new service instance.
     */
    public function __construct(
        private PostRepository $postRepository,
        NotificationService $notificationService
    ) {
        $this->notificationService = $notificationService;
    }

    /**
     * Get paginated posts with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getPaginated($filters, $perPage);
    }

    /**
     * Get published posts with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getPublishedPosts($filters, $perPage);
    }

    /**
     * Get post by slug.
     *
     * @param string $slug
     * @param array $with
     * @return Post|null
     */
    public function getPostBySlug(string $slug, array $with = ['author', 'category', 'tags']): ?Post
    {
        return $this->postRepository->findBySlug($slug, $with);
    }

    /**
     * Get post by ID.
     *
     * @param int $id
     * @param array $with
     * @return Post|null
     */
    public function getPostById(int $id, array $with = ['author', 'category', 'tags']): ?Post
    {
        return $this->postRepository->find($id, ['*']);
    }

    /**
     * Get post by ID with trashed.
     *
     * @param int $id
     * @param array $with
     * @return Post|null
     */
    public function getPostByIdWithTrashed(int $id, array $with = []): ?Post
    {
        $post = Post::withTrashed()->find($id);
        if ($post && !empty($with)) {
            $post->load($with);
        }
        return $post;
    }

    /**
     * Create a new post.
     *
     * @param array $data
     * @param User $user
     * @return Post
     */
    public function createPost(array $data, User $user): Post
    {
        return DB::transaction(function () use ($data, $user) {
            // Prepare post data
            $postData = $this->preparePostData($data, $user);

            // Handle featured image upload
            if (isset($data['featured_image_file']) && $data['featured_image_file'] instanceof UploadedFile) {
                $postData['featured_image'] = $this->uploadFeaturedImage($data['featured_image_file']);
            }

            // Create the post
            $post = $this->postRepository->createPost($postData);

            // Fire created event
            event(new PostCreated($post, $user));

            return $post;
        });
    }

    /**
     * Update an existing post.
     *
     * @param Post $post
     * @param array $data
     * @param User $user
     * @return Post
     */
    public function updatePost(Post $post, array $data, User $user): Post
    {
        return DB::transaction(function () use ($post, $data, $user) {
            // Track changes
            $changes = $this->trackChanges($post, $data);

            // Handle featured image upload
            if (isset($data['featured_image_file']) && $data['featured_image_file'] instanceof UploadedFile) {
                // Delete old image if exists
                if ($post->featured_image) {
                    $this->deleteFeaturedImage($post->featured_image);
                }
                $data['featured_image'] = $this->uploadFeaturedImage($data['featured_image_file']);
            } elseif (isset($data['remove_featured_image']) && $data['remove_featured_image']) {
                // Remove featured image
                if ($post->featured_image) {
                    $this->deleteFeaturedImage($post->featured_image);
                }
                $data['featured_image'] = null;
            }

            // Update the post
            $updatedPost = $this->postRepository->updatePost($post, $data);

            // Fire updated event if there were changes
            if (!empty($changes)) {
                event(new PostUpdated($updatedPost, $user, $changes));
            }

            return $updatedPost;
        });
    }

    /**
     * Delete a post (soft delete).
     *
     * @param Post $post
     * @param User $user
     * @return bool|null
     */
    public function deletePost(Post $post, User $user): ?bool
    {
        $result = $this->postRepository->deletePost($post);

        if ($result) {
            event(new PostDeleted($post, $user));
        }

        return $result;
    }

    /**
     * Restore a deleted post.
     *
     * @param Post $post
     * @param User $user
     * @return bool|null
     */
    public function restorePost(Post $post, User $user): ?bool
    {
        $result = $this->postRepository->restore($post);

        if ($result) {
            event(new PostRestored($post, $user));
        }

        return $result;
    }

    /**
     * Publish a post.
     *
     * @param Post $post
     * @param User $user
     * @param \DateTime|string|null $publishedAt
     * @return Post
     */
    public function publishPost(Post $post, User $user, $publishedAt = null): Post
    {
        return DB::transaction(function () use ($post, $user, $publishedAt) {
            $publishedPost = $this->postRepository->publish($post);

            if ($publishedAt) {
                $publishedPost->published_at = $publishedAt instanceof \DateTime
                    ? $publishedAt
                    : now()->parse($publishedAt);
                $publishedPost->save();
            }

            event(new PostPublished($publishedPost, $user));

            // Notify subscribers about the new post
            $this->notificationService->notifySubscribers($publishedPost);

            return $publishedPost;
        });
    }

    /**
     * Unpublish a post.
     *
     * @param Post $post
     * @param User $user
     * @return Post
     */
    public function unpublishPost(Post $post, User $user): Post
    {
        return $this->postRepository->unpublish($post);
    }

    /**
     * Archive a post.
     *
     * @param Post $post
     * @param User $user
     * @return Post
     */
    public function archivePost(Post $post, User $user): Post
    {
        return $this->postRepository->archive($post);
    }

    /**
     * Feature a post.
     *
     * @param Post $post
     * @param User $user
     * @return Post
     */
    public function featurePost(Post $post, User $user): Post
    {
        return $this->postRepository->feature($post);
    }

    /**
     * Unfeature a post.
     *
     * @param Post $post
     * @param User $user
     * @return Post
     */
    public function unfeaturePost(Post $post, User $user): Post
    {
        return $this->postRepository->unfeature($post);
    }

    /**
     * Autosave a post draft.
     *
     * @param Post $post
     * @param array $data
     * @param User $user
     * @return Post
     */
    public function autosavePost(Post $post, array $data, User $user): Post
    {
        // Check autosave frequency (30 seconds)
        if ($post->updated_at && $post->updated_at->diffInSeconds(now()) < 30) {
            // Optionally throw exception or return current post
            // For now, we'll allow it but could add throttling
        }

        return $this->postRepository->autosave($post, $data);
    }

    /**
     * Get user's posts.
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserPosts(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getUserPosts($userId, $filters, $perPage);
    }

    /**
     * Get posts by category.
     *
     * @param int|string $category
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPostsByCategory(int|string $category, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getByCategory($category, $filters, $perPage);
    }

    /**
     * Get posts by tag.
     *
     * @param int|string $tag
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPostsByTag(int|string $tag, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getByTag($tag, $filters, $perPage);
    }

    /**
     * Get posts by author.
     *
     * @param int $authorId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPostsByAuthor(int $authorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getByAuthor($authorId, $filters, $perPage);
    }

    /**
     * Search posts.
     *
     * @param string $query
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchPosts(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (isset($filters['boolean']) && $filters['boolean']) {
            return $this->postRepository->searchBoolean($query, $filters, $perPage);
        }

        return $this->postRepository->search($query, $filters, $perPage);
    }

    /**
     * Get trending posts.
     *
     * @param int $days
     * @param int $limit
     * @return Collection
     */
    public function getTrendingPosts(int $days = 7, int $limit = 10): Collection
    {
        return $this->postRepository->getTrending($days, $limit);
    }

    /**
     * Get featured posts.
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedPosts(int $limit = 5): Collection
    {
        return $this->postRepository->getFeatured($limit);
    }

    /**
     * Get related posts.
     *
     * @param Post $post
     * @param int $limit
     * @return Collection
     */
    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        return $this->postRepository->getRelated($post, $limit);
    }

    /**
     * Get draft posts for a user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDraftPosts(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getDrafts($userId, $perPage);
    }

    /**
     * Get scheduled posts.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getScheduledPosts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getScheduled($perPage);
    }

    /**
     * Get posts by status.
     *
     * @param string $status
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPostsByStatus(string $status, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getByStatus($status, $filters, $perPage);
    }

    /**
     * Bulk actions on posts.
     *
     * @param array $postIds
     * @param string $action
     * @param User $user
     * @return array
     */
    public function bulkAction(array $postIds, string $action, User $user): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($postIds as $postId) {
            try {
                $post = $this->postRepository->find($postId);

                if (!$post) {
                    $results['failed'][] = [
                        'id' => $postId,
                        'reason' => 'Post not found',
                    ];
                    continue;
                }

                // Check permission for each post
                if (!$this->canPerformBulkAction($user, $action, $post)) {
                    $results['failed'][] = [
                        'id' => $postId,
                        'reason' => 'Insufficient permissions',
                    ];
                    continue;
                }

                switch ($action) {
                    case 'publish':
                        $this->postRepository->publish($post);
                        break;

                    case 'archive':
                        $this->postRepository->archive($post);
                        break;

                    case 'delete':
                        $this->postRepository->deletePost($post);
                        break;

                    case 'feature':
                        $this->postRepository->feature($post);
                        break;

                    case 'restore':
                        $this->postRepository->restore($post);
                        break;
                }

                $results['success'][] = $postId;

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $postId,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Increment view count for a post.
     *
     * @param Post $post
     * @param User|null $user
     * @param string $ipAddress
     * @return bool Returns true if view was counted, false if duplicate
     */
    public function incrementViews(Post $post, ?User $user, string $ipAddress): bool
    {
        // Check for duplicate view within 24 hours
        if ($this->isDuplicateView($post, $user, $ipAddress)) {
            return false;
        }

        // Record the view
        $this->recordView($post, $user, $ipAddress);

        // Increment count
        $this->postRepository->incrementViews($post);

        // Fire event
        event(new PostViewed($post, $user, $ipAddress));

        return true;
    }

    /**
     * Get post counts by status.
     *
     * @param int|null $userId
     * @return array
     */
    public function getCountByStatus(?int $userId = null): array
    {
        return $this->postRepository->getCountByStatus($userId);
    }

    /**
     * Generate preview token for a post.
     *
     * @param Post $post
     * @param User $user
     * @param int $expiresIn Hours until expiration
     * @return string
     */
    public function generatePreviewToken(Post $post, User $user, int $expiresIn = 24): string
    {
        $token = Str::random(64);

        // Store in cache with expiration
        Cache::put(
            "preview_token:{$token}",
            [
                'post_id' => $post->id,
                'user_id' => $user->id,
                'expires_at' => now()->addHours($expiresIn)->timestamp,
            ],
            $expiresIn * 3600
        );

        return $token;
    }

    /**
     * Validate preview token.
     *
     * @param string $token
     * @param int $postId
     * @return bool
     */
    public function validatePreviewToken(string $token, int $postId): bool
    {
        $data = Cache::get("preview_token:{$token}");

        if (!$data) {
            return false;
        }

        if ($data['post_id'] !== $postId) {
            return false;
        }

        if ($data['expires_at'] < now()->timestamp) {
            Cache::forget("preview_token:{$token}");
            return false;
        }

        return true;
    }

    /**
     * Get author information for a post.
     *
     * @param Post $post
     * @return array
     */
    public function getAuthorInfo(Post $post): array
    {
        $author = $post->author;

        if (!$author) {
            return [];
        }

        return [
            'id' => $author->id,
            'name' => $author->name,
            'email' => $author->email,
            'avatar' => $author->avatar,
            'bio' => $author->bio,
            'website' => $author->website,
            'twitter' => $author->twitter,
            'github' => $author->github,
            'linkedin' => $author->linkedin,
            'facebook' => $author->facebook,
            'location' => $author->location,
            'posts_count' => $author->publishedPosts()->count(),
        ];
    }

    /**
     * Prepare post data for creation/update.
     *
     * @param array $data
     * @param User $user
     * @return array
     */
    private function preparePostData(array $data, User $user): array
    {
        $postData = [
            'user_id' => $user->id,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? $this->generateUniqueSlug($data['title']),
            'excerpt' => $data['excerpt'] ?? $this->generateExcerpt($data['content']),
            'content' => $data['content'],
            'category_id' => $data['category_id'] ?? null,
            'status' => $data['status'] ?? Post::STATUS_DRAFT,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
        ];

        // Handle published status
        if ($postData['status'] === Post::STATUS_PUBLISHED) {
            $postData['published_at'] = $data['published_at'] ?? now();
        }

        // Handle scheduled status
        if ($postData['status'] === Post::STATUS_SCHEDULED) {
            $postData['scheduled_for'] = $data['scheduled_for'] ?? null;
        }

        return $postData;
    }

    /**
     * Generate unique slug.
     *
     * @param string $title
     * @param int|null $excludeId
     * @return string
     */
    private function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        return $this->postRepository->generateUniqueSlug($title, $excludeId);
    }

    /**
     * Generate excerpt from content.
     *
     * @param string $content
     * @param int $length
     * @return string
     */
    private function generateExcerpt(string $content, int $length = 150): string
    {
        $excerpt = strip_tags($content);

        if (strlen($excerpt) <= $length) {
            return $excerpt;
        }

        return Str::limit($excerpt, $length);
    }

    /**
     * Upload featured image.
     *
     * @param UploadedFile $file
     * @return string
     */
    private function uploadFeaturedImage(UploadedFile $file): string
    {
        $path = $file->store('posts/featured', 'public');
        return Storage::disk('public')->url($path);
    }

    /**
     * Delete featured image.
     *
     * @param string $path
     * @return void
     */
    private function deleteFeaturedImage(string $path): void
    {
        // Extract relative path from URL
        $relativePath = str_replace(Storage::disk('public')->url(''), '', $path);

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    /**
     * Track changes between old and new data.
     *
     * @param Post $post
     * @param array $newData
     * @return array
     */
    private function trackChanges(Post $post, array $newData): array
    {
        $changes = [];
        $trackableFields = ['title', 'slug', 'content', 'excerpt', 'status', 'category_id', 'meta_title', 'meta_description'];

        foreach ($trackableFields as $field) {
            if (isset($newData[$field]) && $post->getOriginal($field) !== $newData[$field]) {
                $changes[$field] = [
                    'old' => $post->getOriginal($field),
                    'new' => $newData[$field],
                ];
            }
        }

        // Track tag changes
        if (isset($newData['tags'])) {
            $oldTags = $post->tags->pluck('id')->sort()->values()->toArray();
            $newTags = collect($newData['tags'])->sort()->values()->toArray();

            if ($oldTags !== $newTags) {
                $changes['tags'] = [
                    'old' => $oldTags,
                    'new' => $newTags,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if user can perform bulk action on post.
     *
     * @param User $user
     * @param string $action
     * @param Post $post
     * @return bool
     */
    private function canPerformBulkAction(User $user, string $action, Post $post): bool
    {
        switch ($action) {
            case 'delete':
                return $user->id === $post->user_id || $user->role === 'admin';

            case 'publish':
            case 'archive':
                return in_array($user->role, ['admin', 'editor']);

            case 'feature':
                return $user->role === 'admin';

            case 'restore':
                return $user->role === 'admin';

            default:
                return false;
        }
    }

    /**
     * Check for duplicate view within 24 hours.
     *
     * @param Post $post
     * @param User|null $user
     * @param string $ipAddress
     * @return bool
     */
    private function isDuplicateView(Post $post, ?User $user, string $ipAddress): bool
    {
        $cacheKey = "post_view:{$post->id}:";

        if ($user) {
            $cacheKey .= "user:{$user->id}";
        } else {
            $cacheKey .= "ip:{$ipAddress}";
        }

        return Cache::has($cacheKey);
    }

    /**
     * Record view for duplicate checking.
     *
     * @param Post $post
     * @param User|null $user
     * @param string $ipAddress
     * @return void
     */
    private function recordView(Post $post, ?User $user, string $ipAddress): void
    {
        $cacheKey = "post_view:{$post->id}:";

        if ($user) {
            $cacheKey .= "user:{$user->id}";
        } else {
            $cacheKey .= "ip:{$ipAddress}";
        }

        // Store for 24 hours
        Cache::put($cacheKey, true, 86400);

        // Also store in database for analytics
        DB::table('post_views')->insert([
            'post_id' => $post->id,
            'user_id' => $user?->id,
            'ip_address' => $ipAddress,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Notify subscribers about new published post.
     *
     * @param Post $post
     * @return void
     */
    private function notifySubscribers(Post $post): void
    {
        // This would typically dispatch a job to send notifications
        // For now, it's a placeholder for the notification logic
        // \App\Jobs\NotifySubscribers::dispatch($post);
    }
}
