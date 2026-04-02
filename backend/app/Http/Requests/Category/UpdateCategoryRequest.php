<?php

namespace App\Http\Requests\Category;

use App\Rules\RecursiveParent;
use App\Rules\SlugUnique;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateCategoryRequest
 *
 * Validates requests for updating existing categories.
 *
 * @OA\Schema(
 *     schema="UpdateCategoryRequest",
 *     @OA\Property(property="name", type="string", minLength=2, maxLength=100, example="Updated Technology"),
 *     @OA\Property(property="slug", type="string", maxLength=255, example="updated-technology"),
 *     @OA\Property(property="description", type="string", maxLength=500),
 *     @OA\Property(property="parent_id", type="integer", nullable=true),
 *     @OA\Property(property="color", type="string", maxLength=7, example="#10B981"),
 *     @OA\Property(property="icon", type="string", nullable=true),
 *     @OA\Property(property="sort_order", type="integer"),
 *     @OA\Property(property="is_featured", type="boolean"),
 *     @OA\Property(property="is_active", type="boolean")
 * )
 */
class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id ?? $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255', new SlugUnique('categories', 'slug', $categoryId)],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => [
                'nullable', 
                'integer', 
                'exists:categories,id', 
                (new RecursiveParent('categories', 'parent_id'))->exclude($categoryId)
            ],
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
            'parent_id.exists' => 'The selected parent category is invalid.',
            'color.size' => 'Color must be a 6-digit hex code (e.g., #3B82F6).',
            'color.regex' => 'Color must be a valid hex color code.',
        ];
    }
}
