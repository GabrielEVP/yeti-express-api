<?php

namespace App\Service\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
            "amount" => ["required", "numeric", "min:0"],
            "bills" => ["array"],
            "bills.*.name" => ["required", "string", "max:255"],
            "bills.*.amount" => ["required", "numeric", "min:0"],
        ];
    }
}

