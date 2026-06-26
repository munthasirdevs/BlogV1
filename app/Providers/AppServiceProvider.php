<?php

namespace App\Providers;

use App\Events\CommentSubmitted;
use App\Events\PostPublished;
use App\Events\PostWorkflowChanged;
use App\Listeners\SendCommentSubmittedNotification;
use App\Listeners\SendPostPublishedNotification;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Observers\CategoryObserver;
use App\Observers\CommentObserver;
use App\Observers\PostObserver;
use App\Observers\TagObserver;
use App\Services\AI\AIService;
use App\Services\CacheService;
use App\Services\TagService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheService::class, function () {
            return new CacheService();
        });

        $this->app->singleton(TagService::class, function ($app) {
            return new TagService($app->make(CacheService::class), $app->make(AIService::class));
        });
    }

    public function boot(): void
    {
        Event::listen(PostPublished::class, SendPostPublishedNotification::class);
        Event::listen(CommentSubmitted::class, SendCommentSubmittedNotification::class);

        Post::observe(PostObserver::class);
        Tag::observe(TagObserver::class);
        Comment::observe(CommentObserver::class);

        $catObserver = new CategoryObserver();
        Post::created(fn($p) => $catObserver->postCreated($p));
        Post::updated(fn($p) => $catObserver->postUpdated($p));
        Post::deleted(fn($p) => $catObserver->postDeleted($p));
        Category::observe(CategoryObserver::class);
    }
}
