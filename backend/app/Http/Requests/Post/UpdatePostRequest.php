<?php

namespace App\Http\Requests\Post;

use App\Rules\SlugUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdatePostRequest
 *
 * Validates requests for updating existing posts.
 *
 * @OA\Schema(
 *     schema="UpdatePostRequest",
 *     @OA\Property(property="title", type="string", minLength=5, maxLength=200, example="Updated Laravel Guide"),
 *     @OA\Property(property="slug", type="string", maxLength=255, example="updated-laravel-guide"),
 *     @OA\Property(property="excerpt", type="string", maxLength=500, example="Updated excerpt..."),
 *     @OA\Property(property="content", type="string", minLength=50, example="Updated content..."),
 *     @OA\Property(property="featured_image", type="string", format="url", example="https://example.com/new-image.jpg"),
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="tags", type="array", @OA\Items(type="integer"), example={1, 2, 4}),
 *     @OA\Property(property="status", type="string", enum={"draft", "published", "scheduled", "archived"}, example="published"),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
 *     @OA\Property(property="meta_title", type="string", maxLength=60),
 *     @OA\Property(property="meta_description", type="string", maxLength=160),
 *     @OA\Property(property="is_featured", type="boolean", example=true)
 * )
 */
class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');

        // Admins and editors can update any post
        if (in_array($this->user()->role, ['admin', 'editor'])) {
            return true;
        }

        // Authors can only update their own posts
        if ($post && $this->user()->id === $post->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $postId = $this->route('post')?->id ?? $this->route('id');

        return [
            'title' => ['sometimes', 'required', 'string', 'min:5', 'max:200'],
            'slug' => ['nullable', 'string', 'max:255', new SlugUnique('posts', 'slug', $postId)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['sometimes', 'required', 'string', 'min:50'],
            'featured_image' => ['nullable', 'string', 'url'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
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
            'content.required' => 'Please enter content for your post.',
            'content.min' => 'Content must be at least 50 characters.',
            'category_id.exists' => 'The selected category is invalid.',
            'tags.max' => 'You can select up to 10 tags.',
            'tags.*.exists' => 'One or more selected tags are invalid.',
            'status.in' => 'Invalid post status selected.',
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
        ];
    }
}
