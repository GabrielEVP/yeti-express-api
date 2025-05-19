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
            'client_id' => ['required', 'exists:clients,id'],
            'client_address_id' => ['required', 'exists:client_addresses,id'],
            'payment_id' => ['required', 'exists:payment_types,id'],
            'prices_id' => ['required', 'exists:price_types,id'],
            'courier_id' => ['required', 'exists:couriers,id'],
            'delivery_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'comision' => ['required', 'numeric', 'min:0'],
            'open_box_id' => ['nullable', 'exists:box,id'],
            'close_box_id' => ['nullable', 'exists:box,id'],
            'status' => ['required', 'in:pending,in_transit,delivered,cancelled'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],

            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.full_name' => ['required', 'string'],
            'recipients.*.phone' => ['required', 'string', 'max:20'],
            'recipients.*.id_number' => ['required', 'string'],
            'recipients.*.relationship' => ['required', 'string'],
            'recipients.*.received_at' => ['required', 'date'],
            'recipients.*.signature_url' => ['required', 'string']
        ];
    }
}