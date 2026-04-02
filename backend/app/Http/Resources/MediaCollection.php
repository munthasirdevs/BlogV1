<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class MediaCollection
 *
 * Resource collection for Media resources.
 */
class MediaCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = MediaResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'pagination' => $this->paginationData($request),
                'storage' => $this->storageInfo(),
            ],
            'links' => $this->paginationLinks($request),
        ];
    }

    /**
     * Get pagination data.
     *
     * @param Request $request
     * @return array|null
     */
    protected function paginationData(Request $request): ?array
    {
        if (!$this->resource instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return null;
        }

        return [
            'current_page' => $this->resource->currentPage(),
            'last_page' => $this->resource->lastPage(),
            'per_page' => $this->resource->perPage(),
            'total' => $this->resource->total(),
            'from' => $this->resource->firstItem(),
            'to' => $this->resource->lastItem(),
        ];
    }

    /**
     * Get pagination links.
     *
     * @param Request $request
     * @return array
     */
    protected function paginationLinks(Request $request): array
    {
        if (!$this->resource instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return [];
        }

        return [
            'first' => $this->resource->url(1),
            'last' => $this->resource->url($this->resource->lastPage()),
            'prev' => $this->resource->previousPageUrl(),
            'next' => $this->resource->nextPageUrl(),
        ];
    }

    /**
     * Get storage information.
     *
     * @return array
     */
    protected function storageInfo(): array
    {
        $totalSize = $this->collection->sum('file_size');

        return [
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatFileSize($totalSize),
            'count' => $this->collection->count(),
        ];
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
}
