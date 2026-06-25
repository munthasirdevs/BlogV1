# PHASE 8 — BLOG POST SYSTEM (CORE CONTENT ENGINE)

## 1. Post System Architecture

**Core principle:** Content as a lifecycle object — not a static record.

### Architecture Layers
```
PostController (request handling)
    → PostService (business logic)
        → Post model (data access)
            → PostObserver (cache invalidation)
                → PostResource (API transformation)
```

### Post Lifecycle
```
Draft → In Review → SEO Optimization → AI Enhancement → Scheduled → Published → Archived
```

---

## 2. Database Design — Posts Table

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
    content_format ENUM('html','markdown') DEFAULT 'html',
    status ENUM('draft','review','seo_review','approved','scheduled','published','archived') DEFAULT 'draft',
    visibility ENUM('public','private','unlisted') DEFAULT 'public',
    is_featured BOOLEAN DEFAULT FALSE,
    is_scheduled BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    scheduled_at TIMESTAMP NULL,
    reading_time INT UNSIGNED DEFAULT 0,
    word_count INT UNSIGNED DEFAULT 0,
    views_count BIGINT UNSIGNED DEFAULT 0,
    likes_count INT UNSIGNED DEFAULT 0,
    shares_count INT UNSIGNED DEFAULT 0,
    seo_score DECIMAL(5,2) DEFAULT 0.00,
    ai_score DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX posts_slug_index (slug),
    INDEX posts_status_published_index (status, published_at),
    INDEX posts_author_id_index (author_id),
    INDEX posts_category_id_index (category_id),
    FULLTEXT posts_search_index (title, content),
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

---

## 3. Post Relationships

```
Post
├── belongsTo(User)           → author
├── belongsTo(Category)       → category
├── belongsToMany(Tag)        → tags (via post_tag)
├── hasMany(Comment)          → comments
├── hasMany(PostRevision)     → revisions
├── morphOne(SeoMeta)         → seo
├── morphMany(MediaFile)      → media
└── hasOne(ContentMetric)     → metrics
```

---

## 4. Post Lifecycle Management

| State | Entry Conditions | Actions |
|-------|-----------------|---------|
| Draft | Title required | Author writes content |
| In Review | Content + excerpt required | Editor notified |
| SEO Review | Editor reviews SEO fields | AI suggests improvements |
| Approved | SEO score > 60 | Ready for scheduling |
| Scheduled | scheduled_at set | Queue job waits |
| Published | published_at ≤ now | Visible to public |
| Archived | Status change only | Removed from public index |

---

## 5. Post CRUD System

| Action | Permission | Key Features |
|--------|-----------|-------------|
| Create | create_post | Auto-slug, auto-excerpt, autosave drafts |
| Read | view (own/all) | Eager load relationships, cache |
| Update | edit_post | Revision creation, cache invalidation |
| Delete | delete_post | Soft delete, cascade comments |
| Restore | edit_post | Restore all related data |
| Duplicate | create_post | Clone content, reset status to draft |

### Auto-Generation
- **Slug:** From title, unique check, append number if duplicate
- **Excerpt:** First 160 chars of content (configurable)
- **Reading time:** `word_count / 200` rounded up
- **Word count:** `str_word_count(strip_tags(content))`

---

## 6. Content Editor System

### Rich Text Editor: Tiptap (free, open-source)
- Based on ProseMirror
- Extensible with custom nodes
- Lightweight (40kB gzipped)

### Features
- Bold, italic, headings (H2-H4), links, lists
- Code blocks with syntax highlighting
- Media embedding (images, videos)
- Tables
- AI writing assistant button
- Auto-save every 30 seconds via AJAX

### Markdown Mode
- Toggle between visual editor and raw markdown
- Markdown stored as-is when in markdown mode

---

## 7. Post Revision System

