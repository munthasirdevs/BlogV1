<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ExportAnalyticsRequest
 *
 * Validates parameters for analytics export endpoint.
 */
class ExportAnalyticsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->hasAnyRole(['admin', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => [
                'required',
                'date',
                'before_or_equal:end_date',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'format' => [
                'nullable',
                'string',
                'in:json,csv',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
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
            'start_date.required' => 'Start date is required for export',
            'end_date.required' => 'End date is required for export',
            'format.in' => 'Format must be one of: json, csv',
            'email.email' => 'Please provide a valid email address',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $startDate = $this->get('start_date');
            $endDate = $this->get('end_date');

            if ($startDate && $endDate) {
                try {
                    $start = new \DateTime($startDate);
                    $end = new \DateTime($endDate);
                    $diff = $start->diff($end);
                    $days = $diff->days;

                    // For CSV exports, limit to 90 days without async processing
                    if ($this->get('format') === 'csv' && $days > 90) {
                        $validator->errors()->add(
                            'start_date',
                            'CSV export is limited to 90 days. For larger ranges, please use JSON format or contact support.'
                        );
                    }
                } catch (\Exception $e) {
                    // Invalid date format will be caught by date validation
                }
            }
        });
    }

    /**
     * Get validated format.
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->get('format', 'json');
    }
}
