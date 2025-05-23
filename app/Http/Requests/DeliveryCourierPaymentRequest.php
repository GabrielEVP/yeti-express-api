<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryCourierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'method' => [
                'required',
                'string',
                Rule::in(['cash', 'mobile_payment', 'bank_transfer', 'other']),
            ],
            'amount' => 'required|numeric|min:0',
        ];
    }
}

