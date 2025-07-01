<?php

namespace App\Debt\Requests;

use App\Debt\DTO\FilterDebtByStatusDTO;
use Illuminate\Foundation\Http\FormRequest;

class FilterDebtByStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:pending,partial_paid,paid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100']
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The status must be one of: pending, partial_paid, paid',
            'page.integer' => 'The page must be an integer',
            'page.min' => 'The page must be at least 1',
            'per_page.integer' => 'The per page value must be an integer',
            'per_page.min' => 'The per page value must be at least 1',
            'per_page.max' => 'The per page value cannot exceed 100',
        ];
    }

    public function toDTO(): FilterDebtByStatusDTO
    {
        return FilterDebtByStatusDTO::fromRequest($this);
    }

    protected function prepareForValidation(): void
    {
        // Validate the client ID from the route parameter
        if (empty($this->route('client'))) {
            $this->validateClientId();
        }
    }

    private function validateClientId(): void
    {
        $validator = validator(['client' => null], [
            'client' => ['required']
        ], [
            'client.required' => 'Client ID is required'
        ]);

        if ($validator->fails()) {
            $this->failedValidation($validator);
        }
    }
}
