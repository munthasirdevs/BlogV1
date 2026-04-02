<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class TrackEmailRequest
 *
 * Validates email tracking requests (open/click).
 */
class TrackEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Tracking endpoints are public (called from emails)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscriber_id' => 'required|integer|exists:subscriptions,id',
            'email_id' => 'required|integer|exists:email_trackings,id',
            'link_id' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
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
            'subscriber_id.required' => 'Subscriber ID is required.',
            'subscriber_id.exists' => 'Subscriber not found.',
            'email_id.required' => 'Email tracking ID is required.',
            'email_id.exists' => 'Email tracking record not found.',
            'url.url' => 'Invalid URL format.',
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
}
