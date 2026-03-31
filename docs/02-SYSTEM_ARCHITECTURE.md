# System Architecture Document
## Masterclass Blog Platform

**Version:** 1.0  
**Status:** Approved  
**Date:** 2026-04-01

---

## 1. ARCHITECTURE OVERVIEW

### 1.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           CLIENT LAYER                                   │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   Desktop   │  │   Tablet    │  │   Mobile    │  │   Admin     │    │
│  │   Browser   │  │   Browser   │  │   Browser   │  │   Panel     │    │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘    │
│         │                │                │                │            │
│         └────────────────┴────────────────┴────────────────┘            │
│                                  │                                       │
│                          HTTPS / REST API                                │
│                          WebSocket (Real-time)                           │
└──────────────────────────────────┼───────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼───────────────────────────────────────┐
│                         API GATEWAY LAYER                                │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                    Laravel 12 Application                        │    │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │    │
│  │  │   Routing    │  │ Middleware   │  │   Rate       │          │    │
│  │  │   Layer      │  │   Pipeline   │  │   Limiting   │          │    │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │    │
│  └─────────────────────────────────────────────────────────────────┘    │
└──────────────────────────────────┼───────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼───────────────────────────────────────┐
│                       APPLICATION LAYER                                  │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │ Controllers │  │  Services   │  │ Repositories│  │   Events    │    │
│  │   (HTTP)    │  │  (Business  │  │   (Data     │  │  & Listeners│    │
│  │             │  │   Logic)    │  │   Access)   │  │             │    │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    │
│                                                                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   Form      │  │    API      │  │  Validators │  │ Notifications│   │
│  │  Requests   │  │  Resources  │  │             │  │             │    │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    │
└──────────────────────────────────┼───────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼───────────────────────────────────────┐
│                        DATA LAYER                                        │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   Models    │  │ Migrations  │  │   Seeders   │  │  Factories  │    │
│  │  (Eloquent) │  │             │  │             │  │             │    │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    │
└──────────────────────────────────┼───────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼───────────────────────────────────────┐
│                      STORAGE LAYER                                       │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   MySQL/    │  │    Redis    │  │    File     │  │   Session   │    │
│  │ PostgreSQL  │  │   (Cache)   │  │   Storage   │  │   Store     │    │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Architecture Pattern: Layered Architecture (N-Tier)

**Layers:**
1. **Presentation Layer** - HTML/Tailwind/Vanilla JS frontend
2. **API Layer** - Laravel REST API with Sanctum authentication
3. **Business Logic Layer** - Services, Controllers, Events
4. **Data Access Layer** - Repositories, Eloquent Models
5. **Storage Layer** - Database, Cache, File Storage

---

## 2. BACKEND ARCHITECTURE (Laravel 12)

### 2.1 Directory Structure

