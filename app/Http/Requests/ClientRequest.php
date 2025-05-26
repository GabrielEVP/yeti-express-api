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
            'phones.*.phone' => 'required_with:phones|string|max:20',
            'emails' => 'array',
            'emails.*.email' => 'required_with:emails|string|email|max:255',
            'addresses' => 'nullable|array',
            'addresses.*.address' => 'required_with:addresses|string|max:255',
        ];
    }
}