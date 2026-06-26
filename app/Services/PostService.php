<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
use App\Services\AI\AIService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        protected CacheService $cacheService,
        protected AIService $aiService
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Post::with('author', 'category');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('content', 'like', "%{$s}%"));
        }
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['category_id'])) $query->where('category_id', $filters['category_id']);

        match ($filters['sort'] ?? 'created') {
            'views' => $query->orderBy('views_count', 'desc'),
            'published' => $query->orderBy('published_at', 'desc'),
            'updated' => $query->orderBy('updated_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data, int $authorId, array $tagIds = []): Post
    {
        $data['slug'] = !empty($data['slug']) ? $data['slug'] : Post::generateUniqueSlug($data['title']);
        $data['excerpt'] = $data['excerpt'] ?? strip_tags(mb_substr($data['content'] ?? '', 0, 160));
        $data['author_id'] = $authorId;

        $post = Post::create($data);

        if (!empty($tagIds)) {
            $pivot = [];
            foreach ($tagIds as $tid) { $pivot[$tid] = ['relevance_score' => 0, 'created_at' => now()]; }
            $post->tags()->sync($pivot);
        }

        $this->createRevision($post, $authorId, 'Initial creation');

        return $post;
    }

    public function update(Post $post, array $data, array $tagIds = []): Post
    {
        $data['excerpt'] = $data['excerpt'] ?? strip_tags(mb_substr($data['content'] ?? '', 0, 160));
        $post->update($data);

        if (isset($tagIds)) {
            $pivot = [];
            foreach ($tagIds as $tid) { $pivot[$tid] = ['relevance_score' => 0, 'created_at' => now()]; }
            $post->tags()->sync($pivot);
        }

        if ($post->wasChanged(['content', 'title', 'excerpt'])) {
            $this->createRevision($post, auth()->id(), 'Updated via editor');
        }

        return $post;
    }

    public function duplicate(Post $post, int $newAuthorId): Post
    {
        $copy = $post->duplicate($newAuthorId);
        $this->createRevision($copy, $newAuthorId, 'Duplicated from post #' . $post->id);
        return $copy;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    public function restore(int $id): Post
    {
        $post = Post::withTrashed()->findOrFail($id);
        $post->restore();
        return $post;
    }

    public function bulkDelete(array $ids): int
    {
        Post::whereIn('id', $ids)->each(fn($p) => $p->delete());
        return count($ids);
    }

    public function bulkStatus(array $ids, string $status): int
    {
        Post::whereIn('id', $ids)->update(['status' => $status]);
        return count($ids);
    }

    public function getRelated(Post $post, int $limit = 4): Collection
    {
        return $post->related($limit);
    }

    protected function createRevision(Post $post, int $editorId, string $summary, array $extra = []): PostRevision
    {
        $num = (PostRevision::where('post_id', $post->id)->max('revision_number') ?? 0) + 1;

        $previous = PostRevision::where('post_id', $post->id)->orderBy('revision_number', 'desc')->first();

        $diffHash = PostRevision::computeDiffHash(
            $post->title, $post->content, $post->excerpt
        );

        if ($previous && $previous->diff_hash === $diffHash) {
            $previous->update(['change_summary' => $summary]);
            return $previous;
        }

        $seoSnapshot = null;
        if ($post->relationLoaded('seo') && $post->seo) {
            $seoSnapshot = $post->seo->toArray();
        }

        return PostRevision::create(array_merge([
            'post_id' => $post->id,
            'editor_id' => $editorId,
            'revision_number' => $num,
            'title_snapshot' => $post->title,
            'excerpt_snapshot' => $post->excerpt,
            'content_snapshot' => $post->content,
            'seo_snapshot' => $seoSnapshot,
            'change_summary' => $summary,
            'diff_hash' => $diffHash,
        ], $extra));
    }
}
