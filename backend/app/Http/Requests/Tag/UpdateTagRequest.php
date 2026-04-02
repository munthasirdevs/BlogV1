<?php

namespace App\Http\Requests\Tag;

use App\Rules\SlugUnique;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateTagRequest
 *
 * Validates requests for updating existing tags.
 *
 * @OA\Schema(
 *     schema="UpdateTagRequest",
 *     @OA\Property(property="name", type="string", minLength=2, maxLength=50, example="Updated Laravel"),
 *     @OA\Property(property="slug", type="string", maxLength=255, example="updated-laravel"),
 *     @OA\Property(property="description", type="string", maxLength=300),
 *     @OA\Property(property="color", type="string", maxLength=7, example="#FF5733"),
 *     @OA\Property(property="is_featured", type="boolean")
 * )
 */
class UpdateTagRequest extends FormRequest
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
        $tagId = $this->route('tag')?->id ?? $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:50'],
            'slug' => ['nullable', 'string', 'max:255', new SlugUnique('tags', 'slug', $tagId)],
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
}
