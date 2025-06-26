<?php

namespace App\Courier\DTO;

use JsonSerializable;

class FormRequestCourierDTO implements JsonSerializable
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $phone,
        public string $active,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            first_name: $data['first_name'],
            last_name: $data['last_name'],
            phone: $data['phone'],
            active: true,
        );
    }

    public function toArray(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'active' => $this->active,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
