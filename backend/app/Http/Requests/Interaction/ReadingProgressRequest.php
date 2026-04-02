<?php

namespace App\Http\Requests\Interaction;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ReadingProgressRequest
 *
 * Form request for reading progress tracking.
 */
class ReadingProgressRequest extends FormRequest
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
            'percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'time_spent' => ['nullable', 'integer', 'min:0'], // Time in seconds
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
            'percentage.required' => 'Reading percentage is required.',
            'percentage.integer' => 'Percentage must be a whole number.',
            'percentage.min' => 'Percentage cannot be less than 0.',
            'percentage.max' => 'Percentage cannot be more than 100.',
            'time_spent.integer' => 'Time spent must be a whole number.',
            'time_spent.min' => 'Time spent cannot be negative.',
        ];
    }

    /**
     * Get percentage from request.
     */
    public function getPercentage(): int
    {
        return (int) $this->input('percentage');
    }

    /**
     * Get time spent from request.
     */
    public function getTimeSpent(): ?int
    {
        return $this->input('time_spent');
    }
}
