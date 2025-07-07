<?php

namespace App\CompanyBill\DTO;

use App\CompanyBill\Models\CompanyBill;
use JsonSerializable;

final class CompanyBillDTO implements JsonSerializable
{
    public int $id;
    public string $name;
    public ?string $description;
    public string $date;
    public string $method;
    public float $amount;
    public int $userId;
    public string $created_at;
    public string $updated_at;

    public function __construct(CompanyBill $bill)
    {
        $this->id = $bill->id;
        $this->name = $bill->name;
        $this->description = $bill->description;
        $this->date = $bill->date->format('Y-m-d');
        $this->method = $bill->method->value;
        $this->amount = (float) $bill->amount;
        $this->userId = $bill->user_id;
        $this->created_at = $bill->created_at;
        $this->updated_at = $bill->updated_at;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'date' => $this->date,
            'method' => $this->method,
            'amount' => $this->amount,
            'userId' => $this->userId,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
