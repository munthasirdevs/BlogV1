<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('posts', App\Http\Controllers\Admin\PostController::class);
    Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class);
    Route::resource('tags', App\Http\Controllers\Admin\TagController::class);
    Route::resource('media', App\Http\Controllers\Admin\MediaController::class);
    Route::resource('comments', App\Http\Controllers\Admin\CommentController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::post('comments/{id}/approve', [App\Http\Controllers\Admin\CommentController::class, 'approve'])->name('comments.approve');
    Route::post('comments/{id}/reject', [App\Http\Controllers\Admin\CommentController::class, 'reject'])->name('comments.reject');
    Route::post('comments/{id}/spam', [App\Http\Controllers\Admin\CommentController::class, 'markSpam'])->name('comments.spam');
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

    Route::get('analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
        Route::put('/', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('update');
    });
});
