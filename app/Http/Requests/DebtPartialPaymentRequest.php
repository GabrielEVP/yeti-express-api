<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebtPartialPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "debt_id" => "required",
            'amount' => 'required|numeric|min:0',
            'method' => [
                'required',
                'string',
                Rule::in(['cash', 'mobile_payment', 'bank_transfer', 'other']),
            ],
        ];
    }
}

