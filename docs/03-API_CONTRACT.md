# API Contract Specification
## Masterclass Blog Platform - API v1

**Version:** 1.0  
**Base URL:** `/api/v1`  
**Format:** JSON  
**Authentication:** Bearer Token (Sanctum)

---

## 1. API STANDARDS

### 1.1 Request/Response Format

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}  (for protected endpoints)
```

**Response Format:**
```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful",
    "meta": { ... }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    },
    "code": "VALIDATION_ERROR"
}
```

### 1.2 HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET, PUT, PATCH |
| 201 | Created | Successful POST (resource created) |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid input |
| 401 | Unauthorized | Missing/invalid authentication |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Duplicate resource |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### 1.3 Pagination

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

**Response:**
```json
{
    "success": true,
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 150,
        "total_pages": 10,
        "has_more": true
    },
    "links": {
        "first": "/api/v1/posts?page=1",
        "prev": null,
        "next": "/api/v1/posts?page=2",
        "last": "/api/v1/posts?page=10"
    }
}
```

---

## 2. AUTHENTICATION ENDPOINTS

### 2.1 Register User

**Endpoint:** `POST /auth/register`

**Authentication:** Not required

**Request Body:**
```json
{
    "name": {
        "type": "string",
        "required": true,
        "min": 2,
        "max": 255
    },
    "email": {
        "type": "string",
        "required": true,
        "format": "email",
        "unique": true
    },
    "password": {
        "type": "string",
        "required": true,
        "min": 8,
        "rules": ["letters", "numbers"]
    },
    "password_confirmation": {
        "type": "string",
        "required": true,
        "match": "password"
    }
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Registration successful. Please verify your email.",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified": false,
            "created_at": "2026-04-01T10:00:00Z"
        }
    }
}
```

**Error Responses:**
- 409: Email already registered
- 422: Validation errors

---

### 2.2 Login

**Endpoint:** `POST /auth/login`

**Authentication:** Not required

**Request Body:**
```json
{
    "email": {
        "type": "string",
        "required": true,
        "format": "email"
    },
    "password": {
        "type": "string",
        "required": true
    },
    "remember": {
        "type": "boolean",
        "required": false,
        "default": false
    }
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "1|abc123xyz...",
        "token_type": "Bearer",
        "expires_in": 86400,
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified": true,
            "role": "user",
            "avatar": "https://...",
            "bio": "Writer and developer"
        }
    }
}
```

**Error Responses:**
- 401: Invalid credentials
- 422: Validation errors

---

### 2.3 Logout

**Endpoint:** `POST /auth/logout`

**Authentication:** Required

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

### 2.4 Get Current User

**Endpoint:** `GET /auth/me`

**Authentication:** Required

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified": true,
        "role": "user",
        "avatar": "https://...",
        "bio": "Writer and developer",
        "created_at": "2026-01-15T08:30:00Z",
        "posts_count": 12,
        "comments_count": 45
    }
}
```

---

### 2.5 Forgot Password

**Endpoint:** `POST /auth/forgot-password`

**Authentication:** Not required

**Request Body:**
```json
{
    "email": {
        "type": "string",
        "required": true,
        "format": "email"
    }
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Password reset link sent to your email"
}
```

---

### 2.6 Reset Password

**Endpoint:** `POST /auth/reset-password`

**Authentication:** Not required

**Request Body:**
```json
{
    "token": {
        "type": "string",
        "required": true
    },
    "email": {
        "type": "string",
        "required": true,
        "format": "email"
    },
    "password": {
        "type": "string",
        "required": true,
        "min": 8
    },
    "password_confirmation": {
        "type": "string",
        "required": true
    }
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Password reset successfully"
}
```

---

### 2.7 Verify Email

**Endpoint:** `POST /auth/verify-email`

**Authentication:** Required

**Request Body:**
```json
{
    "token": {
        "type": "string",
        "required": true
    }
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Email verified successfully"
}
```

---

### 2.8 Resend Verification Email

**Endpoint:** `POST /auth/resend-verification`

**Authentication:** Required

**Success Response (200):**
```json
{
    "success": true,
    "message": "Verification email sent"
}
```

