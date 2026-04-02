<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AttachTagsToPostRequest
 *
 * Validates requests for attaching tags to a post.
 *
 * @OA\Schema(
 *     schema="AttachTagsToPostRequest",
 *     required={"tags"},
 *     @OA\Property(
 *         property="tags",
 *         type="array",
 *         description="Array of tag IDs or tag names to attach",
 *         @OA\Items(anyOf={
 *             @OA\Schema(type="integer", example=1),
 *             @OA\Schema(type="string", example="laravel")
 *         })
 *     ),
 *     @OA\Property(
 *         property="create_if_not_exist",
 *         type="boolean",
 *         example=false,
 *         description="Create tags if they don't exist"
 *     )
 * )
 */
class AttachTagsToPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Users can attach tags to their own posts
        // Editors and admins can attach to any post
        $post = $this->route('post');
        
        if (!$post) {
            return false;
        }

        $user = $this->user();
        
        if ($user->hasRole(['admin', 'editor'])) {
            return true;
        }

        return $user->id === $post->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tags' => ['required', 'array', 'min:1'],
            'tags.*' => ['required_with:tags', 'string', 'max:50'],
            'create_if_not_exist' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tags.required' => 'Please provide at least one tag to attach.',
            'tags.array' => 'Tags must be an array.',
            'tags.min' => 'Please provide at least one tag.',
            'tags.*.required_with' => 'Each tag must be a valid ID or name.',
            'tags.*.string' => 'Each tag must be a valid ID or name.',
            'tags.*.max' => 'Tag names cannot exceed 50 characters.',
        ];
    }

    /**
     * Get the post from the route.
     *
     * @return \App\Models\Post|null
     */
    public function getPost(): ?\App\Models\Post
    {
        return $this->route('post');
    }
}
