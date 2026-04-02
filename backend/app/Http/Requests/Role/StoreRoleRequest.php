<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreRoleRequest
 *
 * Validation rules for creating a new role.
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:roles,name',
                'regex:/^[a-z_-]+$/', // Only lowercase letters, underscores, and hyphens
            ],
            'permissions' => [
                'array',
            ],
            'permissions.*' => [
                'string',
                'exists:permissions,name',
            ],
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
            'name.regex' => 'Role name must contain only lowercase letters, underscores, and hyphens.',
            'name.unique' => 'A role with this name already exists.',
        ];
    }
}
