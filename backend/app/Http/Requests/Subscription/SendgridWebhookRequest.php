<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class SendgridWebhookRequest
 *
 * Validates SendGrid Event Webhook requests.
 */
class SendgridWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // In production, verify SendGrid signature
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * SendGrid sends an array of events
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '*.event' => 'required|string|in:bounce,spamreport,unsubscribe,processed,delivered,open,click,dropped,deferred',
            '*.email' => 'required|string|email',
            '*.timestamp' => 'required|integer',
            '*.smtp-id' => 'nullable|string',
            '*.category' => 'nullable|array',
            '*.category.*' => 'string',
            '*.reason' => 'nullable|string',
            '*.type' => 'nullable|string|in:blocked,bounced,spam',
            '*.sg_event_id' => 'nullable|string',
            '*.sg_message_id' => 'nullable|string',
            '*.url' => 'nullable|url',
            '*.ip' => 'nullable|ip',
            '*.useragent' => 'nullable|string',
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
                'message' => 'Invalid webhook payload',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Get all events from the request.
     */
    public function getEvents(): array
    {
        return $this->all();
    }

    /**
     * Get bounce events.
     */
    public function getBounceEvents(): array
    {
        return array_filter($this->all(), fn($e) => $e['event'] === 'bounce');
    }

    /**
     * Get spam complaint events.
     */
    public function getSpamEvents(): array
    {
        return array_filter($this->all(), fn($e) => $e['event'] === 'spamreport');
    }

    /**
     * Get unsubscribe events.
     */
    public function getUnsubscribeEvents(): array
    {
        return array_filter($this->all(), fn($e) => $e['event'] === 'unsubscribe');
    }
}
