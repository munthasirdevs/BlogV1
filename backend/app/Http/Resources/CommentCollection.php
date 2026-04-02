<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class CommentCollection
 *
 * Resource collection for Comment resources.
 */
class CommentCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = CommentResource::class;

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
}