---

## 3. POSTS ENDPOINTS

### 3.1 List Posts

**Endpoint:** `GET /posts`

**Authentication:** Not required

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 15 | Items per page |
| category | string | - | Filter by category slug |
| tag | string | - | Filter by tag slug |
| author | integer | - | Filter by author ID |
| search | string | - | Search in title/content |
| sort | string | "published_at" | Sort field |
| order | string | "desc" | Sort order (asc/desc) |
| status | string | "published" | Filter by status |

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Getting Started with Laravel",
            "slug": "getting-started-with-laravel",
            "excerpt": "Learn the basics of Laravel framework...",
            "content": "Full content here...",
            "featured_image": "https://...",
            "status": "published",
            "views_count": 1234,
            "reading_time": 5,
            "published_at": "2026-04-01T10:00:00Z",
            "created_at": "2026-03-28T08:00:00Z",
            "updated_at": "2026-04-01T09:00:00Z",
            "author": {
                "id": 1,
                "name": "John Doe",
                "avatar": "https://..."
            },
            "category": {
                "id": 1,
                "name": "Tutorials",
                "slug": "tutorials"
            },
            "tags": [
                {
                    "id": 1,
                    "name": "Laravel",
                    "slug": "laravel"
                }
            ],
            "comments_count": 12,
            "likes_count": 45,
            "is_liked": false,
            "is_bookmarked": false
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 150,
        "total_pages": 10,
        "has_more": true
    }
}
```

---

### 3.2 Get Single Post

**Endpoint:** `GET /posts/{slug}`

**Authentication:** Not required

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| slug | string | Post slug |

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Getting Started with Laravel",
        "slug": "getting-started-with-laravel",
        "excerpt": "Learn the basics of Laravel framework...",
        "content": "<p>Full HTML content here...</p>",
        "featured_image": "https://...",
        "status": "published",
        "views_count": 1235,
        "reading_time": 5,
        "published_at": "2026-04-01T10:00:00Z",
        "created_at": "2026-03-28T08:00:00Z",
        "updated_at": "2026-04-01T09:00:00Z",
        "author": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "avatar": "https://...",
            "bio": "Senior Developer",
            "posts_count": 12
        },
        "category": {
            "id": 1,
            "name": "Tutorials",
            "slug": "tutorials",
            "description": "Step-by-step guides"
        },
        "tags": [
            {
                "id": 1,
                "name": "Laravel",
                "slug": "laravel"
            },
            {
                "id": 2,
                "name": "PHP",
                "slug": "php"
            }
        ],
        "comments_count": 12,
        "likes_count": 45,
        "is_liked": false,
        "is_bookmarked": false,
        "related_posts": [
            {
                "id": 2,
                "title": "Advanced Laravel Techniques",
                "slug": "advanced-laravel-techniques",
                "excerpt": "...",
                "featured_image": "https://..."
            }
        ]
    }
}
```

**Error Responses:**
- 404: Post not found

---

### 3.3 Create Post

**Endpoint:** `POST /posts`

**Authentication:** Required (user or admin)

**Request Body:**
```json
{
    "title": {
        "type": "string",
        "required": true,
        "min": 5,
        "max": 200
    },
    "slug": {
        "type": "string",
        "required": false,
        "description": "Auto-generated if not provided"
    },
    "excerpt": {
        "type": "string",
        "required": false,
        "max": 500,
        "description": "Auto-generated from content if not provided"
    },
    "content": {
        "type": "string",
        "required": true,
        "min": 50
    },
    "featured_image": {
        "type": "string",
        "required": false,
        "format": "url"
    },
    "category_id": {
        "type": "integer",
        "required": true,
        "exists": "categories.id"
    },
    "tags": {
        "type": "array",
        "required": false,
        "items": "integer",
        "max_items": 10,
        "exists": "tags.id"
    },
    "status": {
        "type": "string",
        "required": false,
        "enum": ["draft", "published"],
        "default": "draft"
    },
    "published_at": {
        "type": "string",
        "required": false,
        "format": "datetime"
    },
    "meta_title": {
        "type": "string",
        "required": false,
        "max": 60
    },
    "meta_description": {
        "type": "string",
        "required": false,
        "max": 160
    }
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Post created successfully",
    "data": {
        "id": 1,
        "title": "Getting Started with Laravel",
        "slug": "getting-started-with-laravel",
        "status": "draft",
        "created_at": "2026-04-01T10:00:00Z"
    }
}
```

