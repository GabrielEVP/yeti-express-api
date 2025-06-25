<?php

namespace App\Service\DTO;

use App\Service\Models\Service;
use JsonSerializable;

final class ServiceDTO implements JsonSerializable
{
    public int $id;
    public string $name;
    public ?string $description;
    public ?float $commission;
    public float $amount;
    public int $userId;
    public string $created_at;
    public string $updated_at;
    public float $total_expense;
    public float $total_earning;

    public array $bills = [];
    public array $events = [];

    public function __construct(Service $service)
    {
        $this->id = $service->id;
        $this->name = $service->name;
        $this->description = $service->description;
        $this->commission = $service->comision !== null ? (float)$service->comision : null;
        $this->amount = (float)$service->amount;
        $this->userId = $service->user_id;
        $this->created_at = $service->created_at->toDateTimeString();
        $this->updated_at = $service->updated_at->toDateTimeString();
        $this->total_expense = (float)$service->total_expense ?? 0;
        $this->total_earning = (float)$service->total_earning ?? 0;

        foreach ($service->bills ?? [] as $bill) {
            $this->bills[] = [
                'id' => $bill->id,
                'name' => $bill->name,
                'amount' => (float)$bill->amount,
            ];
        }

        foreach ($service->events ?? [] as $event) {
            $this->events[] = [
                'id' => $event->id,
                'event' => $event->event,
                'section' => $event->section,
                'reference_id' => $event->reference_id,
                'created_at' => $event->created_at->toDateTimeString(),
            ];
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'commission' => $this->commission,
            'amount' => $this->amount,
            'userId' => $this->userId,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'total_expense' => $this->total_expense,
            'total_earning' => $this->total_earning,
            'bills' => $this->bills,
            'events' => $this->events,
        ];
    }
}

