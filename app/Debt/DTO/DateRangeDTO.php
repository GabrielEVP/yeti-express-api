<?php

namespace App\Debt\DTO;

use JsonSerializable;

class DateRangeDTO implements JsonSerializable
{
    public function __construct(
        public string $startDate,
        public string $endDate
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            startDate: $request->get('start_date') ?: \Carbon\Carbon::today()->format('Y-m-d'),
            endDate: $request->get('end_date') ?: \Carbon\Carbon::today()->format('Y-m-d')
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate
        ];
    }
}