**Error Responses:**
- 403: Email not verified
- 422: Validation errors

---

### 3.4 Update Post

**Endpoint:** `PUT /posts/{id}`

**Authentication:** Required (owner or admin)

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Post ID |

**Request Body:** Same as Create Post (all fields optional)

**Success Response (200):**
```json
{
    "success": true,
    "message": "Post updated successfully",
    "data": {
        "id": 1,
        "title": "Getting Started with Laravel",
        "slug": "getting-started-with-laravel",
        "updated_at": "2026-04-01T11:00:00Z"
    }
}
```

**Error Responses:**
- 403: Unauthorized to edit this post
- 404: Post not found
- 422: Validation errors

---

### 3.5 Delete Post

**Endpoint:** `DELETE /posts/{id}`

**Authentication:** Required (owner or admin)

**Success Response (204):** No content

**Error Responses:**
- 403: Unauthorized to delete this post
- 404: Post not found

---

### 3.6 Get User's Posts

**Endpoint:** `GET /user/posts`

**Authentication:** Required

**Query Parameters:** Same as List Posts

**Success Response (200):** Same format as List Posts

---

## 4. CATEGORIES ENDPOINTS

### 4.1 List Categories

**Endpoint:** `GET /categories`

**Authentication:** Not required

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| parent | integer | null | Filter by parent category |
| with_count | boolean | false | Include post count |

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Tutorials",
            "slug": "tutorials",
            "description": "Step-by-step guides and how-tos",
            "parent_id": null,
            "posts_count": 45,
            "children": [
                {
                    "id": 2,
                    "name": "Laravel",
                    "slug": "laravel",
                    "posts_count": 20
                }
            ]
        }
    ]
}
```

---

### 4.2 Get Category Posts

**Endpoint:** `GET /categories/{slug}/posts`

**Authentication:** Not required

**Success Response (200):** Same format as List Posts

---

### 4.3 Create Category (Admin)

**Endpoint:** `POST /categories`

**Authentication:** Required (admin only)

**Request Body:**
```json
{
    "name": {
        "type": "string",
        "required": true,
        "max": 255
    },
    "slug": {
        "type": "string",
        "required": false
    },
    "description": {
        "type": "string",
        "required": false,
        "max": 1000
    },
    "parent_id": {
        "type": "integer",
        "required": false,
        "exists": "categories.id"
    }
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Category created successfully",
    "data": {
        "id": 1,
        "name": "Tutorials",
        "slug": "tutorials"
    }
}
```

---

### 4.4 Update Category (Admin)

**Endpoint:** `PUT /categories/{id}`

**Authentication:** Required (admin only)

**Success Response (200):**
```json
{
    "success": true,
    "message": "Category updated successfully"
}
```

---

### 4.5 Delete Category (Admin)

**Endpoint:** `DELETE /categories/{id}`

**Authentication:** Required (admin only)

**Success Response (204):** No content

---

## 5. TAGS ENDPOINTS

### 5.1 List Tags

**Endpoint:** `GET /tags`

**Authentication:** Not required

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| search | string | - | Search tags |
| with_count | boolean | false | Include post count |

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Laravel",
            "slug": "laravel",
            "posts_count": 20
        }
    ]
}
```

---

### 5.2 Get Tag Posts

**Endpoint:** `GET /tags/{slug}/posts`

**Authentication:** Not required

**Success Response (200):** Same format as List Posts

---

### 5.3 Create Tag (Admin)

**Endpoint:** `POST /tags`

**Authentication:** Required (admin only)

**Request Body:**
```json
{
    "name": {
        "type": "string",
        "required": true,
        "max": 255
    },
    "slug": {
        "type": "string",
        "required": false
    }
}
```

---

## 6. COMMENTS ENDPOINTS

### 6.1 Get Post Comments

