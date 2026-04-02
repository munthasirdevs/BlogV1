# Posts API Documentation

## Overview

The Posts API provides comprehensive CRUD operations and advanced features for managing blog posts. This includes post creation, editing, publishing workflow, search, filtering, and bulk operations.

**Base URL:** `/api/v1/posts`

## Authentication

Most endpoints require authentication via Sanctum tokens. Include the token in the `Authorization` header:

```
Authorization: Bearer {your-token}
```

## Authorization Matrix

| Role | Create | Edit Own | Edit Any | Delete Own | Delete Any | Publish | Feature |
|------|--------|----------|----------|------------|------------|---------|---------|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editor | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| Author | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Subscriber | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Endpoints

### List Posts

**GET** `/api/v1/posts`

Retrieve a paginated list of posts with optional filtering and sorting.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `status` | string | - | Filter by status: `draft`, `published`, `scheduled`, `archived` |
| `category` | string | - | Filter by category slug or ID |
| `tag` | string | - | Filter by tag slug or ID |
| `author` | integer | - | Filter by author ID |
| `search` | string | - | Search in title, excerpt, and content |
| `featured` | boolean | - | Filter featured posts only |
| `from_date` | date | - | Filter posts from this date |
| `to_date` | date | - | Filter posts until this date |
| `sort` | string | `published_at` | Sort field: `id`, `title`, `published_at`, `created_at`, `views_count`, `likes_count` |
| `order` | string | `desc` | Sort order: `asc`, `desc` |
| `per_page` | integer | `15` | Items per page (max: 100) |
| `page` | integer | `1` | Page number |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Getting Started with Laravel",
      "slug": "getting-started-with-laravel",
      "excerpt": "A comprehensive guide to Laravel...",
      "featured_image": "https://example.com/image.jpg",
      "is_featured": false,
      "reading_time": 5,
      "reading_time_formatted": "5 min read",
      "status": "published",
      "views_count": 150,
      "likes_count": 25,
      "comments_count": 10,
      "published_at": "2024-01-15T10:00:00Z",
      "created_at": "2024-01-14T08:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z",
      "author": {
        "id": 1,
        "name": "John Doe",
        "avatar": "https://example.com/avatar.jpg"
      },
      "category": {
        "id": 1,
        "name": "Laravel",
        "slug": "laravel"
      },
      "tags": [
        {
          "id": 1,
          "name": "PHP",
          "slug": "php"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "total_pages": 4,
    "has_more": true
  },
  "links": {
    "first": "https://api.example.com/api/v1/posts?page=1",
    "prev": null,
    "next": "https://api.example.com/api/v1/posts?page=2",
    "last": "https://api.example.com/api/v1/posts?page=4"
  }
}
```

---

### Get Single Post

**GET** `/api/v1/posts/{identifier}`

Retrieve a single post by ID or slug.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `identifier` | string | Post ID or slug |

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Getting Started with Laravel",
    "slug": "getting-started-with-laravel",
    "excerpt": "A comprehensive guide to Laravel...",
    "content": "Full post content here...",
    "featured_image": "https://example.com/image.jpg",
    "is_featured": false,
    "reading_time": 5,
    "status": "published",
    "views_count": 151,
    "author": { ... },
    "category": { ... },
    "tags": [ ... ],
    "can": {
      "update": true,
      "delete": false
    }
  }
}
```

**Notes:**
- View count is incremented on each request for published posts
- Duplicate views from same user/IP are prevented within 24 hours

---

### Create Post

**POST** `/api/v1/posts`

Create a new blog post.

**Authorization:** Requires `author`, `editor`, or `admin` role

**Request Body:**

```json
{
  "title": "My New Post",
  "slug": "my-new-post", // Optional, auto-generated if not provided
  "excerpt": "Brief summary...", // Optional
  "content": "Full post content...",
  "featured_image": "https://example.com/image.jpg", // Optional
  "category_id": 1,
  "tags": [1, 2, 3], // Optional, array of tag IDs
  "status": "draft", // Optional: draft, published, scheduled, archived
  "published_at": "2024-01-15T10:00:00Z", // Optional
  "meta_title": "SEO Title", // Optional
  "meta_description": "SEO Description", // Optional
  "meta_keywords": ["laravel", "php"] // Optional
}
```

**Form Data (for file upload):**

```
title: My New Post
content: Full post content...
category_id: 1
featured_image: (file)
```

**Response (201 Created):**

```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "id": 1,
    "title": "My New Post",
    "slug": "my-new-post",
    "status": "draft",
    "created_at": "2024-01-15T08:00:00Z"
  }
}
```

**Validation Rules:**

| Field | Rules |
|-------|-------|
| `title` | required, string, min:5, max:200 |
| `content` | required, string, min:50 |
| `category_id` | required, integer, exists:categories |
| `slug` | optional, string, max:255, unique |
| `excerpt` | optional, string, max:500 |
| `tags` | optional, array, max:10 items |
| `status` | optional, in:draft,published,scheduled,archived |

