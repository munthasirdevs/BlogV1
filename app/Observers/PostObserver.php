<?php

namespace App\Observers;

use App\Models\Post;
use App\Notifications\ContentApprovalNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostObserver
{
    protected array $validTransitions = [
        'draft' => ['review'],
        'review' => ['seo_review', 'draft'],
        'seo_review' => ['approved', 'draft'],
        'approved' => ['scheduled', 'draft', 'published'],
        'scheduled' => ['published', 'draft'],
        'published' => ['archived', 'draft'],
        'archived' => ['draft'],
    ];

    public function creating(Post $post): void
    {
        if (empty($post->uuid)) {
            $post->uuid = (string) Str::uuid();
        }

        if (!empty($post->content)) {
            $post->word_count = str_word_count(strip_tags($post->content));
            $post->reading_time = max(1, (int) ceil($post->word_count / 200));
        }
    }

    public function updated(Post $post): void
    {
        if ($post->isDirty('status')) {
            $oldStatus = $post->getOriginal('status');
            $newStatus = $post->status;

            $this->validateTransition($oldStatus, $newStatus);

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
        if (!method_exists($post, 'activity')) {
            return;
        }

        activity()
            ->performedOn($post)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ])
            ->log("Post status changed from {$oldStatus} to {$newStatus}");
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
