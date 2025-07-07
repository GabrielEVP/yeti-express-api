<?php

namespace App\Employee\DTO;

class EmployeeEventFilterDTO
{
    public function __construct(
        public ?string $employeeId = null,
        public ?string $startDate = null,
        public ?string $endDate = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employee_id'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null
        );
    }
}
