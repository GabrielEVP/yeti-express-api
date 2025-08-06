<?php

namespace App\Delivery\Request;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "date" => ["required", "date_format:Y-m-d"],
            "notes" => ["nullable", "string"],
            "service_id" => ["required", "exists:services,id"],
            "courier_id" => ["required", "exists:couriers,id"],
            "pickup_address" => ["required", "max:200"],
            "receipt.full_name" => ["required", "string"],
            "receipt.phone" => ["required", "string", "max:20"],
            "receipt.address" => ["required", "string", "max:255"],
        ];

        if ($this->has('client_id') && !empty($this->input('client_id')) && $this->input('client_id') !== '0' && $this->input('client_id') !== 0) {
            $rules['client_id'] = ['required', 'exists:clients,id'];
            $rules['payment_type'] = ["required", "in:partial,full"];
        } else {
            $this->merge(['client_id' => null]);
            $rules['anonymous_client.legal_name'] = ['required', 'string'];
            $rules['anonymous_client.type'] = ['required', 'string'];
            $rules['anonymous_client.registration_number'] = ['required', 'string'];
            $rules['anonymous_client.phone'] = ['required', 'string'];
        }

        return $rules;
    }
}
