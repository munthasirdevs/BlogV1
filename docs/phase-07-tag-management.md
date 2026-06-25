# PHASE 7 — TAG MANAGEMENT SYSTEM (ENTERPRISE CONTENT DISCOVERY & SEO TAXONOMY)

## 1. Tag System Architecture

**Flat taxonomy** — no hierarchy. Tags are lightweight, flexible keywords.

### Why Flat?
- Faster queries (no recursive lookups)
- Easier AI generation
- Simpler management
- Better for SEO keyword targeting

### Difference from Categories
| Feature | Categories | Tags |
|---------|-----------|------|
| Hierarchy | Hierarchical | Flat |
| Purpose | Content organization | Content discovery |
| Count | Limited (10-50) | Unlimited |
| SEO Focus | Broad topics | Specific keywords |
| URL | /category/{path} | /tag/{slug} |

---

## 2. Tag Database Design

```sql
CREATE TABLE tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    color VARCHAR(7) NULL,
    usage_count INT UNSIGNED DEFAULT 0,
    trending_score DECIMAL(5,2) DEFAULT 0.00,
    seo_title VARCHAR(255) NULL,
    seo_description TEXT NULL,
    canonical_url VARCHAR(500) NULL,
    status ENUM('active','hidden') DEFAULT 'active',
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX tags_slug_index (slug),
    INDEX tags_usage_count_index (usage_count DESC),
    INDEX tags_trending_score_index (trending_score DESC),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 3. Post-Tag Relationship

```sql
CREATE TABLE post_tag (
    post_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    relevance_score DECIMAL(5,2) DEFAULT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (post_id, tag_id),
    INDEX post_tag_tag_id_index (tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

---

## 4. Tag CRUD System

| Action | Permission | Details |
|--------|-----------|---------|
| Create | create_tag | Auto-slug, unique validation |
| Read | global | Index page with search/filter |
| Update | edit_tag | Name, slug, SEO fields |
| Delete | delete_tag | Confirm usage, reassign option |
| Restore | edit_tag | Restore soft-deleted |
| Merge | merge_tag | Combine two tags into one |
| Bulk | edit_tag | Status, delete, merge |

---

## 5. Tag SEO System

### Per-Tag SEO Fields
| Field | Auto-Generated | Manual Override |
|-------|:-------------:|:--------------:|
| Meta Title | "Tag Name - Blog Name" | ✅ |
| Meta Description | Auto from description | ✅ |
| Canonical URL | /tag/{slug} | ✅ |
| OG Title | Meta title | ✅ |
| OG Description | Meta description | ✅ |
| Schema Type | CollectionPage | Fixed |

---

## 6. Tag Landing Pages

URL: `/tag/{slug}`

### Page Content
- Tag title + description
- Breadcrumb: Home > Tag > {tag name}
- Post listing (paginated, 12 per page)
- Sidebar: related tags, trending posts
- Noindex if `usage_count < 2` (thin content prevention)

### Caching
- Cache page for 3600s
- Invalidate on tag update or new post with tag

---

## 7. Tag Search System

- **Admin:** AJAX live search with 300ms debounce, matches name/slug
- **Frontend:** Global search includes tags
- **Meilisearch:** Tag index for instant autocomplete
- **Redis:** Top 100 tags cached for autocomplete

---

## 8. Tag Cloud System

### Weighted Display
```
[laravel]  [AI]  [php]  [web-dev]  [javascript]
[api]  [security]  [database]  [devops]  [testing]
```

- Font size based on `usage_count` (min 12px, max 36px)
- Color based on tag color or random from palette
- Click navigates to `/tag/{slug}`

### Display Locations
- Homepage sidebar widget
- Blog listing sidebar
- Footer widget

---

## 9. Trending Tag System

### Trending Score Calculation
```
trending_score = (posts_last_7_days × 0.5) + (total_engagement × 0.3) + (AI_boost × 0.2)
```

- Recalculated daily via scheduled job
- Top 20 trending tags cached in Redis (TTL: 86400s)
- Displayed in sidebar "Trending Topics" widget

---

## 10. AI Tag Generation System

### Workflow
1. Post content submitted
2. NVIDIA API analyzes content (keywords, topics, entities)
3. AI suggests 3-10 relevant tags with relevance scores
4. Editor approves/rejects suggestions
5. Approved tags assigned to post

### Prompt Template
```
Analyze the following blog post content and suggest 5-10 relevant tags.
Return as JSON array with tag names and relevance scores (0-1).
Content: {post_content}
```

---

## 11. Tag Relation Engine

### Related Tags
Based on co-occurrence frequency (tags that appear together on same posts)

```
Post A: [laravel, php, api]
Post B: [laravel, php, testing]
→ Related: laravel ↔ php (2x), api ↔ testing (1x)
```

Stored in Redis: `related_tags:{tag_id}` → array of tag IDs with weights

---

## 12. Tag Merging System

### Merge Workflow
1. Select source tag(s) and target tag
2. Update all `post_tag` records (source → target)
3. Preserve higher `usage_count`
4. Create 301 redirect: `/tag/{source-slug}` → `/tag/{target-slug}`
5. Soft delete source tag
6. Log activity

---

## 13. Tag Splitting System

### Split Workflow
1. Select tag to split
2. Specify multiple target tags
3. Bulk reassign posts proportionally
4. Create 301 redirects for all splits
5. Keep original tag or archive

---

## 14. Tag Filter System

### Filter Modes
- **AND**: Posts with ALL selected tags
- **OR**: Posts with ANY selected tag

URL: `/tag/laravel+php?mode=and`

SQL optimized with `HAVING COUNT(*) = N` for AND mode.

---

## 15. Tag Analytics

| Metric | Tracking Method |
|--------|----------------|
| Tag page views | `page_views` table |
| Tag click-through | Click events on tag links |
| Posts per tag | `usage_count` column |
| Trending score | Daily calculation |

---

## 16. Tag Permissions

| Permission | Super Admin | Admin | Editor | Author |
|------------|:---------:|:----:|:-----:|:-----:|
| create_tag | ✅ | ✅ | ✅ | ❌ |
| edit_tag | ✅ | ✅ | ✅ | ❌ |
| delete_tag | ✅ | ✅ | ✅ | ❌ |
| merge_tag | ✅ | ✅ | ❌ | ❌ |
| manage_tag_seo | ✅ | ✅ | ✅ | ❌ |

---

## 17. Tag Activity Logging

Events logged: create, update, delete, restore, merge, split, SEO change
Using Spatie Activitylog with subject → Tag model.

---

## 18. Tag Cache System

| Cache Key | TTL | Invalidation |
|-----------|-----|-------------|
| tags:all | 3600s | Tag CRUD |
| tags:trending | 86400s | Daily recalculation |
| tag:{slug}:page | 3600s | Tag/post update |
| tags:autocomplete | 3600s | Tag creation/deletion |

---

## 19. Tag Performance Optimization

- Indexes: `slug` (UNIQUE), `usage_count` (DESC), `trending_score` (DESC)
- Pivot: Composite primary key (post_id, tag_id) + index on tag_id
- Cache heavy queries (tag cloud, trending, related)
- Full-text index on `name` for search

---

## 20. Tag Import/Export

- **Export:** CSV with columns (name, slug, description, color, seo_title, seo_description)
- **Import:** CSV upload with validation, duplicate detection, batch processing via queue

---

## 21. Tag Cleanup System

Scheduled job (weekly):
- Remove tags with `usage_count = 0` older than 30 days
- Merge duplicate tags (same name, different slug)
- Normalize: lowercase, trim whitespace, remove special chars

---

## 22. Tag Security System

- Validation: `string|max:100|unique:tags,name` on create/update
- XSS: Blade auto-escaping on all tag output
- Slug: Auto-generated, sanitized (alphanumeric + hyphens)
- Permission checks on all tag mutations

---

## 23. Final Output

**Phase 7 complete.** Enterprise Tag Management System:
- Flat tag architecture for fast lookups
- Complete CRUD with merge, split, bulk operations
- SEO-optimized tag pages with metadata
- AI-powered auto-tagging via NVIDIA API
- Tag relation engine (co-occurrence)
- Trending score algorithm
- Tag cloud with weighted display
- Redis caching for all tag data
- Analytics tracking per tag
- Cleanup and normalization jobs

Ready to proceed to **Phase 8**.
