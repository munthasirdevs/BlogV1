<?php

namespace App\Http\Requests\User;

use App\Rules\EmailDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Class UpdateUserRequest
 *
 * Validates requests for updating existing users (admin only).
 *
 * @OA\Schema(
 *     schema="UpdateUserRequest",
 *     @OA\Property(property="name", type="string", minLength=2, maxLength=255, example="John Updated"),
 *     @OA\Property(property="email", type="string", format="email", example="john.updated@example.com"),
 *     @OA\Property(property="bio", type="string", maxLength=500),
 *     @OA\Property(property="avatar", type="string", format="url"),
 *     @OA\Property(property="role", type="string", enum={"user", "editor", "moderator", "admin"}),
 *     @OA\Property(property="status", type="string", enum={"active", "banned", "suspended"}),
 *     @OA\Property(property="website", type="string", format="url", nullable=true),
 *     @OA\Property(property="twitter", type="string", nullable=true),
 *     @OA\Property(property="github", type="string", nullable=true),
 *     @OA\Property(property="linkedin", type="string", nullable=true),
 *     @OA\Property(property="location", type="string", maxLength=100, nullable=true),
 *     @OA\Property(property="timezone", type="string", example="America/New_York")
 * )
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');
        $allowedDomains = config('blog.allowed_email_domains', []);

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'string', 'url'],
            'role' => ['nullable', 'string', Rule::in(['user', 'editor', 'moderator', 'admin'])],
            'status' => ['nullable', 'string', Rule::in(['active', 'banned', 'suspended'])],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'twitter' => ['nullable', 'string', 'max:50'],
            'github' => ['nullable', 'string', 'max:50'],
            'linkedin' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ];

        // Add email domain restriction if configured
        if (!empty($allowedDomains)) {
            $rules['email'][] = new EmailDomain($allowedDomains);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name.',
            'name.min' => 'Name must be at least 2 characters.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.in' => 'Invalid role selected.',
            'status.in' => 'Invalid status selected.',
            'website.url' => 'Website must be a valid URL.',
        ];
    }
}