```sql
CREATE TABLE post_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NULL,
    excerpt TEXT NULL,
    editor_id BIGINT UNSIGNED NULL,
    revision_number INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX post_revisions_post_id_index (post_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
```

### Features
- Auto-create revision on every save when status is "draft" or "review"
- Compare any two revisions side-by-side
- Restore: copy revision content back to post (creates new revision)
- Max 50 revisions per post (oldest auto-pruned)

---

## 8. Post Publishing System

### Modes
| Mode | Trigger | Process |
|------|---------|---------|
| Immediate | Editor clicks "Publish" | status = published, published_at = now |
| Scheduled | Editor sets future date | status = scheduled, queue job at scheduled_at |
| Approval | Author submits draft | status = review, editor notified |

### Queue Job
```php
SchedulePostPublishing::dispatch($post)->delay($post->scheduled_at);
// On publish: update status, clear cache, notify subscribers, ping sitemap
```

---

## 9. SEO System Integration

### Per-Post SEO (stored in seo_meta polymorphic)
| Field | Auto-Generate | AI Enhance |
|-------|:------------:|:----------:|
| meta_title | Title (truncated 60 chars) | ✅ |
| meta_description | Excerpt (truncated 160 chars) | ✅ |
| canonical_url | Post URL | — |
| og:title | meta_title | ✅ |
| og:description | meta_description | ✅ |
| og:image | featured_image | — |
| schema_type | Article | — |

### SEO Score
Calculated from: title length, description length, keyword presence, image alt texts, heading structure, internal links.

---

## 10. AI Content System (NVIDIA)

### AI Capabilities
| Feature | Input | Output | Prompt |
|---------|-------|--------|--------|
| Generate article | Topic + outline | Full article | "Write a blog post about {topic}" |
| Generate titles | Content | 5-10 titles | "Suggest 10 engaging titles" |
| Generate meta | Content | Meta title + desc | "Generate SEO metadata" |
| Rewrite | Content | Improved version | "Rewrite for clarity" |
| Expand | Content | Expanded version | "Expand this section" |
| Keywords | Content | Keyword list | "Extract key SEO keywords" |

### Workflow
1. User clicks AI button in editor
2. Modal displays AI options
3. Content sent to NVIDIA API
4. Response shown to user
5. User approves/rejects/modifies
6. Accepted content inserted into editor

---

## 11. Featured Content System

| Type | Selection | Rotation |
|------|-----------|----------|
| Featured | Admin picks | Manual |
| Trending | Algorithm (views/24h) | Daily |
| Recommended | Based on tags/category | Per request |
| Editor's Picks | Editor selects | Manual |

### Featured Flag
Column `is_featured` on posts. Displayed in:
- Homepage hero section
- Sidebar "Featured" widget
- Category page top section

---

## 12. Post Analytics System

| Metric | Storage | Update |
|--------|---------|--------|
| Page views | Redis counter → DB batch | Every view increments Redis |
| Unique visitors | Redis set (IP hash) → DB | Daily rollup |
| Reading time | JavaScript beacon | On page unload |
| Scroll depth | JavaScript beacon | On 25%/50%/75%/100% |
| Likes | Counter column | Immediate |
| Shares | Counter column | Via social button click |

### DB Schema
```sql
-- page_views and post_views tables (from Phase 3)
```

---

## 13. Related Posts Engine

### Algorithm (weighted)
```
score = (same_category × 0.4) + (shared_tags × 0.3) + (ai_similarity × 0.3)
```

### Implementation
- Cache results in Redis: `related:{post_id}` (TTL: 3600s)
- Recalculate on post update
- Fallback to latest posts if no related found
- Display 3-6 related posts at bottom of article

---

## 14. Post Search System

- **Full-text:** MySQL FULLTEXT index on `title` + `content`
- **Scout/Meilisearch:** Real-time indexing of posts
- **Filters:** Category, tag, date range, author
- **Sort:** Relevance, date, popularity

---

## 15. Post Visibility System

