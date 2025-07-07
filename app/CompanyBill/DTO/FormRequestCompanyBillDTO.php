<?php

namespace App\CompanyBill\DTO;

use App\CompanyBill\Models\Method;
use DateTime;
use Illuminate\Support\Facades\Auth;
use JsonSerializable;

readonly class FormRequestCompanyBillDTO implements JsonSerializable
{
    public function __construct(
        public DateTime $date,
        public string $name,
        public ?string $description,
        public Method $method,
        public float $amount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            date: new DateTime($data['date']),
            name: $data['name'],
            description: $data['description'] ?? null,
            method: Method::from($data['method']),
            amount: (float) $data['amount'],
        );
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'name' => $this->name,
            'description' => $this->description,
            'method' => $this->method->value,
            'amount' => $this->amount,
            'user_id' => Auth::id(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
