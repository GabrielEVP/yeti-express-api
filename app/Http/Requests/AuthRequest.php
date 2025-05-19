<?php

namespace App\Http\Requests;

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
}
