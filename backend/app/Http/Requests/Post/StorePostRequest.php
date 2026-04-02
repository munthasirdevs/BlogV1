<?php

namespace App\Http\Requests\Post;

use App\Rules\SlugUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StorePostRequest
 *
 * Validates requests for creating new posts.
 *
 * @OA\Schema(
 *     schema="StorePostRequest",
 *     required={"title", "content", "category_id"},
 *     @OA\Property(property="title", type="string", minLength=5, maxLength=200, example="Getting Started with Laravel"),
 *     @OA\Property(property="slug", type="string", maxLength=255, example="getting-started-with-laravel"),
 *     @OA\Property(property="excerpt", type="string", maxLength=500, example="A brief introduction to Laravel..."),
 *     @OA\Property(property="content", type="string", minLength=50, example="Full post content here..."),
 *     @OA\Property(property="featured_image", type="string", format="url", example="https://example.com/image.jpg"),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="tags", type="array", @OA\Items(type="integer"), example={1, 2, 3}),
 *     @OA\Property(property="status", type="string", enum={"draft", "published", "scheduled", "archived"}, example="draft"),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
 *     @OA\Property(property="meta_title", type="string", maxLength=60, example="Laravel Guide"),
 *     @OA\Property(property="meta_description", type="string", maxLength=160, example="Learn Laravel basics..."),
 *     @OA\Property(property="is_featured", type="boolean", example=false)
 * )
 */
class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Users can create posts if they're authenticated
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'slug' => ['nullable', 'string', 'max:255', new SlugUnique('posts', 'slug')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'min:50'],
            'featured_image' => ['nullable', 'string', 'url'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published', 'scheduled', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a title for your post.',
            'title.min' => 'Title must be at least 5 characters.',
            'title.max' => 'Title cannot exceed 200 characters.',
            'content.required' => 'Please enter content for your post.',
            'content.min' => 'Content must be at least 50 characters.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'tags.max' => 'You can select up to 10 tags.',
            'tags.*.exists' => 'One or more selected tags are invalid.',
            'status.in' => 'Invalid post status selected.',
            'featured_image.url' => 'Featured image must be a valid URL.',
            'published_at.date' => 'Published date must be a valid date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'tags.*' => 'tag',
            'published_at' => 'publish date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug if not provided and title exists
        if (empty($this->slug) && !empty($this->title)) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title),
            ]);
        }
    }
}
