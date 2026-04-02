<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AutosavePostRequest
 *
 * Validates requests for auto-saving post drafts.
 */
class AutosavePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');

        // Admins and editors can autosave any post
        if (in_array($this->user()->role, ['admin', 'editor'])) {
            return true;
        }

        // Authors can only autosave their own posts
        return $post && $this->user()->id === $post->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Title cannot exceed 200 characters.',
            'excerpt.max' => 'Excerpt cannot exceed 500 characters.',
            'meta_title.max' => 'Meta title cannot exceed 60 characters.',
            'meta_description.max' => 'Meta description cannot exceed 160 characters.',
        ];
    }
}
