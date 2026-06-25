# PHASE 5 — ROLE, PERMISSION & ENTERPRISE ACCESS CONTROL SYSTEM

## 1. Access Control Strategy

### Architecture Layers
```
┌─────────────────────────────────┐
│    Authentication Layer         │  Who is this?
│   (Breeze/Sanctum/Session)      │
├─────────────────────────────────┤
│    Authorization Layer          │  Are they allowed?
│   (Middleware/Gates)            │
├─────────────────────────────────┤
│    Permission Layer             │  What can they do?
│   (Spatie Permission)           │
├─────────────────────────────────┤
│    Resource Layer               │  What resource?
│   (Policies/Ownership)          │
└─────────────────────────────────┘
```

**Why RBAC:** Industry standard for CMS, flexible permission inheritance, supported by Spatie, scalable to thousands of users.

---

## 2. Role Hierarchy Design

```
Level 1:  Super Admin  ───── Full system ownership
Level 2:  Admin        ───── Operational management
Level 3:  Editor       ───── Content lifecycle management
Level 4:  Author       ───── Own content creation
Level 5:  Contributor  ───── Draft creation only
Level 6:  Registered   ───── Public user features
Level 7:  Guest        ───── Read-only access
```

Each level inherits permissions from all levels below it.

---

## 3. Super Admin Role

### Full Permissions
- User management (create, read, update, delete any user)
- Role & permission management (create/modify roles, assign permissions)
- Content management (all posts, pages, categories, tags)
- Media management (all files, folders)
- SEO management (meta, sitemaps, redirects, schema)
- AI management (models, usage limits, history)
- Settings management (general, SEO, email, security, AI)
- Analytics (full access, export)
- Backup management (run backups, restore)
- System configuration

**Restrictions:** None

---

## 4. Admin Role

### Permissions
- Content management (all posts, categories, tags)
- User management (view, create, edit users; cannot modify Super Admin)
- Media management (upload, edit, delete)
- SEO management (meta, redirects, sitemap)
- Analytics (view dashboards, export)
- Comments (moderate, approve, delete)

**Restrictions:** Cannot modify permission architecture, cannot change system ownership, cannot edit Super Admin accounts.

---

## 5. Editor Role

### Permissions
- Create, edit, delete any post
- Publish, schedule, archive posts
- Manage categories (CRUD)
- Manage tags (CRUD)
- Moderate comments
- Manage SEO metadata on posts/pages
- Use AI tools

**Restrictions:** No user management, no settings access, no permission management.

---

## 6. Author Role

