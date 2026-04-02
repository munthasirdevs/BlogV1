# Phase 9: User Interactions API Documentation

## Overview

Phase 9 implements a complete user interaction system including:
- **Likes** - Like posts and comments with toggle functionality
- **Bookmarks** - Save posts to collections with organization features
- **Shares** - Track social media shares with UTM parameter generation
- **Views** - Automatic view tracking with unique view detection
- **Reading Progress** - Track user reading progress per post
- **Engagement Scoring** - Calculate engagement scores for trending content

---

## Table of Contents

1. [Likes API](#likes-api)
2. [Bookmarks API](#bookmarks-api)
3. [Shares API](#shares-api)
4. [Reading Progress API](#reading-progress-api)
5. [Engagement Score](#engagement-score)

---

## Likes API

### Toggle Like on Post

**Endpoint:** `POST /api/v1/posts/{id}/like`

**Authentication:** Required

**Description:** Toggle like status on a post. Creates like if doesn't exist, deletes if exists.

**Response:**
```json
{
  "success": true,
  "data": {
    "liked": true,
    "likes_count": 1
  },
  "message": "Post liked successfully"
}
```

---

### Toggle Like on Comment

**Endpoint:** `POST /api/v1/comments/{id}/like`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "liked": true,
    "likes_count": 1
  },
  "message": "Comment liked successfully"
}
```

---

### Get Post Likers

**Endpoint:** `GET /api/v1/posts/{id}/likes`

**Authentication:** Required

**Query Parameters:**
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "name": "John Doe",
        "avatar": "https://..."
      },
      "liked_at": "2026-04-01T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 10,
    "total_pages": 1
  }
}
```

---

### Get User's Liked Posts

**Endpoint:** `GET /api/v1/users/{id}/likes/posts`

**Authentication:** Required (own likes only)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "post": {
        "id": 5,
        "title": "Post Title",
        "slug": "post-title",
        "excerpt": "...",
        "published_at": "2026-04-01T12:00:00Z",
        "author": { "id": 1, "name": "Author" },
        "category": { "id": 1, "name": "Category" }
      },
      "liked_at": "2026-04-01T12:00:00Z"
    }
  ],
  "meta": { ... }
}
```

---

### Get User's Liked Comments

**Endpoint:** `GET /api/v1/users/{id}/likes/comments`

**Authentication:** Required (own likes only)

---

## Bookmarks API

### Toggle Bookmark

**Endpoint:** `POST /api/v1/posts/{id}/bookmark`

**Authentication:** Required

**Request Body:**
```json
{
  "collection": "favorites",
  "notes": "Great article!"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "bookmarked": true,
    "bookmark": {
      "id": 1,
      "collection": "favorites",
      "notes": "Great article!",
      "created_at": "2026-04-01T12:00:00Z"
    },
    "action": "created"
  }
}
```

---

### Get User's Bookmarks

**Endpoint:** `GET /api/v1/user/bookmarks`

**Authentication:** Required

**Query Parameters:**
- `collection` (optional): Filter by collection name
- `per_page` (optional): Results per page

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "post": { ... },
      "collection": {
        "name": "favorites",
        "display_name": "Favorites"
      },
      "notes": "Great article!",
      "bookmarked_at": "2026-04-01T12:00:00Z"
    }
  ],
  "meta": { ... }
}
```

---

### Get Collections

