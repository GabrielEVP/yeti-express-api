<?php

namespace App\Client\DTO;

final class FormRequestClientDTO implements \JsonSerializable
{
    public function __construct(
        public string $legal_name,
        public string $registration_number,
        public string $notes,
        public array  $addresses,
        public array  $emails,
        public array  $phones,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            legal_name: $data['legal_name'] ?? '',
            registration_number: $data['registration_number'] ?? '',
            notes: $data['notes'] ?? '',
            addresses: array_map(
                fn(array $address) => [
                    'address' => $address['address'] ?? '',
                ],
                $data['addresses'] ?? []
            ),
            emails: array_map(
                fn(array $email) => [
                    'email' => $email['email'] ?? '',
                ],
                $data['emails'] ?? []
            ),
            phones: array_map(
                fn(array $phone) => [
                    'phone' => $phone['phone'] ?? '',
                ],
                $data['phones'] ?? []
            )
        );
    }

    public function toArray(): array
    {
        return [
            'legal_name' => $this->legal_name,
            'registration_number' => $this->registration_number,
            'notes' => $this->notes,
            'phones' => $this->phones,
            'emails' => $this->emails,
            'addresses' => $this->addresses,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
