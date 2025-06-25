<?php

namespace App\Service\DTO;

use Illuminate\Support\Facades\Auth;
use JsonSerializable;

final readonly class FormRequestServiceDTO implements JsonSerializable
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $amount,
        public float $comision,
        public array $bills = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            amount: (float) $data['amount'],
            comision: (float) $data['comision'],
            bills: array_map(
                fn(array $bill) => [
                    'name' => $bill['name'],
                    'amount' => (float) $bill['amount'],
                ],
                $data['bills'] ?? []
            )
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'comision' => $this->comision,
            'user_id' => Auth::id(),
            'bills' => $this->bills,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
