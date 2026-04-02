# Blog API Documentation

## Overview

The Blog API is a RESTful API built with Laravel 11 that provides complete functionality for a blog platform including posts, categories, tags, comments, users, and media management.

**Base URL:** `/api/v1`  
**Version:** v1  
**Format:** JSON

## Table of Contents

1. [Authentication](#authentication)
2. [Response Format](#response-format)
3. [Error Handling](#error-handling)
4. [Rate Limiting](#rate-limiting)
5. [Endpoints](#endpoints)
6. [Filtering & Sorting](#filtering--sorting)
7. [Pagination](#pagination)

---

## Authentication

Most endpoints require authentication using Laravel Sanctum tokens.

### Obtaining a Token

**POST** `/api/v1/auth/login`

```json
{
  "email": "user@example.com",
  "password": "password123",
  "remember": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

### Using the Token

Include the token in the `Authorization` header:

```
Authorization: Bearer 1|abc123...
```

### Registration

**POST** `/api/v1/auth/register`

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

---

## Response Format

All API responses follow a consistent JSON structure:

### Success Response

```json
{
  "success": true,
  "message": "Success",
  "data": { ... },
  "meta": {
    "version": "v1",
    "timestamp": "2024-01-15T10:00:00Z",
    "rate_limit": {
      "limit": 60,
      "remaining": 59,
      "reset": 1705312800
    }
  },
  "errors": null
}
```

### Paginated Response

```json
{
  "success": true,
  "message": "Success",
  "data": [ ... ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 15,
      "total": 150,
      "from": 1,
      "to": 15,
      "count": 15
    }
  },
  "links": {
    "first": "/api/v1/posts?page=1",
    "last": "/api/v1/posts?page=10",
    "prev": null,
    "next": "/api/v1/posts?page=2"
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "data": null,
  "error_code": "VALIDATION_ERROR",
  "errors": {
    "type": "validation_error",
    "errors": [
      {
        "field": "email",
        "messages": ["The email field is required."]
      }
    ]
  },
  "request_id": "req_abc123",
  "timestamp": "2024-01-15T10:00:00Z"
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 204 | No Content | Request successful, no content to return |
| 400 | Bad Request | Invalid request syntax or parameters |
| 401 | Unauthorized | Authentication required or failed |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 405 | Method Not Allowed | HTTP method not supported |
| 409 | Conflict | Resource conflict (e.g., duplicate) |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Error Codes

| Error Code | Description |
|------------|-------------|
| VALIDATION_ERROR | Request validation failed |
| UNAUTHORIZED | Authentication required |
| FORBIDDEN | Insufficient permissions |
| NOT_FOUND | Resource not found |
| METHOD_NOT_ALLOWED | HTTP method not supported |
| CONFLICT_ERROR | Resource conflict |
| RATE_LIMIT_EXCEEDED | Too many requests |
| INTERNAL_SERVER_ERROR | Server error |

---

## Rate Limiting

Rate limits are applied based on the endpoint type:

| Endpoint Type | Limit | Window |
|--------------|-------|--------|
| Public | 60 requests | 1 minute |
| Authenticated | 120 requests | 1 minute |
| Editor | 150 requests | 1 minute |
| Admin | 200 requests | 1 minute |
| Authentication | 10 requests | 1 minute |

### Rate Limit Headers

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1705312800
Retry-After: 60
```

---

## Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/auth/register` | Register new user | No |
| POST | `/auth/login` | Login user | No |
| POST | `/auth/logout` | Logout user | Yes |
| POST | `/auth/logout-all` | Logout all sessions | Yes |
| POST | `/auth/refresh` | Refresh token | Yes |
| GET | `/auth/me` | Get current user | Yes |
| POST | `/auth/forgot-password` | Request password reset | No |
| POST | `/auth/reset-password` | Reset password | No |
| GET | `/auth/verify/{id}/{hash}` | Verify email | No |
| POST | `/auth/verify-email` | Verify email (token) | Yes |

### Posts

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/posts` | List published posts | No |
| GET | `/posts/{slug}` | Get post by slug | No |
| POST | `/posts` | Create post | Yes |
| PUT | `/posts/{id}` | Update post | Yes |
| DELETE | `/posts/{id}` | Delete post | Yes |
| GET | `/user/posts` | Get user's posts | Yes |
| POST | `/posts/{id}/like` | Toggle like | Yes |
| POST | `/posts/{id}/bookmark` | Toggle bookmark | Yes |

#### Create Post

**POST** `/api/v1/posts`

```json
{
  "title": "My First Post",
  "slug": "my-first-post",
  "excerpt": "A brief introduction...",
  "content": "Full post content here...",
  "category_id": 1,
  "tags": [1, 2, 3],
  "status": "draft",
  "featured_image": "https://example.com/image.jpg",
  "meta_title": "SEO Title",
  "meta_description": "SEO description"
}
```

### Categories

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/categories` | List categories | No |
| GET | `/categories/{slug}` | Get category | No |
| GET | `/categories/{slug}/posts` | Get posts by category | No |
| POST | `/categories` | Create category | Admin |
| PUT | `/categories/{id}` | Update category | Admin |
| DELETE | `/categories/{id}` | Delete category | Admin |

### Tags

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/tags` | List tags | No |
| GET | `/tags/{slug}` | Get tag | No |
| GET | `/tags/{slug}/posts` | Get posts by tag | No |
| POST | `/tags` | Create tag | Admin |
| PUT | `/tags/{id}` | Update tag | Admin |
| DELETE | `/tags/{id}` | Delete tag | Admin |

### Comments

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/posts/{id}/comments` | List comments | No |
| POST | `/posts/{id}/comments` | Create comment | Yes |
| PUT | `/comments/{id}` | Update comment | Yes |
| DELETE | `/comments/{id}` | Delete comment | Yes |
| GET | `/admin/comments/pending` | Get pending comments | Admin |
| POST | `/comments/{id}/approve` | Approve comment | Admin |
| POST | `/comments/{id}/reject` | Reject comment | Admin |

#### Create Comment

**POST** `/api/v1/posts/{id}/comments`

```json
{
  "content": "Great article! Very helpful.",
  "parent_id": null,
  "is_anonymous": false
}
```

### Users

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/users/{id}` | Get user profile | No |
| GET | `/users/{id}/posts` | Get user's posts | No |
| GET | `/user/profile` | Get own profile | Yes |
| PUT | `/user/profile` | Update profile | Yes |
| PUT | `/user/password` | Update password | Yes |
| GET | `/admin/users` | List all users | Admin |
| POST | `/admin/users` | Create user | Admin |
| PUT | `/admin/users/{id}` | Update user | Admin |
| DELETE | `/admin/users/{id}` | Delete user | Admin |
| POST | `/admin/users/{id}/roles` | Assign roles | Admin |
| POST | `/admin/users/{id}/ban` | Ban user | Admin |

### Media

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/media/upload` | Upload file | Yes |
| GET | `/media` | List media | Yes |
| GET | `/media/{id}` | Get media | Yes |
| PUT | `/media/{id}` | Update media | Yes |
| DELETE | `/media/{id}` | Delete media | Yes |

#### Upload Media

**POST** `/api/v1/media/upload`

Content-Type: `multipart/form-data`

```
file: (binary)
collection_name: "posts"
alt_text: "Image description"
title: "Image Title"
```

### Search

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/search` | Global search | No |
| GET | `/search/suggest` | Search suggestions | No |

#### Search

**GET** `/api/v1/search?q=laravel&type=posts`

Parameters:
- `q` - Search query
- `type` - Resource type (posts, users, categories)
- `page` - Page number
- `per_page` - Items per page

---

## Filtering & Sorting

### Filtering

Most list endpoints support filtering via query parameters:

```
GET /api/v1/posts?filter[status]=published&filter[category_id]=1
```

### Filter Operators

| Operator | Example | Description |
|----------|---------|-------------|
| `=` | `filter[status]=published` | Exact match |
| `!=` | `filter[status]!=draft` | Not equal |
| `>` | `filter[views_count]>100` | Greater than |
| `>=` | `filter[views_count]>=100` | Greater or equal |
| `<` | `filter[views_count]<100` | Less than |
| `<=` | `filter[views_count]<=100` | Less or equal |
| `like` | `filter[title]=like:laravel` | Contains |
| `in` | `filter[status]=in:published,draft` | In array |

### Sorting

```
GET /api/v1/posts?sort=created_at&order=desc
GET /api/v1/posts?sort=-created_at  # Shorthand for desc
GET /api/v1/posts?sort=views_count,-created_at  # Multiple sorts
```

### Search

```
GET /api/v1/posts?search=laravel
```

### Includes (Eager Loading)

```
GET /api/v1/posts?include=author,category,tags
```

### Sparse Fieldsets

```
GET /api/v1/posts?fields[posts]=id,title,slug
```

---

## Pagination

All list endpoints support pagination:

### Query Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `page` | 1 | Page number |
| `per_page` | 15 | Items per page (max: 100) |

### Example

```
GET /api/v1/posts?page=2&per_page=25
```

### Response

```json
{
  "meta": {
    "pagination": {
      "current_page": 2,
      "last_page": 10,
      "per_page": 25,
      "total": 250,
      "from": 26,
      "to": 50,
      "count": 25
    }
  },
  "links": {
    "first": "/api/v1/posts?page=1",
    "last": "/api/v1/posts?page=10",
    "prev": "/api/v1/posts?page=1",
    "next": "/api/v1/posts?page=3"
  }
}
```

---

## API Versioning

### Version Header

Include the API version in the Accept header:

```
Accept: application/vnd.blog.v1+json
```

### Deprecation

When an API version is deprecated, responses include:

```
Deprecation: true
Sunset: Sat, 01 Oct 2024 00:00:00 GMT
Link: <https://docs.example.com/migration>; rel="deprecation"
```

---

## Best Practices

1. **Always use HTTPS** in production
2. **Include the Authorization header** for protected endpoints
3. **Handle rate limits** by checking response headers
4. **Use pagination** for list endpoints
5. **Use sparse fieldsets** to reduce payload size
6. **Cache responses** when appropriate
7. **Implement exponential backoff** for retries

---

## Support

For API support, contact: api-support@example.com

**Documentation Version:** 1.0.0  
**Last Updated:** 2024-01-15
