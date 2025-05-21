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
            'payment_type' => ['required', 'in:partial,full'],
            'currency' => ['required', 'in:USD,BOV,OTH'],
            'total' => ['required', 'numeric', 'min:0'],
            'comision' => ['required', 'numeric', 'min:0'],
            'courier_id' => ['required', 'exists:couriers,id'],
            'delivery_date' => ['required', 'date'],
            'open_box_id' => ['nullable', 'exists:box,id'],
            'close_box_id' => ['nullable', 'exists:box,id'],
            'status' => ['required', 'in:pending,in_transit,delivered,cancelled'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.total' => ['required', 'numeric', 'min:0'],
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