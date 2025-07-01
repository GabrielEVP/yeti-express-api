<?php

namespace App\Debt\DTO;

use App\Debt\Models\DebtPayment;
use Illuminate\Support\Collection;
use JsonSerializable;

class DebtPaymentCollectionDTO implements JsonSerializable
{
    /**
     * @var DebtPaymentDTO[]
     */
    public array $payments;

    public function __construct(array $payments)
    {
        $this->payments = $payments;
    }

    public static function fromCollection(Collection $payments): self
    {
        $paymentDTOs = $payments->map(function (DebtPayment $payment) {
            return DebtPaymentDTO::fromModel($payment);
        })->toArray();

        return new self($paymentDTOs);
    }

    public static function fromArray(array $payments): self
    {
        $paymentDTOs = array_map(function (DebtPayment $payment) {
            return DebtPaymentDTO::fromModel($payment);
        }, $payments);

        return new self($paymentDTOs);
    }

    public function jsonSerialize(): array
    {
        return $this->payments;
    }
}
