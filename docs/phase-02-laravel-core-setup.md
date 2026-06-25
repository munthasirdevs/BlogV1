# PHASE 2 — LARAVEL 12 CORE SETUP & SYSTEM FOUNDATION

## 1. Initial Project Architecture

### Folder Architecture
```
BlogV1/
├── app/
│   ├── Actions/
│   ├── Console/Commands/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   └── Public/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Jobs/
│   ├── Livewire/
│   ├── Models/
│   ├── Notifications/
│   ├── Observers/
│   ├── Providers/
│   ├── Repositories/
│   ├── Rules/
│   ├── Services/
│   │   ├── AI/
│   │   ├── SEO/
│   │   ├── Analytics/
│   │   ├── Media/
│   │   └── Search/
│   └── Traits/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/
├── public/
│   ├── build/
│   └── storage/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── admin/
│       ├── auth/
│       ├── components/
│       ├── layouts/
│       ├── pages/
│       └── partials/
├── routes/
│   ├── admin.php
│   ├── api.php
│   ├── channels.php
│   └── web.php
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── TestCase.php
└── vendor/
```

### Naming Conventions
- Classes: PascalCase (PostService, CategoryController)
- Methods/Functions: camelCase (getPublishedPosts, storeCategory)
- Variables/Properties: camelCase ($publishedPosts, $categoryName)
- Database Tables: snake_case plural (posts, categories, post_tag)
- Database Columns: snake_case (published_at, featured_image)
- Routes: kebab-case (blog/{slug}, admin/posts/create)
- Files: PascalCase for classes, camelCase for blade partials
- Views: kebab-case dot notation (admin.posts.create)

### SOLID Principles
- **S** — Single Responsibility: Each class has one job (PostService handles only post logic)
- **O** — Open/Closed: Services extend via interfaces, not modification
- **L** — Liskov Substitution: Repository interfaces ensure interchangeable implementations
- **I** — Interface Segregation: Small focused interfaces (SEOInterface, MediaInterface)
- **D** — Dependency Injection: All dependencies injected via constructor

---

## 2. Development Environment

### Required Software
| Tool | Version | Purpose |
|------|---------|---------|
| PHP | 8.4+ | Application runtime |
| Composer | 2.x | PHP package manager |
| Node.js | 20+ | Frontend build tools |
| NPM | 10+ | Package management |
| MySQL | 8.0+ | Primary database |
| Redis | 7+ | Cache/Queue/Session |
| Git | Latest | Version control |

### Environment Setup
```bash
# Create Laravel project
composer create-project laravel/laravel:^12.0 .

# Install frontend dependencies
npm install

# Install Laravel Breeze (Blade stack)
composer require laravel/breeze --dev
php artisan breeze:install blade

# Install Spatie packages
composer require spatie/laravel-permission
composer require spatie/laravel-medialibrary
composer require spatie/laravel-activitylog
composer require spatie/laravel-backup
composer require spatie/laravel-sitemap

# Install SEO tools
composer require laravel/scout
composer require intervention/image-laravel
```

---

## 3. Environment Configuration

```env
APP_NAME="BlogV1"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blogv1
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@blogv1.com"
MAIL_FROM_NAME="${APP_NAME}"

FILESYSTEM_DISK=local

NVIDIA_API_KEY=
NVIDIA_API_ENDPOINT=https://api.nvcf.nvidia.com/v2/nvcf
```

---

## 4. Application Configuration

| Setting | Value | Location |
|---------|-------|----------|
| Timezone | UTC | config/app.php |
| Locale | en | config/app.php |
| Fallback Locale | en | config/app.php |
| Date Format | Y-m-d H:i:s | config/app.php |

---

## 5. Laravel Package Planning

| Package | Purpose | Why |
|---------|---------|-----|
| Laravel Breeze | Authentication scaffolding | Free, official, Blade-first |
| Spatie Permission | RBAC | Gold standard for Laravel permissions |
| Spatie Media Library | File/media management | Handles conversions, responsive images |
| Spatie Activitylog | Audit trail | Tracks all model changes |
| Spatie Backup | Automated backups | Database + file backup to cloud |
| Spatie Sitemap | XML sitemaps | Automatic sitemap generation |
| Laravel Scout | Full-text search | Searchable model integration |
| Intervention Image | Image optimization | Resize, crop, compress, WebP |
| Artesaos SEOTools | SEO metadata | Meta tags, OG/Twitter cards |

