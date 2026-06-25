# PHASE 3 — DATABASE ARCHITECTURE & ENTERPRISE DATA MODEL DESIGN

## 1. Database Architecture Strategy

### Naming Conventions

| Convention | Rule | Example | Reason |
|-----------|------|---------|--------|
| Database name | snake_case | blog_v1 | Readability, consistency |
| Table names | snake_case plural | posts, categories, post_tag | Laravel convention |
| Column names | snake_case | published_at, featured_image | Readability |
| Primary keys | id | id | Laravel convention |
| Foreign keys | singular_table_id | post_id, category_id | Clarity |
| Pivot tables | singular_singular | post_tag, role_user | Alphabetical order |
| Indexes | table_column_index | posts_slug_index | Debugging |

### Migration Standards
- All changes via Laravel migrations (version-controlled)
- Never modify existing migrations after commit
- Use `up()` and `down()` for rollback
- Foreign keys defined in separate migration after tables exist
- Indexes created with migration (not manually)

### Data Integrity
- Foreign key constraints with CASCADE on delete
- UUID as public identifier (id remains internal)
- Soft deletes on all content tables
- Timestamps on all tables (created_at, updated_at)

---

## 2. Entity Relationship Design

### Complete Entity Map

```
users 1──* posts
users 1──* comments
users 1──* media_files
users 1──* ai_generations
users 1──* activity_logs
users *──* roles (via model_has_roles)
roles *──* permissions (via role_has_permissions)

posts 1──* post_revisions
posts 1──* comments
posts *──* tags (via post_tag)
posts *──1 categories
posts 1──1 seo_meta (polymorphic)
posts 1──1 content_metrics
posts 1──* post_views

categories 1──* categories (self-referencing parent_id)
categories 1──1 seo_meta (polymorphic)

tags *──* posts (via post_tag)
tags 1──1 seo_meta (polymorphic)

pages 1──1 seo_meta (polymorphic)

media_folders 1──* media_files

notifications *──1 users
settings (standalone key-value)
```

---

## 3. Users Table

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    bio TEXT NULL,
    website VARCHAR(255) NULL,
    social_links JSON NULL,
    status ENUM('active','pending','suspended','banned') DEFAULT 'pending',
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX users_status_index (status),
    INDEX users_username_index (username)
);
```

---

## 4. Roles & Permissions (Spatie)

Tables managed by `spatie/laravel-permission`:
- `roles` — role definitions
- `permissions` — permission definitions
- `role_has_permissions` — role-permission assignment
- `model_has_roles` — user-role assignment
- `model_has_permissions` — direct user-permission assignment

### Default Roles
| Role | Guard | Description |
|------|-------|-------------|
| Super Admin | web | Full system access |
| Admin | web | Operational management |
| Editor | web | Content management, SEO |
| Author | web | Own content publishing |
| Contributor | web | Draft creation only |

---

## 5. Posts Table

```sql
CREATE TABLE posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    author_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT NULL,
    content LONGTEXT NULL,
    featured_image VARCHAR(255) NULL,
    reading_time INT UNSIGNED DEFAULT 0,
    status ENUM('draft','review','seo_review','approved','scheduled','published','archived') DEFAULT 'draft',
    visibility ENUM('public','private','password') DEFAULT 'public',
    featured BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    scheduled_at TIMESTAMP NULL,
    views_count BIGINT UNSIGNED DEFAULT 0,
    likes_count INT UNSIGNED DEFAULT 0,
    shares_count INT UNSIGNED DEFAULT 0,
    seo_score DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX posts_status_index (status),
    INDEX posts_published_at_index (published_at),
    INDEX posts_featured_index (featured),
    INDEX posts_author_id_index (author_id),
    INDEX posts_category_id_index (category_id),
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

---

## 6. Post Revisions Table

```sql
CREATE TABLE post_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    revision_number INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NULL,
    editor_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    INDEX post_revisions_post_id_index (post_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (editor_id) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 7. Categories Table

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    status BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX categories_parent_id_index (parent_id),
    INDEX categories_slug_index (slug),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);
```

---

