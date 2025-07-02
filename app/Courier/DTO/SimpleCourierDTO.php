<?php

namespace App\Courier\DTO;

use JsonSerializable;

class SimpleCourierDTO implements JsonSerializable
{
    public int $id;
    public string $first_name;
    public ?string $last_name;
    public ?string $phone;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->first_name = $data['first_name'];
        $this->last_name = $data['last_name'];
        $this->phone = $data['phone'];
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
        ];
    }

    public static function mapFromArray($row): self
    {
        return new self($row->toArray());
    }
}
