# Phase 8: Comments System API Documentation

## Overview

The Comments System provides a complete solution for managing blog comments with support for:
- Nested replies (up to 5 levels deep)
- Moderation workflow (approve/reject/spam)
- Edit history tracking
- @mention parsing and notifications
- Rate limiting and spam prevention
- Bulk moderation operations

## Base URL

```
/api/v1
```

## Authentication

Most endpoints require authentication via Bearer token:
```
Authorization: Bearer {token}
```

## Rate Limits

- **Public endpoints**: 60 requests/minute
- **Authenticated endpoints**: 120 requests/minute
- **Comment creation**: 3 comments/minute, 10 comments/hour
- **Admin endpoints**: 200 requests/minute

---

## Public Endpoints

### Get Post Comments

Retrieve all comments for a specific post with nested structure.

```
GET /posts/{postId}/comments
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `flat` | boolean | `false` | Return flat list instead of tree |
| `per_page` | integer | `20` | Items per page (for flat mode) |
| `approved_only` | boolean | `true` | Only return approved comments |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "content": "Great article!",
      "content_with_mentions": "Great article!",
      "status": "approved",
      "depth": 0,
      "is_edited": false,
      "likes_count": 5,
      "reply_count": 2,
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z",
      "time_ago": "2 hours ago",
      "author": {
        "id": 1,
        "name": "John Doe",
        "avatar": "https://..."
      },
      "replies": [
        {
          "id": 2,
          "content": "Thanks!",
          "depth": 1,
          "parent_id": 1,
          "author": {...}
        }
      ],
      "can": {
        "update": false,
        "delete": false,
        "approve": false,
        "reject": false,
        "reply": true
      }
    }
  ],
  "meta": {
    "total": 10,
    "post_id": 1
  }
}
```

---

### Get Single Comment

Retrieve a specific comment with full details.

```
GET /comments/{id}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "content": "Great article!",
    "status": "approved",
    "depth": 0,
    "author": {...},
    "post": {...},
    "parent": null,
    "replies": [...],
    "edits": [...]
  }
}
```

---

### Get Comment Replies

Retrieve replies to a specific comment.

```
GET /comments/{id}/replies
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | integer | `20` | Items per page |
| `approved_only` | boolean | `true` | Only approved replies |

---

### Get Mention Suggestions

Get user suggestions for @mentions.

```
GET /comments/mentions/suggest?q={query}
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `q` | string | - | Search query (min 2 chars) |
| `limit` | integer | `5` | Max results |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "username": "johndoe",
      "avatar": "https://..."
    }
  ]
}
```

---

## Authenticated Endpoints

### Create Comment

Create a new comment on a post.

```
POST /posts/{postId}/comments
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
  "content": "This is a great article! Very informative.",
  "parent_id": null,
  "edit_reason": "Optional reason for edit"
}
```

**Validation Rules:**

| Field | Rules | Description |
|-------|-------|-------------|
| `content` | required, string, 10-5000 chars | Comment content |
| `parent_id` | nullable, integer, exists:comments | Parent comment for replies |
| `edit_reason` | nullable, string, max 255 | Reason for edit |

**Response (201 Created):**

```json
{
  "success": true,
  "message": "Comment submitted for approval",
  "data": {
    "id": 1,
    "content": "This is a great article!",
    "status": "pending",
    "depth": 0,
    "author": {...}
  }
}
```

**Status Codes:**
- `201`: Comment created
- `422`: Validation error
- `429`: Rate limit exceeded

---

### Update Comment

Update an existing comment.

```
PUT /comments/{id}
```

**Request Body:**

```json
{
  "content": "Updated comment content.",
  "edit_reason": "Fixed typo"
}
```

**Constraints:**
- Only authors can edit within 30 minutes
- Staff (admin/editor/moderator) can edit anytime
- Maximum 5 edits per comment

**Response:**

```json
{
  "success": true,
  "message": "Comment updated successfully",
  "data": {
    "id": 1,
    "content": "Updated comment content.",
    "is_edited": true
  }
}
```

---

### Delete Comment

Soft delete a comment.

```
DELETE /comments/{id}
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `cascade` | boolean | `false` | Also delete replies |

**Response (204 No Content):**

```json
{
  "success": true,
  "message": "Comment deleted successfully"
}
```

---

### Get Comment Edit History

View edit history for a comment.

```
GET /comments/{id}/edits
```

**Authorization:** Comment author or staff only

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "old_content": "Original content",
      "new_content": "Updated content",
      "edit_reason": "Fixed typo",
      "editor": {
        "id": 1,
        "name": "John Doe"
      },
      "edited_at": "2024-01-15T11:00:00Z"
    }
  ]
}
```

---

## Moderation Endpoints (Editor/Moderator/Admin)

### Get Pending Comments

Retrieve comments awaiting approval.

```
GET /comments/pending
GET /editor/comments/pending
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | integer | `20` | Items per page |

---

### Approve Comment

Approve a pending comment.

```
POST /comments/{id}/approve
POST /editor/comments/{id}/approve
```

**Response:**

```json
{
  "success": true,
  "message": "Comment approved"
}
```

---

### Reject Comment

Reject a comment.

```
POST /comments/{id}/reject
POST /editor/comments/{id}/reject
```

**Request Body:**

```json
{
  "reason": "Inappropriate content"
}
```

---

### Mark as Spam

Mark a comment as spam.

```
POST /comments/{id}/spam
```

---

## Admin Endpoints

### Search Comments

Search and filter comments.

