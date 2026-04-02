<?php

namespace App\Http\Requests\Subscription;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class UpdatePreferencesRequest
 *
 * Validates subscription preference update requests.
 */
class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User can update their own subscription
        // Or admin can update any subscription
        $user = $this->user();
        
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Check if subscription belongs to user
        $subscriptionId = $this->route('id') ?? $this->input('subscription_id');
        if ($subscriptionId && $user) {
            $subscription = Subscription::find($subscriptionId);
            return $subscription && $subscription->user_id === $user->id;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscription_id' => 'nullable|integer|exists:subscriptions,id',
            'preferences' => 'required|array',
            'preferences.new_posts' => 'nullable|boolean',
            'preferences.weekly_digest' => 'nullable|boolean',
            'preferences.daily_digest' => 'nullable|boolean',
            'preferences.monthly_digest' => 'nullable|boolean',
            'preferences.frequency' => 'nullable|string|in:instant,daily,weekly,monthly',
            'preferences.categories' => 'nullable|array',
            'preferences.categories.*' => 'integer|exists:categories,id',
            'preferences.content_types' => 'nullable|array',
            'preferences.content_types.*' => 'string|in:articles,tutorials,news,updates',
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
            'subscription_id.exists' => 'Subscription not found.',
            'preferences.required' => 'Preferences are required.',
            'preferences.array' => 'Preferences must be an array.',
            'preferences.frequency.in' => 'Frequency must be one of: instant, daily, weekly, monthly.',
            'preferences.categories.*.exists' => 'One or more categories are invalid.',
            'preferences.content_types.*.in' => 'Invalid content type specified.',
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
     * Get validated preferences.
     */
    public function getPreferences(): array
    {
        return $this->input('preferences', []);
    }

    /**
     * Get subscription ID.
     */
    public function getSubscriptionId(): ?int
    {
        return $this->input('subscription_id') ?? $this->route('id');
    }
}
