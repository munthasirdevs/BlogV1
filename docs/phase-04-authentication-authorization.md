# PHASE 4 — AUTHENTICATION, AUTHORIZATION & USER ACCESS MANAGEMENT SYSTEM

## 1. Authentication Architecture

### Authentication Flow
```
User → Registration/Login → Email Verification → Session Created → 
Role/Permission Check → Dashboard/Content Access
```

### Supported User Types
| Type | Authenticated? | Dashboard Access |
|------|:------------:|:---------------:|
| Guest | No | None |
| Registered User | Yes | None (public only) |
| Contributor | Yes | Create posts only |
| Author | Yes | Own content management |
| Editor | Yes | All content management |
| Admin | Yes | Full operational access |
| Super Admin | Yes | Full system access |

---

## 2. User Account Structure

### Account Management
- Registration with email verification
- Login via email or username
- Secure logout (session destruction)
- Remember Me (encrypted cookie)
- Password reset via email link
- Email verification required for full access

---

## 3. User Registration System

### Registration Fields
| Field | Required | Validation |
|-------|----------|-----------|
| Name | Yes | 2-255 chars |
| Username | Yes | 3-50 chars, alphanumeric, unique |
| Email | Yes | Valid email, unique |
| Password | Yes | 8+ chars, confirmed |
| Phone | No | Optional validation |
| Bio | No | Max 500 chars |
| Website | No | Valid URL |

### Post-Registration Flow
1. Validation → Check duplicates → Create user
2. Assign default role (Registered User)
3. Generate email verification token
4. Queue verification email
5. Redirect to "verify email" notice

---

## 4. Login System

### Login Methods
- Email + Password
- Username + Password

### Features
- Remember Me (30-day cookie token)
- Login throttling: 5 attempts/min per IP
- Last login timestamp and IP logging
- Session regeneration on login

---

## 5. Email Verification System

- Verification link sent on registration
- Token expiration: 60 minutes
- Resend verification allowed (1 per 60s)
- Manual verification option from admin panel
- Middleware redirect for unverified users

---

## 6. Password Management

### Password Policy
| Requirement | Rule |
|-------------|------|
| Minimum Length | 8 characters |
| Mixed Case | Recommended |
| Number | Recommended |
| Special Char | Recommended |
| Hashing | Bcrypt (12 rounds) |

### Features
- Password reset via email
- Password change in profile
- Token expiration: 60 minutes
- Cannot reuse last 3 passwords

---

## 7. Account Recovery System

1. User requests "Forgot Password"
2. Email sent with reset link
3. User clicks link → enters new password
4. Password updated → session regenerated
5. Confirmation email sent

---

## 8. Session Management (Redis)

### Session Config
- Driver: Redis
- Lifetime: 120 minutes
- Expire on close: false
- Cookie: HTTP-only, Secure, SameSite=Lax

### Multi-Device Support
- View all active sessions
- Logout specific device
- Logout all devices
- Session invalidation on password change

---

## 9. Role Management System (Spatie)

### Role Hierarchy
```
Super Admin
    └── Admin
            └── Editor
                    └── Author
                            └── Contributor
```

Each role inherits permissions from roles below it plus its own.

---

## 10. Permission System

### Permission List
| Group | Permissions |
|-------|------------|
| Posts | create_posts, edit_posts, delete_posts, publish_posts |
| Categories | create_categories, edit_categories, delete_categories |
| Tags | create_tags, edit_tags, delete_tags |
| Media | upload_media, delete_media, manage_media |
| Users | view_users, create_users, edit_users, delete_users |
| SEO | manage_seo |
| AI | use_ai_tools, manage_ai_tools |
| Settings | manage_settings |
| Analytics | view_analytics |

---

## 11. Access Control Matrix

| Action | Super Admin | Admin | Editor | Author | Contributor |
|--------|:---------:|:----:|:-----:|:-----:|:----------:|
| Create posts | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit own posts | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit any post | ✅ | ✅ | ✅ | ❌ | ❌ |
| Delete own posts | ✅ | ✅ | ✅ | ✅ | ❌ |
| Delete any post | ✅ | ✅ | ✅ | ❌ | ❌ |
| Publish posts | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage categories | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage tags | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage media | ✅ | ✅ | ✅ | ✅ | ❌ |
| Manage users | ✅ | ✅ | ❌ | ❌ | ❌ |
| Manage SEO | ✅ | ✅ | ✅ | ❌ | ❌ |
| Use AI tools | ✅ | ✅ | ✅ | ✅ | ❌ |
| Manage settings | ✅ | ✅ | ❌ | ❌ | ❌ |
| View analytics | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## 12. Admin Invitation System

1. Admin creates invitation for email
2. System generates unique token
3. Email sent with invitation link
4. Recipient clicks → registration form pre-filled
5. Token validated → user created with assigned role
6. Invitation expires in 48 hours

---

## 13. Profile Management