```
blog-backend/
├── app/
│   ├── Console/
│   │   └── Commands/
│   ├── Events/
│   │   ├── CommentCreated.php
│   │   ├── PostLiked.php
│   │   └── PostPublished.php
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── V1/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── PostController.php
│   │   │   │   │   ├── CategoryController.php
│   │   │   │   │   ├── TagController.php
│   │   │   │   │   ├── CommentController.php
│   │   │   │   │   ├── LikeController.php
│   │   │   │   │   ├── BookmarkController.php
│   │   │   │   │   ├── UserController.php
│   │   │   │   │   ├── SearchController.php
│   │   │   │   │   └── Admin/
│   │   │   │   │       ├── DashboardController.php
│   │   │   │   │       ├── UserController.php
│   │   │   │   │       └── PostController.php
│   │   │   └── Web/
│   │   │       └── HomeController.php
│   │   ├── Middleware/
│   │   │   ├── Authenticate.php
│   │   │   ├── CheckRole.php
│   │   │   ├── EnsureEmailIsVerified.php
│   │   │   └── RateLimitMiddleware.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   └── LoginRequest.php
│   │   │   ├── Post/
│   │   │   │   ├── StorePostRequest.php
│   │   │   │   └── UpdatePostRequest.php
│   │   │   ├── Comment/
│   │   │   │   ├── StoreCommentRequest.php
│   │   │   │   └── UpdateCommentRequest.php
│   │   │   └── User/
│   │   │       └── UpdateProfileRequest.php
│   │   ├── Resources/
│   │   │   ├── UserResource.php
│   │   │   ├── PostResource.php
│   │   │   ├── CommentResource.php
│   │   │   ├── CategoryResource.php
│   │   │   └── TagResource.php
│   │   └── Kernel.php
│   ├── Listeners/
│   │   ├── SendCommentNotification.php
│   │   ├── UpdatePostLikeCount.php
│   │   └── SendWelcomeEmail.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Post.php
│   │   ├── Category.php
│   │   ├── Tag.php
│   │   ├── Comment.php
│   │   ├── Like.php
│   │   └── Bookmark.php
│   ├── Notifications/
│   │   ├── NewCommentNotification.php
│   │   ├── PostLikedNotification.php
│   │   └── WelcomeNotification.php
│   ├── Policies/
│   │   ├── PostPolicy.php
│   │   ├── CommentPolicy.php
│   │   └── UserPolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   └── EventServiceProvider.php
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── PostRepositoryInterface.php
│   │   │   └── CommentRepositoryInterface.php
│   │   ├── PostRepository.php
│   │   └── CommentRepository.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── PostService.php
│   │   ├── CommentService.php
│   │   ├── SearchService.php
│   │   └── ImageUploadService.php
│   └── Traits/
│       ├── HasUuid.php
│       ├── HasSlug.php
│       └── Auditable.php
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/
│   ├── index.php
│   └── assets/
├── resources/
├── routes/
│   ├── api.php
│   ├── web.php
│   └── channels.php
├── storage/
├── tests/
├── .env
├── .env.example
├── composer.json
└── artisan
```

### 2.2 Core Components

#### 2.2.1 Controllers
- **ApiController** - Base API controller with common methods
- **AuthController** - Authentication endpoints
- **PostController** - Blog post CRUD
- **CommentController** - Comment management
- **CategoryController** - Category management
- **TagController** - Tag management
- **LikeController** - Like functionality
- **BookmarkController** - Bookmark functionality
- **UserController** - User profile management
- **SearchController** - Search functionality
- **Admin Controllers** - Admin dashboard and management

#### 2.2.2 Services (Business Logic)
```
AuthService
├── register()
├── login()
├── logout()
├── forgotPassword()
├── resetPassword()
└── verifyEmail()

PostService
├── getAllPosts()
├── getPostBySlug()
├── createPost()
├── updatePost()
├── deletePost()
├── publishPost()
├── getRelatedPosts()
└── incrementViewCount()

CommentService
├── getComments()
├── createComment()
├── updateComment()
├── deleteComment()
├── approveComment()
└── getCommentTree()

SearchService
├── search()
├── suggest()
└── indexPost()

ImageUploadService
├── upload()
├── delete()
└── resize()
```

#### 2.2.3 Repositories (Data Access)
```
PostRepository implements PostRepositoryInterface
├── find()
├── findBySlug()
├── findAll()
├── findByCategory()
├── findByTag()
├── findByAuthor()
├── create()
├── update()
├── delete()
└── search()

CommentRepository implements CommentRepositoryInterface
├── find()
├── findByPost()
├── findByUser()
├── create()
├── update()
├── delete()
└── getNested()
```

### 2.3 Routing Structure

