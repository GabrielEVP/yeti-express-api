<?php

namespace App\Employee\DTO;

use App\Employee\Models\EmployeeEvent;
use Illuminate\Support\Collection;

class EmployeeEventReportDTO
{
    public function __construct(
        public int $id,
        public string $event,
        public string $section,
        public ?string $referenceTable,
        public ?int $referenceId,
        public ?string $message,
        public string $createdAt,
        public string $employeeName
    ) {
    }

    public static function fromModel(EmployeeEvent $event): self
    {
        return new self(
            id: $event->id,
            event: $event->event,
            section: $event->section,
            referenceTable: $event->reference_table,
            referenceId: $event->reference_id,
            message: $event->message,
            createdAt: $event->created_at->format('Y-m-d H:i:s'),
            employeeName: $event->employee?->name ?? 'Unknown'
        );
    }

    public static function fromCollection(Collection $events): array
    {
        return $events->map(fn (EmployeeEvent $event) => self::fromModel($event))->toArray();
    }
}
