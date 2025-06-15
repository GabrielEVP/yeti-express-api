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
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:employees,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,basic'],
            'active' => ['boolean'],
        ];
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
