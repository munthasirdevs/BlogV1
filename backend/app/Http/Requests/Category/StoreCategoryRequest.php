<?php

namespace App\Http\Requests\Category;

use App\Rules\RecursiveParent;
use App\Rules\SlugUnique;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreCategoryRequest
 *
 * Validates requests for creating new categories.
 *
 * @OA\Schema(
 *     schema="StoreCategoryRequest",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", minLength=2, maxLength=100, example="Technology"),
 *     @OA\Property(property="slug", type="string", maxLength=255, example="technology"),
 *     @OA\Property(property="description", type="string", maxLength=500, example="Posts about technology..."),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="color", type="string", maxLength=7, example="#3B82F6"),
 *     @OA\Property(property="icon", type="string", nullable=true, example="fa-code"),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(property="is_featured", type="boolean", example=false),
 *     @OA\Property(property="is_active", type="boolean", example=true)
 * )
 */
class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255', new SlugUnique('categories', 'slug')],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', new RecursiveParent('categories', 'parent_id')],
            'color' => ['nullable', 'string', 'size:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a category name.',
            'name.min' => 'Category name must be at least 2 characters.',
            'name.max' => 'Category name cannot exceed 100 characters.',
            'parent_id.exists' => 'The selected parent category is invalid.',
            'color.size' => 'Color must be a 6-digit hex code (e.g., #3B82F6).',
            'color.regex' => 'Color must be a valid hex color code.',
            'sort_order.min' => 'Sort order cannot be negative.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug if not provided and name exists
        if (empty($this->slug) && !empty($this->name)) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name),
            ]);
        }

        // Default color if not provided
        if (empty($this->color)) {
            $this->merge([
                'color' => '#6B7280',
            ]);
        }

        // Default sort order if not provided
        if (!isset($this->sort_order)) {
            $this->merge([
                'sort_order' => \App\Models\Category::max('sort_order') + 1,
            ]);
        }
    }
}
