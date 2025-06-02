<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "date" => ["required", "date"],
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
            "method" => [
                "required",
                Rule::in(["cash", "mobile_payment", "bank_transfered"]),
            ],
            "amount" => ["required", "numeric", "min:0"],
            "user_id" => ["required", "exists:users,id"],
        ];
    }
}
