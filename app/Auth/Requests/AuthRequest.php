<?php

namespace App\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->is('api/register')) {
            return [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ];
        }

        if ($this->is('api/login')) {
            return [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ];
        }

        if ($this->is('api/user/update')) {
            return [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.string' => 'El nombre debe ser texto',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',

            'email.required' => 'El correo electrónico es obligatorio',
            'email.string' => 'El correo electrónico debe ser texto',
            'email.email' => 'El correo electrónico debe ser válido',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres',
            'email.unique' => 'Este correo electrónico ya está registrado',

            'password.required' => 'La contraseña es obligatoria',
            'password.string' => 'La contraseña debe ser texto',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',

            'profile_image.image' => 'El archivo debe ser una imagen',
            'profile_image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif',
            'profile_image.max' => 'La imagen no puede pesar más de 2MB',
        ];
    }
}
