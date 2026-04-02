<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // Legacy admin check middleware
            'can.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            
            // Spatie Permission middleware
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withProviders([
        \App\Providers\RepositoryServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            // Delegate to custom handler
            $handler = new \App\Exceptions\Handler(app());
            return $handler->render($request, $e);
        });

        $exceptions->report(function (Throwable $e) {
            $handler = new \App\Exceptions\Handler(app());
            $handler->report($e);
        });
    })
    ->create();