**Endpoint:** `GET /posts/{postId}/comments`

**Authentication:** Not required

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| postId | integer | Post ID |

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 20 | Items per page |
| sort | string | "created_at" | Sort field |
| order | string | "asc" | Sort order |

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "content": "Great article! Very helpful.",
            "created_at": "2026-04-01T12:00:00Z",
            "updated_at": null,
            "is_edited": false,
            "author": {
                "id": 2,
                "name": "Jane Smith",
                "avatar": "https://..."
            },
            "replies": [
                {
                    "id": 2,
                    "content": "Thanks for reading!",
                    "author": {
                        "id": 1,
                        "name": "John Doe",
                        "avatar": "https://..."
                    },
                    "created_at": "2026-04-01T12:30:00Z"
                }
            ],
            "likes_count": 5,
            "is_liked": false
        }
    ],
    "meta": {
        "total": 12,
        "current_page": 1,
        "per_page": 20
    }
}
```

---

### 6.2 Create Comment

**Endpoint:** `POST /posts/{postId}/comments`

**Authentication:** Required

**Request Body:**
```json
{
    "content": {
        "type": "string",
        "required": true,
        "min": 1,
        "max": 2000
    },
    "parent_id": {
        "type": "integer",
        "required": false,
        "exists": "comments.id",
        "description": "For replies"
    }
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Comment added successfully",
    "data": {
        "id": 1,
        "content": "Great article!",
        "created_at": "2026-04-01T12:00:00Z",
        "author": {
            "id": 2,
            "name": "Jane Smith"
        }
    }
}
```

---

### 6.3 Update Comment

**Endpoint:** `PUT /comments/{id}`

**Authentication:** Required (owner only)

**Request Body:**
```json
{
    "content": {
        "type": "string",
        "required": true,
        "min": 1,
        "max": 2000
    }
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Comment updated successfully"
}
```

**Error Responses:**
- 403: Not authorized to edit
- 422: Validation errors

---

### 6.4 Delete Comment

**Endpoint:** `DELETE /comments/{id}`

**Authentication:** Required (owner or admin)

**Success Response (204):** No content

---

### 6.5 Approve Comment (Admin)

**Endpoint:** `POST /comments/{id}/approve`

**Authentication:** Required (admin only)

**Success Response (200):**
```json
{
    "success": true,
    "message": "Comment approved"
}
```

---

### 6.6 Reject Comment (Admin)

**Endpoint:** `POST /comments/{id}/reject`

**Authentication:** Required (admin only)

**Success Response (200):**
```json
{
    "success": true,
    "message": "Comment rejected"
}
```

---

## 7. LIKES ENDPOINTS

### 7.1 Toggle Like

**Endpoint:** `POST /posts/{postId}/like`

**Authentication:** Required

**Behavior:** Toggles like status (like if not liked, unlike if liked)

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "is_liked": true,
        "likes_count": 46
    }
}
```

---

## 8. BOOKMARKS ENDPOINTS

### 8.1 Toggle Bookmark

**Endpoint:** `POST /posts/{postId}/bookmark`

**Authentication:** Required

**Behavior:** Toggles bookmark status

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "is_bookmarked": true
    }
}
```

---

### 8.2 Get User Bookmarks

**Endpoint:** `GET /user/bookmarks`

**Authentication:** Required

**Success Response (200):** Same format as List Posts

---

### 8.3 Remove Bookmark

**Endpoint:** `DELETE /user/bookmarks/{postId}`

**Authentication:** Required

**Success Response (204):** No content

---

## 9. USER ENDPOINTS

### 9.1 Get User Profile

**Endpoint:** `GET /users/{id}`

**Authentication:** Not required

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "avatar": "https://...",
        "bio": "Senior Developer",
        "joined_at": "2026-01-15T08:30:00Z",
        "posts_count": 12,
        "comments_count": 45,
        "followers_count": 100,
        "following_count": 50
    }
}
```

---

### 9.2 Get Current User Profile

**Endpoint:** `GET /user/profile`

