<?php

namespace App\Debt\Models;

enum Method: string
{
    case Cash = 'cash';
    case MobilePayment = 'mobile_payment';
    case Transfer = 'transfer';
}
