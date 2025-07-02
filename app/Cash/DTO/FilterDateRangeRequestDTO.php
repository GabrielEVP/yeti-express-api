<?php

namespace App\Cash\DTO;

class FilterDateRangeRequestDTO
{
    public string $startDate;
    public string $endDate;

    public function __construct(
        string $startDate = '',
        string $endDate = ''
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public static function fromRequest($request): self
    {
        return new self(
            $request->get('start_date') ?? '',
            $request->get('end_date') ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];
    }
}
