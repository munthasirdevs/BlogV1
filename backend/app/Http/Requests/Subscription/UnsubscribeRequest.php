<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class UnsubscribeRequest
 *
 * Validates newsletter unsubscription requests.
 */
class UnsubscribeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required_without:token|string|email|max:255',
            'token' => 'required_without:email|string|max:64',
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
            'email.required_without' => 'Email address or token is required.',
            'email.email' => 'Please provide a valid email address.',
            'token.required_without' => 'Email address or token is required.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Get email or token for unsubscription.
     */
    public function getIdentifier(): ?string
    {
        return $this->input('email') ?? $this->input('token');
    }

    /**
     * Check if using token.
     */
    public function isUsingToken(): bool
    {
        return $this->filled('token');
    }
}
