<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\LikeController;
use App\Http\Controllers\Api\V1\BookmarkController;
use App\Http\Controllers\Api\V1\ShareController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Middleware\RequestLoggingMiddleware;
use App\Http\Middleware\ApiVersionMiddleware;
use App\Http\Middleware\ViewTrackingMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are versioned under /api/v1/ prefix.
| Version header support: Accept: application/vnd.blog.v1+json
|
| Rate Limiting:
| - Public routes: 60 requests/minute
| - Authenticated routes: 120 requests/minute
| - Admin routes: 200 requests/minute
| - Authentication routes: 10 requests/minute
|
*/

// API Version Header Middleware
Route::prefix('v1')->middleware([ApiVersionMiddleware::class, RequestLoggingMiddleware::class])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes (No Authentication Required)
    |--------------------------------------------------------------------------
    | Rate limit: 60 requests per minute
    */

    Route::middleware(['throttle:60,1', RequestLoggingMiddleware::class])->group(function () {

        // Health check
        Route::get('health', fn() => response()->json([
            'success' => true,
            'data' => ['status' => 'healthy', 'timestamp' => now()->toISOString()],
        ]));

        // Authentication Routes
        Route::prefix('auth')->group(function () {
            // Registration - 10 attempts per minute
            Route::post('register', [AuthController::class, 'register'])
                ->middleware('throttle:10,1');

            // Login - 10 attempts per minute
            Route::post('login', [AuthController::class, 'login'])
                ->middleware('throttle:10,1');

            // Password reset request - 5 attempts per minute
            Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
                ->middleware('throttle:5,1');

            // Password reset confirmation - 5 attempts per minute
            Route::post('reset-password', [AuthController::class, 'resetPassword'])
                ->middleware('throttle:5,1');

            // Email verification (signed URL)
            Route::get('verify/{id}/{hash}', [AuthController::class, 'verify'])
                ->name('auth.verify')
                ->middleware('throttle:10,1');

            // Email verification (token-based) - 5 attempts per minute
            Route::post('verify-email', [AuthController::class, 'verifyEmail'])
                ->middleware('throttle:5,1');

            // Resend verification email - 3 attempts per minute
            Route::post('resend-verification', [AuthController::class, 'resendVerification'])
                ->middleware('throttle:3,1');
        });

        // Public Content Routes - Posts
        Route::prefix('posts')->group(function () {
            Route::get('/', [PostController::class, 'index']); // List published posts
            Route::get('/trending', [PostController::class, 'trending']); // Trending posts
            Route::get('/featured', [PostController::class, 'featured']); // Featured posts
            Route::get('/search', [PostController::class, 'search']); // Search posts
            Route::get('/{post:slug}', [PostController::class, 'show']); // Get single post
            Route::get('/{post}/author', [PostController::class, 'author']); // Get post author
            Route::get('/{post}/related', [PostController::class, 'related']); // Get related posts
        });

        // Public Content Routes - Categories
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']); // List categories
            Route::get('/tree', [CategoryController::class, 'tree']); // Get category tree
            Route::get('/{category:slug}', [CategoryController::class, 'show']); // Get single category
            Route::get('/{category:slug}/posts', [CategoryController::class, 'posts']); // Get posts by category
        });

        // Public Content Routes - Tags
        Route::prefix('tags')->group(function () {
            Route::get('/', [TagController::class, 'index']); // List tags
            Route::get('/popular', [TagController::class, 'popular']); // Get popular tags
            Route::get('/suggest', [TagController::class, 'suggest']); // Get tag suggestions
            Route::get('/cloud', [TagController::class, 'cloud']); // Get tag cloud
            Route::get('/{tag:slug}', [TagController::class, 'show']); // Get single tag
            Route::get('/{tag:slug}/posts', [TagController::class, 'posts']); // Get posts by tag
        });

        // Public Content Routes - Users
        Route::prefix('users')->group(function () {
            Route::get('/{id}', [UserController::class, 'show']); // Public user profile
            Route::get('/{id}/posts', [UserController::class, 'posts']); // User's published posts
        });

        // Search Routes
        Route::prefix('search')->group(function () {
            Route::get('/', [SearchController::class, 'search']); // Global search
            Route::get('/suggest', [SearchController::class, 'suggest']); // Search suggestions
        });

        // Comments on published posts (read-only)
        Route::get('posts/{post}/comments', [CommentController::class, 'index']);
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Authentication Required)
    |--------------------------------------------------------------------------
    | Rate limit: 120 requests per minute
    */

    Route::middleware(['auth:sanctum', 'throttle:120,1', RequestLoggingMiddleware::class])->group(function () {

        // Auth endpoints
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
            Route::get('sessions', [AuthController::class, 'sessions']);
            Route::delete('sessions/{tokenId}', [AuthController::class, 'revokeSession']);
            Route::post('verification-notification', [AuthController::class, 'resendVerificationAuthenticated']);
        });

        // User's own posts
        Route::prefix('user')->group(function () {
            Route::get('posts', [PostController::class, 'userPosts']); // Get user's posts
            Route::get('bookmarks', [BookmarkController::class, 'index']); // Get user's bookmarks
            Route::delete('bookmarks/{post}', [BookmarkController::class, 'destroy']); // Remove bookmark
            Route::get('profile', [UserController::class, 'profile']); // Get user profile
            Route::put('profile', [UserController::class, 'updateProfile']); // Update profile
            Route::put('password', [UserController::class, 'updatePassword']); // Update password
        });

        // Posts CRUD (for own posts)
        Route::apiResource('posts', PostController::class)
            ->except(['index', 'show']);

        // Additional post routes
        Route::prefix('posts')->group(function () {
            Route::post('/bulk-actions', [PostController::class, 'bulkActions']); // Bulk actions
            Route::get('/{post}/preview', [PostController::class, 'preview']); // Get preview URL
            Route::post('/{post}/autosave', [PostController::class, 'autosave']); // Auto-save draft
            Route::post('/{post}/publish', [PostController::class, 'publish']); // Publish post
            Route::post('/{post}/unpublish', [PostController::class, 'unpublish']); // Unpublish post
            Route::post('/{post}/feature', [PostController::class, 'feature']); // Feature post
            Route::delete('/{post}/feature', [PostController::class, 'unfeature']); // Unfeature post
            Route::post('/{post}/restore', [PostController::class, 'restore']); // Restore deleted post
            Route::get('/counts', [PostController::class, 'counts']); // Get post counts by status
            
            // Post tag management
            Route::post('/{post}/tags', [TagController::class, 'attachToPost']); // Attach tags to post
            Route::delete('/{post}/tags/{tag}', [TagController::class, 'detachFromPost']); // Detach tag from post
        });

        // Comments (CRUD for own comments)
        Route::prefix('posts')->group(function () {
            Route::post('{post}/comments', [CommentController::class, 'store']); // Create comment
            Route::get('{post}/comments', [CommentController::class, 'index']); // Get comments
        });
        Route::apiResource('comments', CommentController::class)
            ->only(['show', 'update', 'destroy']);
        Route::get('comments/{comment}/replies', [CommentController::class, 'replies']); // Get replies
        Route::get('comments/{comment}/edits', [CommentController::class, 'editHistory']); // Get edit history
        Route::get('comments/mentions/suggest', [CommentController::class, 'mentionSuggestions']); // Mention suggestions

        // ==============================
        // User Interactions Routes
        // ==============================

        // Likes
        Route::prefix('posts')->group(function () {
            Route::post('{post}/like', [LikeController::class, 'togglePostLike']); // Toggle like on post
            Route::get('{post}/likes', [LikeController::class, 'getPostLikes']); // Get post likers
        });
        Route::prefix('comments')->group(function () {
            Route::post('{comment}/like', [LikeController::class, 'toggleCommentLike']); // Toggle like on comment
            Route::get('{comment}/likes', [LikeController::class, 'getCommentLikes']); // Get comment likers
        });
        Route::prefix('users')->group(function () {
            Route::get('{id}/likes/posts', [LikeController::class, 'getUserLikedPosts']); // User's liked posts
            Route::get('{id}/likes/comments', [LikeController::class, 'getUserLikedComments']); // User's liked comments
        });

        // Bookmarks
        Route::prefix('posts')->group(function () {
            Route::post('{post}/bookmark', [BookmarkController::class, 'toggle']); // Toggle bookmark
        });
        Route::prefix('user')->group(function () {
            Route::get('bookmarks', [BookmarkController::class, 'index']); // Get user's bookmarks
            Route::delete('bookmarks/{post}', [BookmarkController::class, 'destroy']); // Remove bookmark
            Route::get('bookmarks/search', [BookmarkController::class, 'search']); // Search bookmarks
            Route::get('bookmarks/stats', [BookmarkController::class, 'getStats']); // Bookmark stats
        });
        Route::prefix('bookmarks')->group(function () {
            Route::get('collections', [BookmarkController::class, 'getCollections']); // Get collections
            Route::post('collections', [BookmarkController::class, 'createCollection']); // Create collection
            Route::put('collections/{collection}', [BookmarkController::class, 'updateCollection']); // Update collection
            Route::delete('collections/{collection}', [BookmarkController::class, 'deleteCollection']); // Delete collection
            Route::get('collection/{collection}', [BookmarkController::class, 'getBookmarksByCollection']); // Get by collection
            Route::post('{bookmark}/collection', [BookmarkController::class, 'assignCollection']); // Assign to collection
            Route::get('{bookmark}/collection', [BookmarkController::class, 'getBookmarkCollection']); // Get bookmark's collection
            Route::put('{bookmark}/notes', [BookmarkController::class, 'updateNotes']); // Update notes
        });

        // Shares
        Route::prefix('posts')->group(function () {
            Route::post('{post}/share', [ShareController::class, 'trackShare']); // Track share
            Route::get('{post}/shares', [ShareController::class, 'getShares']); // Get shares
            Route::get('{post}/share-count', [ShareController::class, 'getShareCount']); // Get share count
            Route::get('{post}/share-url', [ShareController::class, 'generateShareUrl']); // Generate share URL
            Route::get('{post}/share-stats', [ShareController::class, 'getStatistics']); // Share statistics
            Route::get('{post}/share-analytics', [ShareController::class, 'getAnalytics']); // Share analytics
        });
        Route::get('shares/providers', [ShareController::class, 'getProviders']); // Get share providers
        Route::get('shares/trending', [ShareController::class, 'getTrendingPosts']); // Trending by shares
        Route::get('user/shares', [ShareController::class, 'getUserShares']); // User's shares

        // Reading Progress
        Route::prefix('posts')->group(function () {
            Route::post('{post}/progress', [PostController::class, 'updateReadingProgress']); // Update progress
            Route::get('{post}/progress', [PostController::class, 'getReadingProgress']); // Get progress
        });
        Route::prefix('user')->group(function () {
            Route::get('reading/stats', [PostController::class, 'getReadingStats']); // Reading stats
            Route::get('reading/history', [PostController::class, 'getReadingHistory']); // Reading history
        });

        // Media Management Routes
        Route::prefix('media')->group(function () {
            // Upload endpoints
            Route::post('upload', [MediaController::class, 'upload']); // Single file upload
            Route::post('upload-multiple', [MediaController::class, 'uploadMultiple']); // Bulk upload (max 10)
            
            // Media library
            Route::get('/', [MediaController::class, 'index']); // List all media
            Route::get('/search', [MediaController::class, 'search']); // Search media
            Route::get('/statistics', [MediaController::class, 'statistics']); // Get statistics
            Route::get('/storage-usage', [MediaController::class, 'storageUsage']); // Get user's storage usage
            
            // Individual media operations
            Route::get('/{id}', [MediaController::class, 'show']); // Get media details
            Route::get('/{id}/url', [MediaController::class, 'url']); // Get media URL
            Route::get('/{id}/usage', [MediaController::class, 'usage']); // Get media usage
            Route::put('/{id}', [MediaController::class, 'update']); // Update metadata
            Route::delete('/{id}', [MediaController::class, 'destroy']); // Soft delete
            Route::post('/{id}/restore', [MediaController::class, 'restore']); // Restore deleted
            Route::post('/{id}/regenerate-thumbnails', [MediaController::class, 'regenerateThumbnails']); // Regenerate thumbnails
        });

        // ==============================
        // Notification Routes
        // ==============================
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']); // List notifications
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']); // Unread count for badge
            Route::get('/stats', [NotificationController::class, 'stats']); // Notification statistics
            Route::get('/{id}', [NotificationController::class, 'show']); // Get single notification
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']); // Mark as read
            Route::post('/{id}/unread', [NotificationController::class, 'markAsUnread']); // Mark as unread
            Route::delete('/{id}', [NotificationController::class, 'destroy']); // Delete notification
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']); // Mark all as read
            Route::post('/test', [NotificationController::class, 'sendTest']); // Send test notification (dev only)
        });

        // Notification Preferences
        Route::prefix('users/me')->group(function () {
            Route::get('/notification-preferences', [NotificationController::class, 'getPreferences']); // Get preferences
            Route::put('/notification-preferences', [NotificationController::class, 'updatePreferences']); // Update preferences
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Editor Routes (Editor or Admin Role Required)
    |--------------------------------------------------------------------------
    | Rate limit: 150 requests per minute
    */

    Route::middleware(['auth:sanctum', 'role:editor|admin', 'throttle:150,1', RequestLoggingMiddleware::class])->group(function () {

        // Editor can manage all posts
        Route::prefix('editor')->group(function () {
            // Post management
            Route::post('posts/{post}/publish', [PostController::class, 'publish']);
            Route::post('posts/{post}/unpublish', [PostController::class, 'unpublish']);
            Route::post('posts/{post}/feature', [PostController::class, 'feature']);

            // Comment moderation
            Route::get('comments/pending', [CommentController::class, 'pending']);
            Route::post('comments/{comment}/approve', [CommentController::class, 'approve']);
            Route::post('comments/{comment}/reject', [CommentController::class, 'reject']);
        });

        // Category management (Editor)
        Route::prefix('categories')->group(function () {
            Route::post('/', [CategoryController::class, 'store']); // Create category
            Route::put('/{category}', [CategoryController::class, 'update']); // Update category
            Route::post('/reorder', [CategoryController::class, 'reorder']); // Reorder categories
        });

        // Tag management (Editor)
        Route::prefix('tags')->group(function () {
            Route::post('/', [TagController::class, 'store']); // Create tag
            Route::put('/{tag}', [TagController::class, 'update']); // Update tag
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (Admin Role Required)
    |--------------------------------------------------------------------------
    | Rate limit: 200 requests per minute
    */

    Route::middleware(['auth:sanctum', 'role:admin', 'throttle:200,1', RequestLoggingMiddleware::class])->prefix('admin')->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('analytics', [DashboardController::class, 'analytics']);

        // User management
        Route::apiResource('users', AdminUserController::class)
            ->except(['create', 'store', 'edit']);

        // User role management
        Route::prefix('users')->group(function () {
            Route::post('/{user}/roles', [AdminUserController::class, 'assignRoles']);
            Route::delete('/{user}/roles/{role}', [AdminUserController::class, 'revokeRole']);
            Route::get('/{user}/permissions', [AdminUserController::class, 'permissions']);
            Route::post('/{user}/ban', [AdminUserController::class, 'ban']);
            Route::post('/{user}/unban', [AdminUserController::class, 'unban']);
        });

        // Post management
        Route::prefix('posts')->group(function () {
            Route::get('/', [AdminPostController::class, 'index']);
            Route::get('/{post}', [AdminPostController::class, 'show']);
            Route::put('/{post}', [AdminPostController::class, 'update']);
            Route::delete('/{post}', [AdminPostController::class, 'destroy']);
            Route::post('/{post}/publish', [AdminPostController::class, 'publish']);
            Route::post('/{post}/unpublish', [AdminPostController::class, 'unpublish']);
            Route::post('/{post}/feature', [AdminPostController::class, 'feature']);
        });

        // Category management (Admin - full CRUD including delete)
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/tree', [CategoryController::class, 'tree']);
            Route::get('/{category}', [CategoryController::class, 'show']);
            Route::get('/{category}/stats', [CategoryController::class, 'stats']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{category}', [CategoryController::class, 'update']);
            Route::post('/reorder', [CategoryController::class, 'reorder']);
            Route::delete('/{category}', [CategoryController::class, 'destroy']);
            Route::post('/{category}/delete-with-cascade', [CategoryController::class, 'destroyWithCascade']);
        });

        // Tag management (Admin - full CRUD including delete)
        Route::prefix('tags')->group(function () {
            Route::get('/', [TagController::class, 'index']);
            Route::get('/popular', [TagController::class, 'popular']);
            Route::get('/suggest', [TagController::class, 'suggest']);
            Route::get('/cloud', [TagController::class, 'cloud']);
            Route::get('/{tag}', [TagController::class, 'show']);
            Route::get('/{tag}/stats', [TagController::class, 'stats']);
            Route::post('/', [TagController::class, 'store']);
            Route::put('/{tag}', [TagController::class, 'update']);
            Route::delete('/{tag}', [TagController::class, 'destroy']);
        });

        // Comment moderation (admin)
        Route::prefix('comments')->group(function () {
            Route::get('/pending', [CommentController::class, 'pending']);
            Route::post('/{comment}/approve', [CommentController::class, 'approve']);
            Route::post('/{comment}/reject', [CommentController::class, 'reject']);
            Route::post('/{comment}/spam', [CommentController::class, 'markAsSpam']);
            Route::get('/search', [CommentController::class, 'search']); // Search comments
            Route::post('/bulk-moderate', [CommentController::class, 'bulkModerate']); // Bulk moderation
            Route::get('/statistics', [CommentController::class, 'statistics']); // Comment statistics
        });

        // Role & Permission Management
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::get('/permissions', [RoleController::class, 'permissions']);
            Route::get('/{role}/permissions', [RoleController::class, 'showRolePermissions']);
            Route::post('/{role}/permissions', [RoleController::class, 'assignPermissions']);
            Route::delete('/{role}/permissions', [RoleController::class, 'removePermissions']);
            Route::post('/', [RoleController::class, 'store']);
            Route::delete('/{role}', [RoleController::class, 'destroy']);
        });

        // Analytics Routes (Admin & Editor)
        Route::prefix('analytics')->group(function () {
            Route::get('overview', [AnalyticsController::class, 'overview']); // Dashboard summary
            Route::get('views', [AnalyticsController::class, 'views']); // Views over time
            Route::get('traffic', [AnalyticsController::class, 'traffic']); // Traffic data
            Route::get('posts', [AnalyticsController::class, 'posts']); // Post performance
            Route::get('posts/top', [AnalyticsController::class, 'topPosts']); // Top posts
            Route::get('engagement', [AnalyticsController::class, 'engagement']); // Engagement metrics
            Route::get('sources', [AnalyticsController::class, 'sources']); // Traffic sources
            Route::get('geo', [AnalyticsController::class, 'geo']); // Geographic data
            Route::get('devices', [AnalyticsController::class, 'devices']); // Device breakdown
            Route::get('realtime', [AnalyticsController::class, 'realtime']); // Real-time active users
            Route::get('audience', [AnalyticsController::class, 'audience']); // Audience insights
            Route::get('export', [AnalyticsController::class, 'export']); // Export data
            Route::post('cache/clear', [AnalyticsController::class, 'clearCache']); // Clear cache
            Route::post('cache/warm', [AnalyticsController::class, 'warmCache']); // Warm cache
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Newsletter & Subscription Routes
    |--------------------------------------------------------------------------
    | Public routes for subscription management and tracking.
    | Rate limit: 60 requests per minute for public, 120 for authenticated
    */

    // Public subscription routes
    Route::middleware(['throttle:60,1'])->group(function () {
        // Newsletter subscription
        Route::prefix('subscribe')->group(function () {
            Route::post('/', [SubscriptionController::class, 'subscribe']); // Subscribe
            Route::post('/confirm/{token}', [SubscriptionController::class, 'confirm'])->name('v1.subscribe.confirm'); // Confirm
            Route::post('/resend', [SubscriptionController::class, 'resendConfirmation']); // Resend confirmation
        });

        // Unsubscribe (public, no auth required)
        Route::post('/unsubscribe', [SubscriptionController::class, 'unsubscribe']);
        Route::get('/unsubscribe/{token}', [SubscriptionController::class, 'showUnsubscribePage']);

        // Email tracking (called from email clients)
        Route::prefix('track')->group(function () {
            Route::post('/open/{subscriberId}/{emailId}', [SubscriptionController::class, 'trackOpen']); // Track open
            Route::get('/click/{subscriberId}/{linkId}', [SubscriptionController::class, 'trackClick']); // Track click
        });

        // Webhooks for email providers
        Route::prefix('webhooks/mail')->group(function () {
            Route::post('/bounce', [WebhookController::class, 'handleMailgunBounce']); // Mailgun bounce
            Route::post('/complaint', [WebhookController::class, 'handleSendgridWebhook']); // SendGrid events
            Route::post('/generic-bounce', [WebhookController::class, 'handleGenericBounce']); // Generic bounce
            Route::post('/generic-complaint', [WebhookController::class, 'handleGenericComplaint']); // Generic complaint
        });
    });

    // Authenticated subscription routes (for managing own preferences)
    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::prefix('subscriptions')->group(function () {
            Route::put('/preferences', [SubscriptionController::class, 'updatePreferences']); // Update preferences
            Route::post('/export', [SubscriptionController::class, 'exportData']); // Export data (GDPR)
            Route::delete('/delete', [SubscriptionController::class, 'deleteData']); // Delete data (GDPR)
        });
    });

    // Admin subscription management routes
    Route::middleware(['auth:sanctum', 'role:admin', 'throttle:200,1'])->prefix('admin')->group(function () {
        Route::prefix('subscribers')->group(function () {
            Route::get('/segments', [SubscriptionController::class, 'segments']); // Get segment counts
            Route::get('/stats', [SubscriptionController::class, 'stats']); // Get statistics
        });

        Route::apiResource('subscriptions', SubscriptionController::class)
            ->except(['create', 'store', 'edit']);
    });

    /*
    |--------------------------------------------------------------------------
    | API Documentation Route
    |--------------------------------------------------------------------------
    */
    Route::get('docs', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'name' => 'Blog API',
                'version' => 'v1',
                'documentation' => '/api/v1/docs/openapi',
                'endpoints' => [
                    'auth' => '/api/v1/auth/*',
                    'posts' => '/api/v1/posts/*',
                    'categories' => '/api/v1/categories/*',
                    'tags' => '/api/v1/tags/*',
                    'comments' => '/api/v1/comments/*',
                    'users' => '/api/v1/users/*',
                    'media' => '/api/v1/media/*',
                    'admin' => '/api/v1/admin/*',
                    'subscriptions' => '/api/v1/subscriptions/*',
                    'newsletter' => '/api/v1/subscribe/*',
                ],
                'rate_limits' => [
                    'public' => '60 requests/minute',
                    'authenticated' => '120 requests/minute',
                    'editor' => '150 requests/minute',
                    'admin' => '200 requests/minute',
                    'auth' => '10 requests/minute',
                ],
            ],
        ]);
    });
});
