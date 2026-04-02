<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Class ResponseTransformer
 *
 * Provides consistent API response formatting across all endpoints.
 * Handles success responses, error responses, pagination metadata,
 * rate limiting headers, and caching headers.
 *
 * @package App\Services
 */
class ResponseTransformer
{
    /**
     * The current request instance.
     */
    protected Request $request;

    /**
     * Default response format version.
     */
    protected string $version = 'v1';

    /**
     * Response metadata.
     */
    protected array $meta = [];

    /**
     * Additional headers to include.
     */
    protected array $headers = [];

    /**
     * Whether to include rate limit info.
     */
    protected bool $includeRateLimit = true;

    /**
     * Whether to include caching headers.
     */
    protected bool $includeCacheHeaders = true;

    /**
     * ResponseTransformer constructor.
     *
     * @param Request|null $request
     */
    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? request();
    }

    /**
     * Create a new instance with the given request.
     *
     * @param Request|null $request
     * @return static
     */
    public static function make(?Request $request = null): static
    {
        return new static($request);
    }

    /**
     * Set the API version.
     *
     * @param string $version
     * @return self
     */
    public function version(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Add metadata to the response.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function withMeta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * Add multiple metadata entries.
     *
     * @param array $meta
     * @return self
     */
    public function withMetaArray(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    /**
     * Add a header to the response.
     *
     * @param string $key
     * @param string $value
     * @return self
     */
    public function withHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Add multiple headers.
     *
     * @param array $headers
     * @return self
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Disable rate limit info in response.
     *
     * @return self
     */
    public function withoutRateLimit(): self
    {
        $this->includeRateLimit = false;
        return $this;
    }

    /**
     * Disable cache headers.
     *
     * @return self
     */
    public function withoutCacheHeaders(): self
    {
        $this->includeCacheHeaders = false;
        return $this;
    }

    /**
     * Create a success response.
     *
     * @param mixed $data The response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    public function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $this->transformData($data),
            'meta' => $this->buildMeta($data),
            'errors' => null,
        ];

        // Remove empty meta
        if (empty($response['meta'])) {
            unset($response['meta']);
        }

        return response()->json($response, $statusCode, $this->buildHeaders());
    }

    /**
     * Create a created response (201).
     *
     * @param mixed $data The created resource
     * @param string $message Success message
     * @return JsonResponse
     */
    public function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Create a paginated success response.
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        $this->withMetaArray([
            'pagination' => $this->buildPaginationMeta($paginator),
        ]);

        return $this->success($paginator->items(), $message, $statusCode);
    }

    /**
     * Create an error response.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param mixed $errors Additional error details
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    public function error(
        string $message,
        int $statusCode = 400,
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];

        if ($errors !== null) {
            $response['errors'] = $this->transformErrors($errors);
        }

        // Add error code
        $response['error_code'] = $this->getErrorCode($statusCode);

        // Add request ID for tracking
        $response['request_id'] = $this->request->header('X-Request-ID') ?? uniqid('req_');

        return response()->json($response, $statusCode, array_merge($this->buildHeaders(), $headers));
    }

    /**
     * Create a validation error response (422).
     *
     * @param array $errors Validation errors
     * @param string $message
     * @return JsonResponse
     */
    public function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        $formattedErrors = [];

        foreach ($errors as $field => $messages) {
            $formattedErrors[] = [
                'field' => $field,
                'messages' => is_array($messages) ? $messages : [$messages],
            ];
        }

        return $this->error($message, 422, [
            'type' => 'validation_error',
            'errors' => $formattedErrors,
        ]);
    }

    /**
     * Create an unauthorized response (401).
     *
     * @param string $message
     * @return JsonResponse
     */
    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401, [
            'type' => 'authentication_error',
        ]);
    }

    /**
     * Create a forbidden response (403).
     *
     * @param string $message
     * @return JsonResponse
     */
    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403, [
            'type' => 'authorization_error',
        ]);
    }

    /**
     * Create a not found response (404).
     *
     * @param string $message
     * @return JsonResponse
     */
    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404, [
            'type' => 'not_found_error',
        ]);
    }

    /**
     * Create a conflict response (409).
     *
     * @param string $message
     * @param mixed $errors
     * @return JsonResponse
     */
    public function conflict(string $message = 'Conflict', mixed $errors = null): JsonResponse
    {
        return $this->error($message, 409, [
            'type' => 'conflict_error',
            'details' => $errors,
        ]);
    }

    /**
     * Create a too many requests response (429).
     *
     * @param string $message
     * @param int|null $retryAfter Seconds until retry is allowed
     * @return JsonResponse
     */
    public function tooManyRequests(string $message = 'Too many requests', ?int $retryAfter = null): JsonResponse
    {
        $headers = [];
        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string) $retryAfter;
        }

        return $this->error($message, 429, [
            'type' => 'rate_limit_error',
            'retry_after' => $retryAfter,
        ], $headers);
    }

    /**
     * Create a server error response (500).
     *
     * @param string $message
     * @param bool $includeDetails Whether to include error details (dev only)
     * @return JsonResponse
     */
    public function serverError(string $message = 'Internal Server Error', bool $includeDetails = false): JsonResponse
    {
        $errors = [
            'type' => 'server_error',
        ];

        if ($includeDetails && config('app.debug')) {
            $errors['debug'] = [
                'env' => config('app.env'),
                'version' => config('app.version', '1.0.0'),
            ];
        }

        return $this->error($message, 500, $errors);
    }

    /**
     * Create a no content response (204).
     *
     * @param string $message
     * @return JsonResponse
     */
    public function noContent(string $message = 'No content'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => null,
            'meta' => null,
            'errors' => null,
        ], 204, $this->buildHeaders());
    }

    /**
     * Transform data based on its type.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function transformData(mixed $data): mixed
    {
        if ($data === null) {
            return null;
        }

        if ($data instanceof Model) {
            return $data;
        }

        if ($data instanceof Collection) {
            return $data->values();
        }

        if ($data instanceof LengthAwarePaginator) {
            return $data->items();
        }

        if (is_array($data)) {
            return $data;
        }

        return $data;
    }

    /**
     * Build meta information for the response.
     *
     * @param mixed $data
     * @return array
     */
    protected function buildMeta(mixed $data): array
    {
        $meta = $this->meta;

        // Add API version
        $meta['version'] = $this->version;

        // Add timestamp
        $meta['timestamp'] = now()->toISOString();

        // Add rate limit info
        if ($this->includeRateLimit) {
            $meta['rate_limit'] = $this->getRateLimitInfo();
        }

        // Add pagination meta if data is paginated
        if ($data instanceof LengthAwarePaginator) {
            $meta['pagination'] = $this->buildPaginationMeta($data);
        }

        return $meta;
    }

    /**
     * Build pagination metadata.
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    protected function buildPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'count' => $paginator->count(),
        ];
    }

    /**
     * Build pagination links.
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function buildPaginationLinks(LengthAwarePaginator $paginator): array
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }

    /**
     * Get rate limit information from the request.
     *
     * @return array|null
     */
    protected function getRateLimitInfo(): ?array
    {
        $headers = $this->request->headers;

        if (!$headers->has('X-RateLimit-Limit')) {
            return null;
        }

        return [
            'limit' => (int) $headers->get('X-RateLimit-Limit'),
            'remaining' => (int) $headers->get('X-RateLimit-Remaining', 0),
            'reset' => (int) $headers->get('X-RateLimit-Reset', 0),
        ];
    }

    /**
     * Build response headers.
     *
     * @return array
     */
    protected function buildHeaders(): array
    {
        $headers = $this->headers;

        // Add API version header
        $headers['X-API-Version'] = $this->version;

        // Add content type
        $headers['Content-Type'] = 'application/json';

        // Add cache headers if enabled
        if ($this->includeCacheHeaders) {
            $headers = array_merge($headers, $this->getCacheHeaders());
        }

        // Add CORS headers
        $headers = array_merge($headers, $this->getCorsHeaders());

        return $headers;
    }

    /**
     * Get cache headers.
     *
     * @return array
     */
    protected function getCacheHeaders(): array
    {
        // Default to no cache for API responses
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Sun, 01 Jan 2000 00:00:00 GMT',
        ];
    }

    /**
     * Get CORS headers.
     *
     * @return array
     */
    protected function getCorsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => config('cors.paths.0', '*'),
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-API-Key, X-Request-ID',
            'Access-Control-Expose-Headers' => 'X-API-Version, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset',
        ];
    }

    /**
     * Transform errors to a consistent format.
     *
     * @param mixed $errors
     * @return array
     */
    protected function transformErrors(mixed $errors): array
    {
        if (is_array($errors)) {
            return $errors;
        }

        if (is_string($errors)) {
            return ['message' => $errors];
        }

        if ($errors instanceof \Illuminate\Validation\ValidationException) {
            return [
                'type' => 'validation_error',
                'errors' => $errors->errors(),
            ];
        }

        if ($errors instanceof \Throwable) {
            return [
                'type' => 'error',
                'message' => $errors->getMessage(),
            ];
        }

        return ['message' => 'An error occurred'];
    }

    /**
     * Get error code for status code.
     *
     * @param int $statusCode
     * @return string
     */
    protected function getErrorCode(int $statusCode): string
    {
        $codes = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_SERVER_ERROR',
            502 => 'BAD_GATEWAY',
            503 => 'SERVICE_UNAVAILABLE',
            504 => 'GATEWAY_TIMEOUT',
        ];

        return $codes[$statusCode] ?? 'UNKNOWN_ERROR';
    }

    /**
     * Set cache headers for the response.
     *
     * @param int $maxAge Max age in seconds
     * @param bool $public Whether cache is public
     * @return self
     */
    public function cache(int $maxAge = 3600, bool $public = true): self
    {
        $cacheControl = $public ? 'public' : 'private';
        $cacheControl .= ", max-age={$maxAge}";

        $this->withHeader('Cache-Control', $cacheControl);
        $this->withHeader('Expires', now()->addSeconds($maxAge)->toRfc7231String());

        return $this;
    }

    /**
     * Set ETag for the response.
     *
     * @param string $etag
     * @return self
     */
    public function etag(string $etag): self
    {
        $this->withHeader('ETag', '"' . $etag . '"');
        return $this;
    }

    /**
     * Set Last-Modified header.
     *
     * @param \DateTimeInterface $date
     * @return self
     */
    public function lastModified(\DateTimeInterface $date): self
    {
        $this->withHeader('Last-Modified', $date->format(\DateTimeInterface::RFC7231));
        return $this;
    }

    /**
     * Add deprecation warning header.
     *
     * @param string $message Deprecation message
     * @param string|null $link Link to migration guide
     * @return self
     */
    public function deprecate(string $message, ?string $link = null): self
    {
        $this->withHeader('Deprecation', 'true');
        $this->withHeader('Sunset', now()->addMonths(6)->format(\DateTimeInterface::RFC7231));

        if ($link) {
            $this->withHeader('Link', "<{$link}>; rel=\"deprecation\"");
        }

        $this->withMeta('deprecation_warning', $message);

        return $this;
    }
}
