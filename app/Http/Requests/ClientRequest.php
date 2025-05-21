<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_name' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'phones' => 'array',
            'phones.*.name' => 'required_with:phones|string|max:20',
            'phones.*.phone' => 'required_with:phones|string|max:20',
            'phones.*.type' => 'nullable|string|in:Work,Personal',
            'emails' => 'array',
            'emails.*.email' => 'required_with:emails|string|email|max:255',
            'emails.*.type' => 'nullable|string|in:Work,Personal',
            'addresses' => 'nullable|array',
            'addresses.*.address' => 'required_with:addresses|string|max:255',
            'addresses.*.state' => 'string|max:100',
            'addresses.*.municipality' => 'required_with:addresses|string|max:100',
            'addresses.*.postal_code' => 'required_with:addresses|string|max:20',
        ];
    }
}