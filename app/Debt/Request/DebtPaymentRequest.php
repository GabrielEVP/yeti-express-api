<?php

namespace App\Debt\Request;

use Illuminate\Foundation\Http\FormRequest;

class DebtPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:255'],
            'client_delivery_debt_id' => ['required', 'exists:client_delivery_debts,id'],
        ];
    }
}