## 8. Tags Table

```sql
CREATE TABLE tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX tags_slug_index (slug)
);
```

---

## 9. Post Tag Pivot

```sql
CREATE TABLE post_tag (
    post_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

---

## 10. Pages Table

```sql
CREATE TABLE pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT NULL,
    status ENUM('draft','published') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX pages_slug_index (slug)
);
```

---

## 11. Media Library

```sql
CREATE TABLE media_folders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (parent_id) REFERENCES media_folders(id) ON DELETE CASCADE
);

CREATE TABLE media_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folder_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    alt_text VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX media_files_mime_type_index (mime_type),
    FOREIGN KEY (folder_id) REFERENCES media_folders(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 12. Comments Table

```sql
CREATE TABLE comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(255) NULL,
    guest_email VARCHAR(255) NULL,
    comment TEXT NOT NULL,
    status ENUM('pending','approved','spam','trash') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX comments_post_id_index (post_id),
    INDEX comments_status_index (status),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 13. Newsletter Subscribers

```sql
CREATE TABLE newsletter_subscribers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    verification_token VARCHAR(100) NULL,
    verified_at TIMESTAMP NULL,
    subscribed_at TIMESTAMP NULL,
    unsubscribed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 14. Contact Messages

```sql
CREATE TABLE contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read','replied','spam') DEFAULT 'unread',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 15. SEO Meta (Polymorphic)

```sql
CREATE TABLE seo_meta (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seoable_type VARCHAR(255) NOT NULL,
    seoable_id BIGINT UNSIGNED NOT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    meta_keywords TEXT NULL,
    canonical_url VARCHAR(500) NULL,
    robots_directive VARCHAR(100) DEFAULT 'index,follow',
    og_title VARCHAR(255) NULL,
    og_description TEXT NULL,
    og_image VARCHAR(255) NULL,
    twitter_title VARCHAR(255) NULL,
    twitter_description TEXT NULL,
    schema_type VARCHAR(100) DEFAULT 'Article',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX seo_meta_seoable_index (seoable_type, seoable_id),
    INDEX seo_meta_canonical_url_index (canonical_url)
);
```

---

## 16. Redirects

```sql
CREATE TABLE redirects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    old_url VARCHAR(500) NOT NULL,
    new_url VARCHAR(500) NULL,
    redirect_type ENUM('301','302') DEFAULT '301',
    hit_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX redirects_old_url_index (old_url)
);
```

---

## 17. AI Generations

```sql
CREATE TABLE ai_generations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    model_name VARCHAR(255) NOT NULL,
    prompt TEXT NOT NULL,
    generated_content LONGTEXT NULL,
    generation_type ENUM('article','title','meta_description','keywords','summary','expansion','tags','category','audit') NOT NULL,
    token_usage INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL,
    INDEX ai_generations_user_id_index (user_id),
    INDEX ai_generations_type_index (generation_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 18. AI Content Audits

```sql
CREATE TABLE ai_content_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    readability_score DECIMAL(5,2) DEFAULT 0.00,
    seo_score DECIMAL(5,2) DEFAULT 0.00,
    keyword_density DECIMAL(5,2) DEFAULT 0.00,
    recommendations JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ai_content_audits_post_id_index (post_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
```

---

## 19. Analytics System

```sql
CREATE TABLE page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(500) NOT NULL,
    ip_hash VARCHAR(64) NOT NULL,
    country VARCHAR(2) NULL,
    device_type ENUM('desktop','tablet','mobile') NULL,
    browser VARCHAR(100) NULL,
    visited_at TIMESTAMP NOT NULL,
    INDEX page_views_visited_at_index (visited_at),
    INDEX page_views_page_url_index (page_url)
);

CREATE TABLE post_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    ip_hash VARCHAR(64) NOT NULL,
    country VARCHAR(2) NULL,
    device_type ENUM('desktop','tablet','mobile') NULL,
    visited_at TIMESTAMP NOT NULL,
    INDEX post_views_post_id_index (post_id),
    INDEX post_views_visited_at_index (visited_at),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
```

---

## 20. Content Metrics (Trending)

```sql
CREATE TABLE content_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL UNIQUE,
    daily_views INT UNSIGNED DEFAULT 0,
    weekly_views INT UNSIGNED DEFAULT 0,
    monthly_views INT UNSIGNED DEFAULT 0,
    engagement_score DECIMAL(5,2) DEFAULT 0.00,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
```

---

## 21. Search Logs

```sql
CREATE TABLE search_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    results_count INT UNSIGNED DEFAULT 0,
    user_id BIGINT UNSIGNED NULL,
    searched_at TIMESTAMP NOT NULL,
    INDEX search_logs_keyword_index (keyword),
    INDEX search_logs_searched_at_index (searched_at)
);
```

---

## 22. Notifications

```sql
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX notifications_notifiable_index (notifiable_type, notifiable_id)
);
```

---

## 23. Settings

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(255) NOT NULL UNIQUE,
    value TEXT NULL,
    group_name VARCHAR(100) NOT NULL DEFAULT 'general',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX settings_group_index (group_name)
);
```

---

## 24. Activity Logs

```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_name VARCHAR(255) NULL,
    description TEXT NOT NULL,
    subject_type VARCHAR(255) NULL,
    event VARCHAR(255) NULL,
    subject_id BIGINT UNSIGNED NULL,
    causer_type VARCHAR(255) NULL,
    causer_id BIGINT UNSIGNED NULL,
    properties JSON NULL,
    batch_uuid CHAR(36) NULL,
    created_at TIMESTAMP NULL,
    INDEX activity_logs_log_name_index (log_name),
    INDEX activity_logs_causer_index (causer_type, causer_id),
    INDEX activity_logs_subject_index (subject_type, subject_id)
);
```

---

## 25. Backup & Recovery Strategy

| Backup Type | Frequency | Retention | Contents |
|-------------|-----------|-----------|----------|
| Database | Daily | 30 days | Full MySQL dump |
| Files | Daily | 30 days | storage/app, public/storage |
| Database | Weekly | 12 weeks | Full MySQL dump |
| Files | Weekly | 12 weeks | All uploads |
| Database | Monthly | 12 months | Full MySQL dump |
| Files | Monthly | 12 months | All files |

Recovery: Restore latest backup → run pending migrations → clear cache

---

## 26. Indexing Strategy

### Composite Indexes for Common Queries

```sql
-- Posts listing (status + published_at for pagination)
ALTER TABLE posts ADD INDEX posts_listing_index (status, published_at DESC);

