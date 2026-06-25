# PHASE 6 — CATEGORY MANAGEMENT SYSTEM (ENTERPRISE CONTENT TAXONOMY)

## 1. Category System Architecture

### Architecture Design
```
categories (self-referencing)
    ├── Level 0: Root categories (parent_id = null)
    │   └── Level 1: Sub categories
    │       └── Level 2: Nested categories
    │           └── Level N: Unlimited nesting
```

**Why this architecture:** Self-referencing parent_id allows unlimited nesting without schema changes, simple querying via recursive relationships, SEO-friendly URL paths.

---

## 2. Category Database Design

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    short_description VARCHAR(500) NULL,
    full_description TEXT NULL,
    image VARCHAR(255) NULL,
    icon VARCHAR(100) NULL,
    color VARCHAR(7) NULL,
    sort_order INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('draft','published','archived','hidden') DEFAULT 'published',
    article_count INT UNSIGNED DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX categories_slug_index (slug),
    INDEX categories_parent_id_index (parent_id),
    INDEX categories_status_index (status),
    INDEX categories_featured_index (featured),
    INDEX categories_sort_order_index (sort_order),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 3. Category Relationships

```
Category
    ├── hasMany(Post)          — posts in this category
    ├── hasMany(Category)      — child categories (self-ref)
    └── belongsTo(Category)    — parent category (self-ref)
```

### Query Optimization
- Eager load parent/children for tree rendering
- Cache full category tree in Redis
- Composite index on (parent_id, sort_order) for ordering
- Use `withCount('posts')` for article counts (or use cached column)

---

## 4. Category CRUD System

| Action | Permissions | Features |
|--------|------------|----------|
| Create | create_categories | Generate slug, set parent, SEO fields |
| Read | view_category | Tree view, table view, search |
| Update | edit_categories | SEO, image, hierarchy, status |
| Delete | delete_categories | Check for posts, reassign or cascade |
| Restore | edit_categories | Restore soft-deleted category |
| Duplicate | create_categories | Clone category and all settings |
| Bulk | edit_categories | Status change, delete, move parent |

---

## 5. Category Hierarchy Management

### Unlimited Nesting
```
Technology (parent_id: null)
├── Web Development (parent_id: 1)
│   ├── Laravel (parent_id: 2)
│   ├── React (parent_id: 2)
│   └── Vue.js (parent_id: 2)
└── Artificial Intelligence (parent_id: 1)
    ├── Machine Learning (parent_id: 5)
    └── NLP (parent_id: 5)
```

### Tree Visualization
- Admin panel uses recursive tree component (Alpine.js)
- Drag-and-drop reordering within same parent
- Collapse/expand for large trees

---

## 6. Category URL Structure

| Level | URL Pattern | Example |
|-------|------------|---------|
| Root | /category/{slug} | /category/technology |
| Level 1 | /category/{parent}/{slug} | /category/technology/web-development |
| Level 2+ | /category/{...ancestors}/{slug} | /category/technology/web-development/laravel |

### Implementation
- Slug path generated recursively from ancestors
- Canonical URL set to full path
- 301 redirects on slug changes

---

## 7. Category SEO System

| SEO Field | Auto-Generate? | Source |
|-----------|---------------|--------|
| Meta Title | Yes | "Category Name - Website Name" |
| Meta Description | Yes | Short description truncated |
| Canonical URL | Yes | Full category URL |
| OG Title | Yes | Meta title |
| OG Description | Yes | Meta description |
| OG Image | Yes | Category image |
| Schema Type | Yes | CollectionPage |

All fields manually editable per category.

---

## 8. Category Landing Page

### Page Layout
```
Header: Category name + description + image
Breadcrumb: Home > Category > Subcategory
Featured Articles: Grid of featured posts
Latest Articles: Paginated list, sortable
Sidebar: Subcategories, popular tags
```

---

## 9. Category Filtering System

| Filter | Implementation |
|--------|---------------|
| Date | `published_at` range filter |
| Popularity | Order by `views_count` |
| Author | Filter by `author_id` |
| Tags | Filter by tag relationship |
| Featured | `featured = true` only |

All filters use query string parameters: `/category/laravel?sort=popular&date=2024`

---

## 10. Category Search System

- **Admin:** Instant search with debounce (300ms), matches name and slug
- **Frontend:** Use Meilisearch for category in global search
- **Cache:** Full category tree cached in Redis (TTL: 86400s)

---

## 11. Featured Category System

Featured categories displayed on:
- Homepage hero section (top 6)
- Sidebar widget
- Navigation mega menu
- Footer links

Admin controls featured status per category.

---

## 12. Category Image Management

| Image Type | Size | Format | Purpose |
|-----------|------|--------|---------|
| Featured | 1200×628 | WebP | Landing page hero |
| Thumbnail | 300×300 | WebP | Card displays |
| Banner | 1920×400 | WebP | Category header |
| Icon | 64×64 | SVG | Navigation |

All images: Spatie Media Library conversions, lazy loaded.

---

## 13. Category Icon System

- SVG icons stored in `resources/icons/categories/`
- Admin can select from icon library (Heroicons, FontAwesome free)
- Custom SVG upload supported
- Icons rendered inline for SEO (not as external images)

---

## 14. Category Color System

- Hex color stored per category (e.g. `#3B82F6`)
- Used for: badges, category cards, navigation highlights
- Admin can pick color via color picker
- Default colors generated from category name hash

---

## 15. Category Status Management

| Status | Visibility | Behavior |
|--------|-----------|----------|
| Draft | Admin only | Not shown on frontend |
| Published | Public | Normal display, indexable |
| Archived | Hidden | Posts preserved but not indexed |
| Hidden | Not listed | Direct URL access works, not in navigation |

---

## 16. Article Count System

- Column `article_count` on categories table
- Updated via observer: `CategoryObserver::updated()`
- Queue job for bulk recount after imports
- Count includes only published posts (not drafts/archived)

---

## 17. Category Navigation System

### Desktop: Mega Menu
```
[Technology ▾]  [Business ▾]  [Lifestyle ▾]
├── Web Dev         ├── Startups     ├── Health
├── AI              ├── Marketing    ├── Travel
└── Security        └── Finance      └── Food
```

### Mobile: Accordion Menu
- Collapsible sections
- Tap to expand subcategories
- Max 3 levels deep on mobile

---

## 18. Category Breadcrumb System

### Example
```
Home > Technology > Web Development > Laravel
```

- Automatically generated from category ancestors
- Schema.org BreadcrumbList markup included
- Each segment is a clickable link

---

## 19. Category Analytics

| Metric | How Tracked |
|--------|------------|
| Page Views | Category landing page tracked via `page_views` |
| Top Categories | Cached weekly ranking |
| CTR | Clicks on category links / impressions |
| Avg Time | Session duration on category pages |
| Subscriber Growth | Newsletter signups per category (future) |

All accessible in admin Analytics section.

---

## 20. Final Output

**Phase 6 complete.** Enterprise Category Management System:
- Self-referencing category hierarchy (unlimited nesting)
- Complete CRUD with permissions
- SEO-optimized URL structure with breadcrumbs
- Category landing pages with featured/filtered content
- Image, icon, and color management
- Status workflow (draft→published→archived→hidden)
- Category navigation (mega menu desktop, accordion mobile)
- Analytics tracking per category
- Full caching strategy

Ready to proceed to **Phase 7**.
