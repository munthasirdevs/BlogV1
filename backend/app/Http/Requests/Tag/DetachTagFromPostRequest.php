<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DetachTagFromPostRequest
 *
 * Validates requests for detaching a tag from a post.
 */
class DetachTagFromPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Users can detach tags from their own posts
        // Editors and admins can detach from any post
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
            // No additional validation needed - route parameters handle it
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

    /**
     * Get the tag from the route.
     *
     * @return \App\Models\Tag|null
     */
    public function getTag(): ?\App\Models\Tag
    {
        return $this->route('tag');
    }
}