**Authentication:** Required

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "avatar": "https://...",
        "bio": "Senior Developer",
        "email_verified": true,
        "created_at": "2026-01-15T08:30:00Z"
    }
}
```

---

### 9.3 Update Profile

**Endpoint:** `PUT /user/profile`

**Authentication:** Required

**Request Body:**
```json
{
    "name": {
        "type": "string",
        "required": false,
        "min": 2,
        "max": 255
    },
    "bio": {
        "type": "string",
        "required": false,
        "max": 500
    },
    "avatar": {
        "type": "string",
        "required": false,
        "format": "url"
    }
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Profile updated successfully"
}
```

---

### 9.4 Update Password

**Endpoint:** `PUT /user/password`

**Authentication:** Required

**Request Body:**
```json
{
    "current_password": {
        "type": "string",
        "required": true
    },
    "password": {
        "type": "string",
        "required": true,
        "min": 8
    },
    "password_confirmation": {
        "type": "string",
        "required": true
    }
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Password updated successfully"
}
```

---

## 10. SEARCH ENDPOINTS

### 10.1 Search Posts

**Endpoint:** `GET /search`

**Authentication:** Not required

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| q | string | - | Search query (required) |
| category | string | - | Filter by category |
| tag | string | - | Filter by tag |
| author | integer | - | Filter by author |
| date_from | date | - | Filter from date |
| date_to | date | - | Filter to date |
| sort | string | "relevance" | Sort by relevance/date/views |
| page | integer | 1 | Page number |
| per_page | integer | 15 | Items per page |

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Getting Started with Laravel",
            "slug": "getting-started-with-laravel",
            "excerpt": "...",
            "highlighted": "<mark>Laravel</mark> is a framework...",
            "author": { ... },
            "category": { ... },
            "published_at": "2026-04-01T10:00:00Z",
            "relevance_score": 0.95
        }
    ],
    "meta": {
        "query": "laravel",
        "total": 25,
        "took_ms": 45
    }
}
```

---

### 10.2 Search Suggestions

**Endpoint:** `GET /search/suggest`

**Authentication:** Not required

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| q | string | Search query (min 2 chars) |
| limit | integer | Max suggestions (default: 5) |

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "queries": ["laravel tutorial", "laravel api", "laravel authentication"],
        "posts": [
            {
                "id": 1,
                "title": "Getting Started with Laravel",
                "slug": "getting-started-with-laravel"
            }
        ],
        "categories": [
            {
                "id": 1,
                "name": "Laravel",
                "slug": "laravel"
            }
        ]
    }
}
```

---

## 11. ADMIN ENDPOINTS

### 11.1 Dashboard Statistics

**Endpoint:** `GET /admin/dashboard`

**Authentication:** Required (admin only)

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "users": {
            "total": 1500,
            "new_today": 12,
            "new_this_week": 85,
            "new_this_month": 320
        },
        "posts": {
            "total": 450,
            "published": 420,
            "draft": 25,
            "pending_review": 5
        },
        "comments": {
            "total": 2500,
            "pending": 15,
            "approved": 2480,
            "rejected": 5
        },
        "views": {
            "today": 5000,
            "this_week": 35000,
            "this_month": 150000
        },
        "popular_posts": [
            {
                "id": 1,
                "title": "Getting Started with Laravel",
                "views": 5000
            }
        ],
        "recent_activity": [
            {
                "type": "user_registered",
                "user": { "id": 100, "name": "New User" },
                "timestamp": "2026-04-01T12:00:00Z"
            }
        ]
    }
}
```

---

### 11.2 List Users (Admin)

**Endpoint:** `GET /admin/users`

