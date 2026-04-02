<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SearchPostsRequest
 *
 * Validates requests for searching posts.
 */
class SearchPostsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Anyone can search published posts
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'category' => ['nullable', 'string'],
            'tag' => ['nullable', 'string'],
            'author' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:draft,published,scheduled,archived'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'sort' => ['nullable', 'string', 'in:relevance,date,title,views'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'boolean' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Please enter a search term.',
            'q.min' => 'Search term must be at least 2 characters.',
            'q.max' => 'Search term cannot exceed 200 characters.',
            'to_date.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page cannot exceed 100.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'q' => 'search term',
            'per_page' => 'items per page',
            'from_date' => 'start date',
            'to_date' => 'end date',
        ];
    }
}
