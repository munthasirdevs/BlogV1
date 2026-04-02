<?php

namespace App\Repositories;

use App\Models\Media;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MediaRepository
 *
 * Repository for Media model operations.
 * Handles all database queries related to media management.
 *
 * @extends BaseRepository<Media>
 */
class MediaRepository extends BaseRepository
{
    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return Media::class;
    }

    /**
     * Get paginated media with filters.
     *
     * @param array $filters Filter options
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply filters
        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Apply filters to query.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Type filter (image, document, video)
        if (isset($filters['type'])) {
            $this->applyTypeFilter($query, $filters['type']);
        }

        // Uploader filter
        if (isset($filters['uploader_id'])) {
            $query->where('uploader_id', $filters['uploader_id']);
        }

        // Collection filter
        if (isset($filters['collection_name'])) {
            $query->where('collection_name', $filters['collection_name']);
        }

        // Search filter
        if (isset($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        // Public/Private filter
        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        // Only non-deleted
        $query->whereNull('deleted_at');
    }

    /**
     * Apply type filter.
     *
     * @param Builder $query
     * @param string $type
     * @return void
     */
    protected function applyTypeFilter(Builder $query, string $type): void
    {
        match ($type) {
            'image' => $query->where('mime_type', 'LIKE', 'image/%'),
            'document' => $query->whereIn('mime_type', [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'text/csv',
            ]),
            'video' => $query->where('mime_type', 'LIKE', 'video/%'),
            default => null,
        };
    }

    /**
     * Apply search filter.
     *
     * @param Builder $query
     * @param string $search
     * @return void
     */
    protected function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('filename', 'LIKE', "%{$search}%")
                ->orWhere('original_filename', 'LIKE', "%{$search}%")
                ->orWhere('alt_text', 'LIKE', "%{$search}%")
                ->orWhere('title', 'LIKE', "%{$search}%")
                ->orWhere('caption', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Search media by filename, alt text, and description.
     *
     * @param string $query Search query
     * @param array $columns Columns to select
     * @return Collection
     */
    public function search(string $query, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('filename', 'LIKE', "%{$query}%")
                    ->orWhere('original_filename', 'LIKE', "%{$query}%")
                    ->orWhere('alt_text', 'LIKE', "%{$query}%")
                    ->orWhere('title', 'LIKE', "%{$query}%")
                    ->orWhere('caption', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderByRaw("
                CASE 
                    WHEN filename LIKE ? THEN 1
                    WHEN filename LIKE ? THEN 2
                    WHEN original_filename LIKE ? THEN 3
                    WHEN alt_text LIKE ? THEN 4
                    ELSE 5
                END
            ", ["{$query}%", "%{$query}%", "{$query}%", "%{$query}%"])
            ->get($columns);
    }

    /**
     * Search media with pagination.
     *
     * @param string $query Search query
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function searchPaginated(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('filename', 'LIKE', "%{$query}%")
                    ->orWhere('original_filename', 'LIKE', "%{$query}%")
                    ->orWhere('alt_text', 'LIKE', "%{$query}%")
                    ->orWhere('title', 'LIKE', "%{$query}%")
                    ->orWhere('caption', 'LIKE', "%{$query}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find media by collection name.
     *
     * @param string $collectionName
     * @param array $columns
     * @return Collection
     */
    public function findByCollection(string $collectionName, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->where('collection_name', $collectionName)
            ->get($columns);
    }

    /**
     * Find media by uploader.
     *
     * @param int $uploaderId
     * @param array $columns
     * @return Collection
     */
    public function findByUploader(int $uploaderId, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->where('uploader_id', $uploaderId)
            ->latest()
            ->get($columns);
    }

    /**
     * Find media by hash (for deduplication).
     *
     * @param string $hash
     * @param array $columns
     * @return Media|null
     */
    public function findByHash(string $hash, array $columns = ['*']): ?Media
    {
        return $this->model->newQuery()
            ->where('file_hash', $hash)
            ->first($columns);
    }

    /**
     * Get images only.
     *
     * @param array $columns
     * @return Collection
     */
    public function findImages(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->where('mime_type', 'LIKE', 'image/%')
            ->get($columns);
    }

    /**
     * Get images with pagination.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getImagesPaginated(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('mime_type', 'LIKE', 'image/%')
            ->latest()
            ->paginate($perPage, $columns);
    }

    /**
     * Get documents only.
     *
     * @param array $columns
     * @return Collection
     */
    public function findDocuments(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->whereIn('mime_type', [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->get($columns);
    }

    /**
     * Get documents with pagination.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getDocumentsPaginated(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->whereIn('mime_type', [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->latest()
            ->paginate($perPage, $columns);
    }

    /**
     * Get videos only.
     *
     * @param array $columns
     * @return Collection
     */
    public function findVideos(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->where('mime_type', 'LIKE', 'video/%')
            ->get($columns);
    }

    /**
     * Get recent uploads.
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function findRecent(int $limit = 10, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->latest()
            ->limit($limit)
            ->get($columns);
    }

    /**
     * Get orphaned media (not attached to any model and soft deleted).
     *
     * @param array $columns
     * @return Collection
     */
    public function findOrphaned(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->whereNull('model_type')
            ->whereNull('model_id')
            ->whereNotNull('deleted_at')
            ->get($columns);
    }

    /**
     * Get soft deleted media.
     *
     * @param array $columns
     * @return Collection
     */
    public function findTrashed(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->onlyTrashed()
            ->get($columns);
    }

    /**
     * Get media storage usage by user.
     *
     * @param int $userId
     * @return int Total bytes
     */
    public function getUserStorageUsage(int $userId): int
    {
        return $this->model->newQuery()
            ->where('uploader_id', $userId)
            ->sum('size');
    }

    /**
     * Get total storage usage.
     *
     * @return int Total bytes
     */
    public function getTotalStorageUsage(): int
    {
        return $this->model->newQuery()->sum('size');
    }

    /**
     * Get storage usage by type.
     *
     * @return array
     */
    public function getStorageUsageByType(): array
    {
        return $this->model->newQuery()
            ->selectRaw("
                CASE 
                    WHEN mime_type LIKE 'image/%' THEN 'images'
                    WHEN mime_type LIKE 'video/%' THEN 'videos'
                    ELSE 'documents'
                END as type,
                SUM(size) as total_size,
                COUNT(*) as count
            ")
            ->groupByRaw("
                CASE 
                    WHEN mime_type LIKE 'image/%' THEN 'images'
                    WHEN mime_type LIKE 'video/%' THEN 'videos'
                    ELSE 'documents'
                END
            ")
            ->get()
            ->pluck('total_size', 'type')
            ->toArray();
    }

    /**
     * Get media count by collection.
     *
     * @return array
     */
    public function getCountByCollection(): array
    {
        return $this->model->newQuery()
            ->selectRaw('collection_name, COUNT(*) as count')
            ->groupBy('collection_name')
            ->get()
            ->pluck('count', 'collection_name')
            ->toArray();
    }

    /**
     * Get media count by type.
     *
     * @return array
     */
    public function getCountByType(): array
    {
        return $this->model->newQuery()
            ->selectRaw("
                CASE 
                    WHEN mime_type LIKE 'image/%' THEN 'images'
                    WHEN mime_type LIKE 'video/%' THEN 'videos'
                    ELSE 'documents'
                END as type,
                COUNT(*) as count
            ")
            ->groupByRaw("
                CASE 
                    WHEN mime_type LIKE 'image/%' THEN 'images'
                    WHEN mime_type LIKE 'video/%' THEN 'videos'
                    ELSE 'documents'
                END
            ")
            ->get()
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get media statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->model->newQuery()->count();
        $totalSize = $this->getTotalStorageUsage();
        $byType = $this->getCountByType();
        $byCollection = $this->getCountByCollection();

        return [
            'total_count' => $total,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatFileSize($totalSize),
            'by_type' => $byType,
            'by_collection' => $byCollection,
            'images_count' => $byType['images'] ?? 0,
            'documents_count' => $byType['documents'] ?? 0,
            'videos_count' => $byType['videos'] ?? 0,
        ];
    }

    /**
     * Find media where used (attached to models).
     *
     * @param int $mediaId
     * @return array Array of model usage
     */
    public function findUsage(int $mediaId): array
    {
        $media = $this->model->newQuery()->find($mediaId);
        if (!$media) {
            return [];
        }

        // Check if media has model attachment
        if ($media->model_type && $media->model_id) {
            return [
                [
                    'model_type' => $media->model_type,
                    'model_id' => $media->model_id,
                ],
            ];
        }

        return [];
    }

    /**
     * Check if media is in use.
     *
     * @param int $mediaId
     * @return bool
     */
    public function isInUse(int $mediaId): bool
    {
        $media = $this->model->newQuery()->find($mediaId);
        if (!$media) {
            return false;
        }

        return $media->model_type !== null && $media->model_id !== null;
    }

    /**
     * Delete old orphaned media.
     *
     * @param int $olderThanHours Delete media older than this many hours
     * @return int Number of deleted records
     */
    public function deleteOldOrphaned(int $olderThanHours = 168): int
    {
        return $this->model->newQuery()
            ->onlyTrashed()
            ->whereNull('model_type')
            ->whereNull('model_id')
            ->where('deleted_at', '<', now()->subHours($olderThanHours))
            ->delete();
    }

    /**
     * Permanently delete media and return file path.
     *
     * @param int $mediaId
     * @return array|null File information or null
     */
    public function getFilePathBeforeDelete(int $mediaId): ?array
    {
        $media = $this->model->newQuery()
            ->withTrashed()
            ->find($mediaId);

        if (!$media) {
            return null;
        }

        return [
            'path' => $media->path,
            'disk' => $media->disk,
        ];
    }

    /**
     * Restore a soft-deleted media.
     *
     * @param int $mediaId
     * @return bool
     */
    public function restore(int $mediaId): bool
    {
        $media = $this->model->newQuery()
            ->withTrashed()
            ->find($mediaId);

        if (!$media) {
            return false;
        }

        return $media->restore();
    }

    /**
     * Update sort order for multiple media items.
     *
     * @param array $items Array of ['id' => mediaId, 'sort_order' => order]
     * @return bool
     */
    public function reorder(array $items): bool
    {
        foreach ($items as $item) {
            $this->update($item['id'], ['sort_order' => $item['sort_order']]);
        }
        return true;
    }

    /**
     * Format file size.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get media by date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $columns
     * @return Collection
     */
    public function findByDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get($columns);
    }

    /**
     * Get largest media files.
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function getLargest(int $limit = 10, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->orderByDesc('size')
            ->limit($limit)
            ->get($columns);
    }

    /**
     * Get media without thumbnails.
     *
     * @param array $columns
     * @return Collection
     */
    public function findWithoutThumbnails(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()
            ->where('mime_type', 'LIKE', 'image/%')
            ->where('mime_type', '!=', 'image/svg+xml')
            ->where(function ($q) {
                $q->whereNull('metadata')
                    ->orWhereJsonLength('metadata', 'thumbnails', 0)
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.thumbnails') IS NULL");
            })
            ->get($columns);
    }
}
