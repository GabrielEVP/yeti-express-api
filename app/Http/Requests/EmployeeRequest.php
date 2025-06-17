<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;

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
                'confirmPassword' => ['required', 'same:password'],
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
                'confirmPassword' => ['required', 'same:password'],
            ];
        }

        return [];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            $email = $this->input('email');
            $userId = $this->user()?->id;

            if ($email && $userId) {
                $userEmail = DB::table('users')->where('id', $userId)->value('email');

                if ($userEmail && $email === $userEmail) {
                    $validator->errors()->add('email', 'El correo del empleado no puede ser igual al del usuario autenticado.');
                }
            }
        });
    }
}
