<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\LikeController;
use App\Http\Controllers\Api\V1\BookmarkController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\PostController as AdminPostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    
    // Public content routes
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post:slug}', [PostController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category:slug}/posts', [CategoryController::class, 'posts']);
    Route::get('tags', [TagController::class, 'index']);
    Route::get('tags/{tag:slug}/posts', [TagController::class, 'posts']);
    Route::get('search', [SearchController::class, 'search']);
    Route::get('search/suggest', [SearchController::class, 'suggest']);
    
    // Public user profile
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::get('users/{id}/posts', [UserController::class, 'posts']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('auth/resend-verification', [AuthController::class, 'resendVerification']);
        
        // Posts
        Route::get('user/posts', [PostController::class, 'userPosts']);
        Route::apiResource('posts', PostController::class)
            ->except(['index', 'show']);
        
        // Comments
        Route::get('posts/{post}/comments', [CommentController::class, 'index']);
        Route::post('posts/{post}/comments', [CommentController::class, 'store']);
        Route::put('comments/{comment}', [CommentController::class, 'update']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
        
        // Likes & Bookmarks
        Route::post('posts/{post}/like', [LikeController::class, 'toggle']);
        Route::post('posts/{post}/bookmark', [BookmarkController::class, 'toggle']);
        Route::get('user/bookmarks', [BookmarkController::class, 'index']);
        Route::delete('user/bookmarks/{post}', [BookmarkController::class, 'destroy']);
        
        // User
        Route::get('user/profile', [UserController::class, 'profile']);
        Route::put('user/profile', [UserController::class, 'updateProfile']);
        Route::put('user/password', [UserController::class, 'updatePassword']);
        
        // Admin routes
        Route::middleware('can:admin')->group(function () {
            Route::prefix('admin')->group(function () {
                // Dashboard
                Route::get('dashboard', [DashboardController::class, 'index']);
                Route::get('analytics', [DashboardController::class, 'analytics']);
                
                // User management
                Route::apiResource('users', AdminUserController::class)
                    ->except(['create', 'store', 'edit']);
                
                // Post management
                Route::get('posts', [AdminPostController::class, 'index']);
                Route::get('posts/{post}', [AdminPostController::class, 'show']);
                Route::put('posts/{post}', [AdminPostController::class, 'update']);
                Route::delete('posts/{post}', [AdminPostController::class, 'destroy']);
                Route::post('posts/{post}/publish', [AdminPostController::class, 'publish']);
                Route::post('posts/{post}/unpublish', [AdminPostController::class, 'unpublish']);
                Route::post('posts/{post}/feature', [AdminPostController::class, 'feature']);
                
                // Comment moderation
                Route::get('comments/pending', [CommentController::class, 'pending']);
                Route::post('comments/{comment}/approve', [CommentController::class, 'approve']);
                Route::post('comments/{comment}/reject', [CommentController::class, 'reject']);
                
                // Category management
                Route::post('categories', [CategoryController::class, 'store']);
                Route::put('categories/{category}', [CategoryController::class, 'update']);
                Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
                
                // Tag management
                Route::post('tags', [TagController::class, 'store']);
                Route::put('tags/{tag}', [TagController::class, 'update']);
                Route::delete('tags/{tag}', [TagController::class, 'destroy']);
            });
        });
    });
});
