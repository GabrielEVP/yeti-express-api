<?php

namespace App\Courier\DTO;

use App\Courier\Models\Courier;
use JsonSerializable;

class CourierDTO implements JsonSerializable
{
    public int $id;
    public string $first_name;
    public ?string $last_name;
    public ?string $phone;
    public string $created_at;
    public string $updated_at;

    public function __construct(Courier $courier)
    {
        $this->id = $courier->id;
        $this->first_name = $courier->first_name;
        $this->last_name = $courier->last_name;
        $this->phone = $courier->phone;
        $this->created_at = $courier->created_at->toDateTimeString();
        $this->updated_at = $courier->updated_at->toDateTimeString();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
