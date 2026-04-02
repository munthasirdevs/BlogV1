<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Class UpdatePasswordRequest
 *
 * Validates requests for updating user password.
 *
 * @OA\Schema(
 *     schema="UpdatePasswordRequest",
 *     required={"current_password", "password", "password_confirmation"},
 *     @OA\Property(property="current_password", type="string", format="password", example="OldPass123!"),
 *     @OA\Property(property="password", type="string", format="password", minLength=8, example="NewSecurePass123!"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="NewSecurePass123!")
 * )
 */
class UpdatePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string'],
            'password' => [
                'required', 
                'confirmed', 
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password.',
            'current_password' => 'Your current password is incorrect.',
            'password.required' => 'Please enter a new password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.uncompromised' => 'The given password has appeared in a data leak. Please choose a different password.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verify current password is correct
            if ($this->user() && !\Illuminate\Support\Facades\Hash::check($this->current_password, $this->user()->password)) {
                $validator->errors()->add('current_password', 'Your current password is incorrect.');
            }

            // Prevent reusing recent passwords (optional - could be enhanced)
            if ($this->user() && \Illuminate\Support\Facades\Hash::check($this->password, $this->user()->password)) {
                $validator->errors()->add('password', 'New password must be different from your current password.');
            }
        });
    }
}
