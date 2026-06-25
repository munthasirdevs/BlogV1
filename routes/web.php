<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/blog');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Blog routes
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [App\Http\Controllers\Public\BlogController::class, 'index'])->name('index');
    Route::get('/{slug}', [App\Http\Controllers\Public\BlogController::class, 'show'])->name('show');
});

Route::prefix('category')->name('category.')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Public\CategoryController::class, 'show'])->name('show');
});

Route::prefix('tag')->name('tag.')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Public\TagController::class, 'show'])->name('show');
});

Route::get('/author/{username}', [App\Http\Controllers\Public\AuthorController::class, 'show'])->name('author.show');

Route::get('/search', [App\Http\Controllers\Public\SearchController::class, 'index'])->name('search');

Route::get('/contact', [App\Http\Controllers\Public\ContactController::class, 'show'])->name('contact');
Route::post('/contact', [App\Http\Controllers\Public\ContactController::class, 'submit'])->name('contact.submit');

Route::get('/about', [App\Http\Controllers\Public\PageController::class, 'about'])->name('about');
Route::get('/privacy', [App\Http\Controllers\Public\PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [App\Http\Controllers\Public\PageController::class, 'terms'])->name('terms');

Route::post('/comments', [App\Http\Controllers\Public\CommentController::class, 'store'])->name('public.comments.store');

Route::post('/newsletter/subscribe', [App\Http\Controllers\Public\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/verify/{token}', [App\Http\Controllers\Public\NewsletterController::class, 'verify'])->name('newsletter.verify');

require __DIR__.'/admin.php';