### User Profile Fields
- Avatar (upload, crop, 200x200)
- Name (editable)
- Username (editable, unique check)
- Bio (textarea, max 500 chars)
- Website (URL)
- Social links (JSON: twitter, github, linkedin, facebook)
- Public profile page at `/author/{username}`

---

## 14. Account Status Management

| Status | Meaning | Restrictions |
|--------|---------|-------------|
| Active | Normal operation | None |
| Pending | Awaiting verification | Cannot access dashboard |
| Suspended | Temporary block | Full access revoked |
| Banned | Permanent block | Cannot login |
| Deleted | Soft deleted | Account hidden, data retained |

---

## 15. Security Hardening

- CSRF: Enabled on all forms (Laravel default)
- XSS: Blade auto-escaping + HTML Purifier on rich content
- SQL Injection: Eloquent ORM prepared statements
- Mass Assignment: `$fillable` or `$guarded` on all models
- Session: Redis, HTTP-only, secure cookies
- Rate Limiting: 60 req/min general, 5 req/min auth

---

## 16. Login Protection

- Throttle: 5 failed attempts → 1-minute lockout
- Progressive delay: 1min → 5min → 15min → 1hr
- Failed login logging with IP tracking
- Suspicious activity alerts (multiple IPs, rapid attempts)

---

## 17. Email Templates

| Template | Trigger | Queue? |
|----------|---------|--------|
| Welcome | After registration | Yes |
| Verify Email | Registration/invitation | Yes |
| Reset Password | Forgot password request | Yes |
| Account Suspended | Admin action | Yes |
| Password Changed | Self-service or admin | Yes |
| Invitation | Admin invites user | Yes |

All emails: responsive HTML, plain-text fallback, queueable

---

## 18. User Activity Tracking

### Tracked Events
- Login / Logout
- Password change
- Email change
- Profile update
- Role change (admin action)
- Account status change

Stored in `activity_logs` table via Spatie Activitylog.

---

## 19. Authorization Middleware

| Middleware | Restricts To | Routes |
|-----------|--------------|--------|
| role:super-admin | Super Admin only | System config |
| role:admin | Admin+ | User management |
| role:editor | Editor+ | Content management |
| role:author | Author+ | Own content |
| permission:manage-seo | Has permission | SEO tools |

Implemented via Spatie's built-in `middleware()` method.

---

## 20. Dashboard Access Control

| Role | Dashboard Sections |
|------|-------------------|
| Super Admin | All sections |
| Admin | Dashboard, Posts, Categories, Tags, Media, Comments, Users, SEO, AI, Analytics, Settings |
| Editor | Dashboard, Posts, Categories, Tags, Media, Comments, SEO, AI, Analytics |
| Author | Dashboard (own stats), Posts (own), Media (own), AI |
| Contributor | Dashboard (limited), Posts (create only), AI (use tools) |

---

## 21. API Authentication Preparation

- Laravel Sanctum for API token management
- Token-based authentication for external clients
- SPA authentication for future single-page frontend
- Rate limiting: 60/min unauthenticated, 200/min authenticated

---

## 22. User Notification System

### Notification Types
- Database: Stored in `notifications` table, displayed in bell icon
- Email: Sent via queue for non-urgent notifications

### Triggers
- Content approval/rejection
- Role changes
- Account status changes
- Comment moderation updates

---

## 23. Testing Strategy

### Test Coverage
- Registration (success, duplicate email, invalid data)
- Login (valid, invalid, throttled, remember me)
- Logout (session destruction)
- Password reset (request, reset, expired token)
- Email verification (verify, resend, expired)
- Permissions (each permission check)
- Roles (each role access scope)
- Middleware (unauthenticated, wrong role)

### Test Types
- Feature tests for all auth flows
- Unit tests for permission helpers
- PEST for readable test syntax

---

## 24. Security Audit Checklist

- [ ] CSRF protection active on all forms
- [ ] XSS sanitization on all user input
- [ ] SQL injection prevention (no raw queries)
- [ ] Rate limiting on auth routes
- [ ] Strong password hashing (bcrypt 12)
- [ ] Session security (Redis, HTTP-only, secure)
- [ ] Login throttling configured
- [ ] Email verification enforced
- [ ] File upload validation (type, size, malware scan)
- [ ] Failed login monitoring
- [ ] Security headers (HSTS, CSP, X-Frame-Options)
- [ ] OWASP Top 10 compliance

---

## 25. Final Output

**Phase 4 complete.** Authentication & Authorization system designed:
- Complete auth architecture with 7 user types
- Registration with email verification
- Secure login with throttling and remember me
- Session management via Redis
- Role hierarchy (5 roles) with Spatie
- 20+ granular permissions
- Access control matrix for all roles
- Admin invitation workflow
- Profile management system
- Account status lifecycle
- Security hardening (OWASP compliant)
- Email notification system (6 templates)
- Activity tracking for all events
- Authorization middleware
- Dashboard access control
- API auth prep with Sanctum
- Full testing strategy
- Security audit checklist

Ready to proceed to **Phase 5** — Role, Permission & Enterprise Access Control System.
