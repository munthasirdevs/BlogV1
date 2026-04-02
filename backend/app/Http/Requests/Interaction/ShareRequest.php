<?php

namespace App\Http\Requests\Interaction;

use App\Models\PostShare;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class ShareRequest
 *
 * Form request for share operations.
 */
class ShareRequest extends FormRequest
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
            'provider' => [
                'required',
                'string',
                Rule::in(PostShare::AVAILABLE_PROVIDERS),
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
            'provider.required' => 'Share provider is required.',
            'provider.in' => 'Invalid share provider. Must be one of: ' . implode(', ', PostShare::AVAILABLE_PROVIDERS),
        ];
    }

    /**
     * Get provider from request.
     */
    public function getProvider(): string
    {
        return $this->input('provider');
    }
}
