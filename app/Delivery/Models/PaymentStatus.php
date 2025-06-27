<?php

namespace App\Delivery\Models;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PARTIAL_PAID = 'partial_paid';
    case PAID = 'paid';
}