### Permissions
- Create posts
- Edit own posts (not others')
- Delete own drafts (not published)
- Upload media to own posts
- Use AI writing tools (generate content, titles, meta)
- View own analytics

**Restrictions:** Cannot publish directly (requires editor approval), cannot edit others' content, no user/settings access.

---

## 7. Contributor Role

### Permissions
- Create drafts
- Edit own drafts (before submission)

**Restrictions:** No publishing, no media upload, no AI tools, no dashboard access beyond own drafts.

---

## 8. Registered User Role

### Permissions
- Manage own profile (name, avatar, bio, social links)
- Comment on posts (needs moderation)
- Save/bookmark favorite posts
- Manage newsletter subscription
- View reading history

**Restrictions:** No dashboard access, no content creation, no media upload.

---

## 9. Guest Role

### Permissions
- Read public content
- Search the website
- Submit contact form
- View public author profiles

**Restrictions:** No account features, no commenting, no personalization.

---

## 10. Permission Categories

### Content
`create_post` `edit_post` `delete_post` `publish_post` `schedule_post` `archive_post`

### Categories
`create_category` `edit_category` `delete_category` `view_category`

### Tags
`create_tag` `edit_tag` `delete_tag`

### Media
`upload_media` `edit_media` `delete_media` `manage_media_folders`

### Comments
`moderate_comments` `approve_comments` `delete_comments`

### Users
`view_users` `create_users` `edit_users` `suspend_users` `ban_users` `delete_users`

### SEO
`manage_meta_titles` `manage_meta_descriptions` `manage_schema` `manage_sitemap` `manage_redirects` `manage_canonicals`

### AI
`generate_ai_content` `generate_ai_titles` `generate_ai_meta` `generate_ai_keywords` `run_ai_audits` `manage_ai_models`

### Analytics
`view_dashboard_analytics` `view_post_analytics` `export_reports`

### Settings
`manage_general_settings` `manage_seo_settings` `manage_email_settings` `manage_security_settings` `manage_ai_settings`

---

## 11. SEO Permission System

| Permission | Super Admin | Admin | Editor | Author | Contributor |
|------------|:---------:|:----:|:-----:|:-----:|:----------:|
| manage_meta_titles | ✅ | ✅ | ✅ | ❌ | ❌ |
| manage_meta_descriptions | ✅ | ✅ | ✅ | ❌ | ❌ |
| manage_schema | ✅ | ✅ | ✅ | ❌ | ❌ |
| manage_sitemap | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage_redirects | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage_canonicals | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## 12. AI Permission System

| Permission | Super Admin | Admin | Editor | Author | Contributor |
|------------|:---------:|:----:|:-----:|:-----:|:----------:|
| generate_ai_content | ✅ | ✅ | ✅ | ✅ | ❌ |
| generate_ai_titles | ✅ | ✅ | ✅ | ✅ | ❌ |
| generate_ai_meta | ✅ | ✅ | ✅ | ❌ | ❌ |
| generate_ai_keywords | ✅ | ✅ | ✅ | ✅ | ❌ |
| run_ai_audits | ✅ | ✅ | ✅ | ❌ | ❌ |
| manage_ai_models | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## 13. Analytics Permissions

| Permission | Super Admin | Admin | Editor | Author |
|------------|:---------:|:----:|:-----:|:-----:|
| view_dashboard_analytics | ✅ | ✅ | ✅ | ❌ |
| view_post_analytics | ✅ | ✅ | ✅ | ✅ (own) |
| export_reports | ✅ | ✅ | ❌ | ❌ |

---

## 14. User Management Permissions

| Permission | Super Admin | Admin |
|------------|:---------:|:----:|
| view_users | ✅ | ✅ |
| create_users | ✅ | ✅ |
| edit_users | ✅ | ✅ |
| suspend_users | ✅ | ✅ |
| ban_users | ✅ | ✅ |
| delete_users | ✅ | ❌ (only non-admin) |

---

## 15. Settings Permissions

| Permission | Super Admin | Admin |
|------------|:---------:|:----:|
| manage_general_settings | ✅ | ✅ |
| manage_seo_settings | ✅ | ✅ |
| manage_email_settings | ✅ | ❌ |
| manage_security_settings | ✅ | ❌ |
| manage_ai_settings | ✅ | ✅ |

---

## 16. Complete Permission Matrix

| Permission | Super Admin | Admin | Editor | Author | Contributor | User | Guest |
|------------|:---------:|:----:|:-----:|:-----:|:----------:|:---:|:----:|
| create_post | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| edit_post | ✅ | ✅ | ✅ | ✅ (own) | ✅ (own draft) | ❌ | ❌ |
| delete_post | ✅ | ✅ | ✅ | ✅ (own draft) | ❌ | ❌ | ❌ |
| publish_post | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| schedule_post | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| archive_post | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| create_category | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| edit_category | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| delete_category | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| create_tag | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| edit_tag | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| delete_tag | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| upload_media | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| delete_media | ✅ | ✅ | ✅ | ✅ (own) | ❌ | ❌ | ❌ |
| moderate_comments | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| view_users | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| create_users | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| edit_users | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| manage_seo | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| use_ai_tools | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage_settings | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| view_analytics | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 17. Policy Architecture

### Policies (app/Policies/)
| Policy | Methods | Ownership Check |
|--------|---------|----------------|
| PostPolicy | view, create, update, delete, publish, archive | author_id matches user |
| CategoryPolicy | view, create, update, delete | Admin+ only |
| TagPolicy | view, create, update, delete | Admin+ only |
| MediaPolicy | view, create, update, delete | user_id matches |
| CommentPolicy | view, create, update, delete, moderate | Admin+ moderate |
| UserPolicy | view, create, update, delete, suspend, ban | Super Admin only for delete/ban |
| SEOSettingsPolicy | view, update | Editor+ |
| AISettingsPolicy | view, update | Admin+ |

---

## 18. Gate Architecture

```php
// App\Providers\AuthServiceProvider
Gate::define('access-admin', fn ($user) => $user->hasAnyRole(['super-admin', 'admin', 'editor']));
Gate::define('access-ai', fn ($user) => $user->hasAnyRole(['super-admin', 'admin', 'editor', 'author']));
Gate::define('access-seo', fn ($user) => $user->hasAnyRole(['super-admin', 'admin', 'editor']));
Gate::define('access-analytics', fn ($user) => $user->hasAnyRole(['super-admin', 'admin', 'editor']));
Gate::define('access-settings', fn ($user) => $user->hasAnyRole(['super-admin', 'admin']));
```

---

## 19. Middleware Structure

| Middleware | Restricts To | Applied To |
|-----------|--------------|------------|
| SuperAdminMiddleware | Super Admin only | System routes |
| AdminMiddleware | Admin+ | Admin panel base |
| EditorMiddleware | Editor+ | Content management |
| AuthorMiddleware | Author+ | Author dashboard |
| ContributorMiddleware | Contributor+ | Contributor routes |

Usage: `Route::middleware(['role:admin'])->group(...)` via Spatie.

---

## 20. Content Ownership System

- **Posts:** `author_id` = user.id. Authors can edit/delete own posts (subject to status)
- **Media:** `user_id` = user.id. Users can delete own media
- **Drafts:** Only the creator can edit/delete their drafts
- Ownership override: Admin+ can manage any content regardless of ownership

---

## 21. Workflow Authorization

| Workflow Step | Who Can Perform |
|---------------|----------------|
| Create Draft | Contributor, Author |
| Submit for Review | Author, Contributor |
| Review Content | Editor |
| SEO Review | Editor |
| Approve | Editor |
| Schedule | Editor, Admin |
| Publish | Editor, Admin, Super Admin |
| Update Published | Editor, Admin, Super Admin |
| Archive | Editor, Admin, Super Admin |

---

## 22. Permission Caching Strategy (Redis)

- Cache all permissions for active user on login
- Cache key: `permissions:{user_id}`
- Cache TTL: 3600 seconds
- Cache invalidation: On role change, permission update, user suspension
- Spatie's built-in cache layer used with Redis driver

---

## 23. Audit & Compliance Logging

| Event | Logged Data | Logged By |
|-------|------------|-----------|
| Role change | User, old role, new role | Admin |
| Permission change | User/role, old perm, new perm | Super Admin |
| User suspended | User, reason, duration | Admin |
| Post published | Post ID, author, publisher | System |
| SEO change | Post ID, field changed, old/new values | Editor |

All stored in `activity_logs` via Spatie Activitylog.

---

## 24. Future Scalability

Architecture prepared for:
- **Membership tiers:** Add `premium_*` permissions
- **Multi-site:** Add `site_id` scope to permissions
- **API tokens:** Sanctum scopes mapped to permissions
- **Team collaboration:** Add `team_id` to content ownership
- **Granular settings:** Sub-groups within settings permissions
- **Feature flags:** Permission-based feature gating

---

## 25. Final Output

**Phase 5 complete.** Enterprise RBAC system defined:
- 7-level role hierarchy with inheritance
- 40+ granular permissions across 10 categories
- Complete permission matrix (7 roles × 22+ permissions)
- Laravel Policies for all 8 entity types
- 5 custom middleware classes
- Content ownership rules per entity
- Workflow authorization per content status
- Redis permission caching strategy
- Audit logging for all access events
- Future scalability for membership, multi-site, API, teams

Ready to proceed to **Phase 6**.
