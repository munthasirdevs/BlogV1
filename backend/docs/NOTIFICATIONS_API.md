# Phase 13: Notifications System - API Documentation

## Overview

The Notifications System provides a comprehensive real-time and email notification system for user activities in the blog platform. It supports multiple notification channels (database, email, broadcast), user preferences, and scheduled digest emails.

## Features

- **Multiple Channels**: Database, Email, and Real-time (WebSocket/Broadcast)
- **Notification Types**: Comments, Likes, Mentions, Post Published
- **User Preferences**: Per-type and per-channel configuration
- **Real-time Updates**: Laravel Echo integration for instant notifications
- **Scheduled Jobs**: Daily/Weekly digest and cleanup of old notifications
- **GDPR Compliant**: Export and delete notification data

## API Endpoints

### Authentication Required
All notification endpoints require authentication via Sanctum token.

---

## Notifications

### List Notifications

Get paginated list of user's notifications.

```http
GET /api/v1/notifications
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| page | integer | Page number (default: 1) |
| per_page | integer | Items per page (default: 50, max: 100) |
| read_status | string | Filter by read status: `read`, `unread` |
| type | string | Filter by notification type |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-string",
      "type": "new_comment",
      "notification_class": "App\\Notifications\\NewCommentNotification",
      "title": "New Comment on Your Post",
      "message": "John Doe commented on your post \"My First Post\": \"Great article!\"",
      "action_url": "http://localhost:3000/posts/my-first-post#comment-123",
      "is_read": false,
      "read_at": null,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "from_user": {
        "id": 2,
        "name": "John Doe",
        "avatar": "http://localhost:3000/avatars/john.jpg"
      },
      "data": {
        "comment_id": 123,
        "post_id": 1,
        "post_slug": "my-first-post",
        "post_title": "My First Post"
      },
      "icon": "comment",
      "color": "blue"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 50,
    "total": 234,
    "from": 1,
    "to": 50
  },
  "links": {
    "first": "http://localhost/api/v1/notifications?page=1",
    "last": "http://localhost/api/v1/notifications?page=5",
    "prev": null,
    "next": "http://localhost/api/v1/notifications?page=2"
  }
}
```

---

### Get Single Notification

Get details of a specific notification.

```http
GET /api/v1/notifications/{id}
```

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | uuid | Notification ID |

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid-string",
    "type": "new_comment",
    "title": "New Comment on Your Post",
    "message": "John Doe commented on your post...",
    "action_url": "http://localhost:3000/posts/my-first-post#comment-123",
    "is_read": false,
    "read_at": null,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "from_user": { ... },
    "data": { ... },
    "icon": "comment",
    "color": "blue"
  }
}
```

---

### Mark Notification as Read

Mark a single notification as read.

```http
POST /api/v1/notifications/{id}/read
```

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | uuid | Notification ID |

**Response:**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

### Mark Notification as Unread

Mark a single notification as unread.

```http
POST /api/v1/notifications/{id}/unread
```

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | uuid | Notification ID |

**Response:**
```json
{
  "success": true,
  "message": "Notification marked as unread"
}
```

---

### Delete Notification

Delete a single notification.

```http
DELETE /api/v1/notifications/{id}
```

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | uuid | Notification ID |

**Response:**
```json
{
  "success": true,
  "message": "Notification deleted successfully"
}
```

---

### Mark All Notifications as Read

Mark all user notifications as read.

```http
POST /api/v1/notifications/mark-all-read
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| type | string | Optional: Filter by notification type |

**Response:**
```json
{
  "success": true,
  "message": "Marked 25 notification(s) as read",
  "data": {
    "marked_count": 25
  }
}
```

---

### Get Unread Count

Get count of unread notifications (for badge display).

```http
GET /api/v1/notifications/unread-count
```

**Response:**
```json
{
  "success": true,
  "data": {
    "unread_count": 12
  }
}
```

**Caching:** This endpoint is cached for 1 minute.

---

### Get Notification Statistics

Get detailed statistics about user's notifications.

```http
GET /api/v1/notifications/stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 234,
    "unread": 12,
    "read": 222,
    "today": 5,
    "unread_today": 3,
    "by_type": {
      "new_comment": 45,
      "new_like_post": 120,
      "new_like_comment": 30,
      "mention": 15,
      "post_published": 24
    }
  }
}
```

---

### Send Test Notification (Development Only)

Send a test notification to the authenticated user. Only available in local/development environments.

```http
POST /api/v1/notifications/test
```

**Response:**
```json
{
  "success": true,
  "message": "Test notification sent successfully"
}
```

---

## Notification Preferences

### Get Preferences

Get user's notification preferences for all notification types.

```http
GET /api/v1/users/me/notification-preferences
```

