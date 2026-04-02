<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class ReorderCategoriesRequest
 *
 * Validates requests for reordering categories.
 *
 * @OA\Schema(
 *     schema="ReorderCategoriesRequest",
 *     required={"categories"},
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *         @OA\Items(
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="sort_order", type="integer", example=1)
 *         )
 *     )
 * )
 */
class ReorderCategoriesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.id' => ['required', 'integer', 'exists:categories,id'],
            'categories.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'categories.required' => 'Please provide at least one category to reorder.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.id.required' => 'Each category must have an ID.',
            'categories.*.id.exists' => 'One or more category IDs are invalid.',
            'categories.*.sort_order.required' => 'Each category must have a sort order.',
            'categories.*.sort_order.integer' => 'Sort order must be a number.',
            'categories.*.sort_order.min' => 'Sort order cannot be negative.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for duplicate IDs
            if ($this->has('categories')) {
                $ids = collect($this->categories)->pluck('id');
                if ($ids->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('categories', 'Duplicate category IDs are not allowed.');
                }
            }
        });
    }
}
