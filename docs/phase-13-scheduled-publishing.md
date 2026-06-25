# PHASE 13 — SCHEDULED PUBLISHING & CONTENT AUTOMATION SYSTEM

## 1. Architecture
Queue-driven: Schedule → Dispatch → Worker → Publish → Invalidate → Notify

## 2. Schema
```sql
CREATE TABLE scheduled_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    post_id BIGINT UNSIGNED NOT NULL,
    job_type ENUM('publish','update','unpublish') NOT NULL,
    scheduled_at TIMESTAMP NOT NULL,
    executed_at TIMESTAMP NULL,
    status ENUM('pending','queued','processing','completed','failed') DEFAULT 'pending',
    retry_count INT UNSIGNED DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    INDEX scheduled_jobs_at_index (scheduled_at, status),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
```

## 3. Workflow
1. User schedules post with `scheduled_at`
2. Queue job dispatched with `delay($scheduled_at)`
3. Worker validates post state at execution time
4. Post status updated to `published`, `published_at` set
5. Cache invalidated (homepage, category, tag, post pages)
6. Sitemap regeneration queued
7. Notification sent to author

## 4. Queues
- `publish-posts` (high priority): Publishing jobs
- `seo-update` (medium): Sitemap/schema updates
- `analytics-processing` (low): Batch processing

## 5. Retry Logic
- Max 3 retries with exponential backoff (30s, 2min, 5min)
- Failed jobs logged in `failed_jobs` table
- Admin notified via database notification

## 6. Timezone
- All schedules stored in UTC
- User interface shows local timezone from profile settings
- Conversion: `Carbon::parse($scheduled_at)->setTimezone($user->timezone)`

## 7. State Machine
`Draft → Scheduled → Queued → Publishing → Published → Archived → Failed`

## 8. AI Pre-Publish Checks
- Grammar validation
- SEO score check (>60 required)
- Meta description completeness
- Image alt text validation

**Phase 13 complete.**
