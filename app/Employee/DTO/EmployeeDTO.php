<?php

namespace App\Employee\DTO;

use App\Employee\Models\Employee;
use JsonSerializable;

class EmployeeDTO implements JsonSerializable
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $email,
        public string $role,
        public int    $user_id,
        public string $created_at,
        public string $updated_at,
        public array  $events = []
    )
    {
    }

    public static function fromModel(Employee $employee): self
    {
        $events = [];

        if ($employee->relationLoaded('events')) {
            foreach ($employee->events as $event) {
                $events[] = [
                    'id' => $event->id,
                    'event' => $event->event,
                    'section' => $event->section,
                    'reference_table' => $event->reference_table,
                    'reference_id' => $event->reference_id,
                    'created_at' => $event->created_at->toDateTimeString(),
                ];
            }
        }

        return new self(
            id: $employee->id,
            name: $employee->name,
            email: $employee->email,
            role: $employee->role,
            user_id: $employee->user_id,
            created_at: $employee->created_at->toDateTimeString(),
            updated_at: $employee->updated_at->toDateTimeString(),
            events: $events
        );
    }

    public static function fromCollection(array $employees): array
    {
        return array_map(fn($employee) => self::fromModel($employee), $employees);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'events' => $this->events
        ];
    }
}


