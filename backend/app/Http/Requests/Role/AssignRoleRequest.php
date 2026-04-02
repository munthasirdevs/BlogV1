<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AssignRoleRequest
 *
 * Validation rules for assigning roles to a user.
 */
class AssignRoleRequest extends FormRequest
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
            'roles' => [
                'required',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'required',
                'string',
                'exists:roles,name',
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
            'roles.required' => 'At least one role must be specified.',
            'roles.min' => 'At least one role must be specified.',
            'roles.*.exists' => 'One or more specified roles do not exist.',
            'roles.*.distinct' => 'Duplicate roles are not allowed.',
        ];
    }
}
