<?php

namespace App\CompanyBill\DTO;

use App\CompanyBill\Models\CompanyBill;
use JsonSerializable;

class SimpleCompanyBillDTO implements JsonSerializable
{
    public int $id;
    public string $name;
    public string $date;
    public string $method;
    public float $amount;

    public function __construct(CompanyBill $bill)
    {
        $this->id = $bill->id;
        $this->name = $bill->name;
        $this->date = $bill->date->format('Y-m-d');
        $this->method = $bill->method->value;
        $this->amount = (float)$bill->amount;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'date' => $this->date,
            'method' => $this->method,
            'amount' => $this->amount,
        ];
    }
}
