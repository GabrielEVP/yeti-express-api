<?php

namespace App\Debt\Requests;

use App\Debt\DTO\DateRangeDTO;
use Illuminate\Foundation\Http\FormRequest;

class DebtReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date']
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date' => 'The start date must be a valid date',
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date',
            'end_date.date' => 'The end date must be a valid date',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date'
        ];
    }

    public function toDTO(): DateRangeDTO
    {
        return DateRangeDTO::fromRequest($this);
    }
}
