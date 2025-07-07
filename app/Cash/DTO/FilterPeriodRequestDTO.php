<?php

namespace App\Cash\DTO;

class FilterPeriodRequestDTO
{
    public string $period;
    public string $date;

    public function __construct(
        string $period = 'day',
        string $date = ''
    ) {
        $this->period = $period;
        $this->date = $date ?: now()->toDateString();
    }

    public static function fromRequest($request): self
    {
        return new self(
            $request->input('period', 'day'),
            $request->input('date', now()->toDateString())
        );
    }

    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'date' => $this->date,
        ];
    }
}
