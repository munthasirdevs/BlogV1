<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/auth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\TrackPageView::class);
        $middleware->append(\App\Http\Middleware\ApplySecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\MeasureRequestTime::class);
        $middleware->alias([
            'security.headers' => \App\Http\Middleware\ApplySecurityHeaders::class,
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'tenant.validate' => \App\Http\Middleware\ValidateTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Not Found'], 404);
            }
            return response()->view('errors.404', [], 404);
        });
    })->create();
