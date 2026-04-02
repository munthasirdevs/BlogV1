<?php

namespace App\Http\Requests\Interaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class BookmarkRequest
 *
 * Form request for bookmark operations.
 */
class BookmarkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'collection' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_\s]+$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'collection.regex' => 'Collection name can only contain letters, numbers, underscores, and spaces.',
            'collection.max' => 'Collection name must not exceed 50 characters.',
            'notes.max' => 'Notes must not exceed 500 characters.',
        ];
    }

    /**
     * Get collection name from request.
     */
    public function getCollection(): ?string
    {
        $collection = $this->input('collection');

        if ($collection) {
            return strtolower(trim(preg_replace('/\s+/', '_', $collection)));
        }

        return null;
    }

    /**
     * Get notes from request.
     */
    public function getNotes(): ?string
    {
        return $this->input('notes');
    }
}
