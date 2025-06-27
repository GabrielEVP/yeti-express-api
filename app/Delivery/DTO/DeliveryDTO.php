<?php

namespace App\Delivery\DTO;

use App\Delivery\Models\Delivery;
use App\Delivery\Models\PaymentStatus;
use App\Delivery\Models\PaymentType;
use App\Delivery\Models\Status;
use JsonSerializable;

final class DeliveryDTO implements JsonSerializable
{
    public int $id;
    public string $number;
    public ?string $date;
    public ?Status $status;
    public ?PaymentType $payment_type;
    public ?PaymentStatus $payment_status;
    public float $amount;
    public ?string $pickup_address;
    public ?string $cancellation_notes;
    public ?string $notes;
    public ?int $service_id;
    public ?int $client_id;
    public ?int $courier_id;
    public ?int $user_id;
    public string $created_at;
    public string $updated_at;
    public ?string $client_legal_name;
    public ?string $service_name;
    public ?string $courier_full_name;

    public function __construct(Delivery $delivery)
    {
        $this->id = $delivery->id;
        $this->number = $delivery->number;
        $this->date = $delivery->date?->toDateString();
        $this->status = $delivery->status;
        $this->payment_type = $delivery->payment_type;
        $this->payment_status = $delivery->payment_status;
        $this->amount = (float)$delivery->amount;
        $this->pickup_address = $delivery->pickup_address;
        $this->cancellation_notes = $delivery->cancellation_notes;
        $this->notes = $delivery->notes;
        $this->service_id = $delivery->service_id;
        $this->client_id = $delivery->client_id;
        $this->courier_id = $delivery->courier_id;
        $this->user_id = $delivery->user_id;
        $this->created_at = $delivery->created_at->toDateTimeString();
        $this->updated_at = $delivery->updated_at->toDateTimeString();
        $this->client_legal_name = $delivery->client?->legal_name;
        $this->service_name = $delivery->service?->name;
        $this->courier_full_name = $delivery->courier
            ? trim($delivery->courier->first_name . ' ' . $delivery->courier->last_name)
            : null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'date' => $this->date,
            'status' => $this->status?->value,
            'payment_type' => $this->payment_type?->value,
            'payment_status' => $this->payment_status?->value,
            'amount' => $this->amount,
            'pickup_address' => $this->pickup_address,
            'cancellation_notes' => $this->cancellation_notes,
            'notes' => $this->notes,
            'service_id' => $this->service_id,
            'client_id' => $this->client_id,
            'courier_id' => $this->courier_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'client_legal_name' => $this->client_legal_name,
            'service_name' => $this->service_name,
            'courier_full_name' => $this->courier_full_name,
        ];
    }
}
