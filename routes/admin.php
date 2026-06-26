<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('posts', App\Http\Controllers\Admin\PostController::class);
    Route::post('posts/{post}/duplicate', [App\Http\Controllers\Admin\PostController::class, 'duplicate'])->name('posts.duplicate');
    Route::post('posts/{post}/restore', [App\Http\Controllers\Admin\PostController::class, 'restore'])->name('posts.restore');
    Route::post('posts/bulk-delete', [App\Http\Controllers\Admin\PostController::class, 'bulkDelete'])->name('posts.bulk-delete');
    Route::post('posts/bulk-status', [App\Http\Controllers\Admin\PostController::class, 'bulkStatus'])->name('posts.bulk-status');
    Route::post('posts/bulk-feature', [App\Http\Controllers\Admin\PostController::class, 'bulkFeature'])->name('posts.bulk-feature');
    Route::post('posts/bulk-schedule', [App\Http\Controllers\Admin\PostController::class, 'bulkSchedule'])->name('posts.bulk-schedule');
    Route::get('posts/export/csv', [App\Http\Controllers\Admin\PostController::class, 'exportCsv'])->name('posts.export.csv');
    Route::get('posts/export/json', [App\Http\Controllers\Admin\PostController::class, 'exportJson'])->name('posts.export.json');
    Route::post('posts/import/csv', [App\Http\Controllers\Admin\PostController::class, 'importCsv'])->name('posts.import.csv');
    Route::post('posts/{post}/reject', [App\Http\Controllers\Admin\PostController::class, 'reject'])->name('posts.reject');
    Route::post('posts/{post}/approve', [App\Http\Controllers\Admin\PostController::class, 'approve'])->name('posts.approve');
    Route::post('posts/ai-improve', [App\Http\Controllers\Admin\PostController::class, 'aiImprove'])->name('posts.ai-improve');
    Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class);
    Route::post('categories/bulk-delete', [App\Http\Controllers\Admin\CategoryController::class, 'bulkDelete'])->name('categories.bulk-delete');
    Route::post('categories/bulk-restore', [App\Http\Controllers\Admin\CategoryController::class, 'bulkRestore'])->name('categories.bulk-restore');
    Route::post('categories/bulk-move', [App\Http\Controllers\Admin\CategoryController::class, 'bulkMove'])->name('categories.bulk-move');
    Route::post('categories/bulk-status', [App\Http\Controllers\Admin\CategoryController::class, 'bulkStatus'])->name('categories.bulk-status');
    Route::post('categories/{id}/restore', [App\Http\Controllers\Admin\CategoryController::class, 'restore'])->name('categories.restore');
    Route::post('categories/{id}/duplicate', [App\Http\Controllers\Admin\CategoryController::class, 'duplicate'])->name('categories.duplicate');
    Route::get('categories/export/csv', [App\Http\Controllers\Admin\CategoryController::class, 'exportCsv'])->name('categories.export.csv');
    Route::post('categories/import/csv', [App\Http\Controllers\Admin\CategoryController::class, 'importCsv'])->name('categories.import.csv');
    Route::prefix('posts/{post}/revisions')->name('posts.revisions.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PostRevisionController::class, 'index'])->name('index');
        Route::get('/{revision}', [App\Http\Controllers\Admin\PostRevisionController::class, 'show'])->name('show');
        Route::patch('/{revision}/restore', [App\Http\Controllers\Admin\PostRevisionController::class, 'restore'])->name('restore');
    });
    Route::resource('tags', App\Http\Controllers\Admin\TagController::class);
    Route::post('tags/merge', [App\Http\Controllers\Admin\TagController::class, 'merge'])->name('tags.merge');
    Route::post('tags/bulk-delete', [App\Http\Controllers\Admin\TagController::class, 'bulkDelete'])->name('tags.bulk-delete');
    Route::post('tags/bulk-status', [App\Http\Controllers\Admin\TagController::class, 'bulkStatus'])->name('tags.bulk-status');
    Route::post('tags/{id}/restore', [App\Http\Controllers\Admin\TagController::class, 'restore'])->name('tags.restore');
    Route::get('tags/export/csv', [App\Http\Controllers\Admin\TagController::class, 'exportCsv'])->name('tags.export.csv');
    Route::post('tags/import/csv', [App\Http\Controllers\Admin\TagController::class, 'importCsv'])->name('tags.import.csv');
    Route::post('content-graph/rebuild', [App\Http\Controllers\Admin\ContentGraphController::class, 'rebuild'])->name('content-graph.rebuild');
    Route::post('content-graph/suggest/{post}', [App\Http\Controllers\Admin\ContentGraphController::class, 'suggest'])->name('content-graph.suggest');
    Route::get('content-graph/orphans', [App\Http\Controllers\Admin\ContentGraphController::class, 'orphans'])->name('content-graph.orphans');
    Route::get('search/autocomplete', [App\Http\Controllers\Admin\SearchController::class, 'autocomplete'])->name('search.autocomplete');
    Route::get('search/trending', [App\Http\Controllers\Admin\SearchController::class, 'trending'])->name('search.trending');
    Route::get('search/popular', [App\Http\Controllers\Admin\SearchController::class, 'popular'])->name('search.popular');
    Route::get('search/zero-results', [App\Http\Controllers\Admin\SearchController::class, 'zeroResults'])->name('search.zero-results');
    Route::prefix('commands')->name('commands.')->group(function () {
        Route::post('flush-cache', [App\Http\Controllers\Admin\CommandController::class, 'flushCache'])->name('flush-cache');
        Route::post('warm-cache', [App\Http\Controllers\Admin\CommandController::class, 'warmCache'])->name('warm-cache');
        Route::post('rebuild-search', [App\Http\Controllers\Admin\CommandController::class, 'rebuildSearch'])->name('rebuild-search');
        Route::post('clear-logs', [App\Http\Controllers\Admin\CommandController::class, 'clearLogs'])->name('clear-logs');
        Route::post('optimize', [App\Http\Controllers\Admin\CommandController::class, 'optimize'])->name('optimize');
    });
    Route::get('system/info', [App\Http\Controllers\Admin\CommandController::class, 'systemInfo'])->name('system.info');
    Route::get('security', [App\Http\Controllers\Admin\SecurityController::class, 'dashboard'])->name('security.dashboard');
    Route::get('observability', [App\Http\Controllers\Admin\ObservabilityController::class, 'index'])->name('observability');
    Route::get('billing/dashboard', [App\Http\Controllers\Admin\BillingDashboardController::class, 'index'])->name('billing.dashboard');
    Route::get('tags/autocomplete', [App\Http\Controllers\Admin\TagController::class, 'autocomplete'])->name('tags.autocomplete');
    Route::post('tags/recalculate-trending', [App\Http\Controllers\Admin\TagController::class, 'recalculateTrending'])->name('tags.recalculate-trending');
    Route::post('media/upload-zip', [App\Http\Controllers\Admin\MediaController::class, 'uploadZip'])->name('media.upload-zip');
    Route::post('media/bulk-delete', [App\Http\Controllers\Admin\MediaController::class, 'bulkDelete'])->name('media.bulk-delete');
    Route::post('media/{medium}/ai-metadata', [App\Http\Controllers\Admin\MediaController::class, 'aiGenerateMetadata'])->name('media.ai-metadata');
    Route::resource('media', App\Http\Controllers\Admin\MediaController::class);

    Route::prefix('featured-images')->name('featured-images.')->group(function () {
        Route::post('upload', [App\Http\Controllers\Admin\FeaturedImageController::class, 'upload'])->name('upload');
        Route::delete('{id}', [App\Http\Controllers\Admin\FeaturedImageController::class, 'destroy'])->name('destroy');
        Route::post('{id}/ai-enrich', [App\Http\Controllers\Admin\FeaturedImageController::class, 'aiEnrich'])->name('ai-enrich');
        Route::get('{id}/og-image', [App\Http\Controllers\Admin\FeaturedImageController::class, 'ogImage'])->name('og-image');
    });
    Route::resource('comments', App\Http\Controllers\Admin\CommentController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::post('comments/{id}/approve', [App\Http\Controllers\Admin\CommentController::class, 'approve'])->name('comments.approve');
    Route::post('comments/{id}/reject', [App\Http\Controllers\Admin\CommentController::class, 'reject'])->name('comments.reject');
    Route::post('comments/{id}/spam', [App\Http\Controllers\Admin\CommentController::class, 'markSpam'])->name('comments.spam');
    Route::post('comments/{comment}/react', [App\Http\Controllers\Admin\CommentController::class, 'react'])->name('comments.react');
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);

    Route::prefix('seo')->name('seo.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SEOController::class, 'index'])->name('index');
        Route::get('edit/{id}', [App\Http\Controllers\Admin\SEOController::class, 'edit'])->name('edit');
        Route::put('update/{id}', [App\Http\Controllers\Admin\SEOController::class, 'update'])->name('update');
        Route::get('analyze/{postId}', [App\Http\Controllers\Admin\SEOController::class, 'analyze'])->name('analyze');
        Route::resource('redirects', App\Http\Controllers\Admin\RedirectController::class);
        Route::post('generate-sitemap', [App\Http\Controllers\Admin\SEOController::class, 'generateSitemap'])->name('sitemap.generate');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
        Route::post('{id}/mark-read', [App\Http\Controllers\Admin\NotificationController::class, 'markRead'])->name('markRead');
        Route::post('mark-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])->name('markAllRead');
        Route::get('count', [App\Http\Controllers\Admin\NotificationController::class, 'count'])->name('count');
    });

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AIController::class, 'index'])->name('index');
        Route::post('generate', [App\Http\Controllers\Admin\AIController::class, 'generate'])->name('generate');
        Route::get('history', [App\Http\Controllers\Admin\AIController::class, 'history'])->name('history');
    });

    Route::get('workflow', [App\Http\Controllers\Admin\WorkflowController::class, 'index'])->name('workflow');
    Route::post('posts/autosave', [App\Http\Controllers\Admin\WorkflowController::class, 'autosave'])->name('posts.autosave');
    Route::post('posts/score', [App\Http\Controllers\Admin\WorkflowController::class, 'score'])->name('posts.score');

    Route::get('analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('analytics/export/csv', [App\Http\Controllers\Admin\AnalyticsController::class, 'exportCsv'])->name('analytics.export.csv');
    Route::get('analytics/posts/csv', [App\Http\Controllers\Admin\AnalyticsController::class, 'postsCsv'])->name('analytics.posts.csv');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
        Route::put('/', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('update');
    });

    Route::resource('tenants', App\Http\Controllers\Admin\TenantController::class);
    Route::post('tenants/{tenant}/suspend', [App\Http\Controllers\Admin\TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('tenants/{tenant}/activate', [App\Http\Controllers\Admin\TenantController::class, 'activate'])->name('tenants.activate');

    Route::resource('plugins', App\Http\Controllers\Admin\PluginController::class)->only(['index', 'show']);
    Route::post('plugins/register', [App\Http\Controllers\Admin\PluginController::class, 'register'])->name('plugins.register');
    Route::post('plugins/{plugin}/enable', [App\Http\Controllers\Admin\PluginController::class, 'enable'])->name('plugins.enable');
    Route::post('plugins/{plugin}/disable', [App\Http\Controllers\Admin\PluginController::class, 'disable'])->name('plugins.disable');
});