```php
// routes/api.php

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{slug}', [PostController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('tags', [TagController::class, 'index']);
    Route::get('search', [SearchController::class, 'search']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/verify-email', [AuthController::class, 'verifyEmail']);
        
        // Posts
        Route::apiResource('posts', PostController::class)
            ->except(['index', 'show']);
        
        // Comments
        Route::apiResource('posts.comments', CommentController::class)
            ->shallow();
        
        // Likes & Bookmarks
        Route::post('posts/{post}/like', [LikeController::class, 'toggle']);
        Route::post('posts/{post}/bookmark', [BookmarkController::class, 'toggle']);
        Route::get('user/bookmarks', [BookmarkController::class, 'index']);
        
        // User
        Route::get('user/profile', [UserController::class, 'profile']);
        Route::put('user/profile', [UserController::class, 'updateProfile']);
        Route::put('user/password', [UserController::class, 'updatePassword']);
        
        // Admin routes
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::apiResource('users', Admin\UserController::class);
            Route::apiResource('posts', Admin\PostController::class);
            Route::get('comments/pending', [CommentController::class, 'pending']);
            Route::post('comments/{comment}/approve', [CommentController::class, 'approve']);
            Route::post('comments/{comment}/reject', [CommentController::class, 'reject']);
            Route::get('analytics', [DashboardController::class, 'analytics']);
        });
    });
});
```

---

## 3. FRONTEND ARCHITECTURE

### 3.1 Directory Structure

```
blog-frontend/
├── public/
│   ├── index.html
│   ├── favicon.ico
│   └── assets/
│       ├── images/
│       └── fonts/
├── src/
│   ├── css/
│   │   ├── app.css          # Tailwind imports + custom styles
│   │   ├── components.css   # Component-specific styles
│   │   └── utilities.css    # Custom utilities
│   ├── js/
│   │   ├── app.js           # Main entry point
│   │   ├── config.js        # App configuration
│   │   ├── router.js        # Client-side routing
│   │   ├── store.js         # State management
│   │   ├── api/
│   │   │   ├── client.js    # HTTP client (axios/fetch)
│   │   │   ├── auth.js      # Auth API calls
│   │   │   ├── posts.js     # Posts API calls
│   │   │   ├── comments.js  # Comments API calls
│   │   │   ├── user.js      # User API calls
│   │   │   └── admin.js     # Admin API calls
│   │   ├── utils/
│   │   │   ├── helpers.js   # Utility functions
│   │   │   ├── validators.js # Form validators
│   │   │   ├── formatters.js # Date, number formatters
│   │   │   └── storage.js   # LocalStorage wrapper
│   │   ├── components/
│   │   │   ├── Header.js
│   │   │   ├── Footer.js
│   │   │   ├── Sidebar.js
│   │   │   ├── PostCard.js
│   │   │   ├── Comment.js
│   │   │   ├── Modal.js
│   │   │   ├── Toast.js
│   │   │   ├── Pagination.js
│   │   │   └── Loading.js
│   │   ├── pages/
│   │   │   ├── Home.js
│   │   │   ├── BlogList.js
│   │   │   ├── BlogDetail.js
│   │   │   ├── Login.js
│   │   │   ├── Register.js
│   │   │   ├── CreatePost.js
│   │   │   ├── EditPost.js
│   │   │   ├── Profile.js
│   │   │   ├── Search.js
│   │   │   └── admin/
│   │   │       ├── Dashboard.js
│   │   │       ├── Users.js
│   │   │       ├── Posts.js
│   │   │       └── Settings.js
│   │   └── services/
│   │       ├── AuthService.js
│   │       ├── NotificationService.js
│   │       └── WebSocketService.js
│   └── pages/
│       ├── 404.html
│       └── offline.html
├── package.json
├── tailwind.config.js
├── postcss.config.js
└── vite.config.js
```

### 3.2 Component Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        App Shell                            │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    Header                           │   │
│  │  ┌─────┐  ┌─────────────┐  ┌─────────────────┐    │   │
│  │  │Logo │  │  Navigation │  │  User Menu      │    │   │
│  │  └─────┘  └─────────────┘  └─────────────────┘    │   │
│  └─────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────────────────────────┐   │
│  │              │  │                                  │   │
│  │   Sidebar    │  │         Main Content             │   │
│  │              │  │                                  │   │
│  │  -Categories │  │    Page-specific components      │   │
│  │  -Tags       │  │                                  │   │
│  │  -Trending   │  │                                  │   │
│  │              │  │                                  │   │
│  └──────────────┘  └──────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    Footer                           │   │
│  │  Links | Social | Copyright | Legal                │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 3.3 State Management

