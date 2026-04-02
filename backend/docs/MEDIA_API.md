# Media Management API Documentation

## Phase 10: Media Management System

Complete media upload, management, and optimization system for the blog platform.

---

## Overview

The Media Management System provides comprehensive file upload, storage, optimization, and retrieval capabilities. It supports images, documents, and other media types with automatic thumbnail generation, image optimization, and secure file handling.

### Features

- **File Upload**: Single and bulk file upload with validation
- **Image Optimization**: Automatic compression, EXIF stripping, and orientation correction
- **Thumbnail Generation**: Multiple sizes (thumbnail, small, medium, large)
- **File Validation**: Type, size, and dimension validation
- **Organized Storage**: Date-based folder structure
- **Soft Delete**: Preserves files temporarily with scheduled cleanup
- **Search & Filter**: Full-text search and filtering capabilities
- **CDN Ready**: Configurable storage disks (local, S3)

---

## Endpoints

### Authentication Required

All media endpoints require authentication via Sanctum token.

```
Authorization: Bearer {token}
```

---

## Upload Endpoints

### POST /api/v1/media/upload

Upload a single media file.

**Request:**
```http
POST /api/v1/media/upload
Content-Type: multipart/form-data
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| file | File | Yes | The file to upload |
| alt_text | string | No | Alt text for accessibility (max 255 chars) |
| title | string | No | Title for the media (max 255 chars) |
| caption | string | No | Caption text (max 500 chars) |
| description | string | No | Description (max 1000 chars) |
| collection_name | string | No | Collection name for grouping (default: "default") |
| is_public | boolean | No | Whether media is publicly accessible (default: true) |

**Validation Rules:**
- **Images**: jpg, png, gif, webp, svg (max 5MB, max 4000x4000px)
- **Documents**: pdf, doc, docx, xls, xlsx, txt, csv (max 10MB)

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "filename": "20260102120000_abc123def456.jpg",
    "original_filename": "my-photo.jpg",
    "path": "media/default/2026/01/02/20260102120000_abc123def456.jpg",
    "url": "http://localhost/storage/media/default/2026/01/02/20260102120000_abc123def456.jpg",
    "thumbnail_url": "http://localhost/storage/media/default/2026/01/02/thumbnails/20260102120000_abc123def456_thumbnail.jpg",
    "mime_type": "image/jpeg",
    "size": 102400,
    "file_size_formatted": "100 KB",
    "alt_text": "A beautiful sunset",
    "title": "Sunset Photo",
    "caption": "Taken at the beach",
    "dimensions": {
      "width": 1920,
      "height": 1080
    },
    "collection_name": "default",
    "is_public": true,
    "is_image": true,
    "metadata": {
      "optimization": {
        "original_size": 150000,
        "optimized_size": 102400,
        "savings_percentage": 31.73
      },
      "thumbnails": {
        "thumbnail": {
          "path": "media/default/2026/01/02/thumbnails/20260102120000_abc123def456_thumbnail.jpg",
          "url": "http://...",
          "width": 150,
          "height": 150,
          "file_size": 5000
        },
        "small": { ... },
        "medium": { ... },
        "large": { ... }
      }
    },
    "uploader": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "created_at": "2026-01-02T12:00:00.000000Z",
    "updated_at": "2026-01-02T12:00:00.000000Z"
  },
  "message": "File uploaded successfully"
}
```

**Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "file": ["The file size must not exceed 5 MB."]
  }
}
```

---

### POST /api/v1/media/upload-multiple

Upload multiple files in a single request (max 10 files).

**Request:**
```http
POST /api/v1/media/upload-multiple
Content-Type: multipart/form-data
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| files | File[] | Yes | Array of files to upload (max 10) |
| alt_text | string | No | Alt text for all files |
| title | string | No | Title for all files |
| collection_name | string | No | Collection name |
| is_public | boolean | No | Public visibility |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "successful": [
      { /* MediaResource */ },
      { /* MediaResource */ }
    ],
    "failed": [
      {
        "index": 2,
        "filename": "invalid.exe",
        "error": "The uploaded file type is not allowed."
      }
    ]
  },
  "meta": {
    "successful_count": 2,
    "failed_count": 1,
    "total_count": 3
  },
  "message": "Uploaded 2 of 3 files successfully"
}
```

---

## List & Search Endpoints

### GET /api/v1/media

List all media files with filtering and pagination.

**Request:**
```http
GET /api/v1/media?page=1&per_page=15&type=image&collection_name=posts&search=sunset
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| page | integer | Page number (default: 1) |
| per_page | integer | Items per page (default: 15, max: 100) |
| type | string | Filter by type: image, document, video |
| collection_name | string | Filter by collection name |
| uploader_id | integer | Filter by uploader user ID |
| search | string | Search in filename, alt_text, title |
| from_date | date | Filter from date (Y-m-d) |
| to_date | date | Filter to date (Y-m-d) |
| is_public | boolean | Filter by visibility |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    { /* MediaResource */ },
    { /* MediaResource */ }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "from": 1,
    "to": 15
  }
}
```

---

### GET /api/v1/media/search

Search media files by filename, alt text, and metadata.

**Request:**
```http
GET /api/v1/media/search?q=sunset&per_page=15
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| q | string | Search query (required) |
| per_page | integer | Items per page (default: 15) |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [ /* MediaResource collection */ ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 25,
    "query": "sunset"
  }
}
```

---

## Single Media Endpoints

### GET /api/v1/media/{id}

Get details of a specific media file.

**Request:**
```http
GET /api/v1/media/1
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": { /* Full MediaResource */ }
}
```

**Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Media not found"
}
```

