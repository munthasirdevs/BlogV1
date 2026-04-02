<?php

namespace App\Http\Requests\Tag;

use App\Rules\SlugUnique;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreTagRequest
 *
 * Validates requests for creating new tags.
 *
 * @OA\Schema(
 *     schema="StoreTagRequest",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", minLength=2, maxLength=50, example="Laravel"),
 *     @OA\Property(property="slug", type="string", maxLength=255, example="laravel"),
 *     @OA\Property(property="description", type="string", maxLength=300, example="Posts about Laravel framework"),
 *     @OA\Property(property="color", type="string", maxLength=7, example="#FF2D20"),
 *     @OA\Property(property="is_featured", type="boolean", example=false)
 * )
 */
class StoreTagRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'slug' => ['nullable', 'string', 'max:255', new SlugUnique('tags', 'slug')],
            'description' => ['nullable', 'string', 'max:300'],
            'color' => ['nullable', 'string', 'size:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a tag name.',
            'name.min' => 'Tag name must be at least 2 characters.',
            'name.max' => 'Tag name cannot exceed 50 characters.',
            'color.size' => 'Color must be a 6-digit hex code (e.g., #FF2D20).',
            'color.regex' => 'Color must be a valid hex color code.',
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
    }
}