---

## 6. Application Layers

```
 ┌─────────────────────────────────────┐
 │         Presentation Layer          │
 │  Blade Views / Components / Alpine  │
 ├─────────────────────────────────────┤
 │         Business Layer              │
 │  Services / Actions / Business Logic│
 ├─────────────────────────────────────┤
 │           Data Layer                │
 │  Models / Repositories / Queries    │
 ├─────────────────────────────────────┤
 │        Infrastructure Layer         │
 │   Queues / Cache / External APIs    │
 ├─────────────────────────────────────┤
 │            AI Layer                 │
 │    NVIDIA Integration / Prompts     │
 ├─────────────────────────────────────┤
 │            SEO Layer                │
 │      Sitemaps / Schema / Meta       │
 └─────────────────────────────────────┘
```

---

## 7. Service Container Architecture

| Service | Interface | Implementation | Binding |
|---------|-----------|----------------|---------|
| PostService | PostServiceInterface | PostService | AppServiceProvider |
| CategoryService | CategoryServiceInterface | CategoryService | AppServiceProvider |
| TagService | TagServiceInterface | TagService | AppServiceProvider |
| UserService | UserServiceInterface | UserService | AppServiceProvider |
| SEOService | SEOServiceInterface | SEOService | AppServiceProvider |
| AIService | AIServiceInterface | AIService | AppServiceProvider |
| MediaService | MediaServiceInterface | MediaService | AppServiceProvider |
| SearchService | SearchServiceInterface | SearchService | AppServiceProvider |

---

## 8. Cache System (Redis)

| Cache Key | TTL | Invalidation Trigger |
|-----------|-----|---------------------|
| homepage:* | 3600s | New post published |
| blog:page:* | 3600s | Post CRUD |
| category:* | 7200s | Category CRUD |
| tag:* | 7200s | Tag CRUD |
| author:* | 3600s | Author profile update |
| related_posts:* | 1800s | Post CRUD |
| settings:* | 86400s | Settings update |

---

## 9. Queue System (Redis)

| Queue Name | Priority | Jobs |
|------------|----------|------|
| high | Highest | AI generation, email |
| default | Normal | Image optimization, SEO analysis |
| low | Lowest | Sitemap generation, analytics processing |

### Retry Logic
- Max attempts: 3
- Backoff: exponential (10s, 30s, 60s)
- Failed jobs stored in failed_jobs table

---

## 10. File Storage System

```
storage/app/public/
├── posts/{yyyy}/{mm}/{slug}/
├── categories/{slug}/
├── authors/{user_id}/
├── pages/{slug}/
├── thumbnails/
├── seo/
└── temporary/
```

### Image Pipeline
1. Upload → Validate (mime, size, dimensions)
2. Generate WebP version
3. Generate thumbnails (150x150, 300x300, 768x768)
4. Store original + optimized
5. Queue for CDN upload (future)

---

## 11. Logging System

| Log Channel | Handler | Contents |
|-------------|---------|----------|
| daily | RotatingFileHandler | Application errors |
| security | DailyLog | Auth attempts, suspicious activity |
| ai | DailyLog | AI API calls, tokens used |
| activity | Activitylog DB | User actions, model changes |

---

## 12. Security Foundation

### Authentication
- Laravel Breeze with Blade stack
- Login/Register/Password Reset/Email Verification
- Throttling: 5 attempts per minute on login

### Security Measures
- CSRF: Enabled on all POST/PUT/DELETE forms
- XSS: Blade auto-escape + validation rules
- SQL Injection: Eloquent ORM prepared statements
- Mass Assignment: Fillable/guarded on all models
- Sessions: Redis with HTTP-only cookies
- Password: Bcrypt hashing, minimum 8 chars

---

## 13. Frontend Foundation

### Blade View Structure
```
resources/views/
├── admin/
│   ├── dashboard.blade.php
│   ├── posts/
│   ├── categories/
│   ├── tags/
│   ├── media/
│   ├── users/
│   ├── seo/
│   ├── ai/
│   ├── analytics/
│   └── settings/
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   └── reset-password.blade.php
├── components/
│   ├── header.blade.php
│   ├── footer.blade.php
│   ├── sidebar.blade.php
│   └── pagination.blade.php
├── layouts/
│   ├── app.blade.php
│   └── admin.blade.php
├── pages/
│   ├── blog/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   └── static/
│       ├── about.blade.php
│       └── contact.blade.php
└── partials/
    ├── seo.blade.php
    ├── comments.blade.php
    └── social-share.blade.php
```