**Endpoint:** `GET /api/v1/bookmarks/collections`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "name": "favorites",
      "display_name": "Favorites",
      "count": 5
    },
    {
      "name": "reading_later",
      "display_name": "Reading Later",
      "count": 3
    }
  ]
}
```

---

### Create Collection

**Endpoint:** `POST /api/v1/bookmarks/collections`

**Authentication:** Required

**Request Body:**
```json
{
  "name": "tech-articles"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "name": "tech_articles",
    "display_name": "Tech Articles",
    "count": 0
  }
}
```

---

### Update Collection

**Endpoint:** `PUT /api/v1/bookmarks/collections/{collection}`

**Authentication:** Required

**Request Body:**
```json
{
  "name": "new-name"
}
```

---

### Delete Collection

**Endpoint:** `DELETE /api/v1/bookmarks/collections/{collection}`

**Authentication:** Required

**Query Parameters:**
- `move_to_default` (optional): Move bookmarks to default instead of deleting

---

### Assign Bookmark to Collection

**Endpoint:** `POST /api/v1/bookmarks/{bookmarkId}/collection`

**Authentication:** Required

**Request Body:**
```json
{
  "collection": "favorites"
}
```

---

### Update Bookmark Notes

**Endpoint:** `PUT /api/v1/bookmarks/{bookmarkId}/notes`

**Authentication:** Required

**Request Body:**
```json
{
  "notes": "Updated notes"
}
```

---

### Get Bookmarks by Collection

**Endpoint:** `GET /api/v1/bookmarks/collection/{collection}`

**Authentication:** Required

---

### Search Bookmarks

**Endpoint:** `GET /api/v1/user/bookmarks/search`

**Authentication:** Required

**Query Parameters:**
- `q`: Search term (searches post titles)

---

### Get Bookmark Stats

**Endpoint:** `GET /api/v1/user/bookmarks/stats`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "total_bookmarks": 10,
    "total_collections": 3,
    "collections": [
      { "name": "default", "display_name": "Default", "count": 5 },
      { "name": "favorites", "display_name": "Favorites", "count": 3 },
      { "name": "reading_later", "display_name": "Reading Later", "count": 2 }
    ]
  }
}
```

---

## Shares API

### Track Share

**Endpoint:** `POST /api/v1/posts/{id}/share`

**Authentication:** Required

**Request Body:**
```json
{
  "provider": "twitter"
}
```

**Available Providers:**
- `twitter`
- `facebook`
- `linkedin`
- `reddit`
- `whatsapp`
- `email`
- `copy`

**Response:**
```json
{
  "success": true,
  "data": {
    "share_id": 1,
    "provider": "twitter",
    "share_count": 5,
    "share_url": "https://...?utm_source=twitter&utm_medium=social&..."
  }
}
```

---

### Get Share Count

**Endpoint:** `GET /api/v1/posts/{id}/share-count`

**Authentication:** Optional

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 10,
    "by_provider": {
      "twitter": 5,
      "facebook": 3,
      "linkedin": 2
    }
  }
}
```

---

### Generate Share URL

**Endpoint:** `GET /api/v1/posts/{id}/share-url`

**Authentication:** Optional

**Query Parameters:**
- `provider`: Share provider (default: copy)
- `params`: Additional query parameters

**Response:**
```json
{
  "success": true,
  "data": {
    "provider": "twitter",
    "share_url": "https://twitter.com/intent/tweet?url=...&text=...",
    "post_url": "https://...?utm_source=twitter&utm_medium=social&..."
  }
}
```

---

### Get Share Providers

**Endpoint:** `GET /api/v1/shares/providers`

**Authentication:** Optional

**Response:**
```json
{
  "success": true,
  "data": {
    "twitter": {
      "label": "Twitter",
      "icon": "fa-twitter",
      "color": "#1DA1F2"
    },
    "facebook": {
      "label": "Facebook",
      "icon": "fa-facebook",
      "color": "#4267B2"
    }
  }
}
```

---

### Get Share Statistics

**Endpoint:** `GET /api/v1/posts/{id}/share-stats`

**Authentication:** Optional

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 10,
    "by_provider": { "twitter": 5, "facebook": 3 },
    "most_popular": "twitter"
  }
}
```

---

### Get Share Analytics

**Endpoint:** `GET /api/v1/posts/{id}/share-analytics`

**Authentication:** Optional

**Query Parameters:**
- `days`: Number of days (default: 30)

---

### Get Trending Posts by Shares

**Endpoint:** `GET /api/v1/shares/trending`

**Authentication:** Optional

**Query Parameters:**
- `days`: Time window (default: 7)
- `limit`: Results limit (default: 10)

---

### Get User's Shares

**Endpoint:** `GET /api/v1/user/shares`

**Authentication:** Required

---

## Reading Progress API

### Update Reading Progress

