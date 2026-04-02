<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class BulkPostsRequest
 *
 * Validates requests for bulk post actions.
 */
class BulkPostsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins and editors can perform bulk actions
        return in_array($this->user()->role, ['admin', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['publish', 'archive', 'delete', 'feature', 'restore'])],
            'post_ids' => ['required', 'array', 'min:1', 'max:100'],
            'post_ids.*' => ['required', 'integer', 'exists:posts,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please specify an action to perform.',
            'action.in' => 'Invalid action. Allowed actions: publish, archive, delete, feature, restore.',
            'post_ids.required' => 'Please select at least one post.',
            'post_ids.min' => 'Please select at least one post.',
            'post_ids.max' => 'You can select up to 100 posts at a time.',
            'post_ids.*.exists' => 'One or more selected posts do not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'post_ids' => 'posts',
        ];
    }
}
