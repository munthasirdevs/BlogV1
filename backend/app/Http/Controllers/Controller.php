<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class Controller
 *
 * Base controller class for all API controllers.
 * Provides common functionality including standardized response methods.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, ApiResponse;

    /**
     * Maximum pagination limit.
     */
    protected const MAX_PAGINATION_LIMIT = 100;

    /**
     * Default pagination limit.
     */
    protected const DEFAULT_PAGINATION_LIMIT = 15;

    /**
     * Get the authenticated user.
     *
     * @return \App\Models\User|null
     */
    protected function user(): ?\App\Models\User
    {
        return request()->user();
    }

    /**
     * Check if the authenticated user is admin.
     *
     * @return bool
     */
    protected function isAdmin(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get pagination limit from request.
     *
     * @return int
     */
    protected function getLimit(): int
    {
        $limit = request()->integer('per_page', self::DEFAULT_PAGINATION_LIMIT);
        return min($limit, self::MAX_PAGINATION_LIMIT);
    }

    /**
     * Get sorting parameters from request.
     *
     * @return array{field: string, direction: string}
     */
    protected function getSorting(array $allowedFields = ['created_at']): array
    {
        $field = request()->get('sort', 'created_at');
        $direction = strtolower(request()->get('order', 'desc'));

        if (!in_array($field, $allowedFields)) {
            $field = 'created_at';
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        return ['field' => $field, 'direction' => $direction];
    }
}
