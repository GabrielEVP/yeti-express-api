<?php

namespace App\Debt\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DebtPartialPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'debt_id' => 'required|exists:debts,id',
            'amount' => 'required|numeric|gt:0',
            'method' => 'required|string|in:cash,transfer,check,credit_card,debit_card'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'debt_id.required' => 'El ID de la deuda es requerido',
            'debt_id.exists' => 'La deuda seleccionada no existe',
            'amount.required' => 'El monto del pago es requerido',
            'amount.numeric' => 'El monto debe ser un valor numérico',
            'amount.gt' => 'El monto debe ser mayor que cero',
            'method.required' => 'El método de pago es requerido',
            'method.in' => 'El método de pago seleccionado no es válido'
        ];
    }

    /**
     * Convert the request to a DTO.
     *
     * @return \App\Debt\DTO\PartialPaymentRequestDTO
     */
    public function toDTO()
    {
        return new \App\Debt\DTO\PartialPaymentRequestDTO(
            debt_id: $this->input('debt_id'),
            amount: (float) $this->input('amount'),
            method: $this->input('method')
        );
    }
}
