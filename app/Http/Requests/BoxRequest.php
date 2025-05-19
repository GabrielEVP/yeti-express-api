<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BoxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'open_time' => ['required', 'date'],
            'close_time' => ['nullable', 'date', 'after:open_time']
        ];
    }
}