<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public content endpoints
    Route::get('/posts', [\App\Http\Controllers\Api\PostController::class, 'index']);
    Route::get('/posts/{post}', [\App\Http\Controllers\Api\PostController::class, 'show']);
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
    Route::get('/tags', [\App\Http\Controllers\Api\TagController::class, 'index']);
    Route::get('/tags/{tag}', [\App\Http\Controllers\Api\TagController::class, 'show']);
    Route::get('/search', [\App\Http\Controllers\Api\SearchController::class, 'search']);

    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('posts', \App\Http\Controllers\Api\PostController::class)->except(['index', 'show']);

        // Media
        Route::post('/media/upload', [\App\Http\Controllers\Api\MediaController::class, 'upload']);

        // AI
        Route::post('/ai/generate', [\App\Http\Controllers\Api\AIController::class, 'generate']);
        Route::post('/ai/seo', [\App\Http\Controllers\Api\SeoController::class, 'analyze']);

        // Analytics
        Route::get('/analytics/dashboard', [\App\Http\Controllers\Api\AnalyticsController::class, 'dashboard']);

        // Billing
        Route::get('/billing/subscription', [\App\Http\Controllers\Api\BillingController::class, 'subscription']);
        Route::get('/billing/invoices', [\App\Http\Controllers\Api\BillingController::class, 'invoices']);

        // Webhooks
        Route::apiResource('webhooks', \App\Http\Controllers\Api\WebhookController::class);
    });
});