---

### Update Post

**PUT** `/api/v1/posts/{post}`

Update an existing post.

**Authorization:** Authors can edit own posts; Editors/Admins can edit any

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `post` | integer | Post ID |

**Request Body:**

```json
{
  "title": "Updated Title",
  "content": "Updated content...",
  "excerpt": "Updated excerpt...",
  "category_id": 2,
  "tags": [1, 2, 4],
  "status": "published",
  "remove_featured_image": true // Optional, removes featured image
}
```

**Response:**

```json
{
  "success": true,
  "message": "Post updated successfully",
  "data": {
    "id": 1,
    "title": "Updated Title",
    "slug": "updated-title",
    "updated_at": "2024-01-16T10:00:00Z"
  }
}
```

**Notes:**
- Slug is auto-updated if title changes (unless manually set)
- Reading time is recalculated if content changes
- Change tracking is performed for audit purposes

---

### Delete Post

**DELETE** `/api/v1/posts/{post}`

Soft delete a post.

**Authorization:** Authors can delete own posts; Admins can delete any

**Response (204 No Content):**

```json
{
  "success": true,
  "message": "Post deleted successfully"
}
```

---

### Restore Post

**POST** `/api/v1/posts/{post}/restore`

Restore a soft-deleted post.

**Authorization:** Admin only

**Response:**

```json
{
  "success": true,
  "message": "Post restored successfully",
  "data": { ... }
}
```

---

### Publish Post

**POST** `/api/v1/posts/{post}/publish`

Publish a draft or scheduled post.

**Authorization:** Editor or Admin only

**Request Body (optional):**

```json
{
  "published_at": "2024-01-15T10:00:00Z"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Post published successfully",
  "data": {
    "id": 1,
    "status": "published",
    "published_at": "2024-01-15T10:00:00Z"
  }
}
```

---

### Unpublish Post

**POST** `/api/v1/posts/{post}/unpublish`

Unpublish a post (reverts to draft).

**Authorization:** Editor or Admin only

**Response:**

```json
{
  "success": true,
  "message": "Post unpublished successfully",
  "data": {
    "id": 1,
    "status": "draft"
  }
}
```

---

### Auto-save Draft

**POST** `/api/v1/posts/{post}/autosave`

Auto-save a post draft without changing status.

**Authorization:** Author (own posts), Editor/Admin (any)

**Request Body:**

```json
{
  "title": "Autosaved Title",
  "content": "Autosaved content...",
  "excerpt": "Autosaved excerpt..."
}
```

**Response:**

```json
{
  "success": true,
  "message": "Draft auto-saved successfully",
  "data": {
    "id": 1,
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

**Notes:**
- Only content-related fields are saved
- Status remains unchanged
- Throttled to prevent excessive saves (30 second minimum)

---

### Feature Post

**POST** `/api/v1/posts/{post}/feature`

Mark a post as featured.

**Authorization:** Admin only

**Response:**

```json
{
  "success": true,
  "message": "Post featured successfully",
  "data": {
    "id": 1,
    "is_featured": true
  }
}
```

---

### Unfeature Post

**DELETE** `/api/v1/posts/{post}/feature`

Remove featured status from a post.

**Authorization:** Admin only

**Response:**

```json
{
  "success": true,
  "message": "Post unfeatured successfully",
  "data": {
    "id": 1,
    "is_featured": false
  }
}
```

---

### Get Preview URL

**GET** `/api/v1/posts/{post}/preview`

Generate a signed preview URL for unpublished posts.

**Authorization:** Author (own posts), Editor/Admin (any)

**Response:**

```json
{
  "success": true,
  "data": {
    "preview_url": "https://api.example.com/api/v1/posts/1?preview_token=abc123...",
    "token": "abc123...",
    "expires_at": "2024-01-16T10:00:00Z"
  }
}
```

**Notes:**
- Token expires after 24 hours
- Allows viewing unpublished posts without authentication

---

### Get Post Author

**GET** `/api/v1/posts/{post}/author`

Get detailed author information for a post.

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "avatar": "https://example.com/avatar.jpg",
    "bio": "Software developer...",
    "website": "https://johndoe.com",
    "twitter": "@johndoe",
    "github": "johndoe",
    "linkedin": "johndoe",
    "location": "New York",
    "posts_count": 25
  }
}
```

---

### Get Related Posts

**GET** `/api/v1/posts/{post}/related`

Get posts related by category and tags.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | integer | `4` | Number of posts to return (max: 10) |

**Response:**

```json
{
  "success": true,
  "data": [ ... ]
}
```

---

### Get Trending Posts

**GET** `/api/v1/posts/trending`

Get trending posts based on views, likes, and comments.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `days` | integer | `7` | Number of days to look back (max: 30) |
| `limit` | integer | `10` | Number of posts to return (max: 20) |

**Response:**

```json
{
  "success": true,
  "data": [ ... ]
}
```