```javascript
// store.js - Simple state management
const store = {
    state: {
        user: null,
        token: null,
        posts: [],
        categories: [],
        tags: [],
        notifications: [],
        isLoading: false,
        error: null
    },
    
    actions: {
        setUser(user) {
            this.state.user = user;
            this.persist();
        },
        setToken(token) {
            this.state.token = token;
            this.persist();
        },
        logout() {
            this.state.user = null;
            this.state.token = null;
            this.persist();
        },
        addNotification(notification) {
            this.state.notifications.unshift(notification);
        },
        markNotificationRead(id) {
            const notif = this.state.notifications.find(n => n.id === id);
            if (notif) notif.read = true;
        }
    },
    
    persist() {
        localStorage.setItem('blog_store', JSON.stringify(this.state));
    },
    
    load() {
        const saved = localStorage.getItem('blog_store');
        if (saved) {
            this.state = { ...this.state, ...JSON.parse(saved) };
        }
    }
};
```

### 3.4 API Client

```javascript
// api/client.js
class ApiClient {
    constructor(baseURL) {
        this.baseURL = baseURL;
        this.token = null;
    }
    
    setToken(token) {
        this.token = token;
    }
    
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers,
            },
        };
        
        if (this.token) {
            config.headers.Authorization = `Bearer ${this.token}`;
        }
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new ApiError(data.message, response.status);
            }
            
            return data;
        } catch (error) {
            if (error instanceof ApiError) throw error;
            throw new ApiError('Network error', 0);
        }
    }
    
    get(endpoint) { return this.request(endpoint, { method: 'GET' }); }
    post(endpoint, data) { return this.request(endpoint, { method: 'POST', body: JSON.stringify(data) }); }
    put(endpoint, data) { return this.request(endpoint, { method: 'PUT', body: JSON.stringify(data) }); }
    delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }
}

class ApiError extends Error {
    constructor(message, status) {
        super(message);
        this.status = status;
    }
}
```

---

## 4. DATABASE ARCHITECTURE

### 4.1 Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐
│     USERS       │       │   CATEGORIES    │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │
│ name            │       │ parent_id (FK)  │◄────┐
│ email           │       │ name            │     │
│ password        │       │ slug            │     │
│ avatar          │       │ description     │     │
│ bio             │       └────────┬────────┘     │
│ role            │                │              │
│ email_verified  │                │ 1            │
│ created_at      │                │              │
│ updated_at      │       ┌────────▼────────┐     │
└────────┬────────┘       │   CATEGORIES    │     │ (self-referencing
         │ 1              └─────────────────┘     │  for hierarchy)
         │                                       │
         │ *              ┌─────────────────┐    │
         ├───────────────►│     POSTS       │    │
         │                ├─────────────────┤    │
         │                │ id (PK)         │    │
         │                │ user_id (FK)    │    │
         │                │ category_id (FK)│────┘
         │                │ title           │
         │     ┌─────────►│ slug            │
         │     │          │ excerpt         │
         │     │          │ content         │
         │     │          │ featured_image  │
         │     │          │ status          │
         │     │          │ views_count     │
         │     │          │ published_at    │
         │     │          │ created_at      │
         │     │          │ updated_at      │
         │     │          └────────┬────────┘
         │     │                   │
         │     │         ┌─────────┼─────────┐
         │     │         │         │         │
         │     │    ┌────▼────┐ ┌──▼──────┐ ┌▼──────────┐
         │     │    │COMMENTS │ │  LIKES  │ │ BOOKMARKS │
         │     │    ├─────────┤ ├─────────┤ ├───────────┤
         │     │    │id (PK)  │ │id (PK)  │ │id (PK)    │
         │     │    │post_id  │ │user_id  │ │user_id    │
         │     │    │user_id  │ │post_id  │ │post_id    │
         │     │    │parent_id│ │created  │ │created    │
         │     │    │content  │ └─────────┘ └───────────┘
         │     │    │status   │
         │     │    │created  │
         │     │    │updated  │
         │     │    └─────────┘
         │     │         ▲
         │     │         │ (self-referencing for nested replies)
         │     │         │
         │     │    ┌────┴────┐
         │     │    │COMMENTS │
         │     │    └─────────┘
         │     │
         │     │    ┌─────────────┐
         │     └────►    TAGS     │
         │          ├─────────────┤
         │          │id (PK)      │
         │          │name         │
         │          │slug         │
         │          │created      │
         │          │updated      │
         │          └─────────────┘
         │                ▲
         │                │
         │          ┌─────┴─────┐
         │          │ POST_TAG  │ (pivot table)
         │          ├───────────┤
         │          │post_id    │
         │          │tag_id     │
         │          └───────────┘
         │
         │    ┌─────────────────┐
         └────►  NOTIFICATIONS  │
              ├─────────────────┤
              │id (PK)          │
              │user_id (FK)     │
              │type             │
              │data (JSON)      │
              │read_at          │
              │created_at       │
              └─────────────────┘