```
GET /admin/comments/search
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | string | - | Search in content |
| `status` | string | - | Filter by status |
| `post_id` | integer | - | Filter by post |
| `user_id` | integer | - | Filter by user |
| `from_date` | date | - | Start date |
| `to_date` | date | - | End date |
| `sort` | string | `created_at` | Sort field |
| `order` | string | `desc` | Sort order |
| `per_page` | integer | `20` | Items per page |

**Response:**

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

---

### Bulk Moderate

Perform bulk actions on multiple comments.

```
POST /admin/comments/bulk-moderate
```

**Request Body:**

```json
{
  "comment_ids": [1, 2, 3],
  "action": "approve",
  "reason": "Optional reason"
}
```

**Actions:**
- `approve` - Approve comments
- `reject` - Reject comments
- `spam` - Mark as spam
- `delete` - Soft delete comments

**Response:**

```json
{
  "success": true,
  "message": "Processed 3 successful, 0 failed",
  "data": {
    "results": {
      "1": {"success": true, "action": "approve"},
      "2": {"success": true, "action": "approve"},
      "3": {"success": true, "action": "approve"}
    },
    "summary": {
      "total": 3,
      "successful": 3,
      "failed": 0
    }
  }
}
```

---

### Get Statistics

Get comment statistics.

```
GET /admin/comments/statistics
```

**Response:**

```json
{
  "success": true,
  "data": {
    "total": 150,
    "approved": 120,
    "pending": 15,
    "rejected": 10,
    "spam": 5,
    "today": 25,
    "this_week": 75,
    "this_month": 140
  }
}
```

---

## Data Models

### Comment Object

```json
{
  "id": "integer",
  "post_id": "integer",
  "user_id": "integer",
  "parent_id": "integer|null",
  "content": "string",
  "content_with_mentions": "string",
  "status": "pending|approved|rejected|spam",
  "depth": "integer (0-5)",
  "is_edited": "boolean",
  "likes_count": "integer",
  "reply_count": "integer",
  "created_at": "datetime",
  "updated_at": "datetime",
  "moderated_at": "datetime|null",
  "time_ago": "string",
  "excerpt": "string",
  "author": "User object",
  "parent": "Comment object",
  "replies": "Comment array",
  "edits": "EditHistory array",
  "mentioned_users": "User array",
  "can": {
    "update": "boolean",
    "delete": "boolean",
    "approve": "boolean",
    "reject": "boolean",
    "reply": "boolean"
  }
}
```

### Comment Status Values

| Status | Description |
|--------|-------------|
| `pending` | Awaiting approval |
| `approved` | Visible to public |
| `rejected` | Not approved |
| `spam` | Marked as spam |

### Edit History Object

```json
{
  "id": "integer",
  "old_content": "string",
  "new_content": "string",
  "edit_reason": "string|null",
  "editor": "User object",
  "edited_at": "datetime"
}
```

---

## Error Responses

### Validation Error (422)

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "content": ["Comment must be at least 10 characters."]
  }
}
```

### Unauthorized (401)

```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

### Rate Limited (429)

```json
{
  "success": false,
  "message": "Rate limit exceeded. Please try again later."
}
```

Headers:
```
Retry-After: 60
```

### Not Found (404)

```json
{
  "success": false,
  "message": "Resource not found."
}
```

---

## Features

### Nested Comments

- Maximum depth: 5 levels
- Parent comment must exist and not be deleted
- Reply count tracked on each comment
- Tree structure returned by default

### Rate Limiting

- 3 comments per minute per user
- 10 comments per hour per user
- Higher limits for trusted users (5+ approved comments)
- Returns 429 with Retry-After header

### Spam Prevention

- URL limits for new users (max 1 URL)
- Profanity filter integration
- Spammy pattern detection (excessive caps, repetitive chars)
- First-time commenters require approval

### @Mentions

- Parse @username patterns
- Maximum 10 mentions per comment
- Link to user profiles
- Trigger notifications to mentioned users
- Validate mentioned users exist

### Edit History

- Track all edits with old/new content
- Optional edit reason
- Maximum 5 edits per comment
- Edit window: 30 minutes for regular users
- Staff can edit anytime

### Caching

- Comment trees cached for 30 minutes
- Cache cleared on create/update/delete
- Improves performance for posts with many comments

---

## Testing

Run the test suite:

```bash
php artisan test --filter CommentApiTest
```

Test coverage includes:
- CRUD operations
- Nested replies (5 levels)
- Moderation workflow
- Rate limiting
- Authorization
- Edit history
- Mention parsing
- Search and filtering
- Bulk operations

---

## Configuration

### Environment Variables

```env
# Comment settings
COMMENT_MAX_DEPTH=5
COMMENT_MAX_EDITS=5
COMMENT_EDIT_WINDOW_MINUTES=30
COMMENT_RATE_LIMIT_PER_MINUTE=3
COMMENT_RATE_LIMIT_PER_HOUR=10
COMMENT_CACHE_TTL=30
```

### Profanity Filter

Configure custom profanity list in `config/profanity.php`:

```php
return [
    'list' => [
        // Add custom words
    ],
];
```

---

## Migration

Run migrations to create comment tables:

```bash
php artisan migrate
```

Tables created:
- `comments` - Main comments table
- `comment_edits` - Edit history tracking

---

## Quick Start Examples

### Create a Comment

```bash
curl -X POST "http://localhost/api/v1/posts/1/comments" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"content": "Great article!"}'
```

### Reply to a Comment

```bash
curl -X POST "http://localhost/api/v1/posts/1/comments" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"content": "I agree!", "parent_id": 1}'
```

### Approve a Comment (Moderator)

```bash
curl -X POST "http://localhost/api/v1/comments/1/approve" \
  -H "Authorization: Bearer {token}"
```

### Search Comments (Admin)

```bash
curl -X GET "http://localhost/api/v1/admin/comments/search?status=pending" \
  -H "Authorization: Bearer {token}"
```
