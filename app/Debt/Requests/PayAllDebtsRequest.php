<?php

namespace App\Debt\Requests;

use App\Debt\DTO\ClientPaymentRequestDTO;
use Illuminate\Foundation\Http\FormRequest;

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
            'pay.method' => ['required', 'string', 'in:cash,credit_card,bank_transfer,check']
        ];
    }

    public function toDTO(): ClientPaymentRequestDTO
    {
        $payData = $this->input('pay');

        return new ClientPaymentRequestDTO(
            clientId: $payData['clientId'],
            method: $payData['method']
        );
    }
}