---

### GET /api/v1/media/{id}/url

Get the URL and thumbnail URLs for a media file.

**Request:**
```http
GET /api/v1/media/1/url
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "url": "http://localhost/storage/media/...",
    "thumbnails": {
      "thumbnail": "http://localhost/storage/media/.../thumbnails/..._thumbnail.jpg",
      "small": "http://localhost/storage/media/.../thumbnails/..._small.jpg",
      "medium": "http://localhost/storage/media/.../thumbnails/..._medium.jpg",
      "large": "http://localhost/storage/media/.../thumbnails/..._large.jpg"
    }
  }
}
```

---

### GET /api/v1/media/{id}/usage

Get usage information for a media file (where it's being used).

**Request:**
```http
GET /api/v1/media/1/usage
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "in_use": false,
    "usages": []
  }
}
```

---

### PUT /api/v1/media/{id}

Update metadata for a media file.

**Request:**
```http
PUT /api/v1/media/1
Content-Type: application/json
```

**Parameters:**

| Field | Type | Description |
|-------|------|-------------|
| alt_text | string | Alt text (max 255 chars) |
| title | string | Title (max 255 chars) |
| caption | string | Caption (max 500 chars) |
| description | string | Description (max 1000 chars) |
| collection_name | string | Collection name |
| is_public | boolean | Public visibility |

**Note:** File replacement is not supported. Delete and re-upload instead.

**Response (200 OK):**
```json
{
  "success": true,
  "data": { /* Updated MediaResource */ },
  "message": "Media metadata updated successfully"
}
```

---

### DELETE /api/v1/media/{id}

Soft delete a media file.

**Request:**
```http
DELETE /api/v1/media/1
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Media deleted successfully"
}
```

**Note:** Files are soft-deleted and cleaned up by the weekly orphan cleanup job.

---

### POST /api/v1/media/{id}/restore

Restore a soft-deleted media file.

**Request:**
```http
POST /api/v1/media/1/restore
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": { /* Restored MediaResource */ },
  "message": "Media restored successfully"
}
```

---

### POST /api/v1/media/{id}/regenerate-thumbnails

Regenerate thumbnails for an image.

**Request:**
```http
POST /api/v1/media/1/regenerate-thumbnails
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "thumbnails": {
      "thumbnail": { /* thumbnail info */ },
      "small": { /* thumbnail info */ },
      "medium": { /* thumbnail info */ },
      "large": { /* thumbnail info */ }
    }
  },
  "message": "Thumbnails regenerated successfully"
}
```

---

## Statistics Endpoints

### GET /api/v1/media/statistics

Get media library statistics.

**Request:**
```http
GET /api/v1/media/statistics
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "total_count": 150,
    "total_size": 157286400,
    "total_size_formatted": "150 MB",
    "by_type": {
      "images": 100,
      "documents": 45,
      "videos": 5
    },
    "by_collection": {
      "default": 80,
      "posts": 50,
      "featured": 20
    },
    "images_count": 100,
    "documents_count": 45,
    "videos_count": 5,
    "storage": {
      "total": 157286400,
      "total_formatted": "150 MB",
      "count": 150
    }
  }
}
```

---

### GET /api/v1/media/storage-usage

Get current user's storage usage.

**Request:**
```http
GET /api/v1/media/storage-usage
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "used": 52428800,
    "used_formatted": "50 MB",
    "limit": 104857600,
    "limit_formatted": "100 MB",
    "percentage": 50.0,
    "remaining": 52428800,
    "remaining_formatted": "50 MB"
  }
}
```

---

## Thumbnail Sizes

| Size | Dimensions | Use Case |
|------|------------|----------|
| thumbnail | 150x150 | Grid views, avatars |
| small | 300x300 | List views, previews |
| medium | 600x600 | Content embeds |
| large | 1200x1200 | Lightbox, full view |

---

## File Size Limits

| Type | Max Size |
|------|----------|
| Images | 5 MB |
| Documents | 10 MB |

---

## Image Dimension Limits

| Property | Limit |
|----------|-------|
| Maximum Width | 4000 px |
| Maximum Height | 4000 px |

---

## Allowed File Types

### Images
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)
- SVG (.svg)

