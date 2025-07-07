<?php

namespace App\CompanyBill\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'method' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}

