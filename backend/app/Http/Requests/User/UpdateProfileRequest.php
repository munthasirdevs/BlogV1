<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Class UpdateProfileRequest
 *
 * Validates requests for updating user profile.
 *
 * @OA\Schema(
 *     schema="UpdateProfileRequest",
 *     @OA\Property(property="name", type="string", minLength=2, maxLength=255, example="John Updated"),
 *     @OA\Property(property="bio", type="string", maxLength=500, example="Updated bio..."),
 *     @OA\Property(property="avatar", type="string", format="url", example="https://example.com/new-avatar.jpg"),
 *     @OA\Property(property="website", type="string", format="url", nullable=true),
 *     @OA\Property(property="twitter", type="string", nullable=true),
 *     @OA\Property(property="github", type="string", nullable=true),
 *     @OA\Property(property="linkedin", type="string", nullable=true),
 *     @OA\Property(property="location", type="string", maxLength=100, nullable=true),
 *     @OA\Property(property="timezone", type="string", example="America/Los_Angeles")
 * )
 */
class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'string', 'url'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'twitter' => ['nullable', 'string', 'max:50'],
            'github' => ['nullable', 'string', 'max:50'],
            'linkedin' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'name.min' => 'Name must be at least 2 characters.',
            'bio.max' => 'Bio cannot exceed 500 characters.',
            'website.url' => 'Website must be a valid URL.',
            'timezone.max' => 'Timezone value is too long.',
        ];
    }
}