**Response:**
```json
{
  "success": true,
  "data": {
    "preferences": {
      "new_comment": {
        "label": "New Comment on Your Post",
        "enabled": true,
        "channels": ["database", "email"]
      },
      "new_reply": {
        "label": "New Reply to Your Comment",
        "enabled": true,
        "channels": ["database", "email"]
      },
      "new_like_post": {
        "label": "New Like on Your Post",
        "enabled": true,
        "channels": ["database"]
      },
      "new_like_comment": {
        "label": "New Like on Your Comment",
        "enabled": true,
        "channels": ["database"]
      },
      "mention": {
        "label": "Mention in a Comment",
        "enabled": true,
        "channels": ["database", "email"]
      },
      "post_published": {
        "label": "New Post Published (Newsletter)",
        "enabled": true,
        "channels": ["database", "email"]
      },
      "digest": {
        "label": "Daily/Weekly Digest",
        "enabled": false,
        "channels": ["email"]
      }
    },
    "available_channels": {
      "database": "In-App Notifications",
      "email": "Email Notifications",
      "broadcast": "Real-time (WebSocket)"
    }
  }
}
```

---

### Update Preferences

Update user's notification preferences.

```http
PUT /api/v1/users/me/notification-preferences
```

**Request Body:**
```json
{
  "preferences": {
    "new_comment": {
      "enabled": true,
      "channels": ["database", "email"]
    },
    "new_like_post": {
      "enabled": false,
      "channels": []
    },
    "mention": {
      "enabled": true,
      "channels": ["database"]
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Notification preferences updated successfully",
  "data": {
    "preferences": { ... },
    "available_channels": { ... }
  }
}
```

---

## Notification Types

| Type | Description | Default Channels |
|------|-------------|------------------|
| `new_comment` | Someone comments on your post | database, email |
| `new_reply` | Someone replies to your comment | database, email |
| `new_like_post` | Someone likes your post | database |
| `new_like_comment` | Someone likes your comment | database |
| `mention` | Someone mentions you with @username | database, email |
| `post_published` | New post published (subscribers) | database, email |
| `digest` | Daily/Weekly digest summary | email |

---

## Real-time Broadcasting (Laravel Echo)

### Channel Names

Notifications are broadcast on private channels:
- `user.{userId}` - User-specific channel
- `notifications.{userId}` - Notifications channel

### Event Name

```javascript
'notification.created'
```

### Event Data

```javascript
{
  id: "notification-uuid",
  type: "new_comment",
  notification_type: "new_comment",
  title: "New Comment on Your Post",
  message: "John Doe commented...",
  action_url: "http://localhost:3000/posts/...",
  from_user: { id, name, avatar },
  created_at: "2024-01-15T10:30:00.000000Z",
  user_id: 1
}
```

### Frontend Integration Example

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Echo
window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
  auth: {
    headers: {
      Authorization: `Bearer ${userToken}`,
    },
  },
});

// Subscribe to notifications channel
Echo.private(`notifications.${userId}`)
  .listen('.notification.created', (event) => {
    console.log('New notification:', event);
    
    // Update notification badge
    updateNotificationBadge();
    
    // Add notification to list
    addNotificationToList(event);
    
    // Show toast notification
    showToastNotification(event);
  });
```

---

## Scheduled Jobs

### Notification Cleanup

**Command:** `notifications:cleanup-old --days=30`

**Schedule:** Weekly on Sunday at 4:00 AM

Deletes read notifications older than 30 days to reduce database size.

### Daily Digest

**Job:** `SendNotificationDigest('daily', 1)`

**Schedule:** Daily at 7:00 AM

Sends a digest email with notifications from the past 24 hours to users who have digest enabled.

### Weekly Digest

**Job:** `SendNotificationDigest('weekly', 7)`

**Schedule:** Weekly on Monday at 7:00 AM

Sends a digest email with notifications from the past 7 days.

---

## Error Responses

### 404 Not Found

```json
{
  "success": false,
  "message": "Notification not found"
}
```

### 403 Forbidden (Test Notification in Production)

```json
{
  "success": false,
  "message": "Test notifications are only available in development"
}
```

### 422 Validation Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "preferences.*.channels.*.in": ["Invalid channel specified."]
  }
}
```

---

## Rate Limiting

All notification endpoints are rate limited to 120 requests per minute for authenticated users.

---

## GDPR Compliance

### Export Notification Data

Users can request export of their notification data via the subscription export endpoint:

```http
POST /api/v1/subscriptions/export
```

### Delete Notification Data

Users can request deletion of their notification data:

```http
DELETE /api/v1/subscriptions/delete
```

---

## Implementation Notes

1. **Authorization**: Users can only access their own notifications
2. **Caching**: Unread count is cached for 1 minute
3. **Queue**: All notifications are queued for async processing
4. **Preferences**: User preferences are checked before sending notifications
5. **Broadcasting**: Real-time updates require Laravel Echo configuration
