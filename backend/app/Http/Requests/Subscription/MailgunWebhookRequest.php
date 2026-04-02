<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * class MailgunWebhookRequest
 *
 * Validates Mailgun webhook requests (bounce, complaint, etc.).
 */
class MailgunWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // In production, verify Mailgun signature
        // For now, allow all webhook requests
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
            'event' => 'required|string|in:bounced,complained,unsubscribed,delivered,opened,clicked',
            'recipient' => 'required|string|email',
            'message.headers.message-id' => 'nullable|string',
            'delivery-status.message' => 'nullable|string',
            'delivery-status.code' => 'nullable|integer',
            'severity' => 'nullable|string|in:permanent,temporary',
            'reason' => 'nullable|string',
            'tags' => 'nullable|array',
            'user-variables' => 'nullable|array',
            'timestamp' => 'required|numeric',
            'token' => 'nullable|string', // For signature verification
            'signature' => 'nullable|array', // For signature verification
            'signature.token' => 'nullable|string',
            'signature.timestamp' => 'nullable|string',
            'signature.value' => 'nullable|string',
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
     * Get the event type.
     */
    public function getEventType(): string
    {
        return $this->input('event');
    }

    /**
     * Get the recipient email.
     */
    public function getRecipient(): string
    {
        return $this->input('recipient');
    }

    /**
     * Get the message ID.
     */
    public function getMessageId(): ?string
    {
        return $this->input('message.headers.message-id');
    }

    /**
     * Get bounce reason.
     */
    public function getBounceReason(): ?string
    {
        return $this->input('delivery-status.message') ?? $this->input('reason');
    }

    /**
     * Get bounce code.
     */
    public function getBounceCode(): ?int
    {
        return $this->input('delivery-status.code');
    }

    /**
     * Check if bounce is permanent (hard).
     */
    public function isHardBounce(): bool
    {
        return $this->input('severity') === 'permanent' 
            || ($this->getBounceCode() !== null && $this->getBounceCode() >= 500);
    }

    /**
     * Get timestamp.
     */
    public function getTimestamp(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::createFromTimestamp($this->input('timestamp'));
    }
}
