<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class PostCollection
 *
 * Resource collection for Post resources.
 */
class PostCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PostResource::class;

    /**
     * Additional meta information.
     */
    protected array $additionalMeta = [];

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
            'meta' => array_merge([
                'pagination' => $this->paginationData($request),
            ], $this->additionalMeta),
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
            'count' => $this->resource->count(),
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
     * Add additional meta information.
     *
     * @param array $meta
     * @return self
     */
    public function withMeta(array $meta): self
    {
        $this->additionalMeta = array_merge($this->additionalMeta, $meta);
        return $this;
    }

    /**
     * Add additional data to the response.
     *
     * @param Request $request
     * @return array
     */
    public function with($request): array
    {
        return array_merge([
            'version' => 'v1',
            'timestamp' => now()->toISOString(),
        ], $this->additionalMeta);
    }
}
