# PHASE 12 — POST REVISION SYSTEM

## 1. Architecture
Editor → Revision Capture → Storage → Diff Engine → Restore Engine

## 2. Schema
```sql
CREATE TABLE post_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    post_id BIGINT UNSIGNED NOT NULL,
    editor_id BIGINT UNSIGNED NOT NULL,
    revision_number INT UNSIGNED NOT NULL,
    title_snapshot VARCHAR(255) NOT NULL,
    excerpt_snapshot TEXT NULL,
    content_snapshot LONGTEXT NULL,
    seo_snapshot JSON NULL,
    ai_generated BOOLEAN DEFAULT FALSE,
    ai_tool_used VARCHAR(100) NULL,
    change_summary VARCHAR(500) NULL,
    diff_hash VARCHAR(64) NULL,
    created_at TIMESTAMP NULL,
    INDEX post_revisions_post_index (post_id, revision_number),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (editor_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 3. Triggers
- Manual save, autosave interval, pre-publish, AI edit applied
- Max 50 per post (auto-prune oldest)
- Minor edits batched (within 5min window)

## 4. Diff Engine
- Word-level text diff (PHP FineDiff or similar free library)
- JSON diff for SEO metadata
- Side-by-side view in UI with insertions (green) and deletions (red)
- AI change summary: "Rewrote introduction, optimized 3 keywords"

## 5. Restore
- Copy revision content to post (creates NEW revision)
- No destructive operations
- Full audit trail preserved

## 6. AI Tracking
- Store AI tool used, prompt summary, impact score
- Track token consumption per revision

## 7. Permissions
- `view_revisions`: Editor+; `restore_revisions`: Editor+; `delete_revisions`: Admin only

## 8. Performance
- Redis: latest 5 revisions cached per post
- DB: indexed on (post_id, revision_number)
- Compression: LONGTEXT compressed at MySQL level

**Phase 12 complete.**
