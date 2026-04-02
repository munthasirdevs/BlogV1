<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Class ApiException
 *
 * Base exception class for API-related errors.
 */
class ApiException extends RuntimeException
{
    /**
     * HTTP status code.
     */
    protected int $statusCode = 500;

    /**
     * Error code for client reference.
     */
    protected ?string $errorCode = null;

    /**
     * Additional error metadata.
     */
    protected array $metadata = [];

    /**
     * ApiException constructor.
     *
     * @param string $message
     * @param int|null $statusCode
     * @param string|null $errorCode
     * @param array $metadata
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'API Error',
        ?int $statusCode = null,
        ?string $errorCode = null,
        array $metadata = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }

        $this->errorCode = $errorCode;
        $this->metadata = $metadata;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the error code.
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the metadata.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Create a validation exception.
     *
     * @param string $message
     * @param array $errors
     * @return self
     */
    public static function validation(string $message = 'Validation failed', array $errors = []): self
    {
        return new self($message, 422, 'VALIDATION_ERROR', ['errors' => $errors]);
    }

    /**
     * Create an authentication exception.
     *
     * @param string $message
     * @return self
     */
    public static function authentication(string $message = 'Authentication required'): self
    {
        return new self($message, 401, 'AUTHENTICATION_ERROR');
    }

    /**
     * Create an authorization exception.
     *
     * @param string $message
     * @return self
     */
    public static function authorization(string $message = 'Forbidden'): self
    {
        return new self($message, 403, 'AUTHORIZATION_ERROR');
    }

    /**
     * Create a not found exception.
     *
     * @param string $message
     * @return self
     */
    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self($message, 404, 'NOT_FOUND_ERROR');
    }

    /**
     * Create a conflict exception.
     *
     * @param string $message
     * @param array $metadata
     * @return self
     */
    public static function conflict(string $message = 'Conflict', array $metadata = []): self
    {
        return new self($message, 409, 'CONFLICT_ERROR', $metadata);
    }

    /**
     * Create a rate limit exception.
     *
     * @param string $message
     * @return self
     */
    public static function rateLimit(string $message = 'Too many requests'): self
    {
        return new self($message, 429, 'RATE_LIMIT_ERROR');
    }

    /**
     * Create a server error exception.
     *
     * @param string $message
     * @return self
     */
    public static function server(string $message = 'Internal server error'): self
    {
        return new self($message, 500, 'SERVER_ERROR');
    }
}