-- Posts by category (category + status + date)
ALTER TABLE posts ADD INDEX posts_category_listing_index (category_id, status, published_at DESC);

-- Author posts
ALTER TABLE posts ADD INDEX posts_author_listing_index (author_id, status, published_at DESC);
```

---

## 27. Performance Optimization

- Archiving: Posts with status `archived` moved to `archived_posts` table after 1 year
- Partitioning: Future-ready for `page_views` and `post_views` by month
- Query optimization: Select explicit columns, avoid SELECT *
- Eager loading: N+1 prevention on all relationships

---

## 28. Future Scalability

The architecture supports:
- **Multi-language**: Add `locale` column to posts, categories, tags, pages
- **Multi-site**: Add `site_id` to all content tables
- **Membership**: Add `memberships` and `subscriptions` tables
- **E-commerce**: Add `products` table with polymorphic pricing
- **API/GraphQL**: Existing relationships support any API layer

---

## 29. Database Documentation

Complete schema documentation stored in `docs/database/`:
- `ERD.md` — Entity relationship descriptions
- `MIGRATIONS.md` — Migration execution order
- `INDEXES.md` — Index specifications
- `SEEDERS.md` — Seed data strategy

---

## 30. Final Output

**Phase 3 complete.** Enterprise database architecture defined with:

- 20+ tables with full schemas
- All relationships documented (1:1, 1:N, M:N, polymorphic)
- Indexing strategy (single + composite)
- Performance optimization guidelines
- Backup & recovery strategy
- Future scalability provisions

Ready to proceed to **Phase 4** — Authentication, Authorization & User Access Management.