**Endpoint:** `POST /api/v1/posts/{id}/progress`

**Authentication:** Required

**Request Body:**
```json
{
  "percentage": 75,
  "time_spent": 180
}
```

**Validation:**
- `percentage`: Required, integer, 0-100
- `time_spent`: Optional, integer, min: 0 (seconds)

**Response:**
```json
{
  "success": true,
  "data": {
    "post_id": 1,
    "percentage": 75,
    "time_spent": 180,
    "time_spent_formatted": "3m 0s",
    "is_complete": false,
    "last_read_at": "2026-04-01T12:00:00Z"
  }
}
```

---

### Get Reading Progress

**Endpoint:** `GET /api/v1/posts/{id}/progress`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "post_id": 1,
    "percentage": 75,
    "time_spent": 180,
    "is_complete": false
  }
}
```

---

### Get Reading Stats

**Endpoint:** `GET /api/v1/user/reading/stats`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "total_posts": 10,
    "completed": 5,
    "in_progress": 5,
    "total_time_spent": 3600,
    "total_time_spent_formatted": "1h 0m",
    "completion_rate": 50.0
  }
}
```

---

### Get Reading History

**Endpoint:** `GET /api/v1/user/reading/history`

**Authentication:** Required

**Query Parameters:**
- `per_page`: Results per page (default: 15)
- `days`: Time window (default: 30)

---

## Engagement Score

The engagement score is calculated automatically using the following weights:

| Action     | Weight |
|------------|--------|
| View       | 1      |
| Like       | 5      |
| Comment    | 10     |
| Bookmark   | 8      |
| Share      | 15     |

**Formula:**
```
raw_score = (views × 1) + (likes × 5) + (comments × 10) + (bookmarks × 8) + (shares × 15)
final_score = raw_score × time_decay_factor
```

**Time Decay:** Newer posts get a boost using logarithmic decay based on days since publication.

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "error_code": "VALIDATION_ERROR",
  "errors": {
    "type": "validation_error",
    "errors": [
      {
        "field": "percentage",
        "messages": ["The percentage must be between 0 and 100."]
      }
    ]
  }
}
```

---

## Rate Limiting

| Endpoint Type      | Limit              |
|--------------------|--------------------|
| Public             | 60 requests/min    |
| Authenticated      | 120 requests/min   |
| Collection Updates | 120 requests/min   |

---

## Implementation Notes

### Race Condition Prevention
- Like and bookmark toggle operations use database transactions with row locking (`lockForUpdate()`)
- Unique constraints prevent duplicate likes/bookmarks
- Count updates are atomic using `increment()`/`decrement()`

### Caching
- Like counts cached for 5 minutes
- Share counts cached for 5 minutes
- Engagement scores cached for 10 minutes

### Unique View Detection
- Views tracked with 24-hour uniqueness window
- Identified by session ID + user ID
- Bot traffic automatically filtered
- Author views excluded from counts

### Database Indexes
- `likes`: user_id, likeable_id + likeable_type, unique(user_id, likeable_id, likeable_type)
- `bookmarks`: user_id, post_id, collection_name
- `post_shares`: post_id, provider, user_id
- `post_reading_progress`: user_id + post_id (unique), percentage

---

## Testing

Run tests with:
```bash
php artisan test --filter "FeatureTest"
```

Test classes:
- `LikeFeatureTest.php` - Like functionality tests
- `BookmarkFeatureTest.php` - Bookmark functionality tests
- `ShareFeatureTest.php` - Share tracking tests
- `ViewTrackingFeatureTest.php` - View and progress tests
- `ConcurrentInteractionsTest.php` - Race condition tests

---

## Service Classes

- `LikeService` - Like business logic
- `BookmarkService` - Bookmark and collection management
- `ShareService` - Share tracking and URL generation
- `ViewService` - View tracking with uniqueness
- `EngagementScoreService` - Engagement calculation

---

## Repository Classes

- `LikeRepository` - Like data access
- `BookmarkRepository` - Bookmark data access

---

## Middleware

- `ViewTrackingMiddleware` - Auto-tracks views on post show endpoints