### Documents
- PDF (.pdf)
- Word (.doc, .docx)
- Excel (.xls, .xlsx)
- Text (.txt)
- CSV (.csv)

---

## Authorization

| Role | Upload | View | Update | Delete |
|------|--------|------|--------|--------|
| super-admin | ✓ | ✓ | ✓ | ✓ |
| admin | ✓ | ✓ | ✓ | ✓ |
| editor | ✓ | ✓ | ✓ | ✓ |
| author | ✓ (own) | ✓ | ✓ (own) | ✓ (own) |
| subscriber | ✗ | ✓ | ✗ | ✗ |

---

## Console Commands

### Orphan Cleanup

Clean up soft-deleted media files older than specified hours.

```bash
# Run cleanup (default: 168 hours = 1 week)
php artisan media:cleanup-orphans

# Custom age threshold
php artisan media:cleanup-orphans --hours=72

# Dry run (show what would be deleted)
php artisan media:cleanup-orphans --dry-run

# Skip confirmation prompt
php artisan media:cleanup-orphans --force
```

### Schedule Configuration

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Run orphan cleanup weekly
    $schedule->command('media:cleanup-orphans --hours=168 --force')
        ->weekly()
        ->sundays()
        ->at('03:00')
        ->withoutOverlapping();
}
```

---

## Configuration

### File Size Limits

In `config/blog.php`:

```php
'max_image_size' => 5242880, // 5MB
'max_document_size' => 10485760, // 10MB
'max_user_storage_mb' => 100,
```

### Thumbnail Sizes

```php
'thumbnail_sizes' => [
    'thumbnail' => ['width' => 150, 'height' => 150],
    'small' => ['width' => 300, 'height' => 300],
    'medium' => ['width' => 600, 'height' => 600],
    'large' => ['width' => 1200, 'height' => 1200],
],
```

### Image Optimization

```php
'image_quality' => 85,
'strip_metadata' => true,
'auto_orient' => true,
'convert_to_webp' => false,
```

### Storage

In `config/filesystems.php`:

```php
'default' => env('FILESYSTEM_DISK', 'public'),

'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
    
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
    ],
],
```

---

## Error Handling

### Common Error Responses

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**403 Forbidden:**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "Media not found"
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["Error message"]
  }
}
```

**500 Server Error:**
```json
{
  "success": false,
  "message": "Failed to upload file: Error details"
}
```

---

## Testing

Run the test suite:

```bash
php artisan test --filter=Media
```

Test coverage includes:
- Upload validation (file types, sizes, dimensions)
- CRUD operations
- Thumbnail generation
- Image optimization
- Orphan cleanup
- Authorization checks

---

## Best Practices

1. **Always provide alt_text** for accessibility
2. **Use appropriate collection_name** to organize media
3. **Use thumbnails** for list/grid views to improve performance
4. **Clean up unused media** regularly using the orphan cleanup command
5. **Validate files client-side** before upload for better UX
6. **Use CDN** for production deployments
7. **Monitor storage usage** with the storage-usage endpoint

---

## Example Usage

### JavaScript (Fetch API)

```javascript
// Upload a file
async function uploadMedia(file, metadata = {}) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('alt_text', metadata.altText || '');
  formData.append('title', metadata.title || '');
  
  const response = await fetch('/api/v1/media/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  return await response.json();
}

// Get media library
async function getMediaLibrary(filters = {}) {
  const params = new URLSearchParams(filters);
  const response = await fetch(`/api/v1/media?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  return await response.json();
}
```

---

## Changelog

### Phase 10 (Current)
- Complete media upload system
- Image optimization with Intervention Image
- Automatic thumbnail generation
- File validation (type, size, dimensions)
- Organized folder structure
- Soft delete with orphan cleanup
- Search and filter capabilities
- Comprehensive API documentation
