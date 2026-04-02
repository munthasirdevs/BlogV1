<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SearchCommentsRequest
 *
 * Validates requests for searching comments (admin/moderator).
 */
class SearchCommentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'moderator']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected,spam'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from_date' => ['nullable', 'date', 'before_or_equal:to_date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'sort' => ['nullable', 'string', 'in:created_at,updated_at,content,status,likes_count'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'Search term cannot exceed 255 characters.',
            'status.in' => 'Invalid status. Must be one of: pending, approved, rejected, spam.',
            'from_date.before_or_equal' => 'Start date must be before or equal to end date.',
            'to_date.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page cannot exceed 100.',
        ];
    }

    /**
     * Get the search term.
     */
    public function getSearchTerm(): ?string
    {
        return $this->input('search');
    }

    /**
     * Get the status filter.
     */
    public function getStatusFilter(): ?string
    {
        return $this->input('status');
    }

    /**
     * Get the post ID filter.
     */
    public function getPostIdFilter(): ?int
    {
        return $this->input('post_id');
    }

    /**
     * Get the user ID filter.
     */
    public function getUserIdFilter(): ?int
    {
        return $this->input('user_id');
    }

    /**
     * Get the from date filter.
     */
    public function getFromDateFilter(): ?string
    {
        return $this->input('from_date');
    }

    /**
     * Get the to date filter.
     */
    public function getToDateFilter(): ?string
    {
        return $this->input('to_date');
    }

    /**
     * Get the sort field.
     */
    public function getSortField(): string
    {
        return $this->input('sort', 'created_at');
    }

    /**
     * Get the sort order.
     */
    public function getSortOrder(): string
    {
        return $this->input('order', 'desc');
    }

    /**
     * Get items per page.
     */
    public function getPerPage(): int
    {
        return $this->input('per_page', 20);
    }

    /**
     * Get all filters as array.
     */
    public function getFilters(): array
    {
        return [
            'search' => $this->getSearchTerm(),
            'status' => $this->getStatusFilter(),
            'post_id' => $this->getPostIdFilter(),
            'user_id' => $this->getUserIdFilter(),
            'from_date' => $this->getFromDateFilter(),
            'to_date' => $this->getToDateFilter(),
        ];
    }
}
