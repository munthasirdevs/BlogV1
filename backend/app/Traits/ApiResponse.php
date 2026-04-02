<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait ApiResponse
 *
 * Provides standardized API response methods for consistent JSON responses.
 * All API controllers should use this trait for uniform response formatting.
 */
trait ApiResponse
{
    /**
     * Success response with data.
     *
     * @param mixed $data The response data (model, collection, array, etc.)
     * @param string $message Success message
     * @param int $statusCode HTTP status code (default: 200)
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $this->transformData($data),
        ];

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Success response with pagination.
     *
     * @param LengthAwarePaginator $paginator Paginated data
     * @param string $message Success message
     * @param int $statusCode HTTP status code (default: 200)
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function successPaginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Created response (201).
     *
     * @param mixed $data The created resource
     * @param string $message Success message
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function created(
        mixed $data = null,
        string $message = 'Resource created successfully',
        array $headers = []
    ): JsonResponse {
        return $this->success($data, $message, 201, $headers);
    }

    /**
     * No content response (204).
     *
     * @param string $message Message
     * @return JsonResponse
     */
    protected function noContent(string $message = 'No content'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => null,
        ], 204);
    }

    /**
     * Error response.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code (default: 400)
     * @param mixed $errors Additional error details
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function error(
        string $message = 'Bad Request',
        int $statusCode = 400,
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Validation error response (422).
     *
     * @param array $errors Validation errors
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function validationError(
        array $errors,
        string $message = 'Validation failed',
        array $headers = []
    ): JsonResponse {
        return $this->error($message, 422, $errors, $headers);
    }

    /**
     * Unauthorized response (401).
     *
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function unauthorized(
        string $message = 'Unauthorized',
        array $headers = []
    ): JsonResponse {
        return $this->error($message, 401, null, $headers);
    }

    /**
     * Forbidden response (403).
     *
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function forbidden(
        string $message = 'Forbidden',
        array $headers = []
    ): JsonResponse {
        return $this->error($message, 403, null, $headers);
    }

    /**
     * Not found response (404).
     *
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function notFound(
        string $message = 'Resource not found',
        array $headers = []
    ): JsonResponse {
        return $this->error($message, 404, null, $headers);
    }

    /**
     * Server error response (500).
     *
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function serverError(
        string $message = 'Internal Server Error',
        array $headers = []
    ): JsonResponse {
        return $this->error($message, 500, null, $headers);
    }

    /**
     * Conflict response (409).
     *
     * @param string $message Error message
     * @param mixed $errors Additional error details
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function conflict(
        string $message = 'Conflict',
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        return $this->error($message, 409, $errors, $headers);
    }

    /**
     * Too many requests response (429).
     *
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return JsonResponse
     */
    protected function tooManyRequests(
        string $message = 'Too many requests',
        array $headers = []
    ): JsonResponse {
        return $this->error($message, 429, null, $headers);
    }

    /**
     * Transform data based on type.
     *
     * @param mixed $data The data to transform
     * @return mixed
     */
    private function transformData(mixed $data): mixed
    {
        if ($data instanceof Model) {
            return $data;
        }

        if ($data instanceof Collection) {
            return $data;
        }

        if ($data instanceof LengthAwarePaginator) {
            return $data->items();
        }

        return $data;
    }
}
