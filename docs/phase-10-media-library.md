# PHASE 10 — MEDIA LIBRARY & DIGITAL ASSET MANAGEMENT SYSTEM

## 1. Media System Architecture

### Core Layers
```
Upload Layer → Storage Layer → Processing Layer → Optimization Layer → Metadata Layer → Retrieval Layer → AI Enhancement Layer
```

### Principles
- Decoupled storage (swap local ↔ S3/MinIO without code changes)
- Metadata-driven (search by type, tag, date, uploader)
- Queue-based processing (optimization via jobs)
- CDN-ready (URL structure supports CDN prefix)

---

## 2. Media Database Design

```sql
CREATE TABLE media_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    folder_id BIGINT UNSIGNED NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_url VARCHAR(500) NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_extension VARCHAR(10) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    duration INT UNSIGNED NULL,
    alt_text VARCHAR(255) NULL,
    caption TEXT NULL,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    optimization_status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    ai_tags JSON NULL,
    hash_signature VARCHAR(64) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX media_files_user_id_index (user_id),
    INDEX media_files_mime_type_index (mime_type),
    INDEX media_files_folder_id_index (folder_id),
    INDEX media_files_hash_signature_index (hash_signature),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (folder_id) REFERENCES media_folders(id) ON DELETE SET NULL
);
```

---

## 3. Media Folders System

```sql
CREATE TABLE media_folders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (parent_id) REFERENCES media_folders(id) ON DELETE CASCADE
);
```

### Folder Structure
```
/Posts/
├── 2024/
│   ├── Post Title 1/
│   └── Post Title 2/
/Categories/
├── Technology/
└── Business/
/Authors/
/Pages/
/General/
```

---

## 4. File Upload System

### Supported Upload Types
- Drag & drop (multiple files)
- File browser (single + multi-select)
- Clipboard paste (screenshots)
- URL fetch (import from URL)

### Chunked Upload
- Enabled for files > 50MB
- 5MB chunks
- Resume on failure
- Progress tracking via Alpine.js

### Validation
| Rule | Images | Videos | Audio | Documents |
|------|--------|--------|-------|-----------|
| Max size | 10MB | 200MB | 50MB | 20MB |
| Allowed types | jpg,png,webp,gif,svg | mp4,webm | mp3,wav,ogg | pdf,docx,xlsx,csv,txt |

---

## 5. Storage Layer

```
storage/app/public/
├── images/{yyyy}/{mm}/{filename}
├── videos/{yyyy}/{mm}/{filename}
├── audio/{yyyy}/{mm}/{filename}
├── documents/{yyyy}/{mm}/{filename}
└── optimized/
    ├── thumbs/ (150x150)
    ├── small/ (300x300)
    ├── medium/ (768x768)
    └── webp/ (WebP conversions)
```

Filesystem driver: `local` (swap to `s3` via env for production).

---

## 6. Image Optimization Pipeline

### Queue Job: `OptimizeMedia`
1. Validate file (mime, size, integrity)
2. Generate WebP version
3. Generate thumbnails: 150×150, 300×300, 768×768, 1200×628
4. Extract EXIF metadata (strip for privacy)
5. Generate hash signature (SHA-256 of content)
6. Update optimization_status to 'completed'

### Responsive Images
```blade
<picture>
    <source srcset="{{ $image->webp_url }}" type="image/webp">
    <img src="{{ $image->medium_url }}"
         srcset="{{ $image->small_url }} 300w,
                 {{ $image->medium_url }} 768w,
                 {{ $image->original_url }} 1200w"
         sizes="(max-width: 768px) 100vw, 768px"
         alt="{{ $image->alt_text }}" loading="lazy">
</picture>
```

---

## 7. Duplicate Detection

- SHA-256 hash stored in `hash_signature`
- On upload: check if hash exists
- If duplicate: show existing file info, allow re-attach instead of re-upload

---

## 8. Media Search & Filtering

| Filter | Type | Options |
|--------|------|---------|
| Search | Text | File name, alt text, caption |
| Type | Select | Images, Videos, Audio, Documents |
| MIME | Multi-select | image/png, image/jpeg, ... |
| Folder | Tree | All folders |
| Date | Range | Upload date picker |
| Uploader | Select | User list |

---

## 9. AI Media Enhancement

### Features (NVIDIA API)
- **Auto alt-text generation** — Analyze image content, generate descriptive alt text
- **Auto tagging** — Classify image content (nature, technology, people, etc.)
- **Caption generation** — Generate natural language caption
- **Duplicate detection** — AI-powered near-duplicate detection

### Workflow
```
Image uploaded → Queue job → NVIDIA API call → 
Alt text + tags + caption generated → Stored in metadata →
Editor can accept/edit/reject
```

---

## 10. Media Permissions

| Permission | Super Admin | Admin | Editor | Author |
|------------|:---------:|:----:|:-----:|:-----:|
| upload_media | ✅ | ✅ | ✅ | ✅ |
| edit_media | ✅ | ✅ | ✅ | ✅ (own) |
| delete_media | ✅ | ✅ | ✅ | ✅ (own) |
| manage_media | ✅ | ✅ | ✅ | ❌ |
| view_media | ✅ | ✅ | ✅ | ✅ |

---

## 11. Media Activity Logging

Events: uploaded, updated, deleted, restored, optimized
Using Spatie Activitylog with subject → MediaFile model.

---

## 12. Media Performance Optimization

- **Lazy loading:** All images have `loading="lazy"`
- **Responsive images:** `srcset` + `sizes` attributes
- **WebP:** Browser detection → serve WebP, fallback to original
- **CDN:** URL prefix configurable in .env
- **Caching:** Media listing cached in Redis (TTL: 3600s)

---

## 13. Final Output

**Phase 10 complete.** Enterprise Media Library:
- Complete DAM architecture (7 layers)
- Media files + folders database schemas
- Chunked upload with drag-drop and clipboard
- Image optimization pipeline (WebP, 4 thumbnail sizes)
- SHA-256 duplicate detection
- Full search and filtering (8 filter dimensions)
- AI-powered alt text, tagging, caption generation
- Role-based permissions per media operation
- Responsive image generation with srcset
- CDN-ready architecture
- Activity logging for all media events

Ready to proceed to **Phase 11**.
