<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TopPostsRequest
 *
 * Validates parameters for top posts analytics endpoint.
 */
class TopPostsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->hasAnyRole(['admin', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => [
                'nullable',
                'date',
                'before_or_equal:end_date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'sort_by' => [
                'nullable',
                'string',
                'in:views,unique_views,engagement',
            ],
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sort_by.in' => 'Sort by must be one of: views, unique_views, engagement',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 100',
        ];
    }

    /**
     * Get validated sort by field.
     *
     * @return string
     */
    public function getSortBy(): string
    {
        return $this->get('sort_by', 'views');
    }

    /**
     * Get validated limit.
     *
     * @return int
     */
    public function getLimit(): int
    {
        return (int) $this->get('limit', 10);
    }
}
