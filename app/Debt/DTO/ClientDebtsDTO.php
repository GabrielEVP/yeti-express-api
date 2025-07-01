<?php

namespace App\Debt\DTO;

use App\Client\Models\Client;
use JsonSerializable;

class ClientDebtsDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $legalName,
        public string $registrationNumber,
        public array $debts = []
    ) {}

    public static function fromModel(Client $client): self
    {
        $debts = [];

        foreach ($client->debts as $debt) {
            $debtData = [
                'id' => $debt->id,
                'amount' => (float) $debt->amount,
                'status' => $debt->status,
                'created_at' => $debt->created_at->format('Y-m-d H:i:s'),
                'payments' => []
            ];

            if ($debt->relationLoaded('payments')) {
                foreach ($debt->payments as $payment) {
                    $debtData['payments'][] = [
                        'id' => $payment->id,
                        'date' => $payment->date->format('Y-m-d'),
                        'amount' => (float) $payment->amount,
                        'method' => $payment->method
                    ];
                }
            }

            if ($debt->relationLoaded('delivery') && $debt->delivery) {
                $debtData['delivery'] = [
                    'id' => $debt->delivery->id,
                    'number' => $debt->delivery->number,
                    'date' => $debt->delivery->date?->format('Y-m-d'),
                    'amount' => (float) $debt->delivery->amount,
                    'status' => $debt->delivery->status,
                    'payment_status' => $debt->delivery->payment_status
                ];

                if ($debt->delivery->relationLoaded('service') && $debt->delivery->service) {
                    $debtData['delivery']['service'] = [
                        'id' => $debt->delivery->service->id,
                        'name' => $debt->delivery->service->name
                    ];
                }
            }

            $debts[] = $debtData;
        }

        return new self(
            id: $client->id,
            legalName: $client->legal_name,
            registrationNumber: $client->registration_number,
            debts: $debts
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'legal_name' => $this->legalName,
            'registration_number' => $this->registrationNumber,
            'debts' => $this->debts
        ];
    }
}
