<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AssignPermissionsRequest
 *
 * Validation rules for assigning permissions to a role.
 */
class AssignPermissionsRequest extends FormRequest
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
            'permissions' => [
                'required',
                'array',
                'min:1',
            ],
            'permissions.*' => [
                'required',
                'string',
                'exists:permissions,name',
                'distinct',
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
            'permissions.required' => 'At least one permission must be specified.',
            'permissions.min' => 'At least one permission must be specified.',
            'permissions.*.exists' => 'One or more specified permissions do not exist.',
            'permissions.*.distinct' => 'Duplicate permissions are not allowed.',
        ];
    }
}
