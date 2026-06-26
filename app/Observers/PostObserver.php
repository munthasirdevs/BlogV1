<?php

namespace App\Observers;

use App\Models\Post;
use App\Notifications\ContentApprovalNotification;
use App\Services\Cache\FullPageCacheService;
use App\Services\CacheService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostObserver
{
    protected array $validTransitions = [
        'draft' => ['review', 'published', 'scheduled', 'archived', 'seo_review', 'approved'],
        'review' => ['seo_review', 'draft', 'revision_required', 'published', 'archived'],
        'seo_review' => ['approved', 'draft', 'revision_required', 'published', 'archived'],
        'approved' => ['scheduled', 'draft', 'published', 'archived'],
        'scheduled' => ['published', 'draft', 'archived'],
        'published' => ['archived', 'draft'],
        'archived' => ['draft', 'published'],
        'revision_required' => ['draft', 'review'],
    ];

    public function creating(Post $post): void
    {
        if (empty($post->uuid)) {
            $post->uuid = (string) Str::uuid();
        }
    }

    public function saved(Post $post): void
    {
        $cache = App::make(FullPageCacheService::class);
        $cache->invalidateByPrefix('global', $post->tenant_id);

        $cacheService = App::make(CacheService::class);
        $cacheService->forget('recent_posts');
        $cacheService->forget('featured_posts');
    }

    public function deleted(Post $post): void
    {
        $cache = App::make(FullPageCacheService::class);
        $cache->invalidateByPrefix('global', $post->tenant_id);
    }

    public function updated(Post $post): void
    {
        if ($post->isDirty('status')) {
            $oldStatus = $post->getOriginal('status');
            $newStatus = $post->status;

            try {
                $this->validateTransition($oldStatus, $newStatus);
            } catch (ValidationException $e) {
                \Illuminate\Support\Facades\Log::warning('Post status transition rejected', [
                    'post_id' => $post->id,
                    'from' => $oldStatus,
                    'to' => $newStatus,
                    'message' => $e->getMessage(),
                ]);

                $post->status = $oldStatus;
                $post->saveQuietly();

                return;
            }

            if ($newStatus === 'published') {
                \App\Events\PostPublished::dispatch($post);
            }
            \App\Events\PostWorkflowChanged::dispatch($post, $oldStatus, $newStatus);

            $this->logStatusChange($post, $oldStatus, $newStatus);

            $this->sendStatusNotification($post, $newStatus);
        }
    }

    protected function validateTransition(?string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === null) {
            return;
        }

        $allowed = $this->validTransitions[$oldStatus] ?? [];

        if (!in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition from '{$oldStatus}' to '{$newStatus}'.",
            ]);
        }
    }

    protected function logStatusChange(Post $post, string $oldStatus, string $newStatus): void
    {
        try {
            activity()
                ->performedOn($post)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ])
                ->log("Post status changed from {$oldStatus} to {$newStatus}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug('Activity log unavailable for post status change', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendStatusNotification(Post $post, string $newStatus): void
    {
        $actionMap = [
            'review' => 'submitted',
            'seo_review' => 'submitted',
            'approved' => 'approved',
            'published' => 'published',
        ];

        $action = $actionMap[$newStatus] ?? null;

        if (!$action) {
            return;
        }

        $users = \App\Models\User::permission(['approve_posts', 'edit_posts'])->get();

        Notification::send($users, new ContentApprovalNotification($post, $action));
    }
}
