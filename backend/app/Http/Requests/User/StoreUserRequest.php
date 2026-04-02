<?php

namespace App\Http\Requests\User;

use App\Rules\EmailDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Class StoreUserRequest
 *
 * Validates requests for creating new users (admin only).
 *
 * @OA\Schema(
 *     schema="StoreUserRequest",
 *     required={"name", "email", "password"},
 *     @OA\Property(property="name", type="string", minLength=2, maxLength=255, example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="password", type="string", format="password", minLength=8, example="SecurePass123!"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123!"),
 *     @OA\Property(property="bio", type="string", maxLength=500, example="Software developer..."),
 *     @OA\Property(property="avatar", type="string", format="url", example="https://example.com/avatar.jpg"),
 *     @OA\Property(property="role", type="string", enum={"user", "editor", "moderator", "admin"}, example="user"),
 *     @OA\Property(property="website", type="string", format="url", nullable=true),
 *     @OA\Property(property="twitter", type="string", nullable=true),
 *     @OA\Property(property="github", type="string", nullable=true),
 *     @OA\Property(property="linkedin", type="string", nullable=true),
 *     @OA\Property(property="location", type="string", maxLength=100, nullable=true),
 *     @OA\Property(property="timezone", type="string", example="UTC")
 * )
 */
class StoreUserRequest extends FormRequest
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
        $allowedDomains = config('blog.allowed_email_domains', []);

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'string', 'url'],
            'role' => ['nullable', 'string', 'in:user,editor,moderator,admin'],
            'status' => ['nullable', 'string', 'in:active,banned,suspended'],
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
            'password.required' => 'Please enter a password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.in' => 'Invalid role selected.',
            'status.in' => 'Invalid status selected.',
            'website.url' => 'Website must be a valid URL.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default role if not provided
        if (!isset($this->role)) {
            $this->merge(['role' => 'user']);
        }

        // Default status if not provided
        if (!isset($this->status)) {
            $this->merge(['status' => 'active']);
        }

        // Default timezone if not provided
        if (!isset($this->timezone)) {
            $this->merge(['timezone' => 'UTC']);
        }
    }
}
