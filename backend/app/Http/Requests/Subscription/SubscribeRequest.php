<?php

namespace App\Http\Requests\Subscription;

use App\Services\SubscriptionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class SubscribeRequest
 *
 * Validates newsletter subscription requests.
 */
class SubscribeRequest extends FormRequest
{
    /**
     * The subscription service instance.
     */
    protected SubscriptionService $subscriptionService;

    /**
     * Constructor.
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // Check if email has hard bounced or complained
                function ($attribute, $value, $fail) {
                    if (!$this->subscriptionService->canSubscribe($value)) {
                        $fail('This email address cannot be subscribed due to previous delivery issues.');
                    }
                },
            ],
            'preferences' => 'nullable|array',
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
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email address must not exceed 255 characters.',
            'preferences.array' => 'Preferences must be an array.',
            'preferences.frequency.in' => 'Frequency must be one of: instant, daily, weekly, monthly.',
            'preferences.categories.*.exists' => 'One or more categories are invalid.',
            'preferences.content_types.*.in' => 'Invalid content type specified.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
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
}
