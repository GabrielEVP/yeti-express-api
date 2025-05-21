<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'currency' => ['required', 'in:USD,BOV,OTH'],
            'status' => ['required', 'in:pending,in_transit,delivered,cancelled'],
            'payment_type' => ['required', 'in:partial,full'],
            'comision' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'courier_id' => ['required', 'exists:couriers,id'],
            'open_box_id' => ['nullable', 'exists:box,id'],
            'close_box_id' => ['nullable', 'exists:box,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'client_address_id' => ['required', 'exists:client_addresses,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.total' => ['required', 'numeric', 'min:0'],
            'receipt' => ['required', 'array', 'min:1'],
            'receipt.full_name' => ['required', 'string'],
            'receipt.phone' => ['required', 'string', 'max:20'],
            'receipt.address' => ['required', 'string', 'max:255'],
            'receipt.state' => ['required', 'string', 'max:100'],
            'receipt.municipality' => ['required', 'string', 'max:100'],
            'receipt.postal_code' => ['required', 'string', 'max:20']
        ];
    }
}