**Authentication:** Required (admin only)

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| search | string | Search by name/email |
| role | string | Filter by role |
| status | string | Filter by status (active/banned) |
| sort | string | Sort field |
| page | integer | Page number |

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "user",
            "status": "active",
            "email_verified": true,
            "posts_count": 12,
            "created_at": "2026-01-15T08:30:00Z"
        }
    ],
    "meta": { ... }
}
```

---

### 11.3 Update User (Admin)

**Endpoint:** `PUT /admin/users/{id}`

**Authentication:** Required (admin only)

**Request Body:**
```json
{
    "name": {
        "type": "string",
        "required": false
    },
    "email": {
        "type": "string",
        "required": false
    },
    "role": {
        "type": "string",
        "required": false,
        "enum": ["user", "admin"]
    },
    "status": {
        "type": "string",
        "required": false,
        "enum": ["active", "banned"]
    },
    "bio": {
        "type": "string",
        "required": false
    }
}
```

---

### 11.4 Delete User (Admin)

**Endpoint:** `DELETE /admin/users/{id}`

**Authentication:** Required (admin only)

**Success Response (204):** No content

---

### 11.5 List All Posts (Admin)

**Endpoint:** `GET /admin/posts`

**Authentication:** Required (admin only)

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| status | string | Filter by status |
| author | integer | Filter by author |
| search | string | Search title |

---

### 11.6 Get Pending Comments (Admin)

**Endpoint:** `GET /admin/comments/pending`

**Authentication:** Required (admin only)

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "content": "Comment content...",
            "post": {
                "id": 1,
                "title": "Post Title"
            },
            "author": {
                "id": 2,
                "name": "User Name"
            },
            "created_at": "2026-04-01T12:00:00Z"
        }
    ]
}
```

---

### 11.7 Analytics (Admin)

**Endpoint:** `GET /admin/analytics`

**Authentication:** Required (admin only)

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| period | string | today/week/month/year |
| metric | string | views/users/posts/comments |

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "views": {
            "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            "data": [1000, 1200, 1100, 1300, 1500, 1800, 1600]
        },
        "users": {
            "labels": [...],
            "data": [...]
        },
        "top_posts": [...],
        "top_categories": [...]
    }
}
```

---

## 12. NOTIFICATIONS ENDPOINTS

### 12.1 Get Notifications

**Endpoint:** `GET /user/notifications`

**Authentication:** Required

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| unread_only | boolean | Filter unread only |
| page | integer | Page number |

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-123",
            "type": "comment.created",
            "title": "New comment on your post",
            "message": "Jane commented on \"Getting Started...\"",
            "data": {
                "post_id": 1,
                "post_title": "Getting Started...",
                "comment_id": 5
            },
            "read": false,
            "created_at": "2026-04-01T12:00:00Z"
        }
    ],
    "meta": {
        "unread_count": 5
    }
}
```

---

### 12.2 Mark Notification as Read

**Endpoint:** `POST /user/notifications/{id}/read`

**Authentication:** Required

**Success Response (200):**
```json
{
    "success": true,
    "message": "Notification marked as read"
}
```

---

### 12.3 Mark All as Read

**Endpoint:** `POST /user/notifications/read-all`

**Authentication:** Required

**Success Response (200):**
```json
{
    "success": true,
    "message": "All notifications marked as read"
}
```

---

## 13. RATE LIMITING

### 13.1 Rate Limit Tiers

| Endpoint | Limit | Window |
|----------|-------|--------|
| /auth/login | 5 requests | 1 minute |
| /auth/register | 3 requests | 1 minute |
| /auth/forgot-password | 3 requests | 1 minute |
| /posts (GET) | 60 requests | 1 minute |
| /posts (POST/PUT/DELETE) | 30 requests | 1 minute |
| /comments (POST) | 10 requests | 1 minute |
| /search | 30 requests | 1 minute |
| All other endpoints | 60 requests | 1 minute |

### 13.2 Rate Limit Headers

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1680350400
```

### 13.3 Rate Limit Exceeded Response (429)

```json
{
    "success": false,
    "message": "Too many requests. Please try again in 45 seconds.",
    "retry_after": 45
}
```

---

## 14. ERROR CODES

| Code | Description |
|------|-------------|
| VALIDATION_ERROR | Request validation failed |
| UNAUTHORIZED | Authentication required |
| FORBIDDEN | Insufficient permissions |
| NOT_FOUND | Resource not found |
| CONFLICT | Resource conflict |
| SERVER_ERROR | Internal server error |
| RATE_LIMITED | Too many requests |
| TOKEN_EXPIRED | Authentication token expired |
| TOKEN_INVALID | Authentication token invalid |
| EMAIL_NOT_VERIFIED | Email verification required |

---

*End of API Contract Specification*
