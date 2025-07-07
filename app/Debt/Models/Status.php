<?php

namespace App\Debt\Models;

enum Status: string
{
    case Pending = 'pending';
    case PartialPaid = 'partial_paid';
    case Paid = 'paid';
}
