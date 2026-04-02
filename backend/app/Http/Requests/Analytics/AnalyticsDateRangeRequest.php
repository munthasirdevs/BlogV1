<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

/**
 * Class AnalyticsDateRangeRequest
 *
 * Validates date range parameters for analytics endpoints.
 */
class AnalyticsDateRangeRequest extends FormRequest
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
                'nullable',
                'date',
                'before_or_equal:end_date',
                'before_or_equal:today',
            ],
            'end_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
                'after_or_equal:start_date',
            ],
            'group_by' => [
                'nullable',
                'string',
                'in:daily,weekly,monthly',
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
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'end_date.before_or_equal' => 'End date cannot be in the future',
            'group_by.in' => 'Group by must be one of: daily, weekly, monthly',
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
            // Validate date range is not too large
            $startDate = $this->get('start_date');
            $endDate = $this->get('end_date');

            if ($startDate && $endDate) {
                try {
                    $start = Carbon::parse($startDate);
                    $end = Carbon::parse($endDate);
                    $days = $start->diffInDays($end);

                    if ($days > 365) {
                        $validator->errors()->add(
                            'start_date',
                            'Date range cannot exceed 365 days. Please use a shorter range.'
                        );
                    }
                } catch (\Exception $e) {
                    // Invalid date format will be caught by date validation
                }
            }
        });
    }

    /**
     * Get validated start date.
     *
     * @return Carbon|null
     */
    public function getStartDate(): ?Carbon
    {
        if ($this->has('start_date')) {
            return Carbon::parse($this->get('start_date'));
        }

        return null;
    }

    /**
     * Get validated end date.
     *
     * @return Carbon|null
     */
    public function getEndDate(): ?Carbon
    {
        if ($this->has('end_date')) {
            return Carbon::parse($this->get('end_date'));
        }

        return null;
    }

    /**
     * Get date range as array.
     *
     * @return array
     */
    public function getDateRange(): array
    {
        return [
            'start_date' => $this->getStartDate(),
            'end_date' => $this->getEndDate(),
        ];
    }
}