**Notes:**
- Results are cached for 1 hour
- Trending score = views + (likes × 2) + (comments × 3)

---

### Get Featured Posts

**GET** `/api/v1/posts/featured`

Get featured posts.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | integer | `5` | Number of posts to return (max: 20) |

**Response:**

```json
{
  "success": true,
  "data": [ ... ]
}
```

---

### Search Posts

**GET** `/api/v1/posts/search`

Search posts with full-text search capabilities.

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` | string | Yes | Search query (min: 2 chars) |
| `category` | string | No | Filter by category slug |
| `tag` | string | No | Filter by tag slug |
| `author` | integer | No | Filter by author ID |
| `boolean` | boolean | No | Enable boolean mode search |
| `from_date` | date | No | Filter from date |
| `to_date` | date | No | Filter to date |
| `per_page` | integer | No | Items per page |

**Boolean Mode Examples:**
- `+Laravel +PHP` - Must contain both
- `Laravel -JavaScript` - Must contain Laravel, not JavaScript
- `Laravel|PHP` - Contains either

**Response:**

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 10,
    "query": "Laravel"
  },
  "links": { ... }
}
```

---

### Bulk Actions

**POST** `/api/v1/posts/bulk-actions`

Perform bulk actions on multiple posts.

**Authorization:** Editor/Admin (varies by action)

**Request Body:**

```json
{
  "action": "publish",
  "post_ids": [1, 2, 3, 4, 5]
}
```

**Available Actions:**

| Action | Required Role | Description |
|--------|---------------|-------------|
| `publish` | Editor/Admin | Publish selected posts |
| `archive` | Editor/Admin | Archive selected posts |
| `delete` | Author (own)/Admin | Soft delete selected posts |
| `feature` | Admin | Feature selected posts |
| `restore` | Admin | Restore deleted posts |

**Response:**

```json
{
  "success": true,
  "message": "Bulk action 'publish' completed",
  "data": {
    "action": "publish",
    "success_count": 4,
    "failed_count": 1,
    "successful": [1, 2, 3, 4],
    "failed": [
      {
        "id": 5,
        "reason": "Insufficient permissions"
      }
    ]
  }
}
```

**Status Codes:**
- `200` - All actions succeeded
- `207` - Partial success (some failed)

---

### Get Post Counts

**GET** `/api/v1/posts/counts`

Get post counts by status.

**Response:**

```json
{
  "success": true,
  "data": {
    "all": 50,
    "draft": 10,
    "published": 35,
    "scheduled": 3,
    "archived": 2
  }
}
```

**Notes:**
- Admins/Editors see all counts
- Authors see only their own counts

---

## Post Status Workflow

```
┌─────────┐     ┌──────────────┐     ┌───────────┐     ┌─────────┐
│  DRAFT  │ ──► │ PENDING_REVIEW │ ──► │ PUBLISHED │ ──► │ ARCHIVED│
└─────────┘     └──────────────┘     └───────────┘     └─────────┘
     ▲                                                        │
     └────────────────────────────────────────────────────────┘
```

**Status Descriptions:**

| Status | Description | Visibility |
|--------|-------------|------------|
| `draft` | Work in progress | Author, Editors, Admins |
| `pending_review` | Submitted for review | Editors, Admins |
| `published` | Live and public | Everyone |
| `archived` | No longer promoted | Everyone (via direct link) |

---

## Error Responses

### 404 Not Found

```json
{
  "success": false,
  "message": "Post not found"
}
```

### 403 Forbidden

```json
{
  "success": false,
  "message": "You do not have permission to perform this action"
}
```

### 422 Validation Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."],
    "content": ["The content must be at least 50 characters."]
  }
}
```

---

## Rate Limiting

| Endpoint Type | Limit |
|---------------|-------|
| Public (GET) | 60 requests/minute |
| Authenticated | 120 requests/minute |
| Admin | 200 requests/minute |

---

## Events

The following events are dispatched during post operations:

| Event | Description |
|-------|-------------|
| `PostCreated` | When a new post is created |
| `PostUpdated` | When a post is updated (includes changes) |
| `PostPublished` | When a post is published |
| `PostDeleted` | When a post is soft deleted |
| `PostRestored` | When a deleted post is restored |
| `PostViewed` | When a post is viewed |

---

## Best Practices

1. **Slug Management**: Always use URL-safe slugs. Let the system auto-generate unless you have specific SEO requirements.

2. **Content Length**: Ensure content meets minimum requirements (50 characters) for better SEO.

3. **Images**: Use the `featured_image` field for post thumbnails. Upload via multipart/form-data for best results.

4. **Tags**: Limit to 10 tags per post for optimal organization.

5. **Auto-save**: Implement client-side auto-save with 30-second intervals to prevent data loss.

6. **Preview**: Use preview tokens for sharing unpublished content with reviewers.

7. **Bulk Operations**: For large operations (>50 posts), consider batching requests.

8. **Caching**: Trending posts are cached for 1 hour. Clear cache when needed via admin panel.
