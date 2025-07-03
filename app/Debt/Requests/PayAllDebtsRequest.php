<?php

namespace App\Debt\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayAllDebtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pay' => ['required', 'array'],
            'pay.clientId' => ['required', 'integer', 'exists:clients,id'],
            'pay.method' => [
                'required',
                'string',
                Rule::in(['cash', 'mobile_payment', 'bank_transfer', 'other']),
            ],
        ];
    }
}
