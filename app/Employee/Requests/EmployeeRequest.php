<?php

namespace App\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('post') && $this->routeIs('employees.store')) {
            return [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:100', 'unique:employees,email'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', 'in:admin,basic'],
                'active' => ['boolean'],
            ];
        }

        if ($this->isMethod('put') && $this->routeIs('employees.update')) {
            return [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:100', "unique:employees,email,{$this->employee->id}"],
                'role' => ['required', 'in:admin,basic'],
                'active' => ['boolean'],
            ];
        }

        if ($this->isMethod('put') && $this->routeIs('employees.updatePassword')) {
            return [
                'password' => ['required', 'string', 'min:8'],
            ];
        }

        return [];
    }
}
