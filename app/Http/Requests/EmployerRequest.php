<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:employers,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,manager,courier,viewer'],
            'active' => ['boolean']
        ];
    }
}