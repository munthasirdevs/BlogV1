<?php

namespace App\Http\Requests\Notification;

use App\Models\NotificationPreference;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ListNotificationsRequest
 *
 * Validation for listing user notifications.
 */
class ListNotificationsRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'read_status' => ['nullable', 'string', 'in:read,unread'],
            'type' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'page.min' => 'Page number must be at least 1.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
            'read_status.in' => 'Read status must be either "read" or "unread".',
        ];
    }
}
