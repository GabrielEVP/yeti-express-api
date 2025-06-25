<?php

namespace App\Service\DTO;

use JsonSerializable;

final class SimpleServiceDTO implements JsonSerializable
{
    public int $id;
    public string $name;
    public float $amount;
    public float $total_expense;
    public float $total_earning;

    public function __construct(array $data)
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : 0;
        $this->name = $data['name'] ?? '';
        $this->amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $this->total_expense = isset($data['total_expense']) ? (float) $data['total_expense'] : 0;
        $this->total_earning = isset($data['total_earning']) ? (float) $data['total_earning'] : 0;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'total_expense' => $this->total_expense,
            'total_earning' => $this->total_earning,
        ];
    }
}
