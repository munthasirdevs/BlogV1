<?php

namespace App\Http\Requests\Notification;

use App\Models\NotificationPreference;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateNotificationPreferencesRequest
 *
 * Validation for updating user notification preferences.
 */
class UpdateNotificationPreferencesRequest extends FormRequest
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
        $availableTypes = array_keys(NotificationPreference::getAvailableTypes());
        $availableChannels = array_keys(NotificationPreference::getAvailableChannels());

        return [
            'preferences' => ['required', 'array'],
            'preferences.*' => ['array'],
            'preferences.*.enabled' => ['nullable', 'boolean'],
            'preferences.*.channels' => ['nullable', 'array'],
            'preferences.*.channels.*' => ['string', 'in:' . implode(',', $availableChannels)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'Preferences array is required.',
            'preferences.array' => 'Preferences must be an array.',
            'preferences.*.array' => 'Each preference must be an array.',
            'preferences.*.enabled.boolean' => 'Enabled must be a boolean.',
            'preferences.*.channels.array' => 'Channels must be an array.',
            'preferences.*.channels.*.in' => 'Invalid channel specified.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $preferences = $this->input('preferences', []);
            $availableTypes = array_keys(NotificationPreference::getAvailableTypes());

            foreach ($preferences as $type => $data) {
                if (!in_array($type, $availableTypes)) {
                    $validator->errors()->add(
                        "preferences.{$type}",
                        "Invalid notification type: {$type}"
                    );
                }
            }
        });
    }
}
