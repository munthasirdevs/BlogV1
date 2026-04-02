<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class RepositoryServiceProvider
 *
 * Registers repository bindings for dependency injection.
 * Binds repository interfaces to their concrete implementations.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository bindings.
     *
     * Format: [Interface::class => Repository::class]
     */
    protected array $repositories = [
        // User
        // \App\Repositories\Contracts\UserRepositoryInterface::class => \App\Repositories\UserRepository::class,
        
        // Post
        // \App\Repositories\Contracts\PostRepositoryInterface::class => \App\Repositories\PostRepository::class,
        
        // Category
        // \App\Repositories\Contracts\CategoryRepositoryInterface::class => \App\Repositories\CategoryRepository::class,
        
        // Tag
        // \App\Repositories\Contracts\TagRepositoryInterface::class => \App\Repositories\TagRepository::class,
        
        // Comment
        // \App\Repositories\Contracts\CommentRepositoryInterface::class => \App\Repositories\CommentRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerRepositories();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register repository bindings.
     */
    protected function registerRepositories(): void
    {
        foreach ($this->repositories as $interface => $repository) {
            $this->app->bind($interface, $repository);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return array_values($this->repositories);
    }
}