| Visibility | Behavior | Who Can See |
|-----------|----------|------------|
| Public | Indexed, listed | Everyone |
| Private | Not listed, direct URL works | Author + Editor+ |
| Unlisted | Not indexed, direct URL | Anyone with link |

---

## 16. Post Scheduling System

- Set `scheduled_at` via datetime picker
- On save: dispatch `PublishScheduledPost` job
- Job checks `scheduled_at <= now()` every minute
- On publish: clear cache, ping sitemap, notify subscribers
- Timezone handled via config (`app.timezone = UTC`)

---

## 17. Auto Content Enhancement

AI-powered post-processing:
- Grammar check and correction
- Readability improvement (shorter sentences, simpler words)
- SEO keyword suggestions and insertion
- Heading structure improvement (H2/H3 balance)

---

## 18. Media Integration

- **Featured image:** Single image, required for publish
- **Inline images:** Drag-drop into editor, auto-upload via AJAX
- **Video embeds:** Auto-convert YouTube/Vimeo URLs to embeds
- **Documents:** PDF, DOCX links supported

Optimization: WebP conversion, responsive srcset, lazy loading.

---

## 19. Comment Integration

- Comments displayed at bottom of post
- Nested (2 levels deep)
- Moderation queue for new users
- Spam filtering (Akismet or reCAPTCHA v3 free)
- Guest comments with email verification

---

## 20. Post Permissions System

| Permission | Super Admin | Admin | Editor | Author | Contributor |
|------------|:---------:|:----:|:-----:|:-----:|:----------:|
| create_post | ✅ | ✅ | ✅ | ✅ | ✅ |
| edit_post | ✅ | ✅ | ✅ | ✅ (own) | ✅ (own draft) |
| delete_post | ✅ | ✅ | ✅ | ✅ (own draft) | ❌ |
| publish_post | ✅ | ✅ | ✅ | ❌ | ❌ |
| schedule_post | ✅ | ✅ | ✅ | ❌ | ❌ |
| feature_post | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## 21. Post Performance Optimization

- **Redis caching:** Post pages cached 3600s, invalidated on update
- **Eager loading:** Always load category, tags, author with post
- **Query optimization:** Select only needed columns on listings
- **CDN:** Featured images served via CDN (public/storage symlink)
- **Lazy loading:** Comments loaded via AJAX after page render

---

## 22. Post Activity Logging

Events: created, updated, published, scheduled, archived, restored, seo_updated, ai_generated
Using Spatie Activitylog with subject → Post model.

---

## 23. Post Import/Export

| Format | Export | Import |
|--------|--------|--------|
| CSV | Title, slug, excerpt, status, category | Batch create/update |
| JSON | Full post with relationships | Full restore/migration |

---

## 24. Post Archiving System

- Archive: `status = archived`, removed from public queries
- Restore: Change status back to draft or published
- Auto-archive: Posts older than 2 years (configurable, opt-in)
- Soft delete: 30-day recovery window before force delete

---

## 25. Security System

- XSS: HTML Purifier on rich content (strip dangerous tags)
- SQL Injection: Eloquent prepared statements
- CSRF: Laravel token on all forms
- Rate limiting: 10 posts/hour per author (configurable)
- Validation: Required fields per status, slug uniqueness

---

## 26. Final Output

**Phase 8 complete.** Core Blog Post System:
- Complete post lifecycle (7 stages)
- Full database schema with indexes and fulltext search
- All CRUD operations with permissions
- Tiptap rich text editor with autosave
- Revision system (50 versions, compare, restore)
- Publishing modes (immediate, scheduled, approval)
- AI integration for content generation and enhancement
- Analytics tracking (views, reads, scroll depth)
- Related posts engine (weighted algorithm)
- Full-text and Meilisearch integration
- Visibility modes (public, private, unlisted)
- Media and comment integration
- Performance optimization and caching

Ready to proceed to **Phase 9**.
