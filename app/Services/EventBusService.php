<?php

namespace App\Services;

use App\Models\EventStore;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EventBusService
{
    public function dispatch(string $eventType, array $payload, ?string $source = null): EventStore
    {
        $event = EventStore::create([
            'tenant_id' => tenant_id(),
            'event_type' => $eventType,
            'payload' => $payload,
            'source' => $source ?? $this->detectSource(),
            'correlation_id' => (string) Str::uuid(),
            'status' => 'pending',
        ]);

        $this->processEvent($event);

        return $event;
    }

    public function dispatchAsync(string $eventType, array $payload, ?string $source = null): EventStore
    {
        $event = EventStore::create([
            'tenant_id' => tenant_id(),
            'event_type' => $eventType,
            'payload' => $payload,
            'source' => $source ?? $this->detectSource(),
            'correlation_id' => (string) Str::uuid(),
            'status' => 'pending',
        ]);

        Log::channel('observability')->info('Event queued for async processing', [
            'event_type' => $eventType,
            'event_id' => $event->event_id,
        ]);

        return $event;
    }

    protected function processEvent(EventStore $event): void
    {
        try {
            $event->update(['status' => 'processing']);

            match ($event->event_type) {
                'post.created' => $this->handlePostCreated($event),
                'post.published' => $this->handlePostPublished($event),
                'media.uploaded' => $this->handleMediaUploaded($event),
                'search.executed' => $this->handleSearchExecuted($event),
                default => Log::debug('Unhandled event type', ['type' => $event->event_type]),
            };

            $event->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $event->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $event->retry_count + 1,
            ]);
            Log::error('Event processing failed', [
                'event_id' => $event->event_id,
                'type' => $event->event_type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function replayByType(string $eventType, ?int $limit = 100): int
    {
        $events = EventStore::byType($eventType)->pending()
            ->orderBy('created_at')
            ->take($limit)
            ->get();

        foreach ($events as $event) {
            $this->processEvent($event);
        }

        return $events->count();
    }

    public function replayFailed(?int $limit = 50): int
    {
        $events = EventStore::failed()->orderBy('created_at')->take($limit)->get();
        foreach ($events as $event) {
            $event->update(['status' => 'pending', 'error_message' => null]);
            $this->processEvent($event);
        }
        return $events->count();
    }

    public function getStats(): array
    {
        return [
            'total' => EventStore::count(),
            'pending' => EventStore::pending()->count(),
            'processing' => EventStore::where('status', 'processing')->count(),
            'completed' => EventStore::where('status', 'completed')->count(),
            'failed' => EventStore::failed()->count(),
            'recent_24h' => EventStore::recent(1440)->count(),
        ];
    }

    protected function handlePostCreated(EventStore $event): void
    {
        Log::info('Post created event', ['post_id' => $event->payload['post_id'] ?? null]);
    }

    protected function handlePostPublished(EventStore $event): void
    {
        $postId = $event->payload['post_id'] ?? null;
        if ($postId) {
            try {
                \App\Events\PostPublished::dispatch(\App\Models\Post::find($postId));
            } catch (\Exception $e) {
                Log::warning('PostPublished event dispatch failed', ['post_id' => $postId]);
            }
        }
    }

    protected function handleMediaUploaded(EventStore $event): void
    {
        Log::info('Media uploaded', ['media_id' => $event->payload['media_id'] ?? null]);
    }

    protected function handleSearchExecuted(EventStore $event): void
    {
        Log::info('Search executed', ['keyword' => $event->payload['keyword'] ?? null]);
    }

    protected function detectSource(): string
    {
        if (app()->runningInConsole()) return 'cli';
        return request()->path() ?? 'unknown';
    }
}
