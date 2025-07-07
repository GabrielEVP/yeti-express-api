<?php

namespace App\Debt\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebtFullPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "debt_id" => "required",
            'method' => [
                'required',
                'string',
                Rule::in(['cash', 'mobile_payment', 'bank_transfer', 'other']),
            ],
        ];
    }
}