```

---

## 5. SECURITY ARCHITECTURE

### 5.1 Authentication Flow

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│  Client  │     │  Laravel │     │  Sanctum │     │ Database │
└────┬─────┘     └────┬─────┘     └────┬─────┘     └────┬─────┘
     │                │                │                │
     │  POST /login   │                │                │
     │───────────────►│                │                │
     │                │  Validate      │                │
     │                │  Credentials   │                │
     │                │───────────────►│                │
     │                │                │  Query User    │
     │                │                │───────────────►│
     │                │                │◄───────────────│
     │                │◄───────────────│                │
     │                │                │                │
     │                │  Create Token  │                │
     │                │───────────────►│                │
     │                │                │  Store Token   │
     │                │                │───────────────►│
     │                │                │◄───────────────│
     │                │◄───────────────│                │
     │                │                │                │
     │  Token Response│                │                │
     │◄───────────────│                │                │
     │                │                │                │
     │  GET /posts    │                │                │
     │  + Bearer Token│                │                │
     │───────────────►│                │                │
     │                │  Verify Token  │                │
     │                │───────────────►│                │
     │                │                │  Lookup Token  │
     │                │                │───────────────►│
     │                │                │◄───────────────│
     │                │◄───────────────│                │
     │                │                │                │
     │  Data Response │                │                │
     │◄───────────────│                │                │
```

### 5.2 Authorization Matrix

| Resource | Guest | User | Admin |
|----------|-------|------|-------|
| View posts | ✓ | ✓ | ✓ |
| View categories | ✓ | ✓ | ✓ |
| View tags | ✓ | ✓ | ✓ |
| Search | ✓ | ✓ | ✓ |
| Register | ✓ | - | - |
| Login | ✓ | - | - |
| Create post | - | ✓ | ✓ |
| Edit own post | - | ✓ | ✓ |
| Delete own post | - | ✓ | ✓ |
| Edit any post | - | - | ✓ |
| Delete any post | - | - | ✓ |
| Comment | - | ✓ | ✓ |
| Like | - | ✓ | ✓ |
| Bookmark | - | ✓ | ✓ |
| View profile | ✓ | ✓ | ✓ |
| Edit profile | - | ✓ | ✓ |
| View admin panel | - | - | ✓ |
| Manage users | - | - | ✓ |
| Moderate content | - | - | ✓ |
| View analytics | - | - | ✓ |

---

## 6. CACHING STRATEGY

### 6.1 Cache Layers

