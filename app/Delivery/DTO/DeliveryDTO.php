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
    public string $pickup_address;
    public string $cancellation_notes;
    public string $notes;
    public string $created_at;
    public string $updated_at;
    public int $service_id;
    public int $client_id;
    public int $courier_id;
    public int $user_id;
    public string $client_legal_name;
    public string $service_name;
    public string $courier_full_name;
    public string $user_name;
    public ?string $debt_id;
    public string $receipt_full_name;
    public string $receipt_phone;
    public string $receipt_address;
    public array $receipt;
    public ?array $anonymous_client = null;

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
        $this->cancellation_notes = $delivery->cancellation_notes ?? '';
        $this->notes = $delivery->notes ?? '';
        $this->created_at = $delivery->created_at->toDateTimeString();
        $this->updated_at = $delivery->updated_at->toDateTimeString();
        $this->service_id = $delivery->service_id;
        $this->client_id = $delivery->client_id;
        $this->courier_id = $delivery->courier_id;
        $this->user_id = $delivery->user_id;
        $this->client_legal_name = $delivery->client?->legal_name;
        $this->service_name = $delivery->service->name;
        $this->courier_full_name = trim($delivery->courier->first_name . ' ' . $delivery->courier->last_name);
        $this->user_name = trim($delivery->user->first_name . ' ' . $delivery->user->last_name);
        $this->debt_id = $delivery->debt?->id;
        $this->receipt_full_name = $delivery->receipt->full_name;
        $this->receipt_phone = $delivery->receipt->phone;
        $this->receipt_address = $delivery->receipt->address;

        $this->receipt = [
            'id' => $delivery->receipt->id,
            'full_name' => $delivery->receipt->full_name,
            'phone' => $delivery->receipt->phone,
            'address' => $delivery->receipt->address,
            'delivery_id' => $delivery->receipt->delivery_id,
        ];

        $this->anonymous_client = $delivery->delivery_anonymous_client
            ? [
                'id' => $delivery->delivery_anonymous_client->id,
                'delivery_id' => $delivery->delivery_anonymous_client->delivery_id,
                'legal_name' => $delivery->delivery_anonymous_client->legal_name,
                'type' => $delivery->delivery_anonymous_client->type?->value,
                'registration_number' => $delivery->delivery_anonymous_client->registration_number,
                'address' => $delivery->delivery_anonymous_client->address,
                'phone' => $delivery->delivery_anonymous_client->phone,
            ]
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'service_id' => $this->service_id,
            'client_id' => $this->client_id,
            'courier_id' => $this->courier_id,
            'user_id' => $this->user_id,
            'client_legal_name' => $this->client_legal_name,
            'service_name' => $this->service_name,
            'courier_full_name' => $this->courier_full_name,
            'user_name' => $this->user_name,
            'debt_id' => $this->debt_id,
            'receipt_full_name' => $this->receipt_full_name,
            'receipt_phone' => $this->receipt_phone,
            'receipt_address' => $this->receipt_address,
            'receipt' => $this->receipt,
            'delivery_anonymous_client' => $this->anonymous_client,
        ];
    }
}
