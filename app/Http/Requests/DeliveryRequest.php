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
            "payment_type" => ["required", "in:partial,full"],
            "notes" => ["nullable", "string"],
            "service_id" => ["required", "exists:services,id"],
            "courier_id" => ["required", "exists:couriers,id"],
            "client_id" => ["required", "exists:clients,id"],
            "pickup_address" => ["required", "max:100"],
            "receipt.full_name" => ["required", "string"],
            "receipt.phone" => ["required", "string", "max:20"],
            "receipt.address" => ["required", "string", "max:255"],
        ];
    }
}
