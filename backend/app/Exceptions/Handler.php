<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class Handler
 *
 * Global exception handler with custom error codes, detailed messages,
 * and user-friendly error responses.
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Custom error codes for different exception types.
     */
    protected array $errorCodes = [
        ValidationException::class => 'VALIDATION_ERROR',
        AuthenticationException::class => 'UNAUTHORIZED',
        AuthorizationException::class => 'FORBIDDEN',
        ModelNotFoundException::class => 'NOT_FOUND',
        NotFoundHttpException::class => 'NOT_FOUND',
        MethodNotAllowedHttpException::class => 'METHOD_NOT_ALLOWED',
        AccessDeniedHttpException::class => 'FORBIDDEN',
        HttpException::class => 'HTTP_ERROR',
    ];

    /**
     * User-friendly error messages.
     */
    protected array $errorMessages = [
        ValidationException::class => 'The given data was invalid.',
        AuthenticationException::class => 'Unauthenticated. Please log in to continue.',
        AuthorizationException::class => 'You do not have permission to perform this action.',
        ModelNotFoundException::class => 'The requested resource was not found.',
        NotFoundHttpException::class => 'The requested endpoint was not found.',
        MethodNotAllowedHttpException::class => 'The HTTP method is not allowed for this endpoint.',
        AccessDeniedHttpException::class => 'Access denied to this resource.',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle API exceptions
        $this->renderable(function (Throwable $e, Request $request) {
            // Only handle API requests
            if (!$this->isApiRequest($request)) {
                return null;
            }

            return $this->handleApiException($request, $e);
        });
    }

    /**
     * Handle API exceptions.
     *
     * @param Request $request
     * @param Throwable $e
     * @return JsonResponse
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Log the exception
        $this->logException($e, $request);

        // Get response data
        $response = $this->getExceptionResponse($e, $request);

        // Add request ID
        $response['request_id'] = $request->header('X-Request-ID') ?? uniqid('req_');

        // Add timestamp
        $response['timestamp'] = now()->toISOString();

        // Add path
        $response['path'] = $request->path();

        return response()->json(
            $response,
            $this->getStatusCode($e),
            $this->getHeaders($e)
        );
    }

    /**
     * Get the exception response data.
     *
     * @param Throwable $e
     * @param Request $request
     * @return array
     */
    protected function getExceptionResponse(Throwable $e, Request $request): array
    {
        $isDev = config('app.debug');

        $response = [
            'success' => false,
            'message' => $this->getMessage($e, $isDev),
            'error_code' => $this->getErrorCode($e),
            'data' => null,
        ];

        // Add detailed errors for validation exceptions
        if ($e instanceof ValidationException) {
            $response['errors'] = $this->formatValidationErrors($e->errors());
        }

        // Add debug information in development
        if ($isDev) {
            $response['debug'] = $this->getDebugInfo($e);
        }

        return $response;
    }

    /**
     * Get the error message.
     *
     * @param Throwable $e
     * @param bool $isDev
     * @return string
     */
    protected function getMessage(Throwable $e, bool $isDev): string
    {
        // Use custom message if available
        if (isset($this->errorMessages[get_class($e)])) {
            return $this->errorMessages[get_class($e)];
        }

        // Show detailed message in development
        if ($isDev) {
            return $e->getMessage();
        }

        // Generic message for production
        return $this->getGenericMessage($this->getStatusCode($e));
    }

    /**
     * Get generic message for status code.
     *
     * @param int $statusCode
     * @return string
     */
    protected function getGenericMessage(int $statusCode): string
    {
        $messages = [
            400 => 'Bad request. Please check your input.',
            401 => 'Authentication required. Please log in.',
            403 => 'You do not have permission to perform this action.',
            404 => 'The requested resource was not found.',
            405 => 'This method is not allowed.',
            409 => 'A conflict occurred. Please try again.',
            422 => 'The given data was invalid.',
            429 => 'Too many requests. Please try again later.',
            500 => 'An unexpected error occurred. Please try again later.',
            502 => 'Bad gateway. Please try again later.',
            503 => 'Service temporarily unavailable. Please try again later.',
            504 => 'Gateway timeout. Please try again later.',
        ];

        return $messages[$statusCode] ?? 'An error occurred.';
    }

    /**
     * Get the error code.
     *
     * @param Throwable $e
     * @return string
     */
    protected function getErrorCode(Throwable $e): string
    {
        // Check for custom error code
        if (isset($this->errorCodes[get_class($e)])) {
            return $this->errorCodes[get_class($e)];
        }

        // Check if exception has a custom error code method
        if (method_exists($e, 'getErrorCode')) {
            return $e->getErrorCode();
        }

        // Default based on status code
        $statusCode = $this->getStatusCode($e);
        return 'ERROR_' . $statusCode;
    }

    /**
     * Format validation errors.
     *
     * @param array $errors
     * @return array
     */
    protected function formatValidationErrors(array $errors): array
    {
        $formatted = [];

        foreach ($errors as $field => $messages) {
            $formatted[] = [
                'field' => $field,
                'messages' => is_array($messages) ? $messages : [$messages],
            ];
        }

        return [
            'type' => 'validation_error',
            'errors' => $formatted,
        ];
    }

    /**
     * Get debug information.
     *
     * @param Throwable $e
     * @return array
     */
    protected function getDebugInfo(Throwable $e): array
    {
        return [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'env' => config('app.env'),
            'version' => config('app.version', '1.0.0'),
        ];
    }

    /**
     * Get the HTTP status code.
     *
     * @param Throwable $e
     * @return int
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof ModelNotFoundException) {
            return 404;
        }

        if ($e instanceof NotFoundHttpException) {
            return 404;
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return 405;
        }

        // Default to 500
        return 500;
    }

    /**
     * Get response headers.
     *
     * @param Throwable $e
     * @return array
     */
    protected function getHeaders(Throwable $e): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-API-Version' => 'v1',
        ];

        if ($e instanceof HttpException) {
            $headers = array_merge($headers, $e->getHeaders());
        }

        return $headers;
    }

    /**
     * Log the exception.
     *
     * @param Throwable $e
     * @param Request $request
     * @return void
     */
    protected function logException(Throwable $e, Request $request): void
    {
        $statusCode = $this->getStatusCode($e);

        // Don't log 4xx errors as errors (they're client errors)
        if ($statusCode >= 400 && $statusCode < 500) {
            Log::channel('api')->warning('API Client Error', [
                'status' => $statusCode,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
        } else {
            Log::channel('api')->error('API Server Error', [
                'status' => $statusCode,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
        }
    }

    /**
     * Check if request is an API request.
     *
     * @param Request $request
     * @return bool
     */
    protected function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') ||
               $request->expectsJson() ||
               $request->wantsJson() ||
               str_contains($request->header('Accept', ''), 'application/json');
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->isApiRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please provide valid authentication credentials.',
                'error_code' => 'UNAUTHORIZED',
                'data' => null,
                'errors' => [
                    'type' => 'authentication_error',
                ],
                'request_id' => $request->header('X-Request-ID') ?? uniqid('req_'),
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        return parent::unauthenticated($request, $exception);
    }
}