### Tech Stack
- Tailwind CSS for styling
- Alpine.js for interactivity
- Vite for asset bundling

---

## 14. Performance Foundation

### Caching Strategy
```bash
php artisan route:cache
php artisan config:cache
php artisan view:cache
```

### Database Optimization
- Eager loading for all relationships
- Select only needed columns
- Query pagination (not offset-based for large datasets)
- Composite indexes on frequent queries

### Core Web Vitals Targets
| Metric | Target |
|--------|--------|
| LCP | <2.5s |
| FID | <100ms |
| CLS | <0.1 |

---

## 15. Admin Panel Foundation

```
/admin
├── /dashboard
├── /posts
│   ├── / (list)
│   ├── /create
│   ├── /{id}/edit
│   └── /{id}/revisions
├── /categories
├── /tags
├── /media
├── /comments
├── /users
├── /seo
│   ├── /sitemap
│   └── /redirects
├── /ai
│   ├── /generator
│   └── /history
├── /analytics
└── /settings
```

### Access Control
- Super Admin: All routes
- Admin: All operational routes
- Editor: Content routes, SEO routes
- Author: Own content routes
- Contributor: Create posts only

---

## 16. API Foundation

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| GET | /api/v1/posts | Public posts list | None |
| GET | /api/v1/posts/{slug} | Single post | None |
| GET | /api/v1/categories | Categories list | None |
| GET | /api/v1/tags | Tags list | None |
| POST | /api/v1/auth/login | API login | None |
| POST | /api/v1/auth/register | API register | None |
| GET | /api/v1/admin/posts | Admin post list | Sanctum |
| POST | /api/v1/admin/posts | Create post | Sanctum |
| PUT | /api/v1/admin/posts/{id} | Update post | Sanctum |
| DELETE | /api/v1/admin/posts/{id} | Delete post | Sanctum |

Rate limiting: 60 req/min for public, 200 req/min for authenticated

---

## 17. Testing Foundation

### Test Structure
```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── RegistrationTest.php
│   │   ├── LoginTest.php
│   │   └── PasswordResetTest.php
│   ├── Post/
│   │   ├── CreatePostTest.php
│   │   ├── EditPostTest.php
│   │   └── DeletePostTest.php
│   ├── Category/
│   ├── Tag/
│   ├── Comment/
│   ├── SEO/
│   ├── AI/
│   └── Permission/
└── Unit/
    ├── Services/
    │   ├── PostServiceTest.php
    │   ├── SEOServiceTest.php
    │   └── AIServiceTest.php
    └── Models/
        ├── PostTest.php
        └── UserTest.php
```

---

## 18. Deployment Foundation

### Deployment Checklist
- [ ] PHP 8.4+ installed and configured
- [ ] Composer dependencies installed (no dev)
- [ ] MySQL 8+ database created
- [ ] Redis installed and running
- [ ] .env configured with production values
- [ ] APP_DEBUG=false
- [ ] Route/Config/View caching executed
- [ ] Storage directory linked (php artisan storage:link)
- [ ] Queue worker running as daemon
- [ ] Scheduler configured (cron)
- [ ] SSL certificate installed
- [ ] Backup system configured

### Cron Setup
```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 19. Documentation

Documentation files stored in `/docs/`:
- `ARCHITECTURE.md` — System architecture overview
- `SETUP.md` — Development environment setup guide
- `DEPLOYMENT.md` — Production deployment guide
- `STANDARDS.md` — Coding standards and conventions

---

## 20. Final Output

**Phase 2 complete.** Laravel 12 foundation architecture defined covering:
- Project folder structure and conventions
- Environment and application configuration
- Package selection with rationale
- All application layers (Presentation, Business, Data, Infrastructure, AI, SEO)
- Service container with DI contracts
- Redis caching and queue architecture
- File storage and image pipeline
- Security foundation (auth, CSRF, XSS, SQLi)
- Blade view structure with Tailwind + Alpine.js
- Performance optimization strategy
- Admin panel navigation and routing
- API foundation with Sanctum
- Testing structure
- Deployment checklist

Ready to proceed to **Phase 3** — Database Architecture & Enterprise Data Model Design.
