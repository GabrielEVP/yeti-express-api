<?php

namespace App\Debt\Helpers;

use App\Debt\Models\Status;

class StatusTranslator
{
    public static function toSpanish(Status $status): string
    {
        return match($status) {
            Status::Pending => 'Pendiente',
            Status::PartialPaid => 'Parcialmente Pagado',
            Status::Paid => 'Pagado',
            default => $status->value,
        };
    }
}
