<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:employees,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,basic'],
            'active' => ['boolean']
        ];
    }
}