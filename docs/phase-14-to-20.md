# PHASE 14 — CONTENT APPROVAL WORKFLOW SYSTEM

## 1. Workflow
Draft → Submit for Review → SEO Review → Editor Approval → Schedule → Publish → Archive

## 2. States
- `draft`: Author editing
- `in_review`: Submitted to editor
- `seo_review`: SEO optimization phase
- `approved`: Ready to schedule
- `scheduled`: Queue for publishing
- `published`: Live
- `archived`: Removed from public

## 3. Notifications
- Author notified on status change
- Editor notified when draft submitted for review
- Admin notified on publishing failure

## 4. Rules
- Contributors: must submit for review
- Authors: auto-submit on "request publish"
- Editors: can approve/reject
- Rejection requires reason

## 5. AI Review
- Auto check SEO score before approval
- Grammar check on submit
- Plagiarism check (future)

**Phase 14 complete.**

# PHASE 15 — SEO INTELLIGENCE SYSTEM

## 1. Architecture
Content → SEO Analyzer → Score Calculation → Recommendations → Auto-Optimization

## 2. Analysis Dimensions
- Title: length (50-60 chars), keyword presence
- Description: length (150-160 chars), keyword presence
- Headings: H1/H2 structure, keyword distribution
- Content: word count (>800), readability (Flesch-Kincaid)
- Images: alt text presence, file size optimization
- Links: internal (min 2), external quality
- URL: slug length, keyword inclusion

## 3. Scoring
```
seo_score = (title × 0.20) + (description × 0.15) + (headings × 0.15) + 
            (content × 0.20) + (images × 0.10) + (links × 0.10) + (url × 0.10)
```

## 4. Auto-Optimization (NVIDIA)
- Generate meta title from content
- Generate meta description
- Suggest keywords
- Rewrite headings for SEO
- Suggest internal links

## 5. Schema Markup
- Article schema on all posts
- BreadcrumbList on category/tag pages
- Organization on homepage
- Person on author pages
- FAQ schema when applicable

**Phase 15 complete.**

# PHASE 16 — SEMANTIC SEO ENGINE

## 1. Architecture
Content → NLP Analysis → Entity Extraction → Topic Clustering → Semantic Linking

## 2. Entity Extraction (NVIDIA)
- Named entities (people, places, organizations)
- Topics and subtopics
- Keywords and keyphrases
- Sentiment analysis

## 3. Internal Linking
- Automatically suggest internal links based on entity matching
- Link relevance score calculation
- Prevent over-linking (max 5 internal links per 1000 words)

## 4. Content Clusters
- Group related content by topic
- Generate pillar page → cluster page structure
- Interlink cluster pages to pillar page
- Track cluster SEO performance

**Phase 16 complete.**

# PHASE 17 — COMMENTS & ENGAGEMENT SYSTEM

## 1. Schema
```sql
CREATE TABLE comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(255) NULL,
    guest_email VARCHAR(255) NULL,
    body TEXT NOT NULL,
    status ENUM('pending','approved','spam','trash') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);
```

## 2. Features
- Nested comments (max 2 levels deep)
- Guest commenting with email verification
- User commenting (auto-approved for trusted users)
- Moderation queue for pending comments
- Spam filtering (Akismet free or reCAPTCHA v3)
- Like/reply actions

## 3. Moderation
- Auto-approve for users with >5 approved comments
- Flag for moderation: new users, links in content, excessive caps
- Bulk approve/reject/delete in admin

## 4. Notifications
- Author notified on new comment
- Reply notification to parent commenter
- Moderation notification to editors

**Phase 17 complete.**

# PHASE 18 — USER AUTHENTICATION & ROLES SYSTEM

(Covered in Phases 4-5. This phase consolidates and refines.)

| Feature | Implementation |
|---------|---------------|
| Registration | Email + username, email verification |
| Login | Email or username, remember me |
| Password Reset | Email token, 60min expiry |
| Email Verification | Verification link, resend capability |
| Session Management | Redis, multi-device, expiry |
| Account Status | Active, pending, suspended, banned |

**Phase 18 complete.**

# PHASE 19 — ADMIN DASHBOARD & CONTROL PANEL

## 1. Navigation
```
Dashboard
├── Overview (stats, quick actions)
├── Content
│   ├── Posts
│   ├── Categories
│   └── Tags
├── Media
├── Comments
├── Users
├── SEO
│   ├── Sitemap
│   └── Redirects
├── AI Tools
├── Analytics
└── Settings
```

## 2. Dashboard Widgets
- Total posts, views, comments (real-time counts)
- Recent posts (last 5)
- Pending comments count
- SEO score average
- Traffic chart (last 30 days)
- Quick actions (new post, new category)

## 3. DataTables
- Searchable, sortable, filterable
- Bulk actions (delete, publish, archive)
- Column visibility toggle
- Export to CSV
- Pagination (per page configurable)

## 4. Access Control
- Dynamic sidebar based on user roles/permissions
- Super Admin: all sections
- Admin: operational sections
- Editor: content sections
- Author: own content + media

**Phase 19 complete.**

# PHASE 20 — NOTIFICATION & EVENT SYSTEM

## 1. Types
- Database notifications (bell icon in admin header)
- Email notifications (queued)
- Browser notifications (optional, future)

## 2. Events
| Event | Recipients | Channel |
|-------|-----------|---------|
| Post scheduled | Author | DB + Email |
| Post published | Author | DB |
| Publish failed | Author + Admin | DB + Email |
| Comment received | Post author | DB + Email |
| Content approved | Author | DB |
| Content rejected | Author | DB + reason |
| User registered | Admin | DB |
| AI generation complete | User | DB |

## 3. Implementation
```php
// App\Notifications\PostPublished.php
class PostPublished extends Notification
{
    use Queueable;
    
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }
}
```

**Phase 20 complete.**
