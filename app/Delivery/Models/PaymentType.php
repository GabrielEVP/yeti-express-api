<?php

namespace App\Delivery\Models;

enum PaymentType: string
{
    case PARTIAL = 'partial';
    case FULL = 'full';
}
