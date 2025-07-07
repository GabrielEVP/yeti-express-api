<?php

namespace App\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeEventReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'nullable|exists:employees,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'The selected employee does not exist.',
            'end_date.after_or_equal' => 'The end date must be equal to or after the start date.',
        ];
    }
}
