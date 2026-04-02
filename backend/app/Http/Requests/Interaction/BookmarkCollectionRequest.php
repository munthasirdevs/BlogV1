<?php

namespace App\Http\Requests\Interaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class BookmarkCollectionRequest
 *
 * Form request for bookmark collection operations.
 */
class BookmarkCollectionRequest extends FormRequest
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
        $rules = [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
            ],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'][] = Rule::unique('bookmarks', 'collection_name')
                ->where('user_id', $this->user()->id)
                ->ignore($this->route('collection'));
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Collection name is required.',
            'name.max' => 'Collection name must not exceed 50 characters.',
            'name.regex' => 'Collection name can only contain letters, numbers, and underscores.',
            'name.unique' => 'A collection with this name already exists.',
        ];
    }

    /**
     * Get collection name from request.
     */
    public function getName(): string
    {
        return strtolower(trim($this->input('name')));
    }
}
