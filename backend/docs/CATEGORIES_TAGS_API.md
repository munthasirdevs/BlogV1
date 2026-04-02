# Phase 7: Categories & Tags System - API Documentation

## Overview

This document provides comprehensive API documentation for the Categories & Tags system implemented in Phase 7. The system provides full CRUD operations, hierarchical category support, tag management, and post filtering capabilities.

---

## Table of Contents

1. [Categories API](#categories-api)
2. [Tags API](#tags-api)
3. [Post Filtering](#post-filtering)
4. [Authorization](#authorization)
5. [Error Handling](#error-handling)
6. [Caching](#caching)

---

## Categories API

### List Categories

```http
GET /api/v1/categories
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| search | string | - | Search by name or description |
| parent_id | integer | - | Filter by parent category |
| is_active | boolean | - | Filter by active status |
| is_featured | boolean | - | Filter by featured status |
| sort | string | sort_order | Sort field (name, sort_order, created_at) |
| order | string | asc | Sort order (asc, desc) |
| per_page | integer | 15 | Items per page (max 100) |
| page | integer | 1 | Page number |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Technology",
      "slug": "technology",
      "description": "Tech related posts",
      "color": "#3B82F6",
      "icon": "fa-code",
      "sort_order": 1,
      "is_featured": true,
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "posts_count": 10,
      "can": {
        "update": false,
        "delete": false
      }
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
    "first": "...",
    "prev": null,
    "next": "...",
    "last": "..."
  }
}
```

---

### Get Category Tree

```http
GET /api/v1/categories/tree
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| max_depth | integer | 3 | Maximum depth (1-3) |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Technology",
      "slug": "technology",
      "depth": 0,
      "published_posts_count": 10,
      "total_posts_count": 15,
      "children": [
        {
          "id": 2,
          "name": "Programming",
          "slug": "programming",
          "depth": 1,
          "published_posts_count": 5,
          "children": []
        }
      ]
    }
  ]
}
```

---

### Get Single Category

```http
GET /api/v1/categories/{slug}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Technology",
    "slug": "technology",
    "description": "Tech related posts",
    "color": "#3B82F6",
    "parent": null,
    "children": [
      {
        "id": 2,
        "name": "Programming",
        "slug": "programming",
        "published_posts_count": 5
      }
    ],
    "full_name": "Technology",
    "path": [
      {"id": 1, "name": "Technology", "slug": "technology"}
    ],
    "total_posts_count": 15
  }
}
```

---

### Get Posts by Category

```http
GET /api/v1/categories/{slug}/posts
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| include_children | boolean | true | Include posts from child categories |
| per_page | integer | 15 | Items per page |

**Response:**

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "total_pages": 4,
    "category": {
      "id": 1,
      "name": "Technology",
      "slug": "technology"
    }
  }
}
```

---

### Create Category

```http
POST /api/v1/categories
```

**Authorization:** Editor, Admin

**Request Body:**

```json
{
  "name": "New Category",
  "slug": "new-category", // Optional, auto-generated if not provided
  "description": "Category description",
  "parent_id": 1, // Optional, for nested categories
  "color": "#3B82F6", // Optional, hex color
  "icon": "fa-code", // Optional
  "sort_order": 1, // Optional
  "is_featured": false, // Optional
  "is_active": true // Optional
}
```

**Validation Rules:**
- `name`: required, string, 2-100 characters
- `slug`: optional, string, max 255, must be unique
- `description`: optional, string, max 500 characters
- `parent_id`: optional, must exist, cannot create circular reference
- `color`: optional, valid hex color (#RRGGBB)
- `sort_order`: optional, integer, min 0

**Response:** `201 Created`

---

### Update Category

```http
PUT /api/v1/categories/{id}
```

**Authorization:** Editor, Admin

**Request Body:** (all fields optional)

```json
{
  "name": "Updated Category",
  "slug": "updated-category",
  "description": "Updated description",
  "parent_id": 2,
  "color": "#10B981",
  "is_active": false
}
```

**Response:** `200 OK`

---

### Reorder Categories

```http
POST /api/v1/categories/reorder
```

**Authorization:** Editor, Admin

**Request Body:**

```json
{
  "categories": [
    {"id": 1, "sort_order": 1},
    {"id": 2, "sort_order": 2},
    {"id": 3, "sort_order": 3}
  ]
}
```

**Response:** `200 OK`

---

### Delete Category

```http
DELETE /api/v1/categories/{id}
```

**Authorization:** Admin only

**Response:** `204 No Content`

**Error:** `400 Bad Request` if category has children or posts

---

### Delete Category with Cascade

```http
POST /api/v1/categories/{id}/delete-with-cascade
```

**Authorization:** Admin only

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| cascade | boolean | false | If true, delete children; if false, move children to root |

**Response:** `204 No Content`

---

### Get Category Statistics

```http
GET /api/v1/categories/{id}/stats
```

**Response:**

```json
{
  "success": true,
  "data": {
    "posts_count": 25,
    "published_posts_count": 20,
    "children_count": 3,
    "descendants_count": 5
  }
}
```

---

## Tags API

### List Tags

```http
GET /api/v1/tags
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| search | string | - | Search by name or description |
| is_featured | boolean | - | Filter by featured status |
| sort | string | name | Sort field (name, created_at) |
| order | string | asc | Sort order (asc, desc) |
| per_page | integer | 15 | Items per page |

---

### Get Popular Tags

```http
GET /api/v1/tags/popular
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | integer | 20 | Number of tags (max 50) |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Laravel",
      "slug": "laravel",
      "posts_count": 45,
      "is_featured": true
    }
  ]
}
```

---

### Get Tag Suggestions

```http
GET /api/v1/tags/suggest
```

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| q | string | Yes | - | Search query |
| limit | integer | No | 10 | Suggestions limit (max 20) |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Laravel",
      "slug": "laravel",
      "posts_count": 45
    }
  ]
}
```

---

### Get Single Tag

```http
GET /api/v1/tags/{slug}
```

---

### Get Posts by Tag

```http
GET /api/v1/tags/{slug}/posts
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | integer | 15 | Items per page |

---

### Create Tag

```http
POST /api/v1/tags
```

**Authorization:** Editor, Admin

**Request Body:**

```json
{
  "name": "Laravel",
  "slug": "laravel", // Optional
  "description": "Laravel framework tag",
  "color": "#FF2D20",
  "is_featured": false
}
```

**Validation Rules:**
- `name`: required, string, 2-50 characters
- `slug`: optional, string, max 255, must be unique
- `description`: optional, string, max 300 characters
- `color`: optional, valid hex color

---

### Update Tag

```http
PUT /api/v1/tags/{id}
```

**Authorization:** Editor, Admin

---

### Delete Tag

```http
DELETE /api/v1/tags/{id}
```

**Authorization:** Admin only

**Error:** `400 Bad Request` if tag is attached to posts

---

### Attach Tags to Post

```http
POST /api/v1/posts/{postId}/tags
```

**Authorization:** Post author, Editor, Admin

**Request Body:**

```json
{
  "tags": ["laravel", "php", "web-development"],
  "create_if_not_exist": false
}
```

**Response:**

```json
{
  "success": true,
  "message": "Tags attached successfully",
  "data": {
    "post_id": 1,
    "tags": [...],
    "attached_count": 3
  }
}
```

---

### Detach Tag from Post

```http
DELETE /api/v1/posts/{postId}/tags/{tagId}
```

**Authorization:** Post author, Editor, Admin

**Response:**

```json
{
  "success": true,
  "message": "Tag detached successfully",
  "data": {
    "post_id": 1,
    "tag_id": 5
  }
}
```

---

### Get Tag Cloud

```http
GET /api/v1/tags/cloud
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | integer | 20 | Number of tags (max 50) |

Returns tags with weight (1-5) based on popularity.

---

## Post Filtering

### Filter Posts by Category and Tag

```http
GET /api/v1/posts
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| category | string/array | Category slug(s) - OR logic |
| tag | string/array | Tag slug(s) - AND logic |
| status | string | Post status |
| author | integer | Author ID |
| search | string | Search term |
| featured | boolean | Featured posts only |
| sort | string | Sort field |
| order | string | Sort order |

**Examples:**

```
# Single category
GET /api/v1/posts?category=technology

# Multiple categories (OR)
GET /api/v1/posts?category[]=technology&category[]=science

# Single tag
GET /api/v1/posts?tag=laravel

# Multiple tags (AND)
GET /api/v1/posts?tag[]=laravel&tag[]=php

# Combined
GET /api/v1/posts?category=technology&tag=laravel
```

---

## Authorization

### Permission Matrix

| Role | View | Create | Update | Delete | Reorder |
|------|------|--------|--------|--------|---------|
| Public | ✓ | ✗ | ✗ | ✗ | ✗ |
| Subscriber | ✓ | ✗ | ✗ | ✗ | ✗ |
| Author | ✓ | ✗ | ✗ | ✗ | ✗ |
| Editor | ✓ | ✓ | ✓ | ✗ | ✓ |
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |

### Tag Permissions

| Role | View | Create | Update | Delete | Attach to Posts |
|------|------|--------|--------|--------|-----------------|
| Public | ✓ | ✗ | ✗ | ✗ | ✗ |
| Subscriber | ✓ | ✗ | ✗ | ✗ | ✗ |
| Author | ✓ | ✗ | ✗ | ✗ | Own posts only |
| Editor | ✓ | ✓ | ✓ | ✗ | All posts |
| Admin | ✓ | ✓ | ✓ | ✓ | All posts |

---

## Error Handling

### Standard Error Response

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 204 | No Content (successful delete) |
| 400 | Bad Request (cannot delete with children/posts) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 422 | Validation Error |

---

## Caching

### Cache Strategy

| Endpoint | Cache Duration | Cache Key |
|----------|----------------|-----------|
| GET /categories/tree | 1 hour | categories.tree |
| GET /tags/popular | 1 hour | tags.popular.{limit} |
| GET /tags/suggest | 1 hour | tags.suggestions.{hash}.{limit} |

### Cache Invalidation

Cache is automatically cleared when:
- Categories are created, updated, deleted, or reordered
- Tags are created, updated, or deleted
- Posts are published/unpublished (affects post counts)

---

## Hierarchical Categories

### Depth Limit

Maximum nesting depth: **3 levels**

```
Level 1 (parent_id: null)
└── Level 2 (parent_id: Level 1)
    └── Level 3 (parent_id: Level 2)
        └── Level 4 (REJECTED - exceeds max depth)
```

### Circular Reference Prevention

The system prevents circular references:
- A category cannot be its own parent
- A category cannot be moved to one of its descendants

### Orphaned Categories

When deleting a parent category:
- **Default behavior:** Children become root level (parent_id = null)
- **Cascade delete:** Children are also deleted (use `?cascade=true`)

---

## Code Examples

### JavaScript/TypeScript

```typescript
// Get category tree
const tree = await fetch('/api/v1/categories/tree')
  .then(res => res.json());

// Create category
const category = await fetch('/api/v1/categories', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    name: 'New Category',
    color: '#3B82F6'
  })
});

// Attach tags to post
await fetch(`/api/v1/posts/${postId}/tags`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    tags: ['laravel', 'php'],
    create_if_not_exist: true
  })
});
```

### PHP

```php
// Get popular tags
$response = Http::get('/api/v1/tags/popular?limit=10');
$tags = $response->json('data');

// Create category
$response = Http::withToken($token)->post('/api/v1/categories', [
    'name' => 'New Category',
    'parent_id' => 1
]);

// Filter posts by category and tag
$posts = Http::get('/api/v1/posts', [
    'category' => 'technology',
    'tag' => ['laravel', 'php']
]);
```

---

## Testing

Run the test suite:

```bash
php artisan test --filter CategoryApiTest
php artisan test --filter TagApiTest
```

Test coverage includes:
- CRUD operations
- Authorization checks
- Hierarchical structure
- Post filtering
- Tag suggestions
- Popular tags
- Combined filtering

---

## Summary

Phase 7 implements a complete Categories & Tags system with:

- **18 API endpoints** for categories
- **11 API endpoints** for tags
- **Hierarchical categories** with 3-level depth limit
- **Auto-generated slugs** with uniqueness validation
- **Post count caching** for performance
- **Tag suggestions** ordered by popularity
- **Combined filtering** (multiple categories OR, multiple tags AND)
- **Comprehensive authorization** using policies
- **40+ feature tests** covering all functionality