```
┌─────────────────────────────────────────────────────────┐
│                    Client Side                          │
│  ┌─────────────────┐  ┌─────────────────┐              │
│  │  LocalStorage   │  │  SessionStorage │              │
│  │  - Auth token   │  │  - Temp data    │              │
│  │  - User prefs   │  │                 │              │
│  └─────────────────┘  └─────────────────┘              │
└─────────────────────────────────────────────────────────┘
                          │
┌─────────────────────────▼─────────────────────────────────┐
│                    Server Side                            │
│  ┌─────────────────┐  ┌─────────────────┐               │
│  │     Redis       │  │  Database       │               │
│  │  - Sessions     │  │  Query Cache    │               │
│  │  - API Cache    │  │  - Model Cache  │               │
│  │  - Rate Limits  │  │                 │               │
│  │  - Real-time    │  │                 │               │
│  └─────────────────┘  └─────────────────┘               │
└─────────────────────────────────────────────────────────┘
```

### 6.2 Cache Keys Structure

```
blog:post:{id}           - Single post data
blog:post:{slug}         - Post by slug
blog:posts:page:{n}      - Paginated post list
blog:posts:category:{id} - Posts by category
blog:posts:tag:{id}      - Posts by tag
blog:user:{id}           - User profile
blog:category:{id}       - Category data
blog:tag:{id}            - Tag data
blog:comments:{post_id}  - Post comments
blog:stats:daily         - Daily statistics
```

---

## 7. REAL-TIME ARCHITECTURE

### 7.1 WebSocket Flow

```
┌──────────┐                          ┌──────────┐
│  Client  │                          │  Server  │
└────┬─────┘                          └────┬─────┘
     │                                     │
     │  WebSocket Connection               │
     │  (with auth token)                  │
     │────────────────────────────────────►│
     │                                     │
     │  Subscribe to channels:             │
     │  - notifications.{userId}           │
     │  - posts.{postId}.comments          │
     │────────────────────────────────────►│
     │                                     │
     │                                     │  Event: New Comment
     │                                     │  Broadcast to channel
     │◄────────────────────────────────────│
     │                                     │
     │  Render notification                │
     │                                     │
```

### 7.2 Event Types

| Event | Channel | Payload |
|-------|---------|---------|
| comment.created | posts.{id}.comments | comment, post |
| comment.replied | users.{id}.notifications | comment, original_comment |
| post.liked | users.{id}.notifications | post, liker |
| post.published | admin.notifications | post |
| user.registered | admin.notifications | user |

---

## 8. DEPLOYMENT ARCHITECTURE

### 8.1 Production Environment

```
                    ┌─────────────────┐
                    │   Cloudflare    │
                    │      (CDN)      │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │   Load Balancer │
                    └────────┬────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
    ┌───────▼───────┐ ┌──────▼──────┐ ┌──────▼──────┐
    │   Web Server  │ │ Web Server  │ │ Web Server  │
    │   (Laravel)   │ │ (Laravel)   │ │ (Laravel)   │
    └───────┬───────┘ └──────┬──────┘ └──────┬──────┘
            │                │                │
            └────────────────┼────────────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
    ┌───────▼───────┐ ┌──────▼──────┐ ┌──────▼──────┐
    │    MySQL      │ │    Redis    │ │   Storage   │
    │   (Primary)   │ │   (Cache)   │ │    (S3)     │
    └───────────────┘ └─────────────┘ └─────────────┘
```

---

## 9. SCALABILITY CONSIDERATIONS

### 9.1 Horizontal Scaling
- Stateless application servers
- Session storage in Redis
- Database read replicas
- CDN for static assets

### 9.2 Vertical Scaling
- Database connection pooling
- Query optimization
- Index strategy
- Caching hot data

### 9.3 Performance Targets
- TTFB < 200ms
- First Contentful Paint < 1.5s
- Time to Interactive < 3.5s
- Lighthouse Score > 90

---

## 10. MONITORING & LOGGING

### 10.1 Application Monitoring
- Error tracking (Sentry)
- Performance monitoring (New Relic/DataDog)
- Uptime monitoring
- Log aggregation (ELK Stack)

### 10.2 Key Metrics
- Request rate
- Error rate
- Response time (p50, p95, p99)
- Database query time
- Cache hit rate
- Active users

---

*End of System Architecture Document*